-- Migration: Ajout de la colonne search_tags pour la recherche IA
-- Date: 2024-12-23

-- Ajouter la colonne search_tags
ALTER TABLE catalogue_fournisseur 
ADD COLUMN IF NOT EXISTS search_tags TEXT AFTER model;

-- Ajouter un index FULLTEXT pour la recherche rapide
ALTER TABLE catalogue_fournisseur 
ADD FULLTEXT INDEX idx_fulltext_search (name, brand, model, reference, search_tags);

-- Index séparé sur search_tags seul
ALTER TABLE catalogue_fournisseur 
ADD FULLTEXT INDEX idx_search_tags (search_tags);
