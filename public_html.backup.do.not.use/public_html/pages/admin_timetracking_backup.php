<?php
/**
 * Interface administrateur améliorée pour le système de pointage
 * Version avec onglet Paramètres pour les créneaux horaires
 */

// Ce fichier est inclus via le routage GeekBoard, donc les sessions sont déjà chargées
// Mais nous devons nous assurer que les fichiers de configuration sont inclus

// S'assurer que les fichiers de configuration sont chargés
if (!function_exists('getShopDBConnection')) {
    require_once BASE_PATH . '/config/database.php';
}

// Vérifier les droits admin 
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-ban"></i> Accès refusé</h4>
        <p>Cette page est réservée aux administrateurs.</p>
        <p><strong>Debug:</strong> Role actuel = ' . ($_SESSION['user_role'] ?? 'non défini') . '</p>
    </div>';
    return;
}

$current_user_id = $_SESSION['user_id'];

// Obtenir la connexion à la base de données
try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Connexion à la base de données échouée");
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-database"></i> Erreur de base de données</h4>
        <p>Impossible de se connecter à la base de données: ' . $e->getMessage() . '</p>
    </div>';
    return;
}

// Fonction pour vérifier si un pointage est dans les créneaux autorisés
function isTimeSlotValid($user_id, $clock_time, $shop_pdo) {
    $time_only = date('H:i:s', strtotime($clock_time));
    $hour = intval(date('H', strtotime($clock_time)));
    
    // Déterminer le type de créneau (matin ou après-midi)
    $slot_type = ($hour < 13) ? 'morning' : 'afternoon';
    
    // Chercher d'abord les règles spécifiques à l'utilisateur
    $stmt = $shop_pdo->prepare("
        SELECT start_time, end_time 
        FROM time_slots 
        WHERE user_id = ? AND slot_type = ? AND is_active = TRUE
    ");
    $stmt->execute([$user_id, $slot_type]);
    $user_slot = $stmt->fetch();
    
    // Si pas de règle spécifique, utiliser la règle globale
    if (!$user_slot) {
        $stmt = $shop_pdo->prepare("
            SELECT start_time, end_time 
            FROM time_slots 
            WHERE user_id IS NULL AND slot_type = ? AND is_active = TRUE
        ");
        $stmt->execute([$slot_type]);
        $user_slot = $stmt->fetch();
    }
    
    if ($user_slot) {
        return ($time_only >= $user_slot['start_time'] && $time_only <= $user_slot['end_time']);
    }
    
    return false; // Pas de créneau défini = non autorisé
}

// Traitement des actions admin (API endpoint)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'save_global_slots':
                // Sauvegarder les créneaux globaux
                $morning_start = $_POST['morning_start'];
                $morning_end = $_POST['morning_end'];
                $afternoon_start = $_POST['afternoon_start'];
                $afternoon_end = $_POST['afternoon_end'];
                
                // Mise à jour créneau matin global
                $stmt = $shop_pdo->prepare("
                    INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                    VALUES (NULL, 'morning', ?, ?, TRUE)
                    ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)
                ");
                $stmt->execute([$morning_start, $morning_end]);
                
                // Mise à jour créneau après-midi global
                $stmt = $shop_pdo->prepare("
                    INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                    VALUES (NULL, 'afternoon', ?, ?, TRUE)
                    ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)
                ");
                $stmt->execute([$afternoon_start, $afternoon_end]);
                
                $response = ['success' => true, 'message' => 'Créneaux globaux sauvegardés'];
                break;
                
            case 'save_user_slots':
                // Sauvegarder les créneaux spécifiques à un utilisateur
                $user_id = intval($_POST['user_id']);
                $morning_start = $_POST['user_morning_start'];
                $morning_end = $_POST['user_morning_end'];
                $afternoon_start = $_POST['user_afternoon_start'];
                $afternoon_end = $_POST['user_afternoon_end'];
                
                // Supprimer les anciens créneaux de cet utilisateur
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Ajouter les nouveaux créneaux
                if ($morning_start && $morning_end) {
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                        VALUES (?, 'morning', ?, ?, TRUE)
                    ");
                    $stmt->execute([$user_id, $morning_start, $morning_end]);
                }
                
                if ($afternoon_start && $afternoon_end) {
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                        VALUES (?, 'afternoon', ?, ?, TRUE)
                    ");
                    $stmt->execute([$user_id, $afternoon_start, $afternoon_end]);
                }
                
                $response = ['success' => true, 'message' => 'Créneaux utilisateur sauvegardés'];
                break;
                
            case 'remove_user_slots':
                // Supprimer les créneaux spécifiques d'un utilisateur (revenir aux globaux)
                $user_id = intval($_POST['user_id']);
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $response = ['success' => true, 'message' => 'Créneaux utilisateur supprimés, utilisation des créneaux globaux'];
                break;
                
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
                
            case 'reject_entry':
                $entry_id = intval($_POST['entry_id']);
                $reason = $_POST['reason'] ?? 'Aucune raison spécifiée';
                $stmt = $shop_pdo->prepare("
                    UPDATE time_tracking 
                    SET admin_approved = FALSE, 
                        admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nRejected by admin at ', NOW(), ' - Reason: ', ?),
                        status = 'rejected'
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$reason, $entry_id])) {
                    $response = ['success' => true, 'message' => 'Entrée rejetée'];
                } else {
                    $response = ['success' => false, 'message' => 'Erreur lors du rejet'];
                }
                break;
                
            case 'send_notification':
                $user_id = intval($_POST['user_id']);
                $message = $_POST['message'];
                $response = ['success' => true, 'message' => 'Notification simulée envoyée'];
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
    
    // Retourner la réponse en JSON pour les requêtes AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Récupérer les données pour l'affichage avec gestion d'erreurs
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_user = $_GET['user'] ?? '';
$filter_status = $_GET['status'] ?? '';

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

try {
    // Vérifier d'abord si la table time_tracking existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_tracking'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo '<div class="alert alert-warning">
            <h4><i class="fas fa-table"></i> Table manquante</h4>
            <p>La table <code>time_tracking</code> n\'existe pas dans cette base de données.</p>
            <p>Veuillez d\'abord créer la table de pointage pour utiliser cette fonctionnalité.</p>
        </div>';
        return;
    }

    // Vérifier si la table time_slots existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_slots'");
    $stmt->execute();
    $slots_table_exists = $stmt->fetch();
    
    if (!$slots_table_exists) {
        // Créer la table time_slots si elle n'existe pas
        $sql = file_get_contents(__DIR__ . '/create_time_slots_table.sql');
        $shop_pdo->exec($sql);
    }

    // Récupérer tous les utilisateurs pour les filtres
    $stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users WHERE role != 'admin' ORDER BY full_name");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Utilisateurs actifs (pointés en ce moment)
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

    // Données pour les graphiques (derniers 7 jours)
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
            'employees' => $day_stats['employees'] ?? 0,
            'hours' => round($day_stats['total_hours'] ?? 0, 1),
            'sessions' => $day_stats['sessions'] ?? 0
        ];
    }

    // Top performers de la semaine
    $stmt = $shop_pdo->prepare("
        SELECT u.full_name, 
               COUNT(*) as sessions,
               COALESCE(SUM(tt.work_duration), 0) as total_hours,
               COALESCE(AVG(tt.work_duration), 0) as avg_hours,
               COUNT(CASE WHEN DATE(tt.clock_in) = CURDATE() THEN 1 END) as today_sessions
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE tt.status = 'completed' 
        AND DATE(tt.clock_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY tt.user_id, u.full_name
        HAVING total_hours > 0
        ORDER BY total_hours DESC
        LIMIT 5
    ");
    $stmt->execute();
    $top_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Données pour le calendrier (mois actuel)
    $calendar_month = $_GET['calendar_month'] ?? date('Y-m');
    $calendar_user_filter = $_GET['calendar_user'] ?? '';
    
    $calendar_query = "
        SELECT tt.*, u.full_name, u.username,
               DATE(tt.clock_in) as work_date,
               TIME(tt.clock_in) as start_time,
               TIME(tt.clock_out) as end_time,
               CASE 
                   WHEN tt.status = 'completed' THEN 'completed'
                   WHEN tt.status IN ('active', 'break') THEN 'active'
                   ELSE 'other'
               END as display_status
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE DATE(tt.clock_in) LIKE ?
    ";
    
    $calendar_params = [$calendar_month . '%'];
    
    if ($calendar_user_filter) {
        $calendar_query .= " AND tt.user_id = ?";
        $calendar_params[] = $calendar_user_filter;
    }
    
    $calendar_query .= " ORDER BY tt.clock_in DESC";
    
    $stmt = $shop_pdo->prepare($calendar_query);
    $stmt->execute($calendar_params);
    $calendar_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Demandes à approuver (incluant les pointages hors créneaux)
    $stmt = $shop_pdo->prepare("
        SELECT tt.*, u.full_name, u.username,
               DATE(tt.clock_in) as work_date,
               TIME(tt.clock_in) as start_time,
               TIME(tt.clock_out) as end_time,
               TIMESTAMPDIFF(MINUTE, tt.clock_in, tt.clock_out) / 60.0 as calculated_hours,
               COALESCE(tt.auto_approved, 0) as auto_approved,
               tt.approval_reason
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE tt.status = 'completed' AND tt.admin_approved = 0
        ORDER BY tt.created_at DESC
        LIMIT 50
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

} catch (Exception $e) {
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-exclamation-triangle"></i> Erreur de données</h4>
        <p>Erreur lors de la récupération des données: ' . $e->getMessage() . '</p>
    </div>';
    return;
}
?>

<!-- CSS pour forcer une colonne unique -->
<style>
/* Reset des styles qui pourraient causer le problème de colonnes */
.admin-timetracking-container {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    float: none !important;
    clear: both !important;
}

.admin-timetracking-container * {
    box-sizing: border-box;
}

/* Variables CSS modernes */
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

/* Navigation moderne */
.admin-nav {
    background: linear-gradient(135deg, var(--primary-color), #004499);
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
    width: 100%;
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
    padding: 0.75rem 1.25rem;
    font-weight: 500;
}

.nav-tabs-custom .nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.nav-tabs-custom .nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
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
    margin-bottom: 1rem;
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

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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

/* Cartes utilisateurs actifs */
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
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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

/* Graphiques */
.chart-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    margin-bottom: 2rem;
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

.alert-item.warning {
    border-left-color: var(--warning-color);
    background: linear-gradient(90deg, #fff3cd, #ffeaa7);
}

.alert-item:hover {
    transform: translateX(4px);
    box-shadow: var(--box-shadow);
}

/* Calendrier */
.calendar-filters {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.calendar-entry {
    background: white;
    border-radius: 8px;
    border-left: 4px solid;
    margin-bottom: 0.5rem;
    transition: var(--transition);
}

.calendar-entry.completed {
    border-left-color: var(--success-color);
}

.calendar-entry.active {
    border-left-color: var(--warning-color);
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
}

.calendar-entry:hover {
    transform: translateX(4px);
    box-shadow: var(--box-shadow);
}

/* Demandes à approuver */
.approval-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    transition: var(--transition);
}

.approval-item:hover {
    box-shadow: var(--box-shadow);
    transform: translateY(-2px);
}

.approval-item.out-of-hours {
    border-left: 4px solid var(--warning-color);
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
}

/* Paramètres */
.settings-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
}

.time-slot-config {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #dee2e6;
}

.user-slot-item {
    background: white;
    border-radius: 8px;
    border-left: 4px solid var(--info-color);
    margin-bottom: 0.5rem;
    transition: var(--transition);
}

.user-slot-item:hover {
    transform: translateX(4px);
    box-shadow: var(--box-shadow);
}

/* Badges et status */
.duration-display {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-weight: bold;
    font-size: 1.1em;
    padding: 0.4rem 0.8rem;
    background: rgba(0, 102, 204, 0.1);
    border-radius: 6px;
    display: inline-block;
    border: 2px solid rgba(0, 102, 204, 0.2);
}

.pulse-animation {
    animation: pulse 2s infinite;
}

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
        padding: 1rem;
    }
    
    .nav-tabs-custom .nav-link {
        margin-right: 0.25rem;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
}
</style>

<!-- Container principal en une seule colonne -->
<div class="admin-timetracking-container">
    <div class="w-100">
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
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button" role="tab">
                        <i class="fas fa-calendar-alt"></i> Calendrier
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals" type="button" role="tab">
                        <i class="fas fa-check-double"></i> Demandes à approuver
                        <?php if (count($pending_requests) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo count($pending_requests); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                        <i class="fas fa-cog"></i> Paramètres
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">
                        <i class="fas fa-bell"></i> Alertes
                        <?php if (count($alerts) > 0): ?>
                        <span class="badge bg-warning ms-1"><?php echo count($alerts); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenu des onglets en pleine largeur -->
        <div class="tab-content w-100" id="adminTabsContent">
            
            <!-- Dashboard Principal -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <!-- KPIs principaux en une ligne -->
                <div class="row w-100 mb-4">
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

                <!-- Graphiques en une ligne -->
                <div class="row w-100 mb-4">
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

                <!-- Top performers en une ligne -->
                <div class="row w-100">
                    <div class="col-lg-6">
                        <div class="card stats-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Performers (7 jours)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($top_performers)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Aucune donnée disponible</p>
                                </div>
                                <?php else: ?>
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
                                <?php endif; ?>
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
                                        <h4 class="text-info"><?php echo $stats['active_employees'] > 0 ? round(($stats['currently_working'] / $stats['active_employees']) * 100) : 0; ?>%</h4>
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
                <div class="w-100">
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
                    <div class="row w-100" id="activeUsersContainer">
                        <?php foreach ($active_users as $user): ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="active-user-card <?php 
                                echo $user['status'] === 'break' ? 'break-user-card' : 
                                    ($user['duration_status'] === 'overtime' ? 'overtime-user-card' : ''); 
                            ?>">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 48px; height: 48px; font-size: 1.2rem;">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                            </div>
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

            <!-- Calendrier -->
            <div class="tab-pane fade" id="calendar" role="tabpanel">
                <div class="w-100">
                    <!-- Filtres du calendrier -->
                    <div class="calendar-filters">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="calendarMonth" class="form-label">
                                    <i class="fas fa-calendar"></i> Mois
                                </label>
                                <input type="month" class="form-control" id="calendarMonth" 
                                       value="<?php echo $calendar_month; ?>" onchange="filterCalendar()">
                            </div>
                            <div class="col-md-6">
                                <label for="calendarUser" class="form-label">
                                    <i class="fas fa-user"></i> Employé
                                </label>
                                <select class="form-select" id="calendarUser" onchange="filterCalendar()">
                                    <option value="">Tous les employés</option>
                                    <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo $calendar_user_filter == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Résultats du calendrier -->
                    <div class="row w-100">
                        <div class="col-12">
                            <div class="card stats-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-alt"></i> Historique des pointages
                                        <span class="badge bg-light text-dark ms-2"><?php echo count($calendar_data); ?> entrées</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($calendar_data)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h4 class="text-muted">Aucun pointage trouvé</h4>
                                        <p class="text-muted">Aucun données pour les critères sélectionnés</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="row">
                                        <?php 
                                        $grouped_by_date = [];
                                        foreach ($calendar_data as $entry) {
                                            $grouped_by_date[$entry['work_date']][] = $entry;
                                        }
                                        ?>
                                        <?php foreach ($grouped_by_date as $date => $entries): ?>
                                        <div class="col-12 mb-4">
                                            <h6 class="text-primary border-bottom pb-2">
                                                <i class="fas fa-calendar-day"></i> 
                                                <?php echo date('l d F Y', strtotime($date)); ?>
                                                <span class="badge bg-secondary ms-2"><?php echo count($entries); ?> pointages</span>
                                            </h6>
                                            <?php foreach ($entries as $entry): ?>
                                            <div class="calendar-entry <?php echo $entry['display_status']; ?> p-3 mb-2">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                                 style="width: 40px; height: 40px;">
                                                                <?php echo strtoupper(substr($entry['full_name'], 0, 1)); ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($entry['full_name']); ?></h6>
                                                            <small class="text-muted">
                                                                <?php echo $entry['start_time']; ?> 
                                                                <?php if ($entry['end_time']): ?>
                                                                → <?php echo $entry['end_time']; ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge bg-<?php 
                                                            echo $entry['display_status'] === 'completed' ? 'success' : 
                                                                ($entry['display_status'] === 'active' ? 'warning' : 'secondary'); 
                                                        ?>">
                                                            <?php 
                                                            if ($entry['display_status'] === 'completed') echo 'Terminé';
                                                            elseif ($entry['display_status'] === 'active') echo 'En cours';
                                                            else echo ucfirst($entry['status']);
                                                            ?>
                                                        </span>
                                                        <?php if ($entry['work_duration']): ?>
                                                        <div class="duration-display mt-1">
                                                            <?php echo number_format($entry['work_duration'], 1); ?>h
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demandes à approuver -->
            <div class="tab-pane fade" id="approvals" role="tabpanel">
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-check-double text-warning"></i> Demandes à approuver</h3>
                        <span class="badge bg-warning text-dark fs-6">
                            <?php echo count($pending_requests); ?> en attente
                        </span>
                    </div>
                    
                    <?php if (empty($pending_requests)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success">Toutes les demandes sont traitées</h4>
                        <p class="text-muted">Aucune demande d'approbation en attente</p>
                    </div>
                    <?php else: ?>
                    <div class="row w-100">
                        <?php foreach ($pending_requests as $request): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="approval-item p-3 <?php echo $request['approval_reason'] ? 'out-of-hours' : ''; ?>">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 48px; height: 48px; font-size: 1.2rem;">
                                                <?php echo strtoupper(substr($request['full_name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($request['full_name']); ?></h6>
                                            <small class="text-muted">@<?php echo htmlspecialchars($request['username']); ?></small>
                                            <?php if ($request['approval_reason']): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Hors créneaux
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning">En attente</span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Date</small>
                                            <strong><?php echo date('d/m/Y', strtotime($request['work_date'])); ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Durée</small>
                                            <strong><?php echo number_format($request['calculated_hours'], 1); ?>h</strong>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Début</small>
                                            <strong><?php echo $request['start_time']; ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Fin</small>
                                            <strong><?php echo $request['end_time'] ?? 'N/A'; ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($request['approval_reason']): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Raison de la demande d'approbation</small>
                                    <div class="bg-warning bg-opacity-25 p-2 rounded" style="font-size: 0.9em;">
                                        <i class="fas fa-info-circle text-warning"></i> 
                                        <?php echo htmlspecialchars($request['approval_reason']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($request['admin_notes']): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Notes</small>
                                    <div class="bg-light p-2 rounded" style="font-size: 0.9em;">
                                        <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success btn-sm flex-fill" 
                                            onclick="approveEntry(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['full_name']); ?>')">
                                        <i class="fas fa-check"></i> Approuver
                                    </button>
                                    <button class="btn btn-danger btn-sm flex-fill" 
                                            onclick="rejectEntry(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['full_name']); ?>')">
                                        <i class="fas fa-times"></i> Rejeter
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" 
                                            onclick="viewDetails(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paramètres -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-cog text-primary"></i> Paramètres des créneaux horaires</h3>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Les pointages dans les créneaux sont approuvés automatiquement
                        </small>
                    </div>
                    
                    <!-- Créneaux globaux -->
                    <div class="settings-section">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-globe"></i> Créneaux globaux (par défaut)</h5>
                        </div>
                        <div class="card-body">
                            <form id="globalSlotsForm" onsubmit="saveGlobalSlots(event)">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="time-slot-config">
                                            <h6><i class="fas fa-sun text-warning"></i> Matin</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="form-label">Début</label>
                                                    <input type="time" class="form-control" name="morning_start" 
                                                           value="<?php echo substr($global_slots['morning']['start_time'] ?? '08:00:00', 0, 5); ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Fin</label>
                                                    <input type="time" class="form-control" name="morning_end" 
                                                           value="<?php echo substr($global_slots['morning']['end_time'] ?? '12:30:00', 0, 5); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="time-slot-config">
                                            <h6><i class="fas fa-moon text-info"></i> Après-midi</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="form-label">Début</label>
                                                    <input type="time" class="form-control" name="afternoon_start" 
                                                           value="<?php echo substr($global_slots['afternoon']['start_time'] ?? '14:00:00', 0, 5); ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Fin</label>
                                                    <input type="time" class="form-control" name="afternoon_end" 
                                                           value="<?php echo substr($global_slots['afternoon']['end_time'] ?? '19:00:00', 0, 5); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Sauvegarder les créneaux globaux
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Créneaux spécifiques par utilisateur -->
                    <div class="settings-section">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Créneaux spécifiques par employé</h5>
                        </div>
                        <div class="card-body">
                            <!-- Formulaire pour ajouter un nouveau créneau utilisateur -->
                            <div class="bg-light p-3 rounded mb-4">
                                <h6><i class="fas fa-plus"></i> Ajouter un créneau spécifique</h6>
                                <form id="userSlotsForm" onsubmit="saveUserSlots(event)">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">Employé</label>
                                            <select class="form-select" name="user_id" required>
                                                <option value="">Sélectionner un employé</option>
                                                <?php foreach ($all_users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Matin début</label>
                                            <input type="time" class="form-control" name="user_morning_start">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Matin fin</label>
                                            <input type="time" class="form-control" name="user_morning_end">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">A-midi début</label>
                                            <input type="time" class="form-control" name="user_afternoon_start">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">A-midi fin</label>
                                            <input type="time" class="form-control" name="user_afternoon_end">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Liste des créneaux spécifiques existants -->
                            <?php if (empty($user_slots)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <h6 class="text-muted">Aucun créneau spécifique configuré</h6>
                                <p class="text-muted">Tous les employés utilisent les créneaux globaux</p>
                            </div>
                            <?php else: ?>
                            <h6 class="mb-3">Créneaux spécifiques configurés</h6>
                            <?php foreach ($user_slots as $user_id => $user_data): ?>
                            <div class="user-slot-item p-3 mb-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user_data['full_name']); ?></h6>
                                        <div class="small text-muted">
                                            <?php if (isset($user_data['slots']['morning'])): ?>
                                            <span class="me-3">
                                                <i class="fas fa-sun text-warning"></i> 
                                                Matin: <?php echo substr($user_data['slots']['morning']['start_time'], 0, 5); ?> - 
                                                <?php echo substr($user_data['slots']['morning']['end_time'], 0, 5); ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if (isset($user_data['slots']['afternoon'])): ?>
                                            <span>
                                                <i class="fas fa-moon text-info"></i> 
                                                A-midi: <?php echo substr($user_data['slots']['afternoon']['start_time'], 0, 5); ?> - 
                                                <?php echo substr($user_data['slots']['afternoon']['end_time'], 0, 5); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="removeUserSlots(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($user_data['full_name']); ?>')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Informations système -->
                    <div class="settings-section">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Fonctionnement du système</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-check-circle text-success"></i> Approbation automatique</h6>
                                    <ul class="small text-muted">
                                        <li>Les pointages dans les créneaux autorisés sont approuvés automatiquement</li>
                                        <li>Les créneaux spécifiques remplacent les créneaux globaux</li>
                                        <li>La vérification se fait à l'heure de pointage</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-exclamation-triangle text-warning"></i> Demande d'approbation</h6>
                                    <ul class="small text-muted">
                                        <li>Pointages trop tôt ou trop tard</li>
                                        <li>Pointages hors créneaux définis</li>
                                        <li>Notification visible dans l'onglet "Demandes à approuver"</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertes -->
            <div class="tab-pane fade" id="alerts" role="tabpanel">
                <div class="alert-panel w-100">
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
</div>

<!-- Scripts Chart.js compatible -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Données pour les graphiques (depuis PHP)
const chartData = <?php echo json_encode($chart_data); ?>;

// Initialisation des graphiques après le chargement
document.addEventListener('DOMContentLoaded', function() {
    // Attendre que Chart.js soit complètement chargé
    if (typeof Chart !== 'undefined') {
        initSimpleCharts();
    } else {
        console.log('Chart.js non disponible, graphiques désactivés');
    }
});

function initSimpleCharts() {
    try {
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
                    },
                    plugins: {
                        legend: {
                            display: true
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
                            <?php echo max(0, ($stats['active_employees'] ?? 0) - ($stats['currently_working'] ?? 0) - ($stats['on_break'] ?? 0)); ?>
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
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        console.log('✅ Graphiques initialisés avec succès');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation des graphiques:', error);
    }
}

// Fonctions pour le calendrier
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
    
    window.location.href = url.toString();
}

// Fonctions pour les paramètres
function saveGlobalSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save_global_slots');
    
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

function saveUserSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save_user_slots');
    
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

function removeUserSlots(userId, userName) {
    if (confirm(`Supprimer les créneaux spécifiques de ${userName} ? L'employé utilisera les créneaux globaux.`)) {
        const formData = new FormData();
        formData.append('action', 'remove_user_slots');
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

// Fonctions pour les approbations
function approveEntry(entryId, userName) {
    if (confirm(`Approuver le pointage de ${userName} ?`)) {
        const formData = new FormData();
        formData.append('action', 'approve_entry');
        formData.append('entry_id', entryId);
        
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

function rejectEntry(entryId, userName) {
    const reason = prompt(`Raison du rejet pour ${userName}:`);
    if (reason !== null) {
        const formData = new FormData();
        formData.append('action', 'reject_entry');
        formData.append('entry_id', entryId);
        formData.append('reason', reason);
        
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

function viewDetails(entryId) {
    // Fonction pour voir les détails (à implémenter selon les besoins)
    alert('Fonctionnalité de détails à venir - ID: ' + entryId);
}

// Fonctions globales existantes
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

function handleAlert(action, userId) {
    if (action === 'force_clock_out' && userId) {
        forceClockOut(userId, 'cet employé');
    }
}

function dismissAlert(button) {
    button.closest('.alert-item').style.display = 'none';
}

// Auto-refresh toutes les 60 secondes pour l'onglet temps réel
setInterval(() => {
    if (document.getElementById('live-tab') && document.getElementById('live-tab').classList.contains('active')) {
        location.reload();
    }
}, 60000);
</script>
