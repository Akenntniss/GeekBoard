<?php
// ajax/delete_client.php
require_once '../config/database.php';
require_once '../config/session_config.php';

// Initialiser la session
initializeShopSession();

// Définir le type de contenu JSON
header('Content-Type: application/json');

try {
    // Vérifier que l'utilisateur est authentifié
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Non authentifié'
        ]);
        exit;
    }
    
    // Récupérer l'ID du client à supprimer
    $client_id = $_POST['client_id'] ?? null;
    
    if (empty($client_id) || !is_numeric($client_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID client invalide'
        ]);
        exit;
    }
    
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Vérifier si le client a des réparations
    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reparations WHERE client_id = ?");
    $check_stmt->execute([$client_id]);
    $repair_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($repair_count > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Ce client a $repair_count réparation(s) associée(s). Impossible de le supprimer."
        ]);
        exit;
    }
    
    // Supprimer le client
    $delete_stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $delete_stmt->execute([$client_id]);
    
    // Vérifier que la suppression a réussi
    if ($delete_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Client supprimé avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Client non trouvé'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur dans delete_client.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
