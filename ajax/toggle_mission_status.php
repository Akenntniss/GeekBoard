<?php
session_start();

// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Initialiser une session si pas active (pour test)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
}

// S'assurer que la session shop est initialisée
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 63; // mkmkmk
}

header('Content-Type: application/json');

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Vérifier les permissions
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Accès non autorisé');
    }

    $mission_id = $_POST['mission_id'] ?? null;
    $new_status = $_POST['status'] ?? null;

    if (!$mission_id || !$new_status) {
        throw new Exception('Paramètres manquants');
    }

    // Valider le statut
    if (!in_array($new_status, ['active', 'inactive'])) {
        throw new Exception('Statut invalide');
    }

    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();

    // Vérifier que la mission existe
    $stmt = $shop_pdo->prepare("SELECT id, titre, statut FROM missions WHERE id = ?");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mission) {
        throw new Exception('Mission non trouvée');
    }

    // Mettre à jour le statut
    $stmt = $shop_pdo->prepare("UPDATE missions SET statut = ? WHERE id = ?");
    $stmt->execute([$new_status, $mission_id]);

    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Statut de la mission mis à jour avec succès',
        'data' => [
            'mission_id' => $mission_id,
            'old_status' => $mission['statut'],
            'new_status' => $new_status,
            'mission_title' => $mission['titre']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors du changement de statut de mission: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors du changement de statut de mission: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>

