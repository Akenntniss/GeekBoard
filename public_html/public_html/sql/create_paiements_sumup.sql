-- Table pour les paiements SumUp dans GeekBoard
-- À exécuter sur toutes les bases de données des shops

CREATE TABLE IF NOT EXISTS `paiements_sumup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reparation_id` int(11) NOT NULL,
  `checkout_id` varchar(255) NOT NULL,
  `checkout_reference` varchar(255) DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `statut_paiement` enum('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `transaction_code` varchar(255) DEFAULT NULL,
  `date_paiement` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `client_info` text DEFAULT NULL COMMENT 'Infos client JSON',
  `description` varchar(500) DEFAULT NULL,
  `erreur_message` text DEFAULT NULL,
  `webhook_data` text DEFAULT NULL COMMENT 'Données webhook JSON',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_checkout` (`checkout_id`),
  KEY `idx_reparation` (`reparation_id`),
  KEY `idx_statut` (`statut_paiement`),
  KEY `idx_date` (`date_paiement`),
  CONSTRAINT `fk_paiements_sumup_reparation` 
    FOREIGN KEY (`reparation_id`) 
    REFERENCES `reparations` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter une colonne à la table reparations pour le statut de paiement SumUp
ALTER TABLE `reparations` 
ADD COLUMN `sumup_checkout_id` varchar(255) DEFAULT NULL AFTER `prix`,
ADD COLUMN `sumup_statut` enum('none','pending','paid','failed') DEFAULT 'none' AFTER `sumup_checkout_id`,
ADD INDEX `idx_sumup_checkout` (`sumup_checkout_id`),
ADD INDEX `idx_sumup_statut` (`sumup_statut`);

-- Table pour les logs SumUp (optionnel, pour debug)
CREATE TABLE IF NOT EXISTS `sumup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `niveau` enum('INFO','WARNING','ERROR','DEBUG') NOT NULL DEFAULT 'INFO',
  `message` text NOT NULL,
  `contexte` text DEFAULT NULL COMMENT 'Données contextuelles JSON',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_niveau` (`niveau`),
  KEY `idx_date` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nettoyer les anciens logs (garder seulement 30 jours)
CREATE EVENT IF NOT EXISTS `cleanup_sumup_logs`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM `sumup_logs` 
  WHERE `date_creation` < DATE_SUB(NOW(), INTERVAL 30 DAY); 