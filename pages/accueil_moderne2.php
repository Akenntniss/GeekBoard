<?php
<?php include_once 'includes/night-mode-system.php'; ?>
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil_moderne2.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil_moderne2');
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    exit;
}

// Fonction pour obtenir la couleur en fonction de la priorité
function get_priority_color($priority) {
    switch(strtolower($priority)) {
        case 'haute':
            return 'danger';
        case 'moyenne':
            return 'warning';
        case 'basse':
            return 'info';
        default:
            return 'secondary';
    }
}

// Récupérer les statistiques pour le tableau de bord (avec cache APCu léger)
$cache_key = 'dashboard_quick_' . ($_SESSION['shop_id'] ?? 'default');
$use_cache = function_exists('apcu_exists') && function_exists('apcu_fetch') && function_exists('apcu_store');

// Essayer le cache d'abord (1 minute seulement)
if ($use_cache && apcu_exists($cache_key)) {
    $cached_data = apcu_fetch($cache_key);
    if ($cached_data && is_array($cached_data)) {
        extract($cached_data);
    } else {
        $use_cache = false; // Cache corrompu, désactiver
    }
}

// Si pas de cache ou cache expiré, récupérer normalement
if (!$use_cache || !isset($reparations_stats_categorie)) {
    $reparations_stats_categorie = get_reparations_count_by_status_categorie();
    $reparations_en_attente = $reparations_stats_categorie['en_attente'];
    $reparations_en_cours = $reparations_stats_categorie['en_cours'];
    $reparations_nouvelles = $reparations_stats_categorie['nouvelles'];
    $reparations_actives = count_active_reparations();

    $total_clients = get_total_clients();
    $taches_recentes_count = get_taches_recentes_count();
    $reparations_recentes = get_recent_reparations(5);
    $reparations_recentes_count = count_recent_reparations();
    $taches = get_taches_en_cours(5);
    
    // Mettre en cache pour 1 minute seulement
    if ($use_cache) {
        try {
            apcu_store($cache_key, compact(
                'reparations_stats_categorie', 'reparations_en_attente', 'reparations_en_cours', 
                'reparations_nouvelles', 'reparations_actives', 'total_clients', 'taches_recentes_count',
                'reparations_recentes', 'reparations_recentes_count', 'taches'
            ), 60);
        } catch (Exception $e) {
            // Ignorer les erreurs de cache
        }
    }
}

// Récupérer les commandes récentes et leur compteur
$commandes_recentes = [];
$commandes_en_attente_count = 0;
try {
    $shop_pdo = getShopDBConnection();
    
    // Compter les commandes en attente
    $stmt_count = $shop_pdo->query("
        SELECT COUNT(*) as count 
        FROM commandes_pieces 
        WHERE statut IN ('en_attente', 'urgent')
    ");
    $commandes_en_attente_count = $stmt_count->fetch()['count'];
    
    // Récupérer les commandes récentes
    $stmt = $shop_pdo->query("
        SELECT c.*, cl.nom as client_nom, cl.prenom as client_prenom, f.nom as fournisseur_nom 
        FROM commandes_pieces c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
        WHERE c.statut IN ('en_attente', 'urgent')
        ORDER BY c.date_creation DESC 
        LIMIT 5
    ");
    $commandes_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
}

// Récupérer les statistiques journalières
function get_daily_stats($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    try {
        $shop_pdo = getShopDBConnection();
        
        // Nouvelles réparations du jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ?
        ");
        $stmt->execute([$date]);
        $nouvelles_reparations = $stmt->fetchColumn();
        
        // Réparations effectuées du jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_modification) = ? 
            AND (statut = 'reparation_effectue' OR statut_categorie = 4)
            AND DATE(date_reception) != ?
        ");
        $stmt->execute([$date, $date]);
        $reparations_effectuees_modifiees = $stmt->fetchColumn();
        
        // Ajouter les réparations créées ET terminées le même jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ? 
            AND (statut = 'reparation_effectue' OR statut_categorie = 4)
        ");
        $stmt->execute([$date]);
        $reparations_effectuees_nouvelles = $stmt->fetchColumn();
        
        $reparations_effectuees = $reparations_effectuees_modifiees + $reparations_effectuees_nouvelles;
        
        // Réparations restituées du jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_modification) = ? 
            AND statut = 'restitue'
            AND DATE(date_reception) != ?
        ");
        $stmt->execute([$date, $date]);
        $reparations_restituees_modifiees = $stmt->fetchColumn();
        
        // Ajouter les réparations créées ET restituées le même jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ? 
            AND statut = 'restitue'
        ");
        $stmt->execute([$date]);
        $reparations_restituees_nouvelles = $stmt->fetchColumn();
        
        $reparations_restituees = $reparations_restituees_modifiees + $reparations_restituees_nouvelles;
        
        // Devis envoyés du jour
        $devis_envoyes = 0;
        try {
            $stmt = $shop_pdo->prepare("
                SELECT COUNT(*) as count 
                FROM devis 
                WHERE DATE(date_envoi) = ? AND statut = 'envoye'
            ");
            $stmt->execute([$date]);
            $devis_envoyes = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Table devis n'existe peut-être pas encore
            $devis_envoyes = 0;
        }
        
        return [
            'nouvelles_reparations' => $nouvelles_reparations ?: 0,
            'reparations_effectuees' => $reparations_effectuees ?: 0,
            'reparations_restituees' => $reparations_restituees ?: 0,
            'devis_envoyes' => $devis_envoyes ?: 0,
            'date' => $date
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques journalières: " . $e->getMessage());
        return [
            'nouvelles_reparations' => 0,
            'reparations_effectuees' => 0,
            'reparations_restituees' => 0,
            'devis_envoyes' => 0,
            'date' => $date
        ];
    }
}

// Récupérer les statistiques du jour
$stats_journalieres = get_daily_stats();
?>

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
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
        height: auto !important;
        width: auto !important;
    }
    
    /* S'assurer que l'animation SERVO est visible */
    body .servo-logo-container .loader {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    body .servo-logo-container svg {
        visibility: visible !important;
        opacity: 1 !important;
        display: inline-block !important;
    }
    
    body .servo-logo-container path {
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Réserver espace navbar */
    body {
        padding-top: 80px !important;
        margin: 0 !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        overflow-x: hidden !important;
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
   VARIABLES CSS POUR LES THÈMES
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
    --night-bg-animated: linear-gradient(-45deg, #0a0a0a, #1a1a2e, #16213e, #0f3460, #533483, #2d1b69, #0a0a0a);
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
}

/* ========================================
   STRUCTURE DE BASE
======================================== */
body {
    margin: 0;
    padding: 0;
    padding-top: 80px; /* Espace pour la navbar fixe */
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

.modern-dashboard {
    position: relative;
    min-height: 100vh;
    padding: 1rem;
    transition: all 0.3s ease;
    margin-top: -80px; /* Remonter sous la navbar */
    padding-top: calc(80px + 1rem); /* Compenser avec padding */
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 300% 300%;
    animation: gradientFlow 20s ease infinite;
    min-height: 100vh;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated) !important;
    background-size: 400% 400% !important;
}

/* Mode nuit - Forcer le fond animé */
body.night-mode .bg-animated {
    background: var(--night-bg-animated) !important;
    background-size: 400% 400% !important;
    animation: gradientFlowNight 25s ease infinite !important;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes gradientFlowNight {
    0% { background-position: 0% 50%; }
    25% { background-position: 100% 0%; }
    50% { background-position: 100% 100%; }
    75% { background-position: 0% 100%; }
    100% { background-position: 0% 50%; }
}

/* ========================================
   ANIMATIONS MODERNES
======================================== */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

/* ========================================
   EN-TÊTE MODERNE
======================================== */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
}

.modern-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--day-text);
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0;
}

.modern-title i {
    color: var(--day-primary);
    font-size: 2rem;
}

/* ========================================
   BOUTONS D'ACTION MODERNES
======================================== */
.modern-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    text-decoration: none;
    border-radius: 15px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    position: relative;
    overflow: hidden;
}

.modern-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.modern-btn:hover::before {
    left: 100%;
}

.modern-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
}

.modern-btn--success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.modern-btn--success:hover {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
}

.modern-btn--info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.modern-btn--info:hover {
    box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4);
}

.modern-btn--warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modern-btn--warning:hover {
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
}

.modern-btn--danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.modern-btn--danger:hover {
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4);
}

/* ========================================
   GRILLE D'ACTIONS PRINCIPALES
======================================== */
.modern-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.modern-action-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    padding: 2rem;
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: slideInUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}

.modern-action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    transition: left 0.5s;
}

.modern-action-card:hover::before {
    left: 100%;
}

.modern-action-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 60px var(--day-shadow);
    border-color: var(--day-primary);
}

.modern-action-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
}

.modern-action-card:hover .modern-action-icon {
    transform: scale(1.1) rotate(5deg);
}

.modern-action-content h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--day-text);
    margin: 0 0 0.5rem 0;
}

.modern-action-content p {
    color: var(--day-text-light);
    font-size: 0.95rem;
    margin: 0;
    line-height: 1.5;
}

/* ========================================
   STATISTIQUES MODERNES
======================================== */
.modern-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.modern-stat-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    animation: slideInUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary), var(--day-accent));
    background-size: 200% 100%;
    animation: gradientFlow 3s ease infinite;
}

.modern-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px var(--day-shadow);
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b !important; /* Noir en mode jour - Priorité forte */
    margin: 0;
    line-height: 1;
}

.stat-label {
    color: var(--day-text-light);
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0.5rem 0 0;
}

/* ========================================
   LISTES MODERNES
======================================== */
.modern-list-container {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    overflow: hidden;
    animation: slideInUp 0.6s ease-out;
    margin-bottom: 1.5rem;
}

.modern-list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--day-border);
}

.modern-list-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--day-text);
    margin: 0;
}

.modern-list-badge {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.modern-list-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
}

.modern-list-item:hover {
    background: rgba(59, 130, 246, 0.05);
    border-color: var(--day-border);
    transform: translateX(4px);
}

.modern-list-item:last-child {
    margin-bottom: 0;
}

.list-item-indicator {
    width: 4px;
    height: 40px;
    border-radius: 2px;
    margin-right: 1rem;
    flex-shrink: 0;
}

.list-item-indicator.repairs { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.list-item-indicator.tasks { background: linear-gradient(135deg, #10b981, #059669); }
.list-item-indicator.orders { background: linear-gradient(135deg, #f59e0b, #d97706); }

.list-item-content {
    flex: 1;
}

.list-item-title {
    font-weight: 600;
    color: var(--day-text);
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.list-item-subtitle {
    color: var(--day-text-light);
    font-size: 0.875rem;
}

.list-item-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.date-badge {
    background: rgba(59, 130, 246, 0.1);
    color: var(--day-primary);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.priority-badge.high {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.priority-badge.medium {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.priority-badge.low {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .modern-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        padding: 1rem;
    }
    
    .modern-actions {
        width: 100%;
        justify-content: center;
    }
    
    .modern-action-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-title {
        font-size: 2rem;
    }

    .modern-list-container {
        padding: 1rem;
    }
}

/* ========================================
   MODE NUIT
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
    background: var(--night-bg-animated) !important;
    background-size: 400% 400% !important;
    animation: gradientFlowNight 25s ease infinite !important;
}

body.night-mode .bg-animated {
    background: var(--night-bg-animated);
}

body.night-mode .modern-header,
body.night-mode .modern-stat-card,
body.night-mode .modern-action-card,
body.night-mode .modern-list-container {
    background: var(--night-card-bg);
    color: var(--night-text);
    border: 1px solid var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

body.night-mode .modern-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

body.night-mode .modern-list-item:hover {
    background: rgba(0, 212, 255, 0.1);
}

body.night-mode .date-badge {
    background: rgba(0, 212, 255, 0.2);
    color: var(--night-primary);
}

/* ========================================
   TOAST NOTIFICATIONS
======================================== */
.modern-toast {
    position: fixed;
    top: 2rem;
    right: 2rem;
    background: white;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    border-left: 4px solid var(--day-primary);
    z-index: 100000;
    animation: slideInUp 0.3s ease;
    min-width: 300px;
}

.modern-toast--success {
    border-left-color: #10b981;
}

.modern-toast--error {
    border-left-color: #ef4444;
}

.modern-toast--warning {
    border-left-color: #f59e0b;
}

body.night-mode .modern-toast {
    background: var(--night-card-bg);
    color: var(--night-text);
}

/* ========================================
   ANIMATIONS DES PARTICULES
======================================== */
.particles-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
}

@keyframes float {
    0%, 100% { 
        transform: translateY(0px) rotate(0deg);
        opacity: 0.3;
    }
    50% { 
        transform: translateY(-20px) rotate(180deg);
        opacity: 0.7;
    }
}

/* ========================================
   MODALS SPÉCIAUX
======================================== */
/* Styles spécifiques pour nos modals */
#ajouterTacheModal .modal-content,
#ajouterCommandeModal .modal-content {
    backdrop-filter: blur(20px) !important;
    border: none !important;
    border-radius: 20px !important;
    overflow: hidden !important;
}

body.night-mode #ajouterTacheModal .modal-content,
body.night-mode #ajouterCommandeModal .modal-content {
    backdrop-filter: blur(25px) !important;
    border: 1px solid rgba(0, 255, 255, 0.3) !important;
    box-shadow: 0 25px 50px rgba(0, 255, 255, 0.4), 0 0 0 1px rgba(0, 255, 255, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
}
</style>

<!-- Particules d'arrière-plan -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-tachometer-alt"></i>
            Tableau de Bord
        </h1>
        <div class="modern-actions">
            <button class="modern-btn" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i>
                Actualiser
            </button>
        </div>
    </div>

    <!-- Actions principales -->
    <div class="modern-action-grid fade-in">
        <a href="#" class="modern-action-card" onclick="ouvrirRechercheModerne(); return false;">
            <div class="modern-action-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <i class="fas fa-search"></i>
            </div>
            <div class="modern-action-content">
                <h3>Recherche Avancée</h3>
                <p>Trouvez rapidement clients, réparations et commandes</p>
            </div>
        </a>
        
        <a href="#" class="modern-action-card" data-bs-toggle="modal" data-bs-target="#ajouterTacheModal" onclick="event.preventDefault();">
            <div class="modern-action-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="modern-action-content">
                <h3>Nouvelle Tâche</h3>
                <p>Créez et organisez vos tâches quotidiennes</p>
            </div>
        </a>
        
        <a href="index.php?page=ajouter_reparation" class="modern-action-card">
            <div class="modern-action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="fas fa-tools"></i>
            </div>
            <div class="modern-action-content">
                <h3>Nouvelle Réparation</h3>
                <p>Enregistrez une nouvelle réparation</p>
            </div>
        </a>
        
        <a href="#" class="modern-action-card" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
            <div class="modern-action-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="modern-action-content">
                <h3>Nouvelle Commande</h3>
                <p>Passez une commande de pièces</p>
            </div>
        </a>
    </div>

    <!-- Statistiques principales -->
    <div class="modern-stats-grid fade-in">
        <a href="index.php?page=reparations&statut_ids=1,2,3,19,20" class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                    <i class="fas fa-tools"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $reparations_actives; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Réparations actives</div>
        </a>
        
        <a href="index.php?page=taches" class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $taches_recentes_count; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Tâches en cours</div>
        </a>
        
        <a href="index.php?page=commandes_pieces" class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $commandes_en_attente_count; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Commandes en attente</div>
        </a>
        
        <a href="index.php?page=clients" class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_clients; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Total clients</div>
        </a>
    </div>

    <!-- Statistiques du jour -->
    <div class="modern-stats-grid fade-in">
        <div class="modern-stat-card" onclick="openStatsModal('nouvelles_reparations')" style="cursor: pointer;">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    <i class="fas fa-plus-circle"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $stats_journalieres['nouvelles_reparations']; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Nouvelles réparations aujourd'hui</div>
        </div>
        
        <div class="modern-stat-card" onclick="openStatsModal('reparations_effectuees')" style="cursor: pointer;">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-wrench"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $stats_journalieres['reparations_effectuees']; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Réparations effectuées</div>
        </div>
        
        <div class="modern-stat-card" onclick="openStatsModal('reparations_restituees')" style="cursor: pointer;">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                    <i class="fas fa-handshake"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $stats_journalieres['reparations_restituees']; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Réparations restituées</div>
        </div>
        
        <div class="modern-stat-card" onclick="openStatsModal('devis_envoyes')" style="cursor: pointer;">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $stats_journalieres['devis_envoyes']; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Devis envoyés</div>
        </div>
    </div>

    <!-- Listes d'éléments récents -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        
        <!-- Réparations récentes -->
        <div class="modern-list-container fade-in">
            <div class="modern-list-header">
                <h4 class="modern-list-title">
                    <i class="fas fa-tools"></i>
                    <a href="index.php?page=reparations" style="text-decoration: none; color: inherit;">Réparations récentes</a>
                </h4>
                <span class="modern-list-badge"><?php echo count($reparations_recentes); ?></span>
<?php include_once 'includes/night-mode-system.php'; ?>
            </div>
            <div class="modern-list-content">
                <?php if (!empty($reparations_recentes)): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <?php foreach ($reparations_recentes as $reparation): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        <div class="modern-list-item" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>&view=cards'">
<?php include_once 'includes/night-mode-system.php'; ?>
                            <div class="list-item-indicator repairs"></div>
                            <div class="list-item-content">
                                <div class="list-item-title"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'Client N/A'); ?> - <?php echo htmlspecialchars($reparation['appareil'] ?? 'Appareil N/A'); ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
                                <div class="list-item-subtitle"><?php echo htmlspecialchars($reparation['probleme_description'] ?? 'Description N/A'); ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
                            </div>
                            <div class="list-item-meta">
                                <div class="date-badge">
                                    <?php echo date('d/m', strtotime($reparation['date_reception'] ?? 'now')); ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                <?php else: ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                        <i class="fas fa-tools" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <div style="font-weight: 600;">Aucune réparation récente</div>
                        <div style="margin-top: 0.5rem;">Pas de nouvelles réparations</div>
                    </div>
                <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
            </div>
        </div>

        <!-- Tâches en cours -->
        <div class="modern-list-container fade-in">
            <div class="modern-list-header">
                <h4 class="modern-list-title">
                    <i class="fas fa-tasks"></i>
                    <a href="index.php?page=taches" style="text-decoration: none; color: inherit;">Tâches en cours</a>
                </h4>
                <span class="modern-list-badge"><?php echo count($taches); ?></span>
<?php include_once 'includes/night-mode-system.php'; ?>
            </div>
            <div class="modern-list-content">
                <?php if (!empty($taches)): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <?php foreach ($taches as $tache): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        <div class="modern-list-item" onclick="openTaskDetails(<?php echo $tache['id']; ?>)">
<?php include_once 'includes/night-mode-system.php'; ?>
                            <div class="list-item-indicator tasks"></div>
                            <div class="list-item-content">
                                <div class="list-item-title"><?php echo htmlspecialchars($tache['titre'] ?? 'Tâche N/A'); ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
                                <div class="list-item-subtitle"><?php echo htmlspecialchars($tache['description'] ?? 'Description N/A'); ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
                            </div>
                            <div class="list-item-meta">
                                <div class="priority-badge <?php echo strtolower($tache['priorite'] ?? 'low'); ?>">
<?php include_once 'includes/night-mode-system.php'; ?>
                                    <?php echo ucfirst($tache['priorite'] ?? 'Basse'); ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                                </div>
                                <div class="date-badge">
                                    <?php echo date('d/m', strtotime($tache['date_creation'] ?? 'now')); ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                <?php else: ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                        <i class="fas fa-tasks" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <div style="font-weight: 600;">Aucune tâche en cours</div>
                        <div style="margin-top: 0.5rem;">Toutes les tâches sont terminées</div>
                    </div>
                <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
            </div>
        </div>

        <!-- Commandes récentes -->
        <div class="modern-list-container fade-in">
            <div class="modern-list-header">
                <h4 class="modern-list-title">
                    <i class="fas fa-shopping-cart"></i>
                    <a href="index.php?page=commandes_pieces" style="text-decoration: none; color: inherit;">Commandes récentes</a>
                </h4>
                <span class="modern-list-badge"><?php echo count($commandes_recentes); ?></span>
<?php include_once 'includes/night-mode-system.php'; ?>
            </div>
            <div class="modern-list-content">
                <?php if (!empty($commandes_recentes)): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <?php foreach ($commandes_recentes as $commande): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        <div class="modern-list-item" onclick="ouvrirModalStatut(event, <?php echo $commande['id']; ?>, '<?php echo $commande['statut']; ?>', '<?php echo htmlspecialchars($commande['reference'] ?? 'REF-' . $commande['id']); ?>', '<?php echo htmlspecialchars($commande['nom_piece']); ?>')">
<?php include_once 'includes/night-mode-system.php'; ?>
                            <div class="list-item-indicator orders"></div>
                            <div class="list-item-content">
                                <div class="list-item-title"><?php echo htmlspecialchars($commande['nom_piece'] ?? 'Produit N/A'); ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
                                <div class="list-item-subtitle"><?php echo htmlspecialchars($commande['fournisseur_nom'] ?? 'Fournisseur N/A'); ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
                            </div>
                            <div class="list-item-meta">
                                <div class="date-badge">
                                    <?php echo date('d/m', strtotime($commande['date_creation'] ?? 'now')); ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                <?php else: ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                        <i class="fas fa-shopping-cart" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <div style="font-weight: 600;">Aucune commande</div>
                        <div style="margin-top: 0.5rem;">Pas de commandes en attente</div>
                    </div>
                <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
            </div>
        </div>

    </div>

</div>

<!-- Modals d'origine avec les mêmes fonctionnalités -->
<?php 
<?php include_once 'includes/night-mode-system.php'; ?>
// Inclure les modals d'origine 
include_once 'includes/modals.php';
?>

<!-- Scripts originaux -->
<script src="assets/js/commande-statut.js"></script>
<script src="assets/js/taches.js"></script>
<script src="assets/js/mobile_dock_bar.js"></script>

<script>
// Détection IMMÉDIATE du mode nuit (avant DOMContentLoaded)
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
        
        // Appliquer immédiatement le gradient de fond futuriste
        document.body.style.background = 'linear-gradient(-45deg, #0a0a0a, #1a1a2e, #16213e, #0f3460, #533483, #2d1b69, #0a0a0a)';
        document.body.style.backgroundSize = '400% 400%';
        document.body.style.animation = 'gradientFlowNight 25s ease infinite';
        
        console.log('🌙 Mode nuit détecté et appliqué immédiatement avec gradient futuriste');
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
        
        // S'assurer que le fond est neutre en mode jour
        document.body.style.background = '';
        document.body.style.backgroundSize = '';
        document.body.style.animation = '';
        
        console.log('☀️ Mode jour détecté et appliqué immédiatement');
    }
})();

// Variables globales pour le thème
let currentTheme = 'day';
let particlesCreated = false;

// Fonction d'initialisation du thème
function showToast(message, type = 'info') {
    // Supprimer les anciens toasts
    const existingToasts = document.querySelectorAll('.modern-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `modern-toast modern-toast--${type}`;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span style="font-weight: 500;">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Supprimer après 4 secondes
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Animations des particules
function createParticles() {
    const container = document.getElementById('particles');
    if (!container || particlesCreated) return;
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(0, 212, 255, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float ${Math.random() * 3 + 2}s ease-in-out infinite;
        `;
        
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 2 + 's';
        
        container.appendChild(particle);
    }
    
    particlesCreated = true;
}

function removeParticles() {
    const container = document.getElementById('particles');
    if (container) {
        container.innerHTML = '';
    }
    particlesCreated = false;
}

// Fonction pour basculer manuellement le mode (si vous voulez ajouter un bouton plus tard)
function toggleTheme() {
    document.body.classList.toggle('night-mode');
    const dashboard = document.getElementById('dashboard');
    const isDark = document.body.classList.contains('night-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    currentTheme = isDark ? 'night' : 'day';
    
    if (isDark) {
        dashboard.classList.add('night-mode');
        // Appliquer le gradient futuriste
        document.body.style.background = 'linear-gradient(-45deg, #0a0a0a, #1a1a2e, #16213e, #0f3460, #533483, #2d1b69, #0a0a0a)';
        document.body.style.backgroundSize = '400% 400%';
        document.body.style.animation = 'gradientFlowNight 25s ease infinite';
        createParticles();
    } else {
        dashboard.classList.remove('night-mode');
        // Retirer le gradient
        document.body.style.background = '';
        document.body.style.backgroundSize = '';
        document.body.style.animation = '';
        removeParticles();
    }
    
    console.log('Mode basculé vers:', isDark ? 'nuit' : 'jour');
}

// Fonction pour ouvrir le modal de statistiques (référence à la fonction existante)
function openStatsModal(type) {
    // Cette fonction doit être définie dans vos scripts existants
    if (typeof window.openStatsModal === 'function') {
        window.openStatsModal(type);
    } else {
        showToast('Fonctionnalité de statistiques en cours de développement', 'info');
    }
}

// Fonction pour ouvrir les détails d'une tâche
function openTaskDetails(taskId) {
    // Cette fonction doit être définie dans vos scripts existants
    if (typeof window.openTaskDetails === 'function') {
        window.openTaskDetails(taskId);
    } else {
        showToast('Ouverture des détails de la tâche #' + taskId, 'info');
    }
}

// Fonction pour ouvrir la recherche moderne
function ouvrirRechercheModerne() {
    // Cette fonction doit être définie dans vos scripts existants
    if (typeof window.ouvrirRechercheModerne === 'function') {
        window.ouvrirRechercheModerne();
    } else {
        showToast('Ouverture de la recherche avancée', 'info');
    }
}

// Fonction pour ouvrir le modal de statut des commandes (référence à la fonction existante)
function ouvrirModalStatut(event, commandeId, statut, reference, nomPiece) {
    // Cette fonction doit être définie dans vos scripts existants
    if (typeof window.ouvrirModalStatut === 'function') {
        window.ouvrirModalStatut(event, commandeId, statut, reference, nomPiece);
    } else {
        showToast('Ouverture des détails de la commande #' + commandeId, 'info');
    }
}

// Fonction pour créer un nouveau client (référence à la fonction existante)
window.createNewClientModal = function() {
    console.log('👤 Ouverture du modal nouveau client');
    
    // Fermer d'abord le modal de commande s'il est ouvert
    const modalCommande = document.getElementById('ajouterCommandeModal');
    if (modalCommande) {
        const modalCommandeInstance = bootstrap.Modal.getInstance(modalCommande);
        if (modalCommandeInstance) {
            modalCommandeInstance.hide();
        }
    }
    
    // Attendre un peu que le modal de commande se ferme avant d'ouvrir le nouveau
    setTimeout(() => {
        // Nettoyer les champs du formulaire
        const nouveauNom = document.getElementById('nouveau_nom_commande');
        const nouveauPrenom = document.getElementById('nouveau_prenom_commande');
        const nouveauTelephone = document.getElementById('nouveau_telephone_commande');
        
        if (nouveauNom) nouveauNom.value = '';
        if (nouveauPrenom) nouveauPrenom.value = '';
        if (nouveauTelephone) nouveauTelephone.value = '';
        
        // Ouvrir le modal nouveau client s'il existe
        const modalNouveauClient = document.getElementById('nouveauClientModal_commande');
        if (modalNouveauClient) {
            const modal = new bootstrap.Modal(modalNouveauClient);
            modal.show();
        }
        
        console.log('✅ Modal nouveau client ouvert');
    }, 300);
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('📱 Initialisation du tableau de bord moderne');
    
    // Détecter et appliquer le mode nuit dès le chargement
    initTheme();
    
    // Configurer l'écoute des changements de thème
    setupThemeListener();
    
    console.log('✅ Tableau de bord moderne initialisé avec détection automatique du mode nuit');
});
</script>
