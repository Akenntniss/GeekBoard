-- Script d'initialisation simplifié SMS Gateway
USE sms_gateway;

-- Supprimer les tables existantes si elles existent (dans l'ordre inverse des dépendances)
DROP TABLE IF EXISTS sms_history;
DROP TABLE IF EXISTS sims;
DROP TABLE IF EXISTS phones;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS system_config;

-- Table des téléphones
CREATE TABLE phones (
    id VARCHAR(36) PRIMARY KEY,
    model VARCHAR(255) NOT NULL,
    android_version VARCHAR(50) NOT NULL,
    app_version VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    last_heartbeat TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des cartes SIM
CREATE TABLE sims (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table de l'historique des SMS
CREATE TABLE sms_history (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table de configuration système
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT true,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des configurations par défaut
INSERT INTO system_config (config_key, config_value, description) VALUES
('heartbeat_interval', '30', 'Intervalle de heartbeat en secondes'),
('polling_interval', '10', 'Intervalle de polling des messages en secondes'),
('retry_attempts', '3', 'Nombre de tentatives de renvoi en cas d\'échec'),
('auto_switch_enabled', 'true', 'Basculement automatique entre SIMs'),
('daily_sms_limit', '1000', 'Limite quotidienne de SMS par SIM'),
('monthly_sms_limit', '30000', 'Limite mensuelle de SMS par SIM'),
('api_rate_limit', '100', 'Limite de requêtes API par minute'),
('maintenance_mode', 'false', 'Mode maintenance activé/désactivé');

-- Création d'un utilisateur admin par défaut (mot de passe: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@smsgateway.local', '$2b$10$rQZ8kHWKtGOuGwHvKV.Ks.xQZ8kHWKtGOuGwHvKV.Ks.xQZ8kHWKtG', 'admin');

-- Affichage des tables créées
SHOW TABLES;

SELECT 'Base de données SMS Gateway initialisée avec succès!' as message; 
USE sms_gateway;

-- Supprimer les tables existantes si elles existent (dans l'ordre inverse des dépendances)
DROP TABLE IF EXISTS sms_history;
DROP TABLE IF EXISTS sims;
DROP TABLE IF EXISTS phones;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS system_config;

-- Table des téléphones
CREATE TABLE phones (
    id VARCHAR(36) PRIMARY KEY,
    model VARCHAR(255) NOT NULL,
    android_version VARCHAR(50) NOT NULL,
    app_version VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    last_heartbeat TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des cartes SIM
CREATE TABLE sims (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table de l'historique des SMS
CREATE TABLE sms_history (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table de configuration système
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT true,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des configurations par défaut
INSERT INTO system_config (config_key, config_value, description) VALUES
('heartbeat_interval', '30', 'Intervalle de heartbeat en secondes'),
('polling_interval', '10', 'Intervalle de polling des messages en secondes'),
('retry_attempts', '3', 'Nombre de tentatives de renvoi en cas d\'échec'),
('auto_switch_enabled', 'true', 'Basculement automatique entre SIMs'),
('daily_sms_limit', '1000', 'Limite quotidienne de SMS par SIM'),
('monthly_sms_limit', '30000', 'Limite mensuelle de SMS par SIM'),
('api_rate_limit', '100', 'Limite de requêtes API par minute'),
('maintenance_mode', 'false', 'Mode maintenance activé/désactivé');

-- Création d'un utilisateur admin par défaut (mot de passe: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@smsgateway.local', '$2b$10$rQZ8kHWKtGOuGwHvKV.Ks.xQZ8kHWKtGOuGwHvKV.Ks.xQZ8kHWKtG', 'admin');

-- Affichage des tables créées
SHOW TABLES;

SELECT 'Base de données SMS Gateway initialisée avec succès!' as message; 
 
 