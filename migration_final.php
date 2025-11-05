<?php
/**
 * Script final de migration avec offsets calculés
 */

// Lire le script adapté
$script_content = file_get_contents('/Users/admin/Documents/GeekBoard/migration_adapted.sql');

// Calculer les offsets
// Max client ID actuel: 14, donc offset = 15 - 384 = -369
// Max reparation ID actuel: 13, donc offset = 14 - 586 = -572

$client_offset = 15 - 384; // -369
$reparation_offset = 14 - 586; // -572

echo "=== CALCUL DES OFFSETS ===\n";
echo "Client offset: $client_offset\n";
echo "Reparation offset: $reparation_offset\n\n";

// Remplacer les placeholders
$final_script = str_replace('CLIENT_OFFSET', $client_offset, $script_content);
$final_script = str_replace('REPARATION_OFFSET', $reparation_offset, $final_script);

// Corriger les calculs négatifs dans les INSERT
// Remplacer "384 + (-369)" par "15", etc.
$final_script = preg_replace_callback(
    '/(\d+) \+ \((-?\d+)\)/',
    function($matches) {
        return (int)$matches[1] + (int)$matches[2];
    },
    $final_script
);

// Sauvegarder le script final
file_put_contents('/Users/admin/Documents/GeekBoard/migration_final.sql', $final_script);

echo "Script final créé: migration_final.sql\n";
echo "Prêt pour l'exécution sur le serveur\n\n";

// Créer aussi un script de test pour quelques enregistrements
$test_script = "-- Script de test avec 5 clients et 3 réparations\n\n";

// Ajouter les modifications de structure
$test_script .= "-- ÉTAPE 1: Modifications de structure\n";
$test_script .= "ALTER TABLE reparations ADD COLUMN IF NOT EXISTS marque varchar(50) NOT NULL DEFAULT '' AFTER type_appareil;\n";
$test_script .= "ALTER TABLE reparations ADD COLUMN IF NOT EXISTS signature_devis longtext DEFAULT NULL AFTER proprietaire;\n";
$test_script .= "ALTER TABLE reparations ADD COLUMN IF NOT EXISTS date_signature_devis datetime DEFAULT NULL AFTER signature_devis;\n\n";

// Test avec quelques clients
$test_script .= "-- ÉTAPE 2: Test avec 5 clients\n";
$test_script .= "INSERT INTO clients (nom, prenom, telephone, email, date_creation, inscrit_parrainage) VALUES\n";
$test_script .= "('Ouerghemi', 'Sofien', '3354115219', NULL, '2025-04-08 11:44:35', 0),\n";
$test_script .= "('Touba', 'Abbes', '3360577563', NULL, '2025-04-08 11:44:35', 0),\n";
$test_script .= "('Smail', 'Cheyma', '3349292638', NULL, '2025-04-08 11:44:35', 0),\n";
$test_script .= "('Diallo', 'Binta', '3345937372', NULL, '2025-04-08 11:44:35', 0),\n";
$test_script .= "('Ziani', 'Sarah', '3345203297', NULL, '2025-04-08 11:44:42', 0);\n\n";

// Vérifications
$test_script .= "-- ÉTAPE 3: Vérifications\n";
$test_script .= "SELECT COUNT(*) as total_clients FROM clients;\n";
$test_script .= "SELECT * FROM clients ORDER BY id DESC LIMIT 5;\n";

file_put_contents('/Users/admin/Documents/GeekBoard/migration_test.sql', $test_script);

echo "Script de test créé: migration_test.sql\n";

?>
