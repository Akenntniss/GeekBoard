<?php
/**
 * Traitement AJAX pour récupérer les détails d'un rapport de bug
 */

// Configuration de session pour assurer la compatibilité
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');

// Démarrer la session
session_start();

require_once '../config/database.php';

header('Content-Type: application/json');

// Récupération des paramètres
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Validation des données
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de rapport invalide']);
    exit;
}

try {
    // Solution temporaire : forcer la connexion au shop mkmkmk si aucun shop_id en session
    if (empty($_SESSION['shop_id'])) {
        error_log("Pas de shop_id en session, tentative de connexion directe à geekboard_mkmkmk");
        
        // Connexion directe à la base mkmkmk
        $shop_pdo = new PDO(
            "mysql:host=localhost;port=3306;dbname=geekboard_mkmkmk;charset=utf8mb4",
            "root",
            "Mamanmaman01#",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        error_log("Connexion directe réussie à geekboard_mkmkmk");
    } else {
        // Utiliser la connexion multi-magasin normale
        $shop_pdo = getShopDBConnection();
    }
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }

    // Récupération des détails du bug
    $query = "SELECT * FROM bug_reports WHERE id = :id";
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([':id' => $id]);
    
    $bug = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bug) {
        // Formater la date pour l'affichage
        $bug['date_creation'] = date('d/m/Y H:i', strtotime($bug['date_creation']));
        
        // Réponse de succès avec les détails du bug
        echo json_encode([
            'success' => true, 
            'bug' => $bug
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun rapport trouvé avec cet ID']);
    }
    
} catch (Exception $e) {
    // Log de l'erreur côté serveur
    error_log("Erreur lors de la récupération des détails du bug: " . $e->getMessage());
    error_log("Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini'));
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'non défini'));
    error_log("Bug ID: " . $id);
    
    // Réponse d'erreur avec plus de détails pour le debug
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue lors de la récupération des détails',
        'debug' => $e->getMessage()
    ]);
}
?>
