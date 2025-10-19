<?php
/**
 * Script de debug pour le système de pointage
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
initializeShopSession();

echo "<h2>Debug Système de Pointage</h2>";

echo "<h3>Session Info:</h3>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . "\n";
echo "user_role: " . ($_SESSION['user_role'] ?? 'NON DÉFINI') . "\n";
echo "shop_id: " . ($_SESSION['shop_id'] ?? 'NON DÉFINI') . "\n";
echo "shop_name: " . ($_SESSION['shop_name'] ?? 'NON DÉFINI') . "\n";
echo "</pre>";

echo "<h3>Database Connection:</h3>";
try {
    $pdo = getShopDBConnection();
    echo "✅ Connexion à la base réussie<br>";
    
    // Vérifier si la table time_tracking existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'time_tracking'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✅ Table time_tracking existe<br>";
        
        // Compter les entrées
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM time_tracking");
        $stmt->execute();
        $count = $stmt->fetch();
        echo "📊 Nombre d'entrées: " . $count['count'] . "<br>";
        
    } else {
        echo "❌ Table time_tracking n'existe pas<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "<br>";
}

echo "<h3>Test API:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<a href='debug_timetracking.php?test_api=1'>Tester l'API get_status</a><br>";
    
    if (isset($_GET['test_api'])) {
        echo "<h4>Résultat API:</h4>";
        
        // Simuler un appel à l'API
        $_POST['action'] = 'get_status';
        
        ob_start();
        include 'time_tracking_api.php';
        $api_result = ob_get_clean();
        
        echo "<pre>" . htmlspecialchars($api_result) . "</pre>";
    }
} else {
    echo "❌ Utilisateur non connecté - impossible de tester l'API<br>";
    echo "<p>Veuillez vous connecter d'abord.</p>";
}
?>
