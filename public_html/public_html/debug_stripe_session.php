<?php
/**
 * Script de diagnostic pour identifier les problèmes de création de session Stripe
 */

// Headers pour debug
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic Stripe Session</h1>";

// 1. Vérifier la configuration Stripe
echo "<h2>1. Configuration Stripe</h2>";
if (file_exists('config/stripe_config.php')) {
    require_once 'config/stripe_config.php';
    
    global $stripe_config;
    
    // Masquer les clés sensibles
    $config_debug = $stripe_config;
    if (isset($config_debug['secret_key'])) {
        $config_debug['secret_key'] = substr($config_debug['secret_key'], 0, 12) . '...';
    }
    if (isset($config_debug['webhook_secret'])) {
        $config_debug['webhook_secret'] = substr($config_debug['webhook_secret'], 0, 12) . '...';
    }
    
    echo "<pre>" . print_r($config_debug, true) . "</pre>";
    echo "✅ Fichier de configuration trouvé<br>";
} else {
    echo "❌ Fichier config/stripe_config.php non trouvé<br>";
}

// 2. Vérifier les classes
echo "<h2>2. Classes PHP</h2>";
if (file_exists('classes/StripeManager.php')) {
    echo "✅ StripeManager.php trouvé<br>";
    require_once 'classes/StripeManager.php';
    
    try {
        $stripe = new StripeManager();
        echo "✅ StripeManager instancié<br>";
    } catch (Exception $e) {
        echo "❌ Erreur StripeManager: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ classes/StripeManager.php non trouvé<br>";
}

// 3. Vérifier la base de données
echo "<h2>3. Base de données</h2>";
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
    echo "✅ Database config trouvé<br>";
    
    try {
        // Initialiser la session shop pour la connexion BDD
        if (function_exists('initializeShopSession')) {
            initializeShopSession();
            echo "✅ Session shop initialisée<br>";
        }
        
        $pdo = getShopDBConnection();
        echo "✅ Connexion BDD réussie<br>";
        
        // Vérifier les tables
        $tables_needed = ['subscription_plans', 'subscriptions', 'shops'];
        foreach ($tables_needed as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table $table existe<br>";
            } else {
                echo "❌ Table $table manquante<br>";
            }
        }
        
        // Vérifier les plans
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscription_plans");
        $count = $stmt->fetch()['count'];
        echo "📊 Plans d'abonnement: $count<br>";
        
    } catch (Exception $e) {
        echo "❌ Erreur BDD: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config/database.php non trouvé<br>";
}

// 4. Test de création de session simple
echo "<h2>4. Test Création Session</h2>";

if (isset($stripe) && isset($pdo)) {
    echo "<h3>Tentative de création session...</h3>";
    
    try {
        // Paramètres de test
        $plan_id = 2; // Professional
        $shop_id = 94;
        $email = 'test@servo.tools';
        
        echo "Paramètres:<br>";
        echo "- Plan ID: $plan_id<br>";
        echo "- Shop ID: $shop_id<br>";
        echo "- Email: $email<br><br>";
        
        // Vérifier le plan
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();
        
        if ($plan) {
            echo "✅ Plan trouvé: " . $plan['name'] . "<br>";
            echo "- Prix: " . $plan['price'] . "€<br>";
            echo "- Stripe Price ID: " . ($plan['stripe_price_id'] ?: 'MANQUANT') . "<br><br>";
            
            if (empty($plan['stripe_price_id'])) {
                echo "❌ PROBLÈME: stripe_price_id manquant dans le plan<br>";
            } else {
                echo "Tentative de création session Stripe...<br>";
                
                // Essayer de créer la session
                $session = $stripe->createCheckoutSession($plan_id, $shop_id, $email);
                
                if ($session) {
                    echo "✅ Session créée avec succès !<br>";
                    echo "Session ID: " . $session->id . "<br>";
                    echo "URL: " . $session->url . "<br>";
                } else {
                    echo "❌ Échec de création de session (retour null)<br>";
                }
            }
            
        } else {
            echo "❌ Plan $plan_id non trouvé en base<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Exception lors de la création: " . $e->getMessage() . "<br>";
        echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// 5. Vérifier les Price IDs Stripe
echo "<h2>5. Vérification Price IDs</h2>";

if (isset($stripe_config)) {
    echo "Price IDs configurés:<br>";
    if (isset($stripe_config['prices'])) {
        foreach ($stripe_config['prices'] as $plan => $prices) {
            echo "<strong>$plan:</strong><br>";
            foreach ($prices as $period => $price_id) {
                echo "- $period: $price_id<br>";
            }
        }
    }
}

// 6. Logs récents
echo "<h2>6. Logs Récents</h2>";
$log_file = __DIR__ . '/logs/stripe_webhook.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $recent_logs = array_slice(array_filter(explode("\n", $logs)), -10);
    echo "<pre>" . implode("\n", $recent_logs) . "</pre>";
} else {
    echo "Aucun log webhook trouvé<br>";
}

echo "<hr>";
echo "<p><strong>🔧 Actions recommandées :</strong></p>";
echo "<ul>";
echo "<li>Vérifiez que les Price IDs sont bien synchronisés</li>";
echo "<li>Testez avec des paramètres différents</li>";
echo "<li>Consultez les logs d'erreur détaillés</li>";
echo "</ul>";
?>
