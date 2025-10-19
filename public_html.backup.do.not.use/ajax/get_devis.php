<?php
header('Content-Type: application/json');

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

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
    
    // Récupérer les paramètres
    $statut = $_GET['statut'] ?? 'en_attente';
    
    // Construire la condition WHERE selon le statut
    $whereCondition = '';
    switch ($statut) {
        case 'en_attente':
            $whereCondition = "d.statut = 'envoye' AND d.date_expiration > NOW()";
            break;
        case 'accepte':
            $whereCondition = "d.statut = 'accepte'";
            break;
        case 'refuse':
            $whereCondition = "d.statut = 'refuse'";
            break;
        case 'expire':
            $whereCondition = "d.statut = 'envoye' AND d.date_expiration <= NOW()";
            break;
        default:
            $whereCondition = "d.statut = 'envoye'";
    }
    
    // Requête pour récupérer les devis
    $stmt = $shop_pdo->prepare("
        SELECT 
            d.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            r.description_probleme as reparation_probleme
        FROM devis d
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE $whereCondition
        ORDER BY d.date_creation DESC
    ");
    
    $stmt->execute();
    $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter des informations calculées
    foreach ($devis as &$devis_item) {
        // Calculer l'expiration
        $now = new DateTime();
        $expiration = new DateTime($devis_item['date_expiration']);
        $diff = $expiration->diff($now);
        
        if ($expiration < $now) {
            $devis_item['jours_expires'] = $diff->days;
            $devis_item['est_expire'] = true;
        } else {
            $devis_item['jours_restants'] = $diff->days;
            $devis_item['est_expire'] = false;
        }
    }
    
    echo json_encode([
        'success' => true,
        'devis' => $devis,
        'total' => count($devis)
    ]);

} catch (Exception $e) {
    error_log("Erreur get_devis.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des devis : ' . $e->getMessage()
    ]);
}
?> 