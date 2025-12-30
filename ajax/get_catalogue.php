<?php
/**
 * Endpoint AJAX pour récupérer les produits du catalogue avec filtres et pagination
 */

header('Content-Type: application/json');

require_once '../config/session_config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Paramètres de filtrage
    $fournisseur_id = isset($_GET['fournisseur_id']) ? intval($_GET['fournisseur_id']) : 0;
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
    $device_type = isset($_GET['device_type']) ? trim($_GET['device_type']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $stock_filter = isset($_GET['stock']) ? trim($_GET['stock']) : '';
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 50;
    $offset = ($page - 1) * $limit;
    
    // Construire la requête
    $where = [];
    $params = [];
    
    if ($fournisseur_id > 0) {
        $where[] = "cf.fournisseur_id = :fournisseur_id";
        $params['fournisseur_id'] = $fournisseur_id;
    }
    
    if (!empty($type)) {
        $where[] = "cf.type = :type";
        $params['type'] = $type;
    }
    
    if (!empty($brand)) {
        $where[] = "cf.brand = :brand";
        $params['brand'] = $brand;
    }
    
    if (!empty($device_type)) {
        $where[] = "cf.device_type = :device_type";
        $params['device_type'] = $device_type;
    }
    
    $model = isset($_GET['model']) ? trim($_GET['model']) : '';
    if (!empty($model)) {
        $where[] = "cf.model = :model";
        $params['model'] = $model;
    }
    
    if (!empty($stock_filter)) {
        if ($stock_filter === 'en_stock') {
            $where[] = "cf.stock LIKE '%En stock%'";
        } elseif ($stock_filter === 'rupture') {
            $where[] = "cf.stock LIKE '%Rupture%'";
        }
    }
    
    if (!empty($search)) {
        // Analyser la requête pour identifier les composants
        $words = preg_split('/\s+/', strtolower($search));
        
        // Mots-clés de type de pièce
        $pieceTypes = ['ecran', 'écran', 'batterie', 'connecteur', 'camera', 'caméra', 'vitre', 'lcd', 'tactile', 'nappe', 'haut-parleur', 'micro', 'bouton', 'chassis', 'coque', 'protection'];
        
        // Marques connues
        $brandMappings = [
            'samsung' => 'Samsung', 'galaxy' => 'Samsung',
            'apple' => 'Apple', 'iphone' => 'Apple', 'ipad' => 'Apple',
            'xiaomi' => 'Xiaomi', 'redmi' => 'Xiaomi', 'poco' => 'Xiaomi',
            'huawei' => 'Huawei', 'honor' => 'Honor',
            'oppo' => 'Oppo', 'oneplus' => 'OnePlus',
            'google' => 'Google', 'pixel' => 'Google',
            'motorola' => 'Motorola', 'moto' => 'Motorola',
            'nokia' => 'Nokia', 'sony' => 'Sony', 'lg' => 'LG',
            'realme' => 'Realme', 'vivo' => 'Vivo', 'asus' => 'Asus',
            'nothing' => 'Nothing', 'fairphone' => 'Fairphone'
        ];
        
        $detectedType = null;
        $detectedBrand = null;
        $modelWords = [];
        
        foreach ($words as $word) {
            if (strlen($word) < 2) continue;
            
            // Détecter le type de pièce
            foreach ($pieceTypes as $type) {
                if (strpos($type, $word) !== false || strpos($word, $type) !== false) {
                    $detectedType = $word;
                    break;
                }
            }
            
            // Détecter la marque
            if (isset($brandMappings[$word])) {
                $detectedBrand = $brandMappings[$word];
                continue; // Ne pas ajouter aux mots du modèle
            }
            
            // Si c'est pas un type de pièce, c'est probablement un mot du modèle
            if ($detectedType !== $word) {
                $modelWords[] = $word;
            }
        }
        
        // Construire les conditions
        
        // CONDITION 1: Le type de pièce (ecran, batterie, etc.) DOIT être dans le nom
        if ($detectedType) {
            $where[] = "cf.name LIKE :type_filter";
            $params['type_filter'] = "%{$detectedType}%";
        }
        
        // CONDITION 2: La marque si détectée
        if ($detectedBrand) {
            $where[] = "cf.brand = :detected_brand";
            $params['detected_brand'] = $detectedBrand;
        }
        
        // CONDITION 3: Les mots du modèle doivent être dans le champ model
        if (!empty($modelWords)) {
            $modelConditions = [];
            foreach ($modelWords as $i => $word) {
                $paramName = "model_w{$i}";
                $modelConditions[] = "cf.model LIKE :{$paramName}";
                $params[$paramName] = "%{$word}%";
            }
            if (!empty($modelConditions)) {
                $where[] = "(" . implode(" AND ", $modelConditions) . ")";
            }
        }
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Compter le total
    $countSql = "SELECT COUNT(*) as total FROM catalogue_fournisseur cf $whereClause";
    $countStmt = $shop_pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Paramètres de tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : '';
    $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

    // Logique de tri
    $orderByClause = "";
    
    if ($sortBy === 'price') {
        $orderByClause = "ORDER BY cf.price $order";
    } elseif ($sortBy === 'name') {
        $orderByClause = "ORDER BY cf.name $order";
    } else {
        // Défaut : Prioriser stock, puis alphabétique
        $orderByClause = "ORDER BY CASE WHEN cf.stock LIKE '%En stock%' THEN 0 ELSE 1 END, cf.name ASC";
    }
    
    // Récupérer les données
    $sql = "
        SELECT cf.*, f.nom as fournisseur_nom
        FROM catalogue_fournisseur cf
        LEFT JOIN fournisseurs f ON cf.fournisseur_id = f.id
        $whereClause
        $orderByClause
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer les infos de pagination
    $totalPages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
