<?php
require_once 'config/database.php';
try {
    $pdo = getShopDBConnection();
    $stmt = $pdo->query("SHOW COLUMNS FROM reparation_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in reparation_logs:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
