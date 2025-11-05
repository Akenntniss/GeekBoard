<?php
// API pour mettre à jour le statut d'une commande de pièce

// Inclure la configuration de session
require_once __DIR__ . '/../config/session_config.php';

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Inclure les fonctions utilitaires
require_once __DIR__ . '/../includes/functions.php';

// Définir le header JSON
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
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

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
    exit;
}

// Vérifier les paramètres requis
if (!isset($input['commande_id']) || !isset($input['new_status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants (commande_id, new_status)']);
    exit;
}

$commande_id = intval($input['commande_id']);
$new_status = $input['new_status'];

// Vérifier que l'ID est valide
if ($commande_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de commande invalide']);
    exit;
}

// Vérifier que le statut est valide
$valid_statuses = [
    'en_attente', 
    'commande', 
    'recue', 
    'utilise', 
    'annulee', 
    'a_retourner'
];

if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Statut invalide. Statuts autorisés: ' . implode(', ', $valid_statuses)
    ]);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }
    
    // Vérifier que la commande existe et appartient au bon magasin
    $check_sql = "SELECT id, statut, reference, nom_piece FROM commandes_pieces WHERE id = ?";
    $check_stmt = $shop_pdo->prepare($check_sql);
    $check_stmt->execute([$commande_id]);
    $commande = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$commande) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Commande non trouvée']);
        exit;
    }
    
    // Mettre à jour le statut
    $update_sql = "UPDATE commandes_pieces SET statut = ?, date_modification = NOW() WHERE id = ?";
    $update_stmt = $shop_pdo->prepare($update_sql);
    $result = $update_stmt->execute([$new_status, $commande_id]);
    
    if ($result) {
        // Ajouter une date spécifique selon le statut
        $date_field = null;
        switch ($new_status) {
            case 'commande':
                $date_field = 'date_commande';
                break;
            case 'recue':
                $date_field = 'date_reception';
                break;
        }
        
        // Mettre à jour la date spécifique si nécessaire
        if ($date_field) {
            $date_sql = "UPDATE commandes_pieces SET $date_field = NOW() WHERE id = ?";
            $date_stmt = $shop_pdo->prepare($date_sql);
            $date_stmt->execute([$commande_id]);
        }
        
        // Log de l'action
        error_log("Statut de commande mis à jour: ID={$commande_id}, ancien_statut={$commande['statut']}, nouveau_statut={$new_status}, user_id={$_SESSION['user_id']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Statut mis à jour avec succès',
            'data' => [
                'commande_id' => $commande_id,
                'old_status' => $commande['statut'],
                'new_status' => $new_status,
                'reference' => $commande['reference'],
                'nom_piece' => $commande['nom_piece']
            ]
        ]);
    } else {
        throw new Exception('Échec de la mise à jour du statut');
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans update_commande_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur dans update_commande_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
