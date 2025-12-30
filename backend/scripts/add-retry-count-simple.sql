-- Script simple pour ajouter la colonne retry_count
ALTER TABLE sms_history ADD COLUMN retry_count INT DEFAULT 0 NOT NULL;
