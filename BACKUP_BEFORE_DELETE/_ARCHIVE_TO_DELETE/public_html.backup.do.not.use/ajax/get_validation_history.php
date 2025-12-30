<?php
/**
 * Fichier AJAX pour récupérer l'historique des validations de missions
 * Retourne un JSON avec les données des validations
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
    
    // Récupérer l'historique des validations (limitée aux 50 dernières)
    $stmt = $shop_pdo->prepare("
        SELECT 
            mv.id,
            mv.description,
            mv.statut,
            mv.date_soumission,
            mv.date_validation,
            mv.commentaire_admin,
            mv.tache_numero,
            m.titre as mission_titre,
            u.full_name as user_nom,
            validator.full_name as validateur_nom
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN missions m ON um.mission_id = m.id
        LEFT JOIN users u ON um.user_id = u.id
        LEFT JOIN users validator ON mv.validee_par = validator.id
        ORDER BY mv.date_soumission DESC
        LIMIT 50
    ");
    
    $stmt->execute();
    $validations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour l'affichage
    $formatted_validations = [];
    foreach ($validations as $validation) {
        $formatted_validations[] = [
            'id' => $validation['id'],
            'description' => $validation['description'],
            'statut' => $validation['statut'],
            'date_soumission' => $validation['date_soumission'],
            'date_validation' => $validation['date_validation'],
            'commentaire_admin' => $validation['commentaire_admin'],
            'tache_numero' => $validation['tache_numero'],
            'mission_titre' => $validation['mission_titre'],
            'user_nom' => $validation['user_nom'],
            'validateur_nom' => $validation['validateur_nom']
        ];
    }
    
    // Retourner les résultats en JSON
    echo json_encode([
        'success' => true,
        'validations' => $formatted_validations,
        'total' => count($formatted_validations),
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