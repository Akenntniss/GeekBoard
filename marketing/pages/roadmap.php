<?php include 'marketing/shared/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden pt-5 pb-5">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: -1;">
        <div class="position-absolute bottom-0 end-0 bg-accent opacity-10 rounded-circle blur-3xl" style="width: 700px; height: 700px; filter: blur(120px); transform: translate(20%, 20%);"></div>
    </div>

    <div class="container position-relative pt-5 mt-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="badge bg-accent bg-opacity-20 text-accent border border-accent border-opacity-20 rounded-pill px-3 py-2 mb-4 fade-in-up">
                    <i class="fa-solid fa-map me-2"></i>Feuille de route publique
                </div>
                <h1 class="display-3 fw-bold mb-4 fade-in-up delay-1">Roadmap Produit</h1>
                <p class="fs-5 text-muted mb-0 fade-in-up delay-2">
                    Voici ce sur quoi nous travaillons. Aidez-nous à construire le meilleur logiciel pour réparateurs en votant pour les fonctionnalités que vous attendez.
                </p>
                <div class="mt-4 pt-2 fade-in-up delay-3">
                     <a href="#submit-idea" class="btn btn-primary rounded-pill px-4 py-2 fw-bold">
                         <i class="fa-regular fa-lightbulb me-2"></i>Proposer une idée
                     </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kanban Roadmap -->
<section class="section pt-0">
    <div class="container-fluid px-lg-5">
        <div class="row g-4">
            
            <!-- Column: En cours (Doing) -->
            <div class="col-lg-4 fade-in-up">
                <div class="d-flex align-items-center mb-4 pb-2 border-bottom border-light">
                    <div class="spinner-grow text-primary spinner-grow-sm me-2" role="status"></div>
                    <h4 class="fw-bold mb-0">En Développement</h4>
                    <span class="badge bg-light bg-opacity-10 ms-auto">Q1 2026</span>
                </div>

                <div class="d-flex flex-column gap-3">
                    <!-- Feature Card -->
                    <div class="card-glass p-4 bg-primary bg-opacity-5 border-primary border-opacity-20">
                        <div class="mb-3">
                             <span class="badge bg-primary">In Progress</span>
                             <span class="badge bg-dark ms-1">Planning</span>
                        </div>
                        <h5 class="fw-bold mb-2">Planification IA Avancée</h5>
                        <p class="text-muted small mb-3">
                            Algorithme prédictif pour l'assignation automatique des réparations aux techniciens selon leur charge et compétences.
                        </p>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light border-opacity-10">
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fa-solid fa-comments me-2"></i>12 com.
                            </div>
                            <div class="text-primary fw-bold small">
                                <i class="fa-solid fa-thumbs-up me-1"></i> 142 votes
                            </div>
                        </div>
                    </div>

                    <!-- Feature Card -->
                     <div class="card-glass p-4">
                        <div class="mb-3">
                             <span class="badge bg-primary">In Progress</span>
                             <span class="badge bg-dark ms-1">Mobile App</span>
                        </div>
                        <h5 class="fw-bold mb-2">App Compagnon Technicien (iOS/Android)</h5>
                        <p class="text-muted small mb-3">
                            Version native optimisée pour scanner les codes-barres avec la caméra et gérer les tâches hors-ligne.
                        </p>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light border-opacity-10">
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fa-solid fa-comments me-2"></i>45 com.
                            </div>
                            <div class="text-primary fw-bold small">
                                <i class="fa-solid fa-thumbs-up me-1"></i> 389 votes
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column: Planifié (Next) -->
            <div class="col-lg-4 fade-in-up delay-1">
                 <div class="d-flex align-items-center mb-4 pb-2 border-bottom border-light">
                    <div class="rounded-circle bg-accent me-2" style="width: 8px; height: 8px;"></div>
                    <h4 class="fw-bold mb-0">Planifié (Q2 2026)</h4>
                    <span class="badge bg-light bg-opacity-10 ms-auto">Bientôt</span>
                </div>

                 <div class="d-flex flex-column gap-3">
                    <!-- Feature Card -->
                     <div class="card-glass p-4">
                        <div class="mb-3">
                             <span class="badge bg-accent">Planned</span>
                             <span class="badge bg-dark ms-1">Marketplace</span>
                        </div>
                        <h5 class="fw-bold mb-2">Marketplace Pièces Détachées</h5>
                        <p class="text-muted small mb-3">
                            Plateforme d'échange de pièces entre réparateurs partenaires SERVO. Vendez vos stocks morts.
                        </p>
                         <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light border-opacity-10">
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fa-solid fa-comments me-2"></i>8 com.
                            </div>
                            <div class="text-primary fw-bold small">
                                <i class="fa-solid fa-thumbs-up me-1"></i> 89 votes
                            </div>
                        </div>
                    </div>
                     <!-- Feature Card -->
                     <div class="card-glass p-4">
                        <div class="mb-3">
                             <span class="badge bg-accent">Planned</span>
                             <span class="badge bg-dark ms-1">Integration</span>
                        </div>
                        <h5 class="fw-bold mb-2">Connecteur Shopify & WooCommerce</h5>
                        <p class="text-muted small mb-3">
                            Synchronisation bidirectionnelle des stocks et commandes pour ceux qui vendent aussi en ligne.
                        </p>
                         <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light border-opacity-10">
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fa-solid fa-comments me-2"></i>24 com.
                            </div>
                            <div class="text-primary fw-bold small">
                                <i class="fa-solid fa-thumbs-up me-1"></i> 112 votes
                            </div>
                        </div>
                    </div>
                 </div>
            </div>

            <!-- Column: Considéré (Backlog) -->
            <div class="col-lg-4 fade-in-up delay-2">
                 <div class="d-flex align-items-center mb-4 pb-2 border-bottom border-light">
                    <div class="rounded-circle bg-secondary me-2" style="width: 8px; height: 8px;"></div>
                    <h4 class="fw-bold mb-0">En Réflexion</h4>
                    <span class="badge bg-light bg-opacity-10 ms-auto">Besoin de votes</span>
                </div>

                 <div class="d-flex flex-column gap-3">
                    <!-- Feature Card -->
                     <div class="card-glass p-4 opacity-75">
                        <div class="mb-3">
                             <span class="badge bg-secondary">Under Review</span>
                        </div>
                        <h5 class="fw-bold mb-2">Module Revendeur (B2B)</h5>
                        <p class="text-muted small mb-3">
                            Portail dédié pour vos clients professionnels avec tarifs préférentiels et facturation fin de mois.
                        </p>
                         <button class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-2">
                             <i class="fa-solid fa-thumbs-up me-1"></i> Voter (45)
                         </button>
                    </div>

                    <!-- Feature Card -->
                     <div class="card-glass p-4 opacity-75">
                        <div class="mb-3">
                             <span class="badge bg-secondary">Under Review</span>
                        </div>
                        <h5 class="fw-bold mb-2">Support Multi-Devises</h5>
                        <p class="text-muted small mb-3">
                            Gestion native des encaissements en devises étrangères (CHF, USD, GBP, CFA).
                        </p>
                        <button class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-2">
                             <i class="fa-solid fa-thumbs-up me-1"></i> Voter (12)
                         </button>
                    </div>
                 </div>
            </div>

        </div>
    </div>
</section>

<!-- Suggestion Box -->
<section id="submit-idea" class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                 <div class="card-glass p-5">
                     <div class="text-center mb-4">
                         <h3 class="fw-bold">Vous avez une idée de génie ?</h3>
                         <p class="text-muted">La plupart de nos meilleures fonctionnalités viennent de nos utilisateurs. Dites-nous tout.</p>
                     </div>
                     <form>
                         <div class="mb-3">
                             <label class="form-label text-muted small fw-bold">TITRE DE LA FONCTIONNALITÉ</label>
                             <input type="text" class="form-control bg-dark border-light" placeholder="Ex: Intégration WhatsApp...">
                         </div>
                         <div class="mb-4">
                             <label class="form-label text-muted small fw-bold">DESCRIPTION</label>
                             <textarea class="form-control bg-dark border-light" rows="4" placeholder="Expliquez comment cela aiderait votre activité..."></textarea>
                         </div>
                         <div class="text-end">
                             <button type="button" class="btn btn-primary px-5 fw-bold rounded-pill">Soumettre l'idée</button>
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
    transition: transform 0.2s, background 0.2s;
}
.card-glass:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.05);
}
</style>

<?php include 'marketing/shared/footer.php'; ?>
