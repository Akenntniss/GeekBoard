<?php
// Désactiver l'affichage des erreurs dans la sortie JSON
error_reporting(0);
ini_set('display_errors', 0);

// Inclure les configurations obligatoires GeekBoard dans le bon ordre
require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Définir l'en-tête JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    // Log pour diagnostic
    error_log("get_tracked_products.php - HOST: " . ($_SERVER['HTTP_HOST'] ?? 'non défini'));
    
    // Initialiser la session du shop si nécessaire
    if (!isset($_SESSION['shop_id'])) {
        initializeShopSession();
    }
    
    // Obtenir la connexion à la base de données
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        // Log pour débogage
        error_log("get_tracked_products.php: Impossible d'obtenir la connexion PDO");
        error_log("Session shop_id: " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'non défini'));
        error_log("Session db_name: " . (isset($_SESSION['shop_db_name']) ? $_SESSION['shop_db_name'] : 'non défini'));
        
        throw new Exception("Impossible de se connecter à la base de données du magasin. Vérifiez que le sous-domaine est correctement configuré.");
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
