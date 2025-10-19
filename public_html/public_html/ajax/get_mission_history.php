<?php
/**
 * Fichier AJAX pour récupérer l'historique des missions
 * Retourne un JSON avec les données des missions
 */

// Headers pour JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration de base de données avec le bon chemin
require_once __DIR__ . '/../config/database.php';

// Vérifier les droits d'accès
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier que le shop_id est présent
if (!isset($_SESSION['shop_id'])) {
    echo json_encode(['success' => false, 'message' => 'Shop ID manquant']);
    exit;
}

try {
    // Connexion directe à la base de données du magasin
    $shop_id = $_SESSION['shop_id'];
    $database_name = 'geekboard_' . $shop_id;
    
    $dsn = "mysql:host=localhost;dbname={$database_name};charset=utf8mb4";
    $shop_pdo = new PDO($dsn, 'root', 'Mamanmaman01#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Récupérer l'historique des missions (limitée aux 50 dernières)
    $stmt = $shop_pdo->prepare("
        SELECT 
            um.id,
            um.created_at,
            um.completed_at,
            um.statut,
            um.points_gagnes,
            um.note_admin,
            m.titre as mission_titre,
            m.description as mission_description,
            m.recompense_xp,
            m.recompense_coins,
            u.full_name as user_nom,
            assigned_by.full_name as assigned_by_nom
        FROM user_missions um
        LEFT JOIN missions m ON um.mission_id = m.id
        LEFT JOIN users u ON um.user_id = u.id
        LEFT JOIN users assigned_by ON um.assigned_by = assigned_by.id
        ORDER BY um.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute();
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour l'affichage
    $formatted_missions = [];
    foreach ($missions as $mission) {
        $formatted_missions[] = [
            'id' => $mission['id'],
            'created_at' => $mission['created_at'],
            'completed_at' => $mission['completed_at'],
            'statut' => $mission['statut'],
            'points_gagnes' => $mission['points_gagnes'],
            'note_admin' => $mission['note_admin'],
            'mission_titre' => $mission['mission_titre'],
            'mission_description' => $mission['mission_description'],
            'recompense_xp' => $mission['recompense_xp'],
            'recompense_coins' => $mission['recompense_coins'],
            'user_nom' => $mission['user_nom'],
            'assigned_by_nom' => $mission['assigned_by_nom']
        ];
    }
    
    // Retourner les résultats en JSON
    echo json_encode([
        'success' => true,
        'missions' => $formatted_missions,
        'total' => count($formatted_missions),
        'database' => $database_name
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, retourner l'erreur en JSON
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
    ]);
}
?> 