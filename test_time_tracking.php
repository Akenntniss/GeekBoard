<?php
// Script de test pour time_tracking_api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de time_tracking_api.php ===\n\n";

$_POST['action'] = 'get_status';
$_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Simulation: HTTP_HOST = {$_SERVER['HTTP_HOST']}\n";
echo "Simulation: action = {$_POST['action']}\n\n";

try {
    ob_start();
    include 'time_tracking_api.php';
    $output = ob_get_clean();
    
    echo "===== SORTIE DE L'API =====\n";
    echo $output;
    echo "\n===== FIN SORTIE =====\n";
} catch (Exception $e) {
    echo "ERREUR PHP: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
