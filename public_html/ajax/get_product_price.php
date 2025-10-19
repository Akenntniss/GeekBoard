<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if ($product_id === false || $product_id === null) {
    echo json_encode(['success' => false, 'message' => 'ID produit manquant ou invalide']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    
    $stmt = $pdo->prepare("SELECT prix_vente FROM produits WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit;
    }
    
    $price = floatval($product['prix_vente'] ?? 0);
    
    echo json_encode([
        'success' => true, 
        'price' => $price
    ]);

} catch (Exception $e) {
    error_log("Erreur get_product_price.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>

