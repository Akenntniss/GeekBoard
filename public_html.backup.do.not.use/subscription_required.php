<?php
// Page affichée quand l'essai gratuit est expiré
session_start();

// Inclure le gestionnaire d'abonnements
require_once('classes/SubscriptionManager.php');
require_once('config/database.php');

// Vérifier si on a un shop_id en session ou paramètre
$shop_id = $_SESSION['shop_id'] ?? $_GET['shop_id'] ?? null;

if (!$shop_id) {
    header('Location: https://mdgeek.top/inscription.php');
    exit;
}

$subscriptionManager = new SubscriptionManager();
$shopStatus = $subscriptionManager->checkShopSubscriptionStatus($shop_id);
$plans = $subscriptionManager->getAvailablePlans();

// Si le shop a encore accès, rediriger vers la boutique
if ($subscriptionManager->hasAccess($shop_id)) {
    $shop_subdomain = '';
    if ($shopStatus) {
        $stmt = (new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#'))
               ->prepare("SELECT subdomain FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch();
        if ($shop) {
            $shop_subdomain = $shop['subdomain'];
            header("Location: https://{$shop_subdomain}.mdgeek.top");
            exit;
        }
    }
}

// Inclure le header marketing
include_once('marketing/shared/header.php');
?>

<!-- Hero Section - Essai expiré -->
<section class="section bg-gradient-primary text-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="mb-4">
                    <i class="fa-solid fa-clock" style="font-size: 4rem; opacity: 0.8;"></i>
                </div>
                
                <h1 class="display-4 fw-black mb-4">Votre essai gratuit est terminé</h1>
                
                <p class="fs-5 mb-4 opacity-90">
                    Nous espérons que ces 30 jours vous ont permis de découvrir tout le potentiel de SERVO. 
                    Avez-vous aimé l'expérience ?
                </p>
                
                <?php if ($shopStatus && $shopStatus['days_remaining'] !== null): ?>
                    <div class="card-modern bg-dark bg-opacity-25 border-0 p-4 mb-4">
                        <div class="d-flex align-items-center justify-content-center text-white">
                            <i class="fa-solid fa-info-circle me-3 fs-4"></i>
                            <div>
                                <strong>Votre période d'essai s'est terminée 
                                <?php 
                                $days_past = abs($shopStatus['days_remaining']);
                                echo $days_past == 0 ? "aujourd'hui" : "il y a {$days_past} jour" . ($days_past > 1 ? 's' : '');
                                ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="row g-4 mt-4">
                    <div class="col-md-6">
                        <div class="card-modern bg-dark bg-opacity-25 border-0 p-4 h-100">
                            <i class="fa-solid fa-heart text-white fs-2 mb-3"></i>
                            <h5 class="text-white fw-bold mb-3">J'ai adoré SERVO !</h5>
                            <p class="text-white opacity-75 mb-4">
                                Continuez à profiter de toutes les fonctionnalités avec un abonnement.
                            </p>
                            <a href="#plans" class="btn btn-light btn-lg">
                                <i class="fa-solid fa-rocket me-2"></i>Choisir mon abonnement
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card-modern bg-dark bg-opacity-25 border-0 p-4 h-100">
                            <i class="fa-solid fa-comment-dots text-white fs-2 mb-3"></i>
                            <h5 class="text-white fw-bold mb-3">J'ai des questions</h5>
                            <p class="text-white opacity-75 mb-4">
                                Parlons ensemble de vos besoins et trouvons la meilleure solution.
                            </p>
                            <a href="/contact" class="btn btn-outline-light btn-lg">
                                <i class="fa-solid fa-phone me-2"></i>Contactez-nous
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section récapitulatif de l'essai -->
<section class="section bg-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="fw-black mb-3">Ce que vous avez découvert pendant 30 jours</h2>
                    <p class="text-muted fs-5">Tout SERVO, sans limitation, comme nos clients payants</p>
                </div>
                
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-message"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-2">SMS automatiques illimités</h6>
                                <p class="text-muted mb-0">Notifications clients, relances, confirmations - sans limite</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-2">Équipe complète</h6>
                                <p class="text-muted mb-0">Tous vos employés, pointage, gestion des droits</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-2">Gestion de stock avancée</h6>
                                <p class="text-muted mb-0">Alertes, commandes fournisseurs, suivi temps réel</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-2">Rapports et analytics</h6>
                                <p class="text-muted mb-0">Performance, rentabilité, exports comptables</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="card-modern border-success border-2 p-4 mb-4">
                        <h6 class="fw-bold text-success mb-2">
                            <i class="fa-solid fa-gift me-2"></i>Offre spéciale fin d'essai
                        </h6>
                        <p class="mb-0">
                            <strong>1 mois gratuit</strong> sur votre premier abonnement annuel
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section plans d'abonnement -->
<section id="plans" class="section bg-gray-50">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Choisissez votre abonnement SERVO</h2>
            <p class="text-muted fs-5">Plans flexibles, annulation à tout moment</p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <?php foreach ($plans as $index => $plan): ?>
                <?php if ($plan['billing_period'] == 'monthly'): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-lg <?php echo $index == 1 ? 'border-primary border-3' : ''; ?>">
                        <?php if ($index == 1): ?>
                            <div class="badge bg-primary position-absolute top-0 start-50 translate-middle px-3 py-2">
                                <i class="fa-solid fa-crown me-1"></i>Recommandé
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body p-4 text-center">
                            <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($plan['name']); ?></h5>
                            
                            <div class="mb-4">
                                <div class="display-6 fw-black text-primary">
                                    <?php echo number_format($plan['price'], 0); ?>€
                                </div>
                                <small class="text-muted">/mois HT</small>
                            </div>
                            
                            <p class="text-muted mb-4"><?php echo htmlspecialchars($plan['description']); ?></p>
                            
                            <div class="d-grid mb-4">
                                <a href="https://mdgeek.top/checkout.php?plan=<?php echo $plan['id']; ?>&shop=<?php echo $shop_id; ?>" 
                                   class="btn <?php echo $index == 1 ? 'btn-primary' : 'btn-outline-primary'; ?> btn-lg">
                                    <i class="fa-solid fa-credit-card me-2"></i>Choisir ce plan
                                </a>
                            </div>
                            
                            <div class="text-start">
                                <h6 class="fw-semibold mb-3">Fonctionnalités incluses :</h6>
                                <ul class="list-unstyled">
                                    <?php 
                                    $features = json_decode($plan['features'], true);
                                    foreach ($features as $feature): 
                                    ?>
                                        <li class="mb-2">
                                            <i class="fa-solid fa-check text-success me-2"></i>
                                            <?php echo htmlspecialchars($feature); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <div class="mt-3 pt-3 border-top">
                                    <?php if ($plan['sms_credits'] == -1): ?>
                                        <small class="text-success fw-semibold">
                                            <i class="fa-solid fa-infinity me-1"></i>SMS illimités
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">
                                            <i class="fa-solid fa-message me-1"></i><?php echo $plan['sms_credits']; ?> SMS/mois
                                        </small>
                                    <?php endif; ?>
                                    
                                    <br>
                                    
                                    <?php if ($plan['max_users'] == -1): ?>
                                        <small class="text-muted">
                                            <i class="fa-solid fa-users me-1"></i>Utilisateurs illimités
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">
                                            <i class="fa-solid fa-users me-1"></i>Jusqu'à <?php echo $plan['max_users']; ?> utilisateurs
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <p class="text-muted mb-3">Besoin d'un plan personnalisé ?</p>
            <a href="/contact" class="btn btn-outline-primary">
                <i class="fa-solid fa-phone me-2"></i>Contactez notre équipe
            </a>
        </div>
    </div>
</section>

<!-- Section FAQ -->
<section class="section bg-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="fw-black mb-3">Questions fréquentes</h2>
                </div>
                
                <div class="accordion" id="subscriptionFAQ">
                    <div class="accordion-item border-0 mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-semibold bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Puis-je changer d'abonnement à tout moment ?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#subscriptionFAQ">
                            <div class="accordion-body">
                                Oui, vous pouvez upgrader ou downgrader votre abonnement à tout moment. Les changements prennent effet immédiatement avec ajustement au prorata.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Puis-je annuler mon abonnement ?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#subscriptionFAQ">
                            <div class="accordion-body">
                                Bien sûr ! Vous pouvez annuler votre abonnement à tout moment depuis votre espace client. Aucun engagement, aucune pénalité.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Mes données sont-elles conservées si j'annule ?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#subscriptionFAQ">
                            <div class="accordion-body">
                                Vos données sont conservées pendant 90 jours après l'annulation. Vous pouvez réactiver votre compte et récupérer toutes vos données dans cette période.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Le support est-il inclus ?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#subscriptionFAQ">
                            <div class="accordion-body">
                                Oui ! Tous nos abonnements incluent le support par email et chat. Les plans Professional et Enterprise bénéficient d'un support prioritaire.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Inclure le footer marketing
include_once('marketing/shared/footer.php');
?>
