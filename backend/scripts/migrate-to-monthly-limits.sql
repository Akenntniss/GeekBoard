-- Script de migration pour passer aux limites mensuelles uniquement
-- Supprime daily_limit et recipients_today
-- Ajoute recipients_monthly pour remplacer recipients_today

USE sms_gateway;

-- Vérifier la structure actuelle
SELECT 'Structure actuelle de la table sims:' as message;
DESCRIBE sims;

-- Ajouter la nouvelle colonne recipients_monthly si elle n'existe pas
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND COLUMN_NAME = 'recipients_monthly') = 0,
    'ALTER TABLE sims ADD COLUMN recipients_monthly INT DEFAULT 0 COMMENT ''Nombre de destinataires uniques contactés ce mois''',
    'SELECT ''Colonne recipients_monthly existe déjà'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrer les données de recipients_today vers recipients_monthly (si nécessaire)
UPDATE sims 
SET recipients_monthly = COALESCE(recipients_monthly, recipients_today)
WHERE recipients_monthly IS NULL OR recipients_monthly = 0;

-- Supprimer la colonne daily_limit
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND COLUMN_NAME = 'daily_limit') > 0,
    'ALTER TABLE sims DROP COLUMN daily_limit',
    'SELECT ''Colonne daily_limit n''''existe pas'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Supprimer la colonne messages_sent_today
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND COLUMN_NAME = 'messages_sent_today') > 0,
    'ALTER TABLE sims DROP COLUMN messages_sent_today',
    'SELECT ''Colonne messages_sent_today n''''existe pas'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Supprimer la colonne recipients_today
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND COLUMN_NAME = 'recipients_today') > 0,
    'ALTER TABLE sims DROP COLUMN recipients_today',
    'SELECT ''Colonne recipients_today n''''existe pas'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Renommer recipient_limit en recipients_monthly_limit pour plus de clarté
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND COLUMN_NAME = 'recipient_limit') > 0,
    'ALTER TABLE sims CHANGE COLUMN recipient_limit recipients_monthly_limit INT DEFAULT 50 COMMENT ''Limite de destinataires uniques par mois''',
    'SELECT ''Colonne recipient_limit n''''existe pas'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Afficher la nouvelle structure
SELECT 'Nouvelle structure de la table sims:' as message;
DESCRIBE sims;

-- Afficher les données existantes pour vérification
SELECT 'Données existantes après migration:' as message;
SELECT id, phone_id, slot_index, carrier_name, monthly_limit, recipients_monthly_limit, recipients_monthly, messages_sent_month 
FROM sims;

SELECT 'Migration terminée avec succès!' as message; 