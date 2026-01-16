<?php
/**
 * API Endpoint: Taches Update
 * Update task fields (status, assignee, etc.)
 */

require_once __DIR__ . '/../config.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    require_auth();
    $input = json_decode(file_get_contents('php://input'), true);
    $shop_id = $_GET['shop_id'] ?? $input['shop_id'] ?? null;
    
    if (!$shop_id) {
        throw new Exception('Shop ID manquant');
    }
    
    initialize_api_shop_context($shop_id);
    $pdo = getShopDBConnection();
    
    $id = $input['id'] ?? null;
    if (!$id) {
        throw new Exception('ID de tâche manquant');
    }
    
    // Build dynamic update query
    $allowedFields = ['statut', 'titre', 'description', 'urgence', 'technicien_id', 'date_echeance'];
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updates)) {
        throw new Exception('Aucun champ à mettre à jour');
    }
    
    // Add date_modification
    $updates[] = "date_modification = NOW()";
    
    // Add id for WHERE clause
    $params[] = $id;
    
    $sql = "UPDATE taches SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // If status changed to 'en_cours' and no date_debut, set it
    if (isset($input['statut']) && $input['statut'] === 'en_cours') {
        $stmt = $pdo->prepare("UPDATE taches SET date_debut = NOW() WHERE id = ? AND date_debut IS NULL");
        $stmt->execute([$id]);
    }
    
    // If status changed to 'terminee', set date_fin
    if (isset($input['statut']) && $input['statut'] === 'terminee') {
        $stmt = $pdo->prepare("UPDATE taches SET date_fin = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tâche mise à jour',
        'id' => $id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
