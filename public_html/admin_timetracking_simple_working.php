<?php
// Page de gestion du pointage - Version simple qui fonctionne
// Session déjà démarrée par config/session_config.php

// Variables globales pour l'authentification (comme presence_gestion.php)
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Obtenir la connexion à la base de données
if (function_exists('getShopDBConnection')) {
    $shop_pdo = getShopDBConnection();
}

// Vérification admin simple
if (!$is_admin) {
    echo "<!DOCTYPE html><html><head><title>Accès refusé</title></head><body>";
    echo "<h1>🚫 Accès refusé</h1>";
    echo "<p>Cette page est réservée aux administrateurs.</p>";
    echo "<p><strong>Debug:</strong></p>";
    echo "<ul>";
    echo "<li>Session ID: " . (session_id() ?: 'AUCUNE') . "</li>";
    echo "<li>User ID: " . ($current_user_id ?: 'NON DÉFINI') . "</li>";
    echo "<li>User Role: " . ($_SESSION['user_role'] ?? 'NON DÉFINI') . "</li>";
    echo "<li>Is Admin: " . ($is_admin ? 'OUI' : 'NON') . "</li>";
    echo "</ul>";
    echo "<a href='../index.php'>← Retour à l'accueil</a>";
    echo "</body></html>";
    exit;
}

// Traitement des actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($shop_pdo)) {
    $response = ['success' => false, 'message' => ''];
    
    try {
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
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
    
    // Retourner la réponse en JSON pour les requêtes AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Récupérer les utilisateurs actifs
$active_users = [];
$stats = ['currently_working' => 0, 'on_break' => 0, 'total_sessions' => 0, 'active_employees' => 0];

if (isset($shop_pdo)) {
    try {
        // Utilisateurs actifs
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
        
        // Statistiques
        $stmt = $shop_pdo->prepare("
            SELECT 
                COUNT(*) as total_sessions,
                COUNT(DISTINCT user_id) as active_employees,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
                SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break
            FROM time_tracking
            WHERE DATE(clock_in) = CURDATE()
        ");
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error_message = "Erreur base de données: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Système de Pointage</title>
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
<body class="bg-light">
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2"><i class="fas fa-clock text-primary"></i> Administration - Pointage</h1>
                        <p class="text-muted">Gestion et supervision des pointages employés</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <a href="../index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                
                <!-- Erreurs -->
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card border-primary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4 class="text-primary"><?php echo $stats['currently_working'] ?? 0; ?></h4>
                                <small class="text-muted">En cours</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card border-warning h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-pause fa-2x text-warning mb-2"></i>
                                <h4 class="text-warning"><?php echo $stats['on_break'] ?? 0; ?></h4>
                                <small class="text-muted">En pause</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card border-info h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-2x text-info mb-2"></i>
                                <h4 class="text-info"><?php echo $stats['total_sessions'] ?? 0; ?></h4>
                                <small class="text-muted">Sessions aujourd'hui</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card border-success h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                                <h4 class="text-success"><?php echo $stats['active_employees'] ?? 0; ?></h4>
                                <small class="text-muted">Employés actifs</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Utilisateurs actifs -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-users-clock text-success"></i> Employés actuellement pointés (<?php echo count($active_users); ?>)</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($active_users)): ?>
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
                        <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> Aucun employé actuellement pointé
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Test API -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-flask"></i> Test API Pointage</h4>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary btn-sm" onclick="testAPI()">Test API Status</button>
                        <button class="btn btn-success btn-sm" onclick="testAdminAPI()">Test Admin API</button>
                        <div id="api-results" class="mt-3"></div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actions admin
        function forceClockOut(userId, userName) {
            if (confirm(`Êtes-vous sûr de vouloir forcer le pointage de sortie de ${userName} ?`)) {
                const formData = new FormData();
                formData.append('action', 'force_clock_out');
                formData.append('user_id', userId);
                
                fetch('admin_timetracking.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
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
        
        // Test API
        function testAPI() {
            fetch('../time_tracking_api.php?action=get_status')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('api-results').innerHTML = 
                        '<h5>API Response:</h5><pre class="bg-light p-2">' + JSON.stringify(data, null, 2) + '</pre>';
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
                        '<h5>Admin API Response:</h5><pre class="bg-light p-2">' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    document.getElementById('api-results').innerHTML = 
                        '<div class="alert alert-danger">Erreur Admin API: ' + error + '</div>';
                });
        }
        
        // Auto-refresh toutes les 30 secondes
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
