<?php
// Création d'une nouvelle catégorie pour la base de connaissances
header('Content-Type: application/json');

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/subdomain_config.php';

// Pas d'authentification - accès libre pour simplifier

try {
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || empty(trim($input['name']))) {
        throw new Exception('Le nom de la catégorie est requis');
    }
    
    $categoryName = trim($input['name']);
    
    // Vérifier que la catégorie n'existe pas déjà
    $shop_pdo = getShopDBConnection();
    $checkQuery = "SELECT id FROM kb_categories WHERE name = ?";
    $checkStmt = $shop_pdo->prepare($checkQuery);
    $checkStmt->execute([$categoryName]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Une catégorie avec ce nom existe déjà');
    }
    
    // Créer la nouvelle catégorie
    $query = "INSERT INTO kb_categories (name, created_at) VALUES (?, NOW())";
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([$categoryName]);
    
    $categoryId = $shop_pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $categoryId,
        'name' => $categoryName,
        'message' => 'Catégorie créée avec succès'
    ]);

} catch (Exception $e) {
    error_log("Erreur création catégorie KB: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
