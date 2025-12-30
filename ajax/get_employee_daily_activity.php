<?php
/**
 * Endpoint AJAX pour récupérer l'activité quotidienne d'un employé depuis reparation_logs
 */

//  Headers
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';

try {
    // La session est déjà démarrée par session_config.php
    
    // Vérifier l'authentification
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Non connecté');
    }
    
    // Récupérer les paramètres
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $date = isset($_GET['start_date']) ? $_GET['start_date'] : (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d')); // Par défaut aujourd'hui
    
    if ($user_id <= 0) {
        throw new Exception('ID utilisateur invalide');
    }
    
    // Connexion à la base
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Récupérer les informations de l'employé
    $stmt_user = $shop_pdo->prepare("SELECT full_name, role FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_info) {
        throw new Exception('Utilisateur non trouvé');
    }
    
    // Récupérer les logs de la journée depuis reparation_logs
    $stmt = $shop_pdo->prepare("
        SELECT 
            rl.id,
            rl.date_action,
            rl.action_type,
            rl.statut_avant,
            rl.statut_apres,
            rl.details,
            rl.reparation_id,
            r.modele as repair_model,
            c.nom as client_nom,
            c.prenom as client_prenom,
            NULL as task_id,
            'repair' as log_type
        FROM reparation_logs rl
        LEFT JOIN reparations r ON rl.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE rl.employe_id = ? 
          AND DATE(rl.date_action) = ?
        ORDER BY rl.date_action DESC
    ");
    
    $stmt->execute([$user_id, $date]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les logs des tâches
    $stmt_tasks = $shop_pdo->prepare("
        SELECT
            tl.id,
            tl.created_at as date_action,
            tl.action_type,
            NULL as statut_avant,
            NULL as statut_apres,
            NULL as details,
            tl.task_id,
            t.titre as task_title,
            t.description as task_description,
            NULL as repair_model,
            NULL as client_nom,
            NULL as client_prenom,
            'task' as log_type
        FROM Task_logs tl
        LEFT JOIN taches t ON tl.task_id = t.id
        WHERE tl.user_id = ?
          AND DATE(tl.created_at) = ?
        ORDER BY tl.created_at DESC
    ");
    $stmt_tasks->execute([$user_id, $date]);
    $task_logs = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les pointages de la journée
    $stmt_tracking = $shop_pdo->prepare("
        SELECT
            id,
            clock_in as date_action,
            clock_out,
            break_start,
            break_end,
            total_hours,
            work_duration,
            break_duration,
            status,
            'time_tracking' as log_type
        FROM time_tracking
        WHERE user_id = ?
          AND DATE(clock_in) = ?
        ORDER BY clock_in DESC
    ");
    $stmt_tracking->execute([$user_id, $date]);
    $tracking_logs = $stmt_tracking->fetchAll(PDO::FETCH_ASSOC);
    
    // Fusionner les trois types de logs
    $all_logs = array_merge($logs, $task_logs, $tracking_logs);
    
    // Trier par date_action
    usort($all_logs, function($a, $b) {
        return strtotime($b['date_action']) - strtotime($a['date_action']);
    });
    
    // Formatter les logs
    $formatted_logs = [];
    foreach ($all_logs as $log) {
        $formatted_log = [
            'id' => $log['id'],
            'date_action' => $log['date_action'],
            'time' => date('H:i', strtotime($log['date_action'])),
            'action_type' => $log['action_type'],
            'log_type' => $log['log_type']
        ];
        
        if ($log['log_type'] == 'repair') {
            $formatted_log['action_label'] = formatActionType($log['action_type']);
            $formatted_log['statut_avant'] = $log['statut_avant'];
            $formatted_log['statut_apres'] = $log['statut_apres'];
            $formatted_log['details'] = $log['details'];
            $formatted_log['reparation_id'] = $log['reparation_id'];
            $formatted_log['repair_model'] = $log['repair_model'] ?: 'N/A';
            $formatted_log['client'] = trim(($log['client_nom'] ?? '') . ' ' . ($log['client_prenom'] ?? '')) ?: 'N/A';
            $formatted_log['repair_problem'] = ''; // Assuming this was intended to be empty or derived elsewhere
        } elseif ($log['log_type'] == 'task') {
            // Task log
            $formatted_log['action_label'] = $log['action_type'] == 'start' ? 'Démarrage tâche' : 'Fin tâche';
            $formatted_log['task_id'] = $log['task_id'];
            $formatted_log['task_title'] = $log['task_title'];
            $formatted_log['task_description'] = $log['task_description'];
        } else {
            // Time tracking log
            $formatted_log['action_label'] = 'Pointage';
            $formatted_log['clock_in'] = $log['date_action'];
            $formatted_log['clock_out'] = $log['clock_out'];
            $formatted_log['break_start'] = $log['break_start'];
            $formatted_log['break_end'] = $log['break_end'];
            $formatted_log['total_hours'] = $log['total_hours'];
            $formatted_log['work_duration'] = $log['work_duration'];
            $formatted_log['break_duration'] = $log['break_duration'];
            $formatted_log['status'] = $log['status'];
        }
        
        $formatted_logs[] = $formatted_log;
    }
    
    // Renvoyer les données
    echo json_encode([
        'success' => true,
        'user' => [
            'name' => $user_info['full_name'],
            'role' => $user_info['role']
        ],
        'date' => $date,
        'logs' => $formatted_logs,
        'count' => count($formatted_logs)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function formatActionType($action_type) {
    switch($action_type) {
        case 'demarrage':
            return '🚀 Démarrage';
        case 'terminer':
            return '✅ Terminé';
        case 'changement_statut':
            return '🔄 Changement statut';
        case 'ajout_note':
            return '📝 Note ajoutée';
        case 'modification':
            return '✏️ Modification';
        case 'autre':
            return '❓ Autre';
        default:
            return ucfirst($action_type);
    }
}
?>
