<?php
/**
 * Endpoint d'envoi SMS asynchrone (Fire and Forget)
 * 
 * Ce fichier permet d'envoyer des SMS de manière asynchrone
 * sans bloquer l'interface utilisateur.
 */

// Désactiver la limite de temps pour l'envoi en arrière-plan
ignore_user_abort(true);
set_time_limit(0);

// Supprimer les buffers de sortie pour répondre immédiatement
while (ob_get_level()) {
    ob_end_clean();
}

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le type de contenu comme JSON
header('Content-Type: application/json');
header('Connection: close');

// Récupérer les paramètres
$telephone = $_POST['telephone'] ?? '';
$message = $_POST['message'] ?? '';
$reference_type = $_POST['reference_type'] ?? 'manual';
$reference_id = $_POST['reference_id'] ?? null;
$client_id = $_POST['client_id'] ?? null;
$reparation_id = $_POST['reparation_id'] ?? null;
$shop_id = $_POST['shop_id'] ?? $_SESSION['shop_id'] ?? null;

// Validation basique
if (empty($telephone) || empty($message)) {
    echo json_encode([
        'success' => false,
        'message' => 'Téléphone et message requis',
        'async' => false
    ]);
    exit;
}

// Répondre immédiatement au client
$response = json_encode([
    'success' => true,
    'message' => 'SMS en cours d\'envoi...',
    'async' => true,
    'queued' => true
]);

// Calculer la taille de la réponse
$size = strlen($response);
header("Content-Length: $size");

// Envoyer la réponse
echo $response;

// Fermer la connexion avec le client
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    // Alternative pour les serveurs non-FPM
    flush();
    if (function_exists('ob_flush')) {
        @ob_flush();
    }
}

// === À partir d'ici, le client a reçu sa réponse ===
// === L'envoi SMS se fait en arrière-plan ===

try {
    // Charger les dépendances
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/sms_functions.php';
    
    // Stocker le shop_id dans la session pour les fonctions de billing
    if ($shop_id) {
        $_SESSION['shop_id'] = $shop_id;
    }
    
    // Si besoin de récupérer les données du client
    if (!empty($reparation_id) || !empty($client_id)) {
        $shop_pdo = getShopDBConnection();
        
        // Récupérer les infos si reparation_id fourni
        if (!empty($reparation_id) && $shop_pdo) {
            $stmt = $shop_pdo->prepare("
                SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.id = ?
            ");
            $stmt->execute([$reparation_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data && empty($telephone)) {
                $telephone = $data['client_telephone'];
            }
            
            // Remplacer les variables dans le message
            if ($data) {
                $message = str_replace([
                    '[CLIENT_NOM]', '[CLIENT_PRENOM]', '[REPARATION_ID]',
                    '[APPAREIL_TYPE]', '[APPAREIL_MARQUE]', '[APPAREIL_MODELE]'
                ], [
                    $data['client_nom'] ?? '', $data['client_prenom'] ?? '', $data['id'] ?? '',
                    $data['type_appareil'] ?? '', $data['marque'] ?? '', $data['modele'] ?? ''
                ], $message);
            }
        }
    }
    
    // Envoyer le SMS (de manière synchrone mais en arrière-plan)
    $result = send_sms($telephone, $message, $reference_type, $reference_id, $_SESSION['user_id'] ?? null);
    
    // Logger le résultat
    error_log("SMS Async: " . ($result['success'] ? 'ENVOYÉ' : 'ÉCHEC') . " vers $telephone - " . ($result['message'] ?? ''));
    
} catch (Exception $e) {
    error_log("SMS Async ERROR: " . $e->getMessage());
}
