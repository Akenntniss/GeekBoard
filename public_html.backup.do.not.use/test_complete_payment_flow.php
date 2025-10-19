<?php
/**
 * Script de test complet du flux de paiement PRODUCTION
 * Crée une vraie session de checkout pour tester tous les webhooks
 */

// Démarrer la session
session_start();

require_once 'config/database.php';
require_once 'classes/StripeManager.php';

header('Content-Type: text/html; charset=utf-8');

// Sécurité
$allowed_ips = ['127.0.0.1', '::1', '82.29.168.205'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['allow'])) {
    $server_access = ($_SERVER['HTTP_HOST'] === '82.29.168.205' || strpos($_SERVER['HTTP_HOST'], 'servo.tools') !== false);
    if (!$server_access) {
        http_response_code(403);
        die('Accès refusé');
    }
}

$action = $_GET['action'] ?? 'display';
$shop_id = $_GET['shop_id'] ?? 94; // Shop de test
$plan_id = $_GET['plan_id'] ?? 2; // Professional par défaut

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Flux Paiement PRODUCTION - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .prod-header { background: linear-gradient(135deg, #dc3545, #6f1d23); color: white; padding: 20px 0; }
        .test-section { background: #f8f9fa; border-left: 4px solid #dc3545; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .webhook-test { background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 10px 0; }
        .webhook-status { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; font-weight: bold; }
        .status-pending { background: #ffc107; color: #000; }
        .status-success { background: #28a745; color: white; }
        .status-error { background: #dc3545; color: white; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="prod-header">
        <div class="container">
            <h1><i class="fa-solid fa-test-tube me-2"></i>TEST FLUX PAIEMENT PRODUCTION</h1>
            <p class="mb-0">⚠️ Attention : Environnement LIVE - Vrais paiements</p>
        </div>
    </div>
    
    <div class="container mt-4">
        
        <?php if ($action === 'create_test_session'): ?>
            
            <div class="test-section">
                <h3>🚀 Création Session de Test PRODUCTION</h3>
                <?php
                try {
                    echo "<p><strong>Méthode 1 : Via StripeManager</strong></p>";
                    
                    // Initialiser la session shop pour la BDD
                    if (function_exists('initializeShopSession')) {
                        initializeShopSession();
                    }
                    
                    // Forcer le shop_id en session si pas détecté automatiquement
                    if (!isset($_SESSION['shop_id'])) {
                        $_SESSION['shop_id'] = $shop_id;
                    }
                    
                    $stripeManager = new StripeManager();
                    
                    // Créer une session de checkout réelle
                    $session = $stripeManager->createCheckoutSession($plan_id, $shop_id, 'test@servo.tools');
                    
                    if (!$session) {
                        echo "<div class='alert alert-warning'>";
                        echo "<p>StripeManager a échoué, tentative avec API directe...</p>";
                        echo "</div>";
                        
                        // Méthode alternative : API directe (même méthode que test_stripe_price_validation.php)
                        require_once 'config/stripe_config.php';
                        global $stripe_config;
                        
                        // Mapper plan_id vers price_id
                        $price_mapping = [
                            1 => 'price_1SA6HQKUpWbkHkw0yD1EABMt', // Starter mensuel
                            2 => 'price_1SA6JnKUpWbkHkw03eKJe9oz', // Professional mensuel
                            3 => 'price_1SA6KUKUpWbkHkw0G9YuEGf7'  // Enterprise mensuel
                        ];
                        
                        $price_id = $price_mapping[$plan_id] ?? $price_mapping[2];
                        
                        echo "<p><strong>Méthode 2 : API Stripe directe</strong></p>";
                        echo "<p>Price ID utilisé: <code>$price_id</code></p>";
                        
                        $session_data = [
                            'payment_method_types' => ['card'],
                            'line_items' => [[
                                'price' => $price_id,
                                'quantity' => 1,
                            ]],
                            'mode' => 'subscription',
                            'success_url' => $stripe_config['success_url'],
                            'cancel_url' => $stripe_config['cancel_url'],
                            'customer_email' => 'test@servo.tools',
                            'metadata' => [
                                'shop_id' => (string)$shop_id,
                                'plan_id' => (string)$plan_id,
                                'test' => 'true'
                            ]
                        ];
                        
                        // Debug: vérifier que la config est chargée
                        if (!isset($stripe_config) || empty($stripe_config['secret_key'])) {
                            echo "<div class='alert alert-danger'>";
                            echo "<p>❌ Configuration Stripe non chargée ou clé secrète manquante</p>";
                            echo "</div>";
                            throw new Exception("Configuration Stripe manquante");
                        }
                        
                        $secret_key = $stripe_config['secret_key'];
                        
                        // Requête cURL pour créer la session
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($session_data));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: Bearer $secret_key",
                            "Content-Type: application/x-www-form-urlencoded"
                        ]);
                        
                        $response = curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($http_code === 200) {
                            $session_data = json_decode($response, true);
                            $session = (object) $session_data; // Convertir en objet pour compatibilité
                        } else {
                            $session = null;
                            echo "<div class='alert alert-danger'>";
                            echo "<h4>❌ Erreur API Stripe</h4>";
                            echo "<p>Code HTTP: $http_code</p>";
                            if ($response) {
                                $error_data = json_decode($response, true);
                                echo "<p>Message: " . ($error_data['error']['message'] ?? 'Erreur inconnue') . "</p>";
                            }
                            echo "</div>";
                        }
                    }
                    
                    if ($session) {
                        echo "<div class='alert alert-success'>";
                        echo "<h4>✅ Session créée avec succès !</h4>";
                        echo "<p><strong>Session ID:</strong> " . $session->id . "</p>";
                        echo "<p><strong>URL de paiement:</strong></p>";
                        echo "<div class='mb-3'>";
                        echo "<a href='" . $session->url . "' target='_blank' class='btn btn-primary btn-lg'>";
                        echo "<i class='fa-solid fa-credit-card me-2'></i>Aller au paiement TEST";
                        echo "</a>";
                        echo "</div>";
                        echo "<p class='text-muted'><strong>Note:</strong> Utilisez une carte de test comme 4242 4242 4242 4242</p>";
                        echo "</div>";
                        
                        echo "<div class='warning-box'>";
                        echo "<h5><i class='fa-solid fa-exclamation-triangle me-2'></i>Événements qui seront déclenchés :</h5>";
                        echo "<ol>";
                        echo "<li><code>checkout.session.completed</code> - Quand le paiement est validé</li>";
                        echo "<li><code>customer.subscription.created</code> - Création de l'abonnement</li>";
                        echo "<li><code>invoice.payment_succeeded</code> - Confirmation du paiement</li>";
                        echo "</ol>";
                        echo "</div>";
                        
                    } else {
                        echo "<div class='alert alert-danger'>";
                        echo "<h4>❌ Erreur lors de la création de la session</h4>";
                        echo "<p>Vérifiez les logs Stripe pour plus de détails</p>";
                        echo "</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>";
                    echo "<h4>❌ Exception</h4>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "</div>";
                }
                ?>
            </div>
            
        <?php elseif ($action === 'webhook_logs'): ?>
            
            <div class="test-section">
                <h3>📋 Logs des Webhooks</h3>
                <?php
                $log_file = __DIR__ . '/logs/stripe_webhook.log';
                
                if (file_exists($log_file)) {
                    $logs = file_get_contents($log_file);
                    $recent_logs = array_slice(array_filter(explode("\n", $logs)), -20); // 20 dernières lignes
                    
                    echo "<div class='alert alert-info'>";
                    echo "<h5>📄 Derniers événements webhook :</h5>";
                    echo "<pre style='max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
                    echo htmlspecialchars(implode("\n", $recent_logs));
                    echo "</pre>";
                    echo "</div>";
                    
                    // Bouton pour rafraîchir
                    echo "<a href='?action=webhook_logs' class='btn btn-outline-primary'>";
                    echo "<i class='fa-solid fa-refresh me-2'></i>Rafraîchir les logs";
                    echo "</a>";
                    
                } else {
                    echo "<div class='alert alert-warning'>";
                    echo "<p>Aucun log webhook trouvé. Les événements apparaîtront ici après les premiers tests.</p>";
                    echo "</div>";
                }
                ?>
            </div>
            
        <?php else: ?>
            
            <!-- Dashboard principal -->
            <div class="test-section">
                <h3><i class="fa-solid fa-clipboard-check me-2"></i>Tests des Événements Webhook</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="webhook-test">
                            <h5><i class="fa-solid fa-shopping-cart me-2"></i>checkout.session.completed</h5>
                            <p class="text-muted">Se déclenche quand un paiement est validé</p>
                            <span class="webhook-status status-pending">En attente de test</span>
                        </div>
                        
                        <div class="webhook-test">
                            <h5><i class="fa-solid fa-user-plus me-2"></i>customer.subscription.created</h5>
                            <p class="text-muted">Se déclenche lors de la création d'un abonnement</p>
                            <span class="webhook-status status-pending">En attente de test</span>
                        </div>
                        
                        <div class="webhook-test">
                            <h5><i class="fa-solid fa-edit me-2"></i>customer.subscription.updated</h5>
                            <p class="text-muted">Se déclenche lors de la modification d'un abonnement</p>
                            <span class="webhook-status status-pending">En attente de test</span>
                        </div>
                        
                        <div class="webhook-test">
                            <h5><i class="fa-solid fa-user-times me-2"></i>customer.subscription.deleted</h5>
                            <p class="text-muted">Se déclenche lors de l'annulation d'un abonnement</p>
                            <span class="webhook-status status-pending">En attente de test</span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="webhook-test">
                            <h5><i class="fa-solid fa-clock me-2"></i>customer.subscription.trial_will_end</h5>
                            <p class="text-muted">Se déclenche 3 jours avant la fin d'essai</p>
                            <span class="webhook-status status-pending">En attente de test</span>
                        </div>
                        
                        <div class="webhook-test">
                            <h5><i class="fa-solid fa-check-circle me-2"></i>invoice.payment_succeeded</h5>
                            <p class="text-muted">Se déclenche quand un paiement réussit</p>
                            <span class="webhook-status status-pending">En attente de test</span>
                        </div>
                        
                        <div class="webhook-test">
                            <h5><i class="fa-solid fa-times-circle me-2"></i>invoice.payment_failed</h5>
                            <p class="text-muted">Se déclenche quand un paiement échoue</p>
                            <span class="webhook-status status-pending">En attente de test</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions de test -->
            <div class="test-section">
                <h3><i class="fa-solid fa-flask me-2"></i>Actions de Test</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>🧪 Test avec Session Réelle</h5>
                        <p>Crée une vraie session de checkout pour tester les événements principaux</p>
                        <div class="mb-3">
                            <label class="form-label">Plan à tester :</label>
                            <select class="form-select" id="test-plan">
                                <option value="1">Starter (39.99€)</option>
                                <option value="2" selected>Professional (49.99€)</option>
                                <option value="3">Enterprise (59.99€)</option>
                            </select>
                        </div>
                        <button onclick="createTestSession()" class="btn btn-warning btn-lg">
                            <i class="fa-solid fa-credit-card me-2"></i>Créer Session de Test
                        </button>
                        <p class="text-muted mt-2"><small>⚠️ Utilisera vos clés LIVE mais avec des cartes de test</small></p>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>📋 Monitoring</h5>
                        <p>Surveiller les événements webhook en temps réel</p>
                        <a href="?action=webhook_logs" class="btn btn-info">
                            <i class="fa-solid fa-file-text me-2"></i>Voir les Logs Webhook
                        </a>
                        <br><br>
                        <a href="https://dashboard.stripe.com/webhooks" target="_blank" class="btn btn-outline-primary">
                            <i class="fa-solid fa-external-link me-2"></i>Stripe Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Guide de test -->
            <div class="test-section">
                <h3><i class="fa-solid fa-book me-2"></i>Guide de Test Complet</h3>
                
                <div class="accordion" id="testGuide">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#testFlow">
                                🔄 Flux de Test Complet
                            </button>
                        </h2>
                        <div id="testFlow" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <ol>
                                    <li><strong>Créer session de test</strong> - Bouton ci-dessus</li>
                                    <li><strong>Aller au checkout</strong> - Utiliser l'URL générée</li>
                                    <li><strong>Payer avec carte test</strong> - 4242 4242 4242 4242</li>
                                    <li><strong>Vérifier webhooks</strong> - Logs + Stripe Dashboard</li>
                                    <li><strong>Tester annulation</strong> - Via Stripe Dashboard</li>
                                    <li><strong>Vérifier BDD</strong> - Tables subscriptions/payment_transactions</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#testCards">
                                💳 Cartes de Test Stripe
                            </button>
                        </h2>
                        <div id="testCards" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <ul>
                                    <li><strong>Succès :</strong> 4242 4242 4242 4242</li>
                                    <li><strong>Échec :</strong> 4000 0000 0000 0002</li>
                                    <li><strong>3D Secure :</strong> 4000 0000 0000 3220</li>
                                    <li><strong>Expiration :</strong> Date future (ex: 12/25)</li>
                                    <li><strong>CVC :</strong> N'importe quel code 3 chiffres</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
        
        <div class="alert alert-danger mt-4">
            <h5><i class="fa-solid fa-exclamation-triangle me-2"></i>IMPORTANT</h5>
            <ul class="mb-0">
                <li>🔴 <strong>Environnement PRODUCTION</strong> - Utilisez uniquement des cartes de test</li>
                <li>📧 <strong>Surveillez vos emails</strong> Stripe pour les notifications</li>
                <li>🔍 <strong>Vérifiez les logs</strong> webhook après chaque test</li>
                <li>🗑️ <strong>Supprimez ce script</strong> après les tests</li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function createTestSession() {
            const planId = document.getElementById('test-plan').value;
            const shopId = <?php echo $shop_id; ?>;
            
            if (confirm('⚠️ Créer une session de test PRODUCTION ?\n\nCela déclenchera de vrais événements webhook.')) {
                window.location.href = `?action=create_test_session&plan_id=${planId}&shop_id=${shopId}`;
            }
        }
        
        // Auto-refresh des logs toutes les 30 secondes si on est sur la page logs
        <?php if ($action === 'webhook_logs'): ?>
        setTimeout(() => {
            window.location.reload();
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
