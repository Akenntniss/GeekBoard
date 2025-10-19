-- Ajouter un template SMS spécifique pour les devis expirés récents
-- À exécuter sur CHAQUE base de données de magasin

INSERT IGNORE INTO `sms_templates` (
    `type`, 
    `code`, 
    `nom`, 
    `contenu`, 
    `est_actif`
) VALUES (
    'devis',
    'devis_relance_expire',
    'Relance Devis Expiré',
    'Bonjour {client_nom}, votre devis #{devis_numero} de {montant}€ a expiré il y a {jours_expires} jour(s) mais reste encore valable. Vous pouvez l\'accepter ici: {lien_devis}',
    1
);

-- Mettre à jour le template de relance automatique standard si il existe
UPDATE `sms_templates` 
SET `contenu` = 'Bonjour {client_nom}, votre devis #{devis_numero} de {montant}€ expire dans {jours_restants} jour(s). Consultez-le ici: {lien_devis}'
WHERE `code` = 'devis_relance_auto' AND `type` = 'devis';

-- Si le template de relance automatique n'existe pas, le créer
INSERT IGNORE INTO `sms_templates` (
    `type`, 
    `code`, 
    `nom`, 
    `contenu`, 
    `est_actif`
) VALUES (
    'devis',
    'devis_relance_auto',
    'Relance Devis Automatique',
    'Bonjour {client_nom}, votre devis #{devis_numero} de {montant}€ expire dans {jours_restants} jour(s). Consultez-le ici: {lien_devis}',
    1
);
