<?php
// Config session (doit être inclus en premier pour gérer le cookie MDGEEK_SESSION)
require_once dirname(__DIR__) . '/config/session_config.php';

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
    
    // Récupérer le produit actuel avec son seuil d'alerte
    $stmt = $shop_pdo->prepare("SELECT id, nom, quantite, seuil_alerte FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        throw new Exception("Produit non trouvé");
    }
    
    $ancienne_quantite = intval($produit['quantite']);
    $seuil_alerte = intval($produit['seuil_alerte'] ?? 5);
    
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
    
    // Motif personnalisé ou par défaut
    if (isset($_POST['motif']) && !empty(trim($_POST['motif']))) {
        $motif = trim($_POST['motif']);
    } else {
        $motif = "Ajustement direct: {$ancienne_quantite} → {$nouvelle_quantite}";
    }
    
    // Mettre à jour le stock
    $stmt = $shop_pdo->prepare("UPDATE produits SET quantite = ? WHERE id = ?");
    $stmt->execute([$nouvelle_quantite, $produit_id]);
    
    // Enregistrer le mouvement
    $stmt = $shop_pdo->prepare("
        INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, user_id, date_mouvement)
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
    
    // Vérifier si notification stock bas ou rupture nécessaire
    try {
        require_once __DIR__ . '/../includes/NotificationService.php';
        if ($nouvelle_quantite <= 0) {
            NotificationService::notifyStockOut($produit_id, $produit['nom']);
        } elseif ($nouvelle_quantite <= $seuil_alerte && $ancienne_quantite > $seuil_alerte) {
            NotificationService::notifyLowStock($produit_id, $produit['nom'], $nouvelle_quantite, $seuil_alerte);
        }
    } catch (Exception $e) {
        error_log("NOTIFICATION ERROR (ajuster_stock): " . $e->getMessage());
    }
    
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
