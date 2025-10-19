<?php
// Endpoint AJAX dédié pour les actions des missions
header('Content-Type: application/json');

// Inclure la configuration de session
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['shop_id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$user_id = $_SESSION['user_id'];
$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'accepter_mission':
            $mission_id = (int) $_POST['mission_id'];
            
            if (!$mission_id) {
                echo json_encode(['success' => false, 'message' => 'ID de mission invalide']);
                exit;
            }
            
            // Vérifier si la mission n'est pas déjà prise
            $stmt = $shop_pdo->prepare("SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ?");
            $stmt->execute([$user_id, $mission_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Mission déjà acceptée']);
                exit;
            }
            
            // Vérifier que la mission existe et est active
            $stmt = $shop_pdo->prepare("SELECT id, titre FROM missions WHERE id = ? AND statut = 'active' AND actif = 1");
            $stmt->execute([$mission_id]);
            $mission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$mission) {
                echo json_encode(['success' => false, 'message' => 'Mission non trouvée ou inactive']);
                exit;
            }
            
            // Accepter la mission
            $stmt = $shop_pdo->prepare("
                INSERT INTO user_missions (user_id, mission_id, date_rejointe, statut, progres)
                VALUES (?, ?, NOW(), 'en_cours', 0)
            ");
            $stmt->execute([$user_id, $mission_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Mission "' . htmlspecialchars($mission['titre']) . '" acceptée avec succès !'
            ]);
            break;
            
        case 'valider_tache':
            $user_mission_id = (int) $_POST['user_mission_id'];
            $description = trim($_POST['description'] ?? '');
            
            if (!$user_mission_id) {
                echo json_encode(['success' => false, 'message' => 'ID de mission utilisateur invalide']);
                exit;
            }
            
            if (empty($description)) {
                echo json_encode(['success' => false, 'message' => 'Veuillez décrire la tâche accomplie']);
                exit;
            }
            
            // Récupérer les informations de la mission
            $stmt = $shop_pdo->prepare("
                SELECT um.mission_id, um.progres, m.nombre_taches, m.titre
                FROM user_missions um 
                JOIN missions m ON um.mission_id = m.id 
                WHERE um.id = ? AND um.user_id = ?
            ");
            $stmt->execute([$user_mission_id, $user_id]);
            $mission_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$mission_info) {
                echo json_encode(['success' => false, 'message' => 'Mission non trouvée ou non autorisée']);
                exit;
            }
            
            $progres_actuel = $mission_info['progres'];
            $tache_numero = $progres_actuel + 1; // Prochaine tâche
            
            // Gérer l'upload de la photo
            $photo_url = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/missions/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo_filename = 'mission_' . $user_mission_id . '_' . time() . '.' . $file_extension;
                $photo_path = $upload_dir . $photo_filename;
                
                // Vérifier le type de fichier
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé (JPG, PNG, GIF uniquement)']);
                    exit;
                }
                
                // Déplacer le fichier uploadé
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    $photo_url = '/uploads/missions/' . $photo_filename;
                }
            }
            
            // Insérer la validation
            $stmt = $shop_pdo->prepare("
                INSERT INTO mission_validations (user_mission_id, tache_numero, description, photo_url, date_soumission, statut)
                VALUES (?, ?, ?, ?, NOW(), 'en_attente')
            ");
            $stmt->execute([$user_mission_id, $tache_numero, $description, $photo_url]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Tâche soumise pour validation ! Mission: ' . htmlspecialchars($mission_info['titre'])
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erreur AJAX missions: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
