<?php
/**
 * API pour mettre à jour le prix d'une réparation
 * Compatible avec le nouveau format JSON utilisé dans statut_rapide.php
 */

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Récupérer les chemins des fichiers includes
$config_path = realpath(__DIR__ . '/../config/database.php');
$functions_path = realpath(__DIR__ . '/../includes/functions.php');

if (!file_exists($config_path) || !file_exists($functions_path)) {
    echo json_encode([
        'success' => false,
        'message' => 'Fichiers de configuration introuvables.'
    ]);
    exit;
}

// Inclure les fichiers nécessaires
require_once $config_path;
require_once $functions_path;

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
    exit;
}

// Vérifier l'authentification
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expirée. Veuillez vous reconnecter.'
    ]);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin.');
    }

    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('Aucune donnée reçue');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides: ' . json_last_error_msg());
    }

    // Valider les données obligatoires
    if (empty($data['reparation_id'])) {
        throw new Exception('ID de réparation manquant');
    }
    
    if (!isset($data['price'])) {
        throw new Exception('Prix manquant');
    }

    $reparation_id = intval($data['reparation_id']);
    $prix = floatval($data['price']);

    // Vérifier que la réparation existe et appartient au bon magasin
    $stmt = $shop_pdo->prepare("SELECT id FROM reparations WHERE id = ?");
    $stmt->execute([$reparation_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Réparation non trouvée');
    }

    // Mettre à jour le prix de la réparation
    $stmt = $shop_pdo->prepare("
        UPDATE reparations 
        SET prix_reparation = ?, date_modification = NOW() 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$prix, $reparation_id])) {
        // Enregistrer dans les logs si nécessaire
        $stmt = $shop_pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, details) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $reparation_id,
            $_SESSION['user_id'] ?? $_SESSION['shop_id'],
            'prix_modifie',
            'Prix mis à jour: ' . number_format($prix, 2, ',', ' ') . ' €'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Prix mis à jour avec succès',
            'data' => [
                'reparation_id' => $reparation_id,
                'nouveau_prix' => $prix,
                'prix_formate' => number_format($prix, 2, ',', ' ') . ' €'
            ]
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour du prix');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
