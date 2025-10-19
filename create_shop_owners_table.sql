-- Table pour les inscriptions des propriétaires de magasins
-- À exécuter dans la base de données principale mdgeek.top (geekboard_general)

-- Supprimer l'ancienne table si elle existe
DROP TABLE IF EXISTS `shop_owners`;

CREATE TABLE `shop_owners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `nom_commercial` varchar(200) NULL,
  `subdomain` varchar(50) NOT NULL UNIQUE,
  `email` varchar(150) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `adresse` text NOT NULL,
  `code_postal` varchar(10) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `cgu_acceptees` tinyint(1) NOT NULL DEFAULT 0,
  `cgv_acceptees` tinyint(1) NOT NULL DEFAULT 0,
  `shop_id` int(11) NULL, -- ID du magasin créé (foreign key vers shops.id)
  `statut` enum('en_attente', 'approuve', 'refuse', 'actif') DEFAULT 'en_attente',
  `date_inscription` timestamp DEFAULT CURRENT_TIMESTAMP,
  `date_creation_shop` timestamp NULL,
  `notes_admin` text NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_subdomain` (`subdomain`),
  KEY `idx_statut` (`statut`),
  FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
