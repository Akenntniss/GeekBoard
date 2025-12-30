-- Migration: Ajout de la colonne is_online à la table users
-- Date: 2025-11-26
-- Description: Permet de tracker le statut en ligne/hors ligne des utilisateurs lors des pointages

ALTER TABLE users 
ADD COLUMN is_online TINYINT(1) DEFAULT 0 COMMENT 'Statut: 0=Hors Ligne, 1=En Ligne' 
AFTER active_repair_id;
