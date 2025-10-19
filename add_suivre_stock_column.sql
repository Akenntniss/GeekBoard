-- Script pour ajouter la colonne suivre_stock à la table produits
-- Exécuter ce script sur toutes les bases de données des magasins

ALTER TABLE produits 
ADD COLUMN suivre_stock BOOLEAN DEFAULT FALSE 
COMMENT 'Indique si le produit doit être suivi dans le système de vérification de stock';

-- Créer un index pour optimiser les requêtes
CREATE INDEX idx_produits_suivre_stock ON produits(suivre_stock);

-- Mise à jour des produits existants (optionnel - tous en suivi par défaut)
-- UPDATE produits SET suivre_stock = TRUE WHERE quantite > 0 OR seuil_alerte > 0;

-- Vérifier la structure mise à jour
DESCRIBE produits;
