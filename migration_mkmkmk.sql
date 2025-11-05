-- =====================================================
-- SCRIPT DE MIGRATION POUR IMPORTER LES DONNÉES
-- Source: u139954273_repargsm1-2.sql
-- Destination: geekboard_mkmkmk
-- Date: 2025-11-03
-- =====================================================

-- ÉTAPE 1: Ajouter les colonnes manquantes à la table reparations
-- =====================================================

-- Ajouter la colonne marque (manquante dans la destination)
ALTER TABLE `reparations` 
ADD COLUMN `marque` varchar(50) NOT NULL DEFAULT '' AFTER `type_appareil`;

-- Ajouter les colonnes de signature devis (manquantes dans la destination)
ALTER TABLE `reparations` 
ADD COLUMN `signature_devis` longtext DEFAULT NULL COMMENT 'Signature électronique du client pour acceptation devis (base64)' AFTER `proprietaire`;

ALTER TABLE `reparations` 
ADD COLUMN `date_signature_devis` datetime DEFAULT NULL COMMENT 'Date et heure de la signature du devis' AFTER `signature_devis`;

-- ÉTAPE 2: Sauvegarde des données existantes
-- =====================================================

-- Créer une table de sauvegarde des clients existants
CREATE TABLE `clients_backup_20251103` AS SELECT * FROM `clients`;

-- Créer une table de sauvegarde des réparations existantes
CREATE TABLE `reparations_backup_20251103` AS SELECT * FROM `reparations`;

-- ÉTAPE 3: Préparation des IDs pour éviter les conflits
-- =====================================================

-- Trouver le prochain ID disponible pour les clients
-- (Les nouveaux clients commenceront à partir de cet ID)
SELECT COALESCE(MAX(id), 0) + 1 as next_client_id FROM clients;

-- Trouver le prochain ID disponible pour les réparations
-- (Les nouvelles réparations commenceront à partir de cet ID)
SELECT COALESCE(MAX(id), 0) + 1 as next_reparation_id FROM reparations;

-- ÉTAPE 4: Insertion des nouveaux clients
-- =====================================================
-- Note: Les IDs seront ajustés automatiquement pour éviter les conflits

-- ÉTAPE 5: Insertion des nouvelles réparations
-- =====================================================
-- Note: Les client_id seront mis à jour pour correspondre aux nouveaux IDs clients

-- ÉTAPE 6: Vérification post-migration
-- =====================================================

-- Compter les clients après migration
-- SELECT COUNT(*) as total_clients FROM clients;

-- Compter les réparations après migration
-- SELECT COUNT(*) as total_reparations FROM reparations;

-- Vérifier l'intégrité des relations client-réparation
-- SELECT COUNT(*) as reparations_sans_client 
-- FROM reparations r 
-- LEFT JOIN clients c ON r.client_id = c.id 
-- WHERE c.id IS NULL;

-- =====================================================
-- NOTES IMPORTANTES:
-- =====================================================
-- 1. Ce script doit être exécuté étape par étape
-- 2. Vérifier chaque étape avant de passer à la suivante
-- 3. Les sauvegardes permettent de restaurer en cas de problème
-- 4. Les IDs seront automatiquement ajustés pour éviter les conflits
-- =====================================================
