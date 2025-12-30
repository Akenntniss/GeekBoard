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

// Récupérer les données POST
$task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$priorite = isset($_POST['priorite']) ? trim($_POST['priorite']) : '';
$statut = isset($_POST['statut']) ? trim($_POST['statut']) : '';
$employe_id = isset($_POST['employe_id']) ? $_POST['employe_id'] : '';
$date_limite = isset($_POST['date_limite']) ? $_POST['date_limite'] : '';

// Validation des champs obligatoires
if ($task_id <= 0 || empty($titre) || empty($description) || empty($priorite) || empty($statut)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
    exit;
}

// Valider la priorité
$valid_priorities = ['basse', 'moyenne', 'haute'];
if (!in_array($priorite, $valid_priorities)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Priorité invalide']);
    exit;
}

// Valider le statut
$valid_statuses = ['a_faire', 'en_cours', 'termine'];
if (!in_array($statut, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

// Convertir employee_id (peut être vide pour "non assigné")
$employe_id = ($employe_id === '' || $employe_id === '0') ? null : intval($employe_id);

// Valider la date limite si fournie
$date_limite = (!empty($date_limite)) ? $date_limite : null;

try {
    // Initialiser la session magasin si nécessaire
    if (!isset($_SESSION['shop_id'])) {
        initializeShopSession();
    }
    
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base du magasin. Vérifiez la configuration.');
    }
    
    // Utiliser un user_id par défaut si pas de session
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    
    // Vérifier que l'employé existe s'il est spécifié
    if ($employe_id !== null) {
        $stmt = $shop_pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$employe_id]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Employé inexistant']);
            exit;
        }
    }
    
    // Mettre à jour la tâche
    $stmt = $shop_pdo->prepare("
        UPDATE taches 
        SET titre = ?, 
            description = ?,
            priorite = ?,
            statut = ?,
            employe_id = ?,
            date_limite = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $titre, 
        $description, 
        $priorite, 
        $statut, 
        $employe_id, 
        $date_limite, 
        $task_id
    ]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Tâche mise à jour avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour');
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour de la tâche: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour de la tâche'
    ]);
}
?>
