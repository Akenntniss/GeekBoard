<?php
/**
 * Endpoint AJAX pour ajouter un produit du catalogue fournisseur à la liste des commandes
 * Utilise automatiquement le client "Magasin Atelier"
 * Supporte les requêtes cross-origin depuis l'extension Chrome SERVO
 */

require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// === CORS Headers pour l'extension Chrome ===
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Autoriser les requêtes depuis les sous-domaines SERVO et les extensions Chrome
if (preg_match('/\.(servo\.tools|mdgeek\.top)$/', parse_url($origin, PHP_URL_HOST) ?? '') ||
    strpos($origin, 'chrome-extension://') === 0) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-SERVO-Extension');
}

// Gérer les requêtes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé - Veuillez vous connecter à SERVO']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les données POST
    $catalogue_id = intval($_POST['catalogue_id'] ?? 0);
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0);
    $nom_piece = trim($_POST['nom_piece'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');
    $source_url = trim($_POST['source_url'] ?? ''); // URL source pour traçabilité
    
    // Validation - Plus souple pour les requêtes externes (sans catalogue_id)
    $is_external = isset($_SERVER['HTTP_X_SERVO_EXTENSION']) || $catalogue_id === 0;
    
    if (!$nom_piece || $prix <= 0) {
        throw new Exception('Données manquantes: nom et prix requis');
    }
    
    // === Auto-création du fournisseur si inconnu ===
    // Si fournisseur_id = 16 (AUTRE) et qu'on a une source_url, essayer de trouver/créer le fournisseur
    if ($fournisseur_id == 16 && !empty($source_url)) {
        // Extraire le domaine de l'URL
        $domain = parse_url($source_url, PHP_URL_HOST);
        if ($domain) {
            // Nettoyer le domaine (retirer www.)
            $domain = preg_replace('/^www\./', '', $domain);
            
            // Chercher si un fournisseur existe avec ce domaine
            $stmt = $shop_pdo->prepare("SELECT id FROM fournisseurs WHERE url LIKE :domain OR url LIKE :domain2 LIMIT 1");
            $stmt->execute([
                'domain' => '%' . $domain . '%',
                'domain2' => '%' . $domain . '%'
            ]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $fournisseur_id = $existing['id'];
            } else {
                // Créer le nouveau fournisseur automatiquement
                $supplier_name = ucfirst(explode('.', $domain)[0]); // Prendre la première partie du domaine
                $stmt = $shop_pdo->prepare("INSERT INTO fournisseurs (nom, url) VALUES (:nom, :url)");
                $stmt->execute([
                    'nom' => $supplier_name,
                    'url' => $domain
                ]);
                $fournisseur_id = $shop_pdo->lastInsertId();
                error_log("SERVO Extension: Auto-created supplier '$supplier_name' with ID $fournisseur_id");
            }
        }
    }    
    // Si un client_id est fourni, l'utiliser
    if (isset($_POST['client_id']) && intval($_POST['client_id']) > 0) {
        $client_id = intval($_POST['client_id']);
    } else {
        // Sinon, trouver ou créer le client "Magasin Atelier"
        $stmt = $shop_pdo->prepare("SELECT id FROM clients WHERE nom = 'Magasin' AND prenom = 'Atelier' LIMIT 1");
        $stmt->execute();
        $client = $stmt->fetch();
        
        if (!$client) {
            // Créer le client si il n'existe pas
            $stmt = $shop_pdo->prepare("INSERT INTO clients (nom, prenom, telephone, email, date_ajout) VALUES ('Magasin', 'Atelier', '', '', NOW())");
            $stmt->execute();
            $client_id = $shop_pdo->lastInsertId();
        } else {
            $client_id = $client['id'];
        }
    }
    
    // Générer une référence unique pour la commande
    $cmd_reference = 'CMD-' . date('Ymd') . '-' . uniqid();
    
    // Insérer la commande - utiliser la référence fournisseur comme code_barre
    $stmt = $shop_pdo->prepare("
        INSERT INTO commandes_pieces (
            reference,
            client_id,
            fournisseur_id,
            nom_piece,
            code_barre,
            description,
            quantite,
            prix_estime,
            statut,
            date_creation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    // La référence de l'extension devient le code_barre
    $code_barre = $reference;
    $description = !empty($source_url) ? "Source: " . $source_url : "";
    
    $stmt->execute([
        $cmd_reference,
        $client_id,
        $fournisseur_id,
        $nom_piece,
        $code_barre,
        $description,
        1, // quantité par défaut
        $prix,
        'en_attente' // statut par défaut
    ]);
    
    $commande_id = $shop_pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté au panier avec succès',
        'commande_id' => $commande_id,
        'reference' => $cmd_reference
    ]);
    
} catch (Exception $e) {
    error_log("Erreur add_catalogue_to_cart: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
