<?php
// Test de session simple pour l'API
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';

header('Content-Type: text/plain');

echo "=== TEST API SESSION ===\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . "\n";
echo "User Role: " . ($_SESSION['user_role'] ?? 'NON DÉFINI') . "\n";
echo "Full Name: " . ($_SESSION['full_name'] ?? 'NON DÉFINI') . "\n";
echo "Shop ID: " . ($_SESSION['shop_id'] ?? 'NON DÉFINI') . "\n";
echo "Shop Name: " . ($_SESSION['shop_name'] ?? 'NON DÉFINI') . "\n";

echo "\n=== TOUTES LES VARIABLES SESSION ===\n";
print_r($_SESSION);

echo "\n=== COOKIES ===\n";
print_r($_COOKIE);

echo "\n=== SERVER INFO ===\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NON DÉFINI') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NON DÉFINI') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NON DÉFINI') . "\n";
?>
