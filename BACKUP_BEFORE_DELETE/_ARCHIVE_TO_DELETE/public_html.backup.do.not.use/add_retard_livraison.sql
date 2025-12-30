-- Script pour ajouter le statut "Retard de livraison" et son template SMS
-- Base de données: geekboard_mkmkmk

USE geekboard_mkmkmk;

-- 1. Ajouter le nouveau statut dans la catégorie "En Attente" (id=3)
INSERT INTO statuts (nom, code, categorie_id, est_actif, ordre) 
VALUES ('Retard de livraison', 'retard_livraison', 3, 1, 4);

-- 2. Récupérer l'ID du nouveau statut pour le template SMS
SET @nouveau_statut_id = LAST_INSERT_ID();

-- 3. Ajouter le template SMS "Retard Livraison"
INSERT INTO sms_templates (nom, contenu, statut_id, est_actif, created_at, updated_at)
VALUES (
    'Retard Livraison',
    'La Maison Du Geek
En raison d\'un problème de livraison, votre réparation #[REPARATION_ID] aura un léger retard (≈24h).
Vous pouvez suivre l\'avancée de votre réparation via le lien ci-dessous : [LIEN]
Nous vous enverrons un SMS dès que votre appareil sera prêt.
Veuillez nous excuser pour la gêne occasionnée.

Cordialement,
La Maison du Geek',
    @nouveau_statut_id,
    1,
    NOW(),
    NOW()
);

-- 4. Vérifier les ajouts
SELECT 'Nouveau statut ajouté:' as info;
SELECT s.id, s.nom, s.code, sc.nom as categorie 
FROM statuts s 
JOIN statut_categories sc ON s.categorie_id = sc.id 
WHERE s.code = 'retard_livraison';

SELECT 'Template SMS ajouté:' as info;
SELECT st.id, st.nom, st.statut_id, s.nom as statut_nom
FROM sms_templates st
LEFT JOIN statuts s ON st.statut_id = s.id
WHERE st.nom = 'Retard Livraison';

-- 5. Afficher tous les statuts de la catégorie "En Attente"
SELECT 'Tous les statuts En Attente:' as info;
SELECT s.id, s.nom, s.code, s.ordre 
FROM statuts s 
JOIN statut_categories sc ON s.categorie_id = sc.id 
WHERE sc.code = 'en_attente'
ORDER BY s.ordre;
