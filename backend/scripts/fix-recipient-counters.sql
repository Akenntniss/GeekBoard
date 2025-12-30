-- Script pour recalculer tous les compteurs de destinataires
-- Basé sur l'historique réel des SMS envoyés

-- Mettre à jour tous les compteurs de destinataires pour le mois actuel
UPDATE sims s
SET recipients_monthly = (
    SELECT COUNT(DISTINCT h.recipient)
    FROM sms_history h
    WHERE h.sim_id = s.id
      AND YEAR(h.created_at) = YEAR(CURDATE())
      AND MONTH(h.created_at) = MONTH(CURDATE())
);

-- Afficher le résultat
SELECT 
    s.id,
    s.carrier_name,
    s.phone_number,
    s.recipients_monthly as recipients_uniques_ce_mois,
    s.recipients_monthly_limit as limite_destinataires,
    ROUND((s.recipients_monthly / s.recipients_monthly_limit) * 100, 2) as pourcentage_utilise
FROM sims s
ORDER BY s.carrier_name; 