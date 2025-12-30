<?php
require_once 'config/database.php';

// Initialiser la session et connexion multi-magasin
initializeShopSession();
$shop_pdo = getShopDBConnection();

echo "<h2>Debug des paramètres d'entreprise</h2>";

if (!$shop_pdo) {
    echo "❌ Erreur: Impossible de se connecter à la base de données du magasin<br>";
    exit;
}

echo "✅ Connecté à la base de données: " . $_SESSION['shop_id'] . "<br><br>";

// Vérifier si la table parametres existe
try {
    $tables_result = $shop_pdo->query("SHOW TABLES LIKE 'parametres'");
    $table_exists = $tables_result->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ Table 'parametres' existe<br><br>";
        
        // Vérifier le contenu de la table
        echo "<h3>Contenu de la table parametres :</h3>";
        $stmt = $shop_pdo->prepare("SELECT * FROM parametres ORDER BY cle");
        $stmt->execute();
        $all_params = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($all_params)) {
            echo "⚠️ La table parametres est vide<br>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Clé</th><th>Valeur</th></tr>";
            foreach ($all_params as $param) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($param['cle'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($param['valeur'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Vérifier spécifiquement company_name et company_phone
        echo "<h3>Recherche spécifique company_name et company_phone :</h3>";
        $stmt_company = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
        $stmt_company->execute();
        $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (empty($company_params)) {
            echo "❌ Aucun paramètre company_name ou company_phone trouvé<br>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Paramètre</th><th>Valeur</th></tr>";
            foreach ($company_params as $key => $value) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($key) . "</td>";
                echo "<td>" . htmlspecialchars($value) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ Table 'parametres' n'existe pas<br>";
        
        // Proposer de créer la table
        echo "<h3>Création de la table parametres :</h3>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='action' value='create_table'>";
        echo "<button type='submit'>Créer la table parametres</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la vérification: " . $e->getMessage() . "<br>";
}

// Traiter les actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_table') {
        try {
            $create_sql = "
                CREATE TABLE IF NOT EXISTS `parametres` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `cle` varchar(255) NOT NULL,
                    `valeur` text,
                    `description` text,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `cle` (`cle`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $shop_pdo->exec($create_sql);
            echo "✅ Table parametres créée avec succès<br>";
            
            // Insérer les valeurs par défaut
            $insert_sql = "
                INSERT INTO parametres (cle, valeur, description) VALUES 
                ('company_name', 'Maison du Geek', 'Nom de l\'entreprise'),
                ('company_phone', '08 95 79 59 33', 'Numéro de téléphone de l\'entreprise')
                ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)
            ";
            
            $shop_pdo->exec($insert_sql);
            echo "✅ Paramètres par défaut insérés<br>";
            echo "<script>location.reload();</script>";
            
        } catch (Exception $e) {
            echo "❌ Erreur lors de la création: " . $e->getMessage() . "<br>";
        }
    }
    
    if ($action === 'update_params') {
        try {
            $company_name = $_POST['company_name'] ?? '';
            $company_phone = $_POST['company_phone'] ?? '';
            
            $stmt = $shop_pdo->prepare("
                INSERT INTO parametres (cle, valeur) VALUES 
                ('company_name', ?),
                ('company_phone', ?)
                ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)
            ");
            
            $stmt->execute([$company_name, $company_phone]);
            echo "✅ Paramètres mis à jour avec succès<br>";
            echo "<script>location.reload();</script>";
            
        } catch (Exception $e) {
            echo "❌ Erreur lors de la mise à jour: " . $e->getMessage() . "<br>";
        }
    }
}

// Formulaire de mise à jour si la table existe
try {
    $tables_result = $shop_pdo->query("SHOW TABLES LIKE 'parametres'");
    if ($tables_result->rowCount() > 0) {
        // Récupérer les valeurs actuelles
        $stmt_current = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
        $stmt_current->execute();
        $current_params = $stmt_current->fetchAll(PDO::FETCH_KEY_PAIR);
        
        echo "<h3>Mettre à jour les paramètres :</h3>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='action' value='update_params'>";
        echo "<table>";
        echo "<tr>";
        echo "<td>Nom de l'entreprise :</td>";
        echo "<td><input type='text' name='company_name' value='" . htmlspecialchars($current_params['company_name'] ?? 'Maison du Geek') . "' size='30'></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Téléphone de l'entreprise :</td>";
        echo "<td><input type='text' name='company_phone' value='" . htmlspecialchars($current_params['company_phone'] ?? '08 95 79 59 33') . "' size='30'></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td colspan='2'><button type='submit'>Mettre à jour</button></td>";
        echo "</tr>";
        echo "</table>";
        echo "</form>";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}

echo "<br><a href='javascript:history.back()'>← Retour</a>";
?>
