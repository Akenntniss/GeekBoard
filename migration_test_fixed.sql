-- Script de test corrigé pour geekboard_mkmkmk

-- ÉTAPE 1: Vérifier si les colonnes existent déjà
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'geekboard_mkmkmk' 
  AND TABLE_NAME = 'reparations' 
  AND COLUMN_NAME IN ('marque', 'signature_devis', 'date_signature_devis');

-- ÉTAPE 2: Ajouter les colonnes manquantes (exécuter une par une si nécessaire)
-- ALTER TABLE reparations ADD COLUMN marque varchar(50) NOT NULL DEFAULT '' AFTER type_appareil;
-- ALTER TABLE reparations ADD COLUMN signature_devis longtext DEFAULT NULL AFTER proprietaire;
-- ALTER TABLE reparations ADD COLUMN date_signature_devis datetime DEFAULT NULL AFTER signature_devis;

-- ÉTAPE 3: Test avec 3 clients
INSERT INTO clients (nom, prenom, telephone, email, date_creation, inscrit_parrainage) VALUES
('TEST_Ouerghemi', 'Sofien', '3354115219', NULL, '2025-04-08 11:44:35', 0),
('TEST_Touba', 'Abbes', '3360577563', NULL, '2025-04-08 11:44:35', 0),
('TEST_Smail', 'Cheyma', '3349292638', NULL, '2025-04-08 11:44:35', 0);

-- ÉTAPE 4: Vérifications
SELECT COUNT(*) as total_clients FROM clients;
SELECT * FROM clients WHERE nom LIKE 'TEST_%' ORDER BY id DESC;
