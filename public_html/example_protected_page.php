<?php
/**
 * Exemple d'intégration du middleware de vérification d'abonnement
 * Cette page montre comment protéger une page avec le middleware
 */

session_start();

// Inclure la configuration de base
require_once __DIR__ . '/config/database.php';

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    // La fonction checkSubscriptionAccess() gère la redirection automatique
    exit;
}

// Simulation d'un shop_id pour le test (en réalité, cela vient de la session)
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = $_GET['shop_id'] ?? 94; // Shop ID de test
}

// Inclure le header de votre boutique
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Protégée - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
        }
        .status-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?php 
    // ⭐ AFFICHER LE BANDEAU D'AVERTISSEMENT SI L'ESSAI VA EXPIRER
    displayTrialWarning(); 
    ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar simulé -->
            <div class="col-md-2 bg-dark text-white min-vh-100 p-3">
                <h5><i class="fa-solid fa-tools me-2"></i>GeekBoard</h5>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white active" href="#"><i class="fa-solid fa-home me-2"></i>Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#"><i class="fa-solid fa-wrench me-2"></i>Réparations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#"><i class="fa-solid fa-users me-2"></i>Clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#"><i class="fa-solid fa-box me-2"></i>Stock</a>
                    </li>
                </ul>
            </div>
            
            <!-- Contenu principal -->
            <div class="col-md-10 p-4">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2><i class="fa-solid fa-shield-halved me-2"></i>Page Protégée par Abonnement</h2>
                            <p class="mb-0">Cette page est accessible uniquement avec un abonnement valide.</p>
                            <small class="status-badge mt-2 d-inline-block">
                                <i class="fa-solid fa-check me-1"></i>Abonnement Actif
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fa-solid fa-lock-open" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Informations sur l'abonnement -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5><i class="fa-solid fa-info-circle me-2"></i>Statut du Shop</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Afficher les informations du shop
                                $subscriptionManager = new SubscriptionManager();
                                $status = $subscriptionManager->checkShopSubscriptionStatus($_SESSION['shop_id']);
                                
                                if ($status) {
                                    echo "<p><strong>Shop ID:</strong> " . htmlspecialchars($status['shop_id']) . "</p>";
                                    echo "<p><strong>Statut:</strong> <span class='badge bg-" . 
                                         ($status['subscription_status'] === 'active' ? 'success' : 'warning') . "'>" . 
                                         htmlspecialchars($status['subscription_status']) . "</span></p>";
                                    
                                    if ($status['subscription_status'] === 'trial' && $status['days_remaining'] !== null) {
                                        echo "<p><strong>Jours restants:</strong> " . $status['days_remaining'] . "</p>";
                                        echo "<p><strong>Fin d'essai:</strong> " . date('d/m/Y H:i', strtotime($status['trial_ends_at'])) . "</p>";
                                    }
                                    
                                    if ($status['plan_name']) {
                                        echo "<p><strong>Plan:</strong> " . htmlspecialchars($status['plan_name']) . "</p>";
                                        echo "<p><strong>Prix:</strong> " . number_format($status['plan_price'], 2) . "€</p>";
                                    }
                                } else {
                                    echo "<p class='text-warning'>Informations non disponibles</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5><i class="fa-solid fa-cogs me-2"></i>Actions Rapides</h5>
                            </div>
                            <div class="card-body">
                                <p>Que souhaitez-vous faire ?</p>
                                <div class="d-grid gap-2">
                                    <a href="https://mdgeek.top/subscription_required.php?shop_id=<?php echo $_SESSION['shop_id']; ?>#plans" 
                                       class="btn btn-outline-primary">
                                        <i class="fa-solid fa-credit-card me-2"></i>Gérer mon abonnement
                                    </a>
                                    <a href="https://mdgeek.top/checkout.php?plan=2&shop=<?php echo $_SESSION['shop_id']; ?>" 
                                       class="btn btn-outline-success">
                                        <i class="fa-solid fa-upgrade me-2"></i>Upgrader vers Professional
                                    </a>
                                    <button class="btn btn-outline-info" onclick="testExpiredAccess()">
                                        <i class="fa-solid fa-test me-2"></i>Tester accès expiré
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Zone de test -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fa-solid fa-flask me-2"></i>Zone de Test du Middleware</h5>
                    </div>
                    <div class="card-body">
                        <p>Cette section montre comment le middleware fonctionne :</p>
                        <ul>
                            <li><strong>✅ Accès autorisé :</strong> Vous voyez cette page car l'abonnement est valide</li>
                            <li><strong>❌ Accès refusé :</strong> Si l'abonnement était expiré, vous seriez redirigé vers 
                                <code>subscription_required.php</code></li>
                            <li><strong>⚠️ Avertissement :</strong> Si l'essai expire bientôt, un bandeau apparaît en haut</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <strong>💡 Pour les développeurs :</strong><br>
                            Le middleware vérifie automatiquement l'abonnement sur chaque page protégée.<br>
                            Code requis : <code>require_once 'includes/subscription_redirect_middleware.php';</code><br>
                            Puis : <code>checkSubscriptionAccess();</code> et <code>displayTrialWarning();</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testExpiredAccess() {
            alert('En production, cette fonction simulerait un abonnement expiré.\n\n' +
                  'Le middleware redirigerait automatiquement vers :\n' +
                  'https://mdgeek.top/subscription_required.php?shop_id=' + <?php echo $_SESSION['shop_id']; ?>);
        }
        
        // Simulation d'une requête AJAX qui pourrait échouer avec abonnement expiré
        function testAjaxWithExpiredSubscription() {
            fetch('/api/some_protected_endpoint.php')
                .then(response => {
                    if (response.status === 402) {
                        // Payment Required - abonnement expiré
                        return response.json().then(data => {
                            if (data.error === 'subscription_expired') {
                                window.location.href = data.redirect_url;
                            }
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Succès:', data);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
        }
    </script>
</body>
</html>
