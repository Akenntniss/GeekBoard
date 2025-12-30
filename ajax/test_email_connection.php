<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

$smtp_host = $_POST['smtp_host'] ?? '';
$smtp_port = $_POST['smtp_port'] ?? '';
$smtp_user = $_POST['smtp_user'] ?? '';
$smtp_pass = $_POST['smtp_pass'] ?? '';
$smtp_encryption = $_POST['smtp_encryption'] ?? 'ssl';
$from_name = $_POST['email_from_name'] ?? 'GeekBoard Test';

if (empty($smtp_host) || empty($smtp_port) || empty($smtp_user) || empty($smtp_pass)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs SMTP']);
    exit;
}

$mail = new PHPMailer(true);

try {
    // Configuration
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_user;
    $mail->Password = $smtp_pass;
    $mail->SMTPSecure = ($smtp_encryption === 'none') ? false : $smtp_encryption;
    $mail->Port = $smtp_port;
    $mail->Timeout = 10;
    
    // Expéditeur / Destinataire
    $mail->setFrom($smtp_user, $from_name);
    $mail->addAddress($smtp_user, 'Test GeekBoard'); // S'envoyer à soi-même
    
    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test de connexion SMTP GeekBoard';
    $mail->Body    = 'Ceci est un email de test pour valider votre configuration SMTP sur GeekBoard.<br><br>Date: ' . date('d/m/Y H:i:s');
    
    $mail->send();
    
    echo json_encode(['success' => true, 'message' => 'Test réussi ! L\'email a été envoyé à ' . $smtp_user]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur SMTP: ' . $mail->ErrorInfo ?: $e->getMessage()]);
}
