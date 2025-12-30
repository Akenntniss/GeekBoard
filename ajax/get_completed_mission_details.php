<?php
session_start();

// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Initialiser une session si pas active (pour test)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
}

// S'assurer que la session shop est initialisée
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 63; // mkmkmk
}

header('Content-Type: application/json');

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }

    $user_mission_id = $_GET['user_mission_id'] ?? null;

    if (!$user_mission_id) {
        throw new Exception('ID de mission utilisateur manquant');
    }

    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();

        // Récupérer les détails de la mission complète
        $stmt = $shop_pdo->prepare("
            SELECT 
                m.id, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points, m.created_at,
                mt.nom as type_nom, mt.icon as type_icone, mt.couleur as type_couleur,
                um.id as user_mission_id, um.user_id, um.progression, um.statut as mission_statut, um.date_completion,
                u.full_name as user_name, u.username
            FROM user_missions um
            JOIN missions m ON um.mission_id = m.id
            LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
            LEFT JOIN users u ON um.user_id = u.id
            WHERE um.id = ? AND um.statut = 'terminee'
        ");
    $stmt->execute([$user_mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mission) {
        throw new Exception('Mission complète non trouvée');
    }

    // Récupérer toutes les validations pour cette mission
    $stmt = $shop_pdo->prepare("
        SELECT 
            mv.id, mv.tache_numero, mv.description, mv.preuve_text, mv.statut, 
            mv.created_at, mv.validated_at, mv.commentaire_admin
        FROM mission_validations mv
        WHERE mv.user_mission_id = ?
        ORDER BY mv.tache_numero ASC
    ");
    $stmt->execute([$user_mission_id]);
    $validations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mapper les statuts pour l'affichage
    foreach ($validations as &$validation) {
        if ($validation['statut'] === 'approuvee') {
            $validation['statut'] = 'validee';
        } elseif ($validation['statut'] === 'rejetee') {
            $validation['statut'] = 'refusee';
        }
    }

    // Réponse de succès
    echo json_encode([
        'success' => true,
        'data' => [
            'mission' => $mission,
            'validations' => $validations
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des détails de mission complète: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des détails de mission complète: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
