<?php
// Script de test pour l'API KPI
// Mocker l'environnement serveur pour la détection du shop
$_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';
$_SERVER['REQUEST_URI'] = '/kpi_api.php';

// Simuler la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1; // Simuler un admin
$_SESSION['role'] = 'admin';
// Note: shop_id sera défini par initializeShopSession

// Simuler les paramètres GET
$_GET['action'] = 'all';
$_GET['date_start'] = date('Y-m-d', strtotime('-30 days'));
$_GET['date_end'] = date('Y-m-d');

// Capturer la sortie
ob_start();
require_once 'kpi_api.php';
$output = ob_get_clean();

// Analyser le JSON
$data = json_decode($output, true);

if ($data && isset($data['success']) && $data['success']) {
    echo "✅ API Test Passed!\n";
    echo "Turnover Cash In: " . $data['data']['turnover']['cash_in']['amount'] . " € (" . $data['data']['turnover']['cash_in']['count'] . " repairs)\n";
    echo "Turnover Potential: " . $data['data']['turnover']['potential']['amount'] . " € (" . $data['data']['turnover']['potential']['count'] . " repairs)\n";
    echo "New Repairs: " . $data['data']['repairs']['new_repairs'] . "\n";
    echo "Done Repairs: " . $data['data']['repairs']['done_repairs'] . "\n";
    echo "Returned Repairs: " . $data['data']['repairs']['returned_repairs'] . "\n";
    echo "Autonomy Repairs: " . $data['data']['repairs']['autonomy_repairs'] . "\n";
    echo "Avg Basket: " . $data['data']['repairs']['avg_basket'] . " €\n";
    echo "Employee Behavior Count: " . count($data['data']['behavior']) . "\n";
} else {
    echo "❌ API Test Failed!\n";
    echo "Output: " . substr($output, 0, 500) . "...\n"; // Limiter la sortie en cas d'erreur HTML géante
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
}
?>
