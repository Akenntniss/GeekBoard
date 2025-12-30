-- ============================================
-- Migration : Améliorations Système de Messagerie
-- Date : 2025-12-21
-- Description : Ajout des fonctionnalités style e-mail
-- Version simplifiée compatible MariaDB
-- ============================================

USE geekboard_mdg;

-- 1. Ajouter les colonnes à la table conversations
-- Note: On ignore les erreurs si les colonnes existent déjà

-- Colonne objet
ALTER TABLE conversations 
ADD COLUMN objet VARCHAR(500) NULL COMMENT 'Objet de la conversation (style e-mail)' AFTER titre;

-- Colonne priorite  
ALTER TABLE conversations 
ADD COLUMN priorite ENUM('normale', 'importante', 'urgente') DEFAULT 'normale' COMMENT 'Niveau de priorité' AFTER objet;

-- Colonne statut
ALTER TABLE conversations 
ADD COLUMN statut ENUM('active', 'archivee', 'fermee') DEFAULT 'active' COMMENT 'Statut de la conversation' AFTER priorite;

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
    
    INDEX idx_user_lu (user_id, lu),
    INDEX idx_conversation (conversation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ajouter des index pour améliorer les performances
ALTER TABLE conversations 
ADD INDEX idx_priorite (priorite);

ALTER TABLE conversations 
ADD INDEX idx_statut (statut);

ALTER TABLE conversations 
ADD INDEX idx_derniere_activite (derniere_activite DESC);

ALTER TABLE messages
ADD INDEX idx_conversation_date (conversation_id, date_envoi DESC);

-- 4. Mise à jour des conversations existantes
UPDATE conversations 
SET objet = CONCAT('Discussion: ', titre)
WHERE objet IS NULL AND titre IS NOT NULL;

-- ============================================
-- FIN DE LA MIGRATION
-- ============================================
