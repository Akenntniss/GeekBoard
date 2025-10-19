<?php
/**
 * Script d'installation SIMPLE du Système de Primes GeekBoard
 * Se connecte directement à MySQL sans dépendances
 */

// Configuration de base
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Vérification de l'accès
if (!isset($_GET['install']) || $_GET['install'] !== 'missions_simple') {
    die('Accès refusé. Utilisez le paramètre install=missions_simple pour lancer l\'installation.');
}

// Configuration de connexion directe
$host = 'localhost';
$user = 'root';
$pass = 'Maman01#';
$database = 'geekboard_general'; // Base par défaut, on vérifiera les autres

// Fonction pour afficher les messages
function msg($text, $type = 'info') {
    $colors = ['success' => 'green', 'error' => 'red', 'warning' => 'orange', 'info' => 'blue'];
    echo "<div style='color: {$colors[$type]}; margin: 10px 0; padding: 10px; border-left: 4px solid {$colors[$type]};'>";
    echo "<strong>" . strtoupper($type) . ":</strong> $text</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation Simple - Système de Primes GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; text-align: center; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Installation Simple - Système de Primes GeekBoard</h1>
        
        <?php
        try {
            msg("Tentative de connexion à MySQL...", "info");
            
            // Connexion directe à MySQL
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            msg("✅ Connexion MySQL réussie !", "success");
            
            // Lister les bases de données GeekBoard disponibles
            $stmt = $pdo->query("SHOW DATABASES LIKE 'geekboard_%'");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            msg("Bases GeekBoard trouvées: " . implode(', ', $databases), "info");
            
            // Installer sur chaque base GeekBoard
            foreach ($databases as $db) {
                msg("📊 Installation dans la base: $db", "info");
                
                try {
                    // Se connecter à la base spécifique
                    $pdo->exec("USE `$db`");
                    
                    // Vérifier si la table users existe
                    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
                    if (!$stmt->fetch()) {
                        msg("⚠️ Table users non trouvée dans $db, passage à la suivante", "warning");
                        continue;
                    }
                    
                    // Table 1: mission_types
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS mission_types (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            nom VARCHAR(100) NOT NULL,
                            description TEXT,
                            icon VARCHAR(50) DEFAULT 'fas fa-tasks',
                            couleur VARCHAR(7) DEFAULT '#4361ee',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    
                    // Vérifier si les types existent déjà
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM mission_types");
                    $count = $stmt->fetch()['count'];
                    
                    if ($count == 0) {
                        // Insérer les types de base
                        $types = [
                            ['Reconditionnement Trottinettes', 'Reconditionnement trottinettes pour revente', 'fas fa-tools', '#2ecc71'],
                            ['Reconditionnement Smartphones', 'Reconditionnement smartphones pour revente', 'fas fa-mobile-alt', '#3498db'],
                            ['Publication LeBonCoin', 'Diffusion annonces LeBonCoin', 'fas fa-bullhorn', '#f39c12'],
                            ['Publication eBay', 'Diffusion annonces eBay', 'fas fa-shopping-cart', '#e74c3c'],
                            ['Réparation Express', 'Réparations rapides sous 24h', 'fas fa-clock', '#9b59b6'],
                            ['Satisfaction Client', 'Obtenir avis clients positifs', 'fas fa-star', '#f1c40f']
                        ];
                        
                        $stmt = $pdo->prepare("INSERT INTO mission_types (nom, description, icon, couleur) VALUES (?, ?, ?, ?)");
                        foreach ($types as $type) {
                            $stmt->execute($type);
                        }
                        msg("✅ Types de missions insérés dans $db", "success");
                    }
                    
                    // Table 2: missions
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS missions (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            titre VARCHAR(200) NOT NULL,
                            description TEXT,
                            mission_type_id INT,
                            objectif_nombre INT NOT NULL DEFAULT 1,
                            periode_jours INT NOT NULL DEFAULT 30,
                            recompense_euros DECIMAL(8,2) NOT NULL DEFAULT 0,
                            recompense_points INT NOT NULL DEFAULT 0,
                            statut ENUM('active', 'inactive', 'archivee') NOT NULL DEFAULT 'active',
                            date_debut DATE,
                            date_fin DATE,
                            created_by INT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (mission_type_id) REFERENCES mission_types(id) ON DELETE SET NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    
                    // Table 3: user_missions
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS user_missions (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            user_id INT NOT NULL,
                            mission_id INT NOT NULL,
                            progression_actuelle INT NOT NULL DEFAULT 0,
                            date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            date_completion TIMESTAMP NULL,
                            statut ENUM('en_cours', 'complete', 'abandonnee') NOT NULL DEFAULT 'en_cours',
                            recompense_attribuee BOOLEAN DEFAULT FALSE,
                            INDEX (user_id),
                            INDEX (mission_id),
                            INDEX (statut),
                            UNIQUE KEY unique_user_mission (user_id, mission_id),
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    
                    // Table 4: mission_validations
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS mission_validations (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            user_mission_id INT NOT NULL,
                            user_id INT NOT NULL,
                            mission_id INT NOT NULL,
                            description_tache TEXT NOT NULL,
                            preuve_text TEXT,
                            photo_filename VARCHAR(255),
                            validee BOOLEAN DEFAULT NULL,
                            validee_par INT NULL,
                            commentaire_validation TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            validated_at TIMESTAMP NULL,
                            INDEX (user_id),
                            INDEX (mission_id),
                            INDEX (validee),
                            FOREIGN KEY (user_mission_id) REFERENCES user_missions(id) ON DELETE CASCADE,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    
                    // Table 5: mission_recompenses
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS mission_recompenses (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            user_id INT NOT NULL,
                            mission_id INT NOT NULL,
                            user_mission_id INT NOT NULL,
                            montant_euros DECIMAL(8,2) NOT NULL DEFAULT 0,
                            points_attribues INT NOT NULL DEFAULT 0,
                            date_attribution TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            attribuee_par INT NULL,
                            commentaire TEXT,
                            INDEX (user_id),
                            INDEX (mission_id),
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
                            FOREIGN KEY (user_mission_id) REFERENCES user_missions(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    
                    msg("✅ Tables créées dans $db", "success");
                    
                    // Vérifier s'il existe déjà des missions
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM missions");
                    $missions_count = $stmt->fetch()['count'];
                    
                    if ($missions_count == 0) {
                        // Missions d'exemple
                        $missions = [
                            ['Reconditionnement Trottinettes - Février 2025', 'Reconditionner 5 trottinettes pour la vente en magasin', 1, 5, 30, 50.00, 100, '2025-02-01', '2025-02-28'],
                            ['Reconditionnement Smartphones - Février 2025', 'Reconditionner 5 smartphones pour la vente en magasin', 2, 5, 30, 75.00, 150, '2025-02-01', '2025-02-28'],
                            ['Publication LeBonCoin - Février 2025', 'Diffuser 10 annonces sur LeBonCoin', 3, 10, 30, 25.00, 50, '2025-02-01', '2025-02-28'],
                            ['Publication eBay - Février 2025', 'Diffuser 10 annonces sur eBay', 4, 10, 30, 30.00, 60, '2025-02-01', '2025-02-28']
                        ];
                        
                        $stmt = $pdo->prepare("INSERT INTO missions (titre, description, mission_type_id, objectif_nombre, periode_jours, recompense_euros, recompense_points, date_debut, date_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        foreach ($missions as $mission) {
                            $stmt->execute($mission);
                        }
                        msg("✅ Missions d'exemple créées dans $db", "success");
                    }
                    
                } catch (Exception $e) {
                    msg("❌ Erreur dans $db: " . $e->getMessage(), "error");
                }
            }
            
            echo "<div style='background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 8px; text-align: center; margin: 30px 0;'>";
            echo "<h2>🎉 Installation terminée avec succès !</h2>";
            echo "<p><strong>Le système de primes GeekBoard est maintenant opérationnel !</strong></p>";
            echo "<p><strong>Bases mises à jour:</strong> " . implode(', ', $databases) . "</p>";
            echo "<hr style='border-color: rgba(255,255,255,0.3); margin: 20px 0;'>";
            echo "<h4>📋 Prochaines étapes :</h4>";
            echo "<ul style='text-align: left; max-width: 500px; margin: 0 auto;'>";
            echo "<li><strong>Employés :</strong> Accédez à <code>?page=mes_missions</code></li>";
            echo "<li><strong>Administrateurs :</strong> Accédez à <code>?page=admin_missions</code></li>";
            echo "<li><strong>Sécurité :</strong> Supprimez ce fichier !</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>⚠️ Important :</h4>";
            echo "<p><strong>Supprimez ce fichier après l'installation :</strong></p>";
            echo "<pre>rm install_missions_simple.php</pre>";
            echo "</div>";
            
        } catch (PDOException $e) {
            msg("❌ Erreur de connexion MySQL : " . $e->getMessage(), "error");
        } catch (Exception $e) {
            msg("❌ Erreur générale : " . $e->getMessage(), "error");
        }
        ?>
    </div>
</body>
</html> 