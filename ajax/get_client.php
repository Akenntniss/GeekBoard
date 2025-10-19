<?php
// Utiliser la configuration de session globale
require_once __DIR__ . '/../config/session_config.php';

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/../config/subdomain_config.php';

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Initialiser la session magasin pour les APIs directes
initializeShopSession();

// Vérifier si l'ID du client est fourni
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du client non fourni'
    ]);
    exit;
}

$client_id = intval($_GET['id']);

try {
    // Utiliser la connexion à la base de données du magasin actuel
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de se connecter à la base de données du magasin'
        ]);
        exit;
    }
    
    // Récupérer les informations du client
    $stmt = $pdo->prepare("
        SELECT id, nom, prenom, telephone, email
        FROM clients
        WHERE id = ?
    ");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($client) {
        echo json_encode([
            'success' => true,
            'client' => $client
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Client non trouvé'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des informations du client: ' . $e->getMessage()
    ]);
} 