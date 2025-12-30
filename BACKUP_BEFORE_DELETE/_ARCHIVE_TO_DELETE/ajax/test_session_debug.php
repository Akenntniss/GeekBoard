<?php
// Script de debug pour tester la session et la connexion
header('Content-Type: application/json; charset=utf-8');

// Démarrer la session
session_start();

// Informations de debug
$debug = [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'shop_id_exists' => isset($_SESSION['shop_id']),
    'shop_id_value' => $_SESSION['shop_id'] ?? null,
    'user_id_exists' => isset($_SESSION['user_id']),
    'user_id_value' => $_SESSION['user_id'] ?? null,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'shop_id_param' => $_GET['shop_id'] ?? null,
    'timestamp' => date('Y-m-d H:i:s')
];

// Test de connexion à la base de données
try {
    if (file_exists('../config/config.php')) {
        require_once '../config/config.php';
        $debug['config_loaded'] = true;
    } else {
        $debug['config_error'] = 'config.php non trouvé';
    }
    
    if (file_exists('../includes/database.php')) {
        require_once '../includes/database.php';
        $debug['database_included'] = true;
    } else {
        $debug['database_error'] = 'database.php non trouvé';
    }
    
} catch (Exception $e) {
    $debug['include_error'] = $e->getMessage();
}

// Vérifier la logique d'authentification
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    $debug['auth_result'] = 'FAIL - shop_id manquant';
    $debug['would_return_401'] = true;
} else {
    $debug['auth_result'] = 'SUCCESS - shop_id présent';
    $debug['would_return_401'] = false;
}

echo json_encode($debug, JSON_PRETTY_PRINT);
?>



