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
    
    if (!isset($input['est_active'])) {
        throw new Exception('État manquant');
    }
    
    $est_active = (bool)$input['est_active'];
    
    // Connexion à la base de données du magasin
    $shop_pdo = getShopDBConnectionById($shop_id);
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }
    
    // Mettre à jour seulement l'état actif
    $stmt = $shop_pdo->prepare("
        UPDATE relance_automatique_config 
        SET est_active = ?, date_modification = CURRENT_TIMESTAMP
        WHERE shop_id = ?
    ");
    
    $result = $stmt->execute([$est_active ? 1 : 0, $shop_id]);
    
    if (!$result || $stmt->rowCount() === 0) {
        // Si aucune ligne n'a été mise à jour, créer une configuration par défaut
        $default_horaires = json_encode(['09:00', '14:00', '17:00']);
        $stmt = $shop_pdo->prepare("
            INSERT INTO relance_automatique_config (shop_id, est_active, relances_horaires)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$shop_id, $est_active ? 1 : 0, $default_horaires]);
    }
    
    // Logger l'action
    error_log("Toggle relance automatique - Shop ID: $shop_id, Nouvel état: " . ($est_active ? 'Activé' : 'Désactivé'));
    
    echo json_encode([
        'success' => true,
        'message' => 'État mis à jour avec succès',
        'est_active' => $est_active
    ]);

} catch (Exception $e) {
    error_log("Erreur toggle_relance_auto.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
    ]);
}
?>
