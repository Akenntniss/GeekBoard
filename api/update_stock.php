<?php
/**
 * API pour mettre à jour le stock d'un produit via le scanner
 */

// Inclure la configuration de base de données
require_once '../config/database.php';

// Initialiser la session magasin
initializeShopSession();

// Headers pour les requêtes AJAX
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

try {
    // Récupérer les données POST
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $new_quantity = isset($_POST['new_quantity']) ? intval($_POST['new_quantity']) : 0;
    $old_quantity = isset($_POST['old_quantity']) ? intval($_POST['old_quantity']) : 0;
    
    // Validation des données
    if ($product_id <= 0) {
        throw new Exception('ID du produit invalide');
    }
    
    if ($new_quantity < 0) {
        throw new Exception('La quantité ne peut pas être négative');
    }
    
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Vérifier que le produit existe - essayer d'abord table stock (structure anglaise)
    $stmt = $pdo->prepare("SELECT id, name, quantity FROM stock WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $table_used = 'stock';
    $name_field = 'name';
    $quantity_field = 'quantity';
    
    if (!$product) {
        // Si pas trouvé dans stock, essayer dans produits (structure française)
        $stmt = $pdo->prepare("SELECT id, nom, reference, quantite FROM produits WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $table_used = 'produits';
            $name_field = 'nom';
            $quantity_field = 'quantite';
            // Adapter les champs pour la réponse
            $product['name'] = $product['nom'];
            $product['quantity'] = $product['quantite'];
        }
    }
    
    if (!$product) {
        $pdo->rollBack();
        throw new Exception('Produit non trouvé');
    }
    
    // Mettre à jour la quantité dans la bonne table
    $stmt = $pdo->prepare("UPDATE {$table_used} SET {$quantity_field} = ? WHERE id = ?");
    $result = $stmt->execute([$new_quantity, $product_id]);
    
    if (!$result) {
        $pdo->rollBack();
        throw new Exception('Erreur lors de la mise à jour du stock');
    }
    
    // Enregistrer le mouvement dans l'historique (si la table existe)
    try {
        $difference = $new_quantity - $old_quantity;
        $action = $difference > 0 ? 'Ajout' : 'Retrait';
        $motif = 'Ajustement via scanner code-barres';
        
        // Vérifier si la table stock_movements existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'stock_movements'");
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            $stmt = $pdo->prepare("
                INSERT INTO stock_movements (product_id, type, quantite, motif, date_creation) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$product_id, $action, abs($difference), $motif]);
        }
    } catch (Exception $e) {
        // Continuer même si l'historique échoue
        error_log("Erreur historique stock: " . $e->getMessage());
    }
    
    // Valider la transaction
    $pdo->commit();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Stock mis à jour avec succès',
        'data' => [
            'product_id' => $product_id,
            'product_name' => $product['name'], // Utilise le champ adapté
            'product_reference' => isset($product['reference']) ? $product['reference'] : 'N/A',
            'old_quantity' => $old_quantity,
            'new_quantity' => $new_quantity,
            'difference' => $new_quantity - $old_quantity,
            'table_used' => $table_used
        ]
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Réponse d'erreur
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
