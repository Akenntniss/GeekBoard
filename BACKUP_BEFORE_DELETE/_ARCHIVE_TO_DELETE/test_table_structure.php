<?php
require_once 'config/database.php';

// Initialiser la session magasin
if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

try {
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        throw new Exception("Connexion PDO non disponible");
    }
    
    echo "<h2>Structure de la table rachat_appareils</h2>";
    
    $stmt = $pdo->query("DESCRIBE rachat_appareils");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier spécifiquement la colonne client_photo
    $check_column = $pdo->query("SHOW COLUMNS FROM rachat_appareils LIKE 'client_photo'");
    $column_exists = ($check_column && $check_column->rowCount() > 0);
    
    echo "<h3>Colonne client_photo : " . ($column_exists ? "✅ EXISTE" : "❌ N'EXISTE PAS") . "</h3>";
    
    // Tester un rachat récent
    $stmt = $pdo->query("SELECT id, client_photo FROM rachat_appareils ORDER BY id DESC LIMIT 5");
    $recent_rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>5 rachats les plus récents (colonne client_photo) :</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>client_photo</th></tr>";
    
    foreach ($recent_rachats as $rachat) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($rachat['id']) . "</td>";
        echo "<td>" . htmlspecialchars($rachat['client_photo'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>
