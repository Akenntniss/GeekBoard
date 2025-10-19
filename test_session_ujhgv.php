<?php
// Test de session pour ujhgv.servo.tools
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>Diagnostic Session pour ujhgv.servo.tools</h1>";

echo "<h2>1. Informations serveur</h2>";
echo "<strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'non défini') . "<br>";
echo "<strong>SERVER_NAME:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'non défini') . "<br>";
echo "<strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'non défini') . "<br>";

echo "<h2>2. Configuration session</h2>";
echo "<strong>Session Name:</strong> " . session_name() . "<br>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Cookie Domain:</strong> " . ini_get('session.cookie_domain') . "<br>";
echo "<strong>Cookie Path:</strong> " . ini_get('session.cookie_path') . "<br>";
echo "<strong>Cookie Lifetime:</strong> " . ini_get('session.cookie_lifetime') . "<br>";

echo "<h2>3. Variables de session</h2>";
echo "<strong>shop_id:</strong> " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'NON DÉFINI') . "<br>";
echo "<strong>shop_name:</strong> " . (isset($_SESSION['shop_name']) ? $_SESSION['shop_name'] : 'NON DÉFINI') . "<br>";
echo "<strong>user_id:</strong> " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DÉFINI') . "<br>";
echo "<strong>username:</strong> " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NON DÉFINI') . "<br>";

echo "<h2>4. Détection automatique du magasin</h2>";
$detected_shop_id = detectShopFromSubdomain();
echo "<strong>Shop ID détecté:</strong> " . ($detected_shop_id ?? 'AUCUN') . "<br>";

echo "<h2>5. Cookies</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>6. Test de connexion base de données</h2>";
try {
    $shop_pdo = getShopDBConnection();
    if ($shop_pdo) {
        echo "<strong>Connexion DB:</strong> ✅ OK<br>";
        $db_name = $shop_pdo->query("SELECT DATABASE()")->fetchColumn();
        echo "<strong>Base de données:</strong> " . $db_name . "<br>";
    } else {
        echo "<strong>Connexion DB:</strong> ❌ ÉCHEC<br>";
    }
} catch (Exception $e) {
    echo "<strong>Erreur DB:</strong> " . $e->getMessage() . "<br>";
}

echo "<h2>7. Vérification utilisateur</h2>";
if (isset($_SESSION['shop_id']) && $_SESSION['shop_id']) {
    try {
        $shop_pdo = getShopDBConnection();
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "<strong>Nombre d'utilisateurs:</strong> " . $result['count'] . "<br>";
    } catch (Exception $e) {
        echo "<strong>Erreur utilisateurs:</strong> " . $e->getMessage() . "<br>";
    }
}
?>
