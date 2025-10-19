<?php
header('Content-Type: application/json');

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    // Connexion à la base de données du magasin
    $shop_pdo = getShopDBConnectionById($shop_id);
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }
    
    // Récupérer la configuration
    $stmt = $shop_pdo->prepare("
        SELECT est_active, relances_horaires, derniere_execution
        FROM relance_automatique_config 
        WHERE shop_id = ?
    ");
    $stmt->execute([$shop_id]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Créer une configuration par défaut
        $default_horaires = json_encode(['09:00', '14:00', '17:00']);
        $stmt = $shop_pdo->prepare("
            INSERT INTO relance_automatique_config (shop_id, est_active, relances_horaires)
            VALUES (?, 0, ?)
        ");
        $stmt->execute([$shop_id, $default_horaires]);
        
        $config = [
            'est_active' => false,
            'relances_horaires' => ['09:00', '14:00', '17:00'],
            'derniere_execution' => null
        ];
    } else {
        // Décoder les horaires JSON
        $config['relances_horaires'] = json_decode($config['relances_horaires'], true) ?: ['09:00', '14:00', '17:00'];
        $config['est_active'] = (bool)$config['est_active'];
    }
    
    echo json_encode([
        'success' => true,
        'config' => $config
    ]);

} catch (Exception $e) {
    error_log("Erreur get_relance_auto_config.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement de la configuration : ' . $e->getMessage()
    ]);
}
?>

