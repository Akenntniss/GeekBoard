#!/usr/bin/env php
<?php
/**
 * Cron Job - Vérification des expirations d'essai
 * 
 * À exécuter quotidiennement à 9h00:
 * 0 9 * * * php /path/to/superadmin/cron/check_trial_expirations.php >> /var/log/geekboard_cron.log 2>&1
 * 
 * @author GeekBoard
 * @version 1.0
 */

// Charger les dépendances
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../classes/EmailService.php');

echo "[" . date('Y-m-d H:i:s') . "] Starting trial expiration check...\n";

try {
    $pdo = getMainDBConnection();
    $emailService = EmailService::getInstance();
    
    // Récupérer tous les shops en période d'essai actifs
    $stmt = $pdo->query("
        SELECT * FROM shops 
        WHERE subscription_status = 'trial' 
        AND active = 1
        AND trial_ends_at IS NOT NULL
        ORDER BY trial_ends_at ASC
    ");
    
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($shops) . " shops in trial period.\n";
    
    $notifications_sent = 0;
    $shops_deactivated = 0;
    
    foreach ($shops as $shop) {
        $trial_end = new DateTime($shop['trial_ends_at']);
        $now = new DateTime();
        $diff = $now->diff($trial_end);
        $days_left = (int)$diff->format('%r%a'); // Négatif si expiré
        
        echo "\nShop: {$shop['name']} ({$shop['subdomain']}) - Days left: $days_left\n";
        
        // Cas 1: Essai expiré (J-0 ou passé)
        if ($days_left <= 0) {
            echo "  -> Trial EXPIRED. Deactivating shop...\n";
            
            // Désactiver le shop
            $updateStmt = $pdo->prepare("
                UPDATE shops 
                SET active = 0, subscription_status = 'expired'
                WHERE id = ?
            ");
            $updateStmt->execute([$shop['id']]);
            
            // Envoyer notification d'expiration
            if ($emailService->sendTrialExpiredNotification($shop)) {
                echo "  -> Expiration email sent successfully.\n";
                $notifications_sent++;
            } else {
                echo "  -> Failed to send expiration email.\n";
            }
            
            $shops_deactivated++;
        }
        // Cas 2: J-7 (7 jours restants)
        elseif ($days_left === 7) {
            echo "  -> 7 days left. Sending warning email...\n";
            
            if ($emailService->sendTrialExpiringNotification($shop, 7)) {
                echo "  -> Warning email (J-7) sent successfully.\n";
                $notifications_sent++;
            } else {
                echo "  -> Failed to send warning email (J-7).\n";
            }
        }
        // Cas 3: J-3 (3 jours restants)
        elseif ($days_left === 3) {
            echo "  -> 3 days left. Sending urgent warning email...\n";
            
            if ($emailService->sendTrialExpiringNotification($shop, 3)) {
                echo "  -> Urgent warning email (J-3) sent successfully.\n";
                $notifications_sent++;
            } else {
                echo "  -> Failed to send urgent warning email (J-3).\n";
            }
        }
        // Cas 4: J-1 (dernier jour)
        elseif ($days_left === 1) {
            echo "  -> 1 day left. Sending final warning email...\n";
            
            if ($emailService->sendTrialExpiringNotification($shop, 1)) {
                echo "  -> Final warning email (J-1) sent successfully.\n";
                $notifications_sent++;
            } else {
                echo "  -> Failed to send final warning email (J-1).\n";
            }
        }
        else {
            echo "  -> No action needed yet ($days_left days remaining).\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "SUMMARY:\n";
    echo "  - Shops checked: " . count($shops) . "\n";
    echo "  - Notifications sent: $notifications_sent\n";
    echo "  - Shops deactivated: $shops_deactivated\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "[" . date('Y-m-d H:i:s') . "] Trial expiration check completed successfully.\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
