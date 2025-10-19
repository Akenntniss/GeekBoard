<?php
/**
 * Interface administrateur pour le système de pointage
 * Permet de voir et gérer les pointages des employés
 */

// Inclusion des fichiers de configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier l'authentification et les droits admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$shop_pdo = getShopDBConnection();

// Traitement des actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
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
            
        case 'edit_entry':
            $entry_id = intval($_POST['entry_id']);
            $clock_in = $_POST['edit_clock_in'];
            $clock_out = $_POST['edit_clock_out'] ?: null;
            $notes = $_POST['edit_notes'];
            
            // Calculer les nouvelles durées si clock_out est défini
            $sql = "UPDATE time_tracking SET clock_in = ?, notes = ?, admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nEdited by admin at ', NOW())";
            $params = [$clock_in, $notes];
            
            if ($clock_out) {
                $sql .= ", clock_out = ?, status = 'completed', total_hours = TIMESTAMPDIFF(MINUTE, ?, ?) / 60.0, work_duration = (TIMESTAMPDIFF(MINUTE, ?, ?) / 60.0) - IFNULL(break_duration, 0)";
                $params = array_merge($params, [$clock_out, $clock_in, $clock_out, $clock_in, $clock_out]);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $entry_id;
            
            $stmt = $shop_pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $response = ['success' => true, 'message' => 'Entrée modifiée avec succès'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors de la modification'];
            }
            break;
    }
    
    // Retourner la réponse en JSON pour les requêtes AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Récupérer les données pour l'affichage
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_user = $_GET['user'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Utilisateurs actifs (pointés en ce moment)
$stmt = $shop_pdo->prepare("
    SELECT tt.*, u.full_name, u.username, u.role,
           TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) / 60.0 as current_duration,
           TIME_FORMAT(TIMEDIFF(NOW(), tt.clock_in), '%H:%i') as formatted_duration
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE tt.status IN ('active', 'break')
    ORDER BY tt.clock_in ASC
");
$stmt->execute();
$active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Entrées du jour sélectionné
$where_conditions = ["DATE(tt.clock_in) = ?"];
$params = [$filter_date];

if ($filter_user) {
    $where_conditions[] = "tt.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_status) {
    $where_conditions[] = "tt.status = ?";
    $params[] = $filter_status;
}

$stmt = $shop_pdo->prepare("
    SELECT tt.*, u.full_name, u.username, u.role
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE " . implode(' AND ', $where_conditions) . "
    ORDER BY tt.clock_in DESC
");
$stmt->execute($params);
$daily_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Liste des utilisateurs pour le filtre
$stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users ORDER BY full_name");
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques rapides
$stmt = $shop_pdo->prepare("
    SELECT 
        COUNT(*) as total_sessions,
        COUNT(DISTINCT user_id) as active_employees,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
        SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break,
        AVG(work_duration) as avg_work_hours
    FROM time_tracking
    WHERE DATE(clock_in) = ?
");
$stmt->execute([$filter_date]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Système de Pointage | GeekBoard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .status-badge {
            font-size: 0.8em;
            padding: 0.25em 0.5em;
        }
        
        .active-user-card {
            border-left: 4px solid #28a745;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
        }
        
        .break-user-card {
            border-left: 4px solid #ffc107;
            background: linear-gradient(45deg, #fff3cd, #ffeaa7);
        }
        
        .stats-card {
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .duration-display {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-buttons .btn {
            margin: 2px;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1><i class="fas fa-clock text-primary"></i> Administration - Pointage</h1>
                        <p class="text-muted">Gestion et supervision des pointages employés</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button class="btn btn-success" onclick="exportReport()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>
                
                <!-- Statistiques rapides -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stats-card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4 class="text-primary"><?php echo $stats['currently_working']; ?></h4>
                                <small class="text-muted">En cours</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-pause fa-2x text-warning mb-2"></i>
                                <h4 class="text-warning"><?php echo $stats['on_break']; ?></h4>
                                <small class="text-muted">En pause</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-2x text-info mb-2"></i>
                                <h4 class="text-info"><?php echo $stats['total_sessions']; ?></h4>
                                <small class="text-muted">Sessions aujourd'hui</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                                <h4 class="text-success"><?php echo $stats['active_employees']; ?></h4>
                                <small class="text-muted">Employés actifs</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card border-dark">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x text-dark mb-2"></i>
                                <h4 class="text-dark"><?php echo number_format($stats['avg_work_hours'], 1); ?>h</h4>
                                <small class="text-muted">Moyenne heures/jour</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Utilisateurs actuellement pointés -->
                <?php if (!empty($active_users)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <h3><i class="fas fa-users-clock text-success"></i> Employés actuellement pointés</h3>
                        <div class="row">
                            <?php foreach ($active_users as $user): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card <?php echo $user['status'] === 'break' ? 'break-user-card' : 'active-user-card'; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                            <span class="badge bg-<?php echo $user['status'] === 'break' ? 'warning' : 'success'; ?>">
                                                <?php echo $user['status'] === 'break' ? 'Pause' : 'Actif'; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <small class="text-muted">Pointé depuis:</small>
                                            <div class="duration-display text-<?php echo $user['status'] === 'break' ? 'warning' : 'success'; ?>">
                                                <?php echo $user['formatted_duration']; ?>
                                            </div>
                                            <small class="text-muted">
                                                Début: <?php echo date('H:i', strtotime($user['clock_in'])); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mt-3 d-flex gap-1">
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="forceClockOut(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                                <i class="fas fa-sign-out-alt"></i> Forcer sortie
                                            </button>
                                            <button class="btn btn-outline-info btn-sm" 
                                                    onclick="viewUserDetails(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-eye"></i> Détails
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Filtres -->
                <div class="filter-section">
                    <h4><i class="fas fa-filter"></i> Filtres et recherche</h4>
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="user" class="form-label">Employé</label>
                            <select class="form-select" id="user" name="user">
                                <option value="">Tous les employés</option>
                                <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Actif</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                                <option value="break" <?php echo $filter_status === 'break' ? 'selected' : ''; ?>>En pause</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Rechercher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tableau des entrées -->
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-table"></i> Entrées de pointage - <?php echo date('d/m/Y', strtotime($filter_date)); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Employé</th>
                                        <th>Arrivée</th>
                                        <th>Sortie</th>
                                        <th>Temps travaillé</th>
                                        <th>Pause</th>
                                        <th>Statut</th>
                                        <th>Localisation</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($daily_entries)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-clock fa-3x mb-3"></i><br>
                                            Aucune entrée trouvée pour les critères sélectionnés
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($daily_entries as $entry): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($entry['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($entry['username']); ?></small>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($entry['clock_in'])); ?></td>
                                        <td>
                                            <?php if ($entry['clock_out']): ?>
                                                <?php echo date('H:i', strtotime($entry['clock_out'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">En cours</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($entry['work_duration']): ?>
                                                <span class="duration-display"><?php echo number_format($entry['work_duration'], 2); ?>h</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($entry['break_duration'] > 0): ?>
                                                <span class="text-warning"><?php echo number_format($entry['break_duration'], 2); ?>h</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'active' => 'success',
                                                'break' => 'warning',
                                                'completed' => 'secondary'
                                            ];
                                            $status_labels = [
                                                'active' => 'Actif',
                                                'break' => 'Pause',
                                                'completed' => 'Terminé'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $status_colors[$entry['status']] ?? 'secondary'; ?>">
                                                <?php echo $status_labels[$entry['status']] ?? $entry['status']; ?>
                                            </span>
                                            <?php if ($entry['admin_approved']): ?>
                                                <i class="fas fa-check-circle text-success ms-1" title="Approuvé par admin"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($entry['location']): ?>
                                                <button class="btn btn-outline-info btn-sm" onclick="showLocation('<?php echo $entry['location']; ?>')">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-outline-primary btn-sm" 
                                                        onclick="editEntry(<?php echo $entry['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!$entry['admin_approved']): ?>
                                                <button class="btn btn-outline-success btn-sm" 
                                                        onclick="approveEntry(<?php echo $entry['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-info btn-sm" 
                                                        onclick="viewEntryDetails(<?php echo $entry['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals pour les actions -->
    <!-- Modal d'édition d'entrée -->
    <div class="modal fade" id="editEntryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'entrée de pointage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editEntryForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_entry">
                        <input type="hidden" name="entry_id" id="edit_entry_id">
                        
                        <div class="mb-3">
                            <label for="edit_clock_in" class="form-label">Heure d'arrivée</label>
                            <input type="datetime-local" class="form-control" name="edit_clock_in" id="edit_clock_in" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_clock_out" class="form-label">Heure de sortie (optionnel)</label>
                            <input type="datetime-local" class="form-control" name="edit_clock_out" id="edit_clock_out">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_notes" class="form-label">Notes</label>
                            <textarea class="form-control" name="edit_notes" id="edit_notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/time_tracking.js"></script>
    
    <script>
        // Auto-refresh toutes les 60 secondes
        setInterval(() => {
            location.reload();
        }, 60000);
        
        // Fonctions pour les actions admin
        function forceClockOut(userId, userName) {
            if (confirm(`Êtes-vous sûr de vouloir forcer le pointage de sortie de ${userName} ?`)) {
                const formData = new FormData();
                formData.append('action', 'force_clock_out');
                formData.append('user_id', userId);
                
                fetch('admin_timetracking.php', {
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
        
        function approveEntry(entryId) {
            if (confirm('Approuver cette entrée de pointage ?')) {
                const formData = new FormData();
                formData.append('action', 'approve_entry');
                formData.append('entry_id', entryId);
                
                fetch('admin_timetracking.php', {
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
        
        function editEntry(entryId) {
            // Ici vous pourriez charger les données de l'entrée via AJAX
            // Pour l'instant, on ouvre juste le modal
            document.getElementById('edit_entry_id').value = entryId;
            new bootstrap.Modal(document.getElementById('editEntryModal')).show();
        }
        
        function showLocation(location) {
            const [lat, lng] = location.split(',');
            window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
        }
        
        function exportReport() {
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'csv');
            window.open('?' + params.toString());
        }
        
        function viewUserDetails(userId) {
            // Rediriger vers une page de détails ou ouvrir un modal
            window.open(`presence_gestion.php?user_id=${userId}`, '_blank');
        }
        
        function viewEntryDetails(entryId) {
            // Afficher les détails complets d'une entrée
            alert('Fonctionnalité à implémenter: détails de l\'entrée ' + entryId);
        }
        
        // Gestion du formulaire d'édition
        document.getElementById('editEntryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('admin_timetracking.php', {
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
                    bootstrap.Modal.getInstance(document.getElementById('editEntryModal')).hide();
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
        });
    </script>
</body>
</html>
