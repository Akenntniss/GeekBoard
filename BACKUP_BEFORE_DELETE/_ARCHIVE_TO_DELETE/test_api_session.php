<?php
/**
 * Test simple pour diagnostiquer le problème de session dans l'API
 */

header('Content-Type: application/json');

// Configuration de base
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/database.php';

// Initialiser la session magasin
initializeShopSession();

// Créer la réponse de diagnostic
$response = [
    'success' => true,
    'message' => 'Test API Session',
    'debug' => [
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'user_id' => $_SESSION['user_id'] ?? null,
        'shop_id' => $_SESSION['shop_id'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null,
        'host' => $_SERVER['HTTP_HOST'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]
];

// Tenter la connexion à la base
try {
    $pdo = getShopDBConnection();
    if ($pdo) {
        $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
        $response['debug']['database'] = $db_name;
        
        // Compter les utilisateurs
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $response['debug']['users_count'] = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $response['debug']['database_error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>