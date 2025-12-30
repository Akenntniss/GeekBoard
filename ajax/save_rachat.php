<?php
// Désactiver l'affichage des erreurs pour éviter de corrompre le JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
// Continuer à logger les erreurs dans le fichier de log
error_reporting(E_ALL);

// S'assurer que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers requis
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Debug: vérifier que les fonctions de base de données sont disponibles
$debug_functions = [];
$debug_functions[] = "Fonction getShopDBConnection disponible: " . (function_exists('getShopDBConnection') ? 'OUI' : 'NON');
$debug_functions[] = "Fonction initializeShopSession disponible: " . (function_exists('initializeShopSession') ? 'OUI' : 'NON');

// Initialiser la session magasin pour les APIs directes
if (function_exists('initializeShopSession')) {
    initializeShopSession();
} else {
    // Détection manuelle du sous-domaine si initializeShopSession n'existe pas
    if (!isset($_SESSION['shop_id'])) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'mkmkmk.') === 0) {
            $_SESSION['shop_id'] = 1; // ID pour mkmkmk
        } elseif (strpos($host, 'cannesphones.') === 0) {
            $_SESSION['shop_id'] = 2; // ID pour cannesphones
        } else {
            $_SESSION['shop_id'] = 1; // Par défaut mkmkmk
        }
    }
}

// Définir le type de contenu
header('Content-Type: application/json');

// Gestionnaire d'erreur global pour éviter de corrompre le JSON
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error [$severity]: $message in $file on line $line");
    return true; // Empêche l'affichage de l'erreur
});

// Gestionnaire d'exception non capturée
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    echo json_encode([
        'error' => 'Une erreur système s\'est produite',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'debug' => isset($GLOBALS['debug_info']) ? $GLOBALS['debug_info'] : []
    ]);
    exit;
});

// Vérifier le mode debug
$debug_mode = isset($_POST['debug_mode']) && $_POST['debug_mode'] == '1';

// Variables pour journaliser les étapes
$debug_info = [];
$GLOBALS['debug_info'] = &$debug_info;
$debug_info[] = "Démarrage du traitement - " . date('Y-m-d H:i:s');

// Ajouter les informations de debug des fonctions
if (isset($debug_functions)) {
    $debug_info = array_merge($debug_info, $debug_functions);
}
$debug_info[] = "Session utilisateur: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Non définie');
$debug_info[] = "Rôle utilisateur: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'Non défini');
$debug_info[] = "Session shop_id: " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'Non défini');
$debug_info[] = "Host actuel: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini');

// Debug spécifique pour les photos base64
$debug_info[] = "photo_identite_data présent: " . (isset($_POST['photo_identite_data']) ? 'OUI (' . strlen($_POST['photo_identite_data']) . ' chars)' : 'NON');
$debug_info[] = "photo_appareil_data présent: " . (isset($_POST['photo_appareil_data']) ? 'OUI (' . strlen($_POST['photo_appareil_data']) . ' chars)' : 'NON');
$debug_info[] = "photo_client_data présent: " . (isset($_POST['photo_client_data']) ? 'OUI (' . strlen($_POST['photo_client_data']) . ' chars)' : 'NON');

// Fonction pour journaliser les informations de débogage
function debug_log($message, $data = null) {
    global $debug_mode;
    if ($debug_mode) {
        error_log($message . ($data !== null ? ': ' . json_encode($data) : ''));
    }
}

try {
    // Log des données reçues pour le debug
    $debug_info[] = "POST reçu: " . json_encode($_POST);
    $debug_info[] = "FILES reçu: " . json_encode($_FILES);
    
    // Valider les données requises avec une vérification assouplie
    $required_fields = [];
    
    if (!isset($_POST['client_id']) || empty($_POST['client_id'])) {
        $required_fields[] = 'client_id';
    }
    
    if (!isset($_POST['type_appareil']) || empty($_POST['type_appareil'])) {
        $required_fields[] = 'type_appareil';
    }
    
    if (!isset($_POST['signature']) || empty($_POST['signature'])) {
        $required_fields[] = 'signature';
    }
    
    if (count($required_fields) > 0) {
        $debug_info[] = "Champs requis manquants: " . implode(', ', $required_fields);
        throw new Exception('Les champs suivants sont obligatoires: ' . implode(', ', $required_fields));
    }
    
    // Récupérer et nettoyer les données
    $client_id = (int)$_POST['client_id'];
    $type_appareil = cleanInput($_POST['type_appareil']);
    $modele = isset($_POST['modele']) ? cleanInput($_POST['modele']) : '';
    $sin = isset($_POST['sin']) ? cleanInput($_POST['sin']) : '';
    $prix = isset($_POST['prix_rachat']) && is_numeric($_POST['prix_rachat']) ? (float)$_POST['prix_rachat'] : 0;
    
    // Debug pour le prix
    $debug_info[] = "Prix reçu (prix_rachat): " . ($_POST['prix_rachat'] ?? 'non défini');
    $debug_info[] = "Prix traité: " . $prix;
    $fonctionnel = isset($_POST['fonctionnel']) ? (int)$_POST['fonctionnel'] : 1;
    
    // Obtenir la connexion à la base de données du magasin AVANT la vérification du client
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        $debug_info[] = "ERREUR: Connexion PDO null - shop_id: " . ($_SESSION['shop_id'] ?? 'non défini');
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }
    
    $debug_info[] = "Connexion PDO établie avec succès";
    
    // Vérifier que le client existe
    $debug_info[] = "Vérification de l'existence du client ID: " . $client_id;
    $check_client = $pdo->prepare("SELECT id FROM clients WHERE id = ?");
    $check_client->execute([$client_id]);
    
    if ($check_client->rowCount() === 0) {
        $debug_info[] = "Client ID " . $client_id . " n'existe pas dans la table clients";
        throw new Exception("Le client sélectionné n'existe pas. Veuillez sélectionner un client valide.");
    }
    
    $debug_info[] = "Client ID " . $client_id . " existe et est valide";
    
    // Vérifier la signature
    $signature_data = $_POST['signature'];
    if (empty($signature_data) || strpos($signature_data, 'data:image') !== 0) {
        $debug_info[] = "Signature invalide: " . substr($signature_data, 0, 30) . "...";
        throw new Exception('La signature est invalide.');
    }
    
    $debug_info[] = "Données validées avec succès";
    
    // Créer le dossier de destination si nécessaire
    $upload_dir = __DIR__ . '/../assets/images/rachat/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $debug_info[] = "Échec de création du dossier: " . $upload_dir;
            throw new Exception('Impossible de créer le dossier de destination.');
        }
    }
    
    $debug_info[] = "Dossier de destination vérifié";
    
    // Générer des noms de fichiers uniques
    $photo_identite_name = null;
    $photo_appareil_name = null;
    $signature_name = 'signature_' . time() . '_' . uniqid() . '.png';
    
    // Traiter la signature (convertir de base64 à fichier)
    $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
    $signature_data = str_replace(' ', '+', $signature_data);
    $signature_binary = base64_decode($signature_data);
    
    if ($signature_binary === false) {
        $debug_info[] = "Décodage base64 de la signature échoué";
        throw new Exception('Impossible de décoder la signature.');
    }
    
    $signature_path = $upload_dir . $signature_name;
    if (file_put_contents($signature_path, $signature_binary) === false) {
        $debug_info[] = "Échec d'écriture du fichier signature: " . $signature_path;
        throw new Exception('Impossible d\'enregistrer la signature.');
    }
    
    $debug_info[] = "Signature enregistrée: " . $signature_name;
    
    // Traiter la photo d'identité
    if (isset($_FILES['photo_identite']) && $_FILES['photo_identite']['error'] === UPLOAD_ERR_OK) {
        $photo_identite_name = 'identite_' . time() . '_' . uniqid() . '.jpg';
        $photo_identite_path = $upload_dir . $photo_identite_name;
        
        if (!move_uploaded_file($_FILES['photo_identite']['tmp_name'], $photo_identite_path)) {
            $debug_info[] = "Échec de déplacement du fichier photo identité";
            throw new Exception('Impossible d\'enregistrer la photo d\'identité.');
        }
        
        $debug_info[] = "Photo d'identité enregistrée: " . $photo_identite_name;
    } elseif (isset($_POST['photo_identite_data']) && !empty($_POST['photo_identite_data'])) {
        // Alternative: traiter l'image base64 de la webcam
        $photo_data = $_POST['photo_identite_data'];
        $photo_data = str_replace('data:image/jpeg;base64,', '', $photo_data);
        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        if ($photo_binary === false) {
            $debug_info[] = "Décodage base64 de la photo identité échoué";
            throw new Exception('Impossible de décoder la photo d\'identité.');
        }
        
        $photo_identite_name = 'identite_' . time() . '_' . uniqid() . '.jpg';
        $photo_identite_path = $upload_dir . $photo_identite_name;
        
        if (file_put_contents($photo_identite_path, $photo_binary) === false) {
            $debug_info[] = "Échec d'écriture du fichier photo identité";
            throw new Exception('Impossible d\'enregistrer la photo d\'identité.');
        }
        
        $debug_info[] = "Photo d'identité webcam enregistrée: " . $photo_identite_name;
    }
    
    // Traiter la photo de l'appareil
    if (isset($_FILES['photo_appareil']) && $_FILES['photo_appareil']['error'] === UPLOAD_ERR_OK) {
        $photo_appareil_name = 'appareil_' . time() . '_' . uniqid() . '.jpg';
        $photo_appareil_path = $upload_dir . $photo_appareil_name;
        
        if (!move_uploaded_file($_FILES['photo_appareil']['tmp_name'], $photo_appareil_path)) {
            $debug_info[] = "Échec de déplacement du fichier photo appareil";
            throw new Exception('Impossible d\'enregistrer la photo de l\'appareil.');
        }
        
        $debug_info[] = "Photo d'appareil enregistrée: " . $photo_appareil_name;
    } elseif (isset($_POST['photo_appareil_data']) && !empty($_POST['photo_appareil_data'])) {
        // Alternative: traiter l'image base64
        $photo_data = $_POST['photo_appareil_data'];
        $photo_data = str_replace('data:image/jpeg;base64,', '', $photo_data);
        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        if ($photo_binary === false) {
            $debug_info[] = "Décodage base64 de la photo appareil échoué";
            throw new Exception('Impossible de décoder la photo de l\'appareil.');
        }
        
        $photo_appareil_name = 'appareil_' . time() . '_' . uniqid() . '.jpg';
        $photo_appareil_path = $upload_dir . $photo_appareil_name;
        
        if (file_put_contents($photo_appareil_path, $photo_binary) === false) {
            $debug_info[] = "Échec d'écriture du fichier photo appareil";
            throw new Exception('Impossible d\'enregistrer la photo de l\'appareil.');
        }
        
        $debug_info[] = "Photo d'appareil webcam enregistrée: " . $photo_appareil_name;
    }
    
    // Traiter la photo du client (fichier ou base64)
    $client_photo_name = null;
    if (isset($_FILES['photo_client']) && $_FILES['photo_client']['error'] === UPLOAD_ERR_OK) {
        $client_photo_name = 'client_' . time() . '_' . uniqid() . '.jpg';
        $client_photo_path = $upload_dir . $client_photo_name;
        
        if (!move_uploaded_file($_FILES['photo_client']['tmp_name'], $client_photo_path)) {
            $debug_info[] = "Échec de déplacement du fichier photo client";
            throw new Exception('Impossible d\'enregistrer la photo du client.');
        }
        
        $debug_info[] = "Photo client enregistrée (fichier): " . $client_photo_name;
    } elseif (isset($_POST['photo_client_data']) && !empty($_POST['photo_client_data'])) {
        // Traiter l'image base64 de la caméra
        $photo_data = $_POST['photo_client_data'];
        $photo_data = str_replace('data:image/jpeg;base64,', '', $photo_data);
        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        if ($photo_binary === false) {
            $debug_info[] = "Décodage base64 de la photo client échoué";
            throw new Exception('Impossible de décoder la photo du client.');
        }
        
        $client_photo_name = 'client_' . time() . '_' . uniqid() . '.jpg';
        $client_photo_path = $upload_dir . $client_photo_name;
        
        if (file_put_contents($client_photo_path, $photo_binary) === false) {
            $debug_info[] = "Échec d'écriture du fichier photo client";
            throw new Exception('Impossible d\'enregistrer la photo du client.');
        }
        
        $debug_info[] = "Photo client webcam enregistrée: " . $client_photo_name;
    } elseif (isset($_POST['client_photo_data']) && !empty($_POST['client_photo_data'])) {
        $photo_data = $_POST['client_photo_data'];
        $photo_data = str_replace('data:image/jpeg;base64,', '', $photo_data);
        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        if ($photo_binary === false) {
            $debug_info[] = "Décodage base64 de la photo client échoué";
            throw new Exception('Impossible de décoder la photo du client.');
        }
        
        $client_photo_name = 'client_' . time() . '_' . uniqid() . '.jpg';
        $client_photo_path = $upload_dir . $client_photo_name;
        
        if (file_put_contents($client_photo_path, $photo_binary) === false) {
            $debug_info[] = "Échec d'écriture du fichier photo client";
            throw new Exception('Impossible d\'enregistrer la photo du client.');
        }
        
        $debug_info[] = "Photo du client enregistrée: " . $client_photo_name;
    }
    
    // La connexion PDO a déjà été établie plus haut
    
    // Vérifier si la table rachat_appareils existe
    try {
        $check_table = $pdo->query("SHOW TABLES LIKE 'rachat_appareils'");
        if (!$check_table || $check_table->rowCount() === 0) {
            $debug_info[] = "Table rachat_appareils n'existe pas - création nécessaire";
            
            // Créer la table si elle n'existe pas
            $create_table_sql = "
                CREATE TABLE IF NOT EXISTS rachat_appareils (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    client_id INT NOT NULL,
                    type_appareil VARCHAR(100) NOT NULL,
                    modele VARCHAR(100),
                    sin VARCHAR(50),
                    fonctionnel TINYINT(1) DEFAULT 1,
                    prix DECIMAL(10,2) DEFAULT 0,
                    photo_identite VARCHAR(255),
                    photo_appareil VARCHAR(255),
                    signature VARCHAR(255) NOT NULL,
                    date_rachat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    client_photo VARCHAR(255),
                    INDEX idx_client_id (client_id),
                    INDEX idx_date_rachat (date_rachat)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            if ($pdo->exec($create_table_sql) === false) {
                $debug_info[] = "Échec de création de la table rachat_appareils";
                throw new Exception("Impossible de créer la table rachat_appareils");
            }
            
            $debug_info[] = "Table rachat_appareils créée avec succès";
        } else {
            $debug_info[] = "Table rachat_appareils existe déjà";
        }
    } catch (Exception $e) {
        $debug_info[] = "Erreur lors de la vérification/création de la table: " . $e->getMessage();
        throw new Exception("Erreur de base de données: " . $e->getMessage());
    }
    
    // Insérer l'enregistrement dans la base de données avec colonne pour la photo client
    // Vérifier d'abord si la colonne client_photo existe dans la table
    try {
        $check_column = $pdo->query("SHOW COLUMNS FROM rachat_appareils LIKE 'client_photo'");
        $column_exists = ($check_column && $check_column->rowCount() > 0);
        
        // Si la colonne n'existe pas, l'ajouter
        if (!$column_exists) {
            $debug_info[] = "Colonne client_photo manquante - ajout en cours";
            $pdo->exec("ALTER TABLE rachat_appareils ADD COLUMN client_photo VARCHAR(255) AFTER signature");
            $column_exists = true;
            $debug_info[] = "Colonne client_photo ajoutée avec succès";
        } else {
            $debug_info[] = "Colonne client_photo existe déjà";
        }
    } catch (Exception $e) {
        $column_exists = false;
        $debug_info[] = "Erreur lors de la vérification/ajout de la colonne client_photo: " . $e->getMessage();
    }
    
    // Préparer la requête SQL en fonction de l'existence de la colonne
    if ($column_exists) {
        $stmt = $pdo->prepare("
            INSERT INTO rachat_appareils (
                client_id, type_appareil, modele, sin, fonctionnel, prix,
                photo_identite, photo_appareil, signature, date_rachat, client_photo
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?
            )
        ");
        
        $debug_info[] = "Requête SQL préparée avec colonne client_photo";
        
        $result = $stmt->execute([
            $client_id, 
            $type_appareil, 
            $modele, 
            $sin, 
            $fonctionnel, 
            $prix,
            $photo_identite_name, 
            $photo_appareil_name, 
            $signature_name,
            $client_photo_name
        ]);
    } else {
        // Si la colonne n'existe pas, on utilise la requête sans cette colonne
        $stmt = $pdo->prepare("
            INSERT INTO rachat_appareils (
                client_id, type_appareil, modele, sin, fonctionnel, prix,
                photo_identite, photo_appareil, signature, date_rachat
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        
        $debug_info[] = "Requête SQL préparée sans colonne client_photo";
        
        $result = $stmt->execute([
            $client_id, 
            $type_appareil, 
            $modele, 
            $sin, 
            $fonctionnel, 
            $prix,
            $photo_identite_name, 
            $photo_appareil_name, 
            $signature_name
        ]);
        
        // Sauvegarder quand même la photo client même si elle n'est pas liée en DB
        if ($client_photo_name) {
            $debug_info[] = "Photo client sauvegardée mais non liée en base de données (colonne manquante)";
        }
    }
    
    if (!$result) {
        $debug_info[] = "Échec d'exécution de la requête SQL: " . implode(', ', $stmt->errorInfo());
        throw new Exception('Erreur lors de l\'enregistrement du rachat dans la base de données.');
    }
    
    $rachat_id = $pdo->lastInsertId();
    $debug_info[] = "Rachat enregistré avec succès avec l'ID: " . $rachat_id;
    
    // Envoi notification push
    try {
        require_once __DIR__ . '/../includes/NotificationService.php';
        $marque = isset($_POST['marque']) ? cleanInput($_POST['marque']) : '';
        NotificationService::notifyRachatCreated($rachat_id, $type_appareil, $marque);
        $debug_info[] = "Notification push envoyée pour le rachat";
    } catch (Exception $e) {
        error_log("NOTIFICATION ERROR (save_rachat): " . $e->getMessage());
        $debug_info[] = "Erreur notification: " . $e->getMessage();
    }
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'rachat_id' => $rachat_id,
        'message' => 'Rachat enregistré avec succès.',
        'debug' => $debug_mode ? $debug_info : null
    ]);
    
} catch (Exception $e) {
    // Log l'erreur complète
    error_log("Erreur de rachat: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
    
    $debug_info[] = "ERREUR FINALE: " . $e->getMessage();
    $debug_info[] = "Fichier: " . $e->getFile() . " ligne " . $e->getLine();
    $debug_info[] = "Trace: " . $e->getTraceAsString();
    
    // Envoyer une réponse d'erreur
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Erreur lors de l\'enregistrement du rachat',
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'debug' => $debug_info
    ]);
}
?> 