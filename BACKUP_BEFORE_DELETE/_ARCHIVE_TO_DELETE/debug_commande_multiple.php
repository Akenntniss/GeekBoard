<?php
session_start();
header('Content-Type: application/json');

// Initialiser la connexion à la base de données
require_once __DIR__ . '/public_html/config/database.php';

// Simuler une session pour zebilamcj
$_SESSION['shop_id'] = 150; // ID pour zebilamcj

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_data' => $_SESSION ?? [],
    'post_data' => $_POST,
    'get_data' => $_GET,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
];

error_log("DEBUG COMMANDE MULTIPLE: " . json_encode($debug));

try {
    // Utiliser la fonction système pour obtenir la connexion
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base du magasin', 'debug' => $debug]);
        exit;
    }
    
    // Vérifier quelle base nous utilisons
    try {
        $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        $debug['current_database'] = $db_info['current_db'] ?? 'Inconnue';
    } catch (Exception $e) {
        $debug['db_check_error'] = $e->getMessage();
    }
    
    // Vérifier les dernières commandes créées
    try {
        $stmt = $shop_pdo->query("SELECT id, reference, nom_piece, date_creation FROM commandes_pieces ORDER BY date_creation DESC LIMIT 10");
        $recent_commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug['recent_commands'] = $recent_commands;
    } catch (Exception $e) {
        $debug['recent_commands_error'] = $e->getMessage();
    }
    
    // Compter les commandes créées aujourd'hui
    try {
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM commandes_pieces WHERE DATE(date_creation) = CURDATE()");
        $today_count = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug['commands_today'] = $today_count['count'] ?? 0;
    } catch (Exception $e) {
        $debug['count_error'] = $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Debug terminé',
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    $debug['exception'] = $e->getMessage();
    $debug['exception_trace'] = $e->getTraceAsString();
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage(), 'debug' => $debug]);
}
?>
