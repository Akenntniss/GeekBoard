-- Script de mise à jour de la table users pour le système de missions
-- Ce script ajoute les colonnes nécessaires pour les points XP et la cagnotte

-- Ajouter la colonne cagnotte si elle n'existe pas
ALTER TABLE users ADD COLUMN IF NOT EXISTS cagnotte DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Cagnotte de l\'utilisateur en euros';

-- Ajouter la colonne points_experience si elle n'existe pas
ALTER TABLE users ADD COLUMN IF NOT EXISTS points_experience INT DEFAULT 0 COMMENT 'Points d\'expérience de l\'utilisateur';

-- Ajouter la colonne score_total si elle n'existe pas
ALTER TABLE users ADD COLUMN IF NOT EXISTS score_total INT DEFAULT 0 COMMENT 'Score total de l\'utilisateur';

-- Ajouter la colonne niveau si elle n'existe pas
ALTER TABLE users ADD COLUMN IF NOT EXISTS niveau INT DEFAULT 1 COMMENT 'Niveau de l\'utilisateur';

-- Mettre à jour les valeurs par défaut pour les utilisateurs existants
UPDATE users SET cagnotte = 0.00 WHERE cagnotte IS NULL;
UPDATE users SET points_experience = 0 WHERE points_experience IS NULL;
UPDATE users SET score_total = 0 WHERE score_total IS NULL;
UPDATE users SET niveau = 1 WHERE niveau IS NULL;

-- Créer des index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_users_cagnotte ON users(cagnotte);
CREATE INDEX IF NOT EXISTS idx_users_points_experience ON users(points_experience);
CREATE INDEX IF NOT EXISTS idx_users_score_total ON users(score_total);
CREATE INDEX IF NOT EXISTS idx_users_niveau ON users(niveau);

-- Vérifier les colonnes ajoutées
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' 
AND COLUMN_NAME IN ('cagnotte', 'points_experience', 'score_total', 'niveau')
ORDER BY COLUMN_NAME; 