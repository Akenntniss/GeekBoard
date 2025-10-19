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

$reparation_id = filter_input(INPUT_POST, 'reparation_id', FILTER_VALIDATE_INT);
$produit_id = filter_input(INPUT_POST, 'produit_id', FILTER_VALIDATE_INT);
$quantite_utilisee = filter_input(INPUT_POST, 'quantite_utilisee', FILTER_VALIDATE_INT);
$nouvelle_quantite = filter_input(INPUT_POST, 'nouvelle_quantite', FILTER_VALIDATE_INT);
$ancienne_quantite = filter_input(INPUT_POST, 'ancienne_quantite', FILTER_VALIDATE_INT);

if ($reparation_id === false || $produit_id === false || $quantite_utilisee === false || 
    $nouvelle_quantite === false || $ancienne_quantite === false) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    $pdo->beginTransaction();

    // Vérifier que la réparation existe
    $stmt = $pdo->prepare("SELECT id, client_id FROM reparations WHERE id = ?");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch();
    
    if (!$reparation) {
        throw new Exception("Réparation #$reparation_id non trouvée");
    }

    // Vérifier que le produit existe
    $stmt = $pdo->prepare("SELECT id, nom, reference FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        throw new Exception("Produit #$produit_id non trouvé");
    }

    // Enregistrer l'utilisation de la pièce dans la nouvelle table
    $stmt = $pdo->prepare("
        INSERT INTO pieces_utilisees_reparations 
        (reparation_id, produit_id, quantite_utilisee, user_id, notes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $notes = "Pièce utilisée via scanner QR - Stock ajusté de {$ancienne_quantite} à {$nouvelle_quantite}";
    $stmt->execute([
        $reparation_id,
        $produit_id,
        $quantite_utilisee,
        $_SESSION['user_id'],
        $notes
    ]);

    // Mettre à jour la quantité du produit
    $stmt = $pdo->prepare("UPDATE produits SET quantite = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$nouvelle_quantite, $produit_id]);

    // Enregistrer le mouvement de stock
    $stmt = $pdo->prepare("
        INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, user_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $motif = "Utilisation dans réparation #$reparation_id (QR scan)";
    $stmt->execute([
        $produit_id,
        'sortie',
        $quantite_utilisee,
        $motif,
        $_SESSION['user_id']
    ]);

    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pièce enregistrée avec succès',
        'reparation_id' => $reparation_id,
        'produit_nom' => $produit['nom'],
        'quantite_utilisee' => $quantite_utilisee,
        'nouvelle_quantite' => $nouvelle_quantite
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erreur enregistrer_piece_utilisee.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
