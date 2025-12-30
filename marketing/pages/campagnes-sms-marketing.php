<?php
/**
 * Landing Page - Campagnes SMS Marketing
 * Unique : Marketing automation ciblé
 */
?>

<!-- Hero Section Campagnes SMS -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-warning text-dark mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-bullhorn me-2"></i>
                        Marketing Automation
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        Campagnes SMS<br>ultra-ciblées
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        Envoyez des SMS marketing à vos clients selon leur historique. 
                        Réactivez les inactifs, fidélisez les réguliers, boostez vos ventes.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-warning btn-lg text-dark">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-campaigns" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-paper-plane me-2"></i>
                            Voir la démo
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-filter text-warning"></i>
                            <small>Filtres avancés</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-chart-line"></i>
                            <small>ROI mesurable</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right" style="max-width: 400px; margin: 0 auto;">
                        <h6 class="fw-bold mb-3">
                            <i class="fa-solid fa-bullhorn text-warning me-2"></i>
                            Nouvelle campagne
                        </h6>
                        
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Ciblage</label>
                            <select class="form-select form-select-sm" disabled>
                                <option>Clients inactifs depuis 3 mois</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Message</label>
                            <textarea class="form-control form-control-sm" rows="3" disabled>🎁 Offre spéciale ! 20% sur votre prochaine réparation. Valable jusqu'au 31/01. Code: RETOUR20</textarea>
                        </div>
                        
                        <div class="bg-warning bg-opacity-10 rounded p-2 mb-3">
                            <div class="d-flex justify-content-between small">
                                <span>Destinataires:</span>
                                <strong class="text-warning">247 clients</strong>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Coût estimé:</span>
                                <strong>24,70 €</strong>
                            </div>
                        </div>
                        
                        <button class="btn btn-warning btn-sm w-100">
                            <i class="fa-solid fa-paper-plane me-2"></i>
                            Envoyer la campagne
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-primary mb-1">+32%</div>
                <div class="text-muted">Taux de retour</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-success mb-1">18%</div>
                <div class="text-muted">Taux d'ouverture SMS</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-warning mb-1">0,10€</div>
                <div class="text-muted">Prix par SMS</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-info mb-1">ROI 8x</div>
                <div class="text-muted">Retour investi</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE -->
<section class="section" id="demo-campaigns" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-warning text-dark mb-3 px-3 py-2">
                <i class="fa-solid fa-flask me-2"></i>
                Créez votre campagne
            </div>
            <h2 class="fw-black mb-3">Simulateur de campagne SMS</h2>
            <p class="text-muted fs-5">Ciblez vos clients et mesurez l'impact</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card-modern p-4">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <h6 class="fw-bold mb-3">1. Ciblage</h6>
                            
                            <div class="mb-3">
                                <label class="form-label small">Type de clients</label>
                                <select class="form-select" id="target-type" onchange="updateCampaign()">
                                    <option value="inactive">Inactifs 3+ mois</option>
                                    <option value="regular">Clients réguliers</option>
                                    <option value="high-value">Gros clients (500€+)</option>
                                    <option value="all">Tous les clients</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Période</label>
                                <select class="form-select" id="period" onchange="updateCampaign()">
                                    <option value="3">3 derniers mois</option>
                                    <option value="6">6 derniers mois</option>
                                    <option value="12">12 derniers mois</option>
                                </select>
                            </div>
                            
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small">Destinataires:</span>
                                    <strong class="text-primary" id="recipient-count">247</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="small">Coût total:</span>
                                    <strong id="total-cost">24,70 €</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <h6 class="fw-bold mb-3">2. Message</h6>
                            
                            <select class="form-select mb-3" id="template" onchange="loadTemplate()">
                                <option value="promo">Promotion</option>
                                <option value="reminder">Rappel</option>
                                <option value="custom">Message personnalisé</option>
                            </select>
                            
                            <textarea class="form-control mb-3" rows="5" id="message-content">🎁 Offre spéciale ! 20% sur votre prochaine réparation. Valable jusqu'au 31/01. Code: RETOUR20</textarea>
                            
                            <div class="small text-muted">
                                <span id="char-count">125</span>/160 caractères
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <h6 class="fw-bold mb-3">3. Résultats estimés</h6>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Taux d'ouverture</span>
                                    <strong>98%</strong>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: 98%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Taux de clic estimé</span>
                                    <strong id="click-rate">18%</strong>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: 18%"></div>
                                </div>
                            </div>
                            
                            <div class="bg-success bg-opacity-10 rounded p-3 mb-3">
                                <div class="text-center">
                                    <div class="h4 fw-black text-success mb-1" id="roi-estimate">ROI: 8x</div>
                                    <small class="text-muted">Retour sur investissement</small>
                                </div>
                            </div>
                            
                            <button class="btn btn-warning w-100" onclick="sendCampaign()">
                                <i class="fa-solid fa-paper-plane me-2"></i>
                                Lancer la campagne
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cas d'usage -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Cas d'usage marketing</h2>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-user-clock text-warning fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">Réactiver les inactifs</h5>
                    <p class="text-muted">"Ça fait longtemps ! 20% de remise pour votre retour"</p>
                    <div class="badge bg-success">+32% de retours</div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-gift text-danger fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">Promotions flash</h5>
                    <p class="text-muted">"24h seulement : -30% sur tous les écrans"</p>
                    <div class="badge bg-primary">Ventes x3 le jour J</div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-heart text-primary fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">Fidélisation VIP</h5>
                    <p class="text-muted">"Merci pour votre fidélité ! Offre exclusive pour vous"</p>
                    <div class="badge bg-warning">LTV client +45%</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="fw-black mb-4">Transformez vos clients en ambassadeurs</h2>
                <p class="fs-5 mb-4 opacity-90">
                    SMS marketing ciblé = clients heureux qui reviennent.
                </p>
                <a href="/inscription" class="btn btn-warning btn-lg text-dark">
                    <i class="fa-solid fa-rocket me-2"></i>
                    Démarrer l'essai gratuit
                </a>
            </div>
        </div>
    </div>
</section>

<script>
const campaignData = {
    'inactive': {count: 247, rate: 18},
    'regular': {count: 89, rate: 25},
    'high-value': {count: 34, rate: 35},
    'all': {count: 1243, rate: 12}
};

function updateCampaign() {
    const type = document.getElementById('target-type').value;
    const data = campaignData[type];
    
    document.getElementById('recipient-count').textContent = data.count;
    document.getElementById('total-cost').textContent = (data.count * 0.10).toFixed(2) + ' €';
    document.getElementById('click-rate').textContent = data.rate + '%';
    document.getElementById('roi-estimate').textContent = 'ROI: ' + Math.round(data.rate / 2) + 'x';
    
    document.querySelector('.progress-bar.bg-info').style.width = data.rate + '%';
}

function loadTemplate() {
    const template = document.getElementById('template').value;
    const messages = {
        'promo': '🎁 Offre spéciale ! 20% sur votre prochaine réparation. Valable jusqu\'au 31/01. Code: RETOUR20',
        'reminder': '📱 Bonjour ! Votre appareil est prêt depuis 3 jours. Passez le récupérer. Merci !',
        'custom': ''
    };
    
    document.getElementById('message-content').value = messages[template];
    updateCharCount();
}

function updateCharCount() {
    const message = document.getElementById('message-content').value;
    document.getElementById('char-count').textContent = message.length;
}

function sendCampaign() {
    alert('🎉 Campagne envoyée avec succès !\n\nEn production, cette fonctionnalité enverrait les SMS à tous les destinataires ciblés.');
}

document.getElementById('message-content').addEventListener('input', updateCharCount);
</script>
