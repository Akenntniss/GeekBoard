<?php
/**
 * Cron Job: Fix Missing Trial Periods
 * 
 * Ce script initialise automatiquement les périodes d'essai pour les shops
 * qui ont subscription_status = 'trial' mais trial_started_at = NULL.
 * 
 * Exécution recommandée: 1x par jour (via crontab)
 * Exemple crontab: 0 2 * * * php /var/www/mdgeek.top/cron/fix_missing_trials.php >> /var/log/fix_trials.log 2>&1
 */

// Configuration
$log_prefix = "[" . date('Y-m-d H:i:s') . "] ";

echo $log_prefix . "=== Début du script fix_missing_trials ===\n";

try {
    // Charger les dépendances
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/SubscriptionManager.php';
    
    // Connexion à la base principale
    $pdo = getMainDBConnection();
    
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données principale");
    }
    
    // Trouver les shops sans période d'essai configurée
    $sql = "SELECT id, name, subdomain 
            FROM shops 
            WHERE subscription_status = 'trial' 
              AND trial_started_at IS NULL 
              AND active = 1
            ORDER BY id ASC";
    
    $stmt = $pdo->query($sql);
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = count($shops);
    echo $log_prefix . "Shops à corriger: $total\n";
    
    if ($total === 0) {
        echo $log_prefix . "Aucun shop à corriger. Fin du script.\n";
        exit(0);
    }
    
    $success = 0;
    $failed = 0;
    
    foreach ($shops as $shop) {
        $shop_id = $shop['id'];
        $shop_name = $shop['name'];
        $subdomain = $shop['subdomain'];
        
        echo $log_prefix . "Traitement du shop #$shop_id ($subdomain)... ";
        
        try {
            $manager = new SubscriptionManager($shop_id);
            $result = $manager->initializeTrialPeriod($shop_id);
            
            if ($result) {
                echo "OK\n";
                $success++;
            } else {
                echo "ECHEC (retour false)\n";
                $failed++;
            }
        } catch (Exception $e) {
            echo "ERREUR: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
    
    echo $log_prefix . "=== Résumé ===\n";
    echo $log_prefix . "Total traités: $total\n";
    echo $log_prefix . "Succès: $success\n";
    echo $log_prefix . "Échecs: $failed\n";
    echo $log_prefix . "=== Fin du script ===\n";
    
    exit($failed > 0 ? 1 : 0);
    
} catch (Exception $e) {
    echo $log_prefix . "ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}
