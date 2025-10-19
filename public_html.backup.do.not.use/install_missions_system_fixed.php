<?php
/**
 * Script d'installation du Système de Primes GeekBoard (Version Corrigée)
 * 
 * Ce script installe automatiquement toutes les tables nécessaires
 * pour le système de missions et primes dans GeekBoard en utilisant
 * le système de base de données existant
 */

// Configuration de base
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Fonction pour afficher les messages avec style
function afficher_message($message, $type = 'info') {
    $colors = [
        'success' => '#28a745',
        'error' => '#dc3545',
        'warning' => '#ffc107',
        'info' => '#17a2b8'
    ];
    
    echo "<div style='padding: 10px; margin: 10px 0; border-left: 4px solid {$colors[$type]}; background: {$colors[$type]}15; border-radius: 4px;'>";
    echo "<strong>" . strtoupper($type) . ":</strong> $message";
    echo "</div>";
}

// Vérification de l'accès admin
if (!isset($_GET['install']) || $_GET['install'] !== 'missions_system_fixed') {
    die('Accès refusé. Utilisez le paramètre install=missions_system_fixed pour lancer l\'installation.');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Système de Primes GeekBoard (Corrigée)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; }
        .progress { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; margin: 20px 0; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); border-radius: 10px; transition: width 0.3s; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Installation du Système de Primes GeekBoard (Corrigée)</h1>
        
        <?php
        try {
            // Inclusion des fichiers de configuration GeekBoard existants
            if (file_exists('config/database.php')) {
                require_once('config/database.php');
                afficher_message("✅ Configuration de base de données GeekBoard chargée", "success");
            } else {
                afficher_message("❌ Configuration de base de données GeekBoard introuvable", "error");
                throw new Exception("Fichier config/database.php introuvable");
            }
            
            // Démarrer la session si pas encore fait
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            afficher_message("🔌 Tentative de connexion via le système GeekBoard...", "info");
            
            // Utiliser le système de connexion GeekBoard existant
            $pdo = null;
            
            // Essayer d'abord la connexion principale
            try {
                $pdo = getMainDBConnection();
                if ($pdo) {
                    afficher_message("✅ Connexion à la base principale GeekBoard réussie !", "success");
                } else {
                    afficher_message("⚠️ Connexion principale non disponible, tentative connexion magasin...", "warning");
                    
                    // Essayer la connexion magasin
                    $pdo = getShopDBConnection();
                    if ($pdo) {
                        afficher_message("✅ Connexion à la base de données du magasin réussie !", "success");
                    }
                }
            } catch (Exception $e) {
                afficher_message("⚠️ Connexion principale échouée: " . $e->getMessage(), "warning");
                
                // Essayer la connexion magasin en dernier recours
                try {
                    $pdo = getShopDBConnection();
                    if ($pdo) {
                        afficher_message("✅ Connexion à la base de données du magasin réussie !", "success");
                    }
                } catch (Exception $e2) {
                    afficher_message("❌ Toutes les tentatives de connexion ont échoué", "error");
                    throw new Exception("Impossible de se connecter à la base de données GeekBoard");
                }
            }
            
            if (!$pdo) {
                throw new Exception("Impossible d'obtenir une connexion à la base de données");
            }
            
            // Vérifier quelle base nous utilisons
            $stmt = $pdo->query("SELECT DATABASE() as db_name");
            $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
            afficher_message("📊 Installation dans la base de données: " . ($db_info['db_name'] ?? 'Inconnue'), "info");
            
            // Étape 1: Création de la table mission_types
            afficher_message("📋 Étape 1/5 : Création de la table des types de missions...", "info");
            
            $sql_mission_types = "
            CREATE TABLE IF NOT EXISTS mission_types (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nom VARCHAR(100) NOT NULL,
                description TEXT,
                icon VARCHAR(50) DEFAULT 'fas fa-tasks',
                couleur VARCHAR(7) DEFAULT '#4361ee',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->exec($sql_mission_types);
            afficher_message("✅ Table mission_types créée avec succès", "success");
            
            // Vérifier si les types existent déjà
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM mission_types");
            $count = $stmt->fetch()['count'];
            
            if ($count == 0) {
                // Insertion des types de missions de base
                $types_missions = [
                    ['Reconditionnement Trottinettes', 'Reconditionnement d\'appareils trottinettes pour la revente', 'fas fa-tools', '#2ecc71'],
                    ['Reconditionnement Smartphones', 'Reconditionnement de smartphones pour la revente', 'fas fa-mobile-alt', '#3498db'],
                    ['Publication LeBonCoin', 'Diffusion d\'annonces sur LeBonCoin', 'fas fa-bullhorn', '#f39c12'],
                    ['Publication eBay', 'Diffusion d\'annonces sur eBay', 'fas fa-shopping-cart', '#e74c3c'],
                    ['Réparation Express', 'Réparations rapides sous 24h', 'fas fa-clock', '#9b59b6'],
                    ['Satisfaction Client', 'Obtenir des avis clients positifs', 'fas fa-star', '#f1c40f']
                ];
                
                $stmt = $pdo->prepare("INSERT INTO mission_types (nom, description, icon, couleur) VALUES (?, ?, ?, ?)");
                foreach ($types_missions as $type) {
                    $stmt->execute($type);
                }
                afficher_message("✅ Types de missions de base insérés", "success");
            } else {
                afficher_message("ℹ️ Types de missions déjà existants ($count trouvés)", "info");
            }
            
            // Étape 2: Création de la table missions
            afficher_message("🎯 Étape 2/5 : Création de la table des missions...", "info");
            
            $sql_missions = "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->exec($sql_missions);
            afficher_message("✅ Table missions créée avec succès", "success");
            
            // Étape 3: Création de la table user_missions
            afficher_message("👥 Étape 3/5 : Création de la table de progression des utilisateurs...", "info");
            
            $sql_user_missions = "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->exec($sql_user_missions);
            afficher_message("✅ Table user_missions créée avec succès", "success");
            
            // Étape 4: Création de la table mission_validations
            afficher_message("✅ Étape 4/5 : Création de la table des validations...", "info");
            
            $sql_validations = "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->exec($sql_validations);
            afficher_message("✅ Table mission_validations créée avec succès", "success");
            
            // Étape 5: Création de la table mission_recompenses
            afficher_message("💰 Étape 5/5 : Création de la table des récompenses...", "info");
            
            $sql_recompenses = "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->exec($sql_recompenses);
            afficher_message("✅ Table mission_recompenses créée avec succès", "success");
            
            // Création des vues
            afficher_message("📊 Création des vues pour les statistiques...", "info");
            
            try {
                // Vue pour les statistiques des missions
                $vue_stats = "
                CREATE OR REPLACE VIEW mission_stats AS
                SELECT 
                    m.id as mission_id,
                    m.titre,
                    m.objectif_nombre,
                    m.recompense_euros,
                    COUNT(DISTINCT um.user_id) as participants,
                    COUNT(CASE WHEN um.statut = 'complete' THEN 1 END) as completions,
                    SUM(CASE WHEN um.statut = 'complete' THEN m.recompense_euros ELSE 0 END) as total_recompenses,
                    AVG(um.progression_actuelle) as progression_moyenne,
                    m.statut as mission_statut
                FROM missions m
                LEFT JOIN user_missions um ON m.id = um.mission_id
                GROUP BY m.id;
                ";
                
                $pdo->exec($vue_stats);
                
                // Vue pour le tableau de bord employé
                $vue_dashboard = "
                CREATE OR REPLACE VIEW user_mission_dashboard AS
                SELECT 
                    u.id as user_id,
                    u.full_name,
                    u.role,
                    COUNT(DISTINCT um.mission_id) as missions_actives,
                    COUNT(CASE WHEN um.statut = 'complete' THEN 1 END) as missions_completees,
                    SUM(CASE WHEN um.statut = 'complete' THEN mr.montant_euros ELSE 0 END) as total_gains,
                    SUM(CASE WHEN um.statut = 'complete' THEN mr.points_attribues ELSE 0 END) as total_points_missions,
                    u.score_total + COALESCE(SUM(CASE WHEN um.statut = 'complete' THEN mr.points_attribues ELSE 0 END), 0) as score_total_avec_missions
                FROM users u
                LEFT JOIN user_missions um ON u.id = um.user_id
                LEFT JOIN mission_recompenses mr ON um.id = mr.user_mission_id
                WHERE u.role = 'technicien'
                GROUP BY u.id;
                ";
                
                $pdo->exec($vue_dashboard);
                afficher_message("✅ Vues créées avec succès", "success");
            } catch (Exception $e) {
                afficher_message("⚠️ Erreur lors de la création des vues (non critique): " . $e->getMessage(), "warning");
            }
            
            // Vérifier s'il existe déjà des missions
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM missions");
            $missions_count = $stmt->fetch()['count'];
            
            if ($missions_count == 0) {
                // Insertion de missions d'exemple uniquement si la table est vide
                afficher_message("🚀 Insertion de missions d'exemple...", "info");
                
                $missions_exemple = [
                    [
                        'Reconditionnement Trottinettes - Février 2025',
                        'Reconditionner 5 trottinettes pour la vente en magasin dans le mois',
                        1, 5, 30, 50.00, 100,
                        '2025-02-01', '2025-02-28'
                    ],
                    [
                        'Reconditionnement Smartphones - Février 2025', 
                        'Reconditionner 5 smartphones pour la vente en magasin dans le mois',
                        2, 5, 30, 75.00, 150,
                        '2025-02-01', '2025-02-28'
                    ],
                    [
                        'Publication LeBonCoin - Février 2025',
                        'Diffuser 10 annonces sur LeBonCoin dans le mois',
                        3, 10, 30, 25.00, 50,
                        '2025-02-01', '2025-02-28'
                    ],
                    [
                        'Publication eBay - Février 2025',
                        'Diffuser 10 annonces sur eBay dans le mois', 
                        4, 10, 30, 30.00, 60,
                        '2025-02-01', '2025-02-28'
                    ]
                ];
                
                $stmt = $pdo->prepare("
                    INSERT INTO missions (titre, description, mission_type_id, objectif_nombre, periode_jours, recompense_euros, recompense_points, date_debut, date_fin) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($missions_exemple as $mission) {
                    $stmt->execute($mission);
                }
                
                afficher_message("✅ Missions d'exemple créées avec succès", "success");
            } else {
                afficher_message("ℹ️ Missions existantes trouvées ($missions_count), pas d'insertion d'exemples", "info");
            }
            
            // Message de succès final
            echo "<div style='background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 8px; text-align: center; margin: 30px 0;'>";
            echo "<h2>🎉 Installation terminée avec succès !</h2>";
            echo "<p><strong>Le système de primes GeekBoard est maintenant opérationnel !</strong></p>";
            echo "<p><strong>Base de données utilisée:</strong> " . ($db_info['db_name'] ?? 'Inconnue') . "</p>";
            echo "<hr style='border-color: rgba(255,255,255,0.3); margin: 20px 0;'>";
            echo "<h4>📋 Prochaines étapes :</h4>";
            echo "<ul style='text-align: left; max-width: 500px; margin: 0 auto;'>";
            echo "<li><strong>Employés :</strong> Accédez à <code>?page=mes_missions</code></li>";
            echo "<li><strong>Administrateurs :</strong> Accédez à <code>?page=admin_missions</code></li>";
            echo "<li><strong>Sécurité :</strong> Supprimez ce fichier d'installation !</li>";
            echo "</ul>";
            echo "</div>";
            
            // Informations techniques
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>🔧 Tables créées/vérifiées :</h4>";
            echo "<ul>";
            echo "<li><code>mission_types</code> - Types de missions (6 types pré-configurés)</li>";
            echo "<li><code>missions</code> - Missions disponibles</li>";
            echo "<li><code>user_missions</code> - Progression des employés</li>";
            echo "<li><code>mission_validations</code> - Validations des tâches</li>";
            echo "<li><code>mission_recompenses</code> - Récompenses versées</li>";
            echo "<li><code>mission_stats</code> - Vue des statistiques</li>";
            echo "<li><code>user_mission_dashboard</code> - Vue tableau de bord employé</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>⚠️ Important :</h4>";
            echo "<p><strong>Pour des raisons de sécurité, supprimez ce fichier après l'installation :</strong></p>";
            echo "<pre>rm install_missions_system_fixed.php</pre>";
            echo "</div>";
            
        } catch (PDOException $e) {
            afficher_message("❌ Erreur de base de données : " . $e->getMessage(), "error");
            echo "<pre>Détails de l'erreur :\n" . $e->getTraceAsString() . "</pre>";
        } catch (Exception $e) {
            afficher_message("❌ Erreur générale : " . $e->getMessage(), "error");
            echo "<pre>Détails de l'erreur :\n" . $e->getTraceAsString() . "</pre>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px; color: #6c757d;">
            <small>Installation du Système de Primes GeekBoard v1.0 | Compatible avec l'architecture GeekBoard existante</small>
        </div>
    </div>
</body>
</html> 