<?php
/**
 * Mise à jour du prix d'une réparation
 * Endpoint AJAX pour modifier le prix d'une réparation
 */

// Définir l'en-tête JSON
header('Content-Type: application/json');

// Tenter d'inclure la configuration de session d'abord
$session_config_path = realpath(__DIR__ . '/../config/session_config.php');
if (file_exists($session_config_path)) {
    require_once($session_config_path);
    // session_start() est déjà appelé dans session_config.php
} else {
    // Démarrer la session si nécessaire
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Inclure la configuration de base de données
require_once '../config/database.php';

// Debug: Log des informations de session
error_log("DEBUG Prix - Session ID: " . session_id());
error_log("DEBUG Prix - User ID: " . ($_SESSION['user_id'] ?? 'non défini'));
error_log("DEBUG Prix - Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini'));
error_log("DEBUG Prix - POST data: " . print_r($_POST, true));

// Initialiser la session magasin
initializeShopSession();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("DEBUG Prix - Erreur: Utilisateur non connecté");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé - utilisateur non connecté']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données POST
$repair_id = filter_input(INPUT_POST, 'repair_id', FILTER_VALIDATE_INT);
$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$shop_id = filter_input(INPUT_POST, 'shop_id', FILTER_SANITIZE_STRING);

if (!$repair_id || $price === false || $price < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }
    
    // Vérifier que la réparation existe et appartient au magasin
    $checkStmt = $pdo->prepare("SELECT id FROM reparations WHERE id = ?");
    $checkStmt->execute([$repair_id]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Réparation non trouvée']);
        exit;
    }
    
    // Récupérer l'ancien prix avant la mise à jour pour le log
    $oldPriceStmt = $pdo->prepare("SELECT prix_reparation FROM reparations WHERE id = ?");
    $oldPriceStmt->execute([$repair_id]);
    $oldPrice = $oldPriceStmt->fetchColumn();
    
    // Mettre à jour le prix de la réparation
    $updateStmt = $pdo->prepare("
        UPDATE reparations 
        SET prix_reparation = ?, 
            date_modification = NOW() 
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([$price, $repair_id]);
    
    if ($result) {
        // Log de l'action
        error_log("Prix mis à jour pour la réparation {$repair_id}: {$price}€ par l'utilisateur {$_SESSION['user_id']}");
        
        // Enregistrer dans l'historique (reparation_logs)
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO reparation_logs (reparation_id, employe_id, action_type, details, date_action) 
                VALUES (?, ?, 'modification_prix', ?, NOW())
            ");
            
            $oldPriceFormatted = number_format((float)$oldPrice, 2);
            $newPriceFormatted = number_format((float)$price, 2);
            $details = "Modification du prix : {$oldPriceFormatted}€ -> {$newPriceFormatted}€";
            
            $logStmt->execute([$repair_id, $_SESSION['user_id'], $details]);
            
        } catch (Exception $e) {
            error_log("Erreur lors du log de prix dans la BDD: " . $e->getMessage());
            // On continue même si le log échoue
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Prix mis à jour avec succès',
            'new_price' => number_format($price, 2) . ' €'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour du prix');
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour du prix: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>