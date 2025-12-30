<?php
// ajax/planning_handler.php
// Gestionnaire AJAX dédié pour le planning pour éviter les conflits d'headers HTML

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// Inclure la configuration nécessaire
// Active logs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/planning_error.log');

if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
} else {
    error_log("Config file missing");
    echo json_encode(['success' => false, 'message' => 'Config file missing']);
    exit;
}

if (file_exists('../config/database.php')) {
    require_once '../config/database.php';
} else {
    // Try alternate location
    if (file_exists('../includes/db.php')) {
         require_once '../includes/db.php';
    }
}

if (file_exists('../includes/functions.php')) {
    require_once '../includes/functions.php';
} elseif (file_exists('../functions.php')) {
    require_once '../functions.php';
} else {
    error_log("Functions file missing");
    echo json_encode(['success' => false, 'message' => 'Functions file missing']);
    exit;
}

// Initialiser la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => 'Erreur inconnue'];

try {
    $shop_pdo = getShopDBConnection();

    // Auto-fix DB structure if needed
    try {
        $check_col = $shop_pdo->query("SHOW COLUMNS FROM employee_schedules LIKE 'schedule_date'");
        if (!$check_col || $check_col->rowCount() == 0) {
            
            // Disable FK checks to allow drop/rename
            $shop_pdo->exec("SET FOREIGN_KEY_CHECKS=0");

            $check_table = $shop_pdo->query("SHOW TABLES LIKE 'employee_schedules'");
            if ($check_table && $check_table->rowCount() > 0) {
                try {
                    $shop_pdo->exec("RENAME TABLE employee_schedules TO employee_schedules_old_" . time());
                } catch (Exception $e) {
                    // Rename failed, force DROP
                    $shop_pdo->exec("DROP TABLE IF EXISTS employee_schedules");
                }
            }
            
            $shop_pdo->exec("CREATE TABLE IF NOT EXISTS employee_schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                schedule_date DATE NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                break_start TIME NULL,
                break_end TIME NULL,
                schedule_type VARCHAR(50) DEFAULT 'work',
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, schedule_date),
                INDEX idx_date (schedule_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $shop_pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        }
    } catch (Exception $e) { error_log("Planning DB Fix Error: " . $e->getMessage()); }
    
    // Vérifier l'action
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_schedules':
            $week_start = $_POST['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
            $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
            $user_filter = $_POST['user_id'] ?? null;
            
            error_log("DEBUG PLANNING GET: Recu week_start=$week_start (Calc End=$week_end), UserFilter=$user_filter");

            $query = "SELECT es.*, u.full_name, u.username 
                      FROM employee_schedules es 
                      LEFT JOIN users u ON es.user_id = u.id 
                      WHERE es.schedule_date BETWEEN ? AND ?";
            $params = [$week_start, $week_end];
            
            if ($user_filter) {
                $query .= " AND es.user_id = ?";
                $params[] = $user_filter;
            }
            $query .= " ORDER BY u.full_name, es.schedule_date, es.start_time";
            
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute($params);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DEBUG PLANNING GET: Trouve " . count($schedules) . " resultats");
            
            $response = ['success' => true, 'schedules' => $schedules, 'week_start' => $week_start, 'week_end' => $week_end];
            break;
            
        case 'save_schedule':
            $schedule_id = intval($_POST['schedule_id'] ?? 0);
            $user_id = intval($_POST['user_id'] ?? 0);
            $schedule_date = $_POST['schedule_date'] ?? '';
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';
            $break_start = $_POST['break_start'] ?: null;
            $break_end = $_POST['break_end'] ?: null;
            $schedule_type = $_POST['schedule_type'] ?? 'work';
            $notes = $_POST['notes'] ?? '';
            
            if ($user_id <= 0 || !$schedule_date || !$start_time || !$end_time) {
                $response = ['success' => false, 'message' => 'Données incomplètes'];
                break;
            }
            
            if ($schedule_id > 0) {
                // Update
                $stmt = $shop_pdo->prepare("UPDATE employee_schedules SET 
                    user_id = ?, schedule_date = ?, start_time = ?, end_time = ?,
                    break_start = ?, break_end = ?, schedule_type = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([$user_id, $schedule_date, $start_time, $end_time, $break_start, $break_end, $schedule_type, $notes, $schedule_id]);
                $response = ['success' => true, 'message' => 'Planning modifié'];
            } else {
                // Insert
                $stmt = $shop_pdo->prepare("INSERT INTO employee_schedules 
                    (user_id, schedule_date, start_time, end_time, break_start, break_end, schedule_type, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $schedule_date, $start_time, $end_time, $break_start, $break_end, $schedule_type, $notes, $_SESSION['user_id'] ?? null]);
                $response = ['success' => true, 'message' => 'Planning créé', 'id' => $shop_pdo->lastInsertId()];
            }
            break;
            
        case 'delete_schedule':
            $schedule_id = intval($_POST['schedule_id'] ?? 0);
            if ($schedule_id > 0) {
                $stmt = $shop_pdo->prepare("DELETE FROM employee_schedules WHERE id = ?");
                $stmt->execute([$schedule_id]);
                $response = ['success' => true, 'message' => 'Planning supprimé'];
            } else {
                $response = ['success' => false, 'message' => 'ID invalide'];
            }
            break;
            
        case 'copy_week':
            $source_week = $_POST['source_week'] ?? '';
            $target_week = $_POST['target_week'] ?? '';
            
            if (!$source_week || !$target_week) {
                $response = ['success' => false, 'message' => 'Semaines non spécifiées'];
                break;
            }
            
            $source_end = date('Y-m-d', strtotime($source_week . ' +6 days'));
            $days_diff = (strtotime($target_week) - strtotime($source_week)) / 86400;
            
            $stmt = $shop_pdo->prepare("SELECT * FROM employee_schedules WHERE schedule_date BETWEEN ? AND ?");
            $stmt->execute([$source_week, $source_end]);
            $source_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $copied = 0;
            foreach ($source_schedules as $schedule) {
                $new_date = date('Y-m-d', strtotime($schedule['schedule_date'] . " +{$days_diff} days"));
                $stmt = $shop_pdo->prepare("INSERT INTO employee_schedules 
                    (user_id, schedule_date, start_time, end_time, break_start, break_end, schedule_type, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $schedule['user_id'], $new_date, $schedule['start_time'], $schedule['end_time'],
                    $schedule['break_start'], $schedule['break_end'], $schedule['schedule_type'],
                    $schedule['notes'], $_SESSION['user_id'] ?? null
                ]);
                $copied++;
            }
            
            $response = ['success' => true, 'message' => "$copied créneaux copiés"];
            break;

        case 'save_weekly_schedule':
            // Support multi-week selection
            $week_starts = [];
            if (isset($_POST['week_starts'])) {
                $decoded = json_decode($_POST['week_starts'], true);
                if (is_array($decoded)) $week_starts = $decoded;
            }
            // Fallback single week (legacy)
            if (empty($week_starts) && !empty($_POST['week_start'])) {
                $week_starts[] = $_POST['week_start'];
            }
            
            $selected_days = json_decode($_POST['days'] ?? '[]', true); 
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';
            $break_start = $_POST['break_start'] ?: null;
            $break_end = $_POST['break_end'] ?: null;
            $schedule_type = $_POST['schedule_type'] ?? 'work';
            $notes = $_POST['notes'] ?? '';

            // Gestion multi-utilisateurs
            $user_ids = [];
            if (isset($_POST['user_ids'])) {
                $decoded = json_decode($_POST['user_ids'], true);
                if (is_array($decoded)) $user_ids = $decoded;
            }
            // Fallback single user
            if (empty($user_ids) && !empty($_POST['user_id'])) {
                $user_ids[] = intval($_POST['user_id']);
            }

            if (empty($user_ids) || empty($week_starts) || empty($selected_days) || !$start_time || !$end_time) {
                $response = ['success' => false, 'message' => 'Données incomplètes (Sélectionnez employés, semaines et jours)'];
                break;
            }

            $count = 0;
            
            // Loop through each week
            foreach ($week_starts as $week_start) {
                $week_start_timestamp = strtotime($week_start);
                if (!$week_start_timestamp) continue;
                
                foreach ($user_ids as $uid) {
                    $uid = intval($uid);
                    if ($uid <= 0) continue;

                    foreach ($selected_days as $day_index) {
                        $current_date = date('Y-m-d', strtotime("+$day_index days", $week_start_timestamp));

                        // Delete existing
                        $stmt = $shop_pdo->prepare("DELETE FROM employee_schedules WHERE user_id = ? AND schedule_date = ?");
                        $stmt->execute([$uid, $current_date]);

                        // Insert new
                        $stmt = $shop_pdo->prepare("INSERT INTO employee_schedules 
                            (user_id, schedule_date, start_time, end_time, break_start, break_end, schedule_type, notes, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $uid, $current_date, $start_time, $end_time, 
                            $break_start, $break_end, $schedule_type, $notes, 
                            $_SESSION['user_id'] ?? null
                        ]);
                        $count++;
                    }
                }
            }

            $response = ['success' => true, 'message' => "$count créneaux créés/modifiés"];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Action non reconnue'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
}

echo json_encode($response);
exit;
?>
