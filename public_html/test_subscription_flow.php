<?php
/**
 * Script de test pour vérifier le flux complet d'abonnement
 * Simule différents scénarios d'abonnement
 */

require_once 'config/database.php';
require_once 'classes/SubscriptionManager.php';

header('Content-Type: text/html; charset=utf-8');

// Vérifier l'accès (supprimer en production)
$allowed_ips = ['127.0.0.1', '::1', '82.29.168.205'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['allow'])) {
    $server_access = ($_SERVER['HTTP_HOST'] === '82.29.168.205' || strpos($_SERVER['HTTP_HOST'], 'mdgeek.top') !== false);
    if (!$server_access) {
        http_response_code(403);
        die('Accès refusé');
    }
}

$subscriptionManager = new SubscriptionManager();
$action = $_GET['action'] ?? 'display';
$shop_id = $_GET['shop_id'] ?? 94;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Flux Abonnement - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .scenario { border-left: 4px solid #007cba; padding: 15px; margin: 15px 0; background: #f8f9fa; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .code { background: #f1f1f1; padding: 10px; font-family: monospace; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>🧪 Test du Flux Complet d'Abonnement</h1>
        
        <div class="row">
            <div class="col-md-8">
                <?php if ($action === 'simulate_expired'): ?>
                    
                    <div class="scenario error">
                        <h3>❌ Simulation : Abonnement Expiré</h3>
                        <?php
                        // Marquer temporairement le shop comme expiré
                        try {
                            $pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            $stmt = $pdo->prepare("
                                UPDATE shops 
                                SET subscription_status = 'expired', 
                                    active = 0,
                                    trial_ends_at = DATE_SUB(NOW(), INTERVAL 1 DAY)
                                WHERE id = ?
                            ");
                            $stmt->execute([$shop_id]);
                            
                            echo "<p>✅ Shop $shop_id marqué comme expiré</p>";
                            echo "<p>🔄 Maintenant, testez l'accès à une page protégée :</p>";
                            echo "<div class='code'>";
                            echo "<a href='example_protected_page.php?shop_id=$shop_id' target='_blank'>";
                            echo "example_protected_page.php?shop_id=$shop_id</a>";
                            echo "</div>";
                            echo "<p><strong>Résultat attendu :</strong> Redirection automatique vers subscription_required.php</p>";
                            
                        } catch (Exception $e) {
                            echo "<p class='text-danger'>Erreur: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                    
                <?php elseif ($action === 'simulate_trial_ending'): ?>
                    
                    <div class="scenario warning">
                        <h3>⚠️ Simulation : Essai bientôt expiré (3 jours)</h3>
                        <?php
                        try {
                            $pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            $stmt = $pdo->prepare("
                                UPDATE shops 
                                SET subscription_status = 'trial', 
                                    active = 1,
                                    trial_ends_at = DATE_ADD(NOW(), INTERVAL 3 DAY)
                                WHERE id = ?
                            ");
                            $stmt->execute([$shop_id]);
                            
                            echo "<p>✅ Shop $shop_id configuré avec essai expirant dans 3 jours</p>";
                            echo "<p>🔄 Testez l'accès à une page protégée :</p>";
                            echo "<div class='code'>";
                            echo "<a href='example_protected_page.php?shop_id=$shop_id' target='_blank'>";
                            echo "example_protected_page.php?shop_id=$shop_id</a>";
                            echo "</div>";
                            echo "<p><strong>Résultat attendu :</strong> Page accessible avec bandeau d'avertissement</p>";
                            
                        } catch (Exception $e) {
                            echo "<p class='text-danger'>Erreur: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                    
                <?php elseif ($action === 'restore_active'): ?>
                    
                    <div class="scenario success">
                        <h3>✅ Restauration : Abonnement Actif</h3>
                        <?php
                        try {
                            $pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            $stmt = $pdo->prepare("
                                UPDATE shops 
                                SET subscription_status = 'active', 
                                    active = 1,
                                    trial_ends_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
                                WHERE id = ?
                            ");
                            $stmt->execute([$shop_id]);
                            
                            echo "<p>✅ Shop $shop_id restauré avec abonnement actif</p>";
                            echo "<p>🔄 Testez l'accès à une page protégée :</p>";
                            echo "<div class='code'>";
                            echo "<a href='example_protected_page.php?shop_id=$shop_id' target='_blank'>";
                            echo "example_protected_page.php?shop_id=$shop_id</a>";
                            echo "</div>";
                            echo "<p><strong>Résultat attendu :</strong> Page accessible sans restriction</p>";
                            
                        } catch (Exception $e) {
                            echo "<p class='text-danger'>Erreur: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                    
                <?php else: ?>
                    
                    <div class="scenario">
                        <h3>📊 État Actuel du Shop <?php echo $shop_id; ?></h3>
                        <?php
                        $status = $subscriptionManager->checkShopSubscriptionStatus($shop_id);
                        
                        if ($status) {
                            echo "<table class='table table-sm'>";
                            echo "<tr><td><strong>Statut:</strong></td><td><span class='badge bg-" . 
                                 ($status['subscription_status'] === 'active' ? 'success' : 
                                  ($status['subscription_status'] === 'trial' ? 'warning' : 'danger')) . "'>" . 
                                 $status['subscription_status'] . "</span></td></tr>";
                            echo "<tr><td><strong>Actif:</strong></td><td>" . ($status['active'] ? '✅ Oui' : '❌ Non') . "</td></tr>";
                            
                            if ($status['days_remaining'] !== null) {
                                echo "<tr><td><strong>Jours restants:</strong></td><td>" . $status['days_remaining'] . "</td></tr>";
                            }
                            
                            if ($status['trial_ends_at']) {
                                echo "<tr><td><strong>Fin d'essai:</strong></td><td>" . date('d/m/Y H:i', strtotime($status['trial_ends_at'])) . "</td></tr>";
                            }
                            
                            echo "</table>";
                            
                            $hasAccess = $subscriptionManager->hasAccess($shop_id);
                            echo "<p><strong>Accès autorisé:</strong> " . ($hasAccess ? '✅ Oui' : '❌ Non') . "</p>";
                            
                        } else {
                            echo "<p class='text-warning'>Aucune information trouvée pour le shop $shop_id</p>";
                        }
                        ?>
                    </div>
                    
                <?php endif; ?>
                
                <div class="scenario">
                    <h3>🔗 Flux de Test Complet</h3>
                    <ol>
                        <li><strong>Page expirée :</strong> <a href="?action=simulate_expired&shop_id=<?php echo $shop_id; ?>">Simuler abonnement expiré</a></li>
                        <li><strong>Test redirection :</strong> Accéder à <a href="example_protected_page.php?shop_id=<?php echo $shop_id; ?>" target="_blank">page protégée</a></li>
                        <li><strong>Page abonnement :</strong> Vérifier <a href="https://mdgeek.top/subscription_required.php?shop_id=<?php echo $shop_id; ?>#plans" target="_blank">subscription_required.php</a></li>
                        <li><strong>Page checkout :</strong> Tester <a href="https://mdgeek.top/checkout.php?plan=2&shop=<?php echo $shop_id; ?>" target="_blank">checkout.php</a></li>
                        <li><strong>Restaurer :</strong> <a href="?action=restore_active&shop_id=<?php echo $shop_id; ?>">Remettre abonnement actif</a></li>
                    </ol>
                </div>
                
                <div class="scenario">
                    <h3>⚠️ Test Avertissement Essai</h3>
                    <p><a href="?action=simulate_trial_ending&shop_id=<?php echo $shop_id; ?>" class="btn btn-warning">Simuler essai expirant dans 3 jours</a></p>
                </div>
                
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>🛠️ URLs de Test</h5>
                    </div>
                    <div class="card-body">
                        <h6>Pages principales :</h6>
                        <ul class="list-unstyled">
                            <li>• <a href="subscription_required.php?shop_id=<?php echo $shop_id; ?>" target="_blank">subscription_required.php</a></li>
                            <li>• <a href="checkout.php?plan=2&shop=<?php echo $shop_id; ?>" target="_blank">checkout.php</a></li>
                            <li>• <a href="example_protected_page.php?shop_id=<?php echo $shop_id; ?>" target="_blank">example_protected_page.php</a></li>
                        </ul>
                        
                        <h6>Configuration :</h6>
                        <ul class="list-unstyled">
                            <li>• <a href="check_stripe_setup.php" target="_blank">Diagnostic Stripe</a></li>
                            <li>• <a href="webhook_events_documentation.php" target="_blank">Doc Webhooks</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>📝 Notes</h5>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>Shop ID de test :</strong> <?php echo $shop_id; ?><br>
                            <strong>Middleware :</strong> subscription_redirect_middleware.php<br>
                            <strong>Base de données :</strong> geekboard_general
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
