<?php
// Configuration de la page
$page_title = 'KPI - GeekBoard SuperAdmin';
$page_heading = 'Indicateurs de Performance';
$page_subtitle = 'Métriques avancées et tendances';
$page_icon = 'fas fa-chart-bar';

// Inclure le header
require_once('includes/header.php');
require_once('../config/database.php');

$pdo = getMainDBConnection();

// === MÉTRIQUES PRINCIPALES ===

// Total shops
$stmt = $pdo->query("SELECT COUNT(*) as total FROM shops");
$total_shops = $stmt->fetch()['total'];

// Shops actifs
$stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE active = 1");
$active_shops = $stmt->fetch()['count'];

// Shops en essai
$stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE subscription_status = 'trial'");
$trial_shops = $stmt->fetch()['count'];

// Shops payants
$stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE subscription_status = 'active'");
$paying_shops = $stmt->fetch()['count'];

// Shops expirés
$stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE subscription_status = 'expired'");
$expired_shops = $stmt->fetch()['count'];

// Shops annulés
$stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE subscription_status = 'cancelled'");
$cancelled_shops = $stmt->fetch()['count'];

// === TAUX ET RATIOS ===

// Taux de conversion (essai -> payant)
$conversion_rate = $total_shops > 0 ? round(($paying_shops / $total_shops) * 100, 1) : 0;

// Taux d'activation (actifs / total)
$activation_rate = $total_shops > 0 ? round(($active_shops / $total_shops) * 100, 1) : 0;

// Churn rate (annulés + expirés / total historique)
$churn_rate = $total_shops > 0 ? round((($cancelled_shops + $expired_shops) / $total_shops) * 100, 1) : 0;

// === TENDANCES TEMPORELLES ===

// Créations par mois (12 derniers mois)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM shops
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_creations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conversions par mois (approximation basée sur le passage en 'active')
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(updated_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM shops
    WHERE subscription_status = 'active'
    AND updated_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === DURÉE MOYENNE EN ESSAI ===
$stmt = $pdo->query("
    SELECT AVG(DATEDIFF(
        COALESCE(trial_ends_at, NOW()),
        trial_started_at
    )) as avg_trial_days
    FROM shops
    WHERE trial_started_at IS NOT NULL
");
$avg_trial_days = round($stmt->fetch()['avg_trial_days'] ?? 0, 1);

// === TOP PERFORMANCES ===

// Shops les plus récents convertis en payants
$stmt = $pdo->query("
    SELECT name, subdomain, created_at, updated_at
    FROM shops
    WHERE subscription_status = 'active'
    ORDER BY updated_at DESC
    LIMIT 5
");
$recent_conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Shops en essai les plus anciens (risque de churn)
$stmt = $pdo->query("
    SELECT name, subdomain, trial_started_at, trial_ends_at, 
           DATEDIFF(NOW(), trial_started_at) as days_in_trial
    FROM shops
    WHERE subscription_status = 'trial'
    AND trial_started_at IS NOT NULL
    ORDER BY trial_started_at ASC
    LIMIT 5
");
$oldest_trials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === RÉPARTITION GÉOGRAPHIQUE (si disponible) ===
$stmt = $pdo->query("
    SELECT city, COUNT(*) as count
    FROM shops
    WHERE city IS NOT NULL AND city != ''
    GROUP BY city
    ORDER BY count DESC
    LIMIT 10
");
$city_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Extra CSS -->
<style>
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.kpi-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

@media (max-width: 1200px) {
    .kpi-grid, .kpi-grid-3 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .kpi-grid, .kpi-grid-3 {
        grid-template-columns: 1fr;
    }
}

.kpi-metric-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    border: 1px solid var(--gray-100);
    transition: all 0.3s ease;
}

.kpi-metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.kpi-metric-card.highlight {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: white;
}

.kpi-metric-card.highlight .kpi-metric-label {
    color: rgba(255,255,255,0.8);
}

.kpi-metric-card.highlight .kpi-metric-value {
    color: white;
}

.kpi-metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 16px;
}

.kpi-metric-icon.primary {
    background: var(--primary-50);
    color: var(--primary-600);
}

.kpi-metric-icon.success {
    background: var(--success-50);
    color: var(--success-600);
}

.kpi-metric-icon.warning {
    background: var(--warning-50);
    color: var(--warning-600);
}

.kpi-metric-icon.danger {
    background: var(--danger-50);
    color: var(--danger-600);
}

.kpi-metric-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--gray-900);
    line-height: 1;
    margin-bottom: 8px;
}

.kpi-metric-label {
    font-size: 14px;
    color: var(--gray-600);
    font-weight: 500;
}

.kpi-metric-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    font-weight: 600;
    margin-top: 12px;
    padding: 4px 10px;
    border-radius: 20px;
}

.kpi-metric-trend.positive {
    background: var(--success-50);
    color: var(--success-600);
}

.kpi-metric-trend.negative {
    background: var(--danger-50);
    color: var(--danger-600);
}

.kpi-metric-trend.neutral {
    background: var(--gray-100);
    color: var(--gray-600);
}

.chart-container {
    position: relative;
    height: 350px;
}

.data-table {
    width: 100%;
}

.data-table th {
    text-align: left;
    padding: 12px 16px;
    background: var(--gray-50);
    font-weight: 600;
    font-size: 13px;
    color: var(--gray-700);
}

.data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
}

.data-table tr:hover td {
    background: var(--gray-50);
}

.progress-bar-container {
    height: 8px;
    background: var(--gray-100);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.ai-cta {
    background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
    border-radius: 20px;
    padding: 32px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 32px;
}

.ai-cta-content h3 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.ai-cta-content p {
    color: rgba(255,255,255,0.7);
    margin: 0;
}

.ai-cta-btn {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    border: none;
    color: white;
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.ai-cta-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
}
</style>

<!-- AI Analysis CTA -->
<div class="ai-cta">
    <div class="ai-cta-content">
        <h3><i class="fas fa-robot me-3"></i>Analyse IA Avancée</h3>
        <p>Obtenez des insights personnalisés et des recommandations basées sur vos KPIs</p>
    </div>
    <button class="ai-cta-btn" onclick="launchAIAnalysis()">
        <i class="fas fa-magic"></i>
        Lancer l'Analyse
    </button>
</div>

<!-- Main KPIs -->
<div class="kpi-grid">
    <!-- Taux de Conversion -->
    <div class="kpi-metric-card highlight">
        <div class="kpi-metric-icon" style="background: rgba(255,255,255,0.2); color: white;">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div class="kpi-metric-value"><?php echo $conversion_rate; ?>%</div>
        <div class="kpi-metric-label">Taux de Conversion</div>
        <div class="kpi-metric-trend <?php echo $conversion_rate > 20 ? 'positive' : 'neutral'; ?>" style="background: rgba(255,255,255,0.2); color: white;">
            <i class="fas fa-<?php echo $conversion_rate > 20 ? 'arrow-up' : 'minus'; ?>"></i>
            Essai → Payant
        </div>
    </div>
    
    <!-- Taux d'Activation -->
    <div class="kpi-metric-card">
        <div class="kpi-metric-icon success">
            <i class="fas fa-power-off"></i>
        </div>
        <div class="kpi-metric-value"><?php echo $activation_rate; ?>%</div>
        <div class="kpi-metric-label">Taux d'Activation</div>
        <div class="kpi-metric-trend positive">
            <i class="fas fa-check"></i>
            Shops actifs
        </div>
    </div>
    
    <!-- Churn Rate -->
    <div class="kpi-metric-card">
        <div class="kpi-metric-icon danger">
            <i class="fas fa-user-slash"></i>
        </div>
        <div class="kpi-metric-value"><?php echo $churn_rate; ?>%</div>
        <div class="kpi-metric-label">Taux de Churn</div>
        <div class="kpi-metric-trend <?php echo $churn_rate < 20 ? 'positive' : 'negative'; ?>">
            <i class="fas fa-<?php echo $churn_rate < 20 ? 'thumbs-up' : 'exclamation-triangle'; ?>"></i>
            <?php echo $churn_rate < 20 ? 'Bon' : 'À surveiller'; ?>
        </div>
    </div>
    
    <!-- Durée Moyenne Essai -->
    <div class="kpi-metric-card">
        <div class="kpi-metric-icon warning">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="kpi-metric-value"><?php echo $avg_trial_days; ?></div>
        <div class="kpi-metric-label">Jours Moyens en Essai</div>
        <div class="kpi-metric-trend neutral">
            <i class="fas fa-clock"></i>
            Durée moyenne
        </div>
    </div>
</div>

<!-- Secondary KPIs -->
<div class="kpi-grid">
    <div class="kpi-metric-card">
        <div class="kpi-metric-icon primary">
            <i class="fas fa-store"></i>
        </div>
        <div class="kpi-metric-value"><?php echo $total_shops; ?></div>
        <div class="kpi-metric-label">Total Magasins</div>
    </div>
    
    <div class="kpi-metric-card">
        <div class="kpi-metric-icon success">
            <i class="fas fa-credit-card"></i>
        </div>
        <div class="kpi-metric-value"><?php echo $paying_shops; ?></div>
        <div class="kpi-metric-label">Clients Payants</div>
    </div>
    
    <div class="kpi-metric-card">
        <div class="kpi-metric-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="kpi-metric-value"><?php echo $trial_shops; ?></div>
        <div class="kpi-metric-label">En Essai</div>
    </div>
    
    <div class="kpi-metric-card">
        <div class="kpi-metric-icon danger">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="kpi-metric-value"><?php echo $expired_shops + $cancelled_shops; ?></div>
        <div class="kpi-metric-label">Perdus (Expirés + Annulés)</div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Tendance Créations -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">
                <i class="fas fa-chart-line text-primary-600 me-2"></i>
                Tendance des Inscriptions (12 mois)
            </h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Funnel de Conversion -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">
                <i class="fas fa-filter text-success-600 me-2"></i>
                Funnel de Conversion
            </h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="funnelChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Data Tables Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Conversions Récentes -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">
                <i class="fas fa-trophy text-warning-600 me-2"></i>
                Conversions Récentes
            </h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recent_conversions)): ?>
            <div class="text-center text-gray-500 py-8">
                <p>Aucune conversion récente</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Magasin</th>
                        <th>Date Conversion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_conversions as $shop): ?>
                    <tr>
                        <td>
                            <div class="font-semibold"><?php echo htmlspecialchars($shop['name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top</div>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($shop['updated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Essais à Risque -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold">
                <i class="fas fa-exclamation-triangle text-danger-600 me-2"></i>
                Essais à Risque (les plus anciens)
            </h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($oldest_trials)): ?>
            <div class="text-center text-gray-500 py-8">
                <p>Aucun essai en cours</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Magasin</th>
                        <th>Jours en Essai</th>
                        <th>Risque</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($oldest_trials as $shop): ?>
                    <?php 
                    $risk = $shop['days_in_trial'] > 10 ? 'high' : ($shop['days_in_trial'] > 5 ? 'medium' : 'low');
                    $riskColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger'];
                    ?>
                    <tr>
                        <td>
                            <div class="font-semibold"><?php echo htmlspecialchars($shop['name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top</div>
                        </td>
                        <td><?php echo $shop['days_in_trial']; ?> jours</td>
                        <td>
                            <span class="status-badge status-<?php echo $riskColors[$risk]; ?>">
                                <?php echo ucfirst($risk); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($city_distribution)): ?>
<!-- Geographic Distribution -->
<div class="card mt-6">
    <div class="card-header">
        <h3 class="font-semibold">
            <i class="fas fa-map-marker-alt text-primary-600 me-2"></i>
            Répartition Géographique
        </h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <?php foreach ($city_distribution as $city): ?>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="text-2xl font-bold text-primary-600"><?php echo $city['count']; ?></div>
                <div class="text-sm text-gray-600"><?php echo htmlspecialchars($city['city']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- AI Analysis Modal -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-robot text-primary me-2"></i>
                    Analyse IA des KPIs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="aiAnalysisContent">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Copiez ce prompt pour obtenir une analyse IA détaillée de vos KPIs.
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <pre id="aiPrompt" style="white-space: pre-wrap; font-size: 13px; line-height: 1.6;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="copyPrompt()">
                    <i class="fas fa-copy me-2"></i>Copier le Prompt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendData = <?php echo json_encode($monthly_creations); ?>;

new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: trendData.map(d => {
            const [year, month] = d.month.split('-');
            const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            return monthNames[parseInt(month) - 1];
        }),
        datasets: [{
            label: 'Nouveaux magasins',
            data: trendData.map(d => d.count),
            borderColor: '#7c3aed',
            backgroundColor: 'rgba(124, 58, 237, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#7c3aed',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Funnel Chart
const funnelCtx = document.getElementById('funnelChart').getContext('2d');

new Chart(funnelCtx, {
    type: 'bar',
    data: {
        labels: ['Total', 'Actifs', 'En Essai', 'Payants', 'Perdus'],
        datasets: [{
            label: 'Magasins',
            data: [
                <?php echo $total_shops; ?>,
                <?php echo $active_shops; ?>,
                <?php echo $trial_shops; ?>,
                <?php echo $paying_shops; ?>,
                <?php echo $expired_shops + $cancelled_shops; ?>
            ],
            backgroundColor: [
                '#7c3aed',
                '#10b981',
                '#f59e0b',
                '#3b82f6',
                '#ef4444'
            ],
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// AI Analysis
function launchAIAnalysis() {
    const prompt = `Tu es un expert en analyse business SaaS. Analyse ces KPIs de ma plateforme GeekBoard (gestion de magasins de réparation):

📊 MÉTRIQUES CLÉS:
• Taux de conversion (Essai → Payant): <?php echo $conversion_rate; ?>%
• Taux d'activation: <?php echo $activation_rate; ?>%
• Taux de churn: <?php echo $churn_rate; ?>%
• Durée moyenne en essai: <?php echo $avg_trial_days; ?> jours

📈 VOLUMES:
• Total magasins: <?php echo $total_shops; ?>
• Clients payants: <?php echo $paying_shops; ?>
• En essai: <?php echo $trial_shops; ?>
• Perdus (expirés + annulés): <?php echo $expired_shops + $cancelled_shops; ?>

📅 TENDANCE (12 derniers mois):
<?php echo json_encode($monthly_creations); ?>

Fournis une analyse complète avec:
1. 🎯 Diagnostic global de la santé du business
2. 💪 Points forts identifiés
3. ⚠️ Points d'amélioration urgents
4. 📋 Plan d'action prioritaire (3 actions concrètes)
5. 📊 Benchmarks SaaS recommandés à atteindre

Sois concis, actionnable et stratégique.`;

    document.getElementById('aiPrompt').textContent = prompt;
    const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
    modal.show();
}

function copyPrompt() {
    const prompt = document.getElementById('aiPrompt').textContent;
    navigator.clipboard.writeText(prompt).then(() => {
        showToast('Prompt copié !', 'success');
    });
}
</script>

<?php require_once('includes/footer.php'); ?>
