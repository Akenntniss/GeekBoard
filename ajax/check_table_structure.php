<?php
header('Content-Type: application/json');
session_start();

// Forcer la session admin
$_SESSION["shop_id"] = 63;
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la structure de la table user_missions
    $stmt = $shop_pdo->query("DESCRIBE user_missions");
    $user_missions_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier la structure de la table missions
    $stmt = $shop_pdo->query("DESCRIBE missions");
    $missions_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier s'il y a des données dans user_missions
    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM user_missions");
    $user_missions_count = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'user_missions_columns' => array_column($user_missions_columns, 'Field'),
        'missions_columns' => array_column($missions_columns, 'Field'),
        'user_missions_count' => $user_missions_count,
        'full_structure' => [
            'user_missions' => $user_missions_columns,
            'missions' => $missions_columns
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>