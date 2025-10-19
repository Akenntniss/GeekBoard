<?php
/**
 * Middleware de redirection pour abonnements expirés
 * Vérifie le statut d'abonnement et redirige vers subscription_required.php si nécessaire
 */

require_once __DIR__ . '/../classes/SubscriptionManager.php';

class SubscriptionRedirectMiddleware {
    private $subscriptionManager;
    private $shop_id;
    
    public function __construct($shop_id = null) {
        $this->subscriptionManager = new SubscriptionManager();
        $this->shop_id = $shop_id ?: ($_SESSION['shop_id'] ?? null);
    }
    
    /**
     * Vérifier l'accès et rediriger si nécessaire
     */
    public function checkAccess() {
        // Vérifier si nous avons un shop_id
        if (!$this->shop_id) {
            return true; // Pas de vérification possible, laisser passer
        }
        
        // Vérifier le statut d'abonnement
        $status = $this->subscriptionManager->checkShopSubscriptionStatus($this->shop_id);
        
        if (!$status) {
            // Erreur dans la vérification, laisser passer pour éviter de bloquer
            return true;
        }
        
        // Vérifier si l'accès est autorisé
        $hasAccess = $this->subscriptionManager->hasAccess($this->shop_id);
        
        if (!$hasAccess) {
            // Abonnement expiré, rediriger vers la page d'abonnement
            $this->redirectToSubscription();
            return false;
        }
        
        return true;
    }
    
    /**
     * Rediriger vers la page d'abonnement requis
     */
    private function redirectToSubscription() {
        $redirect_url = "https://mdgeek.top/subscription_required.php?shop_id=" . $this->shop_id . "#plans";
        
        // Si c'est une requête AJAX, renvoyer une réponse JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            http_response_code(402); // Payment Required
            echo json_encode([
                'error' => 'subscription_expired',
                'message' => 'Votre abonnement a expiré',
                'redirect_url' => $redirect_url
            ]);
            exit;
        }
        
        // Redirection HTTP normale
        header("Location: " . $redirect_url);
        exit;
    }
    
    /**
     * Vérifier si le shop est dans la période d'avertissement (proche de l'expiration)
     */
    public function getTrialWarning() {
        if (!$this->shop_id) {
            return null;
        }
        
        $status = $this->subscriptionManager->checkShopSubscriptionStatus($this->shop_id);
        
        if ($status && $status['subscription_status'] === 'trial' && $status['days_remaining'] !== null) {
            if ($status['days_remaining'] <= 7 && $status['days_remaining'] > 0) {
                return [
                    'days_remaining' => $status['days_remaining'],
                    'trial_ends_at' => $status['trial_ends_at'],
                    'message' => "Votre essai gratuit se termine dans {$status['days_remaining']} jour(s)"
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Méthode statique pour utilisation rapide
     */
    public static function requireValidSubscription($shop_id = null) {
        $middleware = new self($shop_id);
        return $middleware->checkAccess();
    }
    
    /**
     * Afficher un bandeau d'avertissement si l'essai va bientôt expirer
     */
    public function displayTrialWarningBanner() {
        $warning = $this->getTrialWarning();
        
        if ($warning) {
            echo '<div class="alert alert-warning trial-warning-banner" style="
                position: fixed; 
                top: 0; 
                left: 0; 
                right: 0; 
                z-index: 9999; 
                margin: 0; 
                border-radius: 0;
                background: #fff3cd;
                border: 1px solid #ffc107;
                padding: 10px 20px;
                text-align: center;
            ">';
            echo '<strong>⏰ ' . htmlspecialchars($warning['message']) . '</strong> ';
            echo '<a href="https://mdgeek.top/subscription_required.php?shop_id=' . $this->shop_id . '#plans" ';
            echo 'class="btn btn-sm btn-warning ms-2">Choisir mon abonnement</a>';
            echo '<button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.style.display=\'none\'"></button>';
            echo '</div>';
            
            // Ajouter un padding au body pour compenser le bandeau fixe
            echo '<style>body { padding-top: 60px !important; }</style>';
        }
    }
}

/**
 * Fonction helper pour utilisation dans les pages
 */
function checkSubscriptionAccess($shop_id = null) {
    return SubscriptionRedirectMiddleware::requireValidSubscription($shop_id);
}

/**
 * Fonction helper pour afficher l'avertissement d'essai
 */
function displayTrialWarning($shop_id = null) {
    $middleware = new SubscriptionRedirectMiddleware($shop_id);
    $middleware->displayTrialWarningBanner();
}
?>
