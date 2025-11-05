<?php
/**
 * Script d'importation des réparations avec mapping des client_id
 */

echo "=== IMPORTATION DES RÉPARATIONS ===\n";

// Lire le fichier source
$source_file = '/Users/admin/Downloads/u139954273_repargsm1-2.sql';
$content = file_get_contents($source_file);

// Extraire les données réparations
preg_match_all('/INSERT INTO `reparations`.*?VALUES\s*(.+?);/s', $content, $reparation_matches);

echo "Réparations trouvées: " . count($reparation_matches[1]) . "\n";

// Créer le mapping des IDs clients (ancien_id => nouveau_id)
$client_mapping = [];
$old_client_id = 384; // Premier ID dans le fichier source
$new_client_id = 1000; // Premier ID dans la base destination

// Générer le mapping pour tous les clients (384 à 1222 dans le source)
for ($old_id = 384; $old_id <= 1222; $old_id++) {
    $client_mapping[$old_id] = $new_client_id++;
}

// Créer le script d'importation des réparations
$script = "-- Importation des réparations\n";
$script .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$script .= "SET UNIQUE_CHECKS = 0;\n";
$script .= "SET AUTOCOMMIT = 0;\n";
$script .= "START TRANSACTION;\n\n";

$reparation_id = 2000; // Commencer les réparations à partir de 2000

foreach ($reparation_matches[1] as $values_block) {
    $lines = explode("\n", $values_block);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line === ',') continue;
        
        // Extraire les valeurs entre parenthèses
        if (preg_match('/\(([^)]+)\)/', $line, $match)) {
            $values_str = $match[1];
            
            // Parser les valeurs manuellement
            $values = [];
            $current_value = '';
            $in_quotes = false;
            $quote_char = '';
            
            for ($i = 0; $i < strlen($values_str); $i++) {
                $char = $values_str[$i];
                
                if (!$in_quotes && ($char === "'" || $char === '"')) {
                    $in_quotes = true;
                    $quote_char = $char;
                    $current_value .= $char;
                } elseif ($in_quotes && $char === $quote_char) {
                    // Vérifier si c'est un échappement
                    if ($i + 1 < strlen($values_str) && $values_str[$i + 1] === $quote_char) {
                        $current_value .= $char . $char;
                        $i++; // Skip next char
                    } else {
                        $in_quotes = false;
                        $quote_char = '';
                        $current_value .= $char;
                    }
                } elseif (!$in_quotes && $char === ',') {
                    $values[] = trim($current_value);
                    $current_value = '';
                } else {
                    $current_value .= $char;
                }
            }
            $values[] = trim($current_value); // Dernier élément
            
            if (count($values) >= 20) {
                // Mapper les IDs
                $old_client_id = (int)$values[1];
                $new_client_id = isset($client_mapping[$old_client_id]) ? $client_mapping[$old_client_id] : $old_client_id;
                
                // Préparer les valeurs échappées
                $escaped_values = [];
                $escaped_values[] = $reparation_id++; // Nouvel ID réparation
                $escaped_values[] = $new_client_id; // Nouveau client_id
                
                // Traiter les autres colonnes
                for ($j = 2; $j < count($values) && $j < 41; $j++) { // Limiter à 41 colonnes max
                    $value = $values[$j];
                    if ($value === 'NULL') {
                        $escaped_values[] = 'NULL';
                    } else {
                        // Enlever les guillemets existants et ré-échapper
                        $value = trim($value, "'\"");
                        $value = str_replace("'", "''", $value); // Échapper les apostrophes
                        $value = str_replace("\\", "\\\\", $value); // Échapper les backslashes
                        $escaped_values[] = "'" . $value . "'";
                    }
                }
                
                // Compléter avec des NULL si nécessaire pour atteindre 41 colonnes
                while (count($escaped_values) < 41) {
                    $escaped_values[] = 'NULL';
                }
                
                $script .= "INSERT IGNORE INTO reparations (id, client_id, type_appareil, marque, modele, description_probleme, date_reception, date_modification, date_fin_prevue, statut, statut_id, statut_categorie, signature, prix, notes_techniques, notes_finales, photo_appareil, mot_de_passe, etat_esthetique, prix_reparation, devis_envoye, devis_accepte, date_envoi_devis, date_reponse_devis, photos, urgent, commande_requise, archive, employe_id, date_gardiennage, gardiennage_facture, parrain_id, reduction_parrainage, reduction_parrainage_pourcentage, signature_client, photo_signature, photo_client, accept_conditions, proprietaire, signature_devis, date_signature_devis) VALUES (" . implode(', ', $escaped_values) . ");\n";
            }
        }
    }
}

$script .= "\nCOMMIT;\n";
$script .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$script .= "SET UNIQUE_CHECKS = 1;\n";
$script .= "SET AUTOCOMMIT = 1;\n\n";

$script .= "-- Vérifications\n";
$script .= "SELECT COUNT(*) as total_reparations FROM reparations;\n";
$script .= "SELECT COUNT(*) as reparations_importees FROM reparations WHERE id >= 2000;\n";
$script .= "SELECT COUNT(*) as orphaned_reparations FROM reparations r LEFT JOIN clients c ON r.client_id = c.id WHERE c.id IS NULL;\n";

// Sauvegarder le script
file_put_contents('/Users/admin/Documents/GeekBoard/import_reparations.sql', $script);

echo "Script des réparations créé: import_reparations.sql\n";
echo "Taille: " . strlen($script) . " caractères\n";

?>
