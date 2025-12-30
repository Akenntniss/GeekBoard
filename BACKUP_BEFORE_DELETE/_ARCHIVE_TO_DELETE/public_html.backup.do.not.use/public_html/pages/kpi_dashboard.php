<?php
/**
 * Dashboard KPI pour GeekBoard
 * Interface moderne pour visualiser les indicateurs de performance
 */

// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/../config/subdomain_config.php';
// Le sous-domaine est détecté et la session est configurée avec le magasin correspondant

// Debug de la session pour identifier le problème
error_log("KPI Dashboard - Session debug: " . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté avec plusieurs variantes possibles
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Utilisateur';

if (!$user_id) {
    // Si pas d'user_id, rediriger vers la page d'accueil plutôt que login
    header('Location: /index.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$current_user_id = $user_id;
$is_admin = isset($user_role) && $user_role === 'admin';
$user_name_display = $user_name;

// Récupérer les utilisateurs pour le sélecteur (si admin)
$users = [];
if ($is_admin) {
    try {
        $pdo = getShopDBConnection();
        $stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'technicien') ORDER BY full_name");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Dashboard - GeekBoard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            background-color: var(--light-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .kpi-label {
            color: var(--secondary-color);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin: 1rem 0;
        }

        .filters-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .table-modern {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .table-modern th {
            background-color: var(--light-color);
            border: none;
            font-weight: 600;
            color: var(--dark-color);
            padding: 1rem;
        }

        .table-modern td {
            border: none;
            padding: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .badge-custom {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .progress-modern {
            height: 8px;
            border-radius: 4px;
            background-color: #e2e8f0;
        }

        .progress-modern .progress-bar {
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .kpi-value {
                font-size: 2rem;
            }
            
            .chart-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-chart-line me-3"></i>
                        Dashboard KPI
                    </h1>
                    <p class="mb-0 opacity-75">Indicateurs de performance en temps réel</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-light text-dark fs-6">
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

    <div class="container">
        <!-- Filtres -->
        <div class="filters-card">
            <div class="card-body">
                <div class="row g-3">
                    <?php if ($is_admin): ?>
                    <div class="col-md-3">
                        <label class="form-label">Employé</label>
                        <select id="userSelect" class="form-select">
                            <option value="">Tous les employés</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                    <span class="text-muted">(<?php echo ucfirst($user['role']); ?>)</span>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <label class="form-label">Date de début</label>
                        <input type="date" id="dateStart" class="form-control" 
                               value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Date de fin</label>
                        <input type="date" id="dateEnd" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button id="refreshData" class="btn btn-primary w-100">
                            <i class="fas fa-sync-alt me-2"></i>
                            Actualiser
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2 text-muted">Chargement des données KPI...</p>
        </div>

        <!-- KPI Cards Overview -->
        <div id="overviewSection">
            <div class="row g-4 mb-4" id="kpiCards">
                <!-- Les cartes KPI seront générées dynamiquement -->
            </div>
        </div>

        <!-- Graphiques principaux -->
        <div class="row g-4 mb-4">
            <!-- Réparations par heure -->
            <div class="col-lg-8">
                <div class="kpi-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tools me-2 text-primary"></i>
                            Réparations par Heure
                        </h5>
                        <div class="chart-container">
                            <canvas id="repairsByHourChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques de productivité -->
            <div class="col-lg-4">
                <div class="kpi-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie me-2 text-success"></i>
                            Répartition par Statut
                        </h5>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques secondaires -->
        <div class="row g-4 mb-4">
            <!-- Analyse par type d'appareil -->
            <div class="col-lg-6">
                <div class="kpi-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-mobile-alt me-2 text-info"></i>
                            Types d'Appareils
                        </h5>
                        <div class="chart-container">
                            <canvas id="deviceTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Présence et temps de travail -->
            <div class="col-lg-6">
                <div class="kpi-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-clock me-2 text-warning"></i>
                            Temps de Travail
                        </h5>
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableaux détaillés -->
        <div class="row g-4">
            <!-- Top Performers -->
            <div class="col-lg-6">
                <div class="kpi-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-trophy me-2 text-warning"></i>
                            Top Performers
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-modern" id="topPerformersTable">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Réparations/h</th>
                                        <th>Total Réparations</th>
                                        <th>Heures Travaillées</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Données générées dynamiquement -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails par employé -->
            <div class="col-lg-6">
                <div class="kpi-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Détails par Employé
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-modern" id="employeeDetailsTable">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Terminées</th>
                                        <th>En cours</th>
                                        <th>CA Généré</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Données générées dynamiquement -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        class KPIDashboard {
            constructor() {
                this.charts = {};
                this.currentFilters = {
                    user_id: '',
                    date_start: document.getElementById('dateStart').value,
                    date_end: document.getElementById('dateEnd').value
                };
                
                this.initializeEventListeners();
                this.loadAllData();
            }

            initializeEventListeners() {
                document.getElementById('refreshData').addEventListener('click', () => {
                    this.updateFilters();
                    this.loadAllData();
                });

                <?php if ($is_admin): ?>
                document.getElementById('userSelect').addEventListener('change', () => {
                    this.updateFilters();
                    this.loadAllData();
                });
                <?php endif; ?>

                document.getElementById('dateStart').addEventListener('change', () => {
                    this.updateFilters();
                });

                document.getElementById('dateEnd').addEventListener('change', () => {
                    this.updateFilters();
                });
            }

            updateFilters() {
                <?php if ($is_admin): ?>
                this.currentFilters.user_id = document.getElementById('userSelect').value;
                <?php else: ?>
                this.currentFilters.user_id = '<?php echo $current_user_id; ?>';
                <?php endif; ?>
                this.currentFilters.date_start = document.getElementById('dateStart').value;
                this.currentFilters.date_end = document.getElementById('dateEnd').value;
            }

            showLoading() {
                document.getElementById('loadingSpinner').style.display = 'block';
            }

            hideLoading() {
                document.getElementById('loadingSpinner').style.display = 'none';
            }

            async loadAllData() {
                this.showLoading();
                
                try {
                    // Charger toutes les données en parallèle
                    const [
                        repairsByHour,
                        productivityStats,
                        deviceAnalysis,
                        attendanceStats,
                        dashboardOverview
                    ] = await Promise.all([
                        this.fetchData('repairs_by_hour'),
                        this.fetchData('productivity_stats'),
                        this.fetchData('device_analysis'),
                        this.fetchData('attendance_stats'),
                        <?php if ($is_admin): ?>
                        this.fetchData('dashboard_overview')
                        <?php else: ?>
                        Promise.resolve(null)
                        <?php endif; ?>
                    ]);

                    // Mettre à jour l'interface
                    this.updateKPICards(repairsByHour, productivityStats, attendanceStats);
                    this.updateRepairsByHourChart(repairsByHour);
                    this.updateProductivityCharts(productivityStats);
                    this.updateDeviceTypeChart(deviceAnalysis);
                    this.updateAttendanceChart(attendanceStats);
                    
                    <?php if ($is_admin): ?>
                    if (dashboardOverview) {
                        this.updateTopPerformersTable(dashboardOverview.top_performers);
                    }
                    <?php endif; ?>
                    
                    this.updateEmployeeDetailsTable(productivityStats);

                } catch (error) {
                    console.error('Erreur lors du chargement des données:', error);
                    this.showError('Erreur lors du chargement des données KPI');
                } finally {
                    this.hideLoading();
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
                const kpiCardsContainer = document.getElementById('kpiCards');
                
                // Calculer les métriques principales
                let totalRepairs = 0;
                let totalHours = 0;
                let totalRevenue = 0;
                let avgRepairsPerHour = 0;

                if (productivityStats && productivityStats.length > 0) {
                    productivityStats.forEach(stat => {
                        totalRepairs += parseInt(stat.repairs_completed || 0);
                        totalRevenue += parseFloat(stat.total_revenue || 0);
                    });
                }

                if (attendanceStats && attendanceStats.length > 0) {
                    attendanceStats.forEach(stat => {
                        totalHours += parseFloat(stat.total_hours_worked || 0);
                    });
                }

                if (repairsByHour && repairsByHour.period_summary && repairsByHour.period_summary.length > 0) {
                    const totalRepairsPerHour = repairsByHour.period_summary.reduce((sum, item) => 
                        sum + parseFloat(item.avg_repairs_per_hour || 0), 0);
                    avgRepairsPerHour = totalRepairsPerHour / repairsByHour.period_summary.length;
                }

                const kpiCards = [
                    {
                        title: 'Réparations Terminées',
                        value: totalRepairs,
                        icon: 'fas fa-check-circle',
                        color: 'success'
                    },
                    {
                        title: 'Réparations/Heure',
                        value: avgRepairsPerHour.toFixed(2),
                        icon: 'fas fa-tachometer-alt',
                        color: 'primary'
                    },
                    {
                        title: 'Heures Travaillées',
                        value: totalHours.toFixed(1) + 'h',
                        icon: 'fas fa-clock',
                        color: 'info'
                    },
                    {
                        title: 'Chiffre d\'Affaires',
                        value: new Intl.NumberFormat('fr-FR', { 
                            style: 'currency', 
                            currency: 'EUR' 
                        }).format(totalRevenue),
                        icon: 'fas fa-euro-sign',
                        color: 'warning'
                    }
                ];

                kpiCardsContainer.innerHTML = kpiCards.map(card => `
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="kpi-icon bg-${card.color} bg-opacity-10 text-${card.color} me-3">
                                    <i class="${card.icon}"></i>
                                </div>
                                <div>
                                    <div class="kpi-value">${card.value}</div>
                                    <div class="kpi-label">${card.title}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            updateRepairsByHourChart(data) {
                const ctx = document.getElementById('repairsByHourChart').getContext('2d');
                
                if (this.charts.repairsByHour) {
                    this.charts.repairsByHour.destroy();
                }

                if (!data || !data.daily_details || data.daily_details.length === 0) {
                    ctx.font = '16px Arial';
                    ctx.fillStyle = '#64748b';
                    ctx.textAlign = 'center';
                    ctx.fillText('Aucune donnée disponible', ctx.canvas.width / 2, ctx.canvas.height / 2);
                    return;
                }

                // Grouper par utilisateur
                const userGroups = {};
                data.daily_details.forEach(item => {
                    if (!userGroups[item.full_name]) {
                        userGroups[item.full_name] = [];
                    }
                    userGroups[item.full_name].push({
                        x: item.work_date,
                        y: parseFloat(item.repairs_per_hour || 0)
                    });
                });

                const datasets = Object.keys(userGroups).map((userName, index) => {
                    const colors = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
                    const color = colors[index % colors.length];
                    
                    return {
                        label: userName,
                        data: userGroups[userName],
                        borderColor: color,
                        backgroundColor: color + '20',
                        fill: false,
                        tension: 0.4
                    };
                });

                this.charts.repairsByHour = new Chart(ctx, {
                    type: 'line',
                    data: { datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Évolution des réparations par heure'
                            },
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day'
                                },
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Réparations/heure'
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            updateProductivityCharts(data) {
                const ctx = document.getElementById('statusChart').getContext('2d');
                
                if (this.charts.status) {
                    this.charts.status.destroy();
                }

                if (!data || data.length === 0) {
                    return;
                }

                // Calculer les totaux
                let totalCompleted = 0;
                let totalInProgress = 0;
                let totalQuotesSent = 0;

                data.forEach(item => {
                    totalCompleted += parseInt(item.repairs_completed || 0);
                    totalInProgress += parseInt(item.repairs_in_progress || 0);
                    totalQuotesSent += parseInt(item.quotes_sent || 0);
                });

                this.charts.status = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Terminées', 'En cours', 'Devis envoyés'],
                        datasets: [{
                            data: [totalCompleted, totalInProgress, totalQuotesSent],
                            backgroundColor: ['#10b981', '#f59e0b', '#06b6d4'],
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
            }

            updateDeviceTypeChart(data) {
                const ctx = document.getElementById('deviceTypeChart').getContext('2d');
                
                if (this.charts.deviceType) {
                    this.charts.deviceType.destroy();
                }

                if (!data || data.length === 0) {
                    return;
                }

                // Prendre les 10 types les plus fréquents
                const sortedData = data.sort((a, b) => b.total_repairs - a.total_repairs).slice(0, 10);

                this.charts.deviceType = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: sortedData.map(item => `${item.type_appareil} ${item.marque}`),
                        datasets: [{
                            label: 'Nombre de réparations',
                            data: sortedData.map(item => item.total_repairs),
                            backgroundColor: '#2563eb',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Nombre de réparations'
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 45
                                }
                            }
                        }
                    }
                });
            }

            updateAttendanceChart(data) {
                const ctx = document.getElementById('attendanceChart').getContext('2d');
                
                if (this.charts.attendance) {
                    this.charts.attendance.destroy();
                }

                if (!data || data.length === 0) {
                    return;
                }

                this.charts.attendance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.full_name),
                        datasets: [{
                            label: 'Heures travaillées',
                            data: data.map(item => parseFloat(item.total_hours_worked || 0)),
                            backgroundColor: '#f59e0b',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Heures'
                                }
                            }
                        }
                    }
                });
            }

            updateTopPerformersTable(data) {
                const tbody = document.querySelector('#topPerformersTable tbody');
                
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Aucune donnée disponible</td></tr>';
                    return;
                }

                tbody.innerHTML = data.map(performer => `
                    <tr>
                        <td>
                            <strong>${performer.full_name}</strong>
                        </td>
                        <td>
                            <span class="badge-custom bg-primary text-white">
                                ${parseFloat(performer.repairs_per_hour || 0).toFixed(2)}
                            </span>
                        </td>
                        <td>${performer.completed_repairs || 0}</td>
                        <td>${parseFloat(performer.hours_worked || 0).toFixed(1)}h</td>
                    </tr>
                `).join('');
            }

            updateEmployeeDetailsTable(data) {
                const tbody = document.querySelector('#employeeDetailsTable tbody');
                
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Aucune donnée disponible</td></tr>';
                    return;
                }

                tbody.innerHTML = data.map(employee => `
                    <tr>
                        <td>
                            <strong>${employee.full_name}</strong>
                        </td>
                        <td>
                            <span class="badge-custom bg-success text-white">
                                ${employee.repairs_completed || 0}
                            </span>
                        </td>
                        <td>
                            <span class="badge-custom bg-warning text-white">
                                ${employee.repairs_in_progress || 0}
                            </span>
                        </td>
                        <td>
                            ${new Intl.NumberFormat('fr-FR', { 
                                style: 'currency', 
                                currency: 'EUR' 
                            }).format(employee.total_revenue || 0)}
                        </td>
                    </tr>
                `).join('');
            }

            showError(message) {
                // Créer une alerte Bootstrap
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.filters-card'));
                
                // Auto-supprimer après 5 secondes
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        }

        // Initialiser le dashboard
        document.addEventListener('DOMContentLoaded', function() {
            new KPIDashboard();
        });
    </script>
</body>
</html>
