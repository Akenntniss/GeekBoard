<?php
/**
 * Script d'extraction et de migration des données
 * Source: u139954273_repargsm1-2.sql
 * Destination: geekboard_mkmkmk
 */

require_once 'config/database.php';

// Configuration
$source_sql_file = '/Users/admin/Downloads/u139954273_repargsm1-2.sql';
$log_file = '/Users/admin/Documents/GeekBoard/migration_log.txt';

// Fonction de logging
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

// Fonction pour extraire les données clients du fichier SQL
function extractClientsFromSQL($sql_file) {
    logMessage("Extraction des clients depuis $sql_file");
    
    $content = file_get_contents($sql_file);
    if (!$content) {
        throw new Exception("Impossible de lire le fichier SQL");
    }
    
    // Regex pour extraire les INSERT INTO clients
    $pattern = '/INSERT INTO `clients`[^;]+;/s';
    preg_match_all($pattern, $content, $matches);
    
    $clients = [];
    foreach ($matches[0] as $insert_statement) {
        // Extraire les valeurs entre parenthèses
        $values_pattern = '/\(([^)]+)\)/';
        preg_match_all($values_pattern, $insert_statement, $value_matches);
        
        foreach ($value_matches[1] as $values_string) {
            // Parser les valeurs (attention aux guillemets et virgules dans les données)
            $values = str_getcsv($values_string, ',', "'");
            
            if (count($values) >= 6) { // Au minimum id, nom, prenom, telephone, email, date_creation
                $clients[] = [
                    'id' => (int)$values[0],
                    'nom' => trim($values[1], "'\""),
                    'prenom' => trim($values[2], "'\""),
                    'telephone' => trim($values[3], "'\""),
                    'email' => $values[4] === 'NULL' ? null : trim($values[4], "'\""),
                    'date_creation' => $values[5] === 'NULL' ? null : trim($values[5], "'\""),
                    'inscrit_parrainage' => isset($values[6]) ? (int)$values[6] : 0,
                    'code_parrainage' => isset($values[7]) && $values[7] !== 'NULL' ? trim($values[7], "'\"") : null,
                    'date_inscription_parrainage' => isset($values[8]) && $values[8] !== 'NULL' ? trim($values[8], "'\"") : null
                ];
            }
        }
    }
    
    logMessage("Extraction terminée: " . count($clients) . " clients trouvés");
    return $clients;
}

// Fonction pour extraire les données réparations du fichier SQL
function extractReparationsFromSQL($sql_file) {
    logMessage("Extraction des réparations depuis $sql_file");
    
    $content = file_get_contents($sql_file);
    if (!$content) {
        throw new Exception("Impossible de lire le fichier SQL");
    }
    
    // Regex pour extraire les INSERT INTO reparations
    $pattern = '/INSERT INTO `reparations`[^;]+;/s';
    preg_match_all($pattern, $content, $matches);
    
    $reparations = [];
    foreach ($matches[0] as $insert_statement) {
        // Extraire les valeurs entre parenthèses
        $values_pattern = '/\(([^)]+)\)/';
        preg_match_all($values_pattern, $insert_statement, $value_matches);
        
        foreach ($value_matches[1] as $values_string) {
            // Parser les valeurs (plus complexe pour les réparations)
            $values = [];
            $in_quotes = false;
            $current_value = '';
            $quote_char = '';
            
            for ($i = 0; $i < strlen($values_string); $i++) {
                $char = $values_string[$i];
                
                if (!$in_quotes && ($char === "'" || $char === '"')) {
                    $in_quotes = true;
                    $quote_char = $char;
                } elseif ($in_quotes && $char === $quote_char) {
                    $in_quotes = false;
                    $quote_char = '';
                } elseif (!$in_quotes && $char === ',') {
                    $values[] = trim($current_value);
                    $current_value = '';
                    continue;
                }
                
                $current_value .= $char;
            }
            $values[] = trim($current_value); // Dernier élément
            
            if (count($values) >= 20) { // Minimum de colonnes requises
                $reparations[] = [
                    'id' => (int)$values[0],
                    'client_id' => (int)$values[1],
                    'type_appareil' => trim($values[2], "'\""),
                    'marque' => trim($values[3], "'\""),
                    'modele' => trim($values[4], "'\""),
                    'description_probleme' => trim($values[5], "'\""),
                    'date_reception' => $values[6] === 'NULL' ? null : trim($values[6], "'\""),
                    'date_modification' => $values[7] === 'NULL' ? null : trim($values[7], "'\""),
                    'date_fin_prevue' => $values[8] === 'NULL' ? null : trim($values[8], "'\""),
                    'statut' => trim($values[9], "'\""),
                    'statut_id' => $values[10] === 'NULL' ? null : (int)$values[10],
                    'statut_categorie' => $values[11] === 'NULL' ? null : (int)$values[11],
                    'signature' => $values[12] === 'NULL' ? null : trim($values[12], "'\""),
                    'prix' => $values[13] === 'NULL' ? null : (float)$values[13],
                    'notes_techniques' => $values[14] === 'NULL' ? null : trim($values[14], "'\""),
                    'notes_finales' => $values[15] === 'NULL' ? null : trim($values[15], "'\""),
                    'photo_appareil' => $values[16] === 'NULL' ? null : trim($values[16], "'\""),
                    'mot_de_passe' => $values[17] === 'NULL' ? null : trim($values[17], "'\""),
                    'etat_esthetique' => $values[18] === 'NULL' ? null : trim($values[18], "'\""),
                    'prix_reparation' => isset($values[19]) ? (float)$values[19] : 0.00,
                    // Ajouter les autres champs selon la structure...
                ];
            }
        }
    }
    
    logMessage("Extraction terminée: " . count($reparations) . " réparations trouvées");
    return $reparations;
}

// Fonction pour obtenir les prochains IDs disponibles
function getNextAvailableIds($pdo) {
    // Prochain ID client
    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM clients");
    $next_client_id = $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
    
    // Prochain ID réparation
    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM reparations");
    $next_reparation_id = $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
    
    return [$next_client_id, $next_reparation_id];
}

// Fonction pour insérer les clients
function insertClients($pdo, $clients, $start_id) {
    logMessage("Insertion de " . count($clients) . " clients à partir de l'ID $start_id");
    
    $stmt = $pdo->prepare("
        INSERT INTO clients (id, nom, prenom, telephone, email, date_creation, inscrit_parrainage, code_parrainage, date_inscription_parrainage)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $id_mapping = []; // Mapping ancien_id => nouveau_id
    $new_id = $start_id;
    
    foreach ($clients as $client) {
        $old_id = $client['id'];
        
        $stmt->execute([
            $new_id,
            $client['nom'],
            $client['prenom'],
            $client['telephone'],
            $client['email'],
            $client['date_creation'],
            $client['inscrit_parrainage'],
            $client['code_parrainage'],
            $client['date_inscription_parrainage']
        ]);
        
        $id_mapping[$old_id] = $new_id;
        $new_id++;
    }
    
    logMessage("Clients insérés avec succès. Mapping créé pour " . count($id_mapping) . " clients");
    return $id_mapping;
}

// Fonction principale
function runMigration() {
    global $source_sql_file;
    
    try {
        logMessage("=== DÉBUT DE LA MIGRATION ===");
        
        // Connexion à la base
        $pdo = getShopDBConnectionById('mkmkmk');
        if (!$pdo) {
            throw new Exception("Impossible de se connecter à la base geekboard_mkmkmk");
        }
        
        logMessage("Connexion à la base geekboard_mkmkmk établie");
        
        // Vérifier si le fichier source existe
        if (!file_exists($source_sql_file)) {
            throw new Exception("Fichier source non trouvé: $source_sql_file");
        }
        
        // Obtenir les prochains IDs disponibles
        list($next_client_id, $next_reparation_id) = getNextAvailableIds($pdo);
        logMessage("Prochains IDs disponibles - Clients: $next_client_id, Réparations: $next_reparation_id");
        
        // Extraire les données
        $clients = extractClientsFromSQL($source_sql_file);
        $reparations = extractReparationsFromSQL($source_sql_file);
        
        // Insérer les clients
        $client_id_mapping = insertClients($pdo, $clients, $next_client_id);
        
        // TODO: Insérer les réparations avec les nouveaux client_id
        
        logMessage("=== MIGRATION TERMINÉE AVEC SUCCÈS ===");
        
    } catch (Exception $e) {
        logMessage("ERREUR: " . $e->getMessage());
        throw $e;
    }
}

// Exécution si appelé directement
if (php_sapi_name() === 'cli') {
    runMigration();
}
?>
