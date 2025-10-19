<?php
// API pour la mise à jour en lot des commandes de pièces

// Inclure la configuration de session
require_once __DIR__ . '/../config/session_config.php';

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// La détection du magasin est gérée automatiquement par getShopDBConnection()

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// La vérification du shop_id est gérée automatiquement par getShopDBConnection()

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
if (!isset($input['commande_ids']) || !isset($input['new_status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$commande_ids = $input['commande_ids'];
$new_status = $input['new_status'];

// Vérifier que les IDs sont fournis
if (!is_array($commande_ids) || empty($commande_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Liste des IDs de commandes invalide']);
    exit;
}

// Vérifier que le statut est valide
$valid_statuses = ['en_attente', 'commande', 'recue', 'installee', 'a_retourner'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Préparer la requête de mise à jour
    $placeholders = str_repeat('?,', count($commande_ids) - 1) . '?';
    $sql = "UPDATE commandes_pieces SET statut = ? WHERE id IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    
    // Préparer les paramètres (nouveau statut + tous les IDs)
    $params = array_merge([$new_status], $commande_ids);
    
    // Exécuter la mise à jour
    $result = $stmt->execute($params);
    
    if ($result) {
        $affected_rows = $stmt->rowCount();
        
        // Valider la transaction
        $pdo->commit();
        
        // Log de l'action
        $shop_id = $_SESSION['shop_id'] ?? 'inconnu';
        error_log("Mise à jour en lot effectuée par l'utilisateur {$_SESSION['user_id']} pour le magasin {$shop_id}: {$affected_rows} commandes mises à jour vers le statut '{$new_status}'");
        
        echo json_encode([
            'success' => true,
            'message' => "Mise à jour réussie",
            'affected_rows' => $affected_rows,
            'new_status' => $new_status
        ]);
    } else {
        // Annuler la transaction
        $pdo->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour des commandes'
        ]);
    }
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Erreur lors de la mise à jour en lot des commandes: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur générale lors de la mise à jour en lot: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne: ' . $e->getMessage()
    ]);
}
?>