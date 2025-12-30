<?php
echo "<!-- DEBUG: Début de la page -->\n";

// Test des includes étape par étape
try {
    echo "<!-- DEBUG: Avant require config/database.php -->\n";
    require_once __DIR__ . '/../config/database.php';
    echo "<!-- DEBUG: database.php OK -->\n";
} catch (Exception $e) {
    echo "<!-- DEBUG: Erreur database.php: " . $e->getMessage() . " -->\n";
}

try {
    echo "<!-- DEBUG: Avant require includes/functions.php -->\n";
    require_once __DIR__ . '/../includes/functions.php';
    echo "<!-- DEBUG: functions.php OK -->\n";
} catch (Exception $e) {
    echo "<!-- DEBUG: Erreur functions.php: " . $e->getMessage() . " -->\n";
}

echo "<!-- DEBUG: Test de base terminé -->\n";

// Test de connexion basique
try {
    echo "<!-- DEBUG: Test connexion PDO directe -->\n";
    $shop_pdo = new PDO(
        "mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4",
        "root",
        "Mamanmaman01#",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "<!-- DEBUG: Connexion PDO OK -->\n";
    
    // Test d'une requête simple
    $tables = $shop_pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<!-- DEBUG: " . count($tables) . " tables trouvées -->\n";
    
} catch (Exception $e) {
    echo "<!-- DEBUG: Erreur connexion: " . $e->getMessage() . " -->\n";
}

echo "<!-- DEBUG: Fin des tests -->\n";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Diagnostic</title>
</head>
<body>
    <h1>Page de Diagnostic - Test de Fonctionnement</h1>
    <p>Si vous voyez cette page, les includes de base fonctionnent.</p>
    <p>Vérifiez les commentaires HTML pour les détails de diagnostic.</p>
</body>
</html>
