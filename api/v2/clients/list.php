<?php
/**
 * API REST v2 - Liste des Clients
 * GeekBoard Desktop Application
 * 
 * GET /api/v2/clients/list
 * Header: Authorization: Bearer <token>
 * Query: ?page=1&limit=50&search=xxx
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
    
    $search = $_GET['search'] ?? '';
    
    // Construction de la requête
    $where_clauses = [];
    $params = [];
    
    if (!empty($search)) {
        $where_clauses[] = "(c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR c.email LIKE ?)";
        $search_param = "%$search%";
        $params = [$search_param, $search_param, $search_param, $search_param];
    }
    
    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total FROM clients c $where_sql";
    $stmt = $shop_pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Récupérer les clients avec le nombre de réparations
    $sql = "SELECT 
                c.id,
                c.nom,
                c.prenom,
                c.telephone,
                c.email,
                c.adresse,
                c.date_creation,
                COUNT(r.id) as nb_reparations,
                MAX(r.date_creation) as derniere_reparation
            FROM clients c
            LEFT JOIN reparations r ON c.id = r.client_id
            $where_sql
            GROUP BY c.id
            ORDER BY c.nom ASC, c.prenom ASC
            LIMIT $limit OFFSET $offset";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
    
    // Calculer les métadonnées de pagination
    $total_pages = ceil($total / $limit);
    
    success_response([
        'data' => $clients,
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
    error_log("API v2 Clients List Error: " . $e->getMessage());
    error_response('Erreur lors de la récupération des clients', 500);
} catch (Exception $e) {
    error_log("API v2 Clients List Error: " . $e->getMessage());
    error_response('Erreur interne du serveur', 500);
}
?>
