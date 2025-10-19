<?php
// Test API simplifié pour le modal
header('Content-Type: application/json');

// Démarrage session robuste
if (isset($_COOKIE['PHPSESSID']) || isset($_GET['PHPSESSID'])) {
    if (isset($_GET['PHPSESSID'])) {
        session_id($_GET['PHPSESSID']);
    }
}
session_start();

// Inclusion des fichiers
require_once 'public_html/config/database.php';

// Récupérer shop_id depuis session ou GET
$shop_id = $_SESSION['shop_id'] ?? $_GET['shop_id'] ?? null;

// Vérifier authentification
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté',
        'debug' => [
            'session_id' => session_id(),
            'user_id_in_session' => $_SESSION['user_id'] ?? 'null',
            'shop_id' => $shop_id,
            'session_content' => $_SESSION
        ]
    ]);
    exit;
}

// Si c'est une requête GET, retourner les utilisateurs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Connexion directe à la base du shop
        if ($shop_id) {
            $main_pdo = new PDO("mysql:host=localhost;dbname=geekboard_general;charset=utf8", "root", "Mamanmaman01#");
            $stmt = $main_pdo->prepare("SELECT database_name FROM shops WHERE id = ?");
            $stmt->execute([$shop_id]);
            $shop_db = $stmt->fetchColumn();
            
            if ($shop_db) {
                $shop_pdo = new PDO("mysql:host=localhost;dbname=$shop_db;charset=utf8", "root", "Mamanmaman01#");
                $stmt = $shop_pdo->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'users' => $users,
                    'debug' => [
                        'shop_id' => $shop_id,
                        'shop_db' => $shop_db,
                        'user_count' => count($users)
                    ]
                ]);
                exit;
            }
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Shop non trouvé',
            'debug' => [
                'shop_id' => $shop_id
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur base de données: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Si c'est une requête POST, traiter l'ajout de tâche
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $priorite = $_POST['priorite'] ?? '';
    $statut = $_POST['statut'] ?? 'a_faire';
    
    if (empty($titre) || empty($priorite)) {
        echo json_encode([
            'success' => false,
            'message' => 'Titre et priorité requis'
        ]);
        exit;
    }
    
    try {
        // Connexion directe à la base du shop
        if ($shop_id) {
            $main_pdo = new PDO("mysql:host=localhost;dbname=geekboard_general;charset=utf8", "root", "Mamanmaman01#");
            $stmt = $main_pdo->prepare("SELECT database_name FROM shops WHERE id = ?");
            $stmt->execute([$shop_id]);
            $shop_db = $stmt->fetchColumn();
            
            if ($shop_db) {
                $shop_pdo = new PDO("mysql:host=localhost;dbname=$shop_db;charset=utf8", "root", "Mamanmaman01#");
                
                $stmt = $shop_pdo->prepare("
                    INSERT INTO taches (titre, description, priorite, statut, user_id, date_creation) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $titre,
                    $description,
                    $priorite,
                    $statut,
                    $_SESSION['user_id']
                ]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Tâche ajoutée avec succès',
                        'task_id' => $shop_pdo->lastInsertId()
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erreur lors de l\'insertion'
                    ]);
                }
                exit;
            }
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Shop non trouvé pour insertion'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur insertion: ' . $e->getMessage()
        ]);
    }
}
?>
