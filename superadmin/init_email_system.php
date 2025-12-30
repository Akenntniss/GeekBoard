<?php
/**
 * Initialiser les tables du système email
 * À exécuter une seule fois
 */

require_once(__DIR__ . '/../config/database.php');

echo "=== Initialisation du système email ===\n\n";

try {
    $pdo = getMainDBConnection();
    
    // Table email_config
    echo "Création de la table email_config...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_config (
            id INT PRIMARY KEY AUTO_INCREMENT,
            smtp_host VARCHAR(255) NOT NULL DEFAULT 'smtp.hostinger.com',
            smtp_port INT NOT NULL DEFAULT 465,
            smtp_encryption VARCHAR(10) NOT NULL DEFAULT 'ssl',
            smtp_username VARCHAR(255) NOT NULL DEFAULT 'saber@maisondugeek.fr',
            smtp_password TEXT NOT NULL,
            from_email VARCHAR(255) NOT NULL DEFAULT 'saber@maisondugeek.fr',
            from_name VARCHAR(255) NOT NULL DEFAULT 'GeekBoard',
            admin_email VARCHAR(255) NOT NULL DEFAULT 'saber.guezguez@icloud.com',
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table email_config créée\n";
    
    // Insérer config par défaut si n'existe pas
    $stmt = $pdo->query("SELECT COUNT(*) FROM email_config");
    if ($stmt->fetchColumn() == 0) {
        echo "Insertion de la configuration par défaut...\n";
        $pdo->exec("
            INSERT INTO email_config (smtp_password) 
            VALUES ('Maisondugeek06$')
        ");
        echo "✓ Configuration par défaut insérée\n";
    } else {
        echo "✓ Configuration déjà existante\n";
    }
    
    // Table email_logs
    echo "\nCréation de la table email_logs...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_logs (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            shop_id INT,
            type ENUM('new_shop', 'trial_expiring_7d', 'trial_expiring_3d', 'trial_expiring_1d', 'trial_expired', 'test', 'manual') NOT NULL,
            recipient_email VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            body_html TEXT,
            status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
            error_message TEXT,
            retry_count INT NOT NULL DEFAULT 0,
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_shop_id (shop_id),
            INDEX idx_type (type),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table email_logs créée\n";
    
    // Table email_notifications_sent
    echo "\nCréation de la table email_notifications_sent...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_notifications_sent (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            shop_id INT NOT NULL,
            notification_type VARCHAR(50) NOT NULL,
            sent_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_notification (shop_id, notification_type, sent_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table email_notifications_sent créée\n";
    
    echo "\n=== ✅ Initialisation terminée avec succès ! ===\n";
    echo "\nVous pouvez maintenant accéder à settings_email.php\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
