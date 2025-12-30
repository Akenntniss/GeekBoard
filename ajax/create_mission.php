<?php
header('Content-Type: application/json');

// Démarrer la session et inclure les fichiers nécessaires
session_start();

// Définir le chemin de base
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

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
        exit;
    }
    
    // Validation des champs obligatoires
    $required_fields = ['titre', 'mission_type_id', 'description', 'objectif_nombre'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Le champ '$field' est obligatoire"]);
            exit;
        }
    }
    
    // Nettoyer et valider les données
    $titre = cleanInput($data['titre']);
    $mission_type_id = (int)$data['mission_type_id'];
    $description = cleanInput($data['description']);
    $objectif_nombre = (int)$data['objectif_nombre'];
    $recompense_euros = isset($data['recompense_euros']) ? (float)$data['recompense_euros'] : 0;
    $recompense_points = isset($data['recompense_points']) ? (int)$data['recompense_points'] : 0;
    $statut = 'active';
    
    // Validation des valeurs
    if ($objectif_nombre <= 0) {
        echo json_encode(['success' => false, 'message' => 'L\'objectif doit être supérieur à 0']);
        exit;
    }
    
    if ($recompense_euros < 0) {
        echo json_encode(['success' => false, 'message' => 'La récompense en euros ne peut pas être négative']);
        exit;
    }
    
    if ($recompense_points < 0) {
        echo json_encode(['success' => false, 'message' => 'Les points XP ne peuvent pas être négatifs']);
        exit;
    }
    
    // Connexion à la base de données
    error_log("Tentative de connexion à la base de données pour shop_id: " . $_SESSION['shop_id']);
    $shop_pdo = getShopDBConnection();
    error_log("Connexion réussie à la base de données");
    
    // Vérifier si la table missions existe, sinon la créer
    error_log("Vérification de l'existence de la table missions");
    $check_table = $shop_pdo->query("SHOW TABLES LIKE 'missions'");
    if ($check_table->rowCount() == 0) {
        error_log("Table missions n'existe pas, création en cours...");
        // Créer la table missions
        $create_table_sql = "
            CREATE TABLE missions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titre VARCHAR(255) NOT NULL,
                description TEXT,
                mission_type_id INT DEFAULT 1,
                objectif_nombre INT NOT NULL DEFAULT 1,
                recompense_euros DECIMAL(10,2) DEFAULT 0.00,
                recompense_points INT DEFAULT 0,
                statut ENUM('active', 'inactive', 'completed') DEFAULT 'active',
                date_debut DATETIME DEFAULT CURRENT_TIMESTAMP,
                date_fin DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $shop_pdo->exec($create_table_sql);
        error_log("Table missions créée avec succès");
    } else {
        error_log("Table missions existe déjà");
        
        // Vérifier si les colonnes date_debut et date_fin existent
        $check_columns = $shop_pdo->query("SHOW COLUMNS FROM missions LIKE 'date_debut'");
        if ($check_columns->rowCount() == 0) {
            error_log("Ajout de la colonne date_debut");
            $shop_pdo->exec("ALTER TABLE missions ADD COLUMN date_debut DATETIME DEFAULT CURRENT_TIMESTAMP");
        }
        
        $check_columns = $shop_pdo->query("SHOW COLUMNS FROM missions LIKE 'date_fin'");
        if ($check_columns->rowCount() == 0) {
            error_log("Ajout de la colonne date_fin");
            $shop_pdo->exec("ALTER TABLE missions ADD COLUMN date_fin DATETIME NULL");
        }
    }
    
    // Insérer la nouvelle mission
    $sql = "INSERT INTO missions (titre, description, mission_type_id, objectif_nombre, recompense_euros, recompense_points, statut, date_debut, date_fin) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))";
    
    error_log("Préparation de la requête d'insertion");
    $stmt = $shop_pdo->prepare($sql);
    
    $params = [
        $titre,
        $description,
        $mission_type_id,
        $objectif_nombre,
        $recompense_euros,
        $recompense_points,
        $statut
    ];
    
    error_log("Paramètres d'insertion: " . json_encode($params));
    $result = $stmt->execute($params);
    
    if ($result) {
        $mission_id = $shop_pdo->lastInsertId();
        
        // === ENVOI NOTIFICATION PUSH ===
        try {
            require_once __DIR__ . '/../includes/NotificationService.php';
            
            // Récupérer tous les employés NON-admin pour les notifier
            // (les admins seront notifiés séparément pour éviter les doublons)
            $stmt_users = $shop_pdo->query("SELECT id FROM users WHERE role NOT IN ('admin', 'manager', 'gerant')");
            $employees = $stmt_users->fetchAll(PDO::FETCH_COLUMN);
            
            $title = "Nouvelle mission disponible";
            $reward_text = $recompense_euros > 0 ? $recompense_euros . "€" : "";
            if ($recompense_points > 0) {
                $reward_text .= ($reward_text ? " + " : "") . $recompense_points . " XP";
            }
            $body = "$titre - Récompense: $reward_text";
            
            // Notifier les employés non-admin
            foreach ($employees as $employee_id) {
                NotificationService::send($employee_id, 'mission_created', $title, $body, [
                    'url' => "/index.php?page=mes_missions",
                    'related_id' => $mission_id,
                    'related_type' => 'mission'
                ]);
            }
            
            // Notifier les admins/managers/gérants (séparément pour éviter les doublons)
            NotificationService::sendToAdmins('mission_created', $title, $body, [
                'url' => "/index.php?page=admin_missions",
                'related_id' => $mission_id,
                'related_type' => 'mission'
            ]);
            
            error_log("NOTIFICATION: Mission creation notification sent for mission #$mission_id to " . count($employees) . " employees + admins");
        } catch (Exception $e) {
            error_log("NOTIFICATION ERROR (mission): " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Mission créée avec succès',
            'mission_id' => $mission_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la mission']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la création de mission: " . $e->getMessage());
    error_log("Code d'erreur PDO: " . $e->getCode());
    error_log("Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la création de mission: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>