<?php
/**
 * Landing Page - SMS Automatiques
 * Page interactive où les visiteurs peuvent tester les SMS en temps réel
 */
?>

<!-- Hero Section SMS -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-dark text-white mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-message me-2"></i>
                        Fonctionnalité phare
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        Fini les appels<br>"Ma réparation est prête ?"
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        Vos clients sont informés automatiquement à chaque étape. 
                        Devis envoyé, réparation terminée, rappel de retrait — tout est géré sans intervention.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-light btn-lg">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-interactive" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-play me-2"></i>
                            Tester maintenant
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-check-circle"></i>
                            <small>-80% d'appels entrants</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-check-circle"></i>
                            <small>Templates personnalisables</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <!-- Simulation téléphone avec SMS -->
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right animate-float" style="max-width: 320px; margin: 0 auto;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success rounded-circle me-3" style="width: 12px; height: 12px; animation: pulse 2s infinite;"></div>
                            <small class="text-muted">Messages automatiques</small>
                        </div>
                        
                        <!-- SMS Devis -->
                        <div class="mb-3" id="sms-preview-1">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3 mb-2">
                                <small class="fw-semibold">📱 Votre devis iPhone 14 est prêt!</small><br>
                                <small class="text-muted">Montant: 149€ • Valider: servo.tools/d/xxx</small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">Envoyé • 14:32</small>
                            </div>
                        </div>
                        
                        <!-- SMS Réparation prête -->
                        <div class="mb-3" id="sms-preview-2" style="opacity: 0.5;">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3 mb-2">
                                <small class="fw-semibold">✅ Réparation terminée!</small><br>
                                <small class="text-muted">iPhone 14 vous attend • 149€ à régler</small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">Programmé • 16:45</small>
                            </div>
                        </div>
                        
                        <!-- SMS Rappel -->
                        <div id="sms-preview-3" style="opacity: 0.3;">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3 mb-2">
                                <small class="fw-semibold">⏰ Rappel: iPhone 14 vous attend</small><br>
                                <small class="text-muted">N'oubliez pas de venir récupérer votre appareil!</small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">Dans 7 jours</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="h2 fw-black text-primary mb-1">-80%</div>
                <div class="text-muted">Appels "C'est prêt?"</div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="h2 fw-black text-success mb-1">+35%</div>
                <div class="text-muted">Taux acceptation devis</div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="h2 fw-black text-warning mb-1">2 sec</div>
                <div class="text-muted">Envoi automatique</div>
            </div>
            <div class="col-lg-3 col-md-6 fade-in-up">
                <div class="h2 fw-black text-primary mb-1">100%</div>
                <div class="text-muted">Personnalisable</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE -->
<section class="section" id="demo-interactive" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-primary bg-opacity-10 text-primary mb-3 px-3 py-2">
                <i class="fa-solid fa-wand-magic-sparkles me-2"></i>
                Démo interactive
            </div>
            <h2 class="fw-black mb-3">Testez les SMS en direct</h2>
            <p class="text-muted fs-5">Simulez une réparation et voyez les SMS générés automatiquement</p>
        </div>
        
        <div class="row g-4 align-items-start">
            <!-- Panneau de contrôle -->
            <div class="col-lg-5">
                <div class="card-modern p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="fa-solid fa-sliders text-primary me-2"></i>
                        Simulateur de réparation
                    </h5>
                    
                    <!-- Étape 1: Client -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-user text-muted me-2"></i>
                            Nom du client
                        </label>
                        <input type="text" id="demo-client-name" class="form-control" value="Jean Dupont" 
                               style="border-radius: var(--border-radius); border: 2px solid var(--border-color);"
                               oninput="updateSMSPreview()">
                    </div>
                    
                    <!-- Étape 2: Appareil -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-mobile-screen text-muted me-2"></i>
                            Appareil
                        </label>
                        <select id="demo-device" class="form-select" 
                                style="border-radius: var(--border-radius); border: 2px solid var(--border-color);"
                                onchange="updateSMSPreview()">
                            <option value="iPhone 15 Pro">iPhone 15 Pro</option>
                            <option value="iPhone 14">iPhone 14</option>
                            <option value="Samsung S24">Samsung S24</option>
                            <option value="MacBook Pro">MacBook Pro</option>
                            <option value="iPad Air">iPad Air</option>
                        </select>
                    </div>
                    
                    <!-- Étape 3: Panne -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-wrench text-muted me-2"></i>
                            Type de réparation
                        </label>
                        <select id="demo-repair" class="form-select" 
                                style="border-radius: var(--border-radius); border: 2px solid var(--border-color);"
                                onchange="updateSMSPreview()">
                            <option value="Écran" data-price="189">Remplacement écran - 189€</option>
                            <option value="Batterie" data-price="79">Changement batterie - 79€</option>
                            <option value="Connecteur" data-price="89">Connecteur de charge - 89€</option>
                            <option value="Vitre arrière" data-price="129">Vitre arrière - 129€</option>
                        </select>
                    </div>
                    
                    <!-- Statut avec boutons -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-arrows-spin text-muted me-2"></i>
                            Changer le statut
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-outline-primary btn-sm status-btn" data-status="devis" onclick="changeStatus('devis')">
                                📝 Devis envoyé
                            </button>
                            <button class="btn btn-outline-success btn-sm status-btn" data-status="termine" onclick="changeStatus('termine')">
                                ✅ Terminé
                            </button>
                            <button class="btn btn-outline-warning btn-sm status-btn" data-status="rappel" onclick="changeStatus('rappel')">
                                ⏰ Rappel
                            </button>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mb-0" style="border-radius: var(--border-radius);">
                        <i class="fa-solid fa-lightbulb me-2"></i>
                        <small>Cliquez sur un statut pour voir le SMS correspondant s'afficher en temps réel !</small>
                    </div>
                </div>
            </div>
            
            <!-- Prévisualisation SMS -->
            <div class="col-lg-7">
                <div class="card-modern p-4" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">
                            <i class="fa-solid fa-mobile-screen-button me-2"></i>
                            Aperçu SMS Client
                        </h5>
                        <span class="badge bg-success">
                            <i class="fa-solid fa-check me-1"></i>
                            Prêt à envoyer
                        </span>
                    </div>
                    
                    <!-- Simulation écran téléphone -->
                    <div class="mx-auto" style="max-width: 320px; background: #000; border-radius: 30px; padding: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                        <!-- Notch -->
                        <div class="bg-dark rounded-pill mx-auto mb-3" style="width: 120px; height: 25px;"></div>
                        
                        <!-- Écran -->
                        <div style="background: linear-gradient(180deg, #f5f5f5 0%, #e8e8e8 100%); border-radius: 20px; padding: 20px; min-height: 300px;">
                            <!-- Header SMS -->
                            <div class="d-flex align-items-center mb-4" style="color: #333;">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-store text-white"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 14px;">SERVO Réparations</div>
                                    <small class="text-muted">SMS automatique</small>
                                </div>
                            </div>
                            
                            <!-- Bulle SMS -->
                            <div id="sms-bubble" class="p-3 mb-3" style="background: #0b93f6; color: white; border-radius: 18px 18px 5px 18px; font-size: 14px; line-height: 1.5; transition: all 0.3s ease;">
                                <span id="sms-content">
                                    📝 Bonjour Jean Dupont,<br><br>
                                    Votre devis pour <strong>iPhone 15 Pro</strong> (Écran) est prêt !<br><br>
                                    💰 Montant: <strong>189€</strong><br>
                                    ✅ Valider: servo.tools/d/ABC123<br><br>
                                    À bientôt !<br>
                                    <em>— L'équipe SERVO</em>
                                </span>
                            </div>
                            
                            <!-- Timestamp -->
                            <div class="text-end" style="color: #666; font-size: 12px;">
                                <span id="sms-time">Aujourd'hui, 14:32</span>
                                <i class="fa-solid fa-check-double text-primary ms-1"></i>
                            </div>
                        </div>
                        
                        <!-- Home indicator -->
                        <div class="bg-white rounded-pill mx-auto mt-3" style="width: 100px; height: 5px;"></div>
                    </div>
                    
                    <!-- Infos en dessous -->
                    <div class="row g-3 mt-4 text-center">
                        <div class="col-4">
                            <div class="fw-bold" id="sms-chars">142</div>
                            <small class="opacity-75">caractères</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">1</div>
                            <small class="opacity-75">SMS (max 160)</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-success">0,07€</div>
                            <small class="opacity-75">coût estimé</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Types de SMS -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">5 types de SMS automatisés</h2>
            <p class="text-muted fs-5">Chaque étape clé déclenche le bon message au bon moment</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fa-solid fa-file-invoice fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">📝 Devis envoyé</h5>
                    <p class="text-muted small mb-3">Le client reçoit son devis avec un lien pour l'accepter en 1 clic.</p>
                    <div class="bg-light rounded p-2">
                        <small class="text-muted fst-italic">"Votre devis iPhone est prêt! Montant: 149€. Valider: [lien]"</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fa-solid fa-check fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">✅ Devis accepté</h5>
                    <p class="text-muted small mb-3">Confirmation automatique quand le client valide en ligne.</p>
                    <div class="bg-light rounded p-2">
                        <small class="text-muted fst-italic">"Merci! Votre devis est validé. Réparation en cours..."</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">🎉 Réparation terminée</h5>
                    <p class="text-muted small mb-3">Notification immédiate dès que le technicien termine.</p>
                    <div class="bg-light rounded p-2">
                        <small class="text-muted fst-italic">"Bonne nouvelle! Votre iPhone vous attend. Montant: 149€"</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fa-solid fa-clock fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">⏰ Rappel automatique</h5>
                    <p class="text-muted small mb-3">Si le client n'est pas venu après 7 jours, rappel automatique.</p>
                    <div class="bg-light rounded p-2">
                        <small class="text-muted fst-italic">"Rappel: votre iPhone vous attend toujours!"</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">🚨 Gardiennage</h5>
                    <p class="text-muted small mb-3">Notification automatique après 30 jours d'inactivité.</p>
                    <div class="bg-light rounded p-2">
                        <small class="text-muted fst-italic">"Attention: frais de gardiennage applicables sous 7 jours"</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 text-center h-100">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fa-solid fa-bullhorn fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">📣 Campagnes marketing</h5>
                    <p class="text-muted small mb-3">Promotions, offres spéciales envoyées à vos clients.</p>
                    <div class="bg-light rounded p-2">
                        <small class="text-muted fst-italic">"-20% sur les écrans ce week-end! Profitez-en"</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Templates personnalisables -->
<section class="section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h2 class="fw-black mb-4">Templates 100% personnalisables</h2>
                <p class="text-muted mb-4">
                    Créez vos propres modèles de SMS avec des variables dynamiques. 
                    Nom du client, appareil, prix, lien de suivi — tout s'insère automatiquement.
                </p>
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-check-circle text-success"></i>
                            <span>Variables dynamiques: <code>{client}</code>, <code>{appareil}</code>, <code>{prix}</code></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-check-circle text-success"></i>
                            <span>Prévisualisation en temps réel avant envoi</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-check-circle text-success"></i>
                            <span>Historique complet des SMS envoyés</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-check-circle text-success"></i>
                            <span>Compatible tous opérateurs français</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="/inscription" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Commencer l'essai gratuit
                    </a>
                </div>
            </div>
            
            <div class="col-lg-6">
                <!-- Éditeur de template interactif -->
                <div class="card-modern p-4">
                    <h6 class="fw-bold mb-3">
                        <i class="fa-solid fa-edit text-primary me-2"></i>
                        Éditeur de template
                    </h6>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted">Votre message personnalisé:</label>
                        <textarea id="template-editor" class="form-control" rows="4" 
                                  style="border-radius: var(--border-radius); border: 2px solid var(--border-color); font-family: monospace;"
                                  oninput="updateTemplatePreview()">Bonjour {client},

Votre {appareil} est prêt ! ✅
Montant à régler: {prix}€

À bientôt,
{boutique}</textarea>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button class="btn btn-outline-secondary btn-sm" onclick="insertVariable('{client}')">
                            <i class="fa-solid fa-user me-1"></i>{client}
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="insertVariable('{appareil}')">
                            <i class="fa-solid fa-mobile me-1"></i>{appareil}
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="insertVariable('{prix}')">
                            <i class="fa-solid fa-euro-sign me-1"></i>{prix}
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="insertVariable('{boutique}')">
                            <i class="fa-solid fa-store me-1"></i>{boutique}
                        </button>
                    </div>
                    
                    <div class="bg-light rounded p-3">
                        <small class="text-muted d-block mb-2">Aperçu avec données réelles:</small>
                        <div id="template-preview" style="white-space: pre-line; font-size: 14px;">
                            Bonjour Jean Dupont,

Votre iPhone 15 Pro est prêt ! ✅
Montant à régler: 189€

À bientôt,
Ma Boutique
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="fw-black mb-4 fade-in-up">Prêt à automatiser vos SMS ?</h2>
                <p class="fs-5 mb-4 opacity-90 fade-in-up">
                    Rejoignez des centaines d'ateliers qui ont déjà réduit leurs appels de 80%.
                    30 jours d'essai gratuit, sans engagement.
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center fade-in-up">
                    <a href="/inscription" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Démarrer l'essai gratuit
                    </a>
                    <a href="/features" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-arrow-right me-2"></i>
                        Voir toutes les fonctionnalités
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript pour la démo interactive -->
<script>
// Variables pour la démo
const smsTemplates = {
    devis: {
        icon: '📝',
        title: 'Devis envoyé',
        color: '#0b93f6',
        template: (data) => `📝 Bonjour ${data.client},

Votre devis pour <strong>${data.device}</strong> (${data.repair}) est prêt !

💰 Montant: <strong>${data.price}€</strong>
✅ Valider: servo.tools/d/ABC123

À bientôt !
<em>— L'équipe SERVO</em>`
    },
    termine: {
        icon: '✅',
        title: 'Réparation terminée',
        color: '#10b981',
        template: (data) => `✅ Bonne nouvelle ${data.client} !

Votre <strong>${data.device}</strong> est réparé et vous attend !

💰 Montant à régler: <strong>${data.price}€</strong>
📍 Passez quand vous voulez !

Merci de votre confiance !
<em>— L'équipe SERVO</em>`
    },
    rappel: {
        icon: '⏰',
        title: 'Rappel',
        color: '#f59e0b',
        template: (data) => `⏰ Rappel ${data.client},

Votre <strong>${data.device}</strong> vous attend toujours !

N'oubliez pas de venir le récupérer.
💰 Montant: <strong>${data.price}€</strong>

À très vite !
<em>— L'équipe SERVO</em>`
    }
};

let currentStatus = 'devis';

function updateSMSPreview() {
    const data = {
        client: document.getElementById('demo-client-name').value || 'Client',
        device: document.getElementById('demo-device').value,
        repair: document.getElementById('demo-repair').options[document.getElementById('demo-repair').selectedIndex].value,
        price: document.getElementById('demo-repair').options[document.getElementById('demo-repair').selectedIndex].dataset.price
    };
    
    const template = smsTemplates[currentStatus];
    const smsContent = template.template(data);
    
    document.getElementById('sms-content').innerHTML = smsContent;
    document.getElementById('sms-bubble').style.background = template.color;
    
    // Update character count
    const plainText = smsContent.replace(/<[^>]*>/g, '');
    document.getElementById('sms-chars').textContent = plainText.length;
}

function changeStatus(status) {
    currentStatus = status;
    
    // Update button states
    document.querySelectorAll('.status-btn').forEach(btn => {
        btn.classList.remove('btn-primary', 'btn-success', 'btn-warning');
        btn.classList.add('btn-outline-' + (btn.dataset.status === 'devis' ? 'primary' : btn.dataset.status === 'termine' ? 'success' : 'warning'));
    });
    
    const activeBtn = document.querySelector(`.status-btn[data-status="${status}"]`);
    activeBtn.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-warning');
    activeBtn.classList.add(status === 'devis' ? 'btn-primary' : status === 'termine' ? 'btn-success' : 'btn-warning');
    
    // Animate SMS bubble
    const bubble = document.getElementById('sms-bubble');
    bubble.style.transform = 'scale(0.95)';
    bubble.style.opacity = '0.5';
    
    setTimeout(() => {
        updateSMSPreview();
        bubble.style.transform = 'scale(1)';
        bubble.style.opacity = '1';
    }, 150);
    
    // Update time
    const now = new Date();
    const timeStr = `Aujourd'hui, ${now.getHours()}:${String(now.getMinutes()).padStart(2, '0')}`;
    document.getElementById('sms-time').textContent = timeStr;
}

function insertVariable(variable) {
    const editor = document.getElementById('template-editor');
    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    const text = editor.value;
    
    editor.value = text.substring(0, start) + variable + text.substring(end);
    editor.focus();
    editor.setSelectionRange(start + variable.length, start + variable.length);
    
    updateTemplatePreview();
}

function updateTemplatePreview() {
    const template = document.getElementById('template-editor').value;
    const preview = template
        .replace(/{client}/g, 'Jean Dupont')
        .replace(/{appareil}/g, 'iPhone 15 Pro')
        .replace(/{prix}/g, '189')
        .replace(/{boutique}/g, 'Ma Boutique');
    
    document.getElementById('template-preview').textContent = preview;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    changeStatus('devis');
});
</script>
