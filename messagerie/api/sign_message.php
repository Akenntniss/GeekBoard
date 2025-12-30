<?php
/**
 * API - Signer un message administratif
 */

// Initialiser la session
require_once __DIR__ . '/../../config/session_config.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Inclure la configuration de base de données
require_once '../../config/database.php';

// Obtenir la connexion à la base de données
$shop_pdo = getShopDBConnection();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['message_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de message manquant']);
    exit;
}

$message_id = (int)$input['message_id'];
$signature_data = isset($input['signature_data']) ? $input['signature_data'] : '';
$user_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];

try {
    global $shop_pdo;

    // 1. Vérifier que le message existe et requiert une signature
    $stmt = $shop_pdo->prepare("
        SELECT id, conversation_id, requires_signature 
        FROM messages 
        WHERE id = :message_id
    ");
    $stmt->execute([':message_id' => $message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new Exception('Message introuvable');
    }

    if ($message['requires_signature'] != 1) {
        throw new Exception('Ce message ne requiert pas de signature');
    }

    // 2. Vérifier que l'utilisateur participe à la conversation
    $stmt = $shop_pdo->prepare("
        SELECT 1 FROM conversation_participants 
        WHERE conversation_id = :conversation_id AND user_id = :user_id
    ");
    $stmt->execute([
        ':conversation_id' => $message['conversation_id'],
        ':user_id' => $user_id
    ]);

    if (!$stmt->fetch()) {
        throw new Exception('Vous ne participez pas à cette conversation');
    }

    // 3. Vérifier si l'utilisateur a déjà signé
    $stmt = $shop_pdo->prepare("
        SELECT 1 FROM message_signatures 
        WHERE message_id = :message_id AND user_id = :user_id
    ");
    $stmt->execute([
        ':message_id' => $message_id,
        ':user_id' => $user_id
    ]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Message déjà signé']);
        exit;
    }

    // 4. Enregistrer la signature
    $stmt = $shop_pdo->prepare("
        INSERT INTO message_signatures (message_id, user_id, signed_at, signature_data, ip_address)
        VALUES (:message_id, :user_id, NOW(), :signature_data, :ip_address)
    ");
    
    $stmt->execute([
        ':message_id' => $message_id,
        ':user_id' => $user_id,
        ':signature_data' => $signature_data,
        ':ip_address' => $ip_address
    ]);

    echo json_encode(['success' => true, 'message' => 'Signature enregistrée avec succès']);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
