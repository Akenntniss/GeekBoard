<?php include 'marketing/shared/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden pt-5 pb-5">
    <!-- Background Effects -->
    <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: -1;">
        <div class="position-absolute top-0 end-0 bg-primary opacity-20 rounded-circle blur-3xl" style="width: 600px; height: 600px; filter: blur(100px); transform: translate(30%, -30%);"></div>
        <div class="position-absolute bottom-0 start-0 bg-secondary opacity-20 rounded-circle blur-3xl" style="width: 500px; height: 500px; filter: blur(100px); transform: translate(-30%, 30%);"></div>
    </div>

    <div class="container position-relative pt-5 mt-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-20 rounded-pill px-3 py-2 mb-4 fade-in-up">
                    <i class="fa-solid fa-code-branch me-2"></i>Mises à jour hebdomadaires
                </div>
                <h1 class="display-3 fw-bold mb-4 fade-in-up delay-1">Changelog</h1>
                <p class="fs-5 text-muted mb-0 fade-in-up delay-2">
                    Découvrez les dernières améliorations, corrections fixes et nouvelles fonctionnalités apportées à SERVO.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Timeline Section -->
<section class="section pt-0">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="timeline-wrapper position-relative">
                    <!-- Vertical Line -->
                    <div class="position-absolute top-0 bottom-0 start-0 border-start border-light" style="left: 20px;"></div>

                    <!-- 2025: Enterprise & Gamification -->
                    <div class="update-item position-relative ps-5 mb-5 fade-in-up">
                        <div class="timeline-marker position-absolute bg-primary rounded-circle border border-4 border-dark" style="width: 16px; height: 16px; left: 13px; top: 5px;"></div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                            <h3 class="fw-bold mb-0">Version 3.0 <span class="badge bg-primary ms-2 align-middle fs-6">Enterprise Era</span></h3>
                            <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>Décembre 2025</span>
                        </div>
                        <div class="card-glass p-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-success bg-opacity-20 text-success me-2">Majeur</span>
                                <h5 class="mb-0">Gamification & Authority</h5>
                            </div>
                            <p class="text-muted mb-4">
                                Lancement des fonctionnalités Enterprise pour les réseaux de franchises.
                            </p>
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li class="d-flex align-items-start text-muted">
                                    <i class="fa-solid fa-gamepad text-primary mt-1 me-2"></i>
                                    <span><strong>Module Missions :</strong> XP, Niveaux et Badges pour les techniciens.</span>
                                </li>
                                <li class="d-flex align-items-start text-muted">
                                    <i class="fa-solid fa-shield-halved text-success mt-1 me-2"></i>
                                    <span><strong>Trust Center :</strong> Conformité RGPD & Sécurité avancée (SSO).</span>
                                </li>
                                <li class="d-flex align-items-start text-muted">
                                    <i class="fa-solid fa-building text-info mt-1 me-2"></i>
                                    <span><strong>Gestion Multi-Sites :</strong> Vue consolidée pour les réseaux.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Mid 2025: AI & Automation -->
                    <div class="update-item position-relative ps-5 mb-5 fade-in-up">
                        <div class="timeline-marker position-absolute bg-secondary rounded-circle border border-4 border-dark" style="width: 16px; height: 16px; left: 13px; top: 5px;"></div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                            <h3 class="fw-bold mb-0">Version 2.5: Intelligence</h3>
                            <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>Juin 2025</span>
                        </div>
                        <div class="card-glass p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h5 class="mb-2"><i class="fa-solid fa-robot text-accent me-2"></i>Servo Assistant</h5>
                                    <p class="text-muted small mb-0">L'IA analyse vos logs de réparation pour suggérer des diagnostics et estimer le temps de réparation.</p>
                                </div>
                                <div class="col-12 border-top border-light border-opacity-10 pt-3">
                                    <h5 class="mb-2"><i class="fa-solid fa-headset text-warning me-2"></i>Support VoIP</h5>
                                    <p class="text-muted small mb-0">Intégration native avec 3CX et Aircall. Les fiches clients s'ouvrent à l'appel entrant.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2024: Compliance & Hardware -->
                    <div class="update-item position-relative ps-5 mb-5 fade-in-up">
                        <div class="timeline-marker position-absolute bg-secondary rounded-circle border border-4 border-dark" style="width: 16px; height: 16px; left: 13px; top: 5px;"></div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                            <h3 class="fw-bold mb-0">Version 2.3: Hardware</h3>
                            <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>Septembre 2024</span>
                        </div>
                        <div class="card-glass p-4">
                             <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-warning bg-opacity-20 text-warning me-2">Rachat</span>
                                <h5 class="mb-0">Module Seconde Main</h5>
                            </div>
                            <p class="text-muted mb-0">
                                Gestion complète des rachats : tests fonctionnels, contrats automatiques, signature sur tablette et export Livre de Police.
                            </p>
                        </div>
                    </div>

                    <!-- 2023: Connectivity -->
                    <div class="update-item position-relative ps-5 mb-5 fade-in-up">
                        <div class="timeline-marker position-absolute bg-secondary rounded-circle border border-4 border-dark" style="width: 16px; height: 16px; left: 13px; top: 5px;"></div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                            <h3 class="fw-bold mb-0">Version 2.0: Connected Shop</h3>
                            <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>Mars 2023</span>
                        </div>
                        <div class="card-glass p-4">
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li class="d-flex align-items-start text-muted">
                                    <i class="fa-solid fa-bolt text-warning mt-1 me-2"></i>
                                    <span><strong>Connecteurs Fournisseurs :</strong> Import catalogue Utopya et SOSav en 1 clic.</span>
                                </li>
                                <li class="d-flex align-items-start text-muted">
                                    <i class="fa-solid fa-barcode text-light mt-1 me-2"></i>
                                    <span><strong>Scan & Go :</strong> Gestion des stocks par code-barres et impression d'étiquettes thermiques.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- 2022: UI Overhaul -->
                    <div class="update-item position-relative ps-5 mb-5 fade-in-up">
                        <div class="timeline-marker position-absolute bg-secondary rounded-circle border border-4 border-dark" style="width: 16px; height: 16px; left: 13px; top: 5px;"></div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                            <h3 class="fw-bold mb-0">Version 1.5: Modern UI</h3>
                            <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>Juin 2022</span>
                        </div>
                        <div class="card-glass p-4">
                            <p class="text-muted mb-0">
                                Refonte complète de l'interface en "Dark Glass". Introduction du <strong>Kanban de réparation</strong> (Trello-style) et des notifications SMS automatiques.
                            </p>
                        </div>
                    </div>

                    <!-- 2020-2021: Origins -->
                    <div class="update-item position-relative ps-5 mb-5 fade-in-up">
                         <div class="timeline-marker position-absolute bg-secondary rounded-circle border border-4 border-dark" style="width: 16px; height: 16px; left: 13px; top: 5px;"></div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                            <h3 class="fw-bold mb-0">Version 1.0: Genesis</h3>
                            <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>2020 - 2021</span>
                        </div>
                        <div class="card-glass p-4">
                            <p class="text-muted mb-0">
                                MVP développé dans un garage. Gestion basique des clients, création de fiches de réparation et facturation simple.
                            </p>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                       <p class="text-muted fst-italic">Et ce n'est que le début...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="section pt-0">
    <div class="container">
        <div class="card-glass p-5 text-center position-relative overflow-hidden">
             <div class="position-absolute top-50 start-50 translate-middle bg-primary opacity-10 rounded-circle blur-3xl" style="width: 300px; height: 300px;"></div>
             <div class="position-relative z-1">
                 <h2 class="fw-bold mb-3">Ne manquez aucune mise à jour</h2>
                 <p class="text-muted mb-4">Recevez directement dans votre boîte mail les nouveautés de SERVO. Pas de spam, promis.</p>
                 <div class="row justify-content-center">
                     <div class="col-md-6 col-lg-4">
                         <form class="d-flex gap-2">
                             <input type="email" class="form-control" placeholder="Votre email pro...">
                             <button class="btn btn-primary px-4 fw-bold">S'abonner</button>
                         </form>
                     </div>
                 </div>
             </div>
        </div>
    </div>
</section>

<style>
/* Specific Page Styles */
.card-glass {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    height: 100%;
}
.timeline-marker {
    box-shadow: 0 0 0 4px rgba(3, 7, 18, 1); 
}
</style>

<?php include 'marketing/shared/footer.php'; ?>
