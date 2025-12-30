<?php include 'marketing/shared/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden pt-5 pb-5">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: -1;">
        <div class="position-absolute bottom-0 end-0 bg-accent opacity-10 rounded-circle blur-3xl" style="width: 800px; height: 800px; filter: blur(120px); transform: translate(30%, 30%);"></div>
    </div>

    <div class="container position-relative pt-5 mt-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="badge bg-accent bg-opacity-20 text-accent border border-accent border-opacity-20 rounded-pill px-3 py-2 mb-4 fade-in-up">
                    <i class="fa-solid fa-building me-2"></i>SERVO Enterprise
                </div>
                <h1 class="display-3 fw-bold mb-4 fade-in-up delay-1">Passez à l'échelle supérieure</h1>
                <p class="fs-5 text-muted mb-4 fade-in-up delay-2">
                    Franchises, chaînes de magasins, centres de services agrées. Une infrastructure dédiée pour piloter votre réseau sans limites.
                </p>
                <div class="d-flex gap-3 fade-in-up delay-3">
                    <a href="#contact" class="btn btn-primary btn-lg rounded-pill fw-bold px-5">Contacter l'équipe Sales</a>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0 fade-in-up delay-2 text-center">
                <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2670&auto=format&fit=crop" class="img-fluid rounded-4 shadow-lg border border-light border-opacity-10" alt="Enterprise Building">
            </div>
        </div>
    </div>
</section>

<!-- Features Grid -->
<section class="section pt-0">
    <div class="container">
        <div class="row g-4">
            
            <div class="col-md-4 fade-in-up">
                <div class="card-glass p-4 h-100">
                    <h3 class="fw-bold mb-3 icon-gradient"><i class="fa-solid fa-network-wired me-2"></i>Multi-Sites Unifié</h3>
                    <p class="text-muted">Gérez 10, 50 ou 200 boutiques depuis un seul tableau de bord "Master". Vue globale sur le CA, les stocks et les KPIs.</p>
                </div>
            </div>
            
            <div class="col-md-4 fade-in-up delay-1">
                <div class="card-glass p-4 h-100">
                    <h3 class="fw-bold mb-3 icon-gradient"><i class="fa-solid fa-fingerprint me-2"></i>SSO & SAML</h3>
                    <p class="text-muted">Connectez SERVO à votre fournisseur d'identité d'entreprise (Okta, Azure AD, Google Workspace) pour une sécurité maximale.</p>
                </div>
            </div>
            
            <div class="col-md-4 fade-in-up delay-2">
                <div class="card-glass p-4 h-100">
                    <h3 class="fw-bold mb-3 icon-gradient"><i class="fa-solid fa-headset me-2"></i>Support Dédié</h3>
                    <p class="text-muted">Un Account Manager vous est attribué. SLA garanti avec temps de réponse < 1h et ligne téléphonique prioritaire.</p>
                </div>
            </div>

            <div class="col-md-4 fade-in-up delay-3">
                <div class="card-glass p-4 h-100">
                    <h3 class="fw-bold mb-3 icon-gradient"><i class="fa-solid fa-database me-2"></i>API Illimitée</h3>
                    <p class="text-muted">Rate limits augmentés (10k req/min) et accès aux endpoints avancés pour connecter votre ERP ou SAP.</p>
                </div>
            </div>

            <div class="col-md-4 fade-in-up delay-4">
                <div class="card-glass p-4 h-100">
                    <h3 class="fw-bold mb-3 icon-gradient"><i class="fa-solid fa-server me-2"></i>Instance Privée</h3>
                    <p class="text-muted">Option de déploiement sur une instance isolée (Single Tenant) pour des besoins de conformité ou de performance extrêmes.</p>
                </div>
            </div>

            <div class="col-md-4 fade-in-up delay-5">
                <div class="card-glass p-4 h-100">
                    <h3 class="fw-bold mb-3 icon-gradient"><i class="fa-solid fa-file-invoice me-2"></i>Facturation Flex</h3>
                    <p class="text-muted">Paiement sur facture annuelle avec délais de paiement à 45 jours. Tarifs dégressifs au volume d'utilisateurs.</p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Contact Form -->
<section id="contact" class="section pt-0">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-glass p-5">
                    <h2 class="fw-bold text-center mb-4">Parlons de votre projet</h2>
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">NOM COMPLET</label>
                                <input type="text" class="form-control bg-dark border-light text-white" placeholder="John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">EMAIL PROFESSIONNEL</label>
                                <input type="email" class="form-control bg-dark border-light text-white" placeholder="john@company.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted small fw-bold">ENTREPRISE</label>
                                <input type="text" class="form-control bg-dark border-light text-white" placeholder="Nom de votre société">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted small fw-bold">TAILLE DU RÉSEAU</label>
                                <select class="form-select bg-dark border-light text-white">
                                    <option>5 - 10 Boutiques</option>
                                    <option>10 - 50 Boutiques</option>
                                    <option>50+ Boutiques</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted small fw-bold">MESSAGE</label>
                                <textarea class="form-control bg-dark border-light text-white" rows="4" placeholder="Décrivez vos besoins..."></textarea>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="button" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold w-100">Demander une démo Enterprise</button>
                            </div>
                        </div>
                    </form>
                </div>
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
.icon-gradient i {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>

<?php include 'marketing/shared/footer.php'; ?>
