-- Script SQL pour améliorer la table time_tracking avec tracking anti-triche maximal
-- Base de données: geekboard_mkmkmk

USE geekboard_mkmkmk;

-- Ajouter les colonnes de géolocalisation précise
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS latitude_in DECIMAL(10, 8) NULL COMMENT 'Latitude GPS arrivée',
ADD COLUMN IF NOT EXISTS longitude_in DECIMAL(11, 8) NULL COMMENT 'Longitude GPS arrivée',
ADD COLUMN IF NOT EXISTS latitude_out DECIMAL(10, 8) NULL COMMENT 'Latitude GPS départ',
ADD COLUMN IF NOT EXISTS longitude_out DECIMAL(11, 8) NULL COMMENT 'Longitude GPS départ',
ADD COLUMN IF NOT EXISTS gps_accuracy_in FLOAT NULL COMMENT 'Précision GPS arrivée (mètres)',
ADD COLUMN IF NOT EXISTS gps_accuracy_out FLOAT NULL COMMENT 'Précision GPS départ (mètres)',
ADD COLUMN IF NOT EXISTS altitude_in FLOAT NULL COMMENT 'Altitude GPS arrivée',
ADD COLUMN IF NOT EXISTS altitude_out FLOAT NULL COMMENT 'Altitude GPS départ';

-- Ajouter les colonnes d'informations de l'appareil
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS device_fingerprint TEXT NULL COMMENT 'Empreinte digitale de l\'appareil',
ADD COLUMN IF NOT EXISTS screen_resolution VARCHAR(20) NULL COMMENT 'Résolution écran (ex: 1920x1080)',
ADD COLUMN IF NOT EXISTS browser_language VARCHAR(10) NULL COMMENT 'Langue du navigateur',
ADD COLUMN IF NOT EXISTS timezone_offset INT NULL COMMENT 'Décalage horaire en minutes',
ADD COLUMN IF NOT EXISTS platform VARCHAR(50) NULL COMMENT 'Plateforme système (Windows, Mac, etc)',
ADD COLUMN IF NOT EXISTS cpu_cores INT NULL COMMENT 'Nombre de cœurs CPU',
ADD COLUMN IF NOT EXISTS memory_gb FLOAT NULL COMMENT 'Mémoire RAM en GB';

-- Ajouter les colonnes de données réseau
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS connection_type VARCHAR(20) NULL COMMENT 'Type de connexion (4g, wifi, etc)',
ADD COLUMN IF NOT EXISTS connection_speed VARCHAR(20) NULL COMMENT 'Vitesse de connexion',
ADD COLUMN IF NOT EXISTS ip_v6 VARCHAR(45) NULL COMMENT 'Adresse IPv6';

-- Ajouter les colonnes de sécurité et détection de fraude
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS battery_level FLOAT NULL COMMENT 'Niveau de batterie (%)',
ADD COLUMN IF NOT EXISTS is_charging BOOLEAN NULL COMMENT 'Appareil en charge',
ADD COLUMN IF NOT EXISTS device_orientation VARCHAR(20) NULL COMMENT 'Orientation de l\'appareil',
ADD COLUMN IF NOT EXISTS canvas_fingerprint VARCHAR(255) NULL COMMENT 'Empreinte Canvas',
ADD COLUMN IF NOT EXISTS webgl_fingerprint VARCHAR(255) NULL COMMENT 'Empreinte WebGL',
ADD COLUMN IF NOT EXISTS audio_fingerprint VARCHAR(255) NULL COMMENT 'Empreinte Audio';

-- Ajouter les colonnes d'horodatage précis
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS client_timestamp TIMESTAMP NULL COMMENT 'Horodatage côté client',
ADD COLUMN IF NOT EXISTS server_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Horodatage côté serveur',
ADD COLUMN IF NOT EXISTS processing_time_ms INT NULL COMMENT 'Temps de traitement en millisecondes';

-- Ajouter les colonnes de détection VPN/Proxy
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS is_vpn_proxy BOOLEAN NULL COMMENT 'Détection VPN/Proxy',
ADD COLUMN IF NOT EXISTS isp_name VARCHAR(100) NULL COMMENT 'Nom du fournisseur internet',
ADD COLUMN IF NOT EXISTS country_code VARCHAR(3) NULL COMMENT 'Code pays',
ADD COLUMN IF NOT EXISTS city_name VARCHAR(100) NULL COMMENT 'Nom de la ville';

-- Améliorer les colonnes existantes si elles n'existent pas déjà
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS admin_notes TEXT NULL COMMENT 'Notes administrateur',
ADD COLUMN IF NOT EXISTS auto_approved BOOLEAN DEFAULT FALSE COMMENT 'Approbation automatique',
ADD COLUMN IF NOT EXISTS approval_reason VARCHAR(255) NULL COMMENT 'Raison d\'approbation/rejet';

-- Améliorer les colonnes de localisation si elles n'existent pas
ALTER TABLE time_tracking 
ADD COLUMN IF NOT EXISTS location_in TEXT NULL COMMENT 'Description localisation arrivée',
ADD COLUMN IF NOT EXISTS location_out TEXT NULL COMMENT 'Description localisation départ';

-- Créer des index pour optimiser les performances
CREATE INDEX IF NOT EXISTS idx_time_tracking_gps_in ON time_tracking(latitude_in, longitude_in);
CREATE INDEX IF NOT EXISTS idx_time_tracking_gps_out ON time_tracking(latitude_out, longitude_out);
CREATE INDEX IF NOT EXISTS idx_time_tracking_device ON time_tracking(device_fingerprint(50));
CREATE INDEX IF NOT EXISTS idx_time_tracking_security ON time_tracking(is_vpn_proxy, country_code);
CREATE INDEX IF NOT EXISTS idx_time_tracking_timestamps ON time_tracking(client_timestamp, server_timestamp);

-- Afficher la structure mise à jour
DESCRIBE time_tracking;
