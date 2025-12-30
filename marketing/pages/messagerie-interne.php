<?php
/**
 * Landing Page - Messagerie Interne
 * Communication équipe instantanée
 */
?>

<!-- Hero Section -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6">
                <div class="badge bg-primary text-white mb-4 px-3 py-2">
                    <i class="fa-solid fa-comments me-2"></i>
                    Communication Équipe
                </div>
                
                <h1 class="display-4 fw-black mb-4">
                    Chat interne<br>100% pro
                </h1>
                
                <p class="fs-5 mb-4 opacity-90">
                    Messagerie instantanée pour votre équipe. Fini WhatsApp personnel, 
                    tout est centralisé dans SERVO.
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                    <a href="/inscription" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Essai gratuit 30 jours
                    </a>
                    <a href="#demo-messaging" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-message me-2"></i>
                        Voir la démo
                    </a>
                </div>
                
                <div class="d-flex flex-wrap gap-4 text-white-50">
                    <div><i class="fa-solid fa-lock me-2"></i>Sécurisé et privé</div>
                    <div><i class="fa-solid fa-history me-2"></i>Historique complet</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card-modern p-3 bg-white text-dark">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <div class="position-relative">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">M</div>
                            <div class="position-absolute bottom-0 end-0 bg-success rounded-circle" style="width: 12px; height: 12px; border: 2px solid white;"></div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">Marc (Technicien)</div>
                            <small class="text-success">En ligne</small>
                        </div>
                    </div>
                    
                    <div style="max-height: 300px; overflow-y: auto;" class="mb-3">
                        <div class="mb-3">
                            <div class="bg-primary bg-opacity-10 rounded p-2 me-5">
                                <small class="text-muted d-block mb-1">Marc • 10:23</small>
                                <div>J'ai besoin d'un écran iPhone 14 Pro pour la réparation #1234</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="bg-success bg-opacity-10 rounded p-2 ms-5">
                                <small class="text-muted d-block mb-1">Vous • 10:24</small>
                                <div>✅ OK, je commande ça tout de suite chez Mobilax</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="bg-primary bg-opacity-10 rounded p-2 me-5">
                                <small class="text-muted d-block mb-1">Marc • 10:25</small>
                                <div>Merci ! C'est urgent, le client vient demain</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Votre message..." disabled>
                        <button class="btn btn-primary" disabled>
                            <i class="fa-solid fa-paper-plane"></i>
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
            <div class="col-lg-3">
                <div class="h2 fw-black text-primary mb-1">Instantané</div>
                <div class="text-muted">Réponse en < 1 min</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-success mb-1">-60%</div>
                <div class="text-muted">Appels interrompus</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-warning mb-1">100%</div>
                <div class="text-muted">Historique sauvegardé</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-info mb-1">Notifications</div>
                <div class="text-muted">Desktop & Mobile</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO -->
<section class="section" id="demo-messaging">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Interface de messagerie</h2>
            <p class="text-muted">Simple et efficace</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card-modern p-3">
                            <h6 class="fw-bold mb-3">Conversations</h6>
                            
                            <div class="d-flex align-items-center gap-2 p-2 bg-primary bg-opacity-10 rounded mb-2">
                                <div class="bg-success rounded-circle text-white fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.75rem;">M</div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-semibold small">Marc</div>
                                    <div class="text-muted small text-truncate">J'ai besoin d'un écran...</div>
                                </div>
                                <span class="badge bg-primary rounded-circle">3</span>
                            </div>
                            
                            <div class="d-flex align-items-center gap-2 p-2 rounded mb-2">
                                <div class="bg-warning rounded-circle text-white fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.75rem;">S</div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-semibold small">Sophie</div>
                                    <div class="text-muted small text-truncate">OK merci pour l'info</div>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center gap-2 p-2 rounded">
                                <div class="bg-info rounded-circle text-white fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.75rem;">T</div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-semibold small">Thomas</div>
                                    <div class="text-muted small text-truncate">À plus tard</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card-modern p-3">
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-message fs-1 mb-3 opacity-25"></i>
                                <p>Sélectionnez une conversation<br>pour commencer</p>
                            </div>
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
        <div class="row g-4">
            <div class="col-lg-4 text-center">
                <i class="fa-solid fa-shield-halved text-primary fs-1 mb-3 d-block"></i>
                <h5 class="fw-bold mb-2">Données sécurisées</h5>
                <p class="text-muted">Conversations chiffrées, jamais sur WhatsApp personnel</p>
            </div>
            <div class="col-lg-4 text-center">
                <i class="fa-solid fa-search text-success fs-1 mb-3 d-block"></i>
                <h5 class="fw-bold mb-2">Recherche puissante</h5>
                <p class="text-muted">Trouvez n'importe quel message en 1 seconde</p>
            </div>
            <div class="col-lg-4 text-center">
                <i class="fa-solid fa-mobile-screen text-warning fs-1 mb-3 d-block"></i>
                <h5 class="fw-bold mb-2">Multi-plateforme</h5>
                <p class="text-muted">Desktop, mobile, tablette : partout</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <h2 class="fw-black mb-4">Votre équipe connectée en 1 clic</h2>
        <a href="/inscription" class="btn btn-primary btn-lg bg-white text-primary">
            <i class="fa-solid fa-rocket me-2"></i>
            Essayer maintenant
        </a>
    </div>
</section>
