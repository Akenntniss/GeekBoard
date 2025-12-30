<?php
// Vérification d'authentification simplifiée
if (!isset($_SESSION['user_id'])) {
    // Initialiser une session de test si pas de session active
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
}

$shop_pdo = getShopDBConnection();

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
            m.id, m.titre, m.description, m.objectif_quantite, m.recompense_euros, m.recompense_points, m.statut, m.created_at,
            mt.nom as type_nom, mt.icone as type_icone, mt.couleur as type_couleur,
            COUNT(DISTINCT um.id) as nb_participants,
            COUNT(DISTINCT CASE WHEN um.statut = 'terminee' THEN um.id END) as nb_completes
        FROM missions m
        LEFT JOIN mission_types mt ON m.type_id = mt.id
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

// Récupérer les validations en attente
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            mv.id, mv.user_mission_id, mv.tache_numero, mv.statut, mv.date_soumission, mv.description,
            m.titre as mission_titre,
            u.full_name as user_nom,
            um.progres as progression_actuelle
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN missions m ON um.mission_id = m.id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE mv.statut = 'en_attente'
        ORDER BY mv.date_soumission DESC
    ");
    $stmt->execute();
    $validations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur récupération validations: " . $e->getMessage());
}
?>

<!-- Meta viewport pour la responsivité mobile -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<style>
/* FIX NAVBAR - Obligatoire pour affichage correct */
/* Masquer dock mobile sur desktop */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    /* Forcer navbar desktop visible */
    #desktop-navbar, nav#desktop-navbar, .navbar, nav.navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 10000 !important;
        height: 60px !important;
        width: 100% !important;
    }
    /* Surcharger navbar-servo-fix.css */
    body #desktop-navbar, html body #desktop-navbar {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
    }
    /* Éléments navbar visibles */
    #desktop-navbar * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Container navbar avec centrage vertical parfait */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.75rem 1rem !important;
        min-height: 60px !important;
    }
    /* Logo avec centrage vertical parfait */
    #desktop-navbar .navbar-brand {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    #desktop-navbar .navbar-brand img {
        height: 32px !important;
        width: auto !important;
        vertical-align: middle !important;
    }
    /* Boutons avec centrage vertical parfait */
    #desktop-navbar .btn,
    #desktop-navbar .navbar-nav .nav-link,
    #desktop-navbar .dropdown-toggle {
        display: flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 0.375rem 0.75rem !important;
        margin: 0.125rem 0.25rem !important;
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Correction spécifique pour les icônes dans les boutons */
    #desktop-navbar .btn i,
    #desktop-navbar .navbar-nav .nav-link i,
    #desktop-navbar .dropdown-toggle i {
        vertical-align: middle !important;
        line-height: 1 !important;
    }
    /* Messages de bienvenue centrés */
    #desktop-navbar .d-none.d-md-flex {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
    }
    /* Forcer l'alignement vertical pour tous les éléments flex */
    #desktop-navbar .d-flex {
        align-items: center !important;
    }
    /* Animation SERVO centrée parfaitement */
    body .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10001 !important;
        display: flex !important;
        align-items: center !important;
        height: 32px !important;
        background: transparent !important;
        pointer-events: none !important;
        overflow: visible !important;
    }
    
    /* S'assurer que l'animation SERVO est visible */
    body .servo-logo-container .loader {
        display: flex !important;
        margin: 0 !important;
        height: 32px !important;
        align-items: center !important;
        gap: 2px !important;
        background: transparent !important;
        z-index: 10001 !important;
    }
    
    body .servo-logo-container svg {
        background: transparent !important;
        background-color: transparent !important;
        z-index: 10001 !important;
        position: relative !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    body .servo-logo-container path {
        z-index: 10001 !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    /* Réserver espace navbar */
    body {
        padding-top: 80px !important;
    }
}

/* Styles généraux navbar (mobile + desktop) */
#desktop-navbar, nav#desktop-navbar {
    display: block !important;
    visibility: visible !important;
    position: fixed !important;
    top: 0 !important;
    z-index: 10000 !important;
}

/* Masquer navbar sur mobile */
@media (max-width: 767px) {
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
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
            <button class="modern-tab-button" data-tab="validations">
                <i class="fas fa-clipboard-check"></i>Validations
                <span class="tab-badge"><?php echo count($validations); ?></span>
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
                                    Objectif: <?php echo $mission['objectif_quantite']; ?>
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
                                    <?php echo htmlspecialchars($validation['user_nom']); ?>
                                </div>
                                <div class="validation-meta">
                                    <i class="fas fa-chart-line"></i>
                                    Progression: <?php echo $validation['progression_actuelle']; ?>
                                </div>
                                <div class="validation-meta">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($validation['date_soumission'])); ?>
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

        <!-- Contenu Récompenses -->
        <div class="modern-tab-content" id="rewards">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="color: var(--day-text); margin: 0;"><i class="fas fa-coins"></i> Cagnotte et Points XP</h3>
                <button class="modern-btn" onclick="showUserRewards()">
                    <i class="fas fa-refresh"></i>Actualiser
                </button>
            </div>
            <div id="userRewardsContainer">
                <div style="text-align: center; padding: 2rem;">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 1rem; color: var(--day-text-light);">Chargement des données...</p>
                </div>
            </div>
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
                        <select class="modern-form-input" name="type_id" required>
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
                        <input type="number" class="modern-form-input" name="objectif_quantite" min="1" required>
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
    <div class="modern-modal-content">
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
    // Simuler les détails d'une mission pour l'instant
    const mockDetails = `
        <div style="padding: 1rem;">
            <h5 style="color: var(--day-text); margin-bottom: 1rem;">Mission #${missionId}</h5>
            <div style="background: var(--day-card-bg); padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
                <p style="color: var(--day-text-light); margin-bottom: 0.5rem;"><strong>Statut:</strong> Active</p>
                <p style="color: var(--day-text-light); margin-bottom: 0.5rem;"><strong>Participants:</strong> 5 employés</p>
                <p style="color: var(--day-text-light); margin-bottom: 0.5rem;"><strong>Progression:</strong> 65%</p>
                <p style="color: var(--day-text-light); margin-bottom: 0;">Les détails complets de la mission seront disponibles prochainement.</p>
            </div>
        </div>
    `;
    
    document.getElementById('missionDetailsContent').innerHTML = mockDetails;
    openModal('missionDetailsModal');
}

function showUserRewards() {
    // Simuler des données de récompenses pour l'instant
    const mockData = `
        <div class="modern-stats-grid">
            <div class="modern-stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">€245.50</div>
                        <div class="stat-label">Total Cagnotte</div>
                    </div>
                </div>
            </div>
            <div class="modern-stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">1,250</div>
                        <div class="stat-label">Points XP</div>
                    </div>
                </div>
            </div>
            <div class="modern-stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">8</div>
                        <div class="stat-label">Employés Actifs</div>
                    </div>
                </div>
            </div>
            <div class="modern-stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">15</div>
                        <div class="stat-label">Missions Complétées</div>
                    </div>
                </div>
            </div>
        </div>
        <div style="margin-top: 2rem; padding: 1.5rem; background: var(--day-card-bg); border-radius: 16px; border: 1px solid var(--day-border);">
            <h4 style="color: var(--day-text); margin-bottom: 1rem;"><i class="fas fa-chart-line"></i> Évolution des Récompenses</h4>
            <p style="color: var(--day-text-light);">Les données détaillées des récompenses seront disponibles prochainement.</p>
        </div>
    `;
    
    document.getElementById('userRewardsContainer').innerHTML = mockData;
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
    const typeId = formData.get('type_id');
    const description = formData.get('description');
    const objectif = formData.get('objectif_quantite');
    
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
        type_id: typeId,
        description: description,
        objectif_quantite: parseInt(objectif),
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
                            <div class="validation-meta-value">${formatDate(validation.date_soumission)}</div>
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
    
    // Charger les récompenses dès le démarrage
    setTimeout(showUserRewards, 500);
    
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
