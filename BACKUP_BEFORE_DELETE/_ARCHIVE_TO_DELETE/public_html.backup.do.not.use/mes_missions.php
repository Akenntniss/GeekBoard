<?php
// Démarrer la session avant tout
session_start();

// Forcer les sessions pour les tests (à supprimer en production)
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6;
$_SESSION["user_role"] = "admin";
$_SESSION["full_name"] = "Administrateur Mkmkmk";
$_SESSION["username"] = "admin";
$_SESSION["is_logged_in"] = true;

// Définir le chemin de base seulement s'il n'est pas déjà défini
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/.');
}

// Inclure les fichiers de configuration et de connexion à la base de données
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Vérification de l'authentification GeekBoard
if (!isset($_SESSION['shop_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

// Récupération des informations utilisateur
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'Utilisateur';
$shop_id = $_SESSION['shop_id'];

// Connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Récupération des données utilisateur (cagnotte, XP, etc.)
$user_data = [];
try {
    $stmt = $shop_pdo->prepare("SELECT cagnotte, points_experience, score_total FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_data) {
        $user_data = ['cagnotte' => 0.00, 'points_experience' => 0, 'score_total' => 0];
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des données utilisateur: " . $e->getMessage());
    $user_data = ['cagnotte' => 0.00, 'points_experience' => 0, 'score_total' => 0];
}

// Traitement des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'accepter_mission') {
        $mission_id = (int) $_POST['mission_id'];
        
        try {
            // Vérifier si la mission n'est pas déjà prise
            $stmt = $shop_pdo->prepare("SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ?");
            $stmt->execute([$user_id, $mission_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Mission déjà acceptée']);
                exit;
            }
            
            // Accepter la mission
            $stmt = $shop_pdo->prepare("
                INSERT INTO user_missions (user_id, mission_id, date_rejointe, statut, progres)
                VALUES (?, ?, NOW(), 'en_cours', 0)
            ");
            $stmt->execute([$user_id, $mission_id]);
            
            echo json_encode(['success' => true, 'message' => 'Mission acceptée avec succès !']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'valider_tache') {
        $user_mission_id = (int) $_POST['user_mission_id'];
        $description = trim($_POST['description']);
        
        if (empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Veuillez décrire la tâche accomplie']);
            exit;
        }
        
        try {
            // Gérer l'upload de la photo
            $photo_url = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/uploads/missions/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo_filename = 'mission_' . $user_mission_id . '_' . time() . '.' . $file_extension;
                $photo_path = $upload_dir . $photo_filename;
                
                // Vérifier le type de fichier
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé']);
                    exit;
                }
                
                // Déplacer le fichier uploadé
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    $photo_url = '/uploads/missions/' . $photo_filename;
                    // Définir les permissions appropriées
                    chmod($photo_path, 0644);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de la photo']);
                    exit;
                }
            }
            
            // Insérer la validation avec photo
            $stmt = $shop_pdo->prepare("
                INSERT INTO mission_validations (user_mission_id, tache_numero, description, photo_url, statut, date_soumission)
                VALUES (?, 1, ?, ?, 'en_attente', NOW())
            ");
            $stmt->execute([$user_mission_id, $description, $photo_url]);
            
            echo json_encode(['success' => true, 'message' => 'Tâche soumise pour validation !']);
            
} catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Récupération des missions par catégorie

// Missions en cours
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.id, m.titre, m.description, mt.nom as type_nom, mt.couleur, 
               m.recompense_euros, m.recompense_points, m.objectif_quantite as nombre_taches,
               COALESCE(mv.taches_validees, 0) as taches_validees,
               COALESCE(mv.taches_approuvees, 0) as taches_approuvees,
               um.date_rejointe as date_acceptation
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.type_id = mt.id
        LEFT JOIN (
            SELECT user_mission_id, 
                   COUNT(*) as taches_validees,
                   SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END) as taches_approuvees
            FROM mission_validations
            GROUP BY user_mission_id
        ) mv ON um.id = mv.user_mission_id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        ORDER BY um.date_rejointe DESC
    ");
    $stmt->execute([$user_id]);
    $missions_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des missions en cours: " . $e->getMessage());
    $missions_en_cours = [];
}

// Missions disponibles
try {
    $stmt = $shop_pdo->prepare("
        SELECT m.id, m.titre, m.description, mt.nom as type_nom, mt.couleur,
               m.recompense_euros, m.recompense_points, m.objectif_quantite as nombre_taches
        FROM missions m
        JOIN mission_types mt ON m.type_id = mt.id
        WHERE m.statut = 'active'
        AND m.id NOT IN (
            SELECT mission_id FROM user_missions WHERE user_id = ?
        )
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des missions disponibles: " . $e->getMessage());
    $missions_disponibles = [];
}

// Missions complétées
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.id, m.titre, m.description, mt.nom as type_nom, mt.couleur,
               m.recompense_euros, m.recompense_points, m.objectif_quantite as nombre_taches,
               um.date_completee
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.type_id = mt.id
        WHERE um.user_id = ? AND um.statut = 'terminee'
        ORDER BY um.date_completee DESC
    ");
    $stmt->execute([$user_id]);
    $missions_completees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des missions terminées: " . $e->getMessage());
    $missions_completees = [];
}

// Calculer les statistiques
$total_missions = count($missions_en_cours) + count($missions_disponibles) + count($missions_completees);
$missions_en_cours_count = count($missions_en_cours);
$missions_disponibles_count = count($missions_disponibles);
$missions_completees_count = count($missions_completees);

// Calculer les récompenses gagnées
$total_euros_gagnes = 0;
$total_points_gagnes = 0;
foreach ($missions_completees as $mission) {
    $total_euros_gagnes += $mission['recompense_euros'];
    $total_points_gagnes += $mission['recompense_points'];
}

// Titre de la page
$page_title = 'Mes Missions';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include 'components/head.php'; ?>
    <title>Mes Missions - GeekBoard</title>
    
    <!-- Styles CSS manquants pour la navbar -->
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/professional-desktop.css" rel="stylesheet">
    <link href="assets/css/modern-effects.css" rel="stylesheet">
    <link href="assets/css/neo-dock.css" rel="stylesheet">
    <link href="assets/css/mobile-navigation.css" rel="stylesheet">
    <link href="assets/css/status-colors.css" rel="stylesheet">
    <link href="assets/css/pwa-enhancements.css" rel="stylesheet">
    
    <!-- Fichier CSS pour le mode nuit -->
    <link href="assets/css/dark-theme.css" rel="stylesheet">
    
    <style>
        /* Styles conformes au dashboard GeekBoard */
        .modern-dashboard {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 2rem 3rem !important;
            background: #f5f7fa !important;
            min-height: 100vh;
        }
        
        /* Mode nuit pour le dashboard */
        body.dark-mode .modern-dashboard {
            background: var(--dark-bg-primary) !important;
        }
        
        .dashboard-header {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 1.5rem;
            background: white;
            border-radius: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        /* Mode nuit pour l'en-tête du dashboard */
        body.dark-mode .dashboard-header {
            background: var(--dark-card-bg) !important;
            color: var(--dark-text-primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .date-time-container h2 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #343a40;
        }
        
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
        }
        
        .action-card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem;
            text-align: center;
            text-decoration: none;
            color: #343a40;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(67, 97, 238, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        /* Mode nuit pour les cartes d'action */
        body.dark-mode .action-card {
            background: var(--dark-card-bg) !important;
            color: var(--dark-text-primary);
            border: 1px solid var(--dark-border-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(67, 97, 238, 0.03), rgba(67, 97, 238, 0));
            z-index: 0;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.1);
            border-color: rgba(67, 97, 238, 0.2);
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: rgba(67, 97, 238, 0.1);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            margin: 0 auto 1rem;
            position: relative;
            z-index: 1;
        }
        
        .action-text {
            font-size: 1rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .action-success .action-icon { color: #2ecc71; background: rgba(46, 204, 113, 0.1); }
        .action-warning .action-icon { color: #f1c40f; background: rgba(241, 196, 15, 0.1); }
        .action-info .action-icon { color: #3498db; background: rgba(52, 152, 219, 0.1); }
        .action-primary .action-icon { color: #4361ee; background: rgba(67, 97, 238, 0.1); }
        
        .statistics-container {
            width: 100%;
            background: white;
            border-radius: 0;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        /* Mode nuit pour le conteneur de statistiques */
        body.dark-mode .statistics-container {
            background: var(--dark-card-bg) !important;
            color: var(--dark-text-primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #343a40;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            width: 100%;
        }
        
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            text-decoration: none;
            color: inherit;
        }
        
        /* Mode nuit pour les cartes de statistiques */
        body.dark-mode .stat-card {
            background: var(--dark-card-bg) !important;
            color: var(--dark-text-primary);
            border: 1px solid var(--dark-border-color);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.1);
            color: inherit;
            text-decoration: none;
        }
        
        .stat-icon {
            width: 3rem;
            height: 3rem;
            background: rgba(67, 97, 238, 0.1);
            color: #4361ee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.25rem;
        }
        
        .waiting-card .stat-icon { background: rgba(67, 97, 238, 0.1); color: #4361ee; }
        .progress-card .stat-icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .clients-card .stat-icon { background: rgba(52, 152, 219, 0.1); color: #3498db; }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #343a40;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        
        /* Styles spécifiques pour les missions */
        .mission-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .mission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .mission-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .mission-type-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            color: white;
        }
        
        .mission-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .mission-description {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .mission-body {
            padding: 1.5rem;
        }

        .mission-progress {
            margin-bottom: 1.5rem;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .progress-text {
            font-size: 0.9rem;
            font-weight: 500;
            color: #374151;
        }
        
        .progress-badge {
            background: #f3f4f6;
            color: #374151;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Mode nuit pour les badges de progression */
        body.dark-mode .progress-badge {
            background: var(--dark-bg-tertiary) !important;
            color: var(--dark-text-primary);
        }

        .mission-rewards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .reward-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        /* Mode nuit pour les éléments de récompense */
        body.dark-mode .reward-item {
            background: var(--dark-bg-tertiary) !important;
            color: var(--dark-text-primary);
        }
        
        .reward-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .reward-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.2rem;
        }
        
        .reward-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .mission-actions {
            text-align: center;
        }

        .btn-mission {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-mission.btn-primary {
            background: #4361ee;
            color: white;
        }
        
        .btn-mission.btn-primary:hover {
            background: #3730a3;
            transform: translateY(-2px);
        }

        .btn-mission.btn-success {
            background: #10b981;
            color: white;
        }

        .btn-mission.btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-mission.btn-outline {
            background: transparent;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }
        
        .mission-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 0.5rem;
            background: white;
            border-radius: 0;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            width: 100%;
        }
        
        /* Mode nuit pour les onglets des missions */
        body.dark-mode .mission-tabs {
            background: var(--dark-card-bg) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .mission-tab {
            flex: 1;
            padding: 1rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 0;
        }
        
        .mission-tab.active {
            background: transparent;
            border-bottom: 3px solid #4361ee;
            color: #4361ee;
            font-weight: 600;
            transform: none;
        }
        
        .mission-tab:hover:not(.active) {
            background: rgba(67, 97, 238, 0.05);
            border-bottom: 3px solid #e9ecef;
            color: #343a40;
            transform: none;
        }
        
        .mission-tab-badge {
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .mission-tab.active .mission-tab-badge {
            background: rgba(67, 97, 238, 0.1);
            color: #4361ee;
        }
        
        .missions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            width: 100%;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #1f2937;
            background: white;
            border-radius: 0;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        /* Mode nuit pour l'état vide */
        body.dark-mode .empty-state {
            background: var(--dark-card-bg) !important;
            color: var(--dark-text-primary);
            border: 1px solid var(--dark-border-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        /* Mode nuit pour les titres et textes */
        body.dark-mode .section-title {
            color: var(--dark-text-primary);
            border-bottom-color: var(--dark-border-color);
        }
        
        body.dark-mode .date-time-container h2 {
            color: var(--dark-text-primary);
        }
        
        body.dark-mode .stat-value {
            color: var(--dark-text-primary);
        }
        
        body.dark-mode .stat-label {
            color: var(--dark-text-secondary);
        }
        
        body.dark-mode .mission-tab {
            color: var(--dark-text-secondary);
        }
        
        body.dark-mode .mission-tab.active {
            color: var(--dark-accent-color);
        }
        
        body.dark-mode .mission-tab-badge {
            background: var(--dark-bg-tertiary);
            color: var(--dark-text-secondary);
        }
        
        body.dark-mode .mission-tab.active .mission-tab-badge {
            background: rgba(67, 97, 238, 0.1);
            color: var(--dark-accent-color);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #6b7280;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .empty-state p {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .tab-content {
            display: none;
            width: 100%;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Ajustements responsive */
        @media (max-width: 1400px) {
            .quick-actions-grid,
            .statistics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .missions-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 992px) {
            .modern-dashboard {
                padding: 1.5rem !important;
            }
        }
        
        @media (max-width: 768px) {
            .modern-dashboard {
                padding: 1rem !important;
            }
            
            .missions-grid {
                grid-template-columns: 1fr;
            }
            
            .mission-tabs {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .mission-tab {
                padding: 0.75rem;
            }
            
            .quick-actions-grid,
            .statistics-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .modern-dashboard {
                padding: 0.5rem !important;
            }
            
            .dashboard-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .statistics-container {
                padding: 1rem;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    
    <div class="modern-dashboard">
        <!-- En-tête -->
        <div class="dashboard-header">
            <div class="date-time-container">
                <h2><i class="fas fa-bullseye me-2"></i>Mes Missions</h2>
                <p>Bonjour, <?php echo htmlspecialchars($user_name); ?> ! Voici vos missions et défis du moment.</p>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="quick-actions-grid">
            <div class="action-card action-success">
                <div class="action-icon">
                    <i class="fas fa-coins"></i>
                            </div>
                <div class="action-text">
                    <div style="font-size: 1.2rem; font-weight: 600;"><?php echo number_format($user_data['cagnotte'], 2, ',', ' '); ?> €</div>
                    <div style="font-size: 0.8rem;">Cagnotte</div>
                            </div>
                        </div>

            <div class="action-card action-warning">
                <div class="action-icon">
                    <i class="fas fa-star"></i>
                    </div>
                <div class="action-text">
                    <div style="font-size: 1.2rem; font-weight: 600;"><?php echo number_format($user_data['points_experience']); ?></div>
                    <div style="font-size: 0.8rem;">Points XP</div>
                </div>
            </div>
            
            <div class="action-card action-info">
                <div class="action-icon">
                    <i class="fas fa-trophy"></i>
                            </div>
                <div class="action-text">
                    <div style="font-size: 1.2rem; font-weight: 600;"><?php echo number_format($user_data['score_total']); ?></div>
                    <div style="font-size: 0.8rem;">Score Total</div>
                            </div>
                        </div>

            <div class="action-card action-primary">
                <div class="action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="action-text">
                    <div style="font-size: 1.2rem; font-weight: 600;"><?php echo $missions_completees_count; ?></div>
                    <div style="font-size: 0.8rem;">Missions terminées</div>
                    </div>
                </div>
            </div>
            
        <!-- Statistiques des missions -->
        <div class="statistics-container">
            <h3 class="section-title">État des missions</h3>
            <div class="statistics-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-play-circle"></i>
                            </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $missions_en_cours_count; ?></div>
                        <div class="stat-label">En cours</div>
                            </div>
                        </div>

                <div class="stat-card waiting-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $missions_disponibles_count; ?></div>
                        <div class="stat-label">Disponibles</div>
                </div>
            </div>
            
                <div class="stat-card progress-card">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                            </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $missions_completees_count; ?></div>
                        <div class="stat-label">Terminées</div>
                            </div>
                        </div>

                <div class="stat-card clients-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_missions; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets de missions -->
        <div class="mission-tabs">
            <a href="#en-cours" class="mission-tab active" data-tab="en-cours">
                <i class="fas fa-play-circle"></i>
                En cours
                <span class="mission-tab-badge"><?php echo $missions_en_cours_count; ?></span>
            </a>
            <a href="#disponibles" class="mission-tab" data-tab="disponibles">
                <i class="fas fa-star"></i>
                Disponibles
                <span class="mission-tab-badge"><?php echo $missions_disponibles_count; ?></span>
            </a>
            <a href="#completees" class="mission-tab" data-tab="completees">
                <i class="fas fa-trophy"></i>
                Terminées
                <span class="mission-tab-badge"><?php echo $missions_completees_count; ?></span>
            </a>
                </div>

        <!-- Contenu des onglets -->
        
        <!-- Missions en cours -->
        <div id="en-cours" class="tab-content active">
            <?php if (empty($missions_en_cours)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <h3>Aucune mission en cours</h3>
                <p>Consultez les missions disponibles pour commencer à gagner des récompenses !</p>
            </div>
            <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_en_cours as $mission): ?>
                <div class="mission-card">
                                <div class="mission-header">
                        <div class="mission-type-badge" style="background-color: <?php echo htmlspecialchars($mission['couleur']); ?>">
                            <?php echo htmlspecialchars($mission['type_nom']); ?>
                                        </div>
                        <div class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></div>
                        <div class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></div>
                                        </div>
                    <div class="mission-body">
                        <div class="mission-progress">
                            <div class="progress-info">
                                <span class="progress-text">Progression</span>
                                <span class="progress-badge"><?php echo $mission['taches_approuvees']; ?>/<?php echo $mission['nombre_taches']; ?></span>
                                    </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?php echo ($mission['nombre_taches'] > 0) ? ($mission['taches_approuvees'] / $mission['nombre_taches'] * 100) : 0; ?>%"></div>
                            </div>
                                </div>

                        <div class="mission-rewards">
                            <div class="reward-item">
                                <div class="reward-icon">💰</div>
                                <div class="reward-value"><?php echo number_format($mission['recompense_euros'], 2); ?> €</div>
                                <div class="reward-label">Récompense</div>
                            </div>
                            <div class="reward-item">
                                <div class="reward-icon">⭐</div>
                                <div class="reward-value"><?php echo $mission['recompense_points']; ?></div>
                                <div class="reward-label">Points XP</div>
                            </div>
                                </div>

                        <div class="mission-actions">
                            <?php if ($mission['taches_approuvees'] < $mission['nombre_taches']): ?>
                            <button type="button" class="btn-mission btn-success" onclick="validerTache(<?php echo $mission['id']; ?>)">
                                <i class="fas fa-check"></i>
                                Valider une tâche
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn-mission btn-outline" disabled>
                                <i class="fas fa-trophy"></i>
                                Mission complète
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Missions disponibles -->
        <div id="disponibles" class="tab-content">
            <?php if (empty($missions_disponibles)): ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <h3>Aucune mission disponible</h3>
                <p>Toutes les missions sont déjà en cours ou terminées. Revenez plus tard !</p>
            </div>
            <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_disponibles as $mission): ?>
                <div class="mission-card">
                    <div class="mission-header">
                        <div class="mission-type-badge" style="background-color: <?php echo htmlspecialchars($mission['couleur']); ?>">
                            <?php echo htmlspecialchars($mission['type_nom']); ?>
                        </div>
                        <div class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></div>
                        <div class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></div>
                    </div>
                    <div class="mission-body">
                                <div class="mission-progress">
                            <div class="progress-info">
                                <span class="progress-text">Tâches requises</span>
                                <span class="progress-badge"><?php echo $mission['nombre_taches']; ?> tâches</span>
                            </div>
                                    <div class="progress">
                                <div class="progress-bar bg-secondary" style="width: 0%"></div>
                                    </div>
                                </div>

                                <div class="mission-rewards">
                            <div class="reward-item">
                                <div class="reward-icon">💰</div>
                                <div class="reward-value"><?php echo number_format($mission['recompense_euros'], 2); ?> €</div>
                                <div class="reward-label">Récompense</div>
                                    </div>
                            <div class="reward-item">
                                <div class="reward-icon">⭐</div>
                                <div class="reward-value"><?php echo $mission['recompense_points']; ?></div>
                                <div class="reward-label">Points XP</div>
                            </div>
                        </div>
                        
                        <div class="mission-actions">
                            <button type="button" class="btn-mission btn-primary" onclick="accepterMission(<?php echo $mission['id']; ?>)">
                                <i class="fas fa-plus"></i>
                                Accepter la mission
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

        <!-- Missions terminées -->
        <div id="completees" class="tab-content">
            <?php if (empty($missions_completees)): ?>
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <h3>Aucune mission terminée</h3>
                <p>Terminez vos premières missions pour les voir apparaître ici !</p>
            </div>
            <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_completees as $mission): ?>
                <div class="mission-card">
                    <div class="mission-header">
                        <div class="mission-type-badge" style="background-color: <?php echo htmlspecialchars($mission['couleur']); ?>">
                            <?php echo htmlspecialchars($mission['type_nom']); ?>
                        </div>
                        <div class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></div>
                        <div class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></div>
                    </div>
                    <div class="mission-body">
                        <div class="mission-progress">
                            <div class="progress-info">
                                <span class="progress-text">Terminée le</span>
                                <span class="progress-badge"><?php echo date('d/m/Y', strtotime($mission['date_completee'])); ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 100%"></div>
                            </div>
                        </div>
                        
                        <div class="mission-rewards">
                            <div class="reward-item">
                                <div class="reward-icon">💰</div>
                                <div class="reward-value"><?php echo number_format($mission['recompense_euros'], 2); ?> €</div>
                                <div class="reward-label">Gagnés</div>
                            </div>
                            <div class="reward-item">
                                <div class="reward-icon">⭐</div>
                                <div class="reward-value"><?php echo $mission['recompense_points']; ?></div>
                                <div class="reward-label">Points XP</div>
                            </div>
                                    </div>
                                    
                                    <div class="mission-actions">
                            <button type="button" class="btn-mission btn-success" disabled>
                                <i class="fas fa-trophy"></i>
                                Mission réussie !
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
            <?php endif; ?>
                        </div>

                    </div>

    <!-- Modal de validation de tâche -->
    <div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="validationModalLabel">
                        <i class="fas fa-check-circle me-2"></i>
                        Valider une tâche
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                <div class="modal-body">
                    <form id="validationForm" enctype="multipart/form-data">
                        <input type="hidden" id="userMissionId" name="user_mission_id">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description de la tâche accomplie *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="Décrivez ce que vous avez fait pour accomplir cette tâche..."
                                      required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="photo" class="form-label">Photo (optionnelle)</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                            <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success" onclick="soumettreValidation()">
                        <i class="fas fa-check"></i>
                        Valider la tâche
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion des onglets
        document.addEventListener('DOMContentLoaded', function() {
            const missionTabs = document.querySelectorAll('.mission-tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            missionTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Retirer la classe active
                    missionTabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Ajouter la classe active
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });

        // Fonction pour accepter une mission
        function accepterMission(missionId) {
            fetch('mes_missions.php', {
                    method: 'POST',
                    headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    },
                body: `action=accepter_mission&mission_id=${missionId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                    } else {
                    showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }

        // Fonction pour valider une tâche
        function validerTache(userMissionId) {
            document.getElementById('userMissionId').value = userMissionId;
            new bootstrap.Modal(document.getElementById('validationModal')).show();
        }

        // Fonction pour soumettre la validation
        function soumettreValidation() {
            const form = document.getElementById('validationForm');
            const formData = new FormData(form);
            formData.append('action', 'valider_tache');
            
            fetch('mes_missions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('validationModal')).hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }

        // Fonction pour afficher les notifications
        function showNotification(message, type) {
            // Créer l'élément de notification
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Supprimer automatiquement après 5 secondes
                setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
    
    <!-- Scripts JavaScript manquants pour la navbar -->
    <script src="assets/js/app.js" defer></script>
    <script src="assets/js/modern-interactions.js" defer></script>
    <script src="components/js/navbar.js" defer></script>
    <script src="components/js/tablet-detect.js" defer></script>
    
    <!-- Script pour le gestionnaire de thème -->
    <script src="assets/js/theme-switcher.js" defer></script>
    
</body>
</html> 