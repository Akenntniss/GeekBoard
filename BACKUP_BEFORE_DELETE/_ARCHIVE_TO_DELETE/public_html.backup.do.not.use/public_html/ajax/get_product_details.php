<?php
// Récupérer les détails complets d'un produit
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID manquant');
    }
    
    $produit_id = intval($_GET['id']);
    
    if ($produit_id <= 0) {
        throw new Exception('ID invalide');
    }
    
    // Connexion directe comme pour ajuster_stock_minimal.php
    $host = 'localhost';
    $dbname = 'geekboard_mkmkmk';
    $username = 'root';
    $password = 'Mamanmaman01#';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer le produit complet avec le fournisseur
    $stmt = $pdo->prepare("
        SELECT p.id, p.reference, p.nom, p.description, p.prix_achat, p.prix_vente, 
               p.quantite, p.seuil_alerte, p.suivre_stock, p.fournisseur_id,
               f.nom as fournisseur_nom
        FROM produits p 
        LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id
        WHERE p.id = ?
    ");
    $stmt->execute([$produit_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
