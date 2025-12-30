<?php
// pages/subscription/manage_plan.php

require_once __DIR__ . '/../../classes/SubscriptionManager.php';

$manager = new SubscriptionManager($_SESSION['client_shop_id']);

// Traitement du formulaire de changement de plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    require_once __DIR__ . '/../../classes/StripeManager.php';
    $stripeManager = new StripeManager(); // Utilise la connexion par défaut
    
    $plan_id = $_POST['plan_id'] ?? 0;
    
    if ($plan_id) {
        // Création de la session Checkout
        // On passe l'email de l'user courant si disponible
        $session = $stripeManager->createCheckoutSession($plan_id, $_SESSION['client_shop_id'], $_SESSION['client_user_email'] ?? null);
        
        if ($session && isset($session->url)) {
            header("Location: " . $session->url);
            exit;
        } else {
            $error = "Erreur lors de l'initialisation du paiement.";
        }
    }
}

$subInfo = $manager->getSubscriptionInfo();
$plans = $manager->getAvailablePlans();

$current_plan_id = $subInfo['plan_id'] ?? 0;
?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Votre abonnement actuel</h2>
    <div class="flex justify-between items-center bg-dark p-4 rounded mb-4" style="background: rgba(255,255,255,0.05);">
        <div>
            <div class="text-xl font-bold"><?= htmlspecialchars($subInfo['plan_name'] ?? 'Inconnu') ?></div>
            <div class="text-sm text-muted">
                Statut : <span class="badge badge-<?= $subInfo['subscription_status'] == 'active' ? 'success' : 'warning' ?>"><?= ucfirst($subInfo['subscription_status']) ?></span>
            </div>
            <?php if ($subInfo['subscription_status'] == 'trial'): ?>
                <div class="text-sm text-warning mt-1">
                    Fin de l'essai : <?= date('d/m/Y', strtotime($subInfo['trial_ends_at'])) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-right">
            <div class="text-xl font-bold"><?= number_format($subInfo['plan_price'], 2) ?> €</div>
            <div class="text-sm text-muted">/ <?= ($subInfo['billing_period'] ?? 'monthly') == 'yearly' ? 'an' : 'mois' ?></div>
        </div>
    </div>
    
    <?php if ($subInfo['subscription_status'] != 'cancelled'): ?>
    <div class="text-right">
        <a href="/subscription_router.php?page=billing" class="text-danger text-sm" style="text-decoration: underline;">
            Gérer mon abonnement (Annulation/Factures)
        </a>
    </div>
    <?php endif; ?>
</div>

<h2 class="page-header page-title" style="font-size: 1.5rem; margin-top: 2rem;">Changer de plan</h2>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
    <?php foreach ($plans as $plan): ?>
        <?php 
            $is_current = ($plan['id'] == $current_plan_id);
            $features = json_decode($plan['features'] ?? '[]', true) ?? [];
        ?>
        <div class="card" style="display: flex; flex-direction: column; <?= $is_current ? 'border-color: var(--primary-color); box-shadow: 0 0 0 1px var(--primary-color);' : '' ?>">
            <div class="mb-4">
                <div class="flex justify-between items-start">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($plan['name']) ?></h3>
                    <?php if ($is_current): ?>
                        <span class="badge badge-success">Actuel</span>
                    <?php endif; ?>
                </div>
                <div class="text-muted text-sm my-2" style="min-height: 40px;"><?= htmlspecialchars($plan['description']) ?></div>
                <div class="text-2xl font-bold mt-2">
                    <?= number_format($plan['price'], 2) ?> €
                    <span class="text-sm text-muted font-normal">/ <?= $plan['billing_period'] == 'yearly' ? 'an' : 'mois' ?></span>
                </div>
            </div>

            <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem 0; flex-grow: 1;">
                <?php foreach ($features as $feature): ?>
                    <li class="flex items-center gap-2 mb-2 text-sm">
                        <i class="fa-solid fa-check text-success"></i>
                        <?= htmlspecialchars($feature) ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="mt-auto">
                <?php if ($is_current): ?>
                    <button class="btn btn-primary" disabled style="opacity: 0.7; cursor: default; width:100%;">Votre plan actuel</button>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="checkout">
                        <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            Choisir ce plan
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
