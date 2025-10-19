-- ================================================================================
-- SYSTÈME DE DEVIS MODERNE - CRÉATION DES TABLES
-- ================================================================================
-- Date de création: 2025-01-27
-- Description: Tables pour le système complet de devis avec pannes, solutions, logs et signatures
-- ================================================================================

-- 1. Table principale des devis
CREATE TABLE IF NOT EXISTS `devis` (
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
CREATE TABLE IF NOT EXISTS `devis_pannes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `devis_id` INT(11) NOT NULL,
    `titre` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `gravite` ENUM('faible', 'moyenne', 'elevee', 'critique') DEFAULT 'moyenne',
    `ordre` INT(11) DEFAULT 1,
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_devis` (`devis_id`),
    FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des solutions proposées
CREATE TABLE IF NOT EXISTS `devis_solutions` (
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
    INDEX `idx_devis` (`devis_id`),
    FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table des éléments détaillés de chaque solution
CREATE TABLE IF NOT EXISTS `devis_solutions_items` (
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
    INDEX `idx_solution` (`solution_id`),
    FOREIGN KEY (`solution_id`) REFERENCES `devis_solutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table des logs d'actions sur les devis
CREATE TABLE IF NOT EXISTS `devis_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `devis_id` INT(11) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `utilisateur_type` ENUM('employe', 'client', 'systeme') NOT NULL,
    `utilisateur_id` INT(11) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `donnees_supplementaires` JSON NULL,
    `date_action` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_devis` (`devis_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_date` (`date_action`),
    FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table des acceptations et signatures
CREATE TABLE IF NOT EXISTS `devis_acceptations` (
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
    INDEX `idx_devis` (`devis_id`),
    FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`solution_choisie_id`) REFERENCES `devis_solutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table des notifications SMS pour les devis
CREATE TABLE IF NOT EXISTS `devis_notifications` (
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
    INDEX `idx_statut` (`statut_envoi`),
    FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table des templates de messages pour les devis
CREATE TABLE IF NOT EXISTS `devis_templates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) NOT NULL,
    `type` ENUM('sms', 'email') NOT NULL,
    `sujet` VARCHAR(255) NULL COMMENT 'Pour les emails',
    `contenu` TEXT NOT NULL,
    `variables_disponibles` JSON COMMENT 'Liste des variables utilisables',
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
-- TRIGGERS POUR LA GESTION AUTOMATIQUE (version compatible)
-- ================================================================================

-- Trigger pour générer automatiquement le numéro de devis
DROP TRIGGER IF EXISTS `generate_devis_number`;
DELIMITER //
CREATE TRIGGER `generate_devis_number` BEFORE INSERT ON `devis`
FOR EACH ROW BEGIN
    DECLARE next_number BIGINT;
    DECLARE formatted_number VARCHAR(50);
    
    -- Récupérer le prochain numéro avec protection
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_devis, 8) AS UNSIGNED)), 0) + 1 
    INTO next_number 
    FROM devis 
    WHERE numero_devis LIKE CONCAT('DV-', YEAR(NOW()), '-%');
    
    -- Protection contre les valeurs invalides
    IF next_number IS NULL OR next_number <= 0 THEN
        SET next_number = 1;
    END IF;
    
    -- Formater le numéro
    SET formatted_number = CONCAT('DV-', YEAR(NOW()), '-', LPAD(next_number, 4, '0'));
    
    -- Assigner le numéro
    SET NEW.numero_devis = formatted_number;
    
    -- Générer le lien sécurisé
    IF NEW.lien_securise IS NULL THEN
        SET NEW.lien_securise = MD5(CONCAT(NEW.reparation_id, '-', NEW.client_id, '-', UNIX_TIMESTAMP(), '-', RAND()));
    END IF;
END//
DELIMITER ;

-- Trigger pour calculer automatiquement les totaux du devis
DROP TRIGGER IF EXISTS `update_devis_totals`;
DELIMITER //
CREATE TRIGGER `update_devis_totals` AFTER INSERT ON `devis_solutions`
FOR EACH ROW BEGIN
    UPDATE devis 
    SET total_ht = (
        SELECT SUM(prix_total) 
        FROM devis_solutions 
        WHERE devis_id = NEW.devis_id
    ),
    total_ttc = (
        SELECT SUM(prix_total) * (1 + taux_tva/100)
        FROM devis_solutions 
        WHERE devis_id = NEW.devis_id
    )
    WHERE id = NEW.devis_id;
END//
DELIMITER ;

-- Trigger pour logger automatiquement les actions importantes
DROP TRIGGER IF EXISTS `log_devis_status_change`;
DELIMITER //
CREATE TRIGGER `log_devis_status_change` AFTER UPDATE ON `devis`
FOR EACH ROW BEGIN
    IF OLD.statut != NEW.statut THEN
        INSERT INTO devis_logs (devis_id, action, description, utilisateur_type, date_action)
        VALUES (NEW.id, CONCAT('STATUT_CHANGE_', NEW.statut), CONCAT('Statut changé de "', OLD.statut, '" vers "', NEW.statut, '"'), 'systeme', NOW());
    END IF;
END//
DELIMITER ;

-- ================================================================================
-- INDEX POUR L'OPTIMISATION DES PERFORMANCES (version compatible)
-- ================================================================================

-- Index composites pour les requêtes fréquentes
-- Supprimer les index s'ils existent déjà pour éviter les erreurs
DROP INDEX IF EXISTS `idx_devis_reparation_statut` ON `devis`;
DROP INDEX IF EXISTS `idx_devis_client_date` ON `devis`;
DROP INDEX IF EXISTS `idx_logs_devis_action_date` ON `devis_logs`;
DROP INDEX IF EXISTS `idx_notifications_statut_date` ON `devis_notifications`;

CREATE INDEX `idx_devis_reparation_statut` ON `devis` (`reparation_id`, `statut`);
CREATE INDEX `idx_devis_client_date` ON `devis` (`client_id`, `date_creation`);
CREATE INDEX `idx_logs_devis_action_date` ON `devis_logs` (`devis_id`, `action`, `date_action`);
CREATE INDEX `idx_notifications_statut_date` ON `devis_notifications` (`statut_envoi`, `date_programmee`);

-- ================================================================================
-- VUES POUR FACILITER LES REQUÊTES
-- ================================================================================

-- Vue pour récupérer les devis avec informations client et réparation
DROP VIEW IF EXISTS `view_devis_complet`;
CREATE VIEW `view_devis_complet` AS
SELECT 
    d.*,
    c.nom as client_nom,
    c.prenom as client_prenom,
    c.telephone as client_telephone,
    c.email as client_email,
    r.type_appareil,
    r.modele as appareil_modele,
    r.description_probleme,
    e.nom as employe_nom,
    e.prenom as employe_prenom,
    COUNT(dp.id) as nb_pannes,
    COUNT(ds.id) as nb_solutions
FROM devis d
LEFT JOIN clients c ON d.client_id = c.id
LEFT JOIN reparations r ON d.reparation_id = r.id
LEFT JOIN employes e ON d.employe_id = e.id
LEFT JOIN devis_pannes dp ON d.id = dp.devis_id
LEFT JOIN devis_solutions ds ON d.id = ds.devis_id
GROUP BY d.id;

-- Vue pour les statistiques des devis
DROP VIEW IF EXISTS `view_devis_stats`;
CREATE VIEW `view_devis_stats` AS
SELECT 
    COUNT(*) as total_devis,
    COUNT(CASE WHEN statut = 'envoye' THEN 1 END) as devis_envoyes,
    COUNT(CASE WHEN statut = 'accepte' THEN 1 END) as devis_acceptes,
    COUNT(CASE WHEN statut = 'refuse' THEN 1 END) as devis_refuses,
    COUNT(CASE WHEN statut = 'expire' THEN 1 END) as devis_expires,
    ROUND(COUNT(CASE WHEN statut = 'accepte' THEN 1 END) * 100.0 / NULLIF(COUNT(CASE WHEN statut IN ('accepte', 'refuse') THEN 1 END), 0), 2) as taux_acceptation,
    AVG(total_ttc) as montant_moyen,
    SUM(CASE WHEN statut = 'accepte' THEN total_ttc ELSE 0 END) as ca_accepte
FROM devis
WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ================================================================================
-- PROCÉDURES STOCKÉES UTILES
-- ================================================================================

-- Procédure pour créer un devis complet
DROP PROCEDURE IF EXISTS `CreateDevisComplet`;
DELIMITER //
CREATE PROCEDURE `CreateDevisComplet`(
    IN p_reparation_id INT,
    IN p_employe_id INT,
    IN p_titre VARCHAR(255),
    IN p_description TEXT,
    OUT p_devis_id INT
)
BEGIN
    DECLARE v_client_id INT;
    
    -- Récupérer le client_id depuis la réparation
    SELECT client_id INTO v_client_id 
    FROM reparations 
    WHERE id = p_reparation_id;
    
    -- Créer le devis
    INSERT INTO devis (reparation_id, client_id, employe_id, titre, description_generale, date_expiration)
    VALUES (p_reparation_id, v_client_id, p_employe_id, p_titre, p_description, DATE_ADD(NOW(), INTERVAL 15 DAY));
    
    SET p_devis_id = LAST_INSERT_ID();
    
    -- Logger la création
    INSERT INTO devis_logs (devis_id, action, description, utilisateur_type, utilisateur_id)
    VALUES (p_devis_id, 'CREATION', 'Devis créé', 'employe', p_employe_id);
    
END//
DELIMITER ;

-- ================================================================================
-- FIN DU SCRIPT
-- ================================================================================ 