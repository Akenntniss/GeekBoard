<?php
/**
 * API pour récupérer la liste des partenaires
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Initialiser la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser la connexion à la base de données du magasin
initializeShopSession();

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    
    // Récupérer tous les partenaires actifs
    $stmt = $pdo->prepare("
        SELECT id, nom, email, telephone, adresse, date_creation
        FROM partenaires 
        WHERE actif = 1 
        ORDER BY nom ASC
    ");
    
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'partners' => $partners,
        'count' => count($partners)
    ]);

} catch (Exception $e) {
    error_log("Erreur API get_partners: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des partenaires: ' . $e->getMessage()
    ]);
}
?>
