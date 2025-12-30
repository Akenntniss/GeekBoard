<?php
// Démarrer la session pour avoir accès à l'ID du magasin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// S'assurer que nous envoyons du JSON
header('Content-Type: application/json');

require_once('../config/database.php');

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Vérifier si la connexion à la base de données est établie
if (!isset($shop_pdo) || $shop_pdo === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Récupérer les données POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'error' => 'Données manquantes'
    ]);
    exit;
}

$repair_id = $input['repair_id'] ?? null;
$notes = $input['notes'] ?? '';

if (!$repair_id) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de réparation manquant'
    ]);
    exit;
}

try {
    // Mettre à jour les notes techniques
    $sql = "UPDATE reparations SET notes_techniques = ? WHERE id = ?";
    $stmt = $shop_pdo->prepare($sql);
    $result = $stmt->execute([$notes, $repair_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Notes techniques mises à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour des notes techniques: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données'
    ]);
}
?>