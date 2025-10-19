<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction pour enregistrer les logs de débogage
function debug_log($message) {
    $log_file = __DIR__ . '/relance_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

debug_log("Début de traitement client_relance.php");
debug_log("GET: " . json_encode($_GET));
debug_log("POST: " . json_encode($_POST));

// Inclure la configuration de la base de données et les fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

// S'assurer que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

debug_log("SESSION: " . json_encode($_SESSION));

// Vérifier que la requête est bien en POST et au format JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

debug_log("Données JSON reçues: " . $json_data);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
    exit;
}

// Récupérer l'ID du magasin depuis différentes sources
$shop_id = null;

// 1. Vérifier dans l'URL (méthode GET)
if (isset($_GET['shop_id'])) {
    $shop_id = (int)$_GET['shop_id'];
    debug_log("ID du magasin trouvé dans l'URL: $shop_id");
}

// 2. Vérifier dans le corps de la requête JSON
if (!$shop_id && isset($data['shop_id'])) {
    $shop_id = (int)$data['shop_id'];
    debug_log("ID du magasin trouvé dans le corps JSON: $shop_id");
}

// 3. Vérifier dans la session
if (!$shop_id && isset($_SESSION['shop_id'])) {
    $shop_id = (int)$_SESSION['shop_id'];
    debug_log("ID du magasin trouvé dans la session: $shop_id");
}

// Si un ID de magasin a été trouvé, le mettre dans la session
if ($shop_id) {
    $_SESSION['shop_id'] = $shop_id;
    debug_log("ID du magasin $shop_id stocké en session");
}

// Vérifier que les données nécessaires sont présentes
if (!isset($data['action']) || !isset($data['days'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

// Nettoyer les données
$action = cleanInput($data['action']);
$days = (int)$data['days'];
$selectedFilters = isset($data['selectedFilters']) ? $data['selectedFilters'] : [];

debug_log("Action: $action, Days: $days, Filtres sélectionnés: " . json_encode($selectedFilters));

// Vérifier que l'action est valide
if (!in_array($action, ['preview', 'send'])) {
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

// Récupérer les IDs des clients sélectionnés (pour l'action 'send')
$selectedClientIds = isset($data['clientIds']) ? $data['clientIds'] : [];
debug_log("Client IDs sélectionnés: " . json_encode($selectedClientIds));

// Vérifier que la connexion à la base de données du magasin est disponible
try {
    debug_log("Vérification de la connexion à la base de données");
    
    if ($shop_pdo === null) {
        throw new Exception("Impossible d'établir une connexion à la base de données");
    }
    debug_log("Connexion shop_pdo disponible");
} catch (Exception $e) {
    debug_log("Erreur de connexion à la base de données: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit;
}

// Récupérer l'ID du modèle de SMS pour la relance client
try {
    debug_log("Recherche du modèle SMS de relance client");
    $template_stmt = $shop_pdo->prepare("SELECT id, contenu FROM sms_templates WHERE nom = 'Relance client' AND est_actif = 1 LIMIT 1");
    $template_stmt->execute();
    $sms_template = $template_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sms_template) {
        debug_log("Modèle SMS de relance non trouvé");
        echo json_encode(['success' => false, 'message' => 'Modèle de SMS de relance non trouvé']);
        exit;
    }
    
    debug_log("Modèle SMS trouvé: ID " . $sms_template['id']);
} catch (PDOException $e) {
    debug_log("Erreur lors de la récupération du modèle de SMS: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du modèle de SMS']);
    exit;
}

// Récupérer les réparations selon les filtres sélectionnés
try {
    debug_log("Construction de la requête SQL pour les filtres: " . json_encode($selectedFilters));
    
    // Vérifier qu'au moins un filtre est sélectionné
    if (empty($selectedFilters)) {
        debug_log("Aucun filtre sélectionné");
        echo json_encode(['success' => false, 'message' => 'Aucun filtre sélectionné']);
        exit;
    }
    
    // D'abord, vérifions les réparations avec les statuts demandés (sans restriction de jours)
    debug_log("=== DIAGNOSTIC COMPLET ===");
    debug_log("Filtres sélectionnés: " . json_encode($selectedFilters));
    debug_log("Jours minimum: $days");
    
    // Test 1: Compter toutes les réparations
    $test_sql = "SELECT COUNT(*) as total FROM reparations";
    $test_stmt = $shop_pdo->prepare($test_sql);
    $test_stmt->execute();
    $total_reparations = $test_stmt->fetchColumn();
    debug_log("Total réparations dans la base: $total_reparations");
    
    // Test 2: Vérifier les statuts disponibles
    $statuts_sql = "SELECT DISTINCT statut, COUNT(*) as count FROM reparations GROUP BY statut";
    $statuts_stmt = $shop_pdo->prepare($statuts_sql);
    $statuts_stmt->execute();
    $statuts_disponibles = $statuts_stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Statuts disponibles dans la base: " . json_encode($statuts_disponibles));
    
    // Test 3: Compter les réparations avec les statuts sélectionnés (sans autres restrictions)
    $count_sql = "SELECT COUNT(*) as count FROM reparations r WHERE r.statut IN (" . implode(',', array_fill(0, count($selectedFilters), '?')) . ")";
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($selectedFilters);
    $count_with_status = $count_stmt->fetchColumn();
    debug_log("Réparations avec statuts sélectionnés (sans restriction): $count_with_status");
    
    // Test 4: Compter avec date_modification NOT NULL
    $count_date_sql = "SELECT COUNT(*) as count FROM reparations r WHERE r.statut IN (" . implode(',', array_fill(0, count($selectedFilters), '?')) . ") AND r.date_modification IS NOT NULL";
    $count_date_stmt = $shop_pdo->prepare($count_date_sql);
    $count_date_stmt->execute($selectedFilters);
    $count_with_date = $count_date_stmt->fetchColumn();
    debug_log("Réparations avec statuts ET date_modification: $count_with_date");
    
    // Test 5: Compter avec restriction des jours
    $count_days_sql = "SELECT COUNT(*) as count FROM reparations r WHERE r.statut IN (" . implode(',', array_fill(0, count($selectedFilters), '?')) . ") AND r.date_modification IS NOT NULL AND DATEDIFF(NOW(), r.date_modification) >= ?";
    $count_days_stmt = $shop_pdo->prepare($count_days_sql);
    $count_days_params = array_merge($selectedFilters, [$days]);
    $count_days_stmt->execute($count_days_params);
    $count_with_days = $count_days_stmt->fetchColumn();
    debug_log("Réparations avec statuts ET date ET jours >= $days: $count_with_days");
    
    // Test 6: Voir quelques exemples de réparations avec les statuts
    $sample_sql = "SELECT r.id, r.statut, r.date_modification, DATEDIFF(NOW(), r.date_modification) as days_since, c.nom, c.prenom FROM reparations r JOIN clients c ON r.client_id = c.id WHERE r.statut IN (" . implode(',', array_fill(0, count($selectedFilters), '?')) . ") LIMIT 5";
    $sample_stmt = $shop_pdo->prepare($sample_sql);
    $sample_stmt->execute($selectedFilters);
    $sample_reparations = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Exemples de réparations trouvées: " . json_encode($sample_reparations));
    
    // Construire la requête SQL finale
    $sql = "
        SELECT r.id, r.client_id, r.statut, r.type_appareil, r.modele, 
               r.date_modification, r.date_reception, c.nom as client_nom, c.prenom as client_prenom, 
               c.telephone, c.email,
               DATEDIFF(NOW(), r.date_modification) as days_since
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.statut IN (" . implode(',', array_fill(0, count($selectedFilters), '?')) . ")
        AND r.date_modification IS NOT NULL 
        AND DATEDIFF(NOW(), r.date_modification) >= ?
        AND r.id NOT IN (
            SELECT reparation_id 
            FROM sms_logs 
            WHERE reparation_id IS NOT NULL 
            AND date_envoi > DATE_SUB(NOW(), INTERVAL 7 DAY)
        )
    ";
    
    debug_log("=== REQUÊTE SQL CONSTRUITE ===");
    debug_log("Nombre de placeholders pour statuts: " . count($selectedFilters));
    debug_log("Statuts sélectionnés: " . implode(', ', $selectedFilters));
    debug_log("Jours demandés: " . $days);
    
    // Si c'est une action d'envoi et que des IDs de clients sont spécifiés, les utiliser
    if ($action === 'send' && !empty($selectedClientIds)) {
        $sql .= " AND r.id IN (" . implode(',', array_fill(0, count($selectedClientIds), '?')) . ")";
    }
    
    $sql .= " ORDER BY days_since DESC";
    
    debug_log("Requête SQL: $sql");
    
    $stmt = $shop_pdo->prepare($sql);
    
    // Préparer les paramètres : filtres + days + éventuellement IDs clients
    $params = array_merge($selectedFilters, [$days]);
    if ($action === 'send' && !empty($selectedClientIds)) {
        $params = array_merge($params, $selectedClientIds);
    }
    
    debug_log("=== PARAMÈTRES PRÉPARÉS ===");
    debug_log("Filtres originaux: " . json_encode($selectedFilters));
    debug_log("Jours: " . $days);
    debug_log("Paramètres finaux: " . json_encode($params));
    debug_log("Nombre de paramètres: " . count($params));
    
    debug_log("=== EXÉCUTION REQUÊTE FINALE ===");
    debug_log("Requête SQL finale: " . $sql);
    debug_log("Paramètres d'exécution: " . json_encode($params));
    
    $stmt->execute($params);
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Nombre de clients trouvés par la requête finale: " . count($clients));
    
    if (count($clients) > 0) {
        debug_log("Premiers clients trouvés: " . json_encode(array_slice($clients, 0, 2)));
    } else {
        debug_log("Aucun client trouvé - vérification de la dernière étape (exclusion SMS)");
        
        // Test sans exclusion SMS pour voir si c'est ça qui bloque
        $sql_no_sms = "
            SELECT r.id, r.client_id, r.statut, r.type_appareil, r.modele, 
                   r.date_modification, r.date_reception, c.nom as client_nom, c.prenom as client_prenom, 
                   c.telephone, c.email,
                   DATEDIFF(NOW(), r.date_modification) as days_since
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            WHERE r.statut IN (" . implode(',', array_fill(0, count($selectedFilters), '?')) . ")
            AND r.date_modification IS NOT NULL 
            AND DATEDIFF(NOW(), r.date_modification) >= ?
        ";
        
        $stmt_no_sms = $shop_pdo->prepare($sql_no_sms);
        $stmt_no_sms->execute($params);
        $clients_no_sms = $stmt_no_sms->fetchAll(PDO::FETCH_ASSOC);
        debug_log("Clients trouvés SANS exclusion SMS: " . count($clients_no_sms));
        
        if (count($clients_no_sms) > 0) {
            debug_log("Le problème vient de l'exclusion SMS - premiers clients sans exclusion: " . json_encode(array_slice($clients_no_sms, 0, 2)));
        }
    }
    
    if (empty($clients)) {
        debug_log("Aucun client trouvé");
        echo json_encode(['success' => true, 'clients' => [], 'count' => 0]);
        exit;
    }
} catch (PDOException $e) {
    debug_log("Erreur lors de la récupération des clients: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des clients: ' . $e->getMessage()]);
    exit;
}

// Si c'est juste un aperçu, retourner la liste des clients
if ($action === 'preview') {
    debug_log("Envoi de la réponse d'aperçu avec " . count($clients) . " clients");
    echo json_encode(['success' => true, 'clients' => $clients, 'count' => count($clients)]);
    exit;
}

// Si on est ici, c'est qu'on doit envoyer les SMS
$sms_sent = 0;
$errors = [];

// Inclure la fonction SMS unifiée
if (!function_exists('send_sms')) {
    require_once __DIR__ . '/../includes/sms_functions.php';
}

debug_log("Début de l'envoi des SMS pour " . count($clients) . " clients via API Gateway");

foreach ($clients as $client) {
    debug_log("Traitement du client ID: {$client['client_id']}, Nom: {$client['client_nom']} {$client['client_prenom']}");
    
    // Vérifier que le client a un numéro de téléphone
    if (empty($client['telephone'])) {
        $error_msg = "Client #{$client['client_id']} ({$client['client_nom']} {$client['client_prenom']}) n'a pas de numéro de téléphone";
        debug_log($error_msg);
        $errors[] = $error_msg;
        continue;
    }
    
    // Préparer le message en remplaçant les variables
    $message = $sms_template['contenu'];
    
    // Adapter le message en fonction du statut de la réparation
    if ($client['statut'] === 'en_attente_accord_client') {
        // Message pour les devis en attente
        $message = "Bonjour [CLIENT_PRENOM] [CLIENT_NOM], votre devis pour [APPAREIL_TYPE] [APPAREIL_MODELE] est prêt. Merci de nous donner votre accord. A bientôt !";
    } elseif ($client['statut'] === 'reparation_effectue') {
        // Message pour les réparations terminées
        $message = "Bonjour [CLIENT_PRENOM] [CLIENT_NOM], votre [APPAREIL_TYPE] [APPAREIL_MODELE] est réparé et disponible dans notre boutique. A bientôt !";
    } elseif ($client['statut'] === 'reparation_annule') {
        // Message pour les réparations annulées
        $message = "Bonjour [CLIENT_PRENOM] [CLIENT_NOM], concernant votre [APPAREIL_TYPE] [APPAREIL_MODELE], merci de passer récupérer votre appareil. A bientôt !";
    }
    
    $message = str_replace('[CLIENT_NOM]', $client['client_nom'], $message);
    $message = str_replace('[CLIENT_PRENOM]', $client['client_prenom'], $message);
    $message = str_replace('[APPAREIL_TYPE]', $client['type_appareil'], $message);
    $message = str_replace('[APPAREIL_MODELE]', $client['modele'], $message);
    $message = str_replace('[REPARATION_ID]', $client['id'], $message);
    
    debug_log("Message préparé: $message");
    
    $telephone = $client['telephone'];
    debug_log("Numéro de téléphone: $telephone");
    
    // Envoyer le SMS via la nouvelle API Gateway
    try {
        // Déterminer le type de référence selon le statut
        if ($client['statut'] === 'en_attente_accord_client') {
            $reference_type = 'relance_devis';
        } elseif ($client['statut'] === 'reparation_effectue') {
            $reference_type = 'relance_reparation_terminee';
        } elseif ($client['statut'] === 'reparation_annule') {
            $reference_type = 'relance_reparation_annulee';
        } else {
            $reference_type = 'relance_reparation';
        }
        $reference_id = $client['id'];
        
        $sms_result = send_sms($telephone, $message, $reference_type, $reference_id, $_SESSION['user_id'] ?? 1);
        
        debug_log("Résultat envoi SMS: " . json_encode($sms_result));
        
        if (isset($sms_result['success']) && $sms_result['success']) {
            debug_log("SMS envoyé avec succès via API Gateway pour le client ID {$client['client_id']}");
            $sms_sent++;
        } else {
            $error_msg = "Erreur lors de l'envoi du SMS: " . ($sms_result['message'] ?? 'Erreur inconnue');
            debug_log($error_msg);
            $errors[] = "Erreur lors de l'envoi du SMS à {$client['client_nom']} {$client['client_prenom']}: " . $error_msg;
        }
    } catch (Exception $e) {
        $error_msg = "Exception lors de l'envoi du SMS: " . $e->getMessage();
        debug_log($error_msg);
        $errors[] = "Erreur lors de l'envoi du SMS à {$client['client_nom']} {$client['client_prenom']}: " . $error_msg;
    }
}

debug_log("Fin de traitement: $sms_sent SMS envoyés, " . count($errors) . " erreurs");

// Retourner le résultat
echo json_encode([
    'success' => true,
    'count' => $sms_sent,
    'errors' => $errors,
    'message' => "$sms_sent SMS de relance envoyés avec succès." . (count($errors) > 0 ? " " . count($errors) . " erreurs rencontrées." : "")
]);
exit; 