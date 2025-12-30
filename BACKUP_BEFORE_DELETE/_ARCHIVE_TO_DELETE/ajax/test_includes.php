<?php
// Test des includes pour identifier le problème
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];

// Test 1: Session
session_start();
$results['session'] = 'OK';

// Test 2: config.php
try {
    if (file_exists('../config/config.php')) {
        require_once '../config/config.php';
        $results['config.php'] = 'OK - Fichier inclus';
    } else {
        $results['config.php'] = 'ERREUR - Fichier non trouvé';
    }
} catch (Exception $e) {
    $results['config.php'] = 'ERREUR - ' . $e->getMessage();
}

// Test 3: functions.php
try {
    if (file_exists('../includes/functions.php')) {
        require_once '../includes/functions.php';
        $results['functions.php'] = 'OK - Fichier inclus';
    } else {
        $results['functions.php'] = 'ERREUR - Fichier non trouvé';
    }
} catch (Exception $e) {
    $results['functions.php'] = 'ERREUR - ' . $e->getMessage();
}

// Test 4: database.php
try {
    if (file_exists('../includes/database.php')) {
        require_once '../includes/database.php';
        $results['database.php'] = 'OK - Fichier inclus';
    } else {
        $results['database.php'] = 'ERREUR - Fichier non trouvé';
    }
} catch (Exception $e) {
    $results['database.php'] = 'ERREUR - ' . $e->getMessage();
}

// Test 5: Authentification
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    $results['auth'] = 'FAIL - shop_id manquant';
} else {
    $results['auth'] = 'OK - shop_id présent: ' . $_SESSION['shop_id'];
}

// Test 6: Méthode HTTP
$results['method'] = $_SERVER['REQUEST_METHOD'];

// Test 7: Données JSON
$input = file_get_contents('php://input');
if ($input) {
    $data = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $results['json'] = 'OK - JSON valide';
    } else {
        $results['json'] = 'ERREUR - JSON invalide: ' . json_last_error_msg();
    }
} else {
    $results['json'] = 'AUCUNE donnée reçue';
}

echo json_encode([
    'success' => true,
    'message' => 'Test des includes terminé',
    'results' => $results,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
?>



