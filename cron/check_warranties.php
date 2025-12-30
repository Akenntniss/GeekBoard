<?php
/**
 * Cron job pour vérifier les garanties qui expirent bientôt
 * Ce fichier doit être exécuté quotidiennement via crontab
 * Exemple crontab: 0 9 * * * /usr/bin/php /var/www/mdgeek.top/cron/check_warranties.php
 */

// Définir le chemin de base
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Désactiver les sessions pour les cron jobs
if (session_status() !== PHP_SESSION_NONE) {
    session_destroy();
}

require_once BASE_PATH . '/config/database.php';

// Force shop context for cron
$_SESSION['shop_id'] = 'mdg'; // Adjust this if needed

$shop_pdo = getShopDBConnection();
require_once BASE_PATH . '/includes/NotificationService.php';

try {
    // Rechercher les garanties qui expirent dans 7 jours
    $stmt = $shop_pdo->prepare("
        SELECT r.id, r.date_garantie_fin, r.type_appareil, r.modele, 
               c.nom, c.prenom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.date_garantie_fin IS NOT NULL
          AND r.date_garantie_fin BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
          AND r.statut NOT IN ('annulee', 'refuse')
        ORDER BY r.date_garantie_fin ASC
    ");
    
    $stmt->execute();
    $expiring_warranties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($expiring_warranties) . " warranties expiring in the next 7 days\n";
    
    foreach ($expiring_warranties as $warranty) {
        // Vérifier si on a déjà envoyé une notification pour cette garantie
        $stmt_check = $shop_pdo->prepare("
            SELECT id FROM notifications 
            WHERE related_id = ? 
              AND related_type = 'warranty'
              AND type = 'warranty_expiring'
              AND created_at > DATE_SUB(NOW(), INTERVAL 8 DAY)
        ");
        $stmt_check->execute([$warranty['id']]);
        
        if ($stmt_check->rowCount() > 0) {
            echo "Notification already sent for warranty #" . $warranty['id'] . "\n";
            continue;
        }
        
        $client_name = trim($warranty['prenom'] . ' ' . $warranty['nom']) ?: 'Client inconnu';
        $device = trim($warranty['type_appareil'] . ' ' . $warranty['modele']);
        $expire_date = date('d/m/Y', strtotime($warranty['date_garantie_fin']));
        $days_left = ceil((strtotime($warranty['date_garantie_fin']) - time()) / 86400);
        
        $title = "Garantie expire bientôt";
        $body = "$client_name - $device - Expire dans $days_left jour(s) ($expire_date)";
        
        NotificationService::sendToAdmins('warranty_expiring', $title, $body, [
            'url' => "/index.php?page=reparations&id=" . $warranty['id'],
            'related_id' => $warranty['id'],
            'related_type' => 'warranty'
        ]);
        
        echo "Notification sent for warranty #" . $warranty['id'] . " - expires on $expire_date\n";
    }
    
    echo "Warranty check completed successfully\n";
    
} catch (Exception $e) {
    error_log("ERROR in check_warranties.php: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
