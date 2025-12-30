<?php
// Inclure la configuration de la base de données et les fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser la session du magasin si nécessaire
if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

// Vérifier que la requête est bien en GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Obtenir la connexion à la base de données
$shop_pdo = null;
try {
    // Récupérer la connexion à la base du magasin
    if (function_exists('getShopDBConnection')) {
        $shop_pdo = getShopDBConnection();
    }
    
    // Si pas de connexion via magasin, essayer la connexion principale
    if ($shop_pdo === null && function_exists('getMainDBConnection')) {
        $shop_pdo = getMainDBConnection();
    }
    
    // Si toujours pas de connexion, utiliser $main_pdo global
    if ($shop_pdo === null) {
        global $main_pdo;
        $shop_pdo = $main_pdo;
    }
    
    if ($shop_pdo === null) {
        throw new Exception("Impossible d'établir une connexion à la base de données");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit;
}

try {
    // Récupérer les réparations terminées (statuts 9 et 10 ou codes spécifiques)
    $stmt = $shop_pdo->prepare("
        SELECT 
            r.id,
            r.type_appareil,
            r.modele,
            r.statut,
            r.date_modification,
            r.date_creation,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone,
            s.nom as statut_nom,
            s.id as statut_id
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON s.code = r.statut
        WHERE r.statut IN (
            SELECT code FROM statuts WHERE id IN (9, 10)
            UNION
            SELECT 'reparation_effectue'
            UNION 
            SELECT 'pret_a_recuperer'
        )
        ORDER BY r.date_modification DESC
        LIMIT 100
    ");
    
    $stmt->execute();
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour le frontend
    $formattedRepairs = [];
    foreach ($repairs as $repair) {
        $formattedRepairs[] = [
            'id' => $repair['id'],
            'type_appareil' => $repair['type_appareil'] ?: 'N/A',
            'modele' => $repair['modele'] ?: '',
            'statut' => $repair['statut'],
            'statut_nom' => $repair['statut_nom'] ?: ucfirst(str_replace('_', ' ', $repair['statut'])),
            'statut_id' => $repair['statut_id'],
            'date_modification' => $repair['date_modification'],
            'date_creation' => $repair['date_creation'],
            'client_nom' => $repair['client_nom'] ?: 'Client inconnu',
            'client_prenom' => $repair['client_prenom'] ?: '',
            'telephone' => $repair['telephone'] ?: ''
        ];
    }
    
    // Retourner les données
    echo json_encode([
        'success' => true,
        'repairs' => $formattedRepairs,
        'count' => count($formattedRepairs),
        'message' => count($formattedRepairs) . ' réparations trouvées'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des réparations: ' . $e->getMessage(),
        'repairs' => []
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'repairs' => []
    ]);
}
?>

