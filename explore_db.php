<?php
// Script d'exploration de la structure DB
$host = 'localhost';
$dbname = 'geekboard_mdg';
$user = 'gb_mdg';
$pass = 'Admin123!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== STRUCTURE employee_schedules ===\n";
    $stmt = $pdo->query("DESCRIBE employee_schedules");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']}) {$row['Null']} {$row['Key']}\n";
    }
    
    echo "\n=== TABLES LIÉES AU POINTAGE ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE '%time%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "Table: {$row[0]}\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE '%clock%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "Table: {$row[0]}\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE '%attendance%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "Table: {$row[0]}\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE '%presence%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "Table: {$row[0]}\n";
    }
    
    echo "\n=== TABLES LIÉES AUX CRÉNEAUX HORAIRES ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE '%slot%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "Table: {$row[0]}\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE '%shift%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "Table: {$row[0]}\n";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE '%schedule%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "Table: {$row[0]}\n";
    }
    
    echo "\n=== STRUCTURE time_slots (si existe) ===\n";
    try {
        $stmt = $pdo->query("DESCRIBE time_slots");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Table time_slots n'existe pas\n";
    }
    
    echo "\n=== STRUCTURE time_tracking (si existe) ===\n";
    try {
        $stmt = $pdo->query("DESCRIBE time_tracking");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Table time_tracking n'existe pas\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
