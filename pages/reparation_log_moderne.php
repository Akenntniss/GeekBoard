<?php 
include_once 'includes/night-mode-system.php';
// Page moderne pour afficher les logs de réparations ET les logs de tâches
// Version optimisée pour grosse base de données et requêtes simultanées
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/cache_helper.php'; // Système de cache
    if (file_exists(__DIR__ . '/../includes/task_logger.php')) {
        require_once __DIR__ . '/../includes/task_logger.php';
    }
} catch (Exception $e) {
    // Ignorer les erreurs d'inclusion pour le mode sans auth
}

// ID du magasin pour le cache (sera défini après l'initialisation de la session)
$shop_id = 0;

// Paramètres de pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

// Filtres
$employe_id = isset($_GET['employe_id']) ? intval($_GET['employe_id']) : 0;
$log_type = isset($_GET['log_type']) ? $_GET['log_type'] : 'all'; // all, reparations, taches
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

try {
    // Démarrer la session si nécessaire
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Initialiser la session magasin si nécessaire
    if (!isset($_SESSION['shop_id'])) {
        initializeShopSession();
    }
    
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base du magasin. Vérifiez la configuration.');
    }
    
    // Maintenant récupérer l'ID du magasin pour le cache
    $shop_id = $_SESSION['shop_id'] ?? 0;
    
    // Construire la requête UNION pour combiner les logs de réparations et de tâches
    $where_conditions_rep = [];
    $where_conditions_task = [];
    $params = [];
    
    if ($employe_id > 0) {
        $where_conditions_rep[] = "rl.employe_id = ?";
        $where_conditions_task[] = "tl.employe_id = ?";
        $params[] = $employe_id;
        $params[] = $employe_id; // Pour la deuxième partie de l'UNION
    }
    
    if (!empty($action_type)) {
        $where_conditions_rep[] = "rl.action_type = ?";
        $where_conditions_task[] = "tl.action_type = ?";
        $params[] = $action_type;
        $params[] = $action_type;
    }
    
    if (!empty($date_debut)) {
        $where_conditions_rep[] = "DATE(rl.date_action) >= ?";
        $where_conditions_task[] = "DATE(tl.date_action) >= ?";
        $params[] = $date_debut;
        $params[] = $date_debut;
    }
    
    if (!empty($date_fin)) {
        $where_conditions_rep[] = "DATE(rl.date_action) <= ?";
        $where_conditions_task[] = "DATE(tl.date_action) <= ?";
        $params[] = $date_fin;
        $params[] = $date_fin;
    }
    
    $where_clause_rep = !empty($where_conditions_rep) ? 'WHERE ' . implode(' AND ', $where_conditions_rep) : '';
    $where_clause_task = !empty($where_conditions_task) ? 'WHERE ' . implode(' AND ', $where_conditions_task) : '';
    
    // Vérifier quelles tables existent
    $tables_exist = [
        'reparation_logs' => false,
        'task_logs' => false,
        'users' => false,
        'taches' => false,
        'time_tracking' => false
    ];
    
    try {
        $tables = $shop_pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            if (isset($tables_exist[$table])) {
                $tables_exist[$table] = true;
            }
        }
    } catch (Exception $table_error) {
        error_log("Erreur vérification tables: " . $table_error->getMessage());
    }
    
    // 🚀 OPTIMISATION: Construire des requêtes SQL optimisées avec index
    $sql_parts = [];
    
    if (($log_type === 'all' || $log_type === 'reparations') && $tables_exist['reparation_logs']) {
        $user_join = $tables_exist['users'] ? "LEFT JOIN users u ON rl.employe_id = u.id" : "";
        $user_field = $tables_exist['users'] ? "u.full_name" : "'Employé inconnu'";
        
        // FORCE INDEX pour garantir l'utilisation des index optimisés
        $force_index = "FORCE INDEX (idx_date_employe)";
        
        $sql_parts[] = "
            SELECT 
                rl.id,
                rl.date_action,
                rl.action_type,
                rl.statut_avant,
                rl.statut_apres,
                rl.details,
                $user_field as employe_nom,
                rl.employe_id,
                'reparation' as log_source,
                rl.reparation_id as reference_id,
                CONCAT('Réparation #', rl.reparation_id) as reference_title
            FROM reparation_logs rl $force_index
            $user_join
            $where_clause_rep
        ";
        
        // Ajouter les créations de réparations depuis la table reparations
        // uniquement si on filtre par 'creation' ou 'all'
        if (empty($action_type) || $action_type === 'creation') {
            $creation_conditions = [];
            $creation_params = [];
            
            if ($employe_id > 0) {
                $creation_conditions[] = "r.cree_par = ?";
                $creation_params[] = $employe_id;
            }
            
            if (!empty($date_debut)) {
                $creation_conditions[] = "DATE(r.date_reception) >= ?";
                $creation_params[] = $date_debut;
            }
            
            if (!empty($date_fin)) {
                $creation_conditions[] = "DATE(r.date_reception) <= ?";
                $creation_params[] = $date_fin;
            }
            
            $creation_where = !empty($creation_conditions) ? 'AND ' . implode(' AND ', $creation_conditions) : '';
            
            $user_join_creation = $tables_exist['users'] ? "LEFT JOIN users u ON r.cree_par = u.id" : "";
            $user_field_creation = $tables_exist['users'] ? "COALESCE(u.full_name, 'Utilisateur inconnu')" : "'Utilisateur inconnu'";
            
            $sql_parts[] = "
                SELECT 
                    r.id * 1000000 as id,
                    r.date_reception as date_action,
                    'creation' as action_type,
                    NULL as statut_avant,
                    r.statut as statut_apres,
                    CONCAT('Création de la réparation par ', $user_field_creation) as details,
                    $user_field_creation as employe_nom,
                    r.cree_par as employe_id,
                    'reparation' as log_source,
                    r.id as reference_id,
                    CONCAT('Réparation #', r.id) as reference_title
                FROM reparations r
                $user_join_creation
                WHERE r.cree_par IS NOT NULL
                $creation_where
            ";
            
            // Ajouter les paramètres de création à la fin
            foreach ($creation_params as $param) {
                $params[] = $param;
            }
        }
    }
    
    if (($log_type === 'all' || $log_type === 'taches') && $tables_exist['task_logs']) {
        $user_join = $tables_exist['users'] ? "LEFT JOIN users u ON tl.employe_id = u.id" : "";
        $user_field = $tables_exist['users'] ? "u.full_name" : "'Employé inconnu'";
        $tache_join = $tables_exist['taches'] ? "LEFT JOIN taches t ON tl.tache_id = t.id" : "";
        $tache_field = $tables_exist['taches'] ? "COALESCE(t.titre, CONCAT('Tâche #', tl.tache_id))" : "CONCAT('Tâche #', tl.tache_id)";
        
        // FORCE INDEX pour garantir l'utilisation des index optimisés
        $force_index = "FORCE INDEX (idx_date_employe)";
        
        $sql_parts[] = "
            SELECT 
                tl.id,
                tl.date_action,
                tl.action_type,
                tl.statut_avant,
                tl.statut_apres,
                tl.details,
                $user_field as employe_nom,
                tl.employe_id,
                'tache' as log_source,
                tl.tache_id as reference_id,
                $tache_field as reference_title
            FROM task_logs tl $force_index
            $user_join
            $tache_join
            $where_clause_task
        ";
    }
    
    if (empty($sql_parts)) {
        $logs = [];
        $total_logs = 0;
    } else {
        // 🚀 OPTIMISATION: Utiliser une sous-requête pour compter efficacement
        $sql_with_count = "
            SELECT SQL_CALC_FOUND_ROWS *
            FROM (
                " . implode(' UNION ALL ', $sql_parts) . "
            ) AS combined_logs
            ORDER BY date_action DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $shop_pdo->prepare($sql_with_count);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 🚀 OPTIMISATION: Récupérer le total avec FOUND_ROWS() - une seule requête!
        try {
            $total_logs = $shop_pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
        } catch (Exception $count_error) {
            error_log("Erreur FOUND_ROWS: " . $count_error->getMessage());
            $total_logs = count($logs); // Fallback
        }
        
        // Si aucun log trouvé et aucune table existe, créer des données d'exemple
        if (empty($logs) && !$tables_exist['reparation_logs'] && !$tables_exist['task_logs']) {
            $logs = [
                [
                    'id' => 1,
                    'date_action' => date('Y-m-d H:i:s', time() - 7200),
                    'action_type' => 'creation',
                    'statut_avant' => null,
                    'statut_apres' => 'nouvelle_intervention',
                    'details' => 'Réparation créée pour démonstration',
                    'employe_nom' => 'Jean Dupont',
                    'log_source' => 'reparation',
                    'reference_id' => 1,
                    'reference_title' => 'Réparation #1',
                    'employe_id' => 1
                ],
                [
                    'id' => 2,
                    'date_action' => date('Y-m-d H:i:s', time() - 3600),
                    'action_type' => 'demarrage',
                    'statut_avant' => 'nouvelle_intervention',
                    'statut_apres' => 'en_cours_intervention',
                    'details' => 'Début de l\'intervention',
                    'employe_nom' => 'Jean Dupont',
                    'log_source' => 'reparation',
                    'reference_id' => 1,
                    'reference_title' => 'Réparation #1',
                    'employe_id' => 1
                ],
                [
                    'id' => 3,
                    'date_action' => date('Y-m-d H:i:s', time() - 1800),
                    'action_type' => 'changement_statut',
                    'statut_avant' => 'en_cours_intervention',
                    'statut_apres' => 'reparation_effectue',
                    'details' => 'Réparation terminée avec succès',
                    'employe_nom' => 'Jean Dupont',
                    'log_source' => 'reparation',
                    'reference_id' => 1,
                    'reference_title' => 'Réparation #1',
                    'employe_id' => 1
                ],
                [
                    'id' => 4,
                    'date_action' => date('Y-m-d H:i:s', time() - 3600),
                    'action_type' => 'demarrage',
                    'statut_avant' => 'a_faire',
                    'statut_apres' => 'en_cours',
                    'details' => 'Début de la tâche',
                    'employe_nom' => 'Marie Martin',
                    'log_source' => 'tache',
                    'reference_id' => 1,
                    'reference_title' => 'Tâche #1',
                    'employe_id' => 2
                ],
                [
                    'id' => 5,
                    'date_action' => date('Y-m-d H:i:s', time() - 900),
                    'action_type' => 'terminer',
                    'statut_avant' => 'en_cours',
                    'statut_apres' => 'termine',
                    'details' => 'Tâche terminée',
                    'employe_nom' => 'Marie Martin',
                    'log_source' => 'tache',
                    'reference_id' => 1,
                    'reference_title' => 'Tâche #1',
                    'employe_id' => 2
                ]
            ];
            $total_logs = 5;
        }
        
        // Regrouper les logs par référence et employé
        $grouped_logs = [];
        foreach ($logs as $log) {
            $key = $log['log_source'] . '_' . $log['reference_id'] . '_employe_' . ($log['employe_id'] ?? 0);
            
            if (!isset($grouped_logs[$key])) {
                $grouped_logs[$key] = [
                    'reference_id' => $log['reference_id'],
                    'reference_title' => $log['reference_title'],
                    'log_source' => $log['log_source'],
                    'employe_id' => $log['employe_id'] ?? 0,
                    'employe_nom' => $log['employe_nom'],
                    'actions' => [],
                    'first_action' => $log['date_action'],
                    'last_action' => $log['date_action']
                ];
            }
            
            $grouped_logs[$key]['actions'][] = [
                'id' => $log['id'],
                'date_action' => $log['date_action'],
                'action_type' => $log['action_type'],
                'statut_avant' => $log['statut_avant'],
                'statut_apres' => $log['statut_apres'],
                'details' => $log['details']
            ];
            
            // Mettre à jour les dates de première et dernière action
            if ($log['date_action'] < $grouped_logs[$key]['first_action']) {
                $grouped_logs[$key]['first_action'] = $log['date_action'];
            }
            if ($log['date_action'] > $grouped_logs[$key]['last_action']) {
                $grouped_logs[$key]['last_action'] = $log['date_action'];
            }
        }
        
        // Trier les actions dans chaque groupe par date
        foreach ($grouped_logs as &$group) {
            usort($group['actions'], function($a, $b) {
                return strtotime($a['date_action']) - strtotime($b['date_action']);
            });
        }
        
        // Calculer les activités en cours par employé (dernière activité de chaque employé)
        $activities_by_employee = [];
        foreach ($logs as $log) {
            $employee_id = $log['employe_id'] ?? 0;
            $employee_name = $log['employe_nom'] ?? 'Employé inconnu';
            
            if (!isset($activities_by_employee[$employee_id]) || 
                strtotime($log['date_action']) > strtotime($activities_by_employee[$employee_id]['date_action'])) {
                $activities_by_employee[$employee_id] = [
                    'employe_id' => $employee_id,
                    'employe_nom' => $employee_name,
                    'date_action' => $log['date_action'],
                    'action_type' => $log['action_type'],
                    'statut_apres' => $log['statut_apres'],
                    'details' => $log['details'],
                    'reference_title' => $log['reference_title'],
                    'log_source' => $log['log_source'],
                    'reference_id' => $log['reference_id']
                ];
            }
        }
        
        // Filtrer les activités "en cours" (pas terminées)
        $ongoing_activities = array_filter($activities_by_employee, function($activity) {
            $ongoing_statuses = [
                'nouvelle_intervention', 'en_cours_intervention', 'attente_piece', 
                'diagnostic_en_cours', 'en_cours', 'a_faire', 'en_attente'
            ];
            return in_array($activity['statut_apres'], $ongoing_statuses);
        });
        
        $total_ongoing_activities = count($ongoing_activities);
        
        // Trier les groupes par dernière action (plus récent en premier)
        uasort($grouped_logs, function($a, $b) {
            return strtotime($b['last_action']) - strtotime($a['last_action']);
        });
    }
    
    $total_pages = ceil($total_logs / $limit);
    
    // Récupérer la liste des employés pour le filtre (avec gestion d'erreur)
    $users = [];
    try {
        $stmt_users = $shop_pdo->prepare("SELECT id, full_name FROM users ORDER BY full_name");
        $stmt_users->execute();
        $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $user_error) {
        error_log("Erreur lors de la récupération des utilisateurs: " . $user_error->getMessage());
        $users = [];
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des logs: " . $e->getMessage());
    $logs = [];
    $total_logs = 0;
    $total_pages = 0;
    $users = [];
}

function formatActionType($action_type) {
    switch($action_type) {
        case 'demarrage':
            return '🚀 Démarrage';
        case 'terminer':
            return '✅ Terminé';
        case 'changement_statut':
            return '🔄 Changement statut';
        case 'creation':
            return '➕ Création';
        case 'modification':
            return '✏️ Modification';
        case 'modification_prix':
            return '💰 Modification prix';
        case 'ajout_note':
            return '📝 Note ajoutée';
        case 'demo':
            return '🎯 Démonstration';
        case 'info':
            return 'ℹ️ Information';
        default:
            return '❓ ' . ucfirst($action_type);
    }
}

function formatStatut($statut) {
    switch($statut) {
        case 'a_faire':
            return '⏳ À faire';
        case 'en_cours':
            return '🔄 En cours';
        case 'termine':
            return '✅ Terminé';
        case 'nouvelle_intervention':
            return '🆕 Nouvelle intervention';
        case 'en_cours_intervention':
            return '🔧 En cours d\'intervention';
        case 'reparation_effectue':
            return '✅ Réparation effectuée';
        case 'en_attente_livraison':
            return '📦 En attente livraison';
        case 'restitue':
            return '✅ Restitué';
        default:
            return $statut;
    }
}

function getLogTypeIcon($log_source) {
    switch($log_source) {
        case 'reparation':
            return '🔧';
        case 'tache':
            return '📋';
        case 'demo':
            return '🎯';
        default:
            return '📝';
    }
}

// 🚀 OPTIMISATION: Calculer les statistiques avec SQL et cache
$cache_key = "log_stats_{$shop_id}_{$log_type}_{$employe_id}_{$action_type}";
$statistics = get_cache($shop_pdo, $cache_key, $shop_id);

if ($statistics === null) {
    // Calculer avec SQL au lieu de PHP
    $stats_sql_parts = [];
    
    if (($log_type === 'all' || $log_type === 'reparations') && $tables_exist['reparation_logs']) {
        $stats_sql_parts[] = "
            SELECT 
                'reparation' as log_source,
                COUNT(*) as total,
                SUM(CASE WHEN DATE(date_action) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN YEARWEEK(date_action, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) as this_week
            FROM reparation_logs rl
            $where_clause_rep
        ";
    }
    
    if (($log_type === 'all' || $log_type === 'taches') && $tables_exist['task_logs']) {
        $stats_sql_parts[] = "
            SELECT 
                'tache' as log_source,
                COUNT(*) as total,
                SUM(CASE WHEN DATE(date_action) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN YEARWEEK(date_action, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) as this_week
            FROM task_logs tl
            $where_clause_task
        ";
    }
    
    $statistics = [
        'total' => 0,
        'reparations' => 0,
        'taches' => 0,
        'today' => 0,
        'this_week' => 0
    ];
    
    if (!empty($stats_sql_parts)) {
        $stats_sql = implode(' UNION ALL ', $stats_sql_parts);
        $count_params = array_slice($params, 0, -2); // Enlever limit et offset
        
        try {
            $stats_stmt = $shop_pdo->prepare($stats_sql);
            $stats_stmt->execute($count_params);
            $stats_results = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($stats_results as $stat) {
                $statistics['total'] += $stat['total'];
                $statistics['today'] += $stat['today'];
                $statistics['this_week'] += $stat['this_week'];
                
                if ($stat['log_source'] === 'reparation') {
                    $statistics['reparations'] = $stat['total'];
                } else if ($stat['log_source'] === 'tache') {
                    $statistics['taches'] = $stat['total'];
                }
            }
        } catch (Exception $stats_error) {
            error_log("Erreur calcul statistiques: " . $stats_error->getMessage());
        }
    }
    
    // Mettre en cache pour 5 minutes
    set_cache($shop_pdo, $cache_key, $statistics, 300, $shop_id);
}

// Utiliser les statistiques
$total_grouped = $statistics['total'];
$grouped_reparations = ['count' => $statistics['reparations']];
$grouped_taches = ['count' => $statistics['taches']];
$grouped_today = ['count' => $statistics['today']];
$grouped_this_week = ['count' => $statistics['this_week']];

// 🚀 OPTIMISATION: Récupérer les données de pointage d'aujourd'hui avec cache
$pointage_cache_key = "pointage_today_{$shop_id}_" . date('Y-m-d');
$pointages_today = get_cache($shop_pdo, $pointage_cache_key, $shop_id);

if ($pointages_today === null && $tables_exist['time_tracking']) {
    try {
        // Récupérer les pointages d'aujourd'hui avec les informations utilisateur
        $pointage_sql = "
            SELECT 
                tt.id,
                tt.user_id,
                u.full_name as employe_nom,
                u.username,
                tt.clock_in,
                tt.clock_out,
                tt.break_start,
                tt.break_end,
                tt.status,
                tt.work_duration,
                tt.break_duration,
                tt.total_hours,
                tt.location,
                tt.notes,
                CASE 
                    WHEN tt.status = 'active' THEN 'En cours'
                    WHEN tt.status = 'break' THEN 'En pause'
                    WHEN tt.status = 'completed' THEN 'Terminé'
                    ELSE 'Inconnu'
                END as status_display,
                CASE 
                    WHEN tt.status = 'active' THEN TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW())
                    WHEN tt.status = 'break' AND tt.break_start IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, tt.break_start, NOW())
                    ELSE 0
                END as minutes_elapsed
            FROM time_tracking tt
            LEFT JOIN users u ON tt.user_id = u.id
            WHERE DATE(tt.clock_in) = CURDATE()
            ORDER BY tt.clock_in DESC
        ";
        
        $pointage_stmt = $shop_pdo->prepare($pointage_sql);
        $pointage_stmt->execute();
        $pointages_today = $pointage_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mettre en cache pour 2 minutes (données temps réel)
        set_cache($shop_pdo, $pointage_cache_key, $pointages_today, 120, $shop_id);
        
    } catch (Exception $pointage_error) {
        error_log("Erreur récupération pointages: " . $pointage_error->getMessage());
        $pointages_today = [];
    }
} else if (!$tables_exist['time_tracking']) {
    // Données d'exemple si la table n'existe pas
    $pointages_today = [
        [
            'id' => 1,
            'user_id' => 1,
            'employe_nom' => 'Jean Dupont',
            'username' => 'jean.dupont',
            'clock_in' => date('Y-m-d 08:30:00'),
            'clock_out' => null,
            'break_start' => null,
            'break_end' => null,
            'status' => 'active',
            'status_display' => 'En cours',
            'work_duration' => null,
            'break_duration' => 0,
            'total_hours' => null,
            'location' => 'Bureau principal',
            'notes' => null,
            'minutes_elapsed' => 180
        ],
        [
            'id' => 2,
            'user_id' => 2,
            'employe_nom' => 'Marie Martin',
            'username' => 'marie.martin',
            'clock_in' => date('Y-m-d 09:00:00'),
            'clock_out' => null,
            'break_start' => date('Y-m-d 10:30:00'),
            'break_end' => null,
            'status' => 'break',
            'status_display' => 'En pause',
            'work_duration' => null,
            'break_duration' => 0,
            'total_hours' => null,
            'location' => 'Bureau principal',
            'notes' => null,
            'minutes_elapsed' => 15
        ]
    ];
}
?>

<style>
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

/* Styles généraux navbar (mobile + desktop) */
#desktop-navbar, nav#desktop-navbar {
    display: block !important;
    visibility: visible !important;
    position: fixed !important;
    top: 0 !important;
    z-index: 10000 !important;
}

/* Masquer navbar sur mobile */
@media (max-width: 767px) {
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
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
body {
    margin: 0;
    padding: 0;
    padding-top: 80px; /* Espace pour la navbar fixe */
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

.modern-dashboard {
    position: relative;
    min-height: 100vh;
    padding: 1rem;
    transition: all 0.3s ease;
    margin-top: -80px; /* Remonter sous la navbar */
    padding-top: calc(80px + 1rem); /* Compenser avec padding */
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 300% 300%;
    animation: gradientFlow 20s ease infinite;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated);
    background-size: 400% 400%;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ========================================
   ANIMATIONS MODERNES
======================================== */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

/* ========================================
   EN-TÊTE MODERNE
======================================== */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
}

.modern-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--day-text);
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0;
}

.modern-title i {
    color: var(--day-primary);
    font-size: 2rem;
}

/* ========================================
   BOUTONS D'ACTION MODERNES
======================================== */
.modern-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    text-decoration: none;
    border-radius: 15px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    position: relative;
    overflow: hidden;
}

.modern-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.modern-btn:hover::before {
    left: 100%;
}

.modern-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
}

.modern-btn--success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.modern-btn--success:hover {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
}

.modern-btn--info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.modern-btn--info:hover {
    box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4);
}

.modern-btn--warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modern-btn--warning:hover {
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
}

/* ========================================
   STATISTIQUES MODERNES
======================================== */
.modern-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.modern-stat-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    animation: slideInUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary), var(--day-accent));
    background-size: 200% 100%;
    animation: gradientFlow 3s ease infinite;
}

.modern-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px var(--day-shadow);
}

/* ========================================
   CARTES STATISTIQUES CLIQUABLES
======================================== */
.stat-card-clickable {
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.stat-card-clickable:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 50px var(--day-shadow);
}

.stat-card-clickable:active {
    transform: translateY(-4px) scale(0.98);
}

.stat-card-clickable.active {
    border: 2px solid var(--day-primary);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.stat-card-clickable.active::before {
    background: var(--day-primary);
    height: 6px;
}

/* Indicateur de filtre actif */
.stat-card-clickable.active::after {
    content: '✓';
    position: absolute;
    top: 10px;
    right: 15px;
    background: var(--day-primary);
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b !important; /* Noir en mode jour - Priorité forte */
    margin: 0;
    line-height: 1;
}

.stat-label {
    color: var(--day-text-light);
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0.5rem 0 0;
}

/* ========================================
   CONTRÔLES MODERNES
======================================== */
.modern-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
}

.modern-search {
    position: relative;
    flex: 1;
    min-width: 300px;
}

.modern-search input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.modern-search input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: rgba(255, 255, 255, 1);
}

.modern-search i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--day-text-light);
    font-size: 1.1rem;
}

.modern-select {
    padding: 1rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    min-width: 150px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.modern-select:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* ========================================
   GRILLE DE LOGS MODERNE
======================================== */
.modern-logs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    animation: slideInUp 0.6s ease-out;
}

.modern-log-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.modern-log-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.modern-log-card.reparation::before {
    background: linear-gradient(90deg, #10b981, #059669);
}

.modern-log-card.tache::before {
    background: linear-gradient(90deg, #06b6d4, #0891b2);
}

.modern-log-card.demo::before {
    background: linear-gradient(90deg, #8b5cf6, #7c3aed);
}

.log-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.log-source-badge {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.log-source-badge.reparation {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.log-source-badge.tache {
    background: linear-gradient(135deg, #e0f2fe 0%, #b3e5fc 100%);
    color: #0c4a6e;
    border: 1px solid #7dd3fc;
}

.log-source-badge.demo {
    background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
    color: #581c87;
    border: 1px solid #c4b5fd;
}

.log-date {
    color: var(--day-text-light);
    font-size: 0.875rem;
    font-weight: 500;
}

.log-action-badge {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.log-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.log-employee {
    color: var(--day-primary);
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.log-status-flow {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: var(--day-text-light);
}

.log-details {
    background: rgba(59, 130, 246, 0.05);
    border-left: 3px solid var(--day-primary);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    color: var(--day-text);
    margin-top: 1rem;
}

/* ========================================
   PAGINATION MODERNE
======================================== */
.modern-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination-btn {
    padding: 0.75rem 1rem;
    border: 2px solid var(--day-border);
    border-radius: 12px;
    background: var(--day-card-bg);
    color: var(--day-text);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.pagination-btn:hover {
    border-color: var(--day-primary);
    background: var(--day-primary);
    color: white;
    transform: translateY(-2px);
}

.pagination-btn.active {
    background: var(--day-primary);
    border-color: var(--day-primary);
    color: white;
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .modern-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .modern-actions {
        width: 100%;
        justify-content: center;
    }
    
    .modern-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .modern-search {
        min-width: unset;
    }
    
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-logs-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-title {
        font-size: 2rem;
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
body.night-mode .modern-controls,
body.night-mode .modern-log-card {
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

body.night-mode .modern-search input,
body.night-mode .modern-select {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modern-search input:focus,
body.night-mode .modern-select:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

body.night-mode .modern-btn {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    color: var(--night-text);
}

/* Règle spécifique pour mode jour */
body:not(.night-mode) .stat-value {
    color: #1e293b !important; /* Noir en mode jour */
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

body.night-mode .log-details {
    background: rgba(0, 212, 255, 0.1);
    border-left-color: var(--night-primary);
}

body.night-mode .pagination-btn {
    background: var(--night-card-bg);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .pagination-btn:hover {
    background: var(--night-primary);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

/* ========================================
   TOAST NOTIFICATIONS
======================================== */
.modern-toast {
    position: fixed;
    top: 2rem;
    right: 2rem;
    background: white;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    border-left: 4px solid var(--day-primary);
    z-index: 100000;
    animation: slideInUp 0.3s ease;
    min-width: 300px;
}

.modern-toast--success {
    border-left-color: #10b981;
}

.modern-toast--error {
    border-left-color: #ef4444;
}

.modern-toast--warning {
    border-left-color: #f59e0b;
}

/* ========================================
   TIMELINE DES ACTIONS
======================================== */
.action-timeline {
    margin: 1.5rem 0;
    padding: 1rem 0;
    border-top: 1px solid var(--day-border);
    border-bottom: 1px solid var(--day-border);
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
    position: relative;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 17px;
    top: 35px;
    bottom: -16px;
    width: 2px;
    background: var(--day-border);
    z-index: 1;
}

.timeline-marker {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    position: relative;
    z-index: 2;
    flex-shrink: 0;
}

.timeline-item.creation .timeline-marker {
    background: linear-gradient(135deg, #10b981, #059669);
}

.timeline-item.demarrage .timeline-marker {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.timeline-item.terminer .timeline-marker {
    background: linear-gradient(135deg, #10b981, #059669);
}

.timeline-item.changement_statut .timeline-marker {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.timeline-content {
    flex: 1;
    min-width: 0;
}

.timeline-action {
    font-weight: 600;
    color: var(--day-text);
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.timeline-time {
    color: var(--day-text-light);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.timeline-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

.status-before {
    color: #6b7280;
    background: rgba(107, 114, 128, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
}

.status-after {
    color: var(--day-primary);
    background: rgba(59, 130, 246, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.timeline-status i {
    color: var(--day-text-light);
    font-size: 0.7rem;
}

.timeline-details {
    background: rgba(59, 130, 246, 0.05);
    border-left: 3px solid var(--day-primary);
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    color: var(--day-text);
    margin-top: 0.5rem;
}

/* ========================================
   RÉSUMÉ DES ACTIONS
======================================== */
.action-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--day-border);
}

.summary-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--day-primary);
    font-weight: 600;
    font-size: 0.9rem;
}

.summary-duration {
    color: var(--day-text-light);
    font-size: 0.9rem;
    font-weight: 500;
}

/* ========================================
   STYLES MODE NUIT POUR TIMELINE
======================================== */
body.night-mode .timeline-details {
    background: rgba(0, 212, 255, 0.1);
    border-left-color: var(--night-primary);
}

body.night-mode .status-before {
    background: rgba(160, 174, 192, 0.1);
    color: var(--night-text-light);
}

body.night-mode .status-after {
    background: rgba(0, 212, 255, 0.1);
    color: var(--night-primary);
}

/* ========================================
   AFFICHAGE SIMPLIFIÉ
======================================== */
.simple-activity-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 1.5rem 0;
    padding: 1rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 12px;
    border: 1px solid var(--day-border);
}

.activity-start, .activity-duration {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.activity-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--day-text-light);
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.activity-value {
    color: var(--day-text);
    font-size: 1.25rem;
    font-weight: 700;
}

.view-details-btn {
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1rem;
}

.view-details-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
}

.view-details-hint {
    text-align: center;
    padding: 0.75rem;
    color: var(--day-text-light);
    font-size: 0.875rem;
    font-style: italic;
    border-top: 1px solid var(--day-border);
    margin-top: 1rem;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.modern-log-card:hover .view-details-hint {
    opacity: 1;
    color: var(--day-primary);
}

.modern-log-card {
    cursor: pointer;
    user-select: none;
}

.modern-log-card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 15px 40px var(--day-shadow);
}

/* ========================================
   MODAL DÉTAILS
======================================== */
.activity-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
    z-index: 100000;
    overflow-y: auto;
}

.modal-content {
    position: relative;
    background: var(--day-card-bg);
    margin: 2rem auto;
    padding: 0;
    border-radius: 20px;
    max-width: 800px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--day-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--day-text-light);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.modal-body {
    padding: 2rem;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-employee {
    background: rgba(59, 130, 246, 0.1);
    padding: 0.75rem 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--day-primary);
    font-weight: 600;
}

/* Styles mode nuit pour modal */
body.night-mode .modal-content {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

body.night-mode .simple-activity-info {
    background: rgba(0, 212, 255, 0.1);
    border-color: var(--night-border);
}

body.night-mode .modal-employee {
    background: rgba(0, 212, 255, 0.1);
    color: var(--night-primary);
}
/* ========================================
   STYLES POUR LES CARTES DE POINTAGE
   ======================================== */

/* Styles pour les cartes de pointage */
.pointage-card {
    background: linear-gradient(135deg, var(--day-card-bg), var(--day-card-hover));
    border: 2px solid var(--day-accent);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 8px 32px var(--day-shadow);
    backdrop-filter: blur(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.pointage-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px var(--day-shadow);
    border-color: var(--day-primary);
}

.pointage-card.status-active {
    border-color: #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
}

.pointage-card.status-break {
    border-color: #f59e0b;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
}

.pointage-card.status-completed {
    border-color: #6b7280;
    background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(107, 114, 128, 0.05));
}

.pointage-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.pointage-employee {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--day-text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.pointage-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pointage-status.active {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.pointage-status.break {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.pointage-status.completed {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
}

.pointage-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.pointage-detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.pointage-detail-label {
    font-size: 0.8rem;
    color: var(--day-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.pointage-detail-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--day-text);
}

.pointage-time-info {
    background: rgba(59, 130, 246, 0.1);
    border-radius: 12px;
    padding: 12px;
    margin-top: 10px;
    border-left: 4px solid var(--day-primary);
}

.pointage-elapsed {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--day-primary);
    margin-bottom: 4px;
}

.pointage-location {
    font-size: 0.85rem;
    color: var(--day-text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
}

/* Mode nuit pour les cartes de pointage */
.night-mode .pointage-card {
    background: linear-gradient(135deg, var(--night-card-bg), var(--night-card-hover));
    border-color: var(--night-accent);
    box-shadow: 0 8px 32px var(--night-shadow);
}

.night-mode .pointage-card:hover {
    border-color: var(--night-primary);
    box-shadow: 0 12px 40px var(--night-shadow);
}

.night-mode .pointage-employee {
    color: var(--night-text);
}

.night-mode .pointage-detail-label {
    color: var(--night-text-muted);
}

.night-mode .pointage-detail-value {
    color: var(--night-text);
}

.night-mode .pointage-time-info {
    background: rgba(139, 92, 246, 0.15);
    border-left-color: var(--night-primary);
}

.night-mode .pointage-elapsed {
    color: var(--night-primary);
}

.night-mode .pointage-location {
    color: var(--night-text-muted);
}

/* Section des pointages */
.pointages-section {
    margin-top: 2rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    border: 1px solid var(--day-border);
}

.pointages-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.night-mode .pointages-section {
    background: rgba(0, 0, 0, 0.2);
    border-color: var(--night-border);
}

.night-mode .pointages-title {
    color: var(--night-text);
}

/* ========================================
   STYLES POUR LES CARTES UNIFIÉES
   ======================================== */

/* Cartes d'activité unifiées */
.unified-activity-card {
    background: linear-gradient(135deg, var(--day-card-bg), var(--day-card-hover));
    border: 1px solid var(--day-border);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 4px 20px var(--day-shadow);
    backdrop-filter: blur(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.unified-activity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px var(--day-shadow);
}

.unified-activity-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 80px;
}

.unified-activity-time {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.activity-icon {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.activity-time {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--day-primary);
    background: rgba(59, 130, 246, 0.1);
    padding: 4px 8px;
    border-radius: 8px;
}

.unified-activity-type {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--day-text-muted);
    font-weight: 500;
    margin-top: 5px;
}

.unified-activity-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.unified-activity-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 5px;
}

.unified-activity-description {
    font-size: 0.95rem;
    color: var(--day-text-muted);
    line-height: 1.4;
}

.unified-activity-employee {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: var(--day-text);
    font-weight: 500;
}

.unified-activity-location {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: var(--day-text-muted);
}

.unified-activity-reference {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: var(--day-primary);
    font-weight: 600;
    background: rgba(59, 130, 246, 0.1);
    padding: 4px 8px;
    border-radius: 6px;
    width: fit-content;
}

.unified-activity-timeline {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 20px;
}

.timeline-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.timeline-line {
    width: 2px;
    height: 40px;
    background: linear-gradient(to bottom, var(--day-border), transparent);
    margin-top: 8px;
}

/* Types spécifiques de cartes */
.unified-activity-card.pointage-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(59, 130, 246, 0.02));
}

.unified-activity-card.activity-card {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.02));
}

/* Mode nuit pour les cartes unifiées */
.night-mode .unified-activity-card {
    background: linear-gradient(135deg, var(--night-card-bg), var(--night-card-hover));
    border-color: var(--night-border);
    box-shadow: 0 4px 20px var(--night-shadow);
}

.night-mode .unified-activity-card:hover {
    box-shadow: 0 8px 30px var(--night-shadow);
}

.night-mode .activity-time {
    color: var(--night-primary);
    background: rgba(139, 92, 246, 0.15);
}

.night-mode .unified-activity-type {
    color: var(--night-text-muted);
}

.night-mode .unified-activity-title {
    color: var(--night-text);
}

.night-mode .unified-activity-description {
    color: var(--night-text-muted);
}

.night-mode .unified-activity-employee {
    color: var(--night-text);
}

.night-mode .unified-activity-location {
    color: var(--night-text-muted);
}

.night-mode .unified-activity-reference {
    color: var(--night-primary);
    background: rgba(139, 92, 246, 0.15);
}

.night-mode .timeline-line {
    background: linear-gradient(to bottom, var(--night-border), transparent);
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .unified-activity-card {
        padding: 15px;
        gap: 10px;
    }
    
    .unified-activity-header {
        min-width: 60px;
    }
    
    .activity-icon {
        font-size: 1.2rem;
    }
    
    .activity-time {
        font-size: 0.8rem;
        padding: 3px 6px;
    }
    
    .unified-activity-title {
        font-size: 1rem;
    }
    
    .unified-activity-description {
        font-size: 0.9rem;
    }
}

/* ========================================
   STYLES POUR LE FILTRE DE DATE
   ======================================== */

.date-filter-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
}

.period-select {
    min-width: 200px;
    background: linear-gradient(135deg, var(--day-card-bg), var(--day-card-hover));
    border: 2px solid var(--day-border);
    border-radius: 12px;
    padding: 10px 15px;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--day-text);
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.period-select:hover {
    border-color: var(--day-primary);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
}

.period-select:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.custom-date-range {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 12px;
    border: 1px solid var(--day-border);
    animation: slideDown 0.3s ease;
}

.modern-date-input {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.85rem;
    color: var(--day-text);
    cursor: pointer;
    transition: all 0.3s ease;
}

.modern-date-input:hover {
    border-color: var(--day-primary);
}

.modern-date-input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.date-separator {
    color: var(--day-primary);
    font-weight: 600;
    font-size: 1.1rem;
}

.modern-btn--sm {
    padding: 8px 12px;
    font-size: 0.85rem;
    min-width: auto;
}

/* Mode nuit pour le filtre de date */
.night-mode .period-select {
    background: linear-gradient(135deg, var(--night-card-bg), var(--night-card-hover));
    border-color: var(--night-border);
    color: var(--night-text);
}

.night-mode .period-select:hover {
    border-color: var(--night-primary);
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2);
}

.night-mode .period-select:focus {
    border-color: var(--night-primary);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.night-mode .custom-date-range {
    background: rgba(139, 92, 246, 0.1);
    border-color: var(--night-border);
}

.night-mode .modern-date-input {
    background: var(--night-card-bg);
    border-color: var(--night-border);
    color: var(--night-text);
}

.night-mode .modern-date-input:hover {
    border-color: var(--night-primary);
}

.night-mode .modern-date-input:focus {
    border-color: var(--night-primary);
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.1);
}

.night-mode .date-separator {
    color: var(--night-primary);
}

/* Animation pour l'apparition du sélecteur personnalisé */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive pour le filtre de date */
@media (max-width: 768px) {
    .date-filter-container {
        width: 100%;
    }
    
    .period-select {
        min-width: 100%;
        font-size: 0.85rem;
    }
    
    .custom-date-range {
        flex-direction: column;
        gap: 10px;
    }
    
    .modern-date-input {
        width: 100%;
    }
}

</style>

<!-- Particules d'arrière-plan -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-desktop"></i>
            Moniteur d'activité
        </h1>
        <div class="modern-actions">
            <!-- Filtre par période -->
            <div class="date-filter-container">
                <select id="periodFilter" class="modern-select period-select" onchange="handlePeriodChange()">
                    <option value="all">📅 Toute période</option>
                    <option value="today">🌅 Aujourd'hui</option>
                    <option value="yesterday">🌄 Hier</option>
                    <option value="this_week">📅 Cette semaine</option>
                    <option value="last_week">📆 Semaine dernière</option>
                    <option value="this_month">🗓️ Ce mois</option>
                    <option value="last_month">🗓️ Mois dernier</option>
                    <option value="custom">🎯 Période personnalisée</option>
                </select>
                
                <!-- Sélecteurs de date personnalisés (cachés par défaut) -->
                <div id="customDateRange" class="custom-date-range" style="display: none;">
                    <input type="date" id="startDate" class="modern-date-input" title="Date de début">
                    <span class="date-separator">→</span>
                    <input type="date" id="endDate" class="modern-date-input" title="Date de fin">
                    <button class="modern-btn modern-btn--sm" onclick="applyCustomDateFilter()">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>
            
            <button class="modern-btn modern-btn--info" onclick="showFilters()">
                <i class="fas fa-filter"></i>
                Filtrer
            </button>
            
            <button class="modern-btn modern-btn--success" onclick="exportLogs()">
                <i class="fas fa-download"></i>
                Exporter
            </button>
            <button class="modern-btn" onclick="refreshLogs()">
                <i class="fas fa-sync-alt"></i>
                Actualiser
            </button>
            <button class="modern-btn modern-btn--warning" onclick="resetAllFilters()">
                <i class="fas fa-filter"></i>
                Réinitialiser Filtres
            </button>
        </div>
    </div>

    <!-- Statistiques modernes -->
    <div class="modern-stats-grid fade-in">
        <div class="modern-stat-card stat-card-clickable" data-filter="ongoing" onclick="filterByStatCard('ongoing')" title="Cliquer pour voir les activités en cours par employé">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-users-cog"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_ongoing_activities; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Suivi des activités en cours</div>
        </div>
        
        <div class="modern-stat-card stat-card-clickable" data-filter="reparations" onclick="filterByStatCard('reparations')" title="Cliquer pour voir uniquement les réparations">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-wrench"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $grouped_reparations['count']; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Réparations</div>
        </div>
        
        <div class="modern-stat-card stat-card-clickable" data-filter="taches" onclick="filterByStatCard('taches')" title="Cliquer pour voir uniquement les tâches">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $grouped_taches['count']; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Tâches</div>
        </div>
        
        <div class="modern-stat-card stat-card-clickable" data-filter="today" onclick="filterByStatCard('today')" title="Cliquer pour voir les activités d'aujourd'hui">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-calendar-day"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $grouped_today['count']; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Aujourd'hui</div>
        </div>
        
        <div class="modern-stat-card stat-card-clickable" data-filter="week" onclick="filterByStatCard('week')" title="Cliquer pour voir les activités de cette semaine">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-calendar-week"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $grouped_this_week['count']; ?></div>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="stat-label">Cette Semaine</div>
        </div>
    </div>

    <!-- Contrôles modernes -->
    <div class="modern-controls fade-in">
        <div class="modern-search">
            <i class="fas fa-search"></i>
            <input id="logSearch" placeholder="Rechercher dans les logs..." />
        </div>
        <select id="logTypeFilter" class="modern-select">
            <option value="all" <?php echo ($log_type == 'all') ? 'selected' : ''; ?>>Tous les logs</option>
<?php include_once 'includes/night-mode-system.php'; ?>
            <option value="reparations" <?php echo ($log_type == 'reparations') ? 'selected' : ''; ?>>🔧 Réparations</option>
<?php include_once 'includes/night-mode-system.php'; ?>
            <option value="taches" <?php echo ($log_type == 'taches') ? 'selected' : ''; ?>>📋 Tâches</option>
<?php include_once 'includes/night-mode-system.php'; ?>
        </select>
        <select id="employeeFilter" class="modern-select">
            <option value="0">Tous les employés</option>
            <?php foreach ($users as $user): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                <option value="<?php echo $user['id']; ?>" <?php echo ($employe_id == $user['id']) ? 'selected' : ''; ?>>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <?php echo htmlspecialchars($user['full_name']); ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                </option>
            <?php endforeach; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
        </select>
        <select id="actionFilter" class="modern-select">
            <option value="">Toutes les actions</option>
            <option value="demarrage" <?php echo ($action_type == 'demarrage') ? 'selected' : ''; ?>>Démarrage</option>
<?php include_once 'includes/night-mode-system.php'; ?>
            <option value="terminer" <?php echo ($action_type == 'terminer') ? 'selected' : ''; ?>>Terminé</option>
<?php include_once 'includes/night-mode-system.php'; ?>
            <option value="changement_statut" <?php echo ($action_type == 'changement_statut') ? 'selected' : ''; ?>>Changement statut</option>
<?php include_once 'includes/night-mode-system.php'; ?>
            <option value="creation" <?php echo ($action_type == 'creation') ? 'selected' : ''; ?>>➕ Création</option>
<?php include_once 'includes/night-mode-system.php'; ?>
            <option value="modification" <?php echo ($action_type == 'modification') ? 'selected' : ''; ?>>✏️ Modification</option>
            <option value="modification_prix" <?php echo ($action_type === 'modification_prix') ? 'selected' : ''; ?>>💰 Modification prix</option>
            <option value="ajout_note" <?php echo ($action_type === 'ajout_note') ? 'selected' : ''; ?>>📝 Note ajoutée</option>
<?php include_once 'includes/night-mode-system.php'; ?>
        </select>
    </div>

    <!-- Grille de logs moderne groupés -->
    <?php if (empty($grouped_logs)): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
        <div class="modern-header fade-in" style="text-align: center;">
            <div style="color: var(--day-text-light);">
                <i class="fas fa-info-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <div style="font-size: 1.1rem; font-weight: 600;">Aucune activité trouvée</div>
                <div style="margin-top: 0.5rem;">Modifiez vos critères de recherche pour voir plus de résultats</div>
            </div>
        </div>
    <?php else: ?>
        <div class="modern-logs-grid">
            <?php foreach ($grouped_logs as $group): ?>
                <?php
                $search_text = strtolower(implode(' ', [
                    $group['reference_title'], 
                    $group['employe_nom'], 
                    implode(' ', array_column($group['actions'], 'details')),
                    implode(' ', array_column($group['actions'], 'action_type'))
                ]));
                ?>
                <div class="modern-log-card <?php echo $group['log_source']; ?>" 
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-search-text="<?php echo $search_text; ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-log-source="<?php echo $group['log_source']; ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-log-date="<?php echo date('Y-m-d', strtotime($group['first_action'])); ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-log-time="<?php echo date('H:i', strtotime($group['first_action'])); ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-log-employee="<?php echo htmlspecialchars($group['employe_nom'] ?? 'Employé inconnu'); ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-log-action="<?php echo htmlspecialchars($group['action_type'] ?? ''); ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-log-details="<?php echo htmlspecialchars($group['details'] ?? ''); ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-reference-id="<?php echo $group['reference_id'] ?? ''; ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     data-reference-title="<?php echo htmlspecialchars($group['reference_title'] ?? ''); ?>"
<?php include_once 'includes/night-mode-system.php'; ?>
                     onclick="showActivityDetails(<?php echo htmlspecialchars(json_encode($group), ENT_QUOTES); ?>)"
<?php include_once 'includes/night-mode-system.php'; ?>
                     style="cursor: pointer;">
                    
                    <div class="log-header">
                        <span class="log-source-badge <?php echo $group['log_source']; ?>">
<?php include_once 'includes/night-mode-system.php'; ?>
                            <?php echo getLogTypeIcon($group['log_source']); ?> 
<?php include_once 'includes/night-mode-system.php'; ?>
                            <?php echo ucfirst($group['log_source']); ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        </span>
                        <div class="log-date">
                            <?php echo 'Du ' . date('d/m H:i', strtotime($group['first_action'])) . ' au ' . date('d/m H:i', strtotime($group['last_action'])); ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        </div>
                    </div>
                    
                    <h3 class="log-title">
                        <i class="fas fa-<?php echo $group['log_source'] === 'reparation' ? 'wrench' : 'tasks'; ?>"></i>
                        <?php echo htmlspecialchars($group['reference_title']); ?>
                    </h3>
                    
                    <div class="log-employee">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($group['employe_nom'] ?: 'Employé inconnu'); ?>
                    </div>
                    
                    <!-- Affichage simplifié -->
                    <div class="simple-activity-info">
                        <?php
                        // Trouver l'heure de démarrage et calculer la durée jusqu'au premier changement de statut
                        $start_time = null;
                        $first_status_change = null;
                        
                        foreach ($group['actions'] as $action) {
                            if ($action['action_type'] === 'demarrage' && !$start_time) {
                                $start_time = $action['date_action'];
                            }
                            if ($start_time && in_array($action['action_type'], ['changement_statut', 'terminer']) && !$first_status_change) {
                                $first_status_change = $action['date_action'];
                                break;
                            }
                        }
                        
                        // Si pas de démarrage trouvé, prendre la première action
                        if (!$start_time && !empty($group['actions'])) {
                            $start_time = $group['first_action'];
                        }
                        
                        // Calculer la durée
                        $duration_text = 'En cours';
                        if ($start_time && $first_status_change) {
                            $start = strtotime($start_time);
                            $end = strtotime($first_status_change);
                            $duration = $end - $start;
                            if ($duration > 0) {
                                $minutes = floor($duration / 60);
                                if ($minutes >= 60) {
                                    $hours = floor($minutes / 60);
                                    $remaining_minutes = $minutes % 60;
                                    $duration_text = $hours . 'h' . ($remaining_minutes > 0 ? ' ' . $remaining_minutes . 'min' : '');
                                } else {
                                    $duration_text = $minutes . ' min';
                                }
                            } else {
                                $duration_text = 'Instantané';
                            }
                        }
                        ?>
                        
                        <div class="activity-start">
                            <div class="activity-label">
                                <i class="fas fa-play"></i>
                                Démarrage
                            </div>
                            <div class="activity-value">
                                <?php echo $start_time ? date('H:i', strtotime($start_time)) : 'N/A'; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                            </div>
                        </div>
                        
                        <div class="activity-duration">
                            <div class="activity-label">
                                <i class="fas fa-clock"></i>
                                Durée
                            </div>
                            <div class="activity-value">
                                <?php echo $duration_text; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Indication cliquable -->
                    <div class="view-details-hint">
                        <i class="fas fa-mouse-pointer"></i>
                        Cliquez pour voir la timeline complète
                    </div>
                </div>
            <?php endforeach; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
        </div>

        <!-- Pagination moderne -->
        <?php if ($total_pages > 1): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
            <div class="modern-pagination">
                <?php if ($page > 1): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page - 1])); ?>" class="pagination-btn">
<?php include_once 'includes/night-mode-system.php'; ?>
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>" 
<?php include_once 'includes/night-mode-system.php'; ?>
                       class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
<?php include_once 'includes/night-mode-system.php'; ?>
                        <?php echo $i; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    </a>
                <?php endfor; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                
                <?php if ($page < $total_pages): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page + 1])); ?>" class="pagination-btn">
<?php include_once 'includes/night-mode-system.php'; ?>
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
            </div>
        <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
    <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
</div>

<!-- Modal pour les détails d'activité -->
<div id="activityModal" class="activity-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-clock"></i>
                Détails de l'Activité
            </h2>
            <button class="modal-close" onclick="closeActivityModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="modalContent">
                <!-- Le contenu sera injecté par JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
// Détection IMMÉDIATE du mode nuit (avant DOMContentLoaded)
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
        // Mode nuit activé
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
        // Mode jour activé
    }
})();

// Variables globales
let currentFilters = {
    search: '',
    logType: '',
    employee: '',
    action: '',
    statCard: 'all'  // Nouveau filtre pour les cartes de statistiques
};

// Variable pour suivre la carte active
let activeStatCard = 'all';

// Recherche en temps réel
document.getElementById('logSearch').addEventListener('input', function() {
    currentFilters.search = this.value.toLowerCase();
    filterLogs();
});

// Filtres
document.getElementById('logTypeFilter').addEventListener('change', function() {
    currentFilters.logType = this.value;
    applyFilters();
});

document.getElementById('employeeFilter').addEventListener('change', function() {
    currentFilters.employee = this.value;
    applyFilters();
});

document.getElementById('actionFilter').addEventListener('change', function() {
    currentFilters.action = this.value;
    applyFilters();
});

// Fonction de filtrage par carte de statistiques
function filterByStatCard(filterType) {
    // Filtrage par carte activé
    
    // Mettre à jour le filtre actuel
    currentFilters.statCard = filterType;
    activeStatCard = filterType;
    
    // Mettre à jour l'apparence des cartes
    document.querySelectorAll('.stat-card-clickable').forEach(card => {
        card.classList.remove('active');
    });
    
    // Activer la carte sélectionnée
    const activeCard = document.querySelector(`[data-filter="${filterType}"]`);
    if (activeCard) {
        activeCard.classList.add('active');
    }
    
    // Si c'est le filtre "ongoing", afficher les activités en cours par employé
    if (filterType === 'ongoing') {
        showOngoingActivitiesByEmployee();
    } else if (filterType === 'today') {
        showTodayActivitiesWithPointages();
    } else {
        // Sinon, appliquer le filtrage normal
        filterLogs();
    }
    
    // Afficher un message de confirmation
    const filterNames = {
        'all': 'Toutes les activités',
        'ongoing': 'Activités en cours par employé',
        'reparations': 'Réparations uniquement',
        'taches': 'Tâches uniquement',
        'today': 'Flux chronologique d\'aujourd\'hui',
        'week': 'Activités de cette semaine'
    };
    
    showToast(`📊 Filtre appliqué: ${filterNames[filterType]}`, 'info');
}

// Fonction pour afficher le flux chronologique unifié d'aujourd'hui
function showTodayActivitiesWithPointages() {
    // Masquer toutes les cartes existantes
    const logCards = document.querySelectorAll('.modern-log-card');
    logCards.forEach(card => {
        card.style.display = 'none';
    });
    
    // Supprimer les cartes précédentes
    const existingCards = document.querySelectorAll('.ongoing-activity-card, .pointage-card, .unified-activity-card');
    existingCards.forEach(card => card.remove());
    
    // Récupérer le container
    const container = document.querySelector('.modern-logs-grid');
    
    if (!container) {
        console.error('❌ Container .modern-logs-grid non trouvé');
        showToast('❌ Erreur: Container non trouvé', 'error');
        return;
    }
    
    // Récupérer les données PHP
    const pointagesToday = <?php echo json_encode($pointages_today); ?>;
<?php include_once 'includes/night-mode-system.php'; ?>
    
    // Créer un flux unifié d'activités
    const unifiedActivities = [];
    
    // 1. Ajouter les pointages comme activités
    pointagesToday.forEach(pointage => {
        // Pointage d'arrivée
        if (pointage.clock_in) {
            unifiedActivities.push({
                type: 'pointage',
                subtype: 'arrival',
                datetime: pointage.clock_in,
                employee: pointage.employe_nom,
                status: pointage.status,
                location: pointage.location,
                icon: '🚪',
                title: 'Arrivée au travail',
                description: `${pointage.employe_nom} est arrivé(e)`,
                time: formatTime(pointage.clock_in)
            });
        }
        
        // Pointage de pause (début)
        if (pointage.break_start) {
            unifiedActivities.push({
                type: 'pointage',
                subtype: 'break_start',
                datetime: pointage.break_start,
                employee: pointage.employe_nom,
                status: 'break',
                location: pointage.location,
                icon: '☕',
                title: 'Début de pause',
                description: `${pointage.employe_nom} a commencé sa pause`,
                time: formatTime(pointage.break_start)
            });
        }
        
        // Pointage de pause (fin)
        if (pointage.break_end) {
            unifiedActivities.push({
                type: 'pointage',
                subtype: 'break_end',
                datetime: pointage.break_end,
                employee: pointage.employe_nom,
                status: 'active',
                location: pointage.location,
                icon: '💼',
                title: 'Fin de pause',
                description: `${pointage.employe_nom} a repris le travail`,
                time: formatTime(pointage.break_end)
            });
        }
        
        // Pointage de sortie
        if (pointage.clock_out) {
            unifiedActivities.push({
                type: 'pointage',
                subtype: 'departure',
                datetime: pointage.clock_out,
                employee: pointage.employe_nom,
                status: 'completed',
                location: pointage.location,
                icon: '🏠',
                title: 'Sortie du travail',
                description: `${pointage.employe_nom} a terminé sa journée`,
                time: formatTime(pointage.clock_out)
            });
        }
    });
    
    // 2. Ajouter les activités normales d'aujourd'hui
    const normalLogCards = document.querySelectorAll('.modern-log-card:not(.pointage-card):not(.ongoing-activity-card)');
    const today = new Date().toISOString().split('T')[0];
    
    normalLogCards.forEach(card => {
        const logDate = card.dataset.logDate || '';
        if (logDate.startsWith(today)) {
            const logTime = card.dataset.logTime || '';
            const logEmployee = card.dataset.logEmployee || '';
            const logAction = card.dataset.logAction || '';
            const logSource = card.dataset.logSource || '';
            const logDetails = card.dataset.logDetails || '';
            const referenceId = card.dataset.referenceId || '';
            const referenceTitle = card.dataset.referenceTitle || '';
            
            let icon = '📋';
            let title = logAction;
            let description = logDetails || `${logEmployee} - ${logAction}`;
            
            // Icônes et titres spécifiques selon le type d'activité
            if (logSource === 'reparation') {
                icon = '🔧';
                title = `Réparation #${referenceId} - ${logAction}`;
                description = `${referenceTitle || 'Réparation'} - ${logDetails || logAction}`;
            } else if (logSource === 'tache') {
                icon = '✅';
                title = `Tâche #${referenceId} - ${logAction}`;
                description = `${referenceTitle || 'Tâche'} - ${logDetails || logAction}`;
            }
            
            // S'assurer que l'heure est bien formatée
            const formattedTime = logTime || 'N/A';
            const fullDatetime = logTime ? `${logDate} ${logTime}:00` : `${logDate} 00:00:00`;
            
            unifiedActivities.push({
                type: 'activity',
                subtype: logSource,
                datetime: fullDatetime,
                employee: logEmployee,
                status: 'activity',
                icon: icon,
                title: title,
                description: description,
                time: formattedTime,
                source: logSource,
                referenceId: referenceId,
                referenceTitle: referenceTitle
            });
        }
    });
    
    // 3. Trier par ordre chronologique
    unifiedActivities.sort((a, b) => new Date(a.datetime) - new Date(b.datetime));
    
    // 4. Pas de titre - directement les cartes
    
    // 5. Créer les cartes unifiées
    if (unifiedActivities.length === 0) {
        const noActivityCard = document.createElement('div');
        noActivityCard.className = 'unified-activity-card';
        noActivityCard.style.cssText = `
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: var(--day-card-bg);
            border: 2px dashed var(--day-border);
            border-radius: 16px;
        `;
        noActivityCard.innerHTML = `
            <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
            <h3 style="color: var(--day-text); margin-bottom: 0.5rem;">Aucune activité aujourd'hui</h3>
            <p style="color: var(--day-text-muted);">Les pointages et activités d'aujourd'hui apparaîtront ici</p>
        `;
        container.appendChild(noActivityCard);
        return;
    }
    
    unifiedActivities.forEach((activity, index) => {
        const card = document.createElement('div');
        card.className = `unified-activity-card ${activity.type}-card status-${activity.status}`;
        
        // Couleur de bordure selon le type
        let borderColor = 'var(--day-accent)';
        if (activity.type === 'pointage') {
            if (activity.subtype === 'arrival') borderColor = '#10b981';
            else if (activity.subtype === 'break_start') borderColor = '#f59e0b';
            else if (activity.subtype === 'break_end') borderColor = '#3b82f6';
            else if (activity.subtype === 'departure') borderColor = '#6b7280';
        } else if (activity.source === 'reparation') {
            borderColor = '#ef4444';
        } else if (activity.source === 'tache') {
            borderColor = '#8b5cf6';
        }
        
        card.style.borderLeftColor = borderColor;
        card.style.borderLeftWidth = '4px';
        card.style.borderLeftStyle = 'solid';
        
        card.innerHTML = `
            <div class="unified-activity-header">
                <div class="unified-activity-time">
                    <span class="activity-icon">${activity.icon}</span>
                    <span class="activity-time">${activity.time}</span>
                </div>
                <div class="unified-activity-type">
                    ${activity.type === 'pointage' ? 'Pointage' : 'Activité'}
                </div>
            </div>
            
            <div class="unified-activity-content">
                <div class="unified-activity-title">${activity.title}</div>
                <div class="unified-activity-description">${activity.description}</div>
                ${activity.employee ? `
                    <div class="unified-activity-employee">
                        <i class="fas fa-user"></i>
                        ${activity.employee}
                    </div>
                ` : ''}
                ${activity.referenceId && activity.type === 'activity' ? `
                    <div class="unified-activity-reference">
                        <i class="fas fa-hashtag"></i>
                        ${activity.source === 'reparation' ? 'Réparation' : 'Tâche'} #${activity.referenceId}
                    </div>
                ` : ''}
                ${activity.location ? `
                    <div class="unified-activity-location">
                        <i class="fas fa-map-marker-alt"></i>
                        ${activity.location}
                    </div>
                ` : ''}
            </div>
            
            <div class="unified-activity-timeline">
                <div class="timeline-dot" style="background-color: ${borderColor};"></div>
                ${index < unifiedActivities.length - 1 ? '<div class="timeline-line"></div>' : ''}
            </div>
        `;
        
        container.appendChild(card);
    });
}

// Fonction pour afficher les activités en cours par employé
function showOngoingActivitiesByEmployee() {
    // Affichage des activités en cours par employé
    
    // Masquer toutes les cartes existantes
    const logCards = document.querySelectorAll('.modern-log-card');
    logCards.forEach(card => {
        card.style.display = 'none';
    });
    
    // Créer et afficher les cartes d'activités en cours par employé
    const grid = document.querySelector('.modern-logs-grid');
    if (!grid) return;
    
    // Supprimer les cartes d'activités en cours existantes
    const existingOngoingCards = document.querySelectorAll('.ongoing-activity-card');
    existingOngoingCards.forEach(card => card.remove());
    
    // Récupérer les données PHP des activités en cours
    const ongoingActivities = <?php echo json_encode(array_values($ongoing_activities)); ?>;
<?php include_once 'includes/night-mode-system.php'; ?>
    
    if (ongoingActivities.length === 0) {
        // Afficher un message si aucune activité en cours
        const noActivityCard = document.createElement('div');
        noActivityCard.className = 'ongoing-activity-card';
        noActivityCard.style.cssText = `
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: var(--day-card-bg);
            border-radius: 20px;
            border: 1px solid var(--day-border);
            color: var(--day-text-light);
        `;
        noActivityCard.innerHTML = `
            <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; color: #10b981;"></i>
            <div style="font-size: 1.1rem; font-weight: 600;">Aucune activité en cours</div>
            <div style="margin-top: 0.5rem;">Tous les employés ont terminé leurs tâches actuelles</div>
        `;
        grid.appendChild(noActivityCard);
        return;
    }
    
    // Créer une carte pour chaque employé avec activité en cours
    ongoingActivities.forEach(activity => {
        const card = document.createElement('div');
        card.className = 'ongoing-activity-card modern-log-card';
        card.style.cssText = `
            background: var(--day-card-bg);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--day-border);
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px var(--day-shadow);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        `;
        
        // Couleur de bordure selon le type d'activité
        const borderColor = activity.log_source === 'reparation' ? '#10b981' : '#06b6d4';
        card.style.borderLeft = `4px solid ${borderColor}`;
        
        const timeAgo = getTimeAgo(activity.date_action);
        const statusIcon = getStatusIcon(activity.statut_apres);
        const statusText = getStatusText(activity.statut_apres);
        
        card.innerHTML = `
            <div class="log-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <span class="log-source-badge ${activity.log_source}" style="
                    background: ${activity.log_source === 'reparation' ? '#10b981' : '#06b6d4'};
                    color: white;
                    padding: 0.25rem 0.75rem;
                    border-radius: 15px;
                    font-size: 0.875rem;
                    font-weight: 600;
                ">
                    <i class="fas fa-${activity.log_source === 'reparation' ? 'wrench' : 'tasks'}"></i>
                    ${activity.log_source === 'reparation' ? 'Réparation' : 'Tâche'}
                </span>
                <div class="log-date" style="color: var(--day-text-light); font-size: 0.875rem;">
                    ${timeAgo}
                </div>
            </div>
            
            <h3 class="log-title" style="
                color: var(--day-text);
                font-size: 1.1rem;
                font-weight: 600;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            ">
                <i class="fas fa-user" style="color: var(--day-primary);"></i>
                ${activity.employe_nom}
            </h3>
            
            <div class="activity-details" style="margin-bottom: 1rem;">
                <div style="
                    background: rgba(59, 130, 246, 0.1);
                    padding: 1rem;
                    border-radius: 10px;
                    margin-bottom: 0.75rem;
                ">
                    <div style="font-weight: 600; color: var(--day-text); margin-bottom: 0.5rem;">
                        <i class="fas fa-${activity.log_source === 'reparation' ? 'wrench' : 'tasks'}" style="margin-right: 0.5rem;"></i>
                        ${activity.reference_title}
                    </div>
                    <div style="color: var(--day-text-light); font-size: 0.875rem;">
                        ${activity.details}
                    </div>
                </div>
                
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 0.75rem;
                    background: ${activity.log_source === 'reparation' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(6, 182, 212, 0.1)'};
                    border-radius: 10px;
                ">
                    <div style="
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        background: ${borderColor};
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-size: 0.875rem;
                    ">
                        ${statusIcon}
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--day-text); font-size: 0.875rem;">
                            Statut actuel
                        </div>
                        <div style="color: ${borderColor}; font-weight: 500; font-size: 0.875rem;">
                            ${statusText}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter un effet hover
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 15px 40px var(--day-shadow)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 8px 32px var(--day-shadow)';
        });
        
        grid.appendChild(card);
    });
}

// Fonctions utilitaires pour l'affichage des activités en cours
function getTimeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'À l\'instant';
    if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`;
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `Il y a ${diffInHours}h`;
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) return `Il y a ${diffInDays} jour${diffInDays > 1 ? 's' : ''}`;
    
    return date.toLocaleDateString('fr-FR');
}

function getStatusIcon(status) {
    const icons = {
        'nouvelle_intervention': '🆕',
        'en_cours_intervention': '🔧',
        'attente_piece': '⏳',
        'diagnostic_en_cours': '🔍',
        'en_cours': '▶️',
        'a_faire': '📋',
        'en_attente': '⏸️'
    };
    return icons[status] || '📌';
}

function getStatusText(status) {
    const texts = {
        'nouvelle_intervention': 'Nouvelle intervention',
        'en_cours_intervention': 'En cours d\'intervention',
        'attente_piece': 'Attente pièce',
        'diagnostic_en_cours': 'Diagnostic en cours',
        'en_cours': 'En cours',
        'a_faire': 'À faire',
        'en_attente': 'En attente'
    };
    return texts[status] || status.replace('_', ' ');
}

// Fonction pour formater le temps (HH:MM)
function formatTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleTimeString('fr-FR', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

// Fonction pour formater le temps écoulé
function formatTimeElapsed(minutes) {
    if (!minutes || minutes <= 0) return '0 min';
    
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    
    if (hours === 0) {
        return `${remainingMinutes} min`;
    } else if (remainingMinutes === 0) {
        return `${hours}h`;
    } else {
        return `${hours}h ${remainingMinutes}min`;
    }
}

// Variables globales pour le filtre de date
let currentDateFilter = {
    type: 'all',
    startDate: null,
    endDate: null
};

// Fonction pour gérer le changement de période
function handlePeriodChange() {
    const periodSelect = document.getElementById('periodFilter');
    const customDateRange = document.getElementById('customDateRange');
    const selectedPeriod = periodSelect.value;
    
    // Afficher/masquer le sélecteur de date personnalisé
    if (selectedPeriod === 'custom') {
        customDateRange.style.display = 'flex';
        // Définir les dates par défaut
        const today = new Date();
        const lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
        
        document.getElementById('startDate').value = lastWeek.toISOString().split('T')[0];
        document.getElementById('endDate').value = today.toISOString().split('T')[0];
    } else {
        customDateRange.style.display = 'none';
        // Appliquer le filtre prédéfini
        applyPredefinedDateFilter(selectedPeriod);
    }
}

// Fonction pour appliquer un filtre de date prédéfini
function applyPredefinedDateFilter(period) {
    const today = new Date();
    let startDate, endDate;
    
    switch (period) {
        case 'all':
            currentDateFilter = { type: 'all', startDate: null, endDate: null };
            break;
            
        case 'today':
            startDate = new Date(today);
            endDate = new Date(today);
            break;
            
        case 'yesterday':
            startDate = new Date(today.getTime() - 24 * 60 * 60 * 1000);
            endDate = new Date(today.getTime() - 24 * 60 * 60 * 1000);
            break;
            
        case 'this_week':
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay() + 1); // Lundi
            startDate = startOfWeek;
            endDate = new Date(today);
            break;
            
        case 'last_week':
            const startOfLastWeek = new Date(today);
            startOfLastWeek.setDate(today.getDate() - today.getDay() - 6); // Lundi de la semaine dernière
            const endOfLastWeek = new Date(today);
            endOfLastWeek.setDate(today.getDate() - today.getDay()); // Dimanche de la semaine dernière
            startDate = startOfLastWeek;
            endDate = endOfLastWeek;
            break;
            
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today);
            break;
            
        case 'last_month':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0); // Dernier jour du mois précédent
            break;
    }
    
    if (period !== 'all') {
        currentDateFilter = {
            type: 'range',
            startDate: startDate.toISOString().split('T')[0],
            endDate: endDate.toISOString().split('T')[0]
        };
    }
    
    // Appliquer le filtre
    filterLogs();
    
    // Afficher un message de confirmation
    const periodNames = {
        'all': 'Toute période',
        'today': 'Aujourd\'hui',
        'yesterday': 'Hier',
        'this_week': 'Cette semaine',
        'last_week': 'Semaine dernière',
        'this_month': 'Ce mois',
        'last_month': 'Mois dernier'
    };
    
    showToast(`📅 Filtre appliqué: ${periodNames[period]}`, 'info');
}

// Fonction pour appliquer un filtre de date personnalisé
function applyCustomDateFilter() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        showToast('❌ Veuillez sélectionner une date de début et de fin', 'error');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        showToast('❌ La date de début doit être antérieure à la date de fin', 'error');
        return;
    }
    
    currentDateFilter = {
        type: 'range',
        startDate: startDate,
        endDate: endDate
    };
    
    // Appliquer le filtre
    filterLogs();
    
    // Afficher un message de confirmation
    const startFormatted = new Date(startDate).toLocaleDateString('fr-FR');
    const endFormatted = new Date(endDate).toLocaleDateString('fr-FR');
    showToast(`📅 Filtre appliqué: ${startFormatted} → ${endFormatted}`, 'info');
}

// Fonction pour vérifier si une date est dans la plage sélectionnée
function isDateInRange(dateString) {
    if (currentDateFilter.type === 'all') {
        return true;
    }
    
    if (currentDateFilter.type === 'range') {
        const date = new Date(dateString);
        const start = new Date(currentDateFilter.startDate);
        const end = new Date(currentDateFilter.endDate);
        
        // Ajuster les heures pour inclure toute la journée
        start.setHours(0, 0, 0, 0);
        end.setHours(23, 59, 59, 999);
        
        return date >= start && date <= end;
    }
    
    return true;
}

// Fonction de filtrage des logs groupés
function filterLogs() {
    // Supprimer les cartes d'activités en cours si elles existent
    const ongoingCards = document.querySelectorAll('.ongoing-activity-card');
    ongoingCards.forEach(card => card.remove());
    
    const logCards = document.querySelectorAll('.modern-log-card:not(.ongoing-activity-card)');
    let visibleCount = 0;
    
    logCards.forEach(card => {
        const searchText = card.dataset.searchText || '';
        const logSource = card.dataset.logSource || '';
        const logDate = card.dataset.logDate || '';
        
        // Vérifier la recherche textuelle
        const matchesSearch = !currentFilters.search || searchText.includes(currentFilters.search);
        
        // Vérifier le filtre de carte de statistiques
        let matchesStatCard = true;
        
        if (currentFilters.statCard !== 'all') {
            const today = new Date().toISOString().split('T')[0];
            const thisWeekStart = new Date();
            thisWeekStart.setDate(thisWeekStart.getDate() - thisWeekStart.getDay());
            const thisWeekStartStr = thisWeekStart.toISOString().split('T')[0];
            
            switch (currentFilters.statCard) {
                case 'reparations':
                    matchesStatCard = logSource === 'reparation';
                    break;
                case 'taches':
                    matchesStatCard = logSource === 'tache';
                    break;
                case 'today':
                    matchesStatCard = logDate === today;
                    break;
                case 'week':
                    matchesStatCard = logDate >= thisWeekStartStr;
                    break;
                default:
                    matchesStatCard = true;
            }
        }
        
        // Filtrage par date
        const matchesDate = isDateInRange(logDate);
        
        // Afficher/masquer la carte selon tous les critères
        if (matchesSearch && matchesStatCard && matchesDate) {
            card.style.display = 'block';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
            visibleCount++;
        } else {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                if (card.style.opacity === '0') {
                    card.style.display = 'none';
                }
            }, 200);
        }
    });
    
    // Afficher un message si aucun résultat
    const grid = document.querySelector('.modern-logs-grid');
    const noResultsMsg = document.querySelector('.no-results-message');
    
    if (visibleCount === 0 && currentFilters.search) {
        if (!noResultsMsg && grid) {
            const message = document.createElement('div');
            message.className = 'no-results-message';
            message.style.cssText = `
                text-align: center;
                padding: 2rem;
                color: var(--day-text-light);
                font-style: italic;
            `;
            message.innerHTML = `
                <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <div>Aucun résultat trouvé pour "${currentFilters.search}"</div>
            `;
            grid.parentNode.insertBefore(message, grid.nextSibling);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Fonction pour appliquer les filtres avec rechargement
function applyFilters() {
    const params = new URLSearchParams(window.location.search);
    
    // Mettre à jour les paramètres
    if (currentFilters.logType) {
        params.set('log_type', currentFilters.logType);
    } else {
        params.delete('log_type');
    }
    
    if (currentFilters.employee) {
        params.set('employe_id', currentFilters.employee);
    } else {
        params.delete('employe_id');
    }
    
    if (currentFilters.action) {
        params.set('action_type', currentFilters.action);
    } else {
        params.delete('action_type');
    }
    
    // Garder la page courante
    params.set('page', 'reparation_log_moderne');
    
    // Recharger avec les nouveaux paramètres
    window.location.search = params.toString();
}

// Fonctions d'action
function showFilters() {
    showToast('💡 Utilisez les filtres pour rechercher parmi les activités groupées par employé', 'info');
}

// Fonctions de gestion de la modal
function showActivityDetails(group) {
    const modal = document.getElementById('activityModal');
    const modalContent = document.getElementById('modalContent');
    
    // Construire le contenu de la modal
    let content = `
        <div class="modal-employee">
            <i class="fas fa-user"></i>
            ${group.employe_nom || 'Employé inconnu'}
        </div>
        
        <h3 style="margin-bottom: 1rem; color: var(--day-text); display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-${group.log_source === 'reparation' ? 'wrench' : 'tasks'}"></i>
            ${group.reference_title}
        </h3>
        
        <div class="action-timeline">
    `;
    
    // Ajouter chaque action à la timeline
    group.actions.forEach((action, index) => {
        let iconClass = 'plus';
        if (action.action_type === 'demarrage') iconClass = 'play';
        else if (action.action_type === 'terminer') iconClass = 'check';
        else if (action.action_type === 'changement_statut') iconClass = 'exchange-alt';
        
        content += `
            <div class="timeline-item ${action.action_type}">
                <div class="timeline-marker">
                    <i class="fas fa-${iconClass}"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-action">
                        ${formatActionTypeJS(action.action_type)}
                    </div>
                    <div class="timeline-time">
                        ${formatDateJS(action.date_action)}
                    </div>
        `;
        
        if (action.statut_avant || action.statut_apres) {
            content += '<div class="timeline-status">';
            if (action.statut_avant) {
                content += `<span class="status-before">${formatStatutJS(action.statut_avant)}</span>`;
            }
            if (action.statut_avant && action.statut_apres) {
                content += '<i class="fas fa-arrow-right"></i>';
            }
            if (action.statut_apres) {
                content += `<span class="status-after">${formatStatutJS(action.statut_apres)}</span>`;
            }
            content += '</div>';
        }
        
        if (action.details) {
            content += `<div class="timeline-details">${escapeHtml(action.details)}</div>`;
        }
        
        content += '</div></div>';
    });
    
    content += '</div>';
    
    // Calculer et afficher la durée totale
    const startTime = new Date(group.first_action);
    const endTime = new Date(group.last_action);
    const duration = endTime - startTime;
    
    let durationText = 'Instantané';
    if (duration > 0) {
        const hours = Math.floor(duration / (1000 * 60 * 60));
        const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
        if (hours > 0) {
            durationText = hours + 'h' + (minutes > 0 ? ' ' + minutes + 'min' : '');
        } else {
            durationText = minutes + 'min';
        }
    }
    
    content += `
        <div class="action-summary">
            <div class="summary-badge">
                <i class="fas fa-clock"></i>
                ${group.actions.length} étape${group.actions.length > 1 ? 's' : ''}
            </div>
            <div class="summary-duration">
                ${durationText}
            </div>
        </div>
    `;
    
    modalContent.innerHTML = content;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeActivityModal() {
    const modal = document.getElementById('activityModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Fonctions utilitaires pour la modal
function formatActionTypeJS(actionType) {
    const types = {
        'demarrage': '🚀 Démarrage',
        'terminer': '✅ Terminé',
        'changement_statut': '🔄 Changement statut',
        'creation': '➕ Création',
        'modification': '✏️ Modification',
        'ajout_note': '📝 Note ajoutée',
        'demo': '🎯 Démonstration',
        'info': 'ℹ️ Information'
    };
    return types[actionType] || '❓ ' + actionType.charAt(0).toUpperCase() + actionType.slice(1);
}

function formatStatutJS(statut) {
    const statuts = {
        'a_faire': '⏳ À faire',
        'en_cours': '🔄 En cours',
        'termine': '✅ Terminé',
        'nouvelle_intervention': '🆕 Nouvelle intervention',
        'en_cours_intervention': '🔧 En cours d\'intervention',
        'reparation_effectue': '✅ Réparation effectuée',
        'en_attente_livraison': '📦 En attente livraison',
        'restitue': '✅ Restitué'
    };
    return statuts[statut] || statut;
}

function formatDateJS(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fermer la modal en cliquant à l'extérieur
document.addEventListener('click', function(event) {
    const modal = document.getElementById('activityModal');
    if (event.target === modal) {
        closeActivityModal();
    }
});

// Fermer avec Echap
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeActivityModal();
    }
});

function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    const exportUrl = `ajax/export_logs.php?${params.toString()}`;
    window.open(exportUrl, '_blank');
    showToast('📤 Export lancé dans un nouvel onglet', 'success');
}

function refreshLogs() {
    showToast('🔄 Actualisation...', 'info');
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// Toast notifications
function showToast(message, type = 'info') {
    // Supprimer les anciens toasts
    const existingToasts = document.querySelectorAll('.modern-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `modern-toast modern-toast--${type}`;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span style="font-weight: 500;">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Supprimer après 4 secondes
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Animations des particules (optionnel)
function createParticles() {
    const container = document.getElementById('particles');
    if (!container) return;
    
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float ${Math.random() * 3 + 2}s ease-in-out infinite;
        `;
        
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 2 + 's';
        
        container.appendChild(particle);
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
        z-index: 1;
        overflow: hidden;
    }
    
    @keyframes float {
        0%, 100% { 
            transform: translateY(0px) rotate(0deg);
            opacity: 0.3;
        }
        50% { 
            transform: translateY(-20px) rotate(180deg);
            opacity: 0.7;
        }
    }
`;
document.head.appendChild(style);

</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">