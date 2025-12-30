-- Script pour ajouter les colonnes manquantes à la table time_tracking

-- Ajouter les colonnes de géolocalisation
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS location_in TEXT NULL AFTER clock_in,
ADD COLUMN IF NOT EXISTS location_out TEXT NULL AFTER clock_out,
ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL AFTER location_out;

-- Ajouter les colonnes d'approbation automatique
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS auto_approved BOOLEAN DEFAULT FALSE AFTER admin_approved,
ADD COLUMN IF NOT EXISTS approval_reason VARCHAR(255) NULL AFTER auto_approved;

-- Ajouter les colonnes de notes et timestamps
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER approval_reason,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Créer la table break_sessions si elle n'existe pas
CREATE TABLE IF NOT EXISTS break_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_tracking_id INT NOT NULL,
    break_start TIMESTAMP NOT NULL,
    break_end TIMESTAMP NULL,
    break_duration DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (time_tracking_id) REFERENCES time_tracking(id) ON DELETE CASCADE
);

-- Afficher la structure finale
SHOW COLUMNS FROM time_tracking;
