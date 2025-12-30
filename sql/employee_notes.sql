-- Table pour les notes contextuelles sur les employés
CREATE TABLE IF NOT EXISTS `employee_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `note_type` ENUM('avertissement', 'incident', 'appreciation', 'remarque', 'sanction', 'autre') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `date_incident` DATE NOT NULL,
  `severity` ENUM('info', 'low', 'medium', 'high', 'critical') DEFAULT 'medium',
  `is_resolved` TINYINT(1) DEFAULT 0,
  `is_private` TINYINT(1) DEFAULT 1,
  `include_in_ai_analysis` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT NULL,
  INDEX `idx_employee` (`employee_id`),
  INDEX `idx_type` (`note_type`),
  INDEX `idx_severity` (`severity`),
  INDEX `idx_ai_inclusion` (`include_in_ai_analysis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
