<?php
/**
 * Configuration Email SMTP pour GeekBoard
 * Utilise PHPMailer pour l'envoi d'emails via SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Configuration SMTP Hostinger
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');
define('SMTP_USERNAME', 'servo@maisondugeek.fr');
define('SMTP_PASSWORD', 'Merguez01#');

// Configuration des emails
define('FROM_EMAIL', 'servo@maisondugeek.fr');
define('FROM_NAME', 'SERVO by Maison Du Geek');
define('REPLY_TO_EMAIL', 'contact@maisondugeek.fr');
define('CONTACT_EMAIL', 'contact@maisondugeek.fr');

/**
 * Envoie un email via SMTP
 * 
 * @param string $to_email Destinataire
 * @param string $to_name Nom du destinataire (optionnel)
 * @param string $subject Sujet
 * @param string $body Corps du message (HTML)
 * @param string $alt_body Corps du message (texte brut, optionnel)
 * @param array $reply_to [email, nom] pour répondre à (optionnel)
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($to_email, $to_name, $subject, $body, $alt_body = '', $reply_to = null) {
    // Chargement de PHPMailer (à adapter selon votre installation)
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Configuration de l'expéditeur
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        
        // Configuration du destinataire
        $mail->addAddress($to_email, $to_name);
        
        // Configuration du reply-to
        if ($reply_to && is_array($reply_to) && count($reply_to) >= 2) {
            $mail->addReplyTo($reply_to[0], $reply_to[1]);
        } else {
            $mail->addReplyTo(REPLY_TO_EMAIL, FROM_NAME);
        }
        
        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        if (!empty($alt_body)) {
            $mail->AltBody = $alt_body;
        }
        
        // Envoi
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email envoyé avec succès'
        ];
        
    } catch (Exception $e) {
        error_log("Erreur PHPMailer: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage()
        ];
    }
}

/**
 * Envoie une notification de nouveau contact
 * 
 * @param array $contact_data Données du formulaire de contact
 * @return array ['success' => bool, 'message' => string]
 */
function sendContactNotification($contact_data) {
    $subject = "Nouvelle demande de contact - " . ($contact_data['subject'] ?? 'Contact général');
    
    // Corps HTML de l'email
    $html_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .info-table { width: 100%; margin: 20px 0; }
            .info-table td { padding: 8px; border-bottom: 1px solid #eee; }
            .label { font-weight: bold; width: 150px; }
            .message-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>Nouvelle demande de contact - SERVO</h2>
        </div>
        
        <div class='content'>
            <p>Une nouvelle demande de contact a été reçue depuis le site servo.tools :</p>
            
            <table class='info-table'>
                <tr>
                    <td class='label'>Nom complet :</td>
                    <td>" . htmlspecialchars($contact_data['firstName'] . ' ' . $contact_data['lastName']) . "</td>
                </tr>
                <tr>
                    <td class='label'>Email :</td>
                    <td><a href='mailto:" . htmlspecialchars($contact_data['email']) . "'>" . htmlspecialchars($contact_data['email']) . "</a></td>
                </tr>";
    
    if (!empty($contact_data['phone'])) {
        $html_body .= "
                <tr>
                    <td class='label'>Téléphone :</td>
                    <td><a href='tel:" . htmlspecialchars($contact_data['phone']) . "'>" . htmlspecialchars($contact_data['phone']) . "</a></td>
                </tr>";
    }
    
    if (!empty($contact_data['company'])) {
        $html_body .= "
                <tr>
                    <td class='label'>Entreprise :</td>
                    <td>" . htmlspecialchars($contact_data['company']) . "</td>
                </tr>";
    }
    
    if (!empty($contact_data['employees'])) {
        $html_body .= "
                <tr>
                    <td class='label'>Nombre d'employés :</td>
                    <td>" . htmlspecialchars($contact_data['employees']) . "</td>
                </tr>";
    }
    
    if (!empty($contact_data['repairs'])) {
        $html_body .= "
                <tr>
                    <td class='label'>Réparations/mois :</td>
                    <td>" . htmlspecialchars($contact_data['repairs']) . "</td>
                </tr>";
    }
    
    $html_body .= "
                <tr>
                    <td class='label'>Sujet :</td>
                    <td><strong>" . htmlspecialchars($contact_data['subject']) . "</strong></td>
                </tr>
                <tr>
                    <td class='label'>Date :</td>
                    <td>" . date('d/m/Y à H:i:s') . "</td>
                </tr>
            </table>";
    
    if (!empty($contact_data['message'])) {
        $html_body .= "
            <h3>Message :</h3>
            <div class='message-box'>
                " . nl2br(htmlspecialchars($contact_data['message'])) . "
            </div>";
    }
    
    $html_body .= "
            <p><strong>Actions rapides :</strong></p>
            <p>
                <a href='mailto:" . htmlspecialchars($contact_data['email']) . "?subject=Re: " . urlencode($contact_data['subject']) . "' 
                   style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                   Répondre par email
                </a>
            </p>
        </div>
        
        <div class='footer'>
            <p>Cette demande a été reçue depuis <strong>servo.tools</strong></p>
            <p>IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . " | User-Agent: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu') . "</p>
        </div>
    </body>
    </html>";
    
    // Corps texte brut (fallback)
    $text_body = "
Nouvelle demande de contact - SERVO

Informations du contact :
- Nom : " . $contact_data['firstName'] . " " . $contact_data['lastName'] . "
- Email : " . $contact_data['email'] . "
- Téléphone : " . ($contact_data['phone'] ?? 'Non renseigné') . "
- Entreprise : " . ($contact_data['company'] ?? 'Non renseignée') . "
- Sujet : " . $contact_data['subject'] . "
- Date : " . date('d/m/Y à H:i:s') . "

Message :
" . ($contact_data['message'] ?? 'Aucun message') . "

---
Cette demande a été reçue depuis servo.tools
IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "
";
    
    // Reply-to vers l'email du client
    $reply_to = [$contact_data['email'], $contact_data['firstName'] . ' ' . $contact_data['lastName']];
    
    return sendEmail(
        CONTACT_EMAIL,
        'Équipe SERVO',
        $subject,
        $html_body,
        $text_body,
        $reply_to
    );
}

/**
 * Envoie un email de confirmation au client
 * 
 * @param array $contact_data Données du formulaire de contact
 * @return array ['success' => bool, 'message' => string]
 */
function sendContactConfirmation($contact_data) {
    $subject = "Merci pour votre demande de démonstration GeekBoard";
    
    $html_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .highlight { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .btn { background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>Demande reçue avec succès !</h2>
        </div>
        
        <div class='content'>
            <p>Bonjour " . htmlspecialchars($contact_data['firstName']) . ",</p>
            
            <p>Merci pour votre demande de démonstration GeekBoard. Nous avons bien reçu votre demande concernant : <strong>" . htmlspecialchars($contact_data['subject']) . "</strong></p>
            
            <div class='highlight'>
                <h3>⏰ Prochaines étapes</h3>
                <ul>
                    <li><strong>Sous 2 heures :</strong> Notre équipe vous contactera pour fixer un créneau</li>
                    <li><strong>Durée :</strong> 15-30 minutes selon vos besoins</li>
                    <li><strong>Format :</strong> Démonstration en ligne personnalisée</li>
                </ul>
            </div>
            
            <p><strong>Ce que vous allez découvrir :</strong></p>
            <ul>
                <li>Interface GeekBoard en conditions réelles</li>
                <li>Calcul ROI personnalisé pour votre atelier</li>
                <li>Réponses à vos questions spécifiques</li>
                <li>Plan d'implémentation sur-mesure</li>
            </ul>
            
            <p>En attendant, n'hésitez pas à nous contacter si vous avez des questions :</p>
            
            <p style='text-align: center;'>
                <a href='tel:0895795933' class='btn'>☎️ 08 95 79 59 33</a>
                <a href='mailto:servo@maisondugeek.fr' class='btn'>✉️ servo@maisondugeek.fr</a>
            </p>
        </div>
        
        <div class='footer'>
            <p><strong>SERVO by Maison Du Geek</strong></p>
            <p>Logiciel de gestion d'atelier nouvelle génération</p>
            <p>🌐 <a href='https://servo.tools'>servo.tools</a> | 📧 servo@maisondugeek.fr | ☎️ 08 95 79 59 33</p>
        </div>
    </body>
    </html>";
    
    $text_body = "
Bonjour " . $contact_data['firstName'] . ",

Merci pour votre demande de démonstration GeekBoard !

Nous avons bien reçu votre demande concernant : " . $contact_data['subject'] . "

Prochaines étapes :
- Sous 2 heures : Notre équipe vous contactera
- Durée : 15-30 minutes selon vos besoins  
- Format : Démonstration en ligne personnalisée

Contact :
- Téléphone : 08 95 79 59 33
- Email : servo@maisondugeek.fr
- Site : https://servo.tools

À très bientôt !
L'équipe SERVO by Maison Du Geek
";
    
    return sendEmail(
        $contact_data['email'],
        $contact_data['firstName'] . ' ' . $contact_data['lastName'],
        $subject,
        $html_body,
        $text_body
    );
}
?>
