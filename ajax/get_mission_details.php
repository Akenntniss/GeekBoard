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

    $mission_id = $_GET['mission_id'] ?? null;

    if (!$mission_id) {
        throw new Exception('ID de mission manquant');
    }

    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les détails de la mission
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.id, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points, 
            m.statut, m.date_debut, m.date_fin, m.created_at,
            mt.nom as type_nom, mt.icon as type_icone, mt.couleur as type_couleur
        FROM missions m
        LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
        WHERE m.id = ?
    ");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        throw new Exception('Mission non trouvée');
    }
    
    // Récupérer les employés actifs sur cette mission avec leurs progressions
    $stmt = $shop_pdo->prepare("
        SELECT 
            um.id as user_mission_id,
            um.user_id,
            um.progression,
            um.statut as mission_statut,
            um.date_inscription,
            um.date_completion,
            u.full_name as user_name, u.username,
            COUNT(DISTINCT mv.id) as total_soumissions,
            COUNT(DISTINCT CASE WHEN mv.statut = 'approuvee' THEN mv.id END) as soumissions_validees,
            COUNT(DISTINCT CASE WHEN mv.statut = 'en_attente' THEN mv.id END) as soumissions_en_attente,
            COUNT(DISTINCT CASE WHEN mv.statut = 'rejetee' THEN mv.id END) as soumissions_rejetees
        FROM user_missions um
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE um.mission_id = ?
        GROUP BY um.id
        ORDER BY um.date_inscription ASC
    ");
    $stmt->execute([$mission_id]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer toutes les soumissions de tâches pour cette mission
    $stmt = $shop_pdo->prepare("
        SELECT 
            mv.id, mv.user_mission_id, mv.tache_numero, mv.description, mv.preuve_text, 
            mv.statut, mv.created_at, mv.validated_at, mv.commentaire_admin,
            um.user_id, um.progression,
            u.full_name as user_name, u.username
        FROM mission_validations mv
        JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE um.mission_id = ?
        ORDER BY mv.created_at DESC
    ");
    $stmt->execute([$mission_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques globales de la mission
    $stats = [
        'total_participants' => count($employees),
        'participants_actifs' => count(array_filter($employees, function($emp) { return $emp['mission_statut'] === 'en_cours'; })),
        'participants_termines' => count(array_filter($employees, function($emp) { return $emp['mission_statut'] === 'terminee'; })),
        'total_soumissions' => array_sum(array_column($employees, 'total_soumissions')),
        'soumissions_validees' => array_sum(array_column($employees, 'soumissions_validees')),
        'soumissions_en_attente' => array_sum(array_column($employees, 'soumissions_en_attente')),
        'progression_moyenne' => count($employees) > 0 ? round(array_sum(array_column($employees, 'progression')) / count($employees), 1) : 0
    ];

    // Réponse de succès
    echo json_encode([
        'success' => true,
        'data' => [
        'mission' => $mission,
            'employees' => $employees,
            'submissions' => $submissions,
            'stats' => $stats
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des détails de mission: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des détails de mission: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 