<?php
// Vérifier la structure de mission_validations
session_start();
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/.');
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

$shop_pdo = getShopDBConnection();

echo "<h1>Structure de mission_validations</h1>";

try {
    $stmt = $shop_pdo->query("DESCRIBE mission_validations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

echo "<h2>Données d'exemple</h2>";
try {
    $stmt = $shop_pdo->query("SELECT * FROM mission_validations LIMIT 3");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($data)) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($data[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucune donnée trouvée</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur données: " . $e->getMessage() . "</p>";
}
?> 