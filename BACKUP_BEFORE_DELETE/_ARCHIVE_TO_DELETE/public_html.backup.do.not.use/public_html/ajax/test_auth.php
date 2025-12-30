<?php
// Script de test pour vérifier l'authentification
session_start();
header('Content-Type: application/json');

$result = [
    'session_data' => $_SESSION,
    'session_id' => session_id(),
    'timestamp' => date('Y-m-d H:i:s')
];

// Vérifier les différentes méthodes d'authentification
$auth_methods = [];

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $auth_methods[] = 'user_id: ' . $_SESSION['user_id'];
}

if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
    $auth_methods[] = 'shop_id: ' . $_SESSION['shop_id'];
}

if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
    $auth_methods[] = 'admin_id: ' . $_SESSION['admin_id'];
}

$result['auth_methods'] = $auth_methods;
$result['is_authenticated'] = !empty($auth_methods);

echo json_encode($result, JSON_PRETTY_PRINT);
?>
