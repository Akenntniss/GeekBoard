<?php
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        throw new Exception('ID produit invalide');
    }

    // Connexion à la base de données du magasin via système multi-magasin
    require_once __DIR__ . '/../config/database.php';
    
    // Démarrer la session si pas déjà fait
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Initialiser la session magasin
    initializeShopSession();
    
    // Obtenir la connexion à la base du magasin actuel
    $pdo = getShopDBConnection();
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base du magasin');
    }

    // Supprimer le produit
    $stmt = $pdo->prepare('DELETE FROM produits WHERE id = ?');
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


