<?php
// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser la session du magasin
require_once '../config/database.php';

// Vérifier si la fonction existe avant de l'appeler
if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    // Vérifier si la colonne suivre_stock existe
    $stmt = $shop_pdo->query("SHOW COLUMNS FROM produits LIKE 'suivre_stock'");
    $column_exists = $stmt->rowCount() > 0;
    
    if ($column_exists) {
        // Récupérer tous les produits avec suivre_stock = 1
        $stmt = $shop_pdo->prepare("
            SELECT p.id, p.reference, p.nom, p.quantite, p.seuil_alerte, p.prix_achat, p.prix_vente
            FROM produits p 
            WHERE p.suivre_stock = 1
            ORDER BY p.nom ASC
        ");
    } else {
        // Si la colonne n'existe pas, récupérer tous les produits
        $stmt = $shop_pdo->prepare("
            SELECT p.id, p.reference, p.nom, p.quantite, p.seuil_alerte, p.prix_achat, p.prix_vente
            FROM produits p 
            ORDER BY p.nom ASC
        ");
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products),
        'column_exists' => $column_exists
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la récupération des produits: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
