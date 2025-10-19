<?php
/**
 * Version de test de admin_timetracking.php pour diagnostiquer le problème 404
 */

// Test basique pour vérifier que PHP fonctionne
echo "<!DOCTYPE html>";
echo "<html><head><title>Test Admin Timetracking</title></head><body>";
echo "<h1>Test de la page admin timetracking</h1>";
echo "<p>Si vous voyez ce message, PHP fonctionne.</p>";

// Tester les inclusions une par une
try {
    echo "<p>Test inclusion config/database.php...</p>";
    require_once __DIR__ . '/config/database.php';
    echo "<p>✅ config/database.php chargé avec succès</p>";
} catch (Exception $e) {
    echo "<p>❌ Erreur config/database.php: " . $e->getMessage() . "</p>";
}

try {
    echo "<p>Test inclusion includes/functions.php...</p>";
    require_once __DIR__ . '/includes/functions.php';
    echo "<p>✅ includes/functions.php chargé avec succès</p>";
} catch (Exception $e) {
    echo "<p>❌ Erreur includes/functions.php: " . $e->getMessage() . "</p>";
}

// Tester la session
try {
    echo "<p>Test session...</p>";
    session_start();
    echo "<p>✅ Session démarrée avec succès</p>";
} catch (Exception $e) {
    echo "<p>❌ Erreur session: " . $e->getMessage() . "</p>";
}

// Tester la connexion base de données
try {
    echo "<p>Test connexion base de données...</p>";
    $shop_pdo = getShopDBConnection();
    echo "<p>✅ Connexion base de données réussie</p>";
} catch (Exception $e) {
    echo "<p>❌ Erreur connexion base de données: " . $e->getMessage() . "</p>";
}

echo "<p>Test terminé.</p>";
echo "</body></html>";
?>
