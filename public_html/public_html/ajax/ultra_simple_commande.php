<?php
session_start();
header('Content-Type: application/json');

// Debug complet
$debug = [
    'session_exists' => isset($_SESSION),
    'user_id_exists' => isset($_SESSION['user_id']),
    'user_id_value' => $_SESSION['user_id'] ?? null,
    'post_data' => $_POST,
    'request_method' => $_SERVER['REQUEST_METHOD']
];

// Log du debug
error_log("Ultra Simple Commande Debug: " . json_encode($debug));

// Vérifications de base
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée', 'debug' => $debug]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée', 'debug' => $debug]);
    exit;
}

// Connexion simple à la base
try {
    $host = 'localhost';
    $dbname = 'geekboard_mkmkmk'; // Base directe
    $username = 'root';
    $password = 'Mamanmaman01#';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les données
    $client_id = intval($_POST['client_id'] ?? 0);
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0);
    $nom_piece = trim($_POST['nom_piece'] ?? '');
    $quantite = intval($_POST['quantite'] ?? 1);
    $prix_estime = floatval($_POST['prix_estime'] ?? 0);
    $code_barre = trim($_POST['code_barre'] ?? '');
    $statut = $_POST['statut'] ?? 'en_attente';
    $reparation_id = intval($_POST['reparation_id'] ?? 0);
    
    // Validation
    if (!$client_id || !$fournisseur_id || !$nom_piece || !$quantite || !$prix_estime) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes', 'debug' => compact('client_id', 'fournisseur_id', 'nom_piece', 'quantite', 'prix_estime')]);
        exit;
    }
    
    // Insertion
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
            'commande_id' => $commande_id,
            'debug' => $debug
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'insertion', 'debug' => $debug]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage(), 'debug' => $debug]);
}
?>
