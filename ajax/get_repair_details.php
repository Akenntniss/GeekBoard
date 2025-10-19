<?php
// Désactiver l'affichage des erreurs PHP pour la production
// mais les logger pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Démarrage de get_repair_details.php");

// Démarrer la session pour avoir accès à l'ID du magasin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'ID du magasin depuis les paramètres POST ou GET
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
    error_log("ID du magasin récupéré depuis la requête: $shop_id_from_request");
}

// Afficher les infos de session pour le débogage
error_log("Session ID: " . session_id());
error_log("Session shop_id: " . ($_SESSION['shop_id'] ?? 'non défini'));
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'non défini'));
error_log("Toute la session: " . print_r($_SESSION, true));

// S'assurer que nous envoyons du JSON
header('Content-Type: application/json');

require_once('../config/database.php');

// IMPORTANT: Forcer la connexion à la base du magasin et non la base principale
// car les réparations sont uniquement dans la base du magasin
// Utiliser la nouvelle fonction si disponible, sinon fallback
$shop_id = $shop_id_from_request ?? $_SESSION['shop_id'] ?? null;
if (function_exists('getShopDBConnectionById') && $shop_id) {
    $shop_pdo = getShopDBConnectionById($shop_id);
    error_log("Utilisation de getShopDBConnectionById avec shop_id: $shop_id");
} else {
$shop_pdo = getShopDBConnection();
    error_log("Utilisation de getShopDBConnection (fallback)");
}

// Vérifier quelle base de données nous utilisons réellement
try {
    $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
    $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Base de données connectée: " . ($db_info['current_db'] ?? 'Inconnue'));
} catch (Exception $e) {
    error_log("Erreur lors de la vérification de la base: " . $e->getMessage());
}

// Vérifier si la connexion à la base de données est établie
if (!isset($shop_pdo) || $shop_pdo === null) {
    error_log("Erreur: Connexion à la base de données non établie dans get_repair_details.php");
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    error_log("Erreur: ID de réparation non fourni dans get_repair_details.php");
    echo json_encode([
        'success' => false,
        'error' => 'ID de réparation non fourni'
    ]);
    exit;
}

$repair_id = (int)$_GET['id'];
error_log("Récupération des détails pour la réparation ID: $repair_id");

try {
    // Vérifier si la table reparations existe
    try {
        $tables_check = $shop_pdo->query("SHOW TABLES LIKE 'reparations'");
        if ($tables_check->rowCount() === 0) {
            error_log("ERREUR: Table 'reparations' inexistante dans la base " . ($db_info['current_db'] ?? 'Inconnue'));
            // Lister les tables disponibles
            $all_tables = $shop_pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            error_log("Tables disponibles: " . implode(', ', $all_tables));
            throw new Exception("Table 'reparations' introuvable dans la base de données");
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification des tables: " . $e->getMessage());
    }

    // Requête améliorée pour inclure le nom et la couleur du statut, l'active_repair_id de l'utilisateur et les informations de garantie
    $sql = "
        SELECT 
            r.*, 
            c.nom as client_nom, 
            c.prenom as client_prenom, 
            c.telephone as client_telephone, 
            c.email as client_email,
            s.nom as statut_nom,       -- Récupérer le nom du statut depuis la table statuts
            sc.couleur as statut_couleur, -- Récupérer la couleur depuis la table statut_categories
            u.active_repair_id,        -- Récupérer l'ID de la réparation active de l'utilisateur connecté
            g.id as garantie_id,       -- ID de la garantie
            g.date_debut as garantie_debut,
            g.date_fin as garantie_fin,
            g.statut as garantie_statut,
            g.duree_jours as garantie_duree,
            g.description_garantie as garantie_description,
            CASE 
                WHEN g.id IS NULL THEN 'aucune'
                WHEN g.statut = 'annulee' THEN 'annulee'
                WHEN g.date_fin < NOW() THEN 'expiree'
                WHEN DATEDIFF(g.date_fin, NOW()) <= 7 THEN 'expire_bientot'
                ELSE 'active'
            END as garantie_etat
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut = s.code -- Joindre avec la table statuts sur le code
        LEFT JOIN statut_categories sc ON s.categorie_id = sc.id -- Joindre avec la table statut_categories pour la couleur
        LEFT JOIN users u ON u.id = ? -- Joindre avec l'utilisateur connecté
        LEFT JOIN garanties g ON r.id = g.reparation_id -- Joindre avec la table garanties
        WHERE r.id = ?
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    if (!$stmt) {
        $error = $shop_pdo->errorInfo();
        error_log("Erreur de préparation SQL: " . json_encode($error));
        throw new PDOException("Erreur lors de la préparation de la requête: " . $error[2]);
    }
    
    // Récupérer l'ID de l'utilisateur connecté (depuis la session ou les paramètres)
    $user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    error_log("get_repair_details.php - user_id: $user_id (source: " . ($_SESSION['user_id'] ? 'session' : ($_GET['user_id'] ? 'GET' : ($_POST['user_id'] ? 'POST' : 'aucune'))) . "), repair_id: $repair_id");
    
    $success = $stmt->execute([$user_id, $repair_id]);
    if (!$success) {
        $error = $stmt->errorInfo();
        error_log("Erreur d'exécution SQL: " . json_encode($error));
        throw new PDOException("Erreur lors de l'exécution de la requête: " . $error[2]);
    }
    
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log des données récupérées pour debug
    error_log("get_repair_details.php - Données récupérées: " . json_encode([
        'id' => $repair['id'] ?? null,
        'employe_id' => $repair['employe_id'] ?? null,
        'active_repair_id' => $repair['active_repair_id'] ?? null
    ]));
    
    if (!$repair) {
        error_log("Réparation non trouvée avec ID: $repair_id");
        
        // Vérifier si la réparation existe directement - recherche simple
        try {
            $check_repair = $shop_pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE id = ?");
            $check_repair->execute([$repair_id]);
            $exists = $check_repair->fetch(PDO::FETCH_ASSOC);
            error_log("Vérification directe - Nombre de réparations avec ID $repair_id: " . ($exists['count'] ?? 0));
            
            // Liste des ID de réparations proches pour aider au débogage
            $ids_query = $shop_pdo->query("SELECT id FROM reparations ORDER BY id DESC LIMIT 10");
            $ids = $ids_query->fetchAll(PDO::FETCH_COLUMN);
            error_log("IDs de réparations disponibles: " . implode(', ', $ids));
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification directe: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => false,
            'error' => 'Réparation non trouvée'
        ]);
        exit;
    }
    
    error_log("Détails de réparation récupérés avec succès pour ID: $repair_id");
    
    // Vérifier si cette réparation est la réparation active de l'utilisateur connecté
    // Requête séparée pour s'assurer que l'information est correcte
    try {
        $active_check_stmt = $shop_pdo->prepare("SELECT active_repair_id FROM users WHERE id = ?");
        $active_check_stmt->execute([$user_id]);
        $user_active_repair = $active_check_stmt->fetchColumn();
        
        // Gérer les cas où fetchColumn retourne false (pas de ligne trouvée) ou null
        if ($user_active_repair === false || $user_active_repair === null) {
            $repair['active_repair_id'] = null;
        } else {
            $repair['active_repair_id'] = (int)$user_active_repair; // Convertir en entier
        }
        
        error_log("get_repair_details.php - Vérification active_repair_id séparée: user_id=$user_id, active_repair_id_raw=$user_active_repair, active_repair_id_final=" . json_encode($repair['active_repair_id']) . ", repair_id=$repair_id");
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de l'active_repair_id: " . $e->getMessage());
        $repair['active_repair_id'] = null;
    }
    
    // Formater les dates
    $repair['date_reception'] = $repair['date_reception'] ? date('d/m/Y H:i', strtotime($repair['date_reception'])) : null;
    $repair['date_debut'] = $repair['date_debut'] ? date('d/m/Y H:i', strtotime($repair['date_debut'])) : null;
    $repair['date_fin'] = $repair['date_fin'] ? date('d/m/Y H:i', strtotime($repair['date_fin'])) : null;
    $repair['date_modification'] = $repair['date_modification'] ? date('d/m/Y H:i', strtotime($repair['date_modification'])) : null;
    
    // Formater le prix
    if (isset($repair['prix_reparation'])) {
        $repair['prix_reparation_formatte'] = number_format((float)$repair['prix_reparation'], 2, ',', ' ');
    }
    
    // Tenter de récupérer les photos mais continuer même en cas d'échec
    $photos = [];
    try {
        $photos_sql = "SELECT * FROM photos_reparation WHERE reparation_id = ? ORDER BY date_upload DESC";
        error_log("Exécution de la requête SQL: $photos_sql avec ID: $repair_id");
        
        $photos_stmt = $shop_pdo->prepare($photos_sql);
        $photos_stmt->execute([$repair_id]);
        $photos = $photos_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Photos récupérées: " . count($photos));
        if (count($photos) > 0) {
            foreach ($photos as $index => $photo) {
                error_log("Photo #$index - ID: {$photo['id']}, URL: {$photo['url']}");
            }
        } else {
            error_log("Aucune photo trouvée pour la réparation ID: $repair_id");
            
            // Essayer avec une autre requête si aucune photo n'est trouvée
            $alt_photos_sql = "SHOW TABLES LIKE 'photos_reparation%'";
            $alt_stmt = $shop_pdo->query($alt_photos_sql);
            $tables = $alt_stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Tables similaires trouvées: " . implode(', ', $tables));
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des photos: " . $e->getMessage());
    }
    
    // Préparer les données de réponse
    $response = [
        'success' => true,
        'repair' => $repair,
        'photos' => $photos
    ];
    
    echo json_encode($response);
    error_log("Réponse JSON envoyée avec succès");
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans get_repair_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des détails: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur inattendue dans get_repair_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue: ' . $e->getMessage()
    ]);
} 