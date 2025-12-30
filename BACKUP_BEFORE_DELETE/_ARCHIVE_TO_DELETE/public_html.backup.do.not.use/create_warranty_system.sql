-- =====================================
-- SYSTÈME DE GARANTIE GEEKBOARD
-- =====================================

-- 1. Créer la table des garanties
CREATE TABLE IF NOT EXISTS garanties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reparation_id INT NOT NULL,
    date_debut TIMESTAMP NOT NULL COMMENT 'Date de début de garantie (quand réparation effectuée)',
    date_fin TIMESTAMP NOT NULL COMMENT 'Date de fin de garantie calculée',
    duree_jours INT NOT NULL COMMENT 'Durée en jours (copie du paramètre au moment de la création)',
    statut ENUM('active', 'expiree', 'utilisee', 'annulee') DEFAULT 'active',
    description_garantie TEXT COMMENT 'Description de ce qui est garanti',
    notes TEXT COMMENT 'Notes administratives',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clés étrangères et index
    FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
    INDEX idx_reparation_id (reparation_id),
    INDEX idx_date_fin (date_fin),
    INDEX idx_statut (statut)
);

-- 2. Ajouter une colonne à la table reparations pour lier à la garantie
ALTER TABLE reparations 
ADD COLUMN garantie_id INT NULL AFTER proprietaire,
ADD COLUMN date_garantie_debut TIMESTAMP NULL AFTER garantie_id,
ADD COLUMN date_garantie_fin TIMESTAMP NULL AFTER date_garantie_debut,
ADD FOREIGN KEY (garantie_id) REFERENCES garanties(id) ON DELETE SET NULL;

-- 3. Créer la table des réclamations de garantie
CREATE TABLE IF NOT EXISTS reclamations_garantie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    garantie_id INT NOT NULL,
    reparation_id INT NOT NULL,
    client_id INT NOT NULL,
    date_reclamation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description_probleme TEXT NOT NULL,
    statut ENUM('en_attente', 'acceptee', 'refusee', 'traitee') DEFAULT 'en_attente',
    decision_admin TEXT COMMENT 'Décision et justification de l\'admin',
    nouvelle_reparation_id INT NULL COMMENT 'ID de la nouvelle réparation si acceptée',
    employe_traite_id INT NULL COMMENT 'Employé qui a traité la réclamation',
    date_traitement TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clés étrangères et index
    FOREIGN KEY (garantie_id) REFERENCES garanties(id) ON DELETE CASCADE,
    FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (nouvelle_reparation_id) REFERENCES reparations(id) ON DELETE SET NULL,
    FOREIGN KEY (employe_traite_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_garantie_id (garantie_id),
    INDEX idx_statut (statut),
    INDEX idx_date_reclamation (date_reclamation)
);

-- 4. Ajouter les paramètres de garantie
INSERT INTO parametres (cle, valeur, description) VALUES
('garantie_active', '1', 'Activer/désactiver le système de garantie (1=actif, 0=inactif)'),
('garantie_duree_defaut', '90', 'Durée par défaut de la garantie en jours'),
('garantie_description_defaut', 'Garantie pièces et main d\'œuvre', 'Description par défaut de la garantie'),
('garantie_auto_creation', '1', 'Création automatique de la garantie quand réparation effectuée (1=auto, 0=manuel)'),
('garantie_notification_expiration', '7', 'Nombre de jours avant expiration pour notifier (0=pas de notification)')
ON DUPLICATE KEY UPDATE 
valeur = VALUES(valeur), 
description = VALUES(description);

-- 5. Créer une vue pour faciliter les requêtes de garantie
CREATE OR REPLACE VIEW vue_garanties_actives AS
SELECT 
    g.id as garantie_id,
    g.date_debut,
    g.date_fin,
    g.duree_jours,
    g.statut as statut_garantie,
    g.description_garantie,
    r.id as reparation_id,
    r.type_appareil,
    r.modele,
    r.description_probleme,
    r.prix_reparation,
    c.id as client_id,
    c.nom,
    c.prenom,
    c.telephone,
    c.email,
    DATEDIFF(g.date_fin, NOW()) as jours_restants,
    CASE 
        WHEN g.date_fin < NOW() THEN 'Expirée'
        WHEN DATEDIFF(g.date_fin, NOW()) <= 7 THEN 'Expire bientôt'
        ELSE 'Active'
    END as alerte_expiration
FROM garanties g
JOIN reparations r ON g.reparation_id = r.id
JOIN clients c ON r.client_id = c.id
WHERE g.statut = 'active'
ORDER BY g.date_fin ASC;

-- 6. Trigger pour créer automatiquement la garantie quand une réparation passe en "effectuée"
DELIMITER //

CREATE TRIGGER trigger_creation_garantie 
AFTER UPDATE ON reparations
FOR EACH ROW
BEGIN
    DECLARE garantie_active INT DEFAULT 0;
    DECLARE garantie_auto INT DEFAULT 0;
    DECLARE duree_defaut INT DEFAULT 90;
    DECLARE description_defaut TEXT DEFAULT 'Garantie pièces et main d\'œuvre';
    DECLARE date_fin_calc TIMESTAMP;
    
    -- Vérifier si le système de garantie est actif et si la création auto est activée
    SELECT CAST(valeur AS UNSIGNED) INTO garantie_active 
    FROM parametres WHERE cle = 'garantie_active' LIMIT 1;
    
    SELECT CAST(valeur AS UNSIGNED) INTO garantie_auto 
    FROM parametres WHERE cle = 'garantie_auto_creation' LIMIT 1;
    
    -- Si le statut change vers "reparation_effectue" (ID 9) et qu'il n'y a pas déjà de garantie
    IF OLD.statut_id != 9 AND NEW.statut_id = 9 AND garantie_active = 1 AND garantie_auto = 1 AND NEW.garantie_id IS NULL THEN
        
        -- Récupérer les paramètres de garantie
        SELECT CAST(valeur AS UNSIGNED) INTO duree_defaut 
        FROM parametres WHERE cle = 'garantie_duree_defaut' LIMIT 1;
        
        SELECT valeur INTO description_defaut 
        FROM parametres WHERE cle = 'garantie_description_defaut' LIMIT 1;
        
        -- Calculer la date de fin
        SET date_fin_calc = DATE_ADD(NOW(), INTERVAL duree_defaut DAY);
        
        -- Créer la garantie
        INSERT INTO garanties (reparation_id, date_debut, date_fin, duree_jours, description_garantie, statut)
        VALUES (NEW.id, NOW(), date_fin_calc, duree_defaut, description_defaut, 'active');
        
        -- Mettre à jour la réparation avec les informations de garantie
        UPDATE reparations 
        SET 
            garantie_id = LAST_INSERT_ID(),
            date_garantie_debut = NOW(),
            date_garantie_fin = date_fin_calc
        WHERE id = NEW.id;
    END IF;
END//

DELIMITER ;

-- 7. Procédure stockée pour vérifier et mettre à jour les garanties expirées
DELIMITER //

CREATE PROCEDURE verifier_garanties_expirees()
BEGIN
    -- Mettre à jour les garanties expirées
    UPDATE garanties 
    SET statut = 'expiree', updated_at = NOW()
    WHERE statut = 'active' AND date_fin < NOW();
    
    -- Mettre à jour les réparations correspondantes
    UPDATE reparations r
    JOIN garanties g ON r.garantie_id = g.id
    SET r.date_garantie_fin = g.date_fin
    WHERE g.statut = 'expiree';
    
    -- Retourner le nombre de garanties mises à jour
    SELECT ROW_COUNT() as garanties_expirees;
END//

DELIMITER ;

-- 8. Index pour optimiser les performances
CREATE INDEX idx_reparations_statut_id ON reparations(statut_id);
CREATE INDEX idx_reparations_garantie_dates ON reparations(date_garantie_debut, date_garantie_fin);
CREATE INDEX idx_garanties_dates ON garanties(date_debut, date_fin);

-- 9. Données d'exemple pour test (optionnel - décommenter si besoin)
/*
-- Exemple de garantie pour une réparation existante
INSERT INTO garanties (reparation_id, date_debut, date_fin, duree_jours, description_garantie, statut)
SELECT 
    id as reparation_id,
    date_modification as date_debut,
    DATE_ADD(date_modification, INTERVAL 90 DAY) as date_fin,
    90 as duree_jours,
    'Garantie pièces et main d\'œuvre - Test' as description_garantie,
    'active' as statut
FROM reparations 
WHERE statut_id = 9 AND garantie_id IS NULL 
LIMIT 1;
*/

