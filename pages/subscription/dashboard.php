<?php
// pages/subscription/dashboard.php

require_once __DIR__ . '/../../classes/SubscriptionManager.php';

$manager = new SubscriptionManager($_SESSION['client_shop_id']);
$subInfo = $manager->getSubscriptionInfo();
$usageStats = $manager->getUsageStats();

// Valeurs par défaut si pas d'info (cas rare mais possible)
if (!$subInfo) {
    echo "<div class='alert alert-danger'>Impossible de charger les informations d'abonnement. Veuillez contacter le support.</div>";
    return;
}

$status_labels = [
    'trial' => 'Période d\'essai',
    'active' => 'Actif',
    'past_due' => 'Paiement en attente',
    'cancelled' => 'Annulé',
    'expired' => 'Expiré'
];

$status_colors = [
    'trial' => 'warning',
    'active' => 'success',
    'past_due' => 'danger',
    'cancelled' => 'secondary',
    'expired' => 'danger'
];

$current_status = $subInfo['subscription_status'] ?? 'unknown';
$status_label = $status_labels[$current_status] ?? ucfirst($current_status);
$badges_class = 'badge badge-' . ($status_colors[$current_status] ?? 'secondary');

?>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
    
    <!-- Carte Résumé Abonnement -->
    <div class="card">
        <div class="card-title flex justify-between items-center">
            <span>Vue d'ensemble</span>
            <span class="<?= $badges_class ?>"><?= htmlspecialchars($status_label) ?></span>
        </div>
        
        <div class="mb-4">
            <div class="text-muted text-sm uppercase">Plan Actuel</div>
            <div style="font-size: 1.5rem; font-weight: 700;">
                <?= htmlspecialchars($subInfo['plan_name'] ?? 'Aucun plan') ?>
            </div>
            <div class="text-muted">
                <?= number_format($subInfo['plan_price'] ?? 0, 2) ?> € / <?= ($subInfo['billing_period'] ?? 'monthly') == 'yearly' ? 'an' : 'mois' ?>
            </div>
        </div>

        <?php if ($current_status === 'trial'): ?>
            <?php 
                $days_left = $subInfo['days_remaining'] ?? 0;
                $progress = $manager->getTrialProgress($subInfo);
            ?>
            <div class="trial-progress">
                <div class="flex justify-between text-sm mb-1">
                    <span>Période d'essai</span>
                    <span><?= max(0, $days_left) ?> jours restants</span>
                </div>
                <div style="height: 8px; background-color: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= $progress ?>%; height: 100%; background-color: var(--warning);"></div>
                </div>
                <div class="text-xs text-muted mt-1">
                    Fin le <?= date('d/m/Y', strtotime($subInfo['trial_ends_at'])) ?>
                </div>
            </div>
        <?php elseif ($current_status === 'active'): ?>
            <div class="text-sm">
                Prochain renouvellement le : 
                <strong><?= date('d/m/Y', strtotime($subInfo['current_period_end'] ?? 'now')) ?></strong>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="?page=manage_plan" class="btn btn-primary" style="width: 100%; box-sizing: border-box;">Gérer mon plan</a>
        </div>
    </div>

    <!-- Carte Statistiques Rapides (Placeholder) -->
    <div class="card">
        <div class="card-title">Utilisation</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 0.5rem; text-align: center;">
                <div class="text-2xl font-bold"><?= number_format($usageStats['sms_count'] ?? 0) ?></div>
                <div class="text-xs text-muted">SMS Envoyés</div>
            </div>
            <div style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 0.5rem; text-align: center;">
                <div class="text-2xl font-bold"><?= number_format($usageStats['client_count'] ?? 0) ?></div>
                <div class="text-xs text-muted">Clients</div>
            </div>
        </div>
        <p class="text-sm text-muted mt-3">
            Les statistiques détaillées seront disponibles prochainement.
        </p>
    </div>

</div>

<!-- Une section pour les actualités ou messages importants -->
<div class="card mt-4">
    <div class="card-title">Besoin d'aide ?</div>
    <p class="text-muted mb-4">
        Notre équipe support est disponible pour vous aider à configurer votre boutique ou répondre à vos questions sur la facturation.
    </p>
    <a href="mailto:support@servo.tools" class="text-primary">Contacter le support <i class="fa-solid fa-arrow-right text-sm"></i></a>
</div>
