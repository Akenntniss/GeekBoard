<?php
// Inclure la configuration de la base de données
require_once('config/database.php');

$shop_pdo = getShopDBConnection();
require_once('includes/functions.php');
require_once('includes/task_logger.php');

// Authentification supprimée - accès libre à la page des logs de réparation

// Activer le débogage
$DEBUG = true; // Mettre à true pour voir les logs de débogage

// Paramètres de pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15; // 15 lignes par page
$offset = ($page - 1) * $limit;

// Filtres
$employe_id = isset($_GET['employe_id']) ? intval($_GET['employe_id']) : 0;
$reparation_id = isset($_GET['reparation_id']) ? intval($_GET['reparation_id']) : 0;
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$heure_debut = isset($_GET['heure_debut']) ? $_GET['heure_debut'] : '';
$heure_fin = isset($_GET['heure_fin']) ? $_GET['heure_fin'] : '';
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_action';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$view_mode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'timeline';
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : 'none';
$log_type = isset($_GET['log_type']) ? $_GET['log_type'] : 'all';

// Valeurs par défaut pour les filtres rapides
if (empty($date_debut) && empty($date_fin)) {
    $quick_filter = isset($_GET['quick_filter']) ? $_GET['quick_filter'] : '';
    switch ($quick_filter) {
        case 'today':
            $date_debut = $date_fin = date('Y-m-d');
            break;
        case 'yesterday':
            $date_debut = $date_fin = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'week':
            $date_debut = date('Y-m-d', strtotime('monday this week'));
            $date_fin = date('Y-m-d');
            break;
        case 'month':
            $date_debut = date('Y-m-01');
            $date_fin = date('Y-m-d');
            break;
    }
}

// Vérifier si la table Log_tasks existe
$log_tasks_exists = false;
try {
    $check_table = $shop_pdo->query("SHOW TABLES LIKE 'Log_tasks'");
    $log_tasks_exists = $check_table->rowCount() > 0;
} catch (PDOException $e) {
    if ($DEBUG) {
        error_log("Erreur lors de la vérification de la table Log_tasks: " . $e->getMessage());
    }
}

// Construction de la requête SQL avec filtres
if (($log_type === 'tasks' || $log_type === 'all') && $log_tasks_exists) {
    // Requête pour les logs de tâches (seulement si la table existe)
    $task_sql = "
        SELECT 
            tl.id,
            tl.task_id as entity_id,
            tl.user_id as employe_id,
            tl.action_type,
            tl.old_status as statut_avant,
            tl.new_status as statut_apres,
            tl.action_timestamp as date_action,
            tl.user_name as employe_nom,
            tl.task_title,
            tl.details,
            'task' as log_source,
            '' as type_appareil,
            '' as modele,
            '' as client_nom,
            '' as reparation_description,
            u.role as employe_role
        FROM Log_tasks tl
        LEFT JOIN users u ON tl.user_id = u.id
        WHERE 1=1
    ";
}

if ($log_type === 'repairs' || $log_type === 'all') {
    // Requête pour les logs de réparations
    $repair_sql = "
        SELECT 
            rl.id,
            rl.reparation_id as entity_id,
            rl.employe_id,
            rl.action_type,
            rl.statut_avant,
            rl.statut_apres,
            rl.date_action,
            u.full_name as employe_nom,
            '' as task_title,
            rl.details,
            'repair' as log_source,
            r.type_appareil,
            r.modele,
            CONCAT(c.nom, ' ', c.prenom) as client_nom,
            r.description_probleme as reparation_description,
            u.role as employe_role
        FROM reparation_logs rl
        JOIN reparations r ON rl.reparation_id = r.id
        JOIN users u ON rl.employe_id = u.id
        JOIN clients c ON r.client_id = c.id
        WHERE 1=1
    ";
}

// Union des deux requêtes selon le filtre choisi et la disponibilité des tables
if ($log_type === 'all') {
    if ($log_tasks_exists && isset($task_sql) && isset($repair_sql)) {
        $sql = "(" . $repair_sql . ") UNION (" . $task_sql . ")";
    } else {
        $sql = $repair_sql; // Seulement les logs de réparations si Log_tasks n'existe pas
    }
} elseif ($log_type === 'tasks') {
    if ($log_tasks_exists && isset($task_sql)) {
        $sql = $task_sql;
    } else {
        // Si on demande les logs de tâches mais que la table n'existe pas, requête vide
        $sql = "SELECT NULL as id, NULL as entity_id, NULL as employe_id, NULL as action_type, 
                NULL as statut_avant, NULL as statut_apres, NULL as date_action, NULL as employe_nom, 
                NULL as task_title, NULL as details, NULL as log_source, NULL as type_appareil, 
                NULL as modele, NULL as client_nom, NULL as reparation_description, NULL as employe_role 
                WHERE FALSE"; // Requête qui ne retourne aucun résultat
    }
} else {
    $sql = $repair_sql;
}

$params = [];

// Construire les conditions de filtre pour chaque type de log
$filter_conditions = [];

if ($employe_id > 0) {
    $filter_conditions[] = "employe_id = ?";
    $params[] = $employe_id;
}

if (!empty($action_type)) {
    $filter_conditions[] = "action_type = ?";
    $params[] = $action_type;
}

if (!empty($date_debut)) {
    if (!empty($heure_debut)) {
        $filter_conditions[] = "date_action >= ?";
        $params[] = $date_debut . ' ' . $heure_debut . ':00';
    } else {
        $filter_conditions[] = "DATE(date_action) >= ?";
        $params[] = $date_debut;
    }
}

if (!empty($date_fin)) {
    if (!empty($heure_fin)) {
        $filter_conditions[] = "date_action <= ?";
        $params[] = $date_fin . ' ' . $heure_fin . ':59';
    } else {
        $filter_conditions[] = "DATE(date_action) <= ?";
        $params[] = $date_fin;
    }
}

// Filtre spécifique pour les réparations
if ($reparation_id > 0 && ($log_type === 'repairs' || $log_type === 'all')) {
    if ($log_type === 'repairs') {
        $filter_conditions[] = "reparation_id = ?";
        $params[] = $reparation_id;
    } else {
        // Pour 'all', on filtre directement dans chaque sous-requête
        $repair_sql .= " AND rl.reparation_id = ?";
    }
}

// Recherche textuelle adaptée selon le type de log
if (!empty($search_term)) {
    $search_param = "%" . $search_term . "%";
    
    if ($log_type === 'tasks') {
        $filter_conditions[] = "(task_title LIKE ? OR details LIKE ? OR employe_nom LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    } elseif ($log_type === 'repairs') {
        $filter_conditions[] = "(reparation_description LIKE ? OR type_appareil LIKE ? OR modele LIKE ? OR client_nom LIKE ? OR employe_nom LIKE ? OR details LIKE ? OR statut_avant LIKE ? OR statut_apres LIKE ?)";
        for ($i = 0; $i < 8; $i++) {
            $params[] = $search_param;
        }
    } else {
        // Pour 'all', on applique la recherche dans chaque sous-requête
        $repair_search = "(r.description_probleme LIKE ? OR r.type_appareil LIKE ? OR r.modele LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ? OR u.full_name LIKE ? OR rl.details LIKE ? OR rl.statut_avant LIKE ? OR rl.statut_apres LIKE ?)";
        $task_search = "(tl.task_title LIKE ? OR tl.details LIKE ? OR tl.user_name LIKE ?)";
        
        $repair_sql .= " AND " . $repair_search;
        if ($log_tasks_exists && isset($task_sql)) {
        $task_sql .= " AND " . $task_search;
        }
        
        // Ajouter les paramètres pour la recherche
        for ($i = 0; $i < 9; $i++) {
            $params[] = $search_param;
        }
        if ($log_tasks_exists && isset($task_sql)) {
        for ($i = 0; $i < 3; $i++) {
            $params[] = $search_param;
            }
        }
    }
}

// Appliquer les conditions aux requêtes
if (!empty($filter_conditions) && $log_type !== 'all') {
    $sql .= " AND " . implode(" AND ", $filter_conditions);
} elseif (!empty($filter_conditions) && $log_type === 'all') {
    $conditions_str = " AND " . implode(" AND ", $filter_conditions);
    $repair_sql .= $conditions_str;
    if ($log_tasks_exists && isset($task_sql)) {
    $task_sql .= $conditions_str;
    }
    
    // Reconstruire la requête union avec les conditions
    if ($log_tasks_exists && isset($task_sql)) {
    $sql = "(" . $repair_sql . ") UNION (" . $task_sql . ")";
    } else {
        $sql = $repair_sql; // Seulement les logs de réparations si Log_tasks n'existe pas
    }
}

// Compter le total pour la pagination
$count_sql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
try {
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_logs = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    $total_logs = 0;
}

// Tri
$valid_sort_columns = ['date_action', 'employe_nom', 'action_type', 'entity_id'];
if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'date_action';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Pour la requête UNION, on doit envelopper dans une sous-requête pour trier
if ($log_type === 'all') {
    $sql = "SELECT * FROM (" . $sql . ") as combined_logs ORDER BY {$sort_by} {$sort_order}";
} else {
    $sql .= " ORDER BY {$sort_by} {$sort_order}";
}

// Pagination pour la timeline
if ($view_mode === 'timeline') {
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
}

// Debug SQL query
if ($DEBUG) {
    error_log("Requête SQL logs: " . $sql);
    error_log("Paramètres: " . print_r($params, true));
}

// Obtenir les résultats selon le type de log sélectionné
try {
    if ($log_type === 'all') {
        // Exécuter la requête UNION pour tous les logs
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Exécuter la requête pour un type spécifique
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($DEBUG) {
        error_log("Nombre de logs trouvés ($log_type): " . count($logs));
    }
} catch (PDOException $e) {
    $logs = [];
    set_message("Erreur lors de la récupération des logs: " . $e->getMessage(), "danger");
    if ($DEBUG) {
        error_log("Erreur SQL: " . $e->getMessage());
    }
}

// Récupérer la liste des employés pour le filtre
try {
    $stmt = $shop_pdo->query("SELECT id, full_name as nom FROM users WHERE role = 'technicien' OR role = 'admin' ORDER BY full_name");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $employes = [];
}

// Récupérer les types d'actions uniques selon le type de log
try {
    if ($log_type === 'tasks') {
        // Actions des tâches uniquement (si la table existe)
        if ($log_tasks_exists) {
            $stmt = $shop_pdo->query("SELECT DISTINCT action_type FROM Log_tasks ORDER BY action_type");
            $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $action_types = []; // Aucun type d'action si la table n'existe pas
        }
    } elseif ($log_type === 'repairs') {
        // Actions des réparations uniquement
        $stmt = $shop_pdo->query("SELECT DISTINCT action_type FROM reparation_logs ORDER BY action_type");
        $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Tous les types d'actions combinés (seulement si Log_tasks existe)
        if ($log_tasks_exists) {
            $stmt = $shop_pdo->query("
                SELECT DISTINCT action_type FROM reparation_logs 
                UNION 
                SELECT DISTINCT action_type FROM Log_tasks 
                ORDER BY action_type
            ");
            $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            // Seulement les actions de réparations si Log_tasks n'existe pas
            $stmt = $shop_pdo->query("SELECT DISTINCT action_type FROM reparation_logs ORDER BY action_type");
            $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }
} catch (PDOException $e) {
    $action_types = [];
    if ($DEBUG) {
        error_log("Erreur lors de la récupération des types d'actions: " . $e->getMessage());
    }
}

// Regrouper les logs par réparation et par employé
$grouped_logs = [];
$employees = [];

// Collecter les données des logs pour la timeline et par employé
foreach ($logs as $log) {
    // Groupement selon le paramètre choisi
    if ($group_by === 'repair') {
        $group_key = $log['log_source'] === 'task' ? 'task_' . $log['entity_id'] : 'repair_' . $log['entity_id'];
        $grouped_logs[$group_key][] = $log;
    } elseif ($group_by === 'employee') {
        $grouped_logs[$log['employe_id']][] = $log;
    } elseif ($group_by === 'date') {
        $date_key = date('Y-m-d', strtotime($log['date_action']));
        $grouped_logs[$date_key][] = $log;
    } else {
        $grouped_logs[] = $log;
    }
    
    // Grouper également par employé pour l'onglet "Activités par employé"
    $employee_id = $log['employe_id'];
    $employee_name = $log['employe_nom'];
    
    // Enregistrer chaque employé unique
    if (!isset($employees[$employee_id])) {
        $employees[$employee_id] = [
            'id' => $employee_id,
            'name' => $employee_name,
            'role' => $log['employe_role'] ?? 'Utilisateur',
            'repairs' => [],
            'tasks' => []
        ];
    }
    
    // Grouper selon le type de log
    if ($log['log_source'] === 'task') {
        // Logs de tâches
        $task_id = $log['entity_id'];
        if (!isset($employees[$employee_id]['tasks'][$task_id])) {
            $employees[$employee_id]['tasks'][$task_id] = [
                'id' => $task_id,
                'title' => $log['task_title'] ?? 'Tâche #' . $task_id,
                'description' => $log['task_description'] ?? '',
                'logs' => []
            ];
        }
        $employees[$employee_id]['tasks'][$task_id]['logs'][] = $log;
    } else {
        // Logs de réparations
        $repair_id = $log['entity_id'];
        if (!isset($employees[$employee_id]['repairs'][$repair_id])) {
            $employees[$employee_id]['repairs'][$repair_id] = [
                'id' => $repair_id,
                'type_appareil' => $log['type_appareil'] ?? '',
                'modele' => $log['modele'] ?? '',
                'client_nom' => $log['client_nom'] ?? '',
                'client_id' => $log['client_id'] ?? '',
                'description' => $log['reparation_description'] ?? '',
                'logs' => []
            ];
        }
        $employees[$employee_id]['repairs'][$repair_id]['logs'][] = $log;
    }
}

// Fonction pour calculer la durée entre deux dates
function calculate_duration($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    
    $duration = '';
    
    if ($interval->d > 0) {
        $duration .= $interval->d . 'j ';
    }
    
    if ($interval->h > 0) {
        $duration .= $interval->h . 'h ';
    }
    
    if ($interval->i > 0) {
        $duration .= $interval->i . 'min';
    } else if ($duration === '') {
        $duration = '< 1min';
    }
    
    return $duration;
}

// Fonction pour obtenir tous les démarrages et fins d'une réparation
function get_all_repair_sequences($logs) {
    $sequences = [];
    $start_logs = [];
    $end_action_types = ['terminer', 'changement_statut', 'ajout_note', 'modification', 'autre'];
    
    // Extraire tous les logs de démarrage
    foreach ($logs as $log) {
        if ($log['action_type'] === 'demarrage') {
            $start_logs[] = $log;
        }
    }
    
    // Trier les logs de démarrage par date (du plus ancien au plus récent)
    usort($start_logs, function($a, $b) {
        return strtotime($a['date_action']) - strtotime($b['date_action']);
    });
    
    // Pour chaque log de démarrage, trouver le log de fin correspondant
    foreach ($start_logs as $start) {
        $start_time = strtotime($start['date_action']);
        $best_end = null;
        $min_time_diff = PHP_INT_MAX;
        
        // Chercher le log de fin le plus proche après ce démarrage
        foreach ($logs as $log) {
            if (in_array($log['action_type'], $end_action_types)) {
                $log_time = strtotime($log['date_action']);
                
                // Le log de fin doit être après le démarrage
                if ($log_time > $start_time) {
                    $time_diff = $log_time - $start_time;
                    
                    // Si c'est le log de fin le plus proche trouvé jusqu'à présent
                    if ($time_diff < $min_time_diff) {
                        $min_time_diff = $time_diff;
                        $best_end = $log;
                    }
                }
            }
        }
        
        // Ajouter cette séquence de démarrage-fin
        $sequences[] = [
            'start' => $start,
            'end' => $best_end
        ];
    }
    
    return $sequences;
}

// Fonction pour obtenir tous les démarrages et fins d'une tâche
function get_all_task_sequences($logs) {
    $sequences = [];
    $start_logs = [];
    $end_action_types = ['terminer', 'pause', 'modifier', 'supprimer'];
    
    // Extraire tous les logs de démarrage
    foreach ($logs as $log) {
        if ($log['action_type'] === 'demarrer') {
            $start_logs[] = $log;
        }
    }
    
    // Trier les logs de démarrage par date (du plus ancien au plus récent)
    usort($start_logs, function($a, $b) {
        return strtotime($a['date_action']) - strtotime($b['date_action']);
    });
    
    // Pour chaque log de démarrage, trouver le log de fin correspondant
    foreach ($start_logs as $start) {
        $start_time = strtotime($start['date_action']);
        $best_end = null;
        $min_time_diff = PHP_INT_MAX;
        
        // Chercher le log de fin le plus proche après ce démarrage
        foreach ($logs as $log) {
            if (in_array($log['action_type'], $end_action_types)) {
                $log_time = strtotime($log['date_action']);
                
                // Le log de fin doit être après le démarrage
                if ($log_time > $start_time) {
                    $time_diff = $log_time - $start_time;
                    
                    // Si c'est le log de fin le plus proche trouvé jusqu'à présent
                    if ($time_diff < $min_time_diff) {
                        $min_time_diff = $time_diff;
                        $best_end = $log;
                    }
                }
            }
        }
        
        // Ajouter cette séquence de démarrage-fin
        $sequences[] = [
            'start' => $start,
            'end' => $best_end
        ];
    }
    
    return $sequences;
}

// Fonction pour obtenir les données de démarrage et terminaison d'une réparation
function get_repair_start_end($logs, $attribution = null) {
    $start = null;
    $end = null;
    
    // Types d'actions considérés comme fin de réparation
    $end_action_types = ['terminer', 'changement_statut', 'ajout_note', 'modification', 'autre'];
    
    // D'abord chercher dans les logs
    foreach ($logs as $log) {
        if ($log['action_type'] === 'demarrage') {
            if (!$start || strtotime($log['date_action']) < strtotime($start['date_action'])) {
                $start = $log;
            }
        } else if (in_array($log['action_type'], $end_action_types)) {
            // Pour les actions de fin, on prend la plus récente
            if (!$end || strtotime($log['date_action']) > strtotime($end['date_action'])) {
                $end = $log;
            }
        }
    }
    
    return ['start' => $start, 'end' => $end];
}

// Fonction pour formater la date
function format_datetime($datetime) {
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i:s');
}

// Fonction pour obtenir une couleur en fonction du type d'action
function get_action_color($action_type, $log_source = 'repair', $details = '') {
    if ($log_source === 'task') {
        switch ($action_type) {
            case 'demarrer':
                return 'primary';
            case 'terminer':
                return 'success';
            case 'pause':
                return 'warning';
            case 'reprendre':
                return 'info';
            case 'modifier':
                return 'secondary';
            case 'creer':
                return 'success';
            case 'supprimer':
                return 'danger';
            default:
                return 'dark';
        }
    } else {
        switch ($action_type) {
            case 'demarrage':
                return 'primary';
            case 'terminer':
                return 'success';
            case 'changement_statut':
                return 'warning';
            case 'ajout_note':
                return 'info';
            case 'modification':
                return 'secondary';
            case 'autre':
                // Vérifier si c'est un log de type "Nouveau Dossier"
                if (strpos($details, 'Nouveau Dossier') !== false) {
                    return 'success'; // Couleur verte pour les nouveaux dossiers
                }
                return 'dark';
            default:
                return 'dark';
        }
    }
}

// Fonction pour obtenir une icône en fonction du type d'action
function get_action_icon($action_type, $log_source = 'repair', $details = '') {
    if ($log_source === 'task') {
        switch ($action_type) {
            case 'demarrer':
                return 'play-circle';
            case 'terminer':
                return 'check-circle';
            case 'pause':
                return 'pause-circle';
            case 'reprendre':
                return 'play-circle';
            case 'modifier':
                return 'edit';
            case 'creer':
                return 'plus-circle';
            case 'supprimer':
                return 'trash';
            default:
                return 'tasks';
        }
    } else {
        switch ($action_type) {
            case 'demarrage':
                return 'play-circle';
            case 'terminer':
                return 'stop-circle';
            case 'changement_statut':
                return 'exchange-alt';
            case 'ajout_note':
                return 'sticky-note';
            case 'modification':
                return 'edit';
            case 'autre':
                // Vérifier si c'est un log de type "Nouveau Dossier"
                if (strpos($details, 'Nouveau Dossier') !== false) {
                    return 'folder-plus'; // Icône dossier avec plus pour nouveaux dossiers
                }
                return 'cog';
            default:
                return 'cog';
        }
    }
}

// Fonction pour obtenir un libellé en fonction du type d'action
function get_action_label($action_type, $log_source = 'repair', $details = '') {
    if ($log_source === 'task') {
        switch ($action_type) {
            case 'demarrer':
                return 'Tâche démarrée';
            case 'terminer':
                return 'Tâche terminée';
            case 'pause':
                return 'Tâche en pause';
            case 'reprendre':
                return 'Tâche reprise';
            case 'modifier':
                return 'Tâche modifiée';
            case 'creer':
                return 'Tâche créée';
            case 'supprimer':
                return 'Tâche supprimée';
            default:
                return 'Action tâche';
        }
    } else {
        switch ($action_type) {
            case 'demarrage':
                return 'Démarrage';
            case 'terminer':
                return 'Terminé';
            case 'changement_statut':
                return 'Changement de statut';
            case 'ajout_note':
                return 'Ajout de note';
            case 'modification':
                return 'Modification';
            case 'autre':
                // Vérifier si c'est un log de type "Nouveau Dossier"
                if (strpos($details, 'Nouveau Dossier') !== false) {
                    return 'Nouveau Dossier';
                }
                return 'Autre';
            default:
                return 'Autre';
        }
    }
}

// Fonction pour obtenir la couleur de fond selon l'employé
function get_employe_background_color($employe_nom) {
    switch (strtolower($employe_nom)) {
        case 'admin':
            return 'bg-danger bg-opacity-10 text-danger';
        case 'rayan':
            return 'bg-primary bg-opacity-10 text-primary';
        case 'benjamin':
            return 'bg-success bg-opacity-10 text-success';
        default:
            return 'bg-secondary bg-opacity-10 text-secondary';
    }
}

// Fonction pour obtenir la couleur principale selon l'employé
function get_employe_color($employe_nom) {
    switch (strtolower($employe_nom)) {
        case 'admin':
            return 'danger';
        case 'rayan':
            return 'primary';
        case 'benjamin':
            return 'success';
        default:
            return 'secondary';
    }
}

// Fonction pour calculer le temps inactif entre deux réparations
function calculate_inactive_time($end_date, $start_date) {
    if (!$end_date || !$start_date) {
        return null;
    }
    
    $end = new DateTime($end_date);
    $start = new DateTime($start_date);
    
    if ($start <= $end) {
        // Les réparations se chevauchent ou se suivent immédiatement
        return '0min';
    }
    
    $interval = $end->diff($start);
    $total_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    
    if ($total_minutes <= 0) {
        return '0min';
    }
    
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'min';
    } else {
        return $minutes . 'min';
    }
}

// Fonction pour calculer le temps total de réparation par employé
function calculate_total_work_time($repairs) {
    $total_seconds = 0;
    
    foreach ($repairs as $repair) {
        $repair_data = get_repair_start_end($repair['logs']);
        $start = $repair_data['start'];
        $end = $repair_data['end'];
        
        if ($start && $end) {
            $start_time = new DateTime($start['date_action']);
            $end_time = new DateTime($end['date_action']);
            $diff = $end_time->getTimestamp() - $start_time->getTimestamp();
            $total_seconds += $diff;
        }
    }
    
    // Formater le temps total
    $hours = floor($total_seconds / 3600);
    $minutes = floor(($total_seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'min';
    } else {
        return $minutes . 'min';
    }
}

// Fonction pour calculer le temps total de travail à partir des interventions
function calculate_total_work_time_from_interventions($repairs) {
    $total_seconds = 0;
    
    foreach ($repairs as $repair) {
        $sequences = get_all_repair_sequences($repair['logs']);
        foreach ($sequences as $sequence) {
            if ($sequence['start']) {
                $start_time = strtotime($sequence['start']['date_action']);
                if ($sequence['end']) {
                    $end_time = strtotime($sequence['end']['date_action']);
                    $total_seconds += ($end_time - $start_time);
                }
            }
        }
    }
    
    // Formater le temps total
    $hours = floor($total_seconds / 3600);
    $minutes = floor(($total_seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'min';
    } else {
        return $minutes . 'min';
    }
}
?>

<!-- Styles personnalisés -->
<style>
    /* Styles de la timeline */
    :root {
        /* Variables pour le mode sombre */
        --dark-bg: #1a1d21;
        --dark-card-bg: #242830;
        --dark-text: #e2e8f0;
        --dark-text-secondary: #a0aec0;
        --dark-border: #374151;
        --dark-hover: #2d3748;
        --dark-timeline-line: #4a5568;
        --dark-box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    }

    body.dark-mode {
        background-color: var(--dark-bg);
        color: var(--dark-text);
    }

    .dark-mode-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        border: none;
        transition: all 0.3s ease;
    }

    .dark-mode-toggle:hover {
        transform: scale(1.1);
    }

    /* Timeline styles */
    .timeline {
        position: relative;
        padding: 1rem 0;
    }
    
    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        left: 18px;
        height: 100%;
        width: 4px;
        background: #e9ecef;
        border-radius: 2px;
    }

    .dark-mode .timeline:before {
        background: var(--dark-timeline-line);
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
        margin-left: 40px;
    }
    
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    
    .timeline-icon {
        position: absolute;
        left: -40px;
        top: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 1;
    }
    
    .timeline-content {
        padding: 1.5rem;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .dark-mode .timeline-content {
        background-color: var(--dark-card-bg);
        box-shadow: var(--dark-box-shadow);
        border: 1px solid var(--dark-border);
    }
    
    .timeline-date {
        display: block;
        margin-bottom: 0.75rem;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .dark-mode .timeline-date {
        color: var(--dark-text-secondary);
    }
    
    .timeline-title {
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .dark-mode .timeline-title {
        color: var(--dark-text);
    }
    
    .timeline-details {
        font-size: 0.95rem;
    }

    .dark-mode .timeline-details {
        color: var(--dark-text-secondary);
    }
    
    /* Styles des cartes */
    .card {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .dark-mode .card {
        background-color: var(--dark-card-bg);
        border-color: var(--dark-border);
    }
    
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.125);
    }

    .dark-mode .card-header {
        background-color: rgba(0,0,0,0.2);
        border-bottom: 1px solid var(--dark-border);
        color: var(--dark-text);
    }
    
    .filter-card {
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .dark-mode .filter-card {
        box-shadow: var(--dark-box-shadow);
    }
    
    .log-card {
        transition: all 0.3s ease;
    }
    
    .log-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .dark-mode .log-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .log-badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 500;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
    }
    
    /* Styles pour la timeline de réparation */
    .repair-timeline {
        width: 100%;
        position: relative;
        padding: 8px 0;
    }
    
    .repair-timeline-track {
        display: flex;
        align-items: center;
        position: relative;
        width: 100%;
        height: 24px;
    }
    
    .repair-timeline-track:before {
        content: '';
        position: absolute;
        top: 50%;
        left: 24px;
        right: 24px;
        height: 3px;
        background: linear-gradient(90deg, #28a745, #dc3545);
        border-radius: 3px;
        z-index: 1;
    }
    
    .repair-timeline-start, 
    .repair-timeline-end {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 0.75rem;
    }
    
    .repair-timeline-start i, 
    .repair-timeline-end i {
        background-color: white;
        border-radius: 50%;
        padding: 2px;
        font-size: 1rem;
    }

    .dark-mode .repair-timeline-start i, 
    .dark-mode .repair-timeline-end i {
        background-color: var(--dark-card-bg);
    }
    
    .repair-timeline-duration {
        position: absolute;
        top: -18px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 2px 8px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        z-index: 2;
    }

    .dark-mode .repair-timeline-duration {
        background-color: var(--dark-card-bg);
        border: 1px solid var(--dark-border);
        color: var(--dark-text);
    }
    
    /* Styles pour les tableaux des employés */
    .employee-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 20px;
        height: 100%;
    }
    
    .employee-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }

    .dark-mode .employee-card:hover {
        box-shadow: 0 10px 20px rgba(0,0,0,0.3) !important;
    }
    
    .employee-card .card-header {
        padding: 1rem;
        border-bottom: 2px solid rgba(0,0,0,0.1);
    }

    .dark-mode .employee-card .card-header {
        border-bottom: 2px solid var(--dark-border);
    }
    
    .employee-card .table {
        margin-bottom: 0;
    }

    .dark-mode .table {
        color: var(--dark-text);
    }
    
    .employee-card .table th {
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .dark-mode .employee-card .table th {
        color: var(--dark-text-secondary);
    }
    
    .employee-card .table td {
        vertical-align: middle;
        padding: 0.75rem;
    }
    
    .employee-stats {
        background-color: rgba(0,0,0,0.03);
        border-top: 1px solid rgba(0,0,0,0.125);
        padding: 0.75rem 1rem;
    }

    .dark-mode .employee-stats {
        background-color: rgba(0,0,0,0.2);
        border-top: 1px solid var(--dark-border);
    }
    
    .stats-badge {
        display: inline-flex;
        align-items: center;
        background-color: rgba(255,255,255,0.8);
        border-radius: 1rem;
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        margin-right: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .dark-mode .stats-badge {
        background-color: rgba(255,255,255,0.1);
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        color: var(--dark-text);
    }
    
    .stats-badge i {
        margin-right: 0.35rem;
    }
    
    /* Style pour les temps inactifs */
    .inactive-time-row {
        position: relative;
        background-color: #f0f5ff !important;
        height: 40px;
    }

    .dark-mode .inactive-time-row {
        background-color: rgba(67, 97, 238, 0.1) !important;
    }
    
    .inactive-time-row::after {
        content: '';
        position: absolute;
        left: 5%;
        right: 5%;
        top: 50%;
        border-top: 2px dashed #adb5bd;
        z-index: 1;
    }

    .dark-mode .inactive-time-row::after {
        border-top: 2px dashed var(--dark-text-secondary);
    }
    
    .inactive-time-badge {
        position: relative;
        z-index: 2;
        background-color: #ffffff;
        border: 1px solid #007bff;
        padding: 4px 12px;
        border-radius: 1rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: #007bff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .dark-mode .inactive-time-badge {
        background-color: var(--dark-card-bg);
        border: 1px solid #4361ee;
        color: #4361ee;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    
    @media (max-width: 767.98px) {
        .timeline-item {
            margin-left: 30px;
        }
        
        .timeline:before {
            left: 15px;
        }
        
        .timeline-icon {
            left: -30px;
            width: 30px;
            height: 30px;
            font-size: 0.9rem;
        }
        
        .employee-card {
            margin-bottom: 1.5rem;
        }
    }
    
    /* Styles pour le menu d'onglets */
    .nav-tabs .nav-link {
        border-radius: 0.5rem 0.5rem 0 0;
        padding: 0.75rem 1.25rem;
        font-weight: 500;
    }

    .dark-mode .nav-tabs {
        border-bottom: 1px solid var(--dark-border);
    }
    
    .dark-mode .nav-tabs .nav-link {
        color: var(--dark-text-secondary);
    }
    
    .nav-tabs .nav-link.active {
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }

    .dark-mode .nav-tabs .nav-link.active {
        background-color: var(--dark-card-bg);
        border-color: var(--dark-border) var(--dark-border) var(--dark-card-bg);
        color: var(--dark-text);
    }

    .dark-mode .nav-tabs .nav-link:hover:not(.active) {
        background-color: var(--dark-hover);
        border-color: var(--dark-border) var(--dark-border) var(--dark-border);
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in-up {
        animation: fadeInUp 0.5s ease forwards;
    }

    /* Mode sombre pour les formulaires */
    .dark-mode .form-control,
    .dark-mode .form-select {
        background-color: var(--dark-card-bg);
        border-color: var(--dark-border);
        color: var(--dark-text);
    }

    .dark-mode .form-control:focus,
    .dark-mode .form-select:focus {
        border-color: #4361ee;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
        background-color: var(--dark-card-bg);
    }

    .dark-mode .form-label {
        color: var(--dark-text);
    }

    /* Mode sombre pour les listes */
    .dark-mode .list-group-item {
        background-color: var(--dark-card-bg);
        border-color: var(--dark-border);
        color: var(--dark-text);
    }

    /* Mode sombre pour les badges */
    .dark-mode .badge.bg-secondary {
        background-color: #4a5568 !important;
    }

    /* Mode sombre pour les tableaux */
    .dark-mode .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(255,255,255,0.05);
    }

    .dark-mode .table-hover tbody tr:hover {
        background-color: rgba(67, 97, 238, 0.1);
    }

    /* Styles pour les groupes de logs */
    .group-card {
        border-left: 4px solid #007bff;
        transition: all 0.3s ease;
    }
    
    .group-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .dark-mode .group-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }
    
    .group-card .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .dark-mode .group-card .card-header {
        background: linear-gradient(135deg, var(--dark-card-bg) 0%, rgba(0,0,0,0.3) 100%);
        border-bottom: 1px solid var(--dark-border);
    }
    
    /* Timeline compacte pour les groupes */
    .timeline-item-sm {
        margin-bottom: 1rem;
        margin-left: 30px;
    }
    
    .timeline-item-sm .timeline-icon {
        left: -30px;
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
    
    .timeline-item-sm .timeline-content {
        padding: 1rem;
        font-size: 0.9rem;
    }
    
    /* Badges plus petits */
    .badge-sm {
        font-size: 0.65em;
        padding: 0.25em 0.5em;
    }
    
    /* Styles pour les filtres améliorés */
    .filter-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .dark-mode .filter-card {
        background: linear-gradient(135deg, var(--dark-card-bg) 0%, rgba(0,0,0,0.2) 100%);
        box-shadow: var(--dark-box-shadow);
    }
    
    .filter-card .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .dark-mode .filter-card .card-header {
        border-bottom: 1px solid var(--dark-border);
    }
    
    /* Boutons radio améliorés */
    .btn-check:checked + .btn-outline-primary {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
        box-shadow: 0 2px 8px rgba(0,123,255,0.3);
    }

    .dark-mode .btn-check:checked + .btn-outline-primary {
        background-color: #4361ee;
        border-color: #4361ee;
        box-shadow: 0 2px 8px rgba(67,97,238,0.3);
    }
    
    /* Animation pour les formulaires */
    .form-control:focus, .form-select:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,123,255,0.15);
    }

    .dark-mode .form-control:focus, 
    .dark-mode .form-select:focus {
        box-shadow: 0 4px 12px rgba(67,97,238,0.25);
    }
    
    /* Mode sombre pour les onglets */
    .dark-mode .tab-content-custom {
        background-color: #000000 !important; /* Noir au lieu de gris clair */
    }
    
    .dark-mode .tab-content-custom .card {
        background-color: #1a1a1a;
        border-color: var(--dark-border);
    }
    
    .dark-mode .tab-content-custom .card-body {
        background-color: #000000 !important; /* Fond noir pour le contenu */
        color: var(--dark-text);
    }
    
    .dark-mode .tab-content-custom .card-header {
        background-color: #0f0f0f;
        border-bottom: 1px solid var(--dark-border);
        color: var(--dark-text);
    }
    
    /* Styles spéciaux pour les cartes d'employés en mode nuit */
    .dark-mode #employees-content .card {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
        border: 1px solid #444 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5) !important;
    }
    
    .dark-mode #employees-content .card-body {
        background: #000000 !important; /* Fond noir pour la zone des cartes employés */
    }
    
    /* Cartes employés individuelles avec couleurs */
    .dark-mode .employee-summary-card {
        background: linear-gradient(135deg, #2d1b69 0%, #11998e 100%) !important; /* Dégradé violet-turquoise */
        border: 1px solid #667eea !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3) !important;
        transition: all 0.3s ease !important;
    }
    
    .dark-mode .employee-summary-card:hover {
        transform: translateY(-8px) !important;
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4) !important;
        border-color: #764ba2 !important;
    }
    
    .dark-mode .employee-summary-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; /* Dégradé violet-bleu */
        color: #ffffff !important;
        border-bottom: 2px solid rgba(255, 255, 255, 0.2) !important;
    }
    
    .dark-mode .employee-summary-card .card-body {
        background: rgba(0, 0, 0, 0.7) !important; /* Fond semi-transparent */
        color: #ffffff !important;
        backdrop-filter: blur(10px) !important;
    }
    
    /* Couleurs pour les badges et statistiques des employés en mode nuit */
    .dark-mode .employee-summary-card .badge {
        background: linear-gradient(45deg, #ff6b6b, #ee5a24) !important; /* Rouge-orange */
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3) !important;
    }
    
    .dark-mode .employee-summary-card .badge.bg-success {
        background: linear-gradient(45deg, #00d2d3, #54a0ff) !important; /* Turquoise-bleu */
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(0, 210, 211, 0.3) !important;
    }
    
    .dark-mode .employee-summary-card .badge.bg-warning {
        background: linear-gradient(45deg, #feca57, #ff9ff3) !important; /* Jaune-rose */
        color: #2d3436 !important;
        box-shadow: 0 2px 8px rgba(254, 202, 87, 0.3) !important;
    }
    
    .dark-mode .employee-summary-card .badge.bg-info {
        background: linear-gradient(45deg, #74b9ff, #0984e3) !important; /* Bleu clair-bleu */
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(116, 185, 255, 0.3) !important;
    }
    
    /* Couleurs pour les textes et éléments dans les cartes employés */
    .dark-mode .employee-summary-card .text-muted {
        color: #a0a0a0 !important; /* Gris clair au lieu de muted */
    }
    
    .dark-mode .employee-summary-card .text-primary {
        color: #74b9ff !important; /* Bleu clair */
    }
    
    .dark-mode .employee-summary-card .text-success {
        color: #00d2d3 !important; /* Turquoise */
    }
    
    .dark-mode .employee-summary-card .text-warning {
        color: #feca57 !important; /* Jaune */
    }
    
    /* Icônes colorées pour les cartes employés en mode nuit */
    .dark-mode .employee-summary-card .fas {
        color: #74b9ff !important; /* Bleu pour les icônes */
        text-shadow: 0 1px 3px rgba(116, 185, 255, 0.5) !important;
    }
    
    /* Mode sombre pour la frise chronologique */
    .dark-mode .timeline-modern {
        background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
        border: 1px solid var(--dark-border);
    }
    
    /* Amélioration des couleurs en mode clair */
    .tab-content-custom .card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
        border: 1px solid #e3e8ff;
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.08);
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .tab-content-custom .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15);
    }
    
    .tab-content-custom .card-body {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
        color: #2d3748;
        padding: 2rem;
    }
    
    .tab-content-custom .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        border-radius: 12px 12px 0 0;
        padding: 1.25rem 2rem;
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    /* Amélioration de la frise chronologique en mode clair */
    .timeline-modern {
        background: #f1f3f5; /* Gris clair */
        border: 1px solid #e3e6ea;
        box-shadow: 0 6px 24px rgba(0,0,0,0.08);
        position: relative;
        overflow: hidden;
    }
    
    /* Amélioration des contrôles de la timeline */
    .timeline-controls {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 1.5rem;
        margin: 1.5rem 0;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    .control-input {
        background: white;
        border: 2px solid #e3e8ff;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.05);
    }
    
    .control-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
    }
    
    .btn-timeline-modern {
        background: #e9ecef;
        color: #222;
        border: 1px solid #dfe3e6;
        border-radius: 12px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    
    .btn-timeline-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        background: #f1f3f5;
    }
    
    /* Boutons de sélection rapide */
    .quick-select-buttons {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .btn-quick-select {
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-quick-select:hover {
        background: #5a6fd8;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-quick-select-custom {
        background: #28a745 !important;
        border-color: #28a745 !important;
    }
    
    .btn-quick-select-custom:hover {
        background: #218838 !important;
        border-color: #1e7e34 !important;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3) !important;
    }
    
    /* Date Range Picker Personnalisé */
    .date-range-container {
        position: relative;
    }
    
    .date-range-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .date-range-display:hover {
        border-color: #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }
    
    .date-range-picker {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        z-index: 1000;
        padding: 15px;
        margin-top: 5px;
    }
    
    .date-picker-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .btn-nav {
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-nav:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        margin-bottom: 15px;
    }
    
    .calendar-day {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 13px;
    }
    
    .calendar-day:hover {
        background: #f0f0f0;
    }
    
    .calendar-day.selected-start {
        background: #667eea;
        color: white;
    }
    
    .calendar-day.selected-end {
        background: #667eea;
        color: white;
    }
    
    .calendar-day.in-range {
        background: rgba(102, 126, 234, 0.2);
        color: #333;
    }
    
    .calendar-day.other-month {
        color: #ccc;
    }
    
    .calendar-day.today {
        border: 2px solid #667eea;
        font-weight: bold;
    }
    
    .calendar-header {
        font-weight: bold;
        color: #666;
        padding: 5px;
        text-align: center;
        font-size: 12px;
    }
    
    .date-picker-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }
    
    /* Styles pour le modal de sélection de période */
    .date-range-container-modal {
        max-width: 100%;
    }
    
    .date-range-picker-modal {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
    }
    
    .selected-period-display {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .selected-date {
        font-weight: bold;
        color: #28a745;
        padding: 8px 12px;
        background: white;
        border: 1px solid #28a745;
        border-radius: 4px;
        text-align: center;
    }
    
    .selected-date.empty {
        color: #6c757d;
        border-color: #6c757d;
    }
    
    /* Styles pour le modal de sélection de période timeline */
    .date-range-container-timeline {
        max-width: 100%;
    }
    
    .date-range-picker-timeline {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
    }
    
    /* Styles pour la section de recherche timeline */
    .timeline-search-section {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 20px;
        margin: 15px 0;
        border: 1px solid rgba(255,255,255,0.3);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .search-header {
        margin-bottom: 15px;
    }
    
    .search-title {
        color: #2c3e50;
        font-weight: 600;
        margin: 0;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
    }
    
    .search-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .search-input {
        flex: 1;
        min-width: 250px;
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .search-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        background: #f8f9ff;
    }
    
    .btn-search-repair {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        white-space: nowrap;
    }
    
    .btn-search-repair:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4c93 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
    }
    
    .btn-clear-search {
        background: #dc3545;
        color: white;
        border: none;
        padding: 12px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-clear-search:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
    }
    
    .search-results {
        margin-top: 15px;
        padding: 15px;
        background: rgba(255,255,255,0.8);
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .search-result-item {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 10px;
        background: white;
        border-left: 4px solid #667eea;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .search-result-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    
    .search-result-item:last-child {
        margin-bottom: 0;
    }
    
    .search-result-repair {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.95rem;
    }
    
    .search-result-details {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .search-no-results {
        text-align: center;
        padding: 20px;
        color: #6c757d;
        font-style: italic;
    }
    
    /* Mode sombre pour la recherche */
    body.dark-mode .timeline-search-section {
        background: rgba(45, 45, 45, 0.95);
        border-color: rgba(255,255,255,0.1);
    }
    
    body.dark-mode .search-title {
        color: #f8f9fa;
    }
    
    body.dark-mode .search-input {
        background: #374151;
        border-color: #4b5563;
        color: #f8f9fa;
    }
    
    body.dark-mode .search-input:focus {
        background: #4b5563;
        border-color: #667eea;
    }
    
    body.dark-mode .search-results {
        background: rgba(55, 65, 81, 0.8);
        border-color: #4b5563;
    }
    
    body.dark-mode .search-result-item {
        background: #374151;
        color: #f8f9fa;
    }
    
    body.dark-mode .search-result-repair {
        color: #f8f9fa;
    }
    
    /* Surbrillance des événements */
    .timeline-event.highlighted {
        background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%) !important;
        border-left: 4px solid #f39c12 !important;
        transform: scale(1.02);
        box-shadow: 0 8px 25px rgba(243, 156, 18, 0.3) !important;
        animation: highlightPulse 0.5s ease-in-out;
    }
    
    body.dark-mode .timeline-event.highlighted {
        background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%) !important;
        color: #2c3e50 !important;
    }
    
    @keyframes highlightPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1.02); }
    }
    
    .search-mode-header {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        background: rgba(102, 126, 234, 0.1);
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .search-results-header {
        padding: 10px 0;
        border-bottom: 2px solid #667eea;
        margin-bottom: 15px;
        color: #2c3e50;
        font-size: 0.95rem;
    }
    
    body.dark-mode .search-results-header {
        color: #f8f9fa;
        border-color: #667eea;
    }
    
    /* Amélioration des événements de la timeline */
    .timeline-event {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.1);
        transition: all 0.3s ease;
    }
    
    .timeline-event:hover {
        transform: translateX(10px);
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.2);
    }
    
    /* Styles pour les cartes d'employés cliquables */
    .employee-clickable {
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .employee-clickable:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
    }
    
    .employee-clickable::after {
        content: '👁️ Voir la frise chronologique';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(102, 126, 234, 0.9);
        color: white;
        padding: 8px;
        text-align: center;
        font-size: 0.85em;
        font-weight: 600;
        opacity: 0;
        transform: translateY(100%);
        transition: all 0.3s ease;
    }
    
    .employee-clickable:hover::after {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* Pagination moderne */
    .pagination {
        gap: 0.25rem;
    }
    
    .pagination .page-link {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        color: #6c757d;
        padding: 0.5rem 0.75rem;
        transition: all 0.3s ease;
        margin: 0 2px;
    }
    
    .pagination .page-link:hover {
        background-color: #667eea;
        border-color: #667eea;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
    }
    
    .pagination .page-item.active .page-link {
        background-color: #667eea;
        border-color: #667eea;
        color: white;
        font-weight: bold;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    
    .pagination .page-item.disabled .page-link {
        color: #adb5bd;
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    
    .page-link {
        border-radius: 0.5rem;
        border: none;
        padding: 0.5rem 0.75rem;
        margin: 0 0.125rem;
        background-color: #f8f9fa;
        color: #6c757d;
        transition: all 0.3s ease;
    }
    
    .page-link:hover {
        background-color: #007bff;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,123,255,0.3);
    }
    
    .page-item.active .page-link {
        background-color: #007bff;
        color: white;
        box-shadow: 0 4px 12px rgba(0,123,255,0.4);
    }

    .dark-mode .page-link {
        background-color: var(--dark-card-bg);
        color: var(--dark-text-secondary);
        border-color: var(--dark-border);
    }

    .dark-mode .page-link:hover,
    .dark-mode .page-item.active .page-link {
        background-color: #4361ee;
        color: white;
    }
    
    /* Responsive amélioré */
    @media (max-width: 768px) {
        .filter-card .row .col-md-3,
        .filter-card .row .col-md-4,
        .filter-card .row .col-md-6 {
            margin-bottom: 1rem;
        }
        
        .btn-group {
            flex-wrap: wrap;
        }
        
        .btn-group .btn {
            margin-bottom: 0.25rem;
        }
        
        .timeline-item-sm {
            margin-left: 20px;
        }
        
        .timeline-item-sm .timeline-icon {
            left: -20px;
            width: 24px;
            height: 24px;
            font-size: 0.7rem;
        }
    }

    /* Effet de transition lors du changement de mode */
    body, .card, .timeline-content, .form-control, .form-select,
    .table, .nav-tabs, .timeline:before, .badge, .btn, .timeline-icon,
    .card-header, .employee-stats, .inactive-time-row, .inactive-time-badge,
    .repair-timeline-start i, .repair-timeline-end i, .repair-timeline-duration,
    .group-card, .filter-card, .page-link {
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }
</style>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <!-- Loader Mode Sombre (par défaut) -->
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    
    <!-- Loader Mode Clair -->
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<div class="container-fluid py-4" id="mainContent" style="display: none;">
    <div class="row">
        <div class="col-12">
            <!-- En-tête de page -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-history me-2"></i>
                    Logs des réparations
                </h1>
                <a href="index.php?page=reparations" class="btn btn-outline-primary">
                    <i class="fas fa-tools me-1"></i>
                    Retour aux réparations
                </a>
            </div>

            <!-- Afficher les messages -->
            <?php echo display_message(); ?>

            <!-- Barre de filtres améliorée -->
            <div class="card filter-card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-link p-0 text-decoration-none" aria-expanded="false" aria-controls="filtersContent" id="filtersToggle">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-filter me-2"></i>
                                Filtres et Options d'affichage
                                <i class="fas fa-chevron-down ms-2" id="filtersChevron"></i>
                            </h5>
                        </button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetFilters">
                                <i class="fas fa-undo me-1"></i>
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>
                <div class="collapse" id="filtersContent" style="transition: all 0.3s ease;">
                    <div class="card-body">
                    <form method="GET" action="index.php" id="filterForm">
                        <input type="hidden" name="page" value="reparation_logs">
                        
                        <!-- Filtres par type de log -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-layer-group me-1"></i>
                                    Type de logs
                                </label>
                                <div class="btn-group me-3" role="group">
                                    <input type="radio" class="btn-check" name="log_type" id="log_all" value="all" <?php echo ($log_type === 'all') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-success" for="log_all">
                                        <i class="fas fa-list me-1"></i>Tout
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="log_type" id="log_repairs" value="repairs" <?php echo ($log_type === 'repairs') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="log_repairs">
                                        <i class="fas fa-tools me-1"></i>Réparations
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="log_type" id="log_tasks" value="tasks" <?php echo ($log_type === 'tasks') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-warning" for="log_tasks">
                                        <i class="fas fa-tasks me-1"></i>Tâches
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filtres rapides par période -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-bolt me-1"></i>
                                    Filtres rapides
                                </label>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_all" value="" <?php echo empty($_GET['quick_filter']) ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_all">Tout</label>
                                    
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_today" value="today" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'today') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_today">Aujourd'hui</label>
                                    
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_yesterday" value="yesterday" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'yesterday') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_yesterday">Hier</label>
                                    
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_week" value="week" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'week') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_week">Cette semaine</label>
                                    
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_month" value="month" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'month') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_month">Ce mois</label>
                                    
                                    <!-- Bouton Personnalisé -->
                                    <button type="button" class="btn btn-outline-success" id="btnPersonnalise" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Personnalisé
                                    </button>
                                    
                                    <!-- Champs cachés pour la soumission du formulaire -->
                                    <input type="hidden" name="date_debut" id="date_debut" value="<?php echo $date_debut; ?>">
                                    <input type="hidden" name="date_fin" id="date_fin" value="<?php echo $date_fin; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filtres principaux -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="employe_id" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Employé
                                </label>
                                <select name="employe_id" id="employe_id" class="form-select">
                                    <option value="">Tous les employés</option>
                                    <?php foreach ($employes as $employe): ?>
                                        <option value="<?php echo $employe['id']; ?>" <?php echo ($employe_id == $employe['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employe['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="action_type" class="form-label">
                                    <i class="fas fa-cogs me-1"></i>
                                    Type d'action
                                </label>
                                <select name="action_type" id="action_type" class="form-select">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($action_types as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo ($action_type === $type) ? 'selected' : ''; ?>>
                                            <?php echo get_action_label($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="reparation_id" class="form-label">
                                    <i class="fas fa-tools me-1"></i>
                                    Réparation #
                                </label>
                                <input type="number" name="reparation_id" id="reparation_id" class="form-control" 
                                       value="<?php echo $reparation_id > 0 ? $reparation_id : ''; ?>" 
                                       placeholder="ID...">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="search_term" class="form-label">
                                    <i class="fas fa-search me-1"></i>
                                    Recherche
                                </label>
                                <input type="text" name="search_term" id="search_term" class="form-control" 
                                       value="<?php echo htmlspecialchars($search_term); ?>" 
                                       placeholder="Client, appareil, détails...">
                            </div>
                        </div>
                        
                        <!-- Options d'affichage -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="view_mode" class="form-label">
                                    <i class="fas fa-eye me-1"></i>
                                    Mode d'affichage
                                </label>
                                <select name="view_mode" id="view_mode" class="form-select">
                                    <option value="timeline" <?php echo ($view_mode === 'timeline') ? 'selected' : ''; ?>>Timeline</option>
                                    <option value="employees" <?php echo ($view_mode === 'employees') ? 'selected' : ''; ?>>Par employé</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="group_by" class="form-label">
                                    <i class="fas fa-layer-group me-1"></i>
                                    Grouper par
                                </label>
                                <select name="group_by" id="group_by" class="form-select">
                                    <option value="none" <?php echo ($group_by === 'none') ? 'selected' : ''; ?>>Aucun</option>
                                    <option value="date" <?php echo ($group_by === 'date') ? 'selected' : ''; ?>>Date</option>
                                    <option value="repair" <?php echo ($group_by === 'repair') ? 'selected' : ''; ?>>Réparation</option>
                                    <option value="employee" <?php echo ($group_by === 'employee') ? 'selected' : ''; ?>>Employé</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="sort_by" class="form-label">
                                    <i class="fas fa-sort me-1"></i>
                                    Trier par
                                </label>
                                <select name="sort_by" id="sort_by" class="form-select">
                                    <option value="date_action" <?php echo ($sort_by === 'date_action') ? 'selected' : ''; ?>>Date</option>
                                    <option value="employe_nom" <?php echo ($sort_by === 'employe_nom') ? 'selected' : ''; ?>>Employé</option>
                                    <option value="action_type" <?php echo ($sort_by === 'action_type') ? 'selected' : ''; ?>>Type d'action</option>
                                    <option value="reparation_id" <?php echo ($sort_by === 'reparation_id') ? 'selected' : ''; ?>>Réparation</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="sort_order" class="form-label">
                                    <i class="fas fa-sort-amount-down me-1"></i>
                                    Ordre
                                </label>
                                <select name="sort_order" id="sort_order" class="form-select">
                                    <option value="DESC" <?php echo ($sort_order === 'DESC') ? 'selected' : ''; ?>>Décroissant</option>
                                    <option value="ASC" <?php echo ($sort_order === 'ASC') ? 'selected' : ''; ?>>Croissant</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Bouton pour les filtres avancés -->
                        <div class="row mb-3">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                                    <i class="fas fa-cog me-1"></i>
                                    Filtres avancés
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filtres avancés (collapsible) -->
                        <div class="collapse" id="advancedFilters">
                            <hr>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-clock me-1"></i>
                                        Heures (optionnel)
                                    </label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="heure_debut" class="form-label small">De</label>
                                            <input type="time" name="heure_debut" id="heure_debut" class="form-control form-control-sm" 
                                                   value="<?php echo $heure_debut; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label for="heure_fin" class="form-label small">À</label>
                                            <input type="time" name="heure_fin" id="heure_fin" class="form-control form-control-sm" 
                                                   value="<?php echo $heure_fin; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>
                                    Appliquer les filtres
                                </button>
                                <a href="index.php?page=reparation_logs" class="btn btn-outline-secondary">
                                    <i class="fas fa-eraser me-1"></i>
                                    Effacer
                                </a>
                            </div>
                            
                            <!-- Options de pagination -->
                            <?php if ($view_mode === 'timeline'): ?>
                            <div class="d-flex align-items-center gap-2">
                                <label for="limit" class="form-label mb-0 small">Éléments par page:</label>
                                <select name="limit" id="limit" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                    <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>10</option>
                                    <option value="15" <?php echo ($limit == 15) ? 'selected' : ''; ?>>15</option>
                                    <option value="20" <?php echo ($limit == 20) ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                    </div>
                </div>
            </div>

            <!-- CSS pour les onglets personnalisés et filtres collapsibles -->
            <style>
                /* CSS pour les filtres collapsibles */
                #filtersContent {
                    overflow: hidden;
                    max-height: 0;
                    transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
                    opacity: 0;
                }
                
                #filtersContent.show {
                    max-height: 2000px; /* Valeur suffisamment grande */
                    opacity: 1;
                    transition: max-height 0.3s ease-in, opacity 0.3s ease-in;
                }
                
                #filtersChevron {
                    transition: transform 0.3s ease;
                }
                
                .custom-tabs {
                    display: flex;
                    background: #f8f9fa;
                    border-radius: 8px;
                    padding: 4px;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .custom-tab {
                    flex: 1;
                    padding: 12px 20px;
                    background: transparent;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    color: #6c757d;
                }
                
                .custom-tab:hover {
                    background: rgba(13, 110, 253, 0.1);
                    color: #0d6efd;
                }
                
                .custom-tab.active {
                    background: #0d6efd;
                    color: white;
                    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
                }
                
                .custom-tab .badge {
                    background: rgba(255,255,255,0.2);
                    color: inherit;
                    padding: 4px 8px;
                    border-radius: 12px;
                    font-size: 0.75em;
                    font-weight: 600;
                }
                
                .custom-tab.active .badge {
                    background: rgba(255,255,255,0.3);
                }
                
                .tab-content-custom {
                    display: none;
                }
                
                .tab-content-custom.active {
                    display: block;
                    animation: fadeIn 0.3s ease;
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                /* Styles pour la frise chronologique des employés */
                .timeline-container-employee {
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    padding: 20px;
                    margin: 20px 0;
                }
                
                .timeline-track-employee {
                    position: relative;
                    height: 60px;
                    background: #ffffff;
                    border-radius: 8px;
                    margin: 15px 0;
                    border: 2px solid #e9ecef;
                    overflow: visible;
                    padding: 5px;
                }
                
                .timeline-hours-employee {
                    position: absolute;
                    top: -30px;
                    left: 0;
                    right: 0;
                    height: 25px;
                    display: flex;
                }
                
                .hour-mark-employee {
                    flex: 1;
                    font-size: 11px;
                    text-align: center;
                    color: #495057;
                    border-left: 1px solid #dee2e6;
                    padding-top: 2px;
                    font-weight: 500;
                }
                
                .timeline-event-employee {
                    position: absolute;
                    top: 5px;
                    height: 50px;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                    color: white;
                    padding: 0 12px;
                    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
                    cursor: pointer;
                    z-index: 10;
                    border: 2px solid rgba(255,255,255,0.3);
                    min-width: 80px;
                }
                
                .event-clock-in-employee { 
                    background: linear-gradient(135deg, #28a745, #34ce57); 
                    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
                }
                .event-clock-out-employee { 
                    background: linear-gradient(135deg, #dc3545, #e85d75); 
                    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
                }
                .event-break-start-employee { 
                    background: linear-gradient(135deg, #ffc107, #ffcd39); 
                    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
                    color: #212529 !important;
                }
                .event-break-end-employee { 
                    background: linear-gradient(135deg, #17a2b8, #3dd5f3); 
                    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
                }
                .event-repair-start-employee { 
                    background: linear-gradient(135deg, #007bff, #4dabf7); 
                    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
                }
                .event-repair-end-employee { 
                    background: linear-gradient(135deg, #6c757d, #8d959f); 
                    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
                }
                
                <style>
                /* Styles pour la frise chronologique moderne */
                .timeline-modern {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 20px;
                    padding: 30px;
                    margin: 20px 0;
                    color: white;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                }
                
                .timeline-header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .timeline-title {
                    font-size: 2.5em;
                    font-weight: 700;
                    margin: 0 0 10px 0;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                }
                
                .timeline-subtitle {
                    font-size: 1.1em;
                    opacity: 0.9;
                    margin: 0;
                }
                
                .timeline-controls {
                    background: rgba(255,255,255,0.1);
                    backdrop-filter: blur(10px);
                    border-radius: 15px;
                    padding: 25px;
                    margin-bottom: 30px;
                    border: 1px solid rgba(255,255,255,0.2);
                }
                
                .controls-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr auto;
                    gap: 20px;
                    align-items: end;
                }
                
                .control-group {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                
                .control-label {
                    font-weight: 600;
                    font-size: 0.9em;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    opacity: 0.9;
                }
                
                .control-input {
                    background: rgba(255,255,255,0.9);
                    border: none;
                    border-radius: 10px;
                    padding: 12px 15px;
                    font-size: 1em;
                    color: #333;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                }
                
                .control-input:focus {
                    outline: none;
                    transform: translateY(-2px);
                    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                    background: white;
                }
                
                .btn-timeline-modern {
                    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
                    color: white;
                    border: none;
                    border-radius: 12px;
                    padding: 12px 25px;
                    font-weight: 600;
                    font-size: 1em;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(238, 90, 36, 0.4);
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                
                .btn-timeline-modern:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 8px 25px rgba(238, 90, 36, 0.6);
                }
                
                .timeline-container {
                    background: rgba(255,255,255,0.05);
                    backdrop-filter: blur(10px);
                    border-radius: 15px;
                    padding: 30px;
                    border: 1px solid rgba(255,255,255,0.1);
                    min-height: 400px;
                }
                
                .timeline-date-header {
                    text-align: center;
                    font-size: 1.5em;
                    font-weight: 600;
                    margin-bottom: 30px;
                    padding: 15px;
                    background: rgba(255,255,255,0.1);
                    border-radius: 10px;
                    border: 1px solid rgba(255,255,255,0.2);
                }
                
                .timeline-line {
                    position: relative;
                    padding-left: 40px;
                    margin: 20px 0;
                }
                
                .timeline-line::before {
                    content: '';
                    position: absolute;
                    left: 20px;
                    top: 0;
                    bottom: 0;
                    width: 3px;
                    background: linear-gradient(to bottom, #fff, rgba(255,255,255,0.3));
                    border-radius: 2px;
                }
                
                .timeline-event {
                    position: relative;
                    background: rgba(255,255,255,0.1);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255,255,255,0.2);
                    border-radius: 15px;
                    padding: 20px;
                    margin-bottom: 20px;
                    margin-left: 30px;
                    transition: all 0.3s ease;
                }
                
                .timeline-event:hover {
                    background: rgba(255,255,255,0.2);
                    transform: translateX(10px);
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                }
                
                .timeline-event::before {
                    content: '';
                    position: absolute;
                    left: -41px;
                    top: 25px;
                    width: 16px;
                    height: 16px;
                    background: #fff;
                    border: 4px solid;
                    border-radius: 50%;
                    box-shadow: 0 0 0 4px rgba(255,255,255,0.3);
                }
                
                /* Couleurs pour les types d'événements */
                .event-demarrage::before { border-color: #00d4aa; }
                .event-terminer::before { border-color: #ff6b6b; }
                .event-changement_statut::before { border-color: #4ecdc4; }
                .event-ajout_note::before { border-color: #45b7d1; }
                .event-autre::before { border-color: #f9ca24; }
                
                /* Couleurs pour les pointages */
                .event-arrivee::before { border-color: #2ecc71; }
                .event-depart::before { border-color: #e74c3c; }
                
                /* Couleurs pour les pauses */
                .event-pause_debut::before { border-color: #f39c12; }
                .event-pause_fin::before { border-color: #27ae60; }
                
                /* Couleurs pour les tâches */
                .event-creation_tache::before { border-color: #9b59b6; }
                
                .event-time {
                    font-size: 1.2em;
                    font-weight: 700;
                    color: #fff;
                    margin-bottom: 8px;
                }
                
                .event-title {
                    font-size: 1.1em;
                    font-weight: 600;
                    margin-bottom: 5px;
                    color: #fff;
                }
                
                .event-subtitle {
                    font-size: 0.9em;
                    opacity: 0.8;
                    margin-bottom: 10px;
                }
                
                .event-employee {
                    font-size: 0.85em;
                    opacity: 0.7;
                    font-style: italic;
                }
                
                .event-details {
                    font-size: 0.9em;
                    opacity: 0.8;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 1px solid rgba(255,255,255,0.2);
                    white-space: pre-line; /* Permet d'afficher les retours à la ligne \n */
                    word-wrap: break-word;
                    line-height: 1.4;
                }
                
                .timeline-interruption {
                    display: block;
                    margin: 20px 0;
                    position: relative;
                    text-align: left;
                    padding-left: 30px;
                }
                
                .interruption-line {
                    position: absolute;
                    left: 150px;
                    right: 0;
                    top: 50%;
                    height: 2px;
                    background: linear-gradient(90deg, rgba(255,255,255,0.3), transparent);
                }
                
                .interruption-time {
                    background: rgba(255,255,255,0.1);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255,255,255,0.2);
                    border-radius: 20px;
                    padding: 8px 16px;
                    font-size: 0.85em;
                    color: rgba(255,255,255,0.8);
                    font-weight: 600;
                    white-space: nowrap;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                    animation: fadeInScale 0.5s ease-out;
                    display: inline-block;
                    position: relative;
                    z-index: 2;
                    margin-left: 0;
                }
                
                @keyframes fadeInScale {
                    from {
                        opacity: 0;
                        transform: scale(0.8);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
                
                .no-events {
                    text-align: center;
                    padding: 60px 20px;
                    opacity: 0.7;
                }
                
                .no-events i {
                    font-size: 4em;
                    margin-bottom: 20px;
                    opacity: 0.5;
                }
                
                .no-events h3 {
                    margin: 0 0 10px 0;
                    font-weight: 600;
                }
                
                .no-events p {
                    margin: 0;
                    opacity: 0.8;
                }
                
                @media (max-width: 768px) {
                    .controls-grid {
                        grid-template-columns: 1fr;
                        gap: 15px;
                    }
                    
                    .timeline-modern {
                        padding: 20px;
                        margin: 10px 0;
                    }
                    
                    .timeline-title {
                        font-size: 2em;
                    }
                }
                
                .work-period-employee {
                    position: absolute;
                    top: 5px;
                    height: 50px;
                    background: linear-gradient(90deg, rgba(40, 167, 69, 0.2), rgba(32, 201, 151, 0.2));
                    border-radius: 8px;
                    border: 2px solid rgba(40, 167, 69, 0.4);
                }
                
                .break-period-employee {
                    position: absolute;
                    top: 5px;
                    height: 50px;
                    background: linear-gradient(90deg, rgba(255, 193, 7, 0.2), rgba(253, 126, 20, 0.2));
                    border-radius: 8px;
                    border: 2px solid rgba(255, 193, 7, 0.4);
                }
                
                .employee-info-timeline {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px;
                    border-radius: 10px;
                }
                
                .results-info {
                    background: #e9ecef;
                    padding: 8px 16px;
                    border-radius: 6px;
                    font-size: 0.875em;
                    color: #6c757d;
                    margin-bottom: 16px;
                    text-align: center;
                }
            </style>

            <!-- Navigation par onglets personnalisée -->
            <div class="custom-tabs">
                <button class="custom-tab <?php echo ($view_mode === 'timeline' || !isset($_GET['view_mode'])) ? 'active' : ''; ?>" 
                        onclick="switchTab('timeline')" id="timeline-tab">
                    <i class="fas fa-stream"></i>
                    Timeline des logs
                    <span class="badge"><?php echo number_format($total_logs); ?></span>
                </button>
                <button class="custom-tab <?php echo ($view_mode === 'employees') ? 'active' : ''; ?>" 
                        onclick="switchTab('employees')" id="employees-tab">
                    <i class="fas fa-users"></i>
                    Réparations par employé
                    <span class="badge"><?php echo count($employees); ?></span>
                </button>
            </div>
            
            <!-- Script pour les onglets - défini immédiatement après les boutons -->
            <script>
                // Fonction switchTab définie immédiatement
                window.switchTab = function(tabName) {
                    console.log('Switching to tab:', tabName);
                    
                    // Masquer tous les contenus d'onglets
                    document.querySelectorAll('.tab-content-custom').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Désactiver tous les onglets
                    document.querySelectorAll('.custom-tab').forEach(tab => {
                        tab.classList.remove('active');
                    });
                    
                    // Activer l'onglet cliqué
                    const tabElement = document.getElementById(tabName + '-tab');
                    if (tabElement) {
                        tabElement.classList.add('active');
                        console.log('Tab activated:', tabName + '-tab');
                    } else {
                        console.error('Tab element not found:', tabName + '-tab');
                    }
                    
                    // Afficher le contenu correspondant
                    const contentElement = document.getElementById(tabName + '-content');
                    if (contentElement) {
                        contentElement.classList.add('active');
                        console.log('Content activated:', tabName + '-content');
                    } else {
                        console.error('Content element not found:', tabName + '-content');
                    }
                    
                    // Mettre à jour l'URL pour conserver l'état
                    const url = new URL(window.location);
                    url.searchParams.set('page', 'reparation_logs');
                    url.searchParams.set('view_mode', tabName);
                    window.history.replaceState({}, '', url);
                };

                
                // Initialiser immédiatement si le DOM est prêt, sinon attendre
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initializeTabs);
                } else {
                    initializeTabs();
                }
                
                function initializeTabs() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const viewMode = urlParams.get('view_mode') || 'timeline';
                    
                    // S'assurer que le bon onglet est actif
                    if (viewMode === 'employees') {
                        switchTab('employees');
                    } else {
                        switchTab('timeline');
                    }
                }
            </script>
            
            <!-- Info résultats pour timeline -->
            <?php if (($view_mode === 'timeline' || !isset($_GET['view_mode'])) && $total_logs > 0): ?>
            <div class="results-info">
                Affichage <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $total_logs); ?> 
                sur <?php echo number_format($total_logs); ?> résultats
            </div>
            <?php endif; ?>
            
            <!-- Contenu des onglets -->
            <div class="tab-content-custom <?php echo ($view_mode === 'timeline' || !isset($_GET['view_mode'])) ? 'active' : ''; ?>" id="timeline-content">
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($logs)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun log trouvé pour les critères sélectionnés.
                                </div>
                            <?php else: ?>
                                <!-- Affichage groupé ou normal -->
                                <?php if ($group_by === 'none'): ?>
                                    <!-- Affichage timeline normale -->
                                    <div class="timeline">
                                        <?php foreach ($grouped_logs as $log): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-icon bg-<?php echo get_action_color($log['action_type'], $log['log_source'], $log['details']); ?>">
                                                    <i class="fas fa-<?php echo get_action_icon($log['action_type'], $log['log_source'], $log['details']); ?>"></i>
                                                </div>
                                                <div class="timeline-content log-card">
                                                    <span class="timeline-date">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?php echo format_datetime($log['date_action']); ?>
                                                    </span>
                                                    <h4 class="timeline-title">
                                                        <span class="log-badge bg-<?php echo get_action_color($log['action_type'], $log['log_source'], $log['details']); ?>">
                                                            <?php echo get_action_label($log['action_type'], $log['log_source'], $log['details']); ?>
                                                        </span>
                                                        
                                                        <?php if ($log['log_source'] === 'task'): ?>
                                                            <span class="text-decoration-none ms-2">
                                                                <i class="fas fa-tasks me-1"></i>
                                                                <?php echo htmlspecialchars($log['task_title']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <a href="index.php?page=details_reparation&id=<?php echo $log['entity_id']; ?>" class="text-decoration-none ms-2">
                                                                <i class="fas fa-tools me-1"></i>
                                                                Réparation #<?php echo $log['entity_id']; ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </h4>
                                                    <div class="timeline-details">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p class="mb-1">
                                                                    <strong><i class="fas fa-user me-1"></i> Employé:</strong>
                                                                    <span class="text-<?php echo get_employe_color($log['employe_nom']); ?>">
                                                                        <?php echo htmlspecialchars($log['employe_nom']); ?>
                                                                    </span>
                                                                </p>
                                                                
                                                                <?php if ($log['log_source'] === 'task'): ?>
                                                                    <p class="mb-1">
                                                                        <strong><i class="fas fa-tasks me-1"></i> Type:</strong>
                                                                        <span class="badge bg-warning">Tâche</span>
                                                                    </p>
                                                                    <?php if ($log['task_title']): ?>
                                                                        <p class="mb-1">
                                                                            <strong><i class="fas fa-tag me-1"></i> Titre:</strong>
                                                                            <?php echo htmlspecialchars($log['task_title']); ?>
                                                                        </p>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <p class="mb-1">
                                                                        <strong><i class="fas fa-user-tie me-1"></i> Client:</strong>
                                                                        <?php echo htmlspecialchars($log['client_nom']); ?>
                                                                    </p>
                                                                    <p class="mb-1">
                                                                        <strong><i class="fas fa-mobile-alt me-1"></i> Appareil:</strong>
                                                                        <?php echo htmlspecialchars($log['type_appareil'] . ' ' . $log['modele']); ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <?php if ($log['statut_avant'] && $log['statut_apres']): ?>
                                                                    <p class="mb-1">
                                                                        <strong><i class="fas fa-exchange-alt me-1"></i> Changement de statut:</strong>
                                                                        <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($log['statut_avant']); ?></span>
                                                                        <i class="fas fa-arrow-right mx-1"></i>
                                                                        <span class="badge bg-success"><?php echo htmlspecialchars($log['statut_apres']); ?></span>
                                                                    </p>
                                                                <?php endif; ?>
                                                                <?php if ($log['details']): ?>
                                                                    <p>
                                                                        <strong><i class="fas fa-info-circle me-1"></i> Détails:</strong>
                                                                        <?php echo htmlspecialchars($log['details']); ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Informations de pagination -->
                                    <?php if ($total_logs > 0): ?>
                                        <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                            <div class="text-muted small">
                                                <?php 
                                                $start_item = ($page - 1) * $limit + 1;
                                                $end_item = min($page * $limit, $total_logs);
                                                ?>
                                                <i class="fas fa-info-circle me-1"></i>
                                                Affichage de <strong><?php echo $start_item; ?></strong> à <strong><?php echo $end_item; ?></strong> 
                                                sur <strong><?php echo $total_logs; ?></strong> élément<?php echo $total_logs > 1 ? 's' : ''; ?>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="fas fa-list me-1"></i>
                                                <strong><?php echo $limit; ?></strong> par page
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Pagination -->
                                    <?php if ($total_logs > $limit): ?>
                                        <?php
                                        $total_pages = ceil($total_logs / $limit);
                                        $current_params = $_GET;
                                        unset($current_params['p']);
                                        $base_url = 'index.php?' . http_build_query($current_params);
                                        ?>
                                        <nav aria-label="Navigation des logs" class="mt-2">
                                            <ul class="pagination justify-content-center">
                                                <!-- Première page -->
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=1">
                                                            <i class="fas fa-angle-double-left"></i>
                                                        </a>
                                                    </li>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=<?php echo ($page - 1); ?>">
                                                            <i class="fas fa-angle-left"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <!-- Pages autour de la page actuelle -->
                                                <?php
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);
                                                
                                                for ($i = $start_page; $i <= $end_page; $i++):
                                                ?>
                                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=<?php echo $i; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <!-- Dernière page -->
                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=<?php echo ($page + 1); ?>">
                                                            <i class="fas fa-angle-right"></i>
                                                        </a>
                                                    </li>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=<?php echo $total_pages; ?>">
                                                            <i class="fas fa-angle-double-right"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <!-- Affichage groupé -->
                                    <?php foreach ($grouped_logs as $group_key => $group_logs): ?>
                                        <div class="card mb-4 group-card">
                                            <div class="card-header">
                                                <h5 class="mb-0">
                                                    <?php
                                                    switch ($group_by) {
                                                        case 'date':
                                                            echo '<i class="fas fa-calendar me-2"></i>' . date('d/m/Y', strtotime($group_key));
                                                            break;
                                                        case 'repair':
                                                            echo '<i class="fas fa-tools me-2"></i>Réparation #' . $group_key;
                                                            if (isset($group_logs[0])) {
                                                                echo ' - ' . htmlspecialchars($group_logs[0]['type_appareil'] . ' ' . $group_logs[0]['modele']);
                                                            }
                                                            break;
                                                        case 'employee':
                                                            if (isset($group_logs[0])) {
                                                                echo '<i class="fas fa-user me-2"></i>' . htmlspecialchars($group_logs[0]['employe_nom']);
                                                            }
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-primary ms-2"><?php echo count($group_logs); ?> logs</span>
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="timeline">
                                                    <?php foreach ($group_logs as $log): ?>
                                                        <div class="timeline-item timeline-item-sm">
                                                            <div class="timeline-icon bg-<?php echo get_action_color($log['action_type'], $log['log_source'], $log['details']); ?>">
                                                                <i class="fas fa-<?php echo get_action_icon($log['action_type'], $log['log_source'], $log['details']); ?>"></i>
                                                            </div>
                                                            <div class="timeline-content log-card">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <span class="timeline-date">
                                                                        <i class="far fa-clock me-1"></i>
                                                                        <?php echo format_datetime($log['date_action']); ?>
                                                                    </span>
                                                                    <span class="log-badge bg-<?php echo get_action_color($log['action_type'], $log['log_source'], $log['details']); ?>">
                                                                        <?php echo get_action_label($log['action_type'], $log['log_source'], $log['details']); ?>
                                                                    </span>
                                                                </div>
                                                                
                                                                <div class="row">
                                                                    <?php if ($group_by !== 'employee'): ?>
                                                                    <div class="col-md-4">
                                                                        <small class="text-muted">Employé:</small><br>
                                                                        <span class="text-<?php echo get_employe_color($log['employe_nom']); ?>">
                                                                            <?php echo htmlspecialchars($log['employe_nom']); ?>
                                                                        </span>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if ($group_by !== 'repair'): ?>
                                                                    <div class="col-md-4">
                                                                        <?php if ($log['log_source'] === 'task'): ?>
                                                                            <small class="text-muted">Tâche:</small><br>
                                                                            <span class="text-decoration-none">
                                                                                <i class="fas fa-tasks me-1"></i>
                                                                                <?php echo htmlspecialchars($log['task_title'] ?: 'Tâche #' . $log['entity_id']); ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <small class="text-muted">Réparation:</small><br>
                                                                            <a href="index.php?page=details_reparation&id=<?php echo $log['entity_id']; ?>" class="text-decoration-none">
                                                                                <i class="fas fa-tools me-1"></i>
                                                                                #<?php echo $log['entity_id']; ?>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <div class="col-md-4">
                                                                        <?php if ($log['statut_avant'] && $log['statut_apres']): ?>
                                                                            <small class="text-muted">Statut:</small><br>
                                                                            <span class="badge bg-secondary badge-sm me-1"><?php echo htmlspecialchars($log['statut_avant']); ?></span>
                                                                            <i class="fas fa-arrow-right mx-1 small"></i>
                                                                            <span class="badge bg-success badge-sm"><?php echo htmlspecialchars($log['statut_apres']); ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                
                                                                <?php if ($log['details']): ?>
                                                                    <div class="mt-2">
                                                                        <small class="text-muted">Détails:</small><br>
                                                                        <small><?php echo htmlspecialchars($log['details']); ?></small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
                
            <!-- Onglet Réparations par employé -->
            <div class="tab-content-custom <?php echo ($view_mode === 'employees') ? 'active' : ''; ?>" id="employees-content">
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($employees)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun employé trouvé avec des logs pour les critères sélectionnés.
                                </div>
                            <?php else: ?>
                                <!-- Simple vue par employé - résumé -->
                                <div class="row">
                                    <?php foreach ($employees as $emp_id => $employee): ?>
                                        <div class="col-lg-6 mb-3">
                                            <div class="card employee-summary-card employee-clickable" 
                                                 data-employee-id="<?php echo $emp_id; ?>" 
                                                 data-employee-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                                 onclick="openEmployeeTimeline(<?php echo $emp_id; ?>, '<?php echo htmlspecialchars($employee['name']); ?>')">
                                                <div class="card-header <?php echo get_employe_background_color($employee['name']); ?>">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-user me-2"></i>
                                                        <?php echo htmlspecialchars($employee['name']); ?>
                                                        <i class="fas fa-external-link-alt ms-2 opacity-50"></i>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <small class="text-muted">Réparations</small>
                                                            <div class="h5 mb-0"><?php echo count($employee['repairs']); ?></div>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted">Tâches</small>
                                                            <div class="h5 mb-0"><?php echo count($employee['tasks']); ?></div>
                                                        </div>
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
                
                <!-- Onglet Réparations par employé -->
                <div class="tab-pane fade" id="employees-tab-pane" role="tabpanel" aria-labelledby="employees-tab" tabindex="0">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-clock me-2"></i>
                                Activités par employé
                                <?php if ($log_type === 'tasks'): ?>
                                    (Tâches uniquement)
                                <?php elseif ($log_type === 'repairs'): ?>
                                    (Réparations uniquement)
                                <?php else: ?>
                                    (Réparations et Tâches)
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($employees)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucune donnée disponible pour les critères sélectionnés.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($employees as $emp_id => $employee): ?>
                                        <div class="col-lg-12 mb-4">
                                            <div class="card employee-card shadow-sm employee-card-clickable" 
                                                 data-employee-id="<?php echo $emp_id; ?>" 
                                                 data-employee-name="<?php echo htmlspecialchars($employee['name'], ENT_QUOTES); ?>"
                                                 onclick="openEmployeeTimeline(<?php echo $emp_id; ?>, this.getAttribute('data-employee-name'))">
                                                <div class="card-header <?php echo get_employe_background_color($employee['name']); ?> position-relative">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h5 class="mb-0 fw-bold">
                                                            <i class="fas fa-user me-2"></i>
                                                            <?php echo htmlspecialchars($employee['name']); ?>
                                                            <span class="timeline-indicator">
                                                                <i class="fas fa-chart-line ms-2"></i>
                                                                <small class="ms-1">Voir timeline</small>
                                                            </span>
                                                        </h5>
                                                        <?php
                                                        $total_interventions = 0;
                                                        $completed_interventions = 0;
                                                        
                                                        foreach ($employee['repairs'] as $repair) {
                                                            $sequences = get_all_repair_sequences($repair['logs']);
                                                            foreach ($sequences as $sequence) {
                                                                if ($sequence['start']) {
                                                                    $total_interventions++;
                                                                    if ($sequence['end']) {
                                                                        $completed_interventions++;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <div>
                                                            <span class="badge bg-<?php echo get_employe_color($employee['name']); ?> rounded-pill">
                                                                <?php echo $total_interventions; ?> intervention(s)
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th width="5%">#</th>
                                                                    <th width="10%">Type</th>
                                                                    <th width="20%">Réparation/Tâche</th>
                                                                    <th width="20%">Description</th>
                                                                    <th width="15%">Démarrage</th>
                                                                    <th width="15%">Fin</th>
                                                                    <th width="15%">Durée</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                // Trier les réparations par date de démarrage (plus récente en premier)
                                                                uasort($employee['repairs'], function($a, $b) {
                                                                    $a_data = get_repair_start_end($a['logs']);
                                                                    $b_data = get_repair_start_end($b['logs']);
                                                                    
                                                                    $a_start = $a_data['start'] ? $a_data['start']['date_action'] : null;
                                                                    $b_start = $b_data['start'] ? $b_data['start']['date_action'] : null;
                                                                    
                                                                    if (!$a_start && !$b_start) return 0;
                                                                    if (!$a_start) return 1;
                                                                    if (!$b_start) return -1;
                                                                    
                                                                    return strtotime($b_start) - strtotime($a_start);
                                                                });
                                                                
                                                                $repair_count = 0;
                                                                $previous_repair_end = null;
                                                                $sorted_repairs = [];
                                                                
                                                                // Combiner réparations et tâches selon le type de log
                                                                $temp_interventions = [];
                                                                
                                                                // Ajouter les réparations si on les affiche
                                                                if ($log_type !== 'tasks') {
                                                                    foreach ($employee['repairs'] as $repair) {
                                                                        $sequences = get_all_repair_sequences($repair['logs']);
                                                                        
                                                                        // Pour chaque séquence (démarrage-fin), créer une intervention
                                                                        foreach ($sequences as $sequence) {
                                                                            $temp_interventions[] = [
                                                                                'type' => 'repair',
                                                                                'repair' => $repair,
                                                                                'start' => $sequence['start'],
                                                                                'end' => $sequence['end']
                                                                            ];
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                // Ajouter les tâches si on les affiche
                                                                if ($log_type !== 'repairs') {
                                                                    foreach ($employee['tasks'] as $task) {
                                                                        $task_sequences = get_all_task_sequences($task['logs']);
                                                                        
                                                                        // Pour chaque séquence (démarrage-fin), créer une intervention
                                                                        foreach ($task_sequences as $sequence) {
                                                                            $temp_interventions[] = [
                                                                                'type' => 'task',
                                                                                'task' => $task,
                                                                                'start' => $sequence['start'],
                                                                                'end' => $sequence['end']
                                                                            ];
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                // Trier toutes les interventions par date de démarrage (du plus ancien au plus récent)
                                                                usort($temp_interventions, function($a, $b) {
                                                                    $a_time = $a['start'] ? strtotime($a['start']['date_action']) : 0;
                                                                    $b_time = $b['start'] ? strtotime($b['start']['date_action']) : 0;
                                                                    
                                                                    if ($a_time == 0 && $b_time == 0) return 0;
                                                                    if ($a_time == 0) return 1;
                                                                    if ($b_time == 0) return -1;
                                                                    
                                                                    return $a_time - $b_time;
                                                                });
                                                                
                                                                // Ajouter un indicateur pour alterner les couleurs
                                                                $row_class = '';
                                                                
                                                                foreach ($temp_interventions as $index => $intervention): 
                                                                    $is_repair = ($intervention['type'] === 'repair');
                                                                    $item = $is_repair ? $intervention['repair'] : $intervention['task'];
                                                                    $start = $intervention['start'];
                                                                    $end = $intervention['end'];
                                                                    $repair_count++;
                                                                    
                                                                    // Alterner les couleurs de fond des lignes
                                                                    $row_class = ($row_class === 'table-light') ? '' : 'table-light';
                                                                    
                                                                    // Si nous avons une réparation précédente terminée
                                                                    if ($previous_repair_end && $start) {
                                                                        $inactive_time = calculate_inactive_time(
                                                                            $previous_repair_end['date_action'],
                                                                            $start['date_action']
                                                                        );
                                                                        
                                                                        // Afficher seulement si le temps inactif est significatif
                                                                        if ($inactive_time && $inactive_time !== '0min') {
                                                                            // Calculer le temps en minutes pour déterminer l'importance visuelle
                                                                            $prev_end_time = strtotime($previous_repair_end['date_action']);
                                                                            $curr_start_time = strtotime($start['date_action']);
                                                                            $minutes_diff = ($curr_start_time - $prev_end_time) / 60;
                                                                            
                                                                            // Déterminer la classe CSS selon la durée
                                                                            $pause_class = '';
                                                                            $pause_icon = 'fa-pause-circle';
                                                                            $badge_color = 'primary';
                                                                            
                                                                            if ($minutes_diff > 60) { // Plus d'une heure
                                                                                $pause_class = 'bg-danger bg-opacity-25';
                                                                                $pause_icon = 'fa-bed';
                                                                                $badge_color = 'danger';
                                                                            } elseif ($minutes_diff > 30) { // Plus de 30 minutes
                                                                                $pause_class = 'bg-warning bg-opacity-25';
                                                                                $pause_icon = 'fa-coffee';
                                                                                $badge_color = 'warning';
                                                                            } elseif ($minutes_diff > 15) { // Plus de 15 minutes
                                                                                $pause_class = 'bg-info bg-opacity-25';
                                                                                $pause_icon = 'fa-mug-hot';
                                                                                $badge_color = 'info';
                                                                            }
                                                                ?>
                                                                <tr class="inactive-time-row <?php echo $pause_class; ?>">
                                                                    <td colspan="6" class="text-center py-3">
                                                                        <span class="inactive-time-badge text-<?php echo $badge_color; ?> border-<?php echo $badge_color; ?>">
                                                                            <i class="fas <?php echo $pause_icon; ?> me-1"></i>
                                                                            Temps de pause: <?php echo $inactive_time; ?>
                                                                        </span>
                                                                        <div class="mt-1 small text-secondary">
                                                                            <?php 
                                                                            $prev_end = new DateTime($previous_repair_end['date_action']);
                                                                            $next_start = new DateTime($start['date_action']);
                                                                            echo "De " . $prev_end->format('d/m/Y H:i') . " à " . $next_start->format('d/m/Y H:i');
                                                                            ?>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                                        }
                                                                    }
                                                                ?>
                                                                <tr class="<?php echo $row_class; ?>">
                                                                    <td>
                                                                        <?php if ($is_repair): ?>
                                                                            <a href="index.php?page=details_reparation&id=<?php echo $item['id']; ?>" 
                                                                               class="btn btn-sm btn-outline-<?php echo get_employe_color($employee['name']); ?>"
                                                                               onclick="event.stopPropagation();">
                                                                                #<?php echo $item['id']; ?>
                                                                            </a>
                                                                        <?php else: ?>
                                                                            <a href="index.php?page=taches&task_id=<?php echo $item['id']; ?>&open_modal=1" 
                                                                               class="btn btn-sm btn-outline-<?php echo get_employe_color($employee['name']); ?>"
                                                                               onclick="event.stopPropagation();">
                                                                                T#<?php echo $item['id']; ?>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($is_repair): ?>
                                                                            <span class="badge bg-primary">
                                                                                <i class="fas fa-tools me-1"></i>
                                                                                Réparation
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-success">
                                                                                <i class="fas fa-tasks me-1"></i>
                                                                                Tâche
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($is_repair): ?>
                                                                            <div>
                                                                                <a href="#" class="text-decoration-none client-info-link" data-bs-toggle="modal" data-bs-target="#clientModal" data-client-id="<?php echo $item['client_id']; ?>">
                                                                                    <i class="fas fa-user-tie me-1 text-muted"></i>
                                                                                    <?php echo htmlspecialchars($item['client_nom']); ?>
                                                                                </a>
                                                                            </div>
                                                                            <div class="text-muted small">
                                                                                <i class="fas fa-<?php echo $item['type_appareil'] === 'Smartphone' ? 'mobile-alt' : 'laptop'; ?> me-1"></i>
                                                                                <?php echo htmlspecialchars($item['type_appareil'] . ' ' . $item['modele']); ?>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="fw-bold">
                                                                                <?php echo htmlspecialchars($item['title']); ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="text-wrap small">
                                                                            <?php echo !empty($item['description']) ? htmlspecialchars($item['description']) : '<span class="text-muted">Aucune description</span>'; ?>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (is_array($start)): ?>
                                                                            <div>
                                                                                <i class="fas fa-play-circle text-success me-1"></i>
                                                                                <strong><?php echo (new DateTime($start['date_action']))->format('d/m/Y H:i'); ?></strong>
                                                                            </div>
                                                                            <?php 
                                                                            // Afficher le statut initial s'il existe
                                                                            if (is_array($start) && array_key_exists('statut_avant', $start) && !empty($start['statut_avant'])): 
                                                                            ?>
                                                                            <div class="mt-1">
                                                                                <span class="badge bg-secondary">
                                                                                    <?php echo htmlspecialchars($start['statut_avant']); ?>
                                                                                </span>
                                                                            </div>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">Non démarré</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (is_array($end)): ?>
                                                                            <div>
                                                                                <?php
                                                                                // Afficher une icône et une couleur différente selon le type d'action de fin
                                                                                $end_icon = 'stop-circle';
                                                                                $end_color = 'danger';
                                                                                
                                                                                if (array_key_exists('action_type', $end)) {
                                                                                    switch ($end['action_type']) {
                                                                                        case 'terminer':
                                                                                            $end_icon = 'stop-circle';
                                                                                            $end_color = 'danger';
                                                                                            break;
                                                                                        case 'changement_statut':
                                                                                            $end_icon = 'exchange-alt';
                                                                                            $end_color = 'warning';
                                                                                            break;
                                                                                        case 'ajout_note':
                                                                                            $end_icon = 'sticky-note';
                                                                                            $end_color = 'info';
                                                                                            break;
                                                                                        case 'modification':
                                                                                            $end_icon = 'edit';
                                                                                            $end_color = 'secondary';
                                                                                            break;
                                                                                        case 'autre':
                                                                                            $end_icon = 'cog';
                                                                                            $end_color = 'dark';
                                                                                            break;
                                                                                    }
                                                                                }
                                                                                ?>
                                                                                <div class="d-flex align-items-center">
                                                                                    <i class="fas fa-<?php echo $end_icon; ?> text-<?php echo $end_color; ?> me-1"></i>
                                                                                    <strong><?php echo (new DateTime($end['date_action']))->format('d/m/Y H:i'); ?></strong>
                                                                                </div>
                                                                                <?php
                                                                                // Afficher le statut final s'il existe
                                                                                if (array_key_exists('statut_apres', $end) && !empty($end['statut_apres'])): 
                                                                                ?>
                                                                                <div class="mt-1">
                                                                                    <span class="badge bg-success">
                                                                                        <?php echo htmlspecialchars($end['statut_apres']); ?>
                                                                                    </span>
                                                                                </div>
                                                                                <?php 
                                                                                // Sinon, afficher le statut avant s'il existe (pour certains types d'actions)
                                                                                elseif (array_key_exists('statut_avant', $end) && !empty($end['statut_avant'])): 
                                                                                ?>
                                                                                <div class="mt-1">
                                                                                    <span class="badge bg-<?php echo array_key_exists('action_type', $end) ? get_action_color($end['action_type']) : 'secondary'; ?>">
                                                                                        <?php echo htmlspecialchars($end['statut_avant']); ?>
                                                                                    </span>
                                                                                </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <?php if (is_array($start)): ?>
                                                                                <span class="badge bg-primary">En cours</span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-secondary">Non terminé</span>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (is_array($start) && is_array($end)): ?>
                                                                            <span class="badge bg-light text-dark">
                                                                                <?php echo calculate_duration($start['date_action'], $end['date_action']); ?>
                                                                            </span>
                                                                        <?php elseif (is_array($start)): ?>
                                                                            <span class="badge bg-primary">En cours</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                                    // Mettre à jour la fin de réparation précédente si cette réparation est terminée
                                                                    if ($end) {
                                                                        $previous_repair_end = $end;
                                                                    }
                                                                endforeach; 
                                                                
                                                                // Si aucune réparation trouvée
                                                                if ($repair_count === 0):
                                                                ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-center py-4">
                                                                        <div class="alert alert-info mb-0">
                                                                            <i class="fas fa-info-circle me-2"></i>
                                                                            Aucune réparation trouvée pour cet employé
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="card-footer employee-stats">
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                                                        <div>
                                                            <span class="stats-badge">
                                                                <i class="fas fa-check-circle text-success"></i>
                                                                <?php echo $completed_interventions; ?> terminée(s)
                                                            </span>
                                                            <span class="stats-badge">
                                                                <i class="fas fa-hourglass-half text-warning"></i>
                                                                <?php echo $total_interventions - $completed_interventions; ?> en cours
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="stats-badge">
                                                                <i class="fas fa-clock text-info"></i>
                                                                Temps total: <?php echo calculate_total_work_time_from_interventions($employee['repairs']); ?>
                                                            </span>
                                                        </div>
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
                
<!-- Modal Client -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="clientModalLabel"><i class="fas fa-user-tie me-2"></i>Informations Client</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="spinner-border text-primary" role="status" id="clientModalLoader">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
                <div id="clientModalContent" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-header bg-primary bg-opacity-10">
                                    <h5 class="mb-0 text-primary"><i class="fas fa-info-circle me-2"></i>Détails du client</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong><i class="fas fa-user me-2"></i>Nom:</strong> <span id="clientNom"></span></p>
                                    <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <span id="clientEmail"></span></p>
                                    <p><strong><i class="fas fa-phone me-2"></i>Téléphone:</strong> <span id="clientTelephone"></span></p>
                                    <p><strong><i class="fas fa-map-marker-alt me-2"></i>Adresse:</strong> <span id="clientAdresse"></span></p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center gap-3 mb-3">
                                <a href="#" class="btn btn-success" id="btnCallClient">
                                    <i class="fas fa-phone-alt me-2"></i>Appeler
                                </a>
                                <a href="#" class="btn btn-info text-white" id="btnSmsClient"
                                   onclick="openSmsModal(
                                       currentClientId, 
                                       document.getElementById('clientNom').textContent.split(' ')[0] || '', 
                                       document.getElementById('clientNom').textContent.split(' ')[1] || '', 
                                       document.getElementById('clientTelephone').textContent
                                   ); return false;">
                                    <i class="fas fa-sms me-2"></i>Envoyer SMS
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary bg-opacity-10">
                                    <h5 class="mb-0 text-primary"><i class="fas fa-history me-2"></i>Historique du client</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush" id="clientHistorique" style="max-height: 300px; overflow-y: auto;">
                                        <!-- L'historique sera chargé ici -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-primary" id="btnVoirFiche">
                    <i class="fas fa-external-link-alt me-2"></i>Voir fiche complète
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Fonctions définies plus haut dans le script inline après les boutons

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page reparation_logs chargée');
    
    // L'initialisation des onglets est gérée par le script inline plus haut
    
    // Fonction pour afficher un indicateur de chargement
    function showLoadingIndicator() {
        // Créer ou afficher un indicateur de chargement
        let loadingIndicator = document.getElementById('loadingIndicator');
        if (!loadingIndicator) {
            loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'loadingIndicator';
            loadingIndicator.innerHTML = `
                <div class="d-flex justify-content-center align-items-center" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
                    <div class="bg-white p-4 rounded shadow">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border text-primary me-3" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <span>Application des filtres...</span>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingIndicator);
        } else {
            loadingIndicator.style.display = 'block';
        }
    }
    
    // Vérifier que le formulaire existe
    const filterForm = document.getElementById('filterForm');
    console.log('Formulaire filterForm trouvé:', !!filterForm);
    

    
    // Animation pour les éléments de la timeline
    const timelineItems = document.querySelectorAll('.timeline-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    timelineItems.forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(item);
    });
    
    // Activer les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Animation lors du changement d'onglet
    const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabEls.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', event => {
            const target = document.querySelector(event.target.getAttribute('data-bs-target'));
            target.querySelectorAll('.card').forEach(card => {
                card.classList.add('fade-in-up');
                setTimeout(() => {
                    card.classList.remove('fade-in-up');
                }, 500);
            });
        });
    });
    
    // Gestion du modal client
    const clientLinks = document.querySelectorAll('.client-info-link');
    const clientModal = document.getElementById('clientModal');
    const clientModalContent = document.getElementById('clientModalContent');
    const clientModalLoader = document.getElementById('clientModalLoader');
    
    // Variable pour stocker l'ID du client actuel
    let currentClientId = '';
    
    // Fonction pour charger les données du client
    function loadClientData(clientId) {
        clientModalContent.style.display = 'none';
        clientModalLoader.style.display = 'block';
        
        console.log('Chargement des données pour le client ID:', clientId);
        
        // Appel AJAX pour récupérer les informations du client
        fetch('ajax/get_client_info.php?client_id=' + clientId, {
            method: 'GET',
            credentials: 'same-origin', // Inclure les cookies de session
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                console.log('Réponse reçue:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('Données reçues:', data);
                
                if (data.success) {
                    // Remplir les informations du client
                    document.getElementById('clientNom').textContent = data.client.nom + ' ' + data.client.prenom;
                    document.getElementById('clientEmail').textContent = data.client.email;
                    document.getElementById('clientTelephone').textContent = data.client.telephone;
                    document.getElementById('clientAdresse').textContent = data.client.adresse;
                    
                    // Configurer les boutons d'action
                    document.getElementById('btnCallClient').href = 'tel:' + data.client.telephone;
                    document.getElementById('btnSmsClient').href = 'sms:' + data.client.telephone;
                    document.getElementById('btnVoirFiche').href = 'index.php?page=details_client&id=' + clientId;
                    
                    // Remplir l'historique du client
                    const historiqueContainer = document.getElementById('clientHistorique');
                    historiqueContainer.innerHTML = '';
                    
                    if (data.historique && data.historique.length > 0) {
                        data.historique.forEach(item => {
                            const historiqueItem = document.createElement('a');
                            historiqueItem.className = 'list-group-item list-group-item-action';
                            historiqueItem.href = 'index.php?page=details_reparation&id=' + item.id;
                            
                            const badgeStatus = document.createElement('span');
                            badgeStatus.className = 'badge bg-' + item.statusColor + ' float-end';
                            badgeStatus.textContent = item.statut;
                            
                            const itemContent = document.createElement('div');
                            itemContent.className = 'd-flex w-100 justify-content-between';
                            
                            const heading = document.createElement('h6');
                            heading.className = 'mb-1';
                            heading.textContent = item.type_appareil + ' ' + item.modele;
                            
                            const date = document.createElement('small');
                            date.className = 'text-muted';
                            date.textContent = item.date_creation;
                            
                            itemContent.appendChild(heading);
                            itemContent.appendChild(date);
                            
                            const details = document.createElement('p');
                            details.className = 'mb-1 small';
                            details.textContent = item.probleme.substring(0, 100) + (item.probleme.length > 100 ? '...' : '');
                            
                            historiqueItem.appendChild(itemContent);
                            historiqueItem.appendChild(details);
                            historiqueItem.appendChild(badgeStatus);
                            
                            historiqueContainer.appendChild(historiqueItem);
                        });
                    } else {
                        historiqueContainer.innerHTML = '<div class="list-group-item text-center py-3">Aucun historique disponible</div>';
                    }
                    
                    // Afficher le contenu du modal
                    clientModalLoader.style.display = 'none';
                    clientModalContent.style.display = 'block';
                } else {
                    // Afficher un message d'erreur
                    clientModalContent.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données client</div>';
                    clientModalLoader.style.display = 'none';
                    clientModalContent.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                clientModalContent.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données client</div>';
                clientModalLoader.style.display = 'none';
                clientModalContent.style.display = 'block';
            });
    }
    
    // Événement lors de l'ouverture du modal
    clientModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const clientId = button.getAttribute('data-client-id');
        console.log('Ouverture du modal pour le client ID:', clientId);
        loadClientData(clientId);
    });
    
    // Événement lors du clic sur les liens client
    clientLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const clientId = this.getAttribute('data-client-id');
            console.log('Clic sur le client avec ID:', clientId);
            // Vérifier que l'ID a bien été récupéré
            if (!clientId) {
                console.error('Erreur: ID client manquant dans l\'attribut data-client-id');
                console.log('Element HTML:', this.outerHTML);
            }
            // Le modal sera ouvert par l'attribut data-bs-toggle
        });
    });

    // Fonction pour définir automatiquement les dates selon la période sélectionnée
    function setPeriode(periode) {
        const today = new Date();
        let dateDebut = '';
        let dateFin = '';
        
        // Formater les dates pour l'entrée 'date' HTML
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        switch (periode) {
            case 'aujourd\'hui':
                dateDebut = formatDate(today);
                dateFin = dateDebut;
                break;
            case 'hier':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                dateDebut = formatDate(yesterday);
                dateFin = dateDebut;
                break;
            case 'semaine':
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1)); // Lundi de la semaine
                dateDebut = formatDate(startOfWeek);
                dateFin = formatDate(today);
                break;
            case 'mois':
                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                dateDebut = formatDate(startOfMonth);
                dateFin = formatDate(today);
                break;
            case 'personnalise':
                // Ne rien faire, l'utilisateur entrera les dates
                break;
        }
        
        document.getElementById('date_debut').value = dateDebut;
        document.getElementById('date_fin').value = dateFin;
    }

    // Recherche dynamique
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Recherche dans les réparations de la timeline
            if (document.getElementById('timeline-tab-pane').classList.contains('active')) {
                const timelineItems = document.querySelectorAll('.timeline-item');
                timelineItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            } 
            // Recherche dans les réparations par employé
            else if (document.getElementById('employees-tab-pane').classList.contains('active')) {
                const repairRows = document.querySelectorAll('.employee-card tbody tr:not(.inactive-time-row)');
                repairRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
        
        // Réinitialiser la recherche lors du changement d'onglet
        const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', event => {
                searchInput.value = '';
                const timelineItems = document.querySelectorAll('.timeline-item');
                timelineItems.forEach(item => {
                    item.style.display = '';
                });
                const repairRows = document.querySelectorAll('.employee-card tbody tr');
                repairRows.forEach(row => {
                    row.style.display = '';
                });
            });
        });
    }

    // Gestion des filtres de type de log (Tout / Réparations / Tâches)
    const logTypeButtons = document.querySelectorAll('input[name="log_type"]');
    console.log('Filtres de type de log trouvés:', logTypeButtons.length);
    
    logTypeButtons.forEach(button => {
        // Utiliser 'click' au lieu de 'change' pour une meilleure compatibilité
        button.addEventListener('click', function() {
            console.log('Clic sur filtre de type de log:', this.value);
            // Ajouter un indicateur de chargement
            const form = document.getElementById('filterForm');
            if (form) {
                // Afficher un indicateur de chargement
                showLoadingIndicator();
                
                // Soumettre automatiquement le formulaire
                setTimeout(() => {
                    console.log('Soumission du formulaire pour type de log:', this.value);
                    form.submit();
                }, 200);
            } else {
                console.error('Formulaire filterForm non trouvé !');
            }
        });
    });
    
    // Gestion des filtres rapides
    const quickFilterButtons = document.querySelectorAll('input[name="quick_filter"]');
    console.log('Filtres rapides trouvés:', quickFilterButtons.length);
    
    quickFilterButtons.forEach(button => {
        // Utiliser 'click' au lieu de 'change' pour une meilleure compatibilité
        button.addEventListener('click', function() {
            console.log('Clic sur filtre rapide:', this.value);
            // Vider les champs de dates personnalisées
            const dateDebut = document.getElementById('date_debut');
            const dateFin = document.getElementById('date_fin');
            if (dateDebut) dateDebut.value = '';
            if (dateFin) dateFin.value = '';
            
            // Ajouter un indicateur de chargement
            const form = document.getElementById('filterForm');
            if (form) {
                // Afficher un indicateur de chargement
                showLoadingIndicator();
                
                // Soumettre automatiquement le formulaire
                setTimeout(() => {
                    console.log('Soumission du formulaire pour filtre rapide:', this.value);
                    form.submit();
                }, 100);
            } else {
                console.error('Formulaire filterForm non trouvé !');
            }
        });
    });

    // Fonction de réinitialisation des filtres
    document.getElementById('resetFilters').addEventListener('click', function() {
        window.location.href = 'index.php?page=reparation_logs';
    });

    // Auto-soumission pour les champs select (non radio)
    const autoSubmitFields = ['view_mode', 'group_by', 'sort_by', 'sort_order'];
    autoSubmitFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field && field.type !== 'radio') {
            field.addEventListener('change', function() {
                console.log('Auto-soumission pour le champ:', fieldName, 'valeur:', this.value);
                showLoadingIndicator();
                setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 100);
            });
        }
    });

    // Recherche en temps réel améliorée
    let searchTimeout;
    const searchInputTerm = document.getElementById('search_term');
    if (searchInputTerm) {
        searchInputTerm.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Recherche côté client pour l'affichage actuel
                const searchTerm = this.value.toLowerCase();
                
                if (document.getElementById('timeline-tab-pane').classList.contains('active')) {
                    // Recherche dans la timeline
                    const timelineItems = document.querySelectorAll('.timeline-item');
                    let visibleCount = 0;
                    
                    timelineItems.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        const isVisible = text.includes(searchTerm);
                        item.style.display = isVisible ? '' : 'none';
                        if (isVisible) visibleCount++;
                    });
                    
                    // Mettre à jour le compteur si présent
                    updateResultsCount(visibleCount);
                } else if (document.getElementById('employees-tab-pane').classList.contains('active')) {
                    // Recherche dans les réparations par employé
                    const repairRows = document.querySelectorAll('.employee-card tbody tr:not(.inactive-time-row)');
                    repairRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                    
                    // Masquer les cartes d'employés qui n'ont aucune réparation visible
                    const employeeCards = document.querySelectorAll('.employee-card');
                    employeeCards.forEach(card => {
                        const visibleRows = card.querySelectorAll('tbody tr:not(.inactive-time-row):not([style*="display: none"])');
                        card.style.display = visibleRows.length > 0 ? '' : 'none';
                    });
                }
            }, 300); // Délai de 300ms
        });
        
        // Soumission du formulaire sur Enter pour recherche côté serveur
        searchInputTerm.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('filterForm').submit();
            }
        });
    }

    // Fonction pour mettre à jour le compteur de résultats
    function updateResultsCount(count) {
        const badge = document.querySelector('#timeline-tab .badge');
        if (badge) {
            badge.textContent = count.toLocaleString();
        }
    }

    // Synchronisation des onglets avec le mode d'affichage
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-bs-target');
            const viewMode = targetTab === '#timeline-tab-pane' ? 'timeline' : 'employees';
            
            // Mettre à jour le champ caché
            const viewModeField = document.getElementById('view_mode');
            if (viewModeField && viewModeField.value !== viewMode) {
                viewModeField.value = viewMode;
                // Soumettre automatiquement pour actualiser les données
                document.getElementById('filterForm').submit();
            }
        });
    });

    // Animation d'apparition pour les nouveaux éléments
    function animateNewElements() {
        const newElements = document.querySelectorAll('.timeline-item, .group-card, .employee-card');
        newElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 50); // Décalage de 50ms entre chaque élément
        });
    }

    // Appliquer l'animation au chargement
    animateNewElements();

    // Gestion des filtres avancés
    const advancedFiltersToggle = document.querySelector('[data-bs-target="#advancedFilters"]');
    if (advancedFiltersToggle) {
        advancedFiltersToggle.addEventListener('click', function() {
            const icon = this.querySelector('i');
            setTimeout(() => {
                if (document.getElementById('advancedFilters').classList.contains('show')) {
                    icon.className = 'fas fa-cog me-1';
                } else {
                    icon.className = 'fas fa-cog me-1';
                }
            }, 350);
        });
    }

    // Fonction pour appliquer rapidement un filtre de date et soumettre le formulaire
    function applyQuickDateFilter(periode) {
        // Cette fonction est conservée pour la compatibilité mais n'est plus utilisée
        // Les filtres rapides sont maintenant gérés par les boutons radio
        console.warn('applyQuickDateFilter est dépréciée, utilisez les boutons radio');
    }

    // Créer le bouton de toggle
    const toggleButton = document.createElement('button');
    toggleButton.className = 'dark-mode-toggle';
    toggleButton.innerHTML = '<i class="fas fa-moon"></i>';
    document.body.appendChild(toggleButton);
    
    // Vérifier si le mode sombre est déjà activé
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
        toggleButton.innerHTML = '<i class="fas fa-sun"></i>';
    }
    
    // Ajouter l'événement au bouton
    toggleButton.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        
        // Mettre à jour l'icône
        if (document.body.classList.contains('dark-mode')) {
            toggleButton.innerHTML = '<i class="fas fa-sun"></i>';
            localStorage.setItem('darkMode', 'enabled');
        } else {
            toggleButton.innerHTML = '<i class="fas fa-moon"></i>';
            localStorage.setItem('darkMode', 'disabled');
        }
    });
});

// Test simple pour vérifier que JavaScript fonctionne
console.log('JavaScript chargé dans reparation_logs.php');

// Ajouter un gestionnaire d'événements alternatif au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, ajout des gestionnaires d\'événements');
    
    // Ajouter des gestionnaires d'événements pour tous les liens d'employés
    const employeeLinks = document.querySelectorAll('.employee-name-link');
    console.log('Liens d\'employés trouvés:', employeeLinks.length);
    
    employeeLinks.forEach((link, index) => {
        console.log(`Lien ${index}:`, link);
        
        // Ajouter un gestionnaire d'événements de clic
        link.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Clic détecté sur le lien employé');
            
            const employeeId = this.getAttribute('data-employee-id');
            const employeeName = this.getAttribute('data-employee-name');
            
            console.log('Données extraites:', { employeeId, employeeName });
            
            if (employeeId && employeeName) {
                openEmployeeTimeline(employeeId, employeeName);
            } else {
                alert('Erreur: Données employé manquantes');
            }
        });
    });
});

// Fonction pour ouvrir la timeline d'un employé
function openEmployeeTimeline(employeeId, employeeName) {
    console.log('openEmployeeTimeline appelée avec:', employeeId, employeeName);
    
    // Test simple d'abord
    if (!employeeId) {
        alert('Erreur: ID employé manquant');
        return;
    }
    
    // Vérifier que les éléments existent
    const modalBody = document.getElementById('employeeTimelineBody');
    const modalLabel = document.getElementById('employeeTimelineModalLabel');
    const modalElement = document.getElementById('employeeTimelineModal');
    
    if (!modalBody || !modalLabel || !modalElement) {
        console.error('Éléments du modal non trouvés:', {
            modalBody: !!modalBody,
            modalLabel: !!modalLabel,
            modalElement: !!modalElement
        });
        alert('Erreur: Éléments du modal non trouvés');
        return;
    }
    
    // Afficher un indicateur de chargement
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-3">Chargement de la timeline de ${employeeName}...</p>
        </div>
    `;
    
    // Mettre à jour le titre du modal
    modalLabel.textContent = `Timeline de ${employeeName}`;
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Charger les données via AJAX
    fetch(`ajax_handlers/get_employee_timeline.php?employee_id=${employeeId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayEmployeeTimeline(data);
            } else {
                throw new Error(data.message || 'Erreur lors du chargement des données');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erreur lors du chargement de la timeline: ${error.message}
                </div>
            `;
        });
}

// Fonction pour afficher la timeline dans le modal
function displayEmployeeTimeline(data) {
    const modalBody = document.getElementById('employeeTimelineBody');
    const { employee, timeline, stats } = data;
    
    let html = `
        <!-- Statistiques globales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h5>${stats.total_work_time}</h5>
                        <small>Temps de travail total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-pause fa-2x mb-2"></i>
                        <h5>${stats.total_inactive_time}</h5>
                        <small>Temps d'inactivité</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5>${stats.completed_tasks}</h5>
                        <small>Tâches terminées</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-percentage fa-2x mb-2"></i>
                        <h5>${stats.efficiency_rate}%</h5>
                        <small>Taux d'efficacité</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Timeline -->
        <div class="timeline-container">
            <h5 class="mb-3">
                <i class="fas fa-history me-2"></i>
                Timeline détaillée (${timeline.length} activités)
            </h5>
    `;
    
    if (timeline.length === 0) {
        html += `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucune activité trouvée pour cet employé.
            </div>
        `;
    } else {
        timeline.forEach((item, index) => {
            const isTask = item.type === 'task';
            const iconClass = isTask ? 'fa-tasks' : 'fa-tools';
            const badgeClass = isTask ? 'bg-success' : 'bg-primary';
            const typeLabel = isTask ? 'Tâche' : 'Réparation';
            
            // Afficher le temps d'inactivité avant cette activité (sauf pour la première)
            if (index > 0 && item.inactive_time) {
                const inactiveClass = item.inactive_duration_minutes > 60 ? 'text-danger' : 
                                    (item.inactive_duration_minutes > 30 ? 'text-warning' : 'text-info');
                
                html += `
                    <div class="timeline-item inactive-period mb-3">
                        <div class="timeline-marker bg-secondary">
                            <i class="fas fa-pause"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="card border-secondary">
                                <div class="card-body text-center py-2">
                                    <span class="${inactiveClass}">
                                        <i class="fas fa-clock me-1"></i>
                                        Pause de ${item.inactive_time}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            html += `
                <div class="timeline-item mb-3">
                    <div class="timeline-marker ${badgeClass}">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge ${badgeClass} me-2">${typeLabel} #${item.entity_id}</span>
                                    <strong>${item.title}</strong>
                                </div>
                                <div class="text-end">
                                    ${item.is_completed ? 
                                        `<span class="badge bg-success"><i class="fas fa-check me-1"></i>Terminé</span>` :
                                        `<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>En cours</span>`
                                    }
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">${item.description || 'Aucune description'}</p>
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">Début:</small><br>
                                        <strong>${item.start_time}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Fin:</small><br>
                                        <strong>${item.end_time || 'En cours...'}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Durée:</small><br>
                                        <strong class="text-primary">${item.work_duration || 'En cours...'}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    modalBody.innerHTML = html;
}
</script>

<!-- Modal Frise Chronologique Employé -->
<div class="modal fade" id="employeeTimelineModal" tabindex="-1" aria-labelledby="employeeTimelineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content employee-timeline-modal">
            <div class="modal-header" style="background: #f1f3f5; color: #222; border-bottom: 1px solid #e3e6ea;">
                <h5 class="modal-title" id="employeeTimelineModalLabel">
                    <i class="fas fa-clock me-2"></i>
                    Frise Chronologique - <span id="employeeModalName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body employee-timeline-body" id="employeeTimelineContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
            </div>
                    <p class="mt-2">Chargement de la frise chronologique...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sélection de Période Personnalisée -->
<div class="modal fade" id="dateRangeModal" tabindex="-1" aria-labelledby="dateRangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="dateRangeModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Sélectionner une période personnalisée
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instructions :</strong> Cliquez sur une première date pour le début, puis sur une seconde date pour la fin de la période.
                    </div>
                </div>
                
                <div class="date-range-container-modal">
                    <div class="date-range-picker-modal" id="dateRangePickerModal">
                        <div class="date-picker-header">
                            <button type="button" class="btn-nav" id="prevMonthModal">&lt;</button>
                            <span id="currentMonthModal"></span>
                            <button type="button" class="btn-nav" id="nextMonthModal">&gt;</button>
                        </div>
                        <div class="calendar-grid" id="calendarGridModal">
                            <!-- Le calendrier sera généré par JavaScript -->
                        </div>
                    </div>
                    
                    <div class="selected-period-display mt-3">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label"><strong>Date de début :</strong></label>
                                <div class="selected-date" id="selectedStartDate">Non sélectionnée</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label"><strong>Date de fin :</strong></label>
                                <div class="selected-date" id="selectedEndDate">Non sélectionnée</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Annuler
                </button>
                <button type="button" class="btn btn-success" id="applyDateRangeModal">
                    <i class="fas fa-check me-1"></i>
                    Appliquer la période
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sélection de Période pour Timeline -->
<div class="modal fade" id="timelineDateRangeModal" tabindex="-1" aria-labelledby="timelineDateRangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="timelineDateRangeModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Sélectionner une période pour la frise chronologique
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instructions :</strong> Cliquez sur une première date pour le début, puis sur une seconde date pour la fin de la période.
                    </div>
                </div>
                
                <div class="date-range-container-timeline">
                    <div class="date-range-picker-timeline" id="dateRangePickerTimeline">
                        <div class="date-picker-header">
                            <button type="button" class="btn-nav" id="prevMonthTimeline">&lt;</button>
                            <span id="currentMonthTimeline"></span>
                            <button type="button" class="btn-nav" id="nextMonthTimeline">&gt;</button>
                        </div>
                        <div class="calendar-grid" id="calendarGridTimeline">
                            <!-- Le calendrier sera généré par JavaScript -->
                        </div>
                    </div>
                    
                    <div class="selected-period-display mt-3">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label"><strong>Date de début :</strong></label>
                                <div class="selected-date" id="selectedStartDateTimeline">Non sélectionnée</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label"><strong>Date de fin :</strong></label>
                                <div class="selected-date" id="selectedEndDateTimeline">Non sélectionnée</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Annuler
                </button>
                <button type="button" class="btn btn-success" id="applyTimelineDateRange">
                    <i class="fas fa-check me-1"></i>
                    Appliquer la période
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le modal timeline employé */
.employee-timeline-modal {
    background-color: #f8f9fa !important; /* Gris clair en mode jour */
    border: none !important;
    border-radius: 15px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
}

.employee-timeline-body {
    background-color: #f8f9fa !important; /* Gris clair en mode jour */
    padding: 20px !important;
}

/* Mode sombre pour le modal timeline */
body.dark-mode .employee-timeline-modal {
    background-color: #1a1a1a !important; /* Noir en mode nuit */
}

body.dark-mode .employee-timeline-body {
    background-color: #1a1a1a !important; /* Noir en mode nuit */
    color: #ffffff !important;
}

/* Ajustements pour la timeline dans le modal */
.employee-timeline-body .timeline-modern {
    background-color: #f8f9fa !important; /* Gris clair en mode jour */
}

body.dark-mode .employee-timeline-body .timeline-modern {
    background-color: #1a1a1a !important; /* Noir en mode nuit */
}

/* Styles pour les contrôles de la timeline dans le modal */
.employee-timeline-body .timeline-controls {
    background-color: #ffffff !important; /* Blanc en mode jour */
    border: 1px solid #e3e6ea !important;
}

body.dark-mode .employee-timeline-body .timeline-controls {
    background-color: #2d2d2d !important; /* Gris foncé en mode nuit */
    border: 1px solid #444 !important;
}

body.dark-mode .employee-timeline-body .control-input {
    background-color: #2d2d2d !important;
    border-color: #444 !important;
    color: #ffffff !important;
}

body.dark-mode .employee-timeline-body .btn-timeline-modern {
    background: #2b2b2b !important;
    color: #fff !important;
    border: 1px solid #3a3a3a !important;
}

/* Mode sombre pour les boutons de sélection rapide */
body.dark-mode .btn-quick-select {
    background: #4a5568 !important;
    color: #e2e8f0 !important;
}

body.dark-mode .btn-quick-select:hover {
    background: #2d3748 !important;
    box-shadow: 0 4px 12px rgba(74, 85, 104, 0.4) !important;
}

/* Styles pour les cartes d'événements en mode jour */
.employee-timeline-body .timeline-event {
    background-color: #ffffff !important; /* Blanc en mode jour */
    border: 1px solid #e3e6ea !important;
    color: #333 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
}

/* Styles spécifiques pour le temps et le titre en mode jour */
.employee-timeline-body .timeline-event .event-time {
    color: #000000 !important; /* Noir en mode jour */
    font-weight: 600 !important;
}

.employee-timeline-body .timeline-event .event-title {
    color: #000000 !important; /* Noir en mode jour */
    font-weight: 600 !important;
}

/* Styles pour les cartes d'événements en mode nuit */
body.dark-mode .employee-timeline-body .timeline-event {
    background-color: #2d2d2d !important; /* Gris foncé en mode nuit */
    border: 1px solid #444 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
}

/* Styles spécifiques pour le temps et le titre en mode nuit */
body.dark-mode .employee-timeline-body .timeline-event .event-time {
    color: #ffffff !important; /* Blanc en mode nuit */
    font-weight: 600 !important;
}

body.dark-mode .employee-timeline-body .timeline-event .event-title {
    color: #ffffff !important; /* Blanc en mode nuit */
    font-weight: 600 !important;
}

/* Styles pour les autres éléments des cartes en mode jour */
.employee-timeline-body .timeline-event .event-subtitle {
    color: #495057 !important; /* Gris foncé en mode jour */
    font-weight: 500 !important;
}

.employee-timeline-body .timeline-event .event-employee {
    color: #6c757d !important; /* Gris moyen en mode jour */
    font-weight: 500 !important;
}

/* Styles pour les autres éléments des cartes en mode nuit */
body.dark-mode .employee-timeline-body .timeline-event .event-subtitle {
    color: #e9ecef !important; /* Gris clair en mode nuit */
    font-weight: 500 !important;
}

body.dark-mode .employee-timeline-body .timeline-event .event-employee {
    color: #adb5bd !important; /* Gris clair en mode nuit */
    font-weight: 500 !important;
}

/* Couleurs spécifiques par type d'événement en mode jour */
.employee-timeline-body .timeline-event.event-arrivee {
    border-left: 4px solid #28a745 !important; /* Vert pour arrivée */
    background-color: #f8fff9 !important;
}

.employee-timeline-body .timeline-event.event-depart {
    border-left: 4px solid #dc3545 !important; /* Rouge pour départ */
    background-color: #fff8f8 !important;
}

.employee-timeline-body .timeline-event.event-demarrage {
    border-left: 4px solid #007bff !important; /* Bleu pour démarrage réparation */
    background-color: #f8f9ff !important;
}

.employee-timeline-body .timeline-event.event-changement_statut {
    border-left: 4px solid #ffc107 !important; /* Jaune pour changement statut */
    background-color: #fffdf8 !important;
}

.employee-timeline-body .timeline-event.event-pause_debut {
    border-left: 4px solid #fd7e14 !important; /* Orange pour début pause */
    background-color: #fff9f5 !important;
}

.employee-timeline-body .timeline-event.event-pause_fin {
    border-left: 4px solid #20c997 !important; /* Teal pour fin pause */
    background-color: #f8fffe !important;
}

/* Couleurs spécifiques par type d'événement en mode nuit */
body.dark-mode .employee-timeline-body .timeline-event.event-arrivee {
    border-left: 4px solid #28a745 !important;
    background-color: #1a2e1f !important;
}

body.dark-mode .employee-timeline-body .timeline-event.event-depart {
    border-left: 4px solid #dc3545 !important;
    background-color: #2e1a1a !important;
}

body.dark-mode .employee-timeline-body .timeline-event.event-demarrage {
    border-left: 4px solid #007bff !important;
    background-color: #1a1e2e !important;
}

body.dark-mode .employee-timeline-body .timeline-event.event-changement_statut {
    border-left: 4px solid #ffc107 !important;
    background-color: #2e2a1a !important;
}

body.dark-mode .employee-timeline-body .timeline-event.event-pause_debut {
    border-left: 4px solid #fd7e14 !important;
    background-color: #2e221a !important;
}

body.dark-mode .employee-timeline-body .timeline-event.event-pause_fin {
    border-left: 4px solid #20c997 !important;
    background-color: #1a2e28 !important;
}

body.dark-mode .employee-timeline-body .event-details {
    color: rgba(255, 255, 255, 0.8) !important;
}

/* Styles pour les cartes d'interruption (temps entre interventions) */
.employee-timeline-body .timeline-interruption {
    margin: 15px 0 !important;
    padding: 0 !important;
}

.employee-timeline-body .interruption-time {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important; /* Bleu clair en mode jour */
    color: #1565c0 !important;
    border: 1px solid #90caf9 !important;
    padding: 8px 16px !important;
    border-radius: 20px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15) !important;
    display: inline-block !important;
    margin-left: 0 !important;
    position: relative !important;
    z-index: 2 !important;
}

.employee-timeline-body .interruption-time:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.25) !important;
    transition: all 0.3s ease !important;
}

/* Mode nuit pour les cartes d'interruption */
body.dark-mode .employee-timeline-body .interruption-time {
    background: linear-gradient(135deg, #263238 0%, #37474f 100%) !important; /* Gris-bleu foncé en mode nuit */
    color: #81d4fa !important;
    border: 1px solid #546e7a !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
}

body.dark-mode .employee-timeline-body .interruption-time:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4) !important;
}

/* Ligne d'interruption */
.employee-timeline-body .interruption-line {
    position: absolute !important;
    left: 150px !important;
    right: 0 !important;
    top: 50% !important;
    height: 2px !important;
    background: linear-gradient(90deg, rgba(33, 150, 243, 0.3), transparent) !important;
    z-index: 1 !important;
}

body.dark-mode .employee-timeline-body .interruption-line {
    background: linear-gradient(90deg, rgba(129, 212, 250, 0.3), transparent) !important;
}

/* Couleurs spéciales selon la durée d'interruption */

/* Interruption courte (moins de 5 minutes) - Vert */
.employee-timeline-body .interruption-time.short-break {
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%) !important;
    color: #2e7d32 !important;
    border: 1px solid #81c784 !important;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15) !important;
}

body.dark-mode .employee-timeline-body .interruption-time.short-break {
    background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%) !important;
    color: #a5d6a7 !important;
    border: 1px solid #4caf50 !important;
}

/* Interruption moyenne (5-30 minutes) - Bleu (par défaut) */
.employee-timeline-body .interruption-time.medium-break {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
    color: #1565c0 !important;
    border: 1px solid #90caf9 !important;
}

body.dark-mode .employee-timeline-body .interruption-time.medium-break {
    background: linear-gradient(135deg, #263238 0%, #37474f 100%) !important;
    color: #81d4fa !important;
    border: 1px solid #546e7a !important;
}

/* Interruption longue (30-60 minutes) - Orange */
.employee-timeline-body .interruption-time.long-break {
    background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%) !important;
    color: #e65100 !important;
    border: 1px solid #ffb74d !important;
    box-shadow: 0 2px 8px rgba(255, 152, 0, 0.15) !important;
}

body.dark-mode .employee-timeline-body .interruption-time.long-break {
    background: linear-gradient(135deg, #e65100 0%, #ff6f00 100%) !important;
    color: #ffcc02 !important;
    border: 1px solid #ff9800 !important;
}

/* Interruption très longue (plus de 60 minutes) - Rouge */
.employee-timeline-body .interruption-time.very-long-break {
    background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%) !important;
    color: #c62828 !important;
    border: 1px solid #ef5350 !important;
    box-shadow: 0 2px 8px rgba(244, 67, 54, 0.15) !important;
}

body.dark-mode .employee-timeline-body .interruption-time.very-long-break {
    background: linear-gradient(135deg, #b71c1c 0%, #d32f2f 100%) !important;
    color: #ffcdd2 !important;
    border: 1px solid #f44336 !important;
}

/* Style pour les cartes "aucune activité" */
.employee-timeline-body .no-events {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
    border: 1px solid #e9ecef !important;
    padding: 2rem !important;
    border-radius: 12px !important;
    text-align: center !important;
}

body.dark-mode .employee-timeline-body .no-events {
    background-color: #2d2d2d !important;
    color: #adb5bd !important;
    border: 1px solid #444 !important;
}

body.dark-mode .employee-timeline-body .spinner-border {
    color: #667eea !important;
}

/* Styles pour le conteneur et l'en-tête de la timeline */
.employee-timeline-body .timeline-container {
    background-color: transparent !important;
}

.employee-timeline-body .timeline-date-header {
    background-color: rgba(255, 255, 255, 0.8) !important; /* Blanc semi-transparent en mode jour */
    color: #222 !important;
    border: 1px solid #e3e6ea !important;
}

body.dark-mode .employee-timeline-body .timeline-date-header {
    background-color: rgba(45, 45, 45, 0.8) !important; /* Gris foncé semi-transparent en mode nuit */
    color: #ffffff !important;
    border: 1px solid #444 !important;
}

/* Styles pour la ligne de timeline */
.employee-timeline-body .timeline-line {
    background-color: transparent !important;
}

/* Styles pour la carte employé cliquable */
.employee-card-clickable {
    cursor: pointer !important;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
}

.employee-card-clickable::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(0, 123, 255, 0.1), rgba(0, 123, 255, 0.05));
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
    z-index: 1;
}

.employee-card-clickable:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2) !important;
    border-color: rgba(0, 123, 255, 0.3);
}

.employee-card-clickable:hover::before {
    opacity: 1;
}

.employee-card-clickable:hover .timeline-indicator {
    opacity: 1;
    transform: translateX(5px);
}

.employee-card-clickable:hover .card-header {
    background: linear-gradient(135deg, var(--header-bg-color, #007bff), rgba(0, 123, 255, 0.8)) !important;
}

.timeline-indicator {
    opacity: 0.7;
    transition: all 0.3s ease;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.timeline-indicator i {
    animation: pulse 2s infinite;
}

.timeline-indicator small {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@keyframes pulse {
    0% { opacity: 0.7; }
    50% { opacity: 1; }
    100% { opacity: 0.7; }
}

/* Indicateur visuel pour montrer que c'est cliquable */
.employee-card-clickable .card-header::after {
    content: "👆 Cliquer pour voir la timeline";
    position: absolute;
    top: 10px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
    color: #333;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 2;
}

.employee-card-clickable:hover .card-header::after {
    opacity: 1;
    transform: translateY(0);
}

/* Styles pour la timeline dans le modal */
.timeline-container {
    position: relative;
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 15px;
}

.timeline-item {
    position: relative;
    padding-left: 50px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 40px;
    bottom: -20px;
    width: 2px;
    background: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 10px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    z-index: 1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    margin-left: 10px;
}

.timeline-item.inactive-period .timeline-marker {
    width: 30px;
    height: 30px;
    font-size: 12px;
    top: 15px;
}

.timeline-item.inactive-period .timeline-content .card {
    background-color: #f8f9fa;
    border-style: dashed;
}

/* Animation pour les éléments de timeline */
.timeline-item {
    opacity: 0;
    transform: translateX(-20px);
    animation: slideInTimeline 0.5s ease forwards;
}

.timeline-item:nth-child(1) { animation-delay: 0.1s; }
.timeline-item:nth-child(2) { animation-delay: 0.2s; }
.timeline-item:nth-child(3) { animation-delay: 0.3s; }
.timeline-item:nth-child(4) { animation-delay: 0.4s; }
.timeline-item:nth-child(5) { animation-delay: 0.5s; }
.timeline-item:nth-child(n+6) { animation-delay: 0.6s; }

@keyframes slideInTimeline {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .timeline-item {
        padding-left: 40px;
    }
    
    .timeline-marker {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
    
    .timeline-item:not(:last-child)::before {
        left: 15px;
    }
}

/* Mode sombre pour la timeline */
.dark-mode .timeline-item:not(:last-child)::before {
    background: #495057;
}

.dark-mode .timeline-item.inactive-period .timeline-content .card {
    background-color: #343a40;
    border-color: #495057;
}
</style> 

<script>
// Fonction pour ouvrir la frise chronologique d'un employé
function openEmployeeTimeline(employeeId, employeeName) {
    console.log('🎯 openEmployeeTimeline appelée:', employeeId, employeeName);
    
    // Stocker l'employé actuel pour le modal de période personnalisée
    window.currentTimelineEmployeeId = employeeId;
    window.currentTimelineEmployeeName = employeeName;
    
    // Vérifier les éléments du modal
    const modalElement = document.getElementById('employeeTimelineModal');
    const modalName = document.getElementById('employeeModalName');
    const modalContent = document.getElementById('employeeTimelineContent');
    
    console.log('🎯 Éléments modal:', {
        modal: !!modalElement,
        name: !!modalName,
        content: !!modalContent
    });
    
    if (!modalElement || !modalName || !modalContent) {
        console.error('❌ Éléments du modal manquants');
        alert('Erreur: Éléments du modal manquants');
        return;
    }
    
    // Mettre à jour le titre du modal
    modalName.textContent = employeeName;
    
    // Afficher le modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    console.log('🎯 Modal affiché, chargement des données...');
    
    // Charger les données de la frise chronologique
    loadEmployeeTimelineData(employeeId, employeeName);
}

// Fonction pour charger les données de la timeline
function loadEmployeeTimelineData(employeeId, employeeName) {
    console.log('📊 loadEmployeeTimelineData appelée:', employeeId, employeeName);
    
    const today = new Date().toISOString().split('T')[0];
    const modalContent = document.getElementById('employeeTimelineContent');
    
    console.log('📊 Éléments:', {
        today: today,
        modalContent: !!modalContent
    });
    
    if (!modalContent) {
        console.error('❌ employeeTimelineContent non trouvé');
        return;
    }
    
    modalContent.innerHTML = `
        <div class="timeline-modern">
            <div class="timeline-header">
                <h1 class="timeline-title">⏰ Frise Chronologique de ${employeeName}</h1>
                <p class="timeline-subtitle">Activité du ${new Date().toLocaleDateString('fr-FR')}</p>
            </div>
            
            <div class="timeline-controls">
                <!-- Boutons de sélection rapide -->
                <div class="quick-select-buttons">
                    <button type="button" class="btn-quick-select" onclick="setQuickPeriodModal('today')">Aujourd'hui</button>
                    <button type="button" class="btn-quick-select" onclick="setQuickPeriodModal('yesterday')">Hier</button>
                    <button type="button" class="btn-quick-select" onclick="setQuickPeriodModal('thisWeek')">Cette semaine</button>
                    <button type="button" class="btn-quick-select" onclick="setQuickPeriodModal('lastWeek')">Semaine dernière</button>
                    <button type="button" class="btn-quick-select" onclick="setQuickPeriodModal('thisMonth')">Ce mois</button>
                    <button type="button" class="btn-quick-select btn-quick-select-custom" onclick="openTimelineDateRangeModal()">
                        <i class="fas fa-calendar-alt me-1"></i>Personnalisé
                    </button>
                </div>
                
                <!-- Recherche par numéro de réparation -->
                <div class="timeline-search-section">
                    <div class="search-header">
                        <h6 class="search-title">
                            <i class="fas fa-search me-2"></i>Recherche par numéro de réparation
                        </h6>
                    </div>
                    <div class="search-controls">
                        <div class="search-input-group">
                            <input type="text" 
                                   class="search-input" 
                                   id="timeline-search-repair" 
                                   placeholder="Entrez le numéro de réparation (ex: REP-2024-001)"
                                   maxlength="20">
                            <button type="button" 
                                    class="btn-search-repair" 
                                    onclick="searchTimelineByRepair()">
                                <i class="fas fa-search me-1"></i>Rechercher
                            </button>
                            <button type="button" 
                                    class="btn-clear-search" 
                                    onclick="clearTimelineSearch()"
                                    title="Effacer la recherche">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="search-results" id="timeline-search-results" style="display: none;">
                            <!-- Les résultats de recherche apparaîtront ici -->
                        </div>
                    </div>
                </div>
                
                <!-- Sélection manuelle -->
                <div class="controls-grid">
                    <div class="control-group">
                        <label class="control-label">📅 DATE DÉBUT</label>
                        <input type="date" class="control-input" id="modal-date-debut-chrono" value="${today}">
                    </div>
                    <div class="control-group">
                        <label class="control-label">📅 DATE FIN</label>
                        <input type="date" class="control-input" id="modal-date-fin-chrono" value="${today}">
                    </div>
                    <div class="control-group">
                        <button type="button" class="btn-timeline-modern" onclick="refreshModalTimeline(${employeeId}, '${employeeName}')">
                            🔍 Actualiser
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="timeline-container">
                <div class="timeline-date-header" id="modal-date-display">
                    📅 ${new Date().toLocaleDateString('fr-FR', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    })}
                    <br>👤 ${employeeName}
                </div>
                
                <div id="modal-timeline-events" class="timeline-line">
                    <div class="text-center py-4">
                        <div class="spinner-border text-white" role="status">
                            <span class="visually-hidden">Chargement des événements...</span>
                        </div>
                        <p class="mt-2 text-white">Récupération des données...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Charger les événements réels pour la date du jour (début = fin = aujourd'hui)
    fetchEmployeeEventsPeriod(employeeId, today, today);
}

// Fonction pour récupérer les événements d'un employé via AJAX
function fetchEmployeeEvents(employeeId, date) {
    console.log('🌐 fetchEmployeeEvents appelée:', employeeId, date);
    
    const eventsContainer = document.getElementById('modal-timeline-events');
    console.log('🌐 eventsContainer trouvé:', !!eventsContainer);
    
    if (!eventsContainer) {
        console.error('❌ modal-timeline-events non trouvé');
        return;
    }
    
    const apiUrl = `api/employee_timeline.php?employee_id=${employeeId}&date=${date}`;
    console.log('🌐 Appel API:', apiUrl);
    
    // Faire un appel AJAX à l'API
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                eventsContainer.innerHTML = `
                    <div class="no-events">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Erreur</h3>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            if (!data.events || data.events.length === 0) {
                eventsContainer.innerHTML = `
                    <div class="no-events">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Aucune activité</h3>
                        <p>Aucune activité trouvée pour ${data.employee_name} le ${new Date(date).toLocaleDateString('fr-FR')}.</p>
                    </div>
                `;
                return;
            }
            
            // Mettre à jour l'affichage de la date
            const dateDisplay = document.getElementById('modal-date-display');
            if (dateDisplay) {
                const dateObj = new Date(date);
                dateDisplay.innerHTML = `
                    📅 ${dateObj.toLocaleDateString('fr-FR', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    })}
                    <br>👤 ${data.employee_name}
                `;
            }
            
            // Générer le HTML des événements
            let eventsHtml = '';
            let previousTime = null;
            
            data.events.forEach((event, index) => {
                // Calculer le temps d'interruption
                if (previousTime && index > 0) {
                    const currentTimestamp = new Date(event.time).getTime();
                    const previousTimestamp = new Date(previousTime).getTime();
                    const diffMinutes = Math.round((currentTimestamp - previousTimestamp) / (1000 * 60));
                    
                    if (diffMinutes > 0) {
                        let interruptionTime = diffMinutes < 60 ? 
                            diffMinutes + ' minute' + (diffMinutes > 1 ? 's' : '') :
                            Math.floor(diffMinutes / 60) + 'h' + (diffMinutes % 60 > 0 ? ' ' + (diffMinutes % 60) + 'min' : '');
                        
                        // Déterminer la classe CSS selon la durée
                        let breakClass = 'medium-break'; // Par défaut
                        if (diffMinutes < 5) {
                            breakClass = 'short-break';
                        } else if (diffMinutes >= 30 && diffMinutes < 60) {
                            breakClass = 'long-break';
                        } else if (diffMinutes >= 60) {
                            breakClass = 'very-long-break';
                        }
                        
                        eventsHtml += `
                            <div class="timeline-interruption">
                                <div class="interruption-time ${breakClass}">⏱️ ${interruptionTime}</div>
                                <div class="interruption-line"></div>
                            </div>
                        `;
                    }
                }
                
                const eventTime = new Date(event.time);
                const timeString = eventTime.toLocaleTimeString('fr-FR', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                eventsHtml += `
                    <div class="timeline-event event-${event.action}">
                        <div class="event-time">
                            🕐 ${timeString}
                        </div>
                        <div class="event-title">
                            ${event.title}
                        </div>
                        <div class="event-subtitle">
                            ${event.subtitle}
                        </div>
                        <div class="event-employee">
                            👤 ${event.employee}
                        </div>
                        <div class="event-details">
                            💬 ${event.details}
                        </div>
                    </div>
                `;
                
                previousTime = event.time;
            });
            
            eventsContainer.innerHTML = eventsHtml;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des événements:', error);
            eventsContainer.innerHTML = `
                <div class="no-events">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Erreur de chargement</h3>
                    <p>Impossible de charger les événements. Veuillez réessayer.</p>
                </div>
            `;
        });
}

// Fonction pour afficher les événements de timeline
function displayTimelineEvents(events) {
    console.log('🎨 displayTimelineEvents appelée avec', events.length, 'événements');
    
    const eventsContainer = document.getElementById('modal-timeline-events');
    if (!eventsContainer) {
        console.error('❌ modal-timeline-events non trouvé');
        return;
    }
    
    if (!events || events.length === 0) {
        eventsContainer.innerHTML = `
            <div class="no-events">
                <i class="fas fa-calendar-times"></i>
                <h3>Aucun événement</h3>
                <p>Aucune activité trouvée pour cette période.</p>
            </div>
        `;
        return;
    }
    
    let eventsHtml = '';
    let previousTime = null;
    
    events.forEach((event, index) => {
        // Calcul des interruptions entre événements
        if (previousTime && event.time) {
            const currentTime = new Date(event.time);
            const prevTime = new Date(previousTime);
            const timeDiff = (currentTime - prevTime) / (1000 * 60); // différence en minutes
            
            if (timeDiff > 30) { // Plus de 30 minutes d'écart
                const interruptionTime = Math.floor(timeDiff / 60) + 'h' + (timeDiff % 60).toFixed(0) + 'min';
                const breakClass = timeDiff > 120 ? 'long-break' : 'short-break';
                
                eventsHtml += `
                    <div class="timeline-interruption">
                        <div class="interruption-time ${breakClass}">⏱️ ${interruptionTime}</div>
                        <div class="interruption-line"></div>
                    </div>
                `;
            }
        }
        
        const eventTime = new Date(event.time);
        const timeString = eventTime.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        eventsHtml += `
            <div class="timeline-event event-${event.action}">
                <div class="event-time">
                    🕐 ${timeString}
                </div>
                <div class="event-title">
                    ${event.title}
                </div>
                <div class="event-subtitle">
                    ${event.subtitle}
                </div>
                <div class="event-employee">
                    👤 ${event.employee}
                </div>
                <div class="event-details">
                    💬 ${event.details}
                </div>
            </div>
        `;
        
        previousTime = event.time;
    });
    
    eventsContainer.innerHTML = eventsHtml;
    console.log('🎨 Timeline affichée avec', events.length, 'événements');
}

// Fonction pour récupérer les événements d'un employé sur une période via AJAX
function fetchEmployeeEventsPeriod(employeeId, dateDebut, dateFin) {
    console.log('🌐 fetchEmployeeEventsPeriod appelée:', employeeId, dateDebut, dateFin);
    
    const eventsContainer = document.getElementById('modal-timeline-events');
    console.log('🌐 eventsContainer trouvé:', !!eventsContainer);
    
    if (!eventsContainer) {
        console.error('❌ modal-timeline-events non trouvé');
        return;
    }
    
    const apiUrl = `api/employee_timeline.php?employee_id=${employeeId}&date_debut=${dateDebut}&date_fin=${dateFin}`;
    console.log('🌐 Appel API:', apiUrl);
    
    // Faire un appel AJAX à l'API
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                eventsContainer.innerHTML = `
                    <div class="no-events">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Erreur</h3>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            if (!data.events || data.events.length === 0) {
                eventsContainer.innerHTML = `
                    <div class="no-events">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Aucun événement</h3>
                        <p>Aucune activité trouvée pour cette période</p>
                    </div>
                `;
                return;
            }
            
            // Afficher les événements
            displayTimelineEvents(data.events);
            
            // Mettre à jour l'en-tête avec la période
            const dateHeader = document.getElementById('modal-date-display');
            if (dateHeader) {
                const dateDebutFormatted = new Date(dateDebut).toLocaleDateString('fr-FR');
                const dateFinFormatted = new Date(dateFin).toLocaleDateString('fr-FR');
                const employeeName = data.employee_name || 'Employé';
                
                if (dateDebut === dateFin) {
                    dateHeader.innerHTML = `📅 ${dateDebutFormatted}<br>👤 ${employeeName}`;
                } else {
                    dateHeader.innerHTML = `📅 Du ${dateDebutFormatted} au ${dateFinFormatted}<br>👤 ${employeeName}`;
                }
            }
        })
        .catch(error => {
            console.error('🚨 Erreur lors du chargement des événements:', error);
            eventsContainer.innerHTML = `
                <div class="no-events">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Erreur de chargement</h3>
                    <p>Impossible de charger les événements. Veuillez réessayer.</p>
                </div>
            `;
        });
}

// Fonction pour actualiser la timeline du modal
function refreshModalTimeline(employeeId, employeeName) {
    const dateDebut = document.getElementById('modal-date-debut-chrono').value;
    const dateFin = document.getElementById('modal-date-fin-chrono').value;
    const eventsContainer = document.getElementById('modal-timeline-events');
    
    if (!dateDebut || !dateFin) {
        alert('Veuillez sélectionner une date de début et une date de fin');
        return;
    }
    
    if (new Date(dateDebut) > new Date(dateFin)) {
        alert('La date de début doit être antérieure ou égale à la date de fin');
        return;
    }
    
    eventsContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-white" role="status">
                <span class="visually-hidden">Actualisation...</span>
            </div>
            <p class="mt-2 text-white">Actualisation des données...</p>
        </div>
    `;
    
    fetchEmployeeEventsPeriod(employeeId, dateDebut, dateFin);
}

// Fonction pour définir des périodes rapides dans le modal
function setQuickPeriodModal(period) {
    const today = new Date();
    let dateDebut, dateFin;
    
    switch (period) {
        case 'today':
            dateDebut = dateFin = formatDateForInput(today);
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            dateDebut = dateFin = formatDateForInput(yesterday);
            break;
        case 'thisWeek':
            const startOfWeek = new Date(today);
            const day = startOfWeek.getDay();
            const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1); // Lundi
            startOfWeek.setDate(diff);
            dateDebut = formatDateForInput(startOfWeek);
            dateFin = formatDateForInput(today);
            break;
        case 'lastWeek':
            const lastWeekEnd = new Date(today);
            lastWeekEnd.setDate(today.getDate() - today.getDay()); // Dimanche
            const lastWeekStart = new Date(lastWeekEnd);
            lastWeekStart.setDate(lastWeekEnd.getDate() - 6); // Lundi
            dateDebut = formatDateForInput(lastWeekStart);
            dateFin = formatDateForInput(lastWeekEnd);
            break;
        case 'thisMonth':
            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            dateDebut = formatDateForInput(startOfMonth);
            dateFin = formatDateForInput(today);
            break;
        default:
            return;
    }
    
    // Mettre à jour les champs de date
    const debutInput = document.getElementById('modal-date-debut-chrono');
    const finInput = document.getElementById('modal-date-fin-chrono');
    
    if (debutInput) debutInput.value = dateDebut;
    if (finInput) finInput.value = dateFin;
}

// Fonction helper pour formater la date pour les inputs HTML
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Date Range Picker Modal
class DateRangePickerModal {
    constructor() {
        this.startDate = null;
        this.endDate = null;
        this.currentMonth = new Date();
        this.isSelecting = false;
        this.tempStartDate = null;
        
        this.initElements();
        this.bindEvents();
    }
    
    initElements() {
        this.monthLabel = document.getElementById('currentMonthModal');
        this.grid = document.getElementById('calendarGridModal');
        this.prevBtn = document.getElementById('prevMonthModal');
        this.nextBtn = document.getElementById('nextMonthModal');
        this.applyBtn = document.getElementById('applyDateRangeModal');
        this.hiddenStart = document.getElementById('date_debut');
        this.hiddenEnd = document.getElementById('date_fin');
        this.startDisplay = document.getElementById('selectedStartDate');
        this.endDisplay = document.getElementById('selectedEndDate');
        this.modal = document.getElementById('dateRangeModal');
    }
    
    bindEvents() {
        this.prevBtn.addEventListener('click', () => this.previousMonth());
        this.nextBtn.addEventListener('click', () => this.nextMonth());
        this.applyBtn.addEventListener('click', () => this.apply());
        
        // Réinitialiser quand le modal s'ouvre
        this.modal.addEventListener('show.bs.modal', () => {
            this.initializeFromExistingValues();
            this.generateCalendar();
        });
        
        // Nettoyer quand le modal se ferme
        this.modal.addEventListener('hidden.bs.modal', () => {
            this.reset();
        });
    }
    
    initializeFromExistingValues() {
        if (this.hiddenStart.value && this.hiddenEnd.value) {
            this.startDate = new Date(this.hiddenStart.value);
            this.endDate = new Date(this.hiddenEnd.value);
            this.updateDisplays();
        }
    }
    
    reset() {
        this.isSelecting = false;
        this.tempStartDate = null;
    }
    
    previousMonth() {
        this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
        this.generateCalendar();
    }
    
    nextMonth() {
        this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
        this.generateCalendar();
    }
    
    generateCalendar() {
        const year = this.currentMonth.getFullYear();
        const month = this.currentMonth.getMonth();
        
        // Mettre à jour le label du mois
        const monthNames = [
            'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
        ];
        this.monthLabel.textContent = `${monthNames[month]} ${year}`;
        
        // Vider la grille
        this.grid.innerHTML = '';
        
        // Ajouter les en-têtes des jours
        const dayHeaders = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        dayHeaders.forEach(day => {
            const header = document.createElement('div');
            header.className = 'calendar-header';
            header.textContent = day;
            this.grid.appendChild(header);
        });
        
        // Premier jour du mois et nombre de jours
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        
        // Ajuster le premier jour (0 = dimanche, 1 = lundi, etc.)
        let startDay = firstDay.getDay();
        startDay = startDay === 0 ? 6 : startDay - 1;
        
        // Ajouter les jours du mois précédent
        const prevMonth = new Date(year, month - 1, 0);
        for (let i = startDay - 1; i >= 0; i--) {
            const day = prevMonth.getDate() - i;
            const dayElement = this.createDayElement(day, year, month - 1, true);
            this.grid.appendChild(dayElement);
        }
        
        // Ajouter les jours du mois actuel
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = this.createDayElement(day, year, month, false);
            this.grid.appendChild(dayElement);
        }
        
        // Compléter avec les jours du mois suivant
        const totalCells = this.grid.children.length - 7; // Moins les en-têtes
        const remainingCells = 42 - totalCells; // 6 semaines * 7 jours
        for (let day = 1; day <= remainingCells; day++) {
            const dayElement = this.createDayElement(day, year, month + 1, true);
            this.grid.appendChild(dayElement);
        }
    }
    
    createDayElement(day, year, month, isOtherMonth) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        
        const date = new Date(year, month, day);
        const today = new Date();
        
        if (isOtherMonth) {
            dayElement.classList.add('other-month');
        }
        
        if (this.isSameDay(date, today)) {
            dayElement.classList.add('today');
        }
        
        if (this.startDate && this.isSameDay(date, this.startDate)) {
            dayElement.classList.add('selected-start');
        }
        
        if (this.endDate && this.isSameDay(date, this.endDate)) {
            dayElement.classList.add('selected-end');
        }
        
        if (this.startDate && this.endDate && this.isInRange(date, this.startDate, this.endDate)) {
            dayElement.classList.add('in-range');
        }
        
        dayElement.addEventListener('click', () => this.selectDate(date));
        
        return dayElement;
    }
    
    selectDate(date) {
        if (!this.isSelecting) {
            // Première sélection - date de début
            this.tempStartDate = new Date(date);
            this.startDate = new Date(date);
            this.endDate = null;
            this.isSelecting = true;
        } else {
            // Deuxième sélection - date de fin
            if (date < this.tempStartDate) {
                // Si la date de fin est antérieure, inverser
                this.startDate = new Date(date);
                this.endDate = new Date(this.tempStartDate);
            } else {
                this.startDate = new Date(this.tempStartDate);
                this.endDate = new Date(date);
            }
            this.isSelecting = false;
            this.tempStartDate = null;
        }
        
        this.updateDisplays();
        this.generateCalendar();
    }
    
    updateDisplays() {
        if (this.startDate) {
            this.startDisplay.textContent = this.formatDisplayDate(this.startDate);
            this.startDisplay.classList.remove('empty');
        } else {
            this.startDisplay.textContent = 'Non sélectionnée';
            this.startDisplay.classList.add('empty');
        }
        
        if (this.endDate) {
            this.endDisplay.textContent = this.formatDisplayDate(this.endDate);
            this.endDisplay.classList.remove('empty');
        } else {
            this.endDisplay.textContent = 'Non sélectionnée';
            this.endDisplay.classList.add('empty');
        }
    }
    
    isSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }
    
    isInRange(date, start, end) {
        return date > start && date < end;
    }
    
    apply() {
        if (this.startDate && this.endDate) {
            this.hiddenStart.value = this.formatDate(this.startDate);
            this.hiddenEnd.value = this.formatDate(this.endDate);
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(this.modal);
            modal.hide();
            
            // Soumettre le formulaire pour appliquer les filtres
            const form = document.getElementById('filterForm');
            if (form) {
                form.submit();
            }
        } else {
            alert('Veuillez sélectionner une date de début et une date de fin.');
        }
    }
    
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    formatDisplayDate(date) {
        return date.toLocaleDateString('fr-FR');
    }
}

// Initialiser le date range picker modal quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('dateRangeModal')) {
        new DateRangePickerModal();
    }
    
    if (document.getElementById('timelineDateRangeModal')) {
        timelineDateRangePicker = new TimelineDateRangePickerModal();
    }
});

// Date Range Picker Modal pour Timeline
class TimelineDateRangePickerModal {
    constructor() {
        this.startDate = null;
        this.endDate = null;
        this.currentMonth = new Date();
        this.isSelecting = false;
        this.tempStartDate = null;
        this.currentEmployeeId = null;
        this.currentEmployeeName = null;
        
        this.initElements();
        this.bindEvents();
    }
    
    initElements() {
        this.monthLabel = document.getElementById('currentMonthTimeline');
        this.grid = document.getElementById('calendarGridTimeline');
        this.prevBtn = document.getElementById('prevMonthTimeline');
        this.nextBtn = document.getElementById('nextMonthTimeline');
        this.applyBtn = document.getElementById('applyTimelineDateRange');
        this.startDisplay = document.getElementById('selectedStartDateTimeline');
        this.endDisplay = document.getElementById('selectedEndDateTimeline');
        this.modal = document.getElementById('timelineDateRangeModal');
    }
    
    bindEvents() {
        this.prevBtn.addEventListener('click', () => this.previousMonth());
        this.nextBtn.addEventListener('click', () => this.nextMonth());
        this.applyBtn.addEventListener('click', () => this.apply());
        
        // Réinitialiser quand le modal s'ouvre
        this.modal.addEventListener('show.bs.modal', () => {
            this.reset();
            this.generateCalendar();
        });
        
        // Nettoyer quand le modal se ferme
        this.modal.addEventListener('hidden.bs.modal', () => {
            this.reset();
        });
    }
    
    reset() {
        this.isSelecting = false;
        this.tempStartDate = null;
        this.startDate = null;
        this.endDate = null;
        this.updateDisplays();
    }
    
    setEmployeeContext(employeeId, employeeName) {
        this.currentEmployeeId = employeeId;
        this.currentEmployeeName = employeeName;
    }
    
    previousMonth() {
        this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
        this.generateCalendar();
    }
    
    nextMonth() {
        this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
        this.generateCalendar();
    }
    
    generateCalendar() {
        const year = this.currentMonth.getFullYear();
        const month = this.currentMonth.getMonth();
        
        // Mettre à jour le label du mois
        const monthNames = [
            'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
        ];
        this.monthLabel.textContent = `${monthNames[month]} ${year}`;
        
        // Vider la grille
        this.grid.innerHTML = '';
        
        // Ajouter les en-têtes des jours
        const dayHeaders = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        dayHeaders.forEach(day => {
            const header = document.createElement('div');
            header.className = 'calendar-header';
            header.textContent = day;
            this.grid.appendChild(header);
        });
        
        // Premier jour du mois et nombre de jours
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        
        // Ajuster le premier jour (0 = dimanche, 1 = lundi, etc.)
        let startDay = firstDay.getDay();
        startDay = startDay === 0 ? 6 : startDay - 1;
        
        // Ajouter les jours du mois précédent
        const prevMonth = new Date(year, month - 1, 0);
        for (let i = startDay - 1; i >= 0; i--) {
            const day = prevMonth.getDate() - i;
            const dayElement = this.createDayElement(day, year, month - 1, true);
            this.grid.appendChild(dayElement);
        }
        
        // Ajouter les jours du mois actuel
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = this.createDayElement(day, year, month, false);
            this.grid.appendChild(dayElement);
        }
        
        // Compléter avec les jours du mois suivant
        const totalCells = this.grid.children.length - 7; // Moins les en-têtes
        const remainingCells = 42 - totalCells; // 6 semaines * 7 jours
        for (let day = 1; day <= remainingCells; day++) {
            const dayElement = this.createDayElement(day, year, month + 1, true);
            this.grid.appendChild(dayElement);
        }
    }
    
    createDayElement(day, year, month, isOtherMonth) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        
        const date = new Date(year, month, day);
        const today = new Date();
        
        if (isOtherMonth) {
            dayElement.classList.add('other-month');
        }
        
        if (this.isSameDay(date, today)) {
            dayElement.classList.add('today');
        }
        
        if (this.startDate && this.isSameDay(date, this.startDate)) {
            dayElement.classList.add('selected-start');
        }
        
        if (this.endDate && this.isSameDay(date, this.endDate)) {
            dayElement.classList.add('selected-end');
        }
        
        if (this.startDate && this.endDate && this.isInRange(date, this.startDate, this.endDate)) {
            dayElement.classList.add('in-range');
        }
        
        dayElement.addEventListener('click', () => this.selectDate(date));
        
        return dayElement;
    }
    
    selectDate(date) {
        if (!this.isSelecting) {
            // Première sélection - date de début
            this.tempStartDate = new Date(date);
            this.startDate = new Date(date);
            this.endDate = null;
            this.isSelecting = true;
        } else {
            // Deuxième sélection - date de fin
            if (date < this.tempStartDate) {
                // Si la date de fin est antérieure, inverser
                this.startDate = new Date(date);
                this.endDate = new Date(this.tempStartDate);
            } else {
                this.startDate = new Date(this.tempStartDate);
                this.endDate = new Date(date);
            }
            this.isSelecting = false;
            this.tempStartDate = null;
        }
        
        this.updateDisplays();
        this.generateCalendar();
    }
    
    updateDisplays() {
        if (this.startDate) {
            this.startDisplay.textContent = this.formatDisplayDate(this.startDate);
            this.startDisplay.classList.remove('empty');
        } else {
            this.startDisplay.textContent = 'Non sélectionnée';
            this.startDisplay.classList.add('empty');
        }
        
        if (this.endDate) {
            this.endDisplay.textContent = this.formatDisplayDate(this.endDate);
            this.endDisplay.classList.remove('empty');
        } else {
            this.endDisplay.textContent = 'Non sélectionnée';
            this.endDisplay.classList.add('empty');
        }
    }
    
    isSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }
    
    isInRange(date, start, end) {
        return date > start && date < end;
    }
    
    apply() {
        if (this.startDate && this.endDate) {
            const dateDebut = this.formatDate(this.startDate);
            const dateFin = this.formatDate(this.endDate);
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(this.modal);
            modal.hide();
            
            // Appliquer la période à la timeline
            if (this.currentEmployeeId && this.currentEmployeeName) {
                fetchEmployeeEventsPeriod(this.currentEmployeeId, dateDebut, dateFin);
            }
        } else {
            alert('Veuillez sélectionner une date de début et une date de fin.');
        }
    }
    
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    formatDisplayDate(date) {
        return date.toLocaleDateString('fr-FR');
    }
}

// Variable globale pour stocker l'instance du picker timeline
let timelineDateRangePicker = null;

// Fonction pour ouvrir le modal de sélection de période timeline
function openTimelineDateRangeModal() {
    // Récupérer les informations de l'employé actuel depuis les variables globales
    const currentEmployeeId = window.currentTimelineEmployeeId || null;
    const currentEmployeeName = window.currentTimelineEmployeeName || '';
    
    if (timelineDateRangePicker && currentEmployeeId) {
        timelineDateRangePicker.setEmployeeContext(currentEmployeeId, currentEmployeeName);
    }
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('timelineDateRangeModal'));
    modal.show();
}

// Fonction pour rechercher par numéro de réparation dans la timeline
function searchTimelineByRepair() {
    console.log('🔍 searchTimelineByRepair appelée');
    
    const searchInput = document.getElementById('timeline-search-repair');
    const resultsContainer = document.getElementById('timeline-search-results');
    const currentEmployeeId = window.currentTimelineEmployeeId || null;
    
    if (!searchInput || !resultsContainer) {
        console.error('❌ Éléments de recherche non trouvés');
        return;
    }
    
    const repairNumber = searchInput.value.trim();
    
    if (!repairNumber) {
        alert('Veuillez entrer un numéro de réparation');
        searchInput.focus();
        return;
    }
    
    if (!currentEmployeeId) {
        console.error('❌ ID employé non défini');
        return;
    }
    
    // Afficher un indicateur de chargement
    resultsContainer.style.display = 'block';
    resultsContainer.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Recherche en cours...</span>
            </div>
            <p class="mt-2 mb-0">Recherche de la réparation ${repairNumber}...</p>
        </div>
    `;
    
    console.log('🔍 Recherche pour:', repairNumber, 'Employé:', currentEmployeeId);
    
    // Appel API pour rechercher les événements liés à cette réparation
    fetch(`api/employee_timeline.php?employee_id=${currentEmployeeId}&repair_number=${encodeURIComponent(repairNumber)}`)
        .then(response => {
            console.log('🌐 Réponse API reçue:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📊 Données de recherche reçues:', data);
            
            if (data.success && data.events && data.events.length > 0) {
                displaySearchResults(data.events, repairNumber);
                // Afficher aussi les événements dans la timeline principale
                displayTimelineEvents(data.events);
                
                // Mettre à jour l'en-tête pour indiquer qu'on affiche une recherche
                const dateHeader = document.getElementById('modal-date-display');
                if (dateHeader) {
                    dateHeader.innerHTML = `
                        <div class="search-mode-header">
                            <i class="fas fa-search me-2"></i>
                            <strong>Résultats pour : ${repairNumber}</strong>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="clearTimelineSearch()">
                                <i class="fas fa-times me-1"></i>Effacer
                            </button>
                        </div>
                    `;
                }
            } else {
                displaySearchResults([], repairNumber);
            }
        })
        .catch(error => {
            console.error('🚨 Erreur lors de la recherche:', error);
            resultsContainer.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    <p>Erreur lors de la recherche. Veuillez réessayer.</p>
                </div>
            `;
        });
}

// Fonction pour afficher les résultats de recherche
function displaySearchResults(events, repairNumber) {
    console.log('📋 displaySearchResults appelée avec', events.length, 'événements');
    
    const resultsContainer = document.getElementById('timeline-search-results');
    if (!resultsContainer) return;
    
    if (events.length === 0) {
        resultsContainer.innerHTML = `
            <div class="search-no-results">
                <i class="fas fa-search text-muted"></i>
                <p>Aucun événement trouvé pour la réparation <strong>${repairNumber}</strong></p>
                <small class="text-muted">Vérifiez le numéro de réparation et réessayez</small>
            </div>
        `;
        return;
    }
    
    let resultsHtml = `
        <div class="search-results-header">
            <strong>${events.length} événement(s) trouvé(s) pour : ${repairNumber}</strong>
        </div>
    `;
    
    events.forEach(event => {
        const eventTime = new Date(event.time);
        const timeString = eventTime.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        const dateString = eventTime.toLocaleDateString('fr-FR');
        
        resultsHtml += `
            <div class="search-result-item" onclick="highlightTimelineEvent('${event.time}')">
                <div class="search-result-repair">
                    🔧 ${repairNumber} - ${event.title}
                </div>
                <div class="search-result-details">
                    📅 ${dateString} à ${timeString} | 👤 ${event.employee} | 💬 ${event.details}
                </div>
            </div>
        `;
    });
    
    resultsContainer.innerHTML = resultsHtml;
}

// Fonction pour effacer la recherche
function clearTimelineSearch() {
    console.log('🧹 clearTimelineSearch appelée');
    
    const searchInput = document.getElementById('timeline-search-repair');
    const resultsContainer = document.getElementById('timeline-search-results');
    const currentEmployeeId = window.currentTimelineEmployeeId || null;
    const currentEmployeeName = window.currentTimelineEmployeeName || '';
    
    if (searchInput) {
        searchInput.value = '';
    }
    
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
        resultsContainer.innerHTML = '';
    }
    
    // Restaurer l'affichage normal de la timeline
    if (currentEmployeeId) {
        loadEmployeeTimelineData(currentEmployeeId, currentEmployeeName);
    }
}

// Fonction pour mettre en évidence un événement spécifique dans la timeline
function highlightTimelineEvent(eventTime) {
    console.log('✨ highlightTimelineEvent appelée pour:', eventTime);
    
    // Retirer les surbrillances existantes
    document.querySelectorAll('.timeline-event.highlighted').forEach(el => {
        el.classList.remove('highlighted');
    });
    
    // Trouver et mettre en évidence l'événement correspondant
    const timelineEvents = document.querySelectorAll('.timeline-event');
    timelineEvents.forEach(eventEl => {
        const eventTimeText = eventEl.querySelector('.event-time');
        if (eventTimeText) {
            // Comparer les heures (format simplifié)
            const searchTime = new Date(eventTime);
            const searchTimeString = searchTime.toLocaleTimeString('fr-FR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            if (eventTimeText.textContent.includes(searchTimeString)) {
                eventEl.classList.add('highlighted');
                eventEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Retirer la surbrillance après 3 secondes
                setTimeout(() => {
                    eventEl.classList.remove('highlighted');
                }, 3000);
                return;
            }
        }
    });
}

// Ajouter l'événement Enter sur le champ de recherche
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('timeline-search-repair');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTimelineByRepair();
            }
        });
    }
});
</script>

<script>
// Gestion du menu dynamique des filtres - Version simplifiée sans Bootstrap JS
document.addEventListener('DOMContentLoaded', function() {
    const filtersToggle = document.getElementById('filtersToggle');
    const filtersContent = document.getElementById('filtersContent');
    const filtersChevron = document.getElementById('filtersChevron');
    
    if (!filtersToggle || !filtersContent || !filtersChevron) {
        console.error('Éléments des filtres non trouvés');
        return;
    }
    
    // Fonction pour basculer l'état
    function toggleFilters() {
        const isExpanded = filtersContent.classList.contains('show');
        
        if (isExpanded) {
            // Fermer
            filtersContent.classList.remove('show');
            filtersChevron.classList.remove('fa-chevron-up');
            filtersChevron.classList.add('fa-chevron-down');
            filtersToggle.setAttribute('aria-expanded', 'false');
            localStorage.setItem('filtersExpanded', 'false');
        } else {
            // Ouvrir
            filtersContent.classList.add('show');
            filtersChevron.classList.remove('fa-chevron-down');
            filtersChevron.classList.add('fa-chevron-up');
            filtersToggle.setAttribute('aria-expanded', 'true');
            localStorage.setItem('filtersExpanded', 'true');
        }
    }
    
    // Gérer le clic
    filtersToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleFilters();
    });
    
    // Restaurer l'état depuis localStorage
    const filtersState = localStorage.getItem('filtersExpanded');
    if (filtersState === 'true') {
        filtersContent.classList.add('show');
        filtersChevron.classList.remove('fa-chevron-down');
        filtersChevron.classList.add('fa-chevron-up');
        filtersToggle.setAttribute('aria-expanded', 'true');
    }
});
</script>

</div> <!-- Fermeture de mainContent -->

<style>
.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

/* Masquer le loader quand la page est chargée */
.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

/* Gestion des deux types de loaders */
.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

/* En mode clair, inverser l'affichage */
body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

/* Loader Mode Clair - Cercle avec couleurs sombres */
.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

/* Texte du loader mode clair */
.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

/* Appliquer le fond du loader à la page - MODE JOUR ET NUIT */
body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.container-fluid,
.container-fluid * {
  background: transparent !important;
}

/* S'assurer que les cartes et éléments restent visibles */
.card,
.modal-content,
.timeline-item,
.filter-card {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .card,
.dark-mode .modal-content,
.dark-mode .timeline-item,
.dark-mode .filter-card {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    setTimeout(function() {
        loader.classList.add('fade-out');
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500);
    }, 300);
});
</script> 