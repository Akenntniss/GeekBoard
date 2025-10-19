<?php
/**
 * Script de configuration Stripe pour GeekBoard
 * À exécuter une seule fois pour récupérer les Price IDs
 */

// Configuration temporaire sans SDK
$stripe_config = include(__DIR__ . '/config/stripe_config.php');

/**
 * Appel API Stripe direct sans SDK
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

/**
 * Récupérer tous les prix d'un produit
 */
function getProductPrices($product_id) {
    try {
        $response = callStripeAPI("prices?product=$product_id&active=true");
        return $response['data'] ?? [];
    } catch (Exception $e) {
        echo "Erreur récupération prix pour $product_id: " . $e->getMessage() . "\n";
        return [];
    }
}

/**
 * Créer des prix manquants
 */
function createMissingPrices() {
    global $stripe_config;
    
    // Prix à créer selon vos plans GeekBoard
    $plans_to_create = [
        [
            'product' => $stripe_config['products']['starter'],
            'unit_amount' => 2999, // 29.99€
            'currency' => 'eur',
            'recurring' => ['interval' => 'month'],
            'nickname' => 'Starter Monthly'
        ],
        [
            'product' => $stripe_config['products']['starter'],
            'unit_amount' => 29999, // 299.99€
            'currency' => 'eur', 
            'recurring' => ['interval' => 'year'],
            'nickname' => 'Starter Annual'
        ],
        [
            'product' => $stripe_config['products']['professional'],
            'unit_amount' => 5999, // 59.99€
            'currency' => 'eur',
            'recurring' => ['interval' => 'month'],
            'nickname' => 'Professional Monthly'
        ],
        [
            'product' => $stripe_config['products']['professional'],
            'unit_amount' => 59999, // 599.99€
            'currency' => 'eur',
            'recurring' => ['interval' => 'year'],
            'nickname' => 'Professional Annual'
        ],
        [
            'product' => $stripe_config['products']['enterprise'],
            'unit_amount' => 14999, // 149.99€
            'currency' => 'eur',
            'recurring' => ['interval' => 'month'],
            'nickname' => 'Enterprise Monthly'
        ]
    ];
    
    foreach ($plans_to_create as $plan) {
        try {
            $response = callStripeAPI('prices', $plan, 'POST');
            echo "Prix créé: {$response['id']} - {$plan['nickname']}\n";
        } catch (Exception $e) {
            echo "Erreur création prix {$plan['nickname']}: " . $e->getMessage() . "\n";
        }
    }
}

// Interface web pour l'exécution
if (isset($_GET['action'])) {
    header('Content-Type: text/plain');
    
    switch ($_GET['action']) {
        case 'list_prices':
            echo "=== RÉCUPÉRATION DES PRIX EXISTANTS ===\n\n";
            
            foreach ($stripe_config['products'] as $plan_name => $product_id) {
                echo "Produit: $plan_name ($product_id)\n";
                $prices = getProductPrices($product_id);
                
                if (empty($prices)) {
                    echo "  Aucun prix trouvé\n";
                } else {
                    foreach ($prices as $price) {
                        $amount = $price['unit_amount'] / 100;
                        $interval = $price['recurring']['interval'] ?? 'one-time';
                        echo "  - {$price['id']}: {$amount}€ / {$interval}\n";
                        echo "    Actif: " . ($price['active'] ? 'Oui' : 'Non') . "\n";
                        if (isset($price['nickname'])) {
                            echo "    Nom: {$price['nickname']}\n";
                        }
                    }
                }
                echo "\n";
            }
            break;
            
        case 'create_prices':
            echo "=== CRÉATION DES PRIX MANQUANTS ===\n\n";
            createMissingPrices();
            break;
            
        case 'update_database':
            echo "=== MISE À JOUR BASE DE DONNÉES ===\n\n";
            
            // Connexion à la base
            $pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $mapping = [
                'starter_monthly' => 'Starter',
                'starter_yearly' => 'Starter Annual', 
                'professional_monthly' => 'Professional',
                'professional_yearly' => 'Professional Annual',
                'enterprise_monthly' => 'Enterprise'
            ];
            
            foreach ($stripe_config['products'] as $plan_name => $product_id) {
                $prices = getProductPrices($product_id);
                
                foreach ($prices as $price) {
                    $billing_period = $price['recurring']['interval'] === 'year' ? 'yearly' : 'monthly';
                    $key = $plan_name . '_' . ($billing_period === 'yearly' ? 'yearly' : 'monthly');
                    
                    if (isset($mapping[$key])) {
                        $geekboard_name = $mapping[$key];
                        $amount = $price['unit_amount'] / 100;
                        
                        $stmt = $pdo->prepare("
                            UPDATE subscription_plans 
                            SET stripe_price_id = ?, price = ?
                            WHERE name = ? AND billing_period = ?
                        ");
                        $result = $stmt->execute([
                            $price['id'],
                            $amount,
                            $geekboard_name, 
                            $billing_period
                        ]);
                        
                        if ($result && $stmt->rowCount() > 0) {
                            echo "✅ Mis à jour: $geekboard_name ($billing_period) = {$price['id']} ({$amount}€)\n";
                        } else {
                            echo "❌ Pas de mise à jour pour: $geekboard_name ($billing_period)\n";
                        }
                    }
                }
            }
            break;
            
        default:
            echo "Action non reconnue\n";
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Configuration Stripe - GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #005a87; }
        .info { background: #f0f8ff; padding: 15px; border-left: 4px solid #007cba; margin: 20px 0; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
        pre { background: #f5f5f5; padding: 15px; overflow-x: auto; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Configuration Stripe - GeekBoard</h1>
    
    <div class="info">
        <strong>Clés configurées:</strong><br>
        Publique: <?php echo substr($stripe_config['publishable_key'], 0, 20); ?>...<br>
        Privée: <?php echo substr($stripe_config['secret_key'], 0, 20); ?>...<br>
        Environnement: <?php echo $stripe_config['environment']; ?>
    </div>
    
    <div class="warning">
        <strong>⚠️ Important:</strong> Ce script est temporaire et doit être supprimé après configuration.
    </div>
    
    <h2>Étapes de configuration:</h2>
    
    <h3>1. Vérifier les prix existants</h3>
    <p>Voir quels prix sont déjà créés dans vos produits Stripe.</p>
    <a href="?action=list_prices" class="btn">📋 Lister les prix</a>
    
    <h3>2. Créer les prix manquants</h3>
    <p>Créer automatiquement tous les prix nécessaires selon vos plans GeekBoard.</p>
    <a href="?action=create_prices" class="btn">➕ Créer les prix</a>
    
    <h3>3. Mettre à jour la base de données</h3>
    <p>Synchroniser les Price IDs Stripe avec votre table subscription_plans.</p>
    <a href="?action=update_database" class="btn">🔄 Mettre à jour BDD</a>
    
    <h2>Produits configurés:</h2>
    <pre><?php print_r($stripe_config['products']); ?></pre>
    
    <div class="info">
        <strong>Prochaines étapes après cette configuration:</strong><br>
        1. Installer le SDK Stripe (composer require stripe/stripe-php)<br>
        2. Configurer les webhooks<br>
        3. Tester le processus de paiement<br>
        4. Supprimer ce fichier de configuration
    </div>
</body>
</html>
