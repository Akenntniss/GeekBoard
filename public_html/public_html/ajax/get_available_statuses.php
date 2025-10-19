<?php
// Désactiver l'affichage des erreurs PHP pour la production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Démarrer la session pour avoir accès à l'ID du magasin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'ID du magasin depuis les paramètres POST ou GET
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;

// Définir l'ID du magasin en session si fourni dans la requête
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session magasin si nécessaire
if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

header('Content-Type: application/json');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }

    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }

    // Récupérer tous les statuts disponibles (schéma réel)
    $query = "
        SELECT 
            s.id,
            s.code,
            s.nom,
            sc.couleur
        FROM statuts s
        LEFT JOIN statut_categories sc ON sc.id = s.categorie_id
        WHERE s.est_actif = 1
        ORDER BY s.ordre ASC, s.nom ASC
    ";

    $stmt = $shop_pdo->prepare($query);
    $stmt->execute();
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données pour le frontend
    $formatted_statuses = [];
    foreach ($statuses as $status) {
        $formatted_statuses[] = [
            'id' => $status['id'],
            'code' => $status['code'],
            'libelle' => $status['nom'],
            'couleur' => $status['couleur']
        ];
    }

    echo json_encode([
        'success' => true,
        'statuses' => $formatted_statuses,
        'count' => count($formatted_statuses)
    ]);

} catch (Exception $e) {
    error_log("Erreur dans get_available_statuses.php : " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'statuses' => [],
        'count' => 0
    ]);
}
?>
