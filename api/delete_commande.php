<?php
// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Initialiser la session pour la détection du magasin
initializeShopSession();

// Récupérer la connexion à la base de données du magasin
$pdo = getShopDBConnection();

// Définir le type de contenu JSON
header('Content-Type: application/json');

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Méthode non autorisée. Utilisez POST.'
    ]);
    exit;
}

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Vérifier que les données sont valides
if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'ID de commande manquant'
    ]);
    exit;
}

$commande_id = (int)$data['id'];

// Vérifier que l'ID est valide
if ($commande_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'ID de commande invalide'
    ]);
    exit;
}

try {
    // Vérifier que la commande existe
    $check_stmt = $pdo->prepare("SELECT id FROM commandes_pieces WHERE id = ?");
    $check_stmt->execute([$commande_id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Commande non trouvée'
        ]);
        exit;
    }
    
    // Supprimer la commande
    $delete_stmt = $pdo->prepare("DELETE FROM commandes_pieces WHERE id = ?");
    $result = $delete_stmt->execute([$commande_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Commande supprimée avec succès'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la suppression de la commande'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>
