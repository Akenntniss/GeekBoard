<?php
/**
 * Script de mise à jour automatique des prix PRODUCTION
 * Met à jour la base de données avec les nouveaux prix Stripe PRODUCTION
 */

require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔄 Mise à jour des prix PRODUCTION...\n\n";
    
    // Nouveau mapping des prix PRODUCTION
    $updates = [
        // OFFRE STARTER
        ['name' => 'Starter', 'billing_period' => 'monthly', 'price' => 39.99, 'stripe_price_id' => 'price_1SA6HQKUpWbkHkw0yD1EABMt'],
        ['name' => 'Starter Annual', 'billing_period' => 'yearly', 'price' => 383.88, 'stripe_price_id' => 'price_1SA6IWKUpWbkHkw0f4xIAhD4'],
        
        // Professional
        ['name' => 'Professional', 'billing_period' => 'monthly', 'price' => 49.99, 'stripe_price_id' => 'price_1SA6JnKUpWbkHkw03eKJe9oz'],
        ['name' => 'Professional Annual', 'billing_period' => 'yearly', 'price' => 479.88, 'stripe_price_id' => 'price_1SA6JnKUpWbkHkw04DBOO37O'],
        
        // Enterprise
        ['name' => 'Enterprise', 'billing_period' => 'monthly', 'price' => 59.99, 'stripe_price_id' => 'price_1SA6KUKUpWbkHkw0G9YuEGf7'],
        ['name' => 'Enterprise Annual', 'billing_period' => 'yearly', 'price' => 566.40, 'stripe_price_id' => 'price_1SA6LpKUpWbkHkw0vOVrPhed'],
    ];
    
    foreach ($updates as $update) {
        // Vérifier si le plan existe
        $stmt = $pdo->prepare("
            SELECT id FROM subscription_plans 
            WHERE name = ? AND billing_period = ?
        ");
        $stmt->execute([$update['name'], $update['billing_period']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Mettre à jour le plan existant
            $stmt = $pdo->prepare("
                UPDATE subscription_plans 
                SET stripe_price_id = ?, price = ?
                WHERE name = ? AND billing_period = ?
            ");
            $stmt->execute([
                $update['stripe_price_id'],
                $update['price'],
                $update['name'],
                $update['billing_period']
            ]);
            
            echo "✅ Mis à jour: {$update['name']} ({$update['billing_period']}) = {$update['stripe_price_id']} - {$update['price']}€\n";
        } else {
            // Créer le plan s'il n'existe pas
            $description = "Plan " . $update['name'] . " - " . 
                         ($update['billing_period'] === 'yearly' ? 'Annuel' : 'Mensuel');
            
            $stmt = $pdo->prepare("
                INSERT INTO subscription_plans 
                (name, description, price, currency, billing_period, stripe_price_id, active)
                VALUES (?, ?, ?, 'EUR', ?, ?, 1)
            ");
            $stmt->execute([
                $update['name'],
                $description,
                $update['price'],
                $update['billing_period'],
                $update['stripe_price_id']
            ]);
            
            echo "✅ Créé: {$update['name']} ({$update['billing_period']}) = {$update['stripe_price_id']} - {$update['price']}€\n";
        }
    }
    
    echo "\n🎉 Mise à jour terminée !\n";
    echo "Tous les plans sont maintenant synchronisés avec Stripe PRODUCTION.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?>
