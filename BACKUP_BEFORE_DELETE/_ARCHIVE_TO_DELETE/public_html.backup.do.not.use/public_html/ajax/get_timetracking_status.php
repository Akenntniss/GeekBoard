<?php
/**
 * API pour récupérer le statut de pointage d'un utilisateur
 * Compatible avec le système multi-magasin GeekBoard
 */

// Configuration de base
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session et initialiser la session shop
session_start();
initializeShopSession();

try {
    // Connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    // Récupérer l'utilisateur de la session ou le premier utilisateur disponible
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        $stmt = $pdo->prepare("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user) {
            $user_id = $user['id'];
        } else {
            throw new Exception('Aucun utilisateur trouvé dans cette boutique');
        }
    }

    // Vérifier si la table time_tracking existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'time_tracking'");
    $stmt->execute();
    $table_exists = $stmt->fetch();

    if (!$table_exists) {
        echo json_encode([
            'success' => false,
            'message' => 'Table de pointage non configurée'
        ]);
        exit;
    }

    // Récupérer le dernier pointage de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT *, 
               CASE WHEN clock_out IS NULL THEN 'active' ELSE 'completed' END as current_status
        FROM time_tracking 
        WHERE user_id = ? 
        ORDER BY clock_in DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entry) {
        echo json_encode([
            'success' => true,
            'is_clocked_in' => false,
            'status' => 'no_entry',
            'message' => 'Aucun pointage trouvé'
        ]);
        exit;
    }

    // Déterminer si l'utilisateur est actuellement pointé
    $is_clocked_in = ($entry['current_status'] === 'active');

    // Préparer les données de réponse
    $response_data = [
        'success' => true,
        'is_clocked_in' => $is_clocked_in,
        'status' => $entry['current_status'],
        'entry_id' => $entry['id'],
        'clock_in' => $entry['clock_in'],
        'clock_out' => $entry['clock_out'],
        'auto_approved' => (bool)($entry['auto_approved'] ?? false),
        'approval_reason' => $entry['approval_reason'] ?? null,
        'shop_info' => [
            'shop_id' => $_SESSION['shop_id'] ?? null,
            'shop_name' => $_SESSION['shop_name'] ?? 'Magasin'
        ]
    ];

    // Ajouter la durée de travail si applicable
    if ($is_clocked_in) {
        $work_duration = (time() - strtotime($entry['clock_in'])) / 3600;
        $response_data['current_duration'] = round($work_duration, 2);
    } else if ($entry['work_duration']) {
        $response_data['work_duration'] = $entry['work_duration'];
    }

    echo json_encode($response_data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
