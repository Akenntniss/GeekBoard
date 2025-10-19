<?php
/**
 * API pour récupérer les informations du client associé à une réparation
 * Compatible avec le système multi-boutique
 */

// Utiliser la configuration de session globale (nom de session, domaine, sécurité)
require_once __DIR__ . '/../config/session_config.php';

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/../config/subdomain_config.php';

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ]);
    exit;
}

// Vérifier si l'ID de la réparation est fourni (soit 'id' soit 'reparation_id')
$reparation_id = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $reparation_id = (int)$_GET['id'];
} elseif (isset($_GET['reparation_id']) && !empty($_GET['reparation_id'])) {
    $reparation_id = (int)$_GET['reparation_id'];
}

if (!$reparation_id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID réparation non spécifié'
    ]);
    exit;
}

try {
    // Utiliser la connexion à la base de données du magasin actuel
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de se connecter à la base de données du magasin'
        ]);
        exit;
    }
    
    // Récupérer les informations du client associé à la réparation
    $stmt = $shop_pdo->prepare("
        SELECT c.id, c.nom, c.prenom, c.telephone, c.email
        FROM clients c
        JOIN reparations r ON c.id = r.client_id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo json_encode([
            'success' => false,
            'message' => 'Client non trouvé pour cette réparation'
        ]);
        exit;
    }
    
    // Renvoyer les résultats au format JSON
    echo json_encode([
        'success' => true,
        'client' => $client
    ]);
    
} catch (PDOException $e) {
    // Gérer les erreurs de base de données
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des informations du client: ' . $e->getMessage()
    ]);
} 