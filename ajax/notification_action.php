<?php
/**
 * AJAX Handler for Notification Actions
 */

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notification_functions.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

$response = ['success' => false];

try {
    if ($action === 'mark_all_read') {
        $count = mark_all_notifications_as_read($user_id);
        $response = [
            'success' => true,
            'message' => 'All notifications marked as read',
            'count' => $count
        ];
    } else {
        $response['message'] = 'Invalid action';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
