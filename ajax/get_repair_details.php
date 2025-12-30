<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Initialiser la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion des fichiers de configuration
require_once __DIR__ . '/../config/database.php';

// Connexion à la base de données via la fonction centralisée
try {
    $shop_pdo = getShopDBConnection();
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de connexion DB: ' . $e->getMessage()
    ]);
    exit;
}

// Récupérer l'ID depuis GET ou POST
$id = 0;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
} elseif (isset($_POST['id'])) {
    $id = intval($_POST['id']);
}

// Vérifier que l'ID est valide
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de réparation manquant ou invalide']);
    exit;
}

try {
    // Récupérer les détails de la réparation avec les infos client, statut et créateur
    $sql = "
        SELECT 
            r.*, 
            c.nom as client_nom, 
            c.prenom as client_prenom, 
            c.telephone as client_telephone, 
            c.email as client_email,
            s.nom as statut_nom, 
            sc.couleur as statut_couleur,
            u.username as created_by_username,
            u.full_name as created_by_name,
            DATE_FORMAT(r.date_reception, '%d/%m/%Y à %H:%i') as date_creation_formatted
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut = s.code
        LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
        LEFT JOIN users u ON r.cree_par = u.id
        WHERE r.id = :id
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        echo json_encode(['success' => false, 'message' => 'Réparation non trouvée']);
        exit;
    }
    
    // Normalisation du champ prix
    if (!isset($reparation['montant'])) {
        if (isset($reparation['prix_reparation'])) {
            $reparation['montant'] = $reparation['prix_reparation'];
        } elseif (isset($reparation['prix'])) {
            $reparation['montant'] = $reparation['prix'];
        } elseif (isset($reparation['price'])) {
            $reparation['montant'] = $reparation['price'];
        } else {
            $reparation['montant'] = 0;
        }
    }
    
    // Formatage des données pour JSON (UTF-8 safe)
    $reparation = array_map(function($value) {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'UTF-8') : $value;
    }, $reparation);
    
    // Récupérer les photos associées
    $photos = [];
    try {
        // Vérifier quelle table de photos existe
        $table_photos = 'photos_reparation';
        $check_table = $shop_pdo->query("SHOW TABLES LIKE 'photos_reparation'");
        if ($check_table->rowCount() === 0) {
            $check_table_s = $shop_pdo->query("SHOW TABLES LIKE 'photos_reparations'");
            if ($check_table_s->rowCount() > 0) {
                $table_photos = 'photos_reparations';
            } else {
                $table_photos = null;
            }
        }
        
        if ($table_photos) {
            $stmt_photos = $shop_pdo->prepare("SELECT * FROM $table_photos WHERE reparation_id = :id ORDER BY id DESC");
            $stmt_photos->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_photos->execute();
            $photos = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);
            
            // Normaliser les chemins des photos
            foreach ($photos as &$photo) {
                if (isset($photo['url']) && !empty($photo['url'])) {
                    // Si le chemin est relatif (commence par ../), le nettoyer
                    if (strpos($photo['url'], '../') === 0) {
                        $photo['url'] = substr($photo['url'], 3); // Enlever le ../
                    }
                    // S'assurer que le chemin commence par / si ce n'est pas une URL absolue
                    if (!preg_match('/^http/', $photo['url']) && strpos($photo['url'], '/') !== 0) {
                        $photo['url'] = '/' . $photo['url'];
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Erreur récupération photos: " . $e->getMessage());
        // On continue même si erreur photos
    }
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'repair' => $reparation,
        'photos' => $photos
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur SQL: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
