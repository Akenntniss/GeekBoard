-- Script pour ajouter la colonne is_default à la table sims
USE sms_gateway;

-- Vérifier et ajouter la colonne is_default
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND COLUMN_NAME = 'is_default') = 0,
    'ALTER TABLE sims ADD COLUMN is_default BOOLEAN DEFAULT FALSE COMMENT ''Indique si cette SIM est la SIM par défaut pour l''''envoi de SMS''',
    'SELECT ''Colonne is_default existe déjà'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter un index pour optimiser les requêtes sur is_default
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND INDEX_NAME = 'idx_is_default') = 0,
    'ALTER TABLE sims ADD INDEX idx_is_default (is_default)',
    'SELECT ''Index idx_is_default existe déjà'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter la configuration pour la SIM par défaut dans system_config si elle n'existe pas
INSERT IGNORE INTO system_config (config_key, config_value, description) VALUES
('default_sim_id', NULL, 'ID de la SIM par défaut pour l''envoi de SMS'),
('auto_failover_enabled', 'true', 'Basculement automatique vers une autre SIM quand la SIM par défaut atteint ses limites');

-- Afficher la structure mise à jour
DESCRIBE sims;

SELECT 'Colonne is_default ajoutée avec succès!' as message; 