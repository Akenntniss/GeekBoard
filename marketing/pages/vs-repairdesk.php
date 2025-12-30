<?php
/**
 * Page Comparatif : SERVO vs RepairDesk
 * Objectif : Convaincre les utilisateurs de RepairDesk de changer
 */
?>

<style>
/* Page Specific Styles */
.comparison-table .glass-card {
    transition: 0.3s;
}
.comparison-table .glass-card:hover {
    background: rgba(6, 182, 212, 0.05);
}
.vs-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    background: #000;
    border: 1px solid var(--primary);
    color: var(--primary);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 900;
    box-shadow: 0 0 20px var(--primary-glow);
}
</style>

<!-- Hero Section -->
<section class="position-relative pt-5 pb-5 overflow-hidden">
    <!-- Background Effects -->
    <div class="position-absolute top-0 start-50 translate-middle-x bg-primary opacity-10 rounded-circle" style="width: 800px; height: 800px; filter: blur(100px); z-index: -1;"></div>

    <div class="container pt-5 text-center">
        <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-4 py-2 mb-4">
            <i class="fa-solid fa-scale-balanced me-2"></i> ALTERNATIVE #1 À REPAIRDESK
        </div>
        
        <h1 class="display-3 fw-black text-white mb-4">
            Pourquoi payer plus pour<br>
            <span class="text-secondary text-decoration-line-through opacity-50">une usine à gaz</span>
            <span class="text-gradient">le futur ?</span>
        </h1>
        
        <p class="fs-5 text-secondary mb-5 mx-auto" style="max-width: 700px;">
            RepairDesk était la référence. SERVO est la révolution.
            <br>Découvrez pourquoi 150+ ateliers ont migré cette année.
        </p>
        
        <div class="d-flex justify-content-center gap-3">
            <a href="/inscription" class="btn btn-glow btn-lg px-5 rounded-pill">
                MIGRER MAINTENANT
            </a>
        </div>
    </div>
</section>

<!-- Comparison Cards -->
<section class="section">
    <div class="container">
        <div class="row g-0 align-items-center position-relative">
            <!-- VS Badge Middle -->
            <div class="vs-badge display-6">VS</div>

            <!-- RepairDesk Side -->
            <div class="col-lg-6 pe-lg-5 mb-5 mb-lg-0 opacity-75">
                <div class="glass-card p-5 rounded-4 border-end-0 grayscale" style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; transform: scale(0.95);">
                    <h3 class="fw-bold text-muted mb-4">RepairDesk</h3>
                    <ul class="list-unstyled d-flex flex-column gap-3 text-secondary">
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-xmark text-danger me-3"></i> Interface datée (2015)</li>
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-xmark text-danger me-3"></i> Très complexe à configurer</li>
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-xmark text-danger me-3"></i> Support lent en Français</li>
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-xmark text-danger me-3"></i> Prix : 99€/mois par boutique</li>
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-xmark text-danger me-3"></i> Pas d'IA intégrée</li>
                    </ul>
                </div>
            </div>

            <!-- SERVO Side -->
            <div class="col-lg-6 ps-lg-5 position-relative z-1">
                <div class="glass-card p-5 rounded-4 border border-primary position-relative" style="background: rgba(6, 182, 212, 0.05); box-shadow: 0 0 40px rgba(6, 182, 212, 0.1);">
                    <div class="position-absolute top-0 end-0 m-3">
                        <i class="fa-solid fa-trophy text-warning fs-3 animate-pulse"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-4">SERVO</h3>
                    <ul class="list-unstyled d-flex flex-column gap-3 text-white">
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-check text-success me-3"></i> Interface Futuriste & Fluide</li>
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-check text-success me-3"></i> Setup en 5 minutes chrono</li>
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-check text-success me-3"></i> Support Français Réactif 🇫🇷</li>
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-check text-success me-3"></i> Prix : 49€/mois (Tout illimité)</li>
                        <li class="d-flex align-items-center"><i class="fa-solid fa-circle-check text-success me-3"></i> IA Générative Intégrée 🧠</li>
                    </ul>
                    <div class="mt-4 pt-4 border-top border-white border-opacity-10">
                        <a href="/inscription" class="btn btn-primary w-100 fw-bold">
                            JE PASSE CHEZ SERVO
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Detailed Comparison -->
<section class="section py-5">
    <div class="container">
        <h2 class="text-center text-white fw-black mb-5">Le Duel des Fonctionnalités</h2>
        
        <div class="table-responsive">
            <table class="table text-white comparison-table align-middle">
                <thead>
                    <tr class="text-center border-0">
                        <th class="text-start ps-4 text-secondary text-uppercase small py-3" style="width: 40%">Fonctionnalité</th>
                        <th class="text-muted py-3" style="width: 30%">RepairDesk</th>
                        <th class="text-primary fw-bold py-3 bg-primary bg-opacity-10 rounded-top" style="width: 30%">SERVO</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <!-- Row 1 -->
                    <tr>
                        <td class="ps-4 py-3 border-secondary border-opacity-10">
                            <strong>Interface Utilisateur</strong>
                            <div class="small text-secondary">Facilité d'apprentissage</div>
                        </td>
                        <td class="text-center border-secondary border-opacity-10 text-muted">Complexe</td>
                        <td class="text-center border-secondary border-opacity-10 bg-primary bg-opacity-5 fw-bold text-white">Intuitive (Gen-Z Ready)</td>
                    </tr>
                    <!-- Row 2 -->
                    <tr>
                        <td class="ps-4 py-3 border-secondary border-opacity-10">
                            <strong>Marketing SMS</strong>
                            <div class="small text-secondary">Campagnes & Automations</div>
                        </td>
                        <td class="text-center border-secondary border-opacity-10 text-muted">Payant (Add-on)</td>
                        <td class="text-center border-secondary border-opacity-10 bg-primary bg-opacity-5 fw-bold text-success">Inclus (Illimité)</td>
                    </tr>
                    <!-- Row 3 -->
                    <tr>
                        <td class="ps-4 py-3 border-secondary border-opacity-10">
                            <strong>Intelligence Artificielle</strong>
                            <div class="small text-secondary">Aide technique & rédaction</div>
                        </td>
                        <td class="text-center border-secondary border-opacity-10 text-muted"><i class="fa-solid fa-times text-danger"></i></td>
                        <td class="text-center border-secondary border-opacity-10 bg-primary bg-opacity-5 fw-bold text-success"><i class="fa-solid fa-check text-success"></i> Native</td>
                    </tr>
                     <!-- Row 4 -->
                     <tr>
                        <td class="ps-4 py-3 border-secondary border-opacity-10">
                            <strong>Catalogue Fournisseurs</strong>
                            <div class="small text-secondary">Intégrations natives</div>
                        </td>
                        <td class="text-center border-secondary border-opacity-10 text-muted">Limité</td>
                        <td class="text-center border-secondary border-opacity-10 bg-primary bg-opacity-5 fw-bold text-white">10+ Connecteurs</td>
                    </tr>
                     <!-- Row 5 -->
                     <tr>
                        <td class="ps-4 py-3 border-secondary border-opacity-10">
                            <strong>Tarification</strong>
                            <div class="small text-secondary">Coût mensuel pour 1 boutique</div>
                        </td>
                        <td class="text-center border-secondary border-opacity-10 text-danger text-decoration-line-through">99€ / mois</td>
                        <td class="text-center border-secondary border-opacity-10 bg-primary bg-opacity-5 fw-bold text-primary fs-5">49€ / mois</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Migration Help -->
<section class="py-5 bg-dark bg-opacity-50">
    <div class="container text-center">
        <h2 class="fw-black text-white mb-4">Peur de la migration ?</h2>
        <p class="text-secondary fs-5 mb-5 mx-auto" style="max-width: 600px;">
            Notre équipe migre vos données RepairDesk (Clients, Stocks, Tickets) gratuitement en moins de 24h.
        </p>
        
        <div class="row g-4 justify-content-center">
            <div class="col-lg-3 col-md-6">
                <div class="glass-card p-4 rounded-4">
                    <i class="fa-solid fa-file-export fs-1 text-primary mb-3"></i>
                    <h5 class="text-white">Export RepairDesk</h5>
                    <p class="small text-muted mb-0">Nous vous guidons pour l'export</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="glass-card p-4 rounded-4">
                    <i class="fa-solid fa-magic fs-1 text-secondary mb-3"></i>
                    <h5 class="text-white">Import Auto</h5>
                    <p class="small text-muted mb-0">Notre script mouline tout ça</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="glass-card p-4 rounded-4">
                    <i class="fa-solid fa-champagne-glasses fs-1 text-success mb-3"></i>
                    <h5 class="text-white">C'est prêt !</h5>
                    <p class="small text-muted mb-0">Retrouvez tout votre historique</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="py-5 my-5">
    <div class="container text-center">
        <h2 class="display-5 fw-black text-white mb-4">Adieu RepairDesk.<br>Bonjour le Futur.</h2>
        <a href="/inscription" class="btn btn-glow btn-lg px-5 rounded-pill">
            COMMENCER LA MIGRATION
            <i class="fa-solid fa-arrow-right ms-2"></i>
        </a>
        <p class="mt-3 text-muted small">Satisfait ou remboursé pendant 30 jours.</p>
    </div>
</section>
