<?php
/**
 * API - Obtenir la liste des signatures en attente pour un message
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

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérification du rôle admin (à adapter selon votre système de rôles)
$is_admin = false;
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    $is_admin = true;
} else {
    // Vérification BDD secours
    try {
        $stmt = $shop_pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();
        if (in_array($role, ['admin', 'superadmin'])) {
            $is_admin = true;
        }
    } catch (Exception $e) { }
}

if (!$is_admin) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès réservé aux administrateurs']);
    exit;
}

// Récupérer les paramètres
if (!isset($_GET['message_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de message manquant']);
    exit;
}

$message_id = (int)$_GET['message_id'];

try {
    global $shop_pdo;

    // 1. Récupérer les infos du message et de la conversation
    $stmt = $shop_pdo->prepare("
        SELECT m.id, m.conversation_id, m.requires_signature 
        FROM messages m
        WHERE m.id = :message_id
    ");
    $stmt->execute([':message_id' => $message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new Exception('Message introuvable');
    }

    if ($message['requires_signature'] != 1) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'Ce message ne requiert pas de signature']);
        exit;
    }

    // 2. Récupérer tous les participants de la conversation
    $stmt = $shop_pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.role, cp.joined_at
        FROM conversation_participants cp
        JOIN users u ON cp.user_id = u.id
        WHERE cp.conversation_id = :conversation_id
    ");
    $stmt->execute([':conversation_id' => $message['conversation_id']]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Récupérer ceux qui ont signé
    $stmt = $shop_pdo->prepare("
        SELECT user_id, signed_at 
        FROM message_signatures 
        WHERE message_id = :message_id
    ");
    $stmt->execute([':message_id' => $message_id]);
    $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Indexer les signatures par user_id
    $signed_users = [];
    foreach ($signatures as $sig) {
        $signed_users[$sig['user_id']] = $sig['signed_at'];
    }

    // 4. Construire le résultat
    $result = [
        'signed' => [],
        'pending' => []
    ];

    foreach ($participants as $participant) {
        // Ignorer l'admin lui-même s'il le souhaite, ou l'inclure
        // Ici on inclut tout le monde sauf peut-être les bots s'il y en a
        
        $uid = $participant['id'];
        $info = [
            'id' => $uid,
            'name' => $participant['full_name'],
            'role' => $participant['role']
        ];

        if (isset($signed_users[$uid])) {
            $info['signed_at'] = $signed_users[$uid];
            $result['signed'][] = $info;
        } else {
            $result['pending'][] = $info;
        }
    }

    echo json_encode(['success' => true, 'data' => $result]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
