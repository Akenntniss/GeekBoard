<?php
/**
 * Interface administrateur pour le système de pointage
 * Compatible avec la structure GeekBoard existante
 */

// Variables globales pour l'authentification (comme presence_gestion.php)
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Obtenir la connexion à la base de données (comme presence_gestion.php)
if (function_exists('getShopDBConnection')) {
    $shop_pdo = getShopDBConnection();
}

// Si pas d'accès admin, afficher message d'erreur au lieu de rediriger
if (!$is_admin) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Accès refusé</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="alert alert-danger">
                <h4>🚫 Accès refusé</h4>
                <p>Cette page est réservée aux administrateurs.</p>
                <a href="../index.php" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Traitement des actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($shop_pdo)) {
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
$active_users = [];
if (isset($shop_pdo)) {
    try {
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
    } catch (Exception $e) {
        $error_message = "Erreur récupération utilisateurs: " . $e->getMessage();
    }
}

// Entrées du jour sélectionné
$daily_entries = [];
if (isset($shop_pdo)) {
    try {
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
    } catch (Exception $e) {
        $entries_error = "Erreur récupération entrées: " . $e->getMessage();
    }
}

// Liste des utilisateurs pour le filtre
$all_users = [];
if (isset($shop_pdo)) {
    try {
        $stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users ORDER BY full_name");
        $stmt->execute();
        $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Ignorer l'erreur
    }
}

// Statistiques rapides
$stats = ['currently_working' => 0, 'on_break' => 0, 'total_sessions' => 0, 'active_employees' => 0, 'avg_work_hours' => 0];
if (isset($shop_pdo)) {
    try {
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
    } catch (Exception $e) {
        // Utiliser les valeurs par défaut
    }
}
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
        .status-badge { font-size: 0.8em; padding: 0.25em 0.5em; }
        .active-user-card { border-left: 4px solid #28a745; background: linear-gradient(45deg, #f8f9fa, #e9ecef); }
        .break-user-card { border-left: 4px solid #ffc107; background: linear-gradient(45deg, #fff3cd, #ffeaa7); }
        .stats-card { transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-2px); }
        .duration-display { font-family: 'Courier New', monospace; font-weight: bold; font-size: 1.1em; }
    </style>
</head>
<body>
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
                        <a href="../index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <?php if (isset($entries_error)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($entries_error); ?></div>
                <?php endif; ?>
                
                <!-- Statistiques rapides -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4 class="text-primary"><?php echo $stats['currently_working'] ?? 0; ?></h4>
                                <small class="text-muted">En cours</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-pause fa-2x text-warning mb-2"></i>
                                <h4 class="text-warning"><?php echo $stats['on_break'] ?? 0; ?></h4>
                                <small class="text-muted">En pause</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-2x text-info mb-2"></i>
                                <h4 class="text-info"><?php echo $stats['total_sessions'] ?? 0; ?></h4>
                                <small class="text-muted">Sessions aujourd'hui</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                                <h4 class="text-success"><?php echo $stats['active_employees'] ?? 0; ?></h4>
                                <small class="text-muted">Employés actifs</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Utilisateurs actuellement pointés -->
                <?php if (!empty($active_users)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <h3><i class="fas fa-users-clock text-success"></i> Employés actuellement pointés (<?php echo count($active_users); ?>)</h3>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Aucun employé actuellement pointé
                </div>
                <?php endif; ?>
                
                <!-- Test de l'API -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4><i class="fas fa-flask"></i> Test API Pointage</h4>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary" onclick="testAPI()">Test API Status</button>
                        <button class="btn btn-success" onclick="testAdminAPI()">Test Admin API</button>
                        <div id="api-results" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
                
                fetch('admin_timetracking_fixed.php', {
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
        
        function testAPI() {
            fetch('../time_tracking_api.php?action=get_status')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('api-results').innerHTML = 
                        '<h5>API Response:</h5><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('api-results').innerHTML = 
                        '<div class="alert alert-danger">Erreur API: ' + error + '</div>';
                });
        }
        
        function testAdminAPI() {
            fetch('../time_tracking_api.php?action=admin_get_active')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('api-results').innerHTML = 
                        '<h5>Admin API Response:</h5><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('api-results').innerHTML = 
                        '<div class="alert alert-danger">Erreur Admin API: ' + error + '</div>';
                });
        }
    </script>
</body>
</html>
