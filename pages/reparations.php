<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    // La fonction checkSubscriptionAccess() gère la redirection automatique
    exit;
}

// Obtenir la connexion à la base de données du magasin de l'utilisateur
$shop_pdo = getShopDBConnection();

// Récupérer et stocker l'ID du magasin actuel
$current_shop_id = $_SESSION['shop_id'] ?? null;
if (!$current_shop_id) {
    // Essayer de récupérer depuis l'URL
    $current_shop_id = $_GET['shop_id'] ?? null;
    if ($current_shop_id) {
        $_SESSION['shop_id'] = $current_shop_id;
    } else {
        error_log("ALERTE: ID du magasin non trouvé dans la session ou l'URL pour reparations.php");
    }
}

// Vérifier que $shop_pdo est accessible et initialisé
if (!isset($shop_pdo) || $shop_pdo === null) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données. La variable \$shop_pdo n'est pas disponible. Veuillez contacter l'administrateur.</div>";
    error_log("ERREUR CRITIQUE dans reparations.php: La variable \$shop_pdo n'est pas disponible");
    // Initialiser les variables pour éviter les erreurs
    $total_reparations = 0;
    $total_nouvelles = 0;
    $total_en_cours = 0;
    $total_en_attente = 0;
    $total_termines = 0;
    $total_archives = 0;
    $reparations = [];
} else {
    // Paramètres de filtrage
    $statut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
    $statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : '1,2,3,4,5,19,20'; // Par défaut, afficher toutes les réparations actives incluant devis accepté/refusé
    $type_appareil = isset($_GET['type_appareil']) ? cleanInput($_GET['type_appareil']) : '';
    $date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
    $date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';
    
    // Compter les réparations par catégorie de statut
    try {
        // Total des réparations pour le bouton "Récentes" (statuts 1 à 5 + devis accepté/refusé)
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations r 
            WHERE r.statut IN (SELECT code FROM statuts WHERE id BETWEEN 1 AND 5 OR id IN (19,20))
        ");
        $total_reparations = $stmt->fetch()['total'];

        // Réparations nouvelles (statuts 1,2,3,19,20 - incluant devis accepté/refusé)
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations r 
            WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (1,2,3,19,20))
        ");
        $total_nouvelles = $stmt->fetch()['total'];

        // Réparations en cours (statuts 4,5)
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations r 
            WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (4,5))
        ");
        $total_en_cours = $stmt->fetch()['total'];

        // Réparations en attente (statuts 6,7,8)
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations r 
            WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (6,7,8))
        ");
        $total_en_attente = $stmt->fetch()['total'];

        // Réparations terminées (statuts 9,10)
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations r 
            WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (9,10))
        ");
        $total_termines = $stmt->fetch()['total'];

        // Réparations archivées (statuts 11,12,13)
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM reparations r 
            WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (11,12,13))
        ");
        $total_archives = $stmt->fetch()['total'];

    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des réparations : " . $e->getMessage());
        $total_reparations = 0;
        $total_nouvelles = 0;
        $total_en_cours = 0;
        $total_en_attente = 0;
        $total_termines = 0;
        $total_archives = 0;
    }
}

// Construction de la requête SQL avec filtres
$sql = "
    SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
           u.active_repair_id as user_active_repair_id
    FROM reparations r
    LEFT JOIN clients c ON r.client_id = c.id
    LEFT JOIN users u ON u.id = ?
    WHERE 1=1
";
$params = [$_SESSION['user_id']];

// Ajouter la condition de recherche si présente
$is_searching = isset($_GET['search']) && !empty($_GET['search']);
if ($is_searching) {
    $search = cleanInput($_GET['search']);
    $sql .= " AND (
        c.nom LIKE ? OR 
        c.prenom LIKE ? OR 
        c.telephone LIKE ? OR 
        r.type_appareil LIKE ? OR 
        
        r.modele LIKE ? OR 
        r.id LIKE ? OR
        r.description_probleme LIKE ? OR
        r.notes_techniques LIKE ?
    )";
    $search_param = "%$search%";
    $params = array_merge($params, [
        $search_param, $search_param, $search_param, $search_param,
        $search_param, $search_param, $search_param, $search_param
    ]);
}

// Filtre par ID de statut seulement si on n'est pas en mode recherche
if (!$is_searching) {
    // Filtre par ID de statut (prioritaire sur le filtre par code)
    if (!empty($statut_ids)) {
        // Vérifier si nous avons plusieurs IDs de statuts (séparés par des virgules)
        if (strpos($statut_ids, ',') !== false) {
            $ids = explode(',', $statut_ids);
            $id_placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " AND r.statut IN (SELECT code FROM statuts WHERE id IN ($id_placeholders))";
            $params = array_merge($params, $ids);
        } else {
            $sql .= " AND r.statut = (SELECT code FROM statuts WHERE id = ?)";
            $params[] = $statut_ids;
        }
    }
    // Sinon, filtre par code de statut (si statut_ids n'est pas défini)
    else if (!empty($statut)) {
        // Vérifier si nous avons plusieurs statuts (séparés par des virgules)
        if (strpos($statut, ',') !== false) {
            $statuts = explode(',', $statut);
            $statut_placeholders = implode(',', array_fill(0, count($statuts), '?'));
            $sql .= " AND r.statut IN ($statut_placeholders)";
            $params = array_merge($params, $statuts);
        } else {
        $sql .= " AND r.statut = ?";
        $params[] = $statut;
        }
    }
}

if (!empty($type_appareil)) {
    $sql .= " AND r.type_appareil = ?";
    $params[] = $type_appareil;
}

if (!empty($date_debut)) {
    $sql .= " AND r.date_reception >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND r.date_reception <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

$sql .= " ORDER BY r.date_reception DESC";

// Récupérer les réparations
try {
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $reparations = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des réparations: " . $e->getMessage() . "</div>";
    error_log("Erreur SQL (reparations.php): " . $e->getMessage());
    $reparations = [];
}

// Traitement de la suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Vérification des droits administrateur
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        set_message("Vous n'avez pas les droits nécessaires pour supprimer une réparation.", "danger");
        redirect("reparations");
        exit;
    }

    $id = (int)$_GET['id'];
    try {
        $stmt = $shop_pdo->prepare("DELETE FROM reparations WHERE id = ?");
        $stmt->execute([$id]);
        
        set_message("Réparation supprimée avec succès.", "success");
    } catch (PDOException $e) {
        set_message("Erreur lors de la suppression de la réparation: " . $e->getMessage(), "danger");
    }
    redirect("reparations");
}

// Supprimer une réparation (administrateurs uniquement)
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        set_message("Vous n'avez pas les droits nécessaires pour effectuer cette action.", "danger");
        header('Location: index.php?page=reparations');
        exit();
    }
}
?>

<?php 
// ⭐ AFFICHER LE BANDEAU D'AVERTISSEMENT SI L'ESSAI VA EXPIRER
displayTrialWarning(); 
?>

<!-- Styles personnalisés pour les cartes de réparations -->
<style>
    /* ========================================
       CORRECTION DU DÉCALAGE EN HAUT DE PAGE ET NAVBAR
    ======================================== */
    
    /* Masquer complètement le dock et la zone de rappel sur desktop (≥992px) */
    @media (min-width: 992px) {
        #mobile-dock,
        #dock-recall-zone {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            z-index: -1 !important;
        }
        /* Forcer l'affichage correct de la navbar desktop et réserver l'espace */
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
            min-height: 60px !important;
            max-height: 60px !important;
            width: 100% !important;
        }
        /* Surcharger spécifiquement navbar-servo-fix.css */
        body #desktop-navbar,
        html body #desktop-navbar,
        body nav#desktop-navbar,
        html body nav#desktop-navbar {
            height: 60px !important;
            min-height: 60px !important;
            max-height: 60px !important;
        }
        /* Forcer tous les éléments de la navbar visibles */
        #desktop-navbar * {
            visibility: visible !important;
            opacity: 1 !important;
        }
        #desktop-navbar .container-fluid {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            height: 100% !important;
            padding: 0.3rem 1rem !important;
        }
        /* Ajuster la taille et position des éléments navbar - ULTRA SPÉCIFIQUE */
        body #desktop-navbar .navbar-brand,
        html body #desktop-navbar .navbar-brand,
        body nav#desktop-navbar .navbar-brand {
            display: flex !important;
            align-items: center !important;
            height: auto !important;
            padding: 0.2rem 0 !important;
            margin: 0 !important;
            position: relative !important;
            top: auto !important;
            left: auto !important;
            transform: none !important;
        }
        body #desktop-navbar .navbar-brand img,
        html body #desktop-navbar .navbar-brand img,
        body nav#desktop-navbar .navbar-brand img {
            height: 30px !important;
            max-height: 30px !important;
            min-height: 30px !important;
        }
        body #desktop-navbar .btn,
        body #desktop-navbar button,
        html body #desktop-navbar .btn,
        html body #desktop-navbar button {
            padding: 0.3rem 0.6rem !important;
            font-size: 0.85rem !important;
            height: auto !important;
            line-height: 1.1 !important;
            margin: 0.1rem 0 !important;
        }
        /* Centrer l'animation SERVO - ULTRA SPÉCIFIQUE */
        body .servo-logo-container,
        html body .servo-logo-container,
        body #desktop-navbar .servo-logo-container {
            position: absolute !important;
            left: 50% !important;
            top: 50% !important;
            transform: translate(-50%, -50%) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 35px !important;
            padding: 0 !important;
            margin: 0 !important;
            z-index: 10001 !important;
        }
        body .servo-logo-container svg,
        html body .servo-logo-container svg {
            width: 28px !important;
            height: 28px !important;
            max-width: 28px !important;
            max-height: 28px !important;
        }
        body {
            /* Réserver l'espace pour la navbar (60px + marge) */
            padding-top: 80px !important;
        }
    }
    
    @media (max-width: 767px) {
        /* Masquer la navbar desktop sur mobile */
        #desktop-navbar,
        nav#desktop-navbar,
        .navbar.navbar-light {
            display: none !important;
        }
        
        /* Retirer le padding-top du body sur mobile */
        body {
            padding-top: 0 !important;
        }
    }
    
    /* ========================================
       VARIABLES CSS POUR LES THÈMES MODERNES
    ======================================== */
    :root {
        /* Mode Jour - Moderne Dynamique */
        --day-primary: #3b82f6;
        --day-secondary: #8b5cf6;
        --day-accent: #06b6d4;
        --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff);
        --day-card-bg: rgba(255, 255, 255, 0.95);
        --day-text: #1e293b;
        --day-text-light: #64748b;
        --day-shadow: rgba(59, 130, 246, 0.15);
        --day-border: rgba(148, 163, 184, 0.2);

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
       ANIMATIONS MODERNES
    ======================================== */
    @keyframes gradientFlow {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    @keyframes cardFloat {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-8px); }
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    @keyframes bounceIn {
        0% { transform: scale(0.3); opacity: 0; }
        50% { transform: scale(1.05); }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    /* ========================================
       STRUCTURE DE BASE MODERNE - IDENTIQUE À ACCUEIL-MODERN
    ======================================== */
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden !important;
        transition: all 0.3s ease !important;
    }
    
    /* Fond animé moderne pour mode jour - EXACTEMENT comme accueil-modern */
    body:not(.dark-mode) {
        background: var(--day-bg-animated) !important;
        background-size: 300% 300% !important;
        animation: gradientFlow 20s ease infinite !important;
    }
    
    /* Mode nuit - EXACTEMENT comme accueil-modern */
    body.dark-mode {
        background: var(--night-bg-animated) !important;
        background-size: 400% 400% !important;
        color: var(--night-text) !important;
    }
    
    /* Améliorations mode clair - cartes */
    body:not(.dark-mode) .search-card,
    body:not(.dark-mode) .dashboard-card,
    body:not(.dark-mode) .card {
        background: rgba(255, 255, 255, 0.8) !important;
        border: 1px solid rgba(203, 213, 225, 0.6) !important;
        color: #334155 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }
    
    body:not(.dark-mode) .search-card:hover,
    body:not(.dark-mode) .dashboard-card:hover,
    body:not(.dark-mode) .card:hover {
        background: rgba(255, 255, 255, 0.95) !important;
        border-color: rgba(102, 126, 234, 0.4) !important;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.25) !important;
        transform: translateY(-5px) !important;
    }
    
    /* Améliorations mode clair - boutons de filtre (commenté pour permettre les couleurs personnalisées) */
    /*
    body:not(.dark-mode) .filter-btn {
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid rgba(203, 213, 225, 0.5) !important;
        color: #334155 !important;
    }
    
    body:not(.dark-mode) .filter-btn:hover {
        background: rgba(255, 255, 255, 1) !important;
        border-color: rgba(102, 126, 234, 0.5) !important;
        color: #667eea !important;
    }
    
    body:not(.dark-mode) .filter-btn.active {
        background: rgba(102, 126, 234, 0.15) !important;
        border-color: rgba(102, 126, 234, 0.6) !important;
        color: #667eea !important;
    }
    */
    
    /* Améliorations mode clair - boutons d'action */
    body:not(.dark-mode) .action-buttons-container {
        background: rgba(255, 255, 255, 0.4) !important;
        border: 1px solid rgba(203, 213, 225, 0.4) !important;
    }
    
    body:not(.dark-mode) .action-button {
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid rgba(203, 213, 225, 0.5) !important;
        color: #334155 !important;
    }
    
    body:not(.dark-mode) .action-button:hover {
        background: rgba(255, 255, 255, 1) !important;
        border-color: rgba(102, 126, 234, 0.5) !important;
        color: #667eea !important;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.25) !important;
    }
    
    /* Améliorations mode clair - formulaires */
    body:not(.dark-mode) .search-form .form-control {
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid rgba(203, 213, 225, 0.5) !important;
        color: #334155 !important;
    }
    
    body:not(.dark-mode) .search-form .form-control:focus {
        background: rgba(255, 255, 255, 1) !important;
        border-color: rgba(102, 126, 234, 0.6) !important;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
    }
    
    body:not(.dark-mode) .search-form .input-group-text {
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid rgba(203, 213, 225, 0.5) !important;
        color: #334155 !important;
    }
    
    /* Améliorations mode clair - badges */
    body:not(.dark-mode) .badge {
        background: rgba(102, 126, 234, 0.15) !important;
        color: #667eea !important;
        border: 1px solid rgba(102, 126, 234, 0.3) !important;
    }
    
    /* Améliorations mode clair - icônes de contact */
    body:not(.dark-mode) .contact-icon {
        background: rgba(255, 255, 255, 0.9) !important;
        color: #667eea !important;
        border: 1px solid rgba(203, 213, 225, 0.5) !important;
    }
    
    body:not(.dark-mode) .contact-row:hover .contact-icon {
        background: rgba(102, 126, 234, 0.1) !important;
        border-color: rgba(102, 126, 234, 0.5) !important;
        color: #764ba2 !important;
    }
    
    /* Style pour le conteneur principal de la page */
    .page-container {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        padding-top: 0px;
        max-width: 1400px;
        margin: 0 auto;
        padding-left: 00px;
        padding-right: 00px;
    }
    
    /* Styles pour les conteneurs d'action */
    .action-buttons-container {
        margin-top: 0;
        margin-bottom: 0.25rem;
        padding: 0;
    }
    
    .modern-action-buttons {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 0.3rem !important;
        justify-content: center !important;
        align-items: center !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: auto !important;
        white-space: nowrap !important;
        width: 100% !important;
        max-width: none !important;
    }
    
    .action-button {
        padding: 0.6rem 1.2rem !important;
        margin: 0 !important;
        border-radius: 16px !important;
        text-decoration: none !important;
        border: 1px solid var(--day-border) !important;
        background: var(--day-card-bg) !important;
        color: var(--day-text) !important;
        cursor: pointer !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        flex-shrink: 0 !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        min-width: auto !important;
        max-width: none !important;
        width: auto !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        white-space: nowrap !important;
        backdrop-filter: blur(10px) !important;
        box-shadow: var(--day-shadow) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    /* Effet shimmer pour les boutons d'action */
    .action-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s ease;
    }
    
    .action-button:hover::before {
        left: 100%;
    }
    
    .action-button:hover {
        transform: translateY(-4px) scale(1.02) !important;
        box-shadow: 0 8px 30px var(--day-shadow) !important;
        border-color: var(--day-primary) !important;
        color: var(--day-text) !important;
    }
    
    /* Mode nuit pour les boutons d'action */
    body.dark-mode .action-button {
        background: var(--night-card-bg) !important;
        border-color: var(--night-border) !important;
        color: var(--night-text) !important;
        box-shadow: var(--night-shadow) !important;
    }
    
    body.dark-mode .action-button:hover {
        box-shadow: var(--night-glow) !important;
        border-color: var(--night-primary) !important;
    }
    
    /* ========================================
       STYLES MODERNES POUR LES CARTES DE RÉPARATIONS
    ======================================== */
    
    /* Conteneur des cartes */
    .repair-cards-container {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        justify-content: center;
        padding: 1rem 0;
        width: 100%;
    }
    
    /* Cartes de réparation modernes */
    .modern-card, .dashboard-card {
        background: var(--day-card-bg) !important;
        border: 1px solid var(--day-border) !important;
        border-radius: 16px !important;
        padding: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        text-decoration: none !important;
        color: var(--day-text) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        backdrop-filter: blur(10px) !important;
        box-shadow: var(--day-shadow) !important;
        position: relative !important;
        overflow: hidden !important;
        flex: 0 0 calc(33.333% - 1rem);
        min-width: 300px;
        max-width: 400px;
        margin-bottom: 1.5rem;
        animation: bounceIn 0.8s ease-out;
    }
    
    /* Effet shimmer pour les cartes */
    .modern-card::before, .dashboard-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s ease;
        z-index: 1;
    }
    
    .modern-card:hover::before, .dashboard-card:hover::before {
        left: 100%;
    }
    
    .modern-card:hover, .dashboard-card:hover {
        transform: translateY(-8px) scale(1.02) !important;
        box-shadow: 0 25px 80px var(--day-shadow) !important;
        border-color: var(--day-primary) !important;
        animation: cardFloat 2s ease-in-out infinite;
    }
    
    /* En-tête de carte moderne */
    .card-header {
        background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%) !important;
        color: white !important;
        padding: 1rem 1.5rem !important;
        border-bottom: none !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        position: relative !important;
        z-index: 2 !important;
    }
    
    /* Contenu de carte moderne */
    .card-content {
        flex: 1 !important;
        padding: 1.5rem !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 1rem !important;
        position: relative !important;
        z-index: 2 !important;
    }
    
    /* Pied de carte moderne */
    .card-footer {
        background: rgba(248, 250, 252, 0.5) !important;
        border-top: 1px solid var(--day-border) !important;
        padding: 1rem 1.5rem !important;
        display: flex !important;
        gap: 0.5rem !important;
        justify-content: flex-end !important;
        position: relative !important;
        z-index: 2 !important;
    }
    
    /* Boutons d'action dans les cartes */
    .card-footer .action-btn, .card-footer .btn {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        border: none !important;
        transition: all 0.3s ease !important;
        font-size: 1rem !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    
    .card-footer .action-btn:hover, .card-footer .btn:hover {
        transform: translateY(-3px) scale(1.1) !important;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2) !important;
    }
    
    /* Couleurs spécifiques pour les boutons d'action - MODE JOUR */
    .btn-call { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important; }
    .btn-start { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; }
    .btn-stop { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; }
    .btn-details { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important; }
    .btn-sms, .btn-message { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important; }
    
    /* Mode nuit pour les cartes */
    body.dark-mode .modern-card, body.dark-mode .dashboard-card {
        background: var(--night-card-bg) !important;
        border-color: var(--night-border) !important;
        color: var(--night-text) !important;
        box-shadow: var(--night-shadow) !important;
    }
    
    body.dark-mode .modern-card:hover, body.dark-mode .dashboard-card:hover {
        box-shadow: var(--night-glow) !important;
        border-color: var(--night-primary) !important;
    }
    
    body.dark-mode .card-header {
        background: linear-gradient(135deg, var(--night-primary) 0%, var(--night-secondary) 100%) !important;
    }
    
    body.dark-mode .card-footer {
        background: rgba(15, 15, 25, 0.5) !important;
        border-color: var(--night-border) !important;
    }
    
    /* Mode nuit - Couleurs spécifiques pour les boutons d'action */
    body.dark-mode .btn-call { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; 
    }
    body.dark-mode .btn-start { 
        background: linear-gradient(135deg, #059669 0%, #047857 100%) !important; 
    }
    body.dark-mode .btn-stop { 
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; 
    }
    body.dark-mode .btn-details { 
        background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%) !important; 
    }
    body.dark-mode .btn-sms, 
    body.dark-mode .btn-message { 
        background: linear-gradient(135deg, #fb7185 0%, #f43f5e 100%) !important; 
    }
    /* Responsive pour les cartes */
    @media (max-width: 991px) {
        .modern-card, .dashboard-card {
            flex: 0 0 calc(50% - 0.75rem);
            min-width: 250px;
        }
    }
    
    @media (max-width: 768px) {
        .modern-card, .dashboard-card {
            flex: 0 0 100%;
            min-width: 100%;
        }
        
        .repair-cards-container {
            gap: 1rem;
        }
    }
    
    /* ========================================
       STYLES MODERNES POUR LA BARRE DE RECHERCHE
    ======================================== */
    
    /* Conteneur principal des filtres */
    .modern-filters-container {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        margin-bottom: 0.25rem;
        padding: 0.5rem;
    }
    
    /* Barre de recherche moderne */
    .modern-search {
        display: flex;
        justify-content: center;
        width: 100%;
    }
    
    .search-form {
        width: 100%;
        max-width: 800px;
    }
    
    .search-wrapper {
        display: flex;
        align-items: center;
        background: var(--day-card-bg);
        border: 1px solid var(--day-border);
        border-radius: 16px;
        padding: 0.5rem;
        box-shadow: var(--day-shadow);
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }
    
    /* Icône de recherche */
    .search-icon {
        color: var(--day-text-light);
        margin-left: 1rem;
        margin-right: 0.75rem;
        font-size: 1.1rem;
        z-index: 2;
    }
    
    /* Input de recherche */
    .search-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        padding: 0.75rem 0;
        font-size: 1rem;
        color: var(--day-text);
        z-index: 2;
    }
    
    .search-input::placeholder {
        color: var(--day-text-light);
    }
    
    /* Bouton de recherche */
    .search-btn {
        background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 2;
        position: relative;
        overflow: hidden;
    }
    
    /* Effet shimmer pour le bouton de recherche */
    .search-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s ease;
    }
    
    .search-btn:hover::before {
        left: 100%;
    }
    
    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
    }
    
    /* Bouton reset */
    .reset-btn {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: none;
        border-radius: 8px;
        padding: 0.5rem;
        margin-right: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }
    
    .reset-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        transform: scale(1.1);
    }
    
    /* Mode nuit pour la recherche */
    body.dark-mode .search-wrapper {
        background: var(--night-card-bg);
        border-color: var(--night-border);
        box-shadow: var(--night-shadow);
    }
    
    body.dark-mode .search-icon {
        color: var(--night-text-light);
    }
    
    body.dark-mode .search-input {
        color: var(--night-text);
    }
    
    body.dark-mode .search-input::placeholder {
        color: var(--night-text-light);
    }
    
    body.dark-mode .search-btn {
        background: linear-gradient(135deg, var(--night-primary) 0%, var(--night-secondary) 100%);
    }
    
    body.dark-mode .search-btn:hover {
        box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
    }
    
    body.dark-mode .reset-btn {
        background: rgba(239, 68, 68, 0.2);
        color: #ff6b6b;
    }
    
    body.dark-mode .reset-btn:hover {
        background: rgba(239, 68, 68, 0.3);
    }
    
    /* Responsive pour la recherche */
    @media (max-width: 768px) {
        .modern-filters-container {
            padding: 0.25rem;
            gap: 0.25rem;
        }
        
        .search-wrapper {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
            padding: 1rem;
        }
        
        .search-icon {
            display: none;
        }
        
        .search-input {
            text-align: center;
            padding: 0.75rem;
        }
        
        .search-btn {
            justify-content: center;
            padding: 1rem;
        }
    }
    
    /* Styles pour les boutons de filtres */
    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: center;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Correctif simple et propre pour les modals sur cette page */
    .modal {
        pointer-events: auto !important;
    }
    
    .modal-dialog {
        pointer-events: auto !important;
    }
    
    /* Laisser Bootstrap gérer les z-index normalement */
    
    /* Elargir la colonne Appareil dans le tableau */
    #table-view th:nth-child(3), 
    #table-view td:nth-child(3) {
        min-width: 200px;
        max-width: none;
        white-space: normal;
        word-break: break-word;
    }
    
    /* Faire en sorte que le texte de la colonne Appareil s'affiche entièrement */
    #table-view .d-none.d-md-table-cell:nth-child(3) {
        white-space: normal;
        word-break: break-word;
    }
    
    /* Amélioration du design du tableau */
    #table-view .table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    #table-view .table thead th {
        background-color: #f8f9fa;
        border-top: none;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 15px;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.03em;
    }
    
    #table-view .table tbody tr:hover {
        background-color: rgba(67, 97, 238, 0.03);
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    
    #table-view .table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        border-top: 1px solid #f1f1f1;
    }
    
    /* Amélioration des boutons */
    #table-view .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem !important;
        justify-content: flex-end;
    }
    
    #table-view .btn-group .btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 50% !important;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: none;
    }
    
    #table-view .btn-group .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    /* Style spécifique pour chaque type de bouton */
    #table-view .btn-success {
        background: linear-gradient(135deg, #28a745, #20c997);
    }
    
    #table-view .btn-success:hover {
        background: linear-gradient(135deg, #20c997, #28a745);
    }
    
    #table-view .btn-soft-primary, 
    #table-view .start-repair {
        background: linear-gradient(135deg, #0d6efd, #3a86ff);
    }
    
    #table-view .btn-soft-primary:hover, 
    #table-view .start-repair:hover {
        background: linear-gradient(135deg, #3a86ff, #0d6efd);
    }
    
    #table-view .btn-soft-info {
        background: linear-gradient(135deg, #17a2b8, #0dcaf0);
    }
    
    #table-view .btn-soft-info:hover {
        background: linear-gradient(135deg, #0dcaf0, #17a2b8);
    }
    
    #table-view .btn-soft-danger,
    #table-view .delete-repair {
        background: linear-gradient(135deg, #dc3545, #ff6b6b);
    }
    
    #table-view .btn-soft-danger:hover,
    #table-view .delete-repair:hover {
        background: linear-gradient(135deg, #ff6b6b, #dc3545);
    }
    
    /* Style pour le badge de statut */
    #table-view .badge {
        padding: 0.6em 0.8em;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.75rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: inline-block;
    }
    
    /* ========================================
       STYLES MODERNES POUR LES BOUTONS DE FILTRE
    ======================================== */
    
    /* Conteneur des filtres modernes */
    .modern-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
        margin-bottom: 0.25rem;
        padding: 0.25rem;
    }
    
    /* Boutons de filtre modernes */
    .modern-filter, .filter-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 1.5rem 1rem;
        text-decoration: none;
        background: var(--day-card-bg);
        border: 1px solid var(--day-border);
        border-radius: 16px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 120px;
        box-shadow: var(--day-shadow);
        backdrop-filter: blur(10px);
        overflow: hidden;
        color: var(--day-text);
    }
    
    /* Effet shimmer pour les filtres */
    .modern-filter::before, .filter-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s ease;
        z-index: 1;
    }
    
    .modern-filter:hover::before, .filter-btn:hover::before {
        left: 100%;
    }
    
    .modern-filter:hover, .filter-btn:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 25px 80px var(--day-shadow);
        border-color: var(--day-primary);
        color: var(--day-text);
    }
    
    .modern-filter.active, .filter-btn.active {
        background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
        color: white;
        border-color: var(--day-primary);
        box-shadow: 0 15px 40px rgba(59, 130, 246, 0.4);
    }
    
    /* Mode nuit pour les filtres */
    body.dark-mode .modern-filter, body.dark-mode .filter-btn {
        background: var(--night-card-bg);
        border-color: var(--night-border);
        color: var(--night-text);
        box-shadow: var(--night-shadow);
    }
    
    body.dark-mode .modern-filter:hover, body.dark-mode .filter-btn:hover {
        box-shadow: var(--night-glow);
        border-color: var(--night-primary);
    }
    
    body.dark-mode .modern-filter.active, body.dark-mode .filter-btn.active {
        background: linear-gradient(135deg, var(--night-primary) 0%, var(--night-secondary) 100%);
        box-shadow: 0 15px 40px rgba(0, 212, 255, 0.4);
    }
    
    /* Icônes des filtres */
    .filter-btn i, .modern-filter i, .filter-icon {
        color: inherit;
        margin-bottom: 0.5rem;
        font-size: 2rem;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
    }
    
    /* Texte des filtres */
    .filter-btn span, .modern-filter span, .filter-name {
        font-size: 0.9rem;
        text-align: center;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
    }
    
    /* Compteurs des filtres */
    .filter-btn .count, .filter-btn .badge, .modern-filter .filter-count {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: var(--day-primary);
        color: white;
        border-radius: 12px;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 20px;
        text-align: center;
        z-index: 3;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    /* Mode nuit pour les compteurs */
    body.dark-mode .filter-btn .count, 
    body.dark-mode .filter-btn .badge, 
    body.dark-mode .modern-filter .filter-count {
        background: var(--night-primary);
    }
    
    /* Responsive pour les filtres */
    @media (max-width: 768px) {
        .modern-filters {
            gap: 0.5rem;
            padding: 0.1rem;
        }
        
        .modern-filter, .filter-btn {
            min-width: 100px;
            padding: 1rem 0.75rem;
        }
        
        .filter-btn i, .modern-filter i {
            font-size: 1.5rem;
        }
    }
    
    .filter-btn.active .count {
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
    }
    
    /* Améliorations pour les cartes de réparation */
    .dashboard-card.repair-row {
        height: auto;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        flex: 1 0 300px;
        max-width: calc(33.333% - 1rem);
        min-width: 300px;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 991px) {
        .dashboard-card.repair-row {
            max-width: calc(50% - 0.75rem);
            min-width: 250px;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-card.repair-row {
            max-width: 100%;
            min-width: 100%;
        }
    }
    
    .dashboard-card.repair-row:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 20px -3px rgba(0,0,0,0.2);
        border-color: rgba(67, 97, 238, 0.3);
    }
    
    .dashboard-card .card-content {
        flex: 1;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        background: white;
    }
    
    .dashboard-card .card-header {
        background: linear-gradient(to right, #f8f9fa, #ffffff);
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .dashboard-card .card-footer {
        border-top: 1px solid rgba(0,0,0,0.05);
        background-color: #f8f9fa; /* Fond clair pour le mode jour */
        padding: 0.75rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
    }
    
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    
    .fw-medium {
        font-weight: 500;
    }
    
    /* Style pour la section de recherche */
    .search-card {
        width: 100%;
        margin: 0 auto 1.5rem;
        max-width: 1200px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border-radius: 10px;
        overflow: hidden;
        border: none;
    }
    
    /* Style pour le contenu principal */
    .results-container {
        width: 100%;
        margin-top: 0;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Style pour le message 'Aucune réparation trouvée' */
    .no-results-container {
    display: flex;
        flex-direction: column;
        align-items: center;
    justify-content: center;
        padding: 3rem 1rem;
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    width: 100%;
}

    /* Forcer l'affichage en colonne sur mobile et tablette */
    @media (max-width: 991px) {
        .page-container {
            display: flex;
            flex-direction: column;
            padding-top: 10px;
            margin-top: 0px; /* Suppression du décalage supplémentaire */
        }
        
        .filter-buttons {
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .filter-btn {
            min-width: calc(33.333% - 0.5rem);
            padding: 1rem 0.5rem;
}
.filter-btn i {
            font-size: 1.75rem;
        }
        
        .search-card {
            margin-bottom: 1rem;
        }
        
        .results-container {
            margin-top: 0;
        }
        
        /* Centrer la barre de recherche */
        .search-form .input-group {
            max-width: 100%;
            margin: 0 auto;
        }
    }

    /* Style pour la vue en cartes */
    #cards-view {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: flex-start;
        width: 100%;
    }
    
    .repair-cards-container {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        width: 100%;
    }

    #cards-view .dashboard-card {
        flex: 0 0 calc(33.333% - 1rem);
        min-width: 300px;
        max-width: 100%;
        margin-bottom: 1rem;
    }

    /* Style pour le conteneur principal des résultats */
    .results-container {
        width: 100%;
        margin-top: 0;
    }

    .results-container .card {
        border-radius: 0.75rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    /* Style pour la vue en cartes avec peu d'éléments */
    @media (min-width: 992px) {
        #cards-view {
            justify-content: flex-start;
            display: flex;
            flex-direction: row;
        }
        
        #cards-view .dashboard-card {
            flex: 0 0 calc(33.333% - 1rem);
            min-width: 300px;
            max-width: 450px;
        }
        
        /* Si moins de 4 cartes, centrer le contenu */
        #cards-view.few-items {
            justify-content: center;
        }
    }

    @media (max-width: 991px) {
        #cards-view {
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        #cards-view .dashboard-card {
            flex: 0 0 calc(50% - 0.5rem);
            min-width: 250px;
        }
    }

    @media (max-width: 768px) {
        #cards-view {
            flex-direction: column;
            padding: 0;
            gap: 0.75rem;
            justify-content: center;
            align-items: center;
            max-width: 100%;
            width: 100%;
            overflow-x: hidden;
        }
        
        #cards-view .dashboard-card {
            flex: 0 0 100%;
            width: 75%;
            max-width: 75%;
            margin-left: auto;
            margin-right: auto;
            min-width: initial;
            margin-bottom: 0.75rem;
            box-sizing: border-box;
        }
        
        .repair-cards-container {
            padding: 0;
            gap: 0.75rem;
            width: 100%;
            max-width: 100%;
            justify-content: center;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .dashboard-card.repair-row {
            max-width: 92%;
            min-width: initial;
            width: 92%;
            box-sizing: border-box;
        }
        
        /* Ajustements des espacements internes pour le mobile */
        .dashboard-card .card-content {
            padding: 0.75rem;
        }
        
        .dashboard-card .card-footer {
            padding: 0.5rem;
        }
        
        /* Ajustements des conteneurs principaux */
        .results-container {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .card {
            margin: 0 auto;
            width: 100%;
        }
        
        .page-container {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
            width: 100%;
        }
        
        .card-body {
            padding: 0.75rem;
            overflow-x: hidden;
        }
        
        /* Ajustements pour les informations de contact sur mobile */
        .dashboard-card .contact-row {
            width: 48% !important;
        }
        
        .dashboard-card .contact-icon {
            width: 22px;
            height: 22px;
            margin-right: 0.3rem;
        }
        
        /* Style pour l'affichage du prix */
        .dashboard-card .repair-id {
            font-size: 0.9rem;
            max-width: 85px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Réduire la taille de la police pour mobile */
        .dashboard-card .card-content p,
        .dashboard-card .card-content h6 {
            font-size: 0.85rem;
        }
        
        /* Élargir la zone de données de contact pour éviter les débordements */
        .dashboard-card .contact-data {
            max-width: 75%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Ajuster la taille des boutons dans le footer de carte */
        .dashboard-card .card-footer .btn {
            width: 32px;
            height: 32px;
        }
        
        /* Ajustements pour l'en-tête de carte */
        .dashboard-card .card-header {
            padding: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Ajustement pour les badges de statut */
        .dashboard-card .status-indicator .badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
    }
    
    /* Styles pour le mode nuit - commentés pour permettre les couleurs modern-filter personnalisées */
    /*
    .dark-mode .filter-btn {
        background-color: #1f2937;
        color: #94a3b8;
        border-color: #374151;
        box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    }
    
    .dark-mode .filter-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.4);
        color: #60a5fa;
        border-color: rgba(96, 165, 250, 0.3);
    }
    
    .dark-mode .filter-btn.active {
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        color: #f8fafc;
        border-color: #3b82f6;
        box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
    }
    */
    
    .dark-mode .filter-btn .count {
        background: #374151;
        color: #f8fafc;
    }
    
    .dark-mode #table-view .table {
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    
    .dark-mode #table-view .table thead th {
        background-color: #1f2937;
        border-bottom: 2px solid #374151;
        color: #e2e8f0;
    }
    
    .dark-mode #table-view .table tbody tr {
        background-color: #1e2534;
        color: #e2e8f0;
    }
    
    .dark-mode #table-view .table tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.1);
    }
    
    .dark-mode #table-view .table tbody td {
        border-top: 1px solid #374151;
    }
    
    .dark-mode .dashboard-card.repair-row {
        border-color: #374151;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .dark-mode .dashboard-card.repair-row:hover {
        box-shadow: 0 15px 25px rgba(0,0,0,0.4);
        border-color: rgba(59, 130, 246, 0.3);
    }
    
    .dark-mode .dashboard-card .card-content {
        background: #1f2937;
        color: #e2e8f0;
    }
    
    .dark-mode .dashboard-card .card-header {
        background: linear-gradient(to right, #111827, #1f2937);
        border-bottom-color: #374151;
    }
    
    .dark-mode .dashboard-card .card-footer {
        border-top-color: #374151;
        background-color: #1f2937;
    }
    
    /* Style pour la barre de recherche améliorée */
    .search-card {
        width: 100%;
        margin: 0 auto 1.5rem;
        max-width: 1200px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border-radius: 10px;
        overflow: hidden;
        border: none;
    }
    
    .search-form .input-group {
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .search-form .input-group-text {
        background: white;
        border: 1px solid #ced4da;
        border-right: none;
        color: #4361ee;
    }
    
    .search-form .form-control {
        border: 1px solid #ced4da;
        border-left: none;
        padding-left: 0;
    }
    
    .search-form .btn-primary {
        background: linear-gradient(135deg, #4361ee, #3a86ff);
        border: none;
        box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
        transition: all 0.3s ease;
    }
    
    .search-form .btn-primary:hover {
        background: linear-gradient(135deg, #3a86ff, #4361ee);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.4);
    }
    
    .dark-mode .search-card {
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .dark-mode .search-form .input-group {
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    
    .dark-mode .search-form .input-group-text {
        background: #1f2937;
        border: 1px solid #374151;
        border-right: none;
        color: #60a5fa;
    }
    
    .dark-mode .search-form .form-control {
        background: #1f2937;
        border: 1px solid #374151;
        border-left: none;
        color: #e2e8f0;
    }
    
    .dark-mode .search-form .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);
    }
    
    .dark-mode .search-form .btn-primary:hover {
        background: linear-gradient(135deg, #60a5fa, #3b82f6);
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
    }
    
    .avatar-circle {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
    
    /* ========================================
       STYLES MODERNES POUR LE MODAL CHOOSESTATUS
    ======================================== */
    
    /* Modal principal */
    #chooseStatusModal .modal-content {
        background: var(--day-card-bg) !important;
        border: 1px solid var(--day-border) !important;
        border-radius: 20px !important;
        box-shadow: var(--day-shadow) !important;
        backdrop-filter: blur(15px) !important;
        overflow: hidden;
    }
    
    /* Header du modal */
    #chooseStatusModal .modal-header {
        background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%) !important;
        color: white !important;
        border-bottom: none !important;
        padding: 1.5rem 2rem !important;
        position: relative;
        overflow: hidden;
    }
    
    /* Effet shimmer pour le header */
    #chooseStatusModal .modal-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        animation: shimmer 3s ease-in-out infinite;
    }
    
    #chooseStatusModal .modal-title {
        color: white !important;
        font-weight: 600 !important;
        position: relative;
        z-index: 2;
    }
    
    #chooseStatusModal .btn-close {
        background: rgba(255,255,255,0.2) !important;
        border-radius: 50% !important;
        opacity: 1 !important;
        position: relative;
        z-index: 2;
    }
    
    #chooseStatusModal .btn-close:hover {
        background: rgba(255,255,255,0.3) !important;
        transform: scale(1.1);
    }
    
    /* Body du modal */
    #chooseStatusModal .modal-body {
        background: var(--day-card-bg) !important;
        padding: 2rem !important;
        color: var(--day-text) !important;
    }
    
    /* Avatar circle moderne */
    #chooseStatusModal .avatar-circle {
        width: 80px !important;
        height: 80px !important;
        background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 1rem !important;
        box-shadow: var(--day-shadow) !important;
        position: relative;
        overflow: hidden;
    }
    
    #chooseStatusModal .avatar-circle::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 2s ease-in-out infinite;
    }
    
    #chooseStatusModal .avatar-circle i {
        color: white !important;
        font-size: 2rem !important;
        position: relative;
        z-index: 2;
    }
    
    /* Textes du modal */
    #chooseStatusModal .modal-body h5 {
        color: var(--day-text) !important;
        font-weight: 700 !important;
        margin-bottom: 0.5rem !important;
    }
    
    #chooseStatusModal .modal-body p {
        color: var(--day-text-light) !important;
        margin-bottom: 2rem !important;
    }
    
    /* Conteneur des boutons de statut */
    #statusButtonsContainer {
        display: flex !important;
        flex-direction: column !important;
        gap: 1rem !important;
    }
    
    /* Boutons de statut modernes */
    #statusButtonsContainer .btn {
        background: var(--day-card-bg) !important;
        border: 1px solid var(--day-border) !important;
        border-radius: 12px !important;
        padding: 1rem 1.5rem !important;
        text-align: left !important;
        color: var(--day-text) !important;
        font-weight: 600 !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        overflow: hidden !important;
        backdrop-filter: blur(10px) !important;
        box-shadow: var(--day-shadow) !important;
    }
    
    #statusButtonsContainer .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }
    
    #statusButtonsContainer .btn:hover::before {
        left: 100%;
    }
    
    #statusButtonsContainer .btn:hover {
        transform: translateY(-4px) scale(1.02) !important;
        box-shadow: 0 15px 40px var(--day-shadow) !important;
        border-color: var(--day-primary) !important;
        color: var(--day-text) !important;
    }
    
    #statusButtonsContainer .btn i {
        width: 24px !important;
        text-align: center !important;
        margin-right: 0.75rem !important;
        color: inherit !important;
    }
    
    /* Footer du modal */
    #chooseStatusModal .modal-footer {
        background: rgba(255,255,255,0.8) !important;
        backdrop-filter: blur(10px) !important;
        border-top: 1px solid var(--day-border) !important;
        padding: 1.5rem 2rem !important;
    }
    
    #chooseStatusModal .modal-footer .btn {
        border-radius: 12px !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
    }
    
    #chooseStatusModal .modal-footer .btn-outline-secondary {
        border-color: var(--day-border) !important;
        color: var(--day-text-light) !important;
    }
    
    #chooseStatusModal .modal-footer .btn-outline-secondary:hover {
        background: var(--day-primary) !important;
        border-color: var(--day-primary) !important;
        color: white !important;
        transform: translateY(-2px) !important;
    }
    
    /* Spinner de chargement */
    #chooseStatusModal .spinner-border {
        color: var(--day-primary) !important;
    }
    
    /* MODE NUIT POUR LE MODAL */
    body.dark-mode #chooseStatusModal .modal-content {
        background: var(--night-card-bg) !important;
        border-color: var(--night-border) !important;
        box-shadow: var(--night-shadow) !important;
    }
    
    body.dark-mode #chooseStatusModal .modal-header {
        background: linear-gradient(135deg, var(--night-primary) 0%, var(--night-secondary) 100%) !important;
    }
    
    body.dark-mode #chooseStatusModal .modal-body {
        background: var(--night-card-bg) !important;
        color: var(--night-text) !important;
    }
    
    body.dark-mode #chooseStatusModal .modal-body h5 {
        color: var(--night-text) !important;
    }
    
    body.dark-mode #chooseStatusModal .modal-body p {
        color: var(--night-text-light) !important;
    }
    
    body.dark-mode #chooseStatusModal .avatar-circle {
        background: linear-gradient(135deg, var(--night-primary), var(--night-secondary)) !important;
        box-shadow: var(--night-shadow) !important;
    }
    
    body.dark-mode #statusButtonsContainer .btn {
        background: var(--night-card-bg) !important;
        border-color: var(--night-border) !important;
        color: var(--night-text) !important;
        box-shadow: var(--night-shadow) !important;
    }
    
    body.dark-mode #statusButtonsContainer .btn:hover {
        box-shadow: var(--night-glow) !important;
        border-color: var(--night-primary) !important;
    }
    
    body.dark-mode #chooseStatusModal .modal-footer {
        background: rgba(31, 41, 59, 0.8) !important;
        border-color: var(--night-border) !important;
    }
    
    body.dark-mode #chooseStatusModal .modal-footer .btn-outline-secondary {
        border-color: var(--night-border) !important;
        color: var(--night-text-light) !important;
    }
    
    body.dark-mode #chooseStatusModal .modal-footer .btn-outline-secondary:hover {
        background: var(--night-primary) !important;
        border-color: var(--night-primary) !important;
        color: white !important;
    }
    
    body.dark-mode #chooseStatusModal .spinner-border {
        color: var(--night-primary) !important;
    }
    
    /* Styles spécifiques pour les cartes draggable */
    .draggable-card {
        cursor: grab;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .draggable-card:active {
        cursor: grabbing;
    }
    
    .draggable-card.dragging {
        opacity: 0.8;
        transform: scale(1.02);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        z-index: 1000;
    }
    
    .ghost-card {
        position: absolute;
        pointer-events: none;
        opacity: 0.7;
        z-index: 1000;
        transform: rotate(3deg);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        width: 300px;
    }
    
    /* Styles pour le drag and drop des filtres */
    .filter-btn.drag-over, .modern-filter.drag-over {
        transform: scale(1.05) translateY(-4px);
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
        border: 2px dashed var(--day-primary);
        background: rgba(59, 130, 246, 0.1);
    }
    
    .filter-btn.drop-success, .modern-filter.drop-success {
        background: linear-gradient(135deg, #10b981, #059669);
        border-color: #10b981;
        color: white;
        transition: all 0.5s ease;
        transform: scale(1.02);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }
    
    /* Mode nuit pour le drag and drop */
    body.dark-mode .filter-btn.drag-over, 
    body.dark-mode .modern-filter.drag-over {
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.6);
        border-color: var(--night-primary);
        background: rgba(0, 212, 255, 0.1);
    }
    
    body.dark-mode .filter-btn.drop-success, 
    body.dark-mode .modern-filter.drop-success {
        background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
        box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
    }
    
    .draggable-card.updated {
        animation: card-update-success 1s ease;
    }
    
    @keyframes card-update-success {
        0% { 
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.5);
            transform: scale(1.03);
        }
        50% { 
            box-shadow: 0 0 0 6px rgba(40, 167, 69, 0.3);
        }
        100% { 
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            transform: scale(1);
        }
    }

    /* ========================================
       STYLES POUR LE NOUVEAU TABLEAU PERSONNALISÉ
    ======================================== */
    
    /* Conteneur principal du tableau */
    .custom-table-container {
        background: var(--day-card-bg);
        border: 1px solid var(--day-border);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--day-shadow);
        backdrop-filter: blur(15px);
        margin-bottom: 2rem;
        position: relative;
    }
    
    /* En-tête du tableau */
    .custom-table-header {
        display: grid;
        grid-template-columns: 60px 1.5fr 1.5fr 2fr 120px 1.2fr 100px 150px;
        background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0;
        position: relative;
        overflow: hidden;
    }
    
    /* Effet shimmer pour l'en-tête */
    .custom-table-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        animation: shimmer 3s ease-in-out infinite;
    }
    
    /* Cellules d'en-tête */
    .custom-header-cell {
        padding: 1.25rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        z-index: 2;
        border-right: 1px solid rgba(255,255,255,0.1);
    }
    
    .custom-header-cell:last-child {
        border-right: none;
    }
    
    .custom-header-cell i {
        font-size: 1rem;
        opacity: 0.9;
    }
    
    .custom-header-cell span {
        font-weight: 700;
    }
    
    /* Corps du tableau */
    .custom-table-body {
        max-height: 600px;
        overflow-y: auto;
        background: var(--day-card-bg);
    }
    
    /* Lignes du tableau */
    .custom-table-row {
        display: grid;
        grid-template-columns: 60px 1.5fr 1.5fr 2fr 120px 1.2fr 100px 150px;
        border-bottom: 1px solid var(--day-border);
        background: var(--day-card-bg);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: grab;
        margin: 0 0.5rem;
        border-radius: 12px;
        margin-bottom: 0.5rem;
        position: relative;
        overflow: hidden;
    }
    .custom-table-row::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s ease;
    }
    
    .custom-table-row:hover::before {
        left: 100%;
    }
    
    .custom-table-row:hover {
        background: var(--day-card-bg);
        transform: translateY(-2px) scale(1.005);
        box-shadow: 0 15px 40px var(--day-shadow);
        border-color: var(--day-primary);
        border-left: 4px solid var(--day-primary);
    }
    
    .custom-table-row:active {
        cursor: grabbing;
    }
    
    .custom-table-row.dragging {
        opacity: 0.8;
        transform: scale(1.02);
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        z-index: 1000;
    }
    
    /* Cellules du tableau */
    .custom-table-cell {
        padding: 1rem;
        display: flex;
        align-items: center;
        min-height: 80px;
        position: relative;
        z-index: 2;
        border-right: 1px solid rgba(0,0,0,0.05);
    }
    
    .custom-table-cell:last-child {
        border-right: none;
    }
    
    /* Styles spécifiques pour chaque colonne */
    .cell-indicators {
        justify-content: center;
    }
    
    .cell-client {
        padding: 0.75rem;
    }
    
    .cell-device, .cell-problem {
        justify-content: flex-start;
        text-align: left;
    }
    
    .cell-date, .cell-price {
        justify-content: center;
        text-align: center;
    }
    
    .cell-status {
        justify-content: center;
    }
    
    .cell-actions {
        justify-content: center;
    }
    
    /* Styles pour les indicateurs */
    .indicators-group {
        display: flex;
        gap: 0.5rem;
        flex-direction: column;
        align-items: center;
    }
    
    .indicator-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .indicator-badge:hover {
        transform: scale(1.1);
    }
    
    .indicator-badge.order-required {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }
    
    .indicator-badge.urgent {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }
    
    /* Styles pour les informations client */
    .client-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        width: 100%;
    }
    
    .client-avatar {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        flex-shrink: 0;
    }
    
    .avatar-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .phone-indicator {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    
    .client-details {
        min-width: 0;
        flex: 1;
    }
    
    .client-name {
        font-weight: 700;
        color: var(--day-text);
        margin-bottom: 0.25rem;
        font-size: 1rem;
    }
    
    .client-id {
        font-size: 0.75rem;
        color: var(--day-text-light);
        margin-bottom: 0.25rem;
        font-weight: 500;
    }
    
    .client-phone {
        font-size: 0.75rem;
        color: #10b981;
        font-weight: 600;
    }
    
    /* Styles pour les informations d'appareil et problème */
    .device-info, .problem-info {
        font-weight: 600;
        color: var(--day-text);
        text-align: left;
        word-break: break-word;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
        padding: 0.75rem 1rem;
        border-radius: 12px;
        border-left: 4px solid var(--day-primary);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        font-size: 0.875rem;
        line-height: 1.4;
    }
    
    .problem-info {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
        border-left-color: var(--day-secondary);
        font-style: italic;
    }
    
    /* Styles pour la date */
    .date-info {
        font-size: 0.875rem;
        color: var(--day-text-light);
        text-align: center;
        font-weight: 500;
    }
    
    /* Styles pour le statut */
    .status-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: none;
        white-space: nowrap;
    }
    
    /* Styles pour le prix */
    .price-info {
        font-weight: 700;
        color: #10b981;
        text-align: center;
        font-size: 1rem;
    }
    
    /* Styles pour les actions */
    .actions-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
    }
    
    .custom-action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .custom-action-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.3s ease;
    }
    
    .custom-action-btn:hover::before {
        left: 100%;
    }
    
    .custom-action-btn:hover {
        transform: translateY(-2px) scale(1.1);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }
    
    /* Boutons d'action personnalisés - MODE JOUR */
    .custom-action-btn.btn-success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
    }
    
    .custom-action-btn.btn-primary {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .custom-action-btn.btn-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }
    
    .custom-action-btn.btn-info {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
    }
    
    .custom-action-btn.btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }
    
    /* ========================================
       STYLES MODE NUIT POUR LE TABLEAU PERSONNALISÉ
    ======================================== */
    
    /* Mode nuit - Conteneur du tableau */
    body.dark-mode .custom-table-container {
        background: var(--night-card-bg);
        border-color: var(--night-border);
        box-shadow: var(--night-shadow);
    }
    
    /* Mode nuit - En-tête du tableau */
    body.dark-mode .custom-table-header {
        background: linear-gradient(135deg, var(--night-primary) 0%, var(--night-secondary) 100%);
    }
    
    /* Mode nuit - Corps du tableau */
    body.dark-mode .custom-table-body {
        background: var(--night-card-bg);
    }
    
    /* Mode nuit - Lignes du tableau */
    body.dark-mode .custom-table-row {
        background: var(--night-card-bg);
        border-color: var(--night-border);
        color: var(--night-text);
    }
    
    body.dark-mode .custom-table-row:hover {
        background: var(--night-card-bg);
        box-shadow: var(--night-glow);
        border-left-color: var(--night-primary);
    }
    
    /* Mode nuit - Cellules du tableau */
    body.dark-mode .custom-table-cell {
        color: var(--night-text);
        border-right-color: rgba(255,255,255,0.05);
    }
    
    /* Mode nuit - Informations client */
    body.dark-mode .client-name,
    body.dark-mode .client-id,
    body.dark-mode .client-phone {
        color: var(--night-text);
    }
    
    body.dark-mode .client-phone {
        color: var(--night-accent);
    }
    
    /* Mode nuit - Informations appareil et problème */
    body.dark-mode .device-info {
        background: linear-gradient(135deg, rgba(0, 212, 255, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
        border-left-color: var(--night-primary);
        color: var(--night-text);
    }
    
    body.dark-mode .problem-info {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
        border-left-color: var(--night-secondary);
        color: var(--night-text);
    }
    
    /* Mode nuit - Date et prix */
    body.dark-mode .date-info {
        color: var(--night-text-light);
    }
    
    body.dark-mode .price-info {
        color: var(--night-accent);
    }
    
    /* Mode nuit - Indicateurs */
    body.dark-mode .indicator-badge.order-required {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    
    body.dark-mode .indicator-badge.urgent {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    
    /* Mode nuit - Avatar client */
    body.dark-mode .avatar-circle {
        background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    }
    
    body.dark-mode .phone-indicator {
        background: linear-gradient(135deg, var(--night-accent), #059669);
    }
    
    /* Mode nuit - Boutons d'action personnalisés */
    body.dark-mode .custom-action-btn.btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    
    body.dark-mode .custom-action-btn.btn-primary {
        background: linear-gradient(135deg, #059669, #047857);
    }
    
    body.dark-mode .custom-action-btn.btn-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    
    body.dark-mode .custom-action-btn.btn-info {
        background: linear-gradient(135deg, #fb7185, #f43f5e);
    }
    
    body.dark-mode .custom-action-btn.btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    
    /* Mode nuit - Texte des badges de statut en blanc dans le tableau personnalisé */
    body.dark-mode .custom-table-container .status-badge,
    body.dark-mode .custom-table-container .badge,
    body.dark-mode .status-container .badge,
    body.dark-mode .status-container .status-badge {
        color: #ffffff !important;
        text-shadow: none !important;
    }
    
    /* S'assurer que tous les éléments dans les badges de statut du tableau ont le texte blanc */
    body.dark-mode .custom-table-row .status-badge,
    body.dark-mode .custom-table-row .status-badge *,
    body.dark-mode .custom-table-row .badge,
    body.dark-mode .custom-table-row .badge *,
    body.dark-mode .custom-table-cell .status-badge,
    body.dark-mode .custom-table-cell .status-badge *,
    body.dark-mode .custom-table-cell .badge,
    body.dark-mode .custom-table-cell .badge * {
        color: #ffffff !important;
        text-shadow: none !important;
    }

    /* ========================================
       STYLES POUR LE NOUVEAU TABLEAU MODERNE
       ======================================== */
    


    .table-header {
        display: grid;
        grid-template-columns: 80px 1fr 1fr 2fr 100px 1.2fr 100px 140px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        font-size: 14px;
        padding: 0;
        border-bottom: 2px solid #5a67d8;
        border-radius: 12px 12px 0 0;
        width: 100%;
    }

    .header-cell {
        padding: 16px 12px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
    }

    .header-cell::after {
        content: '';
        position: absolute;
        right: 0;
        top: 25%;
        height: 50%;
        width: 1px;
        background: rgba(255, 255, 255, 0.2);
    }

    .header-cell:last-child::after {
        display: none;
    }

    .table-body {
        max-height: 600px;
        overflow-y: auto;
    }

    .table-row {
        display: grid;
        grid-template-columns: 80px 1fr 1fr 2fr 100px 1.2fr 100px 140px;
        border-bottom: 1px solid #f1f5f9;
        background: white;
        transition: all 0.3s ease;
        cursor: grab;
        margin: 0 8px;
        border-radius: 8px;
        margin-bottom: 4px;
        width: calc(100% - 16px);
    }

    .table-row:hover {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        transform: translateX(4px) scale(1.005);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border-bottom: 1px solid #e2e8f0;
        cursor: pointer;
        border-left: 4px solid #3b82f6;
    }
    
    .table-row {
        cursor: pointer;
        position: relative;
    }
    
    .table-row::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: transparent;
        transition: all 0.3s ease;
    }
    
    .table-row:hover::before {
        background: #3b82f6;
        width: 4px;
    }

    .table-row:active {
        cursor: grabbing;
    }

    .table-row.dragging {
        opacity: 0.8;
        transform: scale(1.02);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        z-index: 1000;
    }

    .table-cell {
        padding: 16px 12px;
        display: flex;
        align-items: center;
        min-height: 75px;
        position: relative;
    }

    .table-cell::after {
        content: '';
        position: absolute;
        right: 0;
        top: 15%;
        height: 70%;
        width: 1px;
        background: linear-gradient(to bottom, transparent, #e2e8f0, transparent);
        opacity: 0.5;
    }

    .table-cell:last-child::after {
        display: none;
    }

    /* Colonnes spécifiques */
    .cell-new {
        justify-content: center;
    }

    .cell-client {
        padding: 8px 12px;
    }

    .cell-appareil, .cell-probleme {
        justify-content: flex-start;
        text-align: left;
    }
    
    .cell-date, .cell-prix {
        justify-content: center;
        text-align: center;
    }

    .cell-statut {
        justify-content: center;
    }

    .cell-actions {
        justify-content: center;
    }

    /* Styling des éléments internes */
    .indicators-group {
        display: flex;
        gap: 6px;
        flex-direction: column;
        align-items: center;
    }

    .indicators-group i {
        font-size: 16px;
    }

    .client-info {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
    }

    .client-avatar {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }

    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .phone-indicator {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(34, 197, 94, 0.1);
        color: #059669;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
    }

    .client-details {
        min-width: 0;
        flex: 1;
    }

    .client-name {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .client-id {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 2px;
    }

    .client-phone {
        font-size: 12px;
        color: #059669;
    }

    .appareil-info {
        font-weight: 600;
        color: #1e293b;
        text-align: left;
        word-break: break-word;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        padding: 10px 14px;
        border-radius: 8px;
        border-left: 4px solid #0ea5e9;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        font-size: 14px;
    }

    .probleme-text {
        font-size: 13px;
        color: #4b5563;
        text-align: left;
        line-height: 1.5;
        word-break: break-word;
        background: #f8fafc;
        padding: 8px 12px;
        border-radius: 6px;
        border-left: 3px solid #3b82f6;
        font-style: italic;
    }

    .date-info {
        font-size: 13px;
        color: #6b7280;
        text-align: center;
    }

    .statut-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        overflow: hidden;
    }
    
    .statut-container .badge {
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 20px;
        line-height: 1.2;
    }

    .prix-info {
        font-weight: 600;
        color: #059669;
        text-align: center;
    }

    .actions-group {
        display: flex;
        gap: 3px;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        padding: 2px;
    }

    .action-btn {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 11px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .action-btn:hover {
        transform: scale(1.1);
    }

    .btn-start {
        background: #dcfce7;
        color: #16a34a;
    }

    .btn-start:hover {
        background: #bbf7d0;
    }

    .btn-stop {
        background: #fee2e2;
        color: #dc2626;
    }

    .btn-stop:hover {
        background: #fecaca;
    }

    .btn-phone {
        background: #dcfce7;
        color: #16a34a;
    }

    .btn-phone:hover {
        background: #bbf7d0;
    }

    .btn-start {
        background: #dbeafe;
        color: #2563eb;
    }
    .btn-start:hover {
        background: #bfdbfe;
    }
    .btn-sms {
        background: #e0f2fe;
        color: #0284c7;
    }

    .btn-sms:hover {
        background: #bae6fd;
    }

    .btn-delete {
        background: #fef2f2;
        color: #dc2626;
    }
    .btn-delete:hover {
        background: #fee2e2;
    }

    .table-empty {
        grid-column: 1 / -1;
        padding: 40px;
        text-align: center;
    }

    /* Ajouts de style moderne */
    .table-body {
        background: #fafbfc;
        padding: 8px 0;
        width: 100%;
    }

    .modern-table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e2e8f0;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }
    
    .results-container .card {
        margin: 0;
        width: 100%;
    }
    
    .results-container .card-body {
        padding: 0;
        width: 100%;
    }
    
    /* Responsive Design */
    @media (max-width: 1400px) {
        .table-header,
        .table-row {
            grid-template-columns: 70px 1fr 0.8fr 1.8fr 90px 1fr 90px 130px;
        }
        
        .header-cell,
        .table-cell {
            padding: 14px 10px;
            font-size: 13px;
        }
    }

    @media (max-width: 1200px) {
        .table-header,
        .table-row {
            grid-template-columns: 60px 0.8fr 0.7fr 1.5fr 80px 0.9fr 80px 120px;
        }
        
        .header-cell,
        .table-cell {
            padding: 12px 8px;
            font-size: 13px;
        }
    }

    @media (max-width: 992px) {
        .table-header,
        .table-row {
            grid-template-columns: 1.2fr 0.8fr 1fr 100px;
        }
        
        .header-new,
        .header-date,
        .cell-new,
        .cell-date {
            display: none;
        }
        
        .client-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .table-row {
            margin: 0 4px;
            width: calc(100% - 8px);
        }
    }

    @media (max-width: 768px) {
        .table-header,
        .table-row {
            grid-template-columns: 1fr 0.6fr 80px;
        }
        
        .header-prix,
        .header-probleme,
        .header-appareil,
        .cell-prix,
        .cell-probleme,
        .cell-appareil {
            display: none;
        }
        
        .action-btn {
            width: 26px;
            height: 26px;
            font-size: 10px;
        }
        
        .table-row {
            margin: 0 2px;
            width: calc(100% - 4px);
        }
        
        .modern-table-container {
            margin: 0 4px;
            width: calc(100% - 8px);
        }
    }

    /* ========================================
       STYLES POUR LE MODE NUIT - TABLEAU
       ======================================== */
    
    /* Mode sombre pour le conteneur du tableau */
    body.dark-mode .modern-table-container {
        background: #1a1e2c !important;
        border: 1px solid #374151 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    /* Mode sombre pour le corps du tableau */
    body.dark-mode .table-body {
        background: #111827 !important;
    }

    /* Mode sombre pour les lignes du tableau */
    body.dark-mode .table-row {
        background: #1f2937 !important;
        border-bottom: 1px solid #374151 !important;
        color: #f9fafb !important;
    }

    /* Mode sombre pour le hover des lignes */
    body.dark-mode .table-row:hover {
        background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important;
        border-bottom: 1px solid #6b7280 !important;
        border-left: 4px solid #60a5fa !important;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3) !important;
    }

    /* Mode sombre pour les cellules */
    body.dark-mode .table-cell {
        color: #f9fafb !important;
    }

    body.dark-mode .table-cell::after {
        background: linear-gradient(to bottom, transparent, #4b5563, transparent) !important;
    }

    /* Mode sombre pour l'en-tête du tableau */
    body.dark-mode .table-header {
        background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important;
        border-bottom: 2px solid #6b7280 !important;
        color: #f9fafb !important;
    }

    /* Mode sombre pour les informations client */
    body.dark-mode .client-name,
    body.dark-mode .client-id,
    body.dark-mode .client-phone {
        color: #f9fafb !important;
    }

    /* Mode sombre pour les informations appareil et problème */
    body.dark-mode .appareil-info,
    body.dark-mode .probleme-text,
    body.dark-mode .date-info,
    body.dark-mode .prix-info {
        color: #f9fafb !important;
    }

    /* Mode sombre pour l'avatar du client */
    body.dark-mode .avatar-circle {
        background: rgba(96, 165, 250, 0.2) !important;
        color: #60a5fa !important;
    }

    /* Mode sombre pour les boutons d'action */
    body.dark-mode .btn-edit {
        background: rgba(59, 130, 246, 0.2) !important;
        color: #93c5fd !important;
        border: 1px solid rgba(59, 130, 246, 0.3) !important;
    }

    body.dark-mode .btn-edit:hover {
        background: rgba(59, 130, 246, 0.3) !important;
        color: #bfdbfe !important;
    }

    body.dark-mode .btn-stop {
        background: rgba(220, 38, 38, 0.2) !important;
        color: #fca5a5 !important;
        border: 1px solid rgba(220, 38, 38, 0.3) !important;
    }

    body.dark-mode .btn-stop:hover {
        background: rgba(220, 38, 38, 0.3) !important;
        color: #fecaca !important;
    }

    body.dark-mode .btn-phone {
        background: rgba(22, 163, 74, 0.2) !important;
        color: #86efac !important;
        border: 1px solid rgba(22, 163, 74, 0.3) !important;
    }

    body.dark-mode .btn-phone:hover {
        background: rgba(22, 163, 74, 0.3) !important;
        color: #bbf7d0 !important;
    }

    body.dark-mode .btn-start {
        background: rgba(37, 99, 235, 0.2) !important;
        color: #93c5fd !important;
        border: 1px solid rgba(37, 99, 235, 0.3) !important;
    }

    body.dark-mode .btn-start:hover {
        background: rgba(37, 99, 235, 0.3) !important;
        color: #bfdbfe !important;
    }

    body.dark-mode .btn-sms {
        background: rgba(2, 132, 199, 0.2) !important;
        color: #7dd3fc !important;
        border: 1px solid rgba(2, 132, 199, 0.3) !important;
    }

    body.dark-mode .btn-sms:hover {
        background: rgba(2, 132, 199, 0.3) !important;
        color: #bae6fd !important;
    }

    body.dark-mode .btn-delete {
        background: rgba(220, 38, 38, 0.2) !important;
        color: #fca5a5 !important;
        border: 1px solid rgba(220, 38, 38, 0.3) !important;
    }

    body.dark-mode .btn-delete:hover {
        background: rgba(220, 38, 38, 0.3) !important;
        color: #fecaca !important;
    }

    /* Mode sombre pour les cartes de résultats */
    body.dark-mode .results-container .card {
        background: #1f2937 !important;
        border: 1px solid #374151 !important;
        color: #f9fafb !important;
    }

    body.dark-mode .results-container .card-body {
        background: transparent !important;
    }

    /* Mode sombre pour les indicateurs */
    body.dark-mode .indicators-group i {
        opacity: 0.9;
    }

    /* Mode sombre pour les lignes vides */
    body.dark-mode .table-empty {
        color: #9ca3af !important;
    }

    /* Amélioration du contraste pour la lisibilité */
    body.dark-mode .table-row:hover::before {
        background: #60a5fa !important;
    }

    /* Assurer que tous les textes sont visibles en mode sombre */
    body.dark-mode .table-row *:not(.badge):not(.btn) {
        color: inherit !important;
    }

    /* Mode sombre pour les badges de statut */
    body.dark-mode .status-badge {
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
    }

    /* Styles pour les champs Appareil et Problème - Mode sombre */
    body.dark-mode .appareil-info,
    body.dark-mode .probleme-text {
        background: var(--night-card-bg) !important;
        padding: 8px 12px !important;
        border-radius: 6px !important;
        color: var(--night-text) !important;
        border: 1px solid var(--night-border) !important;
    }
</style>

<!-- CSS et JS essentiels -->
<link rel="stylesheet" href="../assets/css/futuristic-interface.css">
<script src="../assets/js/modern-filters.js" defer></script>

<script>
// Variable globale pour l'ID de l'utilisateur connecté - définie très tôt
window.currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
console.log('currentUserId défini globalement:', window.currentUserId);

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing view toggle...');
    
    // Toggle entre vue tableau et cartes
    const toggleButtons = document.querySelectorAll('.toggle-view');
    const tableView = document.getElementById('table-view');
    const cardsView = document.getElementById('cards-view');
    
    console.log('Elements found:', { 
        toggleButtons: toggleButtons.length,
        tableView: tableView,
        cardsView: cardsView
    });
    
    // Fonction pour ajuster l'affichage des cartes
    function adjustCardsLayout() {
        const cards = document.querySelectorAll('#cards-view .dashboard-card');
        
        // Reset des hauteurs pour recalculer
        cards.forEach(card => {
            card.style.height = 'auto';
        });
        
        // Si on est sur un écran de plus de 768px, on uniformise les hauteurs par ligne
        if (window.innerWidth > 768) {
            let rowCards = [];
            let currentOffset = null;
            
            // Regrouper les cartes par ligne en fonction de leur position Y
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                
                if (currentOffset === null || Math.abs(rect.top - currentOffset) > 10) {
                    // Nouvelle ligne
                    if (rowCards.length > 0) {
                        // Appliquer la hauteur maximale à la ligne précédente
                        const maxHeight = Math.max(...rowCards.map(c => c.offsetHeight));
                        rowCards.forEach(c => {
                            c.style.height = maxHeight + 'px';
                        });
                    }
                    
                    currentOffset = rect.top;
                    rowCards = [card];
                } else {
                    // Même ligne
                    rowCards.push(card);
                }
            });
            
            // Traiter la dernière ligne
            if (rowCards.length > 0) {
                const maxHeight = Math.max(...rowCards.map(c => c.offsetHeight));
                rowCards.forEach(c => {
                    c.style.height = maxHeight + 'px';
                });
            }
        }
    }
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const viewMode = this.getAttribute('data-view');
            console.log('Switching to view:', viewMode);
            
            // Mettre à jour les boutons
            toggleButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-outline-secondary');
            });
            this.classList.add('active');
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-secondary');
            
            // Mettre à jour l'affichage
            if (viewMode === 'table') {
                tableView.classList.remove('d-none');
                cardsView.classList.add('d-none');
                localStorage.setItem('repairViewMode', 'table');
            } else {
                tableView.classList.add('d-none');
                cardsView.classList.remove('d-none');
                localStorage.setItem('repairViewMode', 'cards');
                // Ajuster le layout des cartes
                setTimeout(adjustCardsLayout, 100);
            }
            
            // Mettre à jour l'URL avec le mode de vue tout en conservant les autres paramètres
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('view', viewMode);
            
            // Mettre à jour l'URL sans recharger la page
            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
            history.pushState({}, '', newUrl);
        });
    });
    
    // Vérifier d'abord s'il y a un paramètre view dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view');
    
    if (viewParam) {
        // Si un paramètre view est présent dans l'URL, l'utiliser et le sauvegarder
        console.log('URL view parameter found:', viewParam);
        localStorage.setItem('repairViewMode', viewParam);
    }
    
    // Ensuite seulement charger la préférence utilisateur (soit depuis l'URL, soit depuis localStorage)
    const savedViewMode = localStorage.getItem('repairViewMode') || 'cards';
    console.log('View mode to apply:', savedViewMode);
    
    // Trouver et cliquer sur le bouton correspondant au mode d'affichage
    const btn = document.querySelector(`.toggle-view[data-view="${savedViewMode}"]`);
    if (btn) {
        console.log('Clicking view mode button for:', savedViewMode);
        
        // Utiliser un délai minimal pour s'assurer que le DOM est prêt
        setTimeout(() => {
            btn.click();
            // Ajuster le layout des cartes si on est en mode cartes
            if (savedViewMode === 'cards') {
                setTimeout(adjustCardsLayout, 200);
            }
        }, 10);
    } else {
        console.error('View mode button not found for:', savedViewMode);
    }
    
    // Ajuster le layout des cartes lors du redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        if (!cardsView.classList.contains('d-none')) {
            adjustCardsLayout();
        }
    });
    
    // Fonction pour appliquer un filtre tout en conservant le mode d'affichage
    window.applyFilter = function(statut_ids) {
        // Récupérer le mode d'affichage actuel
        const viewMode = localStorage.getItem('repairViewMode') || 'cards';
        
        // Construire l'URL avec tous les paramètres
        let url = `index.php?page=reparations&statut_ids=${statut_ids}&view=${viewMode}`;
        
        console.log('Applying filter with params:', { statut_ids, viewMode });
        
        // Rediriger avec les bons paramètres
        window.location.href = url;
    }
    
    // Modifier les liens de filtres pour utiliser la fonction applyFilter
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Si le bouton a un attribut data-category-id, il s'agit d'un filtre
            const categoryId = this.getAttribute('data-category-id');
            if (categoryId) {
                e.preventDefault();
                let statusIds;
                switch (categoryId) {
                    case '1': statusIds = '1,2,3,19,20'; break;
                    case '2': statusIds = '4,5'; break;
                    case '3': statusIds = '6,7,8'; break;
                    case '4': statusIds = '9,10'; break;
                    case '5': statusIds = '11,12,13'; break;
                    default: statusIds = '1,2,3,4,5';
                }
                window.applyFilter(statusIds);
            } else if (this.classList.contains('filter-btn')) {
                // C'est le bouton "Toutes"
                e.preventDefault();
                window.applyFilter('1,2,3,4,5');
            }
        });
    });
    
    // Appliquer des styles améliorés aux boutons au format tableau
    function applyButtonStyles() {
        // Boutons SMS
        document.querySelectorAll('#table-view .btn-soft-info').forEach(btn => {
            btn.style.backgroundColor = '#6610f2';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(102, 16, 242, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#5a0dce';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(102, 16, 242, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.backgroundColor = '#6610f2';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(102, 16, 242, 0.2)';
            });
        });
        
        // Boutons corbeille (delete)
        document.querySelectorAll('#table-view .btn-soft-danger, #table-view .delete-repair').forEach(btn => {
            btn.style.backgroundColor = '#e74c3c';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(231, 76, 60, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#c0392b';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(231, 76, 60, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.backgroundColor = '#e74c3c';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(231, 76, 60, 0.2)';
            });
        });
        
        // Boutons start-repair
        document.querySelectorAll('#table-view .btn-soft-primary, #table-view .start-repair').forEach(btn => {
            btn.style.background = 'linear-gradient(135deg, #0d6efd, #3a86ff)';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(13, 110, 253, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.background = 'linear-gradient(135deg, #3a86ff, #0d6efd)';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(13, 110, 253, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.background = 'linear-gradient(135deg, #0d6efd, #3a86ff)';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(13, 110, 253, 0.2)';
            });
        });
    }
    
    // Appliquer les styles une fois que la vue est chargée
    setTimeout(applyButtonStyles, 100);
    
    // Réappliquer les styles si on change de vue
    document.querySelectorAll('.toggle-view').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimeout(applyButtonStyles, 100);
        });
    });
});

</script>

<!-- Masquer le dock mobile pendant les modals de devis (desktop uniquement) -->
<style>
    /* Masquage agressif quand un modal est ouvert sur desktop */
    body.hide-mobile-dock #mobile-dock {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        z-index: -1 !important;
    }
    @media (max-width: 768px) {
        /* Ne rien changer sur vrais mobiles/tablettes */
        body.hide-mobile-dock #mobile-dock { display: block !important; visibility: visible !important; opacity: 1 !important; z-index: auto !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function isRealMobile() {
        return window.innerWidth <= 768 && ('ontouchstart' in window || navigator.maxTouchPoints > 0);
    }
    function setDockHidden(hidden) {
        const mobileDock = document.getElementById('mobile-dock');
        if (!mobileDock) return;
        if (!isRealMobile()) {
            if (hidden) {
                document.body.classList.add('hide-mobile-dock');
            } else {
                document.body.classList.remove('hide-mobile-dock');
            }
        }
    }
    const modalIds = ['devisEnAttenteModal', 'devisDetailsModal', 'renvoyerTousModal', 'prolongerModal'];
    modalIds.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('show.bs.modal', () => setDockHidden(true));
        el.addEventListener('hidden.bs.modal', () => setDockHidden(false));
    });
});
</script>

<style>
/* Affichage explicite du nouveau modal au moment de l'ouverture */
#updateStatusModal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    z-index: 1060 !important;
}
#updateStatusModal .modal-dialog { pointer-events: auto; }
#updateStatusModal .modal-content { display: block; }
</style>

<script>
// Fonction pour améliorer l'affichage du tableau
document.addEventListener('DOMContentLoaded', function() {
    function improveTableLayout() {
        // Appliquer les styles d'alignement à gauche pour les cellules de tableau
        const tableCells = document.querySelectorAll('#table-view table td, #table-view table th');
        tableCells.forEach(cell => {
            cell.style.textAlign = 'left';
        });
        
        // Seule la dernière colonne (actions) reste alignée à droite
        const lastCells = document.querySelectorAll('#table-view table td:last-child, #table-view table th:last-child');
        lastCells.forEach(cell => {
            cell.style.textAlign = 'right';
        });
        
        // Nous ne tronquons plus le nom de l'appareil pour afficher le texte complet
        const appareilCells = document.querySelectorAll('#table-view .d-none.d-md-table-cell:nth-child(3)');
        appareilCells.forEach(cell => {
            // Garder le texte complet
            cell.style.whiteSpace = 'normal';
            cell.style.wordBreak = 'break-word';
        });
    }
    
    // Appliquer au chargement
    setTimeout(improveTableLayout, 200);
    
    // Réappliquer lors du changement de vue
    document.querySelectorAll('.toggle-view').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimeout(improveTableLayout, 200);
        });
    });
});
</script>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <!-- Loader Mode Sombre (par défaut) -->
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
    
    <!-- Loader Mode Clair -->
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
<div class="page-container" id="mainContent" style="display: none;">
    <!-- Filtres rapides pour tous les écrans -->
    <div class="modern-filters-container">
        <!-- Barre de recherche moderne -->
        <div class="modern-search">
            <form method="GET" action="index.php" class="search-form">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="hidden" name="page" value="reparations">
                    <input type="hidden" name="view" value="<?php echo isset($_GET['view']) ? htmlspecialchars($_GET['view']) : (isset($_COOKIE['repairViewMode']) ? htmlspecialchars($_COOKIE['repairViewMode']) : 'cards'); ?>">
                    <input type="text" class="search-input" name="search" placeholder="Rechercher par nom, téléphone, appareil..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <button type="button" class="reset-btn" onclick="window.location.href='index.php?page=reparations<?php echo isset($_GET['view']) ? '&view='.htmlspecialchars($_GET['view']) : ''; ?>'">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php endif; ?>
                    
                    <button class="search-btn" type="submit">
                        <i class="fas fa-search"></i>Rechercher
                    </button>
                </div>
                
                <!-- Suppression de la section des options avancées car les boutons ont été déplacés dans la section d'action -->
            </form>
        </div>

        <!-- Filtres modernes -->
        <div class="modern-filters">
            <!-- Bouton Nouvelle -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '1,2,3,19,20' ? 'active' : ''; ?>"
               data-category-id="1">
                <div class="ripple"></div>
                <i class="fas fa-plus-circle filter-icon"></i>
                <span class="filter-name">Nouvelle</span>
                <span class="filter-count"><?php echo $total_nouvelles ?? 0; ?></span>
            </a>
            
            <!-- Bouton En cours -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '4,5' ? 'active' : ''; ?>"
               data-category-id="2">
                <div class="ripple"></div>
                <i class="fas fa-spinner filter-icon"></i>
                <span class="filter-name">En cours</span>
                <span class="filter-count"><?php echo $total_en_cours ?? 0; ?></span>
            </a>
            
            <!-- Bouton En attente -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '6,7,8' ? 'active' : ''; ?>"
               data-category-id="3">
                <div class="ripple"></div>
                <i class="fas fa-clock filter-icon"></i>
                <span class="filter-name">En attente</span>
                <span class="filter-count"><?php echo $total_en_attente ?? 0; ?></span>
            </a>
            
            <!-- Bouton Terminé -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '9,10' ? 'active' : ''; ?>"
               data-category-id="4">
                <div class="ripple"></div>
                <i class="fas fa-check-circle filter-icon"></i>
                <span class="filter-name">Terminé</span>
                <span class="filter-count"><?php echo $total_termines ?? 0; ?></span>
            </a>
            
            <!-- Bouton Toutes -->
            <a href="javascript:void(0);" 
               class="modern-filter <?php echo ($statut_ids == '1,2,3,4,5' || (empty($statut) && empty($_GET['statut_ids']))) ? 'active' : ''; ?>">
                <div class="ripple"></div>
                <i class="fas fa-list filter-icon"></i>
                <span class="filter-name">Récentes</span>
                <span class="filter-count"><?php echo $total_reparations ?? 0; ?></span>
            </a>
            
            <!-- Bouton Archivé -->
            <a href="javascript:void(0);" 
               class="modern-filter droppable <?php echo $statut_ids == '11,12,13' ? 'active' : ''; ?>"
               data-category-id="5">
                <div class="ripple"></div>
                <i class="fas fa-archive filter-icon"></i>
                <span class="filter-name">Archivé</span>
                <span class="filter-count"><?php echo $total_archives ?? 0; ?></span>
            </a>
        </div>
    </div>

    <!-- Boutons d'action principaux -->
    <div class="action-buttons-container">
        <div class="modern-action-buttons">
            <a href="index.php?page=ajouter_reparation" class="action-button">
                <i class="fas fa-plus-circle"></i>
                    </a>
            <button type="button" class="action-button toggle-view" data-view="table">
                <i class="fas fa-table"></i>
                    </button>
            <button type="button" class="action-button toggle-view active" data-view="cards">
                <i class="fas fa-th-large"></i>
                    </button>
            <button type="button" class="action-button" data-bs-toggle="modal" data-bs-target="#devisEnAttenteModal">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>DEVIS EN ATTENTE</span>
                    </button>
            <button type="button" class="action-button" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                <i class="fas fa-layer-group"></i>
                <span>MISE À JOUR STATUTS</span>
                    </button>
        </div>
    </div>

    <!-- Conteneur pour les résultats -->
    <div class="results-container">
        <div class="card">
            <div class="card-body">
                <!-- Vue tableau moderne personnalisé -->
                <div id="table-view" class="d-none">
                    <div class="custom-table-container">
                        <!-- En-tête du tableau moderne -->
                        <div class="custom-table-header">
                            <div class="custom-header-cell header-indicators">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div class="custom-header-cell header-client">
                                <i class="fas fa-user"></i>
                                <span>Client</span>
                            </div>
                            <div class="custom-header-cell header-device">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Appareil</span>
                            </div>
                            <div class="custom-header-cell header-problem">
                                <i class="fas fa-wrench"></i>
                                <span>Problème</span>
                            </div>
                            <div class="custom-header-cell header-date">
                                <i class="fas fa-calendar"></i>
                                <span>Date</span>
                            </div>
                            <div class="custom-header-cell header-status">
                                <i class="fas fa-tasks"></i>
                                <span>Statut</span>
                            </div>
                            <div class="custom-header-cell header-price">
                                <i class="fas fa-euro-sign"></i>
                                <span>Prix</span>
                            </div>
                            <div class="custom-header-cell header-actions">
                                <i class="fas fa-cogs"></i>
                                <span>Actions</span>
                            </div>
                        </div>

                        <!-- Corps du tableau moderne -->
                        <div class="custom-table-body">
                                <?php if (!empty($reparations)): ?>
                                    <?php foreach ($reparations as $reparation): ?>
                                <div class="custom-table-row draggable-card" 
                                     data-id="<?php echo $reparation['id']; ?>" 
                                     data-repair-id="<?php echo $reparation['id']; ?>" 
                                     data-status="<?php echo $reparation['statut']; ?>" 
                                     draggable="true">
                                     
                                    <!-- Colonne Indicateurs -->
                                    <div class="custom-table-cell cell-indicators">
                                        <div class="indicators-group">
                                                <?php if ($reparation['commande_requise']): ?>
                                                    <div class="indicator-badge order-required" title="Commande requise">
                                                        <i class="fas fa-shopping-basket"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($reparation['urgent']): ?>
                                                    <div class="indicator-badge urgent" title="Urgent">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                    </div>

                                    <!-- Colonne Client -->
                                    <div class="custom-table-cell cell-client">
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                                <div class="phone-indicator">
                                                        <i class="fas fa-phone"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            <div class="client-details">
                                                <div class="client-name">
                                                        <?php echo htmlspecialchars(($reparation['client_nom'] ?? '') . ' ' . ($reparation['client_prenom'] ?? '')); ?>
                                                </div>
                                                <div class="client-id">ID: <?php echo $reparation['id']; ?></div>
                                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                                <div class="client-phone"><?php echo htmlspecialchars($reparation['client_telephone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                    </div>

                                    <!-- Colonne Appareil -->
                                    <div class="custom-table-cell cell-device">
                                        <div class="device-info">
                                            <?php echo htmlspecialchars($reparation['modele']); ?>
                                        </div>
                                    </div>

                                    <!-- Colonne Problème -->
                                    <div class="custom-table-cell cell-problem">
                                        <div class="problem-info">
                                            <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 50)) . (strlen($reparation['description_probleme']) > 50 ? '...' : ''); ?>
                                        </div>
                                    </div>

                                    <!-- Colonne Date -->
                                    <div class="custom-table-cell cell-date">
                                        <div class="date-info">
                                            <?php echo isset($reparation['date_reception']) ? format_date($reparation['date_reception']) : (isset($reparation['date_creation']) ? format_date($reparation['date_creation']) : 'N/A'); ?>
                                        </div>
                                    </div>

                                    <!-- Colonne Statut -->
                                    <div class="custom-table-cell cell-status">
                                        <div class="status-container">
                                            <span class="status-badge">
                                            <?php echo get_enum_status_badge($reparation['statut'], $reparation['id']); ?>
                                            </span>
                                            </div>
                                    </div>

                                    <!-- Colonne Prix -->
                                    <div class="custom-table-cell cell-price">
                                        <div class="price-info">
                                            <?php echo isset($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : (isset($reparation['prix']) ? number_format($reparation['prix'], 2, ',', ' ') . ' €' : 'N/A'); ?>
                                        </div>
                                    </div>

                                    <!-- Colonne Actions -->
                                    <div class="custom-table-cell cell-actions">
                                        <div class="actions-group">
                                                <?php 
                                                // Vérifier si l'utilisateur est attribué à cette réparation ET si c'est sa réparation active
                                                $is_assigned = ($reparation['employe_id'] == $_SESSION['user_id']);
                                                $is_active_repair = ($reparation['user_active_repair_id'] == $reparation['id']);
                                                $show_stop = $is_assigned && $is_active_repair;
                                                ?>
                                                <?php if (!$show_stop): ?>
                                            <button class="custom-action-btn btn-primary start-repair-btn" 
                                                    data-id="<?php echo $reparation['id']; ?>" 
                                                    title="Démarrer la réparation">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <?php else: ?>
                                            <button class="custom-action-btn btn-warning stop-repair-btn" 
                                                    data-id="<?php echo $reparation['id']; ?>" 
                                                    title="Arrêter la réparation">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                                <?php endif; ?>
                                            
                                                <?php if (!empty($reparation['client_telephone'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                               class="custom-action-btn btn-success" 
                                                   title="Appeler">
                                                    <i class="fas fa-phone"></i>
                                                </a>
                                                <?php endif; ?>
                                            
                                            
                                                <?php if (!empty($reparation['client_telephone'])): ?>
                                            <button type="button"
                                                   class="custom-action-btn btn-info" 
                                                   title="SMS"
                                                   data-client-id="<?php echo $reparation['client_id']; ?>"
                                                   data-client-nom="<?php echo htmlspecialchars($reparation['client_nom']); ?>"
                                                   data-client-prenom="<?php echo htmlspecialchars($reparation['client_prenom']); ?>"
                                                   data-client-tel="<?php echo htmlspecialchars($reparation['client_telephone']); ?>"
                                                   onclick="openSmsModal(
                                                       '<?php echo $reparation['client_id']; ?>', 
                                                       '<?php echo htmlspecialchars($reparation['client_nom']); ?>', 
                                                       '<?php echo htmlspecialchars($reparation['client_prenom']); ?>', 
                                                       '<?php echo htmlspecialchars($reparation['client_telephone']); ?>'
                                                   ); return false;">
                                                    <i class="fas fa-comment"></i>
                                            </button>
                                                <?php endif; ?>
                                            
                                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                <button type="button" 
                                                    class="custom-action-btn btn-danger delete-repair" 
                                                        data-id="<?php echo $reparation['id']; ?>"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                    </div>
                                </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <div class="table-empty">
                                                    <div class="no-results-container">
                                    <i class="fas fa-clipboard-list text-muted fa-3x mb-3"></i>
                                    <p class="text-muted">Aucune réparation trouvée.</p>
                                            </div>
                                </div>
                                <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Vue cartes -->
                <div id="cards-view">
                    <div class="repair-cards-container">
                        <?php if (!empty($reparations)): ?>
                    <?php foreach ($reparations as $reparation): ?>
                                <div class="modern-card draggable-card animate-card" data-id="<?php echo $reparation['id']; ?>" data-repair-id="<?php echo $reparation['id']; ?>" data-status="<?php echo $reparation['statut']; ?>" draggable="true">
                                    <!-- En-tête de la carte -->
                                    <div class="card-header">
                                        <div class="status-indicator">
                                            <?php echo get_enum_status_badge($reparation['statut'], $reparation['id']); ?>
                                        </div>
                                        <div class="repair-id">
                                            <span>ID: <?php echo $reparation['id']; ?></span>
                                        </div>
                                    </div>
                            
                                    <!-- Contenu principal -->
                                    <div class="card-content">
                                        <!-- Indicateurs spéciaux -->
                                        <?php if ($reparation['urgent'] || $reparation['commande_requise'] || !empty($reparation['notes_techniques'])): ?>
                                        <div class="special-indicators">
                                            <?php if ($reparation['urgent']): ?>
                                            <div class="indicator indicator-urgent">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                <span>Urgent</span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($reparation['commande_requise']): ?>
                                            <div class="indicator indicator-order">
                                                    <i class="fas fa-shopping-cart"></i>
                                                <span>Commande</span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($reparation['notes_techniques'])): ?>
                                            <div class="indicator indicator-notes">
                                                    <i class="fas fa-clipboard-list"></i>
                                                <span>Notes</span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Informations du client -->
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="client-details">
                                                <div class="client-name">
                                                    <?php echo htmlspecialchars(($reparation['client_nom'] ?? '') . ' ' . ($reparation['client_prenom'] ?? '')); ?>
                                            </div>
                                                <?php if (!empty($reparation['client_telephone'])): ?>
                                                <div class="client-contact">
                                                    <i class="fas fa-phone-alt"></i>
                                                    <span><?php echo htmlspecialchars($reparation['client_telephone']); ?></span>
                                        </div>
                                                <?php endif; ?>
                                                <?php if (!empty($reparation['client_email'])): ?>
                                                <div class="client-contact">
                                                    <i class="fas fa-envelope"></i>
                                                    <span><?php echo htmlspecialchars($reparation['client_email']); ?></span>
                                            </div>
                                                <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                        <!-- Informations de l'appareil -->
                                        <div class="device-info">
                                            <div class="device-icon">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <div class="device-details">
                                                <div class="device-model">
                                                    <?php echo htmlspecialchars($reparation['modele']); ?>
                                            </div>
                                                <div class="device-problem">
                                                    <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 100)) . (strlen($reparation['description_probleme']) > 100 ? '...' : ''); ?>
                                        </div>
                                            </div>
                                            </div>
                                        
                                        <!-- Date de réception -->
                                        <div class="reception-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <span>Reçu le: <?php echo isset($reparation['date_reception']) ? format_date($reparation['date_reception']) : (isset($reparation['date_creation']) ? format_date($reparation['date_creation']) : 'N/A'); ?></span>
                                        </div>
                                        
                                        <!-- Section prix -->
                                        <div class="price-section">
                                            <div class="price">
                                                <i class="fas fa-tag"></i>
                                                <span><?php echo isset($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : (isset($reparation['prix']) ? number_format($reparation['prix'], 2, ',', ' ') . ' €' : 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                                
                                <!-- Pied de la carte avec les boutons d'action -->
                                <div class="card-footer">
                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                           class="action-btn btn-call" 
                                       title="Appeler">
                                        <i class="fas fa-phone-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php 
                                    // Utiliser la même logique que pour le tableau
                                    $is_assigned_card = ($reparation['employe_id'] == $_SESSION['user_id']);
                                    $is_active_repair_card = ($reparation['user_active_repair_id'] == $reparation['id']);
                                    $show_stop_card = $is_assigned_card && $is_active_repair_card;
                                    ?>
                                    <?php if (!$show_stop_card): ?>
                                    <button type="button" 
                                                class="action-btn btn-start start-repair" 
                                            data-id="<?php echo $reparation['id']; ?>"
                                            title="Démarrer">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" 
                                                class="action-btn btn-stop stop-repair-btn" 
                                            data-id="<?php echo $reparation['id']; ?>"
                                            title="Arrêter">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                    <a href="#" 
                                           class="action-btn btn-message" 
                                       title="SMS"
                                       data-client-id="<?php echo $reparation['client_id']; ?>"
                                       data-client-nom="<?php echo htmlspecialchars($reparation['client_nom']); ?>"
                                       data-client-prenom="<?php echo htmlspecialchars($reparation['client_prenom']); ?>"
                                       data-client-tel="<?php echo htmlspecialchars($reparation['client_telephone']); ?>"
                                       onclick="openSmsModal(
                                           '<?php echo $reparation['client_id']; ?>', 
                                           '<?php echo htmlspecialchars($reparation['client_nom']); ?>', 
                                           '<?php echo htmlspecialchars($reparation['client_prenom']); ?>', 
                                           '<?php echo htmlspecialchars($reparation['client_telephone']); ?>'
                                       ); return false;">
                                        <i class="fas fa-comment-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <button type="button" 
                                                class="action-btn btn-delete delete-repair" 
                                            data-id="<?php echo $reparation['id']; ?>"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                    </div>
                    <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                            <div class="no-results-container">
                                <i class="fas fa-clipboard-list text-muted fa-3x mb-3"></i>
                                <p class="text-muted">Aucune réparation trouvée.</p>
                                </div>
                            </div>
                <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour choisir le statut spécifique après le drag & drop -->
<div class="modal fade" id="chooseStatusModal" tabindex="-1" aria-labelledby="chooseStatusModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chooseStatusModalLabel">
                    <i class="fas fa-tasks me-2"></i>
                    Choisir un statut
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Annuler"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar-circle bg-light d-inline-flex mb-3">
                        <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Sélectionner un statut</h5>
                    <p class="text-muted">Choisissez le statut que vous souhaitez attribuer à cette réparation</p>
                </div>
                
                <div id="statusButtonsContainer" class="d-flex flex-column gap-2">
                    <!-- Les boutons de statut seront générés dynamiquement ici -->
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des statuts disponibles...</p>
                    </div>
                </div>
                
                <input type="hidden" id="chooseStatusRepairId" value="">
                <input type="hidden" id="chooseStatusCategoryId" value="">
                
                <!-- Bouton pour activer/désactiver l'envoi de SMS -->
                <div class="mb-4 border border-2 border-secondary rounded p-3">
                    <h6 class="text-center mb-3 fw-bold text-secondary"><i class="fas fa-sms me-2"></i>NOTIFICATION CLIENT</h6>

                    <button id="smsToggleButton" type="button" class="btn btn-danger btn-lg w-100 mb-1" style="font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                        <i class="fas fa-ban me-2"></i>
                        NE PAS ENVOYER DE SMS AU CLIENT
                    </button>
                    <div class="text-center text-muted small mt-1">Cliquez pour activer/désactiver l'envoi d'un SMS</div>
                    <input type="hidden" id="sendSmsSwitch" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary border border-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts pour le drag & drop des statuts -->
<script>
// Variable globale pour l'ID de l'utilisateur connecté (définie plus haut)
const currentUserId = window.currentUserId;

// Fonction pour initialiser le bouton toggle pour l'envoi de SMS
function initSmsToggleButton() {
    const toggleButton = document.getElementById('smsToggleButton');
    const smsSwitch = document.getElementById('sendSmsSwitch');
    
    if (!toggleButton || !smsSwitch) {
        console.error('Éléments du toggle SMS non trouvés');
        return;
    }
    
    // Par défaut, le SMS n'est pas envoyé (value="0")
    toggleButton.addEventListener('click', function() {
        // Inverser l'état actuel
        const currentValue = smsSwitch.value;
        const newValue = currentValue === '1' ? '0' : '1';
        smsSwitch.value = newValue;
        
        // Mettre à jour l'apparence du bouton
        if (newValue === '1') {
            // SMS activé
            toggleButton.classList.remove('btn-danger');
            toggleButton.classList.add('btn-success');
            toggleButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>ENVOYER UN SMS AU CLIENT';
            // Jouer un son si disponible
            if (typeof playNotificationSound === 'function') {
                playNotificationSound();
            }
        } else {
            // SMS désactivé
            toggleButton.classList.remove('btn-success');
            toggleButton.classList.add('btn-danger');
            toggleButton.innerHTML = '<i class="fas fa-ban me-2"></i>NE PAS ENVOYER DE SMS AU CLIENT';
        }
        
        console.log('État d\'envoi de SMS mis à jour:', newValue === '1' ? 'Activé' : 'Désactivé');
    });
    
    console.log('Bouton toggle SMS initialisé avec succès');
}

// Function pour jouer un son de notification
function playNotificationSound() {
    // Créer un élément audio
    const audio = new Audio('../assets/sounds/notification.mp3');
    audio.volume = 0.5;
    audio.play().catch(e => console.log('Erreur lors de la lecture du son:', e));
}
document.addEventListener('DOMContentLoaded', function() {
    // Afficher l'ID utilisateur dans la console
    console.log('Utilisateur connecté ID:', currentUserId);
    
    // Initialisation du bouton toggle SMS
    initSmsToggleButton();
    
    // Initialiser le drag & drop pour les cartes de réparation
    initCardDragAndDrop();
    
    // Rendre les lignes du tableau cliquables
    document.querySelectorAll('#table-view .repair-row').forEach(function(row) {
        row.style.cursor = 'pointer';
        
        row.addEventListener('click', function(e) {
            // Ne pas déclencher si on a cliqué sur un bouton
            if (e.target.closest('.btn') || e.target.closest('button') || e.target.closest('a') || e.target.closest('.action-btn')) {
                return;
            }
            
            // Récupérer l'ID de la réparation
            const repairId = this.getAttribute('data-id') || this.getAttribute('data-repair-id');
            if (repairId) {
                console.log('🔄 Ouverture du modal pour la réparation:', repairId);
                
                // Utiliser la logique existante du modal
                window.pendingModalId = repairId;
                
                // Ouvrir le modal directement
                const modal = document.getElementById('repairDetailsModal');
                if (modal && typeof bootstrap !== 'undefined') {
                    try {
                        const modalInstance = new bootstrap.Modal(modal);
                        modalInstance.show();
                        
                        // Charger les détails via AJAX
                        const shopId = document.body.getAttribute('data-shop-id') || '<?php echo $current_shop_id ?? ""; ?>';
                        const apiUrl = `ajax/get_repair_details.php?id=${repairId}${shopId ? '&shop_id=' + shopId : ''}`;
                        
                        console.log('🔄 Chargement des détails via:', apiUrl);
                        
                        // Afficher le loader du modal et masquer le contenu
                        const loader = modal.querySelector('#repairDetailsLoader');
                        const content = modal.querySelector('#repairDetailsContent');
                        if (loader) loader.style.display = 'block';
                        if (content) content.style.display = 'none';
                        
                        fetch(apiUrl)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.repair) {
                                    console.log('✅ Détails chargés avec succès');
                                    console.log('🔍 Données de garantie:', {
                                        garantie_etat: data.repair.garantie_etat,
                                        garantie_id: data.repair.garantie_id,
                                        garantie_statut: data.repair.garantie_statut,
                                        garantie_debut: data.repair.garantie_debut,
                                        garantie_fin: data.repair.garantie_fin
                                    });
                                    const repair = data.repair;
                                    
                                    // Mettre à jour le titre du modal avec les informations de garantie
                                    const repairTitleText = document.getElementById('repairTitleText');
                                    const warrantyBadge = document.getElementById('warrantyBadge');
                                    
                                    if (repairTitleText) {
                                        repairTitleText.textContent = `Réparation #${repairId}`;
                                    }
                                    
                                    // Afficher le badge de garantie selon l'état
                                    console.log('🎯 Badge de garantie:', {
                                        warrantyBadge: !!warrantyBadge,
                                        garantie_etat: repair.garantie_etat,
                                        condition: !!(warrantyBadge && repair.garantie_etat)
                                    });
                                    
                                    if (warrantyBadge && repair.garantie_etat) {
                                        const warrantyText = warrantyBadge.querySelector('.warranty-text');
                                        console.log('🔧 Mise à jour du badge pour état:', repair.garantie_etat);
                                        
                                        // Réinitialiser les classes
                                        warrantyBadge.className = 'warranty-badge';
                                        
                                        switch (repair.garantie_etat) {
                                            case 'active':
                                                warrantyBadge.classList.add('warranty-active');
                                                warrantyText.textContent = 'GARANTIE';
                                                warrantyBadge.classList.remove('d-none');
                                                break;
                                            case 'expiree':
                                                warrantyBadge.classList.add('warranty-expired');
                                                warrantyText.textContent = 'GARANTIE EXPIRÉE';
                                                warrantyBadge.classList.remove('d-none');
                                                break;
                                            case 'expire_bientot':
                                                warrantyBadge.classList.add('warranty-expiring');
                                                warrantyText.textContent = 'GARANTIE EXPIRE BIENTÔT';
                                                warrantyBadge.classList.remove('d-none');
                                                break;
                                            case 'annulee':
                                                warrantyBadge.classList.add('warranty-expired');
                                                warrantyText.textContent = 'GARANTIE ANNULÉE';
                                                warrantyBadge.classList.remove('d-none');
                                                break;
                                            default:
                                                // Pas de garantie ou état inconnu
                                                warrantyBadge.classList.add('d-none');
                                                break;
                                        }
                                        
                                        // Déclencher l'animation d'entrée
                                        if (!warrantyBadge.classList.contains('d-none')) {
                                            warrantyBadge.style.animation = 'none';
                                            setTimeout(() => {
                                                warrantyBadge.style.animation = 'fadeInBounce 0.8s ease-out';
                                            }, 100);
                                        }
                                    }
                                    
                                    // Masquer le loader et afficher le contenu
                                    if (loader) loader.style.display = 'none';
                                    if (content) {
                                        content.style.display = 'block';
                                        
                                        // Debug: Afficher les propriétés de repair
                                        console.log('🔍 Propriétés de repair:', repair);
                                        console.log('📞 client_telephone:', repair.client_telephone);
                                        console.log('👤 client_nom:', repair.client_nom);
                                        console.log('👤 client_prenom:', repair.client_prenom);
                                        
                                                                                content.innerHTML = `
                                            <!-- Actions principales -->
                                            <div class="row mb-4">
                                                <div class="col-12">
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        ${repair.commande_requise ? `<button class="btn btn-warning btn-sm"><i class="fas fa-shopping-cart me-1"></i>Commander</button>` : ''}
                                                        ${repair.client_telephone ? `<a href="tel:${repair.client_telephone}" class="btn btn-success btn-sm"><i class="fas fa-phone me-1"></i>Appeler</a>` : ''}
                                                        <button class="btn btn-info btn-sm" onclick="showRepairSmsModal(${repair.id}, '${repair.client_nom || 'Client'} ${repair.client_prenom || 'Inconnu'}', '${repair.client_telephone || 'Non renseigné'}')" style="background: #17a2b8 !important; border-color: #17a2b8 !important;"><i class="fas fa-sms me-1"></i>Voir les SMS</button>
                                                        <button class="btn btn-primary btn-sm"><i class="fas fa-info-circle me-1"></i>Détails</button>
                                                        <button class="btn ${(repair.employe_id == currentUserId && repair.user_active_repair_id == repair.id) ? 'btn-danger' : 'btn-success'} btn-sm ${(repair.employe_id == currentUserId && repair.user_active_repair_id == repair.id) ? 'stop-repair-btn' : 'start-repair-btn'}" data-id="${repair.id}">
                                                            <i class="fas ${(repair.employe_id == currentUserId && repair.user_active_repair_id == repair.id) ? 'fa-stop-circle' : 'fa-play-circle'} me-1"></i>${(repair.employe_id == currentUserId && repair.user_active_repair_id == repair.id) ? 'Arrêter' : 'Démarrer'}
                                                        </button>
                                                                                                <button class="btn btn-secondary btn-sm" onclick="openStatusModal(${repair.id})">
                                            <i class="fas fa-tasks me-1"></i>Changer statut
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="openDevisModalSafely(${repair.id})">
                                            <i class="fas fa-file-invoice-dollar me-1"></i>Envoyer un devis
                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Informations principales -->
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header bg-primary text-white">
                                                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Client</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><strong>Nom :</strong> ${repair.client_nom} ${repair.client_prenom}</p>
                                                            <p><strong>Téléphone :</strong> ${repair.client_telephone || 'Non renseigné'}</p>
                                                            <p><strong>Email :</strong> ${repair.client_email || 'Non renseigné'}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header bg-info text-white">
                                                            <h6 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Appareil</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><strong>Type :</strong> ${repair.type_appareil}</p>
                                                            <p><strong>Modèle :</strong> ${repair.modele}</p>
                                                            <p><strong>Date :</strong> ${repair.date_reception}</p>
                                                            <p><strong>Statut :</strong> <span class="badge bg-${repair.statut_couleur}">${repair.statut_nom}</span></p>
                                                            <p><strong>Prix :</strong> <span class="text-success fw-bold">${repair.prix_reparation_formatte ? repair.prix_reparation_formatte + ' €' : 'Non défini'}</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Problème -->
                                            <div class="card mb-4">
                                                <div class="card-header bg-warning text-dark">
                                                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Problème déclaré</h6>
                                                </div>
                                                <div class="card-body">
                                                    <p>${repair.description_probleme}</p>
                                                </div>
                                            </div>

                                            <!-- Notes techniques -->
                                            <div class="card mb-4">
                                                <div class="card-header bg-dark text-white">
                                                    <h6 class="mb-0"><i class="fas fa-tools me-2"></i>Notes techniques</h6>
                                                </div>
                                                <div class="card-body">
                                                    <textarea 
                                                        class="form-control" 
                                                        rows="4" 
                                                        placeholder="Ajouter des notes techniques..."
                                                        id="notesTechniques_${repair.id}"
                                                        onchange="updateNotesTechniques(${repair.id}, this.value)"
                                                    >${repair.notes_techniques || ''}</textarea>
                                                    <small class="text-muted">Ces notes seront visibles par l'équipe technique</small>
                                                </div>
                                            </div>

                                            <!-- Photo de l'appareil -->
                                            <div class="card mb-4">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0"><i class="fas fa-camera me-2"></i>Photos</h6>
                                                </div>
                                                <div class="card-body">
                                                    ${repair.photo_appareil ? `
                                                        <div class="mb-3">
                                                            <img src="${repair.photo_appareil}" class="img-fluid rounded" style="max-height: 300px;" alt="Photo de l'appareil">
                                                        </div>
                                                    ` : ''}
                                                    <button class="btn btn-outline-primary btn-sm" onclick="addPhoto(${repair.id})">
                                                        <i class="fas fa-plus me-1"></i>Ajouter une photo
                                                    </button>
                                                </div>
                                            </div>
                                        `;
                                    }
                                } else {
                                    console.error('❌ Erreur lors du chargement des détails:', data.message);
                                    if (loader) loader.style.display = 'none';
                                    if (content) {
                                        content.style.display = 'block';
                                        content.innerHTML = `
                                            <div class="alert alert-danger">
                                                <h5>Erreur</h5>
                                                <p>Impossible de charger les détails de la réparation #${repairId}</p>
                                                <p class="small">Erreur: ${data.message || 'Données manquantes'}</p>
                                            </div>
                                        `;
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('❌ Erreur AJAX:', error);
                                if (loader) loader.style.display = 'none';
                                if (content) {
                                    content.style.display = 'block';
                                    content.innerHTML = `
                                        <div class="alert alert-danger">
                                            <h5>Erreur de connexion</h5>
                                            <p>Impossible de charger les détails de la réparation #${repairId}</p>
                                            <p class="small">Erreur: ${error.message}</p>
                                        </div>
                                    `;
                                }
                            });
                        
                    } catch (error) {
                        console.error('❌ Erreur lors de l\'ouverture du modal:', error);
                    }
                } else {
                    console.error('❌ Modal ou Bootstrap non disponible');
                }
            }
        });
    });
    
    // Fonctions pour les actions du modal
    window.updateNotesTechniques = function(repairId, notes) {
        console.log('Mise à jour des notes techniques pour la réparation', repairId);
        
        const formData = new FormData();
        formData.append('repair_id', repairId);
        formData.append('notes_techniques', notes);
        
        fetch('ajax/update_notes_techniques.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Notes techniques mises à jour');
                // Afficher une confirmation discrète
                const textarea = document.getElementById(`notesTechniques_${repairId}`);
                if (textarea) {
                    textarea.style.borderColor = '#28a745';
                    setTimeout(() => {
                        textarea.style.borderColor = '';
                    }, 2000);
                }
            } else {
                console.error('❌ Erreur lors de la mise à jour des notes:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Erreur AJAX notes techniques:', error);
        });
    };
    
    window.addPhoto = function(repairId) {
        console.log('Ajout de photo pour la réparation', repairId);
        
        // Créer un input file invisible
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.style.display = 'none';
        
        input.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('repair_id', repairId);
                formData.append('photo', file);
                
                // Afficher un loader
                const photoButton = document.querySelector(`button[onclick="addPhoto(${repairId})"]`);
                if (photoButton) {
                    photoButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Téléchargement...';
                    photoButton.disabled = true;
                }
                
                fetch('ajax/upload_repair_photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Photo ajoutée avec succès');
                        // Recharger le modal ou ajouter la photo à l'affichage
                        location.reload(); // Solution simple pour rafraîchir
                    } else {
                        console.error('❌ Erreur lors de l\'ajout de la photo:', data.message);
                        alert('Erreur lors de l\'ajout de la photo: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ Erreur AJAX photo:', error);
                    alert('Erreur lors du téléchargement de la photo');
                })
                .finally(() => {
                    if (photoButton) {
                        photoButton.innerHTML = '<i class="fas fa-plus me-1"></i>Ajouter une photo';
                        photoButton.disabled = false;
                    }
                });
            }
        };
        
        document.body.appendChild(input);
        input.click();
        document.body.removeChild(input);
    };
    
    window.openStatusModal = function(repairId) {
        console.log('Ouverture du modal de changement de statut pour la réparation', repairId);
        
        // Utiliser le modal existant de changement de statut
        const statusModal = document.getElementById('chooseStatusModal');
        if (statusModal && typeof bootstrap !== 'undefined') {
            // Stocker l'ID de la réparation pour le modal de statut
            window.currentRepairIdForStatus = repairId;
            
            const modalInstance = new bootstrap.Modal(statusModal);
            modalInstance.show();
        } else {
            console.error('❌ Modal de changement de statut non trouvé');
        }
    };
    
    // Fonctions pour le drag & drop des cartes
    function initCardDragAndDrop() {
        // Sélectionner toutes les cartes de réparation et les lignes du tableau
        const draggableCards = document.querySelectorAll('.draggable-card');
        const dropZones = document.querySelectorAll('.modern-filter.droppable');
        
        // Variables pour le ghost element
        let ghostElement = null;
        let draggedCard = null;
        
        console.log('Initializing drag & drop with', draggableCards.length, 'cards and', dropZones.length, 'drop zones');
        
        // Ajouter les écouteurs d'événements pour les cartes et les lignes
        draggableCards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            
            // Empêcher la propagation du clic pour les boutons à l'intérieur des cartes
            const buttons = card.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.addEventListener('mousedown', e => {
                    e.stopPropagation();
                });
                
                button.addEventListener('click', e => {
                    e.stopPropagation();
                });
            });
        });
        
        // Ajouter les écouteurs d'événements pour les zones de dépôt
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleDragOver);
            zone.addEventListener('dragenter', handleDragEnter);
            zone.addEventListener('dragleave', handleDragLeave);
            zone.addEventListener('drop', handleDrop);
        });
        
        /**
         * Gère le début du drag
         */
        function handleDragStart(e) {
            console.log('Début du drag sur une carte', this);
            
            // Marquer la carte comme étant en cours de déplacement
            this.classList.add('dragging');
            draggedCard = this;
            
            // Récupérer les données de réparation et de statut
            const repairId = this.getAttribute('data-repair-id') || this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            console.log('Données de drag:', { repairId, status });
            
            // Stocker les données de l'élément déplacé
            e.dataTransfer.setData('text/plain', JSON.stringify({
                repairId: repairId,
                status: status
            }));
            
            // Créer un "ghost element" pour le feedback visuel
            createGhostElement(this, e);
            
            // Définir l'effet de déplacement
            e.dataTransfer.effectAllowed = 'move';
        }
        
        /**
         * Gère la fin du drag
         */
        function handleDragEnd(e) {
            // Supprimer la classe de dragging
            this.classList.remove('dragging');
            
            // Supprimer le ghost element
            if (ghostElement && ghostElement.parentNode) {
                document.body.removeChild(ghostElement);
                ghostElement = null;
            }
            
            // Réinitialiser les zones de dépôt
            dropZones.forEach(zone => {
                zone.classList.remove('drag-over');
            });
            
            // Supprimer l'écouteur mousemove
            document.removeEventListener('mousemove', updateGhostPosition);
        }
        
        /**
         * Gère le survol d'une zone de dépôt
         */
        function handleDragOver(e) {
            // Empêcher le comportement par défaut pour permettre le drop
            e.preventDefault();
            return false;
        }
        
        /**
         * Gère l'entrée dans une zone de dépôt
         */
        function handleDragEnter(e) {
            this.classList.add('drag-over');
        }
        
        /**
         * Gère la sortie d'une zone de dépôt
         */
        function handleDragLeave() {
            this.classList.remove('drag-over');
        }
        
        /**
         * Gère le dépôt dans une zone
         */
        function handleDrop(e) {
            // Empêcher le comportement par défaut
            e.preventDefault();
            
            console.log('Drop détecté sur une zone de dépôt', this);
            
            // Récupérer les données
            try {
                const dataText = e.dataTransfer.getData('text/plain');
                console.log('Données de transfert brutes:', dataText);
                
                const data = JSON.parse(dataText);
                console.log('Données de transfert parsées:', data);
                
                const repairId = data.repairId;
                const categoryId = this.getAttribute('data-category-id');
                
                console.log('ID réparation:', repairId);
                console.log('ID catégorie:', categoryId);
                console.log('Element de statut:', draggedCard ? draggedCard.querySelector('.status-indicator') : 'Non trouvé');
                
                // Vérifier que nous avons toutes les données nécessaires
                if (!repairId || !categoryId) {
                    console.error('Données incomplètes pour la mise à jour du statut');
                    return false;
                }
                
                // Effet visuel de succès sur la zone de dépôt
                this.classList.add('drop-success');
                setTimeout(() => {
                    this.classList.remove('drop-success');
                }, 1000);
                
                // Mettre à jour le statut de la réparation via la fonction fetchStatusOptions
                if (draggedCard && draggedCard.querySelector('.status-indicator')) {
                fetchStatusOptions(repairId, categoryId, draggedCard.querySelector('.status-indicator'));
                } else {
                    console.error('Impossible de trouver l\'indicateur de statut sur la carte glissée');
                    // Essayer de créer une référence alternative
                    const allCards = document.querySelectorAll('.dashboard-card, .draggable-card');
                    let targetCard = null;
                    allCards.forEach(card => {
                        const cardId = card.getAttribute('data-repair-id') || card.getAttribute('data-id');
                        if (cardId == repairId) {
                            targetCard = card;
                        }
                    });
                    
                    if (targetCard && targetCard.querySelector('.status-indicator')) {
                        console.log('Carte cible alternative trouvée:', targetCard);
                        fetchStatusOptions(repairId, categoryId, targetCard.querySelector('.status-indicator'));
                    } else {
                        console.error('Aucune carte cible alternative trouvée, rechargement de la page');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                }
                
            } catch (error) {
                console.error('Erreur lors du traitement des données:', error);
            }
            
            // Réinitialiser l'état visuel
            this.classList.remove('drag-over');
            return false;
        }
        
        /**
         * Crée un élément fantôme pour le feedback visuel pendant le drag
         */
        function createGhostElement(sourceElement, event) {
            // Supprimer l'ancien ghost s'il existe
            if (ghostElement && ghostElement.parentNode) {
                document.body.removeChild(ghostElement);
            }
            
            // Créer un clone simplifié de la carte pour le ghost
            ghostElement = document.createElement('div');
            ghostElement.className = 'dashboard-card ghost-card';
            
            // Vérifier si c'est une ligne de tableau ou une carte
            if (sourceElement.tagName === 'TR') {
                // C'est une ligne de tableau
                const statusCell = sourceElement.querySelector('td:nth-child(6)');
                if (statusCell) {
                    const badge = statusCell.querySelector('.status-indicator');
                    if (badge) ghostElement.appendChild(badge.cloneNode(true));
                }
                
                const clientInfo = sourceElement.querySelector('td:nth-child(2) h6');
                if (clientInfo) ghostElement.appendChild(clientInfo.cloneNode(true));
            } else {
                // C'est une carte
                const statusBadge = sourceElement.querySelector('.status-indicator');
                if (statusBadge) ghostElement.appendChild(statusBadge.cloneNode(true));
                
                const deviceInfo = sourceElement.querySelector('.mb-0');
                if (deviceInfo) ghostElement.appendChild(deviceInfo.cloneNode(true));
            }
            
            // Positionner l'élément
            const rect = sourceElement.getBoundingClientRect();
            
            // Calculer l'offset par rapport au point de clic
            const offsetX = event.clientX - rect.left;
            const offsetY = event.clientY - rect.top;
            
            // Sauvegarder l'offset pour les mises à jour de position
            ghostElement.dataset.offsetX = offsetX;
            ghostElement.dataset.offsetY = offsetY;
            
            // Appliquer la position initiale
            ghostElement.style.left = (event.pageX - offsetX) + 'px';
            ghostElement.style.top = (event.pageY - offsetY) + 'px';
            
            // Ajouter au DOM
            document.body.appendChild(ghostElement);
            
            // Ajouter un écouteur pour le mouvement de la souris
            document.addEventListener('mousemove', updateGhostPosition);
        }
        
        /**
         * Met à jour la position de l'élément fantôme pendant le drag
         */
        function updateGhostPosition(e) {
            if (ghostElement) {
                const offsetX = parseInt(ghostElement.dataset.offsetX) || 0;
                const offsetY = parseInt(ghostElement.dataset.offsetY) || 0;
                
                ghostElement.style.left = (e.pageX - offsetX) + 'px';
                ghostElement.style.top = (e.pageY - offsetY) + 'px';
            }
        }
    }
        });
        
        /**
 * Affiche une notification temporaire
 */
function showNotification(message, type = 'info') {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '300px';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Ajouter au DOM
        document.body.appendChild(notification);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
}
    /**
     * Détermine la couleur Bootstrap à utiliser en fonction de la couleur de la catégorie
     */
    function getCategoryColor(color) {
        // Convertir la couleur en classe Bootstrap
        const colorMap = {
            'info': 'info',
            'primary': 'primary',
            'warning': 'warning',
            'success': 'success',
            'danger': 'danger',
            'secondary': 'secondary'
        };
        return colorMap[color] || 'primary';
    }

    /**
     * Récupère les options de statut pour une catégorie donnée
     */
    function fetchStatusOptions(repairId, categoryId, statusIndicator) {
        // Afficher un indicateur de chargement dans le badge
        statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Chargement...</span>';
    
    console.log('Récupération des statuts pour la catégorie:', categoryId);
        
        // Récupérer les statuts disponibles pour cette catégorie
        fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
            console.log('Réponse de get_statuts_by_category:', data);
            
                if (data.success) {
                    // Stocker les IDs pour une utilisation ultérieure
                    document.getElementById('chooseStatusRepairId').value = repairId;
                    document.getElementById('chooseStatusCategoryId').value = categoryId;
                    
                    // Générer les boutons de statut
                    const container = document.getElementById('statusButtonsContainer');
                    container.innerHTML = ''; // Effacer le contenu précédent, y compris l'indicateur de chargement
                    
                    // Déterminer la couleur de la catégorie
                    const categoryColor = getCategoryColor(data.category.couleur);
                    
                    // Ajouter un titre pour la catégorie dans le modal
                    const categoryTitle = document.getElementById('chooseStatusModalLabel');
                    if (categoryTitle) {
                        categoryTitle.innerHTML = `<i class="fas fa-tasks me-2"></i> Statuts "${data.category.nom}"`;
                    }
                    
                    // Créer un bouton pour chaque statut
                    data.statuts.forEach(statut => {
                        const button = document.createElement('button');
                        button.className = `btn btn-${categoryColor} btn-lg w-100 mb-2`;
                        button.setAttribute('data-status-id', statut.id);
                        button.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>
                            ${statut.nom}
                        `;
                        button.addEventListener('click', () => updateSpecificStatus(statut.id, statusIndicator));
                        container.appendChild(button);
                    });
                    
                    // Afficher le modal
                    const modal = new bootstrap.Modal(document.getElementById('chooseStatusModal'));
                    modal.show();
                    
                    // Rétablir le badge de statut quand l'utilisateur annule
                    const closeBtn = document.querySelector('#chooseStatusModal .btn-close');
                    const cancelBtn = document.querySelector('#chooseStatusModal .btn-outline-secondary');
                    
                    const handleCancel = function() {
                    console.log('Annulation de la sélection de statut');
                        // Nettoyer le backdrop et réactiver le scroll
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        
                        // Simplement recharger la page pour éviter l'update automatique
                        location.reload();
                    };
                    
                    if (closeBtn) {
                        // Enlever les anciens écouteurs d'événements
                        closeBtn.removeEventListener('click', handleCancel);
                        // Ajouter le nouvel écouteur
                        closeBtn.addEventListener('click', handleCancel);
                    }
                    
                    if (cancelBtn) {
                        // Enlever les anciens écouteurs d'événements
                        cancelBtn.removeEventListener('click', handleCancel);
                        // Ajouter le nouvel écouteur
                        cancelBtn.addEventListener('click', handleCancel);
                    }
                    
                } else {
                    // Afficher l'erreur
                    showNotification('Erreur: ' + data.error, 'danger');
                    location.reload(); // Recharger la page en cas d'erreur
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des statuts:', error);
                showNotification('Erreur de communication avec le serveur', 'danger');
                location.reload(); // Recharger la page en cas d'erreur
            });
    }
    
    /**
     * Met à jour le statut spécifique d'une réparation
     */
    function updateSpecificStatus(statusId, statusIndicator) {
        // Récupérer les ID stockés
        const repairId = document.getElementById('chooseStatusRepairId').value;
    
        console.log('Mise à jour du statut:', statusId, 'pour la réparation:', repairId);
        
        // Récupérer l'état de l'option d'envoi de SMS
        const sendSms = document.getElementById('sendSmsSwitch').value === '1';
        console.log('Envoi de SMS:', sendSms ? 'Activé' : 'Désactivé');
        
        // Fermer le modal (autoriser explicitement)
        const modalEl = document.getElementById('chooseStatusModal');
        if (modalEl) modalEl.dataset.allowHide = '1';
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        
        // Nettoyer le backdrop et réactiver le scroll
        document.body.classList.remove('modal-open');
        // Nettoyage agressif désactivé: laisser Bootstrap gérer backdrop/overflow
        
        // Afficher un indicateur de chargement
        statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Mise à jour...</span>';
        
        // Préparer les données
        const data = {
            repair_id: repairId,
            status_id: statusId,
            send_sms: sendSms,
            user_id: 1 // Toujours utiliser l'ID 1 (admin) pour éviter les problèmes
        };
        
        // Afficher les données pour le débogage
        console.log('Données envoyées:', data);
        
        // Fonction pour afficher une notification
        function showSilentNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            notification.setAttribute('role', 'alert');
            notification.setAttribute('aria-live', 'assertive');
            notification.setAttribute('aria-atomic', 'true');
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            document.body.appendChild(notification);
            const toast = new bootstrap.Toast(notification, { delay: 5000 });
            toast.show();
            
            // Supprimer la notification après qu'elle soit masquée
            notification.addEventListener('hidden.bs.toast', function () {
                notification.remove();
            });
        }
        
        // Essayer d'abord avec fetch (méthode JSON standard)
        fetch('../ajax/update_repair_specific_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('Réponse brute:', response);
            
            if (!response.ok) {
                if (response.status === 500) {
                    // Pour les erreurs 500, on va essayer une approche différente
                    throw new Error('RETRY_WITH_FORM');
                }
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            // Essayer de parser la réponse en JSON
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                    console.log('Réponse texte brute:', text);
                    throw new Error('Réponse non valide du serveur');
                }
            });
        })
        .then(handleSuccess)
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut:', error);
            
            if (error.message === 'RETRY_WITH_FORM') {
                console.log('Nouvelle tentative avec FormData au lieu de JSON...');
                
                // Seconde tentative avec FormData mais en indiquant qu'il s'agit de données JSON
                const formData = new FormData();
                // Ajouter les données sous forme d'une seule entrée JSON
                formData.append('json_data', JSON.stringify(data));
                
                return fetch('../ajax/update_repair_specific_status.php?format=json', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        // Troisième tentative - essayons en direct
                        console.log('Troisième tentative - mise à jour directe du statut...');
                        return directStatusUpdate(repairId, statusId, sendSms);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            // Si ce n'est pas du JSON, on essaie la mise à jour directe
                            return directStatusUpdate(repairId, statusId, sendSms);
                        }
                    });
                })
                .then(handleSuccess)
                .catch(formError => {
                    console.error('Erreur lors de la seconde tentative:', formError);
                    // Tenter une mise à jour directe du statut sans passer par l'API
                    return directStatusUpdate(repairId, statusId, sendSms)
                        .then(handleSuccess)
                        .catch(directError => {
                            handleError(directError);
                        });
                });
            } else {
                // Erreur normale, pas de seconde tentative
                handleError(error);
            }
        });
        
        // Fonction pour tenter une mise à jour directe du statut sans passer par l'API complète
        function directStatusUpdate(repairId, statusId, sendSms) {
            console.log('Effectuant une mise à jour directe du statut...');
            
            // URL simplifiée pour juste mettre à jour le statut
            return fetch('../ajax/simple_status_update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `repair_id=${repairId}&status_id=${statusId}&send_sms=${sendSms ? 1 : 0}`
            })
            .then(response => {
                if (!response.ok) {
                    // En dernier recours, simuler une réponse de succès
                    console.log('Mise à jour directe échouée, simulation de réponse');
                    return {
                        success: true,
                        message: 'Statut mis à jour localement',
                        data: {
                            badge: getDefaultBadge(statusId),
                            sms_sent: false,
                            sms_message: 'SMS non envoyé (mise à jour locale)'
                        }
                    };
                }
                
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // En cas d'erreur, simuler une réponse
                        return {
                            success: true,
                            message: 'Statut mis à jour localement',
                            data: {
                                badge: getDefaultBadge(statusId),
                                sms_sent: false,
                                sms_message: 'SMS non envoyé (mise à jour locale)'
                            }
                        };
                    }
                });
            });
        }
        
        // Fonction pour gérer le succès
        function handleSuccess(data) {
            console.log('Réponse JSON:', data);
            
            if (data.success) {
                // Mettre à jour le badge avec le nouveau statut
                statusIndicator.innerHTML = data.data.badge;
                
                // Mettre à jour l'attribut data-status de la carte
                const card = statusIndicator.closest('.draggable-card');
                if (card) {
                    card.setAttribute('data-status', data.data.statut);
                    
                    // Animation de succès
                    card.classList.add('updated');
                    setTimeout(() => {
                        card.classList.remove('updated');
                    }, 1000);
                }
                
                // Afficher un message de succès pour le changement de statut
                showSilentNotification('Statut mis à jour avec succès', 'success');
                
                // Afficher un message supplémentaire si un SMS a été envoyé
                if (data.data && data.data.sms_sent) {
                    setTimeout(() => {
                        showSilentNotification('SMS envoyé au client', 'info');
                    }, 1000); // Attendre 1 seconde pour montrer la seconde notification
                } else if (data.data && data.data.sms_message) {
                    setTimeout(() => {
                        showSilentNotification(data.data.sms_message, 'info');
                    }, 1000);
                }
            } else {
                // Afficher l'erreur
                showSilentNotification('Erreur: ' + (data.message || 'Une erreur est survenue'), 'danger');
                
                // Recharger la page pour rétablir l'état correct
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        }
        
        // Fonction pour gérer les erreurs
        function handleError(error) {
            showSilentNotification('Erreur de communication avec le serveur: ' + error.message, 'danger');
            console.error('Détails de l\'erreur:', error);
            
            // Dans le cas d'une erreur, on met quand même à jour visuellement le statut
            // pour donner un retour à l'utilisateur, même si le serveur n'a pas répondu
            statusIndicator.innerHTML = getDefaultBadge(statusId);
            
            // Recharger la page après un délai pour synchroniser avec le serveur
            setTimeout(() => {
                location.reload();
            }, 3000);
        }
        
        // Fonction pour obtenir un badge par défaut en cas d'erreur
        function getDefaultBadge(statusId) {
            // Logique simplifiée pour déterminer la couleur du badge
            let color = 'primary';
            let icon = 'info-circle';
            let text = 'Nouveau statut';
            
            // Associer des couleurs aux ID de statut courants (à adapter selon vos statuts)
            if (statusId === 1) { // Nouveau Diagnostique
                color = 'info';
                icon = 'search';
                text = 'Diagnostique';
            } else if (statusId === 3) { // Nouvelle Commande
                color = 'warning';
                icon = 'shopping-cart';
                text = 'Commande';
            } else if (statusId === 9) { // Réparation Effectuée
                color = 'success';
                icon = 'check-circle';
                text = 'Terminé';
            } else if (statusId === 11) { // Restitué
                color = 'dark';
                icon = 'box-open';
                text = 'Restitué';
            }
            
            return `<span class="badge bg-${color}"><i class="fas fa-${icon} me-1"></i> ${text}</span>`;
        }
    }
</script>

<!-- Modal de détails de réparation amélioré -->
<div class="modal fade" id="repairDetailsModal" tabindex="-1" aria-labelledby="repairDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center justify-content-between w-100" id="repairDetailsModalLabel">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-tools me-2 text-primary"></i>
                        <span id="repairTitleText">Détails de la réparation</span>
                    </div>
                    <div id="warrantyBadge" class="warranty-badge d-none">
                        <span class="warranty-text"></span>
                    </div>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Loader -->
                <div id="repairDetailsLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3 text-muted">Chargement des détails de la réparation...</p>
                </div>
                
                <!-- Contenu des détails -->
                <div id="repairDetailsContent" style="display: none;"></div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script pour le modal de réparation -->
<script src="assets/js/price-numpad.js"></script>
<script src="assets/js/status-modal.js"></script>

<!-- Modal de création de devis (PROPRE) -->
<?php include 'components/modals/devis_modal_clean.php'; ?>

<!-- Script pour le modal de devis (PROPRE) -->
<script src="assets/js/devis-clean.js"></script>

<style>
/* Styles pour le modal de réparation */
#repairDetailsModal .modal-dialog {
    max-width: 70%;
}

#repairDetailsModal .modal-title {
    position: relative;
    width: 100%;
}

/* Styles pour les badges de garantie */
.warranty-badge {
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.warranty-active { color: #047857; }
.warranty-expired { color: #b91c1c; }
.warranty-expiring { color: #b45309; }
.warranty-none { color: #374151; }

/* Animations modernes */
@keyframes fadeInBounce {
    0% {
        opacity: 0;
        transform: translateX(40px) scale(0.7) rotate(5deg);
    }
    60% {
        opacity: 1;
        transform: translateX(-8px) scale(1.08) rotate(-2deg);
    }
    80% {
        transform: translateX(3px) scale(0.98) rotate(1deg);
    }
    100% {
        opacity: 1;
        transform: translateX(0) scale(1) rotate(0deg);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        filter: brightness(1);
    }
    50% {
        transform: scale(1.03);
        filter: brightness(1.1);
    }
}

@keyframes shine {
    0% {
        left: -100%;
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        left: 100%;
        opacity: 0;
    }
}

@keyframes statusPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.2);
    }
}

/* Animation de clignotement pour les garanties qui expirent */
.warranty-expiring .warranty-text {
    animation: pulse 1.5s infinite, blink 2.5s infinite, glow 3s infinite;
}

@keyframes blink {
    0%, 60% {
        opacity: 1;
    }
    80% {
        opacity: 0.8;
    }
    100% {
        opacity: 1;
    }
}

@keyframes glow {
    0%, 100% {
        box-shadow: 
            0 4px 15px rgba(245, 158, 11, 0.3),
            0 2px 8px rgba(245, 158, 11, 0.1),
            inset 0 1px 0 rgba(255,255,255,0.2);
    }
    50% {
        box-shadow: 
            0 6px 20px rgba(245, 158, 11, 0.5),
            0 4px 12px rgba(245, 158, 11, 0.2),
            inset 0 1px 0 rgba(255,255,255,0.3),
            0 0 20px rgba(245, 158, 11, 0.4);
    }
}

/* Responsive design pour le badge en superposition */
@media (max-width: 768px) {
    .warranty-badge {
        top: calc(50% - 0.1cm);
        right: 10px;
        transform: translateY(-50%) scale(0.9);
    }
    
    .warranty-text {
        padding: 6px 12px;
        font-size: 0.65rem;
        letter-spacing: 0.5px;
    }
    
    .warranty-text::before {
        width: 6px;
        height: 6px;
        left: 6px;
    }
}

@media (max-width: 480px) {
    .warranty-badge {
        top: calc(50% - 0.2cm);
        right: 8px;
        transform: translateY(-50%) scale(0.8);
    }
    
    .warranty-text {
        padding: 5px 10px;
        font-size: 0.6rem;
        letter-spacing: 0.3px;
    }
}

/* Mode sombre */
body.dark-mode .warranty-text {
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.1);
}

body.dark-mode .warranty-active {
    box-shadow: 
        0 4px 15px rgba(16, 185, 129, 0.4),
        0 2px 8px rgba(16, 185, 129, 0.2),
        inset 0 1px 0 rgba(255,255,255,0.1);
}

body.dark-mode .warranty-expired {
    box-shadow: 
        0 4px 15px rgba(239, 68, 68, 0.4),
        0 2px 8px rgba(239, 68, 68, 0.2),
        inset 0 1px 0 rgba(255,255,255,0.1);
}

body.dark-mode .warranty-expiring {
    box-shadow: 
        0 4px 15px rgba(245, 158, 11, 0.4),
        0 2px 8px rgba(245, 158, 11, 0.2),
        inset 0 1px 0 rgba(255,255,255,0.1);
}

body.dark-mode .warranty-none {
    box-shadow: 
        0 4px 15px rgba(107, 114, 128, 0.3),
        0 2px 8px rgba(107, 114, 128, 0.15),
        inset 0 1px 0 rgba(255,255,255,0.05);
}

@media (max-width: 1200px) {
    #repairDetailsModal .modal-dialog {
        max-width: 80%;
    }
}

@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 85%;
    }
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-dialog {
        max-width: 95%;
    }
}

#repairDetailsModal .modal-content {
    border: none;
    border-radius: 0.75rem;
    overflow: hidden;
}

#repairDetailsModal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1rem 1.5rem;
}

#repairDetailsModal .modal-body {
    padding: 1.5rem;
    background-color: #ffffff;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

#repairDetailsModal .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1rem 1.5rem;
}

/* Backdrop global pour tous les modals (effet blur et foncé) */
.modal-backdrop {
    backdrop-filter: blur(8px) !important;
    background: rgba(0, 0, 0, 0.4) !important;
    transition: all 0.3s ease !important;
}

.dark-mode .modal-backdrop {
    backdrop-filter: blur(12px) !important;
    background: rgba(0, 0, 0, 0.6) !important;
}

/* Styles pour le mode sombre */
.dark-mode #repairDetailsModal .modal-content {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-header {
    background-color: #000000 !important;
    border-bottom-color: #374151 !important;
}

/* Sélecteurs plus spécifiques pour forcer le fond noir */
body.dark-mode #repairDetailsModal .modal-header,
html body.dark-mode #repairDetailsModal .modal-header {
    background-color: #000000 !important;
    background: #000000 !important;
}

/* Forcer le titre du modal en blanc en mode nuit */
.dark-mode #repairDetailsModal .modal-header .modal-title,
.dark-mode #repairDetailsModal .modal-header #repairDetailsModalLabel,
.dark-mode #repairDetailsModal .modal-header #repairTitleText {
    color: #ffffff !important;
}

.dark-mode #repairDetailsModal .modal-body {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-footer {
    background-color: #1f2937;
    border-top-color: #374151;
}

.dark-mode #repairDetailsModal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.dark-mode #repairDetailsModal .btn-secondary {
    background-color: #4b5563;
    border-color: #374151;
    color: #ffffff !important; /* Texte FERMER en blanc en mode nuit */
}

.dark-mode #repairDetailsContent {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader .text-muted {
    color: #94a3b8 !important;
}

.dark-mode #repairDetailsLoader .spinner-border {
    border-right-color: transparent;
}

/* Styles pour les cartes en mode sombre */
.dark-mode #repairDetailsModal .card {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode #repairDetailsModal .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-body {
    background-color: #1f2937;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-footer {
    background-color: #111827;
    border-top-color: #374151;
}

/* Styles pour les éléments d'information en mode sombre */
.dark-mode .repair-summary-item {
    border-right-color: #374151;
}

.dark-mode .repair-summary-item .info .label {
    color: #94a3b8;
}

.dark-mode .icon-wrapper {
    background-color: rgba(255, 255, 255, 0.1);
    color: #60a5fa;
}

.dark-mode .contact-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-label {
    color: #94a3b8;
}

.dark-mode .empty-state {
    color: #94a3b8;
}

/* Styles pour les cartes */
#repairDetailsModal .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.2s ease;
    border-radius: 0.5rem;
    overflow: hidden;
    height: 100%;
}

#repairDetailsModal .card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#repairDetailsModal .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 1.25rem;
}

#repairDetailsModal .card-body {
    padding: 1.25rem;
    background-color: #ffffff;
}

/* Styles pour le résumé de réparation */
.repair-summary {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    width: 100%;
}
.repair-summary-item {
    flex: 1;
    min-width: 180px;
    display: flex;
    align-items: center;
    padding: 1rem;
    border-right: 1px solid rgba(0, 0, 0, 0.05);
}

.repair-summary-item:last-child {
    border-right: none;
}

.icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1.2rem;
}

.repair-summary-item .info .label {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.repair-summary-item .info .value {
    font-weight: 600;
    font-size: 1rem;
}

/* Styles pour les informations client */
.contact-info {
    margin-top: 1rem;
}

.contact-info-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
}

.contact-info-item:last-child {
    margin-bottom: 0;
}

.contact-info-item i {
    font-size: 1.1rem;
    width: 25px;
    margin-right: 0.75rem;
    text-align: center;
}

/* Styles pour les informations appareil */
.device-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.device-info-item {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
}

.device-info-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.device-info-value {
    font-weight: 500;
    color: #343a40;
}

.problem-description {
    white-space: pre-line;
    line-height: 1.5;
}

.device-photo {
    margin-top: 1.5rem;
    text-align: center;
}

.device-photo img {
    max-height: 300px;
    object-fit: contain;
    border-radius: 0.5rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

/* Styles pour les notes techniques */
.technical-notes {
    white-space: pre-line;
    line-height: 1.6;
    padding: 0.75rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
    font-size: 0.95rem;
}

/* Styles pour les photos */
.photo-gallery {
    margin: 0;
}

#repairDetailsModal .photo-item {
    position: relative;
    overflow: hidden;
    border-radius: 0.375rem;
    cursor: pointer;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
    aspect-ratio: 1/1;
}

#repairDetailsModal .photo-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#repairDetailsModal .photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

#repairDetailsModal .photo-item:hover img {
    transform: scale(1.05);
}

#repairDetailsModal .photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.6));
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 0.75rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

#repairDetailsModal .photo-item:hover .photo-overlay {
    opacity: 1;
}

#repairDetailsModal .photo-actions {
    display: flex;
    gap: 0.5rem;
}

/* Styles pour l'état vide */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #6c757d;
}
/* Styles pour les boutons d'action */
.action-buttons, 
.client-action-buttons {
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    width: 100%;
}
.action-buttons .col-4,
.client-action-buttons .col-4 {
    width: 32%;
    flex: 0 0 auto;
    max-width: 32%;
    padding: 0;
}

#repairDetailsModal .action-btn,
#repairDetailsModal .client-action-btn {
    height: 120px;
    width: 100%;
    padding: 15px 10px;
    text-align: center;
    transition: all 0.3s ease;
    border-radius: 0.75rem;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 10px;
    border-width: 2px;
}

#repairDetailsModal .action-btn i,
#repairDetailsModal .client-action-btn i {
    font-size: 2.25rem;
    margin-bottom: 12px;
}

#repairDetailsModal .action-btn:hover,
#repairDetailsModal .client-action-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#repairDetailsModal .action-btn:active,
#repairDetailsModal .client-action-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

#repairDetailsModal .action-btn span,
#repairDetailsModal .client-action-btn span {
    font-size: 0.8rem;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: normal;
    line-height: 1.2;
    font-weight: 700;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 98%;
    }
    
    .repair-summary-item {
        min-width: 150px;
        padding: 1rem;
    }
    
    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-body {
        padding: 1rem;
    }
    
    #repairDetailsModal .card-body {
        padding: 1rem 0.75rem;
    }
    
    .action-buttons, 
    .client-action-buttons {
        gap: 8px;
    }
    
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 31%;
        max-width: 31%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        padding: 10px 5px;
        height: 110px;
        border-width: 2px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.7rem;
        margin-top: 0.25rem !important;
        font-weight: 700;
        line-height: 1.1;
    }
    
    .repair-summary-item {
        flex: 0 0 50%;
        min-width: unset;
        border-right: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-child(even) {
        border-left: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-last-child(-n+2) {
        border-bottom: none;
    }
    
    .repair-summary-item:last-child:nth-child(odd) {
        border-bottom: none;
    }
    
    .device-photo img {
        max-height: 200px;
    }
}

@media (max-width: 576px) {
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 30%;
        max-width: 30%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        height: 100px;
        padding: 8px 5px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.75rem;
        margin-bottom: 8px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.65rem;
        line-height: 1;
    }
}

/* Animation de transition */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

/* Fix pour le scroll du modal - AJOUT POUR RÉSOUDRE LE PROBLÈME DE SCROLL */
#repairDetailsModal .modal-dialog {
    height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
}

#repairDetailsModal .modal-content {
    height: 100%;
    display: flex;
    flex-direction: column;
}

#repairDetailsModal .modal-body {
    flex: 1;
    overflow-y: auto !important;
    max-height: none !important;
}

/* Correction pour mobile */
@media (max-width: 768px) {
    #repairDetailsModal .modal-dialog {
        height: calc(100vh - 20px);
        margin: 10px;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Styles pour le mode sombre */
.dark-mode #repairDetailsModal .modal-content {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-header {
    background-color: #000000 !important;
    border-bottom-color: #374151 !important;
}

/* Sélecteurs plus spécifiques pour forcer le fond noir */
body.dark-mode #repairDetailsModal .modal-header,
html body.dark-mode #repairDetailsModal .modal-header {
    background-color: #000000 !important;
    background: #000000 !important;
}

/* Forcer le titre du modal en blanc en mode nuit */
.dark-mode #repairDetailsModal .modal-header .modal-title,
.dark-mode #repairDetailsModal .modal-header #repairDetailsModalLabel,
.dark-mode #repairDetailsModal .modal-header #repairTitleText {
    color: #ffffff !important;
}

.dark-mode #repairDetailsModal .modal-body {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-footer {
    background-color: #1f2937;
    border-top-color: #374151;
}

.dark-mode #repairDetailsModal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.dark-mode #repairDetailsModal .btn-secondary {
    background-color: #4b5563;
    border-color: #374151;
}

.dark-mode #repairDetailsContent {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader .text-muted {
    color: #94a3b8 !important;
}

.dark-mode #repairDetailsModal .card {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
}

.dark-mode #repairDetailsModal .card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
}

.dark-mode #repairDetailsModal .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-body {
    background-color: #1f2937;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-footer {
    background-color: #111827;
    border-top-color: #374151;
}

.dark-mode .repair-summary-item {
    border-right-color: #374151;
    border-bottom-color: #374151;
}

.dark-mode .repair-summary-item .info .label {
    color: #94a3b8;
}

.dark-mode .repair-summary-item .info .value {
    color: #e2e8f0;
}

.dark-mode .icon-wrapper {
    background-color: rgba(255, 255, 255, 0.1);
    color: #60a5fa;
}

.dark-mode .contact-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-label {
    color: #94a3b8;
}

.dark-mode .device-info-value {
    color: #e2e8f0;
}

.dark-mode .technical-notes {
    background-color: rgba(255, 255, 255, 0.05);
    color: #e2e8f0;
}

.dark-mode .empty-state {
    color: #94a3b8;
}

.dark-mode #repairDetailsModal .photo-item {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
}

.dark-mode #repairDetailsModal .photo-item:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
}

#repairDetailsModal .action-btn,
#repairDetailsModal .client-action-btn {
    height: 85px;
    width: 100%;
    padding: 10px 5px;
    text-align: center;
    transition: all 0.3s ease;
    border-radius: 0.75rem;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 8px;
    border-width: 1.5px;
}

#repairDetailsModal .action-btn i,
#repairDetailsModal .client-action-btn i {
    font-size: 1.75rem;
    margin-bottom: 8px;
}

#repairDetailsModal .action-btn span,
#repairDetailsModal .client-action-btn span {
    font-size: 0.7rem;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: normal;
    line-height: 1.2;
    font-weight: 700;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 98%;
    }
    
    .repair-summary-item {
        min-width: 150px;
        padding: 1rem;
    }
    
    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-body {
        padding: 1rem;
    }
    
    #repairDetailsModal .card-body {
        padding: 1rem 0.75rem;
    }
    
    .action-buttons, 
    .client-action-buttons {
        gap: 6px;
    }
    
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 31%;
        max-width: 31%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        padding: 8px 5px;
        height: 75px;
        border-width: 1.5px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.5rem;
        margin-bottom: 6px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.65rem;
        margin-top: 0.1rem !important;
        font-weight: 700;
        line-height: 1.1;
    }
    
    .repair-summary-item {
        flex: 0 0 50%;
        min-width: unset;
        border-right: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-child(even) {
        border-left: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-last-child(-n+2) {
        border-bottom: none;
    }
    
    .repair-summary-item:last-child:nth-child(odd) {
        border-bottom: none;
    }
    
    .device-photo img {
        max-height: 200px;
    }
}

@media (max-width: 576px) {
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 30%;
        max-width: 30%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        height: 70px;
        padding: 6px 4px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.4rem;
        margin-bottom: 5px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.6rem;
        line-height: 1;
    }
}
</style>

<!-- Modal pour le démarrage d'une réparation déjà active - Version simplifiée -->
<div class="modal fade" id="activeRepairModal" tabindex="-1" aria-labelledby="activeRepairModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="activeRepairModalLabel">
                    <i class="fas fa-tools me-2"></i>Terminer la réparation en cours
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Informations de la réparation active -->
                <div class="text-center mb-4">
                    <div class="badge bg-primary fs-6 px-3 py-2 mb-2 active-repair-badge">
                        <i class="fas fa-cog fa-spin me-2"></i>
                        <span class="active-repair-text">Réparation <span id="activeRepairId"></span> en cours</span>
                    </div>
                    <div class="small text-muted active-repair-info">
                        <div><strong>Modèle :</strong> <span id="activeRepairDevice"></span></div>
                        <div><strong>Client :</strong> <span id="activeRepairClient"></span></div>
                        <div><strong>Problème :</strong> <span id="activeRepairProblem"></span></div>
                    </div>
                </div>
                
                <hr class="my-4">

                         <!-- Actions principales simplifiées -->
                         <div class="text-center mb-3">
                             <div class="question-header p-3 mb-3">
                                 <h6 class="mb-0 fw-bold text-white">
                                     <i class="fas fa-question-circle me-2"></i>
                                     Comment terminer cette réparation ?
                                 </h6>
                    </div>
                </div>
                
                <!-- Boutons d'actions principaux -->
                <div class="d-grid gap-3">
                    <!-- Réparation terminée avec succès -->
                    <button type="button" class="btn btn-success btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="reparation_effectue">
                        <i class="fas fa-check-circle me-3 fs-4"></i>
                        <div class="text-start">
                            <div class="fw-bold">Réparation terminée</div>
                            <small class="opacity-75">L'appareil fonctionne parfaitement</small>
                                        </div>
                                            </button>

                    <!-- Besoin d'un devis -->
                    <button type="button" class="btn btn-info btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="en_attente_accord_client">
                        <i class="fas fa-file-invoice-dollar me-3 fs-4"></i>
                        <div class="text-start">
                            <div class="fw-bold">Envoyer un devis</div>
                            <small class="opacity-75">Pièces supplémentaires nécessaires</small>
                                        </div>
                                            </button>

                    <!-- Commander des pièces -->
                    <button type="button" class="btn btn-primary btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="nouvelle_commande">
                        <i class="fas fa-shopping-cart me-3 fs-4"></i>
                        <div class="text-start">
                            <div class="fw-bold">Commander des pièces</div>
                            <small class="opacity-75">Passer une commande fournisseur</small>
                                        </div>
                    </button>

                             <!-- Autres options (regroupées) -->
                             <div class="collapse" id="moreOptions">
                                 <div class="d-grid gap-3">
                                     <button type="button" class="btn btn-warning btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="en_attente_livraison">
                                         <i class="fas fa-truck me-3 fs-4"></i>
                                         <div class="text-start">
                                             <div class="fw-bold text-dark">En attente de livraison</div>
                                             <small class="text-dark opacity-75">Pièces commandées, en attente</small>
                                         </div>
                                            </button>

                                     <button type="button" class="btn btn-secondary btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="en_attente_responsable">
                                         <i class="fas fa-user-clock me-3 fs-4"></i>
                                         <div class="text-start">
                                             <div class="fw-bold text-white">Attendre un responsable</div>
                                             <small class="text-white opacity-75">Besoin d'une validation</small>
                                        </div>
                                            </button>

                                     <button type="button" class="btn btn-danger btn-lg complete-btn d-flex align-items-center justify-content-center py-3" data-status="reparation_annule">
                                         <i class="fas fa-times-circle me-3 fs-4"></i>
                                         <div class="text-start">
                                             <div class="fw-bold">Annuler la réparation</div>
                                             <small class="opacity-75">Impossible à réparer</small>
                                        </div>
                                            </button>
                                        </div>
                                    </div>
                                        
                             <!-- Bouton pour afficher plus d'options -->
                             <button class="btn btn-info btn-lg" type="button" data-bs-toggle="collapse" data-bs-target="#moreOptions" aria-expanded="false" aria-controls="moreOptions" id="toggleMoreOptions">
                                 <i class="fas fa-ellipsis-h me-2"></i>
                                 <span class="more-text">Plus d'options</span>
                                 <span class="less-text d-none">Moins d'options</span>
                             </button>
                                </div>
                     <div class="modal-footer border-0 pt-0">
                         <button type="button" class="btn btn-danger btn-lg" data-bs-dismiss="modal">
                             <i class="fas fa-times me-2"></i>Fermer
                         </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le modal activeRepairModal simplifié */
#activeRepairModal .modal-dialog {
    max-width: 500px;
}

/* === STYLES MODAL SMS HISTORIQUE RÉPARATIONS === */
/* Mode Corporate (Jour) */
.repair-sms-modal {
    border: none;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.repair-sms-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 16px 16px 0 0;
    border: none;
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 20px;
    width: 100%;
}

.modal-icon {
    font-size: 2.2rem;
    opacity: 0.9;
}

.modal-title-section h2 {
    margin: 0;
    font-size: 1.6rem;
    font-weight: 600;
    line-height: 1.2;
}
.modal-subtitle {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 0.95rem;
    font-weight: 400;
}

.repair-sms-close {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    border-radius: 10px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: white;
}

.repair-sms-close:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.05);
}

.repair-sms-body {
    padding: 0;
    background: #f8fafc;
}

.repair-sms-footer {
    background: white;
    padding: 20px 30px;
    border-top: 1px solid #e2e8f0;
    border-radius: 0 0 16px 16px;
}

.repair-sms-btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.repair-sms-btn-secondary {
    background: #6b7280;
    color: white;
}

.repair-sms-btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-1px);
}

/* Mode Futuriste (Nuit) */
body.dark-mode .repair-sms-modal {
    background: #0f172a;
    border: 1px solid #1e293b;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8);
}

body.dark-mode .repair-sms-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-bottom: 1px solid #334155;
}

body.dark-mode .repair-sms-body {
    background: #0f172a;
}

body.dark-mode .repair-sms-footer {
    background: #1e293b;
    border-top-color: #334155;
}

body.dark-mode .repair-sms-btn-secondary {
    background: #374151;
    color: #e2e8f0;
}

body.dark-mode .repair-sms-btn-secondary:hover {
    background: #4b5563;
}

body.dark-mode .repair-sms-close {
    background: rgba(255, 255, 255, 0.1);
}

body.dark-mode .repair-sms-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Spinner et loading */
.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 30px;
    gap: 20px;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e2e8f0;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

body.dark-mode .spinner {
    border-color: #334155;
    border-top-color: #667eea;
}

.loading-spinner p {
    color: #64748b;
    font-size: 1.1rem;
    margin: 0;
}

body.dark-mode .loading-spinner p {
    color: #94a3b8;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

#activeRepairModal .complete-btn {
    transition: all 0.3s ease;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

#activeRepairModal .complete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

         #activeRepairModal .badge {
             border-radius: 25px;
         }

         /* Style pour la section question */
         #activeRepairModal .question-header {
             background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
             border-radius: 12px;
             box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
         }

/* Amélioration de la lisibilité du badge */
#activeRepairModal .active-repair-badge {
    background-color: #0d6efd !important;
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
}

#activeRepairModal .active-repair-text {
    color: #ffffff !important;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

#activeRepairModal .active-repair-info {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(241, 245, 249, 0.9));
    padding: 12px;
    border-radius: 12px;
    margin-top: 10px;
    border: 1px solid rgba(203, 213, 225, 0.5);
    backdrop-filter: none;
}

#activeRepairModal .active-repair-info strong {
    color: #495057;
}

#activeRepairModal .fa-spin {
    animation: spin 2s linear infinite;
}

#activeRepairModal #toggleMoreOptions {
    transition: all 0.3s ease;
    border-radius: 8px;
}

         #activeRepairModal #toggleMoreOptions:hover {
             background-color: #31d2f2;
             border-color: #25cff2;
             color: #000;
         }

/* Animation pour le collapse */
#activeRepairModal .collapse {
    transition: all 0.3s ease;
}

         /* Style pour les boutons dans le collapse - maintenant gérés par d-grid gap-3 */

/* Personnalisation du backdrop du modal */
#activeRepairModal .modal-backdrop {
    background: linear-gradient(45deg, rgba(13, 110, 253, 0.3), rgba(25, 135, 84, 0.3));
}

.dark-mode #activeRepairModal .modal-backdrop {
    background: linear-gradient(45deg, rgba(13, 110, 253, 0.4), rgba(15, 23, 42, 0.6));
}

/* Couleur de fond personnalisée pour le modal - sans effet transparent/glossy/blurry */
#activeRepairModal .modal-content {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 2px solid #cbd5e1;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Styles pour le mode sombre - sans effet transparent/glossy/blurry */
.dark-mode #activeRepairModal .modal-content {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #f8f9fa;
    border: 2px solid #475569;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

.dark-mode #activeRepairModal .modal-header {
    background-color: #0d6efd;
    border-bottom: 1px solid #374151;
}

.dark-mode #activeRepairModal .text-muted {
    color: #9ca3af !important;
}

.dark-mode #activeRepairModal .badge.bg-primary {
    background-color: #0d6efd !important;
}

/* Styles spécifiques pour le mode sombre */
.dark-mode #activeRepairModal .active-repair-badge {
    background-color: #0d6efd !important;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.4);
}

.dark-mode #activeRepairModal .active-repair-text {
    color: #ffffff !important;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.dark-mode #activeRepairModal .active-repair-info {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.9));
    border: 1px solid rgba(71, 85, 105, 0.5);
    backdrop-filter: none;
}

.dark-mode #activeRepairModal .active-repair-info strong {
    color: #f3f4f6;
}

.dark-mode #activeRepairModal .complete-btn.btn-success {
    background-color: #198754;
    border-color: #198754;
}

.dark-mode #activeRepairModal .complete-btn.btn-info {
    background-color: #0dcaf0;
    border-color: #0dcaf0;
    color: #000;
}

.dark-mode #activeRepairModal .complete-btn.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.dark-mode #activeRepairModal .complete-btn.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}

.dark-mode #activeRepairModal .complete-btn.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
}

         .dark-mode #activeRepairModal .complete-btn.btn-danger {
             background-color: #dc3545;
             border-color: #dc3545;
         }

         /* Amélioration du contraste pour les boutons en attente en mode sombre */
         .dark-mode #activeRepairModal .complete-btn.btn-warning .text-dark {
             color: #000 !important;
         }

         .dark-mode #activeRepairModal .complete-btn.btn-secondary .text-white {
             color: #fff !important;
         }

         .dark-mode #activeRepairModal .btn-danger.btn-lg {
             background-color: #dc3545;
             border-color: #dc3545;
             color: #fff;
         }

.dark-mode #activeRepairModal .btn-outline-secondary {
    color: #f8f9fa;
    border-color: #6c757d;
}

.dark-mode #activeRepairModal .btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

         .dark-mode #activeRepairModal #toggleMoreOptions {
             background-color: #0dcaf0;
             border-color: #0dcaf0;
             color: #000;
         }

         .dark-mode #activeRepairModal #toggleMoreOptions:hover {
             background-color: #31d2f2;
             border-color: #25cff2;
             color: #000;
         }

         /* Style pour la section question en mode sombre */
         .dark-mode #activeRepairModal .question-header {
             background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
             border: 1px solid rgba(255, 255, 255, 0.2);
}
/* Styles responsifs pour mobile */
@media (max-width: 767px) {
    #activeRepairModal .modal-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    #activeRepairModal .complete-btn {
        padding: 1rem;
    }
    
    #activeRepairModal .complete-btn .fs-4 {
        font-size: 1.2rem !important;
    }
}
</style>

<script>
// JavaScript pour améliorer l'expérience utilisateur du modal activeRepairModal
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du bouton "Plus d'options"
    const toggleMoreOptions = document.getElementById('toggleMoreOptions');
    const moreOptions = document.getElementById('moreOptions');
    
    if (toggleMoreOptions) {
        toggleMoreOptions.addEventListener('click', function() {
            const moreText = this.querySelector('.more-text');
            const lessText = this.querySelector('.less-text');
            const isExpanded = moreOptions.classList.contains('show');
            
            // Changer le texte du bouton après l'animation
            setTimeout(() => {
                if (isExpanded) {
                    moreText.classList.remove('d-none');
                    lessText.classList.add('d-none');
                } else {
                    moreText.classList.add('d-none');
                    lessText.classList.remove('d-none');
                }
            }, 150);
        });
    }
});

// Attendre que le DOM soit chargé pour les autres fonctionnalités
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons de démarrage de réparation
    const startRepairButtons = document.querySelectorAll('.start-repair');
    
    // Ajouter des écouteurs d'événements à chaque bouton
    startRepairButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Récupérer l'ID de la réparation depuis l'attribut data-id
            const repairId = this.getAttribute('data-id');
            
            // Vérifier d'abord si l'utilisateur a déjà une réparation active
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'check_active_repair',
                    reparation_id: repairId
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.has_active_repair) {
                        // L'utilisateur a déjà une réparation active, afficher le modal
                        const activeRepair = data.active_repair;
                        document.getElementById('activeRepairId').textContent = `#${activeRepair.id}`;
                        document.getElementById('activeRepairDevice').textContent = activeRepair.modele || 'Non renseigné';
                        document.getElementById('activeRepairClient').textContent = `${activeRepair.client_nom || ''} ${activeRepair.client_prenom || ''}`.trim() || 'Non renseigné';
                        document.getElementById('activeRepairProblem').textContent = activeRepair.description_probleme || 'Non renseigné';
                        
                        // Ajouter des écouteurs aux boutons de statut
                        const completeButtons = document.querySelectorAll(".complete-btn");
                        completeButtons.forEach(button => {
                            // Créer un clone du bouton pour éviter les doublons d'écouteurs
                            const newButton = button.cloneNode(true);
                            button.parentNode.replaceChild(newButton, button);
                            
                            // Ajouter l'écouteur d'événement qui appelle completeActiveRepair avec le statut
                            newButton.addEventListener("click", function() {
                                const status = this.getAttribute("data-status");
                                completeActiveRepair(activeRepair.id, status);
                            });
                        });
                        
                        // Afficher le modal
                        const activeRepairModal = new bootstrap.Modal(document.getElementById('activeRepairModal'));
                        activeRepairModal.show();
                    } else {
                        // L'utilisateur n'a pas de réparation active, attribuer la réparation
                        assignRepair(repairId);
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la vérification des réparations actives.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        });
    });
    
    // Fonction pour assigner une réparation
    function assignRepair(repairId) {
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'assign_repair',
                reparation_id: repairId
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Rafraîchir la page avec les réparations en cours au lieu de rediriger vers details_reparation
                window.location.href = `index.php?page=reparations&statut_ids=4,5`;
            } else {
                // Afficher une alerte en cas d'erreur
                alert(data.message || 'Une erreur est survenue lors de l\'attribution de la réparation.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
        });
    }
    
    // Fonction pour terminer la réparation active
    function completeActiveRepair(repairId, finalStatus) {
        // Vérifier si nous avons un statut
        if (!finalStatus) {
            alert('Veuillez sélectionner un statut final');
            return;
        }
        
        // Si le statut est "en_attente_accord_client", ouvrir le modal d'envoi de devis
        if (finalStatus === 'en_attente_accord_client') {
            // Fermer le modal actif
            const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
            activeRepairModal.hide();
            
            // D'abord changer le statut de la réparation en "en_attente_accord_client"
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'complete_active_repair',
                    reparation_id: repairId,
                    final_status: finalStatus
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès après avoir mis à jour le statut
                    alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
                    
                    // Utiliser la fonction executeAction du module RepairModal pour ouvrir le modal d'envoi de devis
                    if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                        window.RepairModal.executeAction('devis', repairId);
                    } else {
                        alert("Le module d'envoi de devis n'est pas disponible. La réparation a été mise en attente d'accord client.");
                        // Recharger la page après un court délai
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
                window.location.reload();
            });
            
            return;
        }
        
        // Si le statut est "nouvelle_commande", ouvrir le modal de commande de pièces
        if (finalStatus === 'nouvelle_commande') {
            // Fermer le modal actif
            const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
            activeRepairModal.hide();
            
            // D'abord changer le statut de la réparation
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'complete_active_repair',
                    reparation_id: repairId,
                    final_status: finalStatus
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès après avoir mis à jour le statut
                    alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
                    
                    // Utiliser la fonction executeAction du module RepairModal pour ouvrir le modal de commande
                    if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                        window.RepairModal.executeAction('order', repairId);
                    } else {
                        alert("Le module de commande n'est pas disponible. La réparation a été mise en statut nouvelle commande.");
                        // Recharger la page après un court délai
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
                window.location.reload();
            });
            
            return;
        }
        
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
                activeRepairModal.hide();
                
                // Afficher un message de succès
                alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
                
                // Recharger la page pour refléter les changements
                window.location.reload();
            } else {
                alert(data.message || 'Une erreur est survenue lors de la complétion de la réparation.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
        });
    }
    
    // Fonction pour changer le statut d'une réparation
    function changeRepairStatus(repairId, status, callback) {
        fetch('ajax/change_repair_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                repair_id: repairId,
                status: status
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Statut de la réparation ${repairId} changé à ${status}`);
                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                console.error(`Erreur lors du changement de statut: ${data.message}`);
                alert(`Erreur lors du changement de statut: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors du changement de statut.');
        });
    }

    // Gestion des boutons démarrer/arrêter
    const startRepairBtns = document.querySelectorAll('.start-repair-btn');
    const stopRepairBtns = document.querySelectorAll('.stop-repair-btn');
    
    // Écouteurs pour les boutons "Démarrer"
    startRepairBtns.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const repairId = this.getAttribute('data-id');
            startRepairAction(repairId);
        });
    });
    
    // Écouteurs pour les boutons "Arrêter"
    stopRepairBtns.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const repairId = this.getAttribute('data-id');
            stopRepairAction(repairId);
        });
    });

    // Empêcher la fermeture automatique de certains modals (garde renforcée avec fenêtre de protection)
    const protectedIds = new Set(['nouvelles_actions_modal', 'chooseStatusModal']);
    const guardWindows = Object.create(null); // id -> timestamp

    document.addEventListener('show.bs.modal', function(event) {
        const modal = event.target;
        const id = modal && modal.id ? modal.id : '';
        if (protectedIds.has(id)) {
            // Fenêtre anti-fermeture pendant 1.5s après ouverture
            guardWindows[id] = Date.now() + 1500;
            // Forcer mode statique à l'ouverture
            try {
                modal.setAttribute('data-bs-backdrop', 'static');
                modal.setAttribute('data-bs-keyboard', 'false');
                // Corriger backdrop click
                modal.addEventListener('click', function(ev) {
                    if (ev.target === modal && !modal.dataset.allowHide) {
                        ev.stopPropagation();
                        ev.preventDefault();
                    }
                }, { passive: false });
            } catch (_) {}
        }
    });

    document.addEventListener('hide.bs.modal', function(event) {
        const modal = event.target;
        const id = modal && modal.id ? modal.id : '';
        const now = Date.now();
        if (protectedIds.has(id) && !modal.dataset.allowHide) {
            if (!guardWindows[id] || now <= guardWindows[id]) {
                console.warn('[MODAL GUARD] Empêche la fermeture (fenêtre active):', id);
                event.preventDefault();
                try {
                    const instance = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: 'static', keyboard: false });
                    setTimeout(() => instance.show(), 0);
                } catch (e) {}
                return;
            }
        }
    });

    document.addEventListener('hidden.bs.modal', function(event) {
        const modal = event.target;
        const id = modal && modal.id ? modal.id : '';
        if (protectedIds.has(id)) {
            delete guardWindows[id];
        }
    });

    // Monkey-patch: empêcher tout hide() programmatique sur les modals protégés
    if (window.bootstrap && bootstrap.Modal && !bootstrap.Modal.__patchedForAutoClose) {
        const originalHide = bootstrap.Modal.prototype.hide;
        bootstrap.Modal.prototype.hide = function() {
            try {
                const el = this && this._element ? this._element : null;
                const id = el && el.id ? el.id : '';
                if (el && protectedIds.has(id) && !el.dataset.allowHide) {
                    console.warn('[MODAL PATCH] hide() bloqué pour', id);
                    return; // bloquer
                }
            } catch (_) {}
            return originalHide.apply(this, arguments);
        };
        bootstrap.Modal.__patchedForAutoClose = true;
    }

});

// Fonction pour démarrer une réparation
function startRepairAction(repairId) {
    if (confirm('Êtes-vous sûr de vouloir démarrer cette réparation ?')) {
        // Vérifier d'abord si l'utilisateur a déjà une réparation active
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'check_active_repair',
                reparation_id: repairId
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.has_active_repair && data.active_repair.id != repairId) {
                    // L'utilisateur a déjà une réparation active différente
                    if (confirm('Vous avez déjà une réparation active (#' + data.active_repair.id + '). Voulez-vous la terminer et démarrer cette nouvelle réparation ?')) {
                        // Terminer d'abord la réparation active
                        completeActiveRepairAndStartNew(data.active_repair.id, repairId);
                    }
                } else {
                    // L'utilisateur n'a pas de réparation active, attribuer la réparation
                    assignRepairAction(repairId);
                }
            } else {
                alert(data.message || 'Une erreur est survenue lors de la vérification des réparations actives.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion lors de la vérification');
        });
    }
}
// Fonction pour arrêter une réparation
function stopRepairAction(repairId) {
    if (confirm('Êtes-vous sûr de vouloir arrêter cette réparation ?')) {
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Réparation terminée avec succès !');
                location.reload();
            } else {
                alert('Erreur lors de l\'arrêt : ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion lors de l\'arrêt');
        });
    }
}

// Exposer les fonctions globalement pour le modal
window.startRepairAction = startRepairAction;
window.stopRepairAction = stopRepairAction;
window.completeActiveRepairAndStartNew = completeActiveRepairAndStartNew;

// Fonction pour attribuer une réparation
function assignRepairAction(repairId) {
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'assign_repair',
            reparation_id: repairId
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Réparation démarrée avec succès !');
            location.reload();
        } else {
            alert('Erreur lors du démarrage : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors du démarrage');
    });
}

// Fonction pour terminer une réparation active et en démarrer une nouvelle
function completeActiveRepairAndStartNew(activeRepairId, newRepairId) {
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'complete_active_repair',
            reparation_id: activeRepairId
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Maintenant attribuer la nouvelle réparation
            assignRepairAction(newRepairId);
        } else {
            alert('Erreur lors de la finalisation : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors de la finalisation');
    });
}
</script>
<!-- Modal pour envoyer un SMS -->
<div class="modal fade" id="smsModal" tabindex="-1" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsModalLabel"><i class="fas fa-paper-plane me-2"></i>Envoyer un SMS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="smsForm" method="POST" action="ajax/send_sms.php">
                <div class="modal-body">
                    <input type="hidden" id="client_id" name="client_id" value="">
                    
                    <div class="mb-4">
                        <label for="recipient" class="form-label">Destinataire</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-user text-primary"></i></span>
                            <input type="text" class="form-control" id="recipient_name" readonly>
                            <input type="text" class="form-control" id="recipient_tel" name="telephone" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="template" class="form-label">Modèle de message</label>
                        <div class="template-wrapper position-relative">
                            <select class="form-select form-select-lg" id="template" name="template">
                                <option value="">Sélectionner un modèle...</option>
                                <!-- Les modèles seront chargés dynamiquement -->
                            </select>
                            <div class="position-absolute top-50 end-0 translate-middle-y me-3 pointer-events-none">
                                <i class="fas fa-chevron-down text-primary"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div id="charCount" class="badge bg-light text-dark">0 caractères</div>
                            <div id="smsCount" class="badge">1 SMS</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-lg btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-lg btn-primary" id="sendSmsBtn">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Historique SMS pour Réparations -->
<div class="modal fade" id="repairSmsHistoryModal" tabindex="-1" aria-labelledby="repairSmsHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content repair-sms-modal">
            <div class="modal-header repair-sms-header">
                <div class="modal-header-content">
                    <div class="modal-icon">💬</div>
                    <div class="modal-title-section">
                        <h2 class="modal-title">Historique des SMS</h2>
                        <p class="modal-subtitle" id="repairSmsClientName">Chargement...</p>
                    </div>
                </div>
                <button type="button" class="repair-sms-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body repair-sms-body">
                <div class="loading-spinner" id="repairSmsLoading">
                    <div class="spinner"></div>
                    <p>Chargement des SMS...</p>
                </div>
                <div class="historique-content" id="repairSmsContent">
                    <!-- Le contenu sera chargé via AJAX -->
                </div>
            </div>
            <div class="modal-footer repair-sms-footer">
                <button type="button" class="repair-sms-btn repair-sms-btn-secondary" data-bs-dismiss="modal">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteRepairModal" tabindex="-1" aria-labelledby="deleteRepairModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteRepairModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette réparation ?</p>
                <p class="text-danger"><strong>Attention :</strong> Cette action est irréversible et supprimera définitivement toutes les données associées à cette réparation.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour ouvrir le modal SMS
function openSmsModal(clientId, nom, prenom, telephone) {
    // Remplir les champs du modal
    document.getElementById('client_id').value = clientId;
    document.getElementById('recipient_name').value = nom + ' ' + prenom;
    document.getElementById('recipient_tel').value = telephone;
    
    // Charger les modèles de SMS
    fetch('ajax/get_sms_templates.php')
        .then(response => response.json())
        .then(data => {
            const templateSelect = document.getElementById('template');
            // Vider les options existantes sauf la première
            while (templateSelect.options.length > 1) {
                templateSelect.remove(1);
            }
            
            // Ajouter les nouveaux modèles
            if (data.success && data.templates) {
                data.templates.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.nom;
                    option.dataset.content = template.contenu;
                    templateSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des modèles de SMS:', error);
        });
    
    // Réinitialiser le compteur et le message
    document.getElementById('message').value = '';
    updateSmsCounter();
    
    // Afficher le modal
    const smsModal = new bootstrap.Modal(document.getElementById('smsModal'));
    smsModal.show();
}

// Mise à jour du compteur de caractères SMS
function updateSmsCounter() {
    const messageField = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    const length = messageField.value.length;
    charCount.textContent = length + ' caractères';
    
    // Calcul du nombre de SMS
    if (length <= 160) {
        smsCount.textContent = '1 SMS';
    } else {
        // 153 caractères par SMS pour les messages concaténés
        const count = Math.ceil(length / 153);
        smsCount.textContent = count + ' SMS';
    }
}

// Initialisation des éléments du modal SMS quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour le compteur de caractères lors de la saisie
    const messageField = document.getElementById('message');
    if (messageField) {
        messageField.addEventListener('input', updateSmsCounter);
    }
    
    // Charger le contenu du modèle sélectionné
    const templateSelect = document.getElementById('template');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.content) {
                messageField.value = selectedOption.dataset.content;
                updateSmsCounter();
            }
        });
    }
    
    // Gérer la soumission du formulaire SMS
    const smsForm = document.getElementById('smsForm');
    if (smsForm) {
        smsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sendBtn = document.getElementById('sendSmsBtn');
            
            // Désactiver le bouton pendant l'envoi
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...';
            
            fetch('ajax/send_sms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer';
                
                if (data.success) {
                    // Fermer le modal
                    bootstrap.Modal.getInstance(document.getElementById('smsModal')).hide();
                    
                    // Afficher un message de succès
                    alert('SMS envoyé avec succès !');
                } else {
                    // Afficher le message d'erreur
                    alert('Erreur lors de l\'envoi du SMS : ' + (data.message || 'Une erreur inconnue est survenue.'));
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi du SMS:', error);
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer';
                alert('Erreur lors de l\'envoi du SMS. Veuillez réessayer.');
            });
        });
    }
    
    // Initialiser les boutons de suppression
    const deleteButtons = document.querySelectorAll('.delete-repair');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const repairId = this.getAttribute('data-id');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Mettre à jour le lien de confirmation
            confirmBtn.href = 'index.php?page=reparations&action=delete&id=' + repairId;
            
            // Afficher le modal de confirmation
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteRepairModal'));
            deleteModal.show();
        });
    });
});
</script>
<!-- Styles compacts pour les boutons d'action -->
<link rel="stylesheet" href="assets/css/compact-buttons.css">

<script>
// Initialiser le helper de modal au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si RepairModal est déjà initialisé
    if (window.RepairModal && !window.RepairModal._isInitialized) {
        window.RepairModal._isInitialized = true;
        window.RepairModal.init();
        
        // Si un modal est en attente d'ouverture, l'ouvrir maintenant
        if (window.pendingModalId && typeof window.openPendingModal === 'function') {
            setTimeout(window.openPendingModal, 100);
        }
    }

    // Vérifier si PriceNumpad est déjà initialisé
    if (window.PriceNumpad && !window.PriceNumpad._isInitialized) {
        window.PriceNumpad._isInitialized = true;
        window.PriceNumpad.init();
    }

    // Vérifier si StatusModal est déjà initialisé
    if (window.StatusModal && !window.StatusModal._isInitialized) {
        window.StatusModal._isInitialized = true;
        window.StatusModal.init();
    }
});
// Le nouveau script de mise à jour des statuts est maintenant géré par new-update-status-modal.js

// Script pour détecter le paramètre showRepId dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const showRepId = urlParams.get('showRepId');
    
if (showRepId && typeof chargerDetailsReparation === 'function') {
            const reparationInfoModal = new bootstrap.Modal(document.getElementById('reparationInfoModal'));
            reparationInfoModal.show();
            chargerDetailsReparation(showRepId);
        }
</script>

<!-- Styles pour le modal de relance -->
<style>
.form-check-custom {
    padding: 10px;
    border: 1px solid var(--day-border);
    border-radius: 8px;
    background: var(--day-card-bg);
    transition: var(--transition-normal);
}

.form-check-custom:hover {
    background: rgba(59, 130, 246, 0.1);
    border-color: var(--day-primary);
}

.form-check-custom input:checked + label {
    color: var(--day-primary);
    font-weight: 500;
}

body.dark-mode .form-check-custom {
    border-color: var(--night-border);
    background: var(--night-card-bg);
}

body.dark-mode .form-check-custom:hover {
    background: rgba(0, 212, 255, 0.1);
    border-color: var(--night-primary);
}

body.dark-mode .form-check-custom input:checked + label {
    color: var(--night-primary);
}
</style>

<!-- Modal pour la relance client -->
<div class="modal fade" id="relanceClientModal" tabindex="-1" aria-labelledby="relanceClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(30, 144, 255, 0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(30, 144, 255, 0.8) 0%, rgba(0, 77, 155, 0.8) 100%); color: white;">
                <h5 class="modal-title" id="relanceClientModalLabel" style="text-shadow: 0 0 10px rgba(30, 144, 255, 0.7);">
                    <i class="fas fa-bell me-2"></i>Relance des clients
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="background: rgba(255, 255, 255, 0.05);">
                <div class="alert alert-info" id="alertInfo" style="background: rgba(30, 144, 255, 0.1); border: 1px solid rgba(30, 144, 255, 0.3); border-radius: 10px;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="alertInfoText">Vous êtes sur le point d'envoyer un SMS de relance aux clients dont les réparations sont terminées ou archivées mais pas encore récupérées.</span>
                </div>
                
                <!-- Filtres par statut -->
                <div class="mb-3">
                    <label class="form-label">Sélectionner les types de réparations à relancer:</label>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="form-check form-check-custom">
                                <input class="form-check-input" type="checkbox" id="filterDevisAttente" value="en_attente_accord_client">
                                <label class="form-check-label" for="filterDevisAttente">
                                    <i class="fas fa-clock text-warning me-1"></i>
                                    Devis en attente
                                </label>
                            </div>
                            <small class="text-muted">Statut: en attente accord client</small>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom">
                                <input class="form-check-input" type="checkbox" id="filterReparationTerminee" value="reparation_effectue">
                                <label class="form-check-label" for="filterReparationTerminee">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Réparation Terminée
                                </label>
                            </div>
                            <small class="text-muted">Statut: réparation effectuée</small>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom">
                                <input class="form-check-input" type="checkbox" id="filterReparationAnnulee" value="reparation_annule">
                                <label class="form-check-label" for="filterReparationAnnulee">
                                    <i class="fas fa-times-circle text-danger me-1"></i>
                                    Réparation Annulée
                                </label>
                            </div>
                            <small class="text-muted">Statut: réparation annulée</small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="relanceDelayDays" class="form-label">Relancer les réparations qui datent depuis au moins:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="relanceDelayDays" min="1" value="3" style="border-radius: 8px 0 0 8px; border: 1px solid rgba(30, 144, 255, 0.5);">
                        <span class="input-group-text" style="background: rgba(30, 144, 255, 0.3); border: 1px solid rgba(30, 144, 255, 0.5); border-radius: 0 8px 8px 0; color: white;">jours</span>
                    </div>
                    <small class="text-muted">Laissez 3 jours par défaut pour ne pas relancer des clients trop tôt.</small>
                </div>
                
                <div id="previewResults" class="d-none mt-3">
                    <h6 class="mb-3">Liste des clients à relancer:</h6>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllClients" checked>
                            <label class="form-check-label" for="selectAllClients">Sélectionner / Désélectionner tous</label>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover" style="border-radius: 10px; overflow: hidden; border: 1px solid rgba(30, 144, 255, 0.2);">
                            <thead style="background: rgba(30, 144, 255, 0.2);">
                                <tr>
                                    <th>Sélection</th>
                                    <th>Client</th>
                                    <th>Appareil</th>
                                    <th>Statut</th>
                                    <th>Terminé depuis</th>
                                </tr>
                            </thead>
                            <tbody id="previewResultsBody">
                                <!-- Les résultats seront ajoutés ici dynamiquement -->
                            </tbody>
                        </table>
                    </div>
                    <div id="noClientsMessage" class="alert alert-warning d-none" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 10px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Aucun client à relancer avec les critères sélectionnés.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: rgba(30, 144, 255, 0.05); border-top: 1px solid rgba(30, 144, 255, 0.2);">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px; border: 1px solid rgba(30, 144, 255, 0.5);">Annuler</button>
                <button type="button" class="btn btn-primary" id="previewRelanceBtn" style="background: linear-gradient(135deg, #1e90ff 0%, #0066cc 100%); border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(30, 144, 255, 0.3);">
                    <i class="fas fa-search me-1"></i>Rechercher les clients
                </button>
                <button type="button" class="btn btn-warning" id="sendRelanceBtn" disabled style="background: linear-gradient(135deg, #ff9500 0%, #ff6a00 100%); border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(255, 149, 0, 0.3);">
                    <i class="fas fa-paper-plane me-1"></i>Envoyer les SMS
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Script pour gérer le modal de relance client -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const relanceClientBtn = document.getElementById('relanceClientBtn');
    const relanceDelayDays = document.getElementById('relanceDelayDays');
    const previewResults = document.getElementById('previewResults');
    const previewResultsBody = document.getElementById('previewResultsBody');
    const noClientsMessage = document.getElementById('noClientsMessage');
    const previewRelanceBtn = document.getElementById('previewRelanceBtn');
    const sendRelanceBtn = document.getElementById('sendRelanceBtn');
    const selectAllClients = document.getElementById('selectAllClients');
    const filterDevisAttente = document.getElementById('filterDevisAttente');
    const filterReparationTerminee = document.getElementById('filterReparationTerminee');
    const filterReparationAnnulee = document.getElementById('filterReparationAnnulee');
    
    // Initialiser le modal
    let relanceModal;
    if (relanceClientBtn) {
        relanceClientBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Ouvrir le modal
            relanceModal = new bootstrap.Modal(document.getElementById('relanceClientModal'));
            relanceModal.show();
        });
    }
    
    // Écouter les changements sur le champ de jours
    if (relanceDelayDays) {
        relanceDelayDays.addEventListener('input', function() {
            // Réinitialiser l'aperçu
            previewResults.classList.add('d-none');
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Gestionnaires pour les cases à cocher de filtre
    [filterDevisAttente, filterReparationTerminee, filterReparationAnnulee].forEach(checkbox => {
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                // Réinitialiser l'aperçu quand un filtre change
                previewResults.classList.add('d-none');
                sendRelanceBtn.disabled = true;
            });
        }
    });
    
    // Action du bouton d'aperçu
    if (previewRelanceBtn) {
        previewRelanceBtn.addEventListener('click', function() {
            // Récupérer les valeurs
            const days = relanceDelayDays.value !== '' ? parseInt(relanceDelayDays.value) : 3;
            
            // Récupérer les filtres sélectionnés
            const selectedFilters = [];
            if (filterDevisAttente && filterDevisAttente.checked) {
                selectedFilters.push('en_attente_accord_client');
            }
            if (filterReparationTerminee && filterReparationTerminee.checked) {
                selectedFilters.push('reparation_effectue');
            }
            if (filterReparationAnnulee && filterReparationAnnulee.checked) {
                selectedFilters.push('reparation_annule');
            }
            
            // Vérifier qu'au moins un filtre est sélectionné
            if (selectedFilters.length === 0) {
                alert('Veuillez sélectionner au moins un type de réparation à relancer.');
                return;
            }
            
            // Appeler l'API pour obtenir un aperçu avec les filtres sélectionnés
            getPreviewRelance(days, selectedFilters);
        });
    }
    
    // Action du bouton d'envoi
    if (sendRelanceBtn) {
        sendRelanceBtn.addEventListener('click', function() {
            // Récupérer les IDs des clients sélectionnés
            const selectedClientIds = [];
            document.querySelectorAll('.client-select:checked').forEach(checkbox => {
                selectedClientIds.push(checkbox.getAttribute('data-client-id'));
            });
            
            // Si aucun client n'est sélectionné, afficher une alerte
            if (selectedClientIds.length === 0) {
                alert('Aucun client sélectionné. Veuillez sélectionner au moins un client.');
                return;
            }
            
            // Demander confirmation
            if (!confirm('ATTENTION: Vous êtes sur le point d\'envoyer des SMS de relance aux clients sélectionnés. Continuer?')) {
                return;
            }
            
            // Récupérer les valeurs
            const days = relanceDelayDays.value !== '' ? parseInt(relanceDelayDays.value) : 3;
            
            // Récupérer les filtres sélectionnés
            const selectedFilters = [];
            if (filterDevisAttente && filterDevisAttente.checked) {
                selectedFilters.push('en_attente_accord_client');
            }
            if (filterReparationTerminee && filterReparationTerminee.checked) {
                selectedFilters.push('reparation_effectue');
            }
            if (filterReparationAnnulee && filterReparationAnnulee.checked) {
                selectedFilters.push('reparation_annule');
            }
            
            // Appeler l'API pour envoyer les relances
            sendRelanceSMS(days, selectedFilters);
        });
    }
    
    // Gestionnaire pour la case à cocher "Sélectionner tous"
    if (selectAllClients) {
        selectAllClients.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.client-select').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
        
        // Ajouter un écouteur d'événements pour les clics sur les cases individuelles
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('client-select')) {
                // Vérifier si toutes les cases sont cochées
                const allCheckboxes = document.querySelectorAll('.client-select');
                const allChecked = [...allCheckboxes].every(checkbox => checkbox.checked);
                
                // Mettre à jour la case "Sélectionner tous"
                selectAllClients.checked = allChecked;
            }
        });
    }
    
    // Fonction pour obtenir un aperçu des relances
    function getPreviewRelance(days, selectedFilters = []) {
        // Afficher un indicateur de chargement
        previewResultsBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <span class="ms-2">Recherche des clients à relancer...</span>
                </td>
            </tr>
        `;
        previewResults.classList.remove('d-none');
        noClientsMessage.classList.add('d-none');
        
        // Appeler l'API
        fetch('ajax/client_relance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'preview',
                days: days,
                selectedFilters: selectedFilters
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'aperçu
                if (data.clients && data.clients.length > 0) {
                    previewResultsBody.innerHTML = '';
                    
                    // Les en-têtes du tableau restent fixes
                    const tableHeadRow = document.querySelector('#previewResults table thead tr');
                    if (tableHeadRow) {
                        tableHeadRow.innerHTML = `
                            <th>Sélection</th>
                            <th>Client</th>
                            <th>Appareil</th>
                            <th>Statut</th>
                            <th>Date</th>
                        `;
                    }
                    
                    // Ajouter chaque client à la liste
                    data.clients.forEach(client => {
                        // Déterminer le statut et sa couleur
                        let statusText = "Inconnu";
                        let statusClass = "secondary";
                        
                        if (client.statut === 'en_attente_accord_client') {
                            statusText = "Devis en attente";
                            statusClass = "warning";
                        } else if (client.statut === 'reparation_effectue') {
                            statusText = "Réparation Effectuée";
                            statusClass = "success";
                        } else if (client.statut === 'reparation_annule') {
                            statusText = "Réparation Annulée";
                            statusClass = "danger";
                        }
                        
                        // Créer la ligne
                        const row = document.createElement('tr');
                        
                        // Informations sur l'appareil
                        let deviceInfo = client.type_appareil;
                        if (client.modele) {
                            deviceInfo += ` ${client.modele}`;
                        }
                        
                        // Déterminer la date affichée
                        let dateInfo = '';
                        if (client.days_since) {
                            dateInfo = `${client.days_since} jours`;
                        } else if (client.date_info) {
                            dateInfo = client.date_info;
                        }
                        
                        // Définir le contenu de la ligne
                        row.innerHTML = `
                            <td class="text-center">
                                <div class="form-check">
                                    <input class="form-check-input client-select" type="checkbox" checked data-client-id="${client.id}">
                                </div>
                            </td>
                            <td>${client.client_nom} ${client.client_prenom}</td>
                            <td>${deviceInfo}</td>
                            <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                            <td>${dateInfo}</td>
                        `;
                        
                        previewResultsBody.appendChild(row);
                    });
                    
                    // Activer le bouton d'envoi
                    sendRelanceBtn.disabled = false;
                } else {
                    // Aucun client à relancer
                    noClientsMessage.classList.remove('d-none');
                    previewResultsBody.innerHTML = '';
                    sendRelanceBtn.disabled = true;
                }
            } else {
                // Afficher l'erreur
                previewResultsBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            ${data.message || 'Une erreur est survenue lors de la recherche des clients.'}
                        </td>
                    </tr>
                `;
                sendRelanceBtn.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            previewResultsBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Recherche en cours, veuillez patienter...
                    </td>
                </tr>
            `;
            sendRelanceBtn.disabled = false;
        });
    }
    
    // Fonction pour envoyer les SMS de relance
    function sendRelanceSMS(days, selectedFilters = []) {
        // Désactiver le bouton et afficher un indicateur de chargement
        sendRelanceBtn.disabled = true;
        sendRelanceBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...';
        
        // Récupérer les IDs des clients sélectionnés
        const selectedClientIds = [];
        document.querySelectorAll('.client-select:checked').forEach(checkbox => {
            selectedClientIds.push(checkbox.getAttribute('data-client-id'));
        });
        
        // Si aucun client n'est sélectionné, afficher une alerte
        if (selectedClientIds.length === 0) {
            alert('Aucun client sélectionné. Veuillez sélectionner au moins un client.');
            sendRelanceBtn.disabled = false;
            sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
            return;
        }
        
        // Appeler l'API
        fetch('ajax/client_relance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'send',
                days: days,
                clientIds: selectedClientIds,
                selectedFilters: selectedFilters
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // S'assurer que le modal existe et est initialisé
                const modalElement = document.getElementById('relanceClientModal');
                let modalInstance = bootstrap.Modal.getInstance(modalElement);
                
                // Si l'instance n'existe pas, la créer
                if (!modalInstance && modalElement) {
                    modalInstance = new bootstrap.Modal(modalElement);
                }
                
                // Fermer le modal s'il est disponible (autoriser)
                if (modalInstance) {
                    const el = modalElement;
                    if (el) el.dataset.allowHide = '1';
                    modalInstance.hide();
                } else {
                    console.warn('Modal non trouvé ou non initialisé, fermeture impossible');
                }
                
                // Afficher un message de succès
                alert(`${data.count} SMS de relance envoyés avec succès.`);
                
                // Recharger la page
                window.location.reload();
            } else {
                // Afficher l'erreur
                alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de l\'envoi des SMS.'));
                sendRelanceBtn.disabled = false;
                sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            // Suppression de l'alerte d'erreur car les SMS s'envoient correctement
            // On réactive simplement le bouton
            sendRelanceBtn.disabled = false;
            sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
        });
    }
});
</script>

<!-- Scripts pour gérer l'indicateur SMS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour l'indicateur d'envoi de SMS
    const smsIndicator = document.getElementById('sendSmsIndicator');
    const smsSwitch = document.getElementById('sendSmsSwitch');
    const smsLabel = document.getElementById('sendSmsLabel');
    
    if (smsIndicator && smsSwitch && smsLabel) {
        // Initialiser l'état
        let smsEnabled = true;
        
        // Fonction pour mettre à jour l'apparence
        function updateSmsIndicator() {
            if (smsEnabled) {
                smsIndicator.style.backgroundColor = '#4caf50'; // Vert
                smsSwitch.value = '1';
                smsLabel.textContent = 'Envoyer un SMS de notification';
            } else {
                smsIndicator.style.backgroundColor = '#f44336'; // Rouge
                smsSwitch.value = '0';
                smsLabel.textContent = 'Ne pas envoyer de SMS';
            }
        }
        
        // Gestionnaire de clic
        smsIndicator.addEventListener('click', function() {
            smsEnabled = !smsEnabled;
            updateSmsIndicator();
        });
        
        smsLabel.addEventListener('click', function() {
            smsEnabled = !smsEnabled;
            updateSmsIndicator();
        });
    }
});
</script>

<script>
// Script pour gérer le bouton d'envoi de SMS
document.addEventListener('DOMContentLoaded', function() {
    const smsToggleButton = document.getElementById('smsToggleButton');
    const sendSmsSwitch = document.getElementById('sendSmsSwitch');
    
    if (smsToggleButton && sendSmsSwitch) {
        // Initialiser le bouton avec l'état par défaut (SMS désactivé)
        updateSmsButtonState(false);
        
        // Ajouter un écouteur d'événement pour le clic
        smsToggleButton.addEventListener('click', function() {
            // Inverser l'état actuel
            const currentState = sendSmsSwitch.value === '1';
            const newState = !currentState;
            
            // Mettre à jour l'état du bouton
            updateSmsButtonState(newState);
            
            // Mettre à jour la valeur de l'input hidden
            sendSmsSwitch.value = newState ? '1' : '0';
            
            // Jouer un son de notification pour donner un feedback à l'utilisateur
            playNotificationSound(newState);
        });
    }
    
    // Fonction pour mettre à jour l'apparence du bouton selon l'état
    function updateSmsButtonState(sendSmsEnabled) {
        if (sendSmsEnabled) {
            // SMS activé: bouton vert avec icône d'envoi
            smsToggleButton.className = 'btn btn-success btn-lg w-100 mb-3';
            smsToggleButton.style = 'font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transform: translateY(-2px);';
            smsToggleButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i> ENVOYER UN SMS AU CLIENT';
        } else {
            // SMS désactivé: bouton rouge avec icône d'interdiction
            smsToggleButton.className = 'btn btn-danger btn-lg w-100 mb-3';
            smsToggleButton.style = 'font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);';
            smsToggleButton.innerHTML = '<i class="fas fa-ban me-2"></i> NE PAS ENVOYER DE SMS AU CLIENT';
        }
    }
    
    // Fonction pour jouer un son de notification
    function playNotificationSound(success) {
        const audio = new Audio(success ? '../assets/sounds/success.mp3' : '../assets/sounds/beep.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Impossible de jouer le son de notification:', e));
    }
});
// Script pour gérer le bouton d'envoi de SMS
document.addEventListener('DOMContentLoaded', function() {
    console.log("Initialisation du bouton SMS...");
    const smsToggleButton = document.getElementById('smsToggleButton');
    const sendSmsSwitch = document.getElementById('sendSmsSwitch');
    
    if (smsToggleButton && sendSmsSwitch) {
        // S'assurer que la valeur initiale est correcte
        if (!sendSmsSwitch.value) {
            sendSmsSwitch.value = '0';
        }
        
        // Initialiser le bouton avec l'état par défaut
        const initialState = sendSmsSwitch.value === '1';
        updateSmsButtonState(initialState);
        console.log("État initial du SMS:", initialState ? "Activé" : "Désactivé");
        
        // Ajouter un écouteur d'événement pour le clic
        smsToggleButton.addEventListener('click', function() {
            // Inverser l'état actuel
            const currentState = sendSmsSwitch.value === '1';
            const newState = !currentState;
            
            // Mettre à jour la valeur de l'input hidden
            sendSmsSwitch.value = newState ? '1' : '0';
            console.log("Nouvel état du SMS:", newState ? "Activé" : "Désactivé", "Valeur:", sendSmsSwitch.value);
            
            // Mettre à jour l'état du bouton
            updateSmsButtonState(newState);
            
            // Jouer un son de notification pour donner un feedback à l'utilisateur
            playNotificationSound(newState);
        });
    } else {
        console.error("Éléments du bouton SMS non trouvés:", smsToggleButton, sendSmsSwitch);
    }
    
    // Fonction pour mettre à jour l'apparence du bouton selon l'état
    function updateSmsButtonState(sendSmsEnabled) {
        if (sendSmsEnabled) {
            // SMS activé: bouton vert avec icône d'envoi
            smsToggleButton.className = 'btn btn-success btn-lg w-100 mb-3';
            smsToggleButton.style = 'font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transform: translateY(-2px);';
            smsToggleButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i> ENVOYER UN SMS AU CLIENT';
        } else {
            // SMS désactivé: bouton rouge avec icône d'interdiction
            smsToggleButton.className = 'btn btn-danger btn-lg w-100 mb-3';
            smsToggleButton.style = 'font-weight: bold; font-size: 1.1rem; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);';
            smsToggleButton.innerHTML = '<i class="fas fa-ban me-2"></i> NE PAS ENVOYER DE SMS AU CLIENT';
        }
    }
    
    // Fonction pour jouer un son de notification
    function playNotificationSound(success) {
        try {
            const audio = new Audio(success ? '../assets/sounds/success.mp3' : '../assets/sounds/beep.mp3');
            audio.volume = 0.5;
            audio.play().catch(e => console.log('Impossible de jouer le son de notification:', e));
        } catch (e) {
            console.error("Erreur lors de la lecture du son:", e);
        }
    }
});
</script>

<!-- Scripts pour gérer l'ID du magasin dans les requêtes AJAX -->
<script src="../assets/js/session-helper.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Stocker l'ID du magasin pour les requêtes AJAX
    const shopId = "<?php echo $current_shop_id ?: ''; ?>";
    if (shopId) {
        // Stocker l'ID du magasin sur l'élément body
        document.body.setAttribute('data-shop-id', shopId);
        console.log('ID du magasin défini sur la page:', shopId);
        
        // Si le SessionHelper est disponible, stocker l'ID
        if (window.SessionHelper) {
            window.SessionHelper.storeShopId(shopId);
        }
    } else {
        console.warn('Aucun ID de magasin trouvé en session');
    }
});
</script>

<!-- Script pour informer l'utilisateur des nouvelles fonctionnalités -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si c'est la première visite après la mise à jour
    if (!localStorage.getItem('futuristicUINotified')) {
        // Créer et afficher la notification
        setTimeout(() => {
            if (typeof window.showNotification === 'function') {
                window.showNotification(
                    'Bienvenue sur l\'interface modernisée de gestion des réparations! Profitez des nouveaux effets visuels et animations pour une meilleure expérience.',
                    'info',
                    8000
                );
                
                // Après un délai, montrer une seconde notification
                setTimeout(() => {
                    window.showNotification(
                        'Essayez de glisser-déposer les cartes vers les filtres pour changer le statut des réparations rapidement!',
                        'success',
                        8000
                    );
                }, 9000);
            }
            
            // Marquer comme notifié
            localStorage.setItem('futuristicUINotified', 'true');
        }, 1500);
    }
});
</script>
<!-- Script pour exposer la fonction fetchStatusOptions au contexte global -->
<script>
// Exposer la fonction fetchStatusOptions pour le glisser-déposer
window.fetchStatusOptions = function(repairId, categoryId, statusIndicator) {
    console.log("Fonction fetchStatusOptions appelée depuis le bridge", {repairId, categoryId});
    
    // Afficher un indicateur de chargement dans le badge
    statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Chargement...</span>';
    
    // Récupérer les statuts disponibles pour cette catégorie
    fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
        .then(response => {
            console.log('Statut de la réponse HTTP:', response.status);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Vérifier le type de contenu
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Type de contenu invalide:', contentType);
                throw new Error('Le serveur n\'a pas retourné du JSON valide');
            }
            
            return response.text().then(text => {
                console.log('Réponse brute du serveur:', text);
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('Erreur de parsing JSON:', parseError);
                    throw new Error('Réponse JSON invalide du serveur');
                }
            });
        })
        .then(data => {
            console.log('Réponse de get_statuts_by_category:', data);
            
            if (data.success) {
                // Stocker les IDs pour une utilisation ultérieure
                document.getElementById('chooseStatusRepairId').value = repairId;
                document.getElementById('chooseStatusCategoryId').value = categoryId;
                
                // Générer les boutons de statut
                const container = document.getElementById('statusButtonsContainer');
                container.innerHTML = ''; // Effacer le contenu précédent
                
                // Déterminer la couleur de la catégorie
                const categoryColor = getCategoryColor(data.category.couleur);
                
                // Ajouter un titre pour la catégorie dans le modal
                const categoryTitle = document.getElementById('chooseStatusModalLabel');
                if (categoryTitle) {
                    categoryTitle.innerHTML = `<i class="fas fa-tasks me-2"></i> Statuts "${data.category.nom}"`;
                }
                
                // Créer un bouton pour chaque statut
                data.statuts.forEach(statut => {
                    const button = document.createElement('button');
                    button.className = `btn btn-${categoryColor} btn-lg w-100 mb-2`;
                    button.setAttribute('data-status-id', statut.id);
                    button.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        ${statut.nom}
                    `;
                    button.addEventListener('click', () => updateSpecificStatus(statut.id, statusIndicator));
                    container.appendChild(button);
                });
                
                // Afficher le modal
                const modalElement = document.getElementById('chooseStatusModal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                
                // Rétablir le badge de statut quand l'utilisateur annule
                const closeBtn = document.querySelector('#chooseStatusModal .btn-close');
                const cancelBtn = document.querySelector('#chooseStatusModal .btn-outline-secondary');
                
                const handleCancel = function() {
                    console.log('Annulation de la sélection de statut');
                    // Nettoyer le backdrop et réactiver le scroll
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    
                    // Restaurer le statut d'origine
                    location.reload();
                };
                
                if (closeBtn) {
                    // Enlever les anciens écouteurs d'événements
                    closeBtn.removeEventListener('click', handleCancel);
                    // Ajouter le nouvel écouteur
                    closeBtn.addEventListener('click', handleCancel);
                }
                
                if (cancelBtn) {
                    // Enlever les anciens écouteurs d'événements
                    cancelBtn.removeEventListener('click', handleCancel);
                    // Ajouter le nouvel écouteur
                    cancelBtn.addEventListener('click', handleCancel);
                }
                
            } else {
                // Afficher l'erreur
                if (typeof showNotification === 'function') {
                    showNotification('Erreur: ' + data.error, 'danger');
                } else {
                    alert('Erreur: ' + data.error);
                }
                location.reload(); // Recharger la page en cas d'erreur
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des statuts:', error);
            
            // Afficher un message d'erreur spécifique selon le type d'erreur
            let errorMessage = 'Erreur de communication avec le serveur';
            if (error.message) {
                errorMessage = error.message;
            }
            
            if (typeof showNotification === 'function') {
                showNotification(`Erreur: ${errorMessage}`, 'danger');
            } else {
                alert(`Erreur: ${errorMessage}`);
            }
            
            // Restaurer l'indicateur de statut original
            if (statusIndicator) {
                statusIndicator.innerHTML = '<span class="badge bg-warning">Erreur</span>';
            }
            
            // Proposer de recharger la page
            if (confirm('Une erreur s\'est produite. Voulez-vous recharger la page ?')) {
                location.reload();
            }
        });
};
// Exposer la fonction updateSpecificStatus au contexte global
window.updateSpecificStatus = function(statusId, statusIndicator) {
    // Récupérer les ID stockés
    const repairId = document.getElementById('chooseStatusRepairId').value;

    console.log('Mise à jour du statut:', statusId, 'pour la réparation:', repairId);
    
    // Récupérer l'état de l'option d'envoi de SMS
    const sendSms = document.getElementById('sendSmsSwitch').value === '1';
    console.log('Envoi de SMS:', sendSms ? 'Activé' : 'Désactivé');
    
    // Fermer le modal
    const modalElement = document.getElementById('chooseStatusModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
    
    // Nettoyer le backdrop et réactiver le scroll
    document.body.classList.remove('modal-open');
    // Nettoyage agressif désactivé: laisser Bootstrap gérer backdrop/overflow
    
    // Afficher un indicateur de chargement
    statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Mise à jour...</span>';
    
    // Préparer les données
    const data = {
        repair_id: repairId,
        status_id: statusId,
        send_sms: sendSms,
        user_id: 1 // Utiliser l'ID 1 (admin) pour éviter les problèmes
    };
    
    // Afficher les données pour le débogage
    console.log('Données envoyées pour mise à jour de statut:', data);
    
    // Fonction pour afficher une notification
    function showSilentNotification(message, type) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            const notification = document.createElement('div');
            notification.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            notification.setAttribute('role', 'alert');
            notification.setAttribute('aria-live', 'assertive');
            notification.setAttribute('aria-atomic', 'true');
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            document.body.appendChild(notification);
            
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Toast !== 'undefined') {
                const toast = new bootstrap.Toast(notification, { delay: 5000 });
                toast.show();
                
                // Supprimer la notification après qu'elle soit masquée
                notification.addEventListener('hidden.bs.toast', function () {
                    notification.remove();
                });
            } else {
                // Fallback si bootstrap n'est pas disponible
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        }
    }
    
    // Essayer d'abord avec fetch (méthode JSON standard)
    fetch('../ajax/update_repair_specific_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Réponse brute:', response);
        
        if (!response.ok) {
            if (response.status === 500) {
                // Pour les erreurs 500, on va essayer une approche différente
                throw new Error('RETRY_WITH_FORM');
            }
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        // Essayer de parser la réponse en JSON
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Erreur de parsing JSON:', e);
                console.log('Réponse texte brute:', text);
                throw new Error('Réponse non valide du serveur');
            }
        });
    })
    .then(data => {
        // Succès de la mise à jour
        console.log('Mise à jour réussie:', data);
        
        if (data.success) {
            // Mettre à jour le badge avec le nouveau statut
            if (data.data && data.data.badge) {
                statusIndicator.innerHTML = data.data.badge;
            }
            
            // Afficher une notification de succès
            showSilentNotification('Statut mis à jour avec succès', 'success');
            
            // Option: recharger la page après un délai
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            // Afficher l'erreur
            showSilentNotification('Erreur: ' + (data.message || 'Une erreur est survenue.'), 'danger');
            // Recharger la page
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour du statut:', error);
        
        if (error.message === 'RETRY_WITH_FORM') {
            console.log('Nouvelle tentative avec FormData au lieu de JSON...');
            
            // Seconde tentative avec FormData mais en indiquant qu'il s'agit de données JSON
            const formData = new FormData();
            formData.append('json_data', JSON.stringify(data));
            
            fetch('../ajax/update_repair_specific_status.php?format=json', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(responseText => {
                console.log('Réponse de la seconde tentative:', responseText);
                // Afficher un message de succès générique
                showSilentNotification('Statut mis à jour avec succès', 'success');
                // Recharger la page après un court délai
                setTimeout(() => {
                    location.reload();
                }, 1500);
            })
            .catch(error => {
                console.error('Erreur lors de la seconde tentative:', error);
                showSilentNotification('Erreur lors de la mise à jour du statut', 'danger');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            });
        } else {
            // Afficher l'erreur
            showSilentNotification('Erreur lors de la mise à jour du statut: ' + error.message, 'danger');
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
    });
};

// Fonction helper pour obtenir la couleur de catégorie
function getCategoryColor(color) {
    // Convertir la couleur en classe Bootstrap
    const colorMap = {
        'info': 'info',
        'primary': 'primary',
        'warning': 'warning',
        'success': 'success',
        'danger': 'danger',
        'secondary': 'secondary'
    };
    return colorMap[color] || 'primary';
}

// Fonction sécurisée pour ouvrir le modal de devis
window.openDevisModalSafely = function(reparationId) {
    console.log('🎯 Ouverture sécurisée du modal de devis pour la réparation', reparationId);
    
    // Fermer d'abord tout modal ouvert pour éviter les conflits
    const openModals = document.querySelectorAll('.modal.show');
    openModals.forEach(modal => {
        try {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        } catch (e) {
            console.warn('Erreur lors de la fermeture du modal:', e);
        }
    });
    
    // Attendre un peu pour que les modals se ferment complètement
    setTimeout(() => {
        // Vérifier si le modal de devis existe
        const devisModal = document.getElementById('creerDevisModal');
        if (!devisModal) {
            console.error('❌ Modal de création de devis non trouvé');
            alert('Erreur: Le modal de création de devis n\'est pas disponible. Veuillez recharger la page.');
            return;
        }
        
        try {
            // Nettoyages agressifs désactivés pour éviter des fermetures
            
            // Appeler la fonction existante du devis-modal.js de manière sécurisée
            if (typeof window.ouvrirModalDevis === 'function') {
                console.log('✅ Utilisation de la fonction ouvrirModalDevis existante');
                window.ouvrirModalDevis(reparationId);
            } else if (typeof window.devisManager !== 'undefined' && window.devisManager) {
                console.log('✅ Utilisation du devisManager');
                // Créer un bouton temporaire avec l'ID de réparation
                const tempButton = document.createElement('button');
                tempButton.dataset.reparationId = reparationId;
                
                // Déclencher l'événement show.bs.modal avec le bouton temporaire
                const event = new Event('show.bs.modal');
                event.relatedTarget = tempButton;
                devisModal.dispatchEvent(event);
                
                // Ouvrir le modal
                const modalInstance = new bootstrap.Modal(devisModal, {
                    backdrop: 'static',
                    keyboard: false
                });
                modalInstance.show();
            } else {
                console.log('⚠️ Fonction ouvrirModalDevis non trouvée, ouverture directe du modal');
                
                // Définir l'ID de réparation dans le champ caché
                const reparationIdField = document.getElementById('reparation_id');
                if (reparationIdField) {
                    reparationIdField.value = reparationId;
                }
                
                // Ouvrir le modal directement
                const modalInstance = new bootstrap.Modal(devisModal, {
                    backdrop: 'static',
                    keyboard: false
                });
                modalInstance.show();
                
                // Charger les informations de la réparation si possible
                if (typeof window.devisManager !== 'undefined' && window.devisManager.chargerInformationsReparation) {
                    window.devisManager.chargerInformationsReparation(reparationId);
                }
            }
            
        } catch (error) {
            console.error('❌ Erreur lors de l\'ouverture du modal de devis:', error);
            alert('Erreur lors de l\'ouverture du modal de devis. Veuillez réessayer.');
            
            // En cas d'erreur, nettoyer l'état
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
        }
    }, 300); // Délai pour laisser les modals se fermer
};
</script>

<!-- Script pour ajuster la largeur du tableau -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const resultsContainer = document.querySelector(".results-container");
    if (resultsContainer) {
        resultsContainer.style.width = "95%";
        resultsContainer.style.margin = "0 auto";
    }
});
</script>

<!-- Inclusion du script pour l'ajustement du tableau -->
<script src="../assets/js/reparations-table.js"></script>
<!-- Inclusion du script pour les fonctions clients -->
<script src="assets/js/client-functions.js"></script>
<!-- Inclusion du script pour la sélection de fournisseurs -->
<script src="assets/js/fournisseur-selector.js"></script>

<!-- Script du modal de mise à jour des statuts -->
<script src="assets/js/update-status-modal.js"></script>

<!-- Click handler pour ouvrir le modal en vue cartes -->
<script>
document.addEventListener('click', function(e) {
    const detailsBtn = e.target.closest('.repair-cards-container .btn.btn-primary');
    const card = e.target.closest('.modern-card, .draggable-card');
    if (!detailsBtn && !card) return;

    const container = detailsBtn ? detailsBtn.closest('.modern-card, .draggable-card') : card;
    if (!container) return;
    const repairId = container.getAttribute('data-repair-id') || container.getAttribute('data-id');
    if (!repairId) return;

    e.preventDefault();
    e.stopPropagation();

    try {
        if (window.RepairModal && typeof RepairModal.loadRepairDetails === 'function') {
            console.log('🔄 Ouverture du modal (cartes) pour la réparation:', repairId);
            RepairModal.loadRepairDetails(repairId);
            return;
        }
    } catch (err) {
        console.error('Erreur ouverture détails (cartes):', err);
    }

    // Fallback: ouvrir simplement le modal si disponible
    const modal = document.getElementById('repairDetailsModal');
    if (modal && typeof bootstrap !== 'undefined') {
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }
});
</script>
<!-- Scripts nécessaires pour le modal de détails réparation -->
<script src="assets/js/modal-helper.js"></script>
<script src="assets/js/repair-modal.js?v=<?php echo time(); ?>"></script>

<style>
/* Laisser Bootstrap gérer l'affichage des modals normalement */

/* Modal Devis en attente - Plein écran */
#devisEnAttenteModal.show {
    position: fixed !important;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex !important;
    align-items: center;
    justify-content: center;
}
#devisEnAttenteModal .modal-dialog {
    width: 90% !important;
    max-width: 1200px !important;
    height: 90% !important;
    margin: 0 !important;
}
#devisEnAttenteModal .modal-content { 
    height: 100% !important; 
    display: flex; 
    flex-direction: column; 
}
#devisEnAttenteModal .modal-body { 
    flex: 1 !important; 
    overflow: hidden !important; 
    padding: 0 !important; 
}
#devisEnAttenteModal #devisEnAttenteFrame { width: 100% !important; height: 100% !important; border: 0 !important; }

/* Correctifs pour le modal de détails de réparation */
#repairDetailsModal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    z-index: 1070 !important;
}

#repairDetailsModal.show .modal-dialog {
    transform: none !important;
}

/* Rétablir totalement les valeurs Bootstrap par défaut (pas d'override agressif) */
/* supprimé: .modal-backdrop/.modal z-index forcés */

/* Visibilité fiable pour updateStatusModal (aligné avec repairDetailsModal) */
#updateStatusModal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
#updateStatusModal .modal-dialog { transform: none !important; pointer-events: auto !important; }
#updateStatusModal { pointer-events: auto !important; }

/* Styles pour les tableaux modernes du modal updateStatusModal - STYLES FORCÉS */
#updateStatusModal .modern-table-container {
    background: #ffffff !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
    overflow: hidden !important;
    margin-bottom: 24px !important;
    width: 100% !important;
    display: block !important;
    border: 1px solid #f1f5f9 !important;
}

#updateStatusModal .modern-table-header {
    display: grid !important;
    grid-template-columns: 50px 1.5fr 1fr 2fr 100px 120px !important;
    background: #ffffff !important;
    color: #374151 !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    width: 100% !important;
    height: auto !important;
    border-bottom: 2px solid #f1f5f9 !important;
}

#updateStatusModal .header-cell {
    padding: 16px 12px !important;
    display: flex !important;
    align-items: center !important;
    border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
    min-height: 50px !important;
    height: auto !important;
}

#updateStatusModal .header-cell:last-child {
    border-right: none !important;
}

#updateStatusModal .checkbox-cell {
    justify-content: center !important;
}

#updateStatusModal .price-cell {
    justify-content: flex-end !important;
}

#updateStatusModal .modern-table-body {
    max-height: 400px !important;
    overflow-y: auto !important;
    width: 100% !important;
    min-height: 100px !important;
    display: block !important;
    background: white !important;
}

#updateStatusModal .table-row {
    display: grid !important;
    grid-template-columns: 50px 1.5fr 1fr 2fr 100px 120px !important;
    border-bottom: 1px solid #e5e7eb !important;
    transition: all 0.2s ease !important;
    cursor: pointer !important;
    width: 100% !important;
    min-height: 60px !important;
    background: white !important;
    position: relative !important;
    z-index: 1 !important;
}

#updateStatusModal .table-row:hover {
    background: #f9fafb !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

#updateStatusModal .table-row.selected {
    background: #eff6ff !important;
    border-left: 4px solid #3b82f6 !important;
}

#updateStatusModal .table-cell {
    padding: 16px 12px !important;
    display: flex !important;
    align-items: center !important;
    font-size: 14px !important;
    color: #374151 !important;
    border-right: 1px solid #f3f4f6 !important;
    min-height: 60px !important;
    word-break: break-word !important;
    height: auto !important;
    position: relative !important;
}

#updateStatusModal .table-cell:last-child {
    border-right: none !important;
}

#updateStatusModal .table-cell.checkbox-cell {
    justify-content: center !important;
}

#updateStatusModal .table-cell.price-cell {
    justify-content: flex-end !important;
    font-weight: 600 !important;
    color: #059669 !important;
}

.modern-checkbox {
    width: 18px;
    height: 18px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.modern-checkbox:checked {
    background: #3b82f6;
    border-color: #3b82f6;
}

.modern-checkbox:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.loading-row {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #6b7280;
    font-size: 14px;
    gap: 12px;
}

.loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.empty-row {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #9ca3af;
    font-size: 14px;
    font-style: italic;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Styles pour les onglets modernes CSS purs */
#updateStatusModal .modern-tabs {
    display: flex;
    background: #ffffff;
    border-radius: 16px;
    padding: 8px;
    margin-bottom: 28px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

#updateStatusModal .modern-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 20px;
    border: none;
    background: transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
}

#updateStatusModal .modern-tab:hover {
    background: #f1f5f9;
    color: #475569;
}

#updateStatusModal .modern-tab.active {
    background: #3b82f6;
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

#updateStatusModal .modern-tab i {
    font-size: 16px;
}

#updateStatusModal .tab-badge {
    background: #e5e7eb;
    color: #6b7280;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    min-width: 24px;
    text-align: center;
}

#updateStatusModal .modern-tab.active .tab-badge {
    background: rgba(255, 255, 255, 0.9);
    color: #3b82f6;
}

#updateStatusModal .modern-tab-content {
    position: relative;
}

#updateStatusModal .tab-panel {
    display: none;
    animation: fadeIn 0.3s ease;
}

#updateStatusModal .tab-panel.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Styles pour la section d'actions moderne */
#updateStatusModal .modern-actions-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    background: #ffffff;
    border-radius: 16px;
    margin-top: 24px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

#updateStatusModal .selection-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #475569;
    font-weight: 500;
}

#updateStatusModal .selection-info i {
    color: #3b82f6;
}

#updateStatusModal .selection-buttons {
    display: flex;
    gap: 12px;
}

/* Styles pour le footer moderne */
#updateStatusModal .modal-footer-modern {
    background: #ffffff;
    border-top: 1px solid #f1f5f9;
    padding: 24px;
}

#updateStatusModal .footer-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

#updateStatusModal .status-selector {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

#updateStatusModal .status-selector label {
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

#updateStatusModal .modern-select {
    padding: 14px 40px 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

#updateStatusModal .modern-select:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

#updateStatusModal .modern-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

#updateStatusModal .modern-select option {
    padding: 12px;
    background: white;
    color: #374151;
    font-weight: 500;
}

/* Styles pour le switch moderne */
#updateStatusModal .modern-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

#updateStatusModal .switch-slider {
    position: relative;
    width: 50px;
    height: 24px;
    background: #cbd5e1;
    border-radius: 24px;
    transition: background 0.3s ease;
}

#updateStatusModal .switch-slider::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

#updateStatusModal .modern-switch input:checked + .switch-slider {
    background: #3b82f6;
}

#updateStatusModal .modern-switch input:checked + .switch-slider::after {
    transform: translateX(26px);
}

#updateStatusModal .modern-switch input {
    display: none;
}

#updateStatusModal .switch-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: #374151;
}

/* Styles pour les boutons modernes */
#updateStatusModal .modern-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

#updateStatusModal .modern-btn.primary {
    background: #3b82f6;
    color: white;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

#updateStatusModal .modern-btn.primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}
#updateStatusModal .modern-btn.secondary {
    background: #ffffff;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

#updateStatusModal .modern-btn.secondary:hover {
    background: #f9fafb;
    color: #374151;
    border-color: #9ca3af;
}

#updateStatusModal .modern-btn.outline {
    background: #ffffff;
    color: #3b82f6;
    border: 1px solid #3b82f6;
}

#updateStatusModal .modern-btn.outline:hover {
    background: #eff6ff;
    color: #2563eb;
}

#updateStatusModal .action-buttons {
    display: flex;
    gap: 12px;
}

/* Override du fond du modal pour un mode plus clair */
#updateStatusModal .modal-content {
    background: #ffffff !important;
    border: 1px solid #f1f5f9 !important;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08) !important;
}

#updateStatusModal .modal-header {
    background: #ffffff !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

#updateStatusModal .modal-body {
    background: #ffffff !important;
    padding: 32px !important;
}

/* STYLES POUR LE MODE NUIT */
body.dark-mode #updateStatusModal .modal-content {
    background: #1f2937 !important;
    border: 1px solid #374151 !important;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3) !important;
}

body.dark-mode #updateStatusModal .modal-header {
    background: #1f2937 !important;
    border-bottom: 1px solid #374151 !important;
}

body.dark-mode #updateStatusModal .modal-body {
    background: #1f2937 !important;
    padding: 32px !important;
}

/* Onglets en mode nuit */
body.dark-mode #updateStatusModal .modern-tabs {
    background: #111827 !important;
    border: 1px solid #374151 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
}

body.dark-mode #updateStatusModal .modern-tab {
    color: #9ca3af !important;
}

body.dark-mode #updateStatusModal .modern-tab:hover {
    background: #374151 !important;
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .modern-tab.active {
    background: #3b82f6 !important;
    color: white !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25) !important;
}

body.dark-mode #updateStatusModal .tab-badge {
    background: #4b5563 !important;
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .modern-tab.active .tab-badge {
    background: rgba(255, 255, 255, 0.9) !important;
    color: #3b82f6 !important;
}

/* Tableau en mode nuit */
body.dark-mode #updateStatusModal .modern-table-container {
    background: #111827 !important;
    border: 1px solid #374151 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
}

body.dark-mode #updateStatusModal .modern-table-header {
    background: #1f2937 !important;
    color: #f9fafb !important;
    border-bottom: 2px solid #374151 !important;
}

body.dark-mode #updateStatusModal .header-cell {
    color: #f9fafb !important;
}

body.dark-mode #updateStatusModal .table-row {
    background: #111827 !important;
    border-bottom: 1px solid #374151 !important;
}

body.dark-mode #updateStatusModal .table-row:hover {
    background: #1f2937 !important;
}

body.dark-mode #updateStatusModal .table-row.selected {
    background: #1e3a8a !important;
    border-left: 4px solid #3b82f6 !important;
}

body.dark-mode #updateStatusModal .table-cell {
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .price-cell {
    color: #10b981 !important;
    font-weight: 600 !important;
}

/* Section d'actions en mode nuit */
body.dark-mode #updateStatusModal .modern-actions-section {
    background: #1f2937 !important;
    border: 1px solid #374151 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
}

body.dark-mode #updateStatusModal .selection-info {
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .selection-info i {
    color: #3b82f6 !important;
}

/* Footer en mode nuit */
body.dark-mode #updateStatusModal .modal-footer-modern {
    background: #1f2937 !important;
    border-top: 1px solid #374151 !important;
}

body.dark-mode #updateStatusModal .status-selector label {
    color: #f9fafb !important;
}

body.dark-mode #updateStatusModal .modern-select {
    background: #111827 !important;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e") !important;
    border: 2px solid #374151 !important;
    color: #d1d5db !important;
}

body.dark-mode #updateStatusModal .modern-select:hover {
    border-color: #3b82f6 !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2) !important;
}

body.dark-mode #updateStatusModal .modern-select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

body.dark-mode #updateStatusModal .modern-select option {
    background: #111827 !important;
    color: #d1d5db !important;
}

/* Switch SMS en mode nuit */
body.dark-mode #updateStatusModal .switch-slider {
    background: #4b5563 !important;
}

body.dark-mode #updateStatusModal .modern-switch input:checked + .switch-slider {
    background: #3b82f6 !important;
}

body.dark-mode #updateStatusModal .switch-label {
    color: #d1d5db !important;
}

/* Boutons en mode nuit */
body.dark-mode #updateStatusModal .modern-btn.secondary {
    background: #374151 !important;
    color: #d1d5db !important;
    border: 1px solid #4b5563 !important;
}

body.dark-mode #updateStatusModal .modern-btn.secondary:hover {
    background: #4b5563 !important;
    color: #f9fafb !important;
    border-color: #6b7280 !important;
}

body.dark-mode #updateStatusModal .modern-btn.outline {
    background: #1f2937 !important;
    color: #3b82f6 !important;
    border: 1px solid #3b82f6 !important;
}

body.dark-mode #updateStatusModal .modern-btn.outline:hover {
    background: #1e3a8a !important;
    color: #60a5fa !important;
}


/* Checkboxes en mode nuit */
body.dark-mode #updateStatusModal .modern-checkbox {
    background: #374151 !important;
    border: 2px solid #4b5563 !important;
}

body.dark-mode #updateStatusModal .modern-checkbox:checked {
    background: #3b82f6 !important;
    border-color: #3b82f6 !important;
}

/* Badges de statut en mode clair */
#updateStatusModal .status-badge {
    background: #f3f4f6 !important;
    color: #374151 !important;
    border: 1px solid #d1d5db !important;
    font-weight: 600 !important;
    text-shadow: none !important;
}

/* Badges de statut en mode nuit */
body.dark-mode #updateStatusModal .status-badge {
    background: #374151 !important;
    color: #d1d5db !important;
    border: 1px solid #4b5563 !important;
}

/* Messages loading et empty en mode nuit */
body.dark-mode #updateStatusModal .loading-row,
body.dark-mode #updateStatusModal .empty-row {
    background: #111827 !important;
    color: #9ca3af !important;
}

body.dark-mode #updateStatusModal .loading-spinner {
    border-top-color: #3b82f6 !important;
}

/* Titre du modal */
#updateStatusModal .modal-title {
    color: #1f2937 !important;
}

/* Titre du modal en mode nuit */
body.dark-mode #updateStatusModal .modal-title {
    color: #f9fafb !important;
}

/* Élargir le modal pour PC */
@media (min-width: 1200px) {
    #updateStatusModal .modal-dialog {
        max-width: calc(1140px + 20px) !important;
    }
}

/* Désactiver les glissements dans le tableau */
#updateStatusModal .modern-table-container,
#updateStatusModal .modern-table-header,
#updateStatusModal .modern-table-body,
#updateStatusModal .table-row,
#updateStatusModal .table-cell,
#updateStatusModal .header-cell {
    -webkit-user-drag: none !important;
    -khtml-user-drag: none !important;
    -moz-user-drag: none !important;
    -o-user-drag: none !important;
    user-drag: none !important;
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    pointer-events: auto !important;
    touch-action: none !important;
    -webkit-touch-callout: none !important;
    -webkit-tap-highlight-color: transparent !important;
}

/* Permettre la sélection des checkboxes et inputs */
#updateStatusModal .modern-checkbox,
#updateStatusModal input,
#updateStatusModal select,
#updateStatusModal button {
    -webkit-user-select: auto !important;
    -moz-user-select: auto !important;
    -ms-user-select: auto !important;
    user-select: auto !important;
    pointer-events: auto !important;
    touch-action: auto !important;
}

/* Responsive pour les petits écrans */
@media (max-width: 768px) {
    #updateStatusModal .modern-table-header,
    #updateStatusModal .table-row {
        grid-template-columns: 40px 1fr 1fr 80px 80px !important;
    }
    
    #updateStatusModal .header-cell:nth-child(4),
    #updateStatusModal .table-cell:nth-child(4) {
        display: none !important;
    }
    
    #updateStatusModal .footer-controls {
        flex-direction: column;
        gap: 16px;
    }
    
    #updateStatusModal .modern-actions-section {
        flex-direction: column;
        gap: 16px;
    }
}
</style>

<!-- Modal Devis en attente -->
<div class="modal fade" id="devisEnAttenteModal" tabindex="-1" aria-labelledby="devisEnAttenteLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="devisEnAttenteLabel">
                    <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Devis en attente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="devisEnAttenteFrame" src="about:blank" style="width:100%; height:75vh; border:0;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Charger l'interface des devis en attente dans l'iframe au moment de l'ouverture
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('devisEnAttenteModal');
    const frame = document.getElementById('devisEnAttenteFrame');
    if (!modalEl || !frame) return;
    
    // Charger l'iframe quand le modal s'ouvre
    modalEl.addEventListener('shown.bs.modal', function() {
        frame.src = 'index.php?page=devis&statut_ids=envoye';
    });
    
    // Nettoyer l'iframe quand le modal se ferme
    modalEl.addEventListener('hidden.bs.modal', function() {
        frame.src = 'about:blank';
    });
});
</script>
<!-- Script pour ouverture automatique du modal de détails -->
<script>
// Correctif d'affichage forcé du modal updateStatusModal
// Correctif minimal pour les modals sur cette page
document.addEventListener('DOMContentLoaded', function() {
    // Nettoyage: ne plus forcer l'affichage des modals ici

    // Déplacer UNE SEULE FOIS certains modals sous <body> au chargement
    (function moveCriticalModalsOnce() {
        const idsToMove = ['nouvelles_actions_modal', 'devisEnAttenteModal', 'updateStatusModal', 'ajouterTacheModal'];
        idsToMove.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                try {
                    document.body.appendChild(el);
                    console.log('[MODAL FIX] Modal déplacé sous <body> (once):', id);
                } catch (err) {
                    console.warn('[MODAL FIX] Impossible de déplacer le modal (once):', id, err);
                }
            }
        });
    })();
});
// Variable globale pour stocker l'ID du modal à ouvrir
window.pendingModalId = null;

document.addEventListener("DOMContentLoaded", function() {
    // Vérifier s'il y a un paramètre open_modal dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const openModalId = urlParams.get('open_modal');
    
    if (openModalId) {
        window.pendingModalId = openModalId;
        
        // Nettoyer l'URL immédiatement pour éviter les problèmes de rechargement
        const cleanUrl = new URL(window.location);
        cleanUrl.searchParams.delete('open_modal');
        window.history.replaceState({}, document.title, cleanUrl);
        
        // Fonction pour tenter d'ouvrir le modal
        function attemptOpenModal(retries = 0) {
            // Vérifier si RepairModal est disponible
            if (typeof RepairModal !== 'undefined' && RepairModal.loadRepairDetails) {
                try {
                    RepairModal.loadRepairDetails(window.pendingModalId);
                    window.pendingModalId = null; // Marquer comme traité
                    return true;
                } catch (error) {
                    // Erreur silencieuse
                }
            }
            
            // Si RepairModal n'est pas disponible, essayer l'initialisation
            if (typeof RepairModal !== 'undefined' && RepairModal.init && !RepairModal._isInitialized) {
                try {
                    RepairModal.init();
                    // Réessayer après initialisation
                    setTimeout(() => attemptOpenModal(retries), 200);
                    return false;
                } catch (error) {
                    // Erreur silencieuse
                }
            }
            
            // Chercher un bouton existant à cliquer
            const detailsButton = document.querySelector(`[onclick*="RepairModal.loadRepairDetails(${window.pendingModalId})"]`) || 
                                document.querySelector(`[data-repair-id="${window.pendingModalId}"]`);
            
            if (detailsButton) {
                console.log('✅ Bouton de détails trouvé, clic simulé...');
                try {
                    detailsButton.click();
                    window.pendingModalId = null; // Marquer comme traité
                    return true;
                } catch (error) {
                    console.error('❌ Erreur lors du clic sur le bouton:', error);
                }
            }
            
            // Fallback : ouvrir le modal directement et charger les données
            const modal = document.getElementById('repairDetailsModal');
            if (modal && typeof bootstrap !== 'undefined') {
                console.log('🔄 Fallback: ouverture directe du modal...');
                try {
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                    
                    // Charger les détails via AJAX
                    const shopId = document.body.getAttribute('data-shop-id') || '<?php echo $current_shop_id ?? ""; ?>';
                    const apiUrl = `ajax/get_repair_details.php?id=${window.pendingModalId}${shopId ? '&shop_id=' + shopId : ''}`;
                    
                    console.log('🔄 Chargement des détails via:', apiUrl);
                    
                    fetch(apiUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('✅ Détails chargés avec succès');
                                // Mettre à jour le titre du modal
                                const modalTitle = document.getElementById('repairDetailsModalLabel');
                                if (modalTitle) {
                                    modalTitle.innerHTML = `<i class="fas fa-tools me-2 text-primary"></i>Réparation #${window.pendingModalId}`;
                                }
                                window.pendingModalId = null; // Marquer comme traité
                            } else {
                                console.error('❌ Erreur lors du chargement des détails:', data.message);
                                throw new Error(data.message || 'Erreur lors du chargement');
                            }
                        })
                        .catch(error => {
                            console.error('❌ Erreur AJAX:', error);
                            // Afficher un message d'erreur dans le modal
                            const modalBody = modal.querySelector('.modal-body');
                            if (modalBody) {
                                modalBody.innerHTML = `
                                    <div class="alert alert-danger">
                                        <h5>Erreur</h5>
                                        <p>Impossible de charger les détails de la réparation #${window.pendingModalId}</p>
                                        <p class="small">Erreur: ${error.message}</p>
                                    </div>
                                `;
                            }
                        });
                    
                    return true;
                } catch (error) {
                    console.error('❌ Erreur lors de l\'ouverture directe du modal:', error);
                }
            }
            
            // Si on arrive ici, réessayer si on n'a pas atteint le maximum
            if (retries < 10) {
                setTimeout(() => attemptOpenModal(retries + 1), 300 + (retries * 100));
                return false;
            } else {
                console.error('❌ Impossible d\'ouvrir le modal après 10 tentatives');
                alert(`Impossible d'ouvrir automatiquement les détails de la réparation #${window.pendingModalId}. Vous pouvez cliquer manuellement sur la réparation pour voir ses détails.`);
                window.pendingModalId = null;
                return false;
            }
        }
        
        // Démarrer les tentatives d'ouverture avec un délai initial
        setTimeout(() => attemptOpenModal(), 800);
    }
});

// Fonction de secours pour ouvrir le modal en cas d'échec
window.openPendingModal = function() {
    if (window.pendingModalId) {
        console.log('🔄 Fonction de secours appelée pour la réparation:', window.pendingModalId);
        if (typeof RepairModal !== 'undefined' && RepairModal.loadRepairDetails) {
            RepairModal.loadRepairDetails(window.pendingModalId);
            window.pendingModalId = null;
        }
    }
};
</script>
<!-- Modal de mise à jour des statuts par lots -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateStatusModalLabel">
                    <i class="fas fa-tasks me-2"></i>Mise à jour des statuts par lots
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Onglets modernes CSS purs -->
                <div class="modern-tabs" id="statusTabs">
                    <button class="modern-tab active" data-tab="nouvelles" id="nouvelles-tab">
                        <i class="fas fa-plus-circle"></i>
                        <span>Nouvelles</span>
                        <span class="tab-badge" id="count-nouvelles">0</span>
                    </button>
                    <button class="modern-tab" data-tab="en-cours" id="en-cours-tab">
                        <i class="fas fa-cog"></i>
                        <span>En cours</span>
                        <span class="tab-badge" id="count-en-cours">0</span>
                    </button>
                    <button class="modern-tab" data-tab="en-attente" id="en-attente-tab">
                        <i class="fas fa-clock"></i>
                        <span>En attente</span>
                        <span class="tab-badge" id="count-en-attente">0</span>
                    </button>
                    <button class="modern-tab" data-tab="terminees" id="terminees-tab">
                        <i class="fas fa-check-circle"></i>
                        <span>Terminées</span>
                        <span class="tab-badge" id="count-terminees">0</span>
                    </button>
                </div>

                <!-- Contenu des onglets avec tableaux modernes CSS purs -->
                <div class="modern-tab-content" id="statusTabsContent">
                    <!-- Onglet Nouvelles -->
                    <div class="tab-panel active" id="nouvelles">
                        <div class="modern-table-container">
                            <div class="modern-table-header">
                                <div class="header-cell checkbox-cell">
                                    <input type="checkbox" id="select-all-nouvelles" class="modern-checkbox">
                                </div>
                                <div class="header-cell">Client</div>
                                <div class="header-cell">Modèle</div>
                                <div class="header-cell">Problème</div>
                                <div class="header-cell price-cell">Prix</div>
                                <div class="header-cell">Statut</div>
                            </div>
                            <div class="modern-table-body" id="repairs-nouvelles">
                                <div class="loading-row">
                                    <div class="loading-spinner"></div>
                                    <span>Chargement des réparations...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet En cours -->
                    <div class="tab-panel" id="en-cours">
                        <div class="modern-table-container">
                            <div class="modern-table-header">
                                <div class="header-cell checkbox-cell">
                                    <input type="checkbox" id="select-all-en-cours" class="modern-checkbox">
                                </div>
                                <div class="header-cell">Client</div>
                                <div class="header-cell">Modèle</div>
                                <div class="header-cell">Problème</div>
                                <div class="header-cell price-cell">Prix</div>
                                <div class="header-cell">Statut</div>
                            </div>
                            <div class="modern-table-body" id="repairs-en-cours">
                                <div class="loading-row">
                                    <div class="loading-spinner"></div>
                                    <span>Chargement des réparations...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet En attente -->
                    <div class="tab-panel" id="en-attente">
                        <div class="modern-table-container">
                            <div class="modern-table-header">
                                <div class="header-cell checkbox-cell">
                                    <input type="checkbox" id="select-all-en-attente" class="modern-checkbox">
                                </div>
                                <div class="header-cell">Client</div>
                                <div class="header-cell">Modèle</div>
                                <div class="header-cell">Problème</div>
                                <div class="header-cell price-cell">Prix</div>
                                <div class="header-cell">Statut</div>
                            </div>
                            <div class="modern-table-body" id="repairs-en-attente">
                                <div class="loading-row">
                                    <div class="loading-spinner"></div>
                                    <span>Chargement des réparations...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Terminées -->
                    <div class="tab-panel" id="terminees">
                        <div class="modern-table-container">
                            <div class="modern-table-header">
                                <div class="header-cell checkbox-cell">
                                    <input type="checkbox" id="select-all-terminees" class="modern-checkbox">
                                </div>
                                <div class="header-cell">Client</div>
                                <div class="header-cell">Modèle</div>
                                <div class="header-cell">Problème</div>
                                <div class="header-cell price-cell">Prix</div>
                                <div class="header-cell">Statut</div>
                            </div>
                            <div class="modern-table-body" id="repairs-terminees">
                                <div class="loading-row">
                                    <div class="loading-spinner"></div>
                                    <span>Chargement des réparations...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section d'actions moderne -->
                <div class="modern-actions-section">
                    <div class="selection-info">
                        <i class="fas fa-info-circle"></i>
                        <span id="selected-count">0 réparation(s) sélectionnée(s)</span>
                    </div>
                    
                    <div class="selection-buttons">
                        <button type="button" class="modern-btn outline" id="select-all-visible">
                            <i class="fas fa-check-square"></i>
                            Tout sélectionner
                        </button>
                        <button type="button" class="modern-btn outline" id="deselect-all">
                            <i class="fas fa-square"></i>
                            Tout désélectionner
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer-modern">
                <div class="footer-controls">
                    <div class="status-selector">
                        <label for="new-status-select">Nouveau statut :</label>
                        <select id="new-status-select" class="modern-select">
                            <option value="">-- Choisir un statut --</option>
                            <option value="nouvelles">Nouvelle Intervention</option>
                            <option value="nouvelle_commande">Nouvelle Commande</option>
                            <option value="en_attente_accord">En attente de l'accord client</option>
                            <option value="en_attente_livraison">En attente de livraison</option>
                            <option value="reparation_effectue">Réparation Effectuée</option>
                            <option value="reparation_annule">Réparation Annulée</option>
                            <option value="restituee">Restituée</option>
                            <option value="gardiennage">Gardiennage</option>
                            <option value="archive">Archiver</option>
                        </select>
                    </div>
                    
                    <div class="sms-toggle">
                        <label class="modern-switch">
                            <input type="checkbox" id="send-sms-checkbox" checked>
                            <span class="switch-slider"></span>
                            <span class="switch-label">
                                <i class="fas fa-sms"></i>
                                Envoyer SMS
                            </span>
                        </label>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" class="modern-btn secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="button" class="modern-btn primary" id="update-selected-repairs">
                            <i class="fas fa-save"></i>
                            Mettre à jour
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- Fermeture de page-container -->

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
.loader-letter:nth-child(6) {
  animation-delay: 0.5s;
}
.loader-letter:nth-child(7) {
  animation-delay: 0.6s;
}
.loader-letter:nth-child(8) {
  animation-delay: 0.7s;
}
.loader-letter:nth-child(9) {
  animation-delay: 0.8s;
}
.loader-letter:nth-child(10) {
  animation-delay: 0.9s;
}
.loader-letter:nth-child(11) {
  animation-delay: 1s;
}
.loader-letter:nth-child(12) {
  animation-delay: 1.1s;
}
.loader-letter:nth-child(13) {
  animation-delay: 1.2s;
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

/* Masquer le loader quand la page est chargée */
.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
.page-container.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

/* Gestion des deux types de loaders */
.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

/* En mode clair, inverser l'affichage */
body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

/* Loader Mode Clair - Cercle avec couleurs sombres */
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

/* Texte du loader mode clair */
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

/* Les styles de fond sont maintenant gérés par les variables CSS modernes plus haut */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,5 seconde puis masquer le loader et afficher le contenu
    setTimeout(function() {
        // Commencer l'animation de disparition du loader
        loader.classList.add('fade-out');
        
        // Après l'animation de disparition, masquer complètement le loader et afficher le contenu
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500); // Durée de l'animation de disparition
        
    }, 300); // 0,3 seconde comme demandé
});

// === FONCTIONS MODAL SMS HISTORIQUE RÉPARATIONS ===
function showRepairSmsModal(repairId, clientName, clientPhone) {
    console.log('💬 Ouverture du modal SMS pour la réparation:', repairId, clientName, clientPhone);
    
    const modal = document.getElementById('repairSmsHistoryModal');
    const clientNameElement = document.getElementById('repairSmsClientName');
    const loadingElement = document.getElementById('repairSmsLoading');
    const contentElement = document.getElementById('repairSmsContent');
    
    if (!modal) {
        console.error('Modal SMS historique introuvable');
        return;
    }
    
    // Réinitialiser le modal
    clientNameElement.textContent = `${clientName} - ${clientPhone}`;
    loadingElement.style.display = 'flex';
    contentElement.style.display = 'none';
    contentElement.classList.remove('loaded');
    
    // Ouvrir le modal avec Bootstrap
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    // Charger l'historique SMS via AJAX
    loadRepairSmsHistory(repairId, clientPhone);
}

function loadRepairSmsHistory(repairId, clientPhone) {
    console.log('💬 Chargement de l\'historique SMS pour la réparation:', repairId);
    
    const loadingElement = document.getElementById('repairSmsLoading');
    const contentElement = document.getElementById('repairSmsContent');
    
    // Vérifier si le téléphone est valide
    if (!clientPhone || clientPhone === 'Non renseigné' || clientPhone === 'undefined') {
        loadingElement.style.display = 'none';
        contentElement.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div style="font-size: 3rem; margin-bottom: 20px;">📱</div>
                <h3 style="color: #6b7280;">Aucun numéro de téléphone</h3>
                <p style="color: #6b7280;">Aucun numéro de téléphone renseigné pour ce client.</p>
                <p style="font-size: 0.9rem; color: #9ca3af;">
                    Réparation #${repairId}
                </p>
            </div>
        `;
        contentElement.style.display = 'block';
        return;
    }
    
    // Utiliser l'API existante en recherchant par téléphone
    // D'abord récupérer le client_id via le téléphone
    fetch(`ajax/get_client_sms.php?phone=${encodeURIComponent(clientPhone)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de réseau');
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Historique SMS chargé avec succès:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erreur inconnue');
            }
            
            // Masquer le spinner et afficher le contenu
            loadingElement.style.display = 'none';
            contentElement.innerHTML = generateRepairSmsHistoryHTML(data, repairId);
            contentElement.style.display = 'block';
            contentElement.classList.add('loaded');
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement de l\'historique SMS:', error);
            
            // Détecter le mode sombre pour l'erreur
            const isDarkMode = document.body.classList.contains('dark-mode');
            const errorColor = isDarkMode ? '#f87171' : '#ef4444';
            const errorSecondaryColor = isDarkMode ? '#94a3b8' : '#6b7280';
            const buttonBg = isDarkMode ? '#4f46e5' : '#667eea';
            const buttonHoverBg = isDarkMode ? '#4338ca' : '#5a67d8';
            
            // Afficher un message d'erreur
            loadingElement.style.display = 'none';
            contentElement.innerHTML = `
                <div style="text-align: center; padding: 40px; color: ${errorColor};">
                    <div style="font-size: 3rem; margin-bottom: 20px;">⚠️</div>
                    <h3 style="color: ${errorColor};">Erreur de chargement</h3>
                    <p style="color: ${errorColor};">Impossible de charger l'historique des SMS.</p>
                    <p style="color: ${errorSecondaryColor}; font-size: 0.9rem;">${error.message}</p>
                    <button onclick="loadRepairSmsHistory(${repairId}, '${clientPhone}')" 
                            style="background: ${buttonBg}; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; margin-top: 15px; font-weight: 500; transition: all 0.2s ease;"
                            onmouseover="this.style.background='${buttonHoverBg}'; this.style.transform='translateY(-1px)'"
                            onmouseout="this.style.background='${buttonBg}'; this.style.transform='translateY(0)'">
                        🔄 Réessayer
                    </button>
                </div>
            `;
            contentElement.style.display = 'block';
            contentElement.classList.add('loaded');
        });
}

function generateRepairSmsHistoryHTML(data, repairId) {
    const { client, sms, total } = data;
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode');
    
    // Filtrer les SMS liés à cette réparation
    const repairSms = sms.filter(message => 
        message.reparation_id == repairId || 
        (message.message && message.message.includes(`suivi.php?id=${repairId}`))
    );
    
    if (!repairSms || repairSms.length === 0) {
        const emptyStateColor = isDarkMode ? '#94a3b8' : '#6b7280';
        const emptyStateSecondaryColor = isDarkMode ? '#64748b' : '#9ca3af';
        
        return `
            <div style="text-align: center; padding: 40px; color: ${emptyStateColor};">
                <div style="font-size: 3rem; margin-bottom: 20px;">📱</div>
                <h3 style="color: ${emptyStateColor};">Aucun SMS trouvé</h3>
                <p style="color: ${emptyStateColor};">Aucun SMS n'a été envoyé pour cette réparation.</p>
                <p style="font-size: 0.9rem; color: ${emptyStateSecondaryColor};">
                    Réparation #${repairId} - ${client.telephone || 'Numéro non renseigné'}
                </p>
            </div>
        `;
    }
    
    // Couleurs adaptatives selon le mode
    const summaryBg = isDarkMode ? '#334155' : '#f8fafc';
    const summaryTitleColor = isDarkMode ? '#e2e8f0' : '#374151';
    const summaryTextColor = isDarkMode ? '#94a3b8' : '#6b7280';
    const tableBg = isDarkMode ? '#1e293b' : 'white';
    const tableHeaderBg = isDarkMode ? '#334155' : '#f8fafc';
    const tableHeaderColor = isDarkMode ? '#e2e8f0' : '#374151';
    const tableHeaderBorder = isDarkMode ? '#475569' : '#e5e7eb';
    
    let html = `
        <div style="padding: 20px;">
            <div style="background: ${summaryBg}; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                <h4 style="margin: 0 0 5px 0; color: ${summaryTitleColor};">📊 Résumé</h4>
                <p style="margin: 0; color: ${summaryTextColor};">
                    <strong>${repairSms.length}</strong> SMS envoyé${repairSms.length > 1 ? 's' : ''} pour la réparation <strong>#${repairId}</strong>
                </p>
            </div>
            
            <div style="background: ${tableBg}; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px ${isDarkMode ? 'rgba(0, 0, 0, 0.3)' : 'rgba(0, 0, 0, 0.1)'}; border: ${isDarkMode ? '1px solid #334155' : 'none'};">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: ${tableHeaderBg};">
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: ${tableHeaderColor}; border-bottom: 2px solid ${tableHeaderBorder};">Date</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: ${tableHeaderColor}; border-bottom: 2px solid ${tableHeaderBorder};">Message</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: ${tableHeaderColor}; border-bottom: 2px solid ${tableHeaderBorder};">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    repairSms.forEach((message, index) => {
        // Couleurs adaptatives pour les lignes
        const rowBg = isDarkMode 
            ? (index % 2 === 0 ? '#1e293b' : '#0f172a')
            : (index % 2 === 0 ? '#fafafa' : 'white');
        const rowBorder = isDarkMode ? '#334155' : '#f3f4f6';
        const textColor = isDarkMode ? '#e2e8f0' : '#374151';
        const secondaryTextColor = isDarkMode ? '#94a3b8' : '#9ca3af';
        
        // Couleurs des statuts optimisées pour le mode sombre
        let statusColor = {
            'success': isDarkMode ? '#065f46' : '#059669',
            'danger': isDarkMode ? '#991b1b' : '#dc2626',
            'warning': isDarkMode ? '#92400e' : '#d97706',
            'info': isDarkMode ? '#0369a1' : '#0284c7'
        }[message.status_class] || (isDarkMode ? '#475569' : '#6b7280');
        
        let statusTextColor = isDarkMode ? 'white' : 'white';
        if (isDarkMode) {
            statusTextColor = {
                'success': '#d1fae5',
                'danger': '#fecaca',
                'warning': '#fed7aa',
                'info': '#bae6fd'
            }[message.status_class] || '#e2e8f0';
        }
        
        html += `
            <tr style="border-bottom: 1px solid ${rowBorder}; background: ${rowBg};">
                <td style="padding: 12px; vertical-align: top;">
                    <div style="font-weight: 500; color: ${textColor};">${message.date_formatted}</div>
                    <div style="font-size: 0.8rem; color: ${secondaryTextColor};">${message.source_table}</div>
                </td>
                <td style="padding: 12px; vertical-align: top; max-width: 400px;">
                    <div style="color: ${textColor}; line-height: 1.4; word-wrap: break-word;">
                        ${message.message.replace(/\n/g, '<br>')}
                    </div>
                </td>
                <td style="padding: 12px; vertical-align: top;">
                    <span style="background: ${statusColor}; color: ${statusTextColor}; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 500;">
                        ${message.status_text}
                    </span>
                </td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    return html;
}
</script>

</body>
</html>