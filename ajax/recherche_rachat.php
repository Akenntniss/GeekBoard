<?php
// Vérifier si la session est déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à ces données
// Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé. Veuillez vous connecter en tant qu\'administrateur.']);
    exit;
}
*/

// Paramètres de recherche et pagination
$search = isset($_POST['search']) ? cleanInput($_POST['search']) : '';
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$status = isset($_POST['status']) ? cleanInput($_POST['status']) : '';
$limit = 10; // Nombre d'éléments par page
$offset = ($page - 1) * $limit;

try {
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }
    
    // Construction de la requête avec conditions
    $whereConditions = [];
    $params = [];
    
    // Recherche textuelle
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $whereConditions[] = "(c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR c.email LIKE ? OR r.modele LIKE ? OR r.sin LIKE ?)";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Filtre par statut (la table n'a pas de colonne statut, on utilise une condition simple)
    if (!empty($status)) {
        // Pour l'instant, on ne filtre pas par statut car la colonne n'existe pas
        // $whereConditions[] = "r.statut = ?";
        // $params[] = $status;
    }
    
    $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Compter le total pour la pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM rachat_appareils r
                 JOIN clients c ON r.client_id = c.id
                 $whereClause";
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Requête principale avec pagination
    $sql = "SELECT 
            r.id, 
            r.modele, 
            r.sin, 
            r.prix,
            r.date_rachat as date_creation,
            r.photo_appareil, 
            r.photo_identite,
            r.client_photo, 
            r.signature,
            CONCAT(c.nom, ' ', c.prenom) as client_nom,
            c.nom as client_nom_seul,
            c.prenom as client_prenom
        FROM rachat_appareils r
        JOIN clients c ON r.client_id = c.id
        $whereClause
        ORDER BY r.date_rachat DESC
        LIMIT $limit OFFSET $offset";
        
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traitement des résultats
    foreach ($rachats as &$rachat) {
        // Ajouter le statut par défaut (la colonne n'existe pas dans la table)
        $rachat['statut'] = 'nouveau';
        
        // Formater le prix (utiliser 'prix' au lieu de 'prix_rachat')
        $rachat['prix_rachat'] = number_format((float)$rachat['prix'], 2, '.', '');
        
        // Séparer nom et prénom pour compatibilité
        $rachat['client_nom'] = $rachat['client_nom_seul'];
        $rachat['client_prenom'] = $rachat['client_prenom'];
        
        // Formater la date
        if ($rachat['date_creation']) {
            $rachat['date_creation'] = date('Y-m-d H:i:s', strtotime($rachat['date_creation']));
        }
        
        // Corriger le nom de la colonne photo client
        $rachat['photo_client'] = $rachat['client_photo'];
    }
    
    // Calculer les informations de pagination
    $totalPages = ceil($total / $limit);
    
    $pagination = [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total' => $total,
        'limit' => $limit,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1
    ];

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'rachats' => $rachats,
        'pagination' => $pagination
    ]);

} catch (Exception $e) {
    error_log("Erreur dans recherche_rachat.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage(),
        'rachats' => [],
        'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total' => 0]
    ]);
}
?>