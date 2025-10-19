<?php
/**
 * Script de test pour vérifier l'intégration du middleware
 * sur les pages Accueil, Réparations et Paramètres
 */

require_once 'config/database.php';
require_once 'classes/SubscriptionManager.php';

header('Content-Type: text/html; charset=utf-8');

// Vérifier l'accès
$allowed_ips = ['127.0.0.1', '::1', '82.29.168.205'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['allow'])) {
    $server_access = ($_SERVER['HTTP_HOST'] === '82.29.168.205' || strpos($_SERVER['HTTP_HOST'], 'mdgeek.top') !== false);
    if (!$server_access) {
        http_response_code(403);
        die('Accès refusé');
    }
}

$action = $_GET['action'] ?? 'display';
$shop_id = $_GET['shop_id'] ?? 94;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Intégration Pages - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .test-section { border-left: 4px solid #007cba; padding: 20px; margin: 20px 0; background: #f8f9fa; border-radius: 8px; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .page-card { background: white; border-radius: 10px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; font-weight: bold; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .test-url { background: #f1f1f1; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1><i class="fa-solid fa-test-tube me-2"></i>Test d'Intégration Middleware - Pages Principales</h1>
        
        <div class="row">
            <div class="col-md-8">
                
                <?php if ($action === 'simulate_expired'): ?>
                    <div class="test-section error">
                        <h3>❌ Test : Abonnement Expiré</h3>
                        <?php
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
                            
                            echo "<p>✅ Shop $shop_id configuré avec abonnement expiré</p>";
                            echo "<p><strong>🧪 Maintenant, testez l'accès aux pages protégées :</strong></p>";
                            
                        } catch (Exception $e) {
                            echo "<p class='text-danger'>Erreur: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                    
                <?php elseif ($action === 'simulate_trial_ending'): ?>
                    <div class="test-section warning">
                        <h3>⚠️ Test : Essai Expirant Bientôt</h3>
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
                            echo "<p><strong>🧪 Testez maintenant - un bandeau d'avertissement devrait apparaître :</strong></p>";
                            
                        } catch (Exception $e) {
                            echo "<p class='text-danger'>Erreur: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                    
                <?php elseif ($action === 'restore_active'): ?>
                    <div class="test-section success">
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
                            echo "<p><strong>🧪 Les pages devraient maintenant être accessibles normalement :</strong></p>";
                            
                        } catch (Exception $e) {
                            echo "<p class='text-danger'>Erreur: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tests des pages principales -->
                <div class="test-section">
                    <h3><i class="fa-solid fa-pages me-2"></i>Pages Intégrées avec Middleware</h3>
                    
                    <div class="page-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fa-solid fa-home me-2"></i>Page Accueil</h5>
                            <span class="status-badge status-ok">✅ Intégré</span>
                        </div>
                        <p><strong>Fichier :</strong> <code>pages/accueil.php</code></p>
                        <p><strong>Middleware :</strong> Vérifie l'abonnement + affiche bandeau d'avertissement</p>
                        <div class="test-url">
                            🔗 Test : <a href="../index.php?page=accueil&shop_id=<?php echo $shop_id; ?>" target="_blank">
                                ../index.php?page=accueil&shop_id=<?php echo $shop_id; ?>
                            </a>
                        </div>
                        <small class="text-muted">
                            <strong>Résultat attendu :</strong> 
                            Si expiré → redirection vers subscription_required.php | 
                            Si essai proche → bandeau d'avertissement
                        </small>
                    </div>
                    
                    <div class="page-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fa-solid fa-wrench me-2"></i>Page Réparations</h5>
                            <span class="status-badge status-ok">✅ Intégré</span>
                        </div>
                        <p><strong>Fichier :</strong> <code>pages/reparations.php</code></p>
                        <p><strong>Middleware :</strong> Vérifie l'abonnement + affiche bandeau d'avertissement</p>
                        <div class="test-url">
                            🔗 Test : <a href="../index.php?page=reparations&shop_id=<?php echo $shop_id; ?>" target="_blank">
                                ../index.php?page=reparations&shop_id=<?php echo $shop_id; ?>
                            </a>
                        </div>
                        <small class="text-muted">
                            <strong>Résultat attendu :</strong> 
                            Même comportement que la page d'accueil
                        </small>
                    </div>
                    
                    <div class="page-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fa-solid fa-cogs me-2"></i>Page Paramètres</h5>
                            <span class="status-badge status-ok">✅ Intégré</span>
                        </div>
                        <p><strong>Fichier :</strong> <code>pages/parametre.php</code></p>
                        <p><strong>Middleware :</strong> Vérifie l'abonnement + affiche bandeau d'avertissement</p>
                        <div class="test-url">
                            🔗 Test : <a href="../index.php?page=parametre&shop_id=<?php echo $shop_id; ?>" target="_blank">
                                ../index.php?page=parametre&shop_id=<?php echo $shop_id; ?>
                            </a>
                        </div>
                        <small class="text-muted">
                            <strong>Résultat attendu :</strong> 
                            Même comportement que les autres pages
                        </small>
                    </div>
                </div>
                
                <!-- Statut actuel -->
                <div class="test-section">
                    <h3><i class="fa-solid fa-info-circle me-2"></i>Statut Actuel du Shop <?php echo $shop_id; ?></h3>
                    <?php
                    $subscriptionManager = new SubscriptionManager();
                    $status = $subscriptionManager->checkShopSubscriptionStatus($shop_id);
                    
                    if ($status) {
                        echo "<div class='row'>";
                        echo "<div class='col-md-6'>";
                        echo "<p><strong>Statut :</strong> <span class='badge bg-" . 
                             ($status['subscription_status'] === 'active' ? 'success' : 
                              ($status['subscription_status'] === 'trial' ? 'warning' : 'danger')) . "'>" . 
                             htmlspecialchars($status['subscription_status']) . "</span></p>";
                        echo "<p><strong>Actif :</strong> " . ($status['active'] ? '✅ Oui' : '❌ Non') . "</p>";
                        echo "</div>";
                        echo "<div class='col-md-6'>";
                        
                        if ($status['days_remaining'] !== null) {
                            echo "<p><strong>Jours restants :</strong> " . $status['days_remaining'] . "</p>";
                        }
                        
                        if ($status['trial_ends_at']) {
                            echo "<p><strong>Fin d'essai :</strong> " . date('d/m/Y H:i', strtotime($status['trial_ends_at'])) . "</p>";
                        }
                        echo "</div>";
                        echo "</div>";
                        
                        $hasAccess = $subscriptionManager->hasAccess($shop_id);
                        $accessClass = $hasAccess ? 'success' : 'danger';
                        $accessIcon = $hasAccess ? '✅' : '❌';
                        echo "<div class='alert alert-$accessClass'>";
                        echo "<strong>$accessIcon Accès autorisé :</strong> " . ($hasAccess ? 'OUI' : 'NON');
                        if (!$hasAccess) {
                            echo "<br><small>Les pages protégées redirigeront vers subscription_required.php</small>";
                        }
                        echo "</div>";
                        
                    } else {
                        echo "<p class='text-warning'>Aucune information trouvée pour le shop $shop_id</p>";
                    }
                    ?>
                </div>
                
                <!-- Actions de test -->
                <div class="test-section">
                    <h3><i class="fa-solid fa-flask me-2"></i>Actions de Test</h3>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="?action=simulate_expired&shop_id=<?php echo $shop_id; ?>" 
                           class="btn btn-danger">
                            <i class="fa-solid fa-times-circle me-2"></i>Simuler Expiré
                        </a>
                        <a href="?action=simulate_trial_ending&shop_id=<?php echo $shop_id; ?>" 
                           class="btn btn-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>Simuler Fin Proche
                        </a>
                        <a href="?action=restore_active&shop_id=<?php echo $shop_id; ?>" 
                           class="btn btn-success">
                            <i class="fa-solid fa-check-circle me-2"></i>Restaurer Actif
                        </a>
                    </div>
                </div>
                
            </div>
            
            <!-- Sidebar avec infos utiles -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fa-solid fa-info me-2"></i>Informations</h5>
                    </div>
                    <div class="card-body">
                        <h6>Shop de test :</h6>
                        <p><strong><?php echo $shop_id; ?></strong></p>
                        
                        <h6>Middleware ajouté dans :</h6>
                        <ul class="list-unstyled">
                            <li>✅ pages/accueil.php</li>
                            <li>✅ pages/reparations.php</li>
                            <li>✅ pages/parametre.php</li>
                        </ul>
                        
                        <h6>Fonctionnalités :</h6>
                        <ul class="list-unstyled">
                            <li>🔒 Vérification automatique</li>
                            <li>↗️ Redirection si expiré</li>
                            <li>⚠️ Bandeau d'avertissement</li>
                            <li>📱 Support AJAX</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fa-solid fa-link me-2"></i>Liens Utiles</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><a href="test_subscription_flow.php" target="_blank">Test Flux Complet</a></li>
                            <li><a href="check_stripe_setup.php" target="_blank">Diagnostic Stripe</a></li>
                            <li><a href="example_protected_page.php" target="_blank">Exemple Page Protégée</a></li>
                            <li><a href="webhook_events_documentation.php" target="_blank">Doc Webhooks</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
