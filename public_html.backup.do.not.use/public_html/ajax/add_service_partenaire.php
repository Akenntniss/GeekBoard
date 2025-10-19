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
$partenaire_id = filter_input(INPUT_POST, 'partenaire_id', FILTER_VALIDATE_INT);
$description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);

if (!$partenaire_id || !$description || !$montant) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }

    // Démarrer une transaction
    $shop_pdo->beginTransaction();

    // Insérer le service dans la table services_partenaires
    $stmt = $shop_pdo->prepare("
        INSERT INTO services_partenaires 
        (partenaire_id, description, montant) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$partenaire_id, $description, $montant]);

    // Créer une transaction correspondante
    $stmt = $shop_pdo->prepare("
        INSERT INTO transactions_partenaires 
        (partenaire_id, type, montant, description) 
        VALUES (?, 'SERVICE', ?, ?)
    ");
    $stmt->execute([$partenaire_id, $montant, "Service: " . $description]);

    // Mettre à jour le solde du partenaire
    $stmt = $shop_pdo->prepare("SELECT solde_actuel FROM soldes_partenaires WHERE partenaire_id = ?");
    $stmt->execute([$partenaire_id]);
    $solde = $stmt->fetch();

    if ($solde) {
        // Mettre à jour le solde existant
        $stmt = $shop_pdo->prepare("
            UPDATE soldes_partenaires 
            SET solde_actuel = solde_actuel + ?, 
                derniere_mise_a_jour = CURRENT_TIMESTAMP 
            WHERE partenaire_id = ?
        ");
        $stmt->execute([$montant, $partenaire_id]);
    } else {
        // Créer un nouveau solde
        $stmt = $shop_pdo->prepare("
            INSERT INTO soldes_partenaires 
            (partenaire_id, solde_actuel) 
            VALUES (?, ?)
        ");
        $stmt->execute([$partenaire_id, $montant]);
    }

    // Valider la transaction
    $shop_pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Service enregistré avec succès']);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    error_log("Erreur lors de l'ajout du service : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'enregistrement du service: ' . $e->getMessage(),
        'debug' => [
            'shop_id' => $_SESSION['shop_id'] ?? 'non défini',
            'user_id' => $_SESSION['user_id'] ?? 'non défini'
        ]
    ]);
} 