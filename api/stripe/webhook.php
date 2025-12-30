<?php
/**
 * Webhook endpoint Stripe pour GeekBoard
 * URL: https://servo.tools/api/stripe/webhook.php
 */

require_once '../../config/database.php';
require_once '../../classes/StripeManager.php';

// Headers
header('Content-Type: application/json');

// Logging
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

// Test GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    logWebhook("Test GET webhook");
    http_response_code(200);
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
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logWebhook("Méthode non autorisée: " . $_SERVER['REQUEST_METHOD'], 'ERROR');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Récupérer le payload et la signature
    $payload = file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    logWebhook("Webhook POST reçu - Signature: " . substr($sig_header, 0, 50) . "...");
    
    // Vérifier le payload
    if (empty($payload)) {
        logWebhook("Payload vide", 'ERROR');
        http_response_code(400);
        echo json_encode(['error' => 'Empty payload']);
        exit;
    }
    
    // Vérifier la signature
    if (empty($sig_header)) {
        logWebhook("Signature manquante", 'ERROR');
        http_response_code(401);
        echo json_encode(['error' => 'Missing signature']);
        exit;
    }
    
    // Traiter avec StripeManager
    try {
        logWebhook("Tentative d'instanciation de StripeManager");
        $stripeManager = new StripeManager();
        logWebhook("StripeManager instancié avec succès");
        
        logWebhook("Appel de processWebhook");
        $result = $stripeManager->processWebhook($payload, $sig_header);
        logWebhook("processWebhook retourné: " . ($result ? 'true' : 'false'));
        
        if ($result === false) {
            // Échec de traitement mais payload reçu et signature valide
            // On retourne 200 pour dire à Stripe qu'on a bien reçu
            logWebhook("Événement reçu mais traitement échoué (retour 200 quand même)", 'WARNING');
            http_response_code(200);
            echo json_encode([
                'status' => 'received',
                'processed' => false,
                'message' => 'Event received but processing failed'
            ]);
        } else {
            // Succès
            logWebhook("Webhook traité avec succès");
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        }
        
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Signature invalide - erreur 401
        logWebhook("Signature invalide: " . $e->getMessage(), 'ERROR');
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        
    } catch (Exception $e) {
        // Autre erreur - log mais retourne 200 pour ne pas bloquer Stripe
        logWebhook("Exception durant traitement: " . $e->getMessage(), 'ERROR');
        logWebhook("Stack trace: " . $e->getTraceAsString(), 'ERROR');
        
        // Retourner 200 pour éviter que Stripe retry indéfiniment
        http_response_code(200);
        echo json_encode([
            'status' => 'received',
            'processed' => false,
            'error' => 'Processing exception',
            'message' => $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    // Erreur fatale - vraie erreur 500
    logWebhook("Exception fatale: " . $e->getMessage(), 'CRITICAL');
    logWebhook("Stack trace: " . $e->getTraceAsString(), 'CRITICAL');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => 'Fatal error - check logs'
    ]);
}
?>
