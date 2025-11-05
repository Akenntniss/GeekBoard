<?php
// Configuration et session sécurisée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Permettre l'accès même sans authentification pour le debug
// if (!isset($_SESSION['user_id'])) {
//     header('Content-Type: application/json');
//     echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
//     exit;
// }

// Vérifier l'ID de la tâche
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de tâche invalide']);
    exit;
}

$task_id = intval($_GET['id']);

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    // Fallback : connexion directe si getShopDBConnection échoue
    if (!$shop_pdo) {
        try {
            $shop_pdo = new PDO(
                "mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4",
                "root",
                "Mamanmaman01#",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Connexion directe à la base de données échouée: ' . $e->getMessage());
        }
    }
    
    if (!$shop_pdo) {
        throw new Exception('Aucune connexion à la base de données disponible');
    }
    
    // Requête pour récupérer tous les détails de la tâche
    $stmt = $shop_pdo->prepare("
        SELECT t.*, 
               e.full_name as employe_nom,
               c.full_name as createur_nom,
               DATE_FORMAT(t.date_creation, '%d/%m/%Y à %H:%i') as date_creation_formatted,
               DATE_FORMAT(t.date_limite, '%d/%m/%Y') as date_limite_formatted
        FROM taches t
        LEFT JOIN users e ON t.employe_id = e.id
        LEFT JOIN users c ON t.created_by = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        // Formater le statut pour l'affichage
        $status_display = '';
        switch($task['statut']) {
            case 'a_faire':
                $status_display = 'À faire';
                break;
            case 'en_cours':
                $status_display = 'En cours';
                break;
            case 'termine':
                $status_display = 'Terminé';
                break;
            default:
                $status_display = ucfirst($task['statut']);
        }
        
        $task['statut_display'] = $status_display;
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'task' => $task
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Tâche non trouvée'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des détails de la tâche: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des détails'
    ]);
}
?>
