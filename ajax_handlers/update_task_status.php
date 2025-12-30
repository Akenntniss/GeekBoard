<?php
// Configuration et session sécurisée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/task_logger.php';

// Permettre l'accès même sans authentification pour le debug
// if (!isset($_SESSION['user_id'])) {
//     header('Content-Type: application/json');
//     echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
//     exit;
// }

// Récupérer les données POST
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validation
if ($task_id <= 0 || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Valider le statut
$valid_statuses = ['a_faire', 'en_cours', 'termine'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

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
    
    // Récupérer le statut actuel avant la mise à jour
    $stmt = $shop_pdo->prepare("SELECT statut FROM taches WHERE id = ?");
    $stmt->execute([$task_id]);
    $current_task = $stmt->fetch(PDO::FETCH_ASSOC);
    $statut_avant = $current_task ? $current_task['statut'] : null;
    
    // Mettre à jour le statut
    $stmt = $shop_pdo->prepare("
        UPDATE taches 
        SET statut = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$status, $task_id]);
    
    if ($result) {
        // Enregistrer le log selon le type d'action
        $action_type = 'changement_statut';
        $details = null;
        
        if ($status === 'en_cours' && $statut_avant !== 'en_cours') {
            $action_type = 'demarrage';
            $details = 'Tâche démarrée par l\'employé';
        } elseif ($status === 'termine' && $statut_avant !== 'termine') {
            $action_type = 'terminer';
            $details = 'Tâche terminée par l\'employé';
        }
        
        // Enregistrer le log
        logTaskAction($task_id, $user_id, $action_type, $statut_avant, $status, $details);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour');
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour du statut: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du statut'
    ]);
}
?>
