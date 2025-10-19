<?php
/**
 * Interface administrateur améliorée pour le système de pointage
 * Version avec correction des erreurs AJAX JSON
 */

// Ce fichier est inclus via le routage GeekBoard, donc les sessions sont déjà chargées
// S'assurer que les fichiers de configuration sont chargés
if (!function_exists('getShopDBConnection')) {
    require_once BASE_PATH . '/config/database.php';
}

// Vérifier les droits admin 
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Accès refusé - droits administrateur requis']);
        exit;
    }
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-ban"></i> Accès refusé</h4>
        <p>Cette page est réservée aux administrateurs.</p>
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
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        exit;
    }
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-database"></i> Erreur de base de données</h4>
        <p>Impossible de se connecter à la base de données: ' . $e->getMessage() . '</p>
    </div>';
    return;
}

// Traitement des actions AJAX UNIQUEMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Force header JSON pour toutes les réponses AJAX
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Vérifier que la table time_slots existe
        $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_slots'");
        $stmt->execute();
        $slots_table_exists = $stmt->fetch();
        
        if (!$slots_table_exists) {
            // Créer la table si elle n'existe pas
            $shop_pdo->exec("
                CREATE TABLE IF NOT EXISTS time_slots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    slot_type ENUM('morning', 'afternoon') NOT NULL,
                    start_time TIME NOT NULL,
                    end_time TIME NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_slot (user_id, slot_type)
                )
            ");
            
            // Insérer créneaux par défaut
            $shop_pdo->exec("
                INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES
                (NULL, 'morning', '08:00:00', '12:30:00', TRUE),
                (NULL, 'afternoon', '14:00:00', '19:00:00', TRUE)
                ON DUPLICATE KEY UPDATE 
                start_time = VALUES(start_time),
                end_time = VALUES(end_time)
            ");
        }
        
        switch ($_POST['action']) {
            case 'save_global_slots':
                $morning_start = $_POST['morning_start'] . ':00';
                $morning_end = $_POST['morning_end'] . ':00';
                $afternoon_start = $_POST['afternoon_start'] . ':00';
                $afternoon_end = $_POST['afternoon_end'] . ':00';
                
                // Validation des heures
                if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $morning_start) ||
                    !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $morning_end) ||
                    !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $afternoon_start) ||
                    !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $afternoon_end)) {
                    throw new Exception("Format d'heure invalide");
                }
                
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
                
                $response = ['success' => true, 'message' => 'Créneaux globaux sauvegardés avec succès'];
                break;
                
            case 'save_user_slots':
                $user_id = intval($_POST['user_id']);
                $morning_start = !empty($_POST['user_morning_start']) ? $_POST['user_morning_start'] . ':00' : null;
                $morning_end = !empty($_POST['user_morning_end']) ? $_POST['user_morning_end'] . ':00' : null;
                $afternoon_start = !empty($_POST['user_afternoon_start']) ? $_POST['user_afternoon_start'] . ':00' : null;
                $afternoon_end = !empty($_POST['user_afternoon_end']) ? $_POST['user_afternoon_end'] . ':00' : null;
                
                if ($user_id <= 0) {
                    throw new Exception("Utilisateur invalide");
                }
                
                // Supprimer les anciens créneaux de cet utilisateur
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $created_slots = 0;
                
                // Ajouter le créneau matin si spécifié
                if ($morning_start && $morning_end) {
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                        VALUES (?, 'morning', ?, ?, TRUE)
                    ");
                    $stmt->execute([$user_id, $morning_start, $morning_end]);
                    $created_slots++;
                }
                
                // Ajouter le créneau après-midi si spécifié
                if ($afternoon_start && $afternoon_end) {
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                        VALUES (?, 'afternoon', ?, ?, TRUE)
                    ");
                    $stmt->execute([$user_id, $afternoon_start, $afternoon_end]);
                    $created_slots++;
                }
                
                $response = ['success' => true, 'message' => "Créneaux utilisateur sauvegardés ($created_slots créneaux créés)"];
                break;
                
            case 'remove_user_slots':
                $user_id = intval($_POST['user_id']);
                if ($user_id <= 0) {
                    throw new Exception("Utilisateur invalide");
                }
                
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $response = ['success' => true, 'message' => 'Créneaux utilisateur supprimés, utilisation des créneaux globaux'];
                break;
                
            case 'approve_entry':
                $entry_id = intval($_POST['entry_id']);
                if ($entry_id <= 0) {
                    throw new Exception("ID d'entrée invalide");
                }
                
                $stmt = $shop_pdo->prepare("
                    UPDATE time_tracking 
                    SET admin_approved = TRUE, 
                        admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nApproved by admin at ', NOW())
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$entry_id])) {
                    $response = ['success' => true, 'message' => 'Entrée approuvée avec succès'];
                } else {
                    throw new Exception('Erreur lors de l\'approbation en base de données');
                }
                break;
                
            case 'reject_entry':
                $entry_id = intval($_POST['entry_id']);
                $reason = trim($_POST['reason'] ?? '');
                
                if ($entry_id <= 0) {
                    throw new Exception("ID d'entrée invalide");
                }
                if (empty($reason)) {
                    throw new Exception("Une raison de rejet est requise");
                }
                
                $stmt = $shop_pdo->prepare("
                    UPDATE time_tracking 
                    SET admin_approved = FALSE, 
                        admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nRejected by admin at ', NOW(), ' - Reason: ', ?),
                        status = 'rejected'
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$reason, $entry_id])) {
                    $response = ['success' => true, 'message' => 'Entrée rejetée avec succès'];
                } else {
                    throw new Exception('Erreur lors du rejet en base de données');
                }
                break;
                
            case 'force_clock_out':
                $user_id = intval($_POST['user_id']);
                if ($user_id <= 0) {
                    throw new Exception("ID utilisateur invalide");
                }
                
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
                    $affected = $stmt->rowCount();
                    if ($affected > 0) {
                        $response = ['success' => true, 'message' => 'Pointage forcé avec succès'];
                    } else {
                        $response = ['success' => false, 'message' => 'Aucun pointage actif trouvé pour cet utilisateur'];
                    }
                } else {
                    throw new Exception('Erreur lors du pointage forcé en base de données');
                }
                break;
                
            case 'send_notification':
                $user_id = intval($_POST['user_id']);
                $message = trim($_POST['message'] ?? '');
                
                if ($user_id <= 0) {
                    throw new Exception("ID utilisateur invalide");
                }
                if (empty($message)) {
                    throw new Exception("Le message ne peut pas être vide");
                }
                
                // Pour l'instant, simulation de l'envoi
                $response = ['success' => true, 'message' => 'Notification simulée envoyée avec succès'];
                break;
                
            default:
                throw new Exception("Action non reconnue: " . $_POST['action']);
        }
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
    
    // Retourner UNIQUEMENT du JSON pour les requêtes AJAX
    echo json_encode($response);
    exit; // IMPORTANT: Arrêter l'exécution ici pour éviter le HTML
}

// CODE HTML UNIQUEMENT SI CE N'EST PAS UNE REQUÊTE AJAX
// Le reste du code continue normalement pour l'affichage de la page...

// Récupérer les données pour l'affichage
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_user = $_GET['user'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Initialiser les variables
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
$all_users = [];
$top_performers = [];
$calendar_data = [];
$pending_requests = [];
$global_slots = [];
$user_slots = [];

try {
    // Vérifier tables
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_tracking'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo '<div class="alert alert-warning">
            <h4><i class="fas fa-table"></i> Table manquante</h4>
            <p>La table <code>time_tracking</code> n\'existe pas.</p>
        </div>';
        return;
    }

    // Vérifier/créer table time_slots
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_slots'");
    $stmt->execute();
    $slots_table_exists = $stmt->fetch();
    
    if (!$slots_table_exists) {
        // Créer la table
        $shop_pdo->exec("
            CREATE TABLE IF NOT EXISTS time_slots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                slot_type ENUM('morning', 'afternoon') NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_slot (user_id, slot_type)
            )
        ");
        
        // Ajouter données par défaut
        $shop_pdo->exec("
            INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES
            (NULL, 'morning', '08:00:00', '12:30:00', TRUE),
            (NULL, 'afternoon', '14:00:00', '19:00:00', TRUE)
        ");
    }

    // Vérifier colonnes time_tracking
    $stmt = $shop_pdo->prepare("SHOW COLUMNS FROM time_tracking LIKE 'auto_approved'");
    $stmt->execute();
    $auto_approved_exists = $stmt->fetch();
    
    if (!$auto_approved_exists) {
        $shop_pdo->exec("
            ALTER TABLE time_tracking 
            ADD COLUMN auto_approved BOOLEAN DEFAULT FALSE AFTER admin_approved,
            ADD COLUMN approval_reason VARCHAR(255) NULL AFTER auto_approved
        ");
    }

    // Récupérer données pour affichage
    // Tous les utilisateurs
    $stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users WHERE role != 'admin' ORDER BY full_name");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Utilisateurs actifs
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

    // Statistiques
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

    // Alertes heures supplémentaires
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

    // Données graphiques (7 derniers jours)
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

    // Top performers
    $stmt = $shop_pdo->prepare("
        SELECT u.full_name, 
               COUNT(*) as sessions,
               COALESCE(SUM(tt.work_duration), 0) as total_hours,
               COALESCE(AVG(tt.work_duration), 0) as avg_hours
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

    // Calendrier données
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

    // Demandes à approuver
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

    // Créneaux globaux
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

    // Créneaux spécifiques
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
        <h4><i class="fas fa-exclamation-triangle"></i> Erreur</h4>
        <p>Erreur lors de la récupération des données: ' . $e->getMessage() . '</p>
    </div>';
    return;
}
?>

<!-- CSS et HTML identiques, mais JavaScript corrigé -->
<style>
.admin-timetracking-container {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    float: none !important;
    clear: both !important;
}

:root {
    --primary-color: #0066cc;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #17a2b8;
    --border-radius: 12px;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

.admin-nav {
    background: linear-gradient(135deg, var(--primary-color), #004499);
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
    width: 100%;
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

.chart-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

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
    padding: 1rem;
}

.approval-item.out-of-hours {
    border-left: 4px solid var(--warning-color);
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
}
</style>

<!-- Interface HTML simplifiée pour éviter les conflits -->
<div class="admin-timetracking-container">
    <div class="w-100">
        <!-- Header -->
        <div class="admin-nav">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="text-white mb-1">
                        <i class="fas fa-tachometer-alt"></i> Dashboard Pointage
                    </h1>
                    <p class="text-white-50 mb-0">Supervision avancée et analytics des temps de travail</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                </div>
            </div>
            
            <!-- Navigation -->
            <ul class="nav nav-tabs nav-tabs-custom" id="adminTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                        <i class="fas fa-cog"></i> Paramètres
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals" type="button">
                        <i class="fas fa-check-double"></i> Demandes
                        <?php if (count($pending_requests) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo count($pending_requests); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenu -->
        <div class="tab-content w-100">
            
            <!-- Dashboard -->
            <div class="tab-pane fade show active" id="dashboard">
                <div class="row w-100 mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <h3 class="text-success mb-1"><?php echo $stats['currently_working']; ?></h3>
                                <small class="text-muted">Actuellement au travail</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <h3 class="text-warning mb-1"><?php echo $stats['on_break']; ?></h3>
                                <small class="text-muted">En pause</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <h3 class="text-info mb-1"><?php echo number_format($stats['total_work_hours'], 1); ?>h</h3>
                                <small class="text-muted">Total heures</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <h3 class="text-danger mb-1"><?php echo $stats['pending_approvals']; ?></h3>
                                <small class="text-muted">À approuver</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paramètres -->
            <div class="tab-pane fade" id="settings">
                <div class="w-100">
                    <!-- Créneaux globaux -->
                    <div class="settings-section">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-globe"></i> Créneaux globaux</h5>
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
                                        <i class="fas fa-save"></i> Sauvegarder
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Créneaux spécifiques -->
                    <div class="settings-section">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Créneaux spécifiques</h5>
                        </div>
                        <div class="card-body">
                            <form id="userSlotsForm" onsubmit="saveUserSlots(event)">
                                <div class="row">
                                    <div class="col-md-3">
                                        <select class="form-select" name="user_id" required>
                                            <option value="">Sélectionner</option>
                                            <?php foreach ($all_users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="time" class="form-control" name="user_morning_start" placeholder="Matin début">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="time" class="form-control" name="user_morning_end" placeholder="Matin fin">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="time" class="form-control" name="user_afternoon_start" placeholder="A-midi début">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="time" class="form-control" name="user_afternoon_end" placeholder="A-midi fin">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-success">+</button>
                                    </div>
                                </div>
                            </form>

                            <?php if (!empty($user_slots)): ?>
                            <h6 class="mt-4 mb-3">Créneaux configurés</h6>
                            <?php foreach ($user_slots as $user_id => $user_data): ?>
                            <div class="user-slot-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user_data['full_name']); ?></h6>
                                        <small class="text-muted">
                                            <?php if (isset($user_data['slots']['morning'])): ?>
                                            Matin: <?php echo substr($user_data['slots']['morning']['start_time'], 0, 5); ?>-<?php echo substr($user_data['slots']['morning']['end_time'], 0, 5); ?>
                                            <?php endif; ?>
                                            <?php if (isset($user_data['slots']['afternoon'])): ?>
                                            | A-midi: <?php echo substr($user_data['slots']['afternoon']['start_time'], 0, 5); ?>-<?php echo substr($user_data['slots']['afternoon']['end_time'], 0, 5); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="removeUserSlots(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($user_data['full_name']); ?>')">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demandes à approuver -->
            <div class="tab-pane fade" id="approvals">
                <div class="w-100">
                    <?php if (empty($pending_requests)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success">Aucune demande en attente</h4>
                    </div>
                    <?php else: ?>
                    <div class="row w-100">
                        <?php foreach ($pending_requests as $request): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="card approval-item <?php echo $request['approval_reason'] ? 'out-of-hours' : ''; ?>">
                                <div class="card-body">
                                    <h6><?php echo htmlspecialchars($request['full_name']); ?></h6>
                                    <p class="small text-muted">
                                        Date: <?php echo date('d/m/Y', strtotime($request['work_date'])); ?> |
                                        Heures: <?php echo $request['start_time']; ?> - <?php echo $request['end_time'] ?? 'N/A'; ?>
                                    </p>
                                    <?php if ($request['approval_reason']): ?>
                                    <div class="bg-warning bg-opacity-25 p-2 rounded mb-2">
                                        <small><?php echo htmlspecialchars($request['approval_reason']); ?></small>
                                    </div>
                                    <?php endif; ?>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-success btn-sm flex-fill" 
                                                onclick="approveEntry(<?php echo $request['id']; ?>)">
                                            Approuver
                                        </button>
                                        <button class="btn btn-danger btn-sm flex-fill" 
                                                onclick="rejectEntry(<?php echo $request['id']; ?>)">
                                            Rejeter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript corrigé pour gérer les erreurs JSON
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
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau: ' + response.status);
        }
        return response.text(); // D'abord en text pour voir le contenu
    })
    .then(text => {
        try {
            const data = JSON.parse(text); // Puis parser JSON
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        } catch (e) {
            console.error('Réponse non-JSON reçue:', text);
            alert('❌ Erreur de format de réponse');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Erreur de connexion');
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
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau: ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        } catch (e) {
            console.error('Réponse non-JSON reçue:', text);
            alert('❌ Erreur de format de réponse');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Erreur de connexion');
    });
}

function removeUserSlots(userId, userName) {
    if (confirm(`Supprimer les créneaux de ${userName} ?`)) {
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
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (e) {
                console.error('Réponse non-JSON reçue:', text);
                alert('❌ Erreur de format de réponse');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('❌ Erreur de connexion');
        });
    }
}

function approveEntry(entryId) {
    if (confirm('Approuver cette entrée ?')) {
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
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (e) {
                console.error('Réponse non-JSON reçue:', text);
                alert('❌ Erreur de format de réponse');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('❌ Erreur de connexion');
        });
    }
}

function rejectEntry(entryId) {
    const reason = prompt('Raison du rejet:');
    if (reason !== null && reason.trim() !== '') {
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
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (e) {
                console.error('Réponse non-JSON reçue:', text);
                alert('❌ Erreur de format de réponse');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('❌ Erreur de connexion');
        });
    }
}
</script>
