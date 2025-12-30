<?php
/**
 * API de sauvegarde du layout d'étiquette par défaut
 * Sauvegarde le layout sélectionné dans les paramètres
 */

// Configuration des en-têtes pour AJAX
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/label_manager.php';

// Initialiser la session magasin si nécessaire
initializeShopSession();

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['layout_id'])) {
        throw new Exception('Layout ID manquant');
    }
    
    $layout_id = cleanInput($input['layout_id']);
    
    // Récupérer la connexion à la base de données
    $pdo = getShopDBConnection();
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Vérifier que le layout existe
    $availableLayouts = LabelManager::getAvailableLayouts();
    if (!isset($availableLayouts[$layout_id])) {
        throw new Exception('Layout non trouvé');
    }
    
    // Sauvegarder le layout par défaut
    $success = LabelManager::setSelectedLayout($pdo, $layout_id);
    
    if (!$success) {
        throw new Exception('Erreur lors de la sauvegarde');
    }
    
    // Retourner une réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Layout sauvegardé avec succès',
        'layout_id' => $layout_id,
        'layout_name' => $availableLayouts[$layout_id]['name']
    ]);
    
} catch (Exception $e) {
    // Retourner une erreur JSON
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>