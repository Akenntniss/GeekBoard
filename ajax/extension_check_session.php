<?php
/**
 * Endpoint AJAX pour vérifier la session depuis l'extension Chrome SERVO
 * Retourne les informations de session de manière sécurisée
 */

require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';

// === CORS Headers pour l'extension Chrome ===
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Autoriser les requêtes depuis les sous-domaines SERVO et les extensions Chrome
if (preg_match('/\.(servo\.tools|mdgeek\.top)$/', parse_url($origin, PHP_URL_HOST) ?? '') ||
    strpos($origin, 'chrome-extension://') === 0) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-SERVO-Extension');
}

// Gérer les requêtes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

$response = [
    'success' => true,
    'logged_in' => $is_logged_in,
    'user_name' => $is_logged_in ? ($_SESSION['full_name'] ?? 'Utilisateur') : null,
    'shop_id' => $is_logged_in ? ($_SESSION['shop_id'] ?? null) : null,
    'user_role' => $is_logged_in ? ($_SESSION['user_role'] ?? null) : null
];

// Si connecté, récupérer le nom du magasin
if ($is_logged_in && isset($_SESSION['shop_id'])) {
    try {
        $shop_pdo = getShopDBConnection();
        $stmt = $shop_pdo->prepare("SELECT company_name FROM company_settings LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result) {
            $response['shop_name'] = $result['company_name'];
        }
    } catch (Exception $e) {
        // Ignorer les erreurs de récupération du nom
    }
}

echo json_encode($response);
