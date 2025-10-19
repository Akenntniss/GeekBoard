-- Script pour ajouter les nouvelles colonnes de multiplicateurs de difficulté
-- À exécuter sur chaque base de données magasin

-- Ajouter les nouvelles colonnes (syntaxe compatible MySQL)
ALTER TABLE `calculator_settings` 
ADD COLUMN `difficulty_easy` decimal(3,1) NOT NULL DEFAULT 1.0 COMMENT 'Multiplicateur pour difficulté facile',
ADD COLUMN `difficulty_medium` decimal(3,1) NOT NULL DEFAULT 1.5 COMMENT 'Multiplicateur pour difficulté moyenne',
ADD COLUMN `difficulty_hard` decimal(3,1) NOT NULL DEFAULT 2.0 COMMENT 'Multiplicateur pour difficulté difficile';

-- Mettre à jour les valeurs existantes si elles sont NULL
UPDATE `calculator_settings` 
SET 
    `difficulty_easy` = 1.0,
    `difficulty_medium` = 1.5,
    `difficulty_hard` = 2.0
WHERE `id` = 1 AND (`difficulty_easy` IS NULL OR `difficulty_medium` IS NULL OR `difficulty_hard` IS NULL);

-- Vérification de la mise à jour
SELECT 'Table calculator_settings mise à jour avec succès' as message;
SELECT id, margin_min, margin_max, difficulty_easy, difficulty_medium, difficulty_hard, time_rate FROM `calculator_settings` WHERE `id` = 1;
