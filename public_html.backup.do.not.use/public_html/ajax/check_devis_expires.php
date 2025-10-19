<?php
header('Content-Type: application/json');

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/subdomain_database_detector.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Récupérer le shop_id depuis l'URL ou utiliser le SubdomainDatabaseDetector
    $shop_id = $_GET['shop_id'] ?? null;
    
    if ($shop_id) {
        // Utiliser la méthode standard avec shop_id
        $shop_pdo = getShopDBConnectionById($shop_id);
    } else {
        // Utiliser le SubdomainDatabaseDetector comme fallback
        $detector = new SubdomainDatabaseDetector();
        $shopConfig = $detector->detectShopFromSubdomain();
        
        if (!$shopConfig) {
            throw new Exception('Shop non détecté');
        }
        
        $shop_pdo = $detector->getShopConnection();
    }
    
    // Compter les devis expirés
    $stmt = $shop_pdo->query("
        SELECT COUNT(*) as total 
        FROM devis 
        WHERE statut = 'envoye' AND date_expiration <= NOW()
    ");
    
    $devis_expires = $stmt->fetch()['total'];
    
    // Récupérer les détails des devis expirés
    $stmt = $shop_pdo->query("
        SELECT 
            d.id,
            d.numero,
            d.date_expiration,
            DATEDIFF(NOW(), d.date_expiration) as jours_expires,
            c.nom as client_nom,
            c.prenom as client_prenom
        FROM devis d
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE d.statut = 'envoye' AND d.date_expiration <= NOW()
        ORDER BY d.date_expiration ASC
    ");
    
    $devis_expires_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer le montant total du gardiennage
    $gardiennage_total = 0;
    foreach ($devis_expires_details as &$devis) {
        $devis['gardiennage_facture'] = $devis['jours_expires'] * 5; // 5€ par jour
        $gardiennage_total += $devis['gardiennage_facture'];
    }
    
    echo json_encode([
        'success' => true,
        'devis_expires' => $devis_expires,
        'devis_expires_details' => $devis_expires_details,
        'gardiennage_total' => $gardiennage_total
    ]);

} catch (Exception $e) {
    error_log("Erreur check_devis_expires.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la vérification : ' . $e->getMessage()
    ]);
}
?> 