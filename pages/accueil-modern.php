<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil-modern.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil-modern');
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
        
        // Nouvelles réparations du jour (toutes les réparations créées aujourd'hui, peu importe leur statut actuel)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_reception) = ?
        ");
        $stmt->execute([$date]);
        $nouvelles_reparations = $stmt->fetchColumn();
        
        // Réparations effectuées du jour (réparations qui ont changé vers le statut "effectué" aujourd'hui)
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
        
        // Réparations restituées du jour (réparations qui ont changé vers le statut "restitué" aujourd'hui)
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

$stats_journalieres = get_daily_stats();
?>

<style>
/* ========================================
   CORRECTION DU DÉCALAGE EN HAUT DE PAGE
======================================== */
body {
    padding-top: 0 !important;
    margin-top: 0 !important;
}

/* ========================================
   VARIABLES CSS POUR LES THÈMES
======================================== */
:root {
    /* Mode Jour - Progressif Moderne */
    --day-primary: #667eea;
    --day-secondary: #764ba2;
    --day-accent: #f093fb;
    --day-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --day-bg-animated: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #2d3748;
    --day-text-light: #718096;
    --day-shadow: rgba(102, 126, 234, 0.15);
    --day-border: rgba(255, 255, 255, 0.2);

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
   STRUCTURE DE BASE
======================================== */
body {
    margin: 0;
    padding: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

.modern-dashboard {
    position: relative;
    min-height: 100vh;
    padding: 1rem;
    transition: all 0.3s ease;
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 400% 400%;
    animation: gradientFlow 15s ease infinite;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated);
    background-size: 400% 400%;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Particules flottantes pour le mode nuit */
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

.particle {
    position: absolute;
    width: 2px;
    height: 2px;
    background: var(--night-primary);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
    opacity: 0.7;
    box-shadow: var(--night-glow);
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
    50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
}

/* ========================================
   BOUTONS D'ACTIONS EN HAUT
======================================== */
.action-buttons-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 10;
}

.action-btn {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: var(--day-text);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px var(--day-shadow);
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s ease;
}

.action-btn:hover::before {
    left: 100%;
}

.action-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px var(--day-shadow);
}

.action-btn .icon {
    width: 48px;
    height: 48px;
    background: var(--day-primary);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.action-btn .content h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.action-btn .content p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--day-text-light);
}

/* ========================================
   STATISTIQUES
======================================== */
.statistics-container {
    margin-bottom: 2rem;
    position: relative;
    z-index: 10;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: var(--day-text);
    text-align: center;
}

.statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: var(--day-text);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px var(--day-shadow);
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px var(--day-shadow);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.stat-card .stat-icon {
    background: var(--day-primary);
}
.stat-card.progress-card .stat-icon {
    background: #10b981;
}
.stat-card.waiting-card .stat-icon {
    background: #f59e0b;
}
.stat-card.clients-card .stat-icon {
    background: #ef4444;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--day-text);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--day-text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-link {
    font-size: 1.25rem;
    color: var(--day-text-light);
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-link {
    transform: translateX(4px);
}

/* ========================================
   TABLEAUX
======================================== */
.tables-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 10;
}

.table-section {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px var(--day-shadow);
    overflow: hidden;
}

.table-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--day-border);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.table-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--day-text);
    flex: 1;
}

.table-header .badge {
    background: var(--day-primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.table-content {
    max-height: 400px;
    overflow-y: auto;
}

.table-row {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--day-border);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.table-row:hover {
    background: rgba(102, 126, 234, 0.05);
}

.table-row:last-child {
    border-bottom: none;
}

.row-indicator {
    width: 4px;
    height: 32px;
    border-radius: 2px;
    flex-shrink: 0;
}

.row-indicator.taches { background: #10b981; }
.row-indicator.reparations { background: var(--day-primary); }
.row-indicator.commandes { background: #f59e0b; }

.row-content {
    flex: 1;
    min-width: 0;
}

.row-title {
    font-weight: 600;
    color: var(--day-text);
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.row-subtitle {
    font-size: 0.875rem;
    color: var(--day-text-light);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.row-meta {
    text-align: right;
    flex-shrink: 0;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-badge.haute { background: #fee2e2; color: #991b1b; }
.priority-badge.moyenne { background: #fef3c7; color: #92400e; }
.priority-badge.basse { background: #dbeafe; color: #1e40af; }

.date-badge {
    font-size: 0.75rem;
    color: var(--day-text-light);
    background: rgba(102, 126, 234, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
}

.table-empty {
    padding: 3rem;
    text-align: center;
    color: var(--day-text-light);
}

.table-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.table-empty .title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

/* ========================================
   STATISTIQUES DU JOUR - BOUTONS MODERNES  
======================================== */
.daily-stats-card {
    transition: all 0.3s ease;
}

.daily-stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px var(--day-shadow);
}

.night-mode .daily-stats-card:hover {
    box-shadow: var(--night-glow);
}

/* ========================================
   MODE NUIT
======================================== */
.night-mode {
    --day-card-bg: var(--night-card-bg);
    --day-text: var(--night-text);
    --day-text-light: var(--night-text-light);
    --day-shadow: var(--night-shadow);
    --day-border: var(--night-border);
    --day-primary: var(--night-primary);
}

.night-mode .action-btn,
.night-mode .stat-card,
.night-mode .table-section,
.night-mode .daily-stat-btn {
    border: 1px solid var(--night-border);
    box-shadow: var(--night-glow);
}

.night-mode .section-title {
    background: linear-gradient(45deg, var(--night-primary), var(--night-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ========================================
   RESPONSIVE - IPAD
======================================== */
@media (max-width: 1024px) and (min-width: 768px) {
    .modern-dashboard {
        padding: 1.5rem;
    }

    .action-buttons-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    .action-btn {
        padding: 1.75rem;
    }

    .action-btn .icon {
        width: 52px;
        height: 52px;
        font-size: 1.6rem;
    }

    .statistics-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .tables-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .table-row {
        padding: 1.25rem 1.5rem;
    }

    /* Touch-friendly hovers for iPad */
    .action-btn:active,
    .stat-card:active,
    .table-row:active {
        transform: scale(0.98);
    }
}

/* ========================================
   RESPONSIVE - MOBILE
======================================== */
@media (max-width: 767px) {
    .modern-dashboard {
        padding: 1rem 0.75rem;
    }

    .action-buttons-container {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .action-btn {
        padding: 1.25rem;
        border-radius: 12px;
    }

    .action-btn .icon {
        width: 44px;
        height: 44px;
        font-size: 1.3rem;
    }

    .section-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .statistics-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .stat-card {
        padding: 1.25rem;
        border-radius: 12px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.25rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .tables-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .table-section {
        border-radius: 12px;
    }

    .table-header {
        padding: 1.25rem;
    }

    .table-row {
        padding: 1rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .row-content,
    .row-meta {
        width: 100%;
    }

    .row-meta {
        text-align: left;
    }

    /* Statistiques du jour responsive */
    .statistics-container .statistics-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    /* Touch-friendly interactions */
    .action-btn,
    .stat-card,
    .table-row {
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }
}

/* ========================================
   ONGLETS MODERNES
======================================== */
.modern-tabs {
    display: flex;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 0.5rem;
}

.modern-tab-button {
    background: transparent;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--day-text-light);
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 1;
    text-align: center;
}

.modern-tab-button:hover {
    background: rgba(102, 126, 234, 0.1);
    color: var(--day-text);
}

.modern-tab-button.active {
    background: var(--day-primary);
    color: white;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.night-mode .modern-tab-button.active {
    background: var(--night-primary);
    box-shadow: var(--night-glow);
}

/* ========================================
   UTILITAIRES
======================================== */
.fade-in {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.theme-toggle {
    position: fixed;
    top: 2rem;
    right: 2rem;
    z-index: 100;
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 50px;
    padding: 0.75rem 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px var(--day-shadow);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--day-text);
}

.theme-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px var(--day-shadow);
}

.night-mode .theme-toggle {
    box-shadow: var(--night-glow);
}
</style>

<!-- Basculeur de thème -->
<div class="theme-toggle" onclick="toggleTheme()">
    <i class="fas fa-moon" id="theme-icon"></i>
    <span id="theme-text">Mode Nuit</span>
</div>

<!-- Container de particules (mode nuit) -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- 🚀 BOUTONS D'ACTIONS EN HAUT -->
    <div class="action-buttons-container fade-in">
        <a href="#" class="action-btn" onclick="ouvrirRechercheModerne(); return false;">
            <div class="icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="content">
                <h3>Rechercher</h3>
                <p>Chercher clients, réparations...</p>
            </div>
        </a>

        <a href="#" class="action-btn" data-bs-toggle="modal" data-bs-target="#ajouterTacheModal" onclick="event.preventDefault();">
            <div class="icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="content">
                <h3>Nouvelle Tâche</h3>
                <p>Créer une nouvelle tâche</p>
            </div>
        </a>

        <a href="index.php?page=ajouter_reparation" class="action-btn">
            <div class="icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="content">
                <h3>Nouvelle Réparation</h3>
                <p>Enregistrer une nouvelle réparation</p>
            </div>
        </a>

        <a href="#" class="action-btn" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="content">
                <h3>Nouvelle Commande</h3>
                <p>Commander une nouvelle pièce</p>
            </div>
        </a>
    </div>

    <!-- 📊 STATISTIQUES -->
    <div class="statistics-container fade-in">
        <h3 class="section-title">État des Réparations</h3>
        <div class="statistics-grid">
            <a href="index.php?page=reparations&statut_ids=1,2,3,19,20" class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $reparations_actives; ?></div>
                    <div class="stat-label">Réparations</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="index.php?page=taches" class="stat-card progress-card">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $taches_recentes_count; ?></div>
                    <div class="stat-label">Tâches</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="index.php?page=commandes_pieces" class="stat-card waiting-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $commandes_en_attente_count; ?></div>
                    <div class="stat-label">Commandes</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="index.php?page=reparations&urgence=1" class="stat-card clients-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $reparations_en_cours; ?></div>
                    <div class="stat-label">Urgences</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- 📋 TABLEAUX -->
    <div class="tables-container fade-in">
        <!-- Tableau 1: Tâches en cours -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-tasks"></i>
                <h4><a href="index.php?page=taches" style="text-decoration: none; color: inherit;">Tâches en cours</a></h4>
                <span class="badge"><?php echo $taches_recentes_count; ?></span>
            </div>
            
            <!-- Onglets pour les tâches -->
            <div class="modern-tabs" style="padding: 1rem; border-bottom: 1px solid var(--day-border);">
                <button class="modern-tab-button active" data-tab="toutes-taches" onclick="switchTab('toutes-taches')">Toutes</button>
                <button class="modern-tab-button" data-tab="mes-taches" onclick="switchTab('mes-taches')">Mes tâches</button>
            </div>
            
            <div class="table-content">
                <!-- Contenu onglet "Toutes les tâches" -->
                <div class="tab-content active" id="toutes-taches">
                    <?php 
                    $toutes_taches = get_toutes_taches_en_cours(10);
                    if (!empty($toutes_taches)): ?>
                        <?php foreach ($toutes_taches as $tache): 
                            $urgence_class = get_urgence_class($tache['urgence']);
                        ?>
                            <div class="table-row modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                <div class="row-indicator taches"></div>
                                <div class="row-content">
                                    <div class="row-title modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars(substr($tache['description'] ?? '', 0, 50)) . '...'; ?></div>
                                </div>
                                <div class="row-meta">
                                    <div class="priority-badge modern-badge <?php echo strtolower($tache['urgence']); ?>">
                                        <?php echo htmlspecialchars($tache['urgence']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-tasks"></i>
                            <div class="title">Aucune tâche en cours</div>
                            <div>Toutes les tâches ont été complétées</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Contenu onglet "Mes tâches" -->
                <div class="tab-content" id="mes-taches">
                    <?php if (!empty($taches)): ?>
                        <?php foreach ($taches as $tache): 
                            $urgence_class = get_urgence_class($tache['urgence']);
                        ?>
                            <div class="table-row modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                <div class="row-indicator taches"></div>
                                <div class="row-content">
                                    <div class="row-title modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars(substr($tache['description'] ?? '', 0, 50)) . '...'; ?></div>
                                </div>
                                <div class="row-meta">
                                    <div class="priority-badge modern-badge <?php echo strtolower($tache['urgence']); ?>">
                                        <?php echo htmlspecialchars($tache['urgence']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-tasks"></i>
                            <div class="title">Aucune tâche</div>
                            <div>Toutes les tâches sont terminées</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tableau 2: Réparations récentes -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-wrench"></i>
                <h4>Réparations récentes</h4>
                <span class="badge"><?php echo $reparations_recentes_count; ?></span>
            </div>
            <div class="table-content">
                <?php if (!empty($reparations_recentes)): ?>
                    <?php foreach ($reparations_recentes as $reparation): ?>
                        <div class="table-row" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                            <div class="row-indicator reparations"></div>
                            <div class="row-content">
                                <div class="row-title"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></div>
                                <div class="row-subtitle"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></div>
                            </div>
                            <div class="row-meta">
                                <div class="date-badge">
                                    <?php echo date('d/m', strtotime($reparation['date_reception'] ?? 'now')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="table-empty">
                        <i class="fas fa-wrench"></i>
                        <div class="title">Aucune réparation</div>
                        <div>Pas de réparations en cours</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tableau 3: Commandes récentes -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-shopping-cart"></i>
                <h4>Commandes récentes</h4>
                <span class="badge"><?php echo count($commandes_recentes); ?></span>
            </div>
            <div class="table-content">
                <?php if (!empty($commandes_recentes)): ?>
                    <?php foreach ($commandes_recentes as $commande): ?>
                        <div class="table-row" onclick="window.location.href='index.php?page=commandes_pieces&id=<?php echo $commande['id']; ?>'">
                            <div class="row-indicator commandes"></div>
                            <div class="row-content">
                                <div class="row-title"><?php echo htmlspecialchars($commande['piece_nom'] ?? $commande['reference'] ?? 'N/A'); ?></div>
                                <div class="row-subtitle"><?php echo htmlspecialchars($commande['fournisseur_nom'] ?? 'Fournisseur N/A'); ?></div>
                            </div>
                            <div class="row-meta">
                                <div class="date-badge">
                                    <?php echo date('d/m', strtotime($commande['date_creation'] ?? 'now')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="table-empty">
                        <i class="fas fa-shopping-cart"></i>
                        <div class="title">Aucune commande</div>
                        <div>Pas de commandes en attente</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 📈 STATISTIQUES DU JOUR (BOUTONS AVEC MODALS) -->
    <div class="statistics-container mt-4 fade-in">
        <h3 class="section-title">Statistiques du jour</h3>
        <div class="statistics-grid">
            <div class="stat-card daily-stats-card" onclick="openStatsModal('nouvelles_reparations')" style="cursor: pointer;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_journalieres['nouvelles_reparations']; ?></div>
                    <div class="stat-label">Nouvelles réparations</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
            
            <div class="stat-card daily-stats-card" onclick="openStatsModal('reparations_effectuees')" style="cursor: pointer;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_journalieres['reparations_effectuees']; ?></div>
                    <div class="stat-label">Réparations effectuées</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            
            <div class="stat-card daily-stats-card" onclick="openStatsModal('reparations_restituees')" style="cursor: pointer;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_journalieres['reparations_restituees']; ?></div>
                    <div class="stat-label">Réparations restituées</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-chart-area"></i>
                </div>
            </div>
            
            <div class="stat-card daily-stats-card" onclick="openStatsModal('devis_envoyes')" style="cursor: pointer;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats_journalieres['devis_envoyes']; ?></div>
                    <div class="stat-label">Devis envoyés</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Note: Le modal de statistiques est géré par le système existant via openStatsModal() -->

<script>
// ========================================
// GESTION DU THÈME
// ========================================
let currentTheme = localStorage.getItem('dashboard-theme') || 'day';
let particlesCreated = false;

function initTheme() {
    const dashboard = document.getElementById('dashboard');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');
    const body = document.body;
    
    console.log('Initialisation du thème:', currentTheme);
    
    if (currentTheme === 'night') {
        dashboard.classList.add('night-mode');
        body.classList.add('night-mode');
        icon.className = 'fas fa-sun';
        text.textContent = 'Mode Jour';
        if (!particlesCreated) {
            createParticles();
        }
        console.log('✅ Mode nuit activé');
    } else {
        dashboard.classList.remove('night-mode');
        body.classList.remove('night-mode');
        icon.className = 'fas fa-moon';
        text.textContent = 'Mode Nuit';
        removeParticles();
        console.log('✅ Mode jour activé');
    }
}

function toggleTheme() {
    currentTheme = currentTheme === 'day' ? 'night' : 'day';
    localStorage.setItem('dashboard-theme', currentTheme);
    initTheme();
}

// ========================================
// PARTICULES FLOTTANTES (MODE NUIT)
// ========================================
function createParticles() {
    const container = document.getElementById('particles');
    const particleCount = 50;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
        container.appendChild(particle);
    }
    particlesCreated = true;
}

function removeParticles() {
    const container = document.getElementById('particles');
    container.innerHTML = '';
    particlesCreated = false;
}

// ========================================
// MODALS DE STATISTIQUES
// ========================================
// Système intelligent de gestion des statistiques avancées
(function() {
    let pendingRequests = [];
    let systemReady = false;
    
    // Vérifier si le système est déjà prêt
    function checkSystemReady() {
        return window.advancedStats && typeof window.advancedStats.openModal === 'function';
    }
    
    // Traiter les demandes en attente
    function processPendingRequests() {
        console.log('🚀 Traitement des demandes en attente:', pendingRequests.length);
        
        while (pendingRequests.length > 0) {
            const request = pendingRequests.shift();
            console.log('📊 Ouverture du modal en attente pour:', request.statType);
            window.advancedStats.openModal(request.statType);
        }
    }
    
    // Écouter l'événement de prêt du système
    window.addEventListener('advancedStatsReady', function() {
        console.log('✅ Système de statistiques avancé prêt !');
        systemReady = true;
        processPendingRequests();
    });
    
    // Fonction principale d'ouverture des modals
    window.openStatsModal = function(statType) {
        console.log('🔄 Demande d\'ouverture du modal pour:', statType);
        
        // Vérifier si le système est prêt
        if (checkSystemReady()) {
            console.log('✅ Système disponible, ouverture immédiate');
            window.advancedStats.openModal(statType);
        } else {
            console.log('⏳ Système non prêt, ajout à la file d\'attente');
            pendingRequests.push({ statType: statType });
            
            // Timeout de sécurité au cas où l'événement ne se déclenche pas
            setTimeout(function() {
                if (!systemReady && checkSystemReady()) {
                    console.log('🔧 Système détecté par timeout, traitement des demandes');
                    systemReady = true;
                    processPendingRequests();
                }
            }, 2000);
        }
    };
    
    // Vérification initiale au cas où le système serait déjà chargé
    setTimeout(function() {
        if (checkSystemReady() && !systemReady) {
            console.log('🔧 Système déjà prêt lors de la vérification initiale');
            systemReady = true;
            processPendingRequests();
        }
    }, 100);
})();

// ========================================
// GESTION DES ONGLETS
// ========================================
function switchTab(tabId) {
    console.log('Basculement vers onglet:', tabId);
    
    // Masquer tous les contenus d'onglets
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Désactiver tous les boutons d'onglets
    document.querySelectorAll('.modern-tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Activer le contenu de l'onglet sélectionné
    const selectedContent = document.getElementById(tabId);
    if (selectedContent) {
        selectedContent.classList.add('active');
    }
    
    // Activer le bouton de l'onglet sélectionné
    const selectedButton = document.querySelector(`[data-tab="${tabId}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

// ========================================
// INITIALISATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    
    // Animation au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observer tous les éléments avec fade-in
    document.querySelectorAll('.fade-in').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    console.log('✅ Page accueil-modern initialisée');
});

// Charger les scripts et styles du système de statistiques avancé
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 Chargement du système de statistiques avancé...');
    
    // Charger les styles du système de statistiques en premier
    const statsCSS = document.createElement('link');
    statsCSS.rel = 'stylesheet';
    statsCSS.href = 'assets/css/advanced-stats-system.css';
    document.head.appendChild(statsCSS);
    
    // Fonction pour charger Chart.js puis le système de stats
    function loadStatsSystem() {
        if (typeof Chart === 'undefined') {
            console.log('📊 Chargement de Chart.js...');
            const chartScript = document.createElement('script');
            chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            chartScript.onload = function() {
                console.log('✅ Chart.js chargé, chargement du système de stats...');
                loadAdvancedStatsScript();
            };
            chartScript.onerror = function() {
                console.error('❌ Erreur lors du chargement de Chart.js');
                loadAdvancedStatsScript(); // Charger quand même le système
            };
            document.head.appendChild(chartScript);
        } else {
            console.log('📊 Chart.js déjà disponible');
            loadAdvancedStatsScript();
        }
    }
    
    // Fonction pour charger le script du système de statistiques
    function loadAdvancedStatsScript() {
        const statsScript = document.createElement('script');
        statsScript.src = 'assets/js/advanced-stats-system.js';
        statsScript.onload = function() {
            console.log('✅ Système de statistiques avancé chargé avec succès');
        };
        statsScript.onerror = function() {
            console.error('❌ Erreur lors du chargement du système de statistiques');
        };
        document.head.appendChild(statsScript);
    }
    
    // Démarrer le chargement
    loadStatsSystem();
});

// ========================================
// DÉTECTION TACTILE
// ========================================
function isTouchDevice() {
    return (('ontouchstart' in window) ||
           (navigator.maxTouchPoints > 0) ||
           (navigator.msMaxTouchPoints > 0));
}

// Ajuster les interactions pour les appareils tactiles
if (isTouchDevice()) {
    document.body.classList.add('touch-device');
    
    // Gestion des touches pour les cartes
    document.querySelectorAll('.action-btn, .stat-card, .table-row, .daily-stats-card').forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        element.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
}
</script>
