<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration des sous-domaines pour la détection automatique du magasin
require_once __DIR__ . '/../config/subdomain_config.php';

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';

// Vérifier l'accès au magasin (pas besoin d'utilisateur connecté pour cette page)
if (!isset($_SESSION['shop_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Accès non autorisé - Magasin non détecté']);
    exit();
}

header('Content-Type: application/json');

try {
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }

    // Récupérer les paramètres de pagination
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Récupérer les paramètres de tri
    $sort = isset($_POST['sort']) ? $_POST['sort'] : 'date_rachat';
    $direction = isset($_POST['direction']) && $_POST['direction'] === 'asc' ? 'ASC' : 'DESC';

    // Valider les colonnes de tri autorisées
    $allowed_sorts = ['date_rachat', 'nom_client', 'modele', 'etat', 'prix_rachat'];
    if (!in_array($sort, $allowed_sorts)) {
        $sort = 'date_rachat';
    }

    // Construire la requête de base
    $sql = "SELECT 
                r.id,
                r.date_rachat,
                r.type_appareil,
                r.modele,
                r.sin,
                r.fonctionnel,
                r.prix,
                r.photo_appareil,
                r.photo_identite,
                r.client_photo,
                r.signature,
                c.nom,
                c.prenom,
                c.telephone,
                c.email,
                CONCAT(c.nom, ' ', c.prenom) as nom_client,
                CASE 
                    WHEN r.fonctionnel = 1 THEN 'fonctionnel'
                    ELSE 'non_fonctionnel'
                END as etat,
                r.prix as prix_rachat
            FROM rachat_appareils r
            JOIN clients c ON r.client_id = c.id
            WHERE 1=1";

    $params = [];

    // Appliquer les filtres
    if (!empty($_POST['client'])) {
        $sql .= " AND CONCAT(c.nom, ' ', c.prenom) LIKE ?";
        $params[] = '%' . $_POST['client'] . '%';
    }

    if (!empty($_POST['modele'])) {
        $sql .= " AND r.modele LIKE ?";
        $params[] = '%' . $_POST['modele'] . '%';
    }

    if (!empty($_POST['etat'])) {
        if ($_POST['etat'] === 'fonctionnel') {
            $sql .= " AND r.fonctionnel = 1";
        } elseif ($_POST['etat'] === 'non_fonctionnel') {
            $sql .= " AND r.fonctionnel = 0";
        }
    }

    if (!empty($_POST['dateDebut'])) {
        $sql .= " AND DATE(r.date_rachat) >= ?";
        $params[] = $_POST['dateDebut'];
    }

    if (!empty($_POST['dateFin'])) {
        $sql .= " AND DATE(r.date_rachat) <= ?";
        $params[] = $_POST['dateFin'];
    }

    if (!empty($_POST['prixMin'])) {
        $sql .= " AND r.prix >= ?";
        $params[] = (float)$_POST['prixMin'];
    }

    if (!empty($_POST['prixMax'])) {
        $sql .= " AND r.prix <= ?";
        $params[] = (float)$_POST['prixMax'];
    }

    if (!empty($_POST['search'])) {
        $sql .= " AND (c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR c.email LIKE ? OR r.modele LIKE ? OR r.sin LIKE ?)";
        $searchTerm = '%' . $_POST['search'] . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    // Compter le total des résultats
    $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalResults = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Ajouter le tri et la pagination
    $sql .= " ORDER BY " . $sort . " " . $direction;
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Exécuter la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer les informations de pagination
    $totalPages = ceil($totalResults / $limit);
    $pagination = [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_results' => $totalResults,
        'per_page' => $limit,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1
    ];

    // Retourner les résultats
    echo json_encode([
        'success' => true,
        'data' => $results,
        'pagination' => $pagination
    ]);

} catch (Exception $e) {
    error_log("Erreur dans recherche_rachat_advanced.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
    ]);
}
?> 