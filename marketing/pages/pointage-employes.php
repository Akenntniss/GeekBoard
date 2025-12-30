<?php
/**
 * Landing Page - Pointage Employés
 * Unique : QR Code + WiFi + Anti-triche
 */
?>

<!-- Hero Section Pointage -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-info text-dark mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-clock me-2"></i>
                        Solution anti-triche
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        Pointage QR + WiFi<br>100% fiable
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        Vos employés pointent via QR code ou WiFi automatique. 
                        Géolocalisation, vérification WiFi, détection anti-triche : impossible de tricher.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-info btn-lg text-dark">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-pointage" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-qrcode me-2"></i>
                            Tester le QR code
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-info"></i>
                            <small>Anti-triche intégré</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-wifi"></i>
                            <small>WiFi automatique</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <!-- Preview pointage -->
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right animate-float" style="max-width: 350px; margin: 0 auto;">
                        <div class="text-center mb-3">
                            <div class="bg-info bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                                <i class="fa-solid fa-qrcode text-info" style="font-size: 3rem;"></i>
                            </div>
                            <h6 class="fw-bold">Scanner pour pointer</h6>
                            <small class="text-muted">QR Code unique magasin</small>
                        </div>
                        
                        <div class="bg-light rounded p-3 mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-location-dot text-success"></i>
                                <small><strong>GPS:</strong> Boutique Paris 9ème</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-wifi text-success"></i>
                                <small><strong>WiFi:</strong> SERVO_SHOP</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-clock text-success"></i>
                                <small><strong>Heure:</strong> 09:00</small>
                            </div>
                        </div>
                        
                        <button class="btn btn-success w-100 btn-lg">
                            <i class="fa-solid fa-check me-2"></i>
                            Pointer Arrivée
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
                <div class="h2 fw-black text-primary mb-1">0%</div>
                <div class="text-muted">Fraude possible</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-success mb-1">100%</div>
                <div class="text-muted">Fiabilité GPS+WiFi</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-warning mb-1">2 sec</div>
                <div class="text-muted">Temps de pointage</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-info mb-1">Auto</div>
                <div class="text-muted">Calcul heures</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE POINTAGE -->
<section class="section" id="demo-pointage" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-info text-dark mb-3 px-3 py-2">
                <i class="fa-solid fa-mobile-screen me-2"></i>
                Démo interactive
            </div>
            <h2 class="fw-black mb-3">Scannez le QR code en direct</h2>
            <p class="text-muted fs-5">Simulez un pointage employé avec vérifications anti-triche</p>
        </div>
        
        <div class="row g-4">
            <!-- QR Code Generator -->
            <div class="col-lg-5">
                <div class="card-modern p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="fa-solid fa-qrcode text-info me-2"></i>
                        QR Code Magasin
                    </h5>
                    
                    <!-- QR Code Display -->
                    <div class="bg-white rounded p-4 mb-4 text-center">
                        <canvas id="qr-canvas" style="max-width: 200px; margin: 0 auto;"></canvas>
                        <p class="text-muted small mt-3 mb-0">QR Code unique par magasin</p>
                    </div>
                    
                    <!-- Paramètres anti-triche -->
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-3">Sécurité activée</h6>
                        
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="check-gps" checked onchange="updateSecurityStatus()">
                            <label class="form-check-label" for="check-gps">
                                <i class="fa-solid fa-location-dot text-success me-1"></i>
                                Vérification GPS
                            </label>
                        </div>
                        
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="check-wifi" checked onchange="updateSecurityStatus()">
                            <label class="form-check-label" for="check-wifi">
                                <i class="fa-solid fa-wifi text-success me-1"></i>
                                Vérification WiFi
                            </label>
                        </div>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="check-time" checked onchange="updateSecurityStatus()">
                            <label class="form-check-label" for="check-time">
                                <i class="fa-solid fa-clock text-success me-1"></i>
                                Créneaux horaires
                            </label>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mb-0" style="border-radius: var(--border-radius);">
                        <i class="fa-solid fa-shield-halved me-2"></i>
                        <small><strong id="security-count">3</strong> vérifications actives</small>
                    </div>
                </div>
            </div>
            
            <!-- Simulation Pointage -->
            <div class="col-lg-7">
                <div class="card-modern p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">
                            <i class="fa-solid fa-mobile-screen-button me-2"></i>
                            App Mobile Employé
                        </h5>
                        <span class="badge bg-white text-dark" id="scan-status">
                            <i class="fa-solid fa-circle-dot me-1"></i>
                            Prêt à scanner
                        </span>
                    </div>
                    
                    <!-- Simulation écran mobile -->
                    <div class="bg-white rounded-4 p-4 text-dark">
                        <!-- État initial -->
                        <div id="scan-state-ready">
                            <div class="text-center mb-4">
                                <div class="bg-info bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                                    <i class="fa-solid fa-qrcode text-info" style="font-size: 3rem;"></i>
                                </div>
                                <h6 class="fw-bold">Pointage QR Code</h6>
                                <small class="text-muted">Scannez le QR code du magasin</small>
                            </div>
                            
                            <button class="btn btn-info w-100 btn-lg" onclick="simulateScan()">
                                <i class="fa-solid fa-camera me-2"></i>
                                Scanner le QR Code
                            </button>
                        </div>
                        
                        <!-- État scanning -->
                        <div id="scan-state-scanning" class="d-none">
                            <div class="text-center mb-4">
                                <div class="spinner-border text-info mb-3" style="width: 60px; height: 60px;"></div>
                                <h6 class="fw-bold">Vérifications en cours...</h6>
                            </div>
                            
                            <div id="verification-steps">
                                <div class="d-flex align-items-center gap-3 mb-3 verification-step" data-step="gps">
                                    <div class="spinner-border spinner-border-sm text-info"></div>
                                    <span>Vérification GPS...</span>
                                </div>
                                <div class="d-flex align-items-center gap-3 mb-3 verification-step" data-step="wifi">
                                    <div class="spinner-border spinner-border-sm text-info"></div>
                                    <span>Vérification WiFi...</span>
                                </div>
                                <div class="d-flex align-items-center gap-3 verification-step" data-step="time">
                                    <div class="spinner-border spinner-border-sm text-info"></div>
                                    <span>Vérification Horaire...</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- État success -->
                        <div id="scan-state-success" class="d-none">
                            <div class="text-center mb-4">
                                <div class="bg-success bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; animation: scaleIn 0.5s;">
                                    <i class="fa-solid fa-check text-success" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="fw-bold text-success">Pointage validé !</h5>
                                <p class="text-muted mb-0">Arrivée enregistrée à <strong id="clock-time">09:00</strong></p>
                            </div>
                            
                            <div class="bg-light rounded p-3 mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Employé</small>
                                    <small class="fw-bold">Jean Dupont</small>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Lieu</small>
                                    <small class="fw-bold">Paris 9ème</small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">Type</small>
                                    <small class="fw-bold text-success">Arrivée</small>
                                </div>
                            </div>
                            
                            <button class="btn btn-outline-secondary w-100" onclick="resetScan()">
                                <i class="fa-solid fa-rotate-right me-2"></i>
                                Refaire une démo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Méthodes de pointage -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">3 méthodes de pointage</h2>
            <p class="text-muted fs-5">Adaptées à tous vos cas d'usage</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-qrcode fs-2"></i>
                    </div>
                    <h5 class="fw-bold mb-3">QR Code</h5>
                    <p class="text-muted mb-4">
                        L'employé scanne le QR code affiché en magasin. 
                        Vérification GPS + WiFi pour valider la position.
                    </p>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-success me-2"></i>
                            QR unique par magasin
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-success me-2"></i>
                            Géolocalisation obligatoire
                        </li>
                        <li>
                            <i class="fa-solid fa-check text-success me-2"></i>
                            Historique photo horodaté
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100 border-info" style="border-width: 2px !important;">
                    <div class="badge bg-info text-dark mb-2">Recommandé</div>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-wifi fs-2"></i>
                    </div>
                    <h5 class="fw-bold mb-3">WiFi Auto</h5>
                    <p class="text-muted mb-4">
                        Pointage automatique dès connexion au WiFi du magasin. 
                        L'employé ne fait rien, c'est 100% transparent.
                    </p>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-success me-2"></i>
                            Pointage automatique
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-success me-2"></i>
                            Aucune manipulation
                        </li>
                        <li>
                            <i class="fa-solid fa-check text-success me-2"></i>
                            Impossible d'oublier
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-mobile-screen fs-2"></i>
                    </div>
                    <h5 class="fw-bold mb-3">Manuel (GPS)</h5>
                    <p class="text-muted mb-4">
                        L'employé clique sur un bouton dans l'app. 
                        Utile pour déplacements externes.
                    </p>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-success me-2"></i>
                            Bouton "Pointer"
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-success me-2"></i>
                            GPS vérifié
                        </li>
                        <li>
                            <i class="fa-solid fa-check text-success me-2"></i>
                            Justificatif photo
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Fonctionnalités anti-triche -->
<section class="section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h2 class="fw-black mb-4">Système anti-triche intégré</h2>
                <p class="text-muted fs-5 mb-4">
                    Impossible de pointer depuis chez soi ou de faire pointer un collègue. 
                    Toutes les tentatives de fraude sont bloquées automatiquement.
                </p>
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex align-items-start gap-3">
                            <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Géolocalisation obligatoire</h6>
                                <small class="text-muted">Rayon de 100m autour du magasin configurable</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-start gap-3">
                            <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">WiFi magasin requis</h6>
                                <small class="text-muted">Double vérification WiFi + GPS pour être sûr</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-start gap-3">
                            <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Créneaux horaires stricts</h6>
                                <small class="text-muted">Pointage uniquement dans les plages définies</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-start gap-3">
                            <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Alertes admin en temps réel</h6>
                                <small class="text-muted">Notification si tentative de fraude détectée</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card-modern p-4 bg-danger bg-opacity-5">
                    <h6 class="fw-bold text-danger mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Exemple d'alerte fraude
                    </h6>
                    
                    <div class="bg-white rounded p-3">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-exclamation"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong class="text-danger">Tentative de pointage refusée</strong>
                                <div class="small text-muted">Il y a 2 minutes</div>
                            </div>
                        </div>
                        
                        <div class="small">
                            <div class="mb-2">
                                <strong>Employé:</strong> Jean Dupont
                            </div>
                            <div class="mb-2">
                                <strong>Raison:</strong> Hors zone GPS autorisée
                            </div>
                            <div class="mb-2">
                                <strong>Position:</strong> 2,3 km du magasin
                            </div>
                            <div class="text-danger">
                                <i class="fa-solid fa-times-circle me-1"></i>
                                Pointage bloqué automatiquement
                            </div>
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
                <h2 class="fw-black mb-4">100% fiable, 0% de fraude</h2>
                <p class="fs-5 mb-4 opacity-90">
                    Le seul système de pointage qui combine QR, WiFi, GPS et créneaux horaires. 
                    Vos employés ne peuvent plus tricher, vous gagnez des heures de gestion.
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="/inscription" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Démarrer l'essai gratuit
                    </a>
                    <a href="/features" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-arrow-right me-2"></i>
                        Autres fonctionnalités
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript pour la démo -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// Générer QR Code
const qrCanvas = document.getElementById('qr-canvas');
new QRCode(qrCanvas, {
    text: 'SERVO_SHOP_PARIS_9',
    width: 200,
    height: 200,
    colorDark: '#0dcaf0',
    colorLight: '#ffffff'
});

function updateSecurityStatus() {
    const gps = document.getElementById('check-gps').checked;
    const wifi = document.getElementById('check-wifi').checked;
    const time = document.getElementById('check-time').checked;
    
    const count = (gps ? 1 : 0) + (wifi ? 1 : 0) + (time ? 1 : 0);
    document.getElementById('security-count').textContent = count;
}

function simulateScan() {
    // Hide ready state
    document.getElementById('scan-state-ready').classList.add('d-none');
    // Show scanning state
    document.getElementById('scan-state-scanning').classList.remove('d-none');
    document.getElementById('scan-status').innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Scan en cours...';
    
    // Simulate verification steps
    const steps = ['gps', 'wifi', 'time'];
    let currentStep = 0;
    
    const interval = setInterval(() => {
        if (currentStep < steps.length) {
            const step = steps[currentStep];
            const checkbox = document.getElementById('check-' + step);
            
            if (checkbox && checkbox.checked) {
                const stepElement = document.querySelector(`.verification-step[data-step="${step}"]`);
                stepElement.innerHTML = `
                    <i class="fa-solid fa-check-circle text-success"></i>
                    <span class="text-success">Vérification ${step.toUpperCase()} ✓</span>
                `;
            }
            currentStep++;
        } else {
            clearInterval(interval);
            
            // Show success after all checks
            setTimeout(() => {
                document.getElementById('scan-state-scanning').classList.add('d-none');
                document.getElementById('scan-state-success').classList.remove('d-none');
                document.getElementById('scan-status').innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Validé';
                
                // Update time
                const now = new Date();
                const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
                document.getElementById('clock-time').textContent = timeStr;
            }, 500);
        }
    }, 800);
}

function resetScan() {
    document.getElementById('scan-state-success').classList.add('d-none');
    document.getElementById('scan-state-ready').classList.remove('d-none');
    document.getElementById('scan-status').innerHTML = '<i class="fa-solid fa-circle-dot me-1"></i> Prêt à scanner';
    
    // Reset verification steps
    const steps = document.querySelectorAll('.verification-step');
    steps.forEach((step, index) => {
        const type = ['gps', 'wifi', 'time'][index];
        step.innerHTML = `
            <div class="spinner-border spinner-border-sm text-info"></div>
            <span>Vérification ${type.toUpperCase()}...</span>
        `;
    });
}

// Animation scale
const style = document.createElement('style');
style.textContent = `
    @keyframes scaleIn {
        from { transform: scale(0); }
        to { transform: scale(1); }
    }
`;
document.head.appendChild(style);
</script>
