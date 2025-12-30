<?php
/**
 * API pour créer une session de checkout Stripe
 * Endpoint appelé par le JavaScript du frontend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../classes/StripeManager.php';

// Log des requêtes
$logFile = __DIR__ . '/../../logs/stripe_api.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logAPI($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logAPI("Méthode non autorisée: " . $_SERVER['REQUEST_METHOD'], 'ERROR');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Récupérer et décoder le payload JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logAPI("JSON invalide: " . json_last_error_msg(), 'ERROR');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    // Valider les données requises
    $plan_id = $data['plan_id'] ?? null;
    $shop_id = $data['shop_id'] ?? null;
    $customer_email = $data['customer_email'] ?? null;
    
    if (!$plan_id || !$shop_id) {
        logAPI("Données manquantes - Plan: $plan_id, Shop: $shop_id", 'ERROR');
        http_response_code(400);
        echo json_encode(['error' => 'Missing required data: plan_id and shop_id']);
        exit;
    }
    
    logAPI("Création session checkout - Shop: $shop_id, Plan: $plan_id");
    
    // Créer la session avec StripeManager
    $stripeManager = new StripeManager();
    $session = $stripeManager->createCheckoutSession($plan_id, $shop_id, $customer_email);
    
    if (!$session) {
        logAPI("Erreur création session Stripe", 'ERROR');
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create checkout session']);
        exit;
    }
    
    logAPI("Session créée avec succès: " . $session->id);
    
    // Retourner l'ID de session
    echo json_encode([
        'session_id' => $session->id,
        'url' => $session->url,
        'status' => 'success'
    ]);
    
} catch (Exception $e) {
    logAPI("Exception API: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
