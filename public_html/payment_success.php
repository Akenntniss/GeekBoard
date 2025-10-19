<?php
// Page de succès après paiement Stripe
session_start();

// Inclure le gestionnaire d'abonnements
require_once('classes/SubscriptionManager.php');
require_once('config/database.php');

$subscription_id = $_GET['subscription_id'] ?? null;
$shop_id = $_GET['shop_id'] ?? $_SESSION['shop_id'] ?? null;

if (!$subscription_id && !$shop_id) {
    header('Location: /subscription_required.php');
    exit;
}

$subscriptionManager = new SubscriptionManager();

// Si on a un subscription_id, récupérer les détails
$subscription_details = null;
if ($subscription_id) {
    // Ici sera ajoutée la logique pour récupérer les détails depuis Stripe
    // Pour l'instant, simulation
    $subscription_details = [
        'plan_name' => 'Professional',
        'amount' => 59.99,
        'billing_period' => 'monthly'
    ];
}

// Obtenir les informations du shop
$pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
$stmt = $pdo->prepare("
    SELECT s.*, so.prenom, so.nom, so.email
    FROM shops s 
    JOIN shop_owners so ON s.id = so.shop_id 
    WHERE s.id = ?
");
$stmt->execute([$shop_id]);
$shopData = $stmt->fetch(PDO::FETCH_ASSOC);

// Inclure le header marketing
include_once('marketing/shared/header.php');
?>

<!-- Hero Section -->
<section class="section bg-gradient-primary text-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="mb-4">
                    <i class="fa-solid fa-check-circle" style="font-size: 4rem; color: #4CAF50;"></i>
                </div>
                
                <h1 class="display-4 fw-black mb-4">Paiement réussi !</h1>
                <p class="fs-5 opacity-90 mb-4">
                    Félicitations ! Votre abonnement SERVO est maintenant actif.
                </p>
                
                <?php if ($subscription_details): ?>
                    <div class="card-modern bg-white bg-opacity-15 border-0 p-4 mb-4">
                        <div class="text-white">
                            <h5 class="fw-bold mb-2">Abonnement activé</h5>
                            <p class="mb-0">
                                <strong><?php echo htmlspecialchars($subscription_details['plan_name']); ?></strong> - 
                                <?php echo number_format($subscription_details['amount'], 2); ?>€/<?php echo $subscription_details['billing_period'] == 'yearly' ? 'an' : 'mois'; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Section informations -->
<section class="section bg-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card-modern p-4 h-100 text-center">
                            <i class="fa-solid fa-rocket text-primary fs-2 mb-3"></i>
                            <h5 class="fw-bold mb-3">Accédez à votre boutique</h5>
                            <p class="text-muted mb-4">
                                Votre boutique SERVO est maintenant pleinement active avec toutes les fonctionnalités.
                            </p>
                            <?php if ($shopData): ?>
                                <a href="https://<?php echo htmlspecialchars($shopData['subdomain']); ?>.mdgeek.top" 
                                   target="_blank" class="btn btn-primary">
                                    <i class="fa-solid fa-external-link-alt me-2"></i>
                                    Ouvrir ma boutique
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card-modern p-4 h-100 text-center">
                            <i class="fa-solid fa-file-invoice text-success fs-2 mb-3"></i>
                            <h5 class="fw-bold mb-3">Facture et gestion</h5>
                            <p class="text-muted mb-4">
                                Gérez votre abonnement et téléchargez vos factures depuis votre espace client.
                            </p>
                            <a href="/customer-portal" class="btn btn-outline-primary">
                                <i class="fa-solid fa-cog me-2"></i>
                                Gérer mon abonnement
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-5">
                    <div class="card-modern bg-success bg-opacity-10 border-success border-2 p-4">
                        <h5 class="fw-bold text-success mb-3">
                            <i class="fa-solid fa-gift me-2"></i>Bienvenue dans SERVO !
                        </h5>
                        <p class="mb-4">
                            Votre transition de l'essai vers l'abonnement s'est parfaitement déroulée. 
                            Toutes vos données, configurations et historiques sont préservés.
                        </p>
                        
                        <div class="row g-3 text-start">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-check text-success"></i>
                                    <small>SMS illimités activés</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-check text-success"></i>
                                    <small>Support prioritaire</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-check text-success"></i>
                                    <small>Fonctionnalités avancées</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section aide -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="fw-black mb-3">Besoin d'aide ?</h2>
                    <p class="text-muted">Notre équipe est là pour vous accompagner</p>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fa-solid fa-book text-primary fs-3 mb-3"></i>
                            <h6 class="fw-bold mb-2">Documentation</h6>
                            <p class="text-muted small mb-3">Guides d'utilisation et tutoriels</p>
                            <a href="/docs" class="btn btn-sm btn-outline-primary">Consulter</a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fa-solid fa-comments text-primary fs-3 mb-3"></i>
                            <h6 class="fw-bold mb-2">Chat support</h6>
                            <p class="text-muted small mb-3">Assistance en temps réel</p>
                            <a href="/support" class="btn btn-sm btn-outline-primary">Discuter</a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fa-solid fa-phone text-primary fs-3 mb-3"></i>
                            <h6 class="fw-bold mb-2">Support téléphonique</h6>
                            <p class="text-muted small mb-3">Lun-Ven 9h-18h</p>
                            <a href="tel:+33123456789" class="btn btn-sm btn-outline-primary">Appeler</a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-5">
                    <p class="text-muted">
                        <i class="fa-solid fa-envelope me-2"></i>
                        Une question ? Contactez-nous à 
                        <a href="mailto:support@mdgeek.top" class="text-primary">support@mdgeek.top</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Inclure le footer marketing
include_once('marketing/shared/footer.php');
?>
