<?php
// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser la session du magasin
require_once '../config/database.php';

// Vérifier si la fonction existe avant de l'appeler
if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    $produit_id = intval($_POST['produit_id']);
    $nouvelle_quantite = intval($_POST['nouvelle_quantite']);
    
    if ($produit_id <= 0) {
        throw new Exception("ID produit invalide");
    }
    
    if ($nouvelle_quantite < 0) {
        throw new Exception("Quantité invalide");
    }
    
    $shop_pdo->beginTransaction();
    
    // Récupérer le produit actuel
    $stmt = $shop_pdo->prepare("SELECT id, nom, quantite FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        throw new Exception("Produit non trouvé");
    }
    
    $ancienne_quantite = intval($produit['quantite']);
    
    // Si pas de changement, on retourne success directement
    if ($nouvelle_quantite === $ancienne_quantite) {
        echo json_encode([
            'success' => true, 
            'message' => 'Aucun changement nécessaire',
            'nouvelle_quantite' => $nouvelle_quantite
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
    
    // Enregistrer le mouvement
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
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
