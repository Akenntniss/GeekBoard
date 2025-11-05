<?php
// Configuration et session sécurisée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Permettre l'accès même sans authentification pour le debug
// if (!isset($_SESSION['user_id'])) {
//     header('Content-Type: application/json');
//     echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
//     exit;
// }

// Récupérer les données POST
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validation
if ($task_id <= 0 || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Valider le statut
$valid_statuses = ['a_faire', 'en_cours', 'termine'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    // Fallback : connexion directe si getShopDBConnection échoue
    if (!$shop_pdo) {
        try {
            $shop_pdo = new PDO(
                "mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4",
                "root",
                "Mamanmaman01#",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Connexion directe à la base de données échouée: ' . $e->getMessage());
        }
    }
    
    if (!$shop_pdo) {
        throw new Exception('Aucune connexion à la base de données disponible');
    }
    
    // Utiliser un user_id par défaut si pas de session
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    
    // Mettre à jour le statut
    $stmt = $shop_pdo->prepare("
        UPDATE taches 
        SET statut = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$status, $task_id]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour');
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour du statut: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du statut'
    ]);
}
?>
