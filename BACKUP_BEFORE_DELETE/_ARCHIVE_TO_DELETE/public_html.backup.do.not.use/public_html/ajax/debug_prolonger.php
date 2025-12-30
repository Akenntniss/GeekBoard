<?php
// Script de debug pour identifier le problème avec prolonger_devis.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'steps' => []
];

try {
    // Étape 1: Session
    $debug_info['steps']['1_session_start'] = 'OK';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $debug_info['session_data'] = $_SESSION;
    
    // Étape 2: Includes
    $debug_info['steps']['2_includes_start'] = 'OK';
    
    $config_path = '../config/database.php';
    $functions_path = '../includes/functions.php';
    
    $debug_info['file_exists'] = [
        'config' => file_exists($config_path),
        'functions' => file_exists($functions_path)
    ];
    
    if (file_exists($config_path)) {
        require_once($config_path);
        $debug_info['steps']['2a_config_included'] = 'OK';
    } else {
        throw new Exception('Config file not found');
    }
    
    if (file_exists($functions_path)) {
        require_once($functions_path);
        $debug_info['steps']['2b_functions_included'] = 'OK';
    } else {
        throw new Exception('Functions file not found');
    }
    
    // Étape 3: Authentification
    $debug_info['steps']['3_auth_start'] = 'OK';
    $is_authenticated = false;
    $user_id = null;
    
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['user_id'];
        $debug_info['auth_method'] = 'user_id';
    } elseif (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['shop_id'];
        $debug_info['auth_method'] = 'shop_id';
    } elseif (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['admin_id'];
        $debug_info['auth_method'] = 'admin_id';
    }
    
    $debug_info['is_authenticated'] = $is_authenticated;
    $debug_info['user_id'] = $user_id;
    
    // Étape 4: Connexion base de données
    $debug_info['steps']['4_db_start'] = 'OK';
    
    if (function_exists('getShopDBConnection')) {
        $debug_info['steps']['4a_function_exists'] = 'OK';
        $shop_pdo = getShopDBConnection();
        
        if ($shop_pdo) {
            $debug_info['steps']['4b_connection_ok'] = 'OK';
            
            // Test de requête simple
            $test_stmt = $shop_pdo->query("SELECT 1 as test");
            $test_result = $test_stmt->fetch();
            $debug_info['steps']['4c_query_test'] = $test_result ? 'OK' : 'FAIL';
            
        } else {
            $debug_info['steps']['4b_connection_ok'] = 'FAIL - PDO is null';
        }
    } else {
        $debug_info['steps']['4a_function_exists'] = 'FAIL - Function not found';
    }
    
    // Étape 5: Test de données JSON
    $debug_info['steps']['5_json_start'] = 'OK';
    $input = file_get_contents('php://input');
    
    if ($input) {
        $data = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $debug_info['steps']['5a_json_valid'] = 'OK';
            $debug_info['input_data'] = $data;
        } else {
            $debug_info['steps']['5a_json_valid'] = 'FAIL - ' . json_last_error_msg();
        }
    } else {
        $debug_info['steps']['5a_json_valid'] = 'NO_DATA';
    }
    
    $debug_info['steps']['6_complete'] = 'SUCCESS';
    
} catch (Exception $e) {
    $debug_info['error'] = $e->getMessage();
    $debug_info['trace'] = $e->getTraceAsString();
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>
