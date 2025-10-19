<?php
/**
 * Webhook endpoint Stripe pour GeekBoard
 * URL à configurer dans Stripe: https://82.29.168.205/api/stripe/webhook.php
 */

require_once '../../config/database.php';
require_once '../../classes/StripeManager.php';

// Headers de sécurité
header('Content-Type: application/json');

// Log des webhooks
$logFile = __DIR__ . '/../../logs/stripe_webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logWebhook($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Endpoint GET pour test
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    logWebhook("Test GET webhook");
    echo json_encode([
        'status' => 'Webhook Stripe GeekBoard PRODUCTION actif',
        'timestamp' => date('Y-m-d H:i:s'),
        'url' => 'https://servo.tools/api/stripe/webhook.php',
        'environment' => 'PRODUCTION',
        'test' => true
    ]);
    exit;
}

try {
    // Récupérer le payload et la signature
    $payload = file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    logWebhook("Webhook reçu - Signature: " . substr($sig_header, 0, 50) . "...");
    logWebhook("Payload: " . substr($payload, 0, 200) . "...");
    
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logWebhook("Méthode non autorisée: " . $_SERVER['REQUEST_METHOD'], 'ERROR');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Vérifier le payload
    if (empty($payload)) {
        logWebhook("Payload vide", 'ERROR');
        http_response_code(400);
        echo json_encode(['error' => 'Empty payload']);
        exit;
    }
    
    // Traiter avec StripeManager
    $stripeManager = new StripeManager();
    $result = $stripeManager->processWebhook($payload, $sig_header);
    
    if ($result) {
        logWebhook("Webhook traité avec succès");
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    } else {
        logWebhook("Erreur traitement webhook", 'ERROR');
        http_response_code(500);
        echo json_encode(['error' => 'Processing failed']);
    }
    
} catch (Exception $e) {
    logWebhook("Exception webhook: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}

?>
