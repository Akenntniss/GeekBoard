<?php
/**
 * Script de migration simplifié
 * Copie le fichier SQL sur le serveur et l'adapte pour l'importation
 */

// Configuration
$source_file = '/Users/admin/Downloads/u139954273_repargsm1-2.sql';
$temp_file = '/Users/admin/Documents/GeekBoard/migration_adapted.sql';

echo "=== PRÉPARATION DE LA MIGRATION ===\n";

// Lire le fichier source
$content = file_get_contents($source_file);
if (!$content) {
    die("Erreur: Impossible de lire le fichier source\n");
}

echo "Fichier source lu: " . strlen($content) . " caractères\n";

// Adaptations nécessaires
$adaptations = [
    // Supprimer les instructions de création de base
    '/-- Base de données.*?\n/s' => '',
    
    // Supprimer les CREATE TABLE (on va les adapter manuellement)
    '/CREATE TABLE `clients`.*?;/s' => '',
    '/CREATE TABLE `reparations`.*?;/s' => '',
    
    // Adapter les INSERT pour éviter les conflits d'ID
    // On va ajouter un offset aux IDs
];

// Appliquer les adaptations de base
foreach ($adaptations as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

// Extraire et adapter les INSERT clients
preg_match_all('/INSERT INTO `clients`.*?;/s', $content, $client_inserts);
preg_match_all('/INSERT INTO `reparations`.*?;/s', $content, $reparation_inserts);

echo "Trouvé: " . count($client_inserts[0]) . " INSERT clients\n";
echo "Trouvé: " . count($reparation_inserts[0]) . " INSERT réparations\n";

// Créer le script adapté
$adapted_script = "-- Script de migration adapté pour geekboard_mkmkmk\n";
$adapted_script .= "-- Généré le: " . date('Y-m-d H:i:s') . "\n\n";

// Ajouter les modifications de structure
$adapted_script .= "-- ÉTAPE 1: Modifications de structure\n";
$adapted_script .= "ALTER TABLE reparations ADD COLUMN marque varchar(50) NOT NULL DEFAULT '' AFTER type_appareil;\n";
$adapted_script .= "ALTER TABLE reparations ADD COLUMN signature_devis longtext DEFAULT NULL AFTER proprietaire;\n";
$adapted_script .= "ALTER TABLE reparations ADD COLUMN date_signature_devis datetime DEFAULT NULL AFTER signature_devis;\n\n";

// Ajouter les sauvegardes
$adapted_script .= "-- ÉTAPE 2: Sauvegardes\n";
$adapted_script .= "CREATE TABLE clients_backup_" . date('Ymd_His') . " AS SELECT * FROM clients;\n";
$adapted_script .= "CREATE TABLE reparations_backup_" . date('Ymd_His') . " AS SELECT * FROM reparations;\n\n";

// Calculer les offsets pour les IDs
$adapted_script .= "-- ÉTAPE 3: Calcul des offsets (à exécuter manuellement)\n";
$adapted_script .= "-- SELECT COALESCE(MAX(id), 0) + 1 as next_client_id FROM clients;\n";
$adapted_script .= "-- SELECT COALESCE(MAX(id), 0) + 1 as next_reparation_id FROM reparations;\n\n";

// Ajouter les INSERT adaptés (avec des placeholders pour les offsets)
$adapted_script .= "-- ÉTAPE 4: Insertion des clients (remplacer CLIENT_OFFSET par la valeur calculée)\n";
foreach ($client_inserts[0] as $insert) {
    // Remplacer les IDs par des IDs avec offset
    $adapted_insert = preg_replace('/\((\d+),/', '(\\1 + CLIENT_OFFSET,', $insert);
    $adapted_script .= $adapted_insert . "\n";
}

$adapted_script .= "\n-- ÉTAPE 5: Insertion des réparations (remplacer REPARATION_OFFSET et CLIENT_OFFSET)\n";
foreach ($reparation_inserts[0] as $insert) {
    // Adapter les IDs de réparation et client_id
    $adapted_insert = preg_replace('/\((\d+),(\d+),/', '(\\1 + REPARATION_OFFSET, \\2 + CLIENT_OFFSET,', $insert);
    $adapted_script .= $adapted_insert . "\n";
}

// Ajouter les vérifications
$adapted_script .= "\n-- ÉTAPE 6: Vérifications\n";
$adapted_script .= "SELECT COUNT(*) as total_clients FROM clients;\n";
$adapted_script .= "SELECT COUNT(*) as total_reparations FROM reparations;\n";
$adapted_script .= "SELECT COUNT(*) as orphaned_reparations FROM reparations r LEFT JOIN clients c ON r.client_id = c.id WHERE c.id IS NULL;\n";

// Sauvegarder le script adapté
file_put_contents($temp_file, $adapted_script);

echo "Script adapté créé: $temp_file\n";
echo "Taille: " . strlen($adapted_script) . " caractères\n\n";

echo "=== PROCHAINES ÉTAPES ===\n";
echo "1. Copier le script sur le serveur\n";
echo "2. Calculer les offsets manuellement\n";
echo "3. Remplacer CLIENT_OFFSET et REPARATION_OFFSET dans le script\n";
echo "4. Exécuter le script étape par étape\n";

?>
