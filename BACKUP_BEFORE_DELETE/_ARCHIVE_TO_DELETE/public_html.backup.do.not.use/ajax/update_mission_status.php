<?php
// Fichier AJAX pour mettre à jour le statut des missions
header('Content-Type: application/json');

// Inclure les fichiers nécessaires
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['mission_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$mission_id = (int) $input['mission_id'];
$new_status = trim($input['status']);

// Valider le statut
$valid_statuses = ['nouvelle', 'en_cours', 'en_attente', 'terminee'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    
    // Vérifier que la mission existe et appartient à l'utilisateur
    $sql_check = "SELECT id FROM user_missions WHERE id = ? AND assignee_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$mission_id, $_SESSION['user_id']]);
    
    if (!$stmt_check->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Mission introuvable']);
        exit;
    }
    
    // Mettre à jour le statut
    $sql_update = "UPDATE user_missions SET 
                   statut = ?, 
                   updated_at = CURRENT_TIMESTAMP";
    
    // Si la mission est terminée, définir la date de completion
    if ($new_status === 'terminee') {
        $sql_update .= ", date_completee = CURRENT_TIMESTAMP";
    }
    
    // Si la mission est démarrée, définir la date de début
    if ($new_status === 'en_cours') {
        $sql_update .= ", date_rejointe = CURRENT_TIMESTAMP";
    }
    
    $sql_update .= " WHERE id = ?";
    
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$new_status, $mission_id]);
    
    if ($stmt_update->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucune modification effectuée']);
    }
    
} catch (Exception $e) {
    error_log("Erreur mise à jour statut mission: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?> 