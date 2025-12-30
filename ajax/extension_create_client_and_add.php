<?php
/**
 * Endpoint AJAX pour créer un client ET ajouter un produit à sa commande
 * Utilisé par l'extension Chrome SERVO
 */

require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// === CORS Headers pour l'extension Chrome ===
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (preg_match('/\.(servo\.tools|mdgeek\.top)$/', parse_url($origin, PHP_URL_HOST) ?? '') ||
    strpos($origin, 'chrome-extension://') === 0) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-SERVO-Extension');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

header('Content-Type: application/json');

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les données client
    $client_nom = trim($_POST['client_nom'] ?? '');
    $client_prenom = trim($_POST['client_prenom'] ?? '');
    $client_phone = trim($_POST['client_phone'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    
    // Récupérer les données produit
    $nom_piece = trim($_POST['nom_piece'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0);
    $source_url = trim($_POST['source_url'] ?? '');
    
    // Validation
    if (empty($client_nom) || empty($client_prenom) || empty($client_phone)) {
        echo json_encode(['success' => false, 'message' => 'Nom, prénom et téléphone requis']);
        exit;
    }
    
    if (empty($nom_piece)) {
        echo json_encode(['success' => false, 'message' => 'Nom de la pièce requis']);
        exit;
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // 1. Créer le client
    $stmt = $shop_pdo->prepare("
        INSERT INTO clients (nom, prenom, telephone, email, date_creation)
        VALUES (:nom, :prenom, :telephone, :email, NOW())
    ");
    $stmt->execute([
        'nom' => $client_nom,
        'prenom' => $client_prenom,
        'telephone' => $client_phone,
        'email' => $client_email ?: null
    ]);
    $client_id = $shop_pdo->lastInsertId();
    
    // 2. Générer une référence de commande unique
    $order_reference = 'EXT-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
    
    // 3. Créer la commande de pièce - utiliser la référence fournisseur comme code_barre
    $stmt = $shop_pdo->prepare("
        INSERT INTO commandes_pieces 
        (reference, client_id, fournisseur_id, nom_piece, code_barre, prix_estime, 
         description, quantite, statut, date_creation)
        VALUES 
        (:ref, :client_id, :fournisseur_id, :nom_piece, :code_barre, :prix,
         :description, 1, 'en_attente', NOW())
    ");
    
    // La référence de l'extension devient le code_barre
    $code_barre = $reference;
    $description_text = !empty($source_url) ? "Source: " . $source_url : "";
    
    $stmt->execute([
        'ref' => $order_reference,
        'client_id' => $client_id,
        'fournisseur_id' => $fournisseur_id,
        'nom_piece' => $nom_piece,
        'code_barre' => $code_barre,
        'prix' => $prix,
        'description' => $description_text
    ]);
    
    $shop_pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Client créé et commande ajoutée',
        'client_id' => $client_id,
        'order_reference' => $order_reference
    ]);
    
} catch (Exception $e) {
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    error_log("Erreur extension_create_client_and_add: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
