<?php
/**
 * API REST v2 - Authentification Login
 * GeekBoard Desktop Application
 * 
 * POST /api/v2/auth/login
 * Body: { "subdomain": "mdg", "email": "user@test.fr", "password": "xxx" }
 */

require_once __DIR__ . '/../config.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Méthode non autorisée', 405);
}

// Récupérer les données
$input = get_json_input();

$subdomain = trim($input['subdomain'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// Validation des champs
if (empty($subdomain)) {
    error_response('Le sous-domaine est requis');
}

if (empty($email)) {
    error_response('L\'email est requis');
}

if (empty($password)) {
    error_response('Le mot de passe est requis');
}

try {
    global $subdomain_detector;
    
    // Récupérer les informations du magasin
    $shop_config = $subdomain_detector->getDatabaseConfig($subdomain);
    $shop_info = null;
    
    // Chercher le magasin dans la base principale
    $main_dsn = "mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4";
    $main_pdo = new PDO($main_dsn, 'root', 'Mamanmaman01#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
    $stmt->execute([$subdomain]);
    $shop_info = $stmt->fetch();
    
    if (!$shop_info) {
        error_response('Magasin non trouvé ou inactif', 404);
    }
    
    // Connexion à la base du magasin
    $shop_pdo = $subdomain_detector->getConnection($subdomain);
    
    if (!$shop_pdo) {
        error_response('Impossible de se connecter à la base du magasin', 500);
    }
    
    // Chercher l'utilisateur
    $stmt = $shop_pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        error_response('Email ou mot de passe incorrect', 401);
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $user['password'])) {
        error_response('Email ou mot de passe incorrect', 401);
    }
    
    // Créer le token JWT
    $payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'nom' => $user['nom'] ?? '',
        'prenom' => $user['prenom'] ?? '',
        'role' => $user['role'] ?? 'user',
        'shop_id' => $shop_info['id'],
        'shop_name' => $shop_info['name'],
        'subdomain' => $subdomain,
        'db_name' => $shop_config['dbname']
    ];
    
    $token = jwt_encode($payload);
    
    // Réponse de succès
    success_response([
        'token' => $token,
        'expires_in' => JWT_EXPIRATION,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'nom' => $user['nom'] ?? '',
            'prenom' => $user['prenom'] ?? '',
            'role' => $user['role'] ?? 'user'
        ],
        'shop' => [
            'id' => $shop_info['id'],
            'name' => $shop_info['name'],
            'subdomain' => $subdomain
        ]
    ], 'Connexion réussie');
    
} catch (PDOException $e) {
    error_log("API v2 Login Error: " . $e->getMessage());
    error_response('Erreur de connexion à la base de données', 500);
} catch (Exception $e) {
    error_log("API v2 Login Error: " . $e->getMessage());
    error_response('Erreur interne du serveur', 500);
}
?>
