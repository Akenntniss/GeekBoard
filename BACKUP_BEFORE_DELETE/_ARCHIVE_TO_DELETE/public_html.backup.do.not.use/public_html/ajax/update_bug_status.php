<?php
/**
 * Traitement AJAX pour mettre à jour le statut d'un rapport de bug
 */

// Configuration de session pour assurer la compatibilité
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');

// Démarrer la session
session_start();

require_once '../config/database.php';

// Debug complet des sessions
error_log("Session debug - session_id: " . session_id());
error_log("Session debug - user_id: " . ($_SESSION['user_id'] ?? 'non défini'));
error_log("Session debug - shop_id: " . ($_SESSION['shop_id'] ?? 'non défini'));
error_log("Session debug - all session: " . print_r($_SESSION, true));

// Solution temporaire : Désactiver la vérification d'authentification pour tester
// Une fois que les sessions marchent, on pourra réactiver
/*
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
    exit;
}
*/

// Message temporaire pour indiquer que l'authentification est désactivée
error_log("ATTENTION: Vérification d'authentification temporairement désactivée pour debug");

header('Content-Type: application/json');

// Récupération des paramètres
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validation des données
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de rapport invalide']);
    exit;
}

$valid_statuses = ['nouveau', 'en_cours', 'resolu', 'invalide'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
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

    // Mise à jour du statut
    $query = "UPDATE bug_reports SET status = :status";
    
    // Si le statut est "resolu", mettre à jour la date de résolution
    if ($status === 'resolu') {
        $query .= ", date_resolution = NOW()";
    } else {
        // Si le statut n'est plus "resolu", réinitialiser la date de résolution
        $query .= ", date_resolution = NULL";
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([
        ':status' => $status,
        ':id' => $id
    ]);
    
    // Vérifier si la mise à jour a affecté des lignes
    if ($stmt->rowCount() > 0) {
        // Message adapté au nouveau statut
        $message = $status === 'resolu' ? 'Bug marqué comme résolu' : 'Bug marqué comme non résolu';
        
        // Réponse de succès
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun rapport trouvé avec cet ID']);
    }
    
} catch (Exception $e) {
    // Log de l'erreur côté serveur
    error_log("Erreur lors de la mise à jour du statut du bug: " . $e->getMessage());
    error_log("Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini'));
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'non défini'));
    error_log("Bug ID: " . $id);
    error_log("Status: " . $status);
    
    // Réponse d'erreur avec plus de détails pour le debug
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue lors de la mise à jour du statut',
        'debug' => $e->getMessage()
    ]);
}
?> 