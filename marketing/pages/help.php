<?php include 'marketing/shared/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden pt-5 pb-5">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: -1;">
        <div class="position-absolute top-0 start-50 bg-primary opacity-20 rounded-circle blur-3xl" style="width: 600px; height: 600px; filter: blur(100px); transform: translate(-50%, -30%);"></div>
    </div>

    <div class="container position-relative pt-5 mt-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-3 fw-bold mb-4 fade-in-up">Comment pouvons-nous vous aider ?</h1>
                
                <!-- Search Box -->
                <div class="position-relative max-w-2xl mx-auto mb-5 fade-in-up delay-1">
                    <input type="text" class="form-control form-control-lg bg-dark border-light rounded-pill py-3 px-5 text-white" placeholder="Rechercher un article, une erreur...">
                    <i class="fa-solid fa-search position-absolute top-50 start-0 translate-middle-y ms-4 text-muted"></i>
                </div>

                <div class="d-flex justify-content-center gap-3 fade-in-up delay-2">
                    <span class="text-muted">Recherches fréquentes :</span>
                    <a href="#" class="text-primary text-decoration-none">Impression ticket</a>
                    <a href="#" class="text-primary text-decoration-none">Import Excel</a>
                    <a href="#" class="text-primary text-decoration-none">Configurer SMTP</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Grid -->
<section class="section pt-0">
    <div class="container">
        <div class="row g-4 justify-content-center">
            
            <!-- Category 1 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-1">
                <div class="card-glass p-4 h-100 text-center hover-up transition-all cursor-pointer">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-rocket fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Premiers Pas</h4>
                    <p class="text-muted small mb-0">Créer un compte, configurer votre atelier et importer vos premiers clients.</p>
                </div>
            </div>

            <!-- Category 2 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-2">
                <div class="card-glass p-4 h-100 text-center hover-up transition-all cursor-pointer">
                    <div class="icon-box bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-screwdriver-wrench fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Gestion des Réparations</h4>
                    <p class="text-muted small mb-0">Créer une fiche, suivre les status, gérer les pièces détachées et la main d'œuvre.</p>
                </div>
            </div>

            <!-- Category 3 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-3">
                <div class="card-glass p-4 h-100 text-center hover-up transition-all cursor-pointer">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-file-invoice-dollar fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Facturation & Caisse</h4>
                    <p class="text-muted small mb-0">Émettre des devis, factures, gérer la TVA et les clôtures de caisse journalières.</p>
                </div>
            </div>

            <!-- Category 4 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-4">
                <div class="card-glass p-4 h-100 text-center hover-up transition-all cursor-pointer">
                    <div class="icon-box bg-info bg-opacity-10 text-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fa-regular fa-comment-dots fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-2">SMS & Communication</h4>
                    <p class="text-muted small mb-0">Configurer les modèles de SMS, les emails automatiques et les rappels.</p>
                </div>
            </div>

            <!-- Category 5 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-5">
                <div class="card-glass p-4 h-100 text-center hover-up transition-all cursor-pointer">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-bug fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Dépannage</h4>
                    <p class="text-muted small mb-0">Résoudre les problèmes d'impression, erreurs de connexion ou bugs divers.</p>
                </div>
            </div>

             <!-- Category 6 -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-6">
                <div class="card-glass p-4 h-100 text-center hover-up transition-all cursor-pointer">
                    <div class="icon-box bg-secondary bg-opacity-10 text-secondary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-code fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-2">API & Intégrations</h4>
                    <p class="text-muted small mb-0">Documentation technique pour connecter SERVO à vos autres outils.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section pt-0">
    <div class="container">
        <h3 class="fw-bold mb-4 text-center">Questions Fréquentes</h3>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion accordion-flush" id="faqAccordion">
                    
                    <div class="accordion-item bg-transparent mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-dark bg-opacity-50 text-white rounded-3 border border-light border-opacity-10" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Comment importer mes anciens clients ?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Vous pouvez importer vos clients via un fichier CSV ou Excel depuis la page "Clients > Import". Un modèle est disponible au téléchargement pour respecter le format.
                            </div>
                        </div>
                    </div>

                     <div class="accordion-item bg-transparent mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-dark bg-opacity-50 text-white rounded-3 border border-light border-opacity-10" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Est-ce que le logiciel fonctionne sans internet ?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                SERVO est une application web qui nécessite une connexion internet pour synchroniser les données. Cependant, le mode hors-ligne (PWA) permet de consulter les fiches et créer des tickets sans connexion immédiate.
                            </div>
                        </div>
                    </div>

                     <div class="accordion-item bg-transparent mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-dark bg-opacity-50 text-white rounded-3 border border-light border-opacity-10" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Comment configurer mon imprimante ticket ?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                SERVO utilise le pilote d'impression natif de votre navigateur. Installez simplement votre imprimante sur votre OS (Windows/Mac), définissez la taille de papier sur 80mm ou 58mm, et vous êtes prêt.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Support -->
<section class="section text-center pt-0">
    <div class="container">
        <div class="card-glass p-5">
            <h3 class="fw-bold mb-3">Vous ne trouvez pas votre réponse ?</h3>
            <p class="text-muted mb-4">Notre équipe de support est disponible du Lundi au Samedi de 9h à 19h.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="mailto:support@servo.tools" class="btn btn-primary px-4 fw-bold">
                    <i class="fa-regular fa-envelope me-2"></i>Envoyer un email
                </a>
                <a href="#" class="btn btn-outline-light px-4">
                    <i class="fa-brands fa-whatsapp me-2"></i>WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

<style>
.card-glass {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
}
.hover-up:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.08);
}
.accordion-button:not(.collapsed) {
    background-color: rgba(6, 182, 212, 0.1);
    color: var(--primary);
    box-shadow: none;
}
.accordion-button:focus {
    box-shadow: none;
    border-color: var(--primary);
}
</style>

<?php include 'marketing/shared/footer.php'; ?>
