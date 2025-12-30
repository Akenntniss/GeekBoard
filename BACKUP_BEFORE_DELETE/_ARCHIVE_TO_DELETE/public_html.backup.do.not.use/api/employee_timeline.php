<?php
// Inclure la configuration de session avant de démarrer la session
require_once dirname(__DIR__) . '/config/session_config.php';

// Inclure la configuration pour la gestion des sous-domaines
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Inclure la configuration de la base de données
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$repair_number = isset($_GET['repair_number']) ? trim($_GET['repair_number']) : null;

// Gérer les paramètres de date (soit une seule date, soit une période)
if (isset($_GET['date_debut']) && isset($_GET['date_fin'])) {
    // Mode période
    $date_debut = $_GET['date_debut'];
    $date_fin = $_GET['date_fin'];
    $selected_date = null; // Pas utilisé en mode période
} else {
    // Mode date unique (compatibilité)
    $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $date_debut = $selected_date;
    $date_fin = $selected_date;
}

if ($employee_id < 0) {
    echo json_encode(['error' => 'ID employé invalide']);
    exit;
}

try {
    // Initialiser la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    // Récupérer les informations de l'employé
    if ($employee_id == 0) {
        $employee_name = 'Tous les employés';
    } else {
        $emp_stmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
        $emp_stmt->execute([$employee_id]);
        $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            echo json_encode(['error' => 'Employé non trouvé']);
            exit;
        }
        
        $employee_name = $employee['full_name'] ?: $employee['username'];
    }
    
    // Récupérer tous les événements pour la date sélectionnée
    $timeline_events = [];
    
    // 1. POINTAGES (time_tracking) - Seulement si pas de recherche par numéro
    if (!$repair_number) {
        $pointage_query = "
            SELECT 
                tt.clock_in,
                tt.clock_out,
                tt.break_start,
                tt.break_end,
                tt.status,
                u.full_name as employe_nom,
                u.username as employe_username,
                tt.user_id
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE DATE(tt.clock_in) >= ? AND DATE(tt.clock_in) <= ?";
        
        if ($employee_id > 0) {
            $pointage_query .= " AND tt.user_id = ?";
            $pointage_params = [$date_debut, $date_fin, $employee_id];
        } else {
            $pointage_params = [$date_debut, $date_fin];
        }
        
        $pointage_query .= " ORDER BY tt.clock_in ASC";
        
        $pointage_stmt = $pdo->prepare($pointage_query);
        $pointage_stmt->execute($pointage_params);
        $pointage_data = $pointage_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Transformer les pointages en événements
        foreach ($pointage_data as $pointage) {
            $emp_display_name = $pointage['employe_nom'] ?: $pointage['employe_username'];
            
            // Pointage d'arrivée
            $timeline_events[] = [
                'time' => $pointage['clock_in'],
                'type' => 'pointage',
                'action' => 'arrivee',
                'title' => '🟢 Arrivée',
                'subtitle' => 'Pointage d\'arrivée',
                'employee' => $emp_display_name,
                'details' => 'Début de journée de travail',
                'status_before' => '',
                'status_after' => 'Présent'
            ];
            
            // Pointage de sortie
            if ($pointage['clock_out']) {
                $timeline_events[] = [
                    'time' => $pointage['clock_out'],
                    'type' => 'pointage',
                    'action' => 'depart',
                    'title' => '🔴 Départ',
                    'subtitle' => 'Pointage de départ',
                    'employee' => $emp_display_name,
                    'details' => 'Fin de journée de travail',
                    'status_before' => 'Présent',
                    'status_after' => 'Parti'
                ];
            }
            
            // Début de pause
            if ($pointage['break_start']) {
                $timeline_events[] = [
                    'time' => $pointage['break_start'],
                    'type' => 'pause',
                    'action' => 'pause_debut',
                    'title' => '⏸️ Début de pause',
                    'subtitle' => 'Pause déjeuner/repos',
                    'employee' => $emp_display_name,
                    'details' => 'Début de la pause',
                    'status_before' => 'Travail',
                    'status_after' => 'Pause'
                ];
            }
            
            // Fin de pause
            if ($pointage['break_end']) {
                $timeline_events[] = [
                    'time' => $pointage['break_end'],
                    'type' => 'pause',
                    'action' => 'pause_fin',
                    'title' => '▶️ Fin de pause',
                    'subtitle' => 'Reprise du travail',
                    'employee' => $emp_display_name,
                    'details' => 'Reprise du travail après pause',
                    'status_before' => 'Pause',
                    'status_after' => 'Travail'
                ];
            }
        }
    }
    
    // 2. LOGS DE RÉPARATIONS
    $logs_query = "
        SELECT 
            rl.*,
            r.id as reparation_id_ref,
            r.type_appareil,
            r.modele,
            CONCAT(c.nom, ' ', c.prenom) as client_nom,
            u.full_name as employe_nom,
            u.username as employe_username
        FROM reparation_logs rl
        JOIN reparations r ON rl.reparation_id = r.id
        JOIN clients c ON r.client_id = c.id
        JOIN users u ON rl.employe_id = u.id
        WHERE 1=1";
    
    $logs_params = [];
    
    // Si recherche par numéro de réparation
    if ($repair_number) {
        $logs_query .= " AND rl.reparation_id = ?";
        $logs_params[] = $repair_number;
    } else {
        // Sinon, filtrer par date
        $logs_query .= " AND DATE(rl.date_action) >= ? AND DATE(rl.date_action) <= ?";
        $logs_params[] = $date_debut;
        $logs_params[] = $date_fin;
    }
    
    // Filtrer par employé si spécifié
    if ($employee_id > 0) {
        $logs_query .= " AND rl.employe_id = ?";
        $logs_params[] = $employee_id;
    }
    
    $logs_query .= " ORDER BY rl.date_action ASC";
    
    $logs_stmt = $pdo->prepare($logs_query);
    $logs_stmt->execute($logs_params);
    $logs_data = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformer les logs de réparations
    foreach ($logs_data as $log) {
        $emp_display_name = $log['employe_nom'] ?: $log['employe_username'];
        
        $action_titles = [
            'demarrage' => '🚀 Démarrage réparation',
            'terminer' => '✅ Fin réparation',
            'changement_statut' => '🔄 Changement statut',
            'ajout_note' => '📝 Note ajoutée',
            'modification' => '✏️ Modification',
            'autre' => '🔧 Autre action'
        ];
        
        $timeline_events[] = [
            'time' => $log['date_action'],
            'type' => 'reparation',
            'action' => $log['action_type'],
            'title' => $action_titles[$log['action_type']] ?? '🔧 Action',
            'subtitle' => 'Réparation #' . $log['reparation_id'] . ' - ' . $log['type_appareil'] . ' ' . $log['modele'],
            'employee' => $emp_display_name,
            'details' => 'Client: ' . $log['client_nom'] . "\n" . 
                       ($log['details'] ?: 'Aucun détail') . 
                       ($log['statut_avant'] ? "\nDe: " . $log['statut_avant'] : '') .
                       ($log['statut_apres'] ? "\nVers: " . $log['statut_apres'] : ''),
            'status_before' => $log['statut_avant'],
            'status_after' => $log['statut_apres'],
            'client' => $log['client_nom']
        ];
    }
    
    // Trier tous les événements par heure
    usort($timeline_events, function($a, $b) {
        return strtotime($a['time']) - strtotime($b['time']);
    });
    
    echo json_encode([
        'success' => true,
        'employee_name' => $employee_name,
        'date_debut' => $date_debut,
        'date_fin' => $date_fin,
        'date' => $selected_date, // Compatibilité
        'events' => $timeline_events
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur base de données: ' . $e->getMessage()]);
}
?>
