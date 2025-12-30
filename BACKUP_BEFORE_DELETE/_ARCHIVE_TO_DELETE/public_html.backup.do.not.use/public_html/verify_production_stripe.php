<?php
/**
 * Script de vérification de la configuration Stripe PRODUCTION
 * Vérifie les clés API, produits, prix et synchronise la base de données
 */

header('Content-Type: text/html; charset=utf-8');

// Sécurité renforcée pour la production
$allowed_ips = ['127.0.0.1', '::1', '82.29.168.205'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['allow'])) {
    $server_access = ($_SERVER['HTTP_HOST'] === '82.29.168.205' || strpos($_SERVER['HTTP_HOST'], 'mdgeek.top') !== false);
    if (!$server_access) {
        http_response_code(403);
        die('Accès refusé - Production');
    }
}

require_once 'config/database.php';
$stripe_config = include('config/stripe_config.php');

/**
 * Appel API Stripe direct avec clés PRODUCTION
 */
function callStripeAPI($endpoint, $data = null, $method = 'GET') {
    global $stripe_config;
    
    $url = 'https://api.stripe.com/v1/' . $endpoint;
    $headers = [
        'Authorization: Bearer ' . $stripe_config['secret_key'],
        'Content-Type: application/x-www-form-urlencoded'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Erreur API Stripe: HTTP $httpCode - $response");
    }
    
    return json_decode($response, true);
}

$action = $_GET['action'] ?? 'display';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Vérification Stripe PRODUCTION - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .production-header { background: linear-gradient(135deg, #dc3545, #6f1d23); color: white; padding: 20px 0; }
        .section { background: #f8f9fa; border-left: 4px solid #dc3545; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { border-left-color: #28a745; background: #d4edda; color: #155724; }
        .warning { border-left-color: #ffc107; background: #fff3cd; color: #856404; }
        .error { border-left-color: #dc3545; background: #f8d7da; color: #721c24; }
        .code { background: #f1f1f1; padding: 10px; font-family: monospace; border-radius: 5px; margin: 10px 0; }
        .price-id { font-family: monospace; background: #e9ecef; padding: 3px 6px; border-radius: 3px; }
        .prod-badge { background: #dc3545; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; }
    </style>
</head>
<body>
    <div class="production-header">
        <div class="container">
            <h1><i class="fa-solid fa-shield-halved me-2"></i>VÉRIFICATION STRIPE PRODUCTION</h1>
            <p class="mb-0">Audit complet de la configuration en mode live</p>
        </div>
    </div>
    
    <div class="container mt-4">
        
        <?php if ($action === 'verify_keys'): ?>
            
            <div class="section">
                <h3>🔑 Vérification des Clés API PRODUCTION</h3>
                <?php
                try {
                    // Test de la clé secrète
                    $account = callStripeAPI('account');
                    
                    echo "<div class='success'>";
                    echo "<h4>✅ Clés API Valides</h4>";
                    echo "<p><strong>Compte ID:</strong> " . $account['id'] . "</p>";
                    echo "<p><strong>Nom du compte:</strong> " . ($account['display_name'] ?: $account['business_profile']['name'] ?? 'Non défini') . "</p>";
                    echo "<p><strong>Pays:</strong> " . strtoupper($account['country']) . "</p>";
                    echo "<p><strong>Devise par défaut:</strong> " . strtoupper($account['default_currency']) . "</p>";
                    echo "<p><strong>Paiements activés:</strong> " . ($account['charges_enabled'] ? '✅ Oui' : '❌ Non') . "</p>";
                    echo "<p><strong>Virements activés:</strong> " . ($account['payouts_enabled'] ? '✅ Oui' : '❌ Non') . "</p>";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>";
                    echo "<h4>❌ Erreur de Clés API</h4>";
                    echo "<p>Impossible de se connecter à Stripe avec les clés fournies:</p>";
                    echo "<p><strong>Erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "</div>";
                }
                ?>
            </div>
            
        <?php elseif ($action === 'verify_products'): ?>
            
            <div class="section">
                <h3>📦 Vérification des Produits PRODUCTION</h3>
                <?php
                foreach ($stripe_config['products'] as $name => $product_id) {
                    echo "<h5>Produit: " . ucfirst($name) . " ($product_id)</h5>";
                    
                    try {
                        // Vérifier le produit
                        $product = callStripeAPI("products/$product_id");
                        
                        echo "<div class='success mb-3'>";
                        echo "<p><strong>✅ Produit trouvé:</strong> " . $product['name'] . "</p>";
                        echo "<p><strong>Description:</strong> " . ($product['description'] ?: 'Aucune') . "</p>";
                        echo "<p><strong>Actif:</strong> " . ($product['active'] ? '✅ Oui' : '❌ Non') . "</p>";
                        
                        // Récupérer les prix pour ce produit
                        $prices = callStripeAPI("prices?product=$product_id&active=true");
                        
                        if (!empty($prices['data'])) {
                            echo "<p><strong>Prix configurés:</strong></p>";
                            echo "<ul>";
                            foreach ($prices['data'] as $price) {
                                $amount = $price['unit_amount'] / 100;
                                $interval = $price['recurring']['interval'] ?? 'one-time';
                                echo "<li>";
                                echo "<span class='price-id'>{$price['id']}</span> - ";
                                echo "{$amount}€ / {$interval}";
                                if (isset($price['nickname'])) {
                                    echo " ({$price['nickname']})";
                                }
                                echo "</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p><strong>⚠️ Aucun prix configuré pour ce produit</strong></p>";
                        }
                        
                        echo "</div>";
                        
                    } catch (Exception $e) {
                        echo "<div class='error mb-3'>";
                        echo "<p><strong>❌ Erreur produit:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
            
        <?php elseif ($action === 'sync_database'): ?>
            
            <div class="section">
                <h3>🔄 Synchronisation Base de Données</h3>
                <?php
                try {
                    $pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Mappage des prix selon vos données
                    $price_mapping = [
                        // OFFRE STARTER
                        'price_1SA6HQKUpWbkHkw0yD1EABMt' => ['name' => 'Starter', 'billing_period' => 'monthly', 'price' => 39.99],
                        'price_1SA6IWKUpWbkHkw0f4xIAhD4' => ['name' => 'Starter Annual', 'billing_period' => 'yearly', 'price' => 383.88],
                        
                        // Professional  
                        'price_1SA6JnKUpWbkHkw03eKJe9oz' => ['name' => 'Professional', 'billing_period' => 'monthly', 'price' => 49.99],
                        'price_1SA6JnKUpWbkHkw04DBOO37O' => ['name' => 'Professional Annual', 'billing_period' => 'yearly', 'price' => 479.88],
                        
                        // Enterprise
                        'price_1SA6KUKUpWbkHkw0G9YuEGf7' => ['name' => 'Enterprise', 'billing_period' => 'monthly', 'price' => 59.99],
                        'price_1SA6LpKUpWbkHkw0vOVrPhed' => ['name' => 'Enterprise Annual', 'billing_period' => 'yearly', 'price' => 566.40],
                    ];
                    
                    echo "<h4>Mise à jour des plans d'abonnement...</h4>";
                    
                    foreach ($price_mapping as $stripe_price_id => $plan_data) {
                        // Vérifier si le plan existe
                        $stmt = $pdo->prepare("
                            SELECT id FROM subscription_plans 
                            WHERE name = ? AND billing_period = ?
                        ");
                        $stmt->execute([$plan_data['name'], $plan_data['billing_period']]);
                        $existing = $stmt->fetch();
                        
                        if ($existing) {
                            // Mettre à jour le plan existant
                            $stmt = $pdo->prepare("
                                UPDATE subscription_plans 
                                SET stripe_price_id = ?, price = ?
                                WHERE name = ? AND billing_period = ?
                            ");
                            $stmt->execute([
                                $stripe_price_id,
                                $plan_data['price'],
                                $plan_data['name'],
                                $plan_data['billing_period']
                            ]);
                            
                            echo "<p>✅ Mis à jour: {$plan_data['name']} ({$plan_data['billing_period']}) = <span class='price-id'>$stripe_price_id</span></p>";
                        } else {
                            // Créer le plan
                            $stmt = $pdo->prepare("
                                INSERT INTO subscription_plans 
                                (name, description, price, currency, billing_period, stripe_price_id, active)
                                VALUES (?, ?, ?, 'EUR', ?, ?, 1)
                            ");
                            
                            $description = "Plan " . $plan_data['name'] . " - " . 
                                         ($plan_data['billing_period'] === 'yearly' ? 'Annuel' : 'Mensuel');
                            
                            $stmt->execute([
                                $plan_data['name'],
                                $description,
                                $plan_data['price'],
                                $plan_data['billing_period'],
                                $stripe_price_id
                            ]);
                            
                            echo "<p>✅ Créé: {$plan_data['name']} ({$plan_data['billing_period']}) = <span class='price-id'>$stripe_price_id</span></p>";
                        }
                    }
                    
                    echo "<div class='success mt-3'>";
                    echo "<h4>✅ Synchronisation terminée</h4>";
                    echo "<p>Tous les plans sont maintenant synchronisés avec Stripe PRODUCTION</p>";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>";
                    echo "<h4>❌ Erreur de synchronisation</h4>";
                    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "</div>";
                }
                ?>
            </div>
            
        <?php elseif ($action === 'webhook_test'): ?>
            
            <div class="section">
                <h3>🔗 Test du Webhook PRODUCTION</h3>
                <?php
                echo "<p><strong>URL Webhook configurée:</strong></p>";
                echo "<div class='code'>{$stripe_config['webhook_url']}</div>";
                echo "<p><strong>⚠️ Assurez-vous que cette URL est configurée dans votre Stripe Dashboard</strong></p>";
                
                echo "<p><strong>Secret Webhook:</strong></p>";
                echo "<div class='code'>" . substr($stripe_config['webhook_secret'], 0, 20) . "...</div>";
                
                // Test simple du webhook endpoint
                echo "<h5>Test de l'endpoint:</h5>";
                $webhook_url = $stripe_config['webhook_url'];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $webhook_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $data = json_decode($response, true);
                    if ($data && isset($data['status'])) {
                        echo "<div class='success'>";
                        echo "<p>✅ Webhook endpoint accessible</p>";
                        echo "<p><strong>Réponse:</strong> " . htmlspecialchars($data['status']) . "</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='warning'>";
                        echo "<p>⚠️ Endpoint accessible mais réponse inattendue</p>";
                        echo "<p><strong>Réponse:</strong> " . htmlspecialchars(substr($response, 0, 200)) . "</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='error'>";
                    echo "<p>❌ Webhook endpoint non accessible</p>";
                    echo "<p><strong>Code HTTP:</strong> $httpCode</p>";
                    echo "</div>";
                }
                ?>
            </div>
            
        <?php else: ?>
            
            <!-- Dashboard principal -->
            <div class="section">
                <h3><i class="fa-solid fa-chart-line me-2"></i>État de la Configuration PRODUCTION</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>🔑 Clés API</h5>
                        <p><strong>Publique:</strong> <span class="price-id"><?php echo substr($stripe_config['publishable_key'], 0, 20); ?>...</span> <span class="prod-badge">LIVE</span></p>
                        <p><strong>Secrète:</strong> <span class="price-id"><?php echo substr($stripe_config['secret_key'], 0, 20); ?>...</span> <span class="prod-badge">LIVE</span></p>
                        <p><strong>Webhook:</strong> <span class="price-id"><?php echo substr($stripe_config['webhook_secret'], 0, 20); ?>...</span></p>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>📦 Produits Configurés</h5>
                        <?php foreach ($stripe_config['products'] as $name => $product_id): ?>
                            <p><strong><?php echo ucfirst($name); ?>:</strong> <span class="price-id"><?php echo $product_id; ?></span></p>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <h5>📋 Prix Stripe Attendus</h5>
                <div class="row">
                    <div class="col-md-4">
                        <h6>OFFRE STARTER</h6>
                        <p>• <span class="price-id">price_1SA6HQKUpWbkHkw0yD1EABMt</span> - 39.99€/mois</p>
                        <p>• <span class="price-id">price_1SA6IWKUpWbkHkw0f4xIAhD4</span> - 383.88€/an</p>
                    </div>
                    <div class="col-md-4">
                        <h6>Professional</h6>
                        <p>• <span class="price-id">price_1SA6JnKUpWbkHkw03eKJe9oz</span> - 49.99€/mois</p>
                        <p>• <span class="price-id">price_1SA6JnKUpWbkHkw04DBOO37O</span> - 479.88€/an</p>
                    </div>
                    <div class="col-md-4">
                        <h6>Enterprise</h6>
                        <p>• <span class="price-id">price_1SA6KUKUpWbkHkw0G9YuEGf7</span> - 59.99€/mois</p>
                        <p>• <span class="price-id">price_1SA6LpKUpWbkHkw0vOVrPhed</span> - 566.40€/an</p>
                    </div>
                </div>
            </div>
            
            <!-- Actions de vérification -->
            <div class="section">
                <h3><i class="fa-solid fa-tools me-2"></i>Actions de Vérification</h3>
                <div class="d-grid gap-2 d-md-flex">
                    <a href="?action=verify_keys" class="btn btn-primary">
                        <i class="fa-solid fa-key me-2"></i>Vérifier Clés API
                    </a>
                    <a href="?action=verify_products" class="btn btn-info">
                        <i class="fa-solid fa-box me-2"></i>Vérifier Produits
                    </a>
                    <a href="?action=sync_database" class="btn btn-success">
                        <i class="fa-solid fa-sync me-2"></i>Synchroniser BDD
                    </a>
                    <a href="?action=webhook_test" class="btn btn-warning">
                        <i class="fa-solid fa-link me-2"></i>Tester Webhook
                    </a>
                </div>
            </div>
            
        <?php endif; ?>
        
        <!-- Vérification actuelle BDD -->
        <div class="section">
            <h3><i class="fa-solid fa-database me-2"></i>État Actuel Base de Données</h3>
            <?php
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $pdo->query("
                    SELECT id, name, price, billing_period, stripe_price_id, active 
                    FROM subscription_plans 
                    ORDER BY billing_period, price
                ");
                $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table class='table table-sm'>";
                echo "<thead><tr><th>ID</th><th>Nom</th><th>Prix</th><th>Période</th><th>Stripe Price ID</th><th>Actif</th></tr></thead>";
                echo "<tbody>";
                
                foreach ($plans as $plan) {
                    $price_status = !empty($plan['stripe_price_id']) ? 'success' : 'danger';
                    $price_icon = !empty($plan['stripe_price_id']) ? '✅' : '❌';
                    
                    echo "<tr>";
                    echo "<td>{$plan['id']}</td>";
                    echo "<td>{$plan['name']}</td>";
                    echo "<td>{$plan['price']}€</td>";
                    echo "<td>" . ucfirst($plan['billing_period']) . "</td>";
                    echo "<td>";
                    if (!empty($plan['stripe_price_id'])) {
                        echo "<span class='price-id'>{$plan['stripe_price_id']}</span>";
                    } else {
                        echo "<span class='text-danger'>Non configuré</span>";
                    }
                    echo "</td>";
                    echo "<td><span class='text-$price_status'>$price_icon</span></td>";
                    echo "</tr>";
                }
                
                echo "</tbody></table>";
                
                $configured_count = count(array_filter($plans, function($p) { return !empty($p['stripe_price_id']); }));
                $total_count = count($plans);
                
                if ($configured_count === $total_count) {
                    echo "<div class='success'>";
                    echo "<p>✅ Tous les plans ($configured_count/$total_count) ont leurs Price IDs configurés</p>";
                    echo "</div>";
                } else {
                    echo "<div class='warning'>";
                    echo "<p>⚠️ Seulement $configured_count/$total_count plans configurés</p>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<p>❌ Erreur base de données: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="alert alert-danger mt-4">
            <h5><i class="fa-solid fa-exclamation-triangle me-2"></i>IMPORTANT - PRODUCTION</h5>
            <ul class="mb-0">
                <li>✅ Clés API configurées en mode LIVE</li>
                <li>⚠️ Supprimez ce script après vérification</li>
                <li>🔒 Vérifiez les webhooks dans Stripe Dashboard</li>
                <li>💳 Testez un vrai paiement avec une petite somme</li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
