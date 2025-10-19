<!-- Contact Hero -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-primary bg-opacity-10 text-primary mb-3 px-3 py-2">
                <i class="fa-solid fa-calendar-days me-2"></i>
                Démo gratuite
            </div>
            <h1 class="display-4 fw-black mb-4">Planifiez votre démonstration</h1>
            <p class="fs-5 text-muted mb-0">
                15 minutes pour découvrir comment GeekBoard peut transformer votre atelier. 
                Démo personnalisée selon votre métier et vos besoins.
            </p>
        </div>
    </div>
</section>

<!-- Contact Form & Info -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="row g-5">
            
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="card-modern p-5">
                    <h4 class="fw-bold mb-4">Réservez votre créneau</h4>
                    
                    <form id="contact-form" onsubmit="submitForm(event)">
                        <div class="row g-3">
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Prénom *</label>
                                <input type="text" class="form-control" name="firstName" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nom *</label>
                                <input type="text" class="form-control" name="lastName" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email professionnel *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Téléphone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nom de votre atelier *</label>
                                <input type="text" class="form-control" name="company" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre d'employés</label>
                                <select class="form-select" name="employees">
                                    <option value="">Choisir</option>
                                    <option value="1-3">1 à 3 employés</option>
                                    <option value="4-10">4 à 10 employés</option>
                                    <option value="11-25">11 à 25 employés</option>
                                    <option value="25+">Plus de 25 employés</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Réparations/mois</label>
                                <select class="form-select" name="repairs">
                                    <option value="">Estimation</option>
                                    <option value="0-100">Moins de 100</option>
                                    <option value="100-300">100 à 300</option>
                                    <option value="300-600">300 à 600</option>
                                    <option value="600+">Plus de 600</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-semibold">Quel est votre besoin prioritaire ?</label>
                                <select class="form-select" name="subject">
                                    <option value="Démo générale">Démo générale de GeekBoard</option>
                                    <option value="SMS automatiques">Focus : SMS automatiques</option>
                                    <option value="Gestion stock">Focus : Gestion de stock</option>
                                    <option value="Multi-boutiques">Focus : Multi-boutiques</option>
                                    <option value="Migration">Migration depuis autre logiciel</option>
                                    <option value="Tarifs">Discussion tarifs & ROI</option>
                                    <option value="Autre">Autre besoin</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-semibold">Message (optionnel)</label>
                                <textarea class="form-control" rows="4" name="message" placeholder="Décrivez vos besoins spécifiques, contraintes actuelles, questions..."></textarea>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="consent" required>
                                    <label class="form-check-label text-muted small" for="consent">
                                        J'accepte d'être contacté par GeekBoard pour ma démonstration. 
                                        <a href="/privacy" class="text-decoration-none">Politique de confidentialité</a>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fa-solid fa-calendar-plus me-2"></i>
                                    Planifier ma démo gratuite
                                </button>
                                <small class="text-muted d-block text-center mt-2">
                                    Réponse sous 2h • Créneau flexible • Démo personnalisée
                                </small>
                            </div>
                            
                        </div>
                    </form>
                    
                    <!-- Success Message -->
                    <div id="success-message" class="alert alert-success d-none mt-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-check-circle fs-4"></i>
                            <div>
                                <h6 class="mb-1">🎉 Demande envoyée avec succès !</h6>
                                <p class="mb-0">Nous vous contactons sous 2h pour fixer votre créneau de démo.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Error Message -->
                    <div id="error-message" class="alert alert-danger d-none mt-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-exclamation-triangle fs-4"></i>
                            <div>
                                <h6 class="mb-1">Erreur d'envoi</h6>
                                <p class="mb-0">Problème technique. Contactez-nous directement au 08 95 79 59 33</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info & Benefits -->
            <div class="col-lg-5">
                
                <!-- Contact Info -->
                <div class="card-feature p-4 mb-4">
                    <h5 class="fw-bold mb-3">Nous contacter directement</h5>
                    
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">08 95 79 59 33</div>
                            <small class="text-muted">SERVO By Maison Du Geek</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">servo@maisondugeek.fr</div>
                            <small class="text-muted">Réponse sous 2h</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Support technique</div>
                            <small class="text-muted">Formation et assistance incluses</small>
                        </div>
                    </div>
                </div>
                
                <!-- Demo Benefits -->
                <div class="card-feature p-4 mb-4">
                    <h5 class="fw-bold mb-3">Ce que vous allez voir</h5>
                    
                    <ul class="list-unstyled">
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>Interface en conditions réelles</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>Calcul ROI pour votre atelier</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>Scénarios de votre quotidien</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>Q&A avec un expert métier</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>Plan d'implémentation personnalisé</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Trust Signals -->
                <div class="card-feature p-4 bg-gradient-primary text-white">
                    <h5 class="fw-bold mb-3">Pourquoi nous faire confiance ?</h5>
                    
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="h5 fw-black">250+</div>
                            <small class="opacity-90">Ateliers clients</small>
                        </div>
                        <div class="col-6">
                            <div class="h5 fw-black">4.8/5</div>
                            <small class="opacity-90">Satisfaction</small>
                        </div>
                        <div class="col-6">
                            <div class="h5 fw-black">48h</div>
                            <small class="opacity-90">Installation</small>
                        </div>
                        <div class="col-6">
                            <div class="h5 fw-black">99.9%</div>
                            <small class="opacity-90">Disponibilité</small>
                        </div>
                    </div>
                    
                    <hr class="my-3 opacity-25">
                    
                    <div class="text-center">
                        <small class="opacity-90">
                            <i class="fa-solid fa-shield-halved me-2"></i>
                            Données sécurisées • Support français • RGPD
                        </small>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Questions fréquentes</h2>
            <p class="text-muted">Tout ce que vous voulez savoir avant la démo</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="demoFAQ">
                    
                    <div class="accordion-item border-0 mb-3">
                        <h6 class="accordion-header">
                            <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#demo-faq1">
                                Combien de temps dure la démonstration ?
                            </button>
                        </h6>
                        <div id="demo-faq1" class="accordion-collapse collapse show" data-bs-parent="#demoFAQ">
                            <div class="accordion-body text-muted">
                                15 à 30 minutes selon vos questions. Nous nous adaptons à votre emploi du temps 
                                et couvrons vos besoins prioritaires.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#demo-faq2">
                                Faut-il préparer quelque chose ?
                            </button>
                        </h6>
                        <div id="demo-faq2" class="accordion-collapse collapse" data-bs-parent="#demoFAQ">
                            <div class="accordion-body text-muted">
                                Rien à préparer ! Ayez juste vos questions en tête : nombre de réparations/mois, 
                                besoins spécifiques, contraintes actuelles.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#demo-faq3">
                                La démo est-elle vraiment gratuite ?
                            </button>
                        </h6>
                        <div id="demo-faq3" class="accordion-collapse collapse" data-bs-parent="#demoFAQ">
                            <div class="accordion-body text-muted">
                                Absolument gratuite, sans engagement. Notre objectif : vous montrer la valeur 
                                de GeekBoard pour votre atelier spécifiquement.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#demo-faq4">
                                Proposez-vous un essai gratuit ?
                            </button>
                        </h6>
                        <div id="demo-faq4" class="accordion-collapse collapse" data-bs-parent="#demoFAQ">
                            <div class="accordion-body text-muted">
                                Oui ! 14 jours d'essai complet avec installation, formation et support. 
                                Aucune carte bancaire requise.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#demo-faq5">
                                Et si je suis déjà client d'un concurrent ?
                            </button>
                        </h6>
                        <div id="demo-faq5" class="accordion-collapse collapse" data-bs-parent="#demoFAQ">
                            <div class="accordion-body text-muted">
                                Parfait ! Nous vous montrerons les différences concrètes et comment migrer 
                                vos données sans interruption de service.
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function submitForm(event) {
    event.preventDefault();
    
    const form = document.getElementById('contact-form');
    const formData = new FormData(form);
    const button = form.querySelector('button[type="submit"]');
    const successMsg = document.getElementById('success-message');
    const errorMsg = document.getElementById('error-message');
    
    // Hide previous messages
    successMsg.classList.add('d-none');
    errorMsg.classList.add('d-none');
    
    // Update button state
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Envoi en cours...';
    
    // Submit form
    fetch('/contact_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successMsg.classList.remove('d-none');
            form.reset();
        } else {
            errorMsg.classList.remove('d-none');
        }
    })
    .catch(error => {
        errorMsg.classList.remove('d-none');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-calendar-plus me-2"></i>Planifier ma démo gratuite';
    });
}
</script>