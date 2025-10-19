<?php
// Initialiser la session du magasin
require_once '../config/database.php';
initializeShopSession();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

$produit_id = $input['produit_id'] ?? null;
$nouvelle_quantite = $input['nouvelle_quantite'] ?? null;
$motif = $input['motif'] ?? 'Mise à jour rapide';

if (!$produit_id || $nouvelle_quantite === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    $shop_pdo->beginTransaction();
    
    // Récupérer le produit actuel
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, reference, quantite, suivre_stock
        FROM produits 
        WHERE id = ? AND suivre_stock = 1
    ");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        throw new Exception("Produit non trouvé ou non suivi");
    }
    
    $ancienne_quantite = $produit['quantite'];
    $nouvelle_quantite = intval($nouvelle_quantite);
    
    // Mettre à jour la quantité
    $stmt = $shop_pdo->prepare("
        UPDATE produits 
        SET quantite = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$nouvelle_quantite, $produit_id]);
    
    // Enregistrer le mouvement de stock
    $difference = $nouvelle_quantite - $ancienne_quantite;
    if ($difference != 0) {
        $type_mouvement = $difference > 0 ? 'entree' : 'sortie';
        $quantite_mouvement = abs($difference);
        $motif_complet = "Mise à jour rapide: {$motif} (de {$ancienne_quantite} à {$nouvelle_quantite})";
        
        $stmt = $shop_pdo->prepare("
            INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $produit_id,
            $type_mouvement,
            $quantite_mouvement,
            $motif_complet,
            $_SESSION['user_id']
        ]);
    }
    
    $shop_pdo->commit();
    
    // Récupérer le produit mis à jour
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, reference, quantite, seuil_alerte
        FROM produits 
        WHERE id = ?
    ");
    $stmt->execute([$produit_id]);
    $produit_updated = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock mis à jour avec succès',
        'produit' => $produit_updated,
        'ancienne_quantite' => $ancienne_quantite,
        'nouvelle_quantite' => $nouvelle_quantite
    ]);
    
} catch (Exception $e) {
    $shop_pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
?>
