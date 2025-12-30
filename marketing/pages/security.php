<?php include 'marketing/shared/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden pt-5 pb-5">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: -1;">
        <div class="position-absolute top-50 start-50 bg-success opacity-10 rounded-circle blur-3xl text-center" style="width: 600px; height: 600px; filter: blur(120px); transform: translate(-50%, -50%);"></div>
    </div>

    <div class="container position-relative pt-5 mt-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-20 rounded-pill px-3 py-2 mb-4 fade-in-up">
                    <i class="fa-solid fa-shield-halved me-2"></i>Sécurité & Conformité
                </div>
                <h1 class="display-3 fw-bold mb-4 fade-in-up delay-1">Vos données sont en sécurité.</h1>
                <p class="fs-5 text-muted mb-0 fade-in-up delay-2">
                    La confiance est la base de notre métier. Découvrez comment SERVO protège votre activité avec des standards de sécurité de niveau bancaire.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Security Grid -->
<section class="section pt-0">
    <div class="container">
        <div class="row g-4">
            
            <!-- Encryption -->
            <div class="col-md-6 col-lg-4 fade-in-up">
                <div class="card-glass p-4 h-100">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle mb-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-lock fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Chiffrement AES-256</h4>
                    <p class="text-muted small">Toutes vos données sensibles sont chiffrées au repos (AES-256) et en transit (TLS 1.3). Personne, pas même nos employés, ne peut lire vos mots de passe.</p>
                </div>
            </div>

            <!-- GDPR -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-1">
                <div class="card-glass p-4 h-100">
                    <div class="icon-box bg-secondary bg-opacity-10 text-secondary rounded-circle mb-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-user-shield fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Conformité RGPD</h4>
                    <p class="text-muted small">SERVO est entièrement conforme au RGPD. Vous gardez le contrôle total sur vos données clients (Droit à l'oubli, Exportabilité).</p>
                </div>
            </div>

            <!-- Backups -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-2">
                <div class="card-glass p-4 h-100">
                    <div class="icon-box bg-info bg-opacity-10 text-info rounded-circle mb-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-cloud-arrow-up fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Sauvegardes Horaires</h4>
                    <p class="text-muted small">Même en cas de catastrophe majeure, vous ne perdez rien. Sauvegardes incrémentales toutes les heures vers 3 datacenters distincts.</p>
                </div>
            </div>

            <!-- Infrastructure -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-3">
                <div class="card-glass p-4 h-100">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle mb-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-server fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Hébergement en France</h4>
                    <p class="text-muted small">Nos serveurs sont situés à Paris et Gravelines (OVHcloud & Scaleway), garantissant la souveraineté de vos données.</p>
                </div>
            </div>

            <!-- Access Control -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-4">
                <div class="card-glass p-4 h-100">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger rounded-circle mb-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-id-card fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Contrôle d'Accès Granulaire</h4>
                    <p class="text-muted small">Définissez précisément qui peut voir quoi. Techniciens, Vendeurs, Comptables... chacun a ses permissions strictes.</p>
                </div>
            </div>

            <!-- Pentesting -->
            <div class="col-md-6 col-lg-4 fade-in-up delay-5">
                <div class="card-glass p-4 h-100">
                    <div class="icon-box bg-success bg-opacity-10 text-success rounded-circle mb-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-bug-slash fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Audits de Sécurité</h4>
                    <p class="text-muted small">Nous effectuons des tests d'intrusion trimestriels avec des cabinets indépendants pour garantir l'étanchéité de notre code.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Compliance Badges -->
<section class="section pt-0 pb-5">
    <div class="container">
        <div class="row justify-content-center text-center opacity-50 grayscale hover-grayscale-0 transition-all">
            <div class="col-6 col-md-3 mb-4">
                <h2 class="fw-bold text-white mb-0">GDPR</h2>
                <span class="text-muted small">Compliant</span>
            </div>
            <div class="col-6 col-md-3 mb-4">
                <h2 class="fw-bold text-white mb-0">ISO</h2>
                <span class="text-muted small">27001 Ready</span>
            </div>
            <div class="col-6 col-md-3 mb-4">
                <h2 class="fw-bold text-white mb-0">SOC 2</h2>
                <span class="text-muted small">Type II</span>
            </div>
            <div class="col-6 col-md-3 mb-4">
                <h2 class="fw-bold text-white mb-0">PCI</h2>
                <span class="text-muted small">DSS Compliant</span>
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
    transition: transform 0.3s ease;
}
.card-glass:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.05);
}
.grayscale { filter: grayscale(100%); }
.hover-grayscale-0:hover { filter: grayscale(0%); }
</style>

<?php include 'marketing/shared/footer.php'; ?>
