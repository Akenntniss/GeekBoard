-- Script pour ajouter les colonnes manquantes à la table time_tracking
-- Version compatible MySQL

-- Ajouter les colonnes de géolocalisation (ignorer les erreurs si elles existent déjà)
ALTER TABLE time_tracking ADD COLUMN location_in TEXT NULL AFTER clock_in;
ALTER TABLE time_tracking ADD COLUMN location_out TEXT NULL AFTER clock_out;
ALTER TABLE time_tracking ADD COLUMN ip_address VARCHAR(45) NULL AFTER location_out;

-- Ajouter les colonnes d'approbation automatique
ALTER TABLE time_tracking ADD COLUMN auto_approved BOOLEAN DEFAULT FALSE AFTER admin_approved;
ALTER TABLE time_tracking ADD COLUMN approval_reason VARCHAR(255) NULL AFTER auto_approved;

-- Ajouter les colonnes de notes et timestamps
ALTER TABLE time_tracking ADD COLUMN notes TEXT NULL AFTER approval_reason;
ALTER TABLE time_tracking ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Créer la table break_sessions si elle n'existe pas
CREATE TABLE IF NOT EXISTS break_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_tracking_id INT NOT NULL,
    break_start TIMESTAMP NOT NULL,
    break_end TIMESTAMP NULL,
    break_duration DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (time_tracking_id)
);

-- Afficher la structure finale
SHOW COLUMNS FROM time_tracking;
