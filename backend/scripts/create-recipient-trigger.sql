-- Trigger pour mettre à jour automatiquement le compteur de destinataires uniques
-- Ce trigger se déclenche après chaque insertion dans sms_history

DELIMITER $$

DROP TRIGGER IF EXISTS update_recipient_counter$$

CREATE TRIGGER update_recipient_counter
AFTER INSERT ON sms_history
FOR EACH ROW
BEGIN
    DECLARE recipient_count INT DEFAULT 0;
    
    -- Compter combien de fois ce destinataire a été contacté ce mois-ci par cette SIM
    SELECT COUNT(*) INTO recipient_count
    FROM sms_history 
    WHERE sim_id = NEW.sim_id 
      AND recipient = NEW.recipient 
      AND YEAR(created_at) = YEAR(CURDATE()) 
      AND MONTH(created_at) = MONTH(CURDATE());
    
    -- Si c'est la première fois ce mois-ci, incrémenter le compteur
    IF recipient_count = 1 THEN
        UPDATE sims 
        SET recipients_monthly = recipients_monthly + 1 
        WHERE id = NEW.sim_id;
    END IF;
END$$

DELIMITER ; 