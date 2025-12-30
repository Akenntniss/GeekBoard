-- Script pour ajouter les templates SMS pour les commandes de pièces
-- Base de données: MDG (à adapter selon votre base)

-- Template "Commande Reçue" pour notifier que la pièce est arrivée
INSERT INTO sms_templates (
    nom, 
    contenu, 
    statut_id, 
    est_actif, 
    created_at, 
    updated_at, 
    code, 
    variables,
    type
) VALUES (
    'Commande Pièce Arrivée',
    '[COMPANY_NAME]
Bonne nouvelle ! La pièce pour votre [APPAREIL_TYPE] est arrivée.
Vous pouvez venir en magasin pour : Récupérer la pièce commandée ( ou Déposer votre appareil pour sa réparation )
Réparation #[REPARATION_ID]
Cordialement,
[COMPANY_NAME]
Tel : [COMPANY_PHONE]',
    NULL,
    1,
    NOW(),
    NOW(),
    'commande_piece_arrivee',
    '[CLIENT_NOM],[CLIENT_PRENOM],[REPARATION_ID],[APPAREIL_TYPE],[APPAREIL_MARQUE],[APPAREIL_MODELE],[COMPANY_NAME],[COMPANY_PHONE]',
    'notification'
);

-- Template "Retard Livraison" pour les commandes en retard
INSERT INTO sms_templates (
    nom, 
    contenu, 
    statut_id, 
    est_actif, 
    created_at, 
    updated_at, 
    code, 
    variables,
    type
) VALUES (
    'Retard Livraison Pièce',
    '[COMPANY_NAME]
En raison d''un problème de livraison, la pièce pour votre réparation #[REPARATION_ID] aura un léger retard (≈24H).
Nous vous enverrons un SMS dès réception de la pièce.
Veuillez nous excuser pour la gêne occasionnée.
Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]
Cordialement,
[COMPANY_NAME]
Tel : [COMPANY_PHONE]',
    NULL,
    1,
    NOW(),
    NOW(),
    'retard_livraison_piece',
    '[CLIENT_NOM],[CLIENT_PRENOM],[REPARATION_ID],[APPAREIL_TYPE],[COMPANY_NAME],[COMPANY_PHONE]',
    'notification'
);

-- Vérification des templates créés
SELECT 'Templates SMS créés:' as info;
SELECT id, nom, code, est_actif 
FROM sms_templates 
WHERE code IN ('commande_piece_arrivee', 'retard_livraison_piece')
ORDER BY id DESC;
