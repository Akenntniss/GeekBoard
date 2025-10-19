<?php
/**
 * Test pour voir les sessions et l'auth en cours
 */

// Analyser les sessions actuelles
if (!session_id()) {
    session_start();
}

echo "<!DOCTYPE html><html><head><title>Test Session & Auth</title></head><body>";
echo "<h1>🔍 Test Session & Authentification</h1>";

echo "<h2>📋 Variables de Session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>🍪 Variables Cookie</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>🌐 Variables Serveur</h2>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
echo "</pre>";

echo "<h2>🔐 Test Auth GeekBoard</h2>";

// Chercher comment GeekBoard vérifie l'auth
$auth_methods = [
    "includes/auth_check.php",
    "config/session.php", 
    "config/session_config.php",
    "includes/session_config.php"
];

foreach ($auth_methods as $auth_file) {
    $full_path = "/var/www/mdgeek.top/$auth_file";
    if (file_exists($full_path)) {
        echo "✅ Fichier auth trouvé: $auth_file<br>";
        
        // Tenter d'inclure
        try {
            require_once $full_path;
            echo "✅ Auth file included<br>";
        } catch (Exception $e) {
            echo "❌ Erreur inclusion: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ $auth_file non trouvé<br>";
    }
}

echo "<h2>📊 Test Base de Données</h2>";

try {
    require_once "/var/www/mdgeek.top/config/database.php";
    echo "✅ Config DB chargée<br>";
    
    if (isset($shop_pdo) && $shop_pdo !== null) {
        echo "✅ Connexion DB OK<br>";
        
        // Test table users
        $stmt = $shop_pdo->query("SELECT id, username, full_name, role FROM users LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "👥 Utilisateurs trouvés:<br>";
        foreach ($users as $user) {
            echo "- ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}<br>";
        }
    } else {
        echo "❌ Pas de connexion DB<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "<br>";
}

echo "<h2>🧪 Test Login Direct</h2>";
echo '<form method="POST" action="">';
echo 'User ID: <input type="number" name="test_user_id" value="1"><br>';
echo 'Role: <select name="test_role"><option value="admin">Admin</option><option value="technicien">Technicien</option></select><br>';
echo '<input type="submit" name="simulate_login" value="Simuler Login">';
echo '</form>';

if (isset($_POST['simulate_login'])) {
    $_SESSION['user_id'] = intval($_POST['test_user_id']);
    $_SESSION['user_role'] = $_POST['test_role'];
    $_SESSION['full_name'] = 'Test User';
    echo "<div style='background: green; color: white; padding: 10px;'>✅ Session simulée définie</div>";
}

echo "</body></html>";
?>
