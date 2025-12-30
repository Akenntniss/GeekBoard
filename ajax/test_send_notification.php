<?php
// ajax/test_send_notification.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../includes/functions.php';
require_once '../includes/NotificationService.php';

try {
    $userId = $_SESSION['user_id'];
    
    // Tentative d'envoi utilisant le service de notification
    // On utilise sendToUser directement pour tester le push
    $pdo = getShopDBConnection();
    require_once '../includes/PushNotifications.php';
    
    $pushService = new PushNotifications($pdo);
    
    $title = "🔔 Test de Notification";
    $body = "Ceci est une notification de test envoyée à " . date('H:i:s');
    
    $result = $pushService->sendToUser($userId, $title, $body, [
        'url' => '/index.php?page=test_notifications',
        'tag' => 'test-notification',
        'important' => true
    ]);
    
    if ($result['success']) {
        // Enregistrer aussi en base pour la forme
        NotificationService::send($userId, 'system_test', $title, $body);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification envoyée',
            'details' => $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'details' => $result
        ]);
    }
    
} catch (Exception $e) {
    error_log("TEST NOTIF ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage()
    ]);
}
