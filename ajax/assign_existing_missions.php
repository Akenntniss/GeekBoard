<?php
session_start();

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
    $shop_pdo = getShopDBConnection();
    
    // Créer la table user_missions si elle n'existe pas
    $check_user_missions = $shop_pdo->query("SHOW TABLES LIKE 'user_missions'");
    if ($check_user_missions->rowCount() == 0) {
        error_log("Création de la table user_missions");
        $create_user_missions_sql = "
            CREATE TABLE user_missions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                mission_id INT NOT NULL,
                statut ENUM('en_cours', 'terminee', 'echouee') DEFAULT 'en_cours',
                progres INT DEFAULT 0,
                date_assignation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                date_completion TIMESTAMP NULL,
                FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_mission (user_id, mission_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $shop_pdo->exec($create_user_missions_sql);
        error_log("Table user_missions créée");
    }
    
    // Récupérer toutes les missions actives
    $missions_stmt = $shop_pdo->prepare("SELECT id FROM missions WHERE statut = 'active'");
    $missions_stmt->execute();
    $missions = $missions_stmt->fetchAll();
    
    $assigned_count = 0;
    
    foreach ($missions as $mission) {
        $mission_id = $mission['id'];
        
        // Assigner à l'utilisateur de test (ID 2)
        try {
            $assign_stmt = $shop_pdo->prepare("
                INSERT IGNORE INTO user_missions (user_id, mission_id, statut, progres) 
                VALUES (?, ?, 'en_cours', 0)
            ");
            $result = $assign_stmt->execute([2, $mission_id]);
            
            if ($result && $assign_stmt->rowCount() > 0) {
                $assigned_count++;
                error_log("Mission ID $mission_id assignée à l'utilisateur test");
            }
        } catch (Exception $e) {
            error_log("Erreur assignation mission ID $mission_id: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$assigned_count missions assignées à l'utilisateur test",
        'missions_count' => count($missions),
        'assigned_count' => $assigned_count
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de l'assignation des missions existantes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
