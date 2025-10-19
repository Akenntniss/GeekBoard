<?php
// Fichier de diagnostic pour identifier les problèmes AJAX
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== DIAGNOSTIC AJAX ===\n\n";

// Vérifier les chemins
echo "1. Vérification des chemins:\n";
echo "- __DIR__: " . __DIR__ . "\n";
echo "- Chemin config: " . __DIR__ . '/../includes/config.php' . "\n";
echo "- Chemin functions: " . __DIR__ . '/../includes/functions.php' . "\n";

echo "\n2. Vérification de l'existence des fichiers:\n";
$config_path = __DIR__ . '/../includes/config.php';
$functions_path = __DIR__ . '/../includes/functions.php';

echo "- config.php existe: " . (file_exists($config_path) ? "OUI" : "NON") . "\n";
echo "- functions.php existe: " . (file_exists($functions_path) ? "OUI" : "NON") . "\n";

if (file_exists($config_path)) {
    echo "- config.php lisible: " . (is_readable($config_path) ? "OUI" : "NON") . "\n";
}

if (file_exists($functions_path)) {
    echo "- functions.php lisible: " . (is_readable($functions_path) ? "OUI" : "NON") . "\n";
}

echo "\n3. Test d'inclusion:\n";
try {
    echo "- Tentative d'inclusion de config.php...\n";
    require_once $config_path;
    echo "  ✅ config.php inclus avec succès\n";
    
    echo "- Tentative d'inclusion de config/database.php...\n";
    require_once __DIR__ . '/../config/database.php';
    echo "  ✅ config/database.php inclus avec succès\n";
    
    echo "- Tentative d'inclusion de functions.php...\n";
    require_once $functions_path;
    echo "  ✅ functions.php inclus avec succès\n";
    
} catch (Exception $e) {
    echo "  ❌ Erreur: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "  ❌ Erreur fatale: " . $e->getMessage() . "\n";
}

echo "\n4. Vérification des fonctions:\n";
if (function_exists('getShopDBConnection')) {
    echo "- getShopDBConnection: ✅ EXISTE\n";
    
    try {
        echo "- Test de connexion à la base...\n";
        $pdo = getShopDBConnection();
        if ($pdo) {
            echo "  ✅ Connexion réussie\n";
        } else {
            echo "  ❌ Connexion échouée\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Erreur de connexion: " . $e->getMessage() . "\n";
    }
} else {
    echo "- getShopDBConnection: ❌ N'EXISTE PAS\n";
}

echo "\n5. Vérification de la session:\n";
session_start();
echo "- Session démarrée: " . (session_status() === PHP_SESSION_ACTIVE ? "OUI" : "NON") . "\n";
echo "- Session ID: " . session_id() . "\n";
echo "- Variables de session:\n";
foreach ($_SESSION as $key => $value) {
    echo "  - $key: " . (is_string($value) ? $value : gettype($value)) . "\n";
}

echo "\n6. Test de sortie JSON:\n";
$test_data = ['success' => true, 'message' => 'Test réussi', 'timestamp' => date('Y-m-d H:i:s')];
echo "- JSON test: " . json_encode($test_data) . "\n";

echo "\n=== FIN DU DIAGNOSTIC ===\n";
?>
