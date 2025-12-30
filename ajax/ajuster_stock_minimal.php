<?php
// Version ultra-minimale sans inclusions complexes
header('Content-Type: application/json');

try {
    // Vérification basique
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    $produit_id = intval($_POST['produit_id'] ?? 0);
    $nouvelle_quantite = intval($_POST['nouvelle_quantite'] ?? 0);
    
    if ($produit_id <= 0) {
        throw new Exception('ID produit invalide');
    }
    
    if ($nouvelle_quantite < 0) {
        throw new Exception('Quantité invalide');
    }
    
    // Connexion sécurisée via getShopDBConnection
    require_once __DIR__ . '/../config/session_config.php';
    require_once __DIR__ . '/../config/database.php';

    // Initialiser la session du shop si nécessaire
    if (!isset($_SESSION['shop_id'])) {
        initializeShopSession();
    }

    $pdo = getShopDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer le produit
    $stmt = $pdo->prepare("SELECT id, nom, quantite FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        throw new Exception('Produit non trouvé');
    }
    
    $ancienne_quantite = intval($produit['quantite']);
    
    // Si pas de changement
    if ($nouvelle_quantite === $ancienne_quantite) {
        echo json_encode([
            'success' => true,
            'message' => 'Aucun changement nécessaire',
            'nouvelle_quantite' => $nouvelle_quantite,
            'produit_nom' => $produit['nom']
        ]);
        exit;
    }
    
    // Mettre à jour directement
    $stmt = $pdo->prepare("UPDATE produits SET quantite = ? WHERE id = ?");
    $stmt->execute([$nouvelle_quantite, $produit_id]);
    
    // ✅ ENREGISTRER LE MOUVEMENT DE STOCK (traçabilité)
    $type_mouvement = $nouvelle_quantite > $ancienne_quantite ? 'entree' : 'sortie';
    $quantite_change = abs($nouvelle_quantite - $ancienne_quantite);
    $motif = "Ajustement minimal: {$ancienne_quantite} → {$nouvelle_quantite}";
    
    $stmt = $pdo->prepare("
        INSERT INTO mouvements_stock 
        (produit_id, type_mouvement, quantite, motif, user_id, date_mouvement)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt->execute([
        $produit_id,
        $type_mouvement,
        $quantite_change,
        $motif,
        $user_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock ajusté avec succès',
        'ancienne_quantite' => $ancienne_quantite,
        'nouvelle_quantite' => $nouvelle_quantite,
        'produit_nom' => $produit['nom']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
