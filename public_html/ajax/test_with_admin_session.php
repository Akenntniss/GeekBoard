<?php
// Test avec session admin forcée
session_start();

// Forcer les variables de session pour admin (pour test uniquement)
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";
$_SESSION["role"] = "admin";  
$_SESSION["full_name"] = "Administrateur Test";
$_SESSION["username"] = "admin";
$_SESSION["is_logged_in"] = true;

header('Content-Type: application/json');

// Inclure les fichiers nécessaires
try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration: ' . $e->getMessage()]);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    
    // Test simple : compter les missions
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM missions");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test réussi avec session admin',
        'shop_id' => $_SESSION['shop_id'],
        'user_role' => $_SESSION['user_role'],
        'missions_count' => $result['total'] ?? 0,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
