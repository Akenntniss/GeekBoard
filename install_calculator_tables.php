<?php
// Script pour installer la table calculator_settings dans toutes les bases de données magasin
require_once 'config/database.php';

echo "<h1>Installation des Tables du Calculateur de Prix</h1>\n";
echo "<pre>\n";

try {
    // Connexion à la base générale pour récupérer la liste des magasins
    $general_pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", "root", "Mamanmaman01#");
    $general_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupération de tous les magasins
    $stmt = $general_pdo->query("SELECT id, subdomain, db_name FROM shops WHERE active = 1");
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Magasins trouvés : " . count($shops) . "\n\n";
    
    // Lecture du script SQL
    $sql_content = file_get_contents('sql/calculator_settings.sql');
    if (!$sql_content) {
        throw new Exception("Impossible de lire le fichier SQL");
    }
    
    // Séparation des requêtes SQL
    $sql_queries = explode(';', $sql_content);
    $sql_queries = array_filter(array_map('trim', $sql_queries));
    
    foreach ($shops as $shop) {
        echo "=== Traitement du magasin: {$shop['subdomain']} ({$shop['db_name']}) ===\n";
        
        try {
            // Connexion à la base de données du magasin
            $shop_pdo = new PDO("mysql:host=localhost;dbname={$shop['db_name']}", "root", "Mamanmaman01#");
            $shop_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Exécution de chaque requête SQL
            foreach ($sql_queries as $query) {
                if (!empty($query) && !preg_match('/^\s*--/', $query)) {
                    try {
                        $shop_pdo->exec($query);
                        echo "✓ Requête exécutée avec succès\n";
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'already exists') !== false) {
                            echo "ⓘ Table déjà existante\n";
                        } else {
                            echo "✗ Erreur requête: " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
            
            // Vérification de la création de la table
            $check = $shop_pdo->query("SELECT COUNT(*) FROM calculator_settings WHERE id = 1");
            $count = $check->fetchColumn();
            
            if ($count > 0) {
                echo "✓ Table calculator_settings installée et configurée\n";
            } else {
                echo "⚠ Table créée mais configuration par défaut manquante\n";
            }
            
        } catch (PDOException $e) {
            echo "✗ Erreur connexion magasin {$shop['subdomain']}: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "=== RÉSUMÉ ===\n";
    echo "Installation terminée pour " . count($shops) . " magasins\n";
    echo "Vous pouvez maintenant accéder au calculateur via : pages/CalculateurPrix.php\n";
    
} catch (Exception $e) {
    echo "ERREUR GÉNÉRALE: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<a href='pages/CalculateurPrix.php'>Accéder au Calculateur de Prix</a>\n";
?>
