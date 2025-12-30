<?php
/**
 * API REST v2 - Liste des Réparations
 * GeekBoard Desktop Application
 * 
 * GET /api/v2/reparations/list
 * Header: Authorization: Bearer <token>
 * Query: ?page=1&limit=50&status=en_cours&search=xxx
 */

require_once __DIR__ . '/../config.php';

// Vérifier l'authentification
$payload = require_auth();

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_response('Méthode non autorisée', 405);
}

try {
    // Connexion à la base du magasin
    $shop_pdo = get_shop_connection($payload['subdomain']);
    
    if (!$shop_pdo) {
        error_response('Impossible de se connecter à la base du magasin', 500);
    }
    
    // Paramètres de pagination et filtres
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;
    
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    // Construction de la requête
    $where_clauses = [];
    $params = [];
    
    if (!empty($status)) {
        $where_clauses[] = "r.status = ?";
        $params[] = $status;
    }
    
    if (!empty($search)) {
        $where_clauses[] = "(r.numero LIKE ? OR c.nom LIKE ? OR c.telephone LIKE ? OR r.appareil LIKE ? OR r.marque LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($date_from)) {
        $where_clauses[] = "DATE(r.date_creation) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_clauses[] = "DATE(r.date_creation) <= ?";
        $params[] = $date_to;
    }
    
    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total FROM reparations r 
                  LEFT JOIN clients c ON r.client_id = c.id 
                  $where_sql";
    $stmt = $shop_pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Récupérer les réparations
    $sql = "SELECT 
                r.id,
                r.numero,
                r.appareil,
                r.marque,
                r.modele,
                r.probleme,
                r.status,
                r.prix,
                r.date_creation,
                r.date_modification,
                r.date_livraison,
                c.id as client_id,
                c.nom as client_nom,
                c.prenom as client_prenom,
                c.telephone as client_telephone,
                c.email as client_email
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            $where_sql
            ORDER BY r.date_creation DESC
            LIMIT $limit OFFSET $offset";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $reparations = $stmt->fetchAll();
    
    // Calculer les métadonnées de pagination
    $total_pages = ceil($total / $limit);
    
    success_response([
        'data' => $reparations,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => $total_pages,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("API v2 Reparations List Error: " . $e->getMessage());
    error_response('Erreur lors de la récupération des réparations', 500);
} catch (Exception $e) {
    error_log("API v2 Reparations List Error: " . $e->getMessage());
    error_response('Erreur interne du serveur', 500);
}
?>
