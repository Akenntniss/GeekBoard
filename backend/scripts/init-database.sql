-- Script d'initialisation de la base de données SMS Gateway
-- Base de données: sms_gateway
-- Host: 168.231.85.4

USE sms_gateway;

-- Table des téléphones
CREATE TABLE IF NOT EXISTS phones (
    id VARCHAR(36) PRIMARY KEY,
    model VARCHAR(255) NOT NULL,
    android_version VARCHAR(50) NOT NULL,
    app_version VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    last_heartbeat TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_last_heartbeat (last_heartbeat)
);

-- Table des cartes SIM
CREATE TABLE IF NOT EXISTS sims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id VARCHAR(36) NOT NULL,
    slot_index INT NOT NULL,
    phone_number VARCHAR(20),
    carrier_name VARCHAR(100),
    country_code VARCHAR(10),
    subscription_id INT,
    is_active BOOLEAN DEFAULT true,
    daily_limit INT DEFAULT 1000,
    monthly_limit INT DEFAULT 30000,
    messages_sent_today INT DEFAULT 0,
    messages_sent_month INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_phone_slot (phone_id, slot_index),
    INDEX idx_phone_id (phone_id),
    INDEX idx_active (is_active)
);

-- Table de l'historique des SMS
CREATE TABLE IF NOT EXISTS sms_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id VARCHAR(36) NOT NULL,
    sim_id INT,
    recipient VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    failure_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone_id (phone_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Table de configuration système
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs (pour l'authentification)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT true,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Insertion des configurations par défaut
INSERT IGNORE INTO system_config (config_key, config_value, description) VALUES
('heartbeat_interval', '30', 'Intervalle de heartbeat en secondes'),
('polling_interval', '10', 'Intervalle de polling des messages en secondes'),
('retry_attempts', '3', 'Nombre de tentatives de renvoi en cas d\'échec'),
('auto_switch_enabled', 'true', 'Basculement automatique entre SIMs'),
('daily_sms_limit', '1000', 'Limite quotidienne de SMS par SIM'),
('monthly_sms_limit', '30000', 'Limite mensuelle de SMS par SIM'),
('api_rate_limit', '100', 'Limite de requêtes API par minute'),
('maintenance_mode', 'false', 'Mode maintenance activé/désactivé');

-- Création d'un utilisateur admin par défaut
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@smsgateway.local', '$2b$10$rQZ8kHWKtGOuGwHvKV.Ks.xQZ8kHWKtGOuGwHvKV.Ks.xQZ8kHWKtG', 'admin');

-- Vues pour les statistiques
CREATE OR REPLACE VIEW phone_stats AS
SELECT 
    p.id,
    p.model,
    p.status,
    COUNT(s.id) as sim_count,
    COUNT(CASE WHEN s.is_active = 1 THEN 1 END) as active_sims,
    COALESCE(SUM(s.messages_sent_today), 0) as messages_today,
    COALESCE(SUM(s.messages_sent_month), 0) as messages_month,
    p.last_heartbeat
FROM phones p
LEFT JOIN sims s ON p.id = s.phone_id
GROUP BY p.id, p.model, p.status, p.last_heartbeat;

CREATE OR REPLACE VIEW daily_stats AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_messages,
    COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_messages,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_messages,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_messages
FROM sms_history
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Index pour optimiser les performances
CREATE INDEX IF NOT EXISTS idx_sms_history_date ON sms_history(DATE(created_at));
CREATE INDEX IF NOT EXISTS idx_sims_messages_today ON sims(messages_sent_today);
CREATE INDEX IF NOT EXISTS idx_phones_heartbeat ON phones(last_heartbeat);

-- Procédure pour réinitialiser les compteurs quotidiens
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ResetDailyCounters()
BEGIN
    UPDATE sims SET messages_sent_today = 0;
    INSERT INTO system_config (config_key, config_value, description) 
    VALUES ('last_daily_reset', NOW(), 'Dernière réinitialisation des compteurs quotidiens')
    ON DUPLICATE KEY UPDATE config_value = NOW();
END //
DELIMITER ;

-- Procédure pour réinitialiser les compteurs mensuels
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ResetMonthlyCounters()
BEGIN
    UPDATE sims SET messages_sent_month = 0;
    INSERT INTO system_config (config_key, config_value, description) 
    VALUES ('last_monthly_reset', NOW(), 'Dernière réinitialisation des compteurs mensuels')
    ON DUPLICATE KEY UPDATE config_value = NOW();
END //
DELIMITER ;

-- Affichage des tables créées
SHOW TABLES;

SELECT 'Base de données SMS Gateway initialisée avec succès!' as message; 
-- Base de données: sms_gateway
-- Host: 168.231.85.4

USE sms_gateway;

-- Table des téléphones
CREATE TABLE IF NOT EXISTS phones (
    id VARCHAR(36) PRIMARY KEY,
    model VARCHAR(255) NOT NULL,
    android_version VARCHAR(50) NOT NULL,
    app_version VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    last_heartbeat TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_last_heartbeat (last_heartbeat)
);

-- Table des cartes SIM
CREATE TABLE IF NOT EXISTS sims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id VARCHAR(36) NOT NULL,
    slot_index INT NOT NULL,
    phone_number VARCHAR(20),
    carrier_name VARCHAR(100),
    country_code VARCHAR(10),
    subscription_id INT,
    is_active BOOLEAN DEFAULT true,
    daily_limit INT DEFAULT 1000,
    monthly_limit INT DEFAULT 30000,
    messages_sent_today INT DEFAULT 0,
    messages_sent_month INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_phone_slot (phone_id, slot_index),
    INDEX idx_phone_id (phone_id),
    INDEX idx_active (is_active)
);

-- Table de l'historique des SMS
CREATE TABLE IF NOT EXISTS sms_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id VARCHAR(36) NOT NULL,
    sim_id INT,
    recipient VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    failure_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone_id (phone_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Table de configuration système
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs (pour l'authentification)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT true,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Insertion des configurations par défaut
INSERT IGNORE INTO system_config (config_key, config_value, description) VALUES
('heartbeat_interval', '30', 'Intervalle de heartbeat en secondes'),
('polling_interval', '10', 'Intervalle de polling des messages en secondes'),
('retry_attempts', '3', 'Nombre de tentatives de renvoi en cas d\'échec'),
('auto_switch_enabled', 'true', 'Basculement automatique entre SIMs'),
('daily_sms_limit', '1000', 'Limite quotidienne de SMS par SIM'),
('monthly_sms_limit', '30000', 'Limite mensuelle de SMS par SIM'),
('api_rate_limit', '100', 'Limite de requêtes API par minute'),
('maintenance_mode', 'false', 'Mode maintenance activé/désactivé');

-- Création d'un utilisateur admin par défaut
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@smsgateway.local', '$2b$10$rQZ8kHWKtGOuGwHvKV.Ks.xQZ8kHWKtGOuGwHvKV.Ks.xQZ8kHWKtG', 'admin');

-- Vues pour les statistiques
CREATE OR REPLACE VIEW phone_stats AS
SELECT 
    p.id,
    p.model,
    p.status,
    COUNT(s.id) as sim_count,
    COUNT(CASE WHEN s.is_active = 1 THEN 1 END) as active_sims,
    COALESCE(SUM(s.messages_sent_today), 0) as messages_today,
    COALESCE(SUM(s.messages_sent_month), 0) as messages_month,
    p.last_heartbeat
FROM phones p
LEFT JOIN sims s ON p.id = s.phone_id
GROUP BY p.id, p.model, p.status, p.last_heartbeat;

CREATE OR REPLACE VIEW daily_stats AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_messages,
    COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_messages,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_messages,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_messages
FROM sms_history
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Index pour optimiser les performances
CREATE INDEX IF NOT EXISTS idx_sms_history_date ON sms_history(DATE(created_at));
CREATE INDEX IF NOT EXISTS idx_sims_messages_today ON sims(messages_sent_today);
CREATE INDEX IF NOT EXISTS idx_phones_heartbeat ON phones(last_heartbeat);

-- Procédure pour réinitialiser les compteurs quotidiens
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ResetDailyCounters()
BEGIN
    UPDATE sims SET messages_sent_today = 0;
    INSERT INTO system_config (config_key, config_value, description) 
    VALUES ('last_daily_reset', NOW(), 'Dernière réinitialisation des compteurs quotidiens')
    ON DUPLICATE KEY UPDATE config_value = NOW();
END //
DELIMITER ;

-- Procédure pour réinitialiser les compteurs mensuels
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ResetMonthlyCounters()
BEGIN
    UPDATE sims SET messages_sent_month = 0;
    INSERT INTO system_config (config_key, config_value, description) 
    VALUES ('last_monthly_reset', NOW(), 'Dernière réinitialisation des compteurs mensuels')
    ON DUPLICATE KEY UPDATE config_value = NOW();
END //
DELIMITER ;

-- Affichage des tables créées
SHOW TABLES;

SELECT 'Base de données SMS Gateway initialisée avec succès!' as message; 
 
 