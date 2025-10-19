<?php
// Même structure que les autres endpoints AJAX qui fonctionnent
session_start();
require_once '../config/database.php';
initializeShopSession();

$shop_pdo = getShopDBConnection();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$produit_id = intval($_POST['produit_id'] ?? 0);
$nouvelle_quantite = intval($_POST['nouvelle_quantite'] ?? 0);

if ($produit_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID produit invalide']);
    exit;
}

if ($nouvelle_quantite < 0) {
    echo json_encode(['success' => false, 'message' => 'Quantité invalide']);
    exit;
}

try {
    $shop_pdo->beginTransaction();
    
    // Récupérer le produit actuel
    $stmt = $shop_pdo->prepare("SELECT id, nom, quantite FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        throw new Exception("Produit non trouvé");
    }
    
    $ancienne_quantite = intval($produit['quantite']);
    
    // Si pas de changement, retourner success
    if ($nouvelle_quantite === $ancienne_quantite) {
        echo json_encode([
            'success' => true, 
            'message' => 'Aucun changement nécessaire',
            'nouvelle_quantite' => $nouvelle_quantite,
            'produit_nom' => $produit['nom']
        ]);
        exit;
    }
    
    // Calculer le mouvement
    $delta = $nouvelle_quantite - $ancienne_quantite;
    $type_mouvement = $delta > 0 ? 'entree' : 'sortie';
    $quantite_mouvement = abs($delta);
    $motif = "Ajustement direct: {$ancienne_quantite} → {$nouvelle_quantite}";
    
    // Mettre à jour le stock
    $stmt = $shop_pdo->prepare("UPDATE produits SET quantite = ? WHERE id = ?");
    $stmt->execute([$nouvelle_quantite, $produit_id]);
    
    // Enregistrer le mouvement dans la table mouvements_stock si elle existe
    try {
        $stmt = $shop_pdo->prepare("
            INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $produit_id,
            $type_mouvement,
            $quantite_mouvement,
            $motif,
            $_SESSION['user_id']
        ]);
    } catch (Exception $e) {
        // Si la table mouvements_stock n'existe pas, continuer sans erreur
    }
    
    $shop_pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock ajusté avec succès',
        'ancienne_quantite' => $ancienne_quantite,
        'nouvelle_quantite' => $nouvelle_quantite,
        'produit_nom' => $produit['nom']
    ]);
    
} catch (Exception $e) {
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
