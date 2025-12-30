<?php
// pages/subscription/payment_methods.php
// Même logique que billing, on redirige vers le portail
require_once __DIR__ . '/../../classes/StripeManager.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'portal') {
    $stripeManager = new StripeManager();
    $return_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    $session = $stripeManager->createPortalSession($_SESSION['client_shop_id'], $return_url);
    
    if ($session && isset($session->url)) {
        header("Location: " . $session->url);
        exit;
    } else {
        $error = "Impossible d'accéder au portail. Avez-vous déjà un abonnement actif ?";
    }
}
?>

<div class="card text-center" style="padding: 4rem 2rem;">
    <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1.5rem;">
        <i class="fa-regular fa-credit-card"></i>
    </div>
    
    <h2 class="card-title text-2xl mb-4">Moyens de Paiement</h2>
    
    <p class="text-muted mb-8" style="max-width: 500px; margin-left: auto; margin-right: auto;">
        Gérez vos cartes bancaires et méthodes de paiement en toute sécurité via notre portail dédié.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger mb-6" style="max-width: 500px; margin: 0 auto 2rem auto;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="action" value="portal">
        <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
            <i class="fa-solid fa-arrow-up-right-from-square mr-2"></i>
            Gérer mes moyens de paiement
        </button>
    </form>
</div>
