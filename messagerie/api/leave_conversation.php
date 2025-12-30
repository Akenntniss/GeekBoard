<?php
/**
 * API - Quitter une conversation
 */

// Initialiser la session via la configuration globale AVANT database.php
require_once __DIR__ . '/../../config/session_config.php';

// Activer l'affichage des erreurs pour le débogage (mais pas dans la sortie standard pour JSON)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Inclure la configuration de base de données
require_once '../../config/database.php';

// Obtenir la connexion à la base de données de la boutique
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

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['conversation_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant']);
    exit;
}

$conversation_id = (int)$input['conversation_id'];
$user_id = $_SESSION['user_id'];

try {
    global $shop_pdo;
    
    // Vérifier si c'est le créateur (admin)
    $stmt = $shop_pdo->prepare("SELECT role FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmt->execute([$conversation_id, $user_id]);
    $role = $stmt->fetchColumn();
    
    if (!$role) {
        throw new Exception("Vous ne faites pas partie de cette conversation");
    }
    
    // Si c'est le seul administrateur, on ne peut pas quitter sans désigner un autre admin ou supprimer
    // Pour simplifier, on supprime juste la participation
    
    $stmt = $shop_pdo->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmt->execute([$conversation_id, $user_id]);
    
    // Ajouter un message système indiquant le départ
    require_once '../includes/functions.php';
    send_message($conversation_id, $user_id, "a quitté la conversation", "system");
    
    echo json_encode(['success' => true, 'message' => 'Vous avez quitté la conversation']);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
