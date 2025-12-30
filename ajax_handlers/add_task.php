<?php
// Démarrer la session de manière sécurisée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers de configuration
require_once __DIR__ . '/../config/database.php';

// Fonction pour nettoyer les entrées
function cleanInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Définir le type de contenu JSON
header('Content-Type: application/json');

// Debug: Logger la requête
error_log("=== DEBUT add_task.php ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

try {
    // Vérifier la méthode de requête
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Initialiser la session magasin si nécessaire
    if (!isset($_SESSION['shop_id'])) {
        initializeShopSession();
    }
    
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base du magasin. Vérifiez la configuration.');
    }

    // Récupération et nettoyage des données
    $titre = cleanInput($_POST['titre'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $priorite = cleanInput($_POST['priorite'] ?? '');
    $statut = cleanInput($_POST['statut'] ?? '');
    $date_limite = cleanInput($_POST['date_limite'] ?? '');
    $employe_id = isset($_POST['employe_id']) && $_POST['employe_id'] !== '' ? (int)$_POST['employe_id'] : null;
    
    // Validation des données
    $errors = [];
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire.";
    }
    
    if (empty($description)) {
        $errors[] = "La description est obligatoire.";
    }
    
    if (empty($priorite)) {
        $errors[] = "La priorité est obligatoire.";
    }
    
    if (empty($statut)) {
        $errors[] = "Le statut est obligatoire.";
    }
    
    // Validation des fichiers supprimée
    
    // Si des erreurs, les retourner
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(' ', $errors)
        ]);
        exit;
    }
    
    // Obtenir l'ID utilisateur (avec fallback et vérification)
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    
    // Si pas d'utilisateur en session, essayer de trouver un utilisateur valide
    if (!$user_id) {
        $stmt = $shop_pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        $firstUser = $stmt->fetch();
        $user_id = $firstUser ? $firstUser['id'] : null;
    } else {
        // Vérifier que l'utilisateur existe
        $stmt = $shop_pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            // L'utilisateur n'existe pas, prendre le premier disponible
            $stmt = $shop_pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
            $firstUser = $stmt->fetch();
            $user_id = $firstUser ? $firstUser['id'] : null;
        }
    }
    
    if (!$user_id) {
        throw new Exception('Aucun utilisateur valide trouvé dans le système');
    }
    
    // Vérifier que l'employé assigné existe s'il est spécifié
    if ($employe_id) {
        $stmt = $shop_pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$employe_id]);
        if (!$stmt->fetch()) {
            $employe_id = null; // Réinitialiser si l'employé n'existe pas
        }
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    try {
        // Insérer la tâche
        $stmt = $shop_pdo->prepare("
            INSERT INTO taches (titre, description, priorite, statut, date_limite, employe_id, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $titre, 
            $description, 
            $priorite, 
            $statut, 
            $date_limite ?: null, 
            $employe_id,
            $user_id
        ]);
        
        $tacheId = $shop_pdo->lastInsertId();
        
        // Traitement des fichiers supprimé
        if (false) {
            // Créer le dossier d'upload s'il n'existe pas
            $baseUploadDir = __DIR__ . '/../uploads/';
            $taskUploadDir = $baseUploadDir . 'taches/';
            $uploadDir = $taskUploadDir . $tacheId . '/';
            
            // Créer les dossiers avec permissions appropriées
            if (!is_dir($baseUploadDir)) {
                mkdir($baseUploadDir, 0755, true);
                chmod($baseUploadDir, 0755);
            }
            if (!is_dir($taskUploadDir)) {
                mkdir($taskUploadDir, 0755, true);
                chmod($taskUploadDir, 0755);
            }
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                chmod($uploadDir, 0755);
            }
            
            foreach ($uploadedFiles as $file) {
                // Générer un nom de fichier unique
                $uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
                $filePath = $uploadDir . $uniqueName;
                $relativeFilePath = 'uploads/taches/' . $tacheId . '/' . $uniqueName;
                
                // Déplacer le fichier
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Définir les permissions du fichier
                    chmod($filePath, 0644);
                    
                    // Déterminer si c'est une image
                    $isImage = in_array($file['type'], ['jpg', 'jpeg', 'png', 'gif']) ? 1 : 0;
                    
                    // Vérifier si la table tache_attachments existe
                    try {
                        // Insérer dans la base de données
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO tache_attachments 
                            (tache_id, file_path, file_name, file_type, file_size, est_image, uploaded_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $tacheId,
                            $relativeFilePath,
                            $file['name'],
                            $file['type'],
                            $file['size'],
                            $isImage,
                            $user_id
                        ]);
                    } catch (PDOException $e) {
                        // Si la table n'existe pas, continuer sans erreur
                        error_log("Table tache_attachments n'existe pas: " . $e->getMessage());
                    }
                } else {
                    error_log("Erreur move_uploaded_file: " . $file['tmp_name'] . " vers " . $filePath);
                    error_log("Permissions du dossier: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
                }
            }
        }
        
        // Confirmer la transaction
        $shop_pdo->commit();
        
        // === ENVOI NOTIFICATION PUSH ===
        try {
            require_once __DIR__ . '/../includes/NotificationService.php';
            NotificationService::notifyTaskCreated($tacheId, $titre, $employe_id);
            error_log("NOTIFICATION: Task creation notification sent for task #$tacheId");
        } catch (Exception $e) {
            error_log("NOTIFICATION ERROR (add_task): " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Tâche ajoutée avec succès !',
            'task_id' => $tacheId
        ]);
        
    } catch (Exception $e) {
        $shop_pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Erreur dans add_task.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // S'assurer qu'on envoie toujours du JSON valide
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout de la tâche: ' . $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>
