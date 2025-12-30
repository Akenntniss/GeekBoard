<?php
/**
 * EmailService - Gestion centralisée des emails avec PHPMailer
 * 
 * @author GeekBoard
 * @version 1.0
 */

require_once(__DIR__ . '/../superadmin/vendor/phpmailer/src/Exception.php');
require_once(__DIR__ . '/../superadmin/vendor/phpmailer/src/PHPMailer.php');
require_once(__DIR__ . '/../superadmin/vendor/phpmailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    private static $instance = null;
    private $pdo;
    private $config;
    private $templatesDir;
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor privé
     */
    private function __construct() {
        require_once(__DIR__ . '/../config/database.php');
        $this->pdo = getMainDBConnection();
        $this->templatesDir = __DIR__ . '/../superadmin/email-templates/';
        $this->loadConfig();
    }
    
    /**
     * Charger la configuration depuis la DB
     */
    private function loadConfig() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM email_config WHERE id = 1 LIMIT 1");
            $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$this->config) {
                throw new Exception('Email configuration not found in database');
            }
        } catch (Exception $e) {
            error_log("EmailService: Failed to load config - " . $e->getMessage());
            $this->config = null;
        }
    }
    
    /**
     * Envoyer notification de nouveau magasin
     */
    public function sendNewShopNotification($shop) {
        $subject = '🎉 Nouveau Magasin Créé - ' . $shop['name'];
        
        $replacements = [
            '{{shop_name}}' => htmlspecialchars($shop['name']),
            '{{subdomain}}' => htmlspecialchars($shop['subdomain']),
            '{{created_at}}' => date('d/m/Y à H:i', strtotime($shop['created_at'])),
            '{{shop_id}}' => $shop['id'],
            '{{description}}' => htmlspecialchars($shop['description'] ?? 'Aucune description'),
            '{{city}}' => htmlspecialchars($shop['city'] ?? 'Non défini'),
            '{{phone}}' => htmlspecialchars($shop['phone'] ?? 'Non défini'),
        ];
        
        $htmlBody = $this->loadTemplate('new_shop.html', $replacements);
        
        return $this->send(
            $this->config['admin_email'],
            $subject,
            $htmlBody,
            'new_shop',
            $shop['id']
        );
    }
    
    /**
     * Envoyer notification d'expiration d'essai (J-7, J-3)
     */
    public function sendTrialExpiringNotification($shop, $daysLeft) {
        $icons = [7 => '⚠️', 3 => '🔔', 1 => '⏰'];
        $icon = $icons[$daysLeft] ?? '⏰';
        
        $subject = "$icon Essai expire dans $daysLeft jour(s) - " . $shop['name'];
        
        $replacements = [
            '{{shop_name}}' => htmlspecialchars($shop['name']),
            '{{subdomain}}' => htmlspecialchars($shop['subdomain']),
            '{{days_left}}' => $daysLeft,
            '{{trial_ends_at}}' => date('d/m/Y', strtotime($shop['trial_ends_at'])),
            '{{shop_id}}' => $shop['id'],
        ];
        
        $htmlBody = $this->loadTemplate('trial_expiring.html', $replacements);
        
        $type = 'trial_expiring_' . $daysLeft . 'd';
        
        return $this->send(
            $this->config['admin_email'],
            $subject,
            $htmlBody,
            $type,
            $shop['id']
        );
    }
    
    /**
     * Envoyer notification d'essai expiré
     */
    public function sendTrialExpiredNotification($shop) {
        $subject = '❌ Essai expiré - ' . $shop['name'];
        
        $replacements = [
            '{{shop_name}}' => htmlspecialchars($shop['name']),
            '{{subdomain}}' => htmlspecialchars($shop['subdomain']),
            '{{trial_ended_at}}' => date('d/m/Y', strtotime($shop['trial_ends_at'])),
            '{{shop_id}}' => $shop['id'],
        ];
        
        $htmlBody = $this->loadTemplate('trial_expired.html', $replacements);
        
        return $this->send(
            $this->config['admin_email'],
            $subject,
            $htmlBody,
            'trial_expired',
            $shop['id']
        );
    }
    
    /**
     * Envoyer email de test
     */
    public function sendTestEmail($email = null) {
        $recipient = $email ?? $this->config['admin_email'];
        $subject = '✅ Test Email - GeekBoard SuperAdmin';
        
        $htmlBody = $this->loadTemplate('test.html', [
            '{{test_date}}' => date('d/m/Y à H:i:s'),
            '{{smtp_host}}' => $this->config['smtp_host'],
        ]);
        
        return $this->send($recipient, $subject, $htmlBody, 'test', null);
    }
    
    /**
     * Charger un template HTML
     */
    private function loadTemplate($filename, $replacements = []) {
        $filepath = $this->templatesDir . $filename;
        
        if (!file_exists($filepath)) {
            error_log("EmailService: Template not found - $filepath");
            return $this->getDefaultTemplate($replacements);
        }
        
        $html = file_get_contents($filepath);
        
        // Remplacer les variables
        foreach ($replacements as $key => $value) {
            $html = str_replace($key, $value, $html);
        }
        
        return $html;
    }
    
    /**
     * Template par défaut si fichier n'existe pas
     */
    private function getDefaultTemplate($replacements) {
        $content = '';
        foreach ($replacements as $key => $value) {
            $label = str_replace(['{{', '}}'], '', $key);
            $content .= "<p><strong>$label:</strong> $value</p>";
        }
        
        return "<!DOCTYPE html>
<html>
<body style='font-family: Arial, sans-serif; padding: 20px;'>
    <div style='max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 8px;'>
        <h2>GeekBoard Notification</h2>
        $content
    </div>
</body>
</html>";
    }
    
    /**
     * Envoyer l'email via PHPMailer
     */
    private function send($to, $subject, $htmlBody, $type = 'manual', $shopId = null) {
        // Vérifier si activé
        if (!$this->config || !$this->config['enabled']) {
            $this->log($shopId, $type, $to, $subject, 'failed', 'Email service is disabled');
            return false;
        }
        
        // Vérifier si déjà envoyé aujourd'hui (éviter doublons)
        if ($shopId && $type != 'test' && $type != 'manual') {
            if ($this->wasAlreadySent($shopId, $type)) {
                $this->log($shopId, $type, $to, $subject, 'failed', 'Already sent today');
                return false;
            }
        }
        
        try {
            $mail = new PHPMailer(true);
            
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = $this->config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Expéditeur
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Destinataire
            $mail->addAddress($to);
            
            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            
            // Envoyer
            $result = $mail->send();
            
            if ($result) {
                $this->log($shopId, $type, $to, $subject, 'sent', null, $htmlBody);
                $this->markAsSent($shopId, $type);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $errorMsg = $mail->ErrorInfo;
            error_log("EmailService: Failed to send - $errorMsg");
            $this->log($shopId, $type, $to, $subject, 'failed', $errorMsg, $htmlBody);
            return false;
        }
    }
    
    /**
     * Logger l'envoi dans la DB
     */
    private function log($shopId, $type, $recipient, $subject, $status, $error = null, $bodyHtml = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (shop_id, type, recipient_email, subject, body_html, status, error_message, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $shopId,
                $type,
                $recipient,
                $subject,
                $bodyHtml,
                $status,
                $error,
                $status === 'sent' ? date('Y-m-d H:i:s') : null
            ]);
        } catch (Exception $e) {
            error_log("EmailService: Failed to log - " . $e->getMessage());
        }
    }
    
    /**
     * Vérifier si notification déjà envoyée aujourd'hui
     */
    private function wasAlreadySent($shopId, $type) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM email_notifications_sent 
                WHERE shop_id = ? AND notification_type = ? AND sent_date = CURDATE()
            ");
            $stmt->execute([$shopId, $type]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Marquer comme envoyé aujourd'hui
     */
    private function markAsSent($shopId, $type) {
        if (!$shopId || $type === 'test' || $type === 'manual') {
            return;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO email_notifications_sent (shop_id, notification_type, sent_date)
                VALUES (?, ?, CURDATE())
            ");
            $stmt->execute([$shopId, $type]);
        } catch (Exception $e) {
            error_log("EmailService: Failed to mark as sent - " . $e->getMessage());
        }
    }
    
    /**
     * Tester la connexion SMTP
     */
    public function testConnection() {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = $this->config['smtp_port'];
            $mail->Timeout = 10;
            
            // Tester la connexion
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                return ['success' => true, 'message' => 'Connexion SMTP réussie'];
            }
            
            return ['success' => false, 'message' => 'Impossible de se connecter au serveur SMTP'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Recharger la configuration (après modification)
     */
    public function reloadConfig() {
        $this->loadConfig();
    }
}
