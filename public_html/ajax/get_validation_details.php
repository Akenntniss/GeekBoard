<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Connexion directe à la base de données
try {
    $dsn = "mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4";
    $username = "root";
    $password = "Mamanmaman01#";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Récupérer l'ID de la validation
$validation_id = $_GET['id'] ?? null;

if (!$validation_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de validation manquant']);
    exit();
}

try {
    // Requête pour récupérer les détails complets de la validation
    $sql = "SELECT 
                v.id,
                v.user_mission_id,
                v.tache_numero,
                v.description,
                v.preuve_fichier,
                v.statut,
                v.commentaire_admin,
                v.date_soumission,
                v.date_validation,
                v.photo_url,
                u.full_name as user_full_name,
                u.username as user_username,
                m.titre as mission_titre,
                m.description as mission_description,
                admin.full_name as admin_full_name,
                admin.username as admin_username
            FROM mission_validations v
            LEFT JOIN user_missions um ON v.user_mission_id = um.id
            LEFT JOIN users u ON um.user_id = u.id
            LEFT JOIN missions m ON um.mission_id = m.id
            LEFT JOIN users admin ON v.validee_par = admin.id
            WHERE v.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$validation_id]);
    $validation = $stmt->fetch();
    
    if (!$validation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Validation non trouvée']);
        exit();
    }
    
    // Vérifier si les fichiers existent
    $photo_exists = false;
    $preuve_exists = false;
    
    if ($validation['photo_url']) {
        $photo_path = __DIR__ . '/../' . $validation['photo_url'];
        $photo_exists = file_exists($photo_path);
    }
    
    if ($validation['preuve_fichier']) {
        $preuve_path = __DIR__ . '/../' . $validation['preuve_fichier'];
        $preuve_exists = file_exists($preuve_path);
    }
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'validation' => [
            'id' => $validation['id'],
            'user_mission_id' => $validation['user_mission_id'],
            'tache_numero' => $validation['tache_numero'],
            'description' => $validation['description'],
            'preuve_fichier' => $validation['preuve_fichier'],
            'statut' => $validation['statut'],
            'commentaire_admin' => $validation['commentaire_admin'],
            'date_soumission' => $validation['date_soumission'],
            'date_validation' => $validation['date_validation'],
            'photo_url' => $validation['photo_url'],
            'photo_exists' => $photo_exists,
            'preuve_exists' => $preuve_exists,
            'user_full_name' => $validation['user_full_name'],
            'user_username' => $validation['user_username'],
            'mission_titre' => $validation['mission_titre'],
            'mission_description' => $validation['mission_description'],
            'admin_full_name' => $validation['admin_full_name'],
            'admin_username' => $validation['admin_username']
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des détails: ' . $e->getMessage()]);
}
?> 