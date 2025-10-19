<?php
/**
 * Script CRON pour vérifier et désactiver les essais expirés
 * À exécuter quotidiennement : 0 6 * * * php /var/www/mdgeek.top/cron/check_expired_trials.php
 */

// Changer vers le répertoire du script
chdir(__DIR__);

// Inclure les classes nécessaires
require_once('../classes/SubscriptionManager.php');
require_once('../config/database.php');

// Logger les erreurs
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/trial_check.log');

try {
    echo "[" . date('Y-m-d H:i:s') . "] Début de vérification des essais expirés\n";
    
    $subscriptionManager = new SubscriptionManager();
    
    // Obtenir les essais qui vont expirer dans les 3 prochains jours
    $expiringSoon = $subscriptionManager->getTrialsExpiringSoon(3);
    
    if (!empty($expiringSoon)) {
        echo "Essais expirant bientôt: " . count($expiringSoon) . "\n";
        
        foreach ($expiringSoon as $shop) {
            echo "- {$shop['shop_name']} ({$shop['subdomain']}) - {$shop['days_remaining']} jours restants\n";
            
            // Ici vous pourriez ajouter l'envoi d'emails de rappel
            // sendTrialExpirationReminder($shop);
        }
    }
    
    // Désactiver les essais expirés
    $deactivatedCount = $subscriptionManager->deactivateExpiredTrials();
    
    if ($deactivatedCount > 0) {
        echo "Essais expirés désactivés: $deactivatedCount\n";
    } else {
        echo "Aucun essai expiré trouvé\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Vérification terminée avec succès\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERREUR: " . $e->getMessage() . "\n";
    error_log("Erreur check_expired_trials: " . $e->getMessage());
}

/**
 * Fonction pour envoyer un email de rappel (à implémenter)
 */
function sendTrialExpirationReminder($shop) {
    // TODO: Implémenter l'envoi d'email de rappel
    // Exemples de messages selon les jours restants:
    // - 3 jours: "Votre essai se termine bientôt"
    // - 1 jour: "Dernière chance - essai se termine demain"
    // - 0 jour: "Votre essai se termine aujourd'hui"
}
?>
