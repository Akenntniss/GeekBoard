<?php
// Désactiver l'affichage des erreurs PHP (évite le HTML dans le JSON)
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Vérifier si les fichiers de configuration existent
$configFile = dirname(__DIR__) . '/config/database.php';
$functionsFile = dirname(__DIR__) . '/includes/functions.php';

if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fichier de configuration manquant']);
    exit;
}

if (!file_exists($functionsFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fichier de fonctions manquant']);
    exit;
}

require_once $configFile;
require_once $functionsFile;

// Initialiser la session
initializeShopSession();

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Connexion à la base de données impossible']);
    exit;
}

// Vérifier si les données sont présentes
if (!isset($_POST['nom']) || empty($_POST['nom'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le nom du fournisseur est requis']);
    exit;
}

$nom = cleanInput($_POST['nom']);
$url = isset($_POST['url']) ? cleanInput($_POST['url']) : '';

try {
    // Vérifier si le fournisseur existe déjà
    $stmt = $shop_pdo->prepare("SELECT id FROM fournisseurs WHERE nom = ?");
    $stmt->execute([$nom]);
    if ($stmt->fetch()) {
        throw new Exception('Un fournisseur avec ce nom existe déjà');
    }

    // Insérer le nouveau fournisseur
    $sql = "INSERT INTO fournisseurs (nom, url) VALUES (?, ?)";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$nom, $url]);

    echo json_encode([
        'success' => true,
        'message' => 'Fournisseur ajouté avec succès',
        'id' => $shop_pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 