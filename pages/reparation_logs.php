<?php
// Page combinée pour afficher les logs de réparations ET les logs de tâches
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/task_logger.php';

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
    
    // Construire la requête selon le type de log demandé
    $sql_parts = [];
    
    if ($log_type === 'all' || $log_type === 'reparations') {
        $sql_parts[] = "
            SELECT 
                rl.id,
                rl.date_action,
                rl.action_type,
                rl.statut_avant,
                rl.statut_apres,
                rl.details,
                u.full_name as employe_nom,
                'reparation' as log_source,
                rl.reparation_id as reference_id,
                CONCAT('Réparation #', rl.reparation_id) as reference_title
            FROM reparation_logs rl
            LEFT JOIN users u ON rl.employe_id = u.id
            $where_clause_rep
        ";
    }
    
    if ($log_type === 'all' || $log_type === 'taches') {
        $sql_parts[] = "
            SELECT 
                tl.id,
                tl.date_action,
                tl.action_type,
                tl.statut_avant,
                tl.statut_apres,
                tl.details,
                u.full_name as employe_nom,
                'tache' as log_source,
                tl.tache_id as reference_id,
                COALESCE(t.titre, CONCAT('Tâche #', tl.tache_id)) as reference_title
            FROM task_logs tl
            LEFT JOIN users u ON tl.employe_id = u.id
            LEFT JOIN taches t ON tl.tache_id = t.id
            $where_clause_task
        ";
    }
    
    if (empty($sql_parts)) {
        $logs = [];
        $total_logs = 0;
    } else {
        $sql = implode(' UNION ALL ', $sql_parts) . "
            ORDER BY date_action DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Compter le total pour la pagination
        $count_sql_parts = [];
        $count_params = array_slice($params, 0, -2); // Enlever limit et offset
        
        if ($log_type === 'all' || $log_type === 'reparations') {
            $count_sql_parts[] = "SELECT COUNT(*) FROM reparation_logs rl $where_clause_rep";
        }
        
        if ($log_type === 'all' || $log_type === 'taches') {
            $count_sql_parts[] = "SELECT COUNT(*) FROM task_logs tl $where_clause_task";
        }
        
        if (!empty($count_sql_parts)) {
            $count_sql = "SELECT SUM(total) as grand_total FROM (" . implode(' UNION ALL ', array_map(function($part) {
                return "SELECT COUNT(*) as total FROM ($part) as sub";
            }, $count_sql_parts)) . ") as counts";
            
            // Simplifier le comptage
            $total_logs = 0;
            foreach ($count_sql_parts as $i => $count_part) {
                $count_stmt = $shop_pdo->prepare($count_part);
                $part_params = array_slice($count_params, $i * (count($count_params) / count($count_sql_parts)), count($count_params) / count($count_sql_parts));
                $count_stmt->execute($part_params);
                $total_logs += $count_stmt->fetchColumn();
            }
        }
    }
    
    $total_pages = ceil($total_logs / $limit);
    
    // Récupérer la liste des employés pour le filtre
    $stmt_users = $shop_pdo->prepare("SELECT id, full_name FROM users ORDER BY full_name");
    $stmt_users->execute();
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    
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
        case 'ajout_note':
            return '📝 Note ajoutée';
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
    return $log_source === 'reparation' ? '🔧' : '📋';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs d'Activité - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .log-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .log-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .log-card.reparation {
            border-left-color: #28a745;
        }
        .log-card.tache {
            border-left-color: #17a2b8;
        }
        .log-card.demarrage {
            border-left-color: #ffc107;
        }
        .log-card.terminer {
            border-left-color: #28a745;
        }
        .badge-action {
            font-size: 0.9em;
        }
        .timeline-date {
            font-size: 0.85em;
            color: #6c757d;
        }
        .log-source-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8em;
        }

        /* ========================================
           FOND ANIMÉ JOUR/NUIT
        ======================================== */
        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Mode Jour - Fond animé */
        body:not(.night-mode) {
            background: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff) !important;
            background-size: 400% 400% !important;
            animation: gradientFlow 15s ease infinite !important;
            padding-top: 70px !important;
        }

        /* Mode Nuit - Fond animé */
        body.night-mode {
            background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483) !important;
            background-size: 400% 400% !important;
            animation: gradientFlow 15s ease infinite !important;
            padding-top: 70px !important;
        }

        /* ========================================
           FIX NAVBAR DESKTOP
        ======================================== */
        @media (min-width: 992px) {
            #mobile-dock, #dock-recall-zone {
                display: none !important;
            }
            
            #desktop-navbar, nav#desktop-navbar, .navbar {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 10000 !important;
                height: 70px !important;
                min-height: 70px !important;
                width: 100% !important;
                overflow: visible !important;
                align-items: center !important;
            }
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
            
            .container-fluid {
                padding-bottom: 100px !important;
            }
        }

        /* ========================================
           CARTES MODE JOUR
        ======================================== */
        body:not(.night-mode) .card {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(148, 163, 184, 0.3) !important;
            color: #1e293b !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        }

        body:not(.night-mode) .card-body {
            background: rgba(255, 255, 255, 0.95) !important;
            color: #1e293b !important;
        }

        body:not(.night-mode) h1,
        body:not(.night-mode) h3,
        body:not(.night-mode) h6,
        body:not(.night-mode) .card-title {
            color: #1e293b !important;
        }

        body:not(.night-mode) .form-control,
        body:not(.night-mode) .form-select {
            background: #ffffff !important;
            border-color: rgba(148, 163, 184, 0.5) !important;
            color: #1e293b !important;
        }

        body:not(.night-mode) .form-label {
            color: #1e293b !important;
        }

        /* ========================================
           CARTES MODE NUIT
        ======================================== */
        body.night-mode .card {
            background: rgba(15, 15, 25, 0.95) !important;
            border: 1px solid rgba(0, 212, 255, 0.3) !important;
            color: #ffffff !important;
        }

        body.night-mode .card-body {
            background: rgba(15, 15, 25, 0.95) !important;
            color: #ffffff !important;
        }

        body.night-mode h1,
        body.night-mode h3,
        body.night-mode h6,
        body.night-mode .card-title {
            color: #ffffff !important;
        }

        body.night-mode .form-control,
        body.night-mode .form-select {
            background: rgba(15, 23, 42, 0.8) !important;
            border-color: rgba(0, 212, 255, 0.3) !important;
            color: #ffffff !important;
        }

        body.night-mode .form-label {
            color: #ffffff !important;
        }

        body.night-mode .text-muted {
            color: #a0aec0 !important;
        }

        body.night-mode .timeline-date {
            color: #a0aec0 !important;
        }

        /* ========================================
           PAGINATION MODE NUIT
        ======================================== */
        body.night-mode .page-link {
            background: rgba(15, 23, 42, 0.8) !important;
            border-color: rgba(0, 212, 255, 0.3) !important;
            color: #ffffff !important;
        }

        body.night-mode .page-link:hover {
            background: rgba(0, 212, 255, 0.2) !important;
            color: #00d4ff !important;
        }

        body.night-mode .page-item.active .page-link {
            background: linear-gradient(135deg, #00d4ff, #7c3aed) !important;
            border-color: #00d4ff !important;
        }

        /* ========================================
           NAVBAR MODE NUIT
        ======================================== */
        body.night-mode #desktop-navbar,
        body.night-mode nav#desktop-navbar,
        body.night-mode .navbar {
            background: rgba(15, 15, 25, 0.95) !important;
            border-bottom: 1px solid rgba(0, 212, 255, 0.3) !important;
        }

        body.night-mode #desktop-navbar .navbar-brand,
        body.night-mode #desktop-navbar .nav-link,
        body.night-mode #desktop-navbar .navbar-text {
            color: #ffffff !important;
        }
    </style>
    <?php include_once 'includes/night-mode-system.php'; ?>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-history me-2"></i>
                        Logs d'Activité
                    </h1>
                    <div class="badge bg-primary fs-6">
                        <?php echo number_format($total_logs); ?> logs au total
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label for="log_type" class="form-label">Type de log</label>
                                <select name="log_type" id="log_type" class="form-select">
                                    <option value="all" <?php echo ($log_type == 'all') ? 'selected' : ''; ?>>Tous</option>
                                    <option value="reparations" <?php echo ($log_type == 'reparations') ? 'selected' : ''; ?>>🔧 Réparations</option>
                                    <option value="taches" <?php echo ($log_type == 'taches') ? 'selected' : ''; ?>>📋 Tâches</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="employe_id" class="form-label">Employé</label>
                                <select name="employe_id" id="employe_id" class="form-select">
                                    <option value="0">Tous les employés</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo ($employe_id == $user['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="action_type" class="form-label">Action</label>
                                <select name="action_type" id="action_type" class="form-select">
                                    <option value="">Toutes les actions</option>
                                    <option value="demarrage" <?php echo ($action_type == 'demarrage') ? 'selected' : ''; ?>>Démarrage</option>
                                    <option value="terminer" <?php echo ($action_type == 'terminer') ? 'selected' : ''; ?>>Terminé</option>
                                    <option value="changement_statut" <?php echo ($action_type == 'changement_statut') ? 'selected' : ''; ?>>Changement statut</option>
                                    <option value="creation" <?php echo ($action_type == 'creation') ? 'selected' : ''; ?>>Création</option>
                                    <option value="modification" <?php echo ($action_type == 'modification') ? 'selected' : ''; ?>>Modification</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_debut" class="form-label">Date début</label>
                                <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?php echo $date_debut; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_fin" class="form-label">Date fin</label>
                                <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?php echo $date_fin; ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des logs -->
                <div class="row">
                    <?php if (empty($logs)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucun log trouvé avec les critères sélectionnés.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card log-card <?php echo $log['log_source']; ?> <?php echo $log['action_type']; ?> h-100 position-relative">
                                    <span class="badge log-source-badge <?php echo $log['log_source'] === 'reparation' ? 'bg-success' : 'bg-info'; ?>">
                                        <?php echo getLogTypeIcon($log['log_source']); ?> <?php echo ucfirst($log['log_source']); ?>
                                    </span>
                                    
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge badge-action bg-primary">
                                                <?php echo formatActionType($log['action_type']); ?>
                                            </span>
                                            <small class="timeline-date">
                                                <?php echo date('d/m/Y H:i', strtotime($log['date_action'])); ?>
                                            </small>
                                        </div>
                                        
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-<?php echo $log['log_source'] === 'reparation' ? 'wrench' : 'tasks'; ?> me-1"></i>
                                            <?php echo htmlspecialchars($log['reference_title']); ?>
                                        </h6>
                                        
                                        <div class="mb-2">
                                            <strong>Employé:</strong>
                                            <span class="text-primary">
                                                <?php echo htmlspecialchars($log['employe_nom'] ?: 'Inconnu'); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($log['statut_avant'] || $log['statut_apres']): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <?php if ($log['statut_avant']): ?>
                                                        <?php echo formatStatut($log['statut_avant']); ?>
                                                    <?php endif; ?>
                                                    <?php if ($log['statut_avant'] && $log['statut_apres']): ?>
                                                        <i class="fas fa-arrow-right mx-1"></i>
                                                    <?php endif; ?>
                                                    <?php if ($log['statut_apres']): ?>
                                                        <?php echo formatStatut($log['statut_apres']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($log['details']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <?php echo htmlspecialchars($log['details']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navigation des logs" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i> Précédent
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page + 1])); ?>">
                                        Suivant <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

