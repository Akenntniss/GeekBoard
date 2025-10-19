<?php
// Test simple de l'API améliorée
header('Content-Type: application/json');

try {
    // Connexion directe pour test
    $pdo = new PDO("mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4", 'root', 'Mamanmaman01#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Test simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM time_tracking");
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'API test réussie',
        'data' => [
            'total_entries' => $result['count'],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
