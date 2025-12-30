<?php
// Utiliser exactement le même système que les actions existantes
session_start();

// Simuler une requête POST vers inventaire_actions.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produit_id']) && isset($_POST['nouvelle_quantite'])) {
    
    $produit_id = intval($_POST['produit_id']);
    $nouvelle_quantite = intval($_POST['nouvelle_quantite']);
    
    if ($produit_id <= 0 || $nouvelle_quantite < 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        exit;
    }
    
    // Récupérer d'abord les infos du produit pour calculer le mouvement
    require_once '../config/database.php';
    if (function_exists('initializeShopSession')) {
        initializeShopSession();
    }
    
    try {
        $shop_pdo = getShopDBConnection();
        
        // Récupérer le produit actuel
        $stmt = $shop_pdo->prepare("SELECT id, nom, quantite FROM produits WHERE id = ?");
        $stmt->execute([$produit_id]);
        $produit = $stmt->fetch();
        
        if (!$produit) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
            exit;
        }
        
        $ancienne_quantite = intval($produit['quantite']);
        
        // Si pas de changement
        if ($nouvelle_quantite === $ancienne_quantite) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Aucun changement nécessaire',
                'nouvelle_quantite' => $nouvelle_quantite,
                'produit_nom' => $produit['nom']
            ]);
            exit;
        }
        
        // Calculer le mouvement pour les actions
        $delta = $nouvelle_quantite - $ancienne_quantite;
        $type_mouvement = $delta > 0 ? 'entree' : 'sortie';
        $quantite_mouvement = abs($delta);
        
        // Préparer les données pour le système d'actions
        $_POST['action'] = 'mouvement_stock';
        $_POST['type_mouvement'] = $type_mouvement;
        $_POST['quantite'] = $quantite_mouvement;
        $_POST['motif'] = "Ajustement direct: {$ancienne_quantite} → {$nouvelle_quantite}";
        
        // Capturer la sortie du système d'actions
        ob_start();
        
        // Inclure le système d'actions
        require_once '../actions/inventaire_actions.php';
        
        $output = ob_get_clean();
        
        // Retourner une réponse JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Stock ajusté avec succès',
            'ancienne_quantite' => $ancienne_quantite,
            'nouvelle_quantite' => $nouvelle_quantite,
            'produit_nom' => $produit['nom']
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
