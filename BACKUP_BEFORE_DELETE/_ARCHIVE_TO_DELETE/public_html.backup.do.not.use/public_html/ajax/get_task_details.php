<?php
// Initialisation de la session (si ce n'est pas déjà fait)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier si l'ID de la tâche est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID de tâche invalide'
    ]);
    exit;
}

$task_id = intval($_GET['id']);

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    // Préparer et exécuter la requête
    $stmt = $shop_pdo->prepare("
        SELECT description, statut as status
        FROM taches 
        WHERE id = ?
    ");
    $stmt->execute([$task_id]);
    
    // Récupérer les résultats
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        // Récupérer les pièces jointes
        $stmt = $shop_pdo->prepare("
            SELECT id, file_name, file_type, file_size, file_path, est_image, date_upload
            FROM tache_attachments 
            WHERE tache_id = ?
            ORDER BY date_upload ASC
        ");
        $stmt->execute([$task_id]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'description' => $task['description'],
            'status' => $task['status'],
            'attachments' => $attachments
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Tâche non trouvée'
        ]);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails de la tâche: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la récupération des détails de la tâche: ' . $e->getMessage()
    ]);
}