<?php
/**
 * Dashboard KPI GeekBoard - Version Complète avec Onglets
 * Inclut : Dashboard KPI, Notes Employés, Notes Magasin, Profils IA
 */

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification authentification  
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Utilisateur';

if (!$user_id) {
    header('Location: /index.php');
    exit();
}

$is_admin = ($user_role === 'admin');

// Récupérer les utilisateurs (pour filtres)
$users = [];
if ($is_admin) {
    try {
        $pdo = getShopDBConnection();
        $stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'technicien') ORDER BY full_name");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur récupération users: " . $e->getMessage());
    }
}

$page_title = "Dashboard KPI";
// Charger Chart.js et Google Fonts si nécessaire
?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
        :root {
            --primary: #0078e8;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --dark: #343a40;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            padding-top: 70px;
        }
        
        /* Navbar */
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 60px;
        }
        
        /* Onglets Navigation */
        .nav-tabs-custom {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            padding: 12px 20px;
            border-radius: 8px;
            margin-right: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .nav-tabs-custom .nav-link:hover {
            background: #f8f9fa;
            color: var(--primary);
        }
        
        .nav-tabs-custom .nav-link.active {
            background: var(--primary);
            color: white;
        }
        
        .nav-tabs-custom .nav-link i {
            margin-right: 8px;
        }
        
        /* Cards */
        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .kpi-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        /* Filtres */
        .filters-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        /* Loading */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .spinner-border-custom {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 350px;
            margin-top: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }
            
            .nav-tabs-custom .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .kpi-value {
                font-size: 1.5rem;
            }
        }
    </style>
    
    <?php include_once __DIR__ . '/../includes/night-mode-system.php'; ?>
</head>
<body>

<?php if (!isset($is_standalone_mode) || !$is_standalone_mode): ?>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-chart-line text-primary"></i>
            <strong class="ms-2">Dashboard KPI</strong>
        </a>
        
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3">
                <i class="fas fa-user-circle"></i>
                <?php echo htmlspecialchars($user_name); ?>
                <?php if ($is_admin): ?>
                    <span class="badge bg-warning ms-2">Admin</span>
                <?php endif; ?>
            </span>
            <a href="../index.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</nav>
<?php endif; ?>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-light spinner-border-custom" role="status"></div>
        <div class="text-white mt-3">Chargement...</div>
    </div>
</div>

<div class="container-fluid mt-4">
    
    <!-- Navigation Onglets -->
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs border-0" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                    <i class="fas fa-chart-bar"></i> Dashboard KPI
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notes-employes-tab" data-bs-toggle="tab" data-bs-target="#notes-employes" type="button" role="tab">
                    <i class="fas fa-user-edit"></i> Notes Employés
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notes-magasin-tab" data-bs-toggle="tab" data-bs-target="#notes-magasin" type="button" role="tab">
                    <i class="fas fa-store"></i> Notes Magasin
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profils-ia-tab" data-bs-toggle="tab" data-bs-target="#profils-ia" type="button" role="tab">
                    <i class="fas fa-robot"></i> Profils IA
                </button>
            </li>
        </ul>
    </div>
    
    <!-- Contenu des Onglets -->
    <div class="tab-content" id="mainTabsContent">
        
        <!-- ONGLET 1: DASHBOARD KPI -->
        <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
            
            <!-- Filtres -->
            <div class="filters-card">
                <div class="row g-3">
                    <?php if ($is_admin): ?>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user me-2"></i>Employé
                        </label>
                        <select id="filterEmployee" class="form-select">
                            <option value="">Tous les employés</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-calendar me-2"></i>Date Début
                        </label>
                        <input type="date" id="filterDateStart" class="form-control" 
                               value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-calendar me-2"></i>Date Fin
                        </label>
                        <input type="date" id="filterDateEnd" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button id="btnRefresh" class="btn btn-primary w-100">
                            <i class="fas fa-sync-alt me-2"></i>Actualiser
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- KPI Cards -->
            <div class="row" id="kpiCardsContainer">
                <!-- Rempli dynamiquement par JavaScript -->
            </div>
            
            <!-- Graphiques -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="kpi-card">
                        <h5 class="mb-3">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Évolution du Chiffre d'Affaires
                        </h5>
                        <div class="chart-container">
                            <canvas id="chartCA"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="kpi-card">
                        <h5 class="mb-3">
                            <i class="fas fa-chart-pie text-success me-2"></i>
                            Répartition Réparations
                        </h5>
                        <div class="chart-container">
                            <canvas id="chartReparations"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tableaux Détaillés -->
            <div class="row">
                <div class="col-12">
                    <div class="kpi-card">
                        <h5 class="mb-3">
                            <i class="fas fa-users text-info me-2"></i>
                            Performance par Employé
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tableEmployees">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Réparations</th>
                                        <th>CA Encaissé</th>
                                        <th>CA Total</th>
                                        <th>Panier Moyen</th>
                                        <th>Taux Autonomie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Rempli dynamiquement -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        
        <!-- ONGLET 2: NOTES EMPLOYÉS -->
        <div class="tab-pane fade" id="notes-employes" role="tabpanel">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-sticky-note text-warning me-2"></i>
                        Gestion des Notes Employés
                    </h5>
                    <button class="btn btn-primary" onclick="openEmployeeNoteModal()">
                        <i class="fas fa-plus me-2"></i>Ajouter une note
                    </button>
                </div>
                
                <!-- Filtres Notes -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <select class="form-select" id="filterNoteEmployee">
                            <option value="">Tous les employés</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterNoteType">
                            <option value="">Tous les types</option>
                            <option value="avertissement">Avertissement</option>
                            <option value="incident">Incident</option>
                            <option value="appreciation">Appréciation</option>
                            <option value="remarque">Remarque</option>
                            <option value="sanction">Sanction</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterNoteSeverity">
                            <option value="">Toutes gravités</option>
                            <option value="info">Info</option>
                            <option value="low">Faible</option>
                            <option value="medium">Moyen</option>
                            <option value="high">Élevé</option>
                            <option value="critical">Critique</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="loadEmployeeNotes()">
                            <i class="fas fa-filter me-2"></i>Filtrer
                        </button>
                    </div>
                </div>
                
                <!-- Liste Notes -->
                <div id="employeeNotesContainer">
                    <!-- Rempli dynamiquement -->
                </div>
            </div>
        </div>
        
        <!-- ONGLET 3: NOTES MAGASIN -->
        <div class="tab-pane fade" id="notes-magasin" role="tabpanel">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-building text-info me-2"></i>
                        Gestion des Notes Magasin
                    </h5>
                    <button class="btn btn-primary" onclick="openShopNoteModal()">
                        <i class="fas fa-plus me-2"></i>Ajouter un événement
                    </button>
                </div>
                
                <!-- Timeline visuelle -->
                <div id="shopNotesTimeline">
                    <!-- Timeline des événements -->
                </div>
                
                <!-- Liste Notes Magasin -->
                <div id="shopNotesContainer">
                    <!-- Rempli dynamiquement -->
                </div>
            </div>
        </div>
        
        <!-- ONGLET 4: PROFILS IA -->
        <div class="tab-pane fade" id="profils-ia" role="tabpanel">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-user-cog text-primary me-2"></i>
                        Gestion des Profils d'Experts IA
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="openProfileModal()">
                            <i class="fas fa-plus me-2"></i>Créer un profil
                        </button>
                        <a href="/pages/ai_profiles.php" class="btn btn-outline-primary">
                            <i class="fas fa-cogs me-2"></i>Gérer les profils
                        </a>
                    </div>
                </div>
                
                <div id="profilesContainer">
                    <!-- Liste des profils -->
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Modals -->
<?php include_once __DIR__ . '/../includes/kpi_modals.php'; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Suite du JavaScript dans le prochain fichier...
// (Le fichier est trop volumineux, je continue dans un fichier JS séparé)
</script>

<script src="assets/js/kpi_dashboard.js?v=<?php echo time(); ?>"></script>
