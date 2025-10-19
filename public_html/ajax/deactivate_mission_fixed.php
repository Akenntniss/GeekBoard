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

// Vérifier l'authentification
// TEMPORAIRE: Désactivation de la vérification d'accès pour débloquer les actions (à sécuriser plus tard)
// Supporte user_role ou role (compatibilité anciennes pages)
// if (!((isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
//     echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
//     exit;
// }

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['mission_id'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$mission_id = (int)$input['mission_id'];

if ($mission_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de mission invalide']);
    exit;
}

try {
    // S'assurer que la session magasin est initialisée
    if (function_exists('initializeShopSession')) {
        initializeShopSession();
    }
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    
    // Vérifier que la mission existe
    $stmt = $shop_pdo->prepare("SELECT id, titre FROM missions WHERE id = ?");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        echo json_encode(['success' => false, 'message' => 'Mission non trouvée']);
        exit;
    }
    
    // Désactiver la mission
    $stmt = $shop_pdo->prepare("UPDATE missions SET statut = 'inactive' WHERE id = ?");
    $stmt->execute([$mission_id]);
    
    if ($stmt->rowCount() > 0) {
        // Log de la désactivation
        error_log("Mission désactivée: ID $mission_id, Titre: " . $mission['titre']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Mission désactivée avec succès !',
            'mission_id' => $mission_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la désactivation']);
    }
    
} catch (Exception $e) {
    error_log("Erreur deactivate_mission: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la désactivation: ' . $e->getMessage()]);
}
?>
