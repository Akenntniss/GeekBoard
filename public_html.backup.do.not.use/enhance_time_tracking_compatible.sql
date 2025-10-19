-- Script SQL compatible pour améliorer la table time_tracking
-- Base de données: geekboard_mkmkmk

USE geekboard_mkmkmk;

-- Ajouter les colonnes de géolocalisation précise
ALTER TABLE time_tracking ADD COLUMN latitude_in DECIMAL(10, 8) NULL COMMENT 'Latitude GPS arrivée';
ALTER TABLE time_tracking ADD COLUMN longitude_in DECIMAL(11, 8) NULL COMMENT 'Longitude GPS arrivée';
ALTER TABLE time_tracking ADD COLUMN latitude_out DECIMAL(10, 8) NULL COMMENT 'Latitude GPS départ';
ALTER TABLE time_tracking ADD COLUMN longitude_out DECIMAL(11, 8) NULL COMMENT 'Longitude GPS départ';
ALTER TABLE time_tracking ADD COLUMN gps_accuracy_in FLOAT NULL COMMENT 'Précision GPS arrivée (mètres)';
ALTER TABLE time_tracking ADD COLUMN gps_accuracy_out FLOAT NULL COMMENT 'Précision GPS départ (mètres)';
ALTER TABLE time_tracking ADD COLUMN altitude_in FLOAT NULL COMMENT 'Altitude GPS arrivée';
ALTER TABLE time_tracking ADD COLUMN altitude_out FLOAT NULL COMMENT 'Altitude GPS départ';

-- Ajouter les colonnes d'informations de l'appareil
ALTER TABLE time_tracking ADD COLUMN device_fingerprint TEXT NULL COMMENT 'Empreinte digitale de l\'appareil';
ALTER TABLE time_tracking ADD COLUMN screen_resolution VARCHAR(20) NULL COMMENT 'Résolution écran';
ALTER TABLE time_tracking ADD COLUMN browser_language VARCHAR(10) NULL COMMENT 'Langue du navigateur';
ALTER TABLE time_tracking ADD COLUMN timezone_offset INT NULL COMMENT 'Décalage horaire en minutes';
ALTER TABLE time_tracking ADD COLUMN platform VARCHAR(50) NULL COMMENT 'Plateforme système';
ALTER TABLE time_tracking ADD COLUMN cpu_cores INT NULL COMMENT 'Nombre de cœurs CPU';
ALTER TABLE time_tracking ADD COLUMN memory_gb FLOAT NULL COMMENT 'Mémoire RAM en GB';

-- Ajouter les colonnes de données réseau
ALTER TABLE time_tracking ADD COLUMN connection_type VARCHAR(20) NULL COMMENT 'Type de connexion';
ALTER TABLE time_tracking ADD COLUMN connection_speed VARCHAR(20) NULL COMMENT 'Vitesse de connexion';
ALTER TABLE time_tracking ADD COLUMN ip_v6 VARCHAR(45) NULL COMMENT 'Adresse IPv6';

-- Ajouter les colonnes de sécurité et détection de fraude
ALTER TABLE time_tracking ADD COLUMN battery_level FLOAT NULL COMMENT 'Niveau de batterie';
ALTER TABLE time_tracking ADD COLUMN is_charging BOOLEAN NULL COMMENT 'Appareil en charge';
ALTER TABLE time_tracking ADD COLUMN device_orientation VARCHAR(20) NULL COMMENT 'Orientation de l\'appareil';
ALTER TABLE time_tracking ADD COLUMN canvas_fingerprint VARCHAR(255) NULL COMMENT 'Empreinte Canvas';
ALTER TABLE time_tracking ADD COLUMN webgl_fingerprint VARCHAR(255) NULL COMMENT 'Empreinte WebGL';
ALTER TABLE time_tracking ADD COLUMN audio_fingerprint VARCHAR(255) NULL COMMENT 'Empreinte Audio';

-- Ajouter les colonnes d'horodatage précis
ALTER TABLE time_tracking ADD COLUMN client_timestamp TIMESTAMP NULL COMMENT 'Horodatage côté client';
ALTER TABLE time_tracking ADD COLUMN server_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Horodatage côté serveur';
ALTER TABLE time_tracking ADD COLUMN processing_time_ms INT NULL COMMENT 'Temps de traitement en millisecondes';

-- Ajouter les colonnes de détection VPN/Proxy
ALTER TABLE time_tracking ADD COLUMN is_vpn_proxy BOOLEAN NULL COMMENT 'Détection VPN/Proxy';
ALTER TABLE time_tracking ADD COLUMN isp_name VARCHAR(100) NULL COMMENT 'Nom du fournisseur internet';
ALTER TABLE time_tracking ADD COLUMN country_code VARCHAR(3) NULL COMMENT 'Code pays';
ALTER TABLE time_tracking ADD COLUMN city_name VARCHAR(100) NULL COMMENT 'Nom de la ville';

-- Créer des index pour optimiser les performances
CREATE INDEX idx_time_tracking_gps_in ON time_tracking(latitude_in, longitude_in);
CREATE INDEX idx_time_tracking_gps_out ON time_tracking(latitude_out, longitude_out);
CREATE INDEX idx_time_tracking_device ON time_tracking(device_fingerprint(50));
CREATE INDEX idx_time_tracking_security ON time_tracking(is_vpn_proxy, country_code);
CREATE INDEX idx_time_tracking_timestamps ON time_tracking(client_timestamp, server_timestamp);

-- Afficher la structure mise à jour
DESCRIBE time_tracking;
