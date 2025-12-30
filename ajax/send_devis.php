<?php
/**
 * Script pour envoyer un devis par SMS et mettre à jour le statut d'une réparation
 */

// Désactiver l'affichage des erreurs PHP pour éviter de corrompre la sortie JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Définir l'en-tête JSON dès le début
header('Content-Type: application/json');

// Initialiser la réponse
$response = [
    'success' => false,
    'error' => null
];

// Vérifier si la requête est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Méthode non autorisée';
    echo json_encode($response);
    exit;
}

// Inclure les fichiers de configuration et de connexion à la base de données
require_once '../config/database.php';
require_once '../includes/functions.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

// S'assurer que $shop_pdo est disponible
if (!isset($shop_pdo) || $shop_pdo === null) {
    $response['error'] = 'Erreur de connexion à la base de données';
    echo json_encode($response);
    exit;
}

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    $response['error'] = 'Utilisateur non authentifié';
    echo json_encode($response);
    exit;
}

// Récupérer les données du formulaire
$repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
$sms_template_id = isset($_POST['sms_template_id']) ? intval($_POST['sms_template_id']) : 0;
$new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';

// Vérifier que les données requises sont présentes
if ($repair_id <= 0 || $sms_template_id <= 0 || empty($new_status)) {
    $response['error'] = 'Données invalides';
    echo json_encode($response);
    exit;
}

try {
    // Début de la transaction
    $shop_pdo->beginTransaction();
    
    // 1. Récupérer les informations de la réparation et du client
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.telephone, c.nom, c.prenom, c.email 
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$repair_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }
    
    // 2. Récupérer le template SMS
    $stmt = $shop_pdo->prepare("SELECT * FROM sms_templates WHERE id = ?");
    $stmt->execute([$sms_template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Template SMS non trouvé');
    }
    
    // 3. Récupérer le code du statut
    $stmt = $shop_pdo->prepare("SELECT code FROM statuts WHERE code = ? OR id = ?");
    $stmt->execute([$new_status, $new_status]);
    $statut = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$statut) {
        throw new Exception('Statut invalide');
    }
    
    $statut_code = $statut['code'];
    
    // 4. Mettre à jour le statut de la réparation
    $stmt = $shop_pdo->prepare("
        UPDATE reparations 
        SET statut = ?, date_modification = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$statut_code, $repair_id]);
    
    // 5. Préparer le message SMS
    $message = $template['contenu'];
    
    // Remplacer les variables dans le message
    $message = str_replace('{ID}', $repair_id, $message);
    $message = str_replace('{NOM}', $reparation['nom'], $message);
    $message = str_replace('{PRENOM}', $reparation['prenom'], $message);
    $message = str_replace('{APPAREIL}', $reparation['type_appareil'], $message);
    $message = str_replace('{MARQUE}', $reparation['marque'], $message);
    $message = str_replace('{MODELE}', $reparation['modele'], $message);
    $message = str_replace('{PRIX}', $reparation['prix'], $message);
    
    // 6. Préparer données SMS pour envoi async
    $sms_data = null;
    if (function_exists('send_sms') && !empty($reparation['telephone'])) {
        $sms_data = [
            'telephone' => $reparation['telephone'],
            'message' => $message,
            'client_id' => $reparation['client_id'],
            'repair_id' => $repair_id,
            'template_id' => $sms_template_id
        ];
    }
    
    // 7. Enregistrer l'historique du SMS (avant envoi)
    try {
        $stmt = $shop_pdo->prepare("
            INSERT INTO sms_history (client_id, reparation_id, telephone, message, template_id, date_envoi, statut)
            VALUES (?, ?, ?, ?, ?, NOW(), 'en_cours')
        ");
        $stmt->execute([
            $reparation['client_id'], 
            $repair_id, 
            $reparation['telephone'], 
            $message, 
            $sms_template_id
        ]);
    } catch (PDOException $db_error) {
        // Ignorer erreur DB
    }
    
    // 8. Enregistrer le changement de statut dans l'historique
    try {
        $stmt = $shop_pdo->prepare("
            INSERT INTO historique_reparations (reparation_id, utilisateur_id, action, ancien_statut, nouveau_statut, date_action)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $repair_id,
            $_SESSION['user_id'],
            'changement_statut',
            $reparation['statut'],
            $statut_code
        ]);
    } catch (PDOException $db_error) {
        // Ignorer erreur DB
    }
    
    // Commit de la transaction
    $shop_pdo->commit();
    
    // === RÉPONDRE IMMÉDIATEMENT AU CLIENT ===
    $response['success'] = true;
    $response['message'] = 'Devis envoyé avec succès et statut mis à jour';
    if ($sms_data !== null) {
        $response['sms_status'] = 'En cours d\'envoi...';
    }
    
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
    if ($sms_data !== null) {
        ignore_user_abort(true);
        set_time_limit(30);
        
        send_sms($sms_data['telephone'], $sms_data['message']);
    }
    
    exit;
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($shop_pdo) && $shop_pdo instanceof PDO && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    // Enregistrer l'erreur et la renvoyer
    error_log('Erreur dans send_devis.php: ' . $e->getMessage());
    $response['error'] = $e->getMessage();
}

// Renvoyer la réponse en JSON
echo json_encode($response); 