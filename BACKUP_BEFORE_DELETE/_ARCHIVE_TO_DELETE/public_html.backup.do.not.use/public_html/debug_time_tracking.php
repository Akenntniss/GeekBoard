<?php
/**
 * Script de diagnostic pour le système de pointage
 */

// Configuration de base
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Headers pour API JSON
header('Content-Type: application/json');

// Démarrer la session et initialiser
session_start();
initializeShopSession();

$debug_info = [];

try {
    // 1. Vérifier la connexion à la base de données
    $pdo = getShopDBConnection();
    $debug_info['database_connection'] = 'OK';
    
    // 2. Vérifier la session
    $debug_info['session'] = [
        'user_id' => $_SESSION['user_id'] ?? 'Non défini',
        'shop_id' => $_SESSION['shop_id'] ?? 'Non défini',
        'user_role' => $_SESSION['user_role'] ?? 'Non défini'
    ];
    
    // 3. Vérifier si la table time_tracking existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'time_tracking'");
    $table_exists = $stmt->fetch() !== false;
    $debug_info['time_tracking_table_exists'] = $table_exists;
    
    if ($table_exists) {
        // 4. Vérifier la structure de la table
        $stmt = $pdo->query("DESCRIBE time_tracking");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $debug_info['time_tracking_columns'] = $columns;
        
        // 5. Compter les entrées
        $stmt = $pdo->query("SELECT COUNT(*) FROM time_tracking");
        $count = $stmt->fetchColumn();
        $debug_info['time_tracking_entries_count'] = $count;
    }
    
    // 6. Vérifier si la table users existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $users_table_exists = $stmt->fetch() !== false;
    $debug_info['users_table_exists'] = $users_table_exists;
    
    if ($users_table_exists) {
        // 7. Récupérer les utilisateurs
        $stmt = $pdo->query("SELECT id, nom, prenom FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug_info['users_sample'] = $users;
    }
    
    // 8. Tester l'API de pointage directement
    $current_user_id = $_SESSION['user_id'] ?? null;
    if (!$current_user_id && $users_table_exists) {
        $stmt = $pdo->prepare("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user) {
            $current_user_id = $user['id'];
        }
    }
    
    $debug_info['current_user_id'] = $current_user_id;
    
    if ($current_user_id && $table_exists) {
        // Tester la requête de statut
        $stmt = $pdo->prepare("
            SELECT tt.*, 
                   CASE 
                       WHEN tt.status = 'active' THEN 1 
                       ELSE 0 
                   END as is_clocked_in,
                   CASE 
                       WHEN tt.status = 'break' THEN 1 
                       ELSE 0 
                   END as is_on_break,
                   TIME_TO_SEC(TIMEDIFF(NOW(), tt.clock_in)) / 3600 as current_duration
            FROM time_tracking tt
            WHERE tt.user_id = ? 
            AND tt.status IN ('active', 'break')
            ORDER BY tt.clock_in DESC 
            LIMIT 1
        ");
        $stmt->execute([$current_user_id]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $debug_info['current_status_query'] = $status ?: 'Aucune session active';
    }
    
    $debug_info['status'] = 'SUCCESS';
    
} catch (Exception $e) {
    $debug_info['status'] = 'ERROR';
    $debug_info['error'] = $e->getMessage();
    $debug_info['trace'] = $e->getTraceAsString();
}

// Retourner les informations de debug
echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>
