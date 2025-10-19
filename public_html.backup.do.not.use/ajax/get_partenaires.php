<?php
// Inclure la configuration de session avant de démarrer la session
require_once dirname(__DIR__) . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Inclure la configuration pour la gestion des sous-domaines
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Initialiser la session du magasin si nécessaire
if (!isset($_SESSION['shop_id'])) {
    $detected_shop_id = detectShopFromSubdomain();
    if ($detected_shop_id) {
        $_SESSION['shop_id'] = $detected_shop_id;
    }
}

// Ajouter un logging de la session pour débogage
error_log("get_partenaires.php - Session data: " . json_encode($_SESSION));
error_log("get_partenaires.php - Session ID: " . session_id());
error_log("get_partenaires.php - Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini'));

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si le shop_id est défini
if (!isset($_SESSION['shop_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Shop non identifié']);
    exit;
}

header('Content-Type: application/json');

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }

    // Récupérer tous les partenaires avec leurs informations
    $stmt = $shop_pdo->prepare("
        SELECT 
            p.id,
            p.nom,
            p.email,
            p.telephone,
            p.adresse,
            p.actif,
            p.date_creation,
            COALESCE(s.solde_actuel, 0) as solde_actuel
        FROM partenaires p
        LEFT JOIN soldes_partenaires s ON p.id = s.partenaire_id
        ORDER BY p.nom ASC
    ");
    
    $stmt->execute();
    $partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour l'affichage
    foreach ($partenaires as &$partenaire) {
        $partenaire['solde_actuel'] = number_format((float)$partenaire['solde_actuel'], 2, '.', '');
        $partenaire['date_creation_formatted'] = date('d/m/Y', strtotime($partenaire['date_creation']));
        $partenaire['statut_display'] = $partenaire['actif'] ? 'actif' : 'inactif';
        
        // Ajouter des données supplémentaires pour l'affichage
        $partenaire['balance_class'] = (float)$partenaire['solde_actuel'] >= 0 ? 'positive' : 'negative';
        $partenaire['balance_prefix'] = (float)$partenaire['solde_actuel'] >= 0 ? '+' : '';
        $partenaire['status_class'] = $partenaire['actif'] ? 'active' : 'inactive';
        $partenaire['status_icon'] = $partenaire['actif'] ? 'fas fa-check-circle' : 'fas fa-times-circle';
    }
    
    error_log("Partenaires récupérés avec succès - Count: " . count($partenaires) . ", Shop: " . ($_SESSION['shop_id'] ?? 'N/A'));
    
    echo json_encode([
        'success' => true,
        'partenaires' => $partenaires,
        'count' => count($partenaires)
    ]);

} catch (Exception $e) {
    error_log("Erreur get_partenaires.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des partenaires: ' . $e->getMessage()
    ]);
}
?>
