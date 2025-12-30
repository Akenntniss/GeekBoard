<?php
/**
 * API - Marquer tous les messages d'une conversation comme lus
 */

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Inclure la configuration de base de données
require_once '../../config/database.php';

// Inclure les fonctions
require_once '../includes/functions.php';

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validation des données
if (!isset($input['conversation_id']) || empty($input['conversation_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant']);
    exit;
}

$conversation_id = (int)$input['conversation_id'];

// Vérifier l'accès à la conversation
if (!user_has_conversation_access($_SESSION['user_id'], $conversation_id)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès refusé à cette conversation']);
    exit;
}

try {
    // Obtenir la connexion à la base de données
    $shop_pdo = getShopDBConnection();
    
    $user_id = $_SESSION['user_id'];
    
    // Marquer tous les messages de la conversation comme lus
    $stmt = $shop_pdo->prepare("
        INSERT IGNORE INTO message_reads (message_id, user_id, date_lecture)
        SELECT m.id, :user_id, NOW()
        FROM messages m
        WHERE m.conversation_id = :conversation_id
          AND m.sender_id != :user_id
          AND m.est_supprime = 0
    ");
    
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':user_id' => $user_id
    ]);
    
    $messages_marked = $stmt->rowCount();
    
    // Marquer aussi les notifications de cette conversation comme lues
    $stmt_notif = $shop_pdo->prepare("
        UPDATE notification_messagerie 
        SET lu = 1 
        WHERE user_id = :user_id 
          AND conversation_id = :conversation_id 
          AND lu = 0
    ");
    
    $stmt_notif->execute([
        ':user_id' => $user_id,
        ':conversation_id' => $conversation_id
    ]);
    
    $notifications_marked = $stmt_notif->rowCount();
    
    // Renvoyer la réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Messages marqués comme lus',
        'messages_marked' => $messages_marked,
        'notifications_marked' => $notifications_marked
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    if (function_exists('log_error')) {
        log_error('Erreur lors du marquage des messages comme lus', $e->getMessage());
    }
    
    // Renvoyer un message d'erreur
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
exit;
?>
