<?php
/**
 * Endpoint AJAX pour supprimer un catalogue ou des produits du catalogue
 */

header('Content-Type: application/json');

require_once '../config/session_config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['fournisseur_id'])) {
        // Supprimer tout le catalogue d'un fournisseur
        $fournisseur_id = intval($data['fournisseur_id']);
        
        $stmt = $shop_pdo->prepare("DELETE FROM catalogue_fournisseur WHERE fournisseur_id = :fournisseur_id");
        $stmt->execute(['fournisseur_id' => $fournisseur_id]);
        
        $deleted = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => "$deleted produits supprimés",
            'deleted' => $deleted
        ]);
        
    } elseif (isset($data['product_id'])) {
        // Supprimer un produit spécifique
        $product_id = intval($data['product_id']);
        
        $stmt = $shop_pdo->prepare("DELETE FROM catalogue_fournisseur WHERE id = :id");
        $stmt->execute(['id' => $product_id]);
        
        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Produit supprimé' : 'Produit non trouvé'
        ]);
        
    } else {
        throw new Exception('Paramètres manquants');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
