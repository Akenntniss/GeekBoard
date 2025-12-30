<?php
/**
 * Envoi de SMS pour les liens partenaires
 * Système simplifié sans token
 */

// Configuration des erreurs (ne pas casser la réponse JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Démarrer la session immédiatement
session_start();

// Réponse JSON par défaut
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Initialiser la session du magasin si nécessaire
if (!isset($_SESSION['shop_id'])) {
    $detected_shop_id = detectShopFromSubdomain();
    if ($detected_shop_id) {
        $_SESSION['shop_id'] = $detected_shop_id;
    }
}

// Session magasin tolérante
if (!isset($_SESSION['shop_id'])) {
    $detected_shop_id = detectShopFromSubdomain();
    if ($detected_shop_id) {
        $_SESSION['shop_id'] = $detected_shop_id;
    }
}
if (!isset($_SESSION['shop_id'])) {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Magasin non identifié']);
    exit;
}

// Auth utilisateur: facultative pour cet envoi
$userId = $_SESSION['user_id'] ?? null;

try {
    // Récupérer les données (JSON ou x-www-form-urlencoded)
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        // Essayer de lire depuis $_POST (form-urlencoded)
        $input = $_POST;
    }
    if (!is_array($input)) {
        throw new Exception('Données invalides');
    }
    
    // Validation des données
    $partenaire_id = filter_var($input['partenaire_id'] ?? '', FILTER_VALIDATE_INT);
    $telephone = filter_var($input['telephone'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    // Construire le message: accepter 'message' ou 'lien'
    $message = filter_var($input['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $lien = filter_var($input['lien'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (!$message && $lien) {
        $message = 'Accès partenaire: ' . $lien;
    }
    
    if (!$partenaire_id || !$telephone || !$message) {
        throw new Exception('Données manquantes ou invalides');
    }
    
    // Connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }
    
    // Vérifier que le partenaire existe
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, telephone 
        FROM partenaires 
        WHERE id = ?
    ");
    $stmt->execute([$partenaire_id]);
    $partenaire = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partenaire) {
        throw new Exception('Partenaire introuvable');
    }
    
    // Inclure les fonctions SMS
    require_once dirname(__DIR__) . '/includes/sms_functions.php';
    
    // Envoyer le SMS
    $sms_result = send_sms(
        $telephone,
        $message,
        'partner_link',
        $partenaire_id,
        $userId
    );
    
    if (!$sms_result['success']) {
        // Si l'envoi SMS échoue
        error_log("Erreur envoi SMS partenaire: " . $sms_result['message']);
        
        // Si c'est un doublon bloqué, on considère que c'est un succès
        if (isset($sms_result['duplicate_blocked']) && $sms_result['duplicate_blocked']) {
            echo json_encode([
                'success' => true,
                'message' => 'Lien généré avec succès (SMS similaire déjà envoyé récemment)'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur d\'envoi SMS: ' . $sms_result['message']
            ]);
        }
        exit;
    }
    
    // Succès complet
    echo json_encode([
        'success' => true,
        'message' => 'SMS envoyé avec succès',
        'sms_result' => $sms_result
    ]);
    
} catch (Exception $e) {
    error_log("Erreur send_partner_sms.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage(),
        'debug' => [
            'shop_id' => $_SESSION['shop_id'] ?? 'non défini',
            'user_id' => $_SESSION['user_id'] ?? 'non défini'
        ]
    ]);
}
?>