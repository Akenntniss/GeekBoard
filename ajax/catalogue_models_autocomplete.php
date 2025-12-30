<?php
/**
 * Endpoint AJAX pour l'autocomplete des modèles du catalogue
 * Retourne les modèles correspondant à la recherche
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../config/session_config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$limit = isset($_GET['limit']) ? min(50, max(5, intval($_GET['limit']))) : 20;

try {
    $shop_pdo = getShopDBConnection();
    
    $where = ["model IS NOT NULL", "model != ''"];
    $params = [];
    
    // Filtrer par query si fourni
    if (!empty($query)) {
        $where[] = "model LIKE :query";
        $params['query'] = "%{$query}%";
    }
    
    // Filtrer par marque si fournie
    if (!empty($brand)) {
        $where[] = "brand = :brand";
        $params['brand'] = $brand;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $where);
    
    // Récupérer les modèles uniques avec leur count
    $sql = "
        SELECT model, brand, COUNT(*) as product_count
        FROM catalogue_fournisseur
        $whereClause
        GROUP BY model, brand
        ORDER BY 
            CASE WHEN model LIKE :order_query THEN 0 ELSE 1 END,
            product_count DESC,
            model ASC
        LIMIT :limit
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':order_query', $query . '%');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $models,
        'count' => count($models)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
