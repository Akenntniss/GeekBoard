<?php
// test_backup_logic.php
define('MAIN_DB_HOST', '127.0.0.1'); // Assuming local execution for test or use config
require_once __DIR__ . '/actions/database_manager.php';
require_once __DIR__ . '/config/database.php';

// Mock session/environment if needed or just test the class
// We need a valid PDO connection. 
// Let's assumegetMainDBConnection works or we mock it.

try {
    echo "Testing DatabaseResetManager... \n";
    
    // 1. Get Connection (Mocking shop context)
    // We'll use the main connection for testing if shop is not set, 
    // BUT the class requires a shop_id.
    // Let's look for a valid shop_id
    
    $pdo = getMainDBConnection();
    $stmt = $pdo->query("SELECT id FROM shops LIMIT 1");
    $shop = $stmt->fetch();
    $shop_id = $shop ? $shop['id'] : 1;
    
    echo "Using Shop ID: $shop_id \n";
    
    // 2. Instantiate
    $manager = new DatabaseResetManager($pdo, $shop_id); // Re-using main PDO as proxy for shop PDO for this test if strictly needed, or getShopDBConnection
    
    // 3. Test Backup
    echo "Creating Backup... \n";
    $file = $manager->createBackup();
    echo "Backup created: $file \n";
    
    if (file_exists(__DIR__ . '/backups/shop_' . $shop_id . '/' . $file)) {
        echo "File exists on disk. ✅ \n";
        echo "Size: " . filesize(__DIR__ . '/backups/shop_' . $shop_id . '/' . $file) . " bytes \n";
    } else {
        echo "File NOT found on disk. ❌ \n";
        exit(1);
    }

    // 4. Test List
    $backups = $manager->getBackups();
    echo "Found " . count($backups) . " backups. \n";
    
    // 5. Cleanup
    // $manager->deleteBackup($file);
    // echo "Cleanup done. \n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
