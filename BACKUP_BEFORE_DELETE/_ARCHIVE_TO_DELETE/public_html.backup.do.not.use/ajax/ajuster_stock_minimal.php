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
    
    // Connexion directe à la base de données (hardcodée pour test)
    $host = 'localhost';
    $dbname = 'geekboard_mkmkmk';  // Base de données directe
    $username = 'root';
    $password = 'Mamanmaman01#';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
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
