<?php
/**
 * Gestionnaire des abonnements et périodes d'essai
 * Système d'essai gratuit de 30 jours pour GeekBoard
 */
class SubscriptionManager {
    private $pdo;
    
    public function __construct($pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            // Connexion à la base principale
            $this->pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }
    
    /**
     * Initialiser la période d'essai pour un nouveau shop
     */
    public function initializeTrialPeriod($shop_id) {
        try {
            $trial_end = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Mettre à jour le shop avec les dates d'essai
            $stmt = $this->pdo->prepare("
                UPDATE shops 
                SET trial_started_at = NOW(),
                    trial_ends_at = ?,
                    subscription_status = 'trial',
                    active = 1
                WHERE id = ?
            ");
            $stmt->execute([$trial_end, $shop_id]);
            
            // Créer l'enregistrement de subscription en mode trial (Plan Professional pour l'essai)
            $stmt = $this->pdo->prepare("
                INSERT INTO subscriptions (shop_id, plan_id, status, trial_start_date, trial_end_date)
                VALUES (?, 2, 'trial', NOW(), ?)
            ");
            $stmt->execute([$shop_id, $trial_end]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur initialisation période d'essai : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier le statut d'abonnement d'un shop
     */
    public function checkShopSubscriptionStatus($shop_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id as shop_id,
                    s.active,
                    s.subscription_status,
                    s.trial_started_at,
                    s.trial_ends_at,
                    DATEDIFF(s.trial_ends_at, NOW()) as days_remaining,
                    sub.id as subscription_id,
                    sp.name as plan_name,
                    sp.price as plan_price
                FROM shops s
                LEFT JOIN subscriptions sub ON s.id = sub.shop_id AND sub.status IN ('trial', 'active')
                LEFT JOIN subscription_plans sp ON sub.plan_id = sp.id
                WHERE s.id = ?
            ");
            $stmt->execute([$shop_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur vérification statut abonnement : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si un shop a accès (période d'essai valide ou abonnement actif)
     */
    public function hasAccess($shop_id) {
        $status = $this->checkShopSubscriptionStatus($shop_id);
        
        if (!$status) {
            return false;
        }
        
        // Si actif et en période d'essai valide
        if ($status['active'] == 1 && $status['subscription_status'] == 'trial') {
            return $status['days_remaining'] >= 0;
        }
        
        // Si abonnement actif
        if ($status['active'] == 1 && $status['subscription_status'] == 'active') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtenir les shops dont l'essai va expirer bientôt
     */
    public function getTrialsExpiringSoon($days = 3) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id,
                    s.name,
                    s.subdomain,
                    s.trial_ends_at,
                    DATEDIFF(s.trial_ends_at, NOW()) as days_remaining,
                    so.email as owner_email,
                    so.prenom,
                    so.nom
                FROM shops s
                JOIN shop_owners so ON s.id = so.shop_id
                WHERE s.subscription_status = 'trial'
                AND s.active = 1
                AND DATEDIFF(s.trial_ends_at, NOW()) <= ?
                AND DATEDIFF(s.trial_ends_at, NOW()) >= 0
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur récupération essais expirant : " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Désactiver les shops dont l'essai a expiré
     */
    public function deactivateExpiredTrials() {
        try {
            // Désactiver les shops expirés
            $stmt = $this->pdo->prepare("
                UPDATE shops 
                SET active = 0, 
                    subscription_status = 'expired'
                WHERE subscription_status = 'trial' 
                AND trial_ends_at < NOW() 
                AND active = 1
            ");
            $affected = $stmt->execute();
            
            // Mettre à jour les subscriptions expirées
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET status = 'expired'
                WHERE status = 'trial' 
                AND trial_end_date < NOW()
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Erreur désactivation essais expirés : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir les plans d'abonnement disponibles
     */
    public function getAvailablePlans($billing_period = null) {
        try {
            $sql = "SELECT * FROM subscription_plans WHERE active = 1";
            $params = [];
            
            if ($billing_period) {
                $sql .= " AND billing_period = ?";
                $params[] = $billing_period;
            }
            
            $sql .= " ORDER BY price ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur récupération plans : " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Créer un abonnement (après paiement Stripe)
     */
    public function createSubscription($shop_id, $plan_id, $stripe_subscription_id, $stripe_customer_id) {
        try {
            $this->pdo->beginTransaction();
            
            // Obtenir les détails du plan
            $stmt = $this->pdo->prepare("SELECT billing_period FROM subscription_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculer les dates de période
            $period_start = date('Y-m-d H:i:s');
            if ($plan['billing_period'] == 'yearly') {
                $period_end = date('Y-m-d H:i:s', strtotime('+1 year'));
            } else {
                $period_end = date('Y-m-d H:i:s', strtotime('+1 month'));
            }
            
            // Mettre à jour le shop
            $stmt = $this->pdo->prepare("
                UPDATE shops 
                SET subscription_status = 'active',
                    active = 1
                WHERE id = ?
            ");
            $stmt->execute([$shop_id]);
            
            // Mettre à jour l'abonnement existant ou en créer un nouveau
            $stmt = $this->pdo->prepare("
                INSERT INTO subscriptions 
                (shop_id, plan_id, status, current_period_start, current_period_end, stripe_subscription_id, stripe_customer_id)
                VALUES (?, ?, 'active', ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                plan_id = VALUES(plan_id),
                status = 'active',
                current_period_start = VALUES(current_period_start),
                current_period_end = VALUES(current_period_end),
                stripe_subscription_id = VALUES(stripe_subscription_id),
                stripe_customer_id = VALUES(stripe_customer_id)
            ");
            $stmt->execute([$shop_id, $plan_id, $period_start, $period_end, $stripe_subscription_id, $stripe_customer_id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur création abonnement : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Annuler un abonnement
     */
    public function cancelSubscription($shop_id) {
        try {
            // Mettre à jour le shop
            $stmt = $this->pdo->prepare("
                UPDATE shops 
                SET subscription_status = 'cancelled'
                WHERE id = ?
            ");
            $stmt->execute([$shop_id]);
            
            // Mettre à jour l'abonnement
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled'
                WHERE shop_id = ? AND status = 'active'
            ");
            $stmt->execute([$shop_id]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur annulation abonnement : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enregistrer une transaction de paiement
     */
    public function recordPaymentTransaction($subscription_id, $amount, $currency, $status, $stripe_payment_intent_id = null, $description = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_transactions 
                (subscription_id, amount, currency, status, stripe_payment_intent_id, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$subscription_id, $amount, $currency, $status, $stripe_payment_intent_id, $description]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Erreur enregistrement transaction : " . $e->getMessage());
            return false;
        }
    }
}
?>
