-- Table pour les événements et notes magasin
CREATE TABLE IF NOT EXISTS `shop_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `note_type` ENUM('fermeture', 'travaux', 'evenement', 'probleme_technique', 'stock', 'autre') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `date_start` DATE NOT NULL,
  `date_end` DATE NULL,
  `impact_level` ENUM('info', 'low', 'medium', 'high', 'critical') DEFAULT 'medium',
  `affects_kpi` TINYINT(1) DEFAULT 1,
  `include_in_ai_analysis` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT NULL,
  INDEX `idx_dates` (`date_start`, `date_end`),
  INDEX `idx_type` (`note_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
