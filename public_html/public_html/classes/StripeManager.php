<?php
/**
 * Gestionnaire Stripe pour GeekBoard
 * Intégration complète avec Stripe API v3
 */

require_once __DIR__ . '/../vendor/autoload.php'; // Si Stripe SDK installé
require_once __DIR__ . '/SubscriptionManager.php';

class StripeManager {
    private $stripe_config;
    private $subscriptionManager;
    private $pdo;
    
    public function __construct($pdo = null) {
        require_once(__DIR__ . '/../config/stripe_config.php');
        global $stripe_config;
        $this->stripe_config = $stripe_config;
        $this->subscriptionManager = new SubscriptionManager($pdo);
        
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            // Connexion à la base principale pour les abonnements
            $this->pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        // Configuration Stripe
        if (class_exists('\\Stripe\\Stripe')) {
            \Stripe\Stripe::setApiKey($this->stripe_config['secret_key']);
        }
    }
    
    /**
     * Récupérer les Price IDs depuis Stripe pour un produit
     */
    public function getProductPrices($product_id) {
        try {
            if (!class_exists('\\Stripe\\Price')) {
                throw new Exception("Stripe SDK non installé");
            }
            
            $prices = \Stripe\Price::all([
                'product' => $product_id,
                'active' => true
            ]);
            
            return $prices->data;
        } catch (Exception $e) {
            $this->log("Erreur récupération prix: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Synchroniser les Price IDs avec la base de données
     */
    public function syncPricesWithDatabase() {
        try {
            $products = $this->stripe_config['products'];
            $updates = [];
            
            foreach ($products as $plan_name => $product_id) {
                $prices = $this->getProductPrices($product_id);
                
                foreach ($prices as $price) {
                    $billing_period = ($price->recurring && $price->recurring->interval === 'year') ? 'yearly' : 'monthly';
                    
                    // Correspondance nom plan GeekBoard -> Stripe
                    $geekboard_plan_name = $this->mapStripePlanToGeekBoard($plan_name, $billing_period);
                    
                    if ($geekboard_plan_name) {
                        $updates[] = [
                            'name' => $geekboard_plan_name,
                            'stripe_price_id' => $price->id,
                            'price' => $price->unit_amount / 100, // Convertir centimes en euros
                            'billing_period' => $billing_period
                        ];
                    }
                }
            }
            
            // Mettre à jour la base de données
            foreach ($updates as $update) {
                $stmt = $this->pdo->prepare("
                    UPDATE subscription_plans 
                    SET stripe_price_id = ?, price = ?
                    WHERE name = ? AND billing_period = ?
                ");
                $stmt->execute([
                    $update['stripe_price_id'],
                    $update['price'], 
                    $update['name'],
                    $update['billing_period']
                ]);
                
                $this->log("Prix synchronisé: {$update['name']} ({$update['billing_period']}) = {$update['stripe_price_id']}");
            }
            
            return $updates;
        } catch (Exception $e) {
            $this->log("Erreur synchronisation prix: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Mapper les noms de plans Stripe vers GeekBoard
     */
    private function mapStripePlanToGeekBoard($stripe_plan, $billing_period) {
        $mapping = [
            'starter' => $billing_period === 'yearly' ? 'Starter Annual' : 'Starter',
            'professional' => $billing_period === 'yearly' ? 'Professional Annual' : 'Professional', 
            'enterprise' => $billing_period === 'yearly' ? 'Enterprise Annual' : 'Enterprise'
        ];
        
        return $mapping[$stripe_plan] ?? null;
    }
    
    /**
     * Créer une session de checkout Stripe
     */
    public function createCheckoutSession($plan_id, $shop_id, $customer_email = null) {
        try {
            if (!class_exists('\\Stripe\\Checkout\\Session')) {
                throw new Exception("Stripe SDK non installé");
            }
            
            // Récupérer les détails du plan
            $stmt = $this->pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plan || !$plan['stripe_price_id']) {
                throw new Exception("Plan non trouvé ou Price ID manquant");
            }
            
            // Récupérer les informations du shop
            $stmt = $this->pdo->prepare("
                SELECT s.*, so.email, so.prenom, so.nom 
                FROM shops s 
                JOIN shop_owners so ON s.id = so.shop_id 
                WHERE s.id = ?
            ");
            $stmt->execute([$shop_id]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$shop) {
                throw new Exception("Shop non trouvé");
            }
            
            // Créer la session Stripe
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $plan['stripe_price_id'],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $this->stripe_config['success_url'],
                'cancel_url' => $this->stripe_config['cancel_url'],
                'customer_email' => $customer_email ?: $shop['email'],
                'client_reference_id' => $shop_id,
                'metadata' => [
                    'shop_id' => $shop_id,
                    'plan_id' => $plan_id,
                    'shop_name' => $shop['name']
                ],
                'subscription_data' => [
                    'metadata' => [
                        'shop_id' => $shop_id,
                        'plan_id' => $plan_id
                    ]
                ]
            ]);
            
            return $session;
        } catch (Exception $e) {
            $this->log("Erreur création session checkout: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Traiter le webhook de paiement réussi
     */
    public function handlePaymentSucceeded($session) {
        try {
            $shop_id = $session->metadata->shop_id ?? $session->client_reference_id;
            $plan_id = $session->metadata->plan_id;
            
            if (!$shop_id || !$plan_id) {
                throw new Exception("Métadonnées manquantes dans la session");
            }
            
            // Récupérer la subscription Stripe
            $subscription = \Stripe\Subscription::retrieve($session->subscription);
            
            // Créer l'abonnement dans GeekBoard
            $result = $this->subscriptionManager->createSubscription(
                $shop_id,
                $plan_id, 
                $subscription->id,
                $subscription->customer
            );
            
            if ($result) {
                $this->log("Abonnement créé avec succès: Shop {$shop_id}, Subscription {$subscription->id}");
                
                // Enregistrer la transaction
                $this->subscriptionManager->recordPaymentTransaction(
                    $shop_id, // Utiliser shop_id temporairement
                    $session->amount_total / 100,
                    strtoupper($session->currency),
                    'succeeded',
                    $session->payment_intent,
                    "Paiement abonnement - Session {$session->id}"
                );
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            $this->log("Erreur traitement paiement: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Traiter les webhooks Stripe
     */
    public function processWebhook($payload, $sig_header) {
        try {
            if (!$this->stripe_config['webhook_secret']) {
                throw new Exception("Webhook secret non configuré");
            }
            
            $event = \Stripe\Webhook::constructEvent(
                $payload, 
                $sig_header, 
                $this->stripe_config['webhook_secret']
            );
            
            $this->log("Webhook reçu: " . $event->type);
            
            switch ($event->type) {
                case 'checkout.session.completed':
                    return $this->handlePaymentSucceeded($event->data->object);
                    
                case 'customer.subscription.created':
                    return $this->handleSubscriptionCreated($event->data->object);
                    
                case 'customer.subscription.updated':
                    return $this->handleSubscriptionUpdated($event->data->object);
                    
                case 'customer.subscription.deleted':
                    return $this->handleSubscriptionCancelled($event->data->object);
                    
                case 'customer.subscription.trial_will_end':
                    return $this->handleTrialWillEnd($event->data->object);
                    
                case 'invoice.payment_succeeded':
                    return $this->handleInvoicePaymentSucceeded($event->data->object);
                    
                case 'invoice.payment_failed':
                    return $this->handlePaymentFailed($event->data->object);
                    
                default:
                    $this->log("Type d'événement non géré: " . $event->type);
                    return true;
            }
        } catch (Exception $e) {
            $this->log("Erreur webhook: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Gérer l'annulation d'abonnement
     */
    private function handleSubscriptionCancelled($subscription) {
        try {
            $shop_id = $subscription->metadata->shop_id ?? null;
            
            if ($shop_id) {
                $this->subscriptionManager->cancelSubscription($shop_id);
                $this->log("Abonnement annulé: Shop {$shop_id}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            $this->log("Erreur annulation: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Gérer la création d'abonnement
     */
    private function handleSubscriptionCreated($subscription) {
        try {
            $shop_id = $subscription->metadata->shop_id ?? null;
            
            if ($shop_id) {
                $this->log("Abonnement créé: Shop {$shop_id}, Subscription {$subscription->id}");
                
                // L'abonnement sera géré par checkout.session.completed
                // Ici on peut ajouter des actions supplémentaires si nécessaire
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            $this->log("Erreur création abonnement: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Gérer la mise à jour d'abonnement
     */
    private function handleSubscriptionUpdated($subscription) {
        try {
            $shop_id = $subscription->metadata->shop_id ?? null;
            
            if (!$shop_id) {
                $this->log("Shop ID manquant dans subscription.updated", 'WARNING');
                return true;
            }
            
            // Mettre à jour le statut local selon le statut Stripe
            $local_status = $this->mapStripeStatus($subscription->status);
            
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET status = ?,
                    current_period_start = FROM_UNIXTIME(?),
                    current_period_end = FROM_UNIXTIME(?)
                WHERE stripe_subscription_id = ?
            ");
            $stmt->execute([
                $local_status,
                $subscription->current_period_start,
                $subscription->current_period_end,
                $subscription->id
            ]);
            
            // Mettre à jour aussi le shop
            $stmt = $this->pdo->prepare("
                UPDATE shops 
                SET subscription_status = ?,
                    active = ?
                WHERE id = ?
            ");
            $active = ($local_status === 'active') ? 1 : 0;
            $stmt->execute([$local_status, $active, $shop_id]);
            
            $this->log("Abonnement mis à jour: Shop {$shop_id}, Status: {$subscription->status} -> {$local_status}");
            return true;
            
        } catch (Exception $e) {
            $this->log("Erreur mise à jour abonnement: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Gérer la fin d'essai proche (3 jours avant)
     */
    private function handleTrialWillEnd($subscription) {
        try {
            $shop_id = $subscription->metadata->shop_id ?? null;
            
            if (!$shop_id) {
                $this->log("Shop ID manquant dans trial_will_end", 'WARNING');
                return true;
            }
            
            // Récupérer les informations du shop et propriétaire
            $stmt = $this->pdo->prepare("
                SELECT s.*, so.email, so.prenom, so.nom 
                FROM shops s 
                JOIN shop_owners so ON s.id = so.shop_id 
                WHERE s.id = ?
            ");
            $stmt->execute([$shop_id]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shop) {
                // Log de l'événement
                $trial_end_date = date('Y-m-d H:i:s', $subscription->trial_end);
                $this->log("Fin d'essai proche: Shop {$shop_id} ({$shop['name']}) - Fin le {$trial_end_date}");
                
                // Ici vous pouvez ajouter :
                // - Envoi d'email de rappel
                // - Notification dans l'interface
                // - SMS de rappel
                
                // Exemple d'enregistrement dans une table de notifications
                $this->recordTrialEndingNotification($shop_id, $trial_end_date);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            $this->log("Erreur gestion fin d'essai: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Gérer le paiement réussi d'une facture
     */
    private function handleInvoicePaymentSucceeded($invoice) {
        try {
            $subscription_id = $invoice->subscription;
            
            if ($subscription_id) {
                // Marquer l'abonnement comme actif si c'était en souffrance
                $stmt = $this->pdo->prepare("
                    UPDATE subscriptions 
                    SET status = 'active'
                    WHERE stripe_subscription_id = ? AND status = 'past_due'
                ");
                $stmt->execute([$subscription_id]);
                
                // Enregistrer la transaction
                $stmt = $this->pdo->prepare("
                    SELECT id FROM subscriptions WHERE stripe_subscription_id = ?
                ");
                $stmt->execute([$subscription_id]);
                $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($subscription) {
                    $this->subscriptionManager->recordPaymentTransaction(
                        $subscription['id'],
                        $invoice->amount_paid / 100,
                        strtoupper($invoice->currency),
                        'succeeded',
                        $invoice->payment_intent,
                        "Paiement facture - Invoice {$invoice->id}"
                    );
                }
                
                $this->log("Paiement facture réussi: Subscription {$subscription_id}, Montant: " . ($invoice->amount_paid / 100));
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            $this->log("Erreur paiement facture: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Gérer l'échec de paiement
     */
    private function handlePaymentFailed($invoice) {
        try {
            $subscription_id = $invoice->subscription;
            
            // Marquer l'abonnement comme en souffrance
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET status = 'past_due' 
                WHERE stripe_subscription_id = ?
            ");
            $stmt->execute([$subscription_id]);
            
            // Marquer le shop comme inactif si échec de paiement
            $stmt = $this->pdo->prepare("
                UPDATE shops s
                JOIN subscriptions sub ON s.id = sub.shop_id 
                SET s.active = 0, s.subscription_status = 'past_due'
                WHERE sub.stripe_subscription_id = ?
            ");
            $stmt->execute([$subscription_id]);
            
            $this->log("Paiement échoué: Subscription {$subscription_id} - Shop désactivé");
            return true;
        } catch (Exception $e) {
            $this->log("Erreur échec paiement: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Mapper les statuts Stripe vers les statuts locaux
     */
    private function mapStripeStatus($stripe_status) {
        $mapping = [
            'trialing' => 'trial',
            'active' => 'active',
            'canceled' => 'cancelled',
            'incomplete' => 'pending',
            'incomplete_expired' => 'expired',
            'past_due' => 'past_due',
            'unpaid' => 'past_due'
        ];
        
        return $mapping[$stripe_status] ?? 'expired';
    }
    
    /**
     * Enregistrer une notification de fin d'essai
     */
    private function recordTrialEndingNotification($shop_id, $trial_end_date) {
        try {
            // Créer la table de notifications si elle n'existe pas
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS trial_notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    shop_id INT NOT NULL,
                    notification_type ENUM('trial_ending', 'trial_ended', 'payment_failed') NOT NULL,
                    trial_end_date TIMESTAMP NULL,
                    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    email_sent TINYINT(1) DEFAULT 0,
                    sms_sent TINYINT(1) DEFAULT 0,
                    INDEX idx_shop_id (shop_id),
                    INDEX idx_notification_type (notification_type)
                )
            ");
            
            $stmt = $this->pdo->prepare("
                INSERT INTO trial_notifications (shop_id, notification_type, trial_end_date)
                VALUES (?, 'trial_ending', ?)
            ");
            $stmt->execute([$shop_id, $trial_end_date]);
            
            $this->log("Notification fin d'essai enregistrée: Shop {$shop_id}");
            return true;
            
        } catch (Exception $e) {
            $this->log("Erreur enregistrement notification: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Logging
     */
    private function log($message, $level = 'INFO') {
        if (!$this->stripe_config['log_enabled']) {
            return;
        }
        
        $log_file = $this->stripe_config['log_file'];
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>
