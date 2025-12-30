<?php
/**
 * Endpoint AJAX pour demander l'ajout d'un nouveau fournisseur
 * Envoie un email via SMTP à contact@maisondugeek.fr
 */

require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';

// === CORS Headers pour l'extension Chrome ===
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (preg_match('/\.(servo\.tools|mdgeek\.top)$/', parse_url($origin, PHP_URL_HOST) ?? '') ||
    strpos($origin, 'chrome-extension://') === 0) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-SERVO-Extension');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

header('Content-Type: application/json');

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer les données POST
$supplier_name = trim($_POST['supplier_name'] ?? '');
$supplier_url = trim($_POST['supplier_url'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (empty($supplier_name) || empty($supplier_url)) {
    echo json_encode(['success' => false, 'message' => 'Nom et URL du fournisseur requis']);
    exit;
}

// Configuration SMTP
$smtp_host = 'smtp.hostinger.com';
$smtp_port = 465; // SSL
$smtp_user = 'adam@maisondugeek.fr';
$smtp_pass = 'Maisondugeek06$';
$email_to = 'contact@maisondugeek.fr';

// Récupérer les infos de l'utilisateur
$user_name = $_SESSION['full_name'] ?? 'Utilisateur inconnu';
$user_email = $_SESSION['email'] ?? '';
$shop_name = '';

try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("SELECT company_name FROM company_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    if ($result) {
        $shop_name = $result['company_name'];
    }
} catch (Exception $e) {
    // Ignorer
}

// Construire le contenu de l'email
$subject = "[SERVO Extension] Demande d'ajout de fournisseur: " . $supplier_name;

$body = "=== Demande d'ajout de fournisseur ===\n\n";
$body .= "Fournisseur demandé: " . $supplier_name . "\n";
$body .= "URL du site: " . $supplier_url . "\n\n";

if (!empty($notes)) {
    $body .= "Notes:\n" . $notes . "\n\n";
}

$body .= "=== Informations utilisateur ===\n";
$body .= "Demandeur: " . $user_name . "\n";
if (!empty($user_email)) {
    $body .= "Email: " . $user_email . "\n";
}
if (!empty($shop_name)) {
    $body .= "Magasin: " . $shop_name . "\n";
}
$body .= "Date: " . date('d/m/Y H:i') . "\n";

// Envoyer l'email via SMTP
try {
    // Utiliser PHPMailer si disponible, sinon socket SMTP direct
    $phpmailer_path = dirname(__DIR__) . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    
    if (file_exists($phpmailer_path)) {
        // Utiliser PHPMailer
        require_once $phpmailer_path;
        require_once dirname(__DIR__) . '/vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once dirname(__DIR__) . '/vendor/phpmailer/phpmailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom($smtp_user, 'SERVO Extension');
        $mail->addAddress($email_to);
        $mail->addReplyTo($user_email ?: $smtp_user, $user_name);
        
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
    } else {
        // Fallback: mail() natif PHP
        $headers = "From: " . $smtp_user . "\r\n";
        $headers .= "Reply-To: " . ($user_email ?: $smtp_user) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        if (!mail($email_to, $subject, $body, $headers)) {
            throw new Exception('Échec de l\'envoi de l\'email');
        }
    }
    
    // Log de la demande
    error_log("[SERVO Extension] Demande fournisseur envoyée: " . $supplier_name . " par " . $user_name);
    
    echo json_encode([
        'success' => true,
        'message' => 'Demande envoyée avec succès'
    ]);
    
} catch (Exception $e) {
    error_log("[SERVO Extension] Erreur envoi email: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi de l\'email'
    ]);
}
