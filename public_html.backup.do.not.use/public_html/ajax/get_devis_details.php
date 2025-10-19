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
    $devis_id = $_GET['id'] ?? null;
    
    if (!$devis_id) {
        throw new Exception('ID du devis manquant');
    }
    
    // Récupérer les informations principales du devis
    $stmt = $shop_pdo->prepare("
        SELECT 
            d.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            c.email as client_email,
            r.description_probleme as reparation_probleme,
            r.type_appareil as reparation_marque,
            r.modele as reparation_modele
        FROM devis d
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE d.id = ?
    ");
    
    $stmt->execute([$devis_id]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$devis) {
        throw new Exception('Devis non trouvé');
    }
    
    // Récupérer les pannes
    $stmt = $shop_pdo->prepare("
        SELECT * FROM devis_pannes 
        WHERE devis_id = ? 
        ORDER BY id
    ");
    $stmt->execute([$devis_id]);
    $devis['pannes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les solutions
    $stmt = $shop_pdo->prepare("
        SELECT * FROM devis_solutions 
        WHERE devis_id = ? 
        ORDER BY ordre
    ");
    $stmt->execute([$devis_id]);
    $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque solution, récupérer ses éléments
    foreach ($solutions as &$solution) {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM devis_solutions_items 
            WHERE solution_id = ? 
            ORDER BY ordre
        ");
        $stmt->execute([$solution['id']]);
        $solution['elements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $devis['solutions'] = $solutions;
    
    // Récupérer les logs du devis
    $stmt = $shop_pdo->prepare("
        SELECT * FROM devis_logs 
        WHERE devis_id = ? 
        ORDER BY date_action DESC
    ");
    $stmt->execute([$devis_id]);
    $devis['logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer des informations supplémentaires
    $now = new DateTime();
    $expiration = new DateTime($devis['date_expiration']);
    $diff = $expiration->diff($now);
    
    if ($expiration < $now) {
        $devis['jours_expires'] = $diff->days;
        $devis['est_expire'] = true;
        $devis['gardiennage_facture'] = $diff->days * 5; // 5€ par jour
    } else {
        $devis['jours_restants'] = $diff->days;
        $devis['est_expire'] = false;
        $devis['gardiennage_facture'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'devis' => $devis
    ]);

} catch (Exception $e) {
    error_log("Erreur get_devis_details.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des détails : ' . $e->getMessage()
    ]);
}
?> 