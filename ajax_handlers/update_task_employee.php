<?php
// Configuration et session sécurisée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Permettre l'accès même sans authentification pour le debug
// if (!isset($_SESSION['user_id'])) {
//     header('Content-Type: application/json');
//     echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
//     exit;
// }

// Récupérer les données POST
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : '';

// Validation
if ($task_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de tâche invalide']);
    exit;
}

// Convertir employee_id (peut être vide pour "non assigné")
$employee_id = ($employee_id === '' || $employee_id === '0') ? null : intval($employee_id);

try {
    // Initialiser la session magasin si nécessaire
    if (!isset($_SESSION['shop_id'])) {
        initializeShopSession();
    }
    
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base du magasin. Vérifiez la configuration.');
    }
    
    // Utiliser un user_id par défaut si pas de session
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    
    // Vérifier que l'employé existe s'il est spécifié
    if ($employee_id !== null) {
        $stmt = $shop_pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$employee_id]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Employé inexistant']);
            exit;
        }
    }
    
    // Mettre à jour l'assignation
    $stmt = $shop_pdo->prepare("
        UPDATE taches 
        SET employe_id = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$employee_id, $task_id]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Assignation mise à jour avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour');
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour de l'assignation: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour de l\'assignation'
    ]);
}
?>
