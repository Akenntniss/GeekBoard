<?php
/**
 * Migration: Create notification_role_preferences table
 * Run once to set up role-based notification preferences
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/session_config.php';
require_once BASE_PATH . '/config/database.php';

$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    die("ERREUR: Impossible de se connecter à la base de données.");
}

echo "<h1>Migration: notification_role_preferences</h1>";

try {
    // 1. Create the new table
    $sql = "
    CREATE TABLE IF NOT EXISTS notification_role_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_group VARCHAR(50) NOT NULL COMMENT 'admin or technicien',
        type_notification VARCHAR(100) NOT NULL,
        active TINYINT(1) DEFAULT 1,
        email_notification TINYINT(1) DEFAULT 0,
        push_notification TINYINT(1) DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_role_type (role_group, type_notification)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $shop_pdo->exec($sql);
    echo "<p style='color:green'>✓ Table notification_role_preferences créée ou existante.</p>";

    // 2. Seed with default values for each role and notification type
    $stmt = $shop_pdo->query("SELECT type_code FROM notification_types");
    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $roles = ['admin', 'technicien'];
    $inserted = 0;
    
    foreach ($roles as $role) {
        foreach ($types as $type) {
            // Insert only if not exists
            $check = $shop_pdo->prepare("SELECT id FROM notification_role_preferences WHERE role_group = ? AND type_notification = ?");
            $check->execute([$role, $type]);
            
            if (!$check->fetch()) {
                $ins = $shop_pdo->prepare("
                    INSERT INTO notification_role_preferences 
                    (role_group, type_notification, active, email_notification, push_notification) 
                    VALUES (?, ?, 1, 0, 1)
                ");
                $ins->execute([$role, $type]);
                $inserted++;
            }
        }
    }
    
    echo "<p style='color:green'>✓ $inserted préférences par défaut insérées.</p>";
    echo "<h2 style='color:green'>Migration terminée avec succès !</h2>";
    echo "<p><a href='index.php?page=notification_preferences'>Aller aux préférences de notification</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red'>ERREUR: " . $e->getMessage() . "</p>";
}
