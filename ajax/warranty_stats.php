<?php
/**
 * Statistiques des garanties
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
// Initialiser la session shop
initializeShopSession();
header('Content-Type: application/json');
try {
    $shop_pdo = getShopDBConnection();
    
    // Statistiques des garanties
    $stats = [
        'active' => 0,
        'expiring' => 0,
        'expired' => 0,
        'claims' => 0
    ];
    
    // Garanties actives
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM garanties WHERE statut = 'active'");
    $stmt->execute();
    $stats['active'] = $stmt->fetchColumn();
    
    // Garanties qui expirent bientôt (dans les 7 prochains jours)
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(*) 
        FROM garanties 
        WHERE statut = 'active' 
        AND date_fin BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $stats['expiring'] = $stmt->fetchColumn();
    
    // Garanties expirées
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM garanties WHERE statut = 'expiree'");
    $stmt->execute();
    $stats['expired'] = $stmt->fetchColumn();
    
    // Réclamations de garantie
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM reclamations_garantie");
    $stmt->execute();
    $stats['claims'] = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Erreur stats garantie: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
