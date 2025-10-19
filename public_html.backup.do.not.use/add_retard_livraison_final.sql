-- Script pour ajouter le statut "Retard de livraison" et son template SMS
-- Base de données: geekboard_mkmkmk

USE geekboard_mkmkmk;

-- 1. Ajouter le nouveau statut dans la catégorie "En Attente" (id=3)
-- Le prochain ordre sera 4 (après "En attente d'un responsable" qui est à 3)
INSERT INTO statuts (nom, code, categorie_id, est_actif, ordre) 
VALUES ('Retard de livraison', 'retard_livraison', 3, 1, 4);

-- 2. Récupérer l'ID du nouveau statut
SET @nouveau_statut_id = LAST_INSERT_ID();

-- 3. Ajouter le template SMS "Retard Livraison"
-- Utilisation des variables compatibles avec le système existant
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
    'Retard Livraison',
    'La Maison Du Geek
En raison d\'un problème de livraison, votre réparation #[REPARATION_ID] aura un léger retard (≈24h).
Vous pouvez suivre l\'avancée de votre réparation via le lien ci-dessous : [LIEN]
Nous vous enverrons un SMS dès que votre [APPAREIL_TYPE] sera prêt.
Veuillez nous excuser pour la gêne occasionnée.

Cordialement,
La Maison du Geek',
    @nouveau_statut_id,
    1,
    NOW(),
    NOW(),
    'retard_livraison',
    '[CLIENT_NOM],[CLIENT_PRENOM],[REPARATION_ID],[APPAREIL_TYPE],[APPAREIL_MARQUE],[APPAREIL_MODELE],[LIEN],[DATE_RECEPTION],[DATE_FIN_PREVUE]',
    'notification'
);

-- 4. Vérifications et affichage des résultats
SELECT 'Nouveau statut ajouté:' as info;
SELECT s.id, s.nom, s.code, sc.nom as categorie, s.ordre
FROM statuts s 
JOIN statut_categories sc ON s.categorie_id = sc.id 
WHERE s.code = 'retard_livraison';

SELECT 'Template SMS ajouté:' as info;
SELECT st.id, st.nom, st.code, st.statut_id, s.nom as statut_nom, st.type
FROM sms_templates st
LEFT JOIN statuts s ON st.statut_id = s.id
WHERE st.code = 'retard_livraison';

-- 5. Afficher tous les statuts de la catégorie "En Attente" pour vérification
SELECT 'Tous les statuts En Attente (mise à jour):' as info;
SELECT s.id, s.nom, s.code, s.ordre 
FROM statuts s 
JOIN statut_categories sc ON s.categorie_id = sc.id 
WHERE sc.code = 'en_attente'
ORDER BY s.ordre;

-- 6. Vérifier le contenu du template créé
SELECT 'Contenu du template SMS:' as info;
SELECT contenu 
FROM sms_templates 
WHERE code = 'retard_livraison';
