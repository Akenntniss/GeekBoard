<?php
// Test direct de l'API depuis le serveur
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';

echo "=== TEST DIRECT API DEPUIS SERVEUR ===\n";

// Simuler une session admin pour le test
$_SESSION['user_id'] = 1; // Supposons que l'admin a l'ID 1
$_SESSION['user_role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';

echo "Session configurée pour test:\n";
echo "- User ID: " . $_SESSION['user_id'] . "\n";
echo "- User Role: " . $_SESSION['user_role'] . "\n";
echo "- Full Name: " . $_SESSION['full_name'] . "\n";

echo "\n=== TEST GET STATUS ===\n";

// Simuler une requête GET à l'API
$_GET['action'] = 'get_status';

// Capturer la sortie de l'API
ob_start();
try {
    include __DIR__ . '/time_tracking_api.php';
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
$api_output = ob_get_clean();

echo "Réponse API:\n";
echo $api_output . "\n";

echo "\n=== TEST ADMIN GET ACTIVE ===\n";

// Reset buffer et test admin
$_GET['action'] = 'admin_get_active';

ob_start();
try {
    include __DIR__ . '/time_tracking_api.php';
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
$api_output = ob_get_clean();

echo "Réponse API Admin:\n";
echo $api_output . "\n";

?>
