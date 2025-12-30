<?php
/**
 * API pour supprimer l'abonnement aux notifications push
 */

// Démarrer ou reprendre une session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Inclure les fichiers nécessaires
require_once '../config/database.php';

// Récupérer les données envoyées
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['endpoint'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Connexion à la base de données via la fonction centrale
    require_once __DIR__ . '/../includes/functions.php';
    $shop_pdo = getShopDBConnection();

    if (!$shop_pdo) {
        throw new Exception("Database connection failed");
    }

    // Supprimer l'abonnement
    $stmt = $shop_pdo->prepare("
        DELETE FROM push_subscriptions 
        WHERE endpoint = ? AND user_id = ?
    ");
    $result = $stmt->execute([$data['endpoint'], $_SESSION['user_id']]);

    if ($result && $stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Abonnement supprimé avec succès']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Aucun abonnement trouvé à supprimer']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    exit;
} 