<?php
/**
 * Version minimale pour diagnostiquer le problème 502
 */

echo "<!DOCTYPE html><html><head><title>Test Admin Timetracking</title></head><body>";
echo "<h1>Test Page - Étape 1</h1>";

// Test 1: Sessions
echo "<h2>Test 1: Session</h2>";
if (!session_id()) {
    session_start();
    echo "✅ Session démarrée<br>";
} else {
    echo "✅ Session déjà active<br>";
}

echo "Session ID: " . session_id() . "<br>";

// Test 2: Variables de session
echo "<h2>Test 2: Variables Session</h2>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NON_DEFINI') . "<br>";
echo "user_role: " . ($_SESSION['user_role'] ?? 'NON_DEFINI') . "<br>";
echo "full_name: " . ($_SESSION['full_name'] ?? 'NON_DEFINI') . "<br>";

// Test 3: Fichiers
echo "<h2>Test 3: Fichiers de config</h2>";

$files_to_check = [
    '../config/session_config.php',
    '../config/database.php', 
    '../includes/functions.php'
];

foreach ($files_to_check as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ " . $file . " existe<br>";
        try {
            require_once __DIR__ . '/' . $file;
            echo "✅ " . $file . " chargé<br>";
        } catch (Exception $e) {
            echo "❌ Erreur chargement " . $file . ": " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ " . $file . " manquant<br>";
    }
}

// Test 4: Fonctions
echo "<h2>Test 4: Fonctions</h2>";
echo "getShopDBConnection exists: " . (function_exists('getShopDBConnection') ? '✅ OUI' : '❌ NON') . "<br>";

// Test 5: Base de données si possible
echo "<h2>Test 5: Base de données</h2>";
try {
    if (function_exists('getShopDBConnection')) {
        $pdo = getShopDBConnection();
        echo "✅ Connexion DB réussie<br>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        echo "✅ Nombre d'utilisateurs: " . $count . "<br>";
    } else {
        echo "❌ Fonction getShopDBConnection non disponible<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ Test terminé avec succès</h2>";
echo "</body></html>";
?>
