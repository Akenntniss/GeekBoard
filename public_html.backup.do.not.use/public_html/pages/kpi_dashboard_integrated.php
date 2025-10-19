<?php
// KPI Dashboard (reset) — integrated route. Minimal scaffold with basic server KPIs
// DB helpers are already available via index header includes

$kpiError = null;
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

try {
    $pdo = getShopDBConnection();
    if ($pdo) {
        $dateStart = date('Y-m-d', strtotime('-30 days'));
        $dateEnd = date('Y-m-d');

        // Completed repairs count
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM reparations r WHERE DATE(COALESCE(r.date_modification, r.date_reception)) BETWEEN ? AND ? AND r.statut IN ('terminee','livree','reparee')");
        $stmt->execute([$dateStart, $dateEnd]);
        $kpi['completed_repairs'] = (int)($stmt->fetch()['c'] ?? 0);

        // Revenue sum
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN r.statut IN ('terminee','livree','reparee') THEN r.prix_reparation END),0) as s FROM reparations r WHERE DATE(COALESCE(r.date_modification, r.date_reception)) BETWEEN ? AND ?");
        $stmt->execute([$dateStart, $dateEnd]);
        $kpi['total_revenue'] = (float)($stmt->fetch()['s'] ?? 0);

        // Active technicians
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT r.employe_id) as c FROM reparations r WHERE DATE(COALESCE(r.date_modification, r.date_reception)) BETWEEN ? AND ?");
        $stmt->execute([$dateStart, $dateEnd]);
        $kpi['active_techs'] = (int)($stmt->fetch()['c'] ?? 0);

        // Total hours from time_tracking
        $hasWorkDuration = gb_column_exists($pdo, 'time_tracking', 'work_duration');
        if ($hasWorkDuration) {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(tt.work_duration),0) as h FROM time_tracking tt WHERE DATE(tt.clock_in) BETWEEN ? AND ? AND tt.status='completed'");
            $stmt->execute([$dateStart, $dateEnd]);
        } else {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, tt.clock_in, COALESCE(tt.clock_out, NOW())))/60.0,0) as h FROM time_tracking tt WHERE DATE(tt.clock_in) BETWEEN ? AND ? AND tt.status='completed'");
            $stmt->execute([$dateStart, $dateEnd]);
        }
        $kpi['total_hours'] = round((float)($stmt->fetch()['h'] ?? 0), 2);
    }
} catch (Exception $e) {
    $kpiError = $e->getMessage();
}
?>

<style>
.page-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
.kpi-header { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #fff; padding: 1rem; margin: -20px -20px 16px -20px; border-radius: 0 0 12px 12px; }
.placeholder-card { background: #111827; border: 1px dashed rgba(255,255,255,0.15); border-radius: 12px; padding: 24px; color: #9CA3AF; }
.filters-container { background: #111827; border-radius: 12px; padding: 16px; color: #9CA3AF; border: 1px solid rgba(255,255,255,0.08); }
.kpi-cards { display: grid; grid-template-columns: repeat(4, minmax(180px, 1fr)); gap: 12px; }
.kpi-card-mini { background: #111827; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 16px; }
.kpi-label { color: #9CA3AF; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
.kpi-value { color: #E5E7EB; font-size: 22px; font-weight: 700; }
.kpi-sub { color: #9CA3AF; font-size: 12px; }
@media (max-width: 900px){ .kpi-cards{ grid-template-columns: repeat(2, 1fr); } }
/* Fix: ensure tabs do not overlay content and content remains visible */
.nav-tabs { position: static !important; z-index: auto !important; }
.tab-content { margin-top: 12px; }
.tab-pane { position: relative; }
.tab-pane:not(.active):not(.show) { display: none; }
</style>

<div class="page-container">
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#kpi-shop" type="button" role="tab">
                <i class="fas fa-store me-2"></i>KPI Magasin
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#kpi-employee" type="button" role="tab">
                <i class="fas fa-user me-2"></i>KPI par Employé
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="kpi-shop" role="tabpanel">
            <div class="kpi-header"><strong><i class="fas fa-chart-line me-2"></i>Dashboard KPI — Magasin</strong></div>
            <?php if ($kpiError): ?>
                <div class="alert alert-danger">Erreur KPI: <?php echo htmlspecialchars($kpiError); ?></div>
            <?php endif; ?>
            <div class="kpi-cards mb-3">
                <div class="kpi-card-mini">
                    <div class="kpi-label">Réparations terminées (30j)</div>
                    <div class="kpi-value"><?php echo number_format($kpi['completed_repairs'], 0, ',', ' '); ?></div>
                </div>
                <div class="kpi-card-mini">
                    <div class="kpi-label">Chiffre d'affaires (30j)</div>
                    <div class="kpi-value"><?php echo number_format($kpi['total_revenue'], 0, ',', ' '); ?> €</div>
                </div>
                <div class="kpi-card-mini">
                    <div class="kpi-label">Techniciens actifs</div>
                    <div class="kpi-value"><?php echo number_format($kpi['active_techs'], 0, ',', ' '); ?></div>
                </div>
                <div class="kpi-card-mini">
                    <div class="kpi-label">Heures travaillées (30j)</div>
                    <div class="kpi-value"><?php echo number_format($kpi['total_hours'], 1, ',', ' '); ?> h</div>
                    <div class="kpi-sub">total sessions complétées</div>
                </div>
            </div>
            <div class="filters-container mb-3">Aucun filtre pour le moment.</div>
            <div class="placeholder-card">Page vierge. Nous allons reconstruire les KPI à partir de zéro ici.</div>
        </div>
        <div class="tab-pane fade" id="kpi-employee" role="tabpanel">
            <div class="kpi-header"><strong><i class="fas fa-users me-2"></i>Dashboard KPI — Employés</strong></div>
            <div class="filters-container mb-3">Aucun filtre pour le moment.</div>
            <div class="placeholder-card">Page vierge. Nous allons construire les KPI par employé ici.</div>
        </div>
    </div>
</div>


