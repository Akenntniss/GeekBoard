-- Table de configuration des relances automatiques
CREATE TABLE IF NOT EXISTS `relance_automatique_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `est_active` tinyint(1) DEFAULT 0,
  `relances_horaires` JSON DEFAULT NULL,
  `derniere_execution` datetime DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_shop` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour logger les relances automatiques
CREATE TABLE IF NOT EXISTS `relance_automatique_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `devis_id` int(11) NOT NULL,
  `heure_programmee` time NOT NULL,
  `date_execution` datetime NOT NULL,
  `statut` enum('succes','echec') DEFAULT 'succes',
  `message` text DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shop_date` (`shop_id`, `date_execution`),
  KEY `idx_devis` (`devis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer une configuration par défaut pour chaque shop existant
INSERT IGNORE INTO `relance_automatique_config` (`shop_id`, `est_active`, `relances_horaires`)
SELECT `id`, 0, JSON_ARRAY('09:00', '14:00', '17:00') 
FROM `shops` 
WHERE `active` = 1;
