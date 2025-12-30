<?php
// subscription_router.php
// Routeur pour l'espace client (Gestion Abonnement)

// Activer l'affichage des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Inclusion de la configuration base de données
require_once __DIR__ . '/config/database.php';

// Récupérer la page demandée
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Vérification de l'authentification "Espace Client"
// On utilise une session différente pour ne pas mélanger avec le shop
// Inclusion du gestionnaire de Token
require_once __DIR__ . '/classes/TokenManager.php';

// Vérification de l'authentification par Token
$is_client_logged_in = false;
$session_token = $_SESSION['subscription_access_token'] ?? null;

if ($session_token) {
    $tokenManager = new TokenManager();
    $sessionData = $tokenManager->validateSession($session_token);
    
    if ($sessionData) {
        $is_client_logged_in = true;
        // Rafraîchir l'ID shop en session pour les helpers qui en ont besoin
        // C'est le token qui fait foi
        $_SESSION['client_shop_id'] = $sessionData['shop_id'];
    } else {
        // Token invalide ou expiré
        unset($_SESSION['subscription_access_token']);
        session_destroy();
    }
}

// Routes publiques (Login)
if (!$is_client_logged_in) {
    if ($page === 'login') {
        require_once __DIR__ . '/pages/subscription/login.php';
    } else {
        // Redirection vers login pour toute autre page si pas connecté
        header('Location: /subscription_router.php?page=login');
        exit;
    }
} else {
    // Routes protégées
    switch ($page) {
        case 'dashboard':
            $content_view = __DIR__ . '/pages/subscription/dashboard.php';
            $page_title = 'Tableau de Bord';
            break;
        case 'manage_plan':
            $content_view = __DIR__ . '/pages/subscription/manage_plan.php';
            $page_title = 'Gestion Abonnement';
            break;
        case 'billing':
            $content_view = __DIR__ . '/pages/subscription/billing.php';
            $page_title = 'Facturation';
            break;
        case 'payment_methods':
            $content_view = __DIR__ . '/pages/subscription/payment_methods.php';
            $page_title = 'Moyens de Paiement';
            break;
        case 'company_profile':
            $content_view = __DIR__ . '/pages/subscription/company_profile.php';
            $page_title = 'Profil Entreprise';
            break;
        case 'logout':
            // Déconnexion
            // Déconnexion
            if (isset($_SESSION['subscription_access_token'])) {
                $tokenManager = new TokenManager();
                $tokenManager->revokeSession($_SESSION['subscription_access_token']);
            }
            session_unset();
            session_destroy();
            header('Location: /subscription_router.php?page=login');
            exit;
        default:
            $content_view = __DIR__ . '/pages/subscription/dashboard.php';
            $page_title = 'Tableau de Bord';
            break;
    }

    // Si c'est une requête AJAX, on ne charge pas le layout complet
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        if (file_exists($content_view)) {
            require_once $content_view;
        } else {
            echo "Page introuvable.";
        }
    } else {
        // Chargement du layout principal qui inclura la vue
        require_once __DIR__ . '/pages/subscription/layout.php';
    }
}
?>
