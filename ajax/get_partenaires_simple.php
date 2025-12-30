<?php
// ajax/get_partenaires_simple.php
// Script léger pour récupérer la liste simple des partenaires (ID, Nom)

header('Content-Type: application/json');

try {
    // 1. Config & Session
    require_once dirname(__DIR__) . '/config/session_config.php';
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/config/subdomain_config.php';

    // 2. Auth Check
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Non autorisé');
    }

    // 3. Init Shop Session if needed
    if (!isset($_SESSION['shop_id'])) {
        $detected = detectShopFromSubdomain();
        if ($detected) {
            initializeShopSession(); 
        } else {
            throw new Exception('Boutique non identifiée');
        }
    }

    // 4. DB Connection
    $pdo = getShopDBConnection();
    
    // 5. Query
    // On ne récupère que ce qui est utile pour le dropdown
    // NOTE: On ne filtre pas par statut car add_partenaire.php ne définit pas de statut par défaut
    $stmt = $pdo->prepare("SELECT id, nom FROM partenaires ORDER BY nom ASC");
    $stmt->execute();
    $partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'partenaires' => $partenaires
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
