<?php
/**
 * Interface administrateur moderne pour le système de pointage
 * Version moderne basée sur inventaire_moderne.php
 */

// Page accessible sans vérification d'authentification pour test

// GESTION AJAX SÉPARÉE - AUCUN HTML NE SERA GÉNÉRÉ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Configuration stricte pour JSON uniquement
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 0);
    
    // Nettoyer TOUS les buffers possibles
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Démarrer un nouveau buffer propre
    ob_start();
    
    // Headers stricts
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Initialisation minimale
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__));
        }
        
        // S'assurer que les fonctions sont disponibles
        if (!function_exists('getShopDBConnection')) {
            // Charger la configuration de base de données et les fonctions nécessaires
            require_once BASE_PATH . '/config/database.php';
            require_once BASE_PATH . '/includes/functions.php';
        }
        
        // Initialiser la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $shop_pdo = getShopDBConnection();
    
    switch ($_POST['action']) {
            case 'save_global_slots':
                $morning_start = $_POST['morning_start'] ?? '';
                $morning_end = $_POST['morning_end'] ?? '';
                $afternoon_start = $_POST['afternoon_start'] ?? '';
                $afternoon_end = $_POST['afternoon_end'] ?? '';
                
                // Créer table si nécessaire
                $shop_pdo->exec("CREATE TABLE IF NOT EXISTS time_slots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    slot_type ENUM('morning', 'afternoon') NOT NULL,
                    start_time TIME NOT NULL,
                    end_time TIME NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
                
                // Supprimer anciens créneaux globaux
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id IS NULL");
                $stmt->execute();
                
                // Ajouter nouveaux créneaux
                if ($morning_start && $morning_end) {
                    $stmt = $shop_pdo->prepare("INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES (NULL, 'morning', ?, ?, TRUE)");
                    $stmt->execute([$morning_start, $morning_end]);
                }
                
                if ($afternoon_start && $afternoon_end) {
                    $stmt = $shop_pdo->prepare("INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES (NULL, 'afternoon', ?, ?, TRUE)");
                    $stmt->execute([$afternoon_start, $afternoon_end]);
                }
                
                $response = ['success' => true, 'message' => 'Créneaux globaux sauvegardés'];
            break;
            
            case 'save_user_slots':
                $user_id = intval($_POST['user_id'] ?? 0);
                $morning_start = $_POST['user_morning_start'] ?? '';
                $morning_end = $_POST['user_morning_end'] ?? '';
                $afternoon_start = $_POST['user_afternoon_start'] ?? '';
                $afternoon_end = $_POST['user_afternoon_end'] ?? '';
                
                if ($user_id > 0) {
                    // Supprimer anciens créneaux utilisateur
                    $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Ajouter nouveaux créneaux
                    if ($morning_start && $morning_end) {
                        $stmt = $shop_pdo->prepare("INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES (?, 'morning', ?, ?, TRUE)");
                        $stmt->execute([$user_id, $morning_start, $morning_end]);
                    }
                    
                    if ($afternoon_start && $afternoon_end) {
                        $stmt = $shop_pdo->prepare("INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES (?, 'afternoon', ?, ?, TRUE)");
                        $stmt->execute([$user_id, $afternoon_start, $afternoon_end]);
                    }
                    
                    $response = ['success' => true, 'message' => 'Créneaux utilisateur sauvegardés'];
            } else {
                    $response = ['success' => false, 'message' => 'ID utilisateur invalide'];
            }
            break;
            
            case 'remove_user_slots':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $response = ['success' => true, 'message' => 'Créneaux utilisateur supprimés'];
                } else {
                    $response = ['success' => false, 'message' => 'ID utilisateur invalide'];
                }
                break;
                
            case 'force_clock_out':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    $stmt = $shop_pdo->prepare("UPDATE time_tracking SET status = 'completed', clock_out = NOW(), admin_approved = 1 WHERE user_id = ? AND status IN ('active', 'break')");
                    $stmt->execute([$user_id]);
                    $response = ['success' => true, 'message' => 'Sortie forcée effectuée'];
            } else {
                    $response = ['success' => false, 'message' => 'ID utilisateur invalide'];
            }
            break;
            
            case 'approve_request':
                $request_id = intval($_POST['request_id'] ?? 0);
                if ($request_id > 0) {
                    $stmt = $shop_pdo->prepare("UPDATE time_tracking SET admin_approved = 1 WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $response = ['success' => true, 'message' => 'Demande approuvée'];
                } else {
                    $response = ['success' => false, 'message' => 'ID demande invalide'];
                }
                break;
                
            case 'reject_request':
                $request_id = intval($_POST['request_id'] ?? 0);
                if ($request_id > 0) {
                    $stmt = $shop_pdo->prepare("DELETE FROM time_tracking WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $response = ['success' => true, 'message' => 'Demande rejetée'];
                } else {
                    $response = ['success' => false, 'message' => 'ID demande invalide'];
            }
            break;

            case 'get_approval_details':
                $request_id = intval($_POST['request_id'] ?? 0);
                if ($request_id > 0) {
                    // 1. Récupérer les infos du pointage
                    $stmt = $shop_pdo->prepare("
                        SELECT tt.*, u.full_name, u.username 
                        FROM time_tracking tt 
                        JOIN users u ON tt.user_id = u.id 
                        WHERE tt.id = ?
                    ");
                    $stmt->execute([$request_id]);
                    $request = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($request) {
                        $date = date('Y-m-d', strtotime($request['clock_in']));
                        $clock_in_time = date('H:i:s', strtotime($request['clock_in']));

                        // 2. Chercher le planning pour ce jour
                        $stmt = $shop_pdo->prepare("
                            SELECT * FROM employee_schedules 
                            WHERE user_id = ? AND schedule_date = ?
                        ");
                        $stmt->execute([$request['user_id'], $date]);
                        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

                        $delay_minutes = 0;
                        $has_schedule = false;
                        $scheduled_start = '';

                        if ($schedule) {
                            $has_schedule = true;
                            $scheduled_start = $schedule['start_time'];
                            
                            // Calcul du retard (si arrivée après le début prévu + tolérance 5min)
                            $scheduled_seconds = strtotime($date . ' ' . $schedule['start_time']);
                            $actual_seconds = strtotime($request['clock_in']);
                            
                            if ($actual_seconds > ($scheduled_seconds + 300)) { // 5 minutes tolérance
                                $delay_minutes = round(($actual_seconds - $scheduled_seconds) / 60);
                            }
                        }

                        $response = [
                            'success' => true,
                            'request' => [
                                'id' => $request['id'],
                                'user_name' => $request['full_name'] ?: $request['username'],
                                'clock_in' => $clock_in_time,
                                'date' => $date
                            ],
                            'schedule' => [
                                'exists' => $has_schedule,
                                'start_time' => $scheduled_start,
                                'delay_minutes' => $delay_minutes
                            ]
                        ];
                    } else {
                        $response = ['success' => false, 'message' => 'Demande introuvable'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'ID demande invalide'];
                }
                break;

            case 'approve_with_lateness':
                $request_id = intval($_POST['request_id'] ?? 0);
                $create_event = filter_var($_POST['create_event'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
                $corrected_clock_in = $_POST['corrected_clock_in'] ?? '';

                if ($request_id > 0) {
                    try {
                        $shop_pdo->beginTransaction();

                        // 1. Mettre à jour l'heure si modifiée
                        if (!empty($corrected_clock_in)) {
                            $stmt = $shop_pdo->prepare("SELECT clock_in, date(clock_in) as date_only FROM time_tracking WHERE id = ?");
                            $stmt->execute([$request_id]);
                            $current = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($current) {
                                // Combiner la date originale avec la nouvelle heure
                                // Si l'heure est au format HH:MM, on ajoute les secondes :00
                                // Si l'heure est au format HH:MM:SS, on ne touche à rien
                                if (strlen($corrected_clock_in) === 5) {
                                     $corrected_clock_in .= ':00';
                                }
                                $new_clock_in_dt = $current['date_only'] . ' ' . $corrected_clock_in;
                                // Update clock_in first
                                $stmt = $shop_pdo->prepare("UPDATE time_tracking SET clock_in = ? WHERE id = ?");
                                $stmt->execute([$new_clock_in_dt, $request_id]);
                            }
                        }

                        // 2. Approuver le pointage
                        $stmt = $shop_pdo->prepare("UPDATE time_tracking SET admin_approved = 1 WHERE id = ?");
                        $stmt->execute([$request_id]);

                        // 2. Créer l'événement retard si demandé
                        if ($create_event && $duration_minutes > 0) {
                            // Récupérer l'ID utilisateur et la date
                            $stmt = $shop_pdo->prepare("SELECT user_id, clock_in FROM time_tracking WHERE id = ?");
                            $stmt->execute([$request_id]);
                            $tt_data = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($tt_data) {
                                // ID Type "Retard" = 1 (Vérifié en base)
                                $type_id = 1; 
                                $date_start = $tt_data['clock_in']; // Le retard commence à l'heure du pointage (ou théorique ?) - Simplification: date du pointage
                                
                                $sql = "INSERT INTO presence_events (employee_id, type_id, date_start, duration_minutes, comment, status, created_by, approved_by, created_at, updated_at) 
                                        VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, NOW(), NOW())";
                                
                                $admin_id = $_SESSION['user_id'] ?? null;
                                
                                $stmt = $shop_pdo->prepare($sql);
                                $stmt->execute([
                                    $tt_data['user_id'],
                                    $type_id,
                                    $date_start,
                                    $duration_minutes,
                                    $comment ?: "Retard validé lors du pointage",
                                    $admin_id,
                                    $admin_id
                                ]);
                                
                                $event_id = $shop_pdo->lastInsertId();
                                
                                // === ENVOI NOTIFICATION PUSH ===
                                try {
                                    require_once __DIR__ . '/../includes/NotificationService.php';
                                    
                                    // Récupérer le nom de l'utilisateur
                                    $stmt_user = $shop_pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
                                    $stmt_user->execute([$tt_data['user_id']]);
                                    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
                                    $user_name = $user_data['full_name'] ?: $user_data['username'] ?: 'Utilisateur inconnu';
                                    
                                    $title = "Retard enregistré";
                                    $body = "$user_name - $duration_minutes min";
                                    NotificationService::sendToAdmins('presence_event_created', $title, $body, [
                                        'url' => "/index.php?page=presence_gestion_moderne&user=" . $tt_data['user_id'],
                                        'related_id' => $event_id,
                                        'related_type' => 'presence_event'
                                    ]);
                                    error_log("NOTIFICATION: Delay event notification sent for event #$event_id");
                                } catch (Exception $e) {
                                    error_log("NOTIFICATION ERROR (delay_event): " . $e->getMessage());
                                }
                            }
                        }

                        $shop_pdo->commit();
                        $response = ['success' => true, 'message' => 'Pointage approuvé avec succès'];

                    } catch (Exception $e) {
                        $shop_pdo->rollBack();
                        $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'ID demande invalide'];
                }
                break;

            case 'manual_entry':
                $user_id = intval($_POST['user_id'] ?? 0);
                $date = $_POST['date'] ?? '';
                $start_time = $_POST['start_time'] ?? '';
                $end_time = $_POST['end_time'] ?? '';
                $break_start = $_POST['break_start'] ?? '';
                $break_end = $_POST['break_end'] ?? '';
                
                if ($user_id > 0 && $date && $start_time) {
                    try {
                        // Construire les datetimes
                        $clock_in = $date . ' ' . $start_time . ':00';
                        $break_start_dt = $break_start ? ($date . ' ' . $break_start . ':00') : null;
                        $break_end_dt = $break_end ? ($date . ' ' . $break_end . ':00') : null;
                        
                        $clock_out = null;
                        $status = 'active';
                        $total_hours = 0;
                        $work_duration = 0;
                        $break_duration = 0;
                        
                        if ($end_time) {
                            $clock_out = $date . ' ' . $end_time . ':00';
                            $status = 'completed';
                            
                            // Calculs de durée
                            $start_ts = strtotime($clock_in);
                            $end_ts = strtotime($clock_out);
                            
                            if ($end_ts <= $start_ts) {
                                 throw new Exception("L'heure de départ doit être après l'heure d'arrivée");
                            }
                            
                            $total_seconds = $end_ts - $start_ts;
                            $break_seconds = 0;
                            
                            if ($break_start_dt && $break_end_dt) {
                                $bs_ts = strtotime($break_start_dt);
                                $be_ts = strtotime($break_end_dt);
                                if ($be_ts > $bs_ts) {
                                    $break_seconds = $be_ts - $bs_ts;
                                }
                            }
                            
                            $work_seconds = $total_seconds - $break_seconds;
                            $total_hours = round($work_seconds / 3600, 2);
                            $work_duration = round($work_seconds / 3600, 2);
                            $break_duration = round($break_seconds / 3600, 2);
                        }
                        
                        // Insérer
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO time_tracking (
                                user_id, clock_in, clock_out, 
                                break_start, break_end, 
                                status, total_hours, 
                                work_duration, break_duration,
                                admin_approved
                            ) VALUES (
                                ?, ?, ?, 
                                ?, ?, 
                                ?, ?, 
                                ?, ?,
                                1
                            )
                        ");
                        
                        $stmt->execute([
                            $user_id, $clock_in, $clock_out,
                            $break_start_dt, $break_end_dt,
                            $status, $total_hours,
                            $work_duration, $break_duration
                        ]);
                        
                        $response = ['success' => true, 'message' => 'Pointage ajouté avec succès'];
                    } catch (Exception $e) {
                        $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Données incomplètes (Employé, Date et Heure d\'arrivée requises)'];
                }
                break;
            
            // ========== PLANNING ACTIONS ==========
            case 'get_schedules':
                $week_start = $_POST['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
                $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
                $user_filter = $_POST['user_id'] ?? null;
                
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
                
            default:
                $response = ['success' => false, 'message' => 'Action non reconnue'];
            break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage(), 'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]];
    } catch (Error $e) {
        $response = ['success' => false, 'message' => 'Erreur PHP: ' . $e->getMessage(), 'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]];
    }
    
    // Nettoyer le buffer et sortir JSON pur
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Include night mode system only for normal page loads (not AJAX)
include_once 'includes/night-mode-system.php';

// Récupération des données
$shop_pdo = getShopDBConnection();
$filter_date = $_GET['date'] ?? date('Y-m-d');

// Initialiser les variables avec des valeurs par défaut
$active_users = [];
$stats = [
    'total_sessions' => 0,
    'active_employees' => 0,
    'currently_working' => 0,
    'on_break' => 0,
    'avg_work_hours' => 0,
    'total_work_hours' => 0,
    'overtime_sessions' => 0,
    'pending_approvals' => 0
];
$alerts = [];
$chart_data = [];
$daily_entries = [];
$all_users = [];
$top_performers = [];
$calendar_data = [];
$pending_requests = [];
$global_slots = [];
$user_slots = [];

// Paramètres pour le calendrier
$calendar_month = $_GET['calendar_month'] ?? date('Y-m');
$calendar_user_filter = $_GET['calendar_user'] ?? '';

try {
    // Vérifier si la table time_tracking existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_tracking'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        $table_missing = true;
            } else {
        $table_missing = false;
        
        // Récupérer tous les utilisateurs pour les filtres
        $stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users ORDER BY full_name");
        $stmt->execute();
        $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
        // Statistiques avancées
                    $stmt = $shop_pdo->prepare("
    SELECT 
                COUNT(*) as total_sessions,
                COUNT(DISTINCT user_id) as active_employees,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
                SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break,
                COALESCE(AVG(work_duration), 0) as avg_work_hours,
                COALESCE(SUM(work_duration), 0) as total_work_hours,
                COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, clock_in, COALESCE(clock_out, NOW())) > 8 THEN 1 END) as overtime_sessions,
                COUNT(CASE WHEN admin_approved = 0 AND status = 'completed' THEN 1 END) as pending_approvals
    FROM time_tracking 
            WHERE DATE(clock_in) = ?
        ");
        $stmt->execute([$filter_date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats = $result;
        }

        // Utilisateurs actuellement actifs
        $stmt = $shop_pdo->prepare("
            SELECT tt.*, u.full_name, u.username, u.role,
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

// Demandes en attente d'approbation
        $stmt = $shop_pdo->prepare("
            SELECT tt.*, u.full_name, u.username,
                   DATE_FORMAT(tt.clock_in, '%d/%m/%Y %H:%i') as formatted_clock_in,
                   DATE_FORMAT(tt.clock_out, '%d/%m/%Y %H:%i') as formatted_clock_out,
                   TIMESTAMPDIFF(MINUTE, tt.clock_in, tt.clock_out) / 60.0 as total_hours
    FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE tt.admin_approved = 0 AND tt.status = 'completed'
            ORDER BY tt.clock_out DESC
            LIMIT 10
        ");
$stmt->execute();
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les créneaux globaux
        $stmt = $shop_pdo->prepare("
            SELECT slot_type, start_time, end_time 
            FROM time_slots 
            WHERE user_id IS NULL AND is_active = TRUE
        ");
        $stmt->execute();
        $global_slots_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($global_slots_raw as $slot) {
            $global_slots[$slot['slot_type']] = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time']
            ];
        }

        // Récupérer les créneaux spécifiques par utilisateur
        $stmt = $shop_pdo->prepare("
            SELECT ts.user_id, ts.slot_type, ts.start_time, ts.end_time, u.full_name 
            FROM time_slots ts
            JOIN users u ON ts.user_id = u.id
            WHERE ts.user_id IS NOT NULL AND ts.is_active = TRUE
            ORDER BY u.full_name, ts.slot_type
        ");
$stmt->execute();
        $user_slots_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($user_slots_raw as $slot) {
            if (!isset($user_slots[$slot['user_id']])) {
                $user_slots[$slot['user_id']] = [
                    'full_name' => $slot['full_name'],
                    'slots' => []
                ];
            }
            $user_slots[$slot['user_id']]['slots'][$slot['slot_type']] = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time']
            ];
        }

        // Top performers (7 derniers jours)
        $stmt = $shop_pdo->prepare("
            SELECT u.full_name, u.username,
                   COUNT(*) as sessions,
                   COALESCE(SUM(work_duration), 0) as total_hours,
                   COALESCE(AVG(work_duration), 0) as avg_hours
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE tt.status = 'completed' 
            AND DATE(tt.clock_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY tt.user_id, u.full_name, u.username
            HAVING total_hours > 0
            ORDER BY total_hours DESC
            LIMIT 5
        ");
$stmt->execute();
        $top_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Données graphique 7 derniers jours
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
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
                'employees' => $day_stats['employees'] ?? 0,
                'hours' => round($day_stats['total_hours'] ?? 0, 1),
                'sessions' => $day_stats['sessions'] ?? 0
            ];
        }
        
        // Données pour le calendrier - avec gestion des status NULL
        $calendar_query = "
            SELECT tt.*, 
                   u.full_name, u.username,
                   CASE 
                       WHEN tt.status = 'active' THEN 'active'
                       WHEN tt.status = 'break' THEN 'break'
                       WHEN tt.status = 'completed' AND tt.admin_approved = 1 THEN 'completed'
                       WHEN tt.status = 'completed' AND (tt.admin_approved = 0 OR tt.admin_approved IS NULL) THEN 'pending'
                       WHEN tt.clock_out IS NOT NULL THEN 'completed'
                       WHEN tt.clock_out IS NULL THEN 'active'
                       ELSE 'unknown'
                   END as display_status,
                   TIMESTAMPDIFF(MINUTE, tt.clock_in, COALESCE(tt.clock_out, NOW())) as total_minutes
            FROM time_tracking tt
            LEFT JOIN users u ON tt.user_id = u.id
            WHERE DATE_FORMAT(tt.clock_in, '%Y-%m') = ?
        ";

        $calendar_params = [$calendar_month];

        if ($calendar_user_filter) {
            $calendar_query .= " AND tt.user_id = ?";
            $calendar_params[] = $calendar_user_filter;
        }

        $calendar_query .= " ORDER BY tt.clock_in DESC";

        $stmt = $shop_pdo->prepare($calendar_query);
        $stmt->execute($calendar_params);
        $calendar_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error_message = "Erreur chargement données: " . $e->getMessage();
}
?>
    
    <style>
/* FIX NAVBAR - Obligatoire pour affichage correct */
/* Masquer dock mobile sur desktop */
        @media (min-width: 992px) {
            #mobile-dock, #dock-recall-zone {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
                z-index: -1 !important;
            }
    /* Forcer navbar desktop visible */
            #desktop-navbar, nav#desktop-navbar, .navbar, nav.navbar {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 10000 !important;
                height: 60px !important;
                width: 100% !important;
            }
    /* Surcharger navbar-servo-fix.css */
            body #desktop-navbar, html body #desktop-navbar {
                height: 60px !important;
                min-height: 60px !important;
                max-height: 60px !important;
            }
    /* Éléments navbar visibles */
            #desktop-navbar * {
                visibility: visible !important;
                opacity: 1 !important;
            }
    /* Container navbar avec centrage vertical parfait */
            #desktop-navbar .container-fluid {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                height: 100% !important;
        padding: 0.75rem 1rem !important; /* Augmenté à 0.75rem pour plus de centrage */
        min-height: 60px !important;
    }
    /* Logo avec centrage vertical parfait */
    #desktop-navbar .navbar-brand {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    #desktop-navbar .navbar-brand img {
        height: 32px !important; /* Encore réduit pour plus d'espace vertical */
        width: auto !important;
        vertical-align: middle !important;
    }
    /* Boutons avec centrage vertical parfait */
    #desktop-navbar .btn,
    #desktop-navbar .navbar-nav .nav-link,
    #desktop-navbar .dropdown-toggle {
        display: flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 0.375rem 0.75rem !important; /* Padding encore plus réduit */
        margin: 0.125rem 0.25rem !important; /* Marges ajustées */
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Correction spécifique pour les icônes dans les boutons */
    #desktop-navbar .btn i,
    #desktop-navbar .navbar-nav .nav-link i,
    #desktop-navbar .dropdown-toggle i {
        vertical-align: middle !important;
        line-height: 1 !important;
    }
    /* Messages de bienvenue centrés */
    #desktop-navbar .d-none.d-md-flex {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
    }
    /* Forcer l'alignement vertical pour tous les éléments flex */
    #desktop-navbar .d-flex {
        align-items: center !important;
    }
    /* Animation SERVO centrée parfaitement */
            body .servo-logo-container {
                position: absolute !important;
                left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
                z-index: 10001 !important;
        display: flex !important;
        align-items: center !important;
            }
    /* Réserver espace navbar */
            body {
                padding-top: 80px !important;
            }
        }

/* ========================================
   FIX NAVBAR & ANIMATION SERVO
   ======================================== */
@media (min-width: 992px) {
    /* Masquer le dock mobile sur desktop */
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    
    /* S'assurer que la navbar desktop est visible */
    #desktop-navbar, nav#desktop-navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1030 !important;
        width: 100% !important;
    }
    
    /* Container fluid de la navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.5rem 1rem !important;
        min-height: 60px !important;
    }
    
    /* Logo SERVO - CENTRÉ horizontalement ET verticalement */
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1031 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    /* S'assurer que le loader SERVO est visible */
    .servo-logo-container .loader {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Animations SVG pour toutes les lettres SERVO */
    .servo-logo-container .dash {
        animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
    }
    
    .servo-logo-container .spin {
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center;
    }
    
    /* Keyframes pour l'animation .dash (S, E, R, V) */
    @keyframes dashArray {
        0% { stroke-dasharray: 0 1 359 0; }
        50% { stroke-dasharray: 0 359 1 0; }
        100% { stroke-dasharray: 359 1 0 0; }
    }
    
    /* Keyframes pour l'animation .spin (O) */
    @keyframes spinDashArray {
        0% { stroke-dasharray: 270 90; }
        50% { stroke-dasharray: 0 360; }
        100% { stroke-dasharray: 250 90; }
    }
    
    /* Animation du trait qui se dessine */
    @keyframes dashOffset {
        0% { stroke-dashoffset: 385; }
        100% { stroke-dashoffset: 5; }
    }
    
    /* Animation de rotation pour le O */
    @keyframes spin {
        0% { rotate: 0deg; }
        12.5%, 25% { rotate: 270deg; }
        37.5%, 50% { rotate: 540deg; }
        62.5%, 75% { rotate: 810deg; }
        87.5%, 100% { rotate: 1080deg; }
    }
    
    /* S'assurer que tous les SVG sont visibles */
    .servo-logo-container svg,
    .servo-logo-container path {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Padding pour le body */
    body {
        padding-top: 80px !important;
    }
}

        /* ========================================
   VARIABLES CSS POUR LES THÈMES
        ======================================== */
:root {
    /* Mode Jour - Moderne Dynamique */
    --day-primary: #3b82f6;
    --day-secondary: #8b5cf6;
    --day-accent: #06b6d4;
    --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(59, 130, 246, 0.15);
    --day-border: rgba(148, 163, 184, 0.2);

    /* Mode Nuit - Futuriste */
    --night-primary: #00d4ff;
    --night-secondary: #7c3aed;
    --night-accent: #ff00aa;
    --night-bg: #0a0a0a;
    --night-bg-animated: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        /* ========================================
   STRUCTURE DE BASE
        ======================================== */
/* Mode Jour - Fond animé sur body */
body:not(.night-mode) {
    margin: 0;
    padding: 0;
    padding-top: 70px !important;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
    background: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff) !important;
    background-size: 400% 400% !important;
    animation: gradientFlow 15s ease infinite !important;
}

/* Mode Nuit - Fond animé sur body */
body.night-mode {
    margin: 0;
    padding: 0;
    padding-top: 70px !important;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
    background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483) !important;
    background-size: 400% 400% !important;
    animation: gradientFlow 15s ease infinite !important;
}

/* ========================================
   MASQUER NAVBAR DESKTOP SUR MOBILE
======================================== */
@media (max-width: 767px) {
    #desktop-navbar,
    nav#desktop-navbar,
    .navbar,
    nav.navbar {
        display: none !important;
        visibility: hidden !important;
    }
    
    body, body:not(.night-mode), body.night-mode {
        padding-top: 0 !important;
    }
    
    .modern-dashboard {
        padding-bottom: 100px !important;
        margin-top: 0 !important;
    }
}

.modern-dashboard {
            position: relative;
    min-height: 100vh;
    padding: 1rem;
    transition: all 0.3s ease;
    margin-top: 0 !important;
    padding-top: 1rem !important;
    background: transparent !important;
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: transparent !important;
}

.bg-animated.night-mode {
    background: transparent !important;
}

@keyframes gradientFlow {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

/* ========================================
   COMPOSANTS MODERNES
======================================== */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.modern-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modern-title i {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.night-mode .modern-title,
.night-mode .modern-title i {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Navigation onglets moderne */
.modern-tabs {
            display: flex;
            background: var(--day-card-bg);
    border-radius: 16px;
    padding: 8px;
    box-shadow: 0 8px 32px var(--day-shadow);
            backdrop-filter: blur(20px);
            border: 1px solid var(--day-border);
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 4px;
}

.night-mode .modern-tabs {
    background: var(--night-card-bg);
    box-shadow: 0 8px 32px var(--night-shadow);
    border: 1px solid var(--night-border);
    box-shadow: var(--night-glow);
}

.modern-tab {
    flex: 1;
    min-width: 120px;
    padding: 12px 20px;
    border: none;
            background: transparent;
            color: var(--day-text-light);
    border-radius: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
    justify-content: center;
    gap: 8px;
            white-space: nowrap;
        }

.modern-tab:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--day-primary);
            transform: translateY(-2px);
        }

.modern-tab.active {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
    transform: translateY(-2px);
}

.night-mode .modern-tab {
    color: var(--night-text-light);
}

.night-mode .modern-tab:hover {
    background: rgba(0, 212, 255, 0.1);
    color: var(--night-primary);
}

.night-mode .modern-tab.active {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
    box-shadow: 0 4px 16px rgba(0, 212, 255, 0.3);
}

/* Statistiques modernes */
.modern-stats-grid {
            display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

.modern-stat-card {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
            border: 1px solid var(--day-border);
    transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
}

.modern-stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 48px var(--day-shadow);
}

.night-mode .modern-stat-card {
    background: var(--night-card-bg);
    box-shadow: 0 8px 32px var(--night-shadow);
    border: 1px solid var(--night-border);
}

.night-mode .modern-stat-card:hover {
    box-shadow: var(--night-glow), 0 16px 48px var(--night-shadow);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
    border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
        }

.night-mode .stat-icon {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
        }

.stat-value {
    font-size: 2.5rem;
            font-weight: 700;
            color: var(--day-text);
    margin-bottom: 0.5rem;
}

.night-mode .stat-value {
    color: var(--night-text);
        }

        .stat-label {
            color: var(--day-text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

.night-mode .stat-label {
    color: var(--night-text-light);
}

/* Contenu des onglets */
        .tab-content {
    display: none;
    animation: fadeIn 0.3s ease-in-out;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Cartes de contenu */
.modern-card {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
            border: 1px solid var(--day-border);
    margin-bottom: 1.5rem;
}

.night-mode .modern-card {
    background: var(--night-card-bg);
    box-shadow: 0 8px 32px var(--night-shadow);
    border: 1px solid var(--night-border);
}

/* Animations d'entrée */
.fade-in {
    animation: fadeInUp 0.6s ease-out forwards;
}

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

/* Tableaux modernes */
.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
}

.modern-table th,
.modern-table td {
    padding: 1rem;
    text-align: left;
            border-bottom: 1px solid var(--day-border);
        }

.night-mode .modern-table th,
.night-mode .modern-table td {
    border-bottom: 1px solid var(--night-border);
        }

.modern-table th {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
    font-weight: 600;
    border-radius: 12px 12px 0 0;
}

.night-mode .modern-table th {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
}

.modern-table td {
    color: var(--day-text);
}

.night-mode .modern-table td {
                color: var(--night-text);
}

/* Badges de statut */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.active {
    background: rgba(34, 197, 94, 0.2);
    color: #16a34a;
}

.status-badge.break {
    background: rgba(245, 158, 11, 0.2);
    color: #d97706;
}

.status-badge.overtime {
    background: rgba(239, 68, 68, 0.2);
    color: #dc2626;
}

.status-badge.normal {
    background: rgba(59, 130, 246, 0.2);
    color: #2563eb;
}

/* Styles pour le calendrier */
.calendar-filters {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
}

.calendar-entry {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.calendar-entry.completed {
    border-left-color: #28a745;
}

.calendar-entry.active {
    border-left-color: #007bff;
    background: rgba(0, 123, 255, 0.05) !important;
}

.calendar-entry.break {
    border-left-color: #ffc107;
    background: rgba(255, 193, 7, 0.05) !important;
}

.calendar-entry.pending {
    border-left-color: #dc3545;
    background: rgba(220, 53, 69, 0.05) !important;
}

.calendar-entry:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}

.date-header {
    font-weight: 600;
    text-transform: capitalize;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .modern-tabs {
        flex-direction: column;
    }
    
    .modern-tab {
        min-width: auto;
    }
    
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
        }

        /* ========================================
   MODE NUIT
        ======================================== */
body.night-mode {
    --day-primary: var(--night-primary);
    --day-secondary: var(--night-secondary);
    --day-accent: var(--night-accent);
                --day-card-bg: var(--night-card-bg);
                --day-text: var(--night-text);
                --day-text-light: var(--night-text-light);
                --day-shadow: var(--night-shadow);
                --day-border: var(--night-border);
            }

body.night-mode .bg-animated {
                background: var(--night-bg-animated);
}

body.night-mode .modern-header,
body.night-mode .modern-stat-card,
body.night-mode .modern-card,
body.night-mode .modern-tabs {
    background: var(--night-card-bg);
    color: var(--night-text);
                border: 1px solid var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

body.night-mode .modern-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .modern-table {
    background: #0f172a;
}

body.night-mode .modern-table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: var(--night-text);
}

body.night-mode .modern-table tr:hover {
    background-color: rgba(0, 212, 255, 0.1);
}

body.night-mode input,
body.night-mode select,
body.night-mode textarea {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode input:focus,
body.night-mode select:focus,
body.night-mode textarea:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

/* Règle spécifique pour mode jour */
body:not(.night-mode) .stat-value {
    color: #1e293b !important; /* Noir en mode jour */
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

/* Mode sombre automatique */
@media (prefers-color-scheme: dark) {
    .modern-dashboard {
        background: var(--night-bg-animated);
    }
    
    .modern-dashboard.bg-animated {
        background: var(--night-bg-animated);
    }
}
    </style>

<!-- Particules d'arrière-plan -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
        <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-clock"></i>
            Administration Pointage
            </h1>
        </div>

    <?php if (isset($table_missing) && $table_missing): ?>

        <div class="modern-card fade-in">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-database" style="font-size: 3rem; color: var(--day-text-light); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--day-text); margin-bottom: 1rem;">Table de pointage manquante</h3>
                <p style="color: var(--day-text-light);">La table <code>time_tracking</code> n'existe pas dans cette base de données.</p>
                <p style="color: var(--day-text-light);">Veuillez d'abord créer la table de pointage pour utiliser cette fonctionnalité.</p>
        </div>
        </div>
    <?php else: ?>


    <!-- Statistiques modernes -->
    <div class="modern-stats-grid fade-in">
        <div class="modern-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['currently_working']; ?></div>

            <div class="stat-label">Actuellement au travail</div>
                    </div>

        <div class="modern-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coffee"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['on_break']; ?></div>

            <div class="stat-label">En pause</div>
                    </div>

        <div class="modern-stat-card">
                        <div class="stat-icon">
                <i class="fas fa-clock"></i>
                        </div>
            <div class="stat-value"><?php echo number_format($stats['total_work_hours'], 1); ?>h</div>

            <div class="stat-label">Total heures aujourd'hui</div>
                    </div>

        <div class="modern-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['total_sessions']; ?></div>

            <div class="stat-label">Sessions totales</div>
                    </div>

        <div class="modern-stat-card">
                        <div class="stat-icon">
                <i class="fas fa-hourglass-half"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['overtime_sessions']; ?></div>

            <div class="stat-label">Heures supplémentaires</div>
                </div>

        <div class="modern-stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['pending_approvals']; ?></div>

            <div class="stat-label">À approuver</div>
                    </div>
                </div>

    <!-- Navigation par onglets moderne -->
    <div class="modern-tabs fade-in">
        <button class="modern-tab active" data-tab="live">
            <i class="fas fa-broadcast-tower"></i>
            Temps Réel
        </button>
        <button class="modern-tab" data-tab="approvals">
            <i class="fas fa-check-double"></i>
            Approbations
            <?php if (count($pending_requests) > 0): ?>

                <span class="badge" style="background: #ef4444; color: white; border-radius: 10px; padding: 2px 8px; font-size: 0.7rem; margin-left: 4px;"><?php echo count($pending_requests); ?></span>

            <?php endif; ?>

        </button>
        <button class="modern-tab" data-tab="dashboard">
            <i class="fas fa-chart-pie"></i>
            Dashboard
        </button>
        <button class="modern-tab" data-tab="calendar">
            <i class="fas fa-calendar-alt"></i>
            Calendrier
        </button>
        <button class="modern-tab" data-tab="planning">
            <i class="fas fa-calendar-week"></i>
            Planning
        </button>

            </div>

    <!-- Contenu des onglets -->
    
    <!-- Onglet Temps Réel -->
    <div class="tab-content active" id="live-content">
                <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-broadcast-tower"></i>
                Employés actuellement pointés
            </h3>

                    <?php if (empty($active_users)): ?>

                <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                    <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Aucun employé pointé actuellement</p>
                        </div>
                    <?php else: ?>

                <div style="overflow-x: auto;">
                    <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Statut</th>
                                        <th>Heure d'arrivée</th>
                                <th>Durée</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_users as $user): ?>

                                    <tr>
                                        <td>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>

                                        <small style="color: var(--day-text-light);"><?php echo htmlspecialchars($user['username']); ?></small>

                                        </td>
                                        <td>
                                        <span class="status-badge <?php echo $user['status']; ?>">

                                            <?php echo $user['status'] === 'active' ? 'Actif' : 'En pause'; ?>

                                                </span>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($user['clock_in'])); ?></td>

                                        <td>
                                        <span class="status-badge <?php echo $user['duration_status']; ?>">

                                            <?php echo $user['formatted_duration']; ?>

                                        </span>
                                        </td>
                                        <td>
                                        <button class="btn btn-sm" style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; border: none; border-radius: 8px; padding: 0.25rem 0.75rem;" onclick="forceClockOut(<?php echo $user['user_id']; ?>)">

                                            <i class="fas fa-sign-out-alt"></i>
                                            Forcer sortie
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

    <!-- Onglet Approbations -->
    <div class="tab-content" id="approvals-content">
                <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-check-double"></i>
                Demandes d'approbation
            </h3>

                    <?php if (empty($pending_requests)): ?>

                <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; color: #22c55e;"></i>
                    <p>Aucune demande en attente</p>
                        </div>
                    <?php else: ?>

                <div style="overflow-x: auto;">
                    <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                <th>Entrée</th>
                                <th>Sortie</th>
                                        <th>Durée</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_requests as $request): ?>

                                    <tr>
                                        <td>
                                        <strong><?php echo htmlspecialchars($request['full_name']); ?></strong><br>

                                        <small style="color: var(--day-text-light);"><?php echo htmlspecialchars($request['username']); ?></small>

                                        </td>
                                    <td><?php echo $request['formatted_clock_in']; ?></td>

                                    <td><?php echo $request['formatted_clock_out']; ?></td>

                                    <td><?php echo number_format($request['total_hours'], 2); ?>h</td>

                                    <td>
                                        <button class="btn btn-sm" style="background: #22c55e; color: white; border: none; border-radius: 8px; padding: 0.25rem 0.75rem; margin-right: 0.5rem;" onclick="approveRequest(<?php echo $request['id']; ?>)">

                                                    <i class="fas fa-check"></i>
                                                    Approuver
                                                </button>
                                        <button class="btn btn-sm" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 0.25rem 0.75rem;" onclick="rejectRequest(<?php echo $request['id']; ?>)">

                                                    <i class="fas fa-times"></i>
                                                    Rejeter
                                                </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                                            </div>
                    <?php endif; ?>

                </div>
            </div>

    <!-- Onglet Dashboard -->
    <div class="tab-content" id="dashboard-content">
        <!-- Graphiques en une ligne -->
        <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-chart-line"></i>
                Évolution 7 derniers jours
            </h3>
            <div style="position: relative; height: 300px; margin-bottom: 2rem;">
                <canvas id="weeklyChart" style="width: 100%; height: 100%;"></canvas>
            </div>
        </div>

        <!-- Top performers -->
        <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-trophy"></i>
                Top Performers (7 jours)
            </h3>
            
            <?php if (empty($top_performers)): ?>

                <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                    <i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Aucune donnée disponible</p>
                </div>
            <?php else: ?>

                <div style="overflow-x: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Employé</th>
                                <th>Sessions</th>
                                <th>Total heures</th>
                                <th>Moyenne/jour</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_performers as $index => $performer): ?>

                                <tr>
                                    <td>
                                        <span style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo $index + 1; ?>

                                        </span>
                                        </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($performer['full_name']); ?></strong><br>

                                        <small style="color: var(--day-text-light);"><?php echo htmlspecialchars($performer['username']); ?></small>

                                    </td>
                                    <td><?php echo $performer['sessions']; ?></td>

                                    <td><?php echo number_format($performer['total_hours'], 1); ?>h</td>

                                    <td><?php echo number_format($performer['avg_hours'], 1); ?>h</td>

                                    </tr>
                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

    <!-- Onglet Calendrier -->
    <div class="tab-content" id="calendar-content">
        <div class="modern-card">
            <!-- Header avec filtres intégrés -->
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-history" style="color: white; font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h3 style="color: var(--day-text); margin: 0; font-size: 1.25rem; font-weight: 600;">Historique</h3>
                        <span style="color: var(--day-text-light); font-size: 0.85rem;"><?php echo count($calendar_data); ?> pointages</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <input type="month" class="form-control" id="calendarMonth" 
                           value="<?php echo $calendar_month; ?>" onchange="filterCalendar()"
                           style="border-radius: 10px; border: 1px solid var(--day-border); padding: 0.5rem 1rem; font-size: 0.9rem; max-width: 180px;">
                    <select class="form-select" id="calendarUser" onchange="filterCalendar()"
                            style="border-radius: 10px; border: 1px solid var(--day-border); padding: 0.5rem 1rem; font-size: 0.9rem; max-width: 180px;">
                        <option value="">Tous</option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $calendar_user_filter == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn" onclick="openManualEntryModal()" style="background: var(--day-primary); color: white; border-radius: 10px; padding: 0.5rem 1rem; font-size: 0.9rem; border: none; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i>
                        Ajouter un pointage
                    </button>
                </div>
            </div>

            <?php if (empty($calendar_data)): ?>
                <div style="text-align: center; padding: 4rem 2rem;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-calendar-times" style="font-size: 2rem; color: #94a3b8;"></i>
                    </div>
                    <p style="color: var(--day-text); font-weight: 500; margin-bottom: 0.5rem;">Aucun pointage trouvé</p>
                    <p style="color: var(--day-text-light); font-size: 0.9rem; margin: 0;">Modifiez les filtres pour voir d'autres données</p>
                </div>
            <?php else: ?>
                <?php 
                // Regrouper les entrées par date puis par utilisateur
                $grouped_data = [];
                foreach ($calendar_data as $entry) {
                    $entry_date = date('Y-m-d', strtotime($entry['clock_in']));
                    $user_id = $entry['user_id'] ?? 0;
                    
                    if (!isset($grouped_data[$entry_date])) {
                        $grouped_data[$entry_date] = [];
                    }
                    if (!isset($grouped_data[$entry_date][$user_id])) {
                        $grouped_data[$entry_date][$user_id] = [
                            'user_name' => $entry['full_name'] ?? $entry['username'] ?? 'Utilisateur',
                            'user_id' => $user_id,
                            'sessions' => [],
                            'total_minutes' => 0,
                            'has_active' => false,
                            'has_pending' => false
                        ];
                    }
                    
                    $grouped_data[$entry_date][$user_id]['sessions'][] = $entry;
                    $grouped_data[$entry_date][$user_id]['total_minutes'] += ($entry['total_minutes'] ?? 0);
                    if ($entry['display_status'] === 'active') $grouped_data[$entry_date][$user_id]['has_active'] = true;
                    if ($entry['display_status'] === 'pending') $grouped_data[$entry_date][$user_id]['has_pending'] = true;
                }
                
                // Couleurs des employés
                $employee_colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#ef4444'];
                $color_map = [];
                
                // Jours et mois en français
                $days_fr = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                $months_fr = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                ?>
                
                <div class="timeline-container">
                    <?php foreach ($grouped_data as $date => $users): 
                        $timestamp = strtotime($date);
                        $date_formatted = $days_fr[date('w', $timestamp)] . ' ' . date('j', $timestamp) . ' ' . $months_fr[date('n', $timestamp)];
                    ?>
                        <div class="date-section" style="margin-bottom: 2rem;">
                            <!-- En-tête de date -->
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(99, 102, 241, 0.15);">
                                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; font-size: 0.9rem;">
                                    <?php echo date('d', $timestamp); ?>
                                </div>
                                <span style="color: var(--day-text); font-weight: 600; font-size: 1rem;"><?php echo $date_formatted; ?></span>
                            </div>
                            
                            <!-- Cartes utilisateurs -->
                            <?php foreach ($users as $user_id => $user_data): 
                                // Couleur utilisateur
                                if (!isset($color_map[$user_id])) {
                                    $color_map[$user_id] = $employee_colors[count($color_map) % count($employee_colors)];
                                }
                                $user_color = $color_map[$user_id];
                                $initial = mb_strtoupper(mb_substr($user_data['user_name'], 0, 1));
                                $total_hours = floor($user_data['total_minutes'] / 60);
                                $total_mins = $user_data['total_minutes'] % 60;
                                $bg_color = $user_data['has_pending'] ? 'rgba(251, 191, 36, 0.06)' : ($user_data['has_active'] ? 'rgba(34, 197, 94, 0.06)' : 'rgba(241, 245, 249, 0.4)');
                            ?>
                                <div style="background: <?php echo $bg_color; ?>; border-radius: 14px; padding: 1rem; margin-bottom: 0.75rem; border-left: 4px solid <?php echo $user_color; ?>;">
                                    <!-- Header de la carte -->
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 42px; height: 42px; background: <?php echo $user_color; ?>; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: white; font-size: 1.1rem;">
                                                <?php echo $initial; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: var(--day-text); font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                                    <?php echo htmlspecialchars($user_data['user_name']); ?>
                                                    <?php if ($user_data['has_active']): ?>
                                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #22c55e; color: white; font-size: 0.65rem; padding: 2px 8px; border-radius: 20px; font-weight: 500;">
                                                            <span style="width: 5px; height: 5px; background: white; border-radius: 50%; animation: pulse 1.5s infinite;"></span>
                                                            Actif
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="color: var(--day-text-light); font-size: 0.8rem;"><?php echo count($user_data['sessions']); ?> pointage<?php echo count($user_data['sessions']) > 1 ? 's' : ''; ?></div>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 1.4rem; font-weight: 700; color: <?php echo $user_data['has_active'] ? '#22c55e' : 'var(--day-text)'; ?>;">
                                                <?php echo sprintf('%dh%02d', $total_hours, $total_mins); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--day-text-light);">total</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Sessions (matin/après-midi) -->
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                        <?php foreach ($user_data['sessions'] as $index => $session): 
                                            $clock_in = date('H:i', strtotime($session['clock_in']));
                                            $clock_out = $session['clock_out'] ? date('H:i', strtotime($session['clock_out'])) : null;
                                            $s_hours = floor(($session['total_minutes'] ?? 0) / 60);
                                            $s_mins = ($session['total_minutes'] ?? 0) % 60;
                                            $is_morning = (int)date('H', strtotime($session['clock_in'])) < 14;
                                            $session_label = $is_morning ? 'Matin' : 'Après-midi';
                                            $is_active = $session['display_status'] === 'active';
                                            $is_pending = $session['display_status'] === 'pending';
                                        ?>
                                            <div style="flex: 1; min-width: 140px; background: <?php echo $is_pending ? 'rgba(251, 191, 36, 0.12)' : ($is_active ? 'rgba(34, 197, 94, 0.1)' : 'rgba(255,255,255,0.7)'); ?>; border-radius: 10px; padding: 0.6rem 0.75rem; border: 1px solid <?php echo $is_pending ? 'rgba(251, 191, 36, 0.3)' : ($is_active ? 'rgba(34, 197, 94, 0.25)' : 'rgba(0,0,0,0.05)'); ?>;">
                                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                                    <span style="font-size: 0.7rem; font-weight: 600; color: <?php echo $is_morning ? '#6366f1' : '#f59e0b'; ?>; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        <?php echo $session_label; ?>
                                                    </span>
                                                    <?php if ($is_pending): ?>
                                                        <div style="display: flex; gap: 3px;">
                                                            <button onclick="approveRequest(<?php echo $session['id']; ?>)" style="width: 22px; height: 22px; border-radius: 5px; border: none; background: #22c55e; color: white; cursor: pointer; font-size: 0.65rem; display: flex; align-items: center; justify-content: center;">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button onclick="rejectRequest(<?php echo $session['id']; ?>)" style="width: 22px; height: 22px; border-radius: 5px; border: none; background: #ef4444; color: white; cursor: pointer; font-size: 0.65rem; display: flex; align-items: center; justify-content: center;">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    <?php elseif ($is_active): ?>
                                                        <span style="width: 7px; height: 7px; background: #22c55e; border-radius: 50%; animation: pulse 1.5s infinite;"></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 0.95rem; font-weight: 600; color: var(--day-text);">
                                                    <?php echo $clock_in; ?> <span style="color: var(--day-text-light); font-weight: 400;">→</span> <?php echo $clock_out ?? '...'; ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--day-text-light); margin-top: 2px;">
                                                    <?php echo sprintf('%dh%02d', $s_hours, $s_mins); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>


    <!-- Onglet Planning -->
    <div class="tab-content" id="planning-content">
        <div class="modern-card">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h3 style="color: var(--day-text); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-calendar-week"></i>
                    Gestion du Planning
                </h3>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button onclick="openScheduleModal()" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Nouveau créneau
                    </button>
                    <button onclick="openAIPlanningModal()" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-robot"></i> Générer avec l'IA
                    </button>
                    <button onclick="openExportPlanningModal()" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-file-export"></i> Exporter
                    </button>
                </div>
            </div>

            <!-- Vue Planning Général -->
            <div id="planning-general-view">
                <!-- Navigation semaine -->
                <div class="week-navigation" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-radius: 12px;">
                    <button onclick="navigateWeek(-1)" style="background: var(--day-primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chevron-left"></i> Semaine précédente
                    </button>
                    <div id="currentWeekLabel" style="font-size: 1.2rem; font-weight: 600; color: var(--day-text);"></div>
                    <button onclick="navigateWeek(1)" style="background: var(--day-primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        Semaine suivante <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Grille Planning -->
                <div class="planning-grid-container" style="overflow-x: auto;">
                    <table class="planning-grid" style="width: 100%; border-collapse: separate; border-spacing: 4px;">
                        <thead>
                            <tr>
                                <th style="background: var(--day-primary); color: white; padding: 12px; border-radius: 8px; min-width: 150px;">Employé</th>
                                <th class="day-header" data-day="0" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 12px; border-radius: 8px; text-align: center;">Lun</th>
                                <th class="day-header" data-day="1" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 12px; border-radius: 8px; text-align: center;">Mar</th>
                                <th class="day-header" data-day="2" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 12px; border-radius: 8px; text-align: center;">Mer</th>
                                <th class="day-header" data-day="3" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 12px; border-radius: 8px; text-align: center;">Jeu</th>
                                <th class="day-header" data-day="4" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 12px; border-radius: 8px; text-align: center;">Ven</th>
                                <th class="day-header" data-day="5" style="background: linear-gradient(135deg, #f59e0b, #ef4444); color: white; padding: 12px; border-radius: 8px; text-align: center;">Sam</th>
                                <th class="day-header" data-day="6" style="background: linear-gradient(135deg, #f59e0b, #ef4444); color: white; padding: 12px; border-radius: 8px; text-align: center;">Dim</th>
                            </tr>
                        </thead>
                        <tbody id="planningGridBody">
                            <!-- Rempli dynamiquement -->
                        </tbody>
                    </table>
                </div>

                <!-- Actions -->
                <div class="planning-actions" style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button onclick="openScheduleModal()" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 12px 24px; border-radius: 10px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);">
                        <i class="fas fa-plus"></i> Ajouter un créneau
                    </button>
                    <button onclick="openCopyWeekModal()" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 12px 24px; border-radius: 10px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);">
                        <i class="fas fa-copy"></i> Copier la semaine
                    </button>
                </div>
            </div>

            <!-- Vue Planning Par Employé supprimée -->
        </div>
    </div>

    <!-- Modal Ajout/Modification Planning -->
    <div id="scheduleModal" class="planning-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 10000; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h4 id="scheduleModalTitle" style="color: var(--day-text); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-plus"></i> Nouveau créneau
                </h4>
                <button onclick="closeScheduleModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--day-text-light);">×</button>
            </div>
            
            <form id="scheduleForm" onsubmit="saveSchedule(event)">
                <input type="hidden" id="scheduleId" value="">
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Employés *</label>
                    <div class="custom-multiselect" style="position: relative;">
                         <input type="hidden" id="scheduleUserId"> <!-- Legacy compatibility -->
                        <!-- Trigger -->
                        <div id="multiSelectTrigger" onclick="toggleMultiSelect(event)" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text); cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                            <span id="multiSelectLabel" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">0 employé(s)</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <!-- Dropdown -->
                        <div id="multiSelectDropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 0 0 8px 8px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                            <div style="padding: 10px; border-bottom: 1px solid var(--day-border); background: rgba(0,0,0,0.1);">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: var(--day-text); margin: 0;">
                                    <input type="checkbox" onchange="toggleAllEmployees(this)" style="accent-color: var(--primary); width: 16px; height: 16px;"> Tout sélectionner
                                </label>
                            </div>
                            <?php foreach ($all_users as $user): ?>
                            <label style="display: flex; align-items: center; gap: 10px; padding: 10px; cursor: pointer; color: var(--day-text); border-bottom: 1px solid var(--day-border); margin: 0; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                                <input type="checkbox" class="employee-checkbox" value="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>" onchange="updateMultiSelectLabel()" style="accent-color: var(--primary); width: 16px; height: 16px;">
                                <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Semaines *</label>
                    <div class="custom-multiselect" style="position: relative;">
                        <input type="hidden" id="scheduleWeek"> <!-- Legacy compatibility -->
                        <!-- Trigger -->
                        <div id="weekSelectTrigger" onclick="toggleWeekSelect(event)" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text); cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                            <span id="weekSelectLabel" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sélectionner...</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <!-- Dropdown -->
                        <div id="weekSelectDropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 0 0 8px 8px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                            <div style="padding: 10px; border-bottom: 1px solid var(--day-border); background: rgba(0,0,0,0.1);">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: var(--day-text); margin: 0;">
                                    <input type="checkbox" onchange="toggleAllWeeks(this)" style="accent-color: var(--primary); width: 16px; height: 16px;"> Tout sélectionner
                                </label>
                            </div>
                            <?php 
                            // Générer les 26 prochaines semaines
                            $startDate = new DateTime('monday this week');
                            for ($i = 0; $i < 26; $i++): 
                                $weekStart = clone $startDate;
                                $weekStart->modify("+{$i} weeks");
                                $weekEnd = clone $weekStart;
                                $weekEnd->modify('+6 days');
                                $weekValue = $weekStart->format('Y-m-d');
                                $weekLabel = 'Sem. ' . $weekStart->format('W') . ' : ' . $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m/Y');
                            ?>
                            <label style="display: flex; align-items: center; gap: 10px; padding: 10px; cursor: pointer; color: var(--day-text); border-bottom: 1px solid var(--day-border); margin: 0; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                                <input type="checkbox" class="week-checkbox" value="<?php echo $weekValue; ?>" data-label="<?php echo $weekLabel; ?>" onchange="updateWeekSelectLabel()" style="accent-color: var(--primary); width: 16px; height: 16px;">
                                <?php echo $weekLabel; ?>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Jours à planifier</label>
                    <div class="days-selector" style="display: flex; gap: 4px; justify-content: space-between;">
                        <button type="button" class="day-btn" data-day="0" onclick="this.classList.toggle('active')">Lun</button>
                        <button type="button" class="day-btn" data-day="1" onclick="this.classList.toggle('active')">Mar</button>
                        <button type="button" class="day-btn" data-day="2" onclick="this.classList.toggle('active')">Mer</button>
                        <button type="button" class="day-btn" data-day="3" onclick="this.classList.toggle('active')">Jeu</button>
                        <button type="button" class="day-btn" data-day="4" onclick="this.classList.toggle('active')">Ven</button>
                        <button type="button" class="day-btn" data-day="5" onclick="this.classList.toggle('active')">Sam</button>
                        <button type="button" class="day-btn" data-day="6" onclick="this.classList.toggle('active')">Dim</button>
                    </div>
                    <!-- Hidden removed, will use week input -->
                    <style>
                        .day-btn {
                            flex: 1;
                            padding: 10px 0;
                            border: 1px solid var(--day-border);
                            background: var(--day-card-bg);
                            color: var(--day-text);
                            border-radius: 8px;
                            cursor: pointer;
                            transition: all 0.2s;
                            font-size: 0.9rem;
                            font-weight: 500;
                        }
                        .day-btn.active {
                            background: var(--day-primary);
                            color: white;
                            border-color: var(--day-primary);
                            transform: scale(1.05);
                            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                        }
                        .day-btn:hover:not(.active) {
                            background: rgba(59, 130, 246, 0.1);
                        }
                    </style>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Début *</label>
                        <input type="time" id="scheduleStartTime" required style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Fin *</label>
                        <input type="time" id="scheduleEndTime" required style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Pause début</label>
                        <input type="time" id="scheduleBreakStart" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Pause fin</label>
                        <input type="time" id="scheduleBreakEnd" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Type</label>
                    <select id="scheduleType" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                        <option value="work">🟢 Travail</option>
                        <option value="rest">🔵 Repos</option>
                        <option value="vacation">🟡 Congé</option>
                        <option value="sick">🔴 Maladie</option>
                        <option value="training">🟣 Formation</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Notes</label>
                    <textarea id="scheduleNotes" rows="2" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text); resize: vertical;"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="flex: 1; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" onclick="closeScheduleModal()" style="flex: 1; background: var(--day-text-light); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Copier Semaine -->
    <div id="copyWeekModal" class="planning-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 10000; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; max-width: 400px; width: 90%;">
            <h4 style="color: var(--day-text); margin: 0 0 1.5rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-copy"></i> Copier la semaine
            </h4>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Semaine source</label>
                <input type="week" id="sourceWeek" required style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Vers semaine</label>
                <input type="week" id="targetWeek" required style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button onclick="copyWeek()" style="flex: 1; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-copy"></i> Copier
                </button>
                <button onclick="closeCopyWeekModal()" style="flex: 1; background: var(--day-text-light); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    Annuler
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Export Planning -->
    <div id="exportPlanningModal" class="planning-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 10000; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <h4 style="color: var(--day-text); margin: 0 0 1.5rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-export"></i> Exporter le Planning Mensuel
            </h4>
            
            <!-- Sélection mois -->
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Mois *</label>
                <select id="exportMonth" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                    <?php 
                    // Générer les 12 prochains mois
                    $currentDate = new DateTime();
                    for ($i = 0; $i < 12; $i++): 
                        $monthDate = clone $currentDate;
                        $monthDate->modify("+{$i} months");
                        $monthValue = $monthDate->format('Y-m');
                        $monthLabel = ucfirst(strftime('%B %Y', $monthDate->getTimestamp()));
                        // Fallback for systems without strftime locale
                        $months_fr = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                        $monthLabel = $months_fr[(int)$monthDate->format('n') - 1] . ' ' . $monthDate->format('Y');
                    ?>
                    <option value="<?php echo $monthValue; ?>"><?php echo $monthLabel; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Sélection employés -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Employés *</label>
                <div class="custom-multiselect" style="position: relative;">
                    <!-- Trigger -->
                    <div id="exportEmployeeTrigger" onclick="toggleExportEmployeeSelect(event)" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text); cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                        <span id="exportEmployeeLabel" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sélectionner...</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <!-- Dropdown -->
                    <div id="exportEmployeeDropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 0 0 8px 8px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                        <div style="padding: 10px; border-bottom: 1px solid var(--day-border); background: rgba(0,0,0,0.1);">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: var(--day-text); margin: 0;">
                                <input type="checkbox" onchange="toggleAllExportEmployees(this)" style="accent-color: var(--primary); width: 16px; height: 16px;"> Tout sélectionner
                            </label>
                        </div>
                        <?php foreach ($all_users as $user): ?>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 10px; cursor: pointer; color: var(--day-text); border-bottom: 1px solid var(--day-border); margin: 0; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" class="export-employee-checkbox" value="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>" onchange="updateExportEmployeeLabel()" style="accent-color: var(--primary); width: 16px; height: 16px;">
                            <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button onclick="exportPlanning()" style="flex: 1; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-file-export"></i> Exporter
                </button>
                <button onclick="closeExportPlanningModal()" style="flex: 1; background: var(--day-text-light); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    Annuler
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Configuration IA Planning -->
    <div id="aiPlanningModal" class="planning-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 10000; justify-content: center; align-items: center; overflow-y: auto;">
        <div class="modal-content" style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; margin: 20px auto;">
            <h4 style="color: var(--day-text); margin: 0 0 1.5rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-robot" style="color: #8b5cf6;"></i> Génération Planning par IA
            </h4>
            
            <!-- Sélection mois -->
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(139, 92, 246, 0.1); border-radius: 12px;">
                <h5 style="color: var(--day-text); margin: 0 0 1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar"></i> Mois à planifier
                </h5>
                <select id="aiMonth" style="width: 100%; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                    <?php 
                    $currentDate = new DateTime();
                    for ($i = 0; $i < 12; $i++): 
                        $monthDate = clone $currentDate;
                        $monthDate->modify("+{$i} months");
                        $monthValue = $monthDate->format('Y-m');
                        $months_fr = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                        $monthLabel = $months_fr[(int)$monthDate->format('n') - 1] . ' ' . $monthDate->format('Y');
                    ?>
                    <option value="<?php echo $monthValue; ?>"><?php echo $monthLabel; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <!-- Config Magasin -->
            <div style="margin-bottom: 1rem; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.2);">
                <h5 onclick="document.getElementById('aiStoreConfigBody').style.display = document.getElementById('aiStoreConfigBody').style.display === 'none' ? 'block' : 'none'" style="color: var(--day-text); margin: 0; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <i class="fas fa-store"></i> Configuration Magasin
                    <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 0.8rem;"></i>
                </h5>
                <div id="aiStoreConfigBody" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(59, 130, 246, 0.2);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text-light); font-size: 0.9rem;">Ouverture</label>
                            <input type="time" id="aiStoreOpen" value="10:00" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text-light); font-size: 0.9rem;">Fermeture</label>
                            <input type="time" id="aiStoreClose" value="19:00" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text-light); font-size: 0.9rem;">Jours d'ouverture</label>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 5px; padding: 8px 12px; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 6px; cursor: pointer; color: var(--day-text); font-size: 0.85rem;">
                                <input type="checkbox" class="ai-day-open" value="0" checked> Lun
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; padding: 8px 12px; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 6px; cursor: pointer; color: var(--day-text); font-size: 0.85rem;">
                                <input type="checkbox" class="ai-day-open" value="1" checked> Mar
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; padding: 8px 12px; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 6px; cursor: pointer; color: var(--day-text); font-size: 0.85rem;">
                                <input type="checkbox" class="ai-day-open" value="2" checked> Mer
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; padding: 8px 12px; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 6px; cursor: pointer; color: var(--day-text); font-size: 0.85rem;">
                                <input type="checkbox" class="ai-day-open" value="3" checked> Jeu
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; padding: 8px 12px; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 6px; cursor: pointer; color: var(--day-text); font-size: 0.85rem;">
                                <input type="checkbox" class="ai-day-open" value="4" checked> Ven
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; padding: 8px 12px; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 6px; cursor: pointer; color: var(--day-text); font-size: 0.85rem;">
                                <input type="checkbox" class="ai-day-open" value="5" checked> Sam
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; padding: 8px 12px; background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 6px; cursor: pointer; color: var(--day-text); font-size: 0.85rem;">
                                <input type="checkbox" class="ai-day-open" value="6"> Dim
                            </label>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text-light); font-size: 0.9rem;">Durée pause</label>
                            <input type="text" id="aiBreakDuration" value="1h30" placeholder="1h30" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text-light); font-size: 0.9rem;">Pause dès</label>
                            <input type="time" id="aiBreakStart" value="11:30" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text-light); font-size: 0.9rem;">Pause jusqu'à</label>
                            <input type="time" id="aiBreakEnd" value="15:00" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                        </div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text-light); font-size: 0.9rem;">Contraintes particulières</label>
                        <textarea id="aiStoreConstraints" placeholder="Ex: 2 personnes minimum le samedi..." style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text); min-height: 60px; resize: vertical;"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Règles Avancées Magasin (Accordéon) -->
            <div style="margin-bottom: 1rem; padding: 1rem; background: rgba(139, 92, 246, 0.1); border-radius: 12px; border: 1px solid rgba(139, 92, 246, 0.2);">
                <h5 onclick="document.getElementById('aiGlobalRules').style.display = document.getElementById('aiGlobalRules').style.display === 'none' ? 'block' : 'none'" style="color: var(--day-text); margin: 0; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <i class="fas fa-cogs"></i> Règles Avancées & Objectifs
                    <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 0.8rem;"></i>
                </h5>
                <div id="aiGlobalRules" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(139, 92, 246, 0.2);">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
                        <!-- Toggles -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; color: var(--day-text); font-size: 0.9rem;">
                                    <input type="checkbox" id="ruleAbsences" checked> 📅 Optimisation Absences (BDD)
                                    <i class="fas fa-question-circle" onclick="toggleHelp(event, 'help-absences')" style="color: var(--day-primary); cursor: pointer; opacity: 0.8;"></i>
                                </label>
                                <div id="help-absences" style="display: none; margin-left: 24px; font-size: 0.8rem; color: var(--day-text-light); padding: 4px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-top: 2px;">
                                    Vérifie les congés validés en base de données et empêche l'IA de planifier les employés absents.
                                </div>
                            </div>

                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; color: var(--day-text); font-size: 0.9rem;">
                                    <input type="checkbox" id="ruleStagger" checked> ☕ Pauses Décalées (Couverture)
                                    <i class="fas fa-question-circle" onclick="toggleHelp(event, 'help-stagger')" style="color: var(--day-primary); cursor: pointer; opacity: 0.8;"></i>
                                </label>
                                <div id="help-stagger" style="display: none; margin-left: 24px; font-size: 0.8rem; color: var(--day-text-light); padding: 4px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-top: 2px;">
                                    Assure une permanence continue (ex: 12h-14h). Interdit les pauses simultanées si l'effectif min n'est pas respecté.
                                </div>
                            </div>

                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; color: var(--day-text); font-size: 0.9rem;">
                                    <input type="checkbox" id="ruleEquity" checked> ⚖️ Équité (Samedis/Fermetures)
                                    <i class="fas fa-question-circle" onclick="toggleHelp(event, 'help-equity')" style="color: var(--day-primary); cursor: pointer; opacity: 0.8;"></i>
                                </label>
                                <div id="help-equity" style="display: none; margin-left: 24px; font-size: 0.8rem; color: var(--day-text-light); padding: 4px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-top: 2px;">
                                    Force l'IA à répartir équitablement les contraintes (samedis, fermetures) entre tous les employés.
                                </div>
                            </div>

                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; color: var(--day-text); font-size: 0.9rem;">
                                    <input type="checkbox" id="ruleWeekends" checked> 🔄 Rotation Weekends
                                    <i class="fas fa-question-circle" onclick="toggleHelp(event, 'help-weekends')" style="color: var(--day-primary); cursor: pointer; opacity: 0.8;"></i>
                                </label>
                                <div id="help-weekends" style="display: none; margin-left: 24px; font-size: 0.8rem; color: var(--day-text-light); padding: 4px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-top: 2px;">
                                    Alterne les samedis travaillés pour éviter que ce soit toujours les mêmes employés.
                                </div>
                            </div>
                        </div>

                        <!-- Values -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; color: var(--day-text-light); display: flex; align-items: center; gap: 4px;">
                                    Effectif Min.
                                    <i class="fas fa-question-circle" onclick="toggleHelp(event, 'help-minstaff')" style="color: var(--day-primary); cursor: pointer; opacity: 0.8;"></i>
                                </label>
                                <input type="number" id="ruleMinStaff" value="1" min="1" max="5" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid var(--day-border); background: var(--day-card-bg); color: var(--day-text);">
                                <div id="help-minstaff" style="display: none; font-size: 0.75rem; color: var(--day-text-light); padding: 4px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-top: 2px; position: absolute; z-index: 10; width: 150px; background: var(--day-card-bg); border: 1px solid var(--day-border);">
                                    Minimum d'employés présents à tout moment.
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; color: var(--day-text-light); display: flex; align-items: center; gap: 4px;">
                                    Max Jours Conséc.
                                    <i class="fas fa-question-circle" onclick="toggleHelp(event, 'help-maxdays')" style="color: var(--day-primary); cursor: pointer; opacity: 0.8;"></i>
                                </label>
                                <input type="number" id="ruleMaxDays" value="6" min="1" max="10" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid var(--day-border); background: var(--day-card-bg); color: var(--day-text);">
                                <div id="help-maxdays" style="display: none; font-size: 0.75rem; color: var(--day-text-light); padding: 4px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-top: 2px; position: absolute; z-index: 10; width: 150px; background: var(--day-card-bg); border: 1px solid var(--day-border);">
                                    Max jours de travail d'affilée sans repos.
                                </div>
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 0.8rem; color: var(--day-text-light); display: flex; align-items: center; gap: 4px;">
                                    Durée Shift (Min - Max)
                                    <i class="fas fa-question-circle" onclick="toggleHelp(event, 'help-shift')" style="color: var(--day-primary); cursor: pointer; opacity: 0.8;"></i>
                                </label>
                                <div style="display: flex; gap: 5px; align-items: center;">
                                    <input type="number" id="ruleShiftMin" value="4" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid var(--day-border); background: var(--day-card-bg); color: var(--day-text);">
                                    <span style="color: var(--day-text);">-</span>
                                    <input type="number" id="ruleShiftMax" value="10" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid var(--day-border); background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                                <div id="help-shift" style="display: none; font-size: 0.75rem; color: var(--day-text-light); padding: 4px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-top: 2px;">
                                    Durée minimale et maximale d'une journée de travail.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Config Employés (Accordéon) -->
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(34, 197, 94, 0.1); border-radius: 12px; border: 1px solid rgba(34, 197, 94, 0.2);">
                <h5 onclick="document.getElementById('aiEmployeesContainer').style.display = document.getElementById('aiEmployeesContainer').style.display === 'none' ? 'block' : 'none'" style="color: var(--day-text); margin: 0; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <i class="fas fa-users"></i> Configuration Employés
                    <span style="font-size: 0.8rem; color: var(--day-text-light); font-weight: normal; margin-left: 10px;">(Cliquer pour ouvrir)</span>
                    <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 0.8rem;"></i>
                </h5>
                <div id="aiEmployeesContainer" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(34, 197, 94, 0.2);">
                    <?php foreach ($all_users as $idx => $user): ?>
                    <div class="ai-employee-config" data-user-id="<?php echo $user['id']; ?>" style="background: var(--day-card-bg); border: 1px solid var(--day-border); border-radius: 8px; margin-bottom: 0.5rem; overflow: hidden;">
                        <!-- Header cliquable -->
                        <div class="ai-emp-header" onclick="toggleEmployeeConfig(this)" style="padding: 12px 15px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" class="ai-employee-checkbox" value="<?php echo $user['id']; ?>" checked style="width: 18px; height: 18px;" onclick="event.stopPropagation()">
                            <strong style="color: var(--day-text); flex: 1;"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></strong>
                            <span class="ai-emp-summary" style="font-size: 0.8rem; color: var(--day-text-light);">35h/sem, 2j repos</span>
                            <i class="fas fa-chevron-down ai-emp-arrow" style="color: var(--day-text-light); transition: transform 0.3s;"></i>
                        </div>
                        <!-- Contenu expansible -->
                        <div class="ai-emp-body" style="display: none; padding: 0 15px 15px; border-top: 1px solid var(--day-border);">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-top: 12px;">
                                <div>
                                    <label style="display: block; font-size: 0.8rem; color: var(--day-text-light);">Heures/sem</label>
                                    <input type="number" class="ai-emp-hours" value="35" min="0" max="50" onchange="updateEmployeeSummary(this)" style="width: 100%; padding: 8px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8rem; color: var(--day-text-light);">Jours repos/sem</label>
                                    <input type="number" class="ai-emp-rest" value="2" min="0" max="6" onchange="updateEmployeeSummary(this)" style="width: 100%; padding: 8px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8rem; color: var(--day-text-light);">Jours école/sem</label>
                                    <input type="number" class="ai-emp-school" value="0" min="0" max="5" onchange="updateEmployeeSummary(this)" style="width: 100%; padding: 8px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                            </div>
                            
                            <!-- Nouveaux réglages employés -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-top: 0.5rem;">
                                <div>
                                    <label style="display: block; font-size: 0.8rem; color: var(--day-text-light);">Préférence Horaire</label>
                                    <select class="ai-emp-pref" onchange="updateEmployeeSummary(this)" style="width: 100%; padding: 8px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                                        <option value="">Indifférent</option>
                                        <option value="Matin">Matin (Ouverture)</option>
                                        <option value="Soir">Soir (Fermeture)</option>
                                    </select>
                                </div>
                                <div style="display: flex; flex-direction: column; justify-content: center; gap: 5px;">
                                    <label style="display: flex; align-items: center; gap: 5px; font-size: 0.8rem; color: var(--day-text);">
                                        <input type="checkbox" class="ai-emp-apprentice" onchange="updateEmployeeSummary(this)"> 👨‍🏫 Mode Apprenti (Jamais seul)
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 5px; font-size: 0.8rem; color: var(--day-text);">
                                        <input type="checkbox" class="ai-emp-consecutive" onchange="updateEmployeeSummary(this)"> 📆 Repos Groupés
                                    </label>
                                </div>
                            </div>

                            <div style="margin-top: 0.5rem;">
                                <input type="text" class="ai-emp-constraints" placeholder="Contraintes textuelles (ex: pas le lundi...)" onchange="updateEmployeeSummary(this)" style="width: 100%; padding: 8px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text); font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button onclick="saveAIConfiguration()" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); padding: 14px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; margin-right: 10px; transition: all 0.2s;">
                    <i class="fas fa-save"></i> Sauvegarder Config
                </button>
                <button onclick="generateAIPlanning()" id="aiGenerateBtn" style="flex: 2; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 14px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-magic"></i> Générer le Planning
                </button>
                <button onclick="closeAIPlanningModal()" style="flex: 1; background: var(--day-text-light); color: white; border: none; padding: 14px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    Annuler
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Résultats IA Planning -->
    <div id="aiPlanningResultsModal" class="planning-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 10001; justify-content: center; align-items: center; overflow-y: auto;">
        <div class="modal-content" style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; max-width: 95%; width: 1200px; max-height: 90vh; overflow-y: auto; margin: 20px auto;">
            <h4 style="color: var(--day-text); margin: 0 0 1.5rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle" style="color: #22c55e;"></i> Prévisualisation du Planning Généré
                <span id="aiResultsMonth" style="margin-left: auto; font-size: 0.9rem; color: var(--day-text-light);"></span>
            </h4>
            
            <div id="aiResultsLoading" style="text-align: center; padding: 2rem; display: none;">
                <div id="aiPromptPreview" style="text-align: left; margin-bottom: 1.5rem; padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 8px; max-height: 300px; overflow-y: auto;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 600;">
                        <i class="fas fa-code"></i> Prompt envoyé à l'IA :
                    </label>
                    <pre id="aiPromptText" style="white-space: pre-wrap; font-size: 0.8rem; color: var(--day-text-light); margin: 0; font-family: monospace;"></pre>
                </div>
                <i class="fas fa-spinner fa-spin fa-2x" style="color: #8b5cf6;"></i>
                <p style="color: var(--day-text); margin-top: 1rem;">L'IA génère votre planning...</p>
            </div>
            
            <div id="aiResultsContent" style="display: none;">
                <!-- Navigation semaine IA -->
                <div class="week-navigation" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-radius: 12px;">
                    <button onclick="navigateAIWeek(-1)" style="background: var(--day-primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chevron-left"></i> Semaine précédente
                    </button>
                    <div id="aiCurrentWeekLabel" style="font-size: 1.1rem; font-weight: 600; color: var(--day-text);"></div>
                    <button onclick="navigateAIWeek(1)" style="background: var(--day-primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        Semaine suivante <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                
                <div style="overflow-x: auto; margin-bottom: 1.5rem;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">
                        <thead>
                            <tr id="aiGridHeaders">
                                <th style="padding: 12px; text-align: left; color: var(--day-text);">Employé</th>
                                <!-- Days will be injected here -->
                            </tr>
                        </thead>
                        <tbody id="aiResultsTableBody"></tbody>
                    </table>
                </div>
                
                <!-- Zone de modification conversationnelle -->
                <div style="margin-bottom: 1rem; padding: 1rem; background: rgba(139, 92, 246, 0.1); border-radius: 12px;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">
                        <i class="fas fa-comment-dots"></i> Demander une modification à l'IA
                    </label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="aiModifyMessage" placeholder="Ex: Donne plus d'heures à Benjamin le samedi, retire les lundis pour Sarah..." style="flex: 1; padding: 12px; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                        <button onclick="modifyAIPlanning()" id="aiModifyBtn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                            <i class="fas fa-magic"></i> Modifier
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button onclick="saveAIPlanning()" style="flex: 2; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 14px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-save"></i> Valider et Enregistrer
                    </button>
                    <button onclick="closeAIResultsModal()" style="flex: 1; background: var(--day-text-light); color: white; border: none; padding: 14px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>


                                <?php endif; ?>

            </div>

    <script>
// Détection IMMÉDIATE du mode nuit (avant DOMContentLoaded)
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
        console.log('🌙 Mode nuit détecté et appliqué immédiatement');
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
        console.log('☀️ Mode jour détecté et appliqué immédiatement');
    }
})();

// Gestion des onglets
document.querySelectorAll('.modern-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const targetTab = this.dataset.tab;
        
        // Mettre à jour les onglets actifs
        document.querySelectorAll('.modern-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
        
        // Mettre à jour le contenu actif
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(targetTab + '-content').classList.add('active');
    });
});

// Fonction de détection automatique du mode nuit
function detectAndApplyDarkMode() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.body.classList.add('night-mode');
    } else {
        document.body.classList.remove('night-mode');
    }
}

// Écouter les changements de préférence système
if (window.matchMedia) {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addListener(function(e) {
        if (localStorage.getItem('theme') === null) {
            if (e.matches) {
                document.body.classList.add('night-mode');
            } else {
                document.body.classList.remove('night-mode');
            }
        }
    });
}

// Fonction pour basculer manuellement le mode
function toggleDarkMode() {
    document.body.classList.toggle('night-mode');
    const isDark = document.body.classList.contains('night-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// Actualisation automatique désactivée pour debug
// setInterval(function() {
//     location.reload();
// }, 30000);

// =====================================================
// FONCTIONS D'ACTION - Force sortie, Approuver, Rejeter
// =====================================================

function forceClockOut(userId) {
    const formData = new FormData();
    formData.append('action', 'force_clock_out');
    formData.append('user_id', userId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur HTTP: ' + response.status);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Réponse non-JSON reçue:', text);
                throw new Error('Réponse invalide du serveur');
            }
        });
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            console.error('Erreur:', data.message);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Erreur complète:', error);
        location.reload();
    });
}

function approveRequest(requestId) {
    // 1. Récupérer les détails avant de montrer le modal
    const formData = new FormData();
    formData.append('action', 'get_approval_details');
    formData.append('request_id', requestId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remplir le modal
            document.getElementById('latencyRequestId').value = requestId;
            document.getElementById('latencyUserName').textContent = data.request.user_name;
            document.getElementById('latencyClockIn').value = data.request.clock_in; //  + ' (' + data.request.date + ')';
            
            const schedule = data.schedule;
            const alertBox = document.getElementById('latencyDetectedAlert');
            const createCheckbox = document.getElementById('createLatencyEvent');
            const latencyFields = document.getElementById('latencyFields');
            const durationInput = document.getElementById('latencyDuration');
            
            // Reset fields
            document.getElementById('latencyComment').value = '';
            
            if (schedule.exists) {
                document.getElementById('latencyScheduledStart').textContent = schedule.start_time;
                
                if (schedule.delay_minutes > 0) {
                    // RETARD DÉTECTÉ
                    alertBox.style.display = 'block';
                    document.getElementById('latencyMinutesDisplay').textContent = schedule.delay_minutes;
                    createCheckbox.checked = true;
                    durationInput.value = schedule.delay_minutes;
                    latencyFields.style.display = 'block';
                } else {
                    // Pas de retard
                    alertBox.style.display = 'none';
                    createCheckbox.checked = false;
                    durationInput.value = '';
                    latencyFields.style.display = 'none';
                }
            } else {
                // Pas de planning
                document.getElementById('latencyScheduledStart').textContent = 'Non planifié';
                alertBox.style.display = 'none';
                createCheckbox.checked = false;
                durationInput.value = '';
                latencyFields.style.display = 'none';
            }
            
            // Afficher le modal
            document.getElementById('approveLatencyModal').style.display = 'flex';
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de communication avec le serveur');
    });
}

function toggleLatencyFields() {
    const isChecked = document.getElementById('createLatencyEvent').checked;
    document.getElementById('latencyFields').style.display = isChecked ? 'block' : 'none';
}

function closeLatencyModal() {
    document.getElementById('approveLatencyModal').style.display = 'none';
}

function confirmApprovalWithLatency() {
    const requestId = document.getElementById('latencyRequestId').value;
    const createEvent = document.getElementById('createLatencyEvent').checked;
    const duration = document.getElementById('latencyDuration').value;
    const comment = document.getElementById('latencyComment').value;
    const correctedClockIn = document.getElementById('latencyClockIn').value;
    
    if (createEvent && (!duration || duration <= 0)) {
        alert('Veuillez saisir une durée valide pour le retard.');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'approve_with_lateness');
    formData.append('request_id', requestId);
    formData.append('create_event', createEvent);
    formData.append('duration_minutes', duration);
    formData.append('comment', comment);
    formData.append('corrected_clock_in', correctedClockIn);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeLatencyModal();
            // Supprimer la ligne du tableau sans recharger toute la page si possible, ou reload
            location.reload(); 
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la validation');
    });
}

function rejectRequest(requestId) {
    if (confirm('Rejeter cette demande de pointage ?')) {
        const formData = new FormData();
        formData.append('action', 'reject_request');
        formData.append('request_id', requestId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Réponse non-JSON reçue:', text);
                    throw new Error('Réponse invalide du serveur');
                }
            });
        })
        .then(data => {
            if (data.success) {
                alert('Demande rejetée avec succès');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur complète:', error);
            location.reload();
        });
    }
}



// =====================================================
// FONCTIONS CALENDRIER
// =====================================================

function filterCalendar() {
    const month = document.getElementById('calendarMonth').value;
    const user = document.getElementById('calendarUser').value;
    
    const url = new URL(window.location);
    url.searchParams.set('calendar_month', month);
    if (user) {
        url.searchParams.set('calendar_user', user);
    } else {
        url.searchParams.delete('calendar_user');
    }
    
    window.location = url;
}

// =====================================================
// ANIMATION PARTICULES
// =====================================================

function createParticles() {
    const particlesContainer = document.getElementById('particles');
    if (!particlesContainer) return;
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float ${Math.random() * 3 + 2}s infinite ease-in-out;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation-delay: ${Math.random() * 2}s;
        `;
        particlesContainer.appendChild(particle);
    }
}

// Style pour l'animation des particules
const style = document.createElement('style');
style.textContent = `
    .particles-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
        50% { transform: translateY(-20px) rotate(180deg); opacity: 0.8; }
    }
`;
document.head.appendChild(style);

// Initialiser les particules
createParticles();

// Initialisation complète
document.addEventListener('DOMContentLoaded', function() {
    detectAndApplyDarkMode();
});

// =====================================================
// GRAPHIQUE DES 7 DERNIERS JOURS
// =====================================================

function initWeeklyChart() {
    const canvas = document.getElementById('weeklyChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const chartData = <?php echo json_encode($chart_data ?? []); ?>;
    
    if (!chartData || chartData.length === 0) return;
    
    // Données pour le graphique
    const labels = chartData.map(d => d.display_date);
    const hoursData = chartData.map(d => d.hours);
    
    // Dimensions
    const width = canvas.width;
    const height = canvas.height;
    const padding = 40;
    
    // Nettoyer le canvas
    ctx.clearRect(0, 0, width, height);
    
    // Couleurs
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const primaryColor = isDark ? '#00d4ff' : '#3b82f6';
    const textColor = isDark ? '#ffffff' : '#1e293b';
    const gridColor = isDark ? '#374151' : '#e5e7eb';
    
    // Dessiner la grille
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 1;
    
    // Lignes horizontales
    for (let i = 0; i <= 5; i++) {
        const y = padding + (height - 2 * padding) * i / 5;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(width - padding, y);
        ctx.stroke();
    }
    
    // Dessiner les données (heures)
    if (hoursData.length > 0) {
        const maxHours = Math.max(...hoursData, 1);
        
        ctx.strokeStyle = primaryColor;
        ctx.lineWidth = 3;
        ctx.beginPath();
        
        for (let i = 0; i < hoursData.length; i++) {
            const x = padding + (width - 2 * padding) * i / (hoursData.length - 1);
            const y = height - padding - (height - 2 * padding) * hoursData[i] / maxHours;
            
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
            
            // Points
            ctx.fillStyle = primaryColor;
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, 2 * Math.PI);
            ctx.fill();
        }
        ctx.stroke();
    }
    
    // Labels des jours
    ctx.fillStyle = textColor;
    ctx.font = '12px Inter, sans-serif';
    ctx.textAlign = 'center';
    
    for (let i = 0; i < labels.length; i++) {
        const x = padding + (width - 2 * padding) * i / (labels.length - 1);
        ctx.fillText(labels[i], x, height - 10);
    }
    
    // Titre
    ctx.font = 'bold 14px Inter, sans-serif';
    ctx.fillText('Heures travaillées par jour', width / 2, 20);
}

// Initialiser le graphique après chargement
setTimeout(initWeeklyChart, 500);

// ========== PLANNING FUNCTIONS ==========
let currentWeekStart = getMonday(new Date());
let currentEmployeeMonth = new Date();
let selectedEmployeeId = null;
let allUsers = <?php echo json_encode($all_users); ?>;

function getMonday(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function formatDateFR(date) {
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// Switch entre vues Planning
function switchPlanningView(view) {
    const generalView = document.getElementById('planning-general-view');
    const employeeView = document.getElementById('planning-employee-view');
    const buttons = document.querySelectorAll('.planning-view-btn');
    
    buttons.forEach(btn => {
        btn.style.background = btn.dataset.view === view ? 'var(--day-primary)' : 'transparent';
        btn.style.color = btn.dataset.view === view ? 'white' : 'var(--day-text)';
    });
    
    if (view === 'general') {
        generalView.style.display = 'block';
        employeeView.style.display = 'none';
        loadSchedules();
    } else {
        generalView.style.display = 'none';
        employeeView.style.display = 'block';
    }
}

// Navigation semaine
function navigateWeek(direction) {
    currentWeekStart.setDate(currentWeekStart.getDate() + (direction * 7));
    loadSchedules();
}

function updateWeekLabel() {
    const weekEnd = new Date(currentWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    document.getElementById('currentWeekLabel').textContent = 
        `Semaine du ${formatDateFR(currentWeekStart)} au ${formatDateFR(weekEnd)}`;
}

// Charger les plannings
function loadSchedules() {
    updateWeekLabel();
    const tbody = document.getElementById('planningGridBody');
    if (tbody.innerHTML.trim() === '') {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">Chargement...</td></tr>';
    }
    
    const formData = new FormData();
    formData.append('action', 'get_schedules');
    const d = new Date(currentWeekStart);
    const dateStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    formData.append('week_start', dateStr);
    
    fetch('ajax/planning_handler.php', { method: 'POST', body: formData })
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    renderPlanningGrid(data.schedules);
                } else {
                    console.error('Erreur API:', data.message);
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Erreur: ${data.message}</td></tr>`;
                }
            } catch (e) {
                console.error('Erreur Parse JSON:', e, text);
                tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Erreur format données (${text.substring(0, 50)}...)</td></tr>`;
            }
        })
        .catch(err => {
            console.error('Erreur:', err);
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">Erreur de chargement</td></tr>';
        });
}

// Afficher la grille
function renderPlanningGrid(schedules) {
    console.log('Rendering grid with', schedules.length, 'schedules and', allUsers.length, 'users');
    const tbody = document.getElementById('planningGridBody');
    const headers = document.querySelectorAll('.day-header');
    
    // Mettre à jour les dates des headers
    for (let i = 0; i < 7; i++) {
        const date = new Date(currentWeekStart);
        date.setDate(date.getDate() + i);
        const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        headers[i].innerHTML = `${dayNames[i]}<br><small>${date.getDate()}/${date.getMonth() + 1}</small>`;
    }
    
    // Grouper par utilisateur
    const userSchedules = {};
    allUsers.forEach(user => {
        userSchedules[user.id] = { name: user.full_name || user.username, days: {} };
    });
    
    schedules.forEach(s => {
        if (!userSchedules[s.user_id]) {
            userSchedules[s.user_id] = { name: s.full_name || s.username, days: {} };
        }
        userSchedules[s.user_id].days[s.schedule_date] = s;
    });
    
    // Construire les lignes
    let html = '';
    Object.entries(userSchedules).forEach(([userId, userData]) => {
        html += `<tr>
            <td style="background: var(--day-card-bg); padding: 12px; border-radius: 8px; font-weight: 500; color: var(--day-text);">
                ${userData.name}
            </td>`;
        
        for (let i = 0; i < 7; i++) {
            const date = new Date(currentWeekStart);
            date.setDate(date.getDate() + i);
            const dateStr = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            const schedule = userData.days[dateStr];
            
            if (schedule) {
                const typeColors = {
                    work: '#22c55e',
                    rest: '#3b82f6',
                    vacation: '#f59e0b',
                    sick: '#ef4444',
                    training: '#8b5cf6'
                };
                const color = typeColors[schedule.schedule_type] || '#22c55e';
                const startTime = schedule.start_time.substring(0, 5);
                const endTime = schedule.end_time.substring(0, 5);
                
                html += `<td onclick="editSchedule(${schedule.id})" 
                         style="background: ${color}; color: white; padding: 8px; border-radius: 8px; text-align: center; cursor: pointer; transition: transform 0.2s;"
                         onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <strong>${startTime}</strong><br><small>${endTime}</small>
                </td>`;
            } else {
                html += `<td onclick="openScheduleModalForDate('${dateStr}', ${userId})" 
                         style="background: rgba(148, 163, 184, 0.2); padding: 8px; border-radius: 8px; text-align: center; cursor: pointer; min-height: 50px;"
                         onmouseover="this.style.background='rgba(59, 130, 246, 0.2)'" onmouseout="this.style.background='rgba(148, 163, 184, 0.2)'">
                    <i class="fas fa-plus" style="color: var(--day-text-light); opacity: 0.5;"></i>
                </td>`;
            }
        }
        html += '</tr>';
    });
    
    tbody.innerHTML = html || '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: var(--day-text-light);">Aucun employé configuré</td></tr>';
}

// Modal Planning
function openScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'flex';
    document.getElementById('scheduleId').value = '';
    
    // Reset Form
    document.getElementById('scheduleForm').reset();
    
    // Reset Employee Checkboxes
    document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
    updateMultiSelectLabel();
    
    // Reset Week Checkboxes
    document.querySelectorAll('.week-checkbox').forEach(cb => cb.checked = false);
    // Pre-select current week
    let dateToUse = (typeof currentWeekStart !== 'undefined') ? currentWeekStart : new Date();
    const d = new Date(dateToUse);
    const currentWeekValue = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const weekCb = document.querySelector(`.week-checkbox[value="${currentWeekValue}"]`);
    if (weekCb) weekCb.checked = true;
    updateWeekSelectLabel();
    
    // Reset Days
    document.querySelectorAll('.day-btn').forEach(b => b.classList.remove('active'));

    document.getElementById('scheduleModalTitle').innerHTML = '<i class="fas fa-calendar-plus"></i> Nouveau créneau';
}

function openScheduleModalForDate(dateStr, userId) {
    openScheduleModal();
    // Pre-select User
    if (userId) {
        const cb = document.querySelector(`.employee-checkbox[value="${userId}"]`);
        if (cb) {
             cb.checked = true;
             updateMultiSelectLabel();
        }
        // Legacy compat (if handled elsewhere)
        const hiddenInput = document.getElementById('scheduleUserId');
        if (hiddenInput) hiddenInput.value = userId;
    }

    // Calculer le jour de la semaine et l'activer
    const targetDate = new Date(dateStr);
    const dayIndex = (targetDate.getDay() + 6) % 7; // 0=Lun
    const btn = document.querySelector(`.day-btn[data-day="${dayIndex}"]`);
    if(btn) btn.classList.add('active');
    
    // Set Week based on targetDate
    document.getElementById('scheduleWeek').value = getWeekString(targetDate);
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'none';
    const dd = document.getElementById('multiSelectDropdown');
    if (dd) dd.style.display = 'none';
}

function editSchedule(scheduleId) {
    openScheduleModal();
    document.getElementById('scheduleModalTitle').innerHTML = '<i class="fas fa-edit"></i> Modifier créneau';
    document.getElementById('scheduleId').value = scheduleId;
}

function saveSchedule(e) {
    e.preventDefault();
    
    // Get selected users
    const selectedUsers = [];
    document.querySelectorAll('.employee-checkbox:checked').forEach(cb => {
        selectedUsers.push(cb.value);
    });

    if (selectedUsers.length === 0) {
        showToast('Veuillez sélectionner au moins un employé', 'error');
        return;
    }
    
    // Get selected days
    const selectedDays = [];
    document.querySelectorAll('.day-btn.active').forEach(b => {
        selectedDays.push(parseInt(b.dataset.day));
    });
    
    if (selectedDays.length === 0) {
        showToast('Veuillez sélectionner au moins un jour', 'error');
        return;
    }
    
    // Get selected weeks
    const selectedWeeks = [];
    document.querySelectorAll('.week-checkbox:checked').forEach(cb => {
        selectedWeeks.push(cb.value); // Already in YYYY-MM-DD format
    });
    
    if (selectedWeeks.length === 0) {
        showToast('Veuillez sélectionner au moins une semaine', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'save_weekly_schedule');
    formData.append('user_ids', JSON.stringify(selectedUsers));
    formData.append('week_starts', JSON.stringify(selectedWeeks)); // NEW: Multiple weeks
    formData.append('days', JSON.stringify(selectedDays));
    formData.append('start_time', document.getElementById('scheduleStartTime').value);
    formData.append('end_time', document.getElementById('scheduleEndTime').value);
    formData.append('break_start', document.getElementById('scheduleBreakStart').value);
    formData.append('break_end', document.getElementById('scheduleBreakEnd').value);
    formData.append('schedule_type', document.getElementById('scheduleType').value);
    formData.append('notes', document.getElementById('scheduleNotes').value);
    
    fetch('ajax/planning_handler.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update view to match saved week
                if (typeof currentWeekStart !== 'undefined' && typeof weekStartDate !== 'undefined') {
                    currentWeekStart = new Date(weekStartDate);
                    // Try to find display element
                    const displays = document.querySelectorAll('#currentWeekDisplay, #weekDisplay, .week-display');
                    if (displays.length > 0) {
                        const end = new Date(currentWeekStart);
                        end.setDate(end.getDate() + 6);
                        const options = {day: 'numeric', month: 'long'};
                        const text = `Semaine du ${currentWeekStart.toLocaleDateString('fr-FR', options)} au ${end.toLocaleDateString('fr-FR', options)}`;
                        displays.forEach(d => d.textContent = text);
                        // Also update render headers
                         const headers = document.querySelectorAll('.day-header');
                         if (headers.length === 7) {
                             for (let i = 0; i < 7; i++) {
                                 const d = new Date(currentWeekStart);
                                 d.setDate(d.getDate() + i);
                                 const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
                                 headers[i].innerHTML = `${dayNames[i]}<br><small>${d.getDate()}/${d.getMonth() + 1}</small>`;
                             }
                         }
                    }
                }

                closeScheduleModal();
                loadSchedules();
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erreur de connexion', 'error');
        });
}

// MultiSelect Helper Functions
function toggleMultiSelect(e) {
    if(e) e.stopPropagation();
    const dropdown = document.getElementById('multiSelectDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function updateMultiSelectLabel() {
    const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
    const label = document.getElementById('multiSelectLabel');
    
    if (checkboxes.length === 0) {
        label.textContent = "Sélectionner...";
    } else if (checkboxes.length === 1) {
        label.textContent = checkboxes[0].dataset.name;
    } else {
        label.textContent = checkboxes.length + " employés";
    }
}

function toggleAllEmployees(source) {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
    });
    updateMultiSelectLabel();
}

document.addEventListener('click', function(e) {
    // Close employee dropdown
    const dropdown = document.getElementById('multiSelectDropdown');
    const trigger = document.getElementById('multiSelectTrigger');
    if (dropdown && dropdown.style.display === 'block') {
         if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
             dropdown.style.display = 'none';
         }
    }
    // Close week dropdown
    const weekDropdown = document.getElementById('weekSelectDropdown');
    const weekTrigger = document.getElementById('weekSelectTrigger');
    if (weekDropdown && weekDropdown.style.display === 'block') {
         if (!weekDropdown.contains(e.target) && !weekTrigger.contains(e.target)) {
             weekDropdown.style.display = 'none';
         }
    }
});

// Week MultiSelect Helper Functions
function toggleWeekSelect(e) {
    if(e) e.stopPropagation();
    const dropdown = document.getElementById('weekSelectDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function updateWeekSelectLabel() {
    const checkboxes = document.querySelectorAll('.week-checkbox:checked');
    const label = document.getElementById('weekSelectLabel');
    
    if (checkboxes.length === 0) {
        label.textContent = "Sélectionner...";
    } else if (checkboxes.length === 1) {
        label.textContent = checkboxes[0].dataset.label;
    } else {
        label.textContent = checkboxes.length + " semaines";
    }
    
    // Update legacy hidden input with first selected value
    if (checkboxes.length > 0) {
        document.getElementById('scheduleWeek').value = checkboxes[0].value;
    }
}

function toggleAllWeeks(source) {
    const checkboxes = document.querySelectorAll('.week-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
    });
    updateWeekSelectLabel();
}

// Export Planning Functions
function openExportPlanningModal() {
    document.getElementById('exportPlanningModal').style.display = 'flex';
    // Reset checkboxes
    document.querySelectorAll('.export-employee-checkbox').forEach(cb => cb.checked = false);
    updateExportEmployeeLabel();
}

function closeExportPlanningModal() {
    document.getElementById('exportPlanningModal').style.display = 'none';
    const dd = document.getElementById('exportEmployeeDropdown');
    if (dd) dd.style.display = 'none';
}

function toggleExportEmployeeSelect(e) {
    if(e) e.stopPropagation();
    const dropdown = document.getElementById('exportEmployeeDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function updateExportEmployeeLabel() {
    const checkboxes = document.querySelectorAll('.export-employee-checkbox:checked');
    const label = document.getElementById('exportEmployeeLabel');
    
    if (checkboxes.length === 0) {
        label.textContent = "Sélectionner...";
    } else if (checkboxes.length === 1) {
        label.textContent = checkboxes[0].dataset.name;
    } else {
        label.textContent = checkboxes.length + " employés";
    }
}

function toggleAllExportEmployees(source) {
    const checkboxes = document.querySelectorAll('.export-employee-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
    });
    updateExportEmployeeLabel();
}

function exportPlanning() {
    const selectedUsers = [];
    document.querySelectorAll('.export-employee-checkbox:checked').forEach(cb => {
        selectedUsers.push(cb.value);
    });
    
    if (selectedUsers.length === 0) {
        showToast('Veuillez sélectionner au moins un employé', 'error');
        return;
    }
    
    const month = document.getElementById('exportMonth').value;
    if (!month) {
        showToast('Veuillez sélectionner un mois', 'error');
        return;
    }
    
    // Open print page in new tab
    const params = new URLSearchParams();
    params.append('month', month);
    selectedUsers.forEach(id => params.append('user_ids[]', id));
    
    window.open('pages/planning_export_print.php?' + params.toString(), '_blank');
    closeExportPlanningModal();
}

// Close export employee dropdown on click outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('exportEmployeeDropdown');
    const trigger = document.getElementById('exportEmployeeTrigger');
    if (dropdown && dropdown.style.display === 'block') {
        if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    }
});

// ============================
// AI PLANNING GENERATOR
// ============================

let aiGeneratedSchedules = [];

// Accordion toggle for employee config
function toggleHelp(e, id) {
    if (e) e.stopPropagation();
    const el = document.getElementById(id);
    // Close others
    document.querySelectorAll('[id^="help-"]').forEach(d => {
        if(d.id !== id) d.style.display = 'none';
    });
    
    if (el) {
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
}

function toggleEmployeeConfig(header) {
    const card = header.closest('.ai-employee-config');
    const body = card.querySelector('.ai-emp-body');
    const arrow = header.querySelector('.ai-emp-arrow');
    
    if (body.style.display === 'none' || !body.style.display) {
        body.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    }
}

// Update summary text when employee config changes
function updateEmployeeSummary(input) {
    const card = input.closest('.ai-employee-config');
    const summary = card.querySelector('.ai-emp-summary');
    const hours = card.querySelector('.ai-emp-hours').value || 35;
    const rest = card.querySelector('.ai-emp-rest').value || 2;
    const school = card.querySelector('.ai-emp-school').value || 0;
    const constraints = card.querySelector('.ai-emp-constraints').value;
    const apprentice = card.querySelector('.ai-emp-apprentice').checked;
    
    let text = `${hours}h/sem, ${rest}j repos`;
    if (school > 0) text += `, ${school}j école`;
    if (apprentice) text += ' 👨‍🏫';
    if (constraints) text += ' ⚠️';
    
    summary.textContent = text;
}

function openAIPlanningModal() {
    document.getElementById('aiPlanningModal').style.display = 'flex';
    loadAIConfiguration(); // Charger la config sauvegardée
}

function saveAIConfiguration() {
    // Collect Data identical to generateAIPlanning logic
    const storeConfig = {
        open_time: document.getElementById('aiStoreOpen').value,
        close_time: document.getElementById('aiStoreClose').value,
        days_open: Array.from(document.querySelectorAll('.ai-day-open:checked')).map(cb => parseInt(cb.value)),
        break_duration: document.getElementById('aiBreakDuration').value,
        break_window_start: document.getElementById('aiBreakStart').value,
        break_window_end: document.getElementById('aiBreakEnd').value,
        constraints: document.getElementById('aiStoreConstraints').value
    };

    const storeRules = {
        check_absences: document.getElementById('ruleAbsences').checked,
        stagger_breaks: document.getElementById('ruleStagger').checked,
        equity: document.getElementById('ruleEquity').checked,
        rotation_weekends: document.getElementById('ruleWeekends').checked,
        min_staff: document.getElementById('ruleMinStaff').value,
        max_consecutive_days: document.getElementById('ruleMaxDays').value,
        shift_min: document.getElementById('ruleShiftMin').value,
        shift_max: document.getElementById('ruleShiftMax').value
    };

    const employeesConfig = [];
    document.querySelectorAll('.ai-employee-config').forEach(div => {
        const checkbox = div.querySelector('.ai-employee-checkbox');
        // Save even unchecked ones for preferences
        employeesConfig.push({
            id: parseInt(div.dataset.userId),
            checked: checkbox && checkbox.checked,
            hours_per_week: parseInt(div.querySelector('.ai-emp-hours').value) || 35,
            rest_days: parseInt(div.querySelector('.ai-emp-rest').value) || 2,
            school_days: parseInt(div.querySelector('.ai-emp-school').value) || 0,
            constraints: div.querySelector('.ai-emp-constraints').value,
            preference: div.querySelector('.ai-emp-pref').value,
            apprentice_mode: div.querySelector('.ai-emp-apprentice').checked,
            consecutive_rest: div.querySelector('.ai-emp-consecutive').checked
        });
    });

    const config = {
        store: storeConfig,
        rules: storeRules,
        employees: employeesConfig
    };

    const formData = new FormData();
    formData.append('action', 'save_config');
    formData.append('config', JSON.stringify(config));

    fetch('ajax/planning_ai_generator.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                showToast('Configuration sauvegardée avec succès !', 'success');
            } else {
                showToast('Erreur sauvegarde: ' + data.error, 'error');
            }
        })
        .catch(err => showToast('Erreur réseau', 'error'));
}

function loadAIConfiguration() {
    const formData = new FormData();
    formData.append('action', 'get_config');

    fetch('ajax/planning_ai_generator.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success && data.config) {
                const c = data.config;
                
                // Store Config
                if (c.store) {
                    if (c.store.open_time) document.getElementById('aiStoreOpen').value = c.store.open_time;
                    if (c.store.close_time) document.getElementById('aiStoreClose').value = c.store.close_time;
                    if (c.store.break_duration) document.getElementById('aiBreakDuration').value = c.store.break_duration;
                    if (c.store.break_window_start) document.getElementById('aiBreakStart').value = c.store.break_window_start;
                    if (c.store.break_window_end) document.getElementById('aiBreakEnd').value = c.store.break_window_end;
                    if (c.store.constraints) document.getElementById('aiStoreConstraints').value = c.store.constraints;
                    
                    if (c.store.days_open && Array.isArray(c.store.days_open)) {
                        document.querySelectorAll('.ai-day-open').forEach(cb => {
                            cb.checked = c.store.days_open.includes(parseInt(cb.value));
                        });
                    }
                }
                
                // Rules
                if (c.rules) {
                    if(c.rules.hasOwnProperty('check_absences')) document.getElementById('ruleAbsences').checked = c.rules.check_absences;
                    if(c.rules.hasOwnProperty('stagger_breaks')) document.getElementById('ruleStagger').checked = c.rules.stagger_breaks;
                    if(c.rules.hasOwnProperty('equity')) document.getElementById('ruleEquity').checked = c.rules.equity;
                    if(c.rules.hasOwnProperty('rotation_weekends')) document.getElementById('ruleWeekends').checked = c.rules.rotation_weekends;
                    if(c.rules.min_staff) document.getElementById('ruleMinStaff').value = c.rules.min_staff;
                    if(c.rules.max_consecutive_days) document.getElementById('ruleMaxDays').value = c.rules.max_consecutive_days;
                    if(c.rules.shift_min) document.getElementById('ruleShiftMin').value = c.rules.shift_min;
                    if(c.rules.shift_max) document.getElementById('ruleShiftMax').value = c.rules.shift_max;
                }
                
                // Employees
                if (c.employees && Array.isArray(c.employees)) {
                    c.employees.forEach(emp => {
                        const div = document.querySelector(`.ai-employee-config[data-user-id="${emp.id}"]`);
                        if (div) {
                            if (emp.hasOwnProperty('checked')) {
                                const cb = div.querySelector('.ai-employee-checkbox');
                                if (cb) cb.checked = emp.checked;
                            }
                            if (emp.hours_per_week) div.querySelector('.ai-emp-hours').value = emp.hours_per_week;
                            if (emp.rest_days) div.querySelector('.ai-emp-rest').value = emp.rest_days;
                            if (emp.school_days) div.querySelector('.ai-emp-school').value = emp.school_days;
                            if (emp.constraints) div.querySelector('.ai-emp-constraints').value = emp.constraints;
                            if (emp.preference) div.querySelector('.ai-emp-pref').value = emp.preference;
                            if (emp.hasOwnProperty('apprentice_mode')) div.querySelector('.ai-emp-apprentice').checked = emp.apprentice_mode;
                            if (emp.hasOwnProperty('consecutive_rest')) div.querySelector('.ai-emp-consecutive').checked = emp.consecutive_rest;
                            
                            // Update visual summary
                            updateEmployeeSummary(div.querySelector('.ai-emp-hours'));
                        }
                    });
                }
            }
        });
}

function closeAIPlanningModal() {
    document.getElementById('aiPlanningModal').style.display = 'none';
}

function closeAIResultsModal() {
    document.getElementById('aiPlanningResultsModal').style.display = 'none';
}

function generateAIPlanning() {
    // Collect store config
    const storeConfig = {
        open_time: document.getElementById('aiStoreOpen').value,
        close_time: document.getElementById('aiStoreClose').value,
        days_open: [],
        break_duration: document.getElementById('aiBreakDuration').value,
        break_window_start: document.getElementById('aiBreakStart').value,
        break_window_end: document.getElementById('aiBreakEnd').value,
        constraints: document.getElementById('aiStoreConstraints').value
    };
    
    document.querySelectorAll('.ai-day-open:checked').forEach(cb => {
        storeConfig.days_open.push(parseInt(cb.value));
    });
    
    // Collect global rules
    const storeRules = {
        check_absences: document.getElementById('ruleAbsences').checked,
        stagger_breaks: document.getElementById('ruleStagger').checked,
        equity: document.getElementById('ruleEquity').checked,
        rotation_weekends: document.getElementById('ruleWeekends').checked,
        min_staff: document.getElementById('ruleMinStaff').value,
        max_consecutive_days: document.getElementById('ruleMaxDays').value,
        shift_min: document.getElementById('ruleShiftMin').value,
        shift_max: document.getElementById('ruleShiftMax').value
    };
    
    // Collect employees config
    const employeesConfig = [];
    document.querySelectorAll('.ai-employee-config').forEach(div => {
        const checkbox = div.querySelector('.ai-employee-checkbox');
        if (checkbox && checkbox.checked) {
            employeesConfig.push({
                id: parseInt(div.dataset.userId),
                hours_per_week: parseInt(div.querySelector('.ai-emp-hours').value) || 35,
                rest_days: parseInt(div.querySelector('.ai-emp-rest').value) || 2,
                school_days: parseInt(div.querySelector('.ai-emp-school').value) || 0,
                constraints: div.querySelector('.ai-emp-constraints').value,
                preference: div.querySelector('.ai-emp-pref').value,
                apprentice_mode: div.querySelector('.ai-emp-apprentice').checked,
                consecutive_rest: div.querySelector('.ai-emp-consecutive').checked
            });
        }
    });
    
    if (employeesConfig.length === 0) {
        showToast('Veuillez sélectionner au moins un employé', 'error');
        return;
    }
    
    const month = document.getElementById('aiMonth').value;
    
    // Build prompt preview
    const daysNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    const openDays = storeConfig.days_open.map(d => daysNames[d]).join(', ');
    
    let promptPreview = `=== RÈGLES GLOBALES ===
Min Staff: ${storeRules.min_staff}, Max Jours: ${storeRules.max_consecutive_days}
Shift: ${storeRules.shift_min}h - ${storeRules.shift_max}h
Absences: ${storeRules.check_absences ? 'OUI' : 'NON'}, Pauses Décalées: ${storeRules.stagger_breaks ? 'OUI' : 'NON'}
Équité: ${storeRules.equity ? 'OUI' : 'NON'}, W.E: ${storeRules.rotation_weekends ? 'OUI' : 'NON'}

=== CONFIGURATION MAGASIN ===
Horaires: ${storeConfig.open_time} - ${storeConfig.close_time}
Jours d'ouverture: ${openDays}
Pause: ${storeConfig.break_duration} (${storeConfig.break_window_start} - ${storeConfig.break_window_end})
${storeConfig.constraints ? 'Contraintes: ' + storeConfig.constraints : ''}

=== CONFIGURATION EMPLOYÉS ===
`;
    employeesConfig.forEach(emp => {
        const empName = document.querySelector(`.ai-employee-config[data-user-id="${emp.id}"] strong`)?.textContent || 'Employé #' + emp.id;
        promptPreview += `• ${empName}: ${emp.hours_per_week}h/sem, ${emp.rest_days}j repos`;
        if (emp.school_days > 0) promptPreview += `, ${emp.school_days}j école`;
        if (emp.apprentice_mode) promptPreview += ' [APPRENTI]';
        if (emp.preference) promptPreview += ` [Pref: ${emp.preference}]`;
        if (emp.constraints) promptPreview += ` - ${emp.constraints}`;
        promptPreview += '\n';
    });
    
    promptPreview += `\n=== MOIS À PLANIFIER ===\n${month}`;
    
    // Show loading with prompt preview
    closeAIPlanningModal();
    document.getElementById('aiPlanningResultsModal').style.display = 'flex';
    document.getElementById('aiResultsLoading').style.display = 'block';
    document.getElementById('aiResultsContent').style.display = 'none';
    document.getElementById('aiResultsMonth').textContent = month;
    document.getElementById('aiPromptText').textContent = promptPreview;
    
    // Call API
    const formData = new FormData();
    formData.append('action', 'generate');
    formData.append('month', month);
    formData.append('store_config', JSON.stringify(storeConfig));
    formData.append('employees_config', JSON.stringify(employeesConfig));
    formData.append('store_rules', JSON.stringify(storeRules));
    
    fetch('ajax/planning_ai_generator.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('aiResultsLoading').style.display = 'none';
        
        if (data.success) {
            aiGeneratedSchedules = data.schedules;
            displayAIResults(data.schedules);
            document.getElementById('aiResultsContent').style.display = 'block';
        } else {
            showToast(data.error || 'Erreur de génération', 'error');
            if (data.ai_response) {
                console.log('AI Response:', data.ai_response);
            }
            closeAIResultsModal();
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('aiResultsLoading').style.display = 'none';
        showToast('Erreur de connexion', 'error');
        closeAIResultsModal();
    });
}

// ==========================================
// AI RESULT GRID VIEW IMPLEMENTATION
// ==========================================
let aiCurrentWeekStart = null;

function displayAIResults(schedules) {
    if (!schedules || schedules.length === 0) return;
    
    aiGeneratedSchedules = schedules;
    
    // Initialiser sur la première semaine disponible
    const firstDate = new Date(schedules[0].date);
    aiCurrentWeekStart = getMonday(firstDate);
    
    renderAIWeeklyGrid();
    
    document.getElementById('aiResultsLoading').style.display = 'none';
    document.getElementById('aiResultsContent').style.display = 'block';
}

function navigateAIWeek(offset) {
    if (!aiCurrentWeekStart) return;
    const newDate = new Date(aiCurrentWeekStart);
    newDate.setDate(newDate.getDate() + (offset * 7));
    aiCurrentWeekStart = newDate;
    renderAIWeeklyGrid();
}

function renderAIWeeklyGrid() {
    const tbody = document.getElementById('aiResultsTableBody');
    const headerRow = document.getElementById('aiGridHeaders');
    const label = document.getElementById('aiCurrentWeekLabel');
    
    // 1. Update Headers & Label
    const weekEnd = new Date(aiCurrentWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    const options = {day: 'numeric', month: 'long', year: 'numeric'};
    label.textContent = `Semaine du ${aiCurrentWeekStart.toLocaleDateString('fr-FR', options)} au ${weekEnd.toLocaleDateString('fr-FR', options)}`;
    
    let headerHTML = '<th style="padding: 12px; text-align: left; color: var(--day-text);">Employé</th>';
    const currentWeekDates = [];
    
    for (let i = 0; i < 7; i++) {
        const d = new Date(aiCurrentWeekStart);
        d.setDate(d.getDate() + i);
        const dayName = d.toLocaleDateString('fr-FR', {weekday: 'short'});
        const dayDate = d.toLocaleDateString('fr-FR', {day: 'numeric', month: 'numeric'});
        const dateStr = d.toISOString().split('T')[0];
        currentWeekDates.push(dateStr);
        
        let bgColor = '#8b5cf6'; // Violet par défaut
        if(i >= 5) bgColor = '#f97316'; // Orange weekend
        
        headerHTML += `
            <th style="padding: 10px; text-align: center; color: white; background: ${bgColor}; border-radius: 8px; margin: 0 4px; min-width: 100px;">
                <div style="text-transform: capitalize;">${dayName}</div>
                <div style="font-size: 0.8rem; opacity: 0.9;">${dayDate}</div>
            </th>`;
    }
    headerRow.innerHTML = headerHTML;
    
    // 2. Group Schedules by User
    let html = '';
    
    // Utiliser allUsers (injecté via PHP)
    allUsers.forEach(user => {
        const userName = user.full_name || user.username;
        const userId = parseInt(user.id);
        
        // Filter AI schedules for this user in this week
        const userWeekSchedules = {};
        aiGeneratedSchedules.forEach(s => {
            if (parseInt(s.user_id) === userId) {
                userWeekSchedules[s.date] = s;
            }
        });
        
        html += `<tr style="background: var(--day-card-bg); margin-bottom: 5px;">
            <td style="padding: 15px; font-weight: 600; color: var(--day-text); border-radius: 8px 0 0 8px;">
                ${userName}
            </td>`;
            
        currentWeekDates.forEach(dateStr => {
            const s = userWeekSchedules[dateStr];
            
            if (s) {
                // Créneau existant
                const timeText = `${s.start_time}<br>${s.end_time}`;
                html += `
                    <td style="padding: 4px;">
                        <div style="background: #22c55e; color: white; padding: 8px; border-radius: 6px; text-align: center; font-size: 0.85rem; font-weight: 600; box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);">
                            ${timeText}
                        </div>
                    </td>`;
            } else {
                // Pas de créneau (Empty slot)
                html += `
                    <td style="padding: 4px;">
                        <div style="background: rgba(0,0,0,0.05); color: var(--day-text-light); padding: 8px; border-radius: 6px; text-align: center; font-size: 1.2rem; min-height: 48px; display: flex; align-items: center; justify-content: center;">
                            +
                        </div>
                    </td>`;
            }
        });
        
        html += '</tr>';
    });
    
    tbody.innerHTML = html;
}

function updateAISchedule(idx, field, value) {
    if (aiGeneratedSchedules[idx]) {
        aiGeneratedSchedules[idx][field] = value;
    }
}

function removeAISchedule(idx) {
    aiGeneratedSchedules.splice(idx, 1);
    displayAIResults(aiGeneratedSchedules);
}

function modifyAIPlanning() {
    const message = document.getElementById('aiModifyMessage').value.trim();
    if (!message) {
        showToast('Veuillez entrer une modification', 'error');
        return;
    }
    
    // Show loading
    document.getElementById('aiResultsLoading').style.display = 'block';
    document.getElementById('aiResultsContent').style.display = 'none';
    
    const formData = new FormData();
    formData.append('action', 'modify');
    formData.append('message', message);
    formData.append('current_schedules', JSON.stringify(aiGeneratedSchedules));
    formData.append('month', document.getElementById('aiResultsMonth').textContent);
    
    fetch('ajax/planning_ai_generator.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('aiResultsLoading').style.display = 'none';
        document.getElementById('aiResultsContent').style.display = 'block';
        
        if (data.success) {
            aiGeneratedSchedules = data.schedules;
            displayAIResults(data.schedules);
            document.getElementById('aiModifyMessage').value = '';
            showToast('Planning modifié !', 'success');
        } else {
            showToast(data.error || 'Erreur de modification', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('aiResultsLoading').style.display = 'none';
        document.getElementById('aiResultsContent').style.display = 'block';
        showToast('Erreur de connexion', 'error');
    });
}

function saveAIPlanning() {
    if (aiGeneratedSchedules.length === 0) {
        showToast('Aucun créneau à enregistrer', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save');
    formData.append('schedules', JSON.stringify(aiGeneratedSchedules));
    
    fetch('ajax/planning_ai_generator.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeAIResultsModal();
            loadSchedules(); // Refresh the planning view
        } else {
            showToast(data.error || 'Erreur d\'enregistrement', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Erreur de connexion', 'error');
    });
}

function getWeekString(d) {
    d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay()||7));
    var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
    var weekNo = Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7);
    return d.getUTCFullYear() + "-W" + (weekNo < 10 ? '0' : '') + weekNo;
}

// Modal Copier Semaine
function openCopyWeekModal() {
    document.getElementById('copyWeekModal').style.display = 'flex';
}

function closeCopyWeekModal() {
    document.getElementById('copyWeekModal').style.display = 'none';
}

function copyWeek() {
    const sourceWeek = document.getElementById('sourceWeek').value;
    const targetWeek = document.getElementById('targetWeek').value;
    
    if (!sourceWeek || !targetWeek) {
        showToast('Veuillez sélectionner les deux semaines', 'error');
        return;
    }
    
    // Convertir format week en date
    const [sYear, sWeek] = sourceWeek.split('-W');
    const [tYear, tWeek] = targetWeek.split('-W');
    const sourceDate = getDateOfWeek(parseInt(sWeek), parseInt(sYear));
    const targetDate = getDateOfWeek(parseInt(tWeek), parseInt(tYear));
    
    const formData = new FormData();
    formData.append('action', 'copy_week');
    formData.append('source_week', formatDate(sourceDate));
    formData.append('target_week', formatDate(targetDate));
    
    fetch('ajax/planning_handler.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeCopyWeekModal();
                loadSchedules();
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        });
}

function getDateOfWeek(week, year) {
    const simple = new Date(year, 0, 1 + (week - 1) * 7);
    const dow = simple.getDay();
    const ISOweekStart = simple;
    if (dow <= 4) ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1);
    else ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay());
    return ISOweekStart;
}

// Vue par employé
function loadEmployeeSchedule(userId) {
    selectedEmployeeId = userId;
    if (!userId) {
        document.getElementById('employeeScheduleContent').style.display = 'none';
        return;
    }
    document.getElementById('employeeScheduleContent').style.display = 'block';
    updateEmployeeMonthLabel();
    // TODO: Charger et afficher le calendrier mensuel
}

function navigateEmployeeMonth(direction) {
    currentEmployeeMonth.setMonth(currentEmployeeMonth.getMonth() + direction);
    updateEmployeeMonthLabel();
    // TODO: Recharger le calendrier
}

function updateEmployeeMonthLabel() {
    const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    document.getElementById('employeeMonthLabel').textContent = 
        `${months[currentEmployeeMonth.getMonth()]} ${currentEmployeeMonth.getFullYear()}`;
}

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; bottom: 20px; right: 20px; padding: 15px 25px;
        background: ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white; border-radius: 10px; z-index: 99999; font-weight: 500;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2); animation: slideIn 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Initialiser le planning au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Charger le planning si l'onglet est actif
    const planningTab = document.querySelector('[data-tab="planning"]');
    if (planningTab) {
        planningTab.addEventListener('click', () => setTimeout(loadSchedules, 100));
    }
});


</script>

<!-- Modal Approbation avec Retard -->
<div id="approveLatencyModal" class="planning-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 10000; justify-content: center; align-items: center;">
    <div class="modal-content" style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
        <h4 style="color: var(--day-text); margin: 0 0 1.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-user-clock"></i> Validation du pointage
        </h4>
        
        <input type="hidden" id="latencyRequestId">
        
        <div style="background: rgba(59, 130, 246, 0.05); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: var(--day-text-light);">Employé:</span>
                <strong style="color: var(--day-text);" id="latencyUserName">-</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: var(--day-text-light);">Heure d'arrivée:</span>
                <input type="time" id="latencyClockIn" class="form-control" style="width: auto; padding: 4px 8px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text); font-weight: bold;">
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--day-text-light);">Début prévu:</span>
                <strong style="color: var(--day-text);" id="latencyScheduledStart">-</strong>
            </div>
        </div>

        <div id="latencyDetectedAlert" style="display: none; background: #fff7ed; border-left: 4px solid #f59e0b; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px;">
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-top: 3px;"></i>
                <div>
                    <strong style="color: #9a3412; display: block; margin-bottom: 4px;">Retard détecté</strong>
                    <span style="color: #c2410c; font-size: 0.9em;">L'employé est arrivé <span id="latencyMinutesDisplay">0</span> minutes après l'heure prévue.</span>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; background: var(--day-bg); padding: 10px; border-radius: 8px; border: 1px solid var(--day-border);">
                <input type="checkbox" id="createLatencyEvent" onchange="toggleLatencyFields()" style="accent-color: #f59e0b; width: 18px; height: 18px;">
                <span style="color: var(--day-text); font-weight: 500;">Enregistrer un événement "Retard"</span>
            </label>
        </div>

        <div id="latencyFields" style="display: none; padding-left: 1rem; border-left: 2px solid var(--day-border); margin-bottom: 1.5rem;">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Durée du retard (minutes)</label>
                <input type="number" id="latencyDuration" min="1" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Commentaire (facultatif)</label>
                <input type="text" id="latencyComment" placeholder="Raison du retard..." style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button onclick="confirmApprovalWithLatency()" style="flex: 1; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                <i class="fas fa-check"></i> Valider le pointage
            </button>
            <button onclick="closeLatencyModal()" style="flex: 1; background: var(--day-text-light); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                Annuler
            </button>
        </div>
    </div>
</div>

<!-- Modal Ajout Pointage Manuel -->
<div id="manualEntryModal" class="planning-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 10000; justify-content: center; align-items: center;">
    <div class="modal-content" style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
        <h4 style="color: var(--day-text); margin: 0 0 1.5rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-plus-circle"></i> Ajouter un pointage manuellement
        </h4>
        
        <form id="manualEntryForm" onsubmit="saveManualEntry(event)">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Employé</label>
                <select id="manualEntryUser" class="form-select" required style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                    <option value="">Sélectionner un employé...</option>
                    <?php foreach ($all_users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Date</label>
                <input type="date" id="manualEntryDate" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Heure d'arrivée</label>
                    <input type="time" id="manualEntryStart" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Heure de départ</label>
                    <input type="time" id="manualEntryEnd" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                </div>
            </div>
            
            <div style="margin-bottom: 1rem; padding: 1rem; background: rgba(59, 130, 246, 0.05); border-radius: 8px;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500; font-size: 0.9em;">Pause (Optionnel)</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <input type="time" id="manualEntryBreakStart" class="form-control" placeholder="Début" style="width: 100%; padding: 8px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                    </div>
                    <div>
                        <input type="time" id="manualEntryBreakEnd" class="form-control" placeholder="Fin" style="width: 100%; padding: 8px; border: 1px solid var(--day-border); border-radius: 6px; background: var(--day-card-bg); color: var(--day-text);">
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" style="flex: 1; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" onclick="closeManualEntryModal()" style="flex: 1; background: var(--day-text-light); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openManualEntryModal() {
    document.getElementById('manualEntryModal').style.display = 'flex';
    document.getElementById('manualEntryForm').reset();
    document.getElementById('manualEntryDate').valueAsDate = new Date();
}

function closeManualEntryModal() {
    document.getElementById('manualEntryModal').style.display = 'none';
}

function saveManualEntry(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'manual_entry');
    formData.append('user_id', document.getElementById('manualEntryUser').value);
    formData.append('date', document.getElementById('manualEntryDate').value);
    formData.append('start_time', document.getElementById('manualEntryStart').value);
    formData.append('end_time', document.getElementById('manualEntryEnd').value);
    formData.append('break_start', document.getElementById('manualEntryBreakStart').value);
    formData.append('break_end', document.getElementById('manualEntryBreakEnd').value);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Pointage ajouté avec succès', 'success');
            closeManualEntryModal();
            location.reload(); // Refresh to show new entry
        } else {
            showToast('Erreur: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Erreur de communication', 'error');
    });
}
</script>
</body>
</html>