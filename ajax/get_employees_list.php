<?php
/**
 * API pour récupérer la liste des employés
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Initialiser la session shop si nécessaire
initializeShopSession();

try {
    // Récupérer la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception("Connexion base de données échouée");
    }
    
    // Récupérer tous les utilisateurs (admin + techniciens + vendeurs)
    $stmt = $pdo->prepare("
        SELECT 
            id,
            full_name,
            role,
            username
        FROM users 
        WHERE role IN ('admin', 'technicien', 'vendeur')
        AND full_name IS NOT NULL
        AND full_name != ''
        ORDER BY full_name ASC
    ");
    
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $employees,
        'count' => count($employees)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erreur get_employees_list: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des employés',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
