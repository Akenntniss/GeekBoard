-- Script de test avec 5 clients et 3 réparations

-- ÉTAPE 1: Modifications de structure
ALTER TABLE reparations ADD COLUMN IF NOT EXISTS marque varchar(50) NOT NULL DEFAULT '' AFTER type_appareil;
ALTER TABLE reparations ADD COLUMN IF NOT EXISTS signature_devis longtext DEFAULT NULL AFTER proprietaire;
ALTER TABLE reparations ADD COLUMN IF NOT EXISTS date_signature_devis datetime DEFAULT NULL AFTER signature_devis;

-- ÉTAPE 2: Test avec 5 clients
INSERT INTO clients (nom, prenom, telephone, email, date_creation, inscrit_parrainage) VALUES
('Ouerghemi', 'Sofien', '3354115219', NULL, '2025-04-08 11:44:35', 0),
('Touba', 'Abbes', '3360577563', NULL, '2025-04-08 11:44:35', 0),
('Smail', 'Cheyma', '3349292638', NULL, '2025-04-08 11:44:35', 0),
('Diallo', 'Binta', '3345937372', NULL, '2025-04-08 11:44:35', 0),
('Ziani', 'Sarah', '3345203297', NULL, '2025-04-08 11:44:42', 0);

-- ÉTAPE 3: Vérifications
SELECT COUNT(*) as total_clients FROM clients;
SELECT * FROM clients ORDER BY id DESC LIMIT 5;
