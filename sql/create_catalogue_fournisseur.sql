-- Script SQL pour créer la table catalogue_fournisseur
-- Exécuter ce script pour créer la structure de la base de données

CREATE TABLE IF NOT EXISTS `catalogue_fournisseur` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fournisseur_id` INT NOT NULL,
    `name` VARCHAR(500) NOT NULL,
    `url` VARCHAR(1000) DEFAULT NULL,
    `price` DECIMAL(10,2) DEFAULT NULL,
    `reference` VARCHAR(100) DEFAULT NULL,
    `stock` VARCHAR(50) DEFAULT NULL,
    `type` VARCHAR(100) DEFAULT NULL COMMENT 'Pièces détachées, Accessoires, Protections, Outillages',
    `device_type` VARCHAR(100) DEFAULT NULL COMMENT 'Téléphonie, Tablette, Montre, Ordinateur',
    `brand` VARCHAR(100) DEFAULT NULL COMMENT 'Marque (Samsung, Apple, etc.)',
    `series` VARCHAR(100) DEFAULT NULL COMMENT 'Série/Gamme (Galaxy S, iPhone, etc.)',
    `model` VARCHAR(200) DEFAULT NULL COMMENT 'Modèle spécifique',
    `date_import` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fournisseur` (`fournisseur_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_brand` (`brand`),
    INDEX `idx_device_type` (`device_type`),
    INDEX `idx_reference` (`reference`),
    FULLTEXT INDEX `idx_search` (`name`, `reference`, `brand`, `model`),
    FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
