<?php
/**
 * Script d'importation sécurisé avec échappement correct
 */

echo "=== IMPORTATION SÉCURISÉE ===\n";

// Connexion à la base locale pour tester
try {
    // Simuler la connexion (remplacer par la vraie connexion)
    echo "Préparation des données...\n";
    
    // Lire et parser le fichier source
    $source_file = '/Users/admin/Downloads/u139954273_repargsm1-2.sql';
    $content = file_get_contents($source_file);
    
    // Extraire les données clients
    preg_match_all('/INSERT INTO `clients`.*?VALUES\s*(.+?);/s', $content, $client_matches);
    preg_match_all('/INSERT INTO `reparations`.*?VALUES\s*(.+?);/s', $content, $reparation_matches);
    
    echo "Clients trouvés: " . count($client_matches[1]) . "\n";
    echo "Réparations trouvées: " . count($reparation_matches[1]) . "\n";
    
    // Créer un script sécurisé
    $safe_script = "-- Script d'importation sécurisé\n";
    $safe_script .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $safe_script .= "SET UNIQUE_CHECKS = 0;\n";
    $safe_script .= "SET AUTOCOMMIT = 0;\n";
    $safe_script .= "START TRANSACTION;\n\n";
    
    // Traiter les clients
    $safe_script .= "-- CLIENTS\n";
    foreach ($client_matches[1] as $values_block) {
        // Parser chaque ligne de valeurs
        $lines = explode("\n", $values_block);
        $client_id = 1000;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line === ',') continue;
            
            // Extraire les valeurs entre parenthèses
            if (preg_match('/\(([^)]+)\)/', $line, $match)) {
                $values_str = $match[1];
                
                // Parser les valeurs manuellement pour gérer les apostrophes
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
                
                if (count($values) >= 6) {
                    // Échapper correctement les valeurs
                    $escaped_values = [];
                    $escaped_values[] = $client_id++; // Nouvel ID
                    
                    for ($j = 1; $j < count($values); $j++) {
                        $value = $values[$j];
                        if ($value === 'NULL') {
                            $escaped_values[] = 'NULL';
                        } else {
                            // Enlever les guillemets existants et ré-échapper
                            $value = trim($value, "'\"");
                            $value = str_replace("'", "''", $value); // Échapper les apostrophes
                            $escaped_values[] = "'" . $value . "'";
                        }
                    }
                    
                    $safe_script .= "INSERT IGNORE INTO clients (id, nom, prenom, telephone, email, date_creation, inscrit_parrainage, code_parrainage, date_inscription_parrainage) VALUES (" . implode(', ', $escaped_values) . ");\n";
                }
            }
        }
    }
    
    $safe_script .= "\nCOMMIT;\n";
    $safe_script .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $safe_script .= "SET UNIQUE_CHECKS = 1;\n";
    $safe_script .= "SET AUTOCOMMIT = 1;\n\n";
    
    $safe_script .= "SELECT COUNT(*) as total_clients FROM clients;\n";
    
    // Sauvegarder le script sécurisé
    file_put_contents('/Users/admin/Documents/GeekBoard/import_clients_safe.sql', $safe_script);
    
    echo "Script sécurisé créé: import_clients_safe.sql\n";
    echo "Taille: " . strlen($safe_script) . " caractères\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

?>
