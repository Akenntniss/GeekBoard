-- Ajout de la colonne retry_count pour le système de retry intelligent
-- Cette colonne permet de suivre le nombre de tentatives de retry pour chaque message

-- Vérifier si la colonne existe déjà
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'sms_history' 
AND COLUMN_NAME = 'retry_count';

-- Ajouter la colonne retry_count si elle n'existe pas
ALTER TABLE sms_history 
ADD COLUMN retry_count INT DEFAULT 0 NOT NULL 
COMMENT 'Nombre de tentatives de retry pour ce message';

-- Ajouter un index pour optimiser les requêtes de retry
CREATE INDEX idx_sms_history_retry_count ON sms_history(retry_count);

-- Ajouter un index composé pour les requêtes de timeout
CREATE INDEX idx_sms_history_status_created ON sms_history(status, created_at);

-- Mettre à jour les messages existants pour initialiser retry_count à 0
UPDATE sms_history 
SET retry_count = 0 
WHERE retry_count IS NULL;

-- Afficher le résultat
SELECT 'Colonne retry_count ajoutée avec succès' as result;

-- Vérifier la structure de la table
DESCRIBE sms_history;
























