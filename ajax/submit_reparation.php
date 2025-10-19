<?php
/**
 * Traitement direct de soumission de réparation
 * Contourne le système d'authentification principal
 */

// Configuration d'erreur PHP pour debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction de gestion d'erreur globale
set_error_handler(function($severity, $message, $file, $line) {
    // Si c'est une erreur de mkdir pour le debug, on la log mais on ne l'arrête pas
    if (strpos($message, 'mkdir()') !== false && strpos($file, 'submit_reparation.php') !== false) {
        error_log("⚠️ ERREUR DEBUG (non fatale): $message dans " . basename($file) . ":$line");
        return true; // Continue l'exécution
    }
    
    // Pour les autres erreurs, comportement normal
    error_log("❌ ERREUR PHP: $message dans $file:$line");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur PHP: ' . $message,
        'debug' => [
            'file' => basename($file),
            'line' => $line,
            'severity' => $severity
        ]
    ]);
    exit;
});

// Fonction de gestion d'exception globale
set_exception_handler(function($exception) {
    error_log("❌ EXCEPTION PHP: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Exception PHP: ' . $exception->getMessage(),
        'debug' => [
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
            'trace' => array_slice($exception->getTrace(), 0, 3) // Limiter la trace
        ]
    ]);
    exit;
});

// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Forcer l'initialisation de la session magasin
initializeShopSession();

// Log immédiat
error_log("🚀 SUBMIT_REPARATION.PHP - Début traitement");
error_log("🚀 METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNDEFINED'));
error_log("🚀 POST count: " . count($_POST));
error_log("🚀 SESSION shop_id: " . ($_SESSION['shop_id'] ?? 'NON DÉFINI'));
error_log("🚀 REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NON DÉFINI'));

// Ajouter les headers pour éviter les problèmes CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Vérifier que c'est bien une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier qu'on a une session magasin
if (!isset($_SESSION['shop_id'])) {
    error_log("❌ SESSION shop_id manquante");
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Session magasin manquante',
        'debug' => [
            'session_keys' => array_keys($_SESSION ?? []),
            'post_keys' => array_keys($_POST ?? [])
        ]
    ]);
    exit;
}

// Récupérer la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    error_log("❌ Connexion base de données échouée");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Connexion base de données échouée',
        'debug' => [
            'shop_id' => $_SESSION['shop_id'] ?? 'NULL',
            'function_exists' => function_exists('getShopDBConnection')
        ]
    ]);
    exit;
}

try {
    // Récupération et nettoyage des données POST
    $client_id = (int)$_POST['client_id'];
    $type_appareil = cleanInput($_POST['type_appareil']);
    $modele = cleanInput($_POST['modele']);
    $description_probleme = cleanInput($_POST['description_probleme']);
    $a_mot_de_passe = isset($_POST['a_mot_de_passe']) ? cleanInput($_POST['a_mot_de_passe']) : 'non';
    $mot_de_passe = ($a_mot_de_passe === 'oui') ? cleanInput($_POST['mot_de_passe']) : '';
    $prix_reparation = (float)$_POST['prix_reparation'];
    $employe_id = isset($_POST['employe_id']) && !empty($_POST['employe_id']) ? (int)$_POST['employe_id'] : null;
    
    // Gestion du statut
    $statutForDB = 'nouvelle_intervention';
    $categorie_id = 1; // Catégorie par défaut
    $notes_techniques = isset($_POST['notes_techniques']) ? cleanInput($_POST['notes_techniques']) : '';

    // Gestion de la photo (avec gestion d'erreurs de permissions)
    $photo_path = null;
    $disable_photo_upload = false; // Mettre à true pour désactiver complètement l'upload de photos
    
    if (!$disable_photo_upload && isset($_POST['photo_appareil']) && !empty($_POST['photo_appareil'])) {
        $photo_data = $_POST['photo_appareil'];
        if (strpos($photo_data, 'data:image') === 0) {
            $parts = explode(',', $photo_data);
            if (count($parts) > 1) {
                $decoded_data = base64_decode($parts[1]);
                if ($decoded_data !== false) {
                    $upload_dir = '../assets/images/reparations/';
                    
                    // Vérifier/créer le dossier avec gestion d'erreurs
                    if (!is_dir($upload_dir)) {
                        error_log("📁 Tentative de création du dossier: $upload_dir");
                        if (!@mkdir($upload_dir, 0755, true)) {
                            error_log("❌ Échec création dossier: $upload_dir");
                            // Essayer un dossier alternatif
                            $upload_dir = '../assets/temp/';
                            if (!is_dir($upload_dir)) {
                                @mkdir($upload_dir, 0755, true);
                            }
                        }
                    }
                    
                    // Vérifier les permissions d'écriture
                    if (is_writable($upload_dir)) {
                        $photo_name = uniqid('repair_') . '.jpg';
                        $photo_path_abs = $upload_dir . $photo_name;
                        $photo_path = str_replace('../', '', $upload_dir) . $photo_name;
                        
                        error_log("📸 Tentative sauvegarde photo: $photo_path_abs");
                        if (@file_put_contents($photo_path_abs, $decoded_data) === false) {
                            error_log("❌ Échec sauvegarde photo: $photo_path_abs");
                            $photo_path = null;
                        } else {
                            error_log("✅ Photo sauvegardée: $photo_path");
                        }
                    } else {
                        error_log("❌ Dossier non accessible en écriture: $upload_dir");
                        $photo_path = null;
                    }
                }
            }
        }
    }

    error_log("📝 DONNÉES PRÉPARÉES:");
    error_log("  client_id: $client_id");
    error_log("  type_appareil: $type_appareil");
    error_log("  modele: $modele");
    error_log("  description_probleme: $description_probleme");
    error_log("  prix_reparation: $prix_reparation");
    error_log("  photo_path: " . ($photo_path ?? 'NULL - Photo non sauvegardée'));

    // Préparer la requête d'insertion
    $stmt = $shop_pdo->prepare("
        INSERT INTO reparations (client_id, type_appareil, modele, description_probleme, 
        mot_de_passe, prix_reparation, date_reception, statut, photo_appareil, commande_requise, statut_categorie, notes_techniques, employe_id) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)
    ");

    // Exécuter l'insertion
    $result = $stmt->execute([
        $client_id, 
        $type_appareil, 
        $modele, 
        $description_probleme,
        $mot_de_passe,
        $prix_reparation,
        $statutForDB,
        $photo_path,
        isset($_POST['commande_requise']) ? 1 : 0,
        $categorie_id,
        $notes_techniques,
        $employe_id
    ]);

    if ($result) {
        $reparation_id = $shop_pdo->lastInsertId();
        error_log("✅ INSERTION RÉUSSIE - ID: $reparation_id");

        // Vérifier que l'ID est valide
        if ($reparation_id && $reparation_id > 0) {
            $current_domain = $_SERVER['HTTP_HOST'];
            $redirect_url = "https://" . $current_domain . "/index.php?page=imprimer_etiquette&id=" . $reparation_id;
            
            // Créer le fichier de debug
            $debug_info = [
                'timestamp' => date('Y-m-d H:i:s'),
                'success' => true,
                'reparation_id' => $reparation_id,
                'redirect_url' => $redirect_url,
                'shop_id' => $_SESSION['shop_id'],
                'database' => $shop_pdo->query("SELECT DATABASE()")->fetchColumn(),
                'post_data' => $_POST,
                'photo_saved' => $photo_path ? 'Oui' : 'Non'
            ];
            
            $debug_file = 'debug_reparation_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';
            
            // Essayer plusieurs dossiers pour le debug
            $debug_paths = [
                '../assets/debug/',
                '../assets/temp/',
                '../temp/',
                './'
            ];
            
            $debug_saved = false;
            foreach ($debug_paths as $debug_dir) {
                if (!is_dir($debug_dir)) {
                    @mkdir($debug_dir, 0755, true);
                }
                
                if (is_writable($debug_dir)) {
                    $debug_path = $debug_dir . $debug_file;
                    if (@file_put_contents($debug_path, json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                        error_log("✅ Debug sauvegardé: $debug_path");
                        $debug_saved = true;
                        break;
                    }
                }
            }
            
            if (!$debug_saved) {
                error_log("❌ Impossible de sauvegarder le fichier de debug");
            }

            // Retourner une réponse JSON avec redirection
            echo json_encode([
                'success' => true,
                'reparation_id' => $reparation_id,
                'redirect_url' => $redirect_url,
                'debug_file' => $debug_file,
                'message' => 'Réparation créée avec succès!'
            ]);
        } else {
            throw new Exception('ID de réparation invalide: ' . $reparation_id);
        }
    } else {
        throw new Exception('Échec de l\'insertion en base de données');
    }

} catch (Exception $e) {
    error_log("❌ ERREUR: " . $e->getMessage());
    
    // Créer un fichier de debug pour l'erreur
    $debug_info = [
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => false,
        'error' => $e->getMessage(),
        'shop_id' => $_SESSION['shop_id'] ?? null,
        'post_data' => $_POST
    ];
    
    $debug_file = 'debug_error_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';
    
    // Essayer plusieurs dossiers pour le debug d'erreur
    $debug_paths = [
        '../assets/debug/',
        '../assets/temp/',
        '../temp/',
        './'
    ];
    
    $debug_saved = false;
    foreach ($debug_paths as $debug_dir) {
        // Vérifier si le dossier parent existe et est accessible
        $parent_dir = dirname($debug_dir);
        if (!is_dir($parent_dir) || !is_writable($parent_dir)) {
            error_log("⚠️ Dossier parent inaccessible: $parent_dir");
            continue;
        }
        
        // Créer le dossier de debug si nécessaire
        if (!is_dir($debug_dir)) {
            $mkdir_result = @mkdir($debug_dir, 0755, true);
            if (!$mkdir_result) {
                error_log("⚠️ Impossible de créer le dossier de debug: $debug_dir");
                continue;
            }
        }
        
        // Vérifier les permissions d'écriture
        if (is_writable($debug_dir)) {
            $debug_path = $debug_dir . $debug_file;
            if (@file_put_contents($debug_path, json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                error_log("✅ Debug erreur sauvegardé: $debug_path");
                $debug_saved = true;
                break;
            }
        }
    }
    
    if (!$debug_saved) {
        error_log("❌ Impossible de sauvegarder le fichier de debug d'erreur");
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'post_data_keys' => array_keys($_POST),
            'session_shop_id' => $_SESSION['shop_id'] ?? 'NULL',
            'debug_file' => $debug_file ?? 'Non créé'
        ]
    ]);
}
?>
