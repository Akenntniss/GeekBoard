<?php
// Test ultra-simple pour identifier le problème
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG SERVEUR ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Session status: " . session_status() . "\n";

try {
    session_start();
    echo "✅ Session démarrée\n";
} catch (Exception $e) {
    echo "❌ Erreur session: " . $e->getMessage() . "\n";
}

echo "POST data: " . json_encode($_POST) . "\n";
echo "SESSION data: " . json_encode($_SESSION) . "\n";

try {
    $config_path = '../config/database.php';
    echo "Config path: $config_path\n";
    echo "File exists: " . (file_exists($config_path) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($config_path)) {
        require_once $config_path;
        echo "✅ Config inclus\n";
        
        if (function_exists('initializeShopSession')) {
            initializeShopSession();
            echo "✅ initializeShopSession appelé\n";
        } else {
            echo "❌ initializeShopSession non trouvé\n";
        }
        
        if (function_exists('getShopDBConnection')) {
            $pdo = getShopDBConnection();
            echo "✅ Connexion DB obtenue\n";
            
            // Test simple
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM produits LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            echo "✅ Test DB réussi: " . $result['count'] . " produits\n";
        } else {
            echo "❌ getShopDBConnection non trouvé\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "❌ Trace: " . $e->getTraceAsString() . "\n";
}

echo "=== FIN DEBUG ===\n";
?>
