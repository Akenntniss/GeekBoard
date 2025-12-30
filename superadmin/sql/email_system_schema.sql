-- ============================================
-- Email Notification System - Database Schema
-- ============================================

-- Table de configuration SMTP
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
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES superadmins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des logs d'emails
CREATE TABLE IF NOT EXISTS email_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    shop_id INT,
    type ENUM('new_shop', 'trial_expiring_7d', 'trial_expiring_3d', 'trial_expired', 'test', 'manual') NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    error_message TEXT,
    retry_count INT NOT NULL DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_shop_id (shop_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer la configuration par défaut
INSERT INTO email_config (smtp_password) 
VALUES ('Maisondugeek06$')
ON DUPLICATE KEY UPDATE id=id;

-- Table pour tracker les notifications déjà envoyées (éviter doublons)
CREATE TABLE IF NOT EXISTS email_notifications_sent (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    shop_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    sent_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_notification (shop_id, notification_type, sent_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
