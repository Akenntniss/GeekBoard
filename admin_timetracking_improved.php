<?php
/**
 * Interface administrateur améliorée pour le système de pointage
 * Inspirée des meilleures pratiques des logiciels professionnels de gestion du temps
 * Version moderne avec dashboard avancé, graphiques et analytics
 */

// Inclusion des fichiers de configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Vérifier l'authentification et les droits admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$shop_pdo = getShopDBConnection();

// Traitement des actions admin (API endpoint)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'force_clock_out':
            $user_id = intval($_POST['user_id']);
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = NOW(), 
                    status = 'completed',
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nForced clock-out by admin at ', NOW()),
                    total_hours = TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0,
                    work_duration = (TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0) - IFNULL(break_duration, 0)
                WHERE user_id = ? AND status IN ('active', 'break')
            ");
            
            if ($stmt->execute([$user_id])) {
                $response = ['success' => true, 'message' => 'Pointage forcé avec succès'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors du pointage forcé'];
            }
            break;
            
        case 'approve_entry':
            $entry_id = intval($_POST['entry_id']);
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET admin_approved = TRUE, admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nApproved by admin at ', NOW())
                WHERE id = ?
            ");
            
            if ($stmt->execute([$entry_id])) {
                $response = ['success' => true, 'message' => 'Entrée approuvée'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors de l\'approbation'];
            }
            break;
            
        case 'edit_entry':
            $entry_id = intval($_POST['entry_id']);
            $clock_in = $_POST['edit_clock_in'];
            $clock_out = $_POST['edit_clock_out'] ?: null;
            $notes = $_POST['edit_notes'];
            
            $sql = "UPDATE time_tracking SET clock_in = ?, notes = ?, admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nEdited by admin at ', NOW())";
            $params = [$clock_in, $notes];
            
            if ($clock_out) {
                $sql .= ", clock_out = ?, status = 'completed', total_hours = TIMESTAMPDIFF(MINUTE, ?, ?) / 60.0, work_duration = (TIMESTAMPDIFF(MINUTE, ?, ?) / 60.0) - IFNULL(break_duration, 0)";
                $params = array_merge($params, [$clock_out, $clock_in, $clock_out, $clock_in, $clock_out]);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $entry_id;
            
            $stmt = $shop_pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $response = ['success' => true, 'message' => 'Entrée modifiée avec succès'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors de la modification'];
            }
            break;
            
        case 'send_notification':
            $user_id = intval($_POST['user_id']);
            $message = $_POST['message'];
            
            // Note: Implémentation simplifiée - dans un vrai système, utiliser WebSocket ou push notifications
            $stmt = $shop_pdo->prepare("
                INSERT INTO admin_notifications (user_id, message, sent_by, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$user_id, $message, $current_user_id])) {
                $response = ['success' => true, 'message' => 'Notification envoyée'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors de l\'envoi'];
            }
            break;
    }
    
    // Retourner la réponse en JSON pour les requêtes AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Récupérer les données pour l'affichage
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_user = $_GET['user'] ?? '';
$filter_status = $_GET['status'] ?? '';
$view_mode = $_GET['view'] ?? 'dashboard'; // dashboard, list, calendar, reports

// Utilisateurs actifs (pointés en ce moment)
$stmt = $shop_pdo->prepare("
    SELECT tt.*, u.full_name, u.username, u.role, u.profile_picture,
           TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) / 60.0 as current_duration,
           TIME_FORMAT(TIMEDIFF(NOW(), tt.clock_in), '%H:%i') as formatted_duration,
           CASE 
               WHEN TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 8 THEN 'overtime'
               WHEN TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 6 THEN 'normal'
               ELSE 'short'
           END as duration_status
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE tt.status IN ('active', 'break')
    ORDER BY tt.clock_in ASC
");
$stmt->execute();
$active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques avancées
$stmt = $shop_pdo->prepare("
    SELECT 
        COUNT(*) as total_sessions,
        COUNT(DISTINCT user_id) as active_employees,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
        SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break,
        AVG(work_duration) as avg_work_hours,
        SUM(work_duration) as total_work_hours,
        COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, clock_in, clock_out) > 8 THEN 1 END) as overtime_sessions,
        COUNT(CASE WHEN admin_approved = 0 THEN 1 END) as pending_approvals
    FROM time_tracking
    WHERE DATE(clock_in) = ?
");
$stmt->execute([$filter_date]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Alertes et anomalies
$alerts = [];

// Alertes pour heures supplémentaires
$stmt = $shop_pdo->prepare("
    SELECT u.full_name, tt.clock_in, 
           TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) as hours_worked
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE tt.status IN ('active', 'break') 
    AND TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 8
");
$stmt->execute();
$overtime_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($overtime_alerts as $alert) {
    $alerts[] = [
        'type' => 'warning',
        'icon' => 'fas fa-clock',
        'title' => 'Heures supplémentaires',
        'message' => "{$alert['full_name']} travaille depuis {$alert['hours_worked']}h",
        'action' => 'force_clock_out',
        'user_id' => $alert['user_id'] ?? null
    ];
}

// Entrées non approuvées
$stmt = $shop_pdo->prepare("
    SELECT COUNT(*) as count 
    FROM time_tracking 
    WHERE admin_approved = 0 AND status = 'completed' AND DATE(clock_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->execute();
$unapproved_count = $stmt->fetch(PDO::FETCH_COLUMN);

if ($unapproved_count > 0) {
    $alerts[] = [
        'type' => 'info',
        'icon' => 'fas fa-check-circle',
        'title' => 'Approbations en attente',
        'message' => "{$unapproved_count} entrée(s) à approuver",
        'action' => 'view_pending'
    ];
}

// Données pour les graphiques (derniers 7 jours)
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $shop_pdo->prepare("
        SELECT 
            COUNT(DISTINCT user_id) as employees,
            COALESCE(SUM(work_duration), 0) as total_hours,
            COUNT(*) as sessions
        FROM time_tracking 
        WHERE DATE(clock_in) = ? AND status = 'completed'
    ");
    $stmt->execute([$date]);
    $day_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $chart_data[] = [
        'date' => $date,
        'display_date' => date('d/m', strtotime($date)),
        'employees' => $day_stats['employees'],
        'hours' => round($day_stats['total_hours'], 1),
        'sessions' => $day_stats['sessions']
    ];
}

// Entrées du jour avec filtres
$where_conditions = ["DATE(tt.clock_in) = ?"];
$params = [$filter_date];

if ($filter_user) {
    $where_conditions[] = "tt.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_status) {
    $where_conditions[] = "tt.status = ?";
    $params[] = $filter_status;
}

$stmt = $shop_pdo->prepare("
    SELECT tt.*, u.full_name, u.username, u.role, u.profile_picture,
           CASE 
               WHEN tt.work_duration > 8 THEN 'overtime'
               WHEN tt.work_duration < 4 AND tt.status = 'completed' THEN 'short'
               ELSE 'normal'
           END as duration_category
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE " . implode(' AND ', $where_conditions) . "
    ORDER BY tt.clock_in DESC
");
$stmt->execute($params);
$daily_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Liste des utilisateurs pour les filtres
$stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users ORDER BY full_name");
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top performers de la semaine
$stmt = $shop_pdo->prepare("
    SELECT u.full_name, 
           COUNT(*) as sessions,
           SUM(tt.work_duration) as total_hours,
           AVG(tt.work_duration) as avg_hours,
           COUNT(CASE WHEN DATE(tt.clock_in) = CURDATE() THEN 1 END) as today_sessions
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE tt.status = 'completed' 
    AND DATE(tt.clock_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY tt.user_id, u.full_name
    ORDER BY total_hours DESC
    LIMIT 5
");
$stmt->execute();
$top_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Gestion Pointage | GeekBoard</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0066cc;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navigation moderne */
        .admin-nav {
            background: linear-gradient(135deg, var(--primary-color), #004499);
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            padding: 1rem;
            box-shadow: var(--box-shadow);
        }

        .nav-tabs-custom {
            border: none;
            margin: 0;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: rgba(255, 255, 255, 0.8);
            background: transparent;
            border-radius: 8px;
            margin-right: 0.5rem;
            transition: var(--transition);
        }

        .nav-tabs-custom .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-tabs-custom .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border: none;
        }

        /* Cards modernes */
        .stats-card {
            background: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            overflow: hidden;
            position: relative;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
        }

        .stats-card.warning::before {
            background: linear-gradient(90deg, var(--warning-color), #e0a800);
        }

        .stats-card.danger::before {
            background: linear-gradient(90deg, var(--danger-color), #c82333);
        }

        .stats-card.info::before {
            background: linear-gradient(90deg, var(--info-color), #138496);
        }

        /* Utilisateurs actifs */
        .active-user-card {
            background: white;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--success-color);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            margin-bottom: 1rem;
        }

        .active-user-card:hover {
            transform: translateX(4px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .break-user-card {
            border-left-color: var(--warning-color);
            background: linear-gradient(45deg, #fff3cd, #ffeaa7);
        }

        .overtime-user-card {
            border-left-color: var(--danger-color);
            background: linear-gradient(45deg, #f8d7da, #f5c6cb);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        /* Alertes */
        .alert-panel {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
        }

        .alert-item {
            border-left: 4px solid;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .alert-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .alert-item.warning {
            border-left-color: var(--warning-color);
            background: linear-gradient(90deg, #fff3cd, #ffeaa7);
        }

        .alert-item.danger {
            border-left-color: var(--danger-color);
            background: linear-gradient(90deg, #f8d7da, #f5c6cb);
        }

        .alert-item.info {
            border-left-color: var(--info-color);
            background: linear-gradient(90deg, #d1ecf1, #bee5eb);
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-color), var(--success-color));
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.875rem;
            top: 1rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid white;
            box-shadow: 0 0 0 3px var(--primary-color);
        }

        /* Filtres modernes */
        .filter-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e0e6ed;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
        }

        /* Boutons modernes */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #004499);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #004499, #003366);
            transform: translateY(-1px);
        }

        /* Tableaux modernes */
        .table-modern {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table-modern thead {
            background: linear-gradient(135deg, var(--dark-color), #495057);
            color: white;
        }

        .table-modern tbody tr {
            border: none;
            transition: var(--transition);
        }

        .table-modern tbody tr:hover {
            background: rgba(0, 102, 204, 0.05);
            transform: scale(1.01);
        }

        /* Badges et status */
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .duration-display {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-weight: bold;
            font-size: 1.1em;
            padding: 0.25rem 0.5rem;
            background: rgba(0, 102, 204, 0.1);
            border-radius: 6px;
            display: inline-block;
        }

        /* Graphiques */
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-nav {
                padding: 1rem 0.5rem;
            }
            
            .nav-tabs-custom .nav-link {
                margin-right: 0.25rem;
                font-size: 0.9rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .timeline {
                padding-left: 1rem;
            }
            
            .timeline::before {
                left: 0.25rem;
            }
            
            .timeline-item::before {
                left: -1.375rem;
            }
        }

        /* Notifications flottantes */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        /* Modal moderne */
        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #004499);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        /* Loading states */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .spinner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid mt-4 fade-in">
        <!-- Header avec navigation -->
        <div class="admin-nav">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="text-white mb-1">
                        <i class="fas fa-tachometer-alt"></i> Dashboard Pointage
                    </h1>
                    <p class="text-white-50 mb-0">Supervision avancée et analytics des temps de travail</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light btn-sm" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="exportData()">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                    <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#settingsModal">
                        <i class="fas fa-cog"></i> Paramètres
                    </button>
                </div>
            </div>
            
            <!-- Navigation par onglets -->
            <ul class="nav nav-tabs nav-tabs-custom" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="live-tab" data-bs-toggle="tab" data-bs-target="#live" type="button" role="tab">
                        <i class="fas fa-broadcast-tower"></i> Temps Réel
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                        <i class="fas fa-history"></i> Historique
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                        <i class="fas fa-chart-bar"></i> Rapports
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">
                        <i class="fas fa-bell"></i> Alertes
                        <?php if (count($alerts) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo count($alerts); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenu des onglets -->
        <div class="tab-content" id="adminTabsContent">
            
            <!-- Dashboard Principal -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <!-- KPIs principaux -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="text-success mb-1"><?php echo $stats['currently_working']; ?></h3>
                                        <small class="text-muted">Actuellement au travail</small>
                                    </div>
                                    <i class="fas fa-users fa-2x text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card warning">
                            <div class="card-body text-center p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="text-warning mb-1"><?php echo $stats['on_break']; ?></h3>
                                        <small class="text-muted">En pause</small>
                                    </div>
                                    <i class="fas fa-pause fa-2x text-warning opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card info">
                            <div class="card-body text-center p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="text-info mb-1"><?php echo number_format($stats['total_work_hours'], 1); ?>h</h3>
                                        <small class="text-muted">Total heures aujourd'hui</small>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-info opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card danger">
                            <div class="card-body text-center p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="text-danger mb-1"><?php echo $stats['pending_approvals']; ?></h3>
                                        <small class="text-muted">À approuver</small>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-danger opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="chart-container">
                            <h5><i class="fas fa-chart-line text-primary"></i> Évolution 7 derniers jours</h5>
                            <canvas id="weeklyChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="chart-container">
                            <h5><i class="fas fa-chart-doughnut text-primary"></i> Répartition équipes</h5>
                            <canvas id="teamChart" width="200" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top performers et indicateurs -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card stats-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Performers (7 jours)</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($top_performers as $index => $performer): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-pill"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($performer['full_name']); ?></h6>
                                        <small class="text-muted"><?php echo number_format($performer['total_hours'], 1); ?>h total • <?php echo $performer['sessions']; ?> sessions</small>
                                    </div>
                                    <div>
                                        <span class="duration-display text-success"><?php echo number_format($performer['avg_hours'], 1); ?>h/jour</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card stats-card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistiques Rapides</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <h4 class="text-primary"><?php echo number_format($stats['avg_work_hours'], 1); ?>h</h4>
                                        <small class="text-muted">Moyenne/employé</small>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <h4 class="text-warning"><?php echo $stats['overtime_sessions']; ?></h4>
                                        <small class="text-muted">Heures sup.</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success"><?php echo $stats['total_sessions']; ?></h4>
                                        <small class="text-muted">Sessions totales</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-info"><?php echo round(($stats['currently_working'] / max($stats['active_employees'], 1)) * 100); ?>%</h4>
                                        <small class="text-muted">Taux présence</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Temps réel -->
            <div class="tab-pane fade" id="live" role="tabpanel">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3><i class="fas fa-satellite-dish text-success"></i> Activité en Temps Réel</h3>
                            <div>
                                <span class="badge bg-success pulse-animation">
                                    <i class="fas fa-circle"></i> LIVE
                                </span>
                                <small class="text-muted ms-2">Dernière MàJ: <span id="lastUpdate"><?php echo date('H:i:s'); ?></span></small>
                            </div>
                        </div>
                        
                        <?php if (!empty($active_users)): ?>
                        <div class="row" id="activeUsersContainer">
                            <?php foreach ($active_users as $user): ?>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="active-user-card <?php 
                                    echo $user['status'] === 'break' ? 'break-user-card' : 
                                        ($user['duration_status'] === 'overtime' ? 'overtime-user-card' : ''); 
                                ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <?php if (isset($user['profile_picture']) && $user['profile_picture']): ?>
                                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                                     class="rounded-circle" width="48" height="48" alt="Avatar">
                                                <?php else: ?>
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 48px; height: 48px; font-size: 1.2rem;">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $user['status'] === 'break' ? 'warning' : 'success'; ?>">
                                                    <?php echo $user['status'] === 'break' ? 'Pause' : 'Actif'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">Temps écoulé</small>
                                                <span class="duration-display" id="duration-<?php echo $user['user_id']; ?>">
                                                    <?php echo $user['formatted_duration']; ?>
                                                </span>
                                            </div>
                                            <div class="progress mt-2" style="height: 6px;">
                                                <div class="progress-bar <?php 
                                                    echo $user['duration_status'] === 'overtime' ? 'bg-danger' : 
                                                        ($user['duration_status'] === 'normal' ? 'bg-success' : 'bg-info'); 
                                                ?>" 
                                                     style="width: <?php echo min(($user['current_duration'] / 8) * 100, 100); ?>%"></div>
                                            </div>
                                            <small class="text-muted">
                                                Début: <?php echo date('H:i', strtotime($user['clock_in'])); ?>
                                                <?php if ($user['duration_status'] === 'overtime'): ?>
                                                <span class="text-danger ms-2">⚠️ Heures sup.</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-outline-danger btn-sm flex-fill" 
                                                    onclick="forceClockOut(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                                <i class="fas fa-sign-out-alt"></i> Sortie
                                            </button>
                                            <button class="btn btn-outline-info btn-sm" 
                                                    onclick="sendNotification(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" 
                                                    onclick="viewUserDetails(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucun employé actuellement pointé</h4>
                            <p class="text-muted">Les employés pointés apparaîtront ici en temps réel</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Historique -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <!-- Filtres avancés -->
                <div class="filter-section">
                    <h5><i class="fas fa-filter"></i> Filtres Avancés</h5>
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="view" value="history">
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="user" class="form-label">Employé</label>
                            <select class="form-select" id="user" name="user">
                                <option value="">Tous les employés</option>
                                <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Actif</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                                <option value="break" <?php echo $filter_status === 'break' ? 'selected' : ''; ?>>En pause</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-search"></i> Rechercher
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="exportFilteredData()">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tableau des entrées -->
                <div class="card table-modern">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-table"></i> Entrées de pointage - <?php echo date('d/m/Y', strtotime($filter_date)); ?>
                            </h5>
                            <div>
                                <button class="btn btn-outline-primary btn-sm" onclick="toggleView()">
                                    <i class="fas fa-th-list"></i> Vue liste
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="approveAll()">
                                    <i class="fas fa-check-double"></i> Tout approuver
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="3%">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th>Employé</th>
                                        <th>Arrivée</th>
                                        <th>Sortie</th>
                                        <th>Temps</th>
                                        <th>Pause</th>
                                        <th>Statut</th>
                                        <th>Efficacité</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($daily_entries)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <i class="fas fa-clock fa-3x text-muted mb-3"></i><br>
                                            <h5 class="text-muted">Aucune entrée trouvée</h5>
                                            <p class="text-muted">Aucune donnée ne correspond aux critères sélectionnés</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($daily_entries as $entry): ?>
                                    <tr data-entry-id="<?php echo $entry['id']; ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input entry-checkbox" value="<?php echo $entry['id']; ?>">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (isset($entry['profile_picture']) && $entry['profile_picture']): ?>
                                                <img src="<?php echo htmlspecialchars($entry['profile_picture']); ?>" 
                                                     class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                                                <?php else: ?>
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    <?php echo strtoupper(substr($entry['full_name'], 0, 1)); ?>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($entry['full_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($entry['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-success"><?php echo date('H:i', strtotime($entry['clock_in'])); ?></span><br>
                                            <small class="text-muted"><?php echo date('d/m', strtotime($entry['clock_in'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($entry['clock_out']): ?>
                                                <span class="text-danger"><?php echo date('H:i', strtotime($entry['clock_out'])); ?></span><br>
                                                <small class="text-muted"><?php echo date('d/m', strtotime($entry['clock_out'])); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-warning">En cours</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($entry['work_duration']): ?>
                                                <span class="duration-display text-<?php 
                                                    echo $entry['duration_category'] === 'overtime' ? 'danger' : 
                                                        ($entry['duration_category'] === 'short' ? 'warning' : 'success'); 
                                                ?>">
                                                    <?php echo number_format($entry['work_duration'], 2); ?>h
                                                </span>
                                                <?php if ($entry['duration_category'] === 'overtime'): ?>
                                                <br><small class="text-danger">⚠️ Heures sup.</small>
                                                <?php elseif ($entry['duration_category'] === 'short'): ?>
                                                <br><small class="text-warning">⚠️ Journée courte</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($entry['break_duration'] > 0): ?>
                                                <span class="text-warning"><?php echo number_format($entry['break_duration'], 2); ?>h</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'active' => 'success',
                                                'break' => 'warning', 
                                                'completed' => 'secondary'
                                            ];
                                            $status_labels = [
                                                'active' => 'Actif',
                                                'break' => 'Pause',
                                                'completed' => 'Terminé'
                                            ];
                                            ?>
                                            <span class="status-badge bg-<?php echo $status_colors[$entry['status']] ?? 'secondary'; ?>">
                                                <?php echo $status_labels[$entry['status']] ?? $entry['status']; ?>
                                            </span>
                                            <?php if ($entry['admin_approved']): ?>
                                                <br><i class="fas fa-check-circle text-success mt-1" title="Approuvé"></i>
                                            <?php else: ?>
                                                <br><i class="fas fa-clock text-warning mt-1" title="En attente"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $efficiency = $entry['work_duration'] ? 
                                                min(($entry['work_duration'] / 8) * 100, 100) : 0;
                                            $efficiency_color = $efficiency >= 90 ? 'success' : 
                                                ($efficiency >= 70 ? 'warning' : 'danger');
                                            ?>
                                            <div class="d-flex align-items-center">
                                                <small class="me-2"><?php echo round($efficiency); ?>%</small>
                                                <div class="progress flex-grow-1" style="height: 4px;">
                                                    <div class="progress-bar bg-<?php echo $efficiency_color; ?>" 
                                                         style="width: <?php echo $efficiency; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="editEntry(<?php echo $entry['id']; ?>)" 
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!$entry['admin_approved']): ?>
                                                <button class="btn btn-outline-success" 
                                                        onclick="approveEntry(<?php echo $entry['id']; ?>)"
                                                        title="Approuver">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-info" 
                                                        onclick="viewEntryDetails(<?php echo $entry['id']; ?>)"
                                                        title="Détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary dropdown-toggle" 
                                                        data-bs-toggle="dropdown" title="Plus">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="duplicateEntry(<?php echo $entry['id']; ?>)">
                                                        <i class="fas fa-copy"></i> Dupliquer
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="addNote(<?php echo $entry['id']; ?>)">
                                                        <i class="fas fa-sticky-note"></i> Ajouter note
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteEntry(<?php echo $entry['id']; ?>)">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rapports -->
            <div class="tab-pane fade" id="reports" role="tabpanel">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="card stats-card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-file-alt"></i> Rapports Disponibles</h6>
                            </div>
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action" onclick="generateReport('daily')">
                                    <i class="fas fa-calendar-day text-primary"></i> Rapport Journalier
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" onclick="generateReport('weekly')">
                                    <i class="fas fa-calendar-week text-success"></i> Rapport Hebdomadaire
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" onclick="generateReport('monthly')">
                                    <i class="fas fa-calendar-alt text-info"></i> Rapport Mensuel
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" onclick="generateReport('custom')">
                                    <i class="fas fa-sliders-h text-warning"></i> Rapport Personnalisé
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" onclick="generateReport('overtime')">
                                    <i class="fas fa-exclamation-triangle text-danger"></i> Heures Supplémentaires
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-9">
                        <div class="chart-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-chart-area text-primary"></i> Analyse de Productivité</h5>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary active" onclick="switchChartPeriod('week')">7j</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="switchChartPeriod('month')">30j</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="switchChartPeriod('quarter')">3M</button>
                                </div>
                            </div>
                            <canvas id="productivityChart" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <h6><i class="fas fa-user-clock text-info"></i> Temps moyen par employé</h6>
                                    <canvas id="employeeAvgChart" width="200" height="200"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <h6><i class="fas fa-calendar-check text-success"></i> Présence par jour</h6>
                                    <canvas id="attendanceChart" width="200" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertes -->
            <div class="tab-pane fade" id="alerts" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="alert-panel">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle"></i> Alertes Actives 
                                    <span class="badge bg-dark ms-2"><?php echo count($alerts); ?></span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($alerts)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h4 class="text-success">Aucune alerte active</h4>
                                    <p class="text-muted">Tout semble fonctionner normalement</p>
                                </div>
                                <?php else: ?>
                                <?php foreach ($alerts as $alert): ?>
                                <div class="alert-item <?php echo $alert['type']; ?> p-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="<?php echo $alert['icon']; ?> fa-2x me-3"></i>
                                            <div>
                                                <h6 class="mb-1"><?php echo $alert['title']; ?></h6>
                                                <p class="mb-0"><?php echo $alert['message']; ?></p>
                                            </div>
                                        </div>
                                        <div>
                                            <?php if (isset($alert['action'])): ?>
                                            <button class="btn btn-sm btn-outline-dark" 
                                                    onclick="handleAlert('<?php echo $alert['action']; ?>', <?php echo $alert['user_id'] ?? 'null'; ?>)">
                                                Résoudre
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="dismissAlert(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card stats-card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-cog"></i> Paramètres d'Alertes</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="overtimeAlerts" checked>
                                    <label class="form-check-label" for="overtimeAlerts">
                                        Alertes heures supplémentaires
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="lateArrivalAlerts" checked>
                                    <label class="form-check-label" for="lateArrivalAlerts">
                                        Alertes retards
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="longBreakAlerts">
                                    <label class="form-check-label" for="longBreakAlerts">
                                        Alertes pauses prolongées
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="missingClockOutAlerts" checked>
                                    <label class="form-check-label" for="missingClockOutAlerts">
                                        Alertes pointages manquants
                                    </label>
                                </div>
                                
                                <hr>
                                
                                <h6>Seuils d'Alerte</h6>
                                <div class="mb-3">
                                    <label class="form-label small">Heures sup. (heures)</label>
                                    <input type="number" class="form-control form-control-sm" value="8" min="1" max="12">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Pause max (minutes)</label>
                                    <input type="number" class="form-control form-control-sm" value="60" min="15" max="180">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Retard toléré (minutes)</label>
                                    <input type="number" class="form-control form-control-sm" value="15" min="0" max="60">
                                </div>
                                
                                <button class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-save"></i> Sauvegarder
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container pour notifications -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Modals -->
    
    <!-- Modal d'édition d'entrée -->
    <div class="modal fade" id="editEntryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier l'entrée de pointage</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editEntryForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_entry">
                        <input type="hidden" name="entry_id" id="edit_entry_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_clock_in" class="form-label">Heure d'arrivée</label>
                                    <input type="datetime-local" class="form-control" name="edit_clock_in" id="edit_clock_in" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_clock_out" class="form-label">Heure de sortie (optionnel)</label>
                                    <input type="datetime-local" class="form-control" name="edit_clock_out" id="edit_clock_out">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_notes" class="form-label">Notes</label>
                            <textarea class="form-control" name="edit_notes" id="edit_notes" rows="3" 
                                     placeholder="Ajouter des notes sur cette entrée..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Les modifications seront enregistrées avec un horodatage administrateur.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de notification -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bell"></i> Envoyer une notification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="notificationForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send_notification">
                        <input type="hidden" name="user_id" id="notification_user_id">
                        
                        <div class="mb-3">
                            <label for="notification_message" class="form-label">Message</label>
                            <textarea class="form-control" name="message" id="notification_message" rows="4" 
                                     placeholder="Tapez votre message..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Messages prédéfinis</label>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                        onclick="setNotificationMessage('Merci de pointer votre sortie.')">
                                    Rappel pointage sortie
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" 
                                        onclick="setNotificationMessage('Votre pause dépasse la durée recommandée.')">
                                    Pause prolongée
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" 
                                        onclick="setNotificationMessage('Merci de venir me voir quand vous avez un moment.')">
                                    Demande d'entretien
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal des paramètres -->
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog"></i> Paramètres du Dashboard</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Actualisation</h6>
                            <div class="mb-3">
                                <label class="form-label">Intervalle (secondes)</label>
                                <select class="form-select" id="refreshInterval">
                                    <option value="30">30 secondes</option>
                                    <option value="60" selected>1 minute</option>
                                    <option value="120">2 minutes</option>
                                    <option value="300">5 minutes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Affichage</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="showAvatars" checked>
                                <label class="form-check-label" for="showAvatars">Afficher les avatars</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="showEfficiency" checked>
                                <label class="form-check-label" for="showEfficiency">Barres d'efficacité</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="enableAnimations" checked>
                                <label class="form-check-label" for="enableAnimations">Animations</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.js"></script>
    <script src="assets/js/time_tracking.js"></script>
    
    <script>
        // Variables globales
        let refreshInterval;
        let charts = {};
        
        // Données pour les graphiques (depuis PHP)
        const chartData = <?php echo json_encode($chart_data); ?>;
        const activeUsers = <?php echo json_encode($active_users); ?>;
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            startAutoRefresh();
            bindEvents();
            updateLastUpdate();
        });
        
        // Initialisation des graphiques
        function initializeCharts() {
            // Graphique hebdomadaire
            const weeklyCtx = document.getElementById('weeklyChart');
            if (weeklyCtx) {
                charts.weekly = new Chart(weeklyCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(d => d.display_date),
                        datasets: [{
                            label: 'Heures travaillées',
                            data: chartData.map(d => d.hours),
                            borderColor: 'rgb(0, 102, 204)',
                            backgroundColor: 'rgba(0, 102, 204, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Employés actifs',
                            data: chartData.map(d => d.employees),
                            borderColor: 'rgb(40, 167, 69)',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Heures' }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: { display: true, text: 'Employés' },
                                grid: { drawOnChartArea: false }
                            }
                        },
                        plugins: {
                            legend: { display: true }
                        }
                    }
                });
            }
            
            // Graphique en donut pour les équipes
            const teamCtx = document.getElementById('teamChart');
            if (teamCtx) {
                charts.team = new Chart(teamCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Actifs', 'En pause', 'Hors ligne'],
                        datasets: [{
                            data: [
                                <?php echo $stats['currently_working']; ?>,
                                <?php echo $stats['on_break']; ?>,
                                <?php echo max(0, $stats['active_employees'] - $stats['currently_working'] - $stats['on_break']); ?>
                            ],
                            backgroundColor: [
                                'rgb(40, 167, 69)',
                                'rgb(255, 193, 7)',
                                'rgb(108, 117, 125)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        }
        
        // Auto-actualisation
        function startAutoRefresh() {
            const interval = parseInt(document.getElementById('refreshInterval')?.value || 60) * 1000;
            refreshInterval = setInterval(() => {
                if (document.querySelector('#live-tab').classList.contains('active')) {
                    refreshActiveUsers();
                }
                updateLastUpdate();
            }, interval);
        }
        
        function refreshActiveUsers() {
            fetch('admin_timetracking_improved.php?ajax=get_active_users')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateActiveUsersDisplay(data.users);
                    }
                })
                .catch(error => console.error('Erreur refresh:', error));
        }
        
        function updateActiveUsersDisplay(users) {
            // Mettre à jour l'affichage des utilisateurs actifs
            users.forEach(user => {
                const durationElement = document.getElementById(`duration-${user.user_id}`);
                if (durationElement) {
                    durationElement.textContent = user.formatted_duration;
                }
            });
        }
        
        function updateLastUpdate() {
            const element = document.getElementById('lastUpdate');
            if (element) {
                element.textContent = new Date().toLocaleTimeString();
            }
        }
        
        // Event listeners
        function bindEvents() {
            // Checkbox "Tout sélectionner"
            document.getElementById('selectAll')?.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.entry-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
            
            // Formulaire d'édition
            document.getElementById('editEntryForm')?.addEventListener('submit', handleEditSubmit);
            
            // Formulaire de notification
            document.getElementById('notificationForm')?.addEventListener('submit', handleNotificationSubmit);
        }
        
        // Gestion des actions
        async function forceClockOut(userId, userName) {
            if (!confirm(`Forcer le pointage de sortie de ${userName} ?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'force_clock_out');
                formData.append('user_id', userId);
                
                const response = await fetch('admin_timetracking_improved.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Erreur de connexion', 'error');
            }
        }
        
        async function approveEntry(entryId) {
            try {
                const formData = new FormData();
                formData.append('action', 'approve_entry');
                formData.append('entry_id', entryId);
                
                const response = await fetch('admin_timetracking_improved.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    // Mettre à jour visuellement la ligne
                    const row = document.querySelector(`tr[data-entry-id="${entryId}"]`);
                    if (row) {
                        const statusCell = row.cells[6];
                        statusCell.innerHTML += '<br><i class="fas fa-check-circle text-success mt-1" title="Approuvé"></i>';
                        row.querySelector('.btn-outline-success')?.remove();
                    }
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Erreur de connexion', 'error');
            }
        }
        
        function editEntry(entryId) {
            // Charger les données de l'entrée (simulation)
            document.getElementById('edit_entry_id').value = entryId;
            new bootstrap.Modal(document.getElementById('editEntryModal')).show();
        }
        
        function sendNotification(userId) {
            document.getElementById('notification_user_id').value = userId;
            document.getElementById('notification_message').value = '';
            new bootstrap.Modal(document.getElementById('notificationModal')).show();
        }
        
        function setNotificationMessage(message) {
            document.getElementById('notification_message').value = message;
        }
        
        // Gestion des formulaires
        async function handleEditSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('admin_timetracking_improved.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editEntryModal')).hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Erreur de connexion', 'error');
            }
        }
        
        async function handleNotificationSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('admin_timetracking_improved.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('notificationModal')).hide();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Erreur de connexion', 'error');
            }
        }
        
        // Utilitaires
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div class="toast" id="${toastId}" role="alert">
                    <div class="toast-header bg-${type === 'error' ? 'danger' : type} text-white">
                        <strong class="me-auto">
                            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
                            ${type === 'success' ? 'Succès' : type === 'error' ? 'Erreur' : 'Information'}
                        </strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Auto-supprimer après fermeture
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }
        
        function refreshDashboard() {
            location.reload();
        }
        
        function exportData() {
            window.open('admin_timetracking_improved.php?export=csv&date=<?php echo $filter_date; ?>', '_blank');
        }
        
        function saveSettings() {
            // Implémentation sauvegarde paramètres
            showToast('Paramètres sauvegardés', 'success');
            bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
        }
        
        // Fonctions avancées
        function generateReport(type) {
            showToast(`Génération du rapport ${type} en cours...`, 'info');
            // Implémentation génération rapport
        }
        
        function approveAll() {
            const checkboxes = document.querySelectorAll('.entry-checkbox:checked');
            if (checkboxes.length === 0) {
                showToast('Aucune entrée sélectionnée', 'warning');
                return;
            }
            
            if (confirm(`Approuver ${checkboxes.length} entrée(s) ?`)) {
                checkboxes.forEach(cb => {
                    approveEntry(cb.value);
                });
            }
        }
        
        function handleAlert(action, userId) {
            switch(action) {
                case 'force_clock_out':
                    if (userId) {
                        forceClockOut(userId, 'cet employé');
                    }
                    break;
                case 'view_pending':
                    document.querySelector('#history-tab').click();
                    break;
            }
        }
        
        function dismissAlert(button) {
            button.closest('.alert-item').style.display = 'none';
        }
        
        // Autres fonctions utilitaires
        function viewUserDetails(userId) {
            window.open(`user_timetracking_details.php?user_id=${userId}`, '_blank');
        }
        
        function viewEntryDetails(entryId) {
            // Afficher modal avec détails complets
            showToast('Fonctionnalité à implémenter', 'info');
        }
        
        function exportFilteredData() {
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'csv');
            window.open('?' + params.toString(), '_blank');
        }
        
        // Gestion responsive des graphiques
        window.addEventListener('resize', function() {
            Object.values(charts).forEach(chart => {
                if (chart) chart.resize();
            });
        });
    </script>
</body>
</html>
