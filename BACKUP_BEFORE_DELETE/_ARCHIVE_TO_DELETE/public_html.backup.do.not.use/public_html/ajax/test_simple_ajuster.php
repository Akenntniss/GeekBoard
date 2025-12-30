<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    session_start();
    
    // Test basique sans inclusion
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les données POST
    $produit_id = intval($_POST['produit_id'] ?? 0);
    $nouvelle_quantite = intval($_POST['nouvelle_quantite'] ?? 0);
    
    if ($produit_id <= 0) {
        throw new Exception('ID produit manquant');
    }
    
    if ($nouvelle_quantite < 0) {
        throw new Exception('Quantité invalide');
    }
    
    // Simuler une réponse réussie
    echo json_encode([
        'success' => true,
        'message' => 'Test réussi - données reçues',
        'data' => [
            'produit_id' => $produit_id,
            'nouvelle_quantite' => $nouvelle_quantite,
            'session' => isset($_SESSION['user_id']) ? 'OK' : 'NO_SESSION'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
