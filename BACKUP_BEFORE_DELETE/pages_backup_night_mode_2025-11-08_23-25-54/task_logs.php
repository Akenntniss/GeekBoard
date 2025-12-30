<?php
// Page pour afficher les logs de tâches
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/task_logger.php';

// Paramètres de pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

// Filtres
$employe_id = isset($_GET['employe_id']) ? intval($_GET['employe_id']) : 0;
$tache_id = isset($_GET['tache_id']) ? intval($_GET['tache_id']) : 0;
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        $shop_pdo = new PDO(
            "mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4",
            "root",
            "Mamanmaman01#",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    
    // Construire la requête avec filtres
    $where_conditions = [];
    $params = [];
    
    if ($employe_id > 0) {
        $where_conditions[] = "tl.employe_id = ?";
        $params[] = $employe_id;
    }
    
    if ($tache_id > 0) {
        $where_conditions[] = "tl.tache_id = ?";
        $params[] = $tache_id;
    }
    
    if (!empty($action_type)) {
        $where_conditions[] = "tl.action_type = ?";
        $params[] = $action_type;
    }
    
    if (!empty($date_debut)) {
        $where_conditions[] = "DATE(tl.date_action) >= ?";
        $params[] = $date_debut;
    }
    
    if (!empty($date_fin)) {
        $where_conditions[] = "DATE(tl.date_action) <= ?";
        $params[] = $date_fin;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Requête pour récupérer les logs
    $sql = "
        SELECT tl.*, u.full_name as employe_nom, t.titre as tache_titre
        FROM task_logs tl
        LEFT JOIN users u ON tl.employe_id = u.id
        LEFT JOIN taches t ON tl.tache_id = t.id
        $where_clause
        ORDER BY tl.date_action DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total pour la pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM task_logs tl
        LEFT JOIN users u ON tl.employe_id = u.id
        LEFT JOIN taches t ON tl.tache_id = t.id
        $where_clause
    ";
    
    $count_params = array_slice($params, 0, -2); // Enlever limit et offset
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_logs = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_logs / $limit);
    
    // Récupérer la liste des employés pour le filtre
    $stmt_users = $shop_pdo->prepare("SELECT id, full_name FROM users ORDER BY full_name");
    $stmt_users->execute();
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des logs de tâches: " . $e->getMessage());
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
        default:
            return $statut;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs des Tâches - GeekBoard</title>
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
        .log-card.demarrage {
            border-left-color: #28a745;
        }
        .log-card.terminer {
            border-left-color: #17a2b8;
        }
        .log-card.changement_statut {
            border-left-color: #ffc107;
        }
        .badge-action {
            font-size: 0.9em;
        }
        .timeline-date {
            font-size: 0.85em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-history me-2"></i>
                        Logs des Tâches
                    </h1>
                    <div class="badge bg-primary fs-6">
                        <?php echo number_format($total_logs); ?> logs au total
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
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
                                <label for="tache_id" class="form-label">ID Tâche</label>
                                <input type="number" name="tache_id" id="tache_id" class="form-control" value="<?php echo $tache_id; ?>" placeholder="ID">
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
                                <div class="card log-card <?php echo $log['action_type']; ?> h-100">
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
                                            <i class="fas fa-tasks me-1"></i>
                                            Tâche #<?php echo $log['tache_id']; ?>
                                        </h6>
                                        
                                        <?php if ($log['tache_titre']): ?>
                                            <p class="card-text small text-muted mb-2">
                                                <?php echo htmlspecialchars($log['tache_titre']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
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

