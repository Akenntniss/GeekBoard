<?php
/**
 * Script de vérification de la configuration Stripe dans la base de données
 * Vérifie les Price IDs et la configuration des plans d'abonnement
 */

// Sécurité simple
$allowed_ips = ['127.0.0.1', '::1', '82.29.168.205'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['allow'])) {
    // Permettre l'accès depuis le serveur ou en local
    $server_access = ($_SERVER['HTTP_HOST'] === '82.29.168.205' || strpos($_SERVER['HTTP_HOST'], 'mdgeek.top') !== false);
    if (!$server_access) {
        http_response_code(403);
        die('Accès refusé');
    }
}

header('Content-Type: text/html; charset=utf-8');

try {
    // Connexion à la base principale
    $pdo = new PDO("mysql:host=localhost;dbname=geekboard_general;charset=utf8", 'root', 'Mamanmaman01#');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configuration Stripe
    $stripe_config = include('config/stripe_config.php');
    
} catch (Exception $e) {
    die("Erreur connexion: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vérification Configuration Stripe - GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 50px auto; padding: 20px; }
        .section { background: #f8f9fa; border-left: 4px solid #007cba; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .error { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f2f2f2; font-weight: bold; }
        .price-id { font-family: monospace; background: #f1f1f1; padding: 3px 6px; border-radius: 3px; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-missing { color: #dc3545; font-weight: bold; }
        .status-partial { color: #ffc107; font-weight: bold; }
        .code { background: #f1f1f1; padding: 10px; font-family: monospace; border-radius: 5px; margin: 10px 0; }
        .actions { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic Configuration Stripe - GeekBoard</h1>
    
    <div class="section">
        <h2>📋 Configuration générale</h2>
        <p><strong>Environnement :</strong> <?php echo $stripe_config['environment']; ?></p>
        <p><strong>Clé publique :</strong> <?php echo substr($stripe_config['publishable_key'], 0, 25) . '...'; ?></p>
        <p><strong>Webhook secret :</strong> <?php echo !empty($stripe_config['webhook_secret']) ? '✅ Configuré' : '❌ Manquant'; ?></p>
        <p><strong>URL Webhook :</strong> <?php echo $stripe_config['webhook_url']; ?></p>
    </div>

    <?php
    // Vérifier la table subscription_plans
    try {
        $stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY id");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($plans)) {
            echo '<div class="section error">';
            echo '<h2>❌ Erreur : Aucun plan d\'abonnement trouvé</h2>';
            echo '<p>La table subscription_plans est vide. Vous devez d\'abord exécuter le script de création des plans.</p>';
            echo '</div>';
        } else {
            // Analyser les plans
            $plans_with_price_ids = 0;
            $total_plans = count($plans);
            
            echo '<div class="section">';
            echo '<h2>📊 Plans d\'abonnement dans la base de données</h2>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Nom</th><th>Prix</th><th>Période</th><th>Stripe Price ID</th><th>Statut</th></tr>';
            
            foreach ($plans as $plan) {
                $has_price_id = !empty($plan['stripe_price_id']);
                if ($has_price_id) $plans_with_price_ids++;
                
                echo '<tr>';
                echo '<td>' . $plan['id'] . '</td>';
                echo '<td>' . htmlspecialchars($plan['name']) . '</td>';
                echo '<td>' . number_format($plan['price'], 2) . ' ' . strtoupper($plan['currency']) . '</td>';
                echo '<td>' . ucfirst($plan['billing_period']) . '</td>';
                echo '<td>';
                if ($has_price_id) {
                    echo '<span class="price-id">' . htmlspecialchars($plan['stripe_price_id']) . '</span>';
                } else {
                    echo '<span class="status-missing">❌ Manquant</span>';
                }
                echo '</td>';
                echo '<td>';
                if ($has_price_id) {
                    echo '<span class="status-ok">✅ Configuré</span>';
                } else {
                    echo '<span class="status-missing">❌ À configurer</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            echo '</div>';
            
            // Résumé de l'état
            if ($plans_with_price_ids === $total_plans) {
                echo '<div class="section success">';
                echo '<h2>✅ Configuration complète</h2>';
                echo "<p>Parfait ! Tous les plans ({$total_plans}/{$total_plans}) ont leurs Price IDs Stripe configurés.</p>";
                echo '<p>Votre système de paiement est prêt à fonctionner !</p>';
                echo '</div>';
            } elseif ($plans_with_price_ids > 0) {
                echo '<div class="section warning">';
                echo '<h2>⚠️ Configuration partielle</h2>';
                echo "<p>Seulement {$plans_with_price_ids}/{$total_plans} plans ont leurs Price IDs configurés.</p>";
                echo '<p>Vous devez compléter la configuration pour les plans manquants.</p>';
                echo '</div>';
            } else {
                echo '<div class="section error">';
                echo '<h2>❌ Configuration manquante</h2>';
                echo "<p>Aucun plan n'a de Price ID Stripe configuré ({$plans_with_price_ids}/{$total_plans}).</p>";
                echo '<p>Vous devez utiliser le script de configuration pour créer les prix dans Stripe.</p>';
                echo '</div>';
            }
        }
        
    } catch (Exception $e) {
        echo '<div class="section error">';
        echo '<h2>❌ Erreur base de données</h2>';
        echo '<p>Impossible de lire la table subscription_plans : ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>

    <?php
    // Vérifier la correspondance avec les produits Stripe configurés
    echo '<div class="section">';
    echo '<h2>🔗 Correspondance Produits Stripe</h2>';
    echo '<table>';
    echo '<tr><th>Plan GeekBoard</th><th>Produit Stripe</th><th>Statut</th></tr>';
    
    $stripe_products = $stripe_config['products'];
    foreach ($stripe_products as $key => $product_id) {
        echo '<tr>';
        echo '<td>' . ucfirst($key) . '</td>';
        echo '<td><span class="price-id">' . $product_id . '</span></td>';
        echo '<td><span class="status-ok">✅ Configuré</span></td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    ?>

    <?php
    // Vérifier les tables liées
    try {
        // Vérifier la table subscriptions
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions");
        $subscriptions_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Vérifier la table payment_transactions  
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_transactions");
        $transactions_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo '<div class="section">';
        echo '<h2>📈 État des données</h2>';
        echo '<p><strong>Abonnements enregistrés :</strong> ' . $subscriptions_count . '</p>';
        echo '<p><strong>Transactions enregistrées :</strong> ' . $transactions_count . '</p>';
        
        if ($subscriptions_count > 0 || $transactions_count > 0) {
            echo '<p class="status-ok">✅ Des données d\'abonnement existent déjà</p>';
        } else {
            echo '<p>ℹ️ Aucune donnée d\'abonnement encore (normal pour un nouveau système)</p>';
        }
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="section warning">';
        echo '<h2>⚠️ Tables optionnelles</h2>';
        echo '<p>Certaines tables peuvent ne pas encore exister : ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>

    <?php
    // Actions recommandées
    if ($plans_with_price_ids < $total_plans) {
        echo '<div class="actions">';
        echo '<h2>🔧 Actions recommandées</h2>';
        echo '<ol>';
        echo '<li><strong>Utiliser le script de configuration :</strong> <a href="setup_stripe.php" target="_blank">setup_stripe.php</a></li>';
        echo '<li><strong>Créer les prix manquants</strong> dans le script (bouton "Créer les prix")</li>';
        echo '<li><strong>Synchroniser la base de données</strong> (bouton "Mettre à jour BDD")</li>';
        echo '<li><strong>Revenir ici</strong> pour vérifier que tout est configuré</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="actions">';
        echo '<h2>🎯 Prochaines étapes</h2>';
        echo '<ol>';
        echo '<li><strong>Tester le webhook :</strong> <a href="api/stripe/webhook.php" target="_blank">api/stripe/webhook.php</a></li>';
        echo '<li><strong>Tester une page de checkout :</strong> <a href="checkout.php?plan=1&shop=1" target="_blank">checkout.php?plan=1&shop=1</a></li>';
        echo '<li><strong>Vérifier la documentation :</strong> <a href="webhook_events_documentation.php" target="_blank">webhook_events_documentation.php</a></li>';
        echo '<li><strong>Supprimer les scripts de configuration</strong> (sécurité)</li>';
        echo '</ol>';
        echo '</div>';
    }
    ?>

    <div class="section">
        <h2>🔄 Commandes SQL utiles</h2>
        <div class="code">
            -- Voir tous les plans<br>
            SELECT id, name, price, billing_period, stripe_price_id FROM subscription_plans;<br><br>
            
            -- Plans sans Price ID<br>
            SELECT * FROM subscription_plans WHERE stripe_price_id IS NULL OR stripe_price_id = '';<br><br>
            
            -- Statistiques abonnements<br>
            SELECT status, COUNT(*) as count FROM subscriptions GROUP BY status;
        </div>
    </div>

    <p><small>Diagnostic généré le <?php echo date('Y-m-d H:i:s'); ?> - GeekBoard Stripe Setup Check</small></p>
</body>
</html>
