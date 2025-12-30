<?php
/**
 * API REST v2 - Configuration
 * GeekBoard Desktop Application
 */

// Empêcher l'accès direct
if (!defined('API_V2')) {
    define('API_V2', true);
}

// Headers CORS pour permettre les requêtes cross-origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Répondre immédiatement aux requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration JWT
define('JWT_SECRET', 'GeekBoard_Desktop_2024_SecretKey_Change_In_Production');
define('JWT_EXPIRATION', 86400); // 24 heures en secondes
define('JWT_ALGORITHM', 'HS256');

// Inclure le détecteur de sous-domaines
require_once __DIR__ . '/../../config/subdomain_database_detector.php';

/**
 * Encode JWT Token
 */
function jwt_encode($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]);
    $header = base64url_encode($header);
    
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRATION;
    $payload = json_encode($payload);
    $payload = base64url_encode($payload);
    
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $signature = base64url_encode($signature);
    
    return "$header.$payload.$signature";
}

/**
 * Decode JWT Token
 */
function jwt_decode($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Vérifier la signature
    $valid_signature = base64url_encode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );
    
    if ($signature !== $valid_signature) {
        return null;
    }
    
    $payload = json_decode(base64url_decode($payload), true);
    
    // Vérifier l'expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }
    
    return $payload;
}

/**
 * Base64 URL-safe encode
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL-safe decode
 */
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

/**
 * Récupérer le token depuis le header Authorization
 */
function get_bearer_token() {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Vérifier l'authentification et retourner le payload du token
 */
function require_auth() {
    $token = get_bearer_token();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token manquant']);
        exit;
    }
    
    $payload = jwt_decode($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token invalide ou expiré']);
        exit;
    }
    
    return $payload;
}

/**
 * Obtenir la connexion à la base du magasin via subdomain
 */
function get_shop_connection($subdomain) {
    global $subdomain_detector;
    
    try {
        return $subdomain_detector->getConnection($subdomain);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Réponse JSON standard
 */
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Réponse d'erreur
 */
function error_response($message, $status = 400) {
    json_response(['success' => false, 'error' => $message], $status);
}

/**
 * Réponse de succès
 */
function success_response($data = [], $message = null) {
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    $response = array_merge($response, $data);
    json_response($response);
}

/**
 * Récupérer les données POST en JSON
 */
function get_json_input() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}
?>
