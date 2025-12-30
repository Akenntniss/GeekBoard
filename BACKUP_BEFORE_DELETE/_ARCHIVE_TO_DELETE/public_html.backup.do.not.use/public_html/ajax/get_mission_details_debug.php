<?php
header('Content-Type: application/json');

// Démarrer la session si pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug des informations de session
$debug_info = [
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
    'session_id' => session_id(),
    'user_role' => $_SESSION['user_role'] ?? 'NON DÉFINI',
    'user_id' => $_SESSION['user_id'] ?? 'NON DÉFINI',
    'shop_id' => $_SESSION['shop_id'] ?? 'NON DÉFINI',
    'is_logged_in' => $_SESSION['is_logged_in'] ?? 'NON DÉFINI',
    'all_session_keys' => array_keys($_SESSION),
    'get_params' => $_GET
];

// Vérifier les paramètres
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'ID de mission invalide',
        'debug' => $debug_info
    ]);
    exit;
}

$mission_id = (int)$_GET['id'];

// Vérifier l'authentification avec debug
if (!isset($_SESSION['user_role'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'user_role non défini dans la session',
        'debug' => $debug_info
    ]);
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode([
        'success' => false, 
        'message' => 'user_role = "' . $_SESSION['user_role'] . '" (attendu: "admin")',
        'debug' => $debug_info
    ]);
    exit;
}

// Inclure les fichiers nécessaires avec gestion d'erreur
try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de configuration: ' . $e->getMessage(),
        'debug' => $debug_info
    ]);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur de connexion à la base de données',
            'debug' => $debug_info
        ]);
        exit;
    }
    
    // Test simple : récupérer la mission
    $stmt = $shop_pdo->prepare("SELECT id, titre, description FROM missions WHERE id = ?");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        echo json_encode([
            'success' => false, 
            'message' => 'Mission non trouvée (ID: ' . $mission_id . ')',
            'debug' => $debug_info
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Mission trouvée avec succès',
        'mission' => $mission,
        'debug' => $debug_info
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage(),
        'debug' => $debug_info
    ]);
}
?>
