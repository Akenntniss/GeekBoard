<?php
/**
 * API - Marquer toutes les conversations comme lues
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

try {
    global $shop_pdo;
    $user_id = $_SESSION['user_id'];
    
    // Mettre à jour la date de dernière lecture pour toutes les conversations de l'utilisateur
    $stmt = $shop_pdo->prepare("
        UPDATE conversation_participants 
        SET date_derniere_lecture = NOW() 
        WHERE user_id = :user_id
    ");
    
    $stmt->execute([':user_id' => $user_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Toutes les conversations ont été marquées comme lues',
        'updated_count' => $stmt->rowCount()
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    if (function_exists('log_error')) {
        log_error('Erreur lors du marquage de tout comme lu', $e->getMessage());
    }
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
