<?php
// ajax/update_repair_internal_notes.php

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
$notes = trim($input['notes'] ?? '');

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
    
    // Mettre à jour les notes internes (notes_finales)
    $stmt = $pdo->prepare("UPDATE reparations SET notes_finales = ? WHERE id = ?");
    $result = $stmt->execute([$notes, $repair_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Notes internes mises à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour des notes internes'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour des notes internes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>

