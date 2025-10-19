<?php
/**
 * Interface administrateur améliorée pour le système de pointage
 * Version intégrée pour le système GeekBoard existant
 * Inspirée des meilleures pratiques des logiciels professionnels
 */

// Ce fichier est inclus via le routage GeekBoard, donc les sessions et configs sont déjà chargées
// Pas besoin de session_start() ou d'includes supplémentaires

// Vérifier les droits admin (utiliser la logique GeekBoard existante)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo '<div class="alert alert-danger">Accès réservé aux administrateurs</div>';
    return;
}

$current_user_id = $_SESSION['user_id'];

// Utiliser la connexion DB existante de GeekBoard
global $pdo;
$shop_pdo = $pdo; // Utiliser la connexion existante

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
$view_mode = $_GET['view'] ?? 'dashboard';

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
    SELECT u.full_name, tt.clock_in, tt.user_id,
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
        'user_id' => $alert['user_id']
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

<!-- CSS moderne intégré -->
<link href="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.css" rel="stylesheet">
<link href="assets/css/admin_timetracking_modern.css" rel="stylesheet">

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

        <!-- Autres onglets similaires... -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Onglet Historique - En cours de développement
            </div>
        </div>

        <div class="tab-pane fade" id="reports" role="tabpanel">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Onglet Rapports - En cours de développement
            </div>
        </div>

        <div class="tab-pane fade" id="alerts" role="tabpanel">
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
    </div>
</div>

<!-- Toast Container pour notifications -->
<div class="toast-container" id="toastContainer"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.js"></script>
<script src="assets/js/admin_timetracking_advanced.js"></script>

<script>
// Données pour les graphiques (depuis PHP)
const chartData = <?php echo json_encode($chart_data); ?>;
const activeUsers = <?php echo json_encode($active_users); ?>;

// Initialisation simple des graphiques
document.addEventListener('DOMContentLoaded', function() {
    initSimpleCharts();
});

function initSimpleCharts() {
    // Graphique hebdomadaire
    const weeklyCtx = document.getElementById('weeklyChart');
    if (weeklyCtx) {
        new Chart(weeklyCtx, {
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
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Graphique en donut
    const teamCtx = document.getElementById('teamChart');
    if (teamCtx) {
        new Chart(teamCtx, {
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
                responsive: true
            }
        });
    }
}

// Fonctions globales simples
function refreshDashboard() {
    location.reload();
}

function exportData() {
    window.open('?page=admin_timetracking&export=csv', '_blank');
}

function forceClockOut(userId, userName) {
    if (confirm(`Forcer le pointage de sortie de ${userName} ?`)) {
        const formData = new FormData();
        formData.append('action', 'force_clock_out');
        formData.append('user_id', userId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
        });
    }
}

function sendNotification(userId) {
    const message = prompt('Message à envoyer:');
    if (message) {
        const formData = new FormData();
        formData.append('action', 'send_notification');
        formData.append('user_id', userId);
        formData.append('message', message);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        });
    }
}

function viewUserDetails(userId) {
    alert('Détails utilisateur - Fonctionnalité à implémenter');
}

function handleAlert(action, userId) {
    if (action === 'force_clock_out' && userId) {
        forceClockOut(userId, 'cet employé');
    }
}

function dismissAlert(button) {
    button.closest('.alert-item').style.display = 'none';
}
</script>
