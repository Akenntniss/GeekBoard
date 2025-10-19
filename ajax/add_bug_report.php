<?php
/**
 * Traitement AJAX pour l'ajout d'un rapport de bug
 */

// Inclure la configuration de session
require_once '../config/session_config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Récupération et nettoyage des données
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$page_url = isset($_POST['page_url']) ? trim($_POST['page_url']) : '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Validation des données
if (empty($description)) {
    echo json_encode(['success' => false, 'message' => 'La description est requise']);
    exit;
}

try {
    // Utiliser la connexion multi-magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }

    // Préparer l'ID utilisateur si disponible
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Requête d'insertion avec les noms de colonnes corrects
    $sql = "INSERT INTO bug_reports (user_id, description, page_url, user_agent, priorite, status, date_creation) 
            VALUES (:user_id, :description, :page_url, :user_agent, 'basse', 'nouveau', NOW())";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':description' => $description,
        ':page_url' => $page_url,
        ':user_agent' => $user_agent
    ]);
    
    // Réponse de succès
    echo json_encode(['success' => true, 'message' => 'Rapport de bug enregistré avec succès']);
    
} catch (Exception $e) {
    // Log de l'erreur côté serveur avec plus de détails
    error_log("Erreur lors de l'ajout d'un rapport de bug: " . $e->getMessage());
    error_log("Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini'));
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'non défini'));
    error_log("Description: " . substr($description, 0, 100) . '...');
    error_log("Page URL: " . $page_url);
    
    // Réponse d'erreur
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue lors de l\'enregistrement du rapport',
        'debug' => $e->getMessage() // Pour le debug (à supprimer en production)
    ]);
} 