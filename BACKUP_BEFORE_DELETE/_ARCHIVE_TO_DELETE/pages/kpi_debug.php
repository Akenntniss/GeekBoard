<?php
/**
 * Page de debug pour identifier le problème de redirection KPI
 */

// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/../config/subdomain_config.php';
// Le sous-domaine est détecté et la session est configurée avec le magasin correspondant

echo "<h2>Debug KPI Dashboard</h2>";
echo "<h3>Session Debug:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Files Check:</h3>";
echo "<pre>";

// Vérifier les chemins des fichiers
$config_path = __DIR__ . '/../config/database.php';
$functions_path = __DIR__ . '/../includes/functions.php';

echo "Config path: $config_path\n";
echo "Config exists: " . (file_exists($config_path) ? 'YES' : 'NO') . "\n";

echo "Functions path: $functions_path\n";
echo "Functions exists: " . (file_exists($functions_path) ? 'YES' : 'NO') . "\n";

echo "</pre>";

// Tester l'inclusion des fichiers
echo "<h3>File Include Test:</h3>";
echo "<pre>";

try {
    require_once $config_path;
    echo "✅ database.php included successfully\n";
} catch (Exception $e) {
    echo "❌ Error including database.php: " . $e->getMessage() . "\n";
}

try {
    require_once $functions_path;
    echo "✅ functions.php included successfully\n";
} catch (Exception $e) {
    echo "❌ Error including functions.php: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Tester la fonction initializeShopSession
echo "<h3>Shop Session Test:</h3>";
echo "<pre>";

try {
    if (function_exists('initializeShopSession')) {
        initializeShopSession();
        echo "✅ initializeShopSession() called successfully\n";
        echo "Shop ID after init: " . ($_SESSION['shop_id'] ?? 'NOT SET') . "\n";
    } else {
        echo "❌ initializeShopSession() function not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error calling initializeShopSession(): " . $e->getMessage() . "\n";
}

echo "</pre>";

// Tester la connexion à la base de données
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

echo "<h3>Server Info:</h3>";
echo "<pre>";
echo "Script: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current Dir: " . __DIR__ . "\n";
echo "Real Path: " . realpath(__DIR__) . "\n";
echo "</pre>";

// Lien vers la vraie page KPI
echo "<h3>Actions:</h3>";
echo "<a href='kpi_dashboard.php' style='background: blue; color: white; padding: 10px; text-decoration: none;'>Tester KPI Dashboard</a><br><br>";
echo "<a href='../index.php' style='background: green; color: white; padding: 10px; text-decoration: none;'>Retour Accueil</a>";
?>
