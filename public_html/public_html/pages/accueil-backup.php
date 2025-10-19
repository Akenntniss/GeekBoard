<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil');
    exit();
}
 
// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    // La fonction checkSubscriptionAccess() gère la redirection automatique
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

// Récupérer les commandes récentes
$commandes_recentes = [];
try {
    $shop_pdo = getShopDBConnection();
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
    error_log("Erreur lors de la récupération des commandes récentes: " . $e->getMessage());
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
            WHERE DATE(date_reception) = ? AND statut_categorie = 1
        ");
        $stmt->execute([$date]);
        $nouvelles_reparations = $stmt->fetchColumn();
        
        // Réparations effectuées du jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_modification) = ? AND (statut = 'reparation_effectue' OR statut_categorie = 4)
        ");
        $stmt->execute([$date]);
        $reparations_effectuees = $stmt->fetchColumn();
        
        // Réparations restituées du jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reparations 
            WHERE DATE(date_modification) = ? AND statut = 'restitue'
        ");
        $stmt->execute([$date]);
        $reparations_restituees = $stmt->fetchColumn();
        
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

<?php 
// ⭐ AFFICHER LE BANDEAU D'AVERTISSEMENT SI L'ESSAI VA EXPIRER
displayTrialWarning(); 
?>

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

<div id="mainContent" style="display: none;">

<!-- Police Orbitron pour l'aspect futuriste -->
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- Styles spécifiques pour le tableau de bord -->
<link href="assets/css/dashboard-new.css" rel="stylesheet">

<!-- Styles futuristes ultra-avancés -->
<link href="assets/css/dashboard-futuristic.css" rel="stylesheet">

<!-- Améliorations complémentaires du dashboard -->
<link href="assets/css/dashboard-enhancements.css" rel="stylesheet">

<!-- Design ultra-moderne révolutionnaire -->
<link href="assets/css/dashboard-ultra-modern.css" rel="stylesheet">

<!-- Tableaux et onglets avancés -->
<link href="assets/css/dashboard-tables-advanced.css" rel="stylesheet">

<!-- Effets spéciaux et micro-interactions -->
<link href="assets/css/dashboard-special-effects.css" rel="stylesheet">

<!-- Correction arrière-plan animé et z-index -->
<link href="assets/css/dashboard-background-fix.css" rel="stylesheet">

<!-- Correction des débordements et rotations -->
<link href="assets/css/dashboard-overflow-fix.css" rel="stylesheet">

<!-- Boutons d'action modernes -->
<link href="assets/css/action-buttons-modern.css" rel="stylesheet">

<!-- Améliorations du header existant (glassmorphism + nouveau bouton) -->
<link href="assets/css/header-improvements.css" rel="stylesheet">

<!-- Animations simples et performantes -->
<link href="assets/css/dashboard-simple-animations.css" rel="stylesheet">

<!-- Design unifié pour tous les boutons et statistiques -->
<link href="assets/css/unified-button-design.css" rel="stylesheet">

<!-- Thème professionnel mode clair -->
<link href="assets/css/professional-light-theme.css" rel="stylesheet">

<!-- Corrections typographie mode nuit -->
<link href="assets/css/night-mode-typography-fix.css" rel="stylesheet">

<!-- Styles pour le modal de commande -->
<link href="assets/css/modern-theme.css" rel="stylesheet">
<link href="assets/css/order-form.css" rel="stylesheet">

<!-- Correction pour tableaux côte à côte -->
<style>
.dashboard-tables-container {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 1.5rem !important;
    margin-top: 2rem !important;
    margin-bottom: 2rem !important;
    width: 100% !important;
}

.table-section {
    display: flex !important;
    flex-direction: column !important;
    background: var(--bg-card, #fff) !important;
    border-radius: 10px !important;
    box-shadow: var(--shadow, 0 2px 4px rgba(0,0,0,0.05)) !important;
    padding: 1rem !important;
    height: 100% !important;
    border: 1px solid var(--border-color, #e5e7eb) !important;
    color: var(--text-primary, #111827) !important;
    transition: background-color var(--transition-fast, 0.15s), color var(--transition-fast, 0.15s), border-color var(--transition-fast, 0.15s) !important;
}

/* Mode sombre pour les sections de tableau */
body.dark-mode .table-section {
    background: var(--dark-card-bg, #1f2937) !important;
    border-color: var(--dark-border-color, #374151) !important;
    color: var(--dark-text-primary, #f9fafb) !important;
    box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.2)) !important;
}

@media (max-width: 1400px) {
    .dashboard-tables-container {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 992px) {
    .dashboard-tables-container {
        grid-template-columns: 1fr !important;
    }
    
    /* Masquer certaines colonnes sur les écrans moyens et mobiles */
    .hide-md {
        display: none !important;
    }
}

@media (max-width: 768px) {
    /* Masquer les colonnes additionnelles sur mobile */
    .hide-sm {
        display: none !important;
    }
}

.order-date, .order-quantity, .order-price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted, #6b7280);
}

.order-price {
    font-weight: 600;
    color: var(--primary, #4361ee);
}

/* Mode sombre pour les éléments de commande */
body.dark-mode .order-date,
body.dark-mode .order-quantity,
body.dark-mode .order-price {
    color: var(--dark-text-secondary, #e5e7eb);
}

body.dark-mode .order-price {
    color: var(--primary, #6282ff);
}

.tabs-header .badge {
    font-size: 0.75rem;
    padding: 3px 6px;
    border-radius: 10px;
}

/* Style pour les boutons d'onglets */
.tab-button {
    padding: 10px 20px;
    border: none;
    background: none;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 2px solid transparent;
    color: var(--text-primary, #111827);
}

.tab-button.active {
    color: var(--primary, #4361ee);
    border-bottom: 2px solid var(--primary, #4361ee);
    background-color: rgba(67, 97, 238, 0.1);
}

.tab-button:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

/* Mode sombre pour les boutons d'onglets */
body.dark-mode .tab-button {
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .tab-button.active {
    color: var(--primary, #6282ff);
    border-bottom-color: var(--primary, #6282ff);
    background-color: rgba(98, 130, 255, 0.15);
}

body.dark-mode .tab-button:hover {
    background-color: rgba(98, 130, 255, 0.1);
}

/* Mode sombre pour les tableaux dans les sections */
body.dark-mode .table-section .table {
    background-color: transparent !important;
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .table-section .table th {
    background-color: var(--dark-bg-tertiary, #1e293b) !important;
    color: var(--dark-text-primary, #f9fafb) !important;
    border-color: var(--dark-border-color, #374151) !important;
}

body.dark-mode .table-section .table td {
    color: var(--dark-text-primary, #f9fafb) !important;
    border-color: var(--dark-border-color, #374151) !important;
}

/* SUPPRESSION COMPLÈTE des effets de survol des tableaux */
.table-section .table tbody tr:hover,
body.dark-mode .table-section .table tbody tr:hover,
.table tbody tr:hover,
body.dark-mode .table tbody tr:hover {
    background-color: inherit !important;
    background: inherit !important;
    transition: none !important;
}

/* Forcer le fond sombre permanent pour toutes les lignes en mode nuit */
body.dark-mode .table-section .table tbody tr {
    background-color: var(--dark-card-bg, #1f2937) !important;
}

/* Désactiver COMPLÈTEMENT tous les effets de survol des tableaux */
.table tbody tr,
.table-section .table tbody tr,
body.dark-mode .table-section .table tbody tr,
body.dark-mode .table tbody tr {
    transition: none !important;
}

.table tbody tr:hover,
.table-section .table tbody tr:hover,
body.dark-mode .table-section .table tbody tr:hover,
body.dark-mode .table tbody tr:hover {
    background-color: inherit !important;
    background: inherit !important;
    transform: none !important;
    box-shadow: none !important;
}

/* ======================== TABLEAUX MODERNES REDESIGNÉS ======================== */

/* Container principal pour les nouveaux tableaux modernes */
.modern-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    margin: 0;
    border: 1px solid #e5e7eb;
    position: relative;
}

/* Bande décorative en haut du tableau */
.modern-table::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    z-index: 1;
}

/* En-tête des colonnes redesigné */
.modern-table-columns {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 8px 24px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    margin-top: 4px; /* Pour la bande décorative */
    position: relative;
}

.modern-table-columns::before {
    content: '';
    position: absolute;
    left: 24px;
    right: 24px;
    bottom: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, #667eea 50%, transparent 100%);
}

.modern-table-columns span {
    font-weight: 700;
    color: #374151;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    position: relative;
}

/* Lignes de données améliorées */
.modern-table-row {
    display: flex;
    align-items: center;
    padding: 8px 24px;
    background: white;
    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
    cursor: pointer;
    transition: none !important;
    position: relative;
}

.modern-table-row:nth-child(even) {
    background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
}

.modern-table-row:last-child {
    border-bottom: none;
    border-radius: 0 0 12px 12px;
}

/* Effet subtil sur les côtés */
.modern-table-row::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: transparent;
    transition: none !important;
}

/* Indicateur coloré redesigné */
.modern-table-indicator {
    width: 4px;
    height: 35px;
    border-radius: 2px;
    margin-right: 18px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.modern-table-indicator.taches {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.modern-table-indicator.reparations {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.modern-table-indicator.commandes {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

/* Avatar moderne redesigné */
.modern-avatar {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 14px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
    position: relative;
}

.modern-avatar::before {
    content: '';
    position: absolute;
    inset: 1px;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 100%);
    border-radius: 11px;
    pointer-events: none;
}

.modern-avatar i {
    color: white;
    font-size: 13px;
    position: relative;
    z-index: 1;
}

/* Contenu des cellules */
.modern-table-cell {
    display: flex;
    align-items: center;
}

.modern-table-cell.primary {
    flex: 1;
}

.modern-table-cell.secondary {
    width: 35%;
    padding-right: 16px;
}

.modern-table-cell.tertiary {
    width: 25%;
    text-align: center;
}

.modern-table-text {
    color: #212529;
    font-weight: 500;
    font-size: 14px;
}

.modern-table-subtext {
    color: #6c757d;
    font-weight: 400;
    font-size: 14px;
}

/* Badges modernes redesignés */
.modern-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 16px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.15);
    position: relative;
    overflow: hidden;
}

.modern-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: none !important;
}

.modern-badge.danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
}

.modern-badge.warning {
    background: linear-gradient(135deg, #feca57, #ff9ff3);
    color: white;
}

.modern-badge.info {
    background: linear-gradient(135deg, #48cae4, #0077b6);
    color: white;
}

.modern-badge.secondary {
    background: linear-gradient(135deg, #9ca3af, #6b7280);
    color: white;
}

/* ======================== BADGES PRIORITÉ AVEC DÉGRADÉS ======================== */

/* Badge Haute priorité */
.modern-badge.bg-danger,
.modern-badge.badge-danger {
    background: linear-gradient(135deg, #ff4757, #c44569) !important;
    color: white !important;
    border: none !important;
}

/* Badge Moyenne priorité */
.modern-badge.bg-warning,
.modern-badge.badge-warning {
    background: linear-gradient(135deg, #ffa502, #ff6348) !important;
    color: white !important;
    border: none !important;
}

/* Badge Faible priorité */
.modern-badge.bg-info,
.modern-badge.badge-info {
    background: linear-gradient(135deg, #3742fa, #2f3542) !important;
    color: white !important;
    border: none !important;
}

/* Badge Normale */
.modern-badge.bg-secondary,
.modern-badge.badge-secondary {
    background: linear-gradient(135deg, #57606f, #3d4454) !important;
    color: white !important;
    border: none !important;
}

/* Badge Urgente */
.modern-badge.bg-primary,
.modern-badge.badge-primary {
    background: linear-gradient(135deg, #5352ed, #40407a) !important;
    color: white !important;
    border: none !important;
}

/* Date badge */
.modern-date-badge {
    background: #f1f3f4;
    padding: 8px 14px;
    border-radius: 12px;
    display: inline-block;
}

.modern-date-badge span {
    color: #6c757d;
    font-size: 12px;
    font-weight: 500;
}

/* État vide */
.modern-table-empty {
    padding: 60px 24px;
    text-align: center;
}

.modern-table-empty i {
    font-size: 32px;
    opacity: 0.3;
    margin-bottom: 16px;
    display: block;
    color: #6c757d;
}

.modern-table-empty .title {
    color: #6c757d;
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 8px;
}

.modern-table-empty .subtitle {
    color: #9ca3af;
    font-size: 13px;
    margin: 0;
}

/* ======================== MODE NUIT REDESIGNÉ ======================== */

body.dark-mode .modern-table {
    background: #1f2937;
    border-color: #374151;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.4);
}

body.dark-mode .modern-table::before {
    background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
}

body.dark-mode .modern-table-columns {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    border-bottom-color: #374151;
}

body.dark-mode .modern-table-columns::before {
    background: linear-gradient(90deg, transparent 0%, #3b82f6 50%, transparent 100%);
}

body.dark-mode .modern-table-columns span {
    color: #e5e7eb;
}

body.dark-mode .modern-table-row {
    background: #1f2937 !important;
    border-bottom-color: rgba(55, 65, 81, 0.5);
}

body.dark-mode .modern-table-row:nth-child(even) {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%) !important;
}

body.dark-mode .modern-table-text {
    color: #f9fafb;
}

body.dark-mode .modern-table-subtext {
    color: #d1d5db;
}

body.dark-mode .modern-date-badge {
    background: #374151;
}

body.dark-mode .modern-date-badge span {
    color: #d1d5db;
}

body.dark-mode .modern-table-empty .title {
    color: #d1d5db;
}

body.dark-mode .modern-table-empty .subtitle {
    color: #9ca3af;
}

body.dark-mode .modern-table-empty i {
    color: #6b7280;
}

/* ======================== CORRECTION FOND AVATAR ENGRENAGE MODE NUIT ======================== */

/* Correction du fond de l'avatar engrenage en mode nuit */
body.dark-mode .modern-avatar[style*="fed6e3"] {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
}

/* Alternative pour tous les avatars engrenage */
body.dark-mode .modern-table-row .modern-avatar[style*="a8edea"] {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
}

/* ======================== ONGLETS MODERNES ======================== */

.modern-tabs {
    display: flex;
    gap: 8px;
    align-items: center;
}

.modern-tab-button {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.8);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
}

.modern-tab-button:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.95);
    transform: translateY(-1px);
}

.modern-tab-button.active {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.4);
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(255, 255, 255, 0.15);
}

/* Mode jour */
body:not(.dark-mode) .modern-tab-button {
    background: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.1);
    color: rgba(0, 0, 0, 0.7);
}

body:not(.dark-mode) .modern-tab-button:hover {
    background: rgba(0, 0, 0, 0.08);
    border-color: rgba(0, 0, 0, 0.15);
    color: rgba(0, 0, 0, 0.9);
}

body:not(.dark-mode) .modern-tab-button.active {
    background: rgba(0, 0, 0, 0.12);
    border-color: rgba(0, 0, 0, 0.2);
    color: #000000;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* ======================== ALIGNEMENT ET ESPACEMENT UNIFORMES ======================== */

/* Container principal des tableaux */
.dashboard-tables-container {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}

/* Sections de tableaux */
.table-section {
    flex: 1;
    margin-bottom: 0 !important;
}

/* En-têtes uniformes */
.table-section-header {
    margin-bottom: 0.5rem !important;
    padding-bottom: 0 !important;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Conteneurs de tableaux */
.table-container {
    margin-top: 0 !important;
}

/* Tableaux modernes avec espacement uniforme */
.modern-table {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}

body.dark-mode .table-section h5,
body.dark-mode .table-section h4,
body.dark-mode .table-section h3 {
    color: var(--dark-text-primary, #f9fafb) !important;
}

/* Mode sombre pour les contenus de tableaux avec styles inline */
body.dark-mode .table-section [style*="background: white"] {
    background: var(--dark-card-bg, #1f2937) !important;
}

body.dark-mode .table-section [style*="background: #f8f9fa"] {
    background: var(--dark-bg-tertiary, #1e293b) !important;
}

body.dark-mode .table-section [style*="background: #ffffff"] {
    background: var(--dark-card-bg, #1f2937) !important;
}

body.dark-mode .table-section [style*="background: #fafbfc"] {
    background: var(--dark-card-bg, #1f2937) !important;
}

body.dark-mode .table-section [style*="color: #212529"] {
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .table-section [style*="color: #495057"] {
    color: var(--dark-text-secondary, #e5e7eb) !important;
}

body.dark-mode .table-section [style*="color: #6c757d"] {
    color: var(--dark-text-muted, #9ca3af) !important;
}

body.dark-mode .table-section [style*="border-bottom: 1px solid #e9ecef"],
body.dark-mode .table-section [style*="border-bottom: 1px solid #f1f3f4"] {
    border-bottom-color: var(--dark-border-color, #374151) !important;
}

/* En-têtes de tableaux en mode sombre */
body.dark-mode .table-section-header {
    color: var(--dark-text-primary, #f9fafb) !important;
    border-bottom-color: var(--dark-border-color, #374151) !important;
}

body.dark-mode .table-section-title {
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .table-section-title a {
    color: var(--dark-text-primary, #f9fafb) !important;
}

/* SUPPRESSION des transitions pour éviter tout effet de survol */
body.dark-mode .table-section [onmouseover] {
    transition: none !important;
}

/* SUPPRESSION COMPLÈTE de l'effet hover pour les éléments onmouseover */
.table-section [onmouseover]:hover,
body.dark-mode .table-section [onmouseover]:hover {
    background-color: inherit !important;
    background: inherit !important;
    transition: none !important;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Styles pour les badges de statut */
.status-badge {
    display: inline-block;
    padding: 0.25em 0.5em;
    font-size: 0.7rem;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 20px;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s;
    letter-spacing: 0.01em;
    text-transform: uppercase;
    background-image: linear-gradient(to bottom, rgba(255,255,255,0.15), rgba(0,0,0,0.05));
}

.status-badge-primary {
    background-color: #0d6efd;
}

.status-badge-success {
    background-color: #28a745;
}

.status-badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.status-badge-danger {
    background-color: #dc3545;
}

.status-badge-info {
    background-color: #17a2b8;
}

.status-badge-secondary {
    background-color: #6c757d;
}

/* ======================== MODAL MODERNE DES TÂCHES ======================== */

/* Container principal du modal */
.modern-task-modal {
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    background: transparent;
    backdrop-filter: blur(10px);
}

/* En-tête moderne avec dégradé */
.modern-task-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 24px 30px;
    border: none;
    position: relative;
    overflow: hidden;
}

.modern-task-modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    opacity: 0.3;
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    z-index: 2;
}

.modal-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.modal-icon i {
    color: white;
    font-size: 20px;
}

.modal-title-section .modal-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.modal-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 4px 0 0 0;
    font-weight: 400;
}

.modern-close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    z-index: 3;
}

.modern-close-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: rotate(90deg);
}

.modern-close-btn i {
    color: white;
    font-size: 14px;
}

/* Corps du modal */
.modern-task-modal-body {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding: 30px;
}

/* Section titre et métadonnées */
.task-header-section {
    margin-bottom: 25px;
}

.modern-task-title {
    color: #1a202c;
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 15px;
    line-height: 1.3;
}

.task-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.priority-container,
.task-status-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.priority-label,
.status-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modern-priority-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.modern-status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

/* Section description */
.task-description-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #f1f5f9;
}

/* Section pièces jointes */
.task-attachments-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #f1f5f9;
}

.attachments-list {
    margin-top: 16px;
}

.attachment-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 16px;
}

.attachment-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.attachment-item:last-child {
    margin-bottom: 0;
}

.attachment-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.attachment-info {
    flex: 1;
    min-width: 0;
}

.attachment-name {
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 4px;
    word-break: break-word;
}

.attachment-size {
    color: #64748b;
    font-size: 0.85rem;
}

.attachment-actions {
    flex-shrink: 0;
}

.attachment-actions .btn {
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.attachment-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.section-title {
    color: #1a202c;
    font-weight: 700;
    margin: 0;
    font-size: 1.1rem;
}

.description-loader {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #64748b;
    font-size: 0.9rem;
}

.loader-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e2e8f0;
    border-top: 2px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.modern-description {
    color: #374151;
    line-height: 1.6;
    font-size: 0.95rem;
    margin: 0;
}

/* Section informations */
.task-info-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #f1f5f9;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.info-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    flex-shrink: 0;
}

.info-content {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: #1a202c;
    font-weight: 600;
    font-size: 0.95rem;
}

/* Gestion des erreurs */
.modern-error-container {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #fca5a5;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 20px;
}

.modern-error-container i {
    color: #dc2626;
    font-size: 18px;
}

.error-message {
    color: #b91c1c;
    font-weight: 500;
}

/* Pied du modal */
.modern-task-modal-footer {
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    padding: 25px 30px;
    border-top: 1px solid #e2e8f0;
}

.footer-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.primary-actions {
    display: flex;
    gap: 12px;
}

.modern-action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    position: relative;
    overflow: hidden;
    min-width: 140px;
}

.modern-action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.modern-action-btn:hover::before {
    left: 100%;
}

.start-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.start-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.complete-btn {
    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.complete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.btn-icon {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.btn-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.btn-text {
    font-size: 0.95rem;
    font-weight: 700;
}

.btn-subtext {
    font-size: 0.75rem;
    opacity: 0.8;
    font-weight: 400;
}

.modern-link-btn {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    padding: 10px 16px;
    border-radius: 10px;
    transition: all 0.3s ease;
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.modern-link-btn:hover {
    background: rgba(102, 126, 234, 0.15);
    transform: translateX(4px);
    color: #5a67d8;
}

/* Mode sombre */
body.dark-mode .modern-task-modal-body {
    background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
}

body.dark-mode .task-description-section,
body.dark-mode .task-info-section,
body.dark-mode .task-attachments-section {
    background: #374151;
    border-color: #4b5563;
}

body.dark-mode .attachment-item {
    background: #1f2937;
    border-color: #4b5563;
}

body.dark-mode .attachment-item:hover {
    background: #111827;
    border-color: #6b7280;
}

body.dark-mode .attachment-name {
    color: #f9fafb;
}

body.dark-mode .attachment-size {
    color: #9ca3af;
}

body.dark-mode .modern-task-title {
    color: #f9fafb;
}

body.dark-mode .section-title {
    color: #f9fafb;
}

body.dark-mode .modern-description {
    color: #e5e7eb;
}

body.dark-mode .info-label {
    color: #9ca3af;
}

body.dark-mode .info-value {
    color: #f9fafb;
}

body.dark-mode .modern-task-modal-footer {
    background: linear-gradient(180deg, #374151 0%, #1f2937 100%);
    border-top-color: #4b5563;
}

body.dark-mode .modern-link-btn {
    background: rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.3);
    color: #a5b4fc;
}

body.dark-mode .modern-link-btn:hover {
    background: rgba(102, 126, 234, 0.3);
    color: #c7d2fe;
}

/* Responsivité */
@media (max-width: 768px) {
    .modern-task-modal-header {
        padding: 20px;
    }
    
    .modern-task-modal-body {
        padding: 20px;
    }
    
    .footer-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .primary-actions {
        width: 100%;
        justify-content: center;
    }
    
    .modern-action-btn {
        flex: 1;
        justify-content: center;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .task-meta {
        flex-direction: column;
        gap: 10px;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 10px;
    }
    
    .modern-task-modal {
        border-radius: 16px;
    }
    
    .modern-task-title {
        font-size: 1.4rem;
    }
}
</style>

<div class="modern-dashboard futuristic-dashboard-container futuristic-enabled">
    <!-- Éléments futuristes de base (générés par JS) -->
    
    <!-- Actions rapides -->
    <?php include 'components/quick-actions.php'; ?>

    <!-- État des réparations -->
    <div class="statistics-container futuristic-card">
        <h3 class="section-title holographic-text">État des réparations</h3>
        <div class="statistics-grid futuristic-stats-grid">
            <a href="index.php?page=reparations&statut_ids=1,2,3,19,20" class="stat-card futuristic-stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon stat-icon-futuristic">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value stat-value-futuristic"><?php echo $reparations_actives; ?></div>
                    <div class="stat-label stat-label-futuristic">Réparation</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=taches" class="stat-card progress-card futuristic-stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon stat-icon-futuristic">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value stat-value-futuristic"><?php echo $taches_recentes_count; ?></div>
                    <div class="stat-label stat-label-futuristic">Tâche</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=commandes_pieces" class="stat-card waiting-card futuristic-stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon stat-icon-futuristic">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value stat-value-futuristic"><?php echo $reparations_en_attente; ?></div>
                    <div class="stat-label stat-label-futuristic">Commande</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=reparations&urgence=1" class="stat-card clients-card futuristic-stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon stat-icon-futuristic">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value stat-value-futuristic"><?php echo $reparations_en_cours; ?></div>
                    <div class="stat-label stat-label-futuristic">Urgence</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Tableaux côte à côte -->
    <div class="dashboard-tables-container futuristic-tables-container">
        
        <!-- Tableau 1: Tâches en cours -->
        <div class="simple-table-section">
            <h4 class="table-title">
                <i class="fas fa-tasks"></i>
                <a href="index.php?page=taches" style="text-decoration: none; color: inherit;">
                    Tâches en cours
                    <span class="badge bg-primary ms-2"><?php echo $taches_recentes_count; ?></span>
                </a>
            </h4>
            <div class="modern-tabs" style="margin-bottom: 1rem;">
                <button class="modern-tab-button active" data-tab="toutes-taches">Toutes</button>
                <button class="modern-tab-button" data-tab="mes-taches">Mes tâches</button>
            </div>
            <!-- 🎯 TABLEAU TÂCHES PARFAITEMENT ALIGNÉ -->
            <div class="table-container">
                <div class="tab-content active" id="toutes-taches">
                    <div class="modern-table">
                        <div class="modern-table-columns">
                            <span style="flex: 1;">Titre</span>
                            <span style="width: 30%; text-align: center;">Priorité</span>
                        </div>
                        <?php
                        $toutes_taches = get_toutes_taches_en_cours(10);
                        if (!empty($toutes_taches)) :
                            foreach ($toutes_taches as $index => $tache) :
                                $urgence_class = get_urgence_class($tache['urgence']);
                        ?>
                            <div class="modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                <div class="modern-table-indicator taches"></div>
                                <div class="modern-table-cell primary">
                                    <span class="modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></span>
                                </div>
                                <div class="modern-table-cell" style="width: 30%; text-align: center;">
                                    <span class="modern-badge <?php echo $urgence_class; ?>"><?php echo htmlspecialchars($tache['urgence']); ?></span>
                                </div>
                            </div>
                        <?php
                            endforeach;
                        else :
                        ?>
                            <div class="modern-table-empty">
                                <i class="fas fa-tasks"></i>
                                <div class="title">Aucune tâche en cours</div>
                                <p class="subtitle">Toutes les tâches ont été complétées</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tab-content" id="mes-taches">
                    <div class="modern-table">
                            <div class="modern-table-columns">
                                <span style="flex: 1;">Titre</span>
                                <span style="width: 30%; text-align: center;">Priorité</span>
                            </div>
                            <?php
                            $mes_taches = get_taches_en_cours(10);
                            if (!empty($mes_taches)) :
                                foreach ($mes_taches as $index => $tache) :
                                    $urgence_class = get_urgence_class($tache['urgence']);
                            ?>
                                <div class="modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                    <div class="modern-table-indicator taches"></div>
                                    <div class="modern-table-cell primary">
                                        <span class="modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></span>
                                    </div>
                                    <div class="modern-table-cell" style="width: 30%; text-align: center;">
                                        <span class="modern-badge <?php echo $urgence_class; ?>"><?php echo htmlspecialchars($tache['urgence']); ?></span>
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else :
                            ?>
                                <div class="modern-table-empty">
                                    <i class="fas fa-tasks"></i>
                                    <div class="title">Aucune tâche en cours</div>
                                    <p class="subtitle">Toutes les tâches ont été complétées</p>
                                </div>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau 2: Réparations récentes -->
        <div class="simple-table-section">
            <h4 class="table-title">
                <i class="fas fa-wrench"></i>
                <a href="index.php?page=reparations" style="text-decoration: none; color: inherit;">
                    Réparations récentes
                    <span class="badge bg-primary ms-2"><?php echo $reparations_recentes_count; ?></span>
                </a>
            </h4>
            <div class="modern-table">
                        <div class="modern-table-columns">
                            <span style="flex: 1;">Client</span>
                            <span style="width: 35%;">Modèle</span>
                            <span style="width: 25%; text-align: center;">Date</span>
                        </div>
                        <?php if (count($reparations_recentes) > 0): ?>
                            <?php foreach ($reparations_recentes as $index => $reparation): ?>
                                <div class="modern-table-row" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                                    <div class="modern-table-indicator reparations"></div>
                                    <div class="modern-table-cell primary">
                                        <div class="modern-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span class="modern-table-text"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="modern-table-cell secondary">
                                        <span class="modern-table-subtext"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></span>
                                    </div>
                                    <div class="modern-table-cell tertiary">
                                        <div class="modern-date-badge">
                                            <span><?php echo format_date($reparation['date_reception'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="modern-table-empty">
                                <i class="fas fa-wrench"></i>
                                <div class="title">Aucune réparation récente</div>
                                <p class="subtitle">Aucune réparation en cours actuellement</p>
                            </div>
                        <?php endif; ?>
                    </div>
        </div>

        <!-- Tableau 3: Commandes à traiter -->
        <div class="simple-table-section">
            <h4 class="table-title">
                <i class="fas fa-shopping-cart"></i>
                <a href="index.php?page=commandes_pieces" style="text-decoration: none; color: inherit;">
                    Commandes à traiter
                </a>
            </h4>
            <div class="modern-table">
                        <div class="modern-table-columns">
                            <span style="flex: 1;">Pièce</span>
                            <span style="width: 30%; text-align: center;">Statut</span>
                            <span style="width: 25%; text-align: center;">Date</span>
                        </div>
                        <?php if (count($commandes_recentes) > 0): ?>
                            <?php foreach ($commandes_recentes as $index => $commande): ?>
                                <?php 
                                $status_class = '';
                                $status_text = '';
                                switch($commande['statut']) {
                                    case 'en_attente':
                                        $status_class = 'warning';
                                        $status_text = 'En attente';
                                        break;
                                    case 'commande':
                                        $status_class = 'info';
                                        $status_text = 'Commandé';
                                        break;
                                    case 'recue':
                                        $status_class = 'info';
                                        $status_text = 'Reçu';
                                        break;
                                    case 'urgent':
                                        $status_class = 'danger';
                                        $status_text = 'URGENT';
                                        break;
                                }
                                ?>
                                <div class="modern-table-row" data-commande-id="<?php echo $commande['id']; ?>" onclick="afficherDetailsCommande(event, <?php echo $commande['id']; ?>)">
                                    <div class="modern-table-indicator commandes"></div>
                                    <div class="modern-table-cell primary" title="<?php echo htmlspecialchars($commande['nom_piece']); ?>">
                                        <div class="modern-avatar" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                            <i class="fas fa-cog" style="color: #666;"></i>
                                        </div>
                                        <span class="modern-table-text">
                                            <?php echo mb_strimwidth(htmlspecialchars($commande['nom_piece']), 0, 30, "..."); ?>
                                        </span>
                                    </div>
                                    <div class="modern-table-cell" style="width: 30%; text-align: center;">
                                        <span class="modern-badge <?php echo $status_class; ?> status-clickable" 
                                              onclick="ouvrirModalStatut(event, <?php echo $commande['id']; ?>, '<?php echo $commande['statut']; ?>', '<?php echo htmlspecialchars($commande['reference']); ?>', '<?php echo htmlspecialchars($commande['nom_piece']); ?>')" 
                                              data-commande-id="<?php echo $commande['id']; ?>" 
                                              data-statut="<?php echo $commande['statut']; ?>"
                                              title="Cliquer pour changer le statut">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                    <div class="modern-table-cell tertiary">
                                        <div class="modern-date-badge">
                                            <span><?php echo format_date($commande['date_creation']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="modern-table-empty">
                                <i class="fas fa-shopping-cart"></i>
                                <div class="title">Aucune commande récente</div>
                                <p class="subtitle">Aucune commande en attente de traitement</p>
                            </div>
                        <?php endif; ?>
                    </div>
        </div>
    </div>

    <!-- Statistiques journalières -->
    <div class="statistics-container mt-4">
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

<!-- Styles spécifiques pour le modal de recherche client -->
<style>
.avatar-lg {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.client-nom {
    font-size: 1.5rem;
    font-weight: 600;
}

.client-telephone {
    font-size: 1rem;
}

#clientHistoryTabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--gray);
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    background: transparent;
}

#clientHistoryTabs .nav-link.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: transparent;
}

#clientHistoryTabs .nav-link:hover:not(.active) {
    border-bottom-color: #e9ecef;
}
</style>

<!-- Modal de recherche client -->
<div class="modal fade" id="searchClientModal" tabindex="-1" aria-labelledby="searchClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchClientModalLabel">Rechercher un client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="clientSearchInput" placeholder="Nom, téléphone ou email">
                        </div>
                    <div id="searchResults" class="search-results">
                        <!-- Résultats de recherche apparaîtront ici -->
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Indicateurs principaux -->
</div>

<!-- Inclure les scripts pour le dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/modal-commande.js"></script>
<script src="assets/js/commandes-details.js"></script>
<script src="assets/js/commande-statut.js"></script>
<script src="assets/js/dashboard-commands.js"></script>
<script src="assets/js/client-historique.js"></script>
<script src="assets/js/taches.js"></script>
<script src="assets/js/dashboard-stats.js"></script>

<!-- Scripts pour interface futuriste -->
<script src="assets/js/dashboard-futuristic.js"></script>
<script src="assets/js/network-background.js"></script>

<!-- Script supprimé - optimisations manuelles appliquées -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button, .modern-tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<!-- Modal futuriste GeekBoard pour afficher les détails d'une tâche -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <!-- En-tête futuriste compact avec badges -->
            <div class="modal-header">
                <div class="modal-header-content" style="display:flex;align-items:center;gap:14px;">
                    <div class="action-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="modal-title-section" style="display:flex;flex-direction:column;gap:4px;">
                        <h5 class="modal-title" id="taskDetailsModalLabel" style="margin:0;">Détails de la tâche</h5>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="modern-priority-badge" id="task-priority"></span>
                            <span class="modern-status-badge" id="task-status">En attente</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du modal en deux colonnes -->
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row g-3">
                        <!-- Colonne gauche: titre + description + pièces jointes -->
                        <div class="col-12 col-lg-8">
                            <div class="futuristic-card" style="margin-bottom:1rem;">
                                <h3 class="section-title holographic-text" style="margin-bottom:1rem;">Titre</h3>
                                <h4 id="task-title" class="modern-task-title" style="margin:0;"></h4>
                            </div>

                            <div class="futuristic-card" style="margin-bottom:1rem;">
                                <h3 class="section-title holographic-text" style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                                    <i class="fas fa-file-alt"></i>
                                    Description
                                </h3>
                                <div class="description-content">
                                    <div id="task-description-loader" class="description-loader">
                                        <div class="loader-spinner"></div>
                                        <span>Chargement des détails...</span>
                                    </div>
                                    <p id="task-description" class="modern-description" style="display:none;"></p>
                                </div>
                            </div>

                            <div id="task-attachments" class="futuristic-card" style="display:none;">
                                <h3 class="section-title holographic-text" style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                                    <i class="fas fa-paperclip"></i>
                                    Pièces jointes
                                </h3>
                                <div class="attachments-content">
                                    <div id="task-attachments-list" class="attachments-list"></div>
                                </div>
                            </div>

                            <div id="task-error-container" class="modern-error-container" style="display:none;margin-top:1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span class="error-message"></span>
                            </div>
                        </div>

                        <!-- Colonne droite: méta-informations -->
                        <div class="col-12 col-lg-4">
                            <div class="futuristic-card">
                                <h3 class="section-title holographic-text" style="margin-bottom:1rem;">Informations</h3>
                                <div class="statistics-grid" style="display:grid;grid-template-columns:1fr;gap:12px;">
                                    <div class="stat-card futuristic-stat-card" style="padding:1rem;">
                                        <div class="stat-icon stat-icon-futuristic" style="margin-bottom:0.75rem;">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-label stat-label-futuristic">Date de création</div>
                                            <div id="task-created-date" class="stat-value stat-value-futuristic">-</div>
                                        </div>
                                    </div>
                                    <div class="stat-card futuristic-stat-card" style="padding:1rem;">
                                        <div class="stat-icon stat-icon-futuristic" style="margin-bottom:0.75rem;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-label stat-label-futuristic">Assigné à</div>
                                            <div id="task-assignee" class="stat-value stat-value-futuristic">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pied du modal avec actions (boutons standards stylés) -->
            <div class="modal-footer">
                <button id="start-task-btn" class="btn btn-primary" data-task-id="" data-status="en_cours">
                    <i class="fas fa-play me-2"></i> Démarrer
                </button>
                <button id="complete-task-btn" class="btn btn-secondary" data-task-id="" data-status="termine">
                    <i class="fas fa-check me-2"></i> Terminer
                </button>
                <a href="index.php?page=taches" id="voir-toutes-taches" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt me-2"></i> Voir toutes les tâches
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal moderne pour afficher les détails d'une commande -->
<div class="modal fade" id="commandeDetailsModal" tabindex="-1" aria-labelledby="commandeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-task-modal">
            <!-- En-tête moderne avec dégradé -->
            <div class="modern-task-modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="modal-title-section">
                        <h5 class="modal-title" id="commandeDetailsModalLabel">Détails de la commande</h5>
                        <p class="modal-subtitle">Informations complètes</p>
                    </div>
                </div>
                <button type="button" class="modern-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body modern-task-modal-body">
                <div class="commande-detail-container">
                    <!-- Section titre et statut -->
                    <div class="task-header-section">
                        <div class="task-title-container">
                            <h4 id="commande-reference" class="modern-task-title"></h4>
                            <p id="commande-piece-nom" class="task-subtitle"></p>
                            <div class="task-meta">
                                <div class="priority-container">
                                    <span class="priority-label">Statut</span>
                                    <span id="commande-statut" class="modern-priority-badge"></span>
                                </div>
                                <div class="task-status-container">
                                    <span class="status-label">Urgence</span>
                                    <span id="commande-urgence" class="modern-status-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loader pendant le chargement -->
                    <div id="commande-description-loader" class="description-loader">
                        <div class="loader-spinner"></div>
                        <span>Chargement des détails...</span>
                    </div>
                    
                    <!-- Contenu des détails de la commande -->
                    <div id="commande-details-content" style="display: none;">

                        <!-- Section Client -->
                        <div class="task-description-section">
                            <div class="section-header">
                                <i class="fas fa-user section-icon"></i>
                                <h6 class="section-title">Informations Client</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-client" class="modern-description"></p>
                                <p id="commande-client-tel" class="task-subtitle"></p>
                            </div>
                        </div>

                        <!-- Section Fournisseur -->
                        <div class="task-description-section">
                            <div class="section-header">
                                <i class="fas fa-truck section-icon"></i>
                                <h6 class="section-title">Fournisseur</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-fournisseur" class="modern-description"></p>
                            </div>
                        </div>

                        <!-- Section Détails de la pièce -->
                        <div class="task-description-section">
                            <div class="section-header">
                                <i class="fas fa-cog section-icon"></i>
                                <h6 class="section-title">Détails de la pièce</h6>
                            </div>
                            <div class="description-content">
                                <div class="row mb-2">
                                    <div class="col-12">
                                        <div class="detail-item">
                                            <span class="detail-label">Nom de la pièce:</span>
                                            <span id="commande-piece-nom-detail" class="detail-value piece-name-value"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="detail-item">
                                            <span class="detail-label">Quantité:</span>
                                            <span id="commande-quantite" class="detail-value"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="detail-item">
                                            <span class="detail-label">Prix estimé:</span>
                                            <span id="commande-prix" class="detail-value price-value"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Code-barres et Date de création -->
                        <div class="task-description-section">
                            <div class="section-header">
                                <i class="fas fa-barcode section-icon"></i>
                                <h6 class="section-title">Informations techniques</h6>
                            </div>
                            <div class="description-content">
                                <div class="row">
                                    <div id="commande-code-barre-section" class="col-md-6" style="display: none;">
                                        <div class="detail-item">
                                            <span class="detail-label">Code-barres:</span>
                                            <span id="commande-code-barre" class="detail-value font-monospace"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-item">
                                            <span class="detail-label">Date de création:</span>
                                            <span id="commande-date-creation" class="detail-value"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Description -->
                        <div id="commande-description-section" class="task-description-section" style="display: none;">
                            <div class="section-header">
                                <i class="fas fa-file-alt section-icon"></i>
                                <h6 class="section-title">Description</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-description" class="modern-description"></p>
                            </div>
                        </div>

                        <!-- Section Dates importantes -->
                        <div class="task-description-section" id="commande-dates-section">
                            <div class="section-header">
                                <i class="fas fa-calendar section-icon"></i>
                                <h6 class="section-title">Dates importantes</h6>
                            </div>
                            <div class="description-content">
                                <div class="row">
                                    <div class="col-md-6" id="commande-date-commande-section" style="display: none;">
                                        <div class="detail-item">
                                            <span class="detail-label">Date de commande:</span>
                                            <span id="commande-date-commande" class="detail-value"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="commande-date-reception-section" style="display: none;">
                                        <div class="detail-item">
                                            <span class="detail-label">Date de réception:</span>
                                            <span id="commande-date-reception" class="detail-value"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Notes -->
                        <div id="commande-notes-section" class="task-description-section" style="display: none;">
                            <div class="section-header">
                                <i class="fas fa-sticky-note section-icon"></i>
                                <h6 class="section-title">Notes</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-notes" class="modern-description"></p>
                            </div>
                        </div>

                        <!-- Section Commentaire interne -->
                        <div id="commande-commentaire-section" class="task-description-section" style="display: none;">
                            <div class="section-header">
                                <i class="fas fa-comment section-icon"></i>
                                <h6 class="section-title">Commentaire interne</h6>
                            </div>
                            <div class="description-content">
                                <p id="commande-commentaire" class="modern-description"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section d'erreur -->
                    <div id="commande-error-container" class="task-description-section" style="display:none;">
                        <div class="section-header">
                            <i class="fas fa-exclamation-triangle section-icon text-danger"></i>
                            <h6 class="section-title text-danger">Erreur</h6>
                        </div>
                        <div class="description-content">
                            <p class="error-message modern-description text-danger">Une erreur est survenue lors du chargement des détails de la commande.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pied de page moderne -->
            <div class="modern-task-modal-footer">
                <div class="footer-actions">
                    <div class="primary-actions">
                        <a href="index.php?page=commandes_pieces" class="modern-action-btn view-all-btn">
                            <div class="btn-icon">
                                <i class="fas fa-list-ul"></i>
                            </div>
                            <div class="btn-content">
                                <span class="btn-text">Voir toutes</span>
                                <span class="btn-subtext">Toutes les commandes</span>
                            </div>
                        </a>
                    </div>
                    <div class="secondary-actions">
                        <button type="button" class="modern-action-btn close-btn" data-bs-dismiss="modal">
                            <div class="btn-icon">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="btn-content">
                                <span class="btn-text">Fermer</span>
                                <span class="btn-subtext">Fermer le modal</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal moderne pour changer le statut d'une commande -->
<div class="modal fade" id="commandeStatutModal" tabindex="-1" aria-labelledby="commandeStatutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-task-modal">
            <!-- En-tête moderne avec dégradé -->
            <div class="modern-task-modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="modal-title-section">
                        <h5 class="modal-title" id="commandeStatutModalLabel">Changer le statut</h5>
                        <p class="modal-subtitle">Mettre à jour le statut de la commande</p>
                    </div>
                </div>
                <button type="button" class="modern-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body modern-task-modal-body">
                <div class="statut-update-container">
                    <!-- Section titre et statut actuel -->
                    <div class="task-header-section">
                        <div class="task-title-container">
                            <h4 id="statut-commande-reference" class="modern-task-title"></h4>
                            <p id="statut-piece-nom" class="task-subtitle"></p>
                            <div class="task-meta">
                                <div class="priority-container">
                                    <span class="priority-label">Statut actuel</span>
                                    <span id="statut-actuel" class="modern-priority-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section choix du nouveau statut -->
                    <div class="task-description-section">
                        <div class="section-header">
                            <i class="fas fa-list-alt section-icon"></i>
                            <h6 class="section-title">Choisir le nouveau statut</h6>
                        </div>
                        <div class="description-content">
                            <div class="status-options-grid">
                                <div class="status-option" data-status="en_attente">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-warning">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">En attente</h6>
                                            <p class="status-description">Pas encore commandé</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="commande">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-primary">
                                            <i class="fas fa-shopping-cart"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Commandé</h6>
                                            <p class="status-description">Commande en cours</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="recue">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-success">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Reçu</h6>
                                            <p class="status-description">Pièce réceptionnée</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="utilise">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-info">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Utilisé</h6>
                                            <p class="status-description">Pièce installée</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="urgent">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">URGENT</h6>
                                            <p class="status-description">Priorité maximale</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="a_retourner">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-secondary">
                                            <i class="fas fa-undo"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">À retourner</h6>
                                            <p class="status-description">Retour fournisseur</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="annulee">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-dark">
                                            <i class="fas fa-times"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Annulé</h6>
                                            <p class="status-description">Commande annulée</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="status-option" data-status="termine">
                                    <div class="status-option-card">
                                        <div class="status-icon bg-success">
                                            <i class="fas fa-flag-checkered"></i>
                                        </div>
                                        <div class="status-info">
                                            <h6 class="status-title">Terminé</h6>
                                            <p class="status-description">Processus terminé</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section d'erreur -->
                    <div id="statut-error-container" class="task-description-section" style="display:none;">
                        <div class="section-header">
                            <i class="fas fa-exclamation-triangle section-icon text-danger"></i>
                            <h6 class="section-title text-danger">Erreur</h6>
                        </div>
                        <div class="description-content">
                            <p class="error-message modern-description text-danger">Une erreur est survenue lors de la mise à jour du statut.</p>
                        </div>
                    </div>
                    
                    <!-- Loader -->
                    <div id="statut-update-loader" class="description-loader" style="display: none;">
                        <div class="loader-spinner"></div>
                        <span>Mise à jour en cours...</span>
                    </div>
                </div>
            </div>
            
            <!-- Pied de page moderne (sans bouton fermer) -->
            <div class="modern-task-modal-footer" style="display: none;">
            </div>
        </div>
    </div>
</div>

<!-- Modal moderne des statistiques -->
<div class="modal fade" id="statsModal" tabindex="-1" aria-labelledby="statsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modern-stats-modal">
            <!-- En-tête du modal avec dégradé -->
            <div class="modern-stats-modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon-stats">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="modal-title-section">
                        <h5 class="modal-title" id="statsModalLabel">Statistiques détaillées</h5>
                        <p class="modal-subtitle" id="statsModalSubtitle">Analyse des données</p>
                    </div>
                </div>
                <button type="button" class="modern-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body modern-stats-modal-body">
                <!-- Filtres -->
                <div class="stats-filters-section">
                    <div class="filters-container">
                        <div class="filter-group">
                            <label class="filter-label">Période</label>
                            <div class="filter-buttons">
                                <button class="filter-btn active" data-period="day" onclick="changePeriod('day')">
                                    <i class="fas fa-calendar-day"></i>
                                    Jour
                                </button>
                                <button class="filter-btn" data-period="week" onclick="changePeriod('week')">
                                    <i class="fas fa-calendar-week"></i>
                                    Semaine
                                </button>
                                <button class="filter-btn" data-period="month" onclick="changePeriod('month')">
                                    <i class="fas fa-calendar-alt"></i>
                                    Mois
                                </button>
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Date spécifique</label>
                            <div class="date-picker-container">
                                <input type="date" id="specificDate" class="form-control modern-date-input" 
                                       value="<?php echo date('Y-m-d'); ?>" onchange="changeSpecificDate()">
                                <button class="btn btn-outline-primary btn-sm" onclick="resetToToday()">
                                    <i class="fas fa-calendar-check"></i>
                                    Aujourd'hui
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section graphique -->
                <div class="stats-chart-section">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h6 class="chart-title" id="chartTitle">Évolution des nouvelles réparations</h6>
                            <div class="chart-controls">
                                <div class="chart-legend" id="chartLegend">
                                    <!-- Légende dynamique -->
                                </div>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="statsChart" width="800" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Section tableau de données -->
                <div class="stats-table-section">
                    <div class="table-header">
                        <h6 class="table-title">Données détaillées</h6>
                        <div class="table-actions">
                            <button class="modern-export-btn" onclick="exportStatsData()">
                                <i class="fas fa-download"></i>
                                <span>Exporter</span>
                            </button>
                        </div>
                    </div>
                    <div class="modern-table-container" id="tableContainer">
                        <!-- Indicateurs de défilement -->
                        <div class="scroll-indicator-top" id="scrollIndicatorTop"></div>
                        <div class="scroll-indicator-bottom" id="scrollIndicatorBottom"></div>
                        
                        <!-- Hint de défilement -->
                        <div class="scroll-hint" id="scrollHint">
                            Faites défiler pour voir plus
                            <i class="fas fa-arrows-alt-v"></i>
                        </div>
                        
                        <div class="modern-data-table" id="statsTable">
                            <div class="modern-table-header" id="statsTableHeader">
                                <!-- En-têtes dynamiques -->
                            </div>
                            <div class="modern-table-scrollable" id="tableScrollable">
                                <div class="modern-table-body" id="statsTableBody">
                                    <!-- Données dynamiques -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Loader -->
                <div id="statsLoader" class="stats-loader" style="display: none;">
                    <div class="loader-spinner"></div>
                    <span>Chargement des statistiques...</span>
                </div>
                
                <!-- Message d'erreur -->
                <div id="statsError" class="stats-error-container" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="error-message"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour le modal des statistiques -->
<style>
/* ======================== MODAL STATISTIQUES MODERNE ======================== */

.modern-stats-modal {
    border: none;
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    background: transparent;
}

.modern-stats-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px 35px;
    border: none;
    position: relative;
    overflow: hidden;
}

.modern-stats-modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    opacity: 0.3;
}

.modal-icon-stats {
    width: 55px;
    height: 55px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    position: relative;
    z-index: 2;
}

.modal-icon-stats i {
    color: white;
    font-size: 24px;
}

.modern-stats-modal-body {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding: 35px;
    max-height: 70vh;
    overflow-y: auto;
}

/* Filtres */
.stats-filters-section {
    background: white;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #f1f5f9;
}

.filters-container {
    display: flex;
    gap: 30px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.filter-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-buttons {
    display: flex;
    gap: 8px;
}

/* Styles filter-btn commentés pour permettre les couleurs modern-filter personnalisées */
/*
.filter-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 12px;
    color: #6b7280;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.filter-btn:hover {
    border-color: #667eea;
    color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.filter-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.filter-btn i {
    font-size: 0.8rem;
}
*/

.date-picker-container {
    display: flex;
    gap: 10px;
    align-items: center;
}

.modern-date-input {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    padding: 10px 14px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.modern-date-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

/* Section graphique */
.stats-chart-section {
    background: white;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #f1f5f9;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f1f5f9;
}

.chart-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}

.chart-legend {
    display: flex;
    gap: 20px;
    align-items: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.chart-wrapper {
    position: relative;
    height: 300px;
}

/* Section tableau moderne */
.stats-table-section {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #f1f5f9;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f1f5f9;
}

.table-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}

/* Bouton d'export moderne */
.modern-export-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.modern-export-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.modern-export-btn i {
    font-size: 0.8rem;
}

/* Container du tableau moderne avec défilement */
.modern-table-container {
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: white;
    position: relative;
    max-height: 500px; /* Hauteur maximale pour forcer le défilement */
}

.modern-data-table {
    width: 100%;
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
}

/* Scrollbar personnalisée pour le container */
.modern-table-scrollable {
    overflow-y: auto;
    overflow-x: hidden;
    flex: 1;
    scrollbar-width: thin;
    scrollbar-color: #667eea #f1f5f9;
    scroll-behavior: smooth;
}

/* Webkit scrollbar personnalisée */
.modern-table-scrollable::-webkit-scrollbar {
    width: 8px;
}

.modern-table-scrollable::-webkit-scrollbar-track {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 4px;
}

.modern-table-scrollable::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
    transition: all 0.3s ease;
}

.modern-table-scrollable::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #5a67d8 0%, #6b46c1 100%);
    box-shadow: 0 0 8px rgba(102, 126, 234, 0.3);
}

/* Indicateur de défilement en haut */
.scroll-indicator-top {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent 0%, #667eea 50%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 10;
    pointer-events: none;
}

.scroll-indicator-top.visible {
    opacity: 1;
}

/* Indicateur de défilement en bas */
.scroll-indicator-bottom {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent 0%, #f093fb 50%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 10;
    pointer-events: none;
}

.scroll-indicator-bottom.visible {
    opacity: 1;
}

/* Effet de fade pour le contenu qui dépasse */
.modern-table-container::before {
    content: '';
    position: absolute;
    top: 60px; /* Après l'en-tête */
    left: 0;
    right: 0;
    height: 20px;
    background: linear-gradient(180deg, rgba(255,255,255,0.9) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 5;
    pointer-events: none;
}

.modern-table-container.has-scroll::before {
    opacity: 1;
}

.modern-table-container::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 20px;
    background: linear-gradient(0deg, rgba(255,255,255,0.9) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 5;
    pointer-events: none;
}

.modern-table-container.has-scroll::after {
    opacity: 1;
}

/* Hint de défilement */
.scroll-hint {
    position: absolute;
    bottom: 10px;
    right: 15px;
    background: rgba(102, 126, 234, 0.9);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
    z-index: 15;
    pointer-events: none;
    backdrop-filter: blur(10px);
}

.scroll-hint.visible {
    opacity: 1;
    transform: translateY(0);
}

/* Styles pour les détails de commande modernes */
.detail-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.detail-label {
    font-weight: 600;
    color: #6b7280;
    margin-right: 0.5rem;
    min-width: 120px;
}

.detail-value {
    color: #1f2937;
    flex: 1;
}

.price-value {
    font-weight: 700;
    color: #059669;
}

.task-subtitle {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 0;
}

.piece-name-value {
    font-weight: 600;
    color: #1f2937;
    font-size: 1.1rem;
}

/* Styles pour les boutons du modal des commandes */
.view-all-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    text-decoration: none !important;
}

.view-all-btn:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    transform: translateY(-2px);
    color: white !important;
}

.close-btn {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
}

.close-btn:hover {
    background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
    box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
    transform: translateY(-2px);
}

/* Mode nuit - amélioration du bouton fermer */
.dark-mode .close-btn {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
    color: #f9fafb;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.dark-mode .close-btn:hover {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    border-color: rgba(59, 130, 246, 0.4);
    transform: translateY(-2px);
    color: #ffffff;
}

.dark-mode .view-all-btn {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    box-shadow: 0 4px 15px rgba(5, 150, 105, 0.4);
}

.dark-mode .view-all-btn:hover {
    background: linear-gradient(135deg, #047857 0%, #065f46 100%);
    box-shadow: 0 6px 20px rgba(5, 150, 105, 0.5);
}

.dark-mode .piece-name-value {
    color: #f9fafb;
}

/* Styles pour le modal de mise à jour de statut */
.status-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.status-option {
    cursor: pointer;
    transition: all 0.3s ease;
}

.status-option-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.status-option-card:hover {
    border-color: #3b82f6;
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.status-option-card.selected {
    border-color: #3b82f6;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.status-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.status-icon i {
    font-size: 1.25rem;
    color: white;
}

.status-info {
    flex: 1;
}

.status-title {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.status-description {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

.status-option-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    transition: left 0.5s ease;
}

.status-option:hover .status-option-card::before {
    left: 100%;
}

/* Mode nuit pour le modal de statut */
.dark-mode .status-option-card {
    background: #1f2937;
    border-color: #374151;
}

.dark-mode .status-option-card:hover {
    border-color: #60a5fa;
    background: #111827;
    box-shadow: 0 4px 12px rgba(96, 165, 250, 0.25);
}

.dark-mode .status-option-card.selected {
    border-color: #60a5fa;
    background: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
}

.dark-mode .status-title {
    color: #f9fafb;
}

.dark-mode .status-description {
    color: #d1d5db;
}

/* Style pour les badges de statut cliquables */
.status-clickable {
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    position: relative;
}

.status-clickable:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.status-clickable::after {
    content: '✏️';
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 10px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.status-clickable:hover::after {
    opacity: 1;
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .status-options-grid {
        grid-template-columns: 1fr;
    }
    
    .status-option-card {
        padding: 0.75rem;
    }
    
    .status-icon {
        width: 40px;
        height: 40px;
        margin-right: 0.75rem;
    }
    
    .status-icon i {
        font-size: 1rem;
    }
}

.scroll-hint i {
    margin-left: 6px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-4px);
    }
    60% {
        transform: translateY(-2px);
    }
}

/* En-tête du tableau moderne */
.modern-table-header {
    display: flex;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-bottom: 2px solid #667eea;
    position: relative;
    overflow: hidden;
}

.modern-table-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
}

.modern-table-header-cell {
    flex: 1;
    padding: 18px 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #374151;
    background: rgba(255, 255, 255, 0.7);
    border-right: 1px solid rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.modern-table-header-cell:last-child {
    border-right: none;
}

.modern-table-header-cell::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, transparent, #667eea, transparent);
    opacity: 0.6;
}

/* Corps du tableau moderne */
.modern-table-body {
    background: white;
}

.modern-table-row {
    display: flex;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.modern-table-row:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    transform: translateX(4px);
    box-shadow: 4px 0 12px rgba(102, 126, 234, 0.1);
}

.modern-table-row:hover::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.modern-table-row:last-child {
    border-bottom: none;
}

.modern-table-cell {
    flex: 1;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
    position: relative;
}

.modern-table-cell:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 0;
    top: 25%;
    bottom: 25%;
    width: 1px;
    background: linear-gradient(to bottom, transparent, #e5e7eb, transparent);
}

/* Cellule de date avec style spécial */
.modern-table-cell.date-cell {
    font-weight: 600;
    color: #1f2937;
}

/* Cellule de nombre avec style spécial */
.modern-table-cell.number-cell {
    font-weight: 700;
    font-size: 1rem;
    color: #667eea;
}

/* Cellule d'évolution avec couleurs */
.modern-table-cell.evolution-cell {
    font-weight: 600;
    font-size: 0.85rem;
}

.modern-table-cell.evolution-positive {
    color: #10b981;
}

.modern-table-cell.evolution-negative {
    color: #ef4444;
}

.modern-table-cell.evolution-neutral {
    color: #6b7280;
}

/* État vide du tableau */
.modern-table-empty {
    padding: 60px 20px;
    text-align: center;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-radius: 12px;
    margin: 20px;
}

.modern-table-empty-icon {
    font-size: 48px;
    color: #d1d5db;
    margin-bottom: 16px;
    opacity: 0.6;
}

.modern-table-empty-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
}

.modern-table-empty-subtitle {
    font-size: 0.9rem;
    color: #9ca3af;
}

/* Animations */
@keyframes slideInFromLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.modern-table-row {
    animation: slideInFromLeft 0.3s ease-out;
}

.modern-table-row:nth-child(even) {
    animation-delay: 0.1s;
}

.modern-table-row:nth-child(odd) {
    animation-delay: 0.05s;
}

/* Loader */
.stats-loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px;
    color: #6b7280;
    font-size: 0.95rem;
    gap: 20px;
}

.stats-error-container {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #fca5a5;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 20px;
}

.stats-error-container i {
    color: #dc2626;
    font-size: 20px;
}

/* Mode sombre */
body.dark-mode .modern-stats-modal-body {
    background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
}

body.dark-mode .stats-filters-section,
body.dark-mode .stats-chart-section,
body.dark-mode .stats-table-section {
    background: #374151;
    border-color: #4b5563;
}

body.dark-mode .filter-label,
body.dark-mode .chart-title,
body.dark-mode .table-title {
    color: #f9fafb;
}

/* Améliorations mode clair - boutons de filtre (commenté pour permettre les couleurs personnalisées) */
/*
body.dark-mode .filter-btn {
    background: #1f2937;
    border-color: #4b5563;
    color: #d1d5db;
}

body.dark-mode .filter-btn:hover {
    border-color: #6366f1;
    color: #a5b4fc;
}
*/

body.dark-mode .modern-date-input {
    background: #1f2937;
    border-color: #4b5563;
    color: #f9fafb;
}

/* Mode sombre pour le nouveau tableau moderne */
body.dark-mode .modern-export-btn {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
}

body.dark-mode .modern-export-btn:hover {
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
}

body.dark-mode .modern-table-container {
    border-color: #4b5563;
    background: #1f2937;
}

body.dark-mode .modern-table-header {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    border-bottom-color: #4f46e5;
}

body.dark-mode .modern-table-header::before {
    background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
}

body.dark-mode .modern-table-header-cell {
    color: #e5e7eb;
    background: rgba(31, 41, 55, 0.7);
    border-right-color: rgba(75, 85, 99, 0.5);
}

body.dark-mode .modern-table-header-cell::after {
    background: linear-gradient(90deg, transparent, #4f46e5, transparent);
}

body.dark-mode .modern-table-body {
    background: #1f2937;
}

body.dark-mode .modern-table-row {
    border-bottom-color: #374151;
}

body.dark-mode .modern-table-row:hover {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
    box-shadow: 4px 0 12px rgba(79, 70, 229, 0.2);
}

body.dark-mode .modern-table-row:hover::before {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
}

body.dark-mode .modern-table-cell {
    color: #e5e7eb;
}

body.dark-mode .modern-table-cell:not(:last-child)::after {
    background: linear-gradient(to bottom, transparent, #4b5563, transparent);
}

body.dark-mode .modern-table-cell.date-cell {
    color: #f3f4f6;
}

body.dark-mode .modern-table-cell.number-cell {
    color: #6366f1;
}

body.dark-mode .modern-table-cell.evolution-positive {
    color: #34d399;
}

body.dark-mode .modern-table-cell.evolution-negative {
    color: #f87171;
}

body.dark-mode .modern-table-cell.evolution-neutral {
    color: #9ca3af;
}

body.dark-mode .modern-table-empty {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
}

body.dark-mode .modern-table-empty-icon {
    color: #6b7280;
}

body.dark-mode .modern-table-empty-title {
    color: #d1d5db;
}

body.dark-mode .modern-table-empty-subtitle {
    color: #9ca3af;
}

/* Mode sombre pour le défilement */
body.dark-mode .modern-table-scrollable::-webkit-scrollbar-track {
    background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
}

body.dark-mode .modern-table-scrollable::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #4f46e5 0%, #7c3aed 100%);
}

body.dark-mode .modern-table-scrollable::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #4338ca 0%, #6d28d9 100%);
    box-shadow: 0 0 8px rgba(79, 70, 229, 0.4);
}

body.dark-mode .modern-table-scrollable {
    scrollbar-color: #4f46e5 #1f2937;
}

body.dark-mode .scroll-indicator-top {
    background: linear-gradient(90deg, transparent 0%, #4f46e5 50%, transparent 100%);
}

body.dark-mode .scroll-indicator-bottom {
    background: linear-gradient(90deg, transparent 0%, #ec4899 50%, transparent 100%);
}

body.dark-mode .modern-table-container::before {
    background: linear-gradient(180deg, rgba(31,41,55,0.9) 0%, transparent 100%);
}

body.dark-mode .modern-table-container::after {
    background: linear-gradient(0deg, rgba(31,41,55,0.9) 0%, transparent 100%);
}

body.dark-mode .scroll-hint {
    background: rgba(79, 70, 229, 0.9);
}

/* Responsivité */
@media (max-width: 768px) {
    .filters-container {
        flex-direction: column;
        gap: 20px;
    }
    
    .filter-buttons {
        flex-wrap: wrap;
    }
    
    .chart-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .table-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .date-picker-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    /* Responsivité du tableau moderne */
    .modern-table-header-cell,
    .modern-table-cell {
        padding: 12px 8px;
        font-size: 0.8rem;
    }
    
    .modern-table-header-cell {
        font-size: 0.65rem;
        letter-spacing: 0.5px;
    }
    
    .modern-table-cell.number-cell {
        font-size: 0.9rem;
    }
    
    .modern-table-cell.evolution-cell {
        font-size: 0.75rem;
    }
    
    .modern-export-btn {
        padding: 8px 16px;
        font-size: 0.8rem;
    }
    
    .modern-export-btn i {
        font-size: 0.7rem;
    }
    
    .modern-table-empty {
        padding: 40px 15px;
    }
    
    .modern-table-empty-icon {
        font-size: 36px;
    }
    
    .modern-table-empty-title {
        font-size: 1rem;
    }
    
    .modern-table-empty-subtitle {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    /* Très petits écrans */
    .modern-table-header-cell:last-child,
    .modern-table-cell:last-child {
        display: none;
    }
    
    .modern-table-header-cell,
    .modern-table-cell {
        padding: 10px 6px;
    }
    
    .modern-export-btn span {
        display: none;
    }
    
    .modern-export-btn {
        padding: 8px 12px;
    }
    
    /* Ajustements du défilement pour mobile */
    .modern-table-container {
        max-height: 400px;
    }
    
    .scroll-hint {
        font-size: 0.65rem;
        padding: 4px 8px;
        bottom: 8px;
        right: 10px;
    }
    
    .modern-table-scrollable::-webkit-scrollbar {
        width: 6px;
    }
}
</style>


</div>
</div>
</div>

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

/* Masquer le loader quand la page est chargée */
.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
#mainContent.fade-in {
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,3 seconde puis masquer le loader et afficher le contenu
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
</script>