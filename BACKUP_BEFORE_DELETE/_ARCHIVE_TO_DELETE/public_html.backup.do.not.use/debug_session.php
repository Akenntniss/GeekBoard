<?php
/**
 * Script de débogage pour vérifier les sessions
 */

// Configuration de base
require_once __DIR__ . '/config/database.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Vérifier la session
session_start();
initializeShopSession();

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'domain' => $_SERVER['HTTP_HOST'] ?? 'Non défini',
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'cookies' => $_COOKIE ?? [],
    'user_authenticated' => isset($_SESSION['user_id']),
    'shop_initialized' => isset($_SESSION['shop_id']),
];

echo json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
