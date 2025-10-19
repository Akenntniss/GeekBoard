<?php
/**
 * API de sauvegarde du layout d'étiquette sélectionné
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/label_manager.php';

header('Content-Type: application/json');

// Démarrer la session mais ne pas vérifier l'authentification
session_start();

try {
    // Récupérer les données POST
    $data = json_decode(file_get_contents('php://input'), true);
    $layoutId = isset($data['layout_id']) ? cleanInput($data['layout_id']) : '';
    
    if (empty($layoutId)) {
        throw new Exception("ID de layout manquant");
    }
    
    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();
    
    // Sauvegarder le layout
    $success = LabelManager::setSelectedLayout($shop_pdo, $layoutId);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Layout sauvegardé avec succès',
            'layout_id' => $layoutId
        ]);
    } else {
        throw new Exception("Impossible de sauvegarder le layout");
    }
    
} catch (Exception $e) {
    error_log("Erreur save_label_layout.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

