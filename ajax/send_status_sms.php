<?php
// 🔧 Configuration de session et sécurité
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Gestion d'erreurs améliorée
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Vérifier si la requête est en GET (pour récupérer le message) ou en POST (pour envoyer le SMS)
$isGetRequest = $_SERVER['REQUEST_METHOD'] === 'GET';

// Récupérer les paramètres de manière sécurisée
if ($isGetRequest) {
    $repair_id = isset($_GET['repair_id']) ? (int)$_GET['repair_id'] : 0;
    $status_id = isset($_GET['status_id']) ? (int)$_GET['status_id'] : 0;
} else {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $repair_id = isset($data['repair_id']) ? (int)$data['repair_id'] : 0;
    $status_id = isset($data['status_id']) ? (int)$data['status_id'] : 0;
}

// Vérifier les paramètres
if (empty($repair_id) || empty($status_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants'
    ]);
    exit;
}

try {
    // Vérifier la connexion à la base de données
    if (!isset($shop_pdo) || !$shop_pdo) {
        $shop_pdo = getShopDBConnection();
    }
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Debug: vérifier la connexion PDO
    if (!$shop_pdo) {
        throw new Exception('Connexion PDO non établie');
    }
    
    // Debug: vérifier quelle base de données est utilisée
    $db_name_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
    $current_db = $db_name_stmt->fetch(PDO::FETCH_ASSOC)['current_db'] ?? 'inconnue';
    
    // Récupérer les informations de la réparation
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$repair_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug: vérifier si la requête a trouvé des résultats
    if (!$reparation) {
        // Tenter une requête plus simple pour déboguer
        $simple_stmt = $shop_pdo->prepare("SELECT id, client_id, type_appareil FROM reparations WHERE id = ?");
        $simple_stmt->execute([$repair_id]);
        $simple_result = $simple_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($simple_result) {
            throw new Exception('Réparation trouvée mais problème de jointure avec clients. ID: ' . $simple_result['id'] . ', client_id: ' . $simple_result['client_id']);
        } else {
            throw new Exception('Réparation non trouvée - ID ' . $repair_id . ' n\'existe pas dans la table reparations');
        }
    }

    // Récupérer le template SMS associé au statut
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, contenu 
        FROM sms_templates 
        WHERE statut_id = ? AND est_actif = 1
        LIMIT 1
    ");
    $stmt->execute([$status_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        echo json_encode([
            'success' => false,
            'message' => 'Aucun modèle SMS disponible pour ce statut'
        ]);
        exit;
    }

    // Si c'est une requête GET, on renvoie juste le message
    if ($isGetRequest) {
        // Préparer le contenu du SMS en remplaçant les variables
        $message = $template['contenu'];
        
        // Récupérer les informations de l'entreprise depuis la table parametres (structure clé/valeur)
        $company_name = 'Maison du Geek';
        $company_phone = '08 95 79 59 33';
        $company_address = '';
        $company_data = [];
        
        try {
            $stmt_company = $shop_pdo->query("SELECT cle, valeur FROM parametres");
            $rows = $stmt_company->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir en tableau associatif
            foreach ($rows as $row) {
                $company_data[$row['cle']] = $row['valeur'];
            }
            
            if (!empty($company_data)) {
                if (!empty($company_data['company_name'])) {
                    $company_name = $company_data['company_name'];
                }
                if (!empty($company_data['company_phone'])) {
                    $company_phone = $company_data['company_phone'];
                }
                if (!empty($company_data['company_address'])) {
                    $company_address = $company_data['company_address'];
                }
            }
        } catch (Exception $e) {
            error_log("Erreur récupération parametres: " . $e->getMessage());
        }
        
        // Générer l'URL de suivi dynamique
        $current_host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'https://';
        $url_suivi = $protocol . $current_host . '/suivi.php?id=' . $repair_id;
        
        // Tableau des remplacements - TOUTES les 17 variables
        $replacements = [
            // Variables client
            '[CLIENT_NOM]' => $reparation['client_nom'],
            '[CLIENT_PRENOM]' => $reparation['client_prenom'],
            '[CLIENT_TELEPHONE]' => $reparation['client_telephone'],
            // Variables réparation
            '[REPARATION_ID]' => $reparation['id'],
            '[APPAREIL_TYPE]' => $reparation['type_appareil'],
            '[APPAREIL_MARQUE]' => $reparation['marque'],
            '[APPAREIL_MODELE]' => $reparation['modele'],
            '[DATE_RECEPTION]' => format_date($reparation['date_reception']),
            '[DATE_FIN_PREVUE]' => !empty($reparation['date_fin_prevue']) ? format_date($reparation['date_fin_prevue']) : '',
            '[PRIX]' => !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . '€' : '',
            '[NOTES_TECHNIQUES]' => $reparation['notes_techniques'] ?? '',
            // Variables magasin
            '[COMPANY_NAME]' => $company_name,
            '[COMPANY_PHONE]' => $company_phone,
            '[COMPANY_ADDRESS]' => $company_address,
            '[COMPANY_NUMBER]' => $company_data['company_number'] ?? '',
            '[COMPANY_HOURS]' => $company_data['company_hours'] ?? '',
            // URLs
            '[URL_SUIVI]' => $url_suivi,
            '[URL_DEVIS]' => $protocol . $current_host . '/devis_client.php?id=' . $repair_id
        ];
        
        // Effectuer les remplacements
        foreach ($replacements as $var => $value) {
            $message = str_replace($var, $value, $message);
        }

        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        exit;
    }

    // Si c'est une requête POST, on envoie le SMS
    if (empty($reparation['client_telephone'])) {
        throw new Exception('Le client n\'a pas de numéro de téléphone');
    }

    // Préparer le contenu du SMS en remplaçant les variables
    $message = $template['contenu'];
    
    // Récupérer les informations de l'entreprise depuis la table parametres (structure clé/valeur)
    $company_name = 'Maison du Geek';
    $company_phone = '08 95 79 59 33';
    $company_address = '';
    $company_data = [];
    
    try {
        $stmt_company = $shop_pdo->query("SELECT cle, valeur FROM parametres");
        $rows = $stmt_company->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir en tableau associatif
        foreach ($rows as $row) {
            $company_data[$row['cle']] = $row['valeur'];
        }
        
        if (!empty($company_data)) {
            if (!empty($company_data['company_name'])) {
                $company_name = $company_data['company_name'];
            }
            if (!empty($company_data['company_phone'])) {
                $company_phone = $company_data['company_phone'];
            }
            if (!empty($company_data['company_address'])) {
                $company_address = $company_data['company_address'];
            }
        }
    } catch (Exception $e) {
        error_log("Erreur récupération parametres: " . $e->getMessage());
    }
    
    // Générer l'URL de suivi dynamique
    $current_host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'https://';
    $url_suivi = $protocol . $current_host . '/suivi.php?id=' . $repair_id;
    
    // Tableau des remplacements - TOUTES les 17 variables
    $replacements = [
        // Variables client
        '[CLIENT_NOM]' => $reparation['client_nom'],
        '[CLIENT_PRENOM]' => $reparation['client_prenom'],
        '[CLIENT_TELEPHONE]' => $reparation['client_telephone'],
        // Variables réparation
        '[REPARATION_ID]' => $reparation['id'],
        '[APPAREIL_TYPE]' => $reparation['type_appareil'],
        '[APPAREIL_MARQUE]' => $reparation['marque'],
        '[APPAREIL_MODELE]' => $reparation['modele'],
        '[DATE_RECEPTION]' => format_date($reparation['date_reception']),
        '[DATE_FIN_PREVUE]' => !empty($reparation['date_fin_prevue']) ? format_date($reparation['date_fin_prevue']) : '',
        '[PRIX]' => !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . '€' : '',
        '[NOTES_TECHNIQUES]' => $reparation['notes_techniques'] ?? '',
        // Variables magasin
        '[COMPANY_NAME]' => $company_name,
        '[COMPANY_PHONE]' => $company_phone,
        '[COMPANY_ADDRESS]' => $company_address,
        '[COMPANY_NUMBER]' => $company_data['company_number'] ?? '',
        '[COMPANY_HOURS]' => $company_data['company_hours'] ?? '',
        // URLs
        '[URL_SUIVI]' => $url_suivi,
        '[URL_DEVIS]' => $protocol . $current_host . '/devis_client.php?id=' . $repair_id
    ];
    
    // Effectuer les remplacements
    foreach ($replacements as $var => $value) {
        $message = str_replace($var, $value, $message);
    }

    // Vérifier que la fonction send_sms existe
    if (!function_exists('send_sms')) {
        // Inclure le fichier des fonctions SMS si nécessaire
        $sms_functions_path = realpath(__DIR__ . '/../includes/sms_functions.php');
        if (file_exists($sms_functions_path)) {
            require_once $sms_functions_path;
        }
        
        if (!function_exists('send_sms')) {
            throw new Exception('Fonction send_sms non disponible');
        }
    }
    
    // Préparer les données SMS pour envoi async
    $sms_data = [
        'telephone' => $reparation['client_telephone'],
        'message' => $message,
        'repair_id' => $repair_id,
        'template_id' => $template['id'],
        'status_id' => $status_id
    ];
    
    // === RÉPONDRE IMMÉDIATEMENT AU CLIENT ===
    $response = [
        'success' => true,
        'message' => 'SMS en cours d\'envoi...'
    ];
    
    $json_response = json_encode($response);
    
    // Nettoyer les buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    header('Connection: close');
    header('Content-Length: ' . strlen($json_response));
    echo $json_response;
    
    // Flush et continuer en arrière-plan
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }
    
    // === ENVOI SMS EN ARRIÈRE-PLAN ===
    ignore_user_abort(true);
    set_time_limit(30);
    
    $sms_result = send_sms($sms_data['telephone'], $sms_data['message']);
    
    if ($sms_result['success']) {
        // Enregistrer l'envoi du SMS dans la base de données
        try {
            $stmt = $shop_pdo->prepare("
                INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $sms_data['repair_id'], 
                $sms_data['template_id'], 
                $sms_data['telephone'], 
                $sms_data['message'], 
                $sms_data['status_id']
            ]);
        } catch (PDOException $db_error) {
            error_log("Erreur enregistrement SMS: " . $db_error->getMessage());
        }
    }
    
    exit;

} catch (Exception $e) {
    // Log de l'erreur pour débogage
    $log_dir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $log_file = $log_dir . '/sms_status_' . date('Y-m-d') . '.log';
    $log_entry = date('[Y-m-d H:i:s] ') . "Erreur send_status_sms.php: " . $e->getMessage() . " | Ligne: " . $e->getLine() . " | Fichier: " . $e->getFile() . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'repair_id' => $repair_id ?? 'non défini',
            'status_id' => $status_id ?? 'non défini',
            'method' => $_SERVER['REQUEST_METHOD'],
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]
    ]);
} 