<?php
// classes/SubscriptionManager.php

class SubscriptionManager {
    private $pdo;
    private $shop_id;

    public function __construct($shop_id = null) {
        $this->shop_id = $shop_id;
        // On a besoin de la connexion principale car les infos d'abonnement sont dans geekboard_general
        $this->pdo = getMainDBConnection();
    }

    /**
     * Récupère les informations complètes de l'abonnement du shop
     */
    public function getSubscriptionInfo() {
        // On essaye d'abord la vue si elle existe, sinon jointure manuelle
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM shop_subscription_info WHERE shop_id = ?");
            $stmt->execute([$this->shop_id]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($info) {
                return $info;
            }
        } catch (Exception $e) {
            // La vue n'existe peut-être pas encore, fallback sur requête manuelle
             error_log("Fallback subscription info: " . $e->getMessage());
        }

        // Requête directe si la vue échoue
        $sql = "SELECT 
                    s.id as shop_id,
                    s.name as shop_name,
                    s.subdomain,
                    s.active,
                    s.subscription_status,
                    s.trial_started_at,
                    s.trial_ends_at,
                    DATEDIFF(s.trial_ends_at, NOW()) as days_remaining,
                    sub.id as subscription_id,
                    sp.name as plan_name,
                    sp.price as plan_price,
                    sp.currency,
                    sp.billing_period,
                    sub.current_period_start,
                    sub.current_period_end
                FROM shops s
                LEFT JOIN subscriptions sub ON s.id = sub.shop_id AND sub.status IN ('trial', 'active')
                LEFT JOIN subscription_plans sp ON sub.plan_id = sp.id
                WHERE s.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->shop_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calcule le pourcentage de temps écoulé (pour la barre de progression)
     */
    public function getTrialProgress($subInfo) {
        // Vérifier que les dates ne sont pas null avant strtotime
        if (empty($subInfo['trial_started_at']) || empty($subInfo['trial_ends_at'])) {
            return 0;
        }
        
        $start = strtotime($subInfo['trial_started_at']);
        $end = strtotime($subInfo['trial_ends_at']);
        $now = time();
        
        $total = $end - $start;
        $elapsed = $now - $start;
        
        if ($total <= 0) {
            return 100;
        }
        
        $progress = ($elapsed / $total) * 100;
        return min(100, max(0, $progress));
    }

    /**
     * Récupère tous les plans disponibles
     */
    public function getAvailablePlans() {
        $stmt = $this->pdo->query("SELECT * FROM subscription_plans WHERE active = 1 ORDER BY price ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère l'historique des factures/transactions
     */
    public function getInvoices($limit = 10) {
        $sql = "SELECT pt.*, s.status as sub_status 
                FROM payment_transactions pt
                JOIN subscriptions s ON pt.subscription_id = s.id
                WHERE s.shop_id = ?
                ORDER BY pt.created_at DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $this->shop_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Pour le CRON : Récupère les essais expirant bientôt
     */
    public function getTrialsExpiringSoon($days = 3) {
        $sql = "SELECT s.id, s.name as shop_name, s.subdomain, 
                       DATEDIFF(sub.trial_end_date, NOW()) as days_remaining
                FROM shops s
                JOIN subscriptions sub ON s.id = sub.shop_id
                WHERE sub.status = 'trial' 
                AND DATEDIFF(sub.trial_end_date, NOW()) <= ? 
                AND DATEDIFF(sub.trial_end_date, NOW()) >= 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pour le CRON : Désactive les essais expirés
     */
    public function deactivateExpiredTrials() {
        // Marquer comme expiré
        $sql = "UPDATE subscriptions 
                SET status = 'expired' 
                WHERE status = 'trial' AND trial_end_date < NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $count = $stmt->rowCount();

        // On pourrait aussi désactiver le shop : 
        // UPDATE shops SET active = 0 WHERE id IN (SELECT shop_id FROM subscriptions WHERE status = 'expired')
        
        return $count;
    }

    /**
     * Vérifie le statut d'abonnement d'un shop (Alias pour le middleware)
     */
    public function checkShopSubscriptionStatus($shop_id) {
        // Si l'instance a déjà été initialisée avec ce shop_id, on peut utiliser getSubscriptionInfo
        if ($this->shop_id == $shop_id) {
            return $this->getSubscriptionInfo();
        }

        // Sinon, on doit créer une nouvelle instance ou adapter la requête... 
        // Pour faire simple et éviter de casser l'instance courante, on fait une requête statique-like
        // Mais comme getSubscriptionInfo utilise $this->shop_id... 
        
        // Solution propre : On utilise l'id passé en paramètre
        $sql = "SELECT 
                    s.id as shop_id,
                    s.name as shop_name,
                    s.subdomain,
                    s.active,
                    s.subscription_status,
                    s.trial_started_at,
                    s.trial_ends_at,
                    DATEDIFF(s.trial_ends_at, NOW()) as days_remaining,
                    sub.id as subscription_id,
                    sub.status as sub_status
                FROM shops s
                LEFT JOIN subscriptions sub ON s.id = sub.shop_id AND sub.status IN ('trial', 'active')
                WHERE s.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$shop_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si le shop a accès (Basé sur le statut d'abonnement)
     */
    public function hasAccess($shop_id) {
        $info = $this->checkShopSubscriptionStatus($shop_id);
        
        if (!$info) return false;
        
        // Si le shop est marqué comme inactif administrativement
        if (isset($info['active']) && $info['active'] == 0) return false;
        
        // Vérification du statut de l'abonnement
        // On regarde d'abord la table subscriptions
        if (!empty($info['sub_status']) && in_array($info['sub_status'], ['active', 'trial'])) {
            // Si c'est un essai, vérifier la date
            if ($info['sub_status'] === 'trial') {
                if ($info['days_remaining'] < 0) return false;
            }
            return true;
        }
        
        // Fallback sur le champ subscription_status de la table shops
        if (isset($info['subscription_status']) && in_array($info['subscription_status'], ['active', 'trial'])) {
             if ($info['subscription_status'] === 'trial') {
                if ($info['days_remaining'] < 0) return false;
            }
            return true;
        }
        
        return false;
    }
    /**
     * Récupère les statistiques d'utilisation (SMS, Clients)
     */
    public function getUsageStats($shop_id = null) {
        $target_shop_id = $shop_id ?: $this->shop_id;
        if (!$target_shop_id) return ['sms_count' => 0, 'client_count' => 0];

        // Connexion à la DB du shop
        // On suppose que getShopDBConnectionById est disponible (défini dans database.php)
        if (!function_exists('getShopDBConnectionById')) {
             return ['sms_count' => 0, 'client_count' => 0];
        }

        $shop_pdo = getShopDBConnectionById($target_shop_id);
        
        if (!$shop_pdo) return ['sms_count' => 0, 'client_count' => 0];

        try {
            // Count SMS
            $stmt = $shop_pdo->query("SELECT COUNT(*) FROM sms_logs");
            $sms_count = $stmt->fetchColumn();

            // Count Clients
            $stmt = $shop_pdo->query("SELECT COUNT(*) FROM clients");
            $client_count = $stmt->fetchColumn();

            return [
                'sms_count' => $sms_count,
                'client_count' => $client_count
            ];
        } catch (Exception $e) {
            error_log("Erreur stats usage: " . $e->getMessage());
            return ['sms_count' => 0, 'client_count' => 0];
        }
    }
    /**
     * Initialise la période d'essai pour un magasin
     */
    public function initializeTrialPeriod($shop_id) {
        try {
            // Calculer la date de fin (30 jours)
            $trial_days = 30;
            $trial_end = date('Y-m-d H:i:s', strtotime("+$trial_days days"));
            $now = date('Y-m-d H:i:s');
            
            // 1. Mettre à jour le shop avec les dates d'essai
            $sql = "UPDATE shops 
                    SET trial_started_at = :now,
                        trial_ends_at = :trial_end,
                        subscription_status = 'trial',
                        active = 1
                    WHERE id = :shop_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':now' => $now,
                ':trial_end' => $trial_end,
                ':shop_id' => $shop_id
            ]);
            
            // 2. Trouver l'ID du plan "Professional" pour l'essai
            $stmt = $this->pdo->prepare("SELECT id FROM subscription_plans WHERE name LIKE '%Professional%' AND billing_period = 'monthly' LIMIT 1");
            $stmt->execute();
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            $plan_id = $plan ? $plan['id'] : 2; // Default to 2 if not found
            
            // 3. Créer l'enregistrement de subscription
            $sql = "INSERT INTO subscriptions (shop_id, plan_id, status, trial_start_date, trial_end_date)
                    VALUES (:shop_id, :plan_id, 'trial', :now, :trial_end)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':shop_id' => $shop_id,
                ':plan_id' => $plan_id,
                ':now' => $now,
                ':trial_end' => $trial_end
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur initializeTrialPeriod: " . $e->getMessage());
            // On ne lance pas d'exception pour ne pas bloquer l'inscription, 
            // le cron de nettoyage s'occupera des incohérences
            return false;
        }
    }

    /**
     * Crée ou met à jour un abonnement après paiement Stripe
     * Appelé par le webhook Stripe après checkout.session.completed
     */
    public function createSubscription($shop_id, $plan_id, $stripe_subscription_id, $stripe_customer_id) {
        try {
            $now = date('Y-m-d H:i:s');
            $period_end = date('Y-m-d H:i:s', strtotime('+1 month'));
            
            // Vérifier si un abonnement existe déjà pour ce shop
            $stmt = $this->pdo->prepare("SELECT id FROM subscriptions WHERE shop_id = ?");
            $stmt->execute([$shop_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Mettre à jour l'abonnement existant
                $sql = "UPDATE subscriptions 
                        SET plan_id = :plan_id,
                            status = 'active',
                            stripe_subscription_id = :stripe_sub_id,
                            stripe_customer_id = :stripe_cust_id,
                            current_period_start = :period_start,
                            current_period_end = :period_end,
                            trial_start_date = NULL,
                            trial_end_date = NULL,
                            updated_at = NOW()
                        WHERE shop_id = :shop_id";
            } else {
                // Créer un nouvel abonnement
                $sql = "INSERT INTO subscriptions 
                        (shop_id, plan_id, status, stripe_subscription_id, stripe_customer_id, 
                         current_period_start, current_period_end, created_at, updated_at)
                        VALUES 
                        (:shop_id, :plan_id, 'active', :stripe_sub_id, :stripe_cust_id,
                         :period_start, :period_end, NOW(), NOW())";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':shop_id' => $shop_id,
                ':plan_id' => $plan_id,
                ':stripe_sub_id' => $stripe_subscription_id,
                ':stripe_cust_id' => $stripe_customer_id,
                ':period_start' => $now,
                ':period_end' => $period_end
            ]);
            
            // Mettre à jour le statut du shop
            $stmt = $this->pdo->prepare("
                UPDATE shops 
                SET subscription_status = 'active',
                    active = 1,
                    trial_started_at = NULL,
                    trial_ends_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$shop_id]);
            
            error_log("SubscriptionManager: Abonnement créé/mis à jour pour shop {$shop_id}");
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur createSubscription: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enregistre une transaction de paiement
     * Appelé par le webhook Stripe après un paiement réussi
     */
    public function recordPaymentTransaction($shop_id, $amount, $currency, $status, $stripe_payment_intent, $description = '') {
        try {
            // Récupérer l'ID de subscription
            $stmt = $this->pdo->prepare("SELECT id FROM subscriptions WHERE shop_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$shop_id]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sub) {
                error_log("recordPaymentTransaction: Aucune subscription trouvée pour shop {$shop_id}");
                return false;
            }
            
            $sql = "INSERT INTO payment_transactions 
                    (subscription_id, amount, currency, status, stripe_payment_intent_id, description, created_at)
                    VALUES 
                    (:sub_id, :amount, :currency, :status, :payment_intent, :description, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':sub_id' => $sub['id'],
                ':amount' => $amount,
                ':currency' => $currency,
                ':status' => $status,
                ':payment_intent' => $stripe_payment_intent,
                ':description' => $description
            ]);
            
            error_log("SubscriptionManager: Transaction enregistrée pour subscription {$sub['id']}");
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur recordPaymentTransaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Annule un abonnement
     */
    public function cancelSubscription($shop_id) {
        try {
            // Mettre à jour le statut de l'abonnement
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled', updated_at = NOW()
                WHERE shop_id = ?
            ");
            $stmt->execute([$shop_id]);
            
            // Mettre à jour le shop
            $stmt = $this->pdo->prepare("
                UPDATE shops 
                SET subscription_status = 'cancelled'
                WHERE id = ?
            ");
            $stmt->execute([$shop_id]);
            
            error_log("SubscriptionManager: Abonnement annulé pour shop {$shop_id}");
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur cancelSubscription: " . $e->getMessage());
            return false;
        }
    }
}
?>
