<?php
/**
 * Dashboard KPI pour GeekBoard - Design SERVO complet
 * Reprend exactement le design des pages GeekBoard existantes
 */

// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/../config/subdomain_config.php';
// Le sous-domaine est détecté et la session est configurée avec le magasin correspondant

// Vérifier si l'utilisateur est connecté avec plusieurs variantes possibles
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Utilisateur';

if (!$user_id) {
    // Si pas d'user_id, rediriger vers la page d'accueil
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta charset="UTF-8">
    <meta name="description" content="Dashboard KPI - Indicateurs de performance GeekBoard">
    <meta name="theme-color" content="#0078e8">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/logo/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/images/logo/apple-touch-icon.png">
    
    <!-- iOS PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="GeekBoard KPI">
    <link rel="apple-touch-icon" href="../assets/images/logo/apple-touch-icon.png">
    
    <title>KPI Dashboard - GeekBoard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    
    <!-- jQuery et Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        :root {
            /* Couleurs principales GeekBoard/SERVO */
            --primary-color: #0078e8;
            --primary-light: #e6f2ff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --navbar-height: 55px;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            padding-top: var(--navbar-height);
        }
        
        /* Navbar GeekBoard Style */
        .navbar-geekboard {
            background-color: white !important;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: var(--navbar-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        
        .navbar-brand img {
            height: 40px;
        }
        
        .servo-text {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-left: 8px;
        }
        
        /* Container principal */
        .main-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header de page style GeekBoard */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), #4a96e6);
            color: white;
            padding: 2rem;
            margin: -20px -20px 30px -20px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        /* Cards style GeekBoard */
        .geek-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border: none;
        }
        
        .geek-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .geek-card .card-body {
            padding: 1.5rem;
        }
        
        /* Filtres container */
        .filters-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        /* KPI Cards */
        .kpi-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border: none;
            overflow: hidden;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .kpi-label {
            color: var(--secondary-color);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        /* Chart containers */
        .chart-container {
            position: relative;
            height: 400px;
            margin: 1rem 0;
        }
        
        /* Tables style GeekBoard */
        .table-geekboard {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .table-geekboard th {
            background-color: var(--light-color);
            border: none;
            font-weight: 600;
            color: var(--dark-color);
            padding: 1rem;
        }
        
        .table-geekboard td {
            border: none;
            padding: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        /* Badges personnalisés */
        .badge-custom {
            padding: 0.5rem 0.75rem;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin: 2rem 0;
        }
        
        /* Boutons style GeekBoard */
        .btn-geekboard {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-geekboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* User badge dans header */
        .user-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            
            .page-header {
                margin: -15px -15px 20px -15px;
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .kpi-value {
                font-size: 2rem;
            }
            
            .chart-container {
                height: 300px;
            }
        }
        
        @media (max-width: 576px) {
            .page-title {
                font-size: 1.25rem;
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar GeekBoard Style -->
    <nav class="navbar navbar-expand-lg navbar-geekboard">
        <div class="container-fluid px-3">
            <!-- Logo SERVO -->
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/images/logo/logoservo.png" alt="GeekBoard" height="40">
                <span class="servo-text">SERVO</span>
            </a>
            
            <!-- Message de bienvenue -->
            <div class="d-none d-md-flex align-items-center ms-auto me-3">
                <span class="fw-medium text-primary">
                    Bonjour, <?php echo htmlspecialchars($user_name_display); ?>
                    <?php if (isset($_SESSION['shop_name'])): ?>
                        <span class="badge bg-info ms-1"><?php echo htmlspecialchars($_SESSION['shop_name']); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <!-- Bouton retour -->
            <a href="../index.php" class="btn btn-outline-primary btn-geekboard">
                <i class="fas fa-arrow-left me-2"></i>
                Retour
            </a>
        </div>
    </nav>

    <div class="main-container">
        <!-- Header de page -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title">
                        <i class="fas fa-chart-line"></i>
                        Dashboard KPI
                    </h1>
                    <p class="page-subtitle">Indicateurs de performance en temps réel</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="user-badge">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($user_name_display); ?></span>
                        <?php if ($is_admin): ?>
                            <span class="badge bg-warning ms-2">Admin</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters-container">
            <div class="row g-3">
                <?php if ($is_admin): ?>
                <div class="col-md-3">
                    <label class="form-label fw-medium">
                        <i class="fas fa-user me-2"></i>
                        Employé
                    </label>
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
                    <label class="form-label fw-medium">
                        <i class="fas fa-calendar me-2"></i>
                        Date de début
                    </label>
                    <input type="date" id="dateStart" class="form-control" 
                           value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-medium">
                        <i class="fas fa-calendar me-2"></i>
                        Date de fin
                    </label>
                    <input type="date" id="dateEnd" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button id="refreshData" class="btn btn-primary btn-geekboard w-100">
                        <i class="fas fa-sync-alt me-2"></i>
                        Actualiser
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <h5 class="mt-3 text-muted">Chargement des données KPI...</h5>
            <p class="text-muted">Veuillez patienter pendant le calcul des indicateurs</p>
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
                <div class="geek-card">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-3">
                            <i class="fas fa-tools me-2 text-primary"></i>
                            Évolution des Réparations par Heure
                        </h5>
                        <div class="chart-container">
                            <canvas id="repairsByHourChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques de productivité -->
            <div class="col-lg-4">
                <div class="geek-card">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-3">
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
                <div class="geek-card">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-3">
                            <i class="fas fa-mobile-alt me-2 text-info"></i>
                            Top Appareils Réparés
                        </h5>
                        <div class="chart-container">
                            <canvas id="deviceTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Présence et temps de travail -->
            <div class="col-lg-6">
                <div class="geek-card">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-3">
                            <i class="fas fa-clock me-2 text-warning"></i>
                            Temps de Travail par Employé
                        </h5>
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableaux détaillés -->
        <div class="row g-4 mb-4">
            <!-- Top Performers -->
            <div class="col-lg-6">
                <div class="geek-card">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-3">
                            <i class="fas fa-trophy me-2 text-warning"></i>
                            Top Performers
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-geekboard" id="topPerformersTable">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="fas fa-user me-2"></i>
                                            Employé
                                        </th>
                                        <th>
                                            <i class="fas fa-tachometer-alt me-2"></i>
                                            Réparations/h
                                        </th>
                                        <th>
                                            <i class="fas fa-check-circle me-2"></i>
                                            Total Réparations
                                        </th>
                                        <th>
                                            <i class="fas fa-clock me-2"></i>
                                            Heures Travaillées
                                        </th>
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
                <div class="geek-card">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-3">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Détails par Employé
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-geekboard" id="employeeDetailsTable">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="fas fa-user me-2"></i>
                                            Employé
                                        </th>
                                        <th>
                                            <i class="fas fa-check me-2"></i>
                                            Terminées
                                        </th>
                                        <th>
                                            <i class="fas fa-cog me-2"></i>
                                            En cours
                                        </th>
                                        <th>
                                            <i class="fas fa-euro-sign me-2"></i>
                                            CA Généré
                                        </th>
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

        <!-- Footer informations -->
        <div class="geek-card">
            <div class="card-body text-center">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0 text-muted">
                            <i class="fas fa-chart-line me-2"></i>
                            Dashboard KPI - Données mises à jour en temps réel
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            <i class="fas fa-clock me-2"></i>
                            Dernière mise à jour : <span id="lastUpdate">--</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                document.getElementById('overviewSection').style.opacity = '0.5';
            }

            hideLoading() {
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('overviewSection').style.opacity = '1';
                document.getElementById('lastUpdate').textContent = new Date().toLocaleString('fr-FR');
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
                    this.showError('Erreur lors du chargement des données KPI: ' + error.message);
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
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
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
                        color: 'success',
                        description: 'Total sur la période'
                    },
                    {
                        title: 'Réparations par Heure',
                        value: avgRepairsPerHour.toFixed(2),
                        icon: 'fas fa-tachometer-alt',
                        color: 'primary',
                        description: 'Moyenne équipe'
                    },
                    {
                        title: 'Heures Travaillées',
                        value: totalHours.toFixed(1) + 'h',
                        icon: 'fas fa-clock',
                        color: 'info',
                        description: 'Total équipe'
                    },
                    {
                        title: 'Chiffre d\'Affaires',
                        value: new Intl.NumberFormat('fr-FR', { 
                            style: 'currency', 
                            currency: 'EUR' 
                        }).format(totalRevenue),
                        icon: 'fas fa-euro-sign',
                        color: 'warning',
                        description: 'Généré sur la période'
                    }
                ];

                kpiCardsContainer.innerHTML = kpiCards.map(card => `
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="card-body text-center">
                                <div class="kpi-icon bg-${card.color} bg-opacity-10 text-${card.color} mx-auto">
                                    <i class="${card.icon}"></i>
                                </div>
                                <div class="kpi-value">${card.value}</div>
                                <div class="kpi-label">${card.title}</div>
                                <small class="text-muted">${card.description}</small>
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
                    ctx.font = '16px Inter';
                    ctx.fillStyle = '#6c757d';
                    ctx.textAlign = 'center';
                    ctx.fillText('Aucune donnée disponible pour cette période', ctx.canvas.width / 2, ctx.canvas.height / 2);
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
                    const colors = ['#0078e8', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1'];
                    const color = colors[index % colors.length];
                    
                    return {
                        label: userName,
                        data: userGroups[userName],
                        borderColor: color,
                        backgroundColor: color + '20',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 6,
                        pointHoverRadius: 8
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
                                text: 'Évolution quotidienne des performances',
                                font: { family: 'Inter', size: 14, weight: '600' }
                            },
                            legend: {
                                position: 'top',
                                labels: { font: { family: 'Inter' } }
                            }
                        },
                        scales: {
                            x: {
                                type: 'time',
                                time: { unit: 'day' },
                                title: {
                                    display: true,
                                    text: 'Date',
                                    font: { family: 'Inter', weight: '600' }
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Réparations par heure',
                                    font: { family: 'Inter', weight: '600' }
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
                    ctx.font = '16px Inter';
                    ctx.fillStyle = '#6c757d';
                    ctx.textAlign = 'center';
                    ctx.fillText('Aucune donnée disponible', ctx.canvas.width / 2, ctx.canvas.height / 2);
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
                            backgroundColor: ['#28a745', '#ffc107', '#17a2b8'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { 
                                    font: { family: 'Inter' },
                                    padding: 20
                                }
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
                    ctx.font = '16px Inter';
                    ctx.fillStyle = '#6c757d';
                    ctx.textAlign = 'center';
                    ctx.fillText('Aucune donnée disponible', ctx.canvas.width / 2, ctx.canvas.height / 2);
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
                            backgroundColor: '#17a2b8',
                            borderRadius: 6,
                            borderSkipped: false
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
                                title: {
                                    display: true,
                                    text: 'Nombre de réparations',
                                    font: { family: 'Inter', weight: '600' }
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    font: { family: 'Inter' }
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
                    ctx.font = '16px Inter';
                    ctx.fillStyle = '#6c757d';
                    ctx.textAlign = 'center';
                    ctx.fillText('Aucune donnée disponible', ctx.canvas.width / 2, ctx.canvas.height / 2);
                    return;
                }

                this.charts.attendance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.full_name),
                        datasets: [{
                            label: 'Heures travaillées',
                            data: data.map(item => parseFloat(item.total_hours_worked || 0)),
                            backgroundColor: '#ffc107',
                            borderRadius: 6,
                            borderSkipped: false
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
                                title: {
                                    display: true,
                                    text: 'Heures',
                                    font: { family: 'Inter', weight: '600' }
                                }
                            },
                            x: {
                                ticks: { font: { family: 'Inter' } }
                            }
                        }
                    }
                });
            }

            updateTopPerformersTable(data) {
                const tbody = document.querySelector('#topPerformersTable tbody');
                
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-info-circle me-2"></i>Aucune donnée disponible</td></tr>';
                    return;
                }

                tbody.innerHTML = data.map((performer, index) => `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="badge-custom bg-primary text-white me-2">${index + 1}</div>
                                <strong>${performer.full_name}</strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge-custom bg-success text-white">
                                ${parseFloat(performer.repairs_per_hour || 0).toFixed(2)}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-primary">${performer.completed_repairs || 0}</span>
                        </td>
                        <td>
                            <span class="text-muted">${parseFloat(performer.hours_worked || 0).toFixed(1)}h</span>
                        </td>
                    </tr>
                `).join('');
            }

            updateEmployeeDetailsTable(data) {
                const tbody = document.querySelector('#employeeDetailsTable tbody');
                
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-info-circle me-2"></i>Aucune donnée disponible</td></tr>';
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
                            <span class="badge-custom bg-warning text-dark">
                                ${employee.repairs_in_progress || 0}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-success">
                                ${new Intl.NumberFormat('fr-FR', { 
                                    style: 'currency', 
                                    currency: 'EUR' 
                                }).format(employee.total_revenue || 0)}
                            </span>
                        </td>
                    </tr>
                `).join('');
            }

            showError(message) {
                // Créer une alerte Bootstrap moderne
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.style.borderRadius = '15px';
                alertDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                        <div>
                            <strong>Erreur de chargement</strong><br>
                            <small>${message}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.querySelector('.main-container').insertBefore(alertDiv, document.querySelector('.filters-container'));
                
                // Auto-supprimer après 8 secondes
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 8000);
            }
        }

        // Initialiser le dashboard au chargement
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Initialisation du Dashboard KPI GeekBoard');
            new KPIDashboard();
        });
    </script>
</body>
</html>
