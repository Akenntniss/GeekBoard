<?php
// Endpoint AJAX pour récupérer les détails complets d'une mission
header('Content-Type: application/json');

// Inclure la configuration de session
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['shop_id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Vérifier que c'est une requête GET ou POST
if (!isset($_GET['mission_id']) && !isset($_POST['mission_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de mission requis']);
    exit;
}

$mission_id = (int) ($_GET['mission_id'] ?? $_POST['mission_id']);
$user_id = $_SESSION['user_id'];

if (!$mission_id) {
    echo json_encode(['success' => false, 'message' => 'ID de mission invalide']);
    exit;
}

$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

try {
    // Récupérer les détails complets de la mission
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.id,
            m.titre,
            m.description,
            m.objectif_quantite,
            m.recompense_euros,
            m.recompense_points,
            m.date_debut,
            m.date_fin,
            m.statut,
            m.created_at,
            m.updated_at,
            m.priorite,
            m.nombre_taches,
            m.actif,
            mt.nom as type_nom,
            mt.description as type_description,
            mt.icone as type_icone,
            mt.couleur as type_couleur,
            -- Informations sur la participation de l'utilisateur
            um.id as user_mission_id,
            um.statut as user_statut,
            um.progres as user_progres,
            um.date_rejointe,
            um.date_completee,
            -- Statistiques de la mission
            COUNT(DISTINCT um_all.id) as total_participants,
            COUNT(DISTINCT CASE WHEN um_all.statut = 'terminee' THEN um_all.id END) as participants_termines,
            -- Validations de l'utilisateur
            COUNT(DISTINCT mv.id) as validations_soumises,
            COUNT(DISTINCT CASE WHEN mv.statut = 'validee' THEN mv.id END) as validations_approuvees,
            COUNT(DISTINCT CASE WHEN mv.statut = 'en_attente' THEN mv.id END) as validations_en_attente
        FROM missions m
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        LEFT JOIN user_missions um ON m.id = um.mission_id AND um.user_id = ?
        LEFT JOIN user_missions um_all ON m.id = um_all.mission_id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        WHERE m.id = ?
        GROUP BY m.id, um.id
    ");
    
    $stmt->execute([$user_id, $mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        echo json_encode(['success' => false, 'message' => 'Mission non trouvée']);
        exit;
    }
    
    // Récupérer les validations de l'utilisateur pour cette mission (si il participe)
    $validations = [];
    if ($mission['user_mission_id']) {
        $stmt = $shop_pdo->prepare("
            SELECT 
                mv.id,
                mv.tache_numero,
                mv.description,
                mv.photo_url,
                mv.statut,
                mv.date_soumission,
                mv.date_validation,
                mv.commentaire_admin,
                u_admin.full_name as validee_par_nom
            FROM mission_validations mv
            LEFT JOIN users u_admin ON mv.validee_par = u_admin.id
            WHERE mv.user_mission_id = ?
            ORDER BY mv.tache_numero ASC
        ");
        $stmt->execute([$mission['user_mission_id']]);
        $validations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Calculs additionnels
    $jours_restants = null;
    if ($mission['date_fin']) {
        $date_fin = new DateTime($mission['date_fin']);
        $aujourd_hui = new DateTime();
        $diff = $aujourd_hui->diff($date_fin);
        $jours_restants = $diff->invert ? -$diff->days : $diff->days;
    }
    
    $progression_pct = 0;
    if ($mission['user_mission_id'] && $mission['nombre_taches'] > 0) {
        $progression_pct = ($mission['validations_approuvees'] / $mission['nombre_taches']) * 100;
    }
    
    $popularite_pct = 0;
    if ($mission['total_participants'] > 0) {
        $popularite_pct = ($mission['participants_termines'] / $mission['total_participants']) * 100;
    }
    
    // Déterminer le statut pour l'utilisateur
    $statut_utilisateur = 'disponible';
    if ($mission['user_mission_id']) {
        $statut_utilisateur = $mission['user_statut'];
    }
    
    // Formatage des dates
    $mission['date_debut_fr'] = $mission['date_debut'] ? date('d/m/Y', strtotime($mission['date_debut'])) : null;
    $mission['date_fin_fr'] = $mission['date_fin'] ? date('d/m/Y', strtotime($mission['date_fin'])) : null;
    $mission['created_at_fr'] = date('d/m/Y à H:i', strtotime($mission['created_at']));
    $mission['date_rejointe_fr'] = $mission['date_rejointe'] ? date('d/m/Y à H:i', strtotime($mission['date_rejointe'])) : null;
    
    echo json_encode([
        'success' => true,
        'mission' => $mission,
        'validations' => $validations,
        'stats' => [
            'jours_restants' => $jours_restants,
            'progression_pct' => round($progression_pct, 1),
            'popularite_pct' => round($popularite_pct, 1),
            'statut_utilisateur' => $statut_utilisateur
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erreur mission_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
