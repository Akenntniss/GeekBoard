<?php
// Configuration des en-têtes et sécurité
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Inclure les configurations obligatoires
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les données requises
$required_fields = ['produit_id', 'nouvelle_quantite', 'ancienne_quantite', 'motif'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Champ manquant: $field"]);
        exit;
    }
}

// Récupérer et valider les données
$produit_id = intval($_POST['produit_id']);
$nouvelle_quantite = intval($_POST['nouvelle_quantite']);
$ancienne_quantite = intval($_POST['ancienne_quantite']);
$motif = trim($_POST['motif']);

// Validation des données
if ($produit_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de produit invalide']);
    exit;
}

if ($nouvelle_quantite < 0) {
    echo json_encode(['success' => false, 'message' => 'La nouvelle quantité ne peut pas être négative']);
    exit;
}

if (empty($motif)) {
    echo json_encode(['success' => false, 'message' => 'Le motif est obligatoire']);
    exit;
}

try {
    // Obtenir la connexion à la base de données
    $pdo = getShopDBConnection();
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Vérifier que le produit existe et récupérer ses informations
    $stmt = $pdo->prepare("SELECT id, nom, quantite, reference FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produit) {
        throw new Exception('Produit non trouvé');
    }
    
    // Vérifier que la quantité actuelle correspond à l'ancienne quantité
    if ($produit['quantite'] != $ancienne_quantite) {
        throw new Exception('Le stock a été modifié par un autre utilisateur. Veuillez actualiser la page.');
    }
    
    // Mettre à jour la quantité du produit
    $stmt = $pdo->prepare("UPDATE produits SET quantite = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$nouvelle_quantite, $produit_id]);
    
    // Calculer le type de mouvement et la quantité du mouvement
    if ($nouvelle_quantite > $ancienne_quantite) {
        // Augmentation = entrée
        $type_mouvement = 'entree';
        $quantite_mouvement = $nouvelle_quantite - $ancienne_quantite;
    } else {
        // Diminution = sortie
        $type_mouvement = 'sortie';
        $quantite_mouvement = $ancienne_quantite - $nouvelle_quantite;
    }
    
    // Enregistrer le mouvement dans l'historique
    $stmt = $pdo->prepare("
        INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, user_id, date_mouvement)
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $produit_id,
        $type_mouvement,
        $quantite_mouvement,
        $motif,
        $_SESSION['user_id']
    ]);
    
    // Valider la transaction
    $pdo->commit();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Stock mis à jour avec succès',
        'data' => [
            'produit_id' => $produit_id,
            'produit_nom' => $produit['nom'],
            'ancienne_quantite' => $ancienne_quantite,
            'nouvelle_quantite' => $nouvelle_quantite,
            'type_mouvement' => $type_mouvement,
            'quantite_mouvement' => $quantite_mouvement,
            'motif' => $motif
        ]
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log l'erreur pour le débogage
    error_log("Erreur dans ajuster_stock_scan.php: " . $e->getMessage());
    
    // Réponse d'erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

