<?php
/**
 * Script pour créer la table company_settings manquante
 */

require_once 'config/database.php';

try {
    // Se connecter à la base du magasin
    $shop_pdo = getShopDBConnection();
    
    echo "<h2>🔧 Création de la table company_settings</h2>";
    
    // Vérifier si la table existe déjà
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'company_settings'");
    $stmt->execute();
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "✅ La table company_settings existe déjà.<br>";
    } else {
        // Créer la table company_settings
        $sql = "
        CREATE TABLE `company_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `shop_id` int(11) NOT NULL,
            `company_name` varchar(255) DEFAULT NULL,
            `company_phone` varchar(20) DEFAULT NULL,
            `company_email` varchar(255) DEFAULT NULL,
            `company_address` text DEFAULT NULL,
            `company_logo` varchar(500) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_shop` (`shop_id`),
            INDEX `idx_shop_id` (`shop_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $shop_pdo->exec($sql);
        echo "✅ Table company_settings créée avec succès.<br>";
    }
    
    // Vérifier si la table relance_automatique_config existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'relance_automatique_config'");
    $stmt->execute();
    $exists_relance = $stmt->rowCount() > 0;
    
    if (!$exists_relance) {
        echo "<br>🔧 Création de la table relance_automatique_config<br>";
        
        $sql_relance = "
        CREATE TABLE `relance_automatique_config` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `shop_id` int(11) NOT NULL,
            `est_active` tinyint(1) NOT NULL DEFAULT 0,
            `relances_horaires` json DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_shop_relance` (`shop_id`),
            INDEX `idx_shop_id_relance` (`shop_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $shop_pdo->exec($sql_relance);
        echo "✅ Table relance_automatique_config créée avec succès.<br>";
    } else {
        echo "✅ La table relance_automatique_config existe déjà.<br>";
    }
    
    // Vérifier si la table preferences existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'preferences'");
    $stmt->execute();
    $exists_prefs = $stmt->rowCount() > 0;
    
    if (!$exists_prefs) {
        echo "<br>🔧 Création de la table preferences<br>";
        
        $sql_prefs = "
        CREATE TABLE `preferences` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `theme` varchar(20) DEFAULT 'light',
            `notifications` tinyint(1) DEFAULT 1,
            `elements_per_page` int(11) DEFAULT 20,
            `timezone_offset` int(11) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_prefs` (`user_id`),
            INDEX `idx_user_id_prefs` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $shop_pdo->exec($sql_prefs);
        echo "✅ Table preferences créée avec succès.<br>";
    } else {
        echo "✅ La table preferences existe déjà.<br>";
    }
    
    echo "<br>🎉 <strong>Toutes les tables nécessaires sont maintenant créées !</strong>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ Erreur : " . $e->getMessage() . "</div>";
}
?>
