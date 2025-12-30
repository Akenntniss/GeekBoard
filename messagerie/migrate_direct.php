<?php
/**
 * Script de migration DIRECTE (CLI)
 * Se connecte à la base principale pour obtenir la liste des boutiques,
 * puis se connecte à chaque base boutique pour appliquer la migration.
 */

// Paramètres de connexion BDD principale
define('MAIN_DB_HOST', 'localhost');
define('MAIN_DB_USER', 'root');
define('MAIN_DB_PASS', 'Mamanmaman01#');
define('MAIN_DB_NAME', 'geekboard_general');

try {
    echo "Connexion à la base principale...\n";
    $mainPdo = new PDO(
        "mysql:host=".MAIN_DB_HOST.";dbname=".MAIN_DB_NAME, 
        MAIN_DB_USER, 
        MAIN_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Récupérer toutes les boutiques actives
    $stmt = $mainPdo->query("SELECT * FROM shops WHERE active = 1");
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Trouvé " . count($shops) . " boutiques actives.\n";
    
    foreach ($shops as $shop) {
        echo "------------------------------------------------\n";
        echo "Traitement de la boutique: " . $shop['name'] . " (DB: " . $shop['db_name'] . ")\n";
        
        try {
            // Connexion à la base de la boutique
            $shopPdo = new PDO(
                "mysql:host=" . $shop['db_host'] . ";dbname=" . $shop['db_name'],
                $shop['db_user'],
                $shop['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // 1. Ajouter requires_signature
            $sql = "SHOW COLUMNS FROM messages LIKE 'requires_signature'";
            $result = $shopPdo->query($sql);
            if ($result->rowCount() == 0) {
                $shopPdo->exec("ALTER TABLE messages ADD COLUMN requires_signature BOOLEAN DEFAULT 0");
                echo "[OK] Colonne 'requires_signature' ajoutée.\n";
            } else {
                echo "[INFO] Colonne 'requires_signature' existe déjà.\n";
            }
            
            // 2. Créer table message_signatures
            $sqlTable = "CREATE TABLE IF NOT EXISTS message_signatures (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id INT NOT NULL,
                user_id INT NOT NULL,
                signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                signature_data TEXT,
                ip_address VARCHAR(45),
                INDEX (message_id),
                INDEX (user_id),
                FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_signature (message_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $shopPdo->exec($sqlTable);
            echo "[OK] Table 'message_signatures' vérifiée/créée.\n";
            
        } catch (PDOException $e) {
            echo "[ERREUR] Problème avec la boutique " . $shop['name'] . ": " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    die("[FATAL] Impossible de se connecter à la base principale: " . $e->getMessage() . "\n");
}

echo "------------------------------------------------\n";
echo "Migration globale terminée.\n";
