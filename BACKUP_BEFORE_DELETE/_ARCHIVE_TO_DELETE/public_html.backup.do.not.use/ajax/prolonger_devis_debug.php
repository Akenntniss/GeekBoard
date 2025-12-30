<?php
// Version debug du script prolonger_devis.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log de démarrage
error_log("=== DEBUT PROLONGER DEVIS DEBUG ===");

try {
    // Démarrer la session si nécessaire
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    error_log("Session démarrée - ID: " . session_id());

    // Définir le type de contenu comme JSON
    header('Content-Type: application/json');
    error_log("Headers définis");

    // Inclure les fichiers nécessaires
    $config_path = '../config/database.php';
    $functions_path = '../includes/functions.php';
    
    error_log("Vérification des fichiers:");
    error_log("Config exists: " . (file_exists($config_path) ? 'YES' : 'NO'));
    error_log("Functions exists: " . (file_exists($functions_path) ? 'YES' : 'NO'));
    
    if (!file_exists($config_path)) {
        throw new Exception('Config file not found: ' . $config_path);
    }
    
    if (!file_exists($functions_path)) {
        throw new Exception('Functions file not found: ' . $functions_path);
    }
    
    require_once($config_path);
    error_log("Config inclus");
    
    require_once($functions_path);
    error_log("Functions inclus");

    // Vérifier l'authentification
    error_log("Session data: " . json_encode($_SESSION));
    
    $is_authenticated = false;
    $user_id = null;

    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['user_id'];
        error_log("Auth via user_id: " . $user_id);
    } elseif (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['shop_id'];
        error_log("Auth via shop_id: " . $user_id);
    } elseif (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['admin_id'];
        error_log("Auth via admin_id: " . $user_id);
    }

    if (!$is_authenticated) {
        error_log("Authentification échouée");
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non authentifié']);
        exit;
    }
    
    error_log("Authentification réussie avec user_id: " . $user_id);

    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Méthode HTTP incorrecte: " . $_SERVER['REQUEST_METHOD']);
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        exit;
    }
    
    error_log("Méthode POST confirmée");

    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    error_log("Input reçu: " . $input);
    
    if (!$input) {
        error_log("Aucune donnée reçue");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }

    $data = json_decode($input, true);
    if (!$data) {
        error_log("JSON invalide: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }
    
    error_log("JSON décodé: " . json_encode($data));

    $devis_id = isset($data['devis_id']) ? (int)$data['devis_id'] : 0;
    $duree_jours = isset($data['duree_jours']) ? (int)$data['duree_jours'] : 0;

    error_log("Devis ID: " . $devis_id . ", Durée: " . $duree_jours);

    // Validation des données
    if ($devis_id <= 0) {
        error_log("ID devis invalide");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de devis invalide']);
        exit;
    }

    if ($duree_jours <= 0 || $duree_jours > 365) {
        error_log("Durée invalide");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Durée invalide (entre 1 et 365 jours)']);
        exit;
    }

    // Obtenir la connexion à la base de données du shop
    error_log("Tentative de connexion à la base de données");
    
    if (!function_exists('getShopDBConnection')) {
        throw new Exception('Fonction getShopDBConnection non trouvée');
    }
    
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du shop');
    }
    
    error_log("Connexion base de données réussie");

    // Test simple
    $test_stmt = $shop_pdo->query("SELECT 1 as test");
    $test_result = $test_stmt->fetch();
    error_log("Test requête: " . json_encode($test_result));
    
    // Réponse de test
    echo json_encode([
        'success' => true,
        'message' => 'Script de debug fonctionnel',
        'debug_info' => [
            'devis_id' => $devis_id,
            'duree_jours' => $duree_jours,
            'user_id' => $user_id,
            'db_test' => $test_result
        ]
    ]);
    
    error_log("=== FIN PROLONGER DEVIS DEBUG SUCCESS ===");
    
} catch (Exception $e) {
    error_log("ERREUR: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur serveur: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    error_log("=== FIN PROLONGER DEVIS DEBUG ERROR ===");
}
?>
