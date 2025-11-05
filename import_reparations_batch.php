<?php
/**
 * Script d'importation des réparations par lots
 */

echo "=== IMPORTATION DES RÉPARATIONS PAR LOTS ===\n";

// Lire le script généré
$script_content = file_get_contents('/Users/admin/Documents/GeekBoard/import_reparations.sql');

// Extraire seulement les INSERT
preg_match_all('/INSERT IGNORE INTO reparations.*?;/', $script_content, $insert_matches);

echo "Nombre d'INSERT trouvés: " . count($insert_matches[0]) . "\n";

// Créer des lots de 10 réparations
$batch_size = 10;
$batches = array_chunk($insert_matches[0], $batch_size);

echo "Nombre de lots: " . count($batches) . "\n";

foreach ($batches as $batch_num => $batch) {
    $batch_script = "-- Lot " . ($batch_num + 1) . "\n";
    $batch_script .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $batch_script .= "START TRANSACTION;\n\n";
    
    foreach ($batch as $insert) {
        $batch_script .= $insert . "\n";
    }
    
    $batch_script .= "\nCOMMIT;\n";
    $batch_script .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Sauvegarder le lot
    $batch_file = "/Users/admin/Documents/GeekBoard/batch_" . ($batch_num + 1) . ".sql";
    file_put_contents($batch_file, $batch_script);
    
    echo "Lot " . ($batch_num + 1) . " créé: " . count($batch) . " réparations\n";
}

echo "\nTous les lots ont été créés. Exécutez-les un par un sur le serveur.\n";

?>
