<?php
// Test simple pour admin_timetracking.php
echo "<!DOCTYPE html><html><head><title>Test Admin Pointage</title></head><body>";
echo "<h1>Test Interface Admin Pointage</h1>";

// Test de la base de données
echo "<h2>Test Base de données</h2>";
try {
    require_once __DIR__ . '/../config/database.php';
    echo "✅ Configuration base de données chargée<br>";
    
    if (isset($shop_pdo) && $shop_pdo !== null) {
        echo "✅ Connexion base de données OK<br>";
        
        // Test de la table time_tracking
        $stmt = $shop_pdo->query("SHOW TABLES LIKE 'time_tracking'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table time_tracking existe<br>";
            
            // Compter les entrées
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM time_tracking");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Entrées dans time_tracking: " . $result['count'] . "<br>";
        } else {
            echo "❌ Table time_tracking n'existe pas<br>";
        }
    } else {
        echo "❌ Pas de connexion base de données<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}

// Test des fonctions
echo "<h2>Test Fonctions</h2>";
try {
    require_once __DIR__ . '/../includes/functions.php';
    echo "✅ Fonctions chargées<br>";
} catch (Exception $e) {
    echo "❌ Erreur fonctions: " . $e->getMessage() . "<br>";
}

// Test session
echo "<h2>Test Session</h2>";
if (!headers_sent()) {
    session_start();
}
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Non défini') . "<br>";
echo "User Role: " . ($_SESSION['user_role'] ?? 'Non défini') . "<br>";

echo "<h2>Test API</h2>";
echo '<button onclick="testAPI()">Test API Pointage</button>';
echo '<div id="api-result"></div>';

echo '<script>
function testAPI() {
    fetch("/time_tracking_api.php?action=get_status")
        .then(response => response.json())
        .then(data => {
            document.getElementById("api-result").innerHTML = "<pre>" + JSON.stringify(data, null, 2) + "</pre>";
        })
        .catch(error => {
            document.getElementById("api-result").innerHTML = "Erreur: " + error;
        });
}
</script>';

echo "</body></html>";
?>

