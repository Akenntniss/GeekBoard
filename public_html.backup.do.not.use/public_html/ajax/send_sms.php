<?php
// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Récupérer les chemins des fichiers includes
$config_path = realpath(__DIR__ . '/../config/database.php');
$functions_path = realpath(__DIR__ . '/../includes/functions.php');

if (!file_exists($config_path) || !file_exists($functions_path)) {
    echo json_encode([
        'success' => false,
        'message' => 'Fichiers de configuration introuvables.'
    ]);
    exit;
}

// Inclure les fichiers nécessaires
require_once $config_path;
require_once $functions_path;

// Récupérer l'ID du magasin depuis les paramètres ou la session
$shop_id = null;
if (isset($_GET['shop_id'])) {
    $shop_id = (int)$_GET['shop_id'];
} elseif (isset($_POST['shop_id'])) {
    $shop_id = (int)$_POST['shop_id'];
} elseif (isset($_SESSION['shop_id'])) {
    $shop_id = (int)$_SESSION['shop_id'];
}

if (!$shop_id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du magasin manquant. Veuillez rafraîchir la page.'
    ]);
    exit;
}

// Stocker l'ID du magasin dans la session pour la fonction getShopDBConnection
$_SESSION['shop_id'] = $shop_id;

// Obtenir la connexion à la base de données du magasin
try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de se connecter à la base de données du magasin.'
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
    ]);
    exit;
}

// Journal de logs pour le débogage
$log_dir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/sms_' . date('Y-m-d') . '.log';

function log_message($message) {
    global $log_file;
    $log_entry = date('[Y-m-d H:i:s] ') . $message . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
    exit;
}

log_message("=== TRAITEMENT REQUÊTE SMS ===");

// Supporte aussi le JSON: fusionner le payload JSON dans $_POST si présent
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
if (is_array($jsonData)) {
    $_POST = array_merge($_POST, $jsonData);
}

log_message("Données reçues (POST fusionné): " . json_encode($_POST));

// Récupérer les données du formulaire
$client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
$reparation_id = isset($_POST['reparation_id']) ? (int)$_POST['reparation_id'] : 0;
$telephone = isset($_POST['telephone']) ? clean_input($_POST['telephone']) : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';

// Si le téléphone est manquant mais qu'on a une réparation, essayer de le retrouver via la BDD
if (empty($telephone) && $reparation_id > 0) {
    try {
        $stmt = $shop_pdo->prepare("SELECT c.id AS client_id, c.telephone AS client_telephone
                                     FROM reparations r
                                     JOIN clients c ON c.id = r.client_id
                                     WHERE r.id = ?");
        $stmt->execute([$reparation_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (empty($client_id) && !empty($row['client_id'])) {
                $client_id = (int)$row['client_id'];
            }
            if (empty($telephone) && !empty($row['client_telephone'])) {
                $telephone = clean_input($row['client_telephone']);
            }
        }
        log_message("Téléphone/Client récupérés via réparation #$reparation_id -> client_id=$client_id, telephone=$telephone");
    } catch (PDOException $e) {
        log_message("Erreur récupération téléphone via réparation: " . $e->getMessage());
    }
}

// Vérifier que les données nécessaires sont présentes
if (empty($telephone) || empty($message)) {
    log_message("Erreur: Données manquantes (téléphone ou message)");
    echo json_encode([
        'success' => false,
        'message' => 'Le numéro de téléphone et le message sont requis.'
    ]);
    exit;
}

// Rechercher les variables dans le message qui nécessitent un remplacement
$variables = [
    '[CLIENT_NOM]',
    '[CLIENT_PRENOM]',
    '[CLIENT_TELEPHONE]',
    '[REPARATION_ID]',
    '[APPAREIL_TYPE]',
    '[APPAREIL_MARQUE]',
    '[APPAREIL_MODELE]',
    '[DATE_RECEPTION]',
    '[DATE_FIN_PREVUE]',
    '[PRIX]',
    '[COMPANY_NAME]',
    '[COMPANY_PHONE]'
];

$variables_found = [];
foreach ($variables as $var) {
    if (strpos($message, $var) !== false) {
        $variables_found[] = $var;
    }
}

// Si des variables sont trouvées, récupérer les données correspondantes
if (!empty($variables_found) && $client_id > 0) {
    log_message("Variables à remplacer trouvées: " . implode(", ", $variables_found));
    
    try {
        // Récupérer les informations du client
        $stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Récupérer la dernière réparation du client
        $stmt = $shop_pdo->prepare("
            SELECT * FROM reparations 
            WHERE client_id = ? 
            ORDER BY date_reception DESC, id DESC 
            LIMIT 1
        ");
        $stmt->execute([$client_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        log_message("Données récupérées - Client: " . ($client ? "Oui" : "Non") . ", Réparation: " . ($reparation ? "Oui" : "Non"));
        
        // Remplacer les variables par les valeurs réelles
        if ($client) {
            $message = str_replace('[CLIENT_NOM]', $client['nom'], $message);
            $message = str_replace('[CLIENT_PRENOM]', $client['prenom'], $message);
            $message = str_replace('[CLIENT_TELEPHONE]', $client['telephone'], $message);
        }
        
        if ($reparation) {
            $message = str_replace('[REPARATION_ID]', $reparation['id'], $message);
            $message = str_replace('[APPAREIL_TYPE]', $reparation['type_appareil'], $message);
            $message = str_replace('[APPAREIL_MARQUE]', $reparation['marque'], $message);
            $message = str_replace('[APPAREIL_MODELE]', $reparation['modele'], $message);
            
            // Formater les dates
            if (!empty($reparation['date_reception'])) {
                $message = str_replace('[DATE_RECEPTION]', date('d/m/Y', strtotime($reparation['date_reception'])), $message);
            }
            
            if (!empty($reparation['date_fin_prevue'])) {
                $message = str_replace('[DATE_FIN_PREVUE]', date('d/m/Y', strtotime($reparation['date_fin_prevue'])), $message);
            }
            
            // Formater le prix
            if (isset($reparation['prix_reparation']) && $reparation['prix_reparation'] > 0) {
                $message = str_replace('[PRIX]', number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €', $message);
            } elseif (isset($reparation['prix']) && $reparation['prix'] > 0) {
                $message = str_replace('[PRIX]', number_format($reparation['prix'], 2, ',', ' ') . ' €', $message);
            }
        }
        
        log_message("Message après remplacement des variables: " . $message);
        
    } catch (PDOException $e) {
        log_message("Erreur lors de la récupération des données: " . $e->getMessage());
        // Continuer malgré l'erreur, en laissant les variables non remplacées
    }
}

// Remplacer les variables d'entreprise si elles sont présentes
if (strpos($message, '[COMPANY_NAME]') !== false || strpos($message, '[COMPANY_PHONE]') !== false) {
    // Récupérer les paramètres d'entreprise
    $company_name = 'Maison du Geek';  // Valeur par défaut
    $company_phone = '08 95 79 59 33';  // Valeur par défaut
    
    try {
        $stmt_company = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
        $stmt_company->execute();
        $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (!empty($company_params['company_name'])) {
            $company_name = $company_params['company_name'];
        }
        if (!empty($company_params['company_phone'])) {
            $company_phone = $company_params['company_phone'];
        }
        
        // Remplacer les variables d'entreprise
        $message = str_replace('[COMPANY_NAME]', $company_name, $message);
        $message = str_replace('[COMPANY_PHONE]', $company_phone, $message);
        
        log_message("Variables d'entreprise remplacées - Nom: $company_name, Téléphone: $company_phone");
        
    } catch (Exception $e) {
        log_message("Erreur lors de la récupération des paramètres d'entreprise: " . $e->getMessage());
    }
}

// Envoyer le SMS
try {
    log_message("Envoi du SMS au numéro: " . $telephone);
    log_message("MIGRATION: Utilisation de la nouvelle API SMS Gateway");
    
    // Utiliser la nouvelle fonction send_sms unifiée
    if (!function_exists('send_sms')) {
        require_once(__DIR__ . '/../includes/sms_functions.php');
    }
    
    // Utiliser la nouvelle fonction send_sms
    log_message("Appel de send_sms() avec numéro: $telephone");
    
    $result = send_sms($telephone, $message, 'manual_sms', $client_id, $_SESSION['user_id'] ?? null);
    
    // L'enregistrement en base de données est maintenant géré par log_sms_to_database() 
    // dans la fonction send_sms() unifiée
    
    log_message("Résultat final: " . json_encode($result));
    echo json_encode($result);
    
} catch (Exception $e) {
    log_message("Exception lors de l'envoi du SMS: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi du SMS: ' . $e->getMessage()
    ]);
} 