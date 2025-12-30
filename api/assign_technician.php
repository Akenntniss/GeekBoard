<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers pour JSON et CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Inclure la configuration de base de données
    require_once '../config/database.php';
    
    // Vérifier l'accès et initialiser la session si nécessaire
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Vérifier que l'utilisateur est connecté
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['shop_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez vous reconnecter.']);
        exit;
    }
    
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }
    
    // Récupérer et valider les données
    $repair_id = $_POST['repair_id'] ?? null;
    $employe_id = $_POST['employe_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    // Validation des données
    if ($action !== 'assign_technician') {
        throw new Exception('Action non valide');
    }
    
    if (!$repair_id || !is_numeric($repair_id)) {
        throw new Exception('ID de réparation invalide');
    }
    
    // L'employe_id peut être vide (pour retirer l'attribution)
    if ($employe_id !== '' && (!is_numeric($employe_id) || $employe_id <= 0)) {
        throw new Exception('ID d\'employé invalide');
    }
    
    // Conversion en entiers
    $repair_id = (int)$repair_id;
    $employe_id = $employe_id === '' ? null : (int)$employe_id;
    
    // Vérifier que la réparation existe
    $check_stmt = $shop_pdo->prepare("SELECT id, appareil, client_nom FROM reparations WHERE id = ?");
    $check_stmt->execute([$repair_id]);
    $repair = $check_stmt->fetch();
    
    if (!$repair) {
        throw new Exception('Réparation non trouvée');
    }
    
    // Si un employé est spécifié, vérifier qu'il existe et qu'il a le bon rôle
    if ($employe_id !== null) {
        $emp_stmt = $shop_pdo->prepare("SELECT id, full_name, role FROM users WHERE id = ? AND role IN ('technicien', 'admin')");
        $emp_stmt->execute([$employe_id]);
        $employee = $emp_stmt->fetch();
        
        if (!$employee) {
            throw new Exception('Technicien non trouvé ou non disponible');
        }
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    try {
        // Mettre à jour l'attribution dans la table reparations
        $update_stmt = $shop_pdo->prepare("UPDATE reparations SET employe_id = ? WHERE id = ?");
        $update_result = $update_stmt->execute([$employe_id, $repair_id]);
        
        if (!$update_result) {
            throw new Exception('Erreur lors de la mise à jour de l\'attribution');
        }
        
        // Ajouter un log dans reparation_logs
        $log_message = $employe_id !== null 
            ? "Attribution à {$employee['full_name']}" 
            : "Suppression de l'attribution";
            
        $log_stmt = $shop_pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
            VALUES (?, ?, ?, NULL, NULL, ?)
        ");
        
        $log_stmt->execute([
            $repair_id,
            $_SESSION['user_id'], // L'utilisateur qui fait l'attribution
            'attribution_technicien',
            $log_message
        ]);
        
        // Valider la transaction
        $shop_pdo->commit();
        
        // Préparer la réponse de succès
        $success_message = $employe_id !== null 
            ? "Réparation #{$repair_id} attribuée à {$employee['full_name']}"
            : "Attribution de la réparation #{$repair_id} supprimée";
        
        echo json_encode([
            'success' => true,
            'message' => $success_message,
            'repair_id' => $repair_id,
            'employe_id' => $employe_id,
            'employee_name' => $employe_id !== null ? $employee['full_name'] : null
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $shop_pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log de l'erreur pour le débogage
    error_log("Erreur attribution technicien: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
