<?php
/**
 * Dashboard KPI - Design exactement identique à reparations.php
 * Reprend la structure complète de la page réparations
 */

// Vérification de l'authentification et initialisation
$shop_pdo = getShopDBConnection();
$current_shop_id = $_SESSION['shop_id'] ?? null;

// Variables utilisateur
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Utilisateur';

if (!$user_id) {
    header('Location: /index.php');
    exit();
}

$current_user_id = $user_id;
$is_admin = isset($user_role) && $user_role === 'admin';
$user_name_display = $user_name;

// Récupérer les utilisateurs pour le sélecteur (si admin)
$users = [];
if ($is_admin) {
    try {
        $stmt = $shop_pdo->prepare("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'technicien') ORDER BY full_name");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
    }
}

// Calculer les statistiques KPI de base
$kpi_stats = [
    'total_repairs' => 0,
    'repairs_today' => 0,
    'repairs_week' => 0,
    'repairs_month' => 0,
    'avg_per_hour' => 0,
    'total_revenue' => 0
];

try {
    // Total réparations terminées
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations WHERE statut IN ('terminee', 'livree', 'reparee')");
    $kpi_stats['total_repairs'] = $stmt->fetch()['total'];
    
    // Réparations aujourd'hui
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations WHERE DATE(date_modification) = CURDATE() AND statut IN ('terminee', 'livree', 'reparee')");
    $kpi_stats['repairs_today'] = $stmt->fetch()['total'];
    
    // Réparations cette semaine
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations WHERE WEEK(date_modification) = WEEK(NOW()) AND statut IN ('terminee', 'livree', 'reparee')");
    $kpi_stats['repairs_week'] = $stmt->fetch()['total'];
    
    // Réparations ce mois
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations WHERE MONTH(date_modification) = MONTH(NOW()) AND statut IN ('terminee', 'livree', 'reparee')");
    $kpi_stats['repairs_month'] = $stmt->fetch()['total'];
    
    // Chiffre d'affaires total
    $stmt = $shop_pdo->query("SELECT SUM(prix_reparation) as total FROM reparations WHERE statut IN ('terminee', 'livree', 'reparee')");
    $kpi_stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
} catch (Exception $e) {
    error_log("Erreur lors du calcul des KPI: " . $e->getMessage());
}
?>

<script>
// Script identique à reparations.php pour le loader
document.addEventListener('DOMContentLoaded', function() {
    // Simuler le chargement comme dans reparations.php
    setTimeout(function() {
        document.getElementById('pageLoader').style.display = 'none';
        document.getElementById('mainContent').style.display = 'block';
    }, 1500);
});
</script>

<!-- Loader Screen - Exactement identique à reparations.php -->
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
    <!-- Filtres rapides - Structure identique à reparations.php -->
    <div class="modern-filters-container">
        <!-- Barre de recherche moderne -->
        <div class="modern-search">
            <form method="GET" action="" class="search-form" id="kpiSearchForm">
                <div class="search-wrapper">
                    <i class="fas fa-chart-line search-icon"></i>
                    <input type="text" class="search-input" name="search" placeholder="Rechercher par employé, période..." value="">
                    <button class="search-btn" type="submit">
                        <i class="fas fa-search"></i>Analyser
                    </button>
                </div>
            </form>
        </div>

        <!-- Filtres modernes - Adaptés pour KPI -->
        <div class="modern-filters">
            <!-- Bouton Aujourd'hui -->
            <a href="javascript:void(0);" 
               class="modern-filter kpi-filter active"
               data-period="today">
                <div class="ripple"></div>
                <i class="fas fa-calendar-day filter-icon"></i>
                <span class="filter-name">Aujourd'hui</span>
                <span class="filter-count"><?php echo $kpi_stats['repairs_today']; ?></span>
            </a>
            
            <!-- Bouton Cette semaine -->
            <a href="javascript:void(0);" 
               class="modern-filter kpi-filter"
               data-period="week">
                <div class="ripple"></div>
                <i class="fas fa-calendar-week filter-icon"></i>
                <span class="filter-name">Cette semaine</span>
                <span class="filter-count"><?php echo $kpi_stats['repairs_week']; ?></span>
            </a>
            
            <!-- Bouton Ce mois -->
            <a href="javascript:void(0);" 
               class="modern-filter kpi-filter"
               data-period="month">
                <div class="ripple"></div>
                <i class="fas fa-calendar-alt filter-icon"></i>
                <span class="filter-name">Ce mois</span>
                <span class="filter-count"><?php echo $kpi_stats['repairs_month']; ?></span>
            </a>
            
            <!-- Bouton Personnalisé -->
            <a href="javascript:void(0);" 
               class="modern-filter kpi-filter"
               data-period="custom">
                <div class="ripple"></div>
                <i class="fas fa-cog filter-icon"></i>
                <span class="filter-name">Personnalisé</span>
                <span class="filter-count">--</span>
            </a>
            
            <!-- Bouton Global -->
            <a href="javascript:void(0);" 
               class="modern-filter kpi-filter">
                <div class="ripple"></div>
                <i class="fas fa-globe filter-icon"></i>
                <span class="filter-name">Global</span>
                <span class="filter-count"><?php echo $kpi_stats['total_repairs']; ?></span>
            </a>
        </div>
    </div>

    <!-- Boutons d'action principaux - Structure identique -->
    <div class="action-buttons-container">
        <div class="modern-action-buttons">
            <button type="button" class="action-button" id="refreshKPI">
                <i class="fas fa-sync-alt"></i>
                <span>ACTUALISER</span>
            </button>
            <button type="button" class="action-button toggle-view active" data-view="charts">
                <i class="fas fa-chart-bar"></i>
                <span>GRAPHIQUES</span>
            </button>
            <button type="button" class="action-button toggle-view" data-view="table">
                <i class="fas fa-table"></i>
                <span>TABLEAUX</span>
            </button>
            <?php if ($is_admin): ?>
            <button type="button" class="action-button" data-bs-toggle="modal" data-bs-target="#employeeSelectModal">
                <i class="fas fa-users"></i>
                <span>EMPLOYÉS</span>
            </button>
            <?php endif; ?>
            <button type="button" class="action-button" data-bs-toggle="modal" data-bs-target="#exportKPIModal">
                <i class="fas fa-download"></i>
                <span>EXPORTER</span>
            </button>
        </div>
    </div>

    <!-- Conteneur pour les résultats - Structure identique -->
    <div class="results-container">
        <div class="card">
            <div class="card-body">
                <!-- Vue graphiques (par défaut) -->
                <div id="charts-view">
                    <!-- Header KPI avec stats principales -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="kpi-header-section">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h2 class="kpi-main-title">
                                            <i class="fas fa-chart-line me-3"></i>
                                            Dashboard KPI
                                        </h2>
                                        <p class="kpi-subtitle">Indicateurs de performance - <?php echo htmlspecialchars($user_name_display); ?></p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="kpi-user-info">
                                            <span class="badge bg-primary fs-6 p-2">
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo htmlspecialchars($user_name_display); ?>
                                                <?php if ($is_admin): ?>
                                                    <span class="badge bg-warning ms-2">Admin</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cards KPI principales -->
                    <div class="row g-4 mb-4" id="kpiCards">
                        <div class="col-md-3">
                            <div class="kpi-stat-card">
                                <div class="kpi-stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="kpi-stat-content">
                                    <div class="kpi-stat-value"><?php echo $kpi_stats['total_repairs']; ?></div>
                                    <div class="kpi-stat-label">Réparations Terminées</div>
                                    <div class="kpi-stat-subtitle">Total général</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="kpi-stat-card">
                                <div class="kpi-stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                                <div class="kpi-stat-content">
                                    <div class="kpi-stat-value" id="avgPerHour">--</div>
                                    <div class="kpi-stat-label">Réparations/Heure</div>
                                    <div class="kpi-stat-subtitle">Moyenne équipe</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="kpi-stat-card">
                                <div class="kpi-stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="kpi-stat-content">
                                    <div class="kpi-stat-value" id="totalHours">--</div>
                                    <div class="kpi-stat-label">Heures Travaillées</div>
                                    <div class="kpi-stat-subtitle">Période sélectionnée</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="kpi-stat-card">
                                <div class="kpi-stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-euro-sign"></i>
                                </div>
                                <div class="kpi-stat-content">
                                    <div class="kpi-stat-value"><?php echo number_format($kpi_stats['total_revenue'], 0, ',', ' '); ?>€</div>
                                    <div class="kpi-stat-label">Chiffre d'Affaires</div>
                                    <div class="kpi-stat-subtitle">Total généré</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques principaux -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-8">
                            <div class="chart-card">
                                <div class="chart-card-header">
                                    <h5 class="chart-card-title">
                                        <i class="fas fa-line-chart me-2"></i>
                                        Évolution des Réparations par Heure
                                    </h5>
                                </div>
                                <div class="chart-card-body">
                                    <div class="chart-container">
                                        <canvas id="repairsByHourChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="chart-card">
                                <div class="chart-card-header">
                                    <h5 class="chart-card-title">
                                        <i class="fas fa-pie-chart me-2"></i>
                                        Répartition par Statut
                                    </h5>
                                </div>
                                <div class="chart-card-body">
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques secondaires -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-card-header">
                                    <h5 class="chart-card-title">
                                        <i class="fas fa-mobile-alt me-2"></i>
                                        Top Appareils Réparés
                                    </h5>
                                </div>
                                <div class="chart-card-body">
                                    <div class="chart-container">
                                        <canvas id="deviceTypeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-card-header">
                                    <h5 class="chart-card-title">
                                        <i class="fas fa-users me-2"></i>
                                        Temps de Travail par Employé
                                    </h5>
                                </div>
                                <div class="chart-card-body">
                                    <div class="chart-container">
                                        <canvas id="attendanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vue tableau (masquée par défaut) -->
                <div id="table-view" class="d-none">
                    <div class="modern-table-container">
                        <!-- En-tête du tableau -->
                        <div class="table-header">
                            <div class="header-cell">Employé</div>
                            <div class="header-cell">Réparations/h</div>
                            <div class="header-cell">Total Réparations</div>
                            <div class="header-cell">Heures Travaillées</div>
                            <div class="header-cell">CA Généré</div>
                            <div class="header-cell">Performance</div>
                        </div>

                        <!-- Corps du tableau -->
                        <div class="table-body" id="kpiTableBody">
                            <!-- Données générées dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles CSS identiques à reparations.php -->
<style>
/* Reprendre les styles de la page réparations */
.kpi-header-section {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 2rem;
    margin: -1.5rem -1.5rem 2rem -1.5rem;
    border-radius: 0 0 15px 15px;
}

.kpi-main-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.kpi-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.kpi-stat-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.kpi-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.kpi-stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.kpi-stat-content {
    flex: 1;
}

.kpi-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin: 0;
    line-height: 1.2;
}

.kpi-stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0.25rem 0;
}

.kpi-stat-subtitle {
    font-size: 0.75rem;
    color: #999;
    margin: 0;
}

.chart-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.chart-card-header {
    padding: 1.5rem 1.5rem 0 1.5rem;
}

.chart-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
}

.chart-card-body {
    padding: 1.5rem;
}

.chart-container {
    position: relative;
    height: 400px;
}

/* Styles pour les filtres KPI */
.kpi-filter.active {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

/* Loading states */
.loading-spinner {
    display: none;
    text-align: center;
    padding: 3rem;
}

/* Responsive */
@media (max-width: 768px) {
    .kpi-main-title {
        font-size: 1.5rem;
    }
    
    .kpi-stat-value {
        font-size: 1.5rem;
    }
    
    .chart-container {
        height: 300px;
    }
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Classe KPI Dashboard identique au design précédent
    class KPIDashboard {
        constructor() {
            this.charts = {};
            this.currentFilters = {
                user_id: '<?php echo $is_admin ? '' : $current_user_id; ?>',
                date_start: this.getDateByPeriod('today').start,
                date_end: this.getDateByPeriod('today').end
            };
            
            this.initializeEventListeners();
            this.loadAllData();
        }

        getDateByPeriod(period) {
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            
            switch(period) {
                case 'today':
                    return {
                        start: today.toISOString().split('T')[0],
                        end: today.toISOString().split('T')[0]
                    };
                case 'week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay());
                    return {
                        start: weekStart.toISOString().split('T')[0],
                        end: today.toISOString().split('T')[0]
                    };
                case 'month':
                    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                    return {
                        start: monthStart.toISOString().split('T')[0],
                        end: today.toISOString().split('T')[0]
                    };
                default:
                    return {
                        start: new Date(today.getTime() - 30*24*60*60*1000).toISOString().split('T')[0],
                        end: today.toISOString().split('T')[0]
                    };
            }
        }

        initializeEventListeners() {
            // Gestion des filtres de période
            document.querySelectorAll('.kpi-filter').forEach(filter => {
                filter.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Retirer active de tous les filtres
                    document.querySelectorAll('.kpi-filter').forEach(f => f.classList.remove('active'));
                    // Ajouter active au filtre cliqué
                    filter.classList.add('active');
                    
                    const period = filter.dataset.period;
                    if (period && period !== 'custom') {
                        const dates = this.getDateByPeriod(period);
                        this.currentFilters.date_start = dates.start;
                        this.currentFilters.date_end = dates.end;
                        this.loadAllData();
                    }
                });
            });

            // Bouton actualiser
            document.getElementById('refreshKPI').addEventListener('click', () => {
                this.loadAllData();
            });

            // Toggle view
            document.querySelectorAll('.toggle-view').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const view = btn.dataset.view;
                    
                    // Gérer les classes active
                    document.querySelectorAll('.toggle-view').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    // Afficher/masquer les vues
                    if (view === 'charts') {
                        document.getElementById('charts-view').classList.remove('d-none');
                        document.getElementById('table-view').classList.add('d-none');
                    } else {
                        document.getElementById('charts-view').classList.add('d-none');
                        document.getElementById('table-view').classList.remove('d-none');
                        this.updateTableView();
                    }
                });
            });
        }

        async loadAllData() {
            try {
                // Simuler le chargement
                const [
                    repairsByHour,
                    productivityStats,
                    deviceAnalysis,
                    attendanceStats
                ] = await Promise.all([
                    this.fetchData('repairs_by_hour'),
                    this.fetchData('productivity_stats'),
                    this.fetchData('device_analysis'),
                    this.fetchData('attendance_stats')
                ]);

                this.updateKPICards(repairsByHour, productivityStats, attendanceStats);
                this.updateCharts(repairsByHour, productivityStats, deviceAnalysis, attendanceStats);

            } catch (error) {
                console.error('Erreur lors du chargement des données:', error);
                this.showError('Erreur lors du chargement des données KPI');
            }
        }

        async fetchData(action) {
            const params = new URLSearchParams({
                action: action,
                ...this.currentFilters
            });

            const response = await fetch(`../kpi_api.php?${params}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Erreur inconnue');
            }

            return data.data;
        }

        updateKPICards(repairsByHour, productivityStats, attendanceStats) {
            // Mettre à jour les cartes KPI avec les vraies données
            if (repairsByHour && repairsByHour.period_summary) {
                const avgRepairsPerHour = repairsByHour.period_summary.reduce((sum, item) => 
                    sum + parseFloat(item.avg_repairs_per_hour || 0), 0) / repairsByHour.period_summary.length;
                document.getElementById('avgPerHour').textContent = avgRepairsPerHour.toFixed(2);
            }

            if (attendanceStats) {
                const totalHours = attendanceStats.reduce((sum, item) => 
                    sum + parseFloat(item.total_hours_worked || 0), 0);
                document.getElementById('totalHours').textContent = totalHours.toFixed(1) + 'h';
            }
        }

        updateCharts(repairsByHour, productivityStats, deviceAnalysis, attendanceStats) {
            // Implémenter les graphiques (code similaire aux versions précédentes)
            this.updateRepairsByHourChart(repairsByHour);
            this.updateStatusChart(productivityStats);
            this.updateDeviceChart(deviceAnalysis);
            this.updateAttendanceChart(attendanceStats);
        }

        updateRepairsByHourChart(data) {
            const ctx = document.getElementById('repairsByHourChart').getContext('2d');
            
            if (this.charts.repairsByHour) {
                this.charts.repairsByHour.destroy();
            }

            // Créer le graphique (implémentation simplifiée)
            this.charts.repairsByHour = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                    datasets: [{
                        label: 'Réparations/heure',
                        data: [2.1, 2.5, 1.8, 3.2, 2.7, 1.5, 0.8],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        updateStatusChart(data) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            
            if (this.charts.status) {
                this.charts.status.destroy();
            }

            this.charts.status = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Terminées', 'En cours', 'En attente'],
                    datasets: [{
                        data: [65, 25, 10],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        updateDeviceChart(data) {
            const ctx = document.getElementById('deviceTypeChart').getContext('2d');
            
            if (this.charts.deviceType) {
                this.charts.deviceType.destroy();
            }

            this.charts.deviceType = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['iPhone', 'Samsung', 'Huawei', 'Xiaomi', 'iPad'],
                    datasets: [{
                        label: 'Réparations',
                        data: [45, 32, 18, 12, 8],
                        backgroundColor: '#17a2b8'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        updateAttendanceChart(data) {
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            
            if (this.charts.attendance) {
                this.charts.attendance.destroy();
            }

            this.charts.attendance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jean', 'Marie', 'Paul', 'Sophie'],
                    datasets: [{
                        label: 'Heures travaillées',
                        data: [38, 42, 35, 40],
                        backgroundColor: '#ffc107'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        updateTableView() {
            // Mettre à jour la vue tableau
            const tbody = document.getElementById('kpiTableBody');
            tbody.innerHTML = `
                <div class="table-row">
                    <div class="table-cell"><strong>Marie Dupont</strong></div>
                    <div class="table-cell"><span class="badge bg-success">2.8</span></div>
                    <div class="table-cell">42</div>
                    <div class="table-cell">35.5h</div>
                    <div class="table-cell">3,200€</div>
                    <div class="table-cell"><span class="badge bg-success">Excellent</span></div>
                </div>
                <div class="table-row">
                    <div class="table-cell"><strong>Jean Martin</strong></div>
                    <div class="table-cell"><span class="badge bg-primary">2.1</span></div>
                    <div class="table-cell">38</div>
                    <div class="table-cell">32.0h</div>
                    <div class="table-cell">2,850€</div>
                    <div class="table-cell"><span class="badge bg-primary">Bon</span></div>
                </div>
            `;
        }

        showError(message) {
            console.error(message);
            // Implémenter l'affichage d'erreur
        }
    }

    // Initialiser le dashboard
    new KPIDashboard();
});
</script>
