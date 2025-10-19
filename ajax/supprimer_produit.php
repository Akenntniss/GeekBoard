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

    // Connexion directe (cohérente avec les autres endpoints ajax simples)
    $pdo = new PDO('mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8', 'root', 'Mamanmaman01#');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Supprimer le produit
    $stmt = $pdo->prepare('DELETE FROM produits WHERE id = ?');
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


