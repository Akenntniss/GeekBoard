<?php
/**
 * Script d'importation complète des données
 * Source: u139954273_repargsm1-2.sql vers geekboard_mkmkmk
 */

echo "=== IMPORTATION COMPLÈTE DES DONNÉES ===\n";

// Lire le fichier source
$source_file = '/Users/admin/Downloads/u139954273_repargsm1-2.sql';
$content = file_get_contents($source_file);

if (!$content) {
    die("Erreur: Impossible de lire le fichier source\n");
}

echo "Fichier source lu: " . strlen($content) . " caractères\n";

// Extraire les INSERT clients
preg_match_all('/INSERT INTO `clients`.*?;/s', $content, $client_matches);
echo "Trouvé " . count($client_matches[0]) . " INSERT clients\n";

// Extraire les INSERT reparations  
preg_match_all('/INSERT INTO `reparations`.*?;/s', $content, $reparation_matches);
echo "Trouvé " . count($reparation_matches[0]) . " INSERT reparations\n";

// Créer le script d'importation optimisé
$import_script = "-- Script d'importation complète pour geekboard_mkmkmk\n";
$import_script .= "-- Généré le: " . date('Y-m-d H:i:s') . "\n\n";

$import_script .= "-- Désactiver les vérifications pour accélérer l'importation\n";
$import_script .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$import_script .= "SET UNIQUE_CHECKS = 0;\n";
$import_script .= "SET AUTOCOMMIT = 0;\n\n";

$import_script .= "-- Commencer une transaction\n";
$import_script .= "START TRANSACTION;\n\n";

// Traiter les clients
$import_script .= "-- IMPORTATION DES CLIENTS\n";
foreach ($client_matches[0] as $client_insert) {
    // Remplacer INSERT INTO par INSERT IGNORE INTO pour éviter les doublons
    $client_insert = str_replace('INSERT INTO `clients`', 'INSERT IGNORE INTO `clients`', $client_insert);
    
    // Extraire les valeurs et les adapter
    if (preg_match('/VALUES\s*(.+);/s', $client_insert, $values_match)) {
        $values_part = $values_match[1];
        
        // Remplacer les IDs par des IDs séquentiels à partir de 1000 pour éviter les conflits
        $values_part = preg_replace_callback(
            '/\((\d+),/',
            function($matches) {
                static $new_id = 1000;
                return '(' . $new_id++ . ',';
            },
            $values_part
        );
        
        $import_script .= "INSERT IGNORE INTO `clients` (`id`, `nom`, `prenom`, `telephone`, `email`, `date_creation`, `inscrit_parrainage`, `code_parrainage`, `date_inscription_parrainage`) VALUES " . $values_part . ";\n";
    }
}

$import_script .= "\n-- IMPORTATION DES RÉPARATIONS\n";
foreach ($reparation_matches[0] as $reparation_insert) {
    // Remplacer INSERT INTO par INSERT IGNORE INTO
    $reparation_insert = str_replace('INSERT INTO `reparations`', 'INSERT IGNORE INTO `reparations`', $reparation_insert);
    
    // Adapter les colonnes pour correspondre à la nouvelle structure
    if (preg_match('/VALUES\s*(.+);/s', $reparation_insert, $values_match)) {
        $values_part = $values_match[1];
        
        // Adapter les IDs des réparations et des clients
        $values_part = preg_replace_callback(
            '/\((\d+),(\d+),/',
            function($matches) {
                static $new_reparation_id = 2000;
                $old_client_id = (int)$matches[2];
                $new_client_id = 1000 + ($old_client_id - 384); // Offset basé sur le premier ID client (384)
                return '(' . $new_reparation_id++ . ',' . $new_client_id . ',';
            },
            $values_part
        );
        
        // Adapter les colonnes pour inclure la nouvelle colonne 'marque'
        $import_script .= "INSERT IGNORE INTO `reparations` (`id`, `client_id`, `type_appareil`, `marque`, `modele`, `description_probleme`, `date_reception`, `date_modification`, `date_fin_prevue`, `statut`, `statut_id`, `statut_categorie`, `signature`, `prix`, `notes_techniques`, `notes_finales`, `photo_appareil`, `mot_de_passe`, `etat_esthetique`, `prix_reparation`, `devis_envoye`, `devis_accepte`, `date_envoi_devis`, `date_reponse_devis`, `photos`, `urgent`, `commande_requise`, `archive`, `employe_id`, `date_gardiennage`, `gardiennage_facture`, `parrain_id`, `reduction_parrainage`, `reduction_parrainage_pourcentage`, `signature_client`, `photo_signature`, `photo_client`, `accept_conditions`, `proprietaire`, `signature_devis`, `date_signature_devis`) VALUES " . $values_part . ";\n";
    }
}

$import_script .= "\n-- Valider la transaction\n";
$import_script .= "COMMIT;\n\n";

$import_script .= "-- Réactiver les vérifications\n";
$import_script .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$import_script .= "SET UNIQUE_CHECKS = 1;\n";
$import_script .= "SET AUTOCOMMIT = 1;\n\n";

$import_script .= "-- Vérifications finales\n";
$import_script .= "SELECT COUNT(*) as total_clients FROM clients;\n";
$import_script .= "SELECT COUNT(*) as total_reparations FROM reparations;\n";
$import_script .= "SELECT COUNT(*) as orphaned_reparations FROM reparations r LEFT JOIN clients c ON r.client_id = c.id WHERE c.id IS NULL;\n";

// Sauvegarder le script
file_put_contents('/Users/admin/Documents/GeekBoard/import_complete.sql', $import_script);

echo "Script d'importation créé: import_complete.sql\n";
echo "Taille: " . strlen($import_script) . " caractères\n";
echo "Prêt pour l'exécution sur le serveur\n";

?>
