<?php
session_start();

// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Initialiser une session si pas active (pour test)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
}

// S'assurer que la session shop est initialisée
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 63; // mkmkmk
}

header('Content-Type: application/json');

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }

    $user_id = $_GET['user_id'] ?? null;

    if (!$user_id) {
        throw new Exception('ID utilisateur manquant');
    }

    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();

    // Récupérer les informations de base de l'employé
    $stmt = $shop_pdo->prepare("
        SELECT 
            user_id,
            solde_euros,
            solde_points,
            total_gagne_euros,
            total_gagne_points
        FROM user_cagnotte
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Employé non trouvé');
    }

    // Créer la table historique_gains si elle n'existe pas
    $check_table = $shop_pdo->query("SHOW TABLES LIKE 'historique_gains'");
    if ($check_table->rowCount() == 0) {
        $shop_pdo->exec("
            CREATE TABLE historique_gains (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                mission_id INT NULL,
                user_mission_id INT NULL,
                montant_euros DECIMAL(10,2) DEFAULT 0.00,
                points_attribues INT DEFAULT 0,
                type_gain ENUM('mission', 'bonus', 'retrait') DEFAULT 'mission',
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Insérer des données de test pour l'historique
        $shop_pdo->exec("
            INSERT INTO historique_gains (user_id, mission_id, montant_euros, points_attribues, type_gain, description, created_at) VALUES
            (1, 1, 25.00, 150, 'mission', 'Mission: Diagnostic Trottinettes', '2024-11-08 10:30:00'),
            (1, 2, 15.50, 100, 'mission', 'Mission: Réparation Écrans', '2024-11-09 14:20:00'),
            (1, NULL, 10.00, 0, 'bonus', 'Bonus performance mensuelle', '2024-11-10 09:00:00'),
            (2, 1, 20.00, 120, 'mission', 'Mission: Diagnostic Trottinettes', '2024-11-07 16:45:00'),
            (2, 3, 18.75, 95, 'mission', 'Mission: Vente Smartphones', '2024-11-09 11:30:00'),
            (3, 1, 30.00, 180, 'mission', 'Mission: Diagnostic Trottinettes', '2024-11-06 13:15:00'),
            (3, 2, 22.50, 140, 'mission', 'Mission: Réparation Écrans', '2024-11-08 15:45:00'),
            (3, NULL, 15.00, 50, 'bonus', 'Bonus qualité service', '2024-11-09 17:00:00'),
            (6, 1, 35.00, 200, 'mission', 'Mission: Diagnostic Trottinettes', '2024-11-05 12:00:00'),
            (6, 2, 28.00, 160, 'mission', 'Mission: Réparation Écrans', '2024-11-07 10:30:00'),
            (6, 3, 25.50, 130, 'mission', 'Mission: Vente Smartphones', '2024-11-08 14:15:00')
        ");
    }

    // Récupérer l'historique des gains
    $stmt = $shop_pdo->prepare("
        SELECT 
            hg.id,
            hg.montant_euros,
            hg.points_attribues,
            hg.type_gain,
            hg.description,
            hg.created_at,
            m.titre as mission_titre
        FROM historique_gains hg
        LEFT JOIN missions m ON hg.mission_id = m.id
        WHERE hg.user_id = ?
        ORDER BY hg.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les missions de l'employé
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.id,
            m.titre,
            m.objectif_nombre,
            um.progression,
            um.statut,
            um.date_inscription,
            um.date_completion
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        WHERE um.user_id = ?
        ORDER BY um.date_inscription DESC
    ");
    $stmt->execute([$user_id]);
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Réponse de succès
    echo json_encode([
        'success' => true,
        'data' => [
            'employee' => $employee,
            'history' => $history,
            'missions' => $missions
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération de l'historique employé: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération de l'historique employé: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>

