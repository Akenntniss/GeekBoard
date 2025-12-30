<?php
// Test simple pour déboguer
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: Démarrage<br>";

require_once __DIR__ . '/../config/session_config.php';
echo "Test 2: Session config OK<br>";

require_once __DIR__ . '/../config/subdomain_config.php';
echo "Test 3: Subdomain config OK<br>";

require_once __DIR__ . '/../includes/functions.php';
echo "Test 4: Functions OK<br>";

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
echo "Test 5: User ID = " . ($user_id ?? 'NULL') . "<br>";

if (!$user_id) {
    echo "Test 6: Pas d'utilisateur, devrait rediriger<br>";
    exit;
}

echo "Test 7: Tentative connexion DB<br>";
try {
    $shop_pdo = getShopDBConnection();
    echo "Test 8: Connexion DB OK<br>";
} catch (Exception $e) {
    echo "Test 8 ERREUR: " . $e->getMessage() . "<br>";
    exit;
}

echo "Test 9: Tentative requête<br>";
try {
    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM ai_expert_profiles");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Test 10: Nombre de profils = " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "Test 10 ERREUR: " . $e->getMessage() . "<br>";
}

echo "<br><strong>Tous les tests passés!</strong>";
?>
