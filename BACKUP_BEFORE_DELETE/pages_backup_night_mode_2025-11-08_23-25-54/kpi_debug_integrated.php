<?php
/**
 * Page de debug KPI intégrée pour identifier les problèmes
 */

echo "<div class='page-container'>";
echo "<h2>Debug KPI Dashboard Intégré</h2>";
echo "<h3>Session Debug:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Variables Globales:</h3>";
echo "<pre>";
echo "Current User ID: " . ($current_user_id ?? 'NOT SET') . "\n";
echo "Is Admin: " . (($is_admin ?? false) ? 'YES' : 'NO') . "\n";
echo "User Name: " . ($user_name ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<h3>Database Connection Test:</h3>";
echo "<pre>";

try {
    if (function_exists('getShopDBConnection')) {
        $pdo = getShopDBConnection();
        if ($pdo) {
            echo "✅ Database connection successful\n";
            $stmt = $pdo->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Database name: " . ($result['db_name'] ?? 'UNKNOWN') . "\n";
            
            // Test de requête KPI
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'technicien')");
            $stmt->execute();
            $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Nombre d'utilisateurs: " . $userCount['count'] . "\n";
        } else {
            echo "❌ Database connection failed - PDO is null\n";
        }
    } else {
        echo "❌ getShopDBConnection() function not found\n";
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h3>Page Info:</h3>";
echo "<pre>";
echo "Current Page: " . ($_GET['page'] ?? 'NOT SET') . "\n";
echo "BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "</pre>";

echo "<h3>Actions:</h3>";
echo "<a href='index.php?page=reparations' style='background: green; color: white; padding: 10px; text-decoration: none; margin-right: 10px;'>Test Page Réparations</a>";
echo "<a href='index.php' style='background: blue; color: white; padding: 10px; text-decoration: none;'>Retour Accueil</a>";

echo "</div>";
?>

