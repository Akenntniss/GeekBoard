<?php
/**
 * API - Mettre à jour le statut ou la priorité d'une conversation
 */

// Initialiser la session via la configuration globale AVANT database.php
require_once __DIR__ . '/../../config/session_config.php';

// Activer l'affichage des erreurs pour le débogage (mais pas dans la sortie standard pour JSON)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

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

// Inclure la configuration de base de données
require_once '../../config/database.php';

// Inclure les fonctions
require_once '../includes/functions.php';

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validation des données
if (!isset($input['conversation_id']) || empty($input['conversation_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant']);
    exit;
}

$conversation_id = (int)$input['conversation_id'];

// Vérifier l'accès à la conversation
if (!user_has_conversation_access($_SESSION['user_id'], $conversation_id)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès refusé à cette conversation']);
    exit;
}

try {
    // Obtenir la connexion à la base de données
    $shop_pdo = getShopDBConnection();
    
    // Préparer les valeurs à mettre à jour
    $updates = [];
    $params = [':conversation_id' => $conversation_id];
    
    // Vérifier si une action spécifique est demandée
    if (isset($input['action'])) {
        $action = $input['action'];
        $user_id = $_SESSION['user_id'];
        
        switch ($action) {
            case 'favorite':
                $sql = "UPDATE conversation_participants SET est_favoris = NOT est_favoris WHERE conversation_id = :conversation_id AND user_id = :user_id";
                $stmt = $shop_pdo->prepare($sql);
                $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $user_id]);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Favoris mis à jour']);
                exit;
                
            case 'mute':
                $sql = "UPDATE conversation_participants SET notification_mute = NOT notification_mute WHERE conversation_id = :conversation_id AND user_id = :user_id";
                $stmt = $shop_pdo->prepare($sql);
                $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $user_id]);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Notifications mises à jour']);
                exit;
                
            case 'archive':
                $sql = "UPDATE conversation_participants SET est_archive = NOT est_archive WHERE conversation_id = :conversation_id AND user_id = :user_id";
                $stmt = $shop_pdo->prepare($sql);
                $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $user_id]);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Archivage mis à jour']);
                exit;
                
            default:
                throw new Exception('Action inconnue');
        }
    }

    // Vérifier et ajouter le statut si fourni (global)
    
    // Vérifi et ajouter la priorité si fournie
    if (isset($input['priorite'])) {
        $allowed_priorites = ['normale', 'importante', 'urgente'];
        if (!in_array($input['priorite'], $allowed_priorites)) {
            throw new Exception('Priorité invalide');
        }
        $updates[] = "priorite = :priorite";
        $params[':priorite'] = $input['priorite'];
    }
    
    // Vérifier qu'il y a au moins une mise à jour
    if (empty($updates)) {
        throw new Exception('Aucune mise à jour spécifiée');
    }
    
    // Construire et exécuter la requête
    $sql = "UPDATE conversations SET " . implode(', ', $updates) . " WHERE id = :conversation_id";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    
    // Renvoyer la réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Conversation mise à jour avec succès'
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    if (function_exists('log_error')) {
        log_error('Erreur lors de la mise à jour de la conversation', $e->getMessage());
    }
    
    // Renvoyer un message d'erreur
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
exit;
?>
