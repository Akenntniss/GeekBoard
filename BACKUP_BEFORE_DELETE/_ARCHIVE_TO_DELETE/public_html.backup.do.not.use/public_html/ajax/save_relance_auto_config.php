<?php
header('Content-Type: application/json');

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';

try {
    // Récupérer le shop_id depuis l'URL
    $shop_id = $_GET['shop_id'] ?? null;
    
    if (!$shop_id) {
        throw new Exception('ID du magasin manquant');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données manquantes');
    }
    
    $est_active = isset($input['est_active']) ? (bool)$input['est_active'] : false;
    $relances_horaires = isset($input['relances_horaires']) ? $input['relances_horaires'] : [];
    
    // Validation des horaires
    if (empty($relances_horaires) || count($relances_horaires) > 10) {
        throw new Exception('Nombre d\'horaires invalide (1-10 requis)');
    }
    
    // Valider le format des horaires
    foreach ($relances_horaires as $heure) {
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $heure)) {
            throw new Exception('Format d\'heure invalide: ' . $heure);
        }
    }
    
    // Connexion à la base de données du magasin
    $shop_pdo = getShopDBConnectionById($shop_id);
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }
    
    // Sauvegarder la configuration
    $stmt = $shop_pdo->prepare("
        INSERT INTO relance_automatique_config (shop_id, est_active, relances_horaires)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        est_active = VALUES(est_active),
        relances_horaires = VALUES(relances_horaires),
        date_modification = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([
        $shop_id,
        $est_active ? 1 : 0,
        json_encode($relances_horaires)
    ]);
    
    // Logger l'action
    error_log("Configuration relance automatique sauvegardée - Shop ID: $shop_id, Active: " . ($est_active ? 'Oui' : 'Non') . ", Horaires: " . implode(', ', $relances_horaires));
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuration sauvegardée avec succès'
    ]);

} catch (Exception $e) {
    error_log("Erreur save_relance_auto_config.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la sauvegarde : ' . $e->getMessage()
    ]);
}
?>

