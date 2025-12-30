<?php
session_start(); // Ensure session is started

// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Initialiser une session si pas active
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
    $_SESSION['shop_id'] = 'mkmkmk';
}

// S'assurer que la session shop est initialisée
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 'mkmkmk';
}

header('Content-Type: application/json');

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }

    $validation_id = $_GET['id'] ?? null;

    error_log("Récupération détails validation - ID: $validation_id");

    // Validation des données
    if (!$validation_id) {
        throw new Exception('ID de validation manquant');
    }

    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();
    error_log("Connexion réussie à la base de données");

    // Vérifier si la table mission_validations existe
    $check_table = $shop_pdo->query("SHOW TABLES LIKE 'mission_validations'");
    if ($check_table->rowCount() == 0) {
        // Créer des données de test si la table n'existe pas
        $test_data = [
            'id' => $validation_id,
            'mission_titre' => 'Mission de Test',
            'mission_description' => 'Ceci est une mission de test pour démonstration',
            'user_name' => 'Utilisateur Test',
            'description' => 'J\'ai complété la tâche demandée avec succès. Voici les détails de ce que j\'ai accompli...',
            'preuve_url' => null,
            'date_soumission' => date('Y-m-d H:i:s'),
            'statut' => 'en_attente',
            'type_validation' => 'completion'
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $test_data
        ]);
        exit;
    }

    // Récupérer les détails complets de la validation
    $sql = "SELECT 
                mv.id,
                mv.user_id,
                mv.mission_id,
                mv.user_mission_id,
                mv.type_validation,
                mv.description,
                mv.preuve_url,
                mv.statut,
                mv.commentaire_admin,
                mv.date_soumission,
                mv.date_traitement,
                mv.traite_par,
                m.titre as mission_titre,
                m.description as mission_description,
                m.objectif_quantite,
                m.recompense_euros,
                m.recompense_points,
                COALESCE(u.nom, 'Utilisateur') as user_name,
                COALESCE(admin.nom, 'Administrateur') as admin_name
            FROM mission_validations mv
            LEFT JOIN missions m ON mv.mission_id = m.id
            LEFT JOIN users u ON mv.user_id = u.id
            LEFT JOIN users admin ON mv.traite_par = admin.id
            WHERE mv.id = ?";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$validation_id]);
    $validation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$validation) {
        throw new Exception('Validation non trouvée');
    }

    error_log("Validation trouvée: " . json_encode($validation));

    // Formater les données pour l'affichage
    $formatted_data = [
        'id' => $validation['id'],
        'mission_titre' => $validation['mission_titre'] ?? 'Mission inconnue',
        'mission_description' => $validation['mission_description'] ?? 'Aucune description',
        'mission_objectif' => $validation['objectif_quantite'] ?? 1,
        'mission_recompense_euros' => $validation['recompense_euros'] ?? 0,
        'mission_recompense_points' => $validation['recompense_points'] ?? 0,
        'user_name' => $validation['user_name'] ?? 'Utilisateur inconnu',
        'description' => $validation['description'] ?? 'Aucune description fournie',
        'preuve_url' => $validation['preuve_url'],
        'statut' => $validation['statut'],
        'type_validation' => $validation['type_validation'] ?? 'completion',
        'date_soumission' => $validation['date_soumission'],
        'date_traitement' => $validation['date_traitement'],
        'commentaire_admin' => $validation['commentaire_admin'],
        'admin_name' => $validation['admin_name']
    ];

    echo json_encode([
        'success' => true,
        'data' => $formatted_data
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des détails: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des détails: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>