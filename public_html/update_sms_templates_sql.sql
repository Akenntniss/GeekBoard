-- Script SQL pour remplacer les URLs hardcodées par la variable [URL_SUIVI] dans les templates SMS
-- À exécuter sur chaque base de données de magasin

-- Mise à jour du template "Nouvelle Intervention" 
UPDATE sms_templates 
SET contenu = '👋 Bonjour [CLIENT_PRENOM],\r\n🛠️ Nous avons bien reçu votre [APPAREIL_MODELE] et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\r\n🔎 Suivez l\'avancement de la réparation ici :\r\n👉 [URL_SUIVI]\r\n💶 [PRIX]\r\n📞 Une question ? Contactez nous au 08 95 79 59 33\r\n🏠 Maison du GEEK 🛠️',
    updated_at = NOW()
WHERE nom = 'Nouvelle Intervention';

-- Mise à jour des autres templates qui utilisent des URLs hardcodées
UPDATE sms_templates 
SET contenu = REPLACE(contenu, 'http://Mdgeek.top/suivi.php?id=[REPARATION_ID]', '[URL_SUIVI]'),
    updated_at = NOW()
WHERE contenu LIKE '%http://Mdgeek.top/suivi.php?id=[REPARATION_ID]%';

UPDATE sms_templates 
SET contenu = REPLACE(contenu, 'http://mdgeek.top/suivi.php?id=[REPARATION_ID]', '[URL_SUIVI]'),
    updated_at = NOW()
WHERE contenu LIKE '%http://mdgeek.top/suivi.php?id=[REPARATION_ID]%';

UPDATE sms_templates 
SET contenu = REPLACE(contenu, 'http://Mdgeek.fr/suivi.php?id=[REPARATION_ID]', '[URL_SUIVI]'),
    updated_at = NOW()
WHERE contenu LIKE '%http://Mdgeek.fr/suivi.php?id=[REPARATION_ID]%';

UPDATE sms_templates 
SET contenu = REPLACE(contenu, 'http://mdgeek.fr/suivi.php?id=[REPARATION_ID]', '[URL_SUIVI]'),
    updated_at = NOW()
WHERE contenu LIKE '%http://mdgeek.fr/suivi.php?id=[REPARATION_ID]%';

UPDATE sms_templates 
SET contenu = REPLACE(contenu, 'https://Mdgeek.top/suivi.php?id=[REPARATION_ID]', '[URL_SUIVI]'),
    updated_at = NOW()
WHERE contenu LIKE '%https://Mdgeek.top/suivi.php?id=[REPARATION_ID]%';

UPDATE sms_templates 
SET contenu = REPLACE(contenu, 'https://mdgeek.top/suivi.php?id=[REPARATION_ID]', '[URL_SUIVI]'),
    updated_at = NOW()
WHERE contenu LIKE '%https://mdgeek.top/suivi.php?id=[REPARATION_ID]%';

UPDATE sms_templates 
SET contenu = REPLACE(contenu, 'mdgeek.top/suivi.php?id=[REPARATION_ID]', '[URL_SUIVI]'),
    updated_at = NOW()
WHERE contenu LIKE '%mdgeek.top/suivi.php?id=[REPARATION_ID]%';

UPDATE sms_templates 
SET contenu = REPLACE(contenu, 'mdgeek.fr/suivi.php?id=[REPARATION_ID]', '[URL_SUIVI]'),
    updated_at = NOW()
WHERE contenu LIKE '%mdgeek.fr/suivi.php?id=[REPARATION_ID]%';

-- Afficher les templates mis à jour pour vérification
SELECT id, nom, contenu 
FROM sms_templates 
WHERE contenu LIKE '%[URL_SUIVI]%'
ORDER BY nom;
