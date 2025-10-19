<?php
// Modifier un produit
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0) ?: null;
    $prix_achat = floatval($_POST['prix_achat'] ?? 0);
    $prix_vente = floatval($_POST['prix_vente'] ?? 0);
    $quantite = intval($_POST['quantite'] ?? 0);
    $seuil_alerte = intval($_POST['seuil_alerte'] ?? 5);
    $suivre_stock = intval($_POST['suivre_stock'] ?? 0);
    
    // Validations
    if ($id <= 0) {
        throw new Exception('ID produit invalide');
    }
    
    if (empty($reference)) {
        throw new Exception('La référence est obligatoire');
    }
    
    if (empty($nom)) {
        throw new Exception('Le nom est obligatoire');
    }
    
    if ($prix_vente <= 0) {
        throw new Exception('Le prix de vente doit être supérieur à 0');
    }
    
    if ($quantite < 0) {
        throw new Exception('La quantité ne peut pas être négative');
    }
    
    // Connexion directe
    $host = 'localhost';
    $dbname = 'geekboard_mkmkmk';
    $username = 'root';
    $password = 'Mamanmaman01#';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier que le produit existe
    $stmt = $pdo->prepare("SELECT id FROM produits WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Produit non trouvé');
    }
    
    // Vérifier que la référence n'est pas déjà utilisée par un autre produit
    $stmt = $pdo->prepare("SELECT id FROM produits WHERE reference = ? AND id != ?");
    $stmt->execute([$reference, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Cette référence est déjà utilisée par un autre produit');
    }
    
    // Mettre à jour le produit
    $stmt = $pdo->prepare("
        UPDATE produits 
        SET reference = ?, nom = ?, description = ?, fournisseur_id = ?, prix_achat = ?, 
            prix_vente = ?, quantite = ?, seuil_alerte = ?, suivre_stock = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $reference, $nom, $description, $fournisseur_id, $prix_achat, 
        $prix_vente, $quantite, $seuil_alerte, $suivre_stock, $id
    ]);
    
    // Récupérer le produit mis à jour avec le fournisseur
    $stmt = $pdo->prepare("
        SELECT p.id, p.reference, p.nom, p.description, p.prix_achat, p.prix_vente, 
               p.quantite, p.seuil_alerte, p.suivre_stock, p.fournisseur_id,
               f.nom as fournisseur_nom
        FROM produits p 
        LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit modifié avec succès',
        'product' => $product
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
