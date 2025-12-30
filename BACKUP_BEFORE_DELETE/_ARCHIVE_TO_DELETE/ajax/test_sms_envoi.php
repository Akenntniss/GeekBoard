<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/sms_functions.php';

try {
    $_SESSION['shop_id'] = 1;
    initializeShopSession();
    $shop_pdo = getShopDBConnection();
    
    // Test d'envoi SMS avec un numéro fictif
    $test_phone = "0123456789";
    $test_message = "Test SMS GeekBoard - " . date('Y-m-d H:i:s');
    
    echo json_encode([
        'test_preparation' => 'OK',
        'phone' => $test_phone,
        'message' => $test_message,
        'message_length' => strlen($test_message)
    ]);
    
    // Tester l'envoi (commenté pour éviter l'envoi réel)
    /*
    $result = send_sms($test_phone, $test_message, 'test', null, 1);
    
    echo json_encode([
        'success' => true,
        'sms_result' => $result,
        'test_phone' => $test_phone,
        'test_message' => $test_message
    ]);
    */
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
