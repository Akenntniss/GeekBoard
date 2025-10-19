<?php
// Test d'authentification et de session
session_start();

echo "<h1>🔐 Debug Session & Auth</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

echo "<h2>📋 Variables de session actuelles :</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>🌐 Variables serveur :</h2>";
echo "<ul>";
echo "<li><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'NON DÉFINI') . "</li>";
echo "<li><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'NON DÉFINI') . "</li>";
echo "<li><strong>PHP_SELF:</strong> " . ($_SERVER['PHP_SELF'] ?? 'NON DÉFINI') . "</li>";
echo "</ul>";

echo "<h2>🔄 Test inclusion des configs :</h2>";

try {
    // Simuler l'environnement
    $_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';
    
    require_once __DIR__ . '/config/database.php';
    echo "<p class='success'>✅ config/database.php inclus</p>";
    
    // Test connexion shop
    $shop_pdo = getShopDBConnection();
    if ($shop_pdo) {
        echo "<p class='success'>✅ Connexion shop réussie</p>";
        
        $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='info'>📊 Base active : " . $db_info['db_name'] . "</p>";
    } else {
        echo "<p class='error'>❌ Échec connexion shop</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . $e->getMessage() . "</p>";
}

echo "<h2>🧪 Simulation session utilisateur :</h2>";
$_SESSION['shop_id'] = 'mkmkmk';
$_SESSION['user_id'] = 6;
$_SESSION['full_name'] = 'Administrateur Mkmkmk';

echo "<p class='info'>Session simulée avec :</p>";
echo "<ul>";
echo "<li>shop_id: " . $_SESSION['shop_id'] . "</li>";
echo "<li>user_id: " . $_SESSION['user_id'] . "</li>";
echo "<li>full_name: " . $_SESSION['full_name'] . "</li>";
echo "</ul>";

echo "<h2>🔗 Liens de test :</h2>";
echo "<ul>";
echo "<li><a href='debug_sql_missions.php'>🔍 Diagnostic SQL détaillé</a></li>";
echo "<li><a href='index.php?page=mes_missions'>📋 Page missions normale</a></li>";
echo "</ul>";

echo "<hr><p><em>Debug session terminé - " . date('Y-m-d H:i:s') . "</em></p>";
?>
