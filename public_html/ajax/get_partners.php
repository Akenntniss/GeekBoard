<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, nom, email, telephone 
        FROM partenaires 
        WHERE actif = 1 
        ORDER BY nom ASC
    ");
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'partners' => $partners
    ]);

} catch (Exception $e) {
    error_log("Erreur get_partners.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>

