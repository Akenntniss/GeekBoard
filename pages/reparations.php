<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    // La fonction checkSubscriptionAccess() gère la redirection automatique
    exit;
}

// Obtenir la connexion à la base de données du magasin de l'utilisateur
$shop_pdo = getShopDBConnection();

// Récupérer et stocker l'ID du magasin actuel
$current_shop_id = $_SESSION['shop_id'] ?? null;
if (!$current_shop_id) {
    // Essayer de récupérer depuis l'URL
    $current_shop_id = $_GET['shop_id'] ?? null;
    if ($current_shop_id) {
        $_SESSION['shop_id'] = $current_shop_id;
    } else {
        error_log("ALERTE: ID du magasin non trouvé dans la session ou l'URL pour reparations.php");
    }
}

// Vérifier que $shop_pdo est accessible et initialisé
if (!isset($shop_pdo) || $shop_pdo === null) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données. La variable \$shop_pdo n'est pas disponible. Veuillez contacter l'administrateur.</div>";
    error_log("ERREUR CRITIQUE dans reparations.php: La variable \$shop_pdo n'est pas disponible");
    exit;
}

// Include Controller
require_once __DIR__ . '/../classes/RepairController.php';

// ⭐ AFFICHER LE BANDEAU D'AVERTISSEMENT SI L'ESSAI VA EXPIRER
displayTrialWarning();

// Instantiate Controller
// Note: $_SESSION['role'] might not be set for all users, default to 'user'
$userRole = $_SESSION['role'] ?? 'user';
$controller = new RepairController($shop_pdo, $_SESSION['user_id'], $userRole);

// Handle Request
$controller->handleRequest();
?>