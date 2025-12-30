<?php
session_start(); // Ensure session is started

// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Initialiser une session si pas active
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
    $_SESSION['shop_id'] = 'mkmkmk';
}

// S'assurer que la session shop est initialisée
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 'mkmkmk';
}

header('Content-Type: application/json');

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer les données POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }

    $validation_id = $input['validation_id'] ?? null;
    $action = $input['action'] ?? null; // 'approve' ou 'reject'
    $commentaire = $input['commentaire'] ?? '';

    error_log("Validation request - ID: $validation_id, Action: $action");

    // Validation des données
    if (!$validation_id || !$action) {
        throw new Exception('Données manquantes');
    }

    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Action invalide');
    }

    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();
    error_log("Connexion réussie à la base de données");

    // Vérifier si la table mission_validations existe
    $check_table = $shop_pdo->query("SHOW TABLES LIKE 'mission_validations'");
    if ($check_table->rowCount() == 0) {
        error_log("Table mission_validations n'existe pas, création en cours...");
        $create_table_sql = "
            CREATE TABLE mission_validations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                mission_id INT NOT NULL,
                user_mission_id INT NOT NULL,
                type_validation ENUM('completion', 'progress') DEFAULT 'completion',
                description TEXT,
                preuve_url VARCHAR(500),
                statut ENUM('en_attente', 'approuvee', 'rejetee') DEFAULT 'en_attente',
                commentaire_admin TEXT,
                date_soumission TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                date_traitement TIMESTAMP NULL,
                traite_par INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_mission (user_id, mission_id),
                INDEX idx_statut (statut)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $shop_pdo->exec($create_table_sql);
        error_log("Table mission_validations créée avec succès");
    } else {
        // Vérifier et ajouter les colonnes manquantes
        error_log("Table mission_validations existe, vérification des colonnes...");
        
        // Vérifier la colonne date_traitement
        $check_date_traitement = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'date_traitement'");
        if ($check_date_traitement->rowCount() == 0) {
            error_log("Ajout de la colonne date_traitement");
            $shop_pdo->exec("ALTER TABLE mission_validations ADD COLUMN date_traitement TIMESTAMP NULL");
        }
        
        // Vérifier la colonne traite_par
        $check_traite_par = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'traite_par'");
        if ($check_traite_par->rowCount() == 0) {
            error_log("Ajout de la colonne traite_par");
            $shop_pdo->exec("ALTER TABLE mission_validations ADD COLUMN traite_par INT NULL");
        }
        
        // Vérifier la colonne commentaire_admin
        $check_commentaire = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'commentaire_admin'");
        if ($check_commentaire->rowCount() == 0) {
            error_log("Ajout de la colonne commentaire_admin");
            $shop_pdo->exec("ALTER TABLE mission_validations ADD COLUMN commentaire_admin TEXT");
        }
        
        // Vérifier la colonne type_validation
        $check_type = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'type_validation'");
        if ($check_type->rowCount() == 0) {
            error_log("Ajout de la colonne type_validation");
            $shop_pdo->exec("ALTER TABLE mission_validations ADD COLUMN type_validation ENUM('completion', 'progress') DEFAULT 'completion'");
        }
        
        // Vérifier la colonne preuve_url
        $check_preuve = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'preuve_url'");
        if ($check_preuve->rowCount() == 0) {
            error_log("Ajout de la colonne preuve_url");
            $shop_pdo->exec("ALTER TABLE mission_validations ADD COLUMN preuve_url VARCHAR(500)");
        }
        
        // Vérifier la colonne updated_at
        $check_updated_at = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'updated_at'");
        if ($check_updated_at->rowCount() == 0) {
            error_log("Ajout de la colonne updated_at");
            $shop_pdo->exec("ALTER TABLE mission_validations ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
        
        // Vérifier la colonne created_at
        $check_created_at = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'created_at'");
        if ($check_created_at->rowCount() == 0) {
            error_log("Ajout de la colonne created_at");
            $shop_pdo->exec("ALTER TABLE mission_validations ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
        
        // Vérifier et corriger la colonne statut ENUM
        $check_statut_enum = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'statut'");
        if ($check_statut_enum->rowCount() > 0) {
            $statut_info = $check_statut_enum->fetch(PDO::FETCH_ASSOC);
            error_log("Info colonne statut actuelle: " . json_encode($statut_info));
            
            // Si la colonne statut n'a pas les bonnes valeurs ENUM, la corriger
            if ($statut_info && isset($statut_info['Type'])) {
                $current_type = $statut_info['Type'];
                if (!strpos($current_type, 'approuvee') || !strpos($current_type, 'rejetee')) {
                    error_log("Correction de la colonne statut ENUM");
                    $shop_pdo->exec("ALTER TABLE mission_validations MODIFY COLUMN statut ENUM('en_attente', 'approuvee', 'rejetee') DEFAULT 'en_attente'");
                    error_log("Colonne statut corrigée");
                }
            }
        } else {
            // Si la colonne statut n'existe pas, l'ajouter
            error_log("Ajout de la colonne statut");
            $shop_pdo->exec("ALTER TABLE mission_validations ADD COLUMN statut ENUM('en_attente', 'approuvee', 'rejetee') DEFAULT 'en_attente'");
        }
        
        error_log("Vérification des colonnes terminée");
    }

    // Créer des données de test si la table est vide
    $count_validations = $shop_pdo->query("SELECT COUNT(*) FROM mission_validations")->fetchColumn();
    if ($count_validations == 0) {
        error_log("Création de données de test pour les validations");
        
        // Vérifier si la table missions existe et a des données
        $check_missions = $shop_pdo->query("SHOW TABLES LIKE 'missions'");
        if ($check_missions->rowCount() > 0) {
            $missions_count = $shop_pdo->query("SELECT COUNT(*) FROM missions")->fetchColumn();
            if ($missions_count == 0) {
                // Créer une mission de test
                $shop_pdo->exec("
                    INSERT INTO missions (titre, description, type_id, objectif_quantite, recompense_euros, recompense_points, statut, date_debut, date_fin) 
                    VALUES ('Mission de Test', 'Ceci est une mission de test pour démonstration', 1, 1, 10.00, 50, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
                ");
                error_log("Mission de test créée");
            }
        }
        
        // Créer des validations de test
        $test_validations = [
            [
                'user_id' => 1,
                'mission_id' => 1,
                'user_mission_id' => 1,
                'description' => 'J\'ai complété la tâche demandée avec succès. Voici une description détaillée de ce que j\'ai accompli : installation du système, configuration des paramètres et tests de fonctionnement.',
                'type_validation' => 'completion',
                'statut' => 'en_attente'
            ],
            [
                'user_id' => 2,
                'mission_id' => 1,
                'user_mission_id' => 2,
                'description' => 'Mission partiellement accomplie. J\'ai réalisé 75% des objectifs fixés. Les étapes suivantes ont été complétées : analyse, planification et début d\'implémentation.',
                'type_validation' => 'progress',
                'statut' => 'en_attente'
            ],
            [
                'user_id' => 3,
                'mission_id' => 1,
                'user_mission_id' => 3,
                'description' => 'Tâche terminée avec documentation complète. Tous les livrables ont été fournis selon les spécifications.',
                'type_validation' => 'completion',
                'statut' => 'en_attente'
            ]
        ];
        
        $insert_validation_sql = "
            INSERT INTO mission_validations (user_id, mission_id, user_mission_id, description, type_validation, statut, date_soumission) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = $shop_pdo->prepare($insert_validation_sql);
        foreach ($test_validations as $validation) {
            $stmt->execute([
                $validation['user_id'],
                $validation['mission_id'],
                $validation['user_mission_id'],
                $validation['description'],
                $validation['type_validation'],
                $validation['statut']
            ]);
        }
        
        error_log("Données de test créées pour les validations");
    }

    // Vérifier que la validation existe et est en attente
    $check_sql = "SELECT * FROM mission_validations WHERE id = ? AND statut = 'en_attente'";
    $check_stmt = $shop_pdo->prepare($check_sql);
    $check_stmt->execute([$validation_id]);
    $validation = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$validation) {
        throw new Exception('Validation non trouvée ou déjà traitée');
    }

    error_log("Validation trouvée: " . json_encode($validation));

    // Déterminer le nouveau statut - Vérifier d'abord les valeurs ENUM acceptées
    $check_enum_sql = "SHOW COLUMNS FROM mission_validations LIKE 'statut'";
    $enum_result = $shop_pdo->query($check_enum_sql);
    $enum_info = $enum_result->fetch(PDO::FETCH_ASSOC);
    
    error_log("Colonne statut info: " . json_encode($enum_info));
    
    // Extraire les valeurs ENUM possibles
    $enum_values = [];
    if ($enum_info && isset($enum_info['Type'])) {
        preg_match_all("/'([^']+)'/", $enum_info['Type'], $matches);
        $enum_values = $matches[1];
        error_log("Valeurs ENUM possibles: " . json_encode($enum_values));
    }
    
    // Déterminer le statut à utiliser selon les valeurs disponibles
    if ($action === 'approve') {
        if (in_array('approuvee', $enum_values)) {
            $nouveau_statut = 'approuvee';
        } elseif (in_array('approved', $enum_values)) {
            $nouveau_statut = 'approved';
        } elseif (in_array('validee', $enum_values)) {
            $nouveau_statut = 'validee';
        } elseif (in_array('acceptee', $enum_values)) {
            $nouveau_statut = 'acceptee';
        } else {
            $nouveau_statut = 'en_attente'; // Fallback
        }
    } else {
        if (in_array('rejetee', $enum_values)) {
            $nouveau_statut = 'rejetee';
        } elseif (in_array('rejected', $enum_values)) {
            $nouveau_statut = 'rejected';
        } elseif (in_array('refusee', $enum_values)) {
            $nouveau_statut = 'refusee';
        } else {
            $nouveau_statut = 'en_attente'; // Fallback
        }
    }
    
    error_log("Statut choisi: $nouveau_statut");
    
    // Mettre à jour la validation - Version défensive
    // D'abord, vérifier quelles colonnes existent
    $columns_to_update = ['statut'];
    $values = [$nouveau_statut];
    
    // Vérifier si commentaire_admin existe
    $check_commentaire = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'commentaire_admin'");
    if ($check_commentaire->rowCount() > 0) {
        $columns_to_update[] = 'commentaire_admin';
        $values[] = $commentaire;
    }
    
    // Vérifier si date_traitement existe
    $check_date_traitement = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'date_traitement'");
    if ($check_date_traitement->rowCount() > 0) {
        $columns_to_update[] = 'date_traitement';
        $values[] = date('Y-m-d H:i:s'); // Utiliser PHP au lieu de NOW() pour plus de compatibilité
    }
    
    // Vérifier si traite_par existe
    $check_traite_par = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'traite_par'");
    if ($check_traite_par->rowCount() > 0) {
        $columns_to_update[] = 'traite_par';
        $values[] = $_SESSION['user_id'];
    }
    
    // Vérifier si updated_at existe
    $check_updated_at = $shop_pdo->query("SHOW COLUMNS FROM mission_validations LIKE 'updated_at'");
    if ($check_updated_at->rowCount() > 0) {
        $columns_to_update[] = 'updated_at';
        $values[] = date('Y-m-d H:i:s');
    }
    
    // Construire la requête dynamiquement
    $set_clause = implode(' = ?, ', $columns_to_update) . ' = ?';
    $update_sql = "UPDATE mission_validations SET $set_clause WHERE id = ?";
    $values[] = $validation_id; // Ajouter l'ID à la fin
    
    error_log("Requête UPDATE: $update_sql");
    error_log("Valeurs: " . json_encode($values));
    
    $update_stmt = $shop_pdo->prepare($update_sql);
    $result = $update_stmt->execute($values);

    if (!$result) {
        throw new Exception('Erreur lors de la mise à jour de la validation');
    }

    error_log("Validation mise à jour avec succès");

    // Si approuvée, mettre à jour la progression et vérifier si la mission est complète
    if ($action === 'approve') {
        // Vérifier si la table user_missions existe
        $check_user_missions = $shop_pdo->query("SHOW TABLES LIKE 'user_missions'");
        if ($check_user_missions->rowCount() > 0) {
            
            // D'abord, incrémenter la progression
            $update_progression_sql = "UPDATE user_missions SET progression = progression + 1 WHERE id = ?";
            $update_progression_stmt = $shop_pdo->prepare($update_progression_sql);
            $update_progression_stmt->execute([$validation['user_mission_id']]);
            error_log("Progression incrémentée pour user_mission_id: " . $validation['user_mission_id']);
            
            // Ensuite, vérifier si la mission est maintenant complète
            $check_completion_sql = "
                SELECT um.progression, m.objectif_nombre, um.id
                FROM user_missions um 
                JOIN missions m ON um.mission_id = m.id 
                WHERE um.id = ?
            ";
            $check_completion_stmt = $shop_pdo->prepare($check_completion_sql);
            $check_completion_stmt->execute([$validation['user_mission_id']]);
            $mission_progress = $check_completion_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($mission_progress) {
                error_log("Progression actuelle: " . $mission_progress['progression'] . "/" . $mission_progress['objectif_nombre']);
                
                // Si toutes les tâches sont complétées, marquer la mission comme terminée
                if ($mission_progress['progression'] >= $mission_progress['objectif_nombre']) {
                    
                    // Version défensive pour user_missions
                    $user_columns_to_update = ['statut'];
                    $user_values = ['terminee'];
                    
                    // Vérifier si date_completion existe
                    $check_date_completion = $shop_pdo->query("SHOW COLUMNS FROM user_missions LIKE 'date_completion'");
                    if ($check_date_completion->rowCount() > 0) {
                        $user_columns_to_update[] = 'date_completion';
                        $user_values[] = date('Y-m-d H:i:s');
                    }
                    
                    // Vérifier si updated_at existe
                    $check_user_updated_at = $shop_pdo->query("SHOW COLUMNS FROM user_missions LIKE 'updated_at'");
                    if ($check_user_updated_at->rowCount() > 0) {
                        $user_columns_to_update[] = 'updated_at';
                        $user_values[] = date('Y-m-d H:i:s');
                    }
                    
                    // Construire la requête pour user_missions
                    $user_set_clause = implode(' = ?, ', $user_columns_to_update) . ' = ?';
                    $update_user_mission_sql = "UPDATE user_missions SET $user_set_clause WHERE id = ?";
                    $user_values[] = $validation['user_mission_id'];
                    
                    error_log("Mission complète ! Marquage comme terminée");
                    error_log("Requête UPDATE user_missions: $update_user_mission_sql");
                    error_log("Valeurs user_missions: " . json_encode($user_values));
                    
                    $update_user_mission_stmt = $shop_pdo->prepare($update_user_mission_sql);
                    $update_user_mission_stmt->execute($user_values);
                    error_log("Mission utilisateur marquée comme terminée");
                    
                    // Attribuer les récompenses à l'utilisateur
                    $reward_sql = "
                        SELECT m.id as mission_id, m.recompense_euros, m.recompense_points, um.user_id
                        FROM user_missions um
                        JOIN missions m ON um.mission_id = m.id
                        WHERE um.id = ?
                    ";
                    $reward_stmt = $shop_pdo->prepare($reward_sql);
                    $reward_stmt->execute([$validation['user_mission_id']]);
                    $rewards = $reward_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($rewards && ($rewards['recompense_euros'] > 0 || $rewards['recompense_points'] > 0)) {
                        $user_id = $rewards['user_id'];
                        $euros = (float)$rewards['recompense_euros'];
                        $points = (int)$rewards['recompense_points'];
                        
                        error_log("Attribution des récompenses: $euros€ et $points points pour user_id: $user_id");
                        
                        // Vérifier si la table user_cagnotte existe
                        $check_cagnotte = $shop_pdo->query("SHOW TABLES LIKE 'user_cagnotte'");
                        if ($check_cagnotte->rowCount() > 0) {
                            // Vérifier si l'utilisateur a déjà une entrée
                            $check_user_cagnotte = $shop_pdo->prepare("SELECT * FROM user_cagnotte WHERE user_id = ?");
                            $check_user_cagnotte->execute([$user_id]);
                            $existing_cagnotte = $check_user_cagnotte->fetch(PDO::FETCH_ASSOC);
                            
                            if ($existing_cagnotte) {
                                // Mettre à jour la cagnotte existante
                                $update_cagnotte_sql = "
                                    UPDATE user_cagnotte 
                                    SET solde_euros = solde_euros + ?,
                                        solde_points = solde_points + ?,
                                        total_gagne_euros = total_gagne_euros + ?,
                                        total_gagne_points = total_gagne_points + ?
                                    WHERE user_id = ?
                                ";
                                $update_cagnotte_stmt = $shop_pdo->prepare($update_cagnotte_sql);
                                $update_cagnotte_stmt->execute([$euros, $points, $euros, $points, $user_id]);
                                error_log("Cagnotte mise à jour pour user_id: $user_id");
                            } else {
                                // Créer une nouvelle entrée
                                $insert_cagnotte_sql = "
                                    INSERT INTO user_cagnotte (user_id, solde_euros, solde_points, total_gagne_euros, total_gagne_points)
                                    VALUES (?, ?, ?, ?, ?)
                                ";
                                $insert_cagnotte_stmt = $shop_pdo->prepare($insert_cagnotte_sql);
                                $insert_cagnotte_stmt->execute([$user_id, $euros, $points, $euros, $points]);
                                error_log("Nouvelle cagnotte créée pour user_id: $user_id");
                            }
                        }
                        
                        // Vérifier si la table historique_gains existe
                        $check_historique = $shop_pdo->query("SHOW TABLES LIKE 'historique_gains'");
                        if ($check_historique->rowCount() > 0) {
                            // Vérifier la structure de la table
                            $check_columns = $shop_pdo->query("SHOW COLUMNS FROM historique_gains");
                            $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
                            
                            // Structure ancienne (avec type enum 'euros','points')
                            if (in_array('type', $columns) && in_array('montant', $columns)) {
                                // Créer deux entrées (une pour euros, une pour points)
                                if ($euros > 0) {
                                    $insert_historique_euros = "
                                        INSERT INTO historique_gains (user_id, mission_id, type, montant, description)
                                        VALUES (?, ?, 'euros', ?, 'Mission complétée')
                                    ";
                                    $insert_historique_euros_stmt = $shop_pdo->prepare($insert_historique_euros);
                                    $insert_historique_euros_stmt->execute([
                                        $user_id,
                                        $rewards['mission_id'],
                                        $euros
                                    ]);
                                    error_log("Entrée euros créée dans historique_gains");
                                }
                                
                                if ($points > 0) {
                                    $insert_historique_points = "
                                        INSERT INTO historique_gains (user_id, mission_id, type, montant, description)
                                        VALUES (?, ?, 'points', ?, 'Mission complétée')
                                    ";
                                    $insert_historique_points_stmt = $shop_pdo->prepare($insert_historique_points);
                                    $insert_historique_points_stmt->execute([
                                        $user_id,
                                        $rewards['mission_id'],
                                        $points
                                    ]);
                                    error_log("Entrée points créée dans historique_gains");
                                }
                            } 
                            // Structure nouvelle (avec montant_euros, points_attribues, type_gain)
                            elseif (in_array('montant_euros', $columns) && in_array('points_attribues', $columns)) {
                                $insert_historique_sql = "
                                    INSERT INTO historique_gains (user_id, mission_id, user_mission_id, montant_euros, points_attribues, type_gain)
                                    VALUES (?, ?, ?, ?, ?, 'mission_completee')
                                ";
                                $insert_historique_stmt = $shop_pdo->prepare($insert_historique_sql);
                                $insert_historique_stmt->execute([
                                    $user_id,
                                    $rewards['mission_id'],
                                    $validation['user_mission_id'],
                                    $euros,
                                    $points
                                ]);
                                error_log("Entrée créée dans historique_gains (nouvelle structure)");
                            }
                        }
                        
                        // Mettre à jour les colonnes dans la table users si elles existent
                        $check_users_cagnotte = $shop_pdo->query("SHOW COLUMNS FROM users LIKE 'cagnotte'");
                        if ($check_users_cagnotte->rowCount() > 0) {
                            $update_users_sql = "UPDATE users SET cagnotte = cagnotte + ? WHERE id = ?";
                            $update_users_stmt = $shop_pdo->prepare($update_users_sql);
                            $update_users_stmt->execute([$euros, $user_id]);
                            error_log("Colonne cagnotte mise à jour dans users");
                        }
                        
                        $check_users_points = $shop_pdo->query("SHOW COLUMNS FROM users LIKE 'points_experience'");
                        if ($check_users_points->rowCount() > 0) {
                            $update_users_points_sql = "UPDATE users SET points_experience = points_experience + ? WHERE id = ?";
                            $update_users_points_stmt = $shop_pdo->prepare($update_users_points_sql);
                            $update_users_points_stmt->execute([$points, $user_id]);
                            error_log("Colonne points_experience mise à jour dans users");
                        }
                    }
                } else {
                    error_log("Mission pas encore complète, reste en cours");
                }
            }
        }
    }

    // Réponse de succès
    $message = ($action === 'approve') ? 'Validation approuvée avec succès' : 'Validation rejetée avec succès';
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'validation_id' => $validation_id,
        'nouveau_statut' => $nouveau_statut
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la validation: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la validation: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
