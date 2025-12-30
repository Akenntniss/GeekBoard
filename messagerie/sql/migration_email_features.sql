-- ============================================
-- Migration : Améliorations Système de Messagerie
-- Date : 2025-12-21
-- Description : Ajout des fonctionnalités style e-mail
-- ============================================

USE geekboard_mdg;

-- 1. Ajouter colonnes à la table conversations (avec vérification)
-- Vérifier et ajouter la colonne 'objet' si elle n'existe pas
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'geekboard_mdg' AND TABLE_NAME = 'conversations' AND COLUMN_NAME = 'objet');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE conversations ADD COLUMN objet VARCHAR(500) NULL COMMENT ''Objet de la conversation (style e-mail)'' AFTER titre', 
    'SELECT ''La colonne objet existe déjà'' AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter la colonne 'priorite' si elle n'existe pas
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'geekboard_mdg' AND TABLE_NAME = 'conversations' AND COLUMN_NAME = 'priorite');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE conversations ADD COLUMN priorite ENUM(''normale'', ''importante'', ''urgente'') DEFAULT ''normale'' COMMENT ''Niveau de priorité de la conversation'' AFTER objet', 
    'SELECT ''La colonne priorite existe déjà'' AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter la colonne 'statut' si elle n'existe pas
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'geekboard_mdg' AND TABLE_NAME = 'conversations' AND COLUMN_NAME = 'statut');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE conversations ADD COLUMN statut ENUM(''active'', ''archivee'', ''fermee'') DEFAULT ''active'' COMMENT ''Statut de la conversation'' AFTER priorite', 
    'SELECT ''La colonne statut existe déjà'' AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Créer la table de notifications de messagerie
CREATE TABLE IF NOT EXISTS notification_messagerie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ID de l\'utilisateur qui reçoit la notification',
    conversation_id INT NOT NULL COMMENT 'ID de la conversation concernée',
    message_id INT NOT NULL COMMENT 'ID du message qui a déclenché la notification',
    lu TINYINT(1) DEFAULT 0 COMMENT 'Indique si la notification a été lue',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création de la notification',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    
    INDEX idx_user_lu (user_id, lu) COMMENT 'Index pour récupérer rapidement les notifications non lues d\'un utilisateur',
    INDEX idx_conversation (conversation_id) COMMENT 'Index pour les notifications par conversation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Notifications de messagerie pour alerter les utilisateurs de nouveaux messages';

-- 3. Ajouter des index pour améliorer les performances
ALTER TABLE conversations 
ADD INDEX IF NOT EXISTS idx_priorite (priorite),
ADD INDEX IF NOT EXISTS idx_statut (statut),
ADD INDEX IF NOT EXISTS idx_derniere_activite (derniere_activite DESC);

ALTER TABLE messages
ADD INDEX IF NOT EXISTS idx_conversation_date (conversation_id, date_envoi DESC) COMMENT 'Pour récupérer rapidement les messages d\'une conversation';

-- 4. Mise à jour des conversations existantes (si elles existent)
-- Définir un objet par défaut basé sur le titre pour les conversations existantes
UPDATE conversations 
SET objet = CONCAT('Discussion: ', titre)
WHERE objet IS NULL AND titre IS NOT NULL;

-- Afficher un résumé des modifications
SELECT 
    'Colonnes ajoutées à conversations' AS Action,
    COUNT(*) AS Total
FROM conversations;

SELECT 
    'Table notification_messagerie créée' AS Action,
    COUNT(*) AS Notifications
FROM notification_messagerie;

-- Vérifier la structure finale de la table conversations
DESCRIBE conversations;

-- Vérifier la structure de la table notification_messagerie
DESCRIBE notification_messagerie;

-- ============================================
-- FIN DE LA MIGRATION
-- ============================================
