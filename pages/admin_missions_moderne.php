<?php
// Inclure la configuration de session et de base de données
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session du magasin si ce n'est pas déjà fait
initializeShopSession();

// Vérification simplifiée pour test (comme les autres pages qui fonctionnent)
if (!isset($_SESSION['user_id'])) {
    // Initialiser une session de test si pas de session active
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
}

// S'assurer que le shop_id est défini pour mkmkmk
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 63; // mkmkmk
}

// Obtenir la connexion PDO pour le magasin actuel
$shop_pdo = getShopDBConnection();

// Fallback si getShopDBConnection() échoue
if (!$shop_pdo && isset($_SESSION['shop_id'])) {
    $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']);
}


// Si la connexion pointe sur la base générale, tenter de basculer vers la base du shop
if ($shop_pdo) {
    try {
        $dbRow = $shop_pdo->query("SELECT DATABASE() AS db")->fetch();
        $activeDb = $dbRow['db'] ?? null;
        if ($activeDb === 'geekboard_general' && isset($_SESSION['shop_id']) && function_exists('getShopDBConnectionById')) {
            $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']);
        }
    } catch (Throwable $e) {
        error_log("Check active DB failed: " . $e->getMessage());
    }
}

if (!$shop_pdo) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base du magasin. Veuillez réessayer.</div>";
    return;
}

// Initialiser les variables
$stats_missions_actives = 0;
$stats_missions_en_cours = 0;
$stats_missions_completees = 0;
$stats_validations_en_attente = 0;
$missions = [];
$validations = [];

// Récupérer les statistiques des missions
try {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM missions WHERE statut = 'active'");
    $stmt->execute();
    $stats_missions_actives = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erreur stats missions actives: " . $e->getMessage());
}

try {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE statut = 'en_cours'");
    $stmt->execute();
    $stats_missions_en_cours = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erreur stats missions en cours: " . $e->getMessage());
}

try {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE statut = 'terminee' AND MONTH(date_completee) = MONTH(NOW()) AND YEAR(date_completee) = YEAR(NOW())");
    $stmt->execute();
    $stats_missions_completees = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erreur stats missions complétées: " . $e->getMessage());
}

try {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM mission_validations WHERE statut = 'en_attente'");
    $stmt->execute();
    $stats_validations_en_attente = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erreur stats validations: " . $e->getMessage());
}

// Récupérer les missions actives avec informations complètes
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.id, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points, m.statut, m.created_at,
            mt.nom as type_nom, mt.icon as type_icone, mt.couleur as type_couleur,
            COUNT(DISTINCT um.id) as nb_participants,
            COUNT(DISTINCT CASE WHEN um.statut = 'terminee' THEN um.id END) as nb_completes
        FROM missions m
        LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN user_missions um ON m.id = um.mission_id
        WHERE m.statut = 'active'
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute();
    $missions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur récupération missions: " . $e->getMessage());
}

// Récupérer les missions inactives avec informations complètes
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.id, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points, m.statut, m.created_at,
            mt.nom as type_nom, mt.icon as type_icone, mt.couleur as type_couleur,
            COUNT(DISTINCT um.id) as nb_participants,
            COUNT(DISTINCT CASE WHEN um.statut = 'terminee' THEN um.id END) as nb_completes
        FROM missions m
        LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN user_missions um ON m.id = um.mission_id
        WHERE m.statut = 'inactive'
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute();
    $missions_inactives = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur récupération missions inactives: " . $e->getMessage());
    $missions_inactives = [];
}

// Récupérer les validations en attente
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            mv.id, mv.user_mission_id, mv.tache_numero, mv.statut, mv.created_at, mv.description, mv.preuve_text,
            m.titre as mission_titre,
            um.user_id,
            um.progression as progression_actuelle,
            u.full_name as user_name, u.username
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN missions m ON um.mission_id = m.id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE mv.statut = 'en_attente'
        ORDER BY mv.created_at DESC
    ");
    $stmt->execute();
    $validations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur récupération validations: " . $e->getMessage());
}

// Récupérer les missions complètes
$missions_completes = [];
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.id, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points, m.created_at,
            mt.nom as type_nom, mt.icon as type_icone, mt.couleur as type_couleur,
            um.id as user_mission_id, um.user_id, um.progression, um.statut as mission_statut, um.date_completion,
            u.full_name as user_name, u.username,
            COUNT(DISTINCT mv.id) as total_validations
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE um.statut = 'terminee'
        GROUP BY um.id
        ORDER BY um.date_completion DESC
    ");
    $stmt->execute();
    $missions_completes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur récupération missions complètes: " . $e->getMessage());
}

// Récupérer les données des employés avec leurs cagnottes et XP
$employees_rewards = [];
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            uc.user_id,
            uc.solde_euros,
            uc.solde_points,
            uc.total_gagne_euros,
            uc.total_gagne_points,
            COUNT(DISTINCT um.id) as missions_completees,
            COUNT(DISTINCT CASE WHEN um.statut = 'en_cours' THEN um.id END) as missions_en_cours,
            MAX(um.date_completion) as derniere_mission
        FROM user_cagnotte uc
        LEFT JOIN user_missions um ON uc.user_id = um.user_id
        GROUP BY uc.user_id
        ORDER BY uc.total_gagne_euros DESC
    ");
    $stmt->execute();
    $employees_rewards = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur récupération données employés: " . $e->getMessage());
    // Fallback: récupérer directement depuis user_cagnotte
    try {
        $stmt = $shop_pdo->prepare("
            SELECT 
                user_id,
                solde_euros,
                solde_points,
                total_gagne_euros,
                total_gagne_points,
                0 as missions_completees,
                0 as missions_en_cours,
                NULL as derniere_mission
            FROM user_cagnotte 
            ORDER BY total_gagne_euros DESC
        ");
        $stmt->execute();
        $employees_rewards = $stmt->fetchAll();
    } catch (Exception $e2) {
        error_log("Erreur fallback: " . $e2->getMessage());
    }
}
?>

<!-- Meta viewport pour la responsivité mobile -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<style>
/* ========================================
   FIX NAVBAR & ANIMATION SERVO
   ======================================== */
@media (min-width: 992px) {
    /* Masquer le dock mobile sur desktop */
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    
    /* S'assurer que la navbar desktop est visible */
    #desktop-navbar, nav#desktop-navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1030 !important;
        width: 100% !important;
        height: 60px !important;
    }
    
    /* Container fluid de la navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.5rem 1rem !important;
        min-height: 60px !important;
    }
    
    /* Logo SERVO - CENTRÉ horizontalement ET verticalement */
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 0 !important;
        transform: translateX(-50%) !important;
        z-index: 99999 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 200px !important;
        height: 100% !important;
        pointer-events: auto !important;
    }
    
    /* S'assurer que le loader SERVO est visible */
    .servo-logo-container .loader {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Animations SVG pour toutes les lettres SERVO */
    .servo-logo-container .dash {
        animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
    }
    
    .servo-logo-container .spin {
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center;
    }
    
    /* Keyframes pour l'animation .dash (S, E, R, V) */
    @keyframes dashArray {
        0% { stroke-dasharray: 0 1 359 0; }
        50% { stroke-dasharray: 0 359 1 0; }
        100% { stroke-dasharray: 359 1 0 0; }
    }
    
    /* Keyframes pour l'animation .spin (O) */
    @keyframes spinDashArray {
        0% { stroke-dasharray: 270 90; }
        50% { stroke-dasharray: 0 360; }
        100% { stroke-dasharray: 250 90; }
    }
    
    /* Animation du trait qui se dessine */
    @keyframes dashOffset {
        0% { stroke-dashoffset: 385; }
        100% { stroke-dashoffset: 5; }
    }
    
    /* Animation de rotation pour le O */
    @keyframes spin {
        0% { rotate: 0deg; }
        12.5%, 25% { rotate: 270deg; }
        37.5%, 50% { rotate: 540deg; }
        62.5%, 75% { rotate: 810deg; }
        87.5%, 100% { rotate: 1080deg; }
    }
    
    /* S'assurer que tous les SVG sont visibles */
    .servo-logo-container svg,
    .servo-logo-container path {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Padding pour le body */
    body {
        padding-top: 80px !important;
    }
}

/* ========================================
   VARIABLES CSS - THÈME JOUR/NUIT
======================================== */
:root {
    /* Mode Jour - Professionnel */
    --day-primary: #2563eb;
    --day-secondary: #7c3aed;
    --day-accent: #06d6a0;
    --day-bg: #f8fafc;
    --day-bg-animated: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #cbd5e1, #94a3b8);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(37, 99, 235, 0.15);
    --day-border: rgba(37, 99, 235, 0.2);
    --day-glow: 0 0 20px rgba(37, 99, 235, 0.3);

    /* Mode Nuit - Futuriste */
    --night-primary: #00d4ff;
    --night-secondary: #7c3aed;
    --night-accent: #ff00aa;
    --night-bg: #0a0a0a;
    --night-bg-animated: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
}

/* ========================================
   FOND ANIMÉ
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -1;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated);
    background-size: 400% 400%;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ========================================
   CONTENEUR PRINCIPAL
======================================== */
.missions-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    position: relative;
    z-index: 1;
}

/* ========================================
   HEADER MODERNE
======================================== */
.modern-header {
    background: var(--day-card-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    border-radius: 24px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
    position: relative;
    overflow: hidden;
}

.modern-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary), var(--day-accent));
    border-radius: 24px 24px 0 0;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.modern-title {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
    line-height: 1.2;
}

.modern-subtitle {
    color: var(--day-text-light);
    font-size: 1.1rem;
    margin-top: 0.5rem;
    font-weight: 500;
}

.modern-btn {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 16px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
}

.modern-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);
}

/* ========================================
   STATISTIQUES MODERNES
======================================== */
.modern-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.modern-stat-card {
    background: var(--day-card-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-accent));
}

.modern-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px var(--day-shadow);
}

.stat-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
}

.stat-content .stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b !important; /* Noir en mode jour - Priorité forte */
    margin: 0;
    line-height: 1;
}

.stat-content .stat-label {
    color: var(--day-text-light);
    font-weight: 600;
    font-size: 0.95rem;
    margin-top: 0.25rem;
}

/* ========================================
   ONGLETS MODERNES
======================================== */
.modern-tabs-container {
    background: var(--day-card-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    border-radius: 24px;
    box-shadow: 0 8px 32px var(--day-shadow);
    overflow: hidden;
}

.modern-tabs-header {
    display: flex;
    background: rgba(37, 99, 235, 0.05);
    border-bottom: 1px solid var(--day-border);
}

.modern-tab-button {
    flex: 1;
    padding: 1.5rem 2rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-weight: 600;
    color: var(--day-text-light);
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    font-size: 1rem;
}

.modern-tab-button:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--day-primary);
}

.modern-tab-button.active {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    position: relative;
}

.modern-tab-button.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--day-accent);
}

.tab-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 700;
    min-width: 24px;
    text-align: center;
}

.modern-tab-button:not(.active) .tab-badge {
    background: var(--day-primary);
    color: white;
}

.modern-tab-content {
    display: none;
    padding: 2.5rem;
}

.modern-tab-content.active {
    display: block;
}

/* ========================================
   CARTES MISSIONS MODERNES
======================================== */
.modern-missions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 2rem;
}

.modern-mission-card {
    background: var(--day-card-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.modern-mission-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-accent));
}

.modern-mission-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 48px var(--day-shadow);
    border-color: var(--day-primary);
}

/* Styles spécifiques pour les missions complètes */
.completed-mission {
    cursor: pointer;
    transition: all 0.3s ease;
}

.completed-mission:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(34, 197, 94, 0.2);
    border-color: #22c55e;
}

.mission-status.completed {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}

.completed-mission .mission-progress .progress-fill {
    background: linear-gradient(135deg, #22c55e, #16a34a);
}

.mission-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--day-border);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--day-text-light);
    font-size: 0.9rem;
}

.meta-item i {
    color: var(--day-primary);
    width: 16px;
}

/* ========================================
   STYLES MODAL MISSIONS COMPLÈTES
======================================== */
.completed-mission-details {
    padding: 1rem;
}

.mission-title-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.mission-title-section h2 {
    color: var(--day-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mission-title-section h2 i {
    color: #22c55e;
}

.mission-description {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.mission-description p {
    margin: 0;
    color: var(--day-text-light);
    line-height: 1.6;
}

.mission-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 12px;
    padding: 1rem;
}

.stat-item i {
    font-size: 1.5rem;
    color: var(--day-primary);
    width: 24px;
    text-align: center;
}

.stat-item div {
    flex: 1;
}

.stat-label {
    display: block;
    font-size: 0.85rem;
    color: var(--day-text-light);
    margin-bottom: 0.25rem;
}

.stat-value {
    display: block;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--day-text);
}

.validations-section {
    margin-top: 2rem;
}

.validations-section h3 {
    color: var(--day-text);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.validations-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.validation-detail-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    overflow: hidden;
}

.validation-detail-header {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.validation-number {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.validation-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.validation-status.validee {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.validation-status.refusee {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.validation-status.en_attente {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
}

.validation-detail-content {
    padding: 1.5rem;
}

.validation-description,
.validation-proof,
.admin-comment {
    margin-bottom: 1.5rem;
}

.validation-description h4,
.validation-proof h4,
.admin-comment h4 {
    color: var(--day-text);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
}

.validation-description p,
.validation-proof p,
.admin-comment p {
    color: var(--day-text-light);
    line-height: 1.6;
    margin: 0;
    background: rgba(0, 0, 0, 0.02);
    padding: 1rem;
    border-radius: 8px;
    border-left: 3px solid var(--day-primary);
}

.validation-dates {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--day-text-light);
    font-size: 0.9rem;
}

.date-item i {
    color: var(--day-primary);
    width: 16px;
}

/* Responsive pour le modal des missions complètes */
@media (max-width: 768px) {
    .mission-title-section {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .mission-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .validation-detail-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .validation-dates {
        flex-direction: column;
        gap: 0.5rem;
    }
}

/* ========================================
   STYLES CAGNOTTE & XP EMPLOYÉS
======================================== */
.global-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.global-stat-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.global-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--day-shadow);
}

.global-stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    font-size: 1.5rem;
}

.global-stat-card .stat-content {
    flex: 1;
}

.global-stat-card .stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 0.25rem;
}

.global-stat-card .stat-label {
    font-size: 0.9rem;
    color: var(--day-text-light);
}

.employees-rewards-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.employee-reward-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    padding: 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 100%;
}

.employee-reward-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-accent));
}

.employee-reward-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px var(--day-shadow);
    border-color: var(--day-primary);
}

.employee-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.employee-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.employee-info {
    flex: 1;
}

.employee-info h4 {
    margin: 0 0 0.25rem 0;
    color: var(--day-text);
    font-size: 1.1rem;
}

.employee-status {
    margin: 0;
    color: var(--day-text-light);
    font-size: 0.9rem;
}

.employee-rank {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--day-text);
}

.employee-stats {
    margin-bottom: 1.5rem;
}

.stat-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.stat-row:last-child {
    margin-bottom: 0;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: rgba(0, 0, 0, 0.02);
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.stat-icon {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.stat-icon.euros {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-icon.points {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-icon.total {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.stat-icon.missions {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.stat-details {
    flex: 1;
}

.stat-details .stat-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--day-text);
    margin-bottom: 0.125rem;
}

.stat-details .stat-label {
    font-size: 0.8rem;
    color: var(--day-text-light);
}

.employee-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid var(--day-border);
}

.last-activity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--day-text-light);
    font-size: 0.85rem;
}

.last-activity i {
    color: var(--day-primary);
}

.view-history-btn {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.view-history-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Responsive pour les cartes employés */
@media (max-width: 1200px) {
    .stat-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .global-stats-row {
        grid-template-columns: 1fr;
    }
    
    .employee-header {
        flex-wrap: wrap;
    }
    
    .stat-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .employee-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .employee-reward-card {
        padding: 1.5rem;
    }
}

/* ========================================
   STYLES MODAL DÉTAILS MISSION
======================================== */
.mission-details-container {
    padding: 0;
}

.mission-overview-section {
    margin-bottom: 2rem;
}

.mission-header-large {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    border: 1px solid var(--day-border);
}

.mission-type-badge-large {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 20px;
    color: white;
    font-size: 2rem;
    flex-shrink: 0;
}

.mission-title-large {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.mission-type-name {
    color: var(--day-text-light);
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.mission-title-large h2 {
    color: var(--day-text);
    margin: 0;
    font-size: 1.8rem;
    font-weight: 600;
    word-wrap: break-word;
    overflow-wrap: break-word;
    line-height: 1.2;
}

.mission-header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.mission-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: none;
}

.mission-toggle-btn.deactivate {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.mission-toggle-btn.deactivate:hover {
    background: rgba(239, 68, 68, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.mission-toggle-btn.activate {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.mission-toggle-btn.activate:hover {
    background: rgba(34, 197, 94, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.mission-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 500;
}

.mission-status.active {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.mission-status.inactive {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Styles pour les cartes de missions inactives */
.modern-mission-card.inactive {
    opacity: 0.7;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.modern-mission-card.inactive:hover {
    opacity: 1;
    border-color: rgba(239, 68, 68, 0.4);
}

.action-btn.activate {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.action-btn.activate:hover {
    background: rgba(34, 197, 94, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.mission-description-section {
    background: var(--day-card-bg);
    padding: 1.5rem;
    border-radius: 15px;
    border: 1px solid var(--day-border);
    margin-bottom: 2rem;
}

.mission-description-section h3 {
    color: var(--day-text);
    margin: 0 0 1rem 0;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mission-description-section p {
    color: var(--day-text-light);
    margin: 0;
    line-height: 1.6;
}

.mission-stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.mission-stat-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 15px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.mission-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.mission-stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--neon-blue), var(--neon-purple));
    color: white;
    font-size: 1.2rem;
}

.mission-stat-card .stat-info {
    flex: 1;
}

.mission-stat-card .stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--day-text);
    margin-bottom: 0.25rem;
}

.mission-stat-card .stat-label {
    display: block;
    font-size: 0.9rem;
    color: var(--day-text-light);
}

.mission-tabs-section {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    overflow: hidden;
}

.mission-detail-tabs {
    display: flex;
    background: var(--day-bg);
    border-bottom: 1px solid var(--day-border);
}

.mission-tab-btn {
    flex: 1;
    padding: 1rem 1.5rem;
    background: transparent;
    border: none;
    color: var(--day-text-light);
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    position: relative;
}

.mission-tab-btn:hover {
    background: var(--day-card-bg);
    color: var(--day-text);
}

.mission-tab-btn.active {
    background: var(--day-card-bg);
    color: var(--neon-blue);
    border-bottom: 3px solid var(--neon-blue);
}

.mission-tab-content {
    display: none;
    padding: 2rem;
}

.mission-tab-content.active {
    display: block;
}

.employees-detail-list,
.submissions-detail-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.employee-detail-card {
    background: var(--day-bg);
    border: 1px solid var(--day-border);
    border-radius: 15px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.employee-detail-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.employee-detail-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.employee-avatar-small {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--neon-cyan), var(--neon-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.employee-info-detail h4 {
    color: var(--day-text);
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
}

.employee-info-detail p {
    color: var(--day-text-light);
    margin: 0;
    font-size: 0.9rem;
}

.employee-status {
    margin-left: auto;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.employee-status.terminee {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.employee-status.en_cours {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.employee-progress-detail {
    margin-bottom: 1rem;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: var(--day-text-light);
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: var(--day-border);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--neon-cyan), var(--neon-blue));
    border-radius: 4px;
    transition: width 0.3s ease;
}

.employee-submissions-summary {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.submission-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 25px;
    font-size: 0.85rem;
    color: var(--day-text-light);
}

.submission-stat.validated {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border-color: rgba(34, 197, 94, 0.2);
}

.submission-stat.pending {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border-color: rgba(245, 158, 11, 0.2);
}

.submission-stat.rejected {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.2);
}

.submission-detail-card {
    background: var(--day-bg);
    border: 1px solid var(--day-border);
    border-radius: 15px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.submission-detail-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.submission-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.submission-employee,
.submission-task {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 25px;
    font-size: 0.85rem;
    color: var(--day-text-light);
}

.submission-status {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.submission-status.approuvee {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.submission-status.en_attente {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.submission-status.rejetee {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.submission-date {
    margin-left: auto;
    font-size: 0.85rem;
    color: var(--day-text-light);
}

.submission-content {
    color: var(--day-text-light);
    line-height: 1.5;
}

.submission-content p {
    margin: 0 0 0.5rem 0;
}

.submission-content p:last-child {
    margin-bottom: 0;
}

/* Mode nuit pour le modal détails mission */
body.night-mode .mission-header-large,
body.night-mode .mission-description-section,
body.night-mode .mission-stat-card,
body.night-mode .mission-tabs-section,
body.night-mode .employee-detail-card,
body.night-mode .submission-detail-card {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

body.night-mode .mission-title-large h2,
body.night-mode .mission-description-section h3,
body.night-mode .mission-stat-card .stat-value,
body.night-mode .employee-info-detail h4 {
    color: var(--night-text);
}

body.night-mode .mission-type-name {
    color: var(--night-text-light);
}

body.night-mode .mission-description-section p,
body.night-mode .mission-stat-card .stat-label,
body.night-mode .employee-info-detail p,
body.night-mode .progress-info,
body.night-mode .submission-stat,
body.night-mode .submission-employee,
body.night-mode .submission-task,
body.night-mode .submission-date,
body.night-mode .submission-content {
    color: var(--night-text-light);
}

body.night-mode .mission-detail-tabs {
    background: var(--night-bg);
    border-color: var(--night-border);
}

body.night-mode .mission-tab-btn {
    color: var(--night-text-light);
}

body.night-mode .mission-tab-btn:hover {
    background: var(--night-card-bg);
    color: var(--night-text);
}

body.night-mode .mission-tab-btn.active {
    background: var(--night-card-bg);
    color: var(--neon-cyan);
    border-bottom-color: var(--neon-cyan);
}

body.night-mode .employee-detail-card,
body.night-mode .submission-detail-card {
    background: var(--night-bg);
}

body.night-mode .submission-stat,
body.night-mode .submission-employee,
body.night-mode .submission-task {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

/* Responsive pour modal détails mission */
@media (max-width: 768px) {
    .mission-header-large {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
        align-items: center;
    }
    
    .mission-type-badge-large {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .mission-title-large {
        text-align: center;
        width: 100%;
    }
    
    .mission-title-large h2 {
        font-size: 1.5rem;
    }
    
    .mission-stats-section {
        grid-template-columns: 1fr;
    }
    
    .mission-detail-tabs {
        flex-direction: column;
    }
    
    .submission-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .submission-date {
        margin-left: 0;
    }
    
    .employee-submissions-summary {
        flex-direction: column;
        gap: 0.5rem;
    }
}

/* ========================================
   STYLES MODAL HISTORIQUE EMPLOYÉ
======================================== */
.employee-history-details {
    padding: 1rem;
}

.employee-header-large {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
}

.employee-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}

.employee-info-large h2 {
    margin: 0 0 1rem 0;
    color: var(--day-text);
}

.employee-stats-summary {
    display: flex;
    gap: 2rem;
}

.summary-stat {
    text-align: center;
}

.summary-stat .stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-primary);
    margin-bottom: 0.25rem;
}

.summary-stat .stat-label {
    font-size: 0.9rem;
    color: var(--day-text-light);
}

.history-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid var(--day-border);
}

.history-tab-btn {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    color: var(--day-text-light);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.history-tab-btn:hover {
    color: var(--day-primary);
}

.history-tab-btn.active {
    color: var(--day-primary);
    border-bottom-color: var(--day-primary);
}

.history-tab-content {
    display: none;
}

.history-tab-content.active {
    display: block;
}

.history-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.history-entry {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
}

.history-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--day-text-light);
    font-size: 0.9rem;
    min-width: 150px;
}

.history-date i {
    color: var(--day-primary);
}

.history-content {
    flex: 1;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.history-type {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    min-width: 80px;
    justify-content: center;
}

.history-type.mission {
    background: rgba(37, 99, 235, 0.1);
    color: #2563eb;
}

.history-type.bonus {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.history-details {
    flex: 1;
}

.history-details h4 {
    margin: 0 0 0.5rem 0;
    color: var(--day-text);
    font-size: 1rem;
}

.history-amounts {
    display: flex;
    gap: 1rem;
}

.amount {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.amount.euros {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.amount.points {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.missions-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mission-summary {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mission-info h4 {
    margin: 0 0 0.75rem 0;
    color: var(--day-text);
}

.mission-progress {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mission-progress span {
    color: var(--day-text-light);
    font-size: 0.9rem;
    min-width: 80px;
}

.mission-progress .progress-bar {
    width: 150px;
    height: 8px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    overflow: hidden;
}

.mission-progress .progress-fill {
    height: 100%;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    transition: width 0.3s ease;
}

.mission-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.mission-status.terminee {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.mission-status.en_cours {
    background: rgba(251, 191, 36, 0.1);
    color: #fbbf24;
}

.mission-status.pending {
    background: rgba(156, 163, 175, 0.1);
    color: #9ca3af;
}

/* Responsive pour le modal historique */
@media (max-width: 768px) {
    .employee-header-large {
        flex-direction: column;
        text-align: center;
    }
    
    .employee-stats-summary {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .history-entry {
        flex-direction: column;
    }
    
    .history-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .mission-summary {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .mission-progress {
        width: 100%;
    }
    
    .mission-progress .progress-bar {
        flex: 1;
    }
}

.mission-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 16px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
    background: rgba(37, 99, 235, 0.1);
    color: var(--day-primary);
    border: 1px solid rgba(37, 99, 235, 0.2);
}

.mission-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--day-text);
    line-height: 1.3;
}

.mission-description {
    color: var(--day-text-light);
    margin-bottom: 1.5rem;
    line-height: 1.6;
    font-size: 0.95rem;
}

.mission-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    color: var(--day-text-light);
    font-weight: 500;
}

.mission-rewards {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.reward-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(6, 214, 160, 0.1));
    border-radius: 12px;
    font-weight: 600;
    color: var(--day-primary);
    font-size: 0.9rem;
    border: 1px solid rgba(37, 99, 235, 0.2);
}

.mission-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--day-border);
}

.mission-date {
    font-size: 0.85rem;
    color: var(--day-text-light);
    font-weight: 500;
}

.mission-actions {
    display: flex;
    gap: 0.75rem;
}

.modern-btn-action {
    padding: 0.5rem 1rem;
    border: 2px solid;
    border-radius: 10px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.modern-btn-edit {
    color: var(--day-primary);
    border-color: var(--day-primary);
    background: transparent;
}

.modern-btn-edit:hover {
    background: var(--day-primary);
    color: white;
    transform: translateY(-2px);
}

.modern-btn-delete {
    color: #ef4444;
    border-color: #ef4444;
    background: transparent;
}

.modern-btn-delete:hover {
    background: #ef4444;
    color: white;
    transform: translateY(-2px);
}

/* ========================================
   VALIDATIONS MODERNES
======================================== */
.modern-validation-card {
    background: var(--day-card-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 32px var(--day-shadow);
    border-left: 4px solid #f59e0b;
    transition: all 0.3s ease;
}

.modern-validation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px var(--day-shadow);
}

.validation-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1.5rem;
}

.validation-info {
    flex: 1;
}

.validation-title {
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--day-text);
    font-size: 1.1rem;
}

.validation-meta {
    color: var(--day-text-light);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.validation-actions {
    display: flex;
    gap: 0.75rem;
    flex-shrink: 0;
}

.modern-btn-approve {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modern-btn-approve:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
}

.modern-btn-reject {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modern-btn-reject:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
}

/* ========================================
   ÉTAT VIDE MODERNE
======================================== */
.modern-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--day-text-light);
}

.modern-empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.6;
    color: var(--day-primary);
}

.modern-empty-state h3 {
    margin-bottom: 1rem;
    color: var(--day-text);
    font-size: 1.5rem;
    font-weight: 700;
}

.modern-empty-state p {
    margin-bottom: 2rem;
    font-size: 1rem;
    line-height: 1.6;
}

/* ========================================
   MODALES MODERNES
======================================== */
.modern-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 10000;
    animation: fadeIn 0.3s ease;
}

.modern-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modern-modal-content {
    background: var(--day-card-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    border-radius: 24px;
    max-width: 700px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease;
}

.modern-modal-content.large-modal {
    max-width: 900px;
    width: 95%;
}

.modern-modal-header {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    padding: 2rem;
    border-radius: 24px 24px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modern-modal-title {
    font-size: 1.3rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modern-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.modern-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.modern-modal-body {
    padding: 2.5rem;
}

.modern-modal-footer {
    padding: 1.5rem 2.5rem 2.5rem;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

/* ========================================
   FORMULAIRES MODERNES
======================================== */
.modern-form-group {
    margin-bottom: 1.5rem;
}

.modern-form-label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: var(--day-text);
    font-size: 0.95rem;
}

.modern-form-input {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid var(--day-border);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
}

.modern-form-input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: var(--day-glow);
    background: rgba(255, 255, 255, 0.95);
}

.modern-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.modern-btn-secondary {
    background: var(--day-text-light);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.modern-btn-secondary:hover {
    background: var(--day-text);
    transform: translateY(-2px);
}

/* ========================================
   ANIMATIONS
======================================== */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* ========================================
   MODE NUIT - SURCHARGES
======================================== */
body.night-mode {
    --day-primary: var(--night-primary);
    --day-secondary: var(--night-secondary);
    --day-accent: var(--night-accent);
    --day-card-bg: var(--night-card-bg);
    --day-text: var(--night-text);
    --day-text-light: var(--night-text-light);
    --day-shadow: var(--night-shadow);
    --day-border: var(--night-border);
    --day-glow: var(--night-glow);
}

body.night-mode .bg-animated {
    background: var(--night-bg-animated);
}

body.night-mode .modern-header,
body.night-mode .modern-stat-card,
body.night-mode .modern-tabs-container,
body.night-mode .modern-mission-card,
body.night-mode .modern-validation-card,
body.night-mode .modern-modal-content {
    background: var(--night-card-bg);
    color: var(--night-text);
    border-color: var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

body.night-mode .completed-mission:hover {
    box-shadow: 0 12px 32px rgba(34, 197, 94, 0.3);
    border-color: #22c55e;
}

body.night-mode .meta-item {
    color: var(--night-text-light);
}

body.night-mode .meta-item i {
    color: var(--night-accent);
}

body.night-mode .mission-meta {
    border-top-color: var(--night-border);
}

/* Styles mode nuit pour le modal des missions complètes */
body.night-mode .mission-description,
body.night-mode .stat-item,
body.night-mode .validation-detail-card {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

body.night-mode .mission-title-section h2,
body.night-mode .validations-section h3,
body.night-mode .validation-description h4,
body.night-mode .validation-proof h4,
body.night-mode .admin-comment h4 {
    color: var(--night-text);
}

body.night-mode .mission-description p,
body.night-mode .validation-description p,
body.night-mode .validation-proof p,
body.night-mode .admin-comment p {
    color: var(--night-text-light);
    background: rgba(255, 255, 255, 0.05);
    border-left-color: var(--night-accent);
}

body.night-mode .stat-label,
body.night-mode .date-item {
    color: var(--night-text-light);
}

body.night-mode .stat-value {
    color: var(--night-text);
}

body.night-mode .stat-item i,
body.night-mode .date-item i {
    color: var(--night-accent);
}

/* Styles mode nuit pour les cartes employés */
body.night-mode .global-stat-card,
body.night-mode .employee-reward-card {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

body.night-mode .global-stat-card .stat-value,
body.night-mode .employee-info h4,
body.night-mode .employee-rank,
body.night-mode .stat-details .stat-value {
    color: var(--night-text);
}

body.night-mode .global-stat-card .stat-label,
body.night-mode .employee-status,
body.night-mode .stat-details .stat-label,
body.night-mode .last-activity {
    color: var(--night-text-light);
}

body.night-mode .stat-item {
    background: rgba(255, 255, 255, 0.05);
    border-color: var(--night-border);
}

body.night-mode .employee-footer {
    border-top-color: var(--night-border);
}

body.night-mode .last-activity i {
    color: var(--night-accent);
}

/* Styles mode nuit pour le modal historique */
body.night-mode .employee-header-large,
body.night-mode .history-entry,
body.night-mode .mission-summary {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

body.night-mode .employee-info-large h2,
body.night-mode .history-details h4,
body.night-mode .mission-info h4 {
    color: var(--night-text);
}

body.night-mode .summary-stat .stat-value {
    color: var(--night-accent);
}

body.night-mode .summary-stat .stat-label,
body.night-mode .history-date,
body.night-mode .mission-progress span {
    color: var(--night-text-light);
}

body.night-mode .history-tab-btn {
    color: var(--night-text-light);
}

body.night-mode .history-tab-btn:hover,
body.night-mode .history-tab-btn.active {
    color: var(--night-accent);
    border-bottom-color: var(--night-accent);
}

body.night-mode .history-tabs {
    border-bottom-color: var(--night-border);
}

body.night-mode .history-date i {
    color: var(--night-accent);
}

body.night-mode .modern-modal {
    background: rgba(0, 0, 0, 0.8);
}

body.night-mode .modern-modal-header {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
}

body.night-mode .modern-modal-body,
body.night-mode .modern-modal-footer {
    background: var(--night-card-bg);
    color: var(--night-text);
}

body.night-mode .modern-form-label {
    color: var(--night-text);
}

body.night-mode .modern-form-input,
body.night-mode .modern-form-input:focus {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modern-form-input:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

body.night-mode .modern-btn {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    color: var(--night-text);
}

body.night-mode .modern-btn-secondary {
    background: var(--night-text-light);
    color: var(--night-text);
}

/* Règle spécifique pour mode jour */
body:not(.night-mode) .stat-value {
    color: #1e293b !important; /* Noir en mode jour */
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

/* ========================================
   NAVBAR MODE NUIT - CORRECTIONS
======================================== */
body.night-mode #desktop-navbar,
body.night-mode nav#desktop-navbar {
    background: rgba(15, 15, 25, 0.95) !important;
    backdrop-filter: blur(20px) !important;
    border-bottom: 1px solid var(--night-border) !important;
    box-shadow: 0 4px 20px var(--night-shadow) !important;
}

body.night-mode .navbar-brand img {
    filter: brightness(1.2) !important;
}

body.night-mode .navbar .btn,
body.night-mode .navbar .nav-link {
    color: var(--night-text) !important;
    border-color: var(--night-border) !important;
}

body.night-mode .navbar .btn:hover,
body.night-mode .navbar .nav-link:hover {
    background: rgba(0, 212, 255, 0.1) !important;
    border-color: var(--night-primary) !important;
    color: var(--night-primary) !important;
}

/* Animation SERVO en mode nuit */
body.night-mode .servo-logo-container {
    opacity: 1 !important;
    visibility: visible !important;
}

body.night-mode .servo-logo-container svg {
    opacity: 1 !important;
    visibility: visible !important;
}

body.night-mode .servo-logo-container path {
    opacity: 1 !important;
    visibility: visible !important;
}

/* Messages de bienvenue et autres éléments navbar en mode nuit */
body.night-mode .navbar .d-none.d-md-flex,
body.night-mode .navbar .navbar-text {
    color: var(--night-text-light) !important;
}

/* Dropdown en mode nuit */
body.night-mode .navbar .dropdown-menu {
    background: var(--night-card-bg) !important;
    border: 1px solid var(--night-border) !important;
    box-shadow: 0 8px 32px var(--night-shadow) !important;
}

body.night-mode .navbar .dropdown-item {
    color: var(--night-text) !important;
}

body.night-mode .navbar .dropdown-item:hover {
    background: rgba(0, 212, 255, 0.1) !important;
    color: var(--night-primary) !important;
}

body.night-mode .modern-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .modern-form-input {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modern-form-input:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

body.night-mode .stat-value {
    color: var(--night-text) !important;
}

/* ========================================
   MODAL DÉTAILS VALIDATION
======================================== */
.validation-details-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.validation-detail-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--day-border);
    border-radius: 12px;
    padding: 1.5rem;
}

.validation-detail-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--day-primary);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.validation-detail-content {
    color: var(--day-text);
    line-height: 1.6;
}

.validation-mission-info {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.validation-task-description {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.validation-proof-section {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.validation-photo-container {
    margin-top: 1rem;
}

.validation-photo {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform 0.3s ease;
}

.validation-photo:hover {
    transform: scale(1.02);
}

.validation-photo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 120px;
    background: rgba(156, 163, 175, 0.1);
    border: 2px dashed rgba(156, 163, 175, 0.3);
    border-radius: 8px;
    color: rgba(156, 163, 175, 0.7);
    font-style: italic;
}

.validation-actions-modal {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.validation-meta-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.validation-meta-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.validation-meta-label {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--day-text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.validation-meta-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--day-text);
}

.loading-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--day-text-light);
    gap: 0.5rem;
}

/* Mode nuit pour les détails de validation */
body.night-mode .validation-detail-section {
    background: rgba(15, 23, 42, 0.6);
    border-color: var(--night-border);
}

body.night-mode .validation-detail-title {
    color: var(--night-primary);
}

body.night-mode .validation-detail-content {
    color: var(--night-text);
}

body.night-mode .validation-mission-info {
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(147, 51, 234, 0.1));
    border-color: rgba(0, 212, 255, 0.2);
}

body.night-mode .validation-task-description {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
    border-color: rgba(16, 185, 129, 0.2);
}

body.night-mode .validation-proof-section {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
    border-color: rgba(245, 158, 11, 0.2);
}

body.night-mode .validation-photo-placeholder {
    background: rgba(71, 85, 105, 0.2);
    border-color: rgba(71, 85, 105, 0.4);
    color: rgba(148, 163, 184, 0.7);
}

body.night-mode .validation-meta-label {
    color: var(--night-text-light);
}

body.night-mode .validation-meta-value {
    color: var(--night-text);
}

body.night-mode .loading-spinner {
    color: var(--night-text-light);
}

/* ========================================
   CARTES VALIDATION CLIQUABLES
======================================== */
.clickable-validation {
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.clickable-validation:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.clickable-validation::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s ease;
}

.clickable-validation:hover::before {
    left: 100%;
}

.validation-view-icon {
    margin-right: 0.5rem;
    color: var(--day-primary);
    opacity: 0.7;
}

.validation-click-hint {
    font-size: 0.8rem;
    color: var(--day-text-light);
    font-style: italic;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.clickable-validation:hover .validation-click-hint {
    opacity: 1;
}

.validation-actions {
    z-index: 2;
    position: relative;
}

/* Badges pour les statuts */
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0.375rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.badge-warning {
    background-color: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.badge-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.badge-danger {
    background-color: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Mode nuit pour les cartes cliquables */
body.night-mode .clickable-validation:hover {
    box-shadow: 0 8px 25px var(--night-shadow);
}

body.night-mode .clickable-validation::before {
    background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.1), transparent);
}

body.night-mode .validation-view-icon {
    color: var(--night-primary);
}

body.night-mode .validation-click-hint {
    color: var(--night-text-light);
}

body.night-mode .badge-warning {
    background-color: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border-color: rgba(245, 158, 11, 0.3);
}

body.night-mode .badge-success {
    background-color: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.3);
}

body.night-mode .badge-danger {
    background-color: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.3);
}

/* ========================================
   RESPONSIVE DESIGN COMPLET
======================================== */

/* Mobile Portrait (320px - 480px) */
@media (max-width: 480px) {
    .missions-container {
        padding: 0.75rem;
        margin: 0;
    }

    .modern-header {
        padding: 1rem;
        margin: 0 -0.75rem 1rem -0.75rem;
    }

    .modern-title {
        font-size: 1.5rem;
        line-height: 1.3;
    }

    .modern-stats-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .modern-stat-card {
        padding: 1rem;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .stat-label {
        font-size: 0.75rem;
    }

    .modern-missions-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .mission-card {
        padding: 1rem;
    }

    .mission-title {
        font-size: 1rem;
    }

    .mission-description {
        font-size: 0.8rem;
    }

    .modern-tabs-header {
        flex-direction: column;
        gap: 0;
    }

    .modern-tab-button {
        border-radius: 0;
        border-bottom: 1px solid var(--day-border);
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }

    .modern-tab-button:first-child {
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    .modern-tab-button:last-child {
        border-bottom: none;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .modern-modal-content {
        width: 95%;
        margin: 0.5rem;
        max-height: 95vh;
    }

    .modern-modal-header {
        padding: 1rem;
    }

    .modern-modal-body {
        padding: 1rem;
    }

    .modern-modal-footer {
        padding: 1rem;
        flex-direction: column;
        gap: 0.5rem;
    }

    .modern-form-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .modern-form-input,
    .modern-form-select,
    .modern-form-textarea {
        font-size: 16px; /* Évite le zoom sur iOS */
    }

    .modern-btn {
        width: 100%;
        padding: 0.75rem;
        font-size: 0.9rem;
    }

    .validation-item {
        padding: 1rem;
    }

    .validation-header {
        flex-direction: column;
        gap: 0.75rem;
    }

    .validation-actions {
        width: 100%;
        gap: 0.5rem;
    }

    .modern-btn-approve,
    .modern-btn-reject {
        flex: 1;
        font-size: 0.8rem;
        padding: 0.5rem;
    }
}

/* Mobile Landscape & Small Tablets (481px - 768px) */
@media (min-width: 481px) and (max-width: 768px) {
    .missions-container {
        padding: 1rem;
    }

    .modern-header {
        padding: 1.5rem;
    }

    .modern-title {
        font-size: 2rem;
    }

    .modern-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .modern-missions-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .modern-tabs-header {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .modern-tab-button {
        flex: 1;
        min-width: calc(50% - 0.25rem);
    }

    .modern-modal-content {
        width: 90%;
        margin: 1rem;
    }

    .modern-form-row {
        grid-template-columns: 1fr;
    }

    .validation-header {
        flex-direction: row;
        align-items: center;
    }

    .validation-actions {
        flex-direction: row;
        gap: 0.5rem;
    }
}

/* Tablets (769px - 1024px) */
@media (min-width: 769px) and (max-width: 1024px) {
    .missions-container {
        padding: 1.5rem;
        max-width: 100%;
    }

    .modern-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .modern-missions-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .modern-tabs-header {
        flex-wrap: wrap;
    }

    .modern-tab-button {
        flex: 1;
        min-width: 200px;
    }

    .modern-modal-content {
        width: 85%;
        max-width: 600px;
    }

    .modern-form-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Small Desktop (1025px - 1200px) */
@media (min-width: 1025px) and (max-width: 1200px) {
    .missions-container {
        max-width: 1000px;
        padding: 2rem;
    }

    .modern-stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .modern-missions-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .modern-modal-content {
        max-width: 700px;
    }
}

/* Large Desktop (1201px - 1600px) */
@media (min-width: 1201px) and (max-width: 1600px) {
    .missions-container {
        max-width: 1200px;
    }

    .modern-stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .modern-missions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Extra Large Desktop (1601px+) */
@media (min-width: 1601px) {
    .missions-container {
        max-width: 1400px;
    }

    .modern-stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .modern-missions-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Orientation Portrait sur tablettes */
@media (orientation: portrait) and (min-width: 768px) and (max-width: 1024px) {
    .modern-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .modern-missions-grid {
        grid-template-columns: 1fr;
    }
}

/* Orientation Landscape sur mobiles */
@media (orientation: landscape) and (max-height: 500px) {
    .modern-header {
        padding: 1rem;
    }

    .modern-title {
        font-size: 1.5rem;
    }

    .modern-stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .modern-modal-content {
        max-height: 85vh;
    }
}

/* Très petits écrans (moins de 320px) */
@media (max-width: 320px) {
    .missions-container {
        padding: 0.5rem;
    }

    .modern-header {
        padding: 0.75rem;
    }

    .modern-title {
        font-size: 1.25rem;
    }

    .stat-value {
        font-size: 1.25rem;
    }

    .stat-label {
        font-size: 0.7rem;
    }

    .mission-title {
        font-size: 0.9rem;
    }

    .modern-tab-button {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
    }
}

/* Écrans ultra-larges (4K+) */
@media (min-width: 2560px) {
    .missions-container {
        max-width: 1800px;
    }

    .modern-missions-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .modern-title {
        font-size: 3rem;
    }

    .stat-value {
        font-size: 2.5rem;
    }
}
</style>

<!-- Font Awesome pour les icônes -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Fond animé -->
<div class="bg-animated" id="animatedBg"></div>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<div class="missions-container" id="mainContent" style="display: none;">
    <!-- Header Moderne -->
    <div class="modern-header">
        <div class="header-content">
            <div>
                <h1 class="modern-title">
                    <i class="fas fa-trophy"></i> Administration des Missions
                </h1>
                <p class="modern-subtitle">Gérez les missions et récompenses de votre équipe</p>
            </div>
            <button class="modern-btn" onclick="openNewMissionModal()">
                <i class="fas fa-plus"></i>Nouvelle Mission
            </button>
        </div>
    </div>

    <!-- Statistiques Modernes -->
    <div class="modern-stats-grid">
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_missions_actives; ?></div>
                    <div class="stat-label">Missions Actives</div>
                </div>
            </div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_missions_en_cours; ?></div>
                    <div class="stat-label">En Cours</div>
                </div>
            </div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_missions_completees; ?></div>
                    <div class="stat-label">Complétées ce mois</div>
                </div>
            </div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_validations_en_attente; ?></div>
                    <div class="stat-label">Validations en attente</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Onglets Modernes -->
    <div class="modern-tabs-container">
        <div class="modern-tabs-header">
            <button class="modern-tab-button active" data-tab="missions">
                <i class="fas fa-tasks"></i>Missions Actives
                <span class="tab-badge"><?php echo count($missions); ?></span>
            </button>
            <button class="modern-tab-button" data-tab="missions-inactives">
                <i class="fas fa-pause-circle"></i>Missions Inactives
                <span class="tab-badge"><?php echo count($missions_inactives); ?></span>
            </button>
            <button class="modern-tab-button" data-tab="validations">
                <i class="fas fa-clipboard-check"></i>Validations
                <span class="tab-badge"><?php echo count($validations); ?></span>
            </button>
            <button class="modern-tab-button" data-tab="completed">
                <i class="fas fa-check-circle"></i>Missions Complètes
                <span class="tab-badge"><?php echo count($missions_completes); ?></span>
            </button>
            <button class="modern-tab-button" data-tab="rewards">
                <i class="fas fa-coins"></i>Cagnotte & XP
            </button>
        </div>

        <!-- Contenu Missions -->
        <div class="modern-tab-content active" id="missions">
            <?php if (empty($missions)): ?>
                <div class="modern-empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Aucune mission active</h3>
                    <p>Créez votre première mission pour motiver votre équipe</p>
                    <button class="modern-btn" onclick="openNewMissionModal()">
                        <i class="fas fa-plus"></i>Créer une mission
                    </button>
                </div>
            <?php else: ?>
                <div class="modern-missions-grid">
                    <?php foreach ($missions as $mission): ?>
                        <div class="modern-mission-card" onclick="showMissionDetails(<?php echo $mission['id']; ?>)">
                            <div class="mission-type-badge" style="background: <?php echo $mission['type_couleur'] ?? '#2563eb'; ?>20; color: <?php echo $mission['type_couleur'] ?? '#2563eb'; ?>; border-color: <?php echo $mission['type_couleur'] ?? '#2563eb'; ?>40;">
                                <i class="<?php echo $mission['type_icone'] ?? 'fas fa-star'; ?>"></i>
                                <?php echo htmlspecialchars($mission['type_nom'] ?? 'Mission'); ?>
                            </div>
                            
                            <div class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></div>
                            <div class="mission-description"><?php echo htmlspecialchars(substr($mission['description'], 0, 120)) . '...'; ?></div>
                            
                            <div class="mission-stats">
                                <span>
                                    <i class="fas fa-users"></i>
                                    <?php echo $mission['nb_participants']; ?> participants
                                </span>
                                <span>
                                    <i class="fas fa-target"></i>
                                    Objectif: <?php echo $mission['objectif_nombre']; ?>
                                </span>
                            </div>
                            
                            <div class="mission-rewards">
                                <?php if ($mission['recompense_euros'] > 0): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-euro-sign"></i>
                                        <?php echo $mission['recompense_euros']; ?>€
                                    </div>
                                <?php endif; ?>
                                <?php if ($mission['recompense_points'] > 0): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-star"></i>
                                        <?php echo $mission['recompense_points']; ?> XP
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mission-footer">
                                <div class="mission-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($mission['created_at'])); ?>
                                </div>
                                <div class="mission-actions">
                                    <button class="modern-btn-action modern-btn-edit" onclick="event.stopPropagation(); editMission(<?php echo $mission['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="modern-btn-action modern-btn-delete" onclick="event.stopPropagation(); deactivateMission(<?php echo $mission['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contenu Missions Inactives -->
        <div class="modern-tab-content" id="missions-inactives">
            <?php if (empty($missions_inactives)): ?>
                <div class="modern-empty-state">
                    <i class="fas fa-pause-circle"></i>
                    <h3>Aucune mission inactive</h3>
                    <p>Toutes vos missions sont actuellement actives</p>
                </div>
            <?php else: ?>
                <div class="missions-grid">
                    <?php foreach ($missions_inactives as $mission): ?>
                        <div class="modern-mission-card inactive">
                            <div class="mission-header">
                                <div class="mission-type-badge" style="background: <?php echo $mission['type_couleur'] ?? '#6366f1'; ?>">
                                    <i class="<?php echo $mission['type_icone'] ?? 'fas fa-tasks'; ?>"></i>
                                </div>
                                <div class="mission-status inactive">
                                    <i class="fas fa-pause-circle"></i>
                                    Inactive
                                </div>
                            </div>
                            
                            <div class="mission-content">
                                <h3><?php echo htmlspecialchars($mission['titre'] ?? ''); ?></h3>
                                <p><?php echo htmlspecialchars($mission['description'] ?? ''); ?></p>
                                
                                <div class="mission-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $mission['nb_participants'] ?? 0; ?> participants</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-target"></i>
                                        <span>Objectif: <?php echo $mission['objectif_nombre'] ?? 0; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mission-rewards">
                                <?php if ($mission['recompense_euros'] > 0): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-euro-sign"></i>
                                        <?php echo $mission['recompense_euros']; ?>€
                                    </div>
                                <?php endif; ?>
                                <?php if ($mission['recompense_points'] > 0): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-star"></i>
                                        <?php echo $mission['recompense_points']; ?> XP
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mission-footer">
                                <div class="mission-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($mission['created_at'])); ?>
                                </div>
                                <div class="mission-actions">
                                    <button class="action-btn view" onclick="showMissionDetails(<?php echo $mission['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn activate" onclick="activateMission(<?php echo $mission['id']; ?>)">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <button class="action-btn delete" onclick="deleteMission(<?php echo $mission['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contenu Validations -->
        <div class="modern-tab-content" id="validations">
            <?php if (empty($validations)): ?>
                <div class="modern-empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>Aucune validation en attente</h3>
                    <p>Toutes les validations ont été traitées</p>
                </div>
            <?php else: ?>
                <?php foreach ($validations as $validation): ?>
                    <div class="modern-validation-card clickable-validation" onclick="showValidationDetails(<?php echo $validation['id']; ?>)">
                        <div class="validation-header">
                            <div class="validation-info">
                                <div class="validation-title">
                                    <i class="fas fa-eye validation-view-icon"></i>
                                    <?php echo htmlspecialchars($validation['mission_titre']); ?>
                                </div>
                                <div class="validation-meta">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($validation['user_name'] ?? $validation['username'] ?? 'Utilisateur #' . $validation['user_id']); ?>
                                </div>
                                <div class="validation-meta">
                                    <i class="fas fa-chart-line"></i>
                                    Progression: <?php echo $validation['progression_actuelle']; ?>
                                </div>
                                <div class="validation-meta">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($validation['created_at'])); ?>
                                </div>
                                <div class="validation-click-hint">
                                    <i class="fas fa-mouse-pointer"></i>
                                    Cliquer pour voir les détails
                                </div>
                            </div>
                            <div class="validation-actions" onclick="event.stopPropagation()">
                                <button class="modern-btn-approve" onclick="validerTacheAdmin(<?php echo $validation['id']; ?>, 'approve')">
                                    <i class="fas fa-check"></i>Approuver
                                </button>
                                <button class="modern-btn-reject" onclick="validerTacheAdmin(<?php echo $validation['id']; ?>, 'reject')">
                                    <i class="fas fa-times"></i>Rejeter
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Contenu Missions Complètes -->
        <div class="modern-tab-content" id="completed">
            <?php if (empty($missions_completes)): ?>
                <div class="modern-empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>Aucune mission complète</h3>
                    <p>Les missions terminées apparaîtront ici</p>
                </div>
            <?php else: ?>
                <div class="modern-missions-grid">
                    <?php foreach ($missions_completes as $mission): ?>
                        <div class="modern-mission-card completed-mission" onclick="showCompletedMissionDetails(<?php echo $mission['user_mission_id']; ?>)">
                            <div class="mission-header">
                                <div class="mission-type-badge" style="background: <?php echo htmlspecialchars($mission['type_couleur'] ?? '#6366f1'); ?>">
                                    <i class="<?php echo htmlspecialchars($mission['type_icone'] ?? 'fas fa-tasks'); ?>"></i>
                                    <?php echo htmlspecialchars($mission['type_nom'] ?? 'Mission'); ?>
                                </div>
                                <div class="mission-status completed">
                                    <i class="fas fa-check-circle"></i>
                                    Terminée
                                </div>
                            </div>
                            
                            <div class="mission-content">
                                <h3><?php echo htmlspecialchars($mission['titre']); ?></h3>
                                <p><?php echo htmlspecialchars($mission['description']); ?></p>
                                
                                <div class="mission-progress">
                                    <div class="progress-info">
                                        <span>Progression</span>
                                        <span class="progress-text"><?php echo $mission['progression']; ?>/<?php echo $mission['objectif_nombre']; ?> tâches</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: 100%"></div>
                                    </div>
                                </div>
                                
                                <div class="mission-rewards">
                                    <div class="reward-item">
                                        <i class="fas fa-euro-sign"></i>
                                        <span><?php echo number_format($mission['recompense_euros'], 2); ?>€</span>
                                    </div>
                                    <div class="reward-item">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo $mission['recompense_points']; ?> XP</span>
                                    </div>
                                </div>
                                
                                <div class="mission-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo htmlspecialchars($mission['user_name'] ?? $mission['username'] ?? 'Utilisateur #' . $mission['user_id']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span><?php echo date('d/m/Y H:i', strtotime($mission['date_completion'])); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clipboard-list"></i>
                                        <span><?php echo $mission['total_validations']; ?> validations</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mission-actions">
                                <button class="modern-btn modern-btn--secondary" onclick="event.stopPropagation(); showCompletedMissionDetails(<?php echo $mission['user_mission_id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                    Voir les détails
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contenu Récompenses -->
        <div class="modern-tab-content" id="rewards">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="color: var(--day-text); margin: 0;"><i class="fas fa-coins"></i> Cagnotte et Points XP par Employé</h3>
                <button class="modern-btn" onclick="refreshEmployeeRewards()">
                    <i class="fas fa-refresh"></i>Actualiser
                </button>
            </div>
            
            <!-- Statistiques globales -->
            <div class="global-stats-row">
                <?php 
                $total_cagnotte = array_sum(array_column($employees_rewards, 'total_gagne_euros'));
                $total_points = array_sum(array_column($employees_rewards, 'total_gagne_points'));
                $total_employees = count($employees_rewards);
                ?>
                <div class="global-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-euro-sign"></i>
                </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($total_cagnotte, 2); ?>€</div>
                        <div class="stat-label">Total Cagnotte</div>
            </div>
        </div>
                <div class="global-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($total_points); ?></div>
                        <div class="stat-label">Total Points XP</div>
                    </div>
                </div>
                <div class="global-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_employees; ?></div>
                        <div class="stat-label">Employés Actifs</div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des employés -->
            <?php if (empty($employees_rewards)): ?>
                <div class="modern-empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>Aucun employé trouvé</h3>
                    <p>Les données des employés apparaîtront ici</p>
                </div>
            <?php else: ?>
                <div class="employees-rewards-grid">
                    <?php foreach ($employees_rewards as $employee): ?>
                        <div class="employee-reward-card" onclick="showEmployeeHistory(<?php echo $employee['user_id']; ?>)">
                            <div class="employee-header">
                                <div class="employee-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="employee-info">
                                    <h4>Employé #<?php echo $employee['user_id']; ?></h4>
                                    <p class="employee-status">
                                        <?php echo $employee['missions_en_cours']; ?> mission(s) en cours
                                    </p>
                                </div>
                                <div class="employee-rank">
                                    <?php 
                                    $rank = array_search($employee, $employees_rewards) + 1;
                                    $rankIcon = $rank == 1 ? 'fas fa-crown' : ($rank <= 3 ? 'fas fa-medal' : 'fas fa-trophy');
                                    $rankColor = $rank == 1 ? '#ffd700' : ($rank <= 3 ? '#c0c0c0' : '#cd7f32');
                                    ?>
                                    <i class="<?php echo $rankIcon; ?>" style="color: <?php echo $rankColor; ?>"></i>
                                    <span>#<?php echo $rank; ?></span>
                                </div>
                            </div>
                            
                            <div class="employee-stats">
                                <div class="stat-row">
                                    <div class="stat-item">
                                        <div class="stat-icon euros">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <div class="stat-details">
                                            <div class="stat-value"><?php echo number_format($employee['solde_euros'], 2); ?>€</div>
                                            <div class="stat-label">Solde Actuel</div>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon points">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="stat-details">
                                            <div class="stat-value"><?php echo number_format($employee['solde_points']); ?></div>
                                            <div class="stat-label">Points XP</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-row">
                                    <div class="stat-item">
                                        <div class="stat-icon total">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                        <div class="stat-details">
                                            <div class="stat-value"><?php echo number_format($employee['total_gagne_euros'], 2); ?>€</div>
                                            <div class="stat-label">Total Gagné</div>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon missions">
                                            <i class="fas fa-tasks"></i>
                                        </div>
                                        <div class="stat-details">
                                            <div class="stat-value"><?php echo $employee['missions_completees']; ?></div>
                                            <div class="stat-label">Missions</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="employee-footer">
                                <div class="last-activity">
                                    <i class="fas fa-clock"></i>
                                    <span>
                                        <?php 
                                        if ($employee['derniere_mission']) {
                                            echo 'Dernière mission: ' . date('d/m/Y', strtotime($employee['derniere_mission']));
                                        } else {
                                            echo 'Aucune mission complétée';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <button class="view-history-btn" onclick="event.stopPropagation(); showEmployeeHistory(<?php echo $employee['user_id']; ?>)">
                                    <i class="fas fa-history"></i>
                                    Historique
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Mission -->
<div class="modern-modal" id="newMissionModal">
    <div class="modern-modal-content">
        <div class="modern-modal-header">
            <div class="modern-modal-title">
                <i class="fas fa-plus"></i>Nouvelle Mission
            </div>
            <button class="modern-modal-close" onclick="closeModal('newMissionModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modern-modal-body">
            <form id="newMissionForm">
                <div class="modern-form-row">
                    <div class="modern-form-group">
                        <label class="modern-form-label">Titre de la mission</label>
                        <input type="text" class="modern-form-input" name="titre" required>
                    </div>
                    <div class="modern-form-group">
                        <label class="modern-form-label">Type de mission</label>
                        <select class="modern-form-input" name="mission_type_id" required>
                            <option value="">Sélectionner un type</option>
                            <option value="1">Trottinettes</option>
                            <option value="2">Smartphones</option>
                            <option value="3">LeBonCoin</option>
                            <option value="4">eBay</option>
                            <option value="5">Réparations Express</option>
                            <option value="6">Service Client</option>
                        </select>
                    </div>
                </div>
                <div class="modern-form-group">
                    <label class="modern-form-label">Description</label>
                    <textarea class="modern-form-input" name="description" rows="3" required></textarea>
                </div>
                <div class="modern-form-row">
                    <div class="modern-form-group">
                        <label class="modern-form-label">Objectif (quantité)</label>
                        <input type="number" class="modern-form-input" name="objectif_nombre" min="1" required>
                    </div>
                    <div class="modern-form-group">
                        <label class="modern-form-label">Récompense (€)</label>
                        <input type="number" class="modern-form-input" name="recompense_euros" min="0" step="0.01">
                    </div>
                    <div class="modern-form-group">
                        <label class="modern-form-label">Points XP</label>
                        <input type="number" class="modern-form-input" name="recompense_points" min="0">
                    </div>
                </div>
            </form>
        </div>
        <div class="modern-modal-footer">
            <button type="button" class="modern-btn-secondary" onclick="closeModal('newMissionModal')">Annuler</button>
            <button type="button" class="modern-btn" onclick="createMission()">
                <i class="fas fa-save"></i>Créer la Mission
            </button>
        </div>
    </div>
</div>

<!-- Modal Détails Mission -->
<div class="modern-modal" id="missionDetailsModal">
    <div class="modern-modal-content large-modal">
        <div class="modern-modal-header">
            <div class="modern-modal-title">
                <i class="fas fa-info-circle"></i>Détails de la Mission
            </div>
            <button class="modern-modal-close" onclick="closeModal('missionDetailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modern-modal-body" id="missionDetailsContent">
            <!-- Le contenu sera chargé via JavaScript -->
        </div>
    </div>
</div>

<!-- Modal Détails Validation -->
<div class="modern-modal" id="validationDetailsModal">
    <div class="modern-modal-content">
        <div class="modern-modal-header">
            <div class="modern-modal-title">
                <i class="fas fa-clipboard-check"></i>Détails de la Validation
            </div>
            <button class="modern-modal-close" onclick="closeModal('validationDetailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modern-modal-body">
            <div id="validationDetailsContainer">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Chargement des détails...
                </div>
            </div>
        </div>
        <div class="modern-modal-footer">
            <div class="validation-actions-modal">
                <button class="modern-btn modern-btn-approve" id="modalApproveBtn" onclick="approveFromModal()">
                    <i class="fas fa-check"></i> Approuver
                </button>
                <button class="modern-btn modern-btn-reject" id="modalRejectBtn" onclick="rejectFromModal()">
                    <i class="fas fa-times"></i> Rejeter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détails Mission Complète -->
<div class="modern-modal" id="completedMissionModal">
    <div class="modern-modal-content large-modal">
        <div class="modern-modal-header">
            <div class="modern-modal-title">
                <i class="fas fa-check-circle"></i>Détails de la Mission Complète
            </div>
            <button class="modern-modal-close" onclick="closeModal('completedMissionModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modern-modal-body" id="completedMissionContent">
            <!-- Le contenu sera chargé via JavaScript -->
        </div>
    </div>
</div>

<!-- Modal Historique Employé -->
<div class="modern-modal" id="employeeHistoryModal">
    <div class="modern-modal-content large-modal">
        <div class="modern-modal-header">
            <div class="modern-modal-title">
                <i class="fas fa-history"></i>Historique de l'Employé
            </div>
            <button class="modern-modal-close" onclick="closeModal('employeeHistoryModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modern-modal-body" id="employeeHistoryContent">
            <!-- Le contenu sera chargé via JavaScript -->
        </div>
    </div>
</div>

<style>
.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

body:not(.night-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.night-mode) .dark-loader {
  display: none;
}

body:not(.night-mode) .light-loader {
  display: flex;
}

.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

.loading-spinner {
    display: inline-block;
    width: 24px;
    height: 24px;
    border: 3px solid rgba(37, 99, 235, 0.2);
    border-top: 3px solid var(--day-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// ==================== DÉTECTION ET APPLICATION DU THÈME ====================
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
        document.getElementById('animatedBg').classList.add('night-mode');
        console.log('🌙 Mode nuit détecté et appliqué immédiatement');
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
        document.getElementById('animatedBg').classList.remove('night-mode');
        console.log('☀️ Mode jour détecté et appliqué immédiatement');
    }
})();

// ==================== GESTION DES ONGLETS ====================
function switchTab(tabName) {
    // Désactiver tous les onglets
    document.querySelectorAll('.modern-tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.modern-tab-content').forEach(content => content.classList.remove('active'));
    
    // Activer l'onglet sélectionné
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(tabName).classList.add('active');
    
    // Charger le contenu spécifique si nécessaire
    if (tabName === 'rewards') {
        showUserRewards();
    }
}

// Event listeners pour les onglets
document.querySelectorAll('.modern-tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tab = this.dataset.tab;
        switchTab(tab);
    });
});

// ==================== GESTION DES MODALES ====================
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

function openNewMissionModal() {
    openModal('newMissionModal');
}

// Fermer les modales en cliquant à l'extérieur
document.querySelectorAll('.modern-modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});

// ==================== FONCTIONS AJAX ====================
function showMissionDetails(missionId) {
    // Ouvrir le modal
    openModal('missionDetailsModal');
    
    // Afficher le spinner de chargement
    document.getElementById('missionDetailsContent').innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i> Chargement des détails...
            </div>
    `;
    
    // Récupérer les détails via AJAX
    fetch(`ajax/get_mission_details.php?mission_id=${missionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMissionDetails(data.data);
            } else {
                document.getElementById('missionDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Erreur: ${data.message}
        </div>
    `;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('missionDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Erreur de connexion
                </div>
            `;
        });
}

function displayMissionDetails(data) {
    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        return new Date(dateString).toLocaleString('fr-FR');
    };

    const mission = data.mission;
    const employees = data.employees;
    const submissions = data.submissions;
    const stats = data.stats;

    // Générer la liste des employés
    let employeesHtml = '';
    if (employees && employees.length > 0) {
        employeesHtml = employees.map((employee) => {
            const progressPercent = mission.objectif_nombre > 0 ? (employee.progression / mission.objectif_nombre) * 100 : 0;
            
            return `
                <div class="employee-detail-card">
                    <div class="employee-detail-header">
                        <div class="employee-avatar-small">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="employee-info-detail">
                            <h4>${employee.user_name || employee.username || 'Employé #' + employee.user_id}</h4>
                            <p>Inscrit le ${formatDate(employee.date_inscription)}</p>
                        </div>
                        <div class="employee-status ${employee.mission_statut}">
                            <i class="fas fa-${employee.mission_statut === 'terminee' ? 'check-circle' : employee.mission_statut === 'en_cours' ? 'clock' : 'pause-circle'}"></i>
                            ${employee.mission_statut === 'terminee' ? 'Terminée' : employee.mission_statut === 'en_cours' ? 'En cours' : 'En attente'}
                        </div>
                    </div>
                    
                    <div class="employee-progress-detail">
                        <div class="progress-info">
                            <span>Progression: ${employee.progression}/${mission.objectif_nombre} tâches</span>
                            <span>${progressPercent.toFixed(1)}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progressPercent}%"></div>
                        </div>
                    </div>
                    
                    <div class="employee-submissions-summary">
                        <div class="submission-stat">
                            <i class="fas fa-paper-plane"></i>
                            <span>${employee.total_soumissions} soumissions</span>
                        </div>
                        <div class="submission-stat validated">
                            <i class="fas fa-check"></i>
                            <span>${employee.soumissions_validees} validées</span>
                        </div>
                        <div class="submission-stat pending">
                            <i class="fas fa-clock"></i>
                            <span>${employee.soumissions_en_attente} en attente</span>
                        </div>
                        ${employee.soumissions_rejetees > 0 ? `
                            <div class="submission-stat rejected">
                                <i class="fas fa-times"></i>
                                <span>${employee.soumissions_rejetees} rejetées</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    } else {
        employeesHtml = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Aucun employé inscrit à cette mission.
            </div>
        `;
    }

    // Générer la liste des soumissions récentes
    let submissionsHtml = '';
    if (submissions && submissions.length > 0) {
        submissionsHtml = submissions.slice(0, 10).map((submission) => `
            <div class="submission-detail-card">
                <div class="submission-header">
                    <div class="submission-employee">
                        <i class="fas fa-user"></i>
                        ${submission.user_name || submission.username || 'Employé #' + submission.user_id}
                    </div>
                    <div class="submission-task">
                        <i class="fas fa-tasks"></i>
                        Tâche #${submission.tache_numero}
                    </div>
                    <div class="submission-status ${submission.statut}">
                        <i class="fas fa-${submission.statut === 'approuvee' ? 'check-circle' : submission.statut === 'rejetee' ? 'times-circle' : 'clock'}"></i>
                        ${submission.statut === 'approuvee' ? 'Validée' : submission.statut === 'rejetee' ? 'Rejetée' : 'En attente'}
                </div>
                    <div class="submission-date">
                        ${formatDate(submission.created_at)}
            </div>
                    </div>
                <div class="submission-content">
                    <p><strong>Description:</strong> ${submission.description || 'Aucune description'}</p>
                    ${submission.preuve_text ? `<p><strong>Preuve:</strong> ${submission.preuve_text}</p>` : ''}
                    ${submission.commentaire_admin ? `<p><strong>Commentaire admin:</strong> ${submission.commentaire_admin}</p>` : ''}
                    </div>
                </div>
        `).join('');
    } else {
        submissionsHtml = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Aucune soumission pour cette mission.
            </div>
        `;
    }

    const content = `
        <div class="mission-details-container">
            <div class="mission-overview-section">
                <div class="mission-header-large">
                    <div class="mission-type-badge-large" style="background: ${mission.type_couleur || '#6366f1'}">
                        <i class="${mission.type_icone || 'fas fa-tasks'}"></i>
                    </div>
                    <div class="mission-title-large">
                        <div class="mission-type-name">${mission.type_nom || 'Mission'}</div>
                        <h2>${mission.titre}</h2>
                        <div class="mission-header-actions">
                            <div class="mission-status ${mission.statut}">
                                <i class="fas fa-${mission.statut === 'active' ? 'play-circle' : 'pause-circle'}"></i>
                                ${mission.statut === 'active' ? 'Active' : 'Inactive'}
                            </div>
                            <button class="mission-toggle-btn ${mission.statut === 'active' ? 'deactivate' : 'activate'}" 
                                    onclick="toggleMissionStatus(${mission.id}, '${mission.statut === 'active' ? 'inactive' : 'active'}')">
                                <i class="fas fa-${mission.statut === 'active' ? 'pause' : 'play'}"></i>
                                ${mission.statut === 'active' ? 'Désactiver' : 'Activer'}
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="mission-description-section">
                    <h3><i class="fas fa-info-circle"></i> Description</h3>
                    <p>${mission.description}</p>
                </div>
                
                <div class="mission-stats-section">
                    <div class="mission-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-target"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value">${mission.objectif_nombre}</span>
                            <span class="stat-label">Tâches requises</span>
                        </div>
                    </div>
                    <div class="mission-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                        <div class="stat-info">
                            <span class="stat-value">${stats.total_participants}</span>
                            <span class="stat-label">Participants</span>
                    </div>
                </div>
                    <div class="mission-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
            </div>
                        <div class="stat-info">
                            <span class="stat-value">${stats.progression_moyenne}</span>
                            <span class="stat-label">Progression moy.</span>
                        </div>
                    </div>
                    <div class="mission-stat-card">
                    <div class="stat-icon">
                            <i class="fas fa-paper-plane"></i>
                    </div>
                        <div class="stat-info">
                            <span class="stat-value">${stats.total_soumissions}</span>
                            <span class="stat-label">Soumissions</span>
                    </div>
                </div>
                    <div class="mission-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-euro-sign"></i>
            </div>
                        <div class="stat-info">
                            <span class="stat-value">${mission.recompense_euros}€</span>
                            <span class="stat-label">Récompense</span>
        </div>
                    </div>
                    <div class="mission-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value">${mission.recompense_points}</span>
                            <span class="stat-label">Points XP</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mission-tabs-section">
                <div class="mission-detail-tabs">
                    <button class="mission-tab-btn active" onclick="switchMissionTab('employees')">
                        <i class="fas fa-users"></i>Employés (${stats.total_participants})
                    </button>
                    <button class="mission-tab-btn" onclick="switchMissionTab('submissions')">
                        <i class="fas fa-paper-plane"></i>Soumissions (${stats.total_soumissions})
                    </button>
                </div>
                
                <div class="mission-tab-content active" id="employees-tab">
                    <div class="employees-detail-list">
                        ${employeesHtml}
                    </div>
                </div>
                
                <div class="mission-tab-content" id="submissions-tab">
                    <div class="submissions-detail-list">
                        ${submissionsHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('missionDetailsContent').innerHTML = content;
}

function switchMissionTab(tabName) {
    // Désactiver tous les onglets
    document.querySelectorAll('.mission-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.mission-tab-content').forEach(content => content.classList.remove('active'));
    
    // Activer l'onglet sélectionné
    document.querySelector(`[onclick="switchMissionTab('${tabName}')"]`).classList.add('active');
    document.getElementById(`${tabName}-tab`).classList.add('active');
}

function toggleMissionStatus(missionId, newStatus) {
    // Confirmation avant changement
    const action = newStatus === 'active' ? 'activer' : 'désactiver';
    if (!confirm(`Êtes-vous sûr de vouloir ${action} cette mission ?`)) {
        return;
    }
    
    // Afficher un indicateur de chargement
    const button = event.target.closest('.mission-toggle-btn');
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    button.disabled = true;
    
    // Envoyer la requête AJAX
    const formData = new FormData();
    formData.append('mission_id', missionId);
    formData.append('status', newStatus);
    
    fetch('ajax/toggle_mission_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher un message de succès
            showToast('Statut de la mission mis à jour avec succès !', 'success');
            
            // Fermer le modal
            closeModal('missionDetailsModal');
            
            // Recharger la page pour actualiser les données
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            // Afficher l'erreur
            showToast('Erreur: ' + data.message, 'error');
            
            // Restaurer le bouton
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showToast('Erreur de connexion', 'error');
        
        // Restaurer le bouton
        button.innerHTML = originalContent;
        button.disabled = false;
    });
}

function activateMission(missionId) {
    toggleMissionStatus(missionId, 'active');
}

function deleteMission(missionId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement cette mission ?')) {
        return;
    }
    
    // TODO: Implémenter la suppression de mission
    showToast('Fonction de suppression à implémenter', 'info');
}

function refreshEmployeeRewards() {
    // Recharger la page pour actualiser les données
    location.reload();
}

// ==================== GESTION DE L'HISTORIQUE EMPLOYÉ ====================
function showEmployeeHistory(userId) {
    // Ouvrir le modal
    openModal('employeeHistoryModal');
    
    // Afficher le spinner de chargement
    document.getElementById('employeeHistoryContent').innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i> Chargement de l'historique...
        </div>
    `;
    
    // Récupérer l'historique via AJAX
    fetch(`ajax/get_employee_history.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayEmployeeHistory(data.data);
            } else {
                document.getElementById('employeeHistoryContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Erreur: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('employeeHistoryContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Erreur de connexion
                </div>
            `;
        });
}

function displayEmployeeHistory(data) {
    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        return new Date(dateString).toLocaleString('fr-FR');
    };

    const employee = data.employee;
    const history = data.history;
    const missions = data.missions;

    let historyHtml = '';
    if (history && history.length > 0) {
        historyHtml = history.map((entry, index) => `
            <div class="history-entry">
                <div class="history-date">
                    <i class="fas fa-calendar"></i>
                    ${formatDate(entry.created_at)}
                </div>
                <div class="history-content">
                    <div class="history-type ${entry.type_gain}">
                        <i class="fas fa-${entry.type_gain === 'mission' ? 'tasks' : 'gift'}"></i>
                        ${entry.type_gain === 'mission' ? 'Mission' : 'Bonus'}
                    </div>
                    <div class="history-details">
                        <h4>${entry.mission_titre || 'Gain manuel'}</h4>
                        <div class="history-amounts">
                            ${entry.montant_euros > 0 ? `<span class="amount euros">+${entry.montant_euros}€</span>` : ''}
                            ${entry.points_attribues > 0 ? `<span class="amount points">+${entry.points_attribues} XP</span>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    } else {
        historyHtml = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Aucun historique trouvé pour cet employé.
            </div>
        `;
    }

    let missionsHtml = '';
    if (missions && missions.length > 0) {
        missionsHtml = missions.map((mission) => `
            <div class="mission-summary">
                <div class="mission-info">
                    <h4>${mission.titre}</h4>
                    <div class="mission-progress">
                        <span>${mission.progression}/${mission.objectif_nombre} tâches</span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${(mission.progression / mission.objectif_nombre) * 100}%"></div>
                        </div>
                    </div>
                </div>
                <div class="mission-status ${mission.statut}">
                    <i class="fas fa-${mission.statut === 'terminee' ? 'check-circle' : mission.statut === 'en_cours' ? 'clock' : 'pause-circle'}"></i>
                    ${mission.statut === 'terminee' ? 'Terminée' : mission.statut === 'en_cours' ? 'En cours' : 'En attente'}
                </div>
            </div>
        `).join('');
    } else {
        missionsHtml = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Aucune mission trouvée pour cet employé.
            </div>
        `;
    }

    const content = `
        <div class="employee-history-details">
            <div class="employee-overview">
                <div class="employee-header-large">
                    <div class="employee-avatar-large">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="employee-info-large">
                        <h2>Employé #${employee.user_id}</h2>
                        <div class="employee-stats-summary">
                            <div class="summary-stat">
                                <span class="stat-value">${employee.solde_euros}€</span>
                                <span class="stat-label">Solde actuel</span>
                            </div>
                            <div class="summary-stat">
                                <span class="stat-value">${employee.solde_points}</span>
                                <span class="stat-label">Points XP</span>
                            </div>
                            <div class="summary-stat">
                                <span class="stat-value">${employee.total_gagne_euros}€</span>
                                <span class="stat-label">Total gagné</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="history-tabs">
                <button class="history-tab-btn active" onclick="switchHistoryTab('gains')">
                    <i class="fas fa-coins"></i>Historique des Gains
                </button>
                <button class="history-tab-btn" onclick="switchHistoryTab('missions')">
                    <i class="fas fa-tasks"></i>Missions (${missions.length})
                </button>
            </div>
            
            <div class="history-tab-content active" id="gains-tab">
                <div class="history-timeline">
                    ${historyHtml}
                </div>
            </div>
            
            <div class="history-tab-content" id="missions-tab">
                <div class="missions-list">
                    ${missionsHtml}
                </div>
            </div>
        </div>
    `;

    document.getElementById('employeeHistoryContent').innerHTML = content;
}

function switchHistoryTab(tabName) {
    // Désactiver tous les onglets
    document.querySelectorAll('.history-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.history-tab-content').forEach(content => content.classList.remove('active'));
    
    // Activer l'onglet sélectionné
    document.querySelector(`[onclick="switchHistoryTab('${tabName}')"]`).classList.add('active');
    document.getElementById(`${tabName}-tab`).classList.add('active');
}

function validerTacheAdmin(validationId, action) {
    const actionText = action === 'approve' ? 'approuver' : 'rejeter';
    
    if (!confirm(`Êtes-vous sûr de vouloir ${actionText} cette validation ?`)) {
        return;
    }

    // Demander un commentaire optionnel
    const commentaire = prompt(`Commentaire optionnel pour cette ${action === 'approve' ? 'approbation' : 'rejection'} :`);
    
    // Afficher un indicateur de chargement
    const originalButton = event.target;
    const originalText = originalButton.textContent;
    originalButton.textContent = 'Traitement...';
    originalButton.disabled = true;

    // Faire l'appel AJAX réel
    fetch('ajax/validate_mission.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            validation_id: validationId,
            action: action,
            commentaire: commentaire || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher le message de succès
            alert(data.message);
            
            // Recharger la page pour mettre à jour la liste
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du traitement de la validation');
    })
    .finally(() => {
        // Restaurer le bouton
        originalButton.textContent = originalText;
        originalButton.disabled = false;
    });
}

function createMission() {
    const form = document.getElementById('newMissionForm');
    const formData = new FormData(form);
    
    // Validation basique
    const titre = formData.get('titre');
    const typeId = formData.get('mission_type_id');
    const description = formData.get('description');
    const objectif = formData.get('objectif_nombre');
    
    if (!titre || !typeId || !description || !objectif) {
        alert('Veuillez remplir tous les champs obligatoires');
        return;
    }
    
    // Désactiver le bouton pendant la création
    const createBtn = document.querySelector('#newMissionModal .modern-btn');
    const originalText = createBtn.innerHTML;
    createBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Création...';
    createBtn.disabled = true;
    
    // Créer l'objet de données à envoyer
    const missionData = {
        titre: titre,
        mission_type_id: typeId,
        description: description,
        objectif_nombre: parseInt(objectif),
        recompense_euros: parseFloat(formData.get('recompense_euros')) || 0,
        recompense_points: parseInt(formData.get('recompense_points')) || 0,
        statut: 'active'
    };
    
    // Appel AJAX pour créer la mission
    fetch('ajax/create_mission.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(missionData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Mission créée avec succès !');
            closeModal('newMissionModal');
            form.reset();
            location.reload(); // Recharger la page pour voir la nouvelle mission
        } else {
            alert('Erreur: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la création de la mission');
    })
    .finally(() => {
        // Réactiver le bouton
        createBtn.innerHTML = originalText;
        createBtn.disabled = false;
    });
}

function deactivateMission(missionId) {
    if (!confirm('Êtes-vous sûr de vouloir désactiver cette mission ?')) {
        return;
    }
    
    // Simuler la désactivation pour l'instant
    alert(`Mission #${missionId} désactivée avec succès ! (Simulation)`);
    // En production, cette fonction fera l'appel AJAX réel
}

function editMission(missionId) {
    alert('Fonction d\'édition à implémenter');
}

// ==================== GESTION DES DÉTAILS DE VALIDATION ====================
let currentValidationId = null;

function showValidationDetails(validationId) {
    currentValidationId = validationId;
    
    // Ouvrir le modal
    openModal('validationDetailsModal');
    
    // Afficher le spinner de chargement
    document.getElementById('validationDetailsContainer').innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i> Chargement des détails...
        </div>
    `;
    
    // Récupérer les détails via AJAX
    fetch(`ajax/get_validation_details.php?id=${validationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayValidationDetails(data.data);
            } else {
                document.getElementById('validationDetailsContainer').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Erreur: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('validationDetailsContainer').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Erreur lors du chargement des détails
                </div>
            `;
        });
}

function displayValidationDetails(validation) {
    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        return new Date(dateString).toLocaleString('fr-FR');
    };

    const photoHtml = validation.preuve_url ? 
        `<div class="validation-photo-container">
            <img src="${validation.preuve_url}" alt="Preuve de validation" class="validation-photo" onclick="openPhotoModal('${validation.preuve_url}')">
        </div>` :
        `<div class="validation-photo-placeholder">
            <i class="fas fa-image"></i> Aucune photo fournie
        </div>`;

    const statusBadge = validation.statut === 'en_attente' ? 
        '<span class="badge badge-warning">En attente</span>' :
        validation.statut === 'approuvee' ? 
        '<span class="badge badge-success">Approuvée</span>' :
        '<span class="badge badge-danger">Rejetée</span>';

    const html = `
        <div class="validation-details-content">
            <!-- Informations de la mission -->
            <div class="validation-detail-section validation-mission-info">
                <div class="validation-detail-title">
                    <i class="fas fa-bullseye"></i>
                    Mission concernée
                </div>
                <div class="validation-detail-content">
                    <h4>${validation.mission_titre}</h4>
                    <p>${validation.mission_description}</p>
                    <div class="validation-meta-info">
                        <div class="validation-meta-item">
                            <div class="validation-meta-label">Objectif</div>
                            <div class="validation-meta-value">${validation.mission_objectif}</div>
                        </div>
                        <div class="validation-meta-item">
                            <div class="validation-meta-label">Récompense</div>
                            <div class="validation-meta-value">${validation.mission_recompense_euros}€ + ${validation.mission_recompense_points} pts</div>
                        </div>
                        <div class="validation-meta-item">
                            <div class="validation-meta-label">Utilisateur</div>
                            <div class="validation-meta-value">${validation.user_name}</div>
                        </div>
                        <div class="validation-meta-item">
                            <div class="validation-meta-label">Statut</div>
                            <div class="validation-meta-value">${statusBadge}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description de la tâche accomplie -->
            <div class="validation-detail-section validation-task-description">
                <div class="validation-detail-title">
                    <i class="fas fa-clipboard-check"></i>
                    Description de la tâche accomplie
                </div>
                <div class="validation-detail-content">
                    ${validation.description || 'Aucune description fournie'}
                </div>
            </div>

            <!-- Preuve et détails complémentaires -->
            <div class="validation-detail-section validation-proof-section">
                <div class="validation-detail-title">
                    <i class="fas fa-camera"></i>
                    Preuve et détails complémentaires
                </div>
                <div class="validation-detail-content">
                    ${photoHtml}
                    
                    <div class="validation-meta-info" style="margin-top: 1.5rem;">
                        <div class="validation-meta-item">
                            <div class="validation-meta-label">Date de soumission</div>
                            <div class="validation-meta-value">${formatDate(validation.created_at)}</div>
                        </div>
                        <div class="validation-meta-item">
                            <div class="validation-meta-label">Type de validation</div>
                            <div class="validation-meta-value">${validation.type_validation === 'completion' ? 'Completion' : 'Progrès'}</div>
                        </div>
                        ${validation.date_traitement ? `
                        <div class="validation-meta-item">
                            <div class="validation-meta-label">Date de traitement</div>
                            <div class="validation-meta-value">${formatDate(validation.date_traitement)}</div>
                        </div>
                        <div class="validation-meta-item">
                            <div class="validation-meta-label">Traité par</div>
                            <div class="validation-meta-value">${validation.admin_name || 'Administrateur'}</div>
                        </div>
                        ` : ''}
                        ${validation.commentaire_admin ? `
                        <div class="validation-meta-item" style="grid-column: 1 / -1;">
                            <div class="validation-meta-label">Commentaire administrateur</div>
                            <div class="validation-meta-value">${validation.commentaire_admin}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('validationDetailsContainer').innerHTML = html;
    
    // Masquer/afficher les boutons selon le statut
    const approveBtn = document.getElementById('modalApproveBtn');
    const rejectBtn = document.getElementById('modalRejectBtn');
    
    if (validation.statut === 'en_attente') {
        approveBtn.style.display = 'flex';
        rejectBtn.style.display = 'flex';
    } else {
        approveBtn.style.display = 'none';
        rejectBtn.style.display = 'none';
    }
}

function approveFromModal() {
    if (currentValidationId) {
        closeModal('validationDetailsModal');
        validerTacheAdmin(currentValidationId, 'approve');
    }
}

function rejectFromModal() {
    if (currentValidationId) {
        closeModal('validationDetailsModal');
        validerTacheAdmin(currentValidationId, 'reject');
    }
}

// ==================== GESTION DES MISSIONS COMPLÈTES ====================
function showCompletedMissionDetails(userMissionId) {
    // Ouvrir le modal
    openModal('completedMissionModal');
    
    // Afficher le spinner de chargement
    document.getElementById('completedMissionContent').innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i> Chargement des détails...
        </div>
    `;
    
    // Récupérer les détails via AJAX
    fetch(`ajax/get_completed_mission_details.php?user_mission_id=${userMissionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCompletedMissionDetails(data.data);
            } else {
                document.getElementById('completedMissionContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Erreur: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('completedMissionContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Erreur de connexion
                </div>
            `;
        });
}

function displayCompletedMissionDetails(data) {
    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        return new Date(dateString).toLocaleString('fr-FR');
    };

    const mission = data.mission;
    const validations = data.validations;

    let validationsHtml = '';
    if (validations && validations.length > 0) {
        validationsHtml = validations.map((validation, index) => `
            <div class="validation-detail-card">
                <div class="validation-detail-header">
                    <div class="validation-number">
                        <i class="fas fa-tasks"></i>
                        Tâche #${validation.tache_numero}
                    </div>
                    <div class="validation-status ${validation.statut}">
                        <i class="fas fa-${validation.statut === 'validee' ? 'check-circle' : validation.statut === 'refusee' ? 'times-circle' : 'clock'}"></i>
                        ${validation.statut === 'validee' ? 'Validée' : validation.statut === 'refusee' ? 'Refusée' : 'En attente'}
                    </div>
                </div>
                
                <div class="validation-detail-content">
                    <div class="validation-description">
                        <h4><i class="fas fa-clipboard-list"></i> Description de la tâche</h4>
                        <p>${validation.description || 'Aucune description fournie'}</p>
                    </div>
                    
                    ${validation.preuve_text ? `
                        <div class="validation-proof">
                            <h4><i class="fas fa-file-text"></i> Preuve fournie</h4>
                            <p>${validation.preuve_text}</p>
                        </div>
                    ` : ''}
                    
                    <div class="validation-dates">
                        <div class="date-item">
                            <i class="fas fa-paper-plane"></i>
                            <span>Soumise le: ${formatDate(validation.created_at)}</span>
                        </div>
                        ${validation.validated_at ? `
                            <div class="date-item">
                                <i class="fas fa-check"></i>
                                <span>Validée le: ${formatDate(validation.validated_at)}</span>
                            </div>
                        ` : ''}
                    </div>
                    
                    ${validation.commentaire_admin ? `
                        <div class="admin-comment">
                            <h4><i class="fas fa-comment"></i> Commentaire administrateur</h4>
                            <p>${validation.commentaire_admin}</p>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    } else {
        validationsHtml = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Aucune validation trouvée pour cette mission.
            </div>
        `;
    }

    const content = `
        <div class="completed-mission-details">
            <div class="mission-overview">
                <div class="mission-title-section">
                    <h2><i class="fas fa-check-circle"></i> ${mission.titre}</h2>
                    <div class="mission-type-badge" style="background: ${mission.type_couleur || '#6366f1'}">
                        <i class="${mission.type_icone || 'fas fa-tasks'}"></i>
                        ${mission.type_nom || 'Mission'}
                    </div>
                </div>
                
                <div class="mission-description">
                    <p>${mission.description}</p>
                </div>
                
                <div class="mission-stats-grid">
                    <div class="stat-item">
                        <i class="fas fa-target"></i>
                        <div>
                            <span class="stat-label">Objectif</span>
                            <span class="stat-value">${mission.objectif_nombre} tâches</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <span class="stat-label">Progression</span>
                            <span class="stat-value">${mission.progression}/${mission.objectif_nombre}</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-euro-sign"></i>
                        <div>
                            <span class="stat-label">Récompense</span>
                            <span class="stat-value">${mission.recompense_euros}€</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-star"></i>
                        <div>
                            <span class="stat-label">Points XP</span>
                            <span class="stat-value">${mission.recompense_points} XP</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-user"></i>
                        <div>
                            <span class="stat-label">Utilisateur</span>
                            <span class="stat-value">#${mission.user_id}</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <span class="stat-label">Terminée le</span>
                            <span class="stat-value">${formatDate(mission.date_completion)}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="validations-section">
                <h3><i class="fas fa-clipboard-check"></i> Détail des validations (${validations.length})</h3>
                <div class="validations-list">
                    ${validationsHtml}
                </div>
            </div>
        </div>
    `;

    document.getElementById('completedMissionContent').innerHTML = content;
}

function openPhotoModal(photoUrl) {
    // Créer un modal simple pour afficher la photo en grand
    const photoModal = document.createElement('div');
    photoModal.className = 'photo-modal-overlay';
    photoModal.innerHTML = `
        <div class="photo-modal-content">
            <img src="${photoUrl}" alt="Preuve de validation" style="max-width: 90vw; max-height: 90vh; border-radius: 8px;">
            <button class="photo-modal-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Ajouter les styles pour le modal photo
    photoModal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        cursor: pointer;
    `;
    
    photoModal.querySelector('.photo-modal-content').style.cssText = `
        position: relative;
        cursor: default;
    `;
    
    photoModal.querySelector('.photo-modal-close').style.cssText = `
        position: absolute;
        top: -40px;
        right: -40px;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    `;
    
    // Fermer en cliquant sur l'overlay
    photoModal.addEventListener('click', (e) => {
        if (e.target === photoModal) {
            photoModal.remove();
        }
    });
    
    document.body.appendChild(photoModal);
}

// ==================== GESTION DU THÈME ====================
function applyTheme() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.body.classList.add('night-mode');
        document.getElementById('animatedBg').classList.add('night-mode');
        console.log('Mode nuit activé');
    } else {
        document.body.classList.remove('night-mode');
        document.getElementById('animatedBg').classList.remove('night-mode');
        console.log('Mode jour activé');
    }
}

// Écouter les changements de préférences système
if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (localStorage.getItem('theme') === null) {
            if (e.matches) {
                document.body.classList.add('night-mode');
                document.getElementById('animatedBg').classList.add('night-mode');
                console.log('Passage automatique en mode nuit');
            } else {
                document.body.classList.remove('night-mode');
                document.getElementById('animatedBg').classList.remove('night-mode');
                console.log('Passage automatique en mode jour');
            }
        }
    });
}

function toggleDarkMode() {
    document.body.classList.toggle('night-mode');
    document.getElementById('animatedBg').classList.toggle('night-mode');
    const isDark = document.body.classList.contains('night-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    console.log('Mode basculé vers:', isDark ? 'nuit' : 'jour');
}

// ==================== INITIALISATION ====================
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Appliquer le thème
    applyTheme();
    
    // Les données des employés sont déjà chargées côté serveur
    // Pas besoin de charger via AJAX au démarrage
    
    // Gérer le loader
    setTimeout(function() {
        loader.classList.add('fade-out');
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500);
    }, 300);
});
</script>
