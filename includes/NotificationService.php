<?php
/**
 * NotificationService - Helper class for sending push notifications
 * 
 * This service provides easy-to-use methods for triggering notifications
 * from anywhere in the application, respecting user preferences.
 */

require_once __DIR__ . '/PushNotifications.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationService {
    
    /**
     * Send a notification to a specific user
     * 
     * @param int $userId Target user ID
     * @param string $type Notification type code (from notification_types table)
     * @param string $title Notification title
     * @param string $body Notification message body
     * @param array $options Additional options (url, related_id, related_type, etc.)
     * @return bool Success status
     */
    public static function send($userId, $type, $title, $body, $options = []) {
        try {
            $pdo = getShopDBConnection();
            
            // Check user preferences
            if (!self::shouldSendNotification($pdo, $userId, $type)) {
                error_log("NotificationService: Notification '$type' disabled for user $userId");
                return false;
            }
            
            // Get notification type info
            $stmt = $pdo->prepare("SELECT * FROM notification_types WHERE type_code = ?");
            $stmt->execute([$type]);
            $typeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $importance = $typeInfo['importance'] ?? 'normale';
            $isImportant = in_array($importance, ['haute', 'critique']);
            
            // Insert notification in database
            $stmt = $pdo->prepare("
                INSERT INTO notifications 
                (user_id, notification_type, message, related_id, related_type, action_url, is_important, is_broadcast, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
            ");
            
            $stmt->execute([
                $userId,
                $type,
                $body,
                $options['related_id'] ?? null,
                $options['related_type'] ?? null,
                $options['url'] ?? null,
                $isImportant ? 1 : 0,
                $_SESSION['user_id'] ?? null
            ]);
            
            // Check if user wants push notifications
            $stmt = $pdo->prepare("
                SELECT push_notification FROM notification_preferences 
                WHERE user_id = ? AND type_notification = ?
            ");
            $stmt->execute([$userId, $type]);
            $pref = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sendPush = $pref ? (bool)$pref['push_notification'] : true;
            
            error_log("NotificationService DEBUG: sendPush=$sendPush for user $userId, type $type");
            
            if ($sendPush) {
                // Send push notification
                error_log("NotificationService DEBUG: Creating PushNotifications instance");
                $pushService = new PushNotifications($pdo);
                error_log("NotificationService DEBUG: Calling sendToUser for user $userId");
                $pushService->sendToUser($userId, $title, $body, [
                    'url' => $options['url'] ?? '/',
                    'icon' => $typeInfo['icon'] ?? '/assets/images/pwa-icons/icon-192x192.png',
                    'tag' => $type,
                    'related_id' => $options['related_id'] ?? null
                ]);
                error_log("NotificationService DEBUG: sendToUser completed");
            }

            // Check if user wants email notifications
            $stmt = $pdo->prepare("
                SELECT email_notification FROM notification_preferences 
                WHERE user_id = ? AND type_notification = ?
            ");
            $stmt->execute([$userId, $type]);
            $prefEmail = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sendEmail = $prefEmail ? (bool)$prefEmail['email_notification'] : false;

            if ($sendEmail) {
                // Check if email notifications are globally enabled for the shop
                $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'email_notifications_enabled'");
                $stmt->execute();
                $globallyEnabled = $stmt->fetchColumn();

                if ($globallyEnabled === '1') {
                    error_log("NotificationService DEBUG: Sending email for user $userId, type $type");
                    self::sendEmailNotification($pdo, $userId, $title, $body, $options);
                } else {
                    error_log("NotificationService DEBUG: Email notifications globally disabled for this shop");
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("NotificationService Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification to all users with a specific role
     */
    public static function sendToRole($role, $type, $title, $body, $options = []) {
        try {
            $pdo = getShopDBConnection();
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ?");
            $stmt->execute([$role]);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($users as $userId) {
                self::send($userId, $type, $title, $body, $options);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("NotificationService Error (sendToRole): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification to all admins and managers
     * @param string $type Notification type
     * @param string $title Notification title  
     * @param string $body Notification body
     * @param array $options Additional options
     * @param int|null $excludeUserId User ID to exclude (to avoid duplicates if already notified)
     */
    public static function sendToAdmins($type, $title, $body, $options = [], $excludeUserId = null) {
        try {
            $pdo = getShopDBConnection();
            
            $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'manager', 'gerant')");
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($users as $userId) {
                // Skip the user if they were already notified individually
                if ($excludeUserId !== null && $userId == $excludeUserId) {
                    continue;
                }
                self::send($userId, $type, $title, $body, $options);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("NotificationService Error (sendToAdmins): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email notification using shop settings
     */
    private static function sendEmailNotification($pdo, $userId, $title, $body, $options) {
        try {
            // Get recipient email
            $stmt = $pdo->prepare("SELECT email, nom, prenom FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || empty($user['email'])) return false;

            // Get shop email settings
            $keys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption', 'email_from_name'];
            $settings = [];
            foreach ($keys as $key) {
                $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = ?");
                $stmt->execute([$key]);
                $settings[$key] = $stmt->fetchColumn() ?: '';
            }

            if (empty($settings['smtp_host']) || empty($settings['smtp_user'])) {
                error_log("NotificationService Error: SMTP settings missing for shop");
                return false;
            }

            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $settings['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $settings['smtp_user'];
            $mail->Password   = $settings['smtp_pass'];
            $mail->SMTPSecure = ($settings['smtp_encryption'] === 'none') ? false : $settings['smtp_encryption'];
            $mail->Port       = $settings['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom($settings['smtp_user'], $settings['email_from_name'] ?: 'GeekBoard');
            $mail->addAddress($user['email'], $user['prenom'] . ' ' . $user['nom']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $title;
            
            $actionUrl = $options['url'] ?? null;
            $buttonHtml = '';
            if ($actionUrl) {
                // Ensure absolute URL if possible, otherwise relative to host
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
                $fullUrl = (strpos($actionUrl, 'http') === 0) ? $actionUrl : "$protocol://$host$actionUrl";
                
                $buttonHtml = "
                    <div style='margin-top: 25px;'>
                        <a href='$fullUrl' style='background-color: #4361ee; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                            Voir les détails
                        </a>
                    </div>";
            }

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;'>
                    <div style='background-color: #4361ee; color: white; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0;'>Notification GeekBoard</h2>
                    </div>
                    <div style='padding: 30px;'>
                        <p style='font-size: 18px; font-weight: bold; margin-top: 0;'>$title</p>
                        <p>$body</p>
                        $buttonHtml
                    </div>
                    <div style='background-color: #f8fafc; color: #64748b; padding: 15px; text-align: center; font-size: 12px;'>
                        <p style='margin: 0;'>Ceci est une notification automatique de GeekBoard.</p>
                        <p style='margin: 5px 0 0 0;'>&copy; " . date('Y') . " Maison Du Geek</p>
                    </div>
                </div>";

            $mail->AltBody = "$title\n\n$body" . ($actionUrl ? "\n\nLien: $fullUrl" : "");

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("NotificationService Email Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if notification should be sent based on user preferences
     */
    private static function shouldSendNotification($pdo, $userId, $type) {
        try {
            $stmt = $pdo->prepare("
                SELECT active FROM notification_preferences 
                WHERE user_id = ? AND type_notification = ?
            ");
            $stmt->execute([$userId, $type]);
            $pref = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no preference exists, default to active
            return $pref ? (bool)$pref['active'] : true;
            
        } catch (Exception $e) {
            return true; // Default to sending on error
        }
    }
    
    // ========================================
    // REPAIR NOTIFICATIONS
    // ========================================
    
    /**
     * Notify when a new repair is created
     */
    public static function notifyRepairCreated($repairId, $modele, $typeAppareil, $creatorId = null) {
        $title = "Nouvelle réparation";
        $body = "Nouvelle réparation créée: $typeAppareil $modele";
        
        return self::sendToAdmins('reparation_start', $title, $body, [
            'url' => "/index.php?page=reparations&id=$repairId&open_modal=1",
            'related_id' => $repairId,
            'related_type' => 'reparation'
        ]);
    }
    
    /**
     * Notify when repair status changes
     */
    public static function notifyRepairStatusChange($repairId, $oldStatus, $newStatus, $employeId = null) {
        try {
            $pdo = getShopDBConnection();
            
            // Get repair info
            $stmt = $pdo->prepare("
                SELECT r.*, c.nom, c.prenom, rs.nom as statut_nom 
                FROM reparations r 
                LEFT JOIN clients c ON r.client_id = c.id
                LEFT JOIN statuts rs ON r.statut = rs.code
                WHERE r.id = ?
            ");
            $stmt->execute([$repairId]);
            $repair = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$repair) return false;
            
            // Determine notification type based on new status
            $type = 'reparation_update';
            if (in_array($newStatus, ['restitue', 'termine', 'repare'])) {
                $type = 'reparation_finish';
            } elseif (in_array($newStatus, ['annule', 'refuse'])) {
                $type = 'reparation_stop';
            } elseif (in_array($newStatus, ['en_cours', 'diagnostique'])) {
                $type = 'reparation_start';
            }
            
            $clientName = trim(($repair['nom'] ?? '') . ' ' . ($repair['prenom'] ?? ''));
            $statutNom = $repair['statut_nom'] ?? $newStatus;
            
            $title = "Changement de statut réparation";
            $body = "Réparation #{$repairId} ($clientName) → $statutNom";
            
            $options = [
                'url' => "/index.php?page=reparations&id=$repairId&open_modal=1",
                'related_id' => $repairId,
                'related_type' => 'reparation'
            ];
            
            // Notify assigned employee if exists
            if ($employeId) {
                self::send($employeId, $type, $title, $body, $options);
            }
            
            // Notify admins (excluding the employee already notified to avoid duplicates)
            return self::sendToAdmins($type, $title, $body, $options, $employeId);
            
        } catch (Exception $e) {
            error_log("NotificationService Error (notifyRepairStatusChange): " . $e->getMessage());
            return false;
        }
    }
    
    // ========================================
    // TASK NOTIFICATIONS
    // ========================================
    
    /**
     * Notify when a new task is created
     */
    public static function notifyTaskCreated($taskId, $title, $assigneeId = null) {
        $notifTitle = "Nouvelle tâche";
        $body = "Tâche: $title";
        
        $options = [
            'url' => "/index.php?page=taches&id=$taskId",
            'related_id' => $taskId,
            'related_type' => 'tache'
        ];
        
        // If assigned to specific employee, notify them
        if ($assigneeId) {
            $notifTitle = "Nouvelle tâche assignée";
            return self::send($assigneeId, 'task_assigned', $notifTitle, $body, $options);
        } else {
            // If no specific employee, notify ALL users
            try {
                $pdo = getShopDBConnection();
                $stmt = $pdo->query("SELECT id FROM users");
                $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($users as $userId) {
                    self::send($userId, 'task_created', $notifTitle, $body, $options);
                }
                return true;
            } catch (Exception $e) {
                error_log("NotificationService Error (notifyTaskCreated to all): " . $e->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Notify when a task is completed
     */
    public static function notifyTaskCompleted($taskId, $taskTitle) {
        $title = "Tâche terminée";
        $body = "La tâche '$taskTitle' a été marquée comme terminée";
        
        return self::sendToAdmins('task_completed', $title, $body, [
            'url' => "/index.php?page=taches&id=$taskId",
            'related_id' => $taskId,
            'related_type' => 'tache'
        ]);
    }
    
    // ========================================
    // QUOTE NOTIFICATIONS
    // ========================================
    
    /**
     * Notify when a quote is accepted
     */
    public static function notifyQuoteAccepted($devisId, $repairId) {
        try {
            $pdo = getShopDBConnection();
            
            $stmt = $pdo->prepare("
                SELECT d.numero_devis, r.modele, c.nom, c.prenom
                FROM devis d
                JOIN reparations r ON d.reparation_id = r.id
                LEFT JOIN clients c ON r.client_id = c.id
                WHERE d.id = ?
            ");
            $stmt->execute([$devisId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $clientName = trim(($info['nom'] ?? '') . ' ' . ($info['prenom'] ?? ''));
            
            $title = "✅ Devis accepté";
            $body = "Le devis {$info['numero_devis']} ($clientName) a été accepté";
            
            return self::sendToAdmins('devis_accepte', $title, $body, [
                'url' => "/index.php?page=reparations&id=$repairId&open_modal=1",
                'related_id' => $devisId,
                'related_type' => 'devis'
            ]);
            
        } catch (Exception $e) {
            error_log("NotificationService Error (notifyQuoteAccepted): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify when a quote is refused
     */
    public static function notifyQuoteRefused($devisId, $repairId) {
        try {
            $pdo = getShopDBConnection();
            
            $stmt = $pdo->prepare("
                SELECT d.numero_devis, r.modele, c.nom, c.prenom
                FROM devis d
                JOIN reparations r ON d.reparation_id = r.id
                LEFT JOIN clients c ON r.client_id = c.id
                WHERE d.id = ?
            ");
            $stmt->execute([$devisId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $clientName = trim(($info['nom'] ?? '') . ' ' . ($info['prenom'] ?? ''));
            
            $title = "❌ Devis refusé";
            $body = "Le devis {$info['numero_devis']} ($clientName) a été refusé";
            
            return self::sendToAdmins('devis_refuse', $title, $body, [
                'url' => "/index.php?page=reparations&id=$repairId&open_modal=1",
                'related_id' => $devisId,
                'related_type' => 'devis'
            ]);
            
        } catch (Exception $e) {
            error_log("NotificationService Error (notifyQuoteRefused): " . $e->getMessage());
            return false;
        }
    }
    
    // ========================================
    // BUYBACK NOTIFICATIONS
    // ========================================
    
    /**
     * Notify when a buyback (rachat) is created
     */
    public static function notifyRachatCreated($rachatId, $typeAppareil, $marque = '') {
        $title = "Nouveau rachat";
        $body = "Nouveau rachat enregistré: $marque $typeAppareil";
        
        return self::sendToAdmins('rachat_create', $title, $body, [
            'url' => "/index.php?page=rachat_appareils&id=$rachatId",
            'related_id' => $rachatId,
            'related_type' => 'rachat'
        ]);
    }
    
    // ========================================
    // STOCK NOTIFICATIONS
    // ========================================
    
    /**
     * Notify when stock is low
     */
    public static function notifyLowStock($productId, $productName, $quantity, $threshold) {
        $title = "⚠️ Stock bas";
        $body = "$productName: $quantity unités restantes (seuil: $threshold)";
        
        return self::sendToAdmins('stock_low', $title, $body, [
            'url' => "/index.php?page=inventaire",
            'related_id' => $productId,
            'related_type' => 'produit'
        ]);
    }
    
    /**
     * Notify when stock is out
     */
    public static function notifyStockOut($productId, $productName) {
        $title = "🚨 Rupture de stock";
        $body = "$productName: RUPTURE DE STOCK";
        
        return self::sendToAdmins('stock_out', $title, $body, [
            'url' => "/index.php?page=inventaire",
            'related_id' => $productId,
            'related_type' => 'produit'
        ]);
    }
    
    // ========================================
    // ORDER NOTIFICATIONS
    // ========================================
    
    /**
     * Notify when a parts order is created
     */
    public static function notifyOrderCreated($orderId, $productName, $fournisseur) {
        $title = "Nouvelle commande pièce";
        $body = "Commande: $productName (Fournisseur: $fournisseur)";
        
        return self::sendToAdmins('commande_create', $title, $body, [
            'url' => "/index.php?page=commandes_pieces",
            'related_id' => $orderId,
            'related_type' => 'commande'
        ]);
    }
    
    /**
     * Notify when a parts order is received
     */
    public static function notifyOrderReceived($orderId, $productName) {
        $title = "📦 Commande reçue";
        $body = "La commande '$productName' a été reçue";
        
        return self::sendToAdmins('commande_received', $title, $body, [
            'url' => "/index.php?page=commandes_pieces",
            'related_id' => $orderId,
            'related_type' => 'commande'
        ]);
    }
}
