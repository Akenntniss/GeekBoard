<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$partner_id = filter_input(INPUT_POST, 'partner_id', FILTER_VALIDATE_INT);
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity_used = filter_input(INPUT_POST, 'quantity_used', FILTER_VALIDATE_INT);
$unit_price = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
$original_quantity = filter_input(INPUT_POST, 'original_quantity', FILTER_VALIDATE_INT);
$new_quantity = filter_input(INPUT_POST, 'new_quantity', FILTER_VALIDATE_INT);

if ($partner_id === false || $product_id === false || $quantity_used === false || 
    $unit_price === false || $original_quantity === false || $new_quantity === false) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides']);
    exit;
}

if ($quantity_used <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantité utilisée invalide']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    $pdo->beginTransaction();

    // 1. Récupérer les informations du produit et du partenaire
    $stmt = $pdo->prepare("SELECT nom, reference FROM produits WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    $stmt = $pdo->prepare("SELECT nom FROM partenaires WHERE id = ? AND actif = 1");
    $stmt->execute([$partner_id]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partner) {
        throw new Exception('Partenaire non trouvé ou inactif');
    }

    // 2. Calculer le montant avec coefficient 1.2
    $montant_unitaire = $unit_price * 1.2;
    $montant_total = $montant_unitaire * $quantity_used;
    
    // 3. Créer la description de la transaction
    $description = "Utilisation pièce: {$product['nom']} (Réf: {$product['reference']}) - Quantité: {$quantity_used}";

    // 4. Créer la transaction partenaire (crédit pour nous)
    $stmt = $pdo->prepare("
        INSERT INTO transactions_partenaires (partenaire_id, type, montant, description, statut)
        VALUES (?, 'SERVICE', ?, ?, 'VALIDÉ')
    ");
    $stmt->execute([
        $partner_id,
        $montant_total,
        $description
    ]);
    
    $transaction_id = $pdo->lastInsertId();

    // 5. Mettre à jour le solde du partenaire
    $stmt = $pdo->prepare("
        INSERT INTO soldes_partenaires (partenaire_id, solde_actuel) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE 
        solde_actuel = solde_actuel + VALUES(solde_actuel),
        derniere_mise_a_jour = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$partner_id, $montant_total]);

    // 6. Mettre à jour la quantité du produit
    $stmt = $pdo->prepare("UPDATE produits SET quantite = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_quantity, $product_id]);

    // 7. Enregistrer le mouvement de stock
    $motif = "Utilisation partenaire: {$partner['nom']} - Transaction #{$transaction_id}";
    $stmt = $pdo->prepare("
        INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, user_id)
        VALUES (?, 'sortie', ?, ?, ?)
    ");
    $stmt->execute([
        $product_id,
        $quantity_used,
        $motif,
        $_SESSION['user_id']
    ]);

    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Transaction créée avec succès',
        'transaction_id' => $transaction_id,
        'montant' => number_format($montant_total, 2, '.', '') . '€',
        'partenaire' => $partner['nom'],
        'nouvelle_quantite' => $new_quantity
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erreur create_partner_transaction.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>

