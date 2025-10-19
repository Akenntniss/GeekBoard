-- Table pour stocker les paramètres du calculateur de prix
-- À exécuter sur chaque base de données magasin

CREATE TABLE IF NOT EXISTS `calculator_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `margin_min` decimal(5,2) NOT NULL DEFAULT 30.00 COMMENT 'Marge minimum en pourcentage',
  `margin_max` decimal(5,2) NOT NULL DEFAULT 60.00 COMMENT 'Marge maximum en pourcentage',
  `difficulty_multiplier` decimal(3,1) NOT NULL DEFAULT 1.5 COMMENT 'Multiplicateur pour la difficulté moyenne',
  `time_rate` decimal(5,2) NOT NULL DEFAULT 25.00 COMMENT 'Tarif horaire en euros',
  `google_api_key` varchar(255) DEFAULT NULL COMMENT 'Clé API Google Custom Search',
  `google_search_engine_id` varchar(255) DEFAULT NULL COMMENT 'ID du moteur de recherche Google personnalisé',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paramètres du calculateur de prix de réparation';

-- Insertion des valeurs par défaut
INSERT INTO `calculator_settings` (
  `id`, 
  `margin_min`, 
  `margin_max`, 
  `difficulty_multiplier`, 
  `time_rate`, 
  `google_api_key`, 
  `google_search_engine_id`
) VALUES (
  1, 
  30.00, 
  60.00, 
  1.5, 
  25.00, 
  NULL, 
  NULL
) ON DUPLICATE KEY UPDATE
  `margin_min` = VALUES(`margin_min`),
  `margin_max` = VALUES(`margin_max`),
  `difficulty_multiplier` = VALUES(`difficulty_multiplier`),
  `time_rate` = VALUES(`time_rate`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Vérification de la création
SELECT 'Table calculator_settings créée avec succès' as message;
SELECT * FROM `calculator_settings` WHERE `id` = 1;
