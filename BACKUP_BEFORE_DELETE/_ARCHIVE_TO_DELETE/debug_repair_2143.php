<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getShopDBConnection(); // Assuming this function exists and connects to the correct shop DB
    
    $stmt = $pdo->prepare("SELECT id, created_by, employe_id, user_id, date_creation FROM reparations WHERE id = 2143");
    $stmt->execute();
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Repair 2143 Details:\n";
    print_r($repair);
    
    // Also check columns to see what we have
    $stmt = $pdo->query("DESCRIBE reparations");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nColumns in reparations table:\n";
    print_r($columns);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
