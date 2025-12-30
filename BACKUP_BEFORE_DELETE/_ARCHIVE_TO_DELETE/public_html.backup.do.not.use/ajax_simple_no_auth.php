<?php
// API simplifiée SANS authentification pour test du modal
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Paramètres de connexion
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'Mamanmaman01#';

// Récupérer shop_id depuis GET ou POST
$shop_id = $_GET['shop_id'] ?? $_POST['shop_id'] ?? 63; // Défaut à 63 (mkmkmk)

try {
    // Si c'est une requête GET, retourner les utilisateurs
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Connexion à la base principale
        $main_pdo = new PDO("mysql:host=$db_host;dbname=geekboard_general;charset=utf8", $db_user, $db_pass);
        
        // Récupérer le nom de la base du shop
        $stmt = $main_pdo->prepare("SELECT db_name FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop_db = $stmt->fetchColumn();
        
        if (!$shop_db) {
            echo json_encode([
                'success' => false,
                'message' => 'Shop non trouvé',
                'shop_id' => $shop_id
            ]);
            exit;
        }
        
        // Connexion à la base du shop
        $shop_pdo = new PDO("mysql:host=$db_host;dbname=$shop_db;charset=utf8", $db_user, $db_pass);
        
        // Récupérer les utilisateurs
        $stmt = $shop_pdo->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'shop_id' => $shop_id,
            'shop_db' => $shop_db,
            'message' => 'Utilisateurs chargés sans authentification'
        ]);
        exit;
    }
    
    // Si c'est une requête POST, ajouter la tâche
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titre = $_POST['titre'] ?? '';
        $description = $_POST['description'] ?? '';
        $priorite = $_POST['priorite'] ?? '';
        $statut = $_POST['statut'] ?? 'a_faire';
        $employe_id = $_POST['employe_assigne'] ?? null;
        $date_limite = $_POST['date_limite'] ?? null;
        
        // Convertir les chaînes vides en NULL pour éviter les erreurs MySQL
        if (empty($date_limite)) {
            $date_limite = null;
        }
        if (empty($employe_id) || $employe_id === '') {
            $employe_id = null;
        }
        
        // Validation basique
        if (empty($titre)) {
            echo json_encode([
                'success' => false,
                'message' => 'Le titre est requis'
            ]);
            exit;
        }
        
        if (empty($priorite)) {
            echo json_encode([
                'success' => false,
                'message' => 'La priorité est requise'
            ]);
            exit;
        }
        
        // Connexion à la base principale
        $main_pdo = new PDO("mysql:host=$db_host;dbname=geekboard_general;charset=utf8", $db_user, $db_pass);
        
        // Récupérer le nom de la base du shop
        $stmt = $main_pdo->prepare("SELECT db_name FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop_db = $stmt->fetchColumn();
        
        if (!$shop_db) {
            echo json_encode([
                'success' => false,
                'message' => 'Shop non trouvé pour insertion',
                'shop_id' => $shop_id
            ]);
            exit;
        }
        
        // Connexion à la base du shop
        $shop_pdo = new PDO("mysql:host=$db_host;dbname=$shop_db;charset=utf8", $db_user, $db_pass);
        
        // Insérer la tâche avec les bons noms de colonnes
        $stmt = $shop_pdo->prepare("
            INSERT INTO taches (titre, description, priorite, statut, employe_id, date_limite) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $titre,
            $description,
            $priorite,
            $statut,
            $employe_id,
            $date_limite
        ]);
        
        if ($result) {
            $task_id = $shop_pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Tâche ajoutée avec succès (sans authentification)',
                'task_id' => $task_id,
                'data' => [
                    'titre' => $titre,
                    'description' => $description,
                    'priorite' => $priorite,
                    'statut' => $statut,
                    'employe_id' => $employe_id,
                    'shop_id' => $shop_id,
                    'shop_db' => $shop_db
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'insertion dans la base de données'
            ]);
        }
        exit;
    }
    
    // Méthode non supportée
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non supportée'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
