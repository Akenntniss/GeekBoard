<?php
/**
 * Landing Page - Téléphonie VOIP
 * Simulateur d'appel intégré
 */
?>

<!-- Hero Section VOIP -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-success text-white mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-phone me-2"></i>
                        VOIP intégré
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        Téléphonie<br>dans SERVO
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        Appelez vos clients directement depuis l'interface. 
                        Historique, enregistrements, SMS : tout centralisé.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-success btn-lg">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-voip" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-phone-volume me-2"></i>
                            Tester un appel
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-bolt text-success"></i>
                            <small>Appel en 1 clic</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-microphone"></i>
                            <small>Enregistrements auto</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right" style="max-width: 350px; margin: 0 auto;">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Jean Dupont</h6>
                                <small class="text-muted">Réparation iPhone 14</small>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg">
                                <i class="fa-solid fa-phone me-2"></i>
                                Appeler
                            </button>
                            <button class="btn btn-outline-secondary">
                                <i class="fa-solid fa-sms me-2"></i>
                                Envoyer SMS
                            </button>
                        </div>
                        
                        <div class="mt-3 small text-muted">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Dernier appel:</span>
                                <span class="text-dark">Il y a 2h</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total appels:</span>
                                <span class="text-dark">3 appels</span>
                            </div>
                        </div>
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
                <div class="h2 fw-black text-primary mb-1">1 clic</div>
                <div class="text-muted">Pour appeler</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-success mb-1">Auto</div>
                <div class="text-muted">Enregistrements</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-warning mb-1">SMS</div>
                <div class="text-muted">Intégré</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-info mb-1">∞</div>
                <div class="text-muted">Historique</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE -->
<section class="section" id="demo-voip" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-success mb-3 px-3 py-2">
                <i class="fa-solid fa-phone me-2"></i>
                Démo appel VOIP
            </div>
            <h2 class="fw-black mb-3">Simulez un appel client</h2>
            <p class="text-muted fs-5">Découvrez l'interface d'appel intégrée</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card-modern p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="text-center mb-4">
                        <h5 class="fw-bold">Interface d'appel SERVO</h5>
                    </div>
                    
                    <div class="bg-white bg-opacity-10 rounded-4 p-4" id="call-interface">
                        <!-- État avant appel -->
                        <div id="call-idle">
                            <div class="text-center mb-4">
                                <div class="bg-white bg-opacity-20 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                                    <i class="fa-solid fa-user fs-1"></i>
                                </div>
                                <h5 class="fw-bold">Jean Dupont</h5>
                                <p class="mb-0 opacity-75">+33 6 12 34 56 78</p>
                                <small class="opacity-50">iPhone 14 - Écran cassé</small>
                            </div>
                            
                            <button class="btn btn-success btn-lg w-100 mb-3" onclick="startCall()">
                                <i class="fa-solid fa-phone me-2"></i>
                                Lancer l'appel
                            </button>
                            
                            <div class="row g-2">
                                <div class="col-4 text-center">
                                    <button class="btn btn-light btn-sm w-100">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </button>
                                    <small class="d-block mt-1 opacity-75">Historique</small>
                                </div>
                                <div class="col-4 text-center">
                                    <button class="btn btn-light btn-sm w-100">
                                        <i class="fa-solid fa-sms"></i>
                                    </button>
                                    <small class="d-block mt-1 opacity-75">SMS</small>
                                </div>
                                <div class="col-4 text-center">
                                    <button class="btn btn-light btn-sm w-100">
                                        <i class="fa-solid fa-user"></i>
                                    </button>
                                    <small class="d-block mt-1 opacity-75">Profil</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Appel en cours -->
                        <div id="call-active" class="d-none">
                            <div class="text-center mb-4">
                                <div class="bg-white bg-opacity-20 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3 animate-pulse" style="width: 100px; height: 100px;">
                                    <i class="fa-solid fa-phone-volume fs-1"></i>
                                </div>
                                <h5 class="fw-bold">Jean Dupont</h5>
                                <p class="mb-0 opacity-75">Appel en cours...</p>
                                <div class="h3 fw-bold mt-2" id="call-timer">00:00</div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-4 text-center">
                                    <button class="btn btn-light rounded-circle" style="width: 60px; height: 60px;">
                                        <i class="fa-solid fa-microphone-slash"></i>
                                    </button>
                                    <small class="d-block mt-1 opacity-75">Mute</small>
                                </div>
                                <div class="col-4 text-center">
                                    <button class="btn btn-light rounded-circle" style="width: 60px; height: 60px;">
                                        <i class="fa-solid fa-pause"></i>
                                    </button>
                                    <small class="d-block mt-1 opacity-75">Pause</small>
                                </div>
                                <div class="col-4 text-center">
                                    <button class="btn btn-light rounded-circle" style="width: 60px; height: 60px;">
                                        <i class="fa-solid fa-volume-high"></i>
                                    </button>
                                    <small class="d-block mt-1 opacity-75">HP</small>
                                </div>
                            </div>
                            
                            <button class="btn btn-danger btn-lg w-100" onclick="endCall()">
                                <i class="fa-solid fa-phone-slash me-2"></i>
                                Raccrocher
                            </button>
                        </div>
                        
                        <!-- Appel terminé -->
                        <div id="call-ended" class="d-none">
                            <div class="text-center mb-4">
                                <div class="bg-white bg-opacity-20 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                                    <i class="fa-solid fa-check fs-1"></i>
                                </div>
                                <h5 class="fw-bold">Appel terminé</h5>
                                <p class="mb-0 opacity-75">Durée: <span id="final-duration">02:34</span></p>
                            </div>
                            
                            <div class="bg-white bg-opacity-10 rounded p-3 mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Enregistrement sauvegardé</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Historique mis à jour</span>
                                </div>
                            </div>
                            
                            <button class="btn btn-light w-100" onclick="resetCall()">
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

<!-- Fonctionnalités -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Téléphonie complète intégrée</h2>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-phone text-success fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">Appel en 1 clic</h5>
                    <p class="text-muted">Depuis n'importe quelle fiche client ou réparation</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-microphone text-danger fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">Enregistrements auto</h5>
                    <p class="text-muted">Tous vos appels enregistrés et disponibles</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-feature p-4 text-center h-100">
                    <i class="fa-solid fa-clock-rotate-left text-info fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-3">Historique complet</h5>
                    <p class="text-muted">Retrouvez tous vos échanges avec chaque client</p>
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
                <h2 class="fw-black mb-4">Centralisez tout dans SERVO</h2>
                <p class="fs-5 mb-4 opacity-90">
                    Plus besoin de 10 outils. Appels, SMS, réparations, tout au même endroit.
                </p>
                <a href="/inscription" class="btn btn-light btn-lg">
                    <i class="fa-solid fa-rocket me-2"></i>
                    Démarrer l'essai gratuit
                </a>
            </div>
        </div>
    </div>
</section>

<script>
let callTimer;
let seconds = 0;

function startCall() {
    document.getElementById('call-idle').classList.add('d-none');
    document.getElementById('call-active').classList.remove('d-none');
    
    seconds = 0;
    callTimer = setInterval(() => {
        seconds++;
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        document.getElementById('call-timer').textContent = 
            `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }, 1000);
}

function endCall() {
    clearInterval(callTimer);
    document.getElementById('call-active').classList.add('d-none');
    document.getElementById('call-ended').classList.remove('d-none');
    
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    document.getElementById('final-duration').textContent = 
        `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function resetCall() {
    document.getElementById('call-ended').classList.add('d-none');
    document.getElementById('call-idle').classList.remove('d-none');
}

// CSS pour l'animation pulse
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    .animate-pulse {
        animation: pulse 2s infinite;
    }
`;
document.head.appendChild(style);
</script>
