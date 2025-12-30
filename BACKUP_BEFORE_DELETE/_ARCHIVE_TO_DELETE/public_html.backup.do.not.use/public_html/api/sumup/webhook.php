<?php
/**
 * Webhook handler pour les notifications SumUp
 * URL à configurer dans SumUp: https://82.29.168.205/MDGEEK/api/sumup/webhook.php
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../classes/SumUpIntegration.php';

// Headers de sécurité
header('Content-Type: application/json');

// Log de toutes les requêtes webhook pour debug
$logFile = __DIR__ . '/../../logs/webhook_sumup.log';
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

try {
    // Log de la requête entrante
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = getallheaders();
    $rawInput = file_get_contents('php://input');
    
    logWebhook("Webhook reçu: {$method} " . json_encode($headers));
    logWebhook("Payload: " . $rawInput);
    
    // Vérifier la méthode HTTP
    if ($method !== 'POST') {
        logWebhook("Méthode non autorisée: {$method}", 'ERROR');
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Décoder le payload JSON
    $payload = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logWebhook("JSON invalide: " . json_last_error_msg(), 'ERROR');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    if (!$payload) {
        logWebhook("Payload vide", 'ERROR');
        http_response_code(400);
        echo json_encode(['error' => 'Empty payload']);
        exit;
    }
    
    // Valider la structure du webhook
    if (!isset($payload['event_type'])) {
        logWebhook("event_type manquant", 'ERROR');
        http_response_code(400);
        echo json_encode(['error' => 'Missing event_type']);
        exit;
    }
    
    // Traiter le webhook avec la classe SumUp
    $sumup = new SumUpIntegration();
    $result = $sumup->processWebhook($payload);
    
    if ($result) {
        logWebhook("Webhook traité avec succès: " . $payload['event_type']);
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    } else {
        logWebhook("Erreur traitement webhook: " . $payload['event_type'], 'ERROR');
        http_response_code(500);
        echo json_encode(['error' => 'Processing failed']);
    }
    
} catch (Exception $e) {
    logWebhook("Exception webhook: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

// Pour debug - endpoint GET pour tester
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    logWebhook("Test GET webhook");
    echo json_encode([
        'status' => 'Webhook SumUp GeekBoard actif',
        'timestamp' => date('Y-m-d H:i:s'),
        'url' => 'https://82.29.168.205/MDGEEK/api/sumup/webhook.php'
    ]);
}
?> 