<?php
include_once 'includes/night-mode-system.php';
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'kpi_dashboard_integrated.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=kpi_dashboard');
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    exit;
}

$kpiError = null;
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$current_user_id = $_SESSION['user_id'] ?? null;

// Minimal server KPIs as initial fallback
$kpi = [
    'completed_repairs' => 0,
    'total_revenue' => 0,
    'active_techs' => 0,
    'total_hours' => 0,
];

function gb_column_exists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch();
    } catch (Exception $e) { return false; }
}

// Preload users for selector if admin
$users = [];
try {
    $pdo = getShopDBConnection();
    if ($pdo) {
        if ($is_admin) {
            $st = $pdo->prepare("SELECT id, full_name, role FROM users WHERE role IN ('admin','technicien') ORDER BY full_name");
            $st->execute();
            $users = $st->fetchAll(PDO::FETCH_ASSOC);
        }

        $dateStart = date('Y-m-d', strtotime('-30 days'));
        $dateEnd = date('Y-m-d');

        $st = $pdo->prepare("SELECT COUNT(*) as c FROM reparations r WHERE DATE(COALESCE(r.date_modification, r.date_reception)) BETWEEN ? AND ? AND r.statut IN ('terminee','livree','reparee')");
        $st->execute([$dateStart, $dateEnd]);
        $kpi['completed_repairs'] = (int)($st->fetch()['c'] ?? 0);

        $st = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN r.statut IN ('terminee','livree','reparee') THEN r.prix_reparation END),0) as s FROM reparations r WHERE DATE(COALESCE(r.date_modification, r.date_reception)) BETWEEN ? AND ?");
        $st->execute([$dateStart, $dateEnd]);
        $kpi['total_revenue'] = (float)($st->fetch()['s'] ?? 0);

        $st = $pdo->prepare("SELECT COUNT(DISTINCT r.employe_id) as c FROM reparations r WHERE DATE(COALESCE(r.date_modification, r.date_reception)) BETWEEN ? AND ?");
        $st->execute([$dateStart, $dateEnd]);
        $kpi['active_techs'] = (int)($st->fetch()['c'] ?? 0);

        $hasWorkDuration = gb_column_exists($pdo, 'time_tracking', 'work_duration');
        if ($hasWorkDuration) {
            $st = $pdo->prepare("SELECT COALESCE(SUM(tt.work_duration),0) as h FROM time_tracking tt WHERE DATE(tt.clock_in) BETWEEN ? AND ? AND tt.status='completed'");
            $st->execute([$dateStart, $dateEnd]);
        } else {
            $st = $pdo->prepare("SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, tt.clock_in, COALESCE(tt.clock_out, NOW())))/60.0,0) as h FROM time_tracking tt WHERE DATE(tt.clock_in) BETWEEN ? AND ? AND tt.status='completed'");
            $st->execute([$dateStart, $dateEnd]);
        }
        $kpi['total_hours'] = round((float)($st->fetch()['h'] ?? 0), 2);
    }
} catch (Exception $e) {
    $kpiError = $e->getMessage();
}
?>

<style>
/* Variables CSS pour thème jour/nuit - identique à accueil-modern */
:root {
    /* Mode jour - Corporate moderne */
    --day-bg: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1a202c;
    --day-text-muted: #64748b;
    --day-border: rgba(0, 0, 0, 0.08);
    --day-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    --day-primary: #3b82f6;
    --day-primary-hover: #2563eb;
    
    /* Mode nuit - Futuriste */
    --night-bg: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    --night-card-bg: rgba(15, 23, 42, 0.85);
    --night-text: #e2e8f0;
    --night-text-muted: #94a3b8;
    --night-border: rgba(0, 212, 255, 0.2);
    --night-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.15);
    --night-primary: #00d4ff;
    --night-primary-hover: #38bdf8;
    
    /* Variables actives (jour par défaut) */
    --active-bg: var(--day-bg);
    --active-card-bg: var(--day-card-bg);
    --active-text: var(--day-text);
    --active-text-muted: var(--day-text-muted);
    --active-border: var(--day-border);
    --active-shadow: var(--day-shadow);
    --active-primary: var(--day-primary);
    --active-primary-hover: var(--day-primary-hover);
}

/* Mode nuit activé */
body.night-mode {
    --active-bg: var(--night-bg);
    --active-card-bg: var(--night-card-bg);
    --active-text: var(--night-text);
    --active-text-muted: var(--night-text-muted);
    --active-border: var(--night-border);
    --active-shadow: var(--night-shadow);
    --active-primary: var(--night-primary);
    --active-primary-hover: var(--night-primary-hover);
}

/* Layout principal - utilise le même conteneur que accueil-modern */
.kpi-main-card {
    background: var(--active-card-bg);
    border: 1px solid var(--active-border);
    border-radius: 16px;
    box-shadow: var(--active-shadow);
    overflow: hidden;
    backdrop-filter: blur(10px);
    max-width: 1400px;
    margin: 0 auto 20px auto;
}

/* Header avec titre et filtres */
.kpi-header {
    background: linear-gradient(135deg, var(--active-primary) 0%, var(--active-primary-hover) 100%);
    color: white;
    padding: 20px 24px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.kpi-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
    font-size: 1.5rem;
}

.kpi-title .icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.kpi-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
}

.kpi-filters .btn {
    border-radius: 10px;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.kpi-filters .btn-outline-light {
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.kpi-filters .btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
}

.date-range {
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-range input {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    color: white;
    padding: 8px 12px;
    font-size: 14px;
}

.date-range input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

/* Cartes KPI - même style que les cartes statistiques d'accueil */
.kpi-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 24px;
    background: var(--active-card-bg);
}

.kpi-card {
    background: var(--active-card-bg);
    border: 1px solid var(--active-border);
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--active-shadow);
    transition: all 0.3s ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}

.night-mode .kpi-card:hover {
    box-shadow: var(--night-glow);
}

.kpi-card-label {
    color: var(--active-text-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 8px;
}

.kpi-card-value {
    color: var(--active-text);
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 4px;
}

.night-mode .kpi-card-value {
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
}

.kpi-card-subtitle {
    color: var(--active-text-muted);
    font-size: 12px;
}

/* Contenu principal avec onglets */
.kpi-content {
    background: var(--active-card-bg);
}

.nav-tabs {
    border-bottom: 1px solid var(--active-border);
    padding: 0 24px;
    background: var(--active-card-bg);
}

.nav-tabs .nav-link {
    color: var(--active-text-muted);
    font-weight: 600;
    border: none;
    padding: 16px 24px;
    border-radius: 0;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    color: var(--active-primary);
    border-color: transparent;
}

.nav-tabs .nav-link.active {
    color: var(--active-primary);
    background: transparent;
    border-color: transparent transparent var(--active-primary) transparent;
    border-width: 0 0 3px 0;
}

.tab-content {
    padding: 24px;
}

/* Panneaux de contenu */
.content-panel {
    background: var(--active-card-bg);
    border: 1px solid var(--active-border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--active-shadow);
}

.panel-title {
    color: var(--active-text);
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.content-grid {
    display: grid;
    gap: 20px;
}

.grid-2-cols {
    grid-template-columns: 1.5fr 1fr;
}

.grid-1-col {
    grid-template-columns: 1fr;
}

/* Tableaux */
.table {
    color: var(--active-text);
    margin-bottom: 0;
}

.table thead th {
    color: var(--active-text-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    border-bottom: 1px solid var(--active-border);
    background: transparent;
}

.table tbody td {
    border-top: 1px solid var(--active-border);
    color: var(--active-text);
}

.table-sm th,
.table-sm td {
    padding: 12px 16px;
}

/* Responsive */
@media (max-width: 1200px) {
    .grid-2-cols {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .kpi-header {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .kpi-filters {
        justify-content: center;
    }
    
    .kpi-cards-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        padding: 16px;
    }
    
    .tab-content {
        padding: 16px;
    }
    
    .date-range {
        flex-direction: column;
        gap: 8px;
    }
}

/* Animations et effets futuristes pour le mode nuit */
.night-mode .kpi-main-card {
    background: var(--night-card-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--night-border);
    box-shadow: var(--night-glow);
}

.night-mode .kpi-header {
    background: linear-gradient(135deg, var(--night-primary) 0%, var(--night-primary-hover) 100%);
    box-shadow: 0 4px 20px rgba(0, 212, 255, 0.2);
}

.night-mode .kpi-title .icon {
    background: rgba(0, 212, 255, 0.2);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
}

/* Effets de glow pour les éléments interactifs en mode nuit */
.night-mode .kpi-card,
.night-mode .content-panel {
    border: 1px solid var(--night-border);
    box-shadow: var(--night-glow);
}

.night-mode .btn-primary {
    background: linear-gradient(135deg, var(--night-primary) 0%, var(--night-primary-hover) 100%);
    border-color: var(--night-primary);
    box-shadow: 0 4px 16px rgba(0, 212, 255, 0.3);
}

.night-mode .btn-primary:hover {
    box-shadow: 0 6px 24px rgba(0, 212, 255, 0.5);
}

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
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1031 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
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


/* Masquer la navbar desktop sur mobile */
@media (max-width: 767px) {
    #desktop-navbar,
    nav#desktop-navbar,
    .navbar.navbar-light {
        display: none !important;
    }
}

/* FORCE ONGLETS VISIBLES - Debug */
.nav-tabs {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.nav-tabs .nav-item {
    display: block !important;
}

.nav-tabs .nav-link {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    cursor: pointer !important;
    pointer-events: auto !important;
}

.tab-content {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.tab-pane {
    display: none !important;
}

.tab-pane.active,
.tab-pane.show {
    display: block !important;
}

/* Debug visuel des onglets */
.nav-tabs .nav-link {
    border: 2px solid red !important;
    background: yellow !important;
    color: black !important;
}

.nav-tabs .nav-link.active {
    border: 2px solid green !important;
    background: lightgreen !important;
    color: black !important;
}
</style>

<div class="kpi-main-card">
    <!-- Header avec titre et filtres globaux -->
    <div class="kpi-header">
        <div class="kpi-title">
            <span class="icon"><i class="fas fa-chart-line"></i></span>
            <span>Dashboard KPI</span>
        </div>
        <div class="kpi-filters">
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-light" data-range="7">7 jours</button>
                <button class="btn btn-sm btn-outline-light" data-range="30">30 jours</button>
                <button class="btn btn-sm btn-outline-light" data-range="90">90 jours</button>
            </div>
            <div class="date-range">
                <input type="date" id="global-start" class="form-control form-control-sm">
                <span style="color: rgba(255,255,255,0.7);">→</span>
                <input type="date" id="global-end" class="form-control form-control-sm">
            </div>
            <button id="global-apply" class="btn btn-sm btn-light">
                <i class="fas fa-sync-alt me-1"></i>Actualiser
            </button>
        </div>
    </div>

    <!-- Cartes KPI principales -->
    <div class="kpi-cards-grid">
        <div class="kpi-card">
            <div class="kpi-card-label">Réparations terminées</div>
            <div class="kpi-card-value" id="kpi-completed"><?php echo number_format($kpi['completed_repairs'], 0, ',', ' '); ?></div>

            <div class="kpi-card-subtitle">période sélectionnée</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-label">Chiffre d'affaires</div>
            <div class="kpi-card-value" id="kpi-revenue"><?php echo number_format($kpi['total_revenue'], 0, ',', ' '); ?> €</div>

            <div class="kpi-card-subtitle">réparations livrées</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-label">Techniciens actifs</div>
            <div class="kpi-card-value" id="kpi-techs"><?php echo number_format($kpi['active_techs'], 0, ',', ' '); ?></div>

            <div class="kpi-card-subtitle">ayant travaillé</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-label">Heures travaillées</div>
            <div class="kpi-card-value" id="kpi-hours"><?php echo number_format($kpi['total_hours'], 1, ',', ' '); ?> h</div>

            <div class="kpi-card-subtitle">sessions complétées</div>
        </div>
    </div>

    <!-- Contenu avec onglets -->
    <div class="kpi-content">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
                    <i class="fas fa-store me-2"></i>Vue d'ensemble
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-employees" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Employés
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-devices" type="button" role="tab">
                    <i class="fas fa-mobile-alt me-2"></i>Appareils
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Onglet Vue d'ensemble -->
            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                <div class="content-grid grid-2-cols">
                    <div class="content-panel">
                        <div class="panel-title">
                            <span>Tendance journalière</span>
                            <small class="text-muted">Réparations & Heures</small>
                        </div>
                        <canvas id="chart-overview-trend" height="120"></canvas>
                    </div>
                    <div class="content-panel">
                        <div class="panel-title">
                            <span>Top 5 Techniciens</span>
                            <small class="text-muted">Efficacité</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Technicien</th>
                                        <th class="text-end">Rép.</th>
                                        <th class="text-end">Heures</th>
                                        <th class="text-end">Rép/h</th>
                                    </tr>
                                </thead>
                                <tbody id="top-performers">
                                    <tr><td colspan="4" class="text-muted text-center">Chargement...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Employés -->
            <div class="tab-pane fade" id="tab-employees" role="tabpanel">
                <?php if ($is_admin): ?>

                <div class="content-panel">
                    <div class="panel-title">
                        <span>Filtres employés</span>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <select id="employee-filter" class="form-select">
                                <option value="all">Tous les techniciens</option>
                                <?php foreach ($users as $user): ?>

                                    <option value="<?php echo (int)$user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>

                                <?php endforeach; ?>

                            </select>
                        </div>
                        <div class="col-md-6">
                            <button id="employee-apply" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Appliquer le filtre
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>


                <div class="content-grid grid-2-cols">
                    <div class="content-panel">
                        <div class="panel-title">Productivité par employé</div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th class="text-end">Terminées</th>
                                        <th class="text-end">En cours</th>
                                        <th class="text-end">CA (€)</th>
                                        <th class="text-end">Temps moy.</th>
                                    </tr>
                                </thead>
                                <tbody id="productivity-table">
                                    <tr><td colspan="5" class="text-muted text-center">Chargement...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="content-panel">
                        <div class="panel-title">Présence et temps de travail</div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th class="text-end">Jours</th>
                                        <th class="text-end">Heures</th>
                                        <th class="text-end">Moy/jour</th>
                                    </tr>
                                </thead>
                                <tbody id="attendance-table">
                                    <tr><td colspan="4" class="text-muted text-center">Chargement...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="content-grid grid-1-col">
                    <div class="content-panel">
                        <div class="panel-title">Rendement quotidien (réparations/heure)</div>
                        <canvas id="chart-employee-trend" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Onglet Appareils -->
            <div class="tab-pane fade" id="tab-devices" role="tabpanel">
                <div class="content-grid grid-1-col">
                    <div class="content-panel">
                        <div class="panel-title">Analyse par type d'appareil</div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Marque</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Terminées</th>
                                        <th class="text-end">Temps moy. (h)</th>
                                        <th class="text-end">Prix moy. (€)</th>
                                    </tr>
                                </thead>
                                <tbody id="devices-table">
                                    <tr><td colspan="6" class="text-muted text-center">Chargement...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($kpiError): ?>

    <div class="alert alert-danger m-3">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Erreur KPI: <?php echo htmlspecialchars($kpiError); ?>

    </div>
<?php endif; ?>


<!-- Chart.js pour les graphiques -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
(function() {
    // Utilitaires de formatage
    const fmt = {
        int: (n) => new Intl.NumberFormat('fr-FR').format(parseInt(n || 0, 10)),
        float: (n, d = 1) => new Intl.NumberFormat('fr-FR', { 
            minimumFractionDigits: d, 
            maximumFractionDigits: d 
        }).format(Number(n || 0))
    };

    // Gestion des plages de dates
    function setDateRange(days) {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - (days - 1));
        
        const toISO = (date) => date.toISOString().slice(0, 10);
        document.getElementById('global-start').value = toISO(start);
        document.getElementById('global-end').value = toISO(end);
    }

    // Initialiser avec 30 jours par défaut
    setDateRange(30);

    // Gestionnaires des boutons de plage rapide
    document.querySelectorAll('[data-range]').forEach(btn => {
        btn.addEventListener('click', () => {
            const days = parseInt(btn.getAttribute('data-range'), 10);
            setDateRange(days);
        });
    });

    // Gestion des graphiques
    let charts = {};
    
    function createChart(canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;
        
        if (charts[canvasId]) {
            charts[canvasId].destroy();
        }
        
        charts[canvasId] = new Chart(canvas.getContext('2d'), config);
        return charts[canvasId];
    }

    // Requêtes API
    async function fetchKPI(action, params = {}) {
        const start = document.getElementById('global-start').value;
        const end = document.getElementById('global-end').value;
        
        const url = new URL('kpi_api.php', window.location.origin);
        url.searchParams.set('action', action);
        url.searchParams.set('date_start', start);
        url.searchParams.set('date_end', end);
        
        Object.entries(params).forEach(([key, value]) => {
            url.searchParams.set(key, value);
        });

        try {
            const response = await fetch(url, { credentials: 'same-origin' });
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erreur inconnue');
            }
            
            return data.data;
        } catch (error) {
            console.error('Erreur API KPI:', error);
            throw error;
        }
    }

    // Chargement des données principales
    async function loadOverview() {
        try {
            const data = await fetchKPI('dashboard_overview');
            const overview = data.overview || {};
            
            // Mise à jour des cartes KPI
            document.getElementById('kpi-completed').textContent = fmt.int(overview.completed_repairs || 0);
            document.getElementById('kpi-revenue').textContent = fmt.int(overview.total_revenue || 0) + ' €';
            document.getElementById('kpi-techs').textContent = fmt.int(overview.active_technicians || 0);
            document.getElementById('kpi-hours').textContent = fmt.float(overview.total_hours_worked || 0);

            // Top performers
            const topPerformers = document.getElementById('top-performers');
            topPerformers.innerHTML = '';
            
            (data.top_performers || []).forEach(performer => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${performer.full_name || ''}</td>
                    <td class="text-end">${fmt.int(performer.completed_repairs || 0)}</td>
                    <td class="text-end">${fmt.float(performer.hours_worked || 0)}</td>
                    <td class="text-end">${fmt.float(performer.repairs_per_hour || 0)}</td>
                `;
                topPerformers.appendChild(row);
            });
            
            if (!topPerformers.children.length) {
                topPerformers.innerHTML = '<tr><td colspan="4" class="text-muted text-center">Aucune donnée</td></tr>';
            }

        } catch (error) {
            console.error('Erreur chargement overview:', error);
        }
    }

    // Chargement du graphique de tendance
    async function loadOverviewTrend() {
        try {
            const data = await fetchKPI('repairs_by_hour', { user_id: 'all' });
            
            // Agrégation par date
            const dailyData = new Map();
            (data.daily_details || []).forEach(item => {
                const date = item.work_date;
                const existing = dailyData.get(date) || { repairs: 0, hours: 0 };
                dailyData.set(date, {
                    repairs: existing.repairs + parseInt(item.repairs_completed || 0, 10),
                    hours: existing.hours + parseFloat(item.total_hours_worked || 0)
                });
            });

            const labels = Array.from(dailyData.keys()).sort();
            const repairsData = labels.map(date => dailyData.get(date).repairs);
            const hoursData = labels.map(date => dailyData.get(date).hours);

            createChart('chart-overview-trend', {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Réparations',
                            data: repairsData,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.3,
                            fill: false
                        },
                        {
                            label: 'Heures',
                            data: hoursData,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: false,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Réparations' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            title: { display: true, text: 'Heures' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Erreur chargement tendance:', error);
        }
    }

    // Chargement des données employés
    async function loadEmployeeData() {
        const selectedUser = document.getElementById('employee-filter')?.value || '<?php echo (int)($current_user_id ?? 0); ?>';

        
        try {
            // Productivité
            const productivity = await fetchKPI('productivity_stats', { user_id: selectedUser });
            const productivityTable = document.getElementById('productivity-table');
            productivityTable.innerHTML = '';
            
            (productivity || []).forEach(emp => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${emp.full_name || ''}</td>
                    <td class="text-end">${fmt.int(emp.repairs_completed || 0)}</td>
                    <td class="text-end">${fmt.int(emp.repairs_in_progress || 0)}</td>
                    <td class="text-end">${fmt.float(emp.total_revenue || 0)}</td>
                    <td class="text-end">${fmt.float(emp.avg_resolution_time_hours || 0)} h</td>
                `;
                productivityTable.appendChild(row);
            });
            
            if (!productivityTable.children.length) {
                productivityTable.innerHTML = '<tr><td colspan="5" class="text-muted text-center">Aucune donnée</td></tr>';
            }

            // Présence
            const attendance = await fetchKPI('attendance_stats', { user_id: selectedUser });
            const attendanceTable = document.getElementById('attendance-table');
            attendanceTable.innerHTML = '';
            
            (attendance || []).forEach(emp => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${emp.full_name || ''}</td>
                    <td class="text-end">${fmt.int(emp.days_worked || 0)}</td>
                    <td class="text-end">${fmt.float(emp.total_hours_worked || 0)}</td>
                    <td class="text-end">${fmt.float(emp.avg_hours_per_day || 0)}</td>
                `;
                attendanceTable.appendChild(row);
            });
            
            if (!attendanceTable.children.length) {
                attendanceTable.innerHTML = '<tr><td colspan="4" class="text-muted text-center">Aucune donnée</td></tr>';
            }

            // Graphique employé
            const empTrend = await fetchKPI('repairs_by_hour', { user_id: selectedUser });
            const dailyRates = new Map();
            
            (empTrend.daily_details || []).forEach(item => {
                const date = item.work_date;
                const repairs = parseInt(item.repairs_completed || 0, 10);
                const hours = parseFloat(item.total_hours_worked || 0);
                const rate = hours > 0 ? repairs / hours : 0;
                
                const existing = dailyRates.get(date) || 0;
                dailyRates.set(date, existing + rate);
            });

            const labels = Array.from(dailyRates.keys()).sort();
            const ratesData = labels.map(date => dailyRates.get(date));

            createChart('chart-employee-trend', {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Réparations/heure',
                        data: ratesData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Réparations/heure' }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Erreur chargement données employés:', error);
        }
    }

    // Chargement des données appareils
    async function loadDeviceData() {
        try {
            const devices = await fetchKPI('device_analysis');
            const devicesTable = document.getElementById('devices-table');
            devicesTable.innerHTML = '';
            
            (devices || []).forEach(device => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${device.type_appareil || '-'}</td>
                    <td>${device.marque || '-'}</td>
                    <td class="text-end">${fmt.int(device.total_repairs || 0)}</td>
                    <td class="text-end">${fmt.int(device.completed_repairs || 0)}</td>
                    <td class="text-end">${fmt.float(device.avg_resolution_time_hours || 0)}</td>
                    <td class="text-end">${fmt.float(device.avg_price || 0)}</td>
                `;
                devicesTable.appendChild(row);
            });
            
            if (!devicesTable.children.length) {
                devicesTable.innerHTML = '<tr><td colspan="6" class="text-muted text-center">Aucune donnée</td></tr>';
            }

        } catch (error) {
            console.error('Erreur chargement données appareils:', error);
        }
    }

    // Fonction de rafraîchissement global
    async function refreshAllData() {
        try {
            await Promise.all([
                loadOverview(),
                loadOverviewTrend(),
                loadEmployeeData(),
                loadDeviceData()
            ]);
        } catch (error) {
            console.error('Erreur rafraîchissement:', error);
        }
    }

    // Gestionnaires d'événements
    document.getElementById('global-apply').addEventListener('click', refreshAllData);
    document.getElementById('employee-apply')?.addEventListener('click', loadEmployeeData);
    document.getElementById('employee-filter')?.addEventListener('change', loadEmployeeData);

    // Debug des onglets Bootstrap
    console.log('🔧 Debug onglets KPI');
    
    // Vérifier Bootstrap
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap non disponible');
    } else {
        console.log('✅ Bootstrap disponible');
    }
    
    // Initialiser manuellement les onglets si nécessaire
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tabTrigger => {
        tabTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🔄 Clic onglet:', this.getAttribute('data-bs-target'));
            
            // Retirer active de tous les onglets
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Activer l'onglet cliqué
            this.classList.add('active');
            const targetId = this.getAttribute('data-bs-target');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
                console.log('✅ Onglet activé:', targetId);
            } else {
                console.error('❌ Panneau non trouvé:', targetId);
            }
        });
    });

    // Chargement initial
    refreshAllData();
})();
</script>