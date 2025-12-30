<?php
// ajax/update_repair_description.php

require_once '../config/database.php';
require_once '../config/session_config.php';

// Initialiser la session
initializeShopSession();

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Données JSON invalides']);
    exit;
}

$repair_id = filter_var($input['repair_id'] ?? null, FILTER_VALIDATE_INT);
$description = trim($input['description'] ?? '');

// Validation
if (!$repair_id) {
    echo json_encode(['success' => false, 'error' => 'ID de réparation invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Mettre à jour la description du problème
    $stmt = $pdo->prepare("UPDATE reparations SET description_probleme = ? WHERE id = ?");
    $result = $stmt->execute([$description, $repair_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Description du problème mise à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour de la description'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour de la description: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>

