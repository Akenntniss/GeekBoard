<?php
// Test simple pour identifier le problème exact
header('Content-Type: application/json; charset=utf-8');

// Activer l'affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo json_encode([
    'status' => 'Test simple OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
?>



