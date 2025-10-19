<?php
/**
 * Interface administrateur pour le système de pointage
 * Permet de voir et gérer les pointages des employés
 */

// Inclusion des fichiers de configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Session pour les fonctionnalités de base
session_start();

$current_user_id = $_SESSION['user_id'] ?? 1; // Valeur par défaut si non connecté
$shop_pdo = getShopDBConnection();

// Traitement des actions admin
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
            
            // Calculer les nouvelles durées si clock_out est défini
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
$filter_period = $_GET['period'] ?? 'day';
$search_query = $_GET['search'] ?? '';

// Construire les conditions de date selon la période
$date_condition = "DATE(tt.clock_in) = ?";
$date_params = [$filter_date];

switch ($filter_period) {
    case 'week':
        $date_condition = "YEARWEEK(tt.clock_in, 1) = YEARWEEK(?, 1)";
        break;
    case 'month':
        $date_condition = "YEAR(tt.clock_in) = YEAR(?) AND MONTH(tt.clock_in) = MONTH(?)";
        $date_params = [$filter_date, $filter_date];
        break;
    case 'day':
    default:
        // Déjà défini ci-dessus
        break;
}

// Utilisateurs actuellement au travail (pointage d'entrée sans sortie)
$stmt = $shop_pdo->prepare("
    SELECT tt.*, u.full_name, u.username, u.role,
           TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) / 60.0 as current_duration,
           TIME_FORMAT(TIMEDIFF(NOW(), tt.clock_in), '%H:%i') as formatted_duration
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE tt.status = 'active' AND tt.clock_out IS NULL
    ORDER BY tt.clock_in ASC
");
$stmt->execute();
$active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Utilisateurs en pause déjeuner (ont terminé le matin selon leurs créneaux, pas encore repris l'après-midi)
$stmt = $shop_pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.username, u.role,
           tt_morning.clock_out as morning_end,
           TIME_FORMAT(TIMEDIFF(NOW(), tt_morning.clock_out), '%H:%i') as break_duration,
           COALESCE(ts_user.end_time, ts_global.end_time) as morning_slot_end,
           COALESCE(ts_user_afternoon.start_time, ts_global_afternoon.start_time) as afternoon_slot_start
    FROM users u
    JOIN time_tracking tt_morning ON u.id = tt_morning.user_id
    
    -- Créneau matin spécifique utilisateur ou global
    LEFT JOIN time_slots ts_user ON ts_user.user_id = u.id AND ts_user.slot_type = 'morning' AND ts_user.is_active = TRUE
    LEFT JOIN time_slots ts_global ON ts_global.user_id IS NULL AND ts_global.slot_type = 'morning' AND ts_global.is_active = TRUE
    
    -- Créneau après-midi spécifique utilisateur ou global  
    LEFT JOIN time_slots ts_user_afternoon ON ts_user_afternoon.user_id = u.id AND ts_user_afternoon.slot_type = 'afternoon' AND ts_user_afternoon.is_active = TRUE
    LEFT JOIN time_slots ts_global_afternoon ON ts_global_afternoon.user_id IS NULL AND ts_global_afternoon.slot_type = 'afternoon' AND ts_global_afternoon.is_active = TRUE
    
    WHERE DATE(tt_morning.clock_in) = CURDATE()
      AND tt_morning.status = 'completed'
      AND tt_morning.clock_out IS NOT NULL
      -- Vérifier que le pointage de sortie est exactement dans les créneaux (aucune tolérance)
      AND TIME(tt_morning.clock_out) >= COALESCE(ts_user.end_time, ts_global.end_time)
      AND TIME(tt_morning.clock_out) < COALESCE(ts_user_afternoon.start_time, ts_global_afternoon.start_time)
      AND NOT EXISTS (
          SELECT 1 FROM time_tracking tt_afternoon 
          WHERE tt_afternoon.user_id = u.id 
            AND DATE(tt_afternoon.clock_in) = CURDATE()
            AND tt_afternoon.clock_in > tt_morning.clock_out
            AND tt_afternoon.status = 'active'
            AND tt_afternoon.clock_out IS NULL
      )
    ORDER BY tt_morning.clock_out ASC
");
$stmt->execute();
$break_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Entrées selon les filtres sélectionnés
$where_conditions = [$date_condition];
$params = $date_params;

if ($filter_user) {
    $where_conditions[] = "tt.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_status) {
    $where_conditions[] = "tt.status = ?";
    $params[] = $filter_status;
}

if ($search_query) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.username LIKE ? OR tt.notes LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$stmt = $shop_pdo->prepare("
    SELECT tt.*, u.full_name, u.username, u.role
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE " . implode(' AND ', $where_conditions) . "
    ORDER BY tt.clock_in DESC
");
$stmt->execute($params);
$daily_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Liste des utilisateurs pour le filtre
$stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users ORDER BY full_name");
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques rapides selon les filtres
$stats_where_conditions = [$date_condition];
$stats_params = $date_params;

if ($filter_user) {
    $stats_where_conditions[] = "user_id = ?";
    $stats_params[] = $filter_user;
}

$stmt = $shop_pdo->prepare("
    SELECT 
        COUNT(*) as total_sessions,
        COUNT(DISTINCT user_id) as active_employees,
        SUM(CASE WHEN status = 'active' AND clock_out IS NULL THEN 1 ELSE 0 END) as currently_working,
        AVG(work_duration) as avg_work_hours
    FROM time_tracking
    WHERE " . implode(' AND ', $stats_where_conditions) . "
");
$stmt->execute($stats_params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Ajouter le nombre d'utilisateurs en pause déjeuner
$stats['on_break'] = count($break_users);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Système de Pointage | GeekBoard</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 600;
        }

        .header .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(45deg, #56ab2f, #a8e6cf);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(45deg, #f093fb, #f5576c);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stats-card .label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .active-users, .break-users {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .user-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 20px;
            border-left: 5px solid;
        }

        .user-card.active {
            border-left-color: #28a745;
        }

        .user-card.break {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        }

        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-control {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .modern-table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .modern-table th,
        .modern-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .modern-table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .modern-table tbody tr {
            transition: background-color 0.3s ease;
        }

        .modern-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .modern-table tbody tr:nth-child(even) {
            background-color: #fdfdfd;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-secondary { background: #6c757d; color: white; }

        .duration-display {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1em;
            color: #2c3e50;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .search-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
        }

        .period-selector {
            display: flex;
            gap: 5px;
        }

        .period-btn {
            padding: 8px 16px;
            border: 2px solid #667eea;
            background: transparent;
            color: #667eea;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .period-btn.active,
        .period-btn:hover {
            background: #667eea;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .period-selector {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-clock"></i> Administration - Pointage</h1>
                <p style="color: #7f8c8d; margin-top: 8px;">Gestion et supervision des pointages employés</p>
            </div>
            <div class="actions">
                <button class="btn btn-outline" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
                <button class="btn btn-success" onclick="exportReport()">
                    <i class="fas fa-download"></i> Exporter
                </button>
            </div>
        </div>
                
        <!-- Statistiques rapides -->
        <div class="stats-grid">
            <div class="stats-card">
                <i class="fas fa-users" style="color: #667eea;"></i>
                <h3 style="color: #667eea;"><?php echo $stats['currently_working']; ?></h3>
                <div class="label">En cours</div>
            </div>
            <div class="stats-card">
                <i class="fas fa-pause" style="color: #ffc107;"></i>
                <h3 style="color: #ffc107;"><?php echo $stats['on_break']; ?></h3>
                <div class="label">En pause</div>
            </div>
            <div class="stats-card">
                <i class="fas fa-calendar-day" style="color: #17a2b8;"></i>
                <h3 style="color: #17a2b8;"><?php echo $stats['total_sessions']; ?></h3>
                <div class="label">Sessions aujourd'hui</div>
            </div>
            <div class="stats-card">
                <i class="fas fa-user-check" style="color: #28a745;"></i>
                <h3 style="color: #28a745;"><?php echo $stats['active_employees']; ?></h3>
                <div class="label">Employés actifs</div>
            </div>
            <div class="stats-card">
                <i class="fas fa-clock" style="color: #6c757d;"></i>
                <h3 style="color: #6c757d;"><?php echo number_format($stats['avg_work_hours'], 1); ?>h</h3>
                <div class="label">Moyenne heures/jour</div>
            </div>
        </div>
                
        <!-- Utilisateurs actuellement au travail -->
        <?php if (!empty($active_users)): ?>
        <div class="active-users">
            <h3><i class="fas fa-users-clock" style="color: #28a745;"></i> Employés actuellement au travail</h3>
            <div class="user-grid">
                <?php foreach ($active_users as $user): ?>
                <div class="user-card active">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h6 style="margin-bottom: 5px; font-size: 1.1rem; color: #2c3e50;"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                            <small style="color: #7f8c8d;"><?php echo htmlspecialchars($user['username']); ?></small>
                        </div>
                        <span class="badge badge-success">
                            <i class="fas fa-circle"></i> Au travail
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <small style="color: #7f8c8d;">Pointé depuis:</small>
                        <div class="duration-display" style="color: #28a745;">
                            <?php echo $user['formatted_duration']; ?>
                        </div>
                        <small style="color: #7f8c8d;">
                            Début: <?php echo date('H:i', strtotime($user['clock_in'])); ?>
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button class="btn btn-danger btn-sm" 
                                onclick="forceClockOut(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                            <i class="fas fa-sign-out-alt"></i> Forcer sortie
                        </button>
                        <button class="btn btn-outline btn-sm" 
                                onclick="viewUserDetails(<?php echo $user['user_id']; ?>)">
                            <i class="fas fa-eye"></i> Détails
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
                
        <!-- Utilisateurs en pause déjeuner -->
        <?php if (!empty($break_users)): ?>
        <div class="break-users">
            <h3><i class="fas fa-utensils" style="color: #ffc107;"></i> Employés en pause déjeuner</h3>
            <div class="user-grid">
                <?php foreach ($break_users as $user): ?>
                <div class="user-card break">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h6 style="margin-bottom: 5px; font-size: 1.1rem; color: #2c3e50;"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                            <small style="color: #7f8c8d;"><?php echo htmlspecialchars($user['username']); ?></small>
                        </div>
                        <span class="badge badge-warning">
                            <i class="fas fa-utensils"></i> Pause déjeuner
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <small style="color: #7f8c8d;">En pause depuis:</small>
                        <div class="duration-display" style="color: #ffc107;">
                            <?php echo $user['break_duration']; ?>
                        </div>
                        <small style="color: #7f8c8d;">
                            Fin matin: <?php echo date('H:i', strtotime($user['morning_end'])); ?>
                            <br>Créneau: <?php echo date('H:i', strtotime($user['morning_slot_end'])); ?> → <?php echo date('H:i', strtotime($user['afternoon_slot_start'])); ?>
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button class="btn btn-success btn-sm" 
                                onclick="resumeWork(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                            <i class="fas fa-play"></i> Reprendre travail
                        </button>
                        <button class="btn btn-outline btn-sm" 
                                onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                            <i class="fas fa-eye"></i> Détails
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
                
        <!-- Filtres et recherche -->
        <div class="filter-section">
            <h4><i class="fas fa-filter"></i> Filtres et recherche</h4>
            
            <!-- Contrôles de recherche -->
            <div class="search-controls">
                <div class="search-input">
                    <input type="text" 
                           class="form-control" 
                           id="search" 
                           name="search" 
                           placeholder="Rechercher par nom, username ou notes..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="period-selector">
                    <button type="button" 
                            class="period-btn <?php echo $filter_period === 'day' ? 'active' : ''; ?>" 
                            onclick="setPeriod('day')">
                        Jour
                    </button>
                    <button type="button" 
                            class="period-btn <?php echo $filter_period === 'week' ? 'active' : ''; ?>" 
                            onclick="setPeriod('week')">
                        Semaine
                    </button>
                    <button type="button" 
                            class="period-btn <?php echo $filter_period === 'month' ? 'active' : ''; ?>" 
                            onclick="setPeriod('month')">
                        Mois
                    </button>
                </div>
            </div>
            
            <!-- Formulaire de filtres -->
            <form method="GET" id="filterForm">
                <input type="hidden" name="period" id="periodInput" value="<?php echo $filter_period; ?>">
                <input type="hidden" name="search" id="searchInput" value="<?php echo htmlspecialchars($search_query); ?>">
                
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="date">Date de référence</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
                    </div>
                    <div class="form-group">
                        <label for="user">Employé</label>
                        <select class="form-control" id="user" name="user">
                            <option value="">Tous les employés</option>
                            <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Actif</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                            <option value="break" <?php echo $filter_status === 'break' ? 'selected' : ''; ?>>En pause</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
                
        <!-- Tableau des entrées -->
        <div class="table-container">
            <h4 style="margin-bottom: 20px;">
                <i class="fas fa-table"></i> Entrées de pointage - 
                <?php 
                switch ($filter_period) {
                    case 'week':
                        echo 'Semaine du ' . date('d/m/Y', strtotime($filter_date));
                        break;
                    case 'month':
                        echo date('F Y', strtotime($filter_date));
                        break;
                    default:
                        echo date('d/m/Y', strtotime($filter_date));
                }
                ?>
            </h4>
            
            <div class="table-wrapper">
                <table class="modern-table" id="timeTrackingTable">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Date</th>
                            <th>Arrivée</th>
                            <th>Sortie</th>
                            <th>Temps travaillé</th>
                            <th>Pause</th>
                            <th>Statut</th>
                            <th>Localisation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daily_entries)): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-clock"></i>
                                <div style="margin-top: 10px;">Aucune entrée trouvée pour les critères sélectionnés</div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($daily_entries as $entry): ?>
                        <tr data-employee="<?php echo htmlspecialchars($entry['full_name']); ?>" 
                            data-username="<?php echo htmlspecialchars($entry['username']); ?>"
                            data-date="<?php echo date('Y-m-d', strtotime($entry['clock_in'])); ?>">
                            <td>
                                <div style="font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($entry['full_name']); ?></div>
                                <small style="color: #7f8c8d;"><?php echo htmlspecialchars($entry['username']); ?></small>
                            </td>
                            <td style="color: #7f8c8d;"><?php echo date('d/m/Y', strtotime($entry['clock_in'])); ?></td>
                            <td style="font-weight: 500;"><?php echo date('H:i', strtotime($entry['clock_in'])); ?></td>
                            <td style="font-weight: 500;">
                                <?php if ($entry['clock_out']): ?>
                                    <?php echo date('H:i', strtotime($entry['clock_out'])); ?>
                                <?php else: ?>
                                    <span style="color: #7f8c8d; font-style: italic;">En cours</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($entry['work_duration']): ?>
                                    <span class="duration-display"><?php echo number_format($entry['work_duration'], 2); ?>h</span>
                                <?php else: ?>
                                    <span style="color: #7f8c8d;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($entry['break_duration'] > 0): ?>
                                    <span style="color: #ffc107; font-weight: 500;"><?php echo number_format($entry['break_duration'], 2); ?>h</span>
                                <?php else: ?>
                                    <span style="color: #7f8c8d;">-</span>
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
                                <span class="badge badge-<?php echo $status_colors[$entry['status']] ?? 'secondary'; ?>">
                                    <?php echo $status_labels[$entry['status']] ?? $entry['status']; ?>
                                </span>
                                <?php if ($entry['admin_approved']): ?>
                                    <i class="fas fa-check-circle" style="color: #28a745; margin-left: 5px;" title="Approuvé par admin"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($entry['location']): ?>
                                    <button class="btn btn-outline btn-sm" onclick="showLocation('<?php echo $entry['location']; ?>')">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </button>
                                <?php else: ?>
                                    <span style="color: #7f8c8d;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="editEntry(<?php echo $entry['id']; ?>)"
                                            title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (!$entry['admin_approved']): ?>
                                    <button class="btn btn-success btn-sm" 
                                            onclick="approveEntry(<?php echo $entry['id']; ?>)"
                                            title="Approuver">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-outline btn-sm" 
                                            onclick="viewEntryDetails(<?php echo $entry['id']; ?>)"
                                            title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </button>
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
    
    <!-- Modals pour les actions -->
    <!-- Modal d'édition d'entrée -->
    <div class="modal" id="editEntryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'entrée de pointage</h5>
                <button type="button" class="close-btn" onclick="closeModal('editEntryModal')">&times;</button>
            </div>
            <form id="editEntryForm" method="POST">
                <div style="padding: 20px;">
                    <input type="hidden" name="action" value="edit_entry">
                    <input type="hidden" name="entry_id" id="edit_entry_id">
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="edit_clock_in">Heure d'arrivée</label>
                        <input type="datetime-local" class="form-control" name="edit_clock_in" id="edit_clock_in" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="edit_clock_out">Heure de sortie (optionnel)</label>
                        <input type="datetime-local" class="form-control" name="edit_clock_out" id="edit_clock_out">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="edit_notes">Notes</label>
                        <textarea class="form-control" name="edit_notes" id="edit_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editEntryModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="assets/js/time_tracking.js"></script>
    
    <script>
        // Auto-refresh toutes les 60 secondes
        setInterval(() => {
            location.reload();
        }, 60000);
        
        // Gestion des périodes de recherche
        function setPeriod(period) {
            document.getElementById('periodInput').value = period;
            
            // Mettre à jour l'affichage des boutons
            const buttons = document.querySelectorAll('.period-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Soumettre automatiquement
            document.getElementById('filterForm').submit();
        }
        
        // Gestion de la recherche en temps réel
        document.getElementById('search').addEventListener('input', function() {
            const searchValue = this.value;
            document.getElementById('searchInput').value = searchValue;
            
            // Filtrer le tableau en temps réel
            filterTable(searchValue);
        });
        
        // Fonction de filtrage du tableau
        function filterTable(searchTerm) {
            const table = document.getElementById('timeTrackingTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            if (!searchTerm) {
                // Afficher toutes les lignes si pas de recherche
                for (let row of rows) {
                    if (!row.classList.contains('empty-state')) {
                        row.style.display = '';
                    }
                }
                return;
            }
            
            const searchLower = searchTerm.toLowerCase();
            let visibleRows = 0;
            
            for (let row of rows) {
                if (row.classList.contains('empty-state')) continue;
                
                const employee = row.getAttribute('data-employee').toLowerCase();
                const username = row.getAttribute('data-username').toLowerCase();
                const cells = row.getElementsByTagName('td');
                let textContent = '';
                
                // Collecter le contenu textuel de toutes les cellules
                for (let cell of cells) {
                    textContent += cell.textContent.toLowerCase() + ' ';
                }
                
                if (employee.includes(searchLower) || 
                    username.includes(searchLower) || 
                    textContent.includes(searchLower)) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Afficher/masquer le message "aucun résultat"
            const emptyRow = table.querySelector('.empty-state');
            if (emptyRow && visibleRows === 0 && searchTerm) {
                // Créer un message de "pas de résultats" temporaire
                const noResultsRow = document.getElementById('noResultsRow');
                if (!noResultsRow) {
                    const newRow = document.createElement('tr');
                    newRow.id = 'noResultsRow';
                    newRow.innerHTML = '<td colspan="9" class="empty-state"><i class="fas fa-search"></i><div style="margin-top: 10px;">Aucun résultat trouvé pour "' + searchTerm + '"</div></td>';
                    table.getElementsByTagName('tbody')[0].appendChild(newRow);
                }
            } else {
                const noResultsRow = document.getElementById('noResultsRow');
                if (noResultsRow) {
                    noResultsRow.remove();
                }
            }
        }
        
        // Gestion des modals
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Fermer les modals en cliquant à l'extérieur
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        });
        
        // Fonctions pour les actions admin
        function forceClockOut(userId, userName) {
            if (confirm(`Êtes-vous sûr de vouloir forcer le pointage de sortie de ${userName} ?`)) {
                const formData = new FormData();
                formData.append('action', 'force_clock_out');
                formData.append('user_id', userId);
                
                fetch('admin_timetracking.php', {
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
        
        function approveEntry(entryId) {
            if (confirm('Approuver cette entrée de pointage ?')) {
                const formData = new FormData();
                formData.append('action', 'approve_entry');
                formData.append('entry_id', entryId);
                
                fetch('admin_timetracking.php', {
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
        
        function editEntry(entryId) {
            // Charger les données de l'entrée et ouvrir le modal
            document.getElementById('edit_entry_id').value = entryId;
            showModal('editEntryModal');
        }
        
        function showLocation(location) {
            const [lat, lng] = location.split(',');
            window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
        }
        
        function exportReport() {
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'csv');
            window.open('?' + params.toString());
        }
        
        function viewUserDetails(userId) {
            window.open(`presence_gestion.php?user_id=${userId}`, '_blank');
        }
        
        function viewEntryDetails(entryId) {
            alert('Fonctionnalité à implémenter: détails de l\'entrée ' + entryId);
        }
        
        function resumeWork(userId, userName) {
            if (confirm(`${userName} va reprendre le travail après sa pause déjeuner. Continuer ?`)) {
                alert('Fonctionnalité à implémenter: L\'utilisateur doit utiliser la page de pointage pour reprendre le travail.');
            }
        }
        
        // Gestion du formulaire d'édition
        document.getElementById('editEntryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('admin_timetracking.php', {
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
                    closeModal('editEntryModal');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
        });
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Appliquer le filtre initial si il y a une recherche en cours
            const searchValue = document.getElementById('search').value;
            if (searchValue) {
                filterTable(searchValue);
            }
        });
    </script>
</body>
</html>

