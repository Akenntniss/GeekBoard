<?php
/**
 * API - Récupérer le nombre de messages non lus pour l'utilisateur connecté
 */

// Initialiser la session via la configuration globale
require_once __DIR__ . '/../../config/session_config.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

// Inclure la configuration de base de données
require_once '../../config/database.php';

try {
    // Obtenir la connexion à la base de données
    $shop_pdo = getShopDBConnection();
    
    $user_id = $_SESSION['user_id'];
    
    // Compter les messages non lus dans toutes les conversations de l'utilisateur
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(DISTINCT m.id) as unread_count
        FROM messages m
        INNER JOIN conversations c ON m.conversation_id = c.id
        INNER JOIN conversation_participants cp ON c.id = cp.conversation_id
        WHERE cp.user_id = :user_id
          AND m.sender_id != :user_id
          AND m.id NOT IN (
              SELECT message_id 
              FROM message_reads 
              WHERE user_id = :user_id
          )
          AND m.est_supprime = 0
    ");
    
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $unread_count = (int)($result['unread_count'] ?? 0);
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $unread_count
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, renvoyer 0
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'count' => 0,
        'error' => $e->getMessage()
    ]);
}
exit;
?>
