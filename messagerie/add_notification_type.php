<?php
/**
 * Script pour ajouter le type de notification 'message_admin_signature'
 * à toutes les boutiques.
 */

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

// Configuration
$type_code = 'message_admin_signature';
$description = 'Message Administratif Important (Signature)';
$importance = 3; // 3 = Critique, 2 = Haute, 1 = Normale, 0 = Basse
$icon = 'fas fa-file-signature';
$color = '#ef4444'; // Rouge

try {
    $mainPdo = new PDO("mysql:host=".MAIN_DB_HOST.";dbname=".MAIN_DB_NAME, MAIN_DB_USER, MAIN_DB_PASS);
    
    // Récupérer toutes les boutiques actives
    $stmt = $mainPdo->query("SELECT * FROM shops WHERE active=1");
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($shops) . " shops.\n";
    
    foreach ($shops as $shop) {
        echo "------------------------------------------------\n";
        echo "Processing Shop: " . $shop['name'] . " (DB: " . $shop['db_name'] . ")\n";
        
        try {
            $shopPdo = new PDO(
                "mysql:host=" . $shop['db_host'] . ";dbname=" . $shop['db_name'],
                $shop['db_user'],
                $shop['db_pass']
            );
            
            // Vérifier si la table notification_types existe
            try {
                $shopPdo->query("SELECT 1 FROM notification_types LIMIT 1");
            } catch (Exception $e) {
                echo "  Table notification_types does not exist. Skipping.\n";
                continue;
            }
            
            // Vérifier si le type existe déjà
            $stmt = $shopPdo->prepare("SELECT id FROM notification_types WHERE type_code = ?");
            $stmt->execute([$type_code]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                echo "  Type '$type_code' already exists. Updating...\n";
                $stmt = $shopPdo->prepare("
                    UPDATE notification_types 
                    SET description = ?, importance = ?, icon = ?, color = ? 
                    WHERE type_code = ?
                ");
                $stmt->execute([$description, $importance, $icon, $color, $type_code]);
            } else {
                echo "  Inserting type '$type_code'...\n";
                // On laisse l'ID en auto-increment ou on force ? Mieux vaut laisser auto.
                $stmt = $shopPdo->prepare("
                    INSERT INTO notification_types (type_code, description, importance, icon, color)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$type_code, $description, $importance, $icon, $color]);
            }
            
            echo "  Done.\n";

        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
