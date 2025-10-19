<?php
/**
 * API pour récupérer la liste des utilisateurs
 * Utilisé par le modal d'ajout de tâches
 */

// Configuration de l'en-tête JSON dès le début
header('Content-Type: application/json');

// Force le démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    // Session ID explicite (via cookie ou URL)
    if (isset($_COOKIE['PHPSESSID'])) {
        session_id($_COOKIE['PHPSESSID']);
    } elseif (isset($_GET['sid'])) {
        session_id($_GET['sid']);
    }
    
    // Configuration de la session pour éviter les problèmes
    ini_set('session.use_only_cookies', 0);
    ini_set('session.use_cookies', 1);
    ini_set('session.use_trans_sid', 1);
    ini_set('session.cache_limiter', 'private');
    
    // Augmenter la durée de la session
    ini_set('session.gc_maxlifetime', 86400); // 24 heures
    
    // Démarrer la session
    session_start();
}

// Inclusion des fichiers nécessaires
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Initialiser la session shop si elle existe
if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

// S'assurer que le shop_id est disponible (depuis session ou GET)
if (!isset($_SESSION['shop_id']) && isset($_GET['shop_id'])) {
    $_SESSION['shop_id'] = $_GET['shop_id'];
    error_log("Get Users - Shop ID défini en session depuis GET: " . $_GET['shop_id']);
}

// Log pour débogage
error_log("Get Users - Session ID: " . session_id());
error_log("Get Users - Session content: " . print_r($_SESSION, true));

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action.',
        'debug' => [
            'session_id' => session_id(),
            'session_keys' => array_keys($_SESSION),
            'has_user_id' => isset($_SESSION['user_id']),
            'user_id_value' => $_SESSION['user_id'] ?? null
        ]
    ]);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer tous les utilisateurs actifs
    $stmt = $shop_pdo->query("
        SELECT id, full_name, role, email 
        FROM users 
        WHERE status = 'active' OR status IS NULL
        ORDER BY role DESC, full_name ASC
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage()
    ]);
}
?>