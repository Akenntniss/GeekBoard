<?php
// ajax/associer_piece_reparation.php
// Associe une pièce à une réparation et déduit automatiquement du stock

// 1. Session & Auth
require_once '../config/session_config.php';
require_once '../config/database.php';

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
    $produit_id = filter_input(INPUT_POST, 'produit_id', FILTER_VALIDATE_INT);
    $reparation_id = filter_input(INPUT_POST, 'reparation_id', FILTER_VALIDATE_INT);
    $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT) ?: 1;
    
    if (!$produit_id || !$reparation_id) {
        throw new Exception('Données invalides');
    }
    
    $pdo = getShopDBConnection();
    $pdo->beginTransaction();
    
    // 1. Vérifier stock disponible
    $stmt = $pdo->prepare("SELECT quantite, nom, reference FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produit) {
        throw new Exception('Produit introuvable');
    }
    
    if ($produit['quantite'] < $quantite) {
        throw new Exception('Stock insuffisant (' . $produit['quantite'] . ' disponible)');
    }
    
    // 2. Vérifier que la réparation existe
    $stmt = $pdo->prepare("SELECT id, marque, modele FROM reparations WHERE id = ?");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        throw new Exception('Réparation #' . $reparation_id . ' introuvable');
    }
    
    // 3. Déduire du stock
    $stmt = $pdo->prepare("UPDATE produits SET quantite = quantite - ? WHERE id = ?");
    $stmt->execute([$quantite, $produit_id]);
    
    // 4. Enregistrer dans pieces_utilisees_reparations
    $stmt = $pdo->prepare("
        INSERT INTO pieces_utilisees_reparations 
        (reparation_id, produit_id, quantite_utilisee, user_id, notes, date_utilisation)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $notes = "Pièce utilisée via scan QR: {$produit['nom']} ({$produit['reference']}) pour {$reparation['marque']} {$reparation['modele']}";
    $stmt->execute([$reparation_id, $produit_id, $quantite, $_SESSION['user_id'], $notes]);
    
    // 5. Logger dans mouvements_stock
    $stmt = $pdo->prepare("
        INSERT INTO mouvements_stock 
        (produit_id, type_mouvement, quantite, motif, user_id, date_mouvement)
        VALUES (?, 'sortie', ?, ?, ?, NOW())
    ");
    $motif = "Utilisé pour réparation #{$reparation_id} ({$reparation['marque']} {$reparation['modele']})";
    $stmt->execute([$produit_id, $quantite, $motif, $_SESSION['user_id']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pièce associée avec succès',
        'reparation_id' => $reparation_id,
        'produit_nom' => $produit['nom'],
        'nouveau_stock' => ($produit['quantite'] - $quantite)
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
