<?php
/**
 * API pour mettre à jour l'abonnement aux notifications push
 */

// Utiliser la configuration de session GeekBoard
require_once __DIR__ . '/../config/session_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté', 'debug' => session_id()]);
    exit;
}

// Inclure les fichiers nécessaires
require_once '../config/database.php';

// Récupérer les données envoyées
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['endpoint']) || !isset($data['keys'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Connexion à la base de données via la fonction centrale
    require_once __DIR__ . '/../includes/functions.php';
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        error_log("PUSH SUBSCRIPTION ERROR: database connection failed");
        throw new Exception("Database connection failed");
    }

    // Vérifier si un enregistrement existe déjà pour cet endpoint
    $stmt = $shop_pdo->prepare("
        SELECT id FROM push_subscriptions 
        WHERE endpoint = ? AND user_id = ?
    ");
    $stmt->execute([$data['endpoint'], $_SESSION['user_id']]);
    $existingSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingSubscription) {
        // Mettre à jour l'abonnement existant
        $stmt = $shop_pdo->prepare("
            UPDATE push_subscriptions 
            SET auth_key = ?, p256dh_key = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([
            $data['keys']['auth'],
            $data['keys']['p256dh'],
            $existingSubscription['id']
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Abonnement mis à jour avec succès',
            'subscription_id' => $existingSubscription['id']
        ]);
    } else {
        // Créer un nouvel abonnement
        $stmt = $shop_pdo->prepare("
            INSERT INTO push_subscriptions 
            (user_id, endpoint, auth_key, p256dh_key, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['endpoint'],
            $data['keys']['auth'],
            $data['keys']['p256dh']
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Nouvel abonnement créé avec succès',
            'subscription_id' => $shop_pdo->lastInsertId()
        ]);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    exit;
} 