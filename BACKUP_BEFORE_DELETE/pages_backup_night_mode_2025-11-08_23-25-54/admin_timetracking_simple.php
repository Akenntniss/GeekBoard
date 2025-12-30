<?php
/**
 * Version simplifiée de l'interface admin pour diagnostic
 */

// Démarrer la session d'abord
session_start();

// Configuration de base minimale
try {
    require_once __DIR__ . '/../config/database.php';
    $shop_pdo = getShopDBConnection();
} catch (Exception $e) {
    die("Erreur connexion DB: " . $e->getMessage());
}

// Vérification utilisateur (simulation pour test)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Simulation pour test
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
}

$current_user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['user_role'] === 'admin';

// Récupérer les utilisateurs actifs
$active_users = [];
try {
    $stmt = $shop_pdo->prepare("
        SELECT tt.*, u.full_name, u.username, u.role,
               TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) / 60.0 as current_duration
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

// Statistiques rapides
$stats = ['currently_working' => 0, 'on_break' => 0, 'total_sessions' => 0];
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
            SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break,
            COUNT(*) as total_sessions
        FROM time_tracking
        WHERE DATE(clock_in) = CURDATE()
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats_error = "Erreur stats: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Pointage - Version Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge { font-size: 0.8em; }
        .active-user-card { border-left: 4px solid #28a745; }
        .break-user-card { border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-clock text-primary"></i> Administration Pointage - TEST</h1>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($stats_error)): ?>
                <div class="alert alert-warning"><?php echo $stats_error; ?></div>
                <?php endif; ?>
                
                <!-- Test de la table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Test Table time_tracking</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stmt = $shop_pdo->query("SHOW TABLES LIKE 'time_tracking'");
                            if ($stmt->rowCount() > 0) {
                                echo "✅ Table time_tracking existe<br>";
                                
                                $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM time_tracking");
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo "📊 Nombre d'entrées: " . $result['count'] . "<br>";
                                
                                // Dernières entrées
                                $stmt = $shop_pdo->query("SELECT * FROM time_tracking ORDER BY created_at DESC LIMIT 3");
                                $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                echo "<h5>Dernières entrées :</h5>";
                                foreach ($recent as $entry) {
                                    echo "- ID: {$entry['id']}, User: {$entry['user_id']}, Status: {$entry['status']}, Clock-in: {$entry['clock_in']}<br>";
                                }
                            } else {
                                echo "❌ Table time_tracking n'existe pas";
                            }
                        } catch (Exception $e) {
                            echo "❌ Erreur: " . $e->getMessage();
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h4 class="text-success"><?php echo $stats['currently_working'] ?? 0; ?></h4>
                                <small>En cours</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h4 class="text-warning"><?php echo $stats['on_break'] ?? 0; ?></h4>
                                <small>En pause</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h4 class="text-info"><?php echo $stats['total_sessions'] ?? 0; ?></h4>
                                <small>Sessions aujourd'hui</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <button class="btn btn-primary" onclick="location.reload()">
                                    <i class="fas fa-sync"></i> Actualiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Utilisateurs actifs -->
                <?php if (!empty($active_users)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users text-success"></i> Employés actuellement pointés (<?php echo count($active_users); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($active_users as $user): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card <?php echo $user['status'] === 'break' ? 'break-user-card' : 'active-user-card'; ?>">
                                    <div class="card-body">
                                        <h6><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
                                        <div class="mt-2">
                                            <span class="badge bg-<?php echo $user['status'] === 'break' ? 'warning' : 'success'; ?>">
                                                <?php echo $user['status'] === 'break' ? 'Pause' : 'Actif'; ?>
                                            </span>
                                        </div>
                                        <div class="mt-2">
                                            <strong>Durée: <?php echo number_format($user['current_duration'], 1); ?>h</strong><br>
                                            <small>Début: <?php echo date('H:i', strtotime($user['clock_in'])); ?></small>
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
                
                <!-- Test API -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Test API</h3>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary" onclick="testAPI()">Test API Get Status</button>
                        <button class="btn btn-success" onclick="testAdminAPI()">Test Admin Get Active</button>
                        <div id="api-results" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function testAPI() {
            fetch('/time_tracking_api.php?action=get_status')
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
            fetch('/time_tracking_api.php?action=admin_get_active')
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
        
        // Auto-refresh toutes les 30 secondes
        setTimeout(() => location.reload(), 30000);
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
