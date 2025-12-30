<?php
// Debug version de subscriptions.php
session_start();

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Debug Subscriptions</title></head><body>";
echo "<h1>Debug de la page subscriptions</h1>";

// Test 1: Session
echo "<h2>1. Test Session</h2>";
if (!isset($_SESSION['superadmin_id'])) {
    echo "<p style='color: red;'>❌ Pas de session superadmin_id détectée</p>";
    echo "<p>Contenu de \$_SESSION:</p>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
} else {
    echo "<p style='color: green;'>✅ Session superadmin_id trouvée: " . $_SESSION['superadmin_id'] . "</p>";
}

// Test 2: Inclusion database.php
echo "<h2>2. Test Database</h2>";
try {
    require_once('../config/database.php');
    echo "<p style='color: green;'>✅ config/database.php inclus avec succès</p>";
    
    // Test fonction getMainDBConnection
    if (function_exists('getMainDBConnection')) {
        echo "<p style='color: green;'>✅ Fonction getMainDBConnection existe</p>";
        
        try {
            $pdo = getMainDBConnection();
            echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
            
            // Test simple requête
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM shops");
            $result = $stmt->fetch();
            echo "<p style='color: green;'>✅ Test requête shops: " . $result['count'] . " shops trouvés</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur connexion DB: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Fonction getMainDBConnection n'existe pas</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur inclusion database.php: " . $e->getMessage() . "</p>";
}

// Test 3: Inclusion SubscriptionManager
echo "<h2>3. Test SubscriptionManager</h2>";
try {
    require_once('../classes/SubscriptionManager.php');
    echo "<p style='color: green;'>✅ SubscriptionManager.php inclus avec succès</p>";
    
    if (class_exists('SubscriptionManager')) {
        echo "<p style='color: green;'>✅ Classe SubscriptionManager existe</p>";
        
        try {
            $subscriptionManager = new SubscriptionManager();
            echo "<p style='color: green;'>✅ Instance SubscriptionManager créée</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur création SubscriptionManager: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Classe SubscriptionManager n'existe pas</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur inclusion SubscriptionManager: " . $e->getMessage() . "</p>";
}

// Test 4: Vérification des tables
echo "<h2>4. Test Tables</h2>";
if (isset($pdo)) {
    $tables = ['shops', 'shop_owners', 'subscriptions', 'subscription_plans', 'payment_transactions'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "<p style='color: green;'>✅ Table $table: " . $result['count'] . " enregistrements</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur table $table: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<hr>";
echo "<h2>Conclusion</h2>";
echo "<p>Si tous les tests sont verts, le problème vient de la redirection de session.</p>";
echo "<p>Si des tests sont rouges, cela indique où se trouve le problème.</p>";

echo "</body></html>";
?>
