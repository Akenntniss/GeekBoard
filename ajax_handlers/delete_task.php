<?php
// Configuration et session sécurisée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier l'ID de la tâche
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de tâche invalide']);
    exit;
}

$task_id = intval($_POST['id']);

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
    
    // Supprimer la tâche
    $stmt = $shop_pdo->prepare("DELETE FROM taches WHERE id = ?");
    $stmt->execute([$task_id]);
    
    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Tâche supprimée avec succès'
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Tâche non trouvée'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la suppression de la tâche: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression de la tâche'
    ]);
}
?>

