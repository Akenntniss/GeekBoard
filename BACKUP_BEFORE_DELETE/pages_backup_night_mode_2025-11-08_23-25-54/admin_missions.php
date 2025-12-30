<?php
// Vérification des droits d'accès admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    set_message("Accès refusé. Vous devez être administrateur pour accéder à cette page.", "error");
    redirect('accueil');
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

<!-- Font Awesome pour les icônes -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* ==================== VARIABLES CSS ==================== */
    :root {
        --primary-color: #4361ee;
        --primary-hover: #3651d4;
        --success-color: #52b788;
        --warning-color: #f77f00;
        --danger-color: #ef476f;
        --info-color: #06d6a0;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
        --border-color: #e9ecef;
        --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
        --shadow-medium: 0 5px 15px rgba(0, 0, 0, 0.15);
        --shadow-heavy: 0 15px 35px rgba(0, 0, 0, 0.2);
        --border-radius: 12px;
        --transition: all 0.3s ease;
    }

    /* ==================== CONTENEUR PRINCIPAL ==================== */
    .admin-missions-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
        width: 100%;
    }
    
    /* Responsive pour différentes tailles d'écrans PC */
    @media (min-width: 1920px) {
        .admin-missions-container {
            max-width: 1600px;
            padding: 30px;
        }
    }
    
    @media (max-width: 1400px) {
        .admin-missions-container {
            max-width: 95%;
            padding: 15px;
        }
    }

    /* ==================== HEADER ==================== */
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary-color), #6c5ce7);
        color: white;
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: var(--border-radius);
        box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3);
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header-title h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .header-title p {
        opacity: 0.8;
        font-size: 1.1rem;
    }
    
    /* Responsive pour le header */
    @media (min-width: 1200px) {
        .dashboard-header {
            padding: 3rem;
        }
        
        .header-title h1 {
            font-size: 2.5rem;
        }
        
        .header-title p {
            font-size: 1.2rem;
        }
        
        .btn-new-mission {
            padding: 15px 30px;
            font-size: 1.1rem;
        }
    }
    
    @media (min-width: 1600px) {
        .header-title h1 {
            font-size: 3rem;
        }
        
        .header-title p {
            font-size: 1.3rem;
        }
    }

    .btn-new-mission {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-new-mission:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-2px);
    }

    /* ==================== STATISTIQUES ==================== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    /* Responsive pour les statistiques */
    @media (min-width: 1200px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
    }
    
    @media (min-width: 1600px) {
        .stats-grid {
            gap: 2.5rem;
        }
        
        .stat-card {
            padding: 2rem;
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            font-size: 1.8rem;
        }
        
        .stat-content .stat-value {
            font-size: 2.5rem;
        }
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .stat-icon.primary { background: var(--primary-color); }
    .stat-icon.success { background: var(--success-color); }
    .stat-icon.warning { background: var(--warning-color); }
    .stat-icon.info { background: var(--info-color); }

    .stat-content .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark-color);
    }

    .stat-content .stat-label {
        color: #6c757d;
        font-weight: 500;
    }

    /* ==================== ONGLETS ==================== */
    .tabs-container {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        overflow: hidden;
    }

    .tabs-header {
        display: flex;
        background: #f8f9fa;
        border-bottom: 1px solid var(--border-color);
    }
    
    /* Responsive pour les onglets */
    @media (max-width: 768px) {
        .tabs-header {
            flex-direction: column;
        }
        
        .tab-button {
            text-align: left;
            justify-content: flex-start;
        }
    }
    
    @media (min-width: 1200px) {
        .tab-button {
            padding: 1.5rem 2rem;
            font-size: 1.1rem;
        }
    }

    .tab-button {
        flex: 1;
        padding: 1rem 1.5rem;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        color: #6c757d;
        transition: var(--transition);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .tab-button:hover {
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary-color);
    }

    .tab-button.active {
        background: var(--primary-color);
        color: white;
    }

    .tab-badge {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .tab-button:not(.active) .tab-badge {
        background: var(--primary-color);
        color: white;
    }

    .tab-content {
        display: none;
        padding: 2rem;
    }

    .tab-content.active {
        display: block;
    }

    /* ==================== CARTES MISSIONS ==================== */
    .missions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
    }
    
    /* Responsive pour les cartes missions */
    @media (min-width: 1200px) {
        .missions-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }
    }
    
    @media (min-width: 1600px) {
        .missions-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 2.5rem;
        }
    }
    
    @media (min-width: 1920px) {
        .missions-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    .mission-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-light);
        transition: var(--transition);
        cursor: pointer;
        border: 2px solid transparent;
    }

    .mission-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-heavy);
        border-color: var(--primary-color);
    }

    .mission-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    .mission-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--dark-color);
    }

    .mission-description {
        color: #6c757d;
        margin-bottom: 1rem;
        line-height: 1.5;
    }

    .mission-stats {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        color: #6c757d;
    }

    .mission-rewards {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .reward-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(67, 97, 238, 0.1);
        border-radius: 8px;
        font-weight: 600;
        color: var(--primary-color);
        font-size: 0.9rem;
    }

    .mission-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .mission-date {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .mission-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-action {
        padding: 6px 12px;
        border: 1px solid;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: var(--transition);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-edit {
        color: var(--primary-color);
        border-color: var(--primary-color);
        background: transparent;
    }

    .btn-edit:hover {
        background: var(--primary-color);
        color: white;
    }

    .btn-delete {
        color: var(--danger-color);
        border-color: var(--danger-color);
        background: transparent;
    }

    .btn-delete:hover {
        background: var(--danger-color);
        color: white;
    }

    /* ==================== VALIDATIONS ==================== */
    .validation-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow-light);
        border-left: 4px solid var(--warning-color);
    }

    .validation-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    /* Responsive pour les validations */
    @media (max-width: 768px) {
        .validation-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .validation-actions {
            align-self: stretch;
        }
        
        .btn-approve, .btn-reject {
            flex: 1;
        }
    }
    
    @media (min-width: 1200px) {
        .validation-card {
            padding: 2rem;
        }
    }

    .validation-info {
        flex: 1;
    }

    .validation-title {
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--dark-color);
    }

    .validation-meta {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .validation-actions {
        display: flex;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .btn-approve {
        background: var(--success-color);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
    }

    .btn-approve:hover {
        background: #459a73;
        transform: translateY(-2px);
    }

    .btn-reject {
        background: var(--danger-color);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
    }

    .btn-reject:hover {
        background: #d63a5c;
        transform: translateY(-2px);
    }

    /* ==================== ÉTAT VIDE ==================== */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state h3 {
        margin-bottom: 1rem;
        color: var(--dark-color);
    }

    .empty-state p {
        margin-bottom: 1.5rem;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
    }

    /* ==================== MODALES ==================== */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    .modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        border-radius: var(--border-radius);
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-heavy);
        animation: slideIn 0.3s ease;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-color), #6c5ce7);
        color: white;
        padding: 1.5rem;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 1.2rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: var(--transition);
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .modal-body {
        padding: 2rem;
    }

    .modal-footer {
        padding: 1rem 2rem 2rem;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }
    
    /* Responsive pour les modals */
    @media (min-width: 1200px) {
        .modal-dialog {
            max-width: 800px;
        }
        
        .modal-body {
            padding: 3rem;
        }
        
        .modal-footer {
            padding: 1.5rem 3rem 3rem;
        }
    }
    
    @media (min-width: 1600px) {
        .modal-dialog {
            max-width: 1000px;
        }
    }

    /* ==================== FORMULAIRES ==================== */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark-color);
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    /* Responsive pour les formulaires */
    @media (min-width: 1200px) {
        .form-row {
            gap: 1.5rem;
        }
        
        .form-control {
            padding: 15px;
            font-size: 1.1rem;
        }
        
        .form-label {
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    /* ==================== LOADING ==================== */
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* ==================== ANIMATIONS ==================== */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* ==================== RESPONSIVE ==================== */
    @media (max-width: 768px) {
        .admin-missions-container {
            padding: 15px;
        }

        .header-content {
            flex-direction: column;
            text-align: center;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .missions-grid {
            grid-template-columns: 1fr;
        }

        .tabs-header {
            flex-direction: column;
        }

        .tab-button {
            border-bottom: 1px solid var(--border-color);
        }

        .tab-button:last-child {
            border-bottom: none;
        }

        .mission-rewards {
            flex-direction: column;
            gap: 0.5rem;
        }

        .mission-footer {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }

        .validation-header {
            flex-direction: column;
            gap: 1rem;
        }

        .validation-actions {
            align-self: stretch;
        }

        .btn-approve,
        .btn-reject {
            flex: 1;
        }

        .modal-content {
            width: 95%;
            margin: 20px;
        }

        .modal-footer {
            flex-direction: column;
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

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

<div class="admin-missions-container" id="mainContent" style="display: none;">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-title">
                <h1><i class="fas fa-trophy"></i> Administration des Missions</h1>
                <p>Gérez les missions et récompenses de votre équipe</p>
            </div>
            <button class="btn-new-mission" onclick="openNewMissionModal()">
                <i class="fas fa-plus"></i>Nouvelle Mission
            </button>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-bullseye"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats_missions_actives; ?></div>
                <div class="stat-label">Missions Actives</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats_missions_en_cours; ?></div>
                <div class="stat-label">En Cours</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats_missions_completees; ?></div>
                <div class="stat-label">Complétées ce mois</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats_validations_en_attente; ?></div>
                <div class="stat-label">Validations en attente</div>
            </div>
        </div>
    </div>

    <!-- Onglets -->
    <div class="tabs-container">
        <div class="tabs-header">
            <button class="tab-button active" data-tab="missions">
                <i class="fas fa-tasks"></i>Missions Actives
                <span class="tab-badge"><?php echo count($missions); ?></span>
            </button>
            <button class="tab-button" data-tab="validations">
                <i class="fas fa-clipboard-check"></i>Validations
                <span class="tab-badge"><?php echo count($validations); ?></span>
            </button>
            <button class="tab-button" data-tab="rewards">
                <i class="fas fa-coins"></i>Cagnotte & XP
            </button>
        </div>

        <!-- Contenu Missions -->
        <div class="tab-content active" id="missions">
            <?php if (empty($missions)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Aucune mission active</h3>
                    <p>Créez votre première mission pour motiver votre équipe</p>
                    <button class="btn-primary" onclick="openNewMissionModal()">
                        <i class="fas fa-plus"></i>Créer une mission
                    </button>
                </div>
            <?php else: ?>
                <div class="missions-grid">
                    <?php foreach ($missions as $mission): ?>
                        <div class="mission-card" onclick="showMissionDetails(<?php echo $mission['id']; ?>)">
                            <div class="mission-type-badge" style="background: <?php echo $mission['type_couleur'] ?? '#4361ee'; ?>20; color: <?php echo $mission['type_couleur'] ?? '#4361ee'; ?>">
                                <i class="<?php echo $mission['type_icone'] ?? 'fas fa-star'; ?>"></i>
                                <?php echo htmlspecialchars($mission['type_nom'] ?? 'Mission'); ?>
                            </div>
                            
                            <div class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></div>
                            <div class="mission-description"><?php echo htmlspecialchars(substr($mission['description'], 0, 100)) . '...'; ?></div>
                            
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
                                    <button class="btn-action btn-edit" onclick="event.stopPropagation(); editMission(<?php echo $mission['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="event.stopPropagation(); deactivateMission(<?php echo $mission['id']; ?>)">
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
        <div class="tab-content" id="validations">
            <?php if (empty($validations)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>Aucune validation en attente</h3>
                    <p>Toutes les validations ont été traitées</p>
                </div>
            <?php else: ?>
                <?php foreach ($validations as $validation): ?>
                    <div class="validation-card">
                        <div class="validation-header">
                            <div class="validation-info">
                                <div class="validation-title"><?php echo htmlspecialchars($validation['mission_titre']); ?></div>
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
                            </div>
                            <div class="validation-actions">
                                <button class="btn-approve" onclick="validerTacheAdmin(<?php echo $validation['id']; ?>, 'approuver')">
                                    <i class="fas fa-check"></i>Approuver
                                </button>
                                <button class="btn-reject" onclick="validerTacheAdmin(<?php echo $validation['id']; ?>, 'rejeter')">
                                    <i class="fas fa-times"></i>Rejeter
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Contenu Récompenses -->
        <div class="tab-content" id="rewards">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3><i class="fas fa-coins"></i> Cagnotte et Points XP</h3>
                <button class="btn-primary" onclick="showUserRewards()">
                    <i class="fas fa-refresh"></i>Actualiser
                </button>
            </div>
            <div id="userRewardsContainer">
                <div style="text-align: center; padding: 2rem;">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 1rem;">Chargement des données...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Mission -->
<div class="modal" id="newMissionModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-plus"></i>Nouvelle Mission
            </div>
            <button class="modal-close" onclick="closeModal('newMissionModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="newMissionForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Titre de la mission</label>
                        <input type="text" class="form-control" name="titre" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type de mission</label>
                        <select class="form-control" name="type_id" required>
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
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Objectif (quantité)</label>
                        <input type="number" class="form-control" name="objectif_quantite" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Récompense (€)</label>
                        <input type="number" class="form-control" name="recompense_euros" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Points XP</label>
                        <input type="number" class="form-control" name="recompense_points" min="0">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('newMissionModal')">Annuler</button>
            <button type="button" class="btn-primary" onclick="createMission()">
                <i class="fas fa-save"></i>Créer la Mission
            </button>
        </div>
    </div>
</div>

<!-- Modal Détails Mission -->
<div class="modal" id="missionDetailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-info-circle"></i>Détails de la Mission
            </div>
            <button class="modal-close" onclick="closeModal('missionDetailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="missionDetailsContent">
            <!-- Le contenu sera chargé via JavaScript -->
        </div>
    </div>
</div>

<script>
    // ==================== GESTION DES ONGLETS ====================
    function switchTab(tabName) {
        // Désactiver tous les onglets
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Activer l'onglet sélectionné
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById(tabName).classList.add('active');
        
        // Charger le contenu spécifique si nécessaire
        if (tabName === 'rewards') {
            showUserRewards();
        }
    }

    // Event listeners pour les onglets
    document.querySelectorAll('.tab-button').forEach(button => {
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
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    // ==================== FONCTIONS AJAX ====================
    function showMissionDetails(missionId) {
        fetch(`ajax/get_mission_details_temp.php?id=${missionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('missionDetailsContent').innerHTML = data.html;
                    openModal('missionDetailsModal');
                } else {
                    alert('Erreur lors du chargement des détails');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du chargement des détails');
            });
    }

    function showUserRewards() {
        fetch('ajax/get_user_rewards_fixed.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('userRewardsContainer').innerHTML = data.html;
                } else {
                    document.getElementById('userRewardsContainer').innerHTML = '<div style="color: var(--danger-color); text-align: center; padding: 2rem;">Erreur lors du chargement des données</div>';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('userRewardsContainer').innerHTML = '<div style="color: var(--danger-color); text-align: center; padding: 2rem;">Erreur lors du chargement des données</div>';
            });
    }

    function validerTacheAdmin(validationId, action) {
        if (!confirm(`Êtes-vous sûr de vouloir ${action} cette validation ?`)) {
            return;
        }
        
        fetch('ajax/valider_mission_fixed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                validation_id: validationId,
                action: action
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la validation');
        });
    }

    function createMission() {
        const form = document.getElementById('newMissionForm');
        const formData = new FormData(form);
        
        fetch('ajax/create_mission_fixed.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('newMissionModal');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la création de la mission');
        });
    }

    function deactivateMission(missionId) {
        if (!confirm('Êtes-vous sûr de vouloir désactiver cette mission ?')) {
            return;
        }
        
        fetch('ajax/deactivate_mission_fixed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                mission_id: missionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la désactivation');
        });
    }

    function editMission(missionId) {
        alert('Fonction d\'édition à implémenter');
    }

    // ==================== INITIALISATION ====================
    document.addEventListener('DOMContentLoaded', function() {
        // Charger les récompenses dès le démarrage
        setTimeout(showUserRewards, 500);
    });
</script>

</div> <!-- Fermeture de mainContent -->

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

body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
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

body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.admin-missions-container,
.admin-missions-container * {
  background: transparent !important;
}

.dashboard-card,
.mission-card,
.modal-content {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .dashboard-card,
.dark-mode .mission-card,
.dark-mode .modal-content {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
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
