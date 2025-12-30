<?php
// Handler AJAX pour l'envoi de SMS depuis les commandes de pièces
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Lire les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Valider les données requises
if (!isset($data['commandeId']) || !isset($data['type']) || !isset($data['telephone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit;
}

$commande_id = (int)$data['commandeId'];
$type = $data['type']; // 'commande_recue' ou 'retard_livraison'
$telephone = $data['telephone'];
$client_id = isset($data['clientId']) ? (int)$data['clientId'] : null;
$reparation_id = isset($data['reparationId']) ? (int)$data['reparationId'] : null;

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les informations de la commande
    $stmt = $shop_pdo->prepare("
        SELECT c.*, 
               cl.nom as client_nom, 
               cl.prenom as client_prenom,
               cl.telephone,
               r.type_appareil, 
               r.marque as appareil_marque,
               r.modele as appareil_modele,
               r.id as reparation_id
        FROM commandes_pieces c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        LEFT JOIN reparations r ON c.reparation_id = r.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$commande) {
        throw new Exception('Commande non trouvée');
    }
    
    // Récupérer le template SMS approprié
    $template_code = ($type === 'commande_recue') ? 'commande_piece_arrivee' : 'retard_livraison_piece';
    
    $stmt = $shop_pdo->prepare("
        SELECT * FROM sms_templates 
        WHERE code = ? AND est_actif = 1 
        LIMIT 1
    ");
    $stmt->execute([$template_code]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Template SMS non trouvé pour le type: ' . $template_code);
    }
    
    // Récupérer les paramètres de l'entreprise
    $stmt = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone', 'company_address')");
    $stmt->execute();
    $params = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $company_name = isset($params['company_name']) && !empty($params['company_name']) 
        ? $params['company_name'] 
        : 'Maison du Geek';
    $company_phone = isset($params['company_phone']) && !empty($params['company_phone']) 
        ? $params['company_phone'] 
        : '08 95 79 59 33';
    $company_address = isset($params['company_address']) && !empty($params['company_address']) 
        ? $params['company_address'] 
        : '';
    
    // Générer l'URL de suivi si reparation_id existe
    $url_suivi = '';
    if ($commande['reparation_id']) {
        $current_host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'https://';
        $url_suivi = $protocol . $current_host . '/suivi.php?id=' . $commande['reparation_id'];
    }
    
    // Préparer les variables de remplacement
    $variables = [
        '[CLIENT_NOM]' => $commande['client_nom'] ?? '',
        '[CLIENT_PRENOM]' => $commande['client_prenom'] ?? '',
        '[CLIENT_TELEPHONE]' => $commande['telephone'] ?? '',
        '[REPARATION_ID]' => $commande['reparation_id'] ?? '',
        '[APPAREIL_TYPE]' => $commande['type_appareil'] ?? '',
        '[APPAREIL_MARQUE]' => $commande['appareil_marque'] ?? '',
        '[APPAREIL_MODELE]' => $commande['appareil_modele'] ?? '',
        '[COMPANY_NAME]' => $company_name,
        '[COMPANY_PHONE]' => $company_phone,
        '[COMPANY_ADDRESS]' => $company_address,
        '[URL_SUIVI]' => $url_suivi
    ];
    
    // Remplacer les variables dans le template
    $message = $template['contenu'];
    foreach ($variables as $key => $value) {
        $message = str_replace($key, $value, $message);
    }
    
    // Envoyer une réponse immédiate au client (asynchrone)
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'SMS en cours d\'envoi'
    ]);
    
    // Fermer la connexion pour que le client reçoive la réponse
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        // Fallback pour les serveurs non-FastCGI
        ob_end_flush();
        flush();
    }
    
    // Continuer l'envoi du SMS en arrière-plan
    // Vérifier si la fonction send_sms existe
    if (function_exists('send_sms')) {
        $sms_result = send_sms($telephone, $message);
        
        // Logger l'envoi dans l'historique SMS
        $stmt = $shop_pdo->prepare("
            INSERT INTO sms_logs (recipient, message, reparation_id, status, response, date_envoi)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $telephone,
            $message,
            $commande['reparation_id'],
            $sms_result['success'] ? 1 : 0,
            json_encode($sms_result)
        ]);
        
        // Logger aussi dans reparation_sms si reparation_id existe
        if ($commande['reparation_id']) {
            $stmt = $shop_pdo->prepare("
                INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $commande['reparation_id'],
                $template['id'],
                $telephone,
                $message
            ]);
        }
        
        error_log("SMS envoyé pour commande #$commande_id - Type: $type - Résultat: " . ($sms_result['success'] ? 'Succès' : 'Échec'));
    } else {
        error_log("Fonction send_sms non disponible pour commande #$commande_id");
    }
    
} catch (Exception $e) {
    error_log("Erreur send_commande_sms: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
