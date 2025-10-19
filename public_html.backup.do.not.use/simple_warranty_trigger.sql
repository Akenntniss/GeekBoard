-- Trigger simple pour créer automatiquement la garantie
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
    IF OLD.statut_id != 9 AND NEW.statut_id = 9 AND garantie_active = 1 AND garantie_auto = 1 THEN
        
        -- Récupérer les paramètres de garantie
        SELECT CAST(valeur AS UNSIGNED) INTO duree_defaut 
        FROM parametres WHERE cle = 'garantie_duree_defaut' LIMIT 1;
        
        SELECT valeur INTO description_defaut 
        FROM parametres WHERE cle = 'garantie_description_defaut' LIMIT 1;
        
        -- Calculer la date de fin
        SET date_fin_calc = DATE_ADD(NOW(), INTERVAL duree_defaut DAY);
        
        -- Créer la garantie seulement si elle n'existe pas déjà
        IF NOT EXISTS (SELECT 1 FROM garanties WHERE reparation_id = NEW.id) THEN
            INSERT INTO garanties (reparation_id, date_debut, date_fin, duree_jours, description_garantie, statut)
            VALUES (NEW.id, NOW(), date_fin_calc, duree_defaut, description_defaut, 'active');
        END IF;
        
    END IF;
END//

DELIMITER ;

