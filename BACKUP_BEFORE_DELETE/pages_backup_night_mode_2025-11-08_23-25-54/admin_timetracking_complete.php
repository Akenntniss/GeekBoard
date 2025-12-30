<?php
/**
 * Interface administrateur pour le système de pointage - Version complète
 * Compatible avec la structure GeekBoard existante
 */

// Initialisation session comme dans presence_gestion.php
if (!session_id()) {
    session_start();
}

// Inclure les fichiers de base comme dans la structure GeekBoard
if (file_exists(__DIR__ . '/../config/session_config.php')) {
    require_once __DIR__ . '/../config/session_config.php';
}

if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}

if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Auto-initialisation du système (comme presence_gestion.php)
if (defined('BASE_PATH')) {
    if (file_exists(BASE_PATH . '/includes/presence_auto_init.php')) {
        require_once BASE_PATH . '/includes/presence_auto_init.php';
        
        // Auto-initialiser si nécessaire
        if (function_exists('isPresenceSystemInitialized') && !isPresenceSystemInitialized()) {
            initializePresenceSystem();
        }
    }
}

// Variables globales pour l'authentification (comme presence_gestion.php)
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Debug des variables de session
$debug_session = [
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'NON_DEFINI',
    'user_role' => $_SESSION['user_role'] ?? 'NON_DEFINI',
    'full_name' => $_SESSION['full_name'] ?? 'NON_DEFINI',
    'is_admin' => $is_admin ? 'OUI' : 'NON'
];

// Obtenir la connexion à la base de données (comme presence_gestion.php)
if (function_exists('getShopDBConnection')) {
    $shop_pdo = getShopDBConnection();
} else {
    $shop_pdo = null;
    $db_error = "Fonction getShopDBConnection non disponible";
}

// Si pas d'accès admin, afficher les infos de debug au lieu de bloquer
$show_debug = !$is_admin;

// Traitement des actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $is_admin && isset($shop_pdo)) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'force_clock_out':
            $user_id = intval($_POST['user_id']);
            try {
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
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
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

// Utilisateurs actifs
$active_users = [];
$active_users_error = null;
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
        $active_users_error = "Erreur récupération utilisateurs: " . $e->getMessage();
    }
}

// Statistiques
$stats = ['currently_working' => 0, 'on_break' => 0, 'total_sessions' => 0, 'active_employees' => 0];
if (isset($shop_pdo)) {
    try {
        $stmt = $shop_pdo->prepare("
            SELECT 
                COUNT(*) as total_sessions,
                COUNT(DISTINCT user_id) as active_employees,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
                SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break
            FROM time_tracking
            WHERE DATE(clock_in) = ?
        ");
        $stmt->execute([$filter_date]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stats_error = "Erreur stats: " . $e->getMessage();
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
        .debug-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.375rem; }
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
                
                <!-- Debug des sessions SI pas admin -->
                <?php if ($show_debug): ?>
                <div class="alert alert-warning debug-section">
                    <h4><i class="fas fa-bug"></i> Debug - Information Session</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Variables de Session</h5>
                            <table class="table table-sm">
                                <?php foreach ($debug_session as $key => $value): ?>
                                <tr>
                                    <th><?php echo $key; ?>:</th>
                                    <td><code><?php echo htmlspecialchars($value); ?></code></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Tests</h5>
                            <p><strong>Session démarrée:</strong> <?php echo session_id() ? '✅ OUI' : '❌ NON'; ?></p>
                            <p><strong>Base de données:</strong> <?php echo isset($shop_pdo) ? '✅ OK' : '❌ ERREUR'; ?></p>
                            <?php if (isset($db_error)): ?>
                            <p><strong>Erreur DB:</strong> <code><?php echo htmlspecialchars($db_error); ?></code></p>
                            <?php endif; ?>
                            <p><strong>Fonction getShopDBConnection:</strong> <?php echo function_exists('getShopDBConnection') ? '✅ OK' : '❌ NON'; ?></p>
                        </div>
                    </div>
                    
                    <?php if (!$is_admin): ?>
                    <div class="alert alert-danger mt-3">
                        <h5>🚫 Accès refusé</h5>
                        <p>Cette page est réservée aux administrateurs.</p>
                        <p><strong>Problème détecté:</strong> La variable $_SESSION['user_role'] n'est pas définie sur 'admin'</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Interface normale SI admin -->
                <?php if ($is_admin): ?>
                
                <!-- Statistiques -->
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
                
                <!-- Erreurs -->
                <?php if (isset($active_users_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($active_users_error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($stats_error)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($stats_error); ?></div>
                <?php endif; ?>
                
                <!-- Utilisateurs actifs -->
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
                                        
                                        <div class="mt-3">
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
                
                <?php endif; // Fin if admin ?>
                
                <!-- Test API (toujours visible) -->
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
        // Fonctions pour les actions admin
        function forceClockOut(userId, userName) {
            if (confirm(`Êtes-vous sûr de vouloir forcer le pointage de sortie de ${userName} ?`)) {
                const formData = new FormData();
                formData.append('action', 'force_clock_out');
                formData.append('user_id', userId);
                
                fetch('admin_timetracking_complete.php', {
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
