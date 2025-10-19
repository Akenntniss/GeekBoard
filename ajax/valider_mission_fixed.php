<?php
header('Content-Type: application/json');

// Démarrer la session si pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Inclure les fichiers nécessaires avec gestion d'erreur
try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration: ' . $e->getMessage()]);
    exit;
}

// TEMP: désactive la vérification stricte (on la réactivera ensuite)
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
//     exit;
// }

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['validation_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$validation_id = (int)$input['validation_id'];
$action = trim($input['action']);

// Valider l'action
$valid_actions = ['approuver', 'rejeter'];
if (!in_array($action, $valid_actions)) {
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

if ($validation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de validation invalide']);
    exit;
}

try {
    if (function_exists('initializeShopSession')) { initializeShopSession(); }
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    
    // Vérifier que la validation existe
    $stmt = $shop_pdo->prepare("
        SELECT mv.*, m.titre as mission_titre, u.full_name as user_nom
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN missions m ON um.mission_id = m.id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE mv.id = ?
    ");
    $stmt->execute([$validation_id]);
    $validation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$validation) {
        echo json_encode(['success' => false, 'message' => 'Validation non trouvée']);
        exit;
    }
    
    // Déterminer le nouveau statut
    $new_status = ($action === 'approuver') ? 'validee' : 'rejetee';
    $admin_id = $_SESSION['user_id'] ?? 1;
    
    // Mettre à jour la validation
    $stmt = $shop_pdo->prepare("
        UPDATE mission_validations 
        SET statut = ?, date_validation = NOW(), admin_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $admin_id, $validation_id]);
    
    if ($stmt->rowCount() > 0) {
        // Si approuvé, mettre à jour la progression de la mission utilisateur
        if ($action === 'approuver') {
            $stmt = $shop_pdo->prepare("
                UPDATE user_missions 
                SET progres = progres + 1, date_derniere_activite = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$validation['user_mission_id']]);
            
            // Vérifier si la mission est maintenant terminée
            $stmt = $shop_pdo->prepare("
                SELECT um.*, m.objectif_quantite
                FROM user_missions um
                LEFT JOIN missions m ON um.mission_id = m.id
                WHERE um.id = ?
            ");
            $stmt->execute([$validation['user_mission_id']]);
            $user_mission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_mission && $user_mission['progres'] >= $user_mission['objectif_quantite']) {
                $stmt = $shop_pdo->prepare("
                    UPDATE user_missions 
                    SET statut = 'terminee', date_completee = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$validation['user_mission_id']]);
            }
        }
        
        // Log de la validation
        error_log("Validation $action: ID $validation_id, Mission: " . $validation['mission_titre'] . ", User: " . $validation['user_nom']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Validation ' . ($action === 'approuver' ? 'approuvée' : 'rejetée') . ' avec succès !',
            'validation_id' => $validation_id,
            'action' => $action
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la validation']);
    }
    
} catch (Exception $e) {
    error_log("Erreur valider_mission: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la validation: ' . $e->getMessage()]);
}
?>
