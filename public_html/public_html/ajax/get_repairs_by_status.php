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

    // Récupérer le statut demandé
    $status = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
    if (empty($status)) {
        throw new Exception('Statut manquant');
    }

    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }

    // Construire la condition WHERE selon l'onglet demandé (basé sur categorie_id)
    // categorie_id: 1=Nouvelles, 2=En cours, 3=En attente, 4=Effectuée/Annulée
    $whereStatut = '';
    if ($status === 'nouvelles') {
        $whereStatut = "r.statut IN (SELECT code FROM statuts WHERE est_actif=1 AND categorie_id=1)";
    } elseif ($status === 'en-cours') {
        $whereStatut = "r.statut IN (SELECT code FROM statuts WHERE est_actif=1 AND categorie_id=2)";
    } elseif ($status === 'en-attente') {
        $whereStatut = "r.statut IN (SELECT code FROM statuts WHERE est_actif=1 AND categorie_id=3)";
    } elseif ($status === 'terminees') {
        $whereStatut = "r.statut IN ('reparation_effectue','reparation_annule','termine','annule')";
    } else {
        throw new Exception('Statut invalide');
    }

    // Requête pour récupérer les réparations par statut
    $query = "
        SELECT 
            r.id,
            CONCAT(c.nom, ' ', c.prenom) as client_nom,
            r.type_appareil,
            r.modele,
            r.description_probleme,
            COALESCE(r.prix_reparation, 0) as prix,
            s.nom as statut_libelle,
            sc.couleur as statut_couleur,
            c.telephone
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut = s.code
        LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
        WHERE $whereStatut
        AND (r.archive IS NULL OR r.archive = 'NON')
        ORDER BY r.date_reception DESC
        LIMIT 100
    ";

    $stmt = $shop_pdo->prepare($query);
    $stmt->execute();
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données pour le frontend
    $formatted_repairs = [];
    foreach ($repairs as $repair) {
        $formatted_repairs[] = [
            'id' => $repair['id'],
            'client' => $repair['client_nom'] ?: 'Client inconnu',
            'type_appareil' => $repair['type_appareil'] ?: '',
            'modele' => $repair['modele'] ?: 'Modèle non spécifié',
            'probleme' => $repair['description_probleme'] ?: 'Problème non spécifié',
            'prix' => number_format((float)$repair['prix'], 2, ',', ' ') . ' €',
            'prix_raw' => (float)$repair['prix'],
            'statut' => $repair['statut_libelle'] ?: 'Statut inconnu',
            'statut_couleur' => $repair['statut_couleur'] ?: '#6b7280',
            'has_phone' => !empty($repair['telephone'])
        ];
    }

    echo json_encode([
        'success' => true,
        'repairs' => $formatted_repairs,
        'count' => count($formatted_repairs),
        'status_requested' => $status
    ]);

} catch (Exception $e) {
    error_log("Erreur dans get_repairs_by_status.php : " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'repairs' => [],
        'count' => 0
    ]);
}
?>
