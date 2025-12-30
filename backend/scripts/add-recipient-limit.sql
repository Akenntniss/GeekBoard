-- Script pour ajouter la colonne recipient_limit à la table sims
USE sms_gateway;

-- Vérifier et ajouter la colonne recipient_limit
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND COLUMN_NAME = 'recipient_limit') = 0,
    'ALTER TABLE sims ADD COLUMN recipient_limit INT DEFAULT 50 COMMENT ''Limite de destinataires uniques par jour''',
    'SELECT ''Colonne recipient_limit existe déjà'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter la colonne recipients_today
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'sms_gateway' 
     AND TABLE_NAME = 'sims' 
     AND COLUMN_NAME = 'recipients_today') = 0,
    'ALTER TABLE sims ADD COLUMN recipients_today INT DEFAULT 0 COMMENT ''Nombre de destinataires uniques contactés aujourd''''hui''',
    'SELECT ''Colonne recipients_today existe déjà'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mettre à jour les SIM existantes avec des valeurs par défaut si les colonnes sont NULL
UPDATE sims 
SET recipient_limit = COALESCE(recipient_limit, 50), 
    recipients_today = COALESCE(recipients_today, 0);

-- Afficher la structure mise à jour
DESCRIBE sims;

SELECT 'Colonnes recipient_limit et recipients_today ajoutées avec succès!' as message; 