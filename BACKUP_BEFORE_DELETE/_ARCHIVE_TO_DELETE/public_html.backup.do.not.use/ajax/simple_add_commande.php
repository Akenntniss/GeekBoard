<?php
// Utiliser la configuration de session globale
require_once dirname(__DIR__) . '/config/session_config.php';

header('Content-Type: application/json');

// Debug de la session
error_log("Simple Add Commande - Session debug:");
error_log("SESSION data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    error_log("Session expirée - user_id non défini");
    echo json_encode(['success' => false, 'message' => 'Session expirée - veuillez vous reconnecter']);
    exit;
}

error_log("Session OK - user_id: " . $_SESSION['user_id']);

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Inclure les fichiers nécessaires
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/config/subdomain_config.php';
    
    // Récupérer les données POST
    $client_id = intval($_POST['client_id'] ?? 0);
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0);
    $nom_piece = trim($_POST['nom_piece'] ?? '');
    $quantite = intval($_POST['quantite'] ?? 1);
    $prix_estime = floatval($_POST['prix_estime'] ?? 0);
    $code_barre = trim($_POST['code_barre'] ?? '');
    $statut = $_POST['statut'] ?? 'en_attente';
    $reparation_id = intval($_POST['reparation_id'] ?? 0);
    
    // Validation basique
    if (!$client_id || !$fournisseur_id || !$nom_piece || !$quantite || !$prix_estime) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    // Préparer la requête d'insertion
    $sql = "INSERT INTO commandes_pieces (
        client_id, fournisseur_id, nom_piece, quantite, prix_estime, 
        code_barre, statut, reparation_id, user_id, date_creation
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $client_id, $fournisseur_id, $nom_piece, $quantite, $prix_estime,
        $code_barre, $statut, $reparation_id, $_SESSION['user_id']
    ]);
    
    if ($result) {
        $commande_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Commande créée avec succès',
            'commande_id' => $commande_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'insertion']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
