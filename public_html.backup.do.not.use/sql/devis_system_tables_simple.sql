-- ================================================================================
-- SYSTÈME DE DEVIS MODERNE - VERSION SIMPLIFIÉE POUR COMPATIBILITÉ
-- ================================================================================
-- Date de création: 2025-01-27
-- Description: Tables pour le système complet de devis avec pannes, solutions, logs et signatures
-- ================================================================================

-- 1. Table principale des devis
CREATE TABLE `devis` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `reparation_id` INT(11) NOT NULL,
    `client_id` INT(11) NOT NULL,
    `employe_id` INT(11) NOT NULL,
    `numero_devis` VARCHAR(50) NOT NULL UNIQUE,
    `titre` VARCHAR(255) NOT NULL,
    `description_generale` TEXT,
    `statut` ENUM('brouillon', 'envoye', 'accepte', 'refuse', 'expire') DEFAULT 'brouillon',
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `date_envoi` TIMESTAMP NULL,
    `date_reponse` TIMESTAMP NULL,
    `date_expiration` TIMESTAMP NULL,
    `lien_securise` VARCHAR(100) UNIQUE,
    `total_ht` DECIMAL(10,2) DEFAULT 0.00,
    `taux_tva` DECIMAL(5,2) DEFAULT 20.00,
    `total_ttc` DECIMAL(10,2) DEFAULT 0.00,
    `solution_choisie_id` INT(11) NULL,
    `notes_acceptation` TEXT NULL,
    `ip_client` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_reparation` (`reparation_id`),
    INDEX `idx_client` (`client_id`),
    INDEX `idx_statut` (`statut`),
    INDEX `idx_lien_securise` (`lien_securise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table des pannes identifiées
CREATE TABLE `devis_pannes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `devis_id` INT(11) NOT NULL,
    `titre` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `gravite` ENUM('faible', 'moyenne', 'elevee', 'critique') DEFAULT 'moyenne',
    `ordre` INT(11) DEFAULT 1,
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_devis` (`devis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des solutions proposées
CREATE TABLE `devis_solutions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `devis_id` INT(11) NOT NULL,
    `nom` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `prix_total` DECIMAL(10,2) NOT NULL,
    `duree_reparation` VARCHAR(100),
    `garantie` VARCHAR(100),
    `recommandee` TINYINT(1) DEFAULT 0,
    `ordre` INT(11) DEFAULT 1,
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_devis` (`devis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table des éléments détaillés de chaque solution
CREATE TABLE `devis_solutions_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `solution_id` INT(11) NOT NULL,
    `nom` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `quantite` INT(11) DEFAULT 1,
    `prix_unitaire` DECIMAL(10,2) NOT NULL,
    `prix_total` DECIMAL(10,2) NOT NULL,
    `type` ENUM('piece', 'main_oeuvre', 'autre') DEFAULT 'piece',
    `ordre` INT(11) DEFAULT 1,
    PRIMARY KEY (`id`),
    INDEX `idx_solution` (`solution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table des logs d'actions sur les devis
CREATE TABLE `devis_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `devis_id` INT(11) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `utilisateur_type` ENUM('employe', 'client', 'systeme') NOT NULL,
    `utilisateur_id` INT(11) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `donnees_supplementaires` TEXT NULL,
    `date_action` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_devis` (`devis_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_date` (`date_action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table des acceptations et signatures
CREATE TABLE `devis_acceptations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `devis_id` INT(11) NOT NULL,
    `solution_choisie_id` INT(11) NOT NULL,
    `signature_client` LONGTEXT NOT NULL COMMENT 'Signature électronique en base64',
    `nom_complet` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NULL,
    `telephone` VARCHAR(20) NOT NULL,
    `adresse` TEXT NULL,
    `date_acceptation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_client` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `conditions_acceptees` TINYINT(1) DEFAULT 1,
    `newsletter_acceptee` TINYINT(1) DEFAULT 0,
    `hash_verification` VARCHAR(64) NOT NULL COMMENT 'Hash pour vérifier l intégrité',
    PRIMARY KEY (`id`),
    INDEX `idx_devis` (`devis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table des notifications SMS pour les devis
CREATE TABLE `devis_notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `devis_id` INT(11) NOT NULL,
    `type` ENUM('envoi_devis', 'rappel', 'acceptation', 'refus') NOT NULL,
    `telephone` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `statut_envoi` ENUM('en_attente', 'envoye', 'echec') DEFAULT 'en_attente',
    `date_programmee` TIMESTAMP NULL,
    `date_envoi` TIMESTAMP NULL,
    `erreur` TEXT NULL,
    `tentatives` INT(11) DEFAULT 0,
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_devis` (`devis_id`),
    INDEX `idx_statut` (`statut_envoi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table des templates de messages pour les devis
CREATE TABLE `devis_templates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) NOT NULL,
    `type` ENUM('sms', 'email') NOT NULL,
    `sujet` VARCHAR(255) NULL COMMENT 'Pour les emails',
    `contenu` TEXT NOT NULL,
    `variables_disponibles` TEXT COMMENT 'Liste des variables utilisables en JSON',
    `actif` TINYINT(1) DEFAULT 1,
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `date_modification` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================================
-- INSERTION DES DONNÉES DE BASE
-- ================================================================================

-- Templates de SMS par défaut
INSERT INTO `devis_templates` (`nom`, `type`, `contenu`, `variables_disponibles`, `actif`) VALUES
('SMS Envoi Devis', 'sms', 'Bonjour {CLIENT_PRENOM}, votre devis pour la réparation de votre {APPAREIL_TYPE} est prêt ! Consultez-le ici : {LIEN_DEVIS} - {NOM_MAGASIN}', '["CLIENT_PRENOM", "CLIENT_NOM", "APPAREIL_TYPE", "APPAREIL_MODELE", "LIEN_DEVIS", "NOM_MAGASIN", "NUMERO_DEVIS"]', 1),
('SMS Rappel Devis', 'sms', 'Rappel : Votre devis #{NUMERO_DEVIS} expire bientôt. Consultez-le rapidement : {LIEN_DEVIS} - {NOM_MAGASIN}', '["CLIENT_PRENOM", "CLIENT_NOM", "NUMERO_DEVIS", "LIEN_DEVIS", "NOM_MAGASIN", "DATE_EXPIRATION"]', 1),
('SMS Acceptation Devis', 'sms', 'Merci {CLIENT_PRENOM} ! Votre devis a été accepté. Nous commençons la réparation de votre {APPAREIL_TYPE}. - {NOM_MAGASIN}', '["CLIENT_PRENOM", "CLIENT_NOM", "APPAREIL_TYPE", "SOLUTION_CHOISIE", "NOM_MAGASIN"]', 1),
('SMS Refus Devis', 'sms', 'Bonjour {CLIENT_PRENOM}, nous avons bien reçu votre refus du devis. Votre {APPAREIL_TYPE} vous attend en magasin. - {NOM_MAGASIN}', '["CLIENT_PRENOM", "CLIENT_NOM", "APPAREIL_TYPE", "NOM_MAGASIN"]', 1);

-- Templates d'email par défaut
INSERT INTO `devis_templates` (`nom`, `type`, `sujet`, `contenu`, `variables_disponibles`, `actif`) VALUES
('Email Envoi Devis', 'email', 'Votre devis #{NUMERO_DEVIS} est prêt', '<h2>Bonjour {CLIENT_PRENOM},</h2><p>Votre devis pour la réparation de votre <strong>{APPAREIL_TYPE} {APPAREIL_MODELE}</strong> est maintenant disponible.</p><p><a href="{LIEN_DEVIS}" style="background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Consulter mon devis</a></p><p>Ce devis est valable jusqu\'au {DATE_EXPIRATION}.</p><p>Cordialement,<br>{NOM_MAGASIN}</p>', '["CLIENT_PRENOM", "CLIENT_NOM", "APPAREIL_TYPE", "APPAREIL_MODELE", "LIEN_DEVIS", "NOM_MAGASIN", "NUMERO_DEVIS", "DATE_EXPIRATION"]', 1);

-- ================================================================================
-- FIN DU SCRIPT
-- ================================================================================ 