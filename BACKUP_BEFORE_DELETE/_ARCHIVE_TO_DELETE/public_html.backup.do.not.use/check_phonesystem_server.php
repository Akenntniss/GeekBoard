<?php
// Script pour vérifier et créer le magasin phonesystem sur le serveur
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration serveur pour la base principale
$server_config = [
    'host' => 'localhost',
    'dbname' => 'geekboard_general',
    'username' => 'root',
    'password' => 'Mamanmaman01#'
];

echo "<h1>Vérification du magasin 'phonesystem' sur le serveur</h1>\n";

try {
    // Connexion à la base principale
    $main_pdo = new PDO(
        "mysql:host={$server_config['host']};dbname={$server_config['dbname']};charset=utf8mb4",
        $server_config['username'],
        $server_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    echo "<p style='color: green;'>✅ Connexion à la base principale réussie</p>\n";
    
    // Vérifier si le magasin phonesystem existe
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE name = ? OR subdomain = ?");
    $stmt->execute(['phonesystem', 'phonesystem']);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop) {
        echo "<p style='color: green;'>✅ Le magasin 'phonesystem' existe déjà!</p>\n";
        echo "<pre>" . print_r($shop, true) . "</pre>\n";
        
        // Vérifier la connexion à la base du magasin
        try {
            $shop_pdo = new PDO(
                "mysql:host={$shop['db_host']};dbname={$shop['db_name']};charset=utf8mb4",
                $shop['db_user'],
                $shop['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
            echo "<p style='color: green;'>✅ Connexion à la base du magasin réussie</p>\n";
            
            // Vérifier quelques tables importantes
            $tables_to_check = ['clients', 'reparations', 'users'];
            foreach ($tables_to_check as $table) {
                try {
                    $stmt = $shop_pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        echo "<p style='color: green;'>✅ Table '$table' présente</p>\n";
                        
                        // Compter les enregistrements
                        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM $table");
                        $count = $stmt->fetch()['count'];
                        echo "<p>&nbsp;&nbsp;&nbsp;📊 $count enregistrements dans '$table'</p>\n";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Table '$table' manquante</p>\n";
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erreur lors de la vérification de la table '$table': " . $e->getMessage() . "</p>\n";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Impossible de se connecter à la base du magasin: " . $e->getMessage() . "</p>\n";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ Le magasin 'phonesystem' n'existe pas</p>\n";
        echo "<p>Création du magasin en cours...</p>\n";
        
        // Créer le magasin phonesystem
        $stmt = $main_pdo->prepare("
            INSERT INTO shops (name, description, subdomain, db_host, db_port, db_name, db_user, db_pass, active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        
        $success = $stmt->execute([
            'phonesystem',
            'Magasin Phone System',
            'phonesystem',
            'localhost',
            '3306',
            'geekboard_phonesystem',
            'root', // Pour le serveur, on utilise root
            'Mamanmaman01#'
        ]);
        
        if ($success) {
            echo "<p style='color: green;'>✅ Magasin 'phonesystem' créé avec succès!</p>\n";
            $shop_id = $main_pdo->lastInsertId();
            echo "<p>ID du magasin: $shop_id</p>\n";
            
            // Créer la base de données du magasin
            echo "<p>Création de la base de données 'geekboard_phonesystem'...</p>\n";
            try {
                $main_pdo->exec("CREATE DATABASE IF NOT EXISTS geekboard_phonesystem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<p style='color: green;'>✅ Base de données 'geekboard_phonesystem' créée</p>\n";
                
                // Copier la structure depuis une base existante (par exemple mkmkmk)
                echo "<p>Copie de la structure depuis geekboard_mkmkmk...</p>\n";
                
                // Se connecter à la base mkmkmk pour obtenir la structure
                $mkmkmk_pdo = new PDO(
                    "mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4",
                    'root',
                    'Mamanmaman01#',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                
                // Obtenir toutes les tables
                $stmt = $mkmkmk_pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Se connecter à la nouvelle base
                $new_pdo = new PDO(
                    "mysql:host=localhost;dbname=geekboard_phonesystem;charset=utf8mb4",
                    'root',
                    'Mamanmaman01#',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                
                foreach ($tables as $table) {
                    try {
                        // Obtenir la structure CREATE TABLE
                        $stmt = $mkmkmk_pdo->query("SHOW CREATE TABLE `$table`");
                        $create_table = $stmt->fetch();
                        
                        // Exécuter le CREATE TABLE dans la nouvelle base
                        $new_pdo->exec($create_table['Create Table']);
                        echo "<p style='color: green;'>✅ Table '$table' créée</p>\n";
                        
                    } catch (Exception $e) {
                        echo "<p style='color: orange;'>⚠️ Erreur lors de la création de la table '$table': " . $e->getMessage() . "</p>\n";
                    }
                }
                
                echo "<p style='color: green;'>✅ Structure copiée avec succès!</p>\n";
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Erreur lors de la création de la base: " . $e->getMessage() . "</p>\n";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Erreur lors de la création du magasin</p>\n";
        }
    }
    
    // Afficher tous les magasins existants
    echo "<h2>Magasins existants dans la base:</h2>\n";
    $stmt = $main_pdo->query("SELECT id, name, subdomain, db_name, active FROM shops ORDER BY id");
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Base de données</th><th>Actif</th></tr>\n";
    foreach ($shops as $shop) {
        $status = $shop['active'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$shop['id']}</td>";
        echo "<td>{$shop['name']}</td>";
        echo "<td>{$shop['subdomain']}</td>";
        echo "<td>{$shop['db_name']}</td>";
        echo "<td>$status</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>\n";
}
?>
