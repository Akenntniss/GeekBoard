<?php
/**
 * Liste des garanties avec filtres
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Initialiser la session shop
initializeShopSession();

header('Content-Type: application/json');

try {
    $shop_pdo = getShopDBConnection();
    
    // Construction de la requête avec filtres
    $where_conditions = [];
    $params = [];
    
    // Filtre par statut
    if (!empty($_GET['status'])) {
        $where_conditions[] = "g.statut = ?";
        $params[] = $_GET['status'];
    }
    
    // Filtre par expiration
    if (!empty($_GET['expiration'])) {
        $expiration = $_GET['expiration'];
        
        if ($expiration === 'expired') {
            $where_conditions[] = "g.date_fin < NOW()";
        } else {
            $days = intval($expiration);
            if ($days > 0) {
                $where_conditions[] = "g.date_fin BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)";
                $params[] = $days;
            }
        }
    }
    
    // Filtre par client
    if (!empty($_GET['client'])) {
        $where_conditions[] = "(c.nom LIKE ? OR c.prenom LIKE ?)";
        $search_term = '%' . $_GET['client'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Construction de la clause WHERE
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(10, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Requête principale
    $sql = "
        SELECT 
            g.id as garantie_id,
            g.date_debut,
            g.date_fin,
            g.duree_jours,
            g.statut as statut_garantie,
            g.description_garantie,
            r.id as reparation_id,
            r.type_appareil,
            r.modele,
            r.description_probleme,
            r.prix_reparation,
            c.id as client_id,
            c.nom,
            c.prenom,
            c.telephone,
            c.email,
            DATEDIFF(g.date_fin, NOW()) as jours_restants,
            CASE 
                WHEN g.date_fin < NOW() THEN 'Expirée'
                WHEN DATEDIFF(g.date_fin, NOW()) <= 7 THEN 'Expire bientôt'
                ELSE 'Active'
            END as alerte_expiration
        FROM garanties g
        JOIN reparations r ON g.reparation_id = r.id
        JOIN clients c ON r.client_id = c.id
        $where_clause
        ORDER BY 
            CASE g.statut 
                WHEN 'active' THEN 1
                WHEN 'expiree' THEN 2
                WHEN 'utilisee' THEN 3
                WHEN 'annulee' THEN 4
            END,
            g.date_fin ASC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $warranties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total pour la pagination
    $count_sql = "
        SELECT COUNT(*) 
        FROM garanties g
        JOIN reparations r ON g.reparation_id = r.id
        JOIN clients c ON r.client_id = c.id
        $where_clause
    ";
    
    $count_params = array_slice($params, 0, -2); // Enlever limit et offset
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total = $count_stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $warranties,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'items_per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erreur liste garanties: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
