<?php
// Vérification de l'authentification
session_start();
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Configuration de la page
$page_title = 'Dashboard - GeekBoard SuperAdmin';
$page_heading = 'Dashboard';
$page_subtitle = 'Vue d\'ensemble et analytics';
$page_icon = 'fas fa-chart-line';

// Inclure le header
require_once('includes/header.php');
require_once('../config/database.php');

// Récupérer les données
$pdo = getMainDBConnection();

// Stats globales
$stmt = $pdo->query("SELECT COUNT(*) as total FROM shops");
$total_shops = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE active = 1");
$active_shops = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE subscription_status = 'trial'");
$trial_shops = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE subscription_status = 'active'");
$paying_shops = $stmt->fetch()['count'];

// Essais expirant bientôt (dans les 7 jours)
$stmt = $pdo->query("
    SELECT id, name, subdomain, trial_ends_at, DATEDIFF(trial_ends_at, NOW()) as days_left
    FROM shops 
    WHERE subscription_status = 'trial' 
    AND trial_ends_at IS NOT NULL 
    AND DATEDIFF(trial_ends_at, NOW()) BETWEEN 0 AND 7
    ORDER BY trial_ends_at ASC
    LIMIT 5
");
$expiring_trials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Activité récente (derniers shops créés)
$stmt = $pdo->query("
    SELECT id, name, subdomain, created_at, subscription_status 
    FROM shops 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Évolution des créations par mois (6 derniers mois)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM shops
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_creations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Répartition par statut
$stmt = $pdo->query("
    SELECT subscription_status, COUNT(*) as count
    FROM shops
    GROUP BY subscription_status
");
$status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer le taux de conversion
$conversion_rate = $total_shops > 0 ? round(($paying_shops / $total_shops) * 100, 1) : 0;
?>

<!-- Extra CSS for dashboard -->
<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

.chart-container {
    position: relative;
    height: 300px;
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--warning-50);
    border-radius: 8px;
    margin-bottom: 8px;
    border-left: 4px solid var(--warning-500);
}

.alert-item.danger {
    background: var(--danger-50);
    border-left-color: var(--danger-500);
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-50);
    color: var(--primary-600);
}

.ai-analysis-btn {
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
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
}

.ai-analysis-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
}

.ai-analysis-btn i {
    font-size: 20px;
}

.quick-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
</style>

<!-- Stats Cards -->
<div class="dashboard-grid">
    <!-- Total Shops -->
    <div class="stat-card animate-slideInUp">
        <div class="stat-card-icon primary">
            <i class="fas fa-store"></i>
        </div>
        <div class="stat-card-value"><?php echo $total_shops; ?></div>
        <div class="stat-card-label">Total Magasins</div>
    </div>
    
    <!-- Active Shops -->
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.1s;">
        <div class="stat-card-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-card-value"><?php echo $active_shops; ?></div>
        <div class="stat-card-label">Actifs</div>
        <div class="stat-card-change positive">
            <i class="fas fa-arrow-up"></i>
            <?php echo $total_shops > 0 ? round(($active_shops / $total_shops) * 100) : 0; ?>%
        </div>
    </div>
    
    <!-- Trial Shops -->
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.2s;">
        <div class="stat-card-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-card-value"><?php echo $trial_shops; ?></div>
        <div class="stat-card-label">En Essai</div>
    </div>
    
    <!-- Paying Shops -->
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.3s;">
        <div class="stat-card-icon primary">
            <i class="fas fa-credit-card"></i>
        </div>
        <div class="stat-card-value"><?php echo $paying_shops; ?></div>
        <div class="stat-card-label">Payants</div>
        <div class="stat-card-change positive">
            <i class="fas fa-percentage"></i>
            <?php echo $conversion_rate; ?>% conversion
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-6">
    <div class="card-body">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h3 class="font-semibold text-lg text-gray-900">Actions Rapides</h3>
                <p class="text-sm text-gray-600">Accédez rapidement aux fonctions principales</p>
            </div>
            <div class="quick-actions">
                <a href="create_shop.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Nouveau Magasin
                </a>
                <a href="shops.php" class="btn btn-secondary">
                    <i class="fas fa-store"></i>
                    Voir les Magasins
                </a>
                <a href="kpi.php" class="btn btn-secondary">
                    <i class="fas fa-chart-bar"></i>
                    KPI Détaillés
                </a>
                <button class="ai-analysis-btn" onclick="launchAIAnalysis()">
                    <i class="fas fa-robot"></i>
                    Analyse IA
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Charts Column -->
    <div class="lg:col-span-2">
        <!-- Evolution Chart -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="font-semibold">
                    <i class="fas fa-chart-line text-primary-600 me-2"></i>
                    Évolution des Inscriptions
                </h3>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="evolutionChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Status Distribution Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">
                    <i class="fas fa-chart-pie text-success-600 me-2"></i>
                    Répartition par Statut
                </h3>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alerts & Activity Column -->
    <div class="lg:col-span-1">
        <!-- Expiring Trials Alert -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="font-semibold">
                    <i class="fas fa-exclamation-triangle text-warning-600 me-2"></i>
                    Essais Expirant Bientôt
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($expiring_trials)): ?>
                <div class="text-center text-gray-500 py-4">
                    <i class="fas fa-check-circle text-success-500 text-2xl mb-2"></i>
                    <p>Aucun essai n'expire dans les 7 prochains jours</p>
                </div>
                <?php else: ?>
                <?php foreach ($expiring_trials as $trial): ?>
                <div class="alert-item <?php echo $trial['days_left'] <= 2 ? 'danger' : ''; ?>">
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($trial['name']); ?></div>
                        <div class="text-xs text-gray-600"><?php echo htmlspecialchars($trial['subdomain']); ?>.mdgeek.top</div>
                    </div>
                    <div class="text-right">
                        <span class="badge <?php echo $trial['days_left'] <= 2 ? 'badge-danger' : 'badge-warning'; ?>">
                            <?php echo $trial['days_left']; ?> jour<?php echo $trial['days_left'] > 1 ? 's' : ''; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">
                    <i class="fas fa-history text-primary-600 me-2"></i>
                    Activité Récente
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($recent_shops)): ?>
                <div class="text-center text-gray-500 py-4">
                    <p>Aucune activité récente</p>
                </div>
                <?php else: ?>
                <?php foreach ($recent_shops as $shop): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($shop['name']); ?></div>
                        <div class="text-xs text-gray-500">
                            Créé le <?php echo date('d/m/Y à H:i', strtotime($shop['created_at'])); ?>
                        </div>
                    </div>
                    <span class="status-badge status-<?php echo $shop['subscription_status']; ?>">
                        <?php 
                        echo match($shop['subscription_status']) {
                            'trial' => 'Essai',
                            'active' => 'Actif',
                            'expired' => 'Expiré',
                            'cancelled' => 'Annulé',
                            default => 'Nouveau'
                        };
                        ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- AI Analysis Modal -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-robot text-primary me-2"></i>
                    Analyse IA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="aiAnalysisContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-3"></div>
                        <p>Analyse en cours...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="copyAnalysis()">
                    <i class="fas fa-copy me-2"></i>Copier
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Evolution Chart
const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
const evolutionData = <?php echo json_encode($monthly_creations); ?>;

new Chart(evolutionCtx, {
    type: 'line',
    data: {
        labels: evolutionData.map(d => {
            const [year, month] = d.month.split('-');
            const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            return monthNames[parseInt(month) - 1] + ' ' + year;
        }),
        datasets: [{
            label: 'Nouveaux magasins',
            data: evolutionData.map(d => d.count),
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

// Status Distribution Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = <?php echo json_encode($status_distribution); ?>;

const statusColors = {
    'trial': '#f59e0b',
    'active': '#10b981',
    'expired': '#ef4444',
    'cancelled': '#6b7280'
};

const statusLabels = {
    'trial': 'Essai',
    'active': 'Actif',
    'expired': 'Expiré',
    'cancelled': 'Annulé'
};

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusData.map(d => statusLabels[d.subscription_status] || d.subscription_status),
        datasets: [{
            data: statusData.map(d => d.count),
            backgroundColor: statusData.map(d => statusColors[d.subscription_status] || '#6b7280'),
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// AI Analysis Function
function launchAIAnalysis() {
    const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
    modal.show();
    
    // Prepare data for AI
    const analysisData = {
        total_shops: <?php echo $total_shops; ?>,
        active_shops: <?php echo $active_shops; ?>,
        trial_shops: <?php echo $trial_shops; ?>,
        paying_shops: <?php echo $paying_shops; ?>,
        conversion_rate: <?php echo $conversion_rate; ?>,
        expiring_trials: <?php echo count($expiring_trials); ?>,
        monthly_creations: <?php echo json_encode($monthly_creations); ?>,
        status_distribution: <?php echo json_encode($status_distribution); ?>
    };
    
    // Generate analysis prompt
    const prompt = `Analyse les KPIs suivants de ma plateforme SaaS GeekBoard (gestion de magasins de réparation):

📊 MÉTRIQUES PRINCIPALES:
- Total magasins: ${analysisData.total_shops}
- Magasins actifs: ${analysisData.active_shops}
- En période d'essai: ${analysisData.trial_shops}
- Clients payants: ${analysisData.paying_shops}
- Taux de conversion: ${analysisData.conversion_rate}%
- Essais expirant sous 7 jours: ${analysisData.expiring_trials}

📈 ÉVOLUTION MENSUELLE:
${JSON.stringify(analysisData.monthly_creations)}

📊 RÉPARTITION PAR STATUT:
${JSON.stringify(analysisData.status_distribution)}

Fournis une analyse détaillée avec:
1. Points forts identifiés
2. Points d'amélioration
3. Recommandations stratégiques
4. Actions prioritaires à mettre en place

Réponds de manière structurée et actionnable.`;

    // Display the prompt for manual copy (since we don't have direct AI API access)
    document.getElementById('aiAnalysisContent').innerHTML = `
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            Copiez ce prompt et collez-le dans votre assistant IA préféré (ChatGPT, Claude, etc.)
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
            <pre style="white-space: pre-wrap; font-size: 13px; line-height: 1.6;">${prompt}</pre>
        </div>
    `;
}

function copyAnalysis() {
    const pre = document.querySelector('#aiAnalysisContent pre');
    if (pre) {
        navigator.clipboard.writeText(pre.textContent).then(() => {
            showToast('Prompt copié !', 'success');
        });
    }
}
</script>

<?php require_once('includes/footer.php'); ?>
