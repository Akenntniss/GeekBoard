<?php
/**
 * API pour le modal tâche - Version multi-database correcte
 * Utilise l'architecture multi-database avec détection automatique du sous-domaine
 */

// Désactiver l'output des erreurs pour éviter de casser le JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Inclure le fichier de configuration database avec getShopDBConnection()
    require_once __DIR__ . '/config/database.php';
    
    // Initialiser la session shop automatiquement via le sous-domaine
    if (function_exists('initializeShopSession')) {
        initializeShopSession();
    }
    
    // Obtenir la connexion à la base de données du shop via l'architecture multi-database
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du shop');
    }
    
    // Si c'est une requête GET, retourner les utilisateurs
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Récupérer les utilisateurs depuis la table users
        $stmt = $shop_pdo->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'message' => 'Utilisateurs chargés via getShopDBConnection()'
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
                'message' => 'Tâche ajoutée avec succès',
                'task_id' => $task_id,
                'data' => [
                    'titre' => $titre,
                    'description' => $description,
                    'priorite' => $priorite,
                    'statut' => $statut,
                    'employe_id' => $employe_id
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
