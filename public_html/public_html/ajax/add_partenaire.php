<?php
// Inclure la configuration de session avant de démarrer la session
require_once dirname(__DIR__) . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Inclure la configuration pour la gestion des sous-domaines
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Initialiser la session du magasin si nécessaire
if (!isset($_SESSION['shop_id'])) {
    initializeShopSession();
}

// Ajouter un logging de la session pour débogage
error_log("Session data: " . json_encode($_SESSION));
error_log("Session ID: " . session_id());
error_log("Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini'));

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si le shop_id est défini
if (!isset($_SESSION['shop_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Magasin non identifié']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$nom = trim(filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$telephone = trim(filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$adresse = trim(filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

if (!$nom) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Le nom est requis']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }

    $shop_pdo->beginTransaction();

    // Insérer le nouveau partenaire
    $stmt = $shop_pdo->prepare("
        INSERT INTO partenaires 
        (nom, email, telephone, adresse) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$nom, $email, $telephone, $adresse]);

    // Créer un solde initial à 0
    $partenaire_id = $shop_pdo->lastInsertId();
    $stmt = $shop_pdo->prepare("
        INSERT INTO soldes_partenaires 
        (partenaire_id, solde_actuel) 
        VALUES (?, 0)
    ");
    $stmt->execute([$partenaire_id]);

    $shop_pdo->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Partenaire ajouté avec succès',
        'partenaire_id' => $partenaire_id
    ]);

} catch (Exception $e) {
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    error_log("Erreur lors de l'ajout du partenaire : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'ajout du partenaire: ' . $e->getMessage(),
        'debug' => [
            'shop_id' => $_SESSION['shop_id'] ?? 'non défini',
            'user_id' => $_SESSION['user_id'] ?? 'non défini'
        ]
    ]);
} 