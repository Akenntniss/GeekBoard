<style>
/* Page-Specific Styles for Futuristic Home */
.hero-glow {
    position: absolute;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(139,92,246,0.15) 0%, rgba(6,182,212,0.05) 50%, transparent 70%);
    border-radius: 50%;
    filter: blur(80px);
    z-index: -1;
    animation: pulse-glow 10s infinite alternate;
}

@keyframes pulse-glow {
    0% { transform: scale(1) translate(0,0); opacity: 0.5; }
    100% { transform: scale(1.2) translate(50px, -50px); opacity: 0.8; }
}

.feature-icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: linear-gradient(145deg, rgba(255,255,255,0.05), rgba(255,255,255,0.01));
    border: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}

.feature-icon-wrapper::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(6,182,212,0.2) 0%, transparent 60%);
    opacity: 0;
    transition: 0.3s;
}

.glass-card:hover .feature-icon-wrapper::after {
    opacity: 1;
}

.stat-value {
    font-family: 'Space Grotesk', sans-serif;
    background: linear-gradient(to right, #ffffff, #94a3b8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.filter-stat {
    border: 1px solid transparent;
    transition: all 0.3s ease;
}

.filter-stat:hover {
    background: rgba(255, 255, 255, 0.05);
}

.filter-stat.active {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>

<!-- Hero Section -->
<section class="position-relative overflow-hidden pt-5 pb-5 mt-5">
    <div class="hero-glow" style="top: -100px; right: -100px;"></div>
    <div class="hero-glow" style="bottom: -100px; left: -100px; background: radial-gradient(circle, rgba(236,72,153,0.15) 0%, transparent 70%);"></div>

    <div class="container position-relative z-1">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="d-inline-flex align-items-center rounded-pill border border-primary border-opacity-25 bg-primary bg-opacity-10 px-3 py-1 mb-4">
                    <span class="badge bg-primary rounded-pill me-2">NOUVEAU</span>
                    <span class="small text-primary fw-bold tracking-wide">VERSION 2.0 CYBER UPDATE</span>
                </div>
                
                <h1 class="display-3 fw-black mb-4 lh-base text-white">
                    Le Futur de la<br>
                    <span class="text-gradient position-relative">
                        Réparation
                        <svg class="position-absolute w-100" style="bottom: 5px; left: 0; height: 10px; z-index:-1;" viewBox="0 0 100 10" preserveAspectRatio="none">
                            <path d="M0 5 Q 50 10 100 5" stroke="var(--primary)" stroke-width="2" fill="none" opacity="0.5"/>
                        </svg>
                    </span>
                </h1>
                
                <p class="fs-5 text-secondary mb-5 opacity-75" style="max-width: 500px;">
                    Gérez votre atelier avec une interface venue du futur. 
                    IA intégrée, automatisation avancée, design immersif.
                    <br><span class="text-primary mt-2 d-inline-block"><i class="fa-solid fa-rocket me-2"></i>Propulsez votre business.</span>
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-4">
                    <a href="/inscription" class="btn btn-glow btn-lg px-5 py-3 rounded-pill">
                        DÉMARRER L'ESSAI
                        <i class="fa-solid fa-arrow-right ms-2 animate-x"></i>
                    </a>
                    <button onclick="toggleInterfacePreview()" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-flask me-2"></i>
                        TESTER L'INTERFACE
                    </button>
                </div>
                
                <div class="mt-5 pt-4 border-top border-secondary border-opacity-10">
                    <div class="d-flex align-items-center gap-4 text-secondary small text-uppercase fw-bold tracking-widest">
                        <div><i class="fa-solid fa-check text-primary me-2"></i>Sans Engagement</div>
                        <div><i class="fa-solid fa-check text-primary me-2"></i>Support 24/7</div>
                        <div><i class="fa-solid fa-check text-primary me-2"></i>Setup Gratuit</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 position-relative">
                <div class="position-relative">
<div class="flip-container position-relative">
    <div id="interface-flipper" class="flipper">
        
        <!-- FRONT: Marketing Message -->
        <div class="front glass-card rounded-4 p-4 d-flex flex-column justify-content-center align-items-center border border-primary border-opacity-25" style="background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(20px);">
            <!-- Marketing Carousel (Headers) -->
            <div id="marketing-carousel" class="w-100 position-relative mb-4" style="height: 220px;">
                <!-- Slide 1: Calls -->
                <div class="carousel-slide active position-absolute w-100 top-50 start-50 translate-middle transition-all duration-500" style="opacity: 1; transition: opacity 0.5s ease;">
                    <div class="text-center">
                        <div class="d-inline-block p-3 rounded-circle mb-3 problem-box">
                            <i class="fa-solid fa-headset fs-1"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2 text-uppercase">LES TECHNICIENS DÉTESTENT<br>APPELER LES CLIENTS !</h3>
                        <div class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill">
                            PROBLÈME N°1
                        </div>
                    </div>
                </div>

                <!-- Slide 2: Orders -->
                <div class="carousel-slide position-absolute w-100 top-50 start-50 translate-middle transition-all duration-500" style="opacity: 0; pointer-events: none; transition: opacity 0.5s ease;">
                     <div class="text-center">
                         <div class="d-inline-block p-3 rounded-circle mb-3" style="color: #fbbf24; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.2);">
                            <i class="fa-solid fa-clipboard-question fs-1"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2 text-uppercase">L'ERREUR EST HUMAINE...<br>MAIS ELLE COÛTE CHER !</h3>
                        <div class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill">
                            PROBLÈME N°2
                        </div>
                    </div>
                </div>

                <!-- Slide 3: Inventory -->
                <div class="carousel-slide position-absolute w-100 top-50 start-50 translate-middle transition-all duration-500" style="opacity: 0; pointer-events: none; transition: opacity 0.5s ease;">
                     <div class="text-center">
                         <div class="d-inline-block p-3 rounded-circle mb-3" style="color: #f87171; background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2);">
                            <i class="fa-solid fa-boxes-stacked fs-1"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2 text-uppercase">ARRÊTEZ DE PAYER<br>VOS PIÈCES TROP CHER !</h3>
                        <div class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill">
                            PROBLÈME N°3
                        </div>
                    </div>
                </div>

                <!-- Slide 4: Partners -->
                 <div class="carousel-slide position-absolute w-100 top-50 start-50 translate-middle transition-all duration-500" style="opacity: 0; pointer-events: none; transition: opacity 0.5s ease;">
                     <div class="text-center">
                         <div class="d-inline-block p-3 rounded-circle mb-3" style="color: #38bdf8; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2);">
                            <i class="fa-solid fa-handshake fs-1"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2 text-uppercase">VOTRE LIVRE DE POLICE<br>EST-IL VRAIMENT CONFORME ?</h3>
                        <div class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2 rounded-pill">
                            PROBLÈME N°4
                        </div>
                    </div>
                </div>

                <!-- Slide 5: Time -->
                 <div class="carousel-slide position-absolute w-100 top-50 start-50 translate-middle transition-all duration-500" style="opacity: 0; pointer-events: none; transition: opacity 0.5s ease;">
                     <div class="text-center">
                         <div class="d-inline-block p-3 rounded-circle mb-3" style="color: #a78bfa; background: rgba(167, 139, 250, 0.1); border: 1px solid rgba(167, 139, 250, 0.2);">
                            <i class="fa-solid fa-clock fs-1"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2 text-uppercase">SAVEZ-VOUS VRAIMENT<br>CE QUE FONT VOS ÉQUIPES ?</h3>
                        <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">
                            PROBLÈME N°5
                        </div>
                    </div>
                </div>
            </div>

            <!-- Static Solution List -->
            <div id="solution-list" class="w-100 mb-3 flex-grow-1 overflow-y-auto pe-2 custom-scrollbar">
                <div class="marketing-list-item d-flex align-items-center gap-3 w-100 text-start py-2" onclick="jumpToSlide(0)" style="cursor: pointer;">
                    <div class="bg-danger bg-opacity-10 rounded p-2 text-danger"><i class="fa-solid fa-phone-slash"></i></div>
                    <div>
                        <div class="text-white fw-bold small">Fini les appels répétitifs</div>
                        <div class="text-white-50 xsmall">Envoi de SMS & liens de suivi automatiques.</div>
                    </div>
                </div>

                <div class="marketing-list-item d-flex align-items-center gap-3 w-100 text-start py-2" onclick="jumpToSlide(1)" style="cursor: pointer;">
                    <div class="bg-warning bg-opacity-10 rounded p-2 text-warning"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div>
                        <div class="text-white fw-bold small">Fini les oublis</div>
                        <div class="text-white-50 xsmall">Bons de commandes automatisés.</div>
                    </div>
                </div>

                <div class="marketing-list-item d-flex align-items-center gap-3 w-100 text-start py-2" onclick="jumpToSlide(2)" style="cursor: pointer;">
                    <div class="bg-danger bg-opacity-10 rounded p-2 text-danger"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <div>
                        <div class="text-white fw-bold small">Boostez votre productivité</div>
                        <div class="text-white-50 xsmall">Catalogue unique fournisseurs.</div>
                    </div>
                </div>

                <div class="marketing-list-item d-flex align-items-center gap-3 w-100 text-start py-2" onclick="jumpToSlide(3)" style="cursor: pointer;">
                    <div class="bg-info bg-opacity-10 rounded p-2 text-info"><i class="fa-solid fa-shield-halved"></i></div>
                    <div>
                        <div class="text-white fw-bold small">Rachetez en toute sécurité</div>
                        <div class="text-white-50 xsmall">Livre de police certifié inclus.</div>
                    </div>
                </div>
                
                 <div class="marketing-list-item d-flex align-items-center gap-3 w-100 text-start py-2" onclick="jumpToSlide(4)" style="cursor: pointer;">
                    <div class="bg-primary bg-opacity-10 rounded p-2 text-primary"><i class="fa-solid fa-trophy"></i></div>
                    <div>
                        <div class="text-white fw-bold small">Motivez vos employés</div>
                        <div class="text-white-50 xsmall">Missions rémunérées & Gamification.</div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                 <div class="d-inline-block px-4 py-2 rounded-pill solution-box mb-3" onclick="toggleInterfacePreview()" style="cursor: pointer; transition: transform 0.2s;">
                    <i class="fa-solid fa-check me-2"></i> ON A LA SOLUTION
                </div>
            </div>
        </div>

        <!-- BACK: Dashboard Preview -->
        <div class="back">
            <div class="row g-4 h-100">
                    <!-- Left Column: Interactive Preview -->
                    <div class="col-lg-9 order-2 order-lg-1">
                        <div class="glass-card p-1 rounded-4 border border-primary border-opacity-25 position-relative z-2" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); min-height: 500px;">
                            
                            <!-- Window Controls -->
                            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom border-white border-opacity-10 bg-dark bg-opacity-25 rounded-top-4 position-relative">
                                <div class="rounded-circle bg-danger" style="width:8px; height:8px;"></div>
                                <div class="rounded-circle bg-warning" style="width:8px; height:8px;"></div>
                                <div class="rounded-circle bg-success" style="width:8px; height:8px;"></div>
                                <div class="text-muted xsmall font-monospace position-absolute start-50 translate-middle-x">SERVO OS</div>
                            </div>

                            <!-- Dashboard Content (Active) -->
                            <div id="preview-dashboard" class="preview-pane p-3 fade-in">
                                <!-- Top Actions -->
                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <div class="p-2 rounded-3 bg-opacity-10 bg-success border border-success border-opacity-20 d-flex align-items-center gap-2">
                                            <div class="bg-success rounded p-1 text-white d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-list-check xsmall"></i></div>
                                            <div class="lh-1 text-white small fw-bold">Nouvelle Tâche</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded-3 bg-opacity-10 bg-warning border border-warning border-opacity-20 d-flex align-items-center gap-2 cursor-pointer hover-scale" onclick="openReparationModal()">
                                            <div class="bg-warning rounded p-1 text-dark d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-wrench xsmall"></i></div>
                                            <div class="lh-1 text-white small fw-bold">Nouvelle Réparation</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded-3 bg-opacity-10 bg-info border border-info border-opacity-20 d-flex align-items-center gap-2 cursor-pointer hover-scale" onclick="openCommandeModal()">
                                            <div class="bg-info rounded p-1 text-white d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-cart-shopping xsmall"></i></div>
                                            <div class="lh-1 text-white small fw-bold">Nouvelle Commande</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Middle Items -->
                                <div class="row g-3 mb-3">
                                    <div class="col-4">
                                        <div class="glass p-2 rounded-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-white border-opacity-10 pb-1">
                                                <span class="xsmall text-info fw-bold"><i class="fa-solid fa-list-ul me-1"></i>Tâches</span>
                                                <span class="badge bg-info bg-opacity-20 text-info rounded-pill xsmall">0</span>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="bg-dark bg-opacity-50 p-1 rounded border border-white border-opacity-5 d-flex align-items-center gap-2">
                                                    <div class="form-check m-0 min-w-auto">
                                                        <input class="form-check-input xsmall bg-transparent border-secondary" type="checkbox" checked>
                                                    </div>
                                                    <div class="xsmall text-decoration-line-through text-white-50 text-truncate">Rappeler M. Thomas</div>
                                                </div>
                                                <div class="bg-dark bg-opacity-50 p-1 rounded border border-white border-opacity-5 d-flex align-items-center gap-2">
                                                    <div class="form-check m-0 min-w-auto">
                                                        <input class="form-check-input xsmall bg-transparent" type="checkbox">
                                                    </div>
                                                    <div class="xsmall text-white text-truncate">Commander écran iPhone 13</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="glass p-2 rounded-3 h-100 cursor-pointer hover-scale" onclick="switchPreview('reparation', document.getElementById('nav-btn-reparation'))">
                                             <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-white border-opacity-10 pb-1">
                                                <span class="xsmall text-light fw-bold"><i class="fa-solid fa-clock-rotate-left me-1"></i>Réparations</span>
                                                <span class="badge bg-primary bg-opacity-20 text-primary rounded-pill xsmall">17</span>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="bg-dark bg-opacity-50 p-1 rounded border border-white border-opacity-5">
                                                    <div class="d-flex justify-content-between xsmall mb-1">
                                                        <span class="fw-bold text-white">Ken Redmi Note 10</span>
                                                        <span class="text-white-50">27/12</span>
                                                    </div>
                                                    <div class="xsmall text-secondary text-truncate">Remplacement caméra...</div>
                                                </div>
                                                <div class="bg-dark bg-opacity-50 p-1 rounded border border-white border-opacity-5">
                                                     <div class="d-flex justify-content-between xsmall mb-1">
                                                        <span class="fw-bold text-white">Mifsud Moov...</span>
                                                        <span class="text-white-50">26/12</span>
                                                    </div>
                                                    <div class="xsmall text-secondary text-truncate">Ne charge pas</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                         <div class="glass p-2 rounded-3 h-100 cursor-pointer hover-scale" onclick="switchPreview('commande', document.getElementById('nav-btn-commande'))">
                                             <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-white border-opacity-10 pb-1">
                                                <span class="xsmall text-warning fw-bold"><i class="fa-solid fa-box-open me-1"></i>Commandes</span>
                                                <span class="badge bg-warning bg-opacity-20 text-warning rounded-pill xsmall">11</span>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                 <div class="bg-dark bg-opacity-50 p-1 rounded border border-white border-opacity-5">
                                                    <div class="d-flex justify-content-between xsmall mb-1">
                                                        <span class="fw-bold text-white">Samsung Tab A...</span>
                                                        <span class="text-white-50">28/12</span>
                                                    </div>
                                                    <div class="xsmall text-secondary">Utopya</div>
                                                </div>
                                                 <div class="bg-dark bg-opacity-50 p-1 rounded border border-white border-opacity-5">
                                                    <div class="d-flex justify-content-between xsmall mb-1">
                                                        <span class="fw-bold text-white">Bloc Ecran Honor...</span>
                                                        <span class="text-white-50">28/12</span>
                                                    </div>
                                                    <div class="xsmall text-secondary">Mobilax</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom Stats -->
                                <div class="row g-2 mb-3">
                                    <div class="col-3">
                                        <div class="glass p-2 rounded-3 text-center border-start border-4 border-primary hover-scale" onclick="openStatsModal()">
                                            <div class="h5 fw-bold text-white mb-0">5</div>
                                            <div class="xsmall text-white-50">Nouvelles</div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="glass p-2 rounded-3 text-center border-start border-4 border-info hover-scale" onclick="openStatsModal()">
                                            <div class="h5 fw-bold text-white mb-0">12</div>
                                            <div class="xsmall text-white-50">Effectuées</div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="glass p-2 rounded-3 text-center border-start border-4 border-success hover-scale" onclick="openStatsModal()">
                                            <div class="h5 fw-bold text-white mb-0">8</div>
                                            <div class="xsmall text-white-50">Restituées</div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="glass p-2 rounded-3 text-center border-start border-4 border-warning hover-scale" onclick="openStatsModal()">
                                            <div class="h5 fw-bold text-white mb-0">3</div>
                                            <div class="xsmall text-white-50">Devis</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employees Status -->
                                <div class="glass p-2 rounded-3">
                                    <div class="xsmall text-white-50 text-uppercase mb-2 fw-bold">Statut des employés</div>
                                    <div class="table-responsive">
                                        <table class="table table-borderless table-sm mb-0 align-middle text-white xsmall">
                                            <thead>
                                                <tr class="text-secondary border-bottom border-white border-opacity-10">
                                                    <th>TECHNICIEN</th>
                                                    <th>STATUT</th>
                                                    <th>TEMPS</th>
                                                    <th>ID RÉP</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="border-bottom border-white border-opacity-5">
                                                    <td class="fw-bold text-primary">Eric</td>
                                                    <td><i class="fa-solid fa-wrench text-warning me-1"></i>En réparation</td>
                                                    <td class="font-monospace">17m 13s</td>
                                                    <td>#1789</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold text-info">Antoine</td>
                                                    <td><i class="fa-solid fa-pause text-secondary me-1"></i>En pause</td>
                                                    <td class="font-monospace">07m 00s</td>
                                                    <td>-</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <!-- Repairs Content (Hidden) -->
                            <div id="preview-reparation" class="preview-pane p-3 fade-in" style="display: none;">
                                <!-- Search & Filters -->
                                <div class="mb-3">
                                    <div class="glass p-2 rounded-3 mb-2 d-flex gap-2">
                                        <div class="input-group input-group-sm bg-transparent">
                                            <span class="input-group-text bg-transparent border-0 text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                                            <input type="text" class="form-control bg-transparent border-0 text-white shadow-none ps-0" placeholder="Rechercher par nom, téléphone...">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 overflow-auto pb-2" style="scrollbar-width: none;">
                                        <button class="btn btn-sm btn-glass-filter active border-opacity-10 text-white-50 rounded-3 d-flex align-items-center gap-2 px-3" 
                                            onclick="filterRepairs('nouvelle', this)"
                                            ondragover="allowDrop(event)" 
                                            ondrop="dropRepair(event, 'nouvelle')"
                                            onlove="highlightDrop(this)"
                                            ondragleave="removeHighlight(this)">
                                            <i class="fa-solid fa-circle-plus text-primary"></i> Nouvelle <span class="badge bg-primary rounded-pill repair-count-nouvelle">4</span>
                                        </button>
                                        <button class="btn btn-sm btn-glass-filter border-opacity-10 text-white-50 rounded-3 d-flex align-items-center gap-2 px-3" 
                                            onclick="filterRepairs('en-cours', this)"
                                            ondragover="allowDrop(event)" 
                                            ondrop="dropRepair(event, 'en-cours')"
                                            ondragenter="highlightDrop(this)"
                                            ondragleave="removeHighlight(this)">
                                            <i class="fa-solid fa-arrows-rotate text-white"></i> En cours <span class="badge bg-white bg-opacity-10 rounded-pill repair-count-en-cours">2</span>
                                        </button>
                                        <button class="btn btn-sm btn-glass-filter border-opacity-10 text-white-50 rounded-3 d-flex align-items-center gap-2 px-3" 
                                            onclick="filterRepairs('en-attente', this)"
                                            ondragover="allowDrop(event)" 
                                            ondrop="dropRepair(event, 'en-attente')"
                                            ondragenter="highlightDrop(this)"
                                            ondragleave="removeHighlight(this)">
                                            <i class="fa-solid fa-hourglass-half text-warning"></i> En attente <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill repair-count-en-attente">3</span>
                                        </button>
                                        <button class="btn btn-sm btn-glass-filter border-opacity-10 text-white-50 rounded-3 d-flex align-items-center gap-2 px-3" 
                                            onclick="filterRepairs('termine', this)"
                                            ondragover="allowDrop(event)" 
                                            ondrop="dropRepair(event, 'termine')"
                                            ondragenter="highlightDrop(this)"
                                            ondragleave="removeHighlight(this)">
                                            <i class="fa-solid fa-check text-success"></i> Terminé <span class="badge bg-white bg-opacity-10 rounded-pill repair-count-termine">4</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Repairs Grid -->
                                <div class="row g-2" id="repairs-grid">
                                    
                                    <!-- NOUVELLE (4) -->
                                    <div class="col-6 repair-card hover-scale" id="card-1783" data-status="nouvelle" draggable="true" ondragstart="dragRepair(event)" onclick="openRepairModal('1783', 'Thomas Martin', 'Redmi Note 10', 'Caméra HS')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge badge-cyan-soft xsmall fw-bold">NOUVELLE</span><span class="xsmall text-white-50">#1783</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Thomas Martin</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-mobile-screen text-secondary xsmall"></i><span class="xsmall text-white fw-bold">Redmi Note 10</span></div><div class="xsmall text-secondary text-truncate">Caméra HS</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1784" data-status="nouvelle" draggable="true" ondragstart="dragRepair(event)" onclick="openRepairModal('1784', 'Sarah Connor', 'iPhone 12', 'Ecran cassé')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge badge-cyan-soft xsmall fw-bold">NOUVELLE</span><span class="xsmall text-white-50">#1784</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Sarah Connor</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-mobile-screen text-secondary xsmall"></i><span class="xsmall text-white fw-bold">iPhone 12</span></div><div class="xsmall text-secondary text-truncate">Ecran cassé</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1785" data-status="nouvelle" draggable="true" ondragstart="dragRepair(event)" onclick="openRepairModal('1785', 'John Doe', 'Samsung S21', 'Batterie gonflée')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge badge-cyan-soft xsmall fw-bold">NOUVELLE</span><span class="xsmall text-white-50">#1785</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">John Doe</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-brands fa-android text-secondary xsmall"></i><span class="xsmall text-white fw-bold">Samsung S21</span></div><div class="xsmall text-secondary text-truncate">Batterie gonflée</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1786" data-status="nouvelle" draggable="true" ondragstart="dragRepair(event)" onclick="openRepairModal('1786', 'Jane Birkin', 'MacBook Air', 'Clavier HS')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge badge-cyan-soft xsmall fw-bold">NOUVELLE</span><span class="xsmall text-white-50">#1786</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Jane Birkin</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-laptop text-secondary xsmall"></i><span class="xsmall text-white fw-bold">MacBook Air</span></div><div class="xsmall text-secondary text-truncate">Clavier HS</div></div>
                                        </div>
                                    </div>

                                    <!-- EN COURS (2) -->
                                    <div class="col-6 repair-card hover-scale" id="card-1782" data-status="en-cours" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1782', 'Sophie Bernard', 'iPad 9', 'Ne charge pas')">
                                         <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-20 xsmall"><i class="fa-solid fa-arrows-rotate me-1"></i>EN COURS</span><span class="xsmall text-white-50">#1782</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Sophie Bernard</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-tablet-screen-button text-secondary xsmall"></i><span class="xsmall text-white fw-bold">iPad 9</span></div><div class="xsmall text-secondary text-truncate">Ne charge pas</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1787" data-status="en-cours" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1787', 'Marc Levy', 'iPad Pro', 'Connecteur charge')">
                                         <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-20 xsmall"><i class="fa-solid fa-arrows-rotate me-1"></i>EN COURS</span><span class="xsmall text-white-50">#1787</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Marc Levy</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-tablet-screen-button text-secondary xsmall"></i><span class="xsmall text-white fw-bold">iPad Pro</span></div><div class="xsmall text-secondary text-truncate">Connecteur charge</div></div>
                                        </div>
                                    </div>

                                    <!-- EN ATTENTE (3) -->
                                    <div class="col-6 repair-card hover-scale" id="card-1779" data-status="en-attente" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1779', 'Emma Dubois', 'Boitier TV', 'Devis en cours')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge badge-warning-soft xsmall">DEVIS DISPO</span><span class="xsmall text-white-50">#1779</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Emma Dubois</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-tv text-secondary xsmall"></i><span class="xsmall text-white fw-bold">Boitier TV</span></div><div class="xsmall text-secondary text-truncate">Devis en cours</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1791" data-status="en-attente" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1791', 'Neo Matrix', 'Asus Rog', 'Attente pièce')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 xsmall"><i class="fa-solid fa-truck me-1"></i>LIVRAISON</span><span class="xsmall text-white-50">#1791</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Neo Matrix</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-laptop text-secondary xsmall"></i><span class="xsmall text-white fw-bold">Asus Rog</span></div><div class="xsmall text-secondary text-truncate">Attente pièce</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1792" data-status="en-attente" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1792', 'Maitre Yoda', 'Tablette', 'Validation Responsable')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 xsmall"><i class="fa-solid fa-user-tie me-1"></i>RESPONSABLE</span><span class="xsmall text-white-50">#1792</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Maitre Yoda</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-tablet text-secondary xsmall"></i><span class="xsmall text-white fw-bold">Tablette</span></div><div class="xsmall text-secondary text-truncate">Validation Resp.</div></div>
                                        </div>
                                    </div>

                                    <!-- TERMINÉ (4) -->
                                    <div class="col-6 repair-card hover-scale" id="card-1780" data-status="termine" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1780', 'Lucas Petit', 'Moovboard', 'Remplacement écran')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 xsmall"><i class="fa-solid fa-check me-1"></i>TERMINÉ</span><span class="xsmall text-white-50">#1780</span></div>
                                            <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Lucas Petit</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-laptop text-secondary xsmall"></i><span class="xsmall text-white fw-bold">Moovboard</span></div><div class="xsmall text-secondary text-truncate">Remplacement écran</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1788" data-status="termine" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1788', 'Emma Watson', 'Huawei P30', 'Vitre arrière')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 xsmall"><i class="fa-solid fa-check me-1"></i>TERMINÉ</span><span class="xsmall text-white-50">#1788</span></div>
                                              <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Emma Watson</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-mobile-screen text-secondary xsmall"></i><span class="xsmall text-white fw-bold">Huawei P30</span></div><div class="xsmall text-secondary text-truncate">Vitre arrière</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1789" data-status="termine" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1789', 'Brad Pitt', 'PS5', 'HDMI HS')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 xsmall"><i class="fa-solid fa-check me-1"></i>TERMINÉ</span><span class="xsmall text-white-50">#1789</span></div>
                                             <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Brad Pitt</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-gamepad text-secondary xsmall"></i><span class="xsmall text-white fw-bold">PS5</span></div><div class="xsmall text-secondary text-truncate">HDMI HS</div></div>
                                        </div>
                                    </div>
                                    <div class="col-6 repair-card hover-scale" id="card-1790" data-status="termine" draggable="true" ondragstart="dragRepair(event)" style="display:none;" onclick="openRepairModal('1790', 'Lara Croft', 'PC Gamer', 'Nettoyage')">
                                        <div class="glass p-2 rounded-3 h-100 position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 xsmall"><i class="fa-solid fa-check me-1"></i>TERMINÉ</span><span class="xsmall text-white-50">#1790</span></div>
                                             <div class="d-flex align-items-center gap-2 mb-2"><div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="fa-solid fa-user xsmall text-white"></i></div><div class="lh-1"><div class="xsmall fw-bold text-white">Lara Croft</div></div></div>
                                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-5 mb-2"><div class="d-flex align-items-center gap-1 mb-1"><i class="fa-solid fa-desktop text-secondary xsmall"></i><span class="xsmall text-white fw-bold">PC Gamer</span></div><div class="xsmall text-secondary text-truncate">Nettoyage</div></div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        <!-- Commandes Content (Hidden) -->
                        <div id="preview-commande" class="preview-pane p-3 fade-in" style="display: none;">
                            <!-- Actions & Filters -->
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-2">
                                     <button class="btn btn-primary d-flex align-items-center gap-2 hover-scale" onclick="openCommandeModal()">
                                        <i class="fas fa-plus-circle"></i>
                                        Nouvelle commande
                                    </button>
                                     <div class="glass px-3 py-1 rounded-3 d-flex align-items-center text-white-50 xsmall">
                                        <i class="fas fa-filter me-2"></i> Filtres
                                     </div>
                                </div>
                                <div class="input-group input-group-sm w-auto glass rounded-3 border-0">
                                    <span class="input-group-text bg-transparent border-0 text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" class="form-control bg-transparent border-0 text-white shadow-none" placeholder="Rechercher..." style="width: 150px;">
                                </div>
                            </div>

                            <!-- Orders List -->
                            <div class="d-flex flex-column gap-2" id="orders-list">
                                <!-- Dummy Order 1 -->
                                <div class="glass p-3 rounded-3 d-flex justify-content-between align-items-center hover-scale cursor-pointer" onclick="openCommandeStatutModal('Ecran iPhone 13', 'Mobilax')">
                                    <div class="d-flex align-items-center gap-3">
                                         <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                            <i class="fa-solid fa-box-open text-warning"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-white">Ecran iPhone 13</div>
                                            <div class="xsmall text-white-50">Mobilax • <span class="text-warning">En attente</span></div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-white">89.00 €</div>
                                        <div class="xsmall text-white-50">#CMD-2023-001</div>
                                    </div>
                                </div>
                                <!-- Dummy Order 2 -->
                                <div class="glass p-3 rounded-3 d-flex justify-content-between align-items-center hover-scale cursor-pointer" onclick="openCommandeStatutModal('Batterie Samsung S21', 'Utopya')">
                                    <div class="d-flex align-items-center gap-3">
                                         <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                            <i class="fa-solid fa-check text-success"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-white">Batterie Samsung S21</div>
                                            <div class="xsmall text-white-50">Utopya • <span class="text-success">Reçu</span></div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-white">45.00 €</div>
                                        <div class="xsmall text-white-50">#CMD-2023-002</div>
                                    </div>
                                </div>
                                 <!-- Dummy Order 3 -->
                                <div class="glass p-3 rounded-3 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                         <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                            <i class="fa-solid fa-truck text-info"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-white">Connecteur Charge iPad</div>
                                            <div class="xsmall text-white-50">SOSav • <span class="text-info">Commandé</span></div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-white">12.50 €</div>
                                        <div class="xsmall text-white-50">#CMD-2023-003</div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Taches Content (Hidden) -->
                        <div id="preview-tache" class="preview-pane p-3 fade-in" style="display: none;">
                             <!-- Top Stats (Clickable Filters) -->
                             <div class="row g-2 mb-3">
                                <div class="col-3">
                                    <div class="glass p-2 rounded-3 border-start border-4 border-primary position-relative overflow-hidden cursor-pointer hover-scale filter-stat active" onclick="filterTasks('all', this)">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="bg-primary bg-opacity-20 rounded p-1"><i class="fas fa-list-ul text-primary"></i></div>
                                            <i class="fas fa-chevron-right text-white-50 xsmall"></i>
                                        </div>
                                        <div class="h5 fw-bold text-white mb-0">3</div>
                                        <div class="xsmall text-white-50">Toutes les tâches</div>
                                    </div>
                                </div>
                                <div class="col-2">
                                     <div class="glass p-2 rounded-3 border-start border-4 border-info position-relative overflow-hidden cursor-pointer hover-scale filter-stat" onclick="filterTasks('todo', this)">
                                         <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="bg-info bg-opacity-20 rounded p-1"><i class="fas fa-clipboard-list text-info"></i></div>
                                            <i class="fas fa-chevron-right text-white-50 xsmall"></i>
                                        </div>
                                        <div class="h5 fw-bold text-white mb-0">1</div>
                                        <div class="xsmall text-white-50">À faire</div>
                                     </div>
                                </div>
                                <div class="col-2">
                                     <div class="glass p-2 rounded-3 border-start border-4 border-warning position-relative overflow-hidden cursor-pointer hover-scale filter-stat" onclick="filterTasks('in-progress', this)">
                                         <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="bg-warning bg-opacity-20 rounded p-1"><i class="fas fa-spinner text-warning"></i></div>
                                            <i class="fas fa-chevron-right text-white-50 xsmall"></i>
                                        </div>
                                        <div class="h5 fw-bold text-white mb-0">1</div>
                                        <div class="xsmall text-white-50">En cours</div>
                                     </div>
                                </div>
                                <div class="col-2">
                                     <div class="glass p-2 rounded-3 border-start border-4 border-success position-relative overflow-hidden cursor-pointer hover-scale filter-stat" onclick="filterTasks('done', this)">
                                         <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="bg-success bg-opacity-20 rounded p-1"><i class="fas fa-check-circle text-success"></i></div>
                                            <i class="fas fa-chevron-right text-white-50 xsmall"></i>
                                        </div>
                                        <div class="h5 fw-bold text-white mb-0">1</div>
                                        <div class="xsmall text-white-50">Terminées</div>
                                     </div>
                                </div>
                                <div class="col-3">
                                     <div class="glass p-2 rounded-3 border-start border-4 border-danger position-relative overflow-hidden cursor-pointer hover-scale filter-stat" onclick="filterTasks('high-priority', this)">
                                         <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="bg-danger bg-opacity-20 rounded p-1"><i class="fas fa-exclamation-triangle text-danger"></i></div>
                                            <i class="fas fa-chevron-right text-white-50 xsmall"></i>
                                        </div>
                                        <div class="h5 fw-bold text-white mb-0">2</div>
                                        <div class="xsmall text-white-50">Haute priorité</div>
                                     </div>
                                </div>
                             </div>

                             <!-- Filter Bar -->
                             <div class="glass p-2 rounded-3 mb-3 d-flex justify-content-between align-items-center">
                                 <div class="d-flex align-items-center gap-2 text-white small">
                                     <i class="fas fa-filter text-white-50"></i> <span id="current-filter-label">Toutes les tâches</span>
                                 </div>
                                 <button class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                                     <i class="fas fa-check"></i> Nouveau
                                 </button>
                             </div>

                             <!-- View Switcher -->
                             <div class="d-flex justify-content-center mb-3">
                                 <div class="btn-group" role="group">
                                     <button type="button" class="btn btn-sm btn-glass-nav active px-3">
                                         <i class="fas fa-th-large me-2"></i>Cartes
                                     </button>
                                     <button type="button" class="btn btn-sm btn-glass-nav px-3">
                                         <i class="fas fa-table me-2"></i>Tableau
                                     </button>
                                 </div>
                             </div>

                             <!-- Task List (9 Items) -->
                             <div class="d-flex flex-column gap-2" id="task-list">
                                 <!-- TODO Items (3) -->
                                 <div class="glass p-3 rounded-3 hover-scale cursor-pointer task-card" data-status="todo" data-priority="high"
                                      draggable="true" ondragstart="dragTask(event)" 
                                      onclick="openTaskModal('Rappeler Client X', 'Haute', 'À faire', 'Client mécontent, rappel urgent', '30/12/2023', 'Eric')">
                                     <div class="d-flex justify-content-between align-items-start mb-2">
                                         <div class="fw-bold text-white">Rappeler Client X</div>
                                         <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 rounded-pill xsmall">HAUTE</span>
                                     </div>
                                     <div class="text-white-50 small mb-3">Client mécontent, rappel urgent</div>
                                     <div class="d-flex justify-content-between align-items-center">
                                         <div class="text-white-50 xsmall"><i class="far fa-calendar-alt me-1"></i>30/12/2023</div>
                                         <span class="badge bg-info bg-opacity-20 text-info border border-info border-opacity-25 rounded-pill xsmall"><i class="fas fa-clipboard-list me-1"></i>À faire</span>
                                     </div>
                                 </div>

                                 <!-- IN PROGRESS Items (1) -->
                                 <div class="glass p-3 rounded-3 hover-scale cursor-pointer task-card" data-status="in-progress" data-priority="high"
                                      draggable="true" ondragstart="dragTask(event)" 
                                      onclick="openTaskModal('Réparation iPhone 12', 'Haute', 'En cours', 'Problème carte mère complexe', '29/12/2023', 'Expert')">
                                     <div class="d-flex justify-content-between align-items-start mb-2">
                                         <div class="fw-bold text-white">Réparation iPhone 12</div>
                                         <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 rounded-pill xsmall">HAUTE</span>
                                     </div>
                                     <div class="text-white-50 small mb-3">Problème carte mère complexe</div>
                                     <div class="d-flex justify-content-between align-items-center">
                                         <div class="text-white-50 xsmall"><i class="far fa-calendar-alt me-1"></i>29/12/2023</div>
                                         <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 rounded-pill xsmall"><i class="fas fa-spinner me-1"></i>En cours</span>
                                     </div>
                                 </div>

                                 <!-- DONE Items (1) -->
                                 <div class="glass p-3 rounded-3 hover-scale cursor-pointer task-card" data-status="done" data-priority="medium"
                                      draggable="true" ondragstart="dragTask(event)" 
                                      onclick="openTaskModal('Changement Écran', 'Moyenne', 'Terminé', 'iPhone 11 terminer et testé', '27/12/2023', 'Eric')">
                                     <div class="d-flex justify-content-between align-items-start mb-2">
                                         <div class="fw-bold text-white">Changement Écran</div>
                                         <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 rounded-pill xsmall">MOYENNE</span>
                                     </div>
                                     <div class="text-white-50 small mb-3">iPhone 11 terminé et testé</div>
                                     <div class="d-flex justify-content-between align-items-center">
                                         <div class="text-white-50 xsmall"><i class="far fa-calendar-alt me-1"></i>27/12/2023</div>
                                         <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 rounded-pill xsmall"><i class="fas fa-check me-1"></i>Terminé</span>
                                     </div>
                                 </div>
                             </div>
                        </div>



                        <script>
                        function openCommandeModal() {
                            const modal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
                            modal.show();
                        }
                        
                        function simulateSaveOrder() {
                            const btn = document.querySelector('#ajouterCommandeModal .btn-primary');
                            const originalContent = btn.innerHTML;
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Enregistrement...';
                            
                            setTimeout(() => {
                                btn.innerHTML = originalContent;
                                const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterCommandeModal'));
                                modal.hide();
                                
                                // Add new dummy order to the list
                                const list = document.getElementById('orders-list');
                                const newOrder = document.createElement('div');
                                newOrder.className = 'glass p-3 rounded-3 d-flex justify-content-between align-items-center fade-in';
                                newOrder.innerHTML = `
                                    <div class="d-flex align-items-center gap-3">
                                         <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                            <i class="fa-solid fa-box-open text-warning"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-white">Ecran iPhone 13</div>
                                            <div class="xsmall text-white-50">Utopya • <span class="text-warning">En attente</span></div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-white">89.00 €</div>
                                        <div class="xsmall text-white-50">#CMD-NEW</div>
                                    </div>
                                `;
                                list.prepend(newOrder);
                                
                            }, 1000);
                        }
                        </script>

                        <!-- Kbase Content (Hidden) -->
                        <div id="preview-kbase" class="preview-pane p-3 fade-in" style="display: none;">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="h4 text-white fw-bold mb-0"><i class="fas fa-book text-info me-2"></i>Base de Connaissances</h2>
                                <button class="btn btn-sm btn-success d-flex align-items-center gap-2">
                                    <i class="fas fa-plus"></i> Créer un article
                                </button>
                            </div>



                            <!-- Search Bar -->
                            <div class="glass p-2 rounded-3 mb-4 d-flex gap-2">
                                <div class="input-group">
                                    <input type="text" class="form-control bg-transparent border-0 text-white shadow-none px-3" placeholder="Posez votre question...">
                                </div>
                                <button class="btn btn-primary px-4 d-flex align-items-center gap-2">
                                    <i class="fas fa-search"></i> Recherche IA
                                </button>
                            </div>

                            <!-- Content Grid (List View) -->
                            <div class="row">
                                <div class="col-12">
                                     <div class="d-flex flex-column gap-3">
                                        <!-- Article 1 -->
                                        <div class="glass p-3 rounded-3 hover-scale cursor-pointer border-start border-4 border-info" onclick="openKbaseArticle('xiaomi')">
                                            <div class="row align-items-center">
                                                <div class="col-auto d-flex align-items-center gap-3 border-end border-white border-opacity-10 pe-4">
                                                     <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                                                         <i class="fas fa-bolt fa-lg"></i>
                                                     </div>
                                                     <div class="d-none d-md-block">
                                                         <div class="fw-bold text-white small">Trotinette</div>
                                                         <div class="text-white-50 xsmall">Xiaomi</div>
                                                     </div>
                                                </div>
                                                <div class="col">
                                                    <h5 class="text-white fw-bold mb-1 fs-6">Code Erreur Xiaomi M365</h5>
                                                    <p class="text-white-50 small mb-0 line-clamp-1">Guide complet des codes erreurs et solutions pour trottinette Xiaomi M365.</p>
                                                </div>
                                                <div class="col-auto text-end ps-4 border-start border-white border-opacity-10">
                                                    <div class="fw-bold text-white">13 <span class="text-white-50 fw-normal xsmall">vues</span></div>
                                                    <div class="text-white-50 xsmall">05/11/25</div>
                                                </div>
                                            </div>
                                        </div>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inventory Content (Hidden) -->
                        <div id="preview-inventaire" class="preview-pane p-3 fade-in" style="display: none;">
                             <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="h4 text-white fw-bold mb-0"><i class="fas fa-boxes text-white me-2"></i>Inventaire</h2>
                                <button class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                                    <i class="fas fa-plus"></i> Nouveau Mouvement
                                </button>
                            </div>

                            <!-- Quick Stats -->
                             <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="glass p-3 rounded-3 border-start border-4 border-primary">
                                        <div class="h3 fw-bold text-white mb-0">12 450 €</div>
                                        <div class="small text-white-50">Valeur totale stock</div>
                                    </div>
                                </div>
                                 <div class="col-md-4">
                                    <div class="glass p-3 rounded-3 border-start border-4 border-warning">
                                        <div class="h3 fw-bold text-white mb-0">3</div>
                                        <div class="small text-white-50">Articles stock faible</div>
                                    </div>
                                </div>
                                 <div class="col-md-4">
                                    <div class="glass p-3 rounded-3 border-start border-4 border-success">
                                        <div class="h3 fw-bold text-white mb-0">98%</div>
                                        <div class="small text-white-50">Précision Inventaire</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions Grid -->
                            <div class="row g-4">
                                <div class="col-md-6 col-lg-4">
                                    <div class="glass p-4 rounded-3 hover-scale cursor-pointer text-center h-100 d-flex flex-column align-items-center justify-content-center" onclick="openStockMovementModal()">
                                        <div class="bg-primary bg-opacity-10 p-4 rounded-circle text-primary mb-3">
                                            <i class="fas fa-history fa-2x"></i>
                                        </div>
                                        <h5 class="fw-bold text-white mb-2">Historique des Mouvements</h5>
                                        <p class="text-white-50 small mb-0">Voir les entrées, sorties et modifications de stock récentes</p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                     <div class="glass p-4 rounded-3 hover-scale cursor-pointer text-center h-100 d-flex flex-column align-items-center justify-content-center opacity-50">
                                        <div class="bg-success bg-opacity-10 p-4 rounded-circle text-success mb-3">
                                            <i class="fas fa-clipboard-check fa-2x"></i>
                                        </div>
                                        <h5 class="fw-bold text-white mb-2">Faire un inventaire</h5>
                                        <p class="text-white-50 small mb-0">Lancer une session de comptage</p>
                                    </div>
                                </div>
                                 <div class="col-md-6 col-lg-4">
                                     <div class="glass p-4 rounded-3 hover-scale cursor-pointer text-center h-100 d-flex flex-column align-items-center justify-content-center opacity-50">
                                        <div class="bg-info bg-opacity-10 p-4 rounded-circle text-info mb-3">
                                            <i class="fas fa-truck-loading fa-2x"></i>
                                        </div>
                                        <h5 class="fw-bold text-white mb-2">Réception Commande</h5>
                                        <p class="text-white-50 small mb-0">Scanner et intégrer des produits reçus</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Decorative Elements -->
                        <div class="position-absolute bg-primary rounded-circle filter-blur" style="width: 200px; height: 200px; opacity: 0.15; top: -50px; right: -50px; z-index: 1; filter: blur(60px);"></div>
                        <div class="position-absolute bg-secondary rounded-circle filter-blur" style="width: 150px; height: 150px; opacity: 0.15; bottom: -30px; left: -30px; z-index: 1; filter: blur(60px);"></div>


                    <!-- MODULE: RACHAT APPAREILS (MOCKUP) -->
                    <div id="preview-rachat" class="preview-pane" style="display: none;">
                        <div class="modern-container">
                            <!-- En-tête -->
                            <div class="page-header">
                                <h3 class="page-title-modern">
                                    <i class="fas fa-hand-holding-usd me-2"></i>Rachat d'Appareils
                                </h3>
                                <p class="text-white-50 mb-0">Gérez les rachats d'appareils électroniques de vos clients</p>
                                <div class="responsive-header-actions">
                                    <button class="btn btn-dark border border-white border-opacity-10 text-white d-flex align-items-center gap-2 px-4 py-2">
                                        <i class="fas fa-plus text-primary"></i> Nouveau Rachat
                                    </button>
                                    <button class="btn btn-dark border border-white border-opacity-10 text-white d-flex align-items-center gap-2 px-4 py-2" onclick="openLivrePoliceModal()">
                                        <i class="fas fa-book text-secondary"></i> Livre de police
                                    </button>
                                </div>
                            </div>

                            <!-- Tableau -->
                            <div class="custom-table-container mt-4">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th>Modèle</th>
                                            <th class="px-4">Date</th>
                                            <th>Prix</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Row 1 -->
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-white">iPhone 12</span>
                                                    <span class="small text-white-50">128GB - Noir</span>
                                                </div>
                                            </td>
                                            <td class="text-white fw-bold px-4">18/12</td>
                                            <td class="text-white fw-bold">80€</td>
                                            <td class="text-end">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-info d-flex align-items-center gap-2" onclick="openRachatModal('guezquez saber', '18/12/2025', '80.00 €', 'NOUVEAU')"><i class="far fa-eye"></i></button>
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50 d-flex align-items-center gap-2"><i class="far fa-file-pdf"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 2 -->
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-white">Samsung S21</span>
                                                    <span class="small text-white-50">256GB - Gris</span>
                                                </div>
                                            </td>
                                            <td class="text-white fw-bold px-4">30/11</td>
                                            <td class="text-white fw-bold">250€</td>
                                            <td class="text-end">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-info d-flex align-items-center gap-2" onclick="openRachatModal('milazzo marvin', '30/11/2025', '9.00 €', 'NOUVEAU')"><i class="far fa-eye"></i></button>
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50 d-flex align-items-center gap-2"><i class="far fa-file-pdf"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 3 -->
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-white">PS5</span>
                                                    <span class="small text-white-50">Digital Edition</span>
                                                </div>
                                            </td>
                                            <td class="text-white fw-bold px-4">30/11</td>
                                            <td class="text-white fw-bold">200€</td>
                                            <td class="text-end">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-info d-flex align-items-center gap-2" onclick="openRachatModal('jacke steven', '30/11/2025', '0.00 €', 'NOUVEAU')"><i class="far fa-eye"></i></button>
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50 d-flex align-items-center gap-2"><i class="far fa-file-pdf"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 4 -->
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-white">iPad Air 4</span>
                                                    <span class="small text-white-50">64GB - Bleu</span>
                                                </div>
                                            </td>
                                            <td class="text-white fw-bold px-4">30/11</td>
                                            <td class="text-white fw-bold">180€</td>
                                            <td class="text-end">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-info d-flex align-items-center gap-2" onclick="openRachatModal('gerlier brigite', '30/11/2025', '0.00 €', 'NOUVEAU')"><i class="far fa-eye"></i></button>
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50 d-flex align-items-center gap-2"><i class="far fa-file-pdf"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 5 -->
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-white">MacBook Pro M1</span>
                                                    <span class="small text-white-50">13" - 8GB/256GB</span>
                                                </div>
                                            </td>
                                            <td class="text-white fw-bold px-4">30/11</td>
                                            <td class="text-white fw-bold">600€</td>
                                            <td class="text-end">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-info d-flex align-items-center gap-2" onclick="openRachatModal('jacke steven', '30/11/2025', '0.00 €', 'NOUVEAU')"><i class="far fa-eye"></i></button>
                                                    <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50 d-flex align-items-center gap-2"><i class="far fa-file-pdf"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-white-50 small">Affichage de 11 résultat(s)</div>
                                <div class="custom-pagination">
                                    <a class="page-link-custom active">1</a>
                                    <a class="page-link-custom">2</a>
                                    <a class="page-link-custom" style="width: auto; padding: 0 15px;">Suivant</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>




                    </div>

                    <!-- Right Column: Navigation -->
                    <div class="col-lg-3 order-1 order-lg-2 d-flex flex-column justify-content-center gap-2">
                     <button id="nav-btn-dashboard" class="btn btn-glass-nav active w-100 text-start d-flex align-items-center gap-3 p-3 rounded-3 transition-all" onclick="switchPreview('dashboard', this)">
                            <i class="fa-solid fa-table-columns text-primary fs-5"></i>
                            <span class="fw-bold text-white">Dashboard</span>
                        </button>
                         <button id="nav-btn-reparation" class="btn btn-glass-nav w-100 text-start d-flex align-items-center gap-3 p-3 rounded-3 transition-all" onclick="switchPreview('reparation', this)">
                            <i class="fa-solid fa-wrench text-secondary fs-5"></i>
                            <span class="fw-bold text-white-50">Réparation</span>
                        </button>
                         <button id="nav-btn-commande" class="btn btn-glass-nav w-100 text-start d-flex align-items-center gap-3 p-3 rounded-3 transition-all" onclick="switchPreview('commande', this)">
                            <i class="fa-solid fa-cart-shopping text-warning fs-5"></i>
                            <span class="fw-bold text-white-50">Commandes</span>
                        </button>
                         <button id="nav-btn-tache" class="btn btn-glass-nav w-100 text-start d-flex align-items-center gap-3 p-3 rounded-3 transition-all" onclick="switchPreview('tache', this)">
                            <i class="fa-solid fa-list-check text-success fs-5"></i>
                            <span class="fw-bold text-white-50">Tâches</span>
                        </button>
                        <button id="nav-btn-kbase" class="btn btn-glass-nav w-100 text-start d-flex align-items-center gap-3 p-3 rounded-3 transition-all" onclick="switchPreview('kbase', this)">
                            <i class="fa-solid fa-book text-info fs-5"></i>
                            <span class="fw-bold text-white-50">Kbase Interne</span>
                        </button>
                         <button id="nav-btn-inventaire" class="btn btn-glass-nav w-100 text-start d-flex align-items-center gap-3 p-3 rounded-3 transition-all" onclick="switchPreview('inventaire', this)">
                            <i class="fa-solid fa-boxes-stacked text-danger fs-5"></i>
                            <span class="fw-bold text-white-50">Inventaire</span>
                        </button>
                         <button id="nav-btn-rachat" class="btn btn-glass-nav w-100 text-start d-flex align-items-center gap-3 p-3 rounded-3 transition-all" onclick="switchPreview('rachat', this)">
                            <i class="fa-solid fa-hand-holding-dollar text-success fs-5"></i>
                            <span class="fw-bold text-white-50">Rachat</span>
                        </button>
                    </div>
                </div>
            </div> <!-- End row g-4 -->
        </div> <!-- End back -->
    </div> <!-- End flipper -->
</div> <!-- End flip-container -->

                <style>
                /* Flip Animation */
                .flip-container {
                    perspective: 1000px;
                    height: 650px; /* Fixed height for better centering */
                }
                .flipper {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    transform-style: preserve-3d;
                    transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                }
                .flipper.flipped {
                    transform: rotateY(180deg);
                }
                .front, .back {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    top: 0;
                    left: 0;
                    backface-visibility: hidden;
                    -webkit-backface-visibility: hidden;
                }
                .front {
                    z-index: 2;
                    transform: rotateY(0deg);
                }
                .back {
                    transform: rotateY(180deg);
                }
                
                /* Custom Marketing List */
                .marketing-list-item {
                    background: rgba(255, 255, 255, 0.03);
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    border-radius: 12px;
                    padding: 12px 16px;
                    margin-bottom: 12px;
                    transition: all 0.3s ease;
                }
                .marketing-list-item:hover {
                    background: rgba(255, 255, 255, 0.08);
                    transform: translateX(5px);
                    border-color: rgba(56, 189, 248, 0.3);
                }
                .marketing-list-item.active-solution {
                    background: rgba(6, 182, 212, 0.15); /* Cyan/Blue tint */
                    border: 1px solid #06b6d4; /* Neon Cyan Border */
                    box-shadow: 0 0 25px rgba(6, 182, 212, 0.3), inset 0 0 10px rgba(6, 182, 212, 0.1);
                }
                
                /* Custom Scrollbar */
                .custom-scrollbar::-webkit-scrollbar {
                    display: none; /* Chrome, Safari, Opera */
                }
                .custom-scrollbar {
                    -ms-overflow-style: none;  /* IE and Edge */
                    scrollbar-width: none;  /* Firefox */
                }
                .problem-box {
                    background: rgba(239, 68, 68, 0.1);
                    border: 1px solid rgba(239, 68, 68, 0.2);
                    color: #fca5a5;
                }
                .solution-box {
                    background: rgba(34, 197, 94, 0.1);
                    border: 1px solid rgba(34, 197, 94, 0.2);
                    color: #86efac;
                }

                .glass { background: rgba(255,255,255,0.05); }
                .xsmall { font-size: 0.7rem; }
                .btn-glass-nav {
                    background: rgba(255, 255, 255, 0.02);
                    border: 1px solid rgba(255, 255, 255, 0.05);
                }
                .btn-glass-nav:hover {
                    background: rgba(255, 255, 255, 0.1);
                    transform: translateX(5px);
                }
                .btn-glass-nav.active {
                    background: rgba(56, 189, 248, 0.15);
                    border-color: rgba(56, 189, 248, 0.5);
                    box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
                }
                .btn-glass-cat {
                    background: rgba(255, 255, 255, 0.02);
                    border: 1px solid rgba(255, 255, 255, 0.05);
                }
                .btn-glass-cat:hover {
                    background: rgba(255, 255, 255, 0.1);
                    transform: translateX(5px);
                }
                .btn-glass-cat.active {
                    background: rgba(56, 189, 248, 0.15);
                    border-color: rgba(56, 189, 248, 0.5);
                    box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
                }
                .badge-cyan-soft {
    background: rgba(34, 211, 238, 0.15) !important;
    color: #22d3ee !important;
    border: 1px solid rgba(34, 211, 238, 0.3) !important;
}
.badge-warning-soft {
    background: rgba(255, 193, 7, 0.15) !important;
    color: #ffc107 !important;
    border: 1px solid rgba(255, 193, 7, 0.3) !important;
}
                .btn-glass-filter {
                    background: rgba(255,255,255,0.02);
                    border: 1px solid rgba(255,255,255,0.05);
                }
                .btn-glass-filter:hover, .btn-glass-filter.active {
                    background: rgba(255,255,255,0.1);
                    border-color: rgba(255,255,255,0.2);
                    color: white !important;
                }
                
                /* Interactive Elements Hover Effect */
                .line-clamp-2 {
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }
                .hover-scale {
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    cursor: pointer !important;
                }
                .hover-scale:hover {
                    transform: translateY(-2px) scale(1.02);
                    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
                    border-color: rgba(255, 255, 255, 0.3) !important;
                    background: rgba(255, 255, 255, 0.05);
                }
                .hover-scale:active {
                    transform: scale(0.98);
                }

                /* Responsive Rachat Module */
                .custom-table-container {
                    width: 100%;
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                
                .responsive-header-actions {
                    display: flex;
                    gap: 1rem;
                    margin-left: auto;
                }

                @media (max-width: 991px) {
                    .page-header {
                        flex-direction: column;
                        align-items: start;
                    }
                    .responsive-header-actions {
                        width: 100%;
                        overflow-x: auto;
                        padding-bottom: 10px;
                        margin-left: 0;
                        margin-top: 1rem;
                        white-space: nowrap;
                    }
                }
                </style>

                <script>
                function toggleInterfacePreview() {
                    const flipper = document.getElementById('interface-flipper');
                    flipper.classList.toggle('flipped');
                }

                let marketingInterval;
                let currentSlide = 0;

                function updateCarouselState(index) {
                    const slides = document.querySelectorAll('.carousel-slide');
                    const solutions = document.querySelectorAll('#solution-list .marketing-list-item');
                    
                    if(slides.length === 0) return;

                    // Reset all
                    slides.forEach(s => {
                        s.style.opacity = '0';
                        s.style.pointerEvents = 'none';
                    });
                    solutions.forEach(s => s.classList.remove('active-solution'));

                    // Activate target
                    currentSlide = index;
                    if(slides[currentSlide]) {
                        slides[currentSlide].style.opacity = '1';
                        slides[currentSlide].style.pointerEvents = 'auto';
                    }
                    if(solutions[currentSlide]) {
                        solutions[currentSlide].classList.add('active-solution');
                        // Scroll active item to top of list (Container only)
                        const container = document.getElementById('solution-list');
                        if (container) {
                            const itemTop = solutions[currentSlide].getBoundingClientRect().top;
                            const containerTop = container.getBoundingClientRect().top;
                            container.scrollTo({
                                top: container.scrollTop + (itemTop - containerTop),
                                behavior: 'smooth'
                            });
                        }
                    }
                }

                function navNextSlide() {
                    const slides = document.querySelectorAll('.carousel-slide');
                    let next = (currentSlide + 1) % slides.length;
                    updateCarouselState(next);
                }

                function jumpToSlide(index) {
                    clearInterval(marketingInterval);
                    updateCarouselState(index);
                    // Restart interval
                    marketingInterval = setInterval(navNextSlide, 4000);
                }

                function startMarketingCarousel() {
                    updateCarouselState(0); // Init
                    marketingInterval = setInterval(navNextSlide, 4000);
                }

                document.addEventListener('DOMContentLoaded', startMarketingCarousel);

                function switchPreview(tabId, btn) {
                    if (!btn) {
                        console.error('switchPreview called with null button for tabId:', tabId);
                        return;
                    }
                    // Update Buttons
                    document.querySelectorAll('.btn-glass-nav').forEach(b => {
                        b.classList.remove('active');
                        const span = b.querySelector('span');
                        if (span) {
                            span.classList.add('text-white-50');
                            span.classList.remove('text-white');
                        }
                    });
                    btn.classList.add('active');
                    const activeSpan = btn.querySelector('span');
                    if (activeSpan) {
                        activeSpan.classList.remove('text-white-50');
                        activeSpan.classList.add('text-white');
                    }

                    // Switch Content using ID convention preview-{tabId}
                    document.querySelectorAll('.preview-pane').forEach(p => p.style.display = 'none');
                    const target = document.getElementById('preview-' + tabId);
                    if(target) {
                        target.style.display = 'block';
                    }
                }

                function filterRepairs(status, btn) {
                    // Update active state of filter buttons
                    if(btn) {
                        document.querySelectorAll('.btn-glass-filter').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    }
                    window.currentRepairFilter = status; // Store current filter

                    // Filter Logic
                    document.querySelectorAll('.repair-card').forEach(card => {
                        if(status === 'all') {
                            card.style.display = 'block';
                        } else {
                            if(card.getAttribute('data-status') === status) {
                                card.style.display = 'block';
                            } else {
                                card.style.display = 'none';
                            }
                        }
                    });
                }

                // Drag & Drop Logic
                function dragRepair(ev) {
                    ev.dataTransfer.setData("text", ev.target.id);
                    ev.target.style.opacity = "0.5";
                }

                function allowDrop(ev) {
                    ev.preventDefault();
                }

                function highlightDrop(btn) {
                    btn.style.background = "rgba(34, 211, 238, 0.2)"; // Cyan glow
                    btn.style.transform = "scale(1.05)";
                }

                function removeHighlight(btn) {
                    btn.style.background = "";
                    btn.style.transform = "";
                }

                function dropRepair(ev, newStatus) {
                    ev.preventDefault();
                    var data = ev.dataTransfer.getData("text");
                    var card = document.getElementById(data);
                    
                    // Reset styling
                    card.style.opacity = "1";
                    
                    // Find the button (target) to remove highlight
                    // Note: ev.target might be the icon or span, so we look for the closest button
                    var btn = ev.target.closest('button');
                    if(btn) removeHighlight(btn);

                    // Update Card Status
                    card.setAttribute('data-status', newStatus);

                    // Update Badge UI
                    var badgeSpan = card.querySelector('.badge');
                    if(newStatus === 'nouvelle') {
                        badgeSpan.className = 'badge badge-cyan-soft xsmall fw-bold';
                        badgeSpan.innerHTML = 'NOUVELLE';
                    } else if(newStatus === 'en-cours') {
                        badgeSpan.className = 'badge bg-white bg-opacity-10 text-white border border-white border-opacity-20 xsmall';
                        badgeSpan.innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i>EN COURS';
                    } else if(newStatus === 'en-attente') {
                        badgeSpan.className = 'badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20 xsmall';
                        badgeSpan.innerHTML = '<i class="fa-solid fa-hourglass-half me-1"></i>EN ATTENTE';
                    } else if(newStatus === 'termine') {
                        badgeSpan.className = 'badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 xsmall';
                        badgeSpan.innerHTML = '<i class="fa-solid fa-check me-1"></i>TERMINÉ';
                    }

                    // Refresh Filter (if currently filtering, hide the card if it no longer matches)
                    if(window.currentRepairFilter && window.currentRepairFilter !== 'all' && window.currentRepairFilter !== newStatus) {
                        card.style.display = 'none'; // Animate out could be better but basic display none is fast
                    }

                    // Optional: Update Counters (Dummy logic just to show interactivity)
                    var countBadge = btn.querySelector('.rounded-pill');
                    if(countBadge) {
                        var currentCount = parseInt(countBadge.innerText);
                        countBadge.innerText = currentCount + 1;
                        
                        // Add pop animation to counter
                        countBadge.style.transform = "scale(1.5)";
                        setTimeout(() => countBadge.style.transform = "scale(1)", 200);
                    }
                }
                </script>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Social Proof (Updated) -->
<section class="border-top border-bottom border-white border-opacity-10 bg-dark bg-opacity-50 py-4">
    <div class="container">
        <div class="row align-items-center">
            <!-- Stats -->
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="d-flex justify-content-around">
                    <div class="text-center">
                        <div class="h2 fw-black text-white mb-0">5000+</div>
                        <div class="small text-secondary text-uppercase tracking-wide">Réparations/Mois</div>
                    </div>
                    <div class="text-center border-start border-white border-opacity-10 ps-4">
                        <div class="h2 fw-black text-white mb-0">150+</div>
                        <div class="small text-secondary text-uppercase tracking-wide">Ateliers actifs</div>
                    </div>
                </div>
            </div>
            <!-- Trust Badges -->
            <div class="col-lg-7">
                <div class="d-flex justify-content-center justify-content-lg-end gap-5 align-items-center opacity-50 grayscale hover-color transition-all">
                    <i class="fa-brands fa-apple fa-2x"></i>
                    <i class="fa-brands fa-android fa-2x"></i>
                    <i class="fa-brands fa-windows fa-2x"></i>
                    <i class="fa-brands fa-aws fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Grid (Futuristic) -->
<section class="section py-5 position-relative">
    <div class="container py-5">
        <div class="text-center mb-5 pb-4">
            <span class="badge border border-primary text-primary rounded-pill mb-3 px-3 py-2">ECOSYSTEME COMPLET</span>
            <h2 class="display-5 fw-black text-white mb-3">Toute la puissance.<br>Zéro complexité.</h2>
            <p class="text-secondary fs-5">Une suite d'outils interconnectés pour dominer votre marché.</p>
        </div>
        
        <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 rounded-4 h-100 position-relative">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-comments text-primary" aria-hidden="true"></i>
                    </div>
                    <h3 class="h4 text-white fw-bold mb-3">SMS & Comms</h3>
                    <p class="text-secondary mb-4">Automatisez vos relances clients. Campagnes marketing ciblées et notifications de statut en temps réel.</p>
                    <a href="/sms-automatiques" class="text-primary fw-bold text-decoration-none stretched-link" aria-label="En savoir plus sur les SMS automatiques">
                        Explorer <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            
            <!-- Feature 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 rounded-4 h-100 position-relative">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-qrcode text-success" aria-hidden="true"></i>
                    </div>
                    <h3 class="h4 text-white fw-bold mb-3">Pointage & RH</h3>
                    <p class="text-secondary mb-4">Suivi des temps par QR Code. Gestion des plannings, retards et productivité d'équipe.</p>
                    <a href="/pointage-employes" class="text-success fw-bold text-decoration-none stretched-link" aria-label="Découvrir la gestion RH">
                        Explorer <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            
            <!-- Feature 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 rounded-4 h-100 position-relative">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-boxes-stacked text-warning" aria-hidden="true"></i>
                    </div>
                    <h3 class="h4 text-white fw-bold mb-3">Stock & Fournisseurs</h3>
                    <p class="text-secondary mb-4">Commandes centralisées. Inventaire temps réel et catalogue multi-fournisseurs intégré.</p>
                    <a href="/catalogue-fournisseurs" class="text-warning fw-bold text-decoration-none stretched-link" aria-label="Voir le catalogue fournisseurs">
                        Explorer <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            
             <!-- Feature 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 rounded-4 h-100 position-relative">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-brain text-info" aria-hidden="true"></i>
                    </div>
                    <h3 class="h4 text-white fw-bold mb-3">IA Assistant</h3>
                    <p class="text-secondary mb-4">Base de connaissance intelligente. Réponses instantanées à vos questions techniques.</p>
                    <a href="/base-connaissances-ia" class="text-info fw-bold text-decoration-none stretched-link" aria-label="Découvrir l'assistant IA">
                        Explorer <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            
             <!-- Feature 5 -->
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 rounded-4 h-100 position-relative">
                    <div class="feature-icon-wrapper">
                        <i class="fa-solid fa-chart-pie text-danger" aria-hidden="true"></i>
                    </div>
                    <h3 class="h4 text-white fw-bold mb-3">Analytics 360°</h3>
                    <p class="text-secondary mb-4">Tableaux de bord financiers, performance technicien et suivi de croissance.</p>
                    <a href="/analytics-kpi" class="text-danger fw-bold text-decoration-none stretched-link" aria-label="Voir les outils d'analytics">
                        Explorer <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            
             <!-- Feature 6 -->
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 rounded-4 h-100 position-relative d-flex align-items-center justify-content-center text-center border-dashed">
                    <div>
                        <div class="mb-3 text-secondary opactiy-50 display-4">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <h5 class="text-white fw-bold">Et bien plus encore...</h5>
                        <a href="/features" class="btn btn-outline-light btn-sm mt-3 rounded-pill">Tout voir</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call To Action -->
<section class="py-5 my-5">
    <div class="container">
        <div class="glass-card rounded-5 p-5 text-center position-relative overflow-hidden border border-primary border-opacity-50">
            <div class="position-absolute top-0 start-0 w-100 h-100 bg-gradient-primary opacity-10"></div>
            
            <div class="position-relative z-1 py-4">
                <h2 class="display-4 fw-black text-white mb-4">Prêt pour le futur ?</h2>
                <p class="fs-4 text-secondary mb-5 max-w-2xl mx-auto">
                    Rejoignez les 150+ ateliers qui ont déjà modernisé leur gestion.
                    <br>30 jours gratuits. Sans carte bancaire.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="/inscription" class="btn btn-glow btn-lg px-5 rounded-pill">
                        COMMENCER MAINTENANT
                    </a>
                </div>
                <div class="mt-4 text-secondary small">
                    <i class="fa-solid fa-lock me-1"></i> Données chiffrées & Sécurisées
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Repair Detail Modal (Hidden by default) -->
<div id="repair-modal-overlay" class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center fade-in" 
     style="background: rgba(0,0,0,0.8); z-index: 9999; display: none; backdrop-filter: blur(5px);">
    
    <div class="glass-modal rounded-4 overflow-hidden d-flex flex-column animate-pop" style="width: 95%; max-width: 1000px; height: 90vh; border: 1px solid rgba(255,255,255,0.1); background: #0f172a;">
        
        <!-- Modal Header -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-white border-opacity-10 bg-dark bg-opacity-50">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-wrench text-white fs-5"></i>
                <div>
                    <div class="fw-bold text-white fs-5">Réparation <span id="modal-id">#1783</span></div>
                    <div class="xsmall text-white-50" id="modal-subtitle">Informaticos Redmi m1901f7g - Nouvelle Intervention</div>
                </div>
            </div>
            <button class="btn btn-link text-white-50 p-0" onclick="closeRepairModal()">
                <i class="fa-solid fa-xmark fs-4"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-4 overflow-auto custom-scrollbar" style="flex: 1;">
            
            <!-- Quick Actions -->
            <div class="mb-4">
                <div class="xsmall text-primary fw-bold mb-2"><i class="fa-solid fa-bolt me-1"></i>Actions rapides</div>
                <div class="d-flex gap-2 mb-3 overflow-auto pb-1">
                    <button class="btn btn-dark border border-white border-opacity-10 text-white-50 flex-grow-1 py-2 xsmall rounded-3">
                        <i class="fa-solid fa-file-invoice d-block mb-1 fs-5"></i> DEVIS
                    </button>
                    <button class="btn btn-dark border border-white border-opacity-10 text-white-50 flex-grow-1 py-2 xsmall rounded-3">
                        <i class="fa-solid fa-list-check d-block mb-1 fs-5"></i> STATUT
                    </button>
                    <button class="btn btn-dark border border-white border-opacity-10 text-white-50 flex-grow-1 py-2 xsmall rounded-3">
                        <i class="fa-solid fa-euro-sign d-block mb-1 fs-5"></i> PRIX
                    </button>
                    <button class="btn btn-dark border border-white border-opacity-10 text-white-50 flex-grow-1 py-2 xsmall rounded-3">
                        <i class="fa-solid fa-cart-shopping d-block mb-1 fs-5"></i> COMMANDER
                    </button>
                    <button class="btn btn-dark border border-white border-opacity-10 text-white-50 flex-grow-1 py-2 xsmall rounded-3">
                        <i class="fa-solid fa-print d-block mb-1 fs-5"></i> IMPRIMER
                    </button>
                    <button class="btn btn-dark border border-white border-opacity-10 text-white-50 flex-grow-1 py-2 xsmall rounded-3">
                        <i class="fa-solid fa-clock-rotate-left d-block mb-1 fs-5"></i> HISTORIQUE
                    </button>
                </div>
                <button class="btn btn-success w-100 fw-bold py-2 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: #10b981; border:none;">
                    <i class="fa-solid fa-circle-play"></i> DÉMARRER LA RÉPARATION
                </button>
            </div>

            <!-- Informatoins -->
            <div class="mb-4">
                <div class="xsmall text-primary fw-bold mb-2"><i class="fa-solid fa-circle-info me-1"></i>Informations</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 p-2 rounded-3 border border-white border-opacity-5">
                            <div class="xsmall text-primary fw-bold text-uppercase mb-1">Client</div>
                            <div class="text-white d-flex align-items-center gap-2">
                                <i class="fa-solid fa-user text-white-50"></i> <span id="modal-client">Thomas Martin</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 p-2 rounded-3 border border-white border-opacity-5">
                            <div class="xsmall text-white-50 text-uppercase mb-1">Téléphone</div>
                            <div class="text-white d-flex align-items-center gap-2">
                                <i class="fa-solid fa-phone text-white-50"></i> <span id="modal-phone">06 12 34 56 78</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 p-2 rounded-3 border border-white border-opacity-5">
                            <div class="xsmall text-white-50 text-uppercase mb-1">Appareil</div>
                            <div class="text-white d-flex align-items-center gap-2">
                                <i class="fa-solid fa-mobile-screen text-white-50"></i> <span id="modal-device">Redmi Note 10</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 p-2 rounded-3 border border-white border-opacity-5">
                            <div class="xsmall text-white-50 text-uppercase mb-1">Créé par</div>
                            <div class="text-white d-flex align-items-center gap-2">
                                <i class="fa-solid fa-user-gear text-white-50"></i> Benjamin, le 27/12/2025 à 11:20
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 p-2 rounded-3 border border-white border-opacity-5">
                            <div class="xsmall text-white-50 text-uppercase mb-1">Statut</div>
                            <div class="text-white d-flex align-items-center gap-2">
                                <i class="fa-solid fa-flag text-white-50"></i> Nouvelle Intervention
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 p-2 rounded-3 border border-white border-opacity-5">
                            <div class="xsmall text-white-50 text-uppercase mb-1">Prix</div>
                            <div class="text-white d-flex align-items-center gap-2">
                                <i class="fa-solid fa-euro-sign text-white-50"></i> 0.00 €
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="xsmall text-primary fw-bold"><i class="fa-solid fa-align-left me-1"></i>Description du problème</div>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2 xsmall border-opacity-25 text-white-50"><i class="fa-solid fa-pen me-1"></i>Modifier</button>
                </div>
                <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-white border-opacity-5 text-white xsmall" id="modal-problem">
                    Remplacement caméra avant + carte sd 64gb 19.99
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="xsmall text-primary fw-bold"><i class="fa-regular fa-note-sticky me-1"></i>Notes Internes</div>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2 xsmall border-opacity-25 text-white-50"><i class="fa-solid fa-pen me-1"></i>Modifier</button>
                </div>
                <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-white border-opacity-5 text-white-50 fst-italic xsmall">
                    Aucune note interne
                </div>
            </div>

             <!-- Photos -->
             <div class="mb-2">
                <div class="xsmall text-primary fw-bold mb-2"><i class="fa-regular fa-images me-1"></i>Photos (1)</div>
                <div class="d-flex gap-2">
                    <div class="rounded-3 overflow-hidden position-relative border border-white border-opacity-10" style="width: 80px; height: 80px;">
                        <img src="https://images.unsplash.com/photo-1592434134753-a70baf7979d5?w=150&q=80" class="w-100 h-100 object-fit-cover" alt="Photo">
                        <div class="position-absolute bottom-0 start-0 w-100 bg-black bg-opacity-50 text-white text-center py-1" style="font-size: 8px;">APPAREIL</div>
                    </div>
                    <div class="rounded-3 border border-white border-opacity-10 d-flex flex-column align-items-center justify-content-center text-white-50 cursor-pointer hover-bg-white-5" style="width: 80px; height: 80px; border-style: dashed !important;">
                        <i class="fa-solid fa-plus mb-1"></i>
                        <div style="font-size: 8px;">Ajouter</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.glass-modal {
    box-shadow: 0 0 50px rgba(0,0,0,0.5);
}
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 3px;
}
.animate-pop {
    animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes popIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.hover-bg-white-5:hover { background: rgba(255,255,255,0.05); }
</style>

<script>
function openRepairModal(cardId, clientName, device, problem) {
     // Populate basic info (Showcase purpose)
     if(cardId) document.getElementById('modal-id').innerText = '#' + cardId;
     if(clientName) document.getElementById('modal-client').innerText = clientName;
     if(device) {
         document.getElementById('modal-device').innerText = device;
         document.getElementById('modal-subtitle').innerText = device + ' - Nouvelle Intervention';
     }
     if(problem) document.getElementById('modal-problem').innerText = problem;

    const overlay = document.getElementById('repair-modal-overlay');
    overlay.style.display = 'flex';
}

function closeRepairModal() {
    const overlay = document.getElementById('repair-modal-overlay');
    overlay.style.display = 'none';
}

// Close on click outside
document.getElementById('repair-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeRepairModal();
});
</script>

<!-- MODAL: AJOUTER COMMANDE (MOCKUP) -->
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1" aria-labelledby="ajouterCommandeModalLabel" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content glass border border-white border-opacity-10 shadow-lg" style="background: rgba(17, 24, 39, 0.95);">
            <!-- En-tête -->
            <div class="modal-header border-bottom border-white border-opacity-10 bg-warning bg-opacity-10">
                <h2 class="modal-title text-warning fs-5"><i class="fas fa-shopping-cart me-2"></i> Nouvelle commande de pièces</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps -->
            <div class="modal-body p-4">
                <form id="ajouterCommandeForm">
                    <!-- Section Client -->
                    <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-5 mb-3">
                        <div class="text-white fw-bold mb-3">
                            <i class="fas fa-user-circle text-primary me-2"></i> Client
                        </div>
                        <div class="form-group mb-3">
                            <input type="text" class="form-control bg-dark border-secondary text-white" id="nom_client_selectionne" placeholder="Saisir ou rechercher un client" value="M. Thomas Martin">
                            <button type="button" class="btn btn-outline-primary w-100 mt-2 btn-sm opacity-50">
                                <i class="fas fa-user-plus me-2"></i>+Créer un nouveau client
                            </button>
                        </div>
                    </div>

                    <!-- Section Fournisseur -->
                    <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-5 mb-3">
                        <div class="text-white fw-bold mb-3">
                            <i class="fas fa-truck text-info me-2"></i> Fournisseur
                        </div>
                        <div class="form-group">
                            <select class="form-select bg-dark border-secondary text-white" name="fournisseur_id" id="fournisseur_id_ajout">
                                <option value="">Sélectionner un fournisseur...</option>
                                <option value="1" selected>Utopya</option>
                                <option value="2">Mobilax</option>
                                <option value="3">SOSav</option>
                            </select>
                        </div>
                    </div>

                    <!-- Section Pièce -->
                    <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-5 mb-3">
                        <div class="text-white fw-bold mb-3">
                            <i class="fas fa-cog text-secondary me-2"></i> Pièce commandée
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <input type="text" class="form-control bg-dark border-secondary text-white" name="nom_piece" placeholder="Désignation" value="Ecran iPhone 13">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control bg-dark border-secondary text-white" name="code_barre" placeholder="Code barre">
                            </div>
                            <div class="col-md-2">
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary btn-sm" type="button">-</button>
                                    <input type="text" class="form-control bg-dark border-secondary text-white text-center p-0" value="1">
                                    <button class="btn btn-outline-secondary btn-sm" type="button">+</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                    <input type="text" class="form-control bg-dark border-secondary text-white" placeholder="Prix" value="89.00">
                            </div>
                        </div>
                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-link text-decoration-none text-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Ajouter une autre pièce
                            </button>
                        </div>
                    </div>

                    <!-- Section Statut -->
                    <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-5 mb-3">
                        <div class="text-white fw-bold mb-3">
                            <i class="fas fa-info-circle text-warning me-2"></i> Statut
                        </div>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="statut" id="statusPending" checked>
                                <label class="form-check-label text-white" for="statusPending">En attente</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="statut" id="statusOrdered">
                                <label class="form-check-label text-white" for="statusOrdered">Commandé</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="statut" id="statusReceived">
                                <label class="form-check-label text-white" for="statusReceived">Reçu</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-top border-white border-opacity-10 p-3">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="simulateSaveOrder()">
                    <i class="fas fa-save me-2"></i> Enregistrer la commande
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: AJOUTER REPARATION (MOCKUP) -->
<div class="modal fade" id="ajouterReparationModal" tabindex="-1" aria-labelledby="ajouterReparationModalLabel" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content glass border border-white border-opacity-10 shadow-lg" style="background: rgba(17, 24, 39, 0.95);">
            <!-- En-tête -->
            <div class="modal-header border-bottom border-white border-opacity-10 bg-info bg-opacity-10">
                <h2 class="modal-title text-info fs-5"><i class="fas fa-wrench me-2"></i> Nouvelle réparation</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps -->
            <div class="modal-body p-4">
                <form id="ajouterReparationForm">
                    <!-- Progress Bar -->
                    <div class="progress mb-4" style="height: 5px; background: rgba(255,255,255,0.1);">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <!-- Étape 1: Type d'appareil -->
                    <div id="step1" class="form-step">
                        <h5 class="text-white mb-3">Type d'appareil</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-4 rounded-3 bg-dark bg-opacity-50 border border-info border-opacity-25 text-center cursor-pointer hover-bg-white-5 transition-all" onclick="selectDeviceType('Informatique')">
                                    <i class="fas fa-laptop fa-3x text-info mb-3"></i>
                                    <h5 class="text-white mb-1">Informatique</h5>
                                    <p class="text-white-50 small mb-0">Ordinateur, téléphone, tablette...</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-4 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-10 text-center cursor-pointer hover-bg-white-5 transition-all opacity-50" onclick="selectDeviceType('Trottinette')">
                                    <i class="fas fa-bolt fa-3x text-white mb-3"></i>
                                    <h5 class="text-white mb-1">Trottinette</h5>
                                    <p class="text-white-50 small mb-0">Électrique, accessoires...</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-primary" onclick="nextStep(2)" id="btnStep1" disabled>Suivant <i class="fas fa-arrow-right ms-2"></i></button>
                        </div>
                    </div>

                    <!-- Étape 2: Client (Mock) -->
                    <div id="step2" class="form-step d-none">
                         <h5 class="text-white mb-3">Client</h5>
                         <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-5 mb-3">
                            <label class="text-white small mb-2">Rechercher un client</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-dark border-secondary text-white"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control bg-dark border-secondary text-white" placeholder="Nom, prénom, téléphone..." value="M. Thomas Martin">
                                <button class="btn btn-primary" type="button">Rechercher</button>
                            </div>
                            
                            <div class="alert alert-info border-info border-opacity-25 bg-info bg-opacity-10 text-white">
                                <div class="d-flex align-items-center">
                                    <div class="me-3"><i class="fas fa-user-check fa-2x"></i></div>
                                    <div>
                                        <strong>Client trouvé :</strong><br>
                                        M. Thomas Martin<br>
                                        <small class="text-white-50">06 12 34 56 78</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-light" onclick="prevStep(1)">Précédent</button>
                            <button type="button" class="btn btn-primary" onclick="nextStep(3)">Suivant <i class="fas fa-arrow-right ms-2"></i></button>
                        </div>
                    </div>

                     <!-- Étape 3: Infos (Mock) -->
                     <div id="step3" class="form-step d-none">
                        <h5 class="text-white mb-3">Informations Appareil</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-white small mb-1">Modèle</label>
                                <input type="text" class="form-control bg-dark border-secondary text-white" value="iPhone 13">
                            </div>
                            <div class="col-md-6">
                                <label class="text-white small mb-1">Mot de passe</label>
                                <input type="text" class="form-control bg-dark border-secondary text-white" value="123456">
                            </div>
                            <div class="col-12">
                                <label class="text-white small mb-1">Description du problème</label>
                                <textarea class="form-control bg-dark border-secondary text-white" rows="3">L'écran est cassé suite à une chute.</textarea>
                            </div>
                        </div>
                         <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-light" onclick="prevStep(2)">Précédent</button>
                            <button type="button" class="btn btn-primary" onclick="simulateSaveReparation()">Enregistrer <i class="fas fa-save ms-2"></i></button>
                        </div>
                     </div>

                </form>
            </div>
            
            <script>
            function selectDeviceType(type) {
                // Visual feedback for selection
                document.getElementById('btnStep1').disabled = false;
                // In a real app we would store the selection
            }

            function nextStep(step) {
                document.querySelectorAll('.form-step').forEach(el => el.classList.add('d-none'));
                document.getElementById('step' + step).classList.remove('d-none');
                const progress = step === 1 ? 25 : (step === 2 ? 60 : 100);
                document.querySelector('#ajouterReparationModal .progress-bar').style.width = progress + '%';
            }
            
             function prevStep(step) {
                document.querySelectorAll('.form-step').forEach(el => el.classList.add('d-none'));
                document.getElementById('step' + step).classList.remove('d-none');
                const progress = step === 1 ? 25 : (step === 2 ? 60 : 100);
                document.querySelector('#ajouterReparationModal .progress-bar').style.width = progress + '%';
            }

            function openReparationModal() {
                // Reset to step 1
                nextStep(1);
                document.getElementById('btnStep1').disabled = true;
                const modal = new bootstrap.Modal(document.getElementById('ajouterReparationModal'));
                modal.show();
            }

            function simulateSaveReparation() {
                const btn = document.querySelector('#step3 .btn-primary');
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Enregistrement...';
                
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterReparationModal'));
                    modal.hide();
                    
                    // Show a toast or feedback (Optional)
                    // alert('Réparation enregistrée (Simulation)');
                    
                }, 1000);
            }
            </script>

        </div>
    </div>
</div>

<!-- MODAL: STATISTIQUES AVANCEES (MOCKUP) -->
<div class="modal fade" id="advancedStatsModal" tabindex="-1" aria-labelledby="advancedStatsModalLabel" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content glass border border-white border-opacity-10 shadow-lg" style="background: rgba(17, 24, 39, 0.95);">
            <!-- En-tête -->
            <div class="modal-header border-bottom border-white border-opacity-10 bg-primary bg-opacity-10">
                <h5 class="modal-title text-white" id="advancedStatsModalLabel">
                    <i class="fas fa-chart-line text-primary me-2"></i> Statistiques Détaillées
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps -->
            <div class="modal-body p-4">
                <!-- Résumé des chiffres (Requested Stats) -->
                <div class="row g-4 mb-4">
                    <!-- Nouvelles -->
                    <div class="col-md-3">
                        <div class="p-4 rounded-3 bg-dark bg-opacity-50 border border-info border-opacity-25 text-center">
                            <div class="fs-1 fw-bold text-info mb-1">5</div>
                            <div class="text-white-50 small text-uppercase fw-bold ls-1">Nouvelles</div>
                        </div>
                    </div>
                    <!-- Effectuées -->
                    <div class="col-md-3">
                        <div class="p-4 rounded-3 bg-dark bg-opacity-50 border border-success border-opacity-25 text-center">
                            <div class="fs-1 fw-bold text-success mb-1">12</div>
                            <div class="text-white-50 small text-uppercase fw-bold ls-1">Effectuées</div>
                        </div>
                    </div>
                    <!-- Restituées -->
                    <div class="col-md-3">
                        <div class="p-4 rounded-3 bg-dark bg-opacity-50 border border-primary border-opacity-25 text-center">
                            <div class="fs-1 fw-bold text-primary mb-1">8</div>
                            <div class="text-white-50 small text-uppercase fw-bold ls-1">Restituées</div>
                        </div>
                    </div>
                    <!-- Devis -->
                    <div class="col-md-3">
                        <div class="p-4 rounded-3 bg-dark bg-opacity-50 border border-warning border-opacity-25 text-center">
                            <div class="fs-1 fw-bold text-warning mb-1">3</div>
                            <div class="text-white-50 small text-uppercase fw-bold ls-1">Devis</div>
                        </div>
                    </div>
                </div>

                <!-- Graphique (Mock) -->
                <!-- Graphique (Mock: Courbe SVG) -->
                <div class="p-4 rounded-3 bg-dark bg-opacity-30 border border-white border-opacity-5">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <h6 class="text-white mb-0"><i class="fas fa-chart-area me-2 text-white-50"></i>Activité sur 7 jours</h6>
                        <span class="badge bg-success bg-opacity-20 text-success rounded-pill small">+12% vs semaine dernière</span>
                    </div>
                    
                    <div style="height: 250px; position: relative; width: 100%;">
                        <!-- SVG Chart -->
                        <svg viewBox="0 0 800 250" class="w-100 h-100" preserveAspectRatio="none" style="overflow: visible;">
                            <!-- Gradient Definition -->
                            <defs>
                                <linearGradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.3"/>
                                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            
                            <!-- Grid Lines -->
                            <line x1="0" y1="200" x2="800" y2="200" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
                            <line x1="0" y1="150" x2="800" y2="150" stroke="rgba(255,255,255,0.05)" stroke-width="1" stroke-dasharray="4" />
                            <line x1="0" y1="100" x2="800" y2="100" stroke="rgba(255,255,255,0.05)" stroke-width="1" stroke-dasharray="4" />
                            <line x1="0" y1="50" x2="800" y2="50" stroke="rgba(255,255,255,0.05)" stroke-width="1" stroke-dasharray="4" />
                            
                            <!-- Area Path -->
                            <path d="M0,200 L0,120 Q100,60 133,100 T266,120 T400,50 T533,90 T666,80 T800,140 L800,200 Z" 
                                  fill="url(#chartGradient)" />
                            
                            <!-- Line Path -->
                            <path d="M0,120 Q100,60 133,100 T266,120 T400,50 T533,90 T666,80 T800,140" 
                                  fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" 
                                  filter="drop-shadow(0 0 4px rgba(59, 130, 246, 0.5))"/>
                            
                            <!-- Points -->
                            <circle cx="0" cy="120" r="4" fill="#3b82f6" stroke="#1e293b" stroke-width="2" />
                            <circle cx="133" cy="100" r="4" fill="#3b82f6" stroke="#1e293b" stroke-width="2" />
                            <circle cx="266" cy="120" r="4" fill="#3b82f6" stroke="#1e293b" stroke-width="2" />
                            <circle cx="400" cy="50" r="5" fill="#fff" stroke="#3b82f6" stroke-width="3" />
                            <circle cx="533" cy="90" r="4" fill="#3b82f6" stroke="#1e293b" stroke-width="2" />
                            <circle cx="666" cy="80" r="4" fill="#3b82f6" stroke="#1e293b" stroke-width="2" />
                            <circle cx="800" cy="140" r="4" fill="#3b82f6" stroke="#1e293b" stroke-width="2" />
                        </svg>
                        
                         <!-- Axis Labels -->
                        <div class="d-flex justify-content-between text-white-50 xsmall mt-2 font-monospace px-2">
                             <span>Lun</span>
                             <span>Mar</span>
                             <span>Mer</span>
                             <span>Jeu</span>
                             <span>Ven</span>
                             <span>Sam</span>
                             <span>Dim</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- MODAL: COMMANDE STATUT (MOCKUP) -->
<div class="modal fade" id="commandeStatutModal" tabindex="-1" aria-labelledby="commandeStatutModalLabel" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass border border-white border-opacity-10 shadow-lg" style="background: rgba(17, 24, 39, 0.95);">
            <!-- En-tête -->
            <div class="modal-header border-bottom border-white border-opacity-10 bg-primary bg-opacity-10">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-20 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-exchange-alt text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-white mb-0" id="commandeStatutModalLabel">Changer le statut</h5>
                        <p class="mb-0 text-white-50 small">Mettre à jour le statut de la commande</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps -->
            <div class="modal-body p-4">
                <div class="mb-4">
                     <h4 class="text-white mb-1" id="mock-cmd-title">Ecran iPhone 13</h4>
                     <p class="text-white-50 mb-3" id="mock-cmd-provider">Mobilax</p>
                     <div class="d-flex align-items-center gap-2">
                        <span class="text-white-50 small">Statut actuel:</span>
                        <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 rounded-pill">En attente</span>
                     </div>
                </div>

                <!-- Options Grid -->
                <div class="row g-3">
                    <!-- En Attente -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-10 hover-scale cursor-pointer d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning bg-opacity-20 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-clock text-warning"></i>
                            </div>
                            <div>
                                <div class="text-white fw-bold">En attente</div>
                                <div class="text-white-50 xsmall">Commande en attente de traitement</div>
                            </div>
                        </div>
                    </div>
                    <!-- Commandé -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-10 hover-scale cursor-pointer d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-20 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-shopping-cart text-primary"></i>
                            </div>
                            <div>
                                <div class="text-white fw-bold">Commandé</div>
                                <div class="text-white-50 xsmall">Commande passée chez le fournisseur</div>
                            </div>
                        </div>
                    </div>
                    <!-- Reçu -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-10 hover-scale cursor-pointer d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-success bg-opacity-20 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-check text-success"></i>
                            </div>
                            <div>
                                <div class="text-white fw-bold">Reçu</div>
                                <div class="text-white-50 xsmall">Pièce reçue et en stock</div>
                            </div>
                        </div>
                    </div>
                     <!-- Annulé -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-white border-opacity-10 hover-scale cursor-pointer d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-danger bg-opacity-20 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-times text-danger"></i>
                            </div>
                            <div>
                                <div class="text-white fw-bold">Annulé</div>
                                <div class="text-white-50 xsmall">Commande annulée</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
             <div class="modal-footer border-top border-white border-opacity-10 bg-dark bg-opacity-30">
                <button type="button" class="btn btn-glass-nav text-white" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- MODAL: DETAILS TACHE (MOCKUP) -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass border border-white border-opacity-10 shadow-lg" style="background: rgba(17, 24, 39, 0.95);">
            <!-- En-tête -->
            <div class="modal-header border-bottom border-0 pb-0">
               <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-list-ul text-primary fs-4"></i>
                    </div>
                    <div>
                        <h4 class="modal-title text-white fw-bold mb-1">Détails de la tâche</h4>
                        <p class="mb-0 text-white-50">Informations complètes</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps -->
            <div class="modal-body p-4">
                <h3 class="text-white fw-bold mb-4" id="mock-task-title">asd</h3>
                
                <div class="d-flex gap-5 mb-4">
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase mb-2">PRIORITÉ</div>
                        <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-2" id="mock-task-priority">Moyenne</span>
                    </div>
                     <div>
                        <div class="text-secondary small fw-bold text-uppercase mb-2">STATUT</div>
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 rounded-pill px-3 py-2" id="mock-task-status">Terminé</span>
                    </div>
                </div>

                <div class="mb-4">
                     <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="bg-info rounded p-1"><i class="fas fa-file-alt text-white small"></i></div>
                        <div class="text-info fw-bold text-uppercase tracking-wider">DESCRIPTION</div>
                     </div>
                     <div class="bg-dark bg-opacity-50 p-4 rounded-3 text-white">
                         <p class="mb-0" id="mock-task-desc">das</p>
                     </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-3 bg-dark bg-opacity-30 p-3 rounded-3">
                             <div class="rounded-3 bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="far fa-calendar-alt text-white"></i>
                            </div>
                            <div>
                                <div class="text-secondary xsmall fw-bold text-uppercase">DATE DE CRÉATION</div>
                                <div class="text-white fw-bold" id="mock-task-date">21/12/2025 à 00:19</div>
                            </div>
                        </div>
                    </div>
                     <div class="col-md-6">
                        <div class="d-flex align-items-center gap-3 bg-dark bg-opacity-30 p-3 rounded-3">
                             <div class="rounded-3 bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <div class="text-secondary xsmall fw-bold text-uppercase">ASSIGNÉ À</div>
                                <div class="text-white fw-bold" id="mock-task-assignee">Non assigné</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-3">
                    <button class="btn btn-success p-3 rounded-3 d-flex align-items-center gap-3 flex-grow-1">
                        <div class="bg-white bg-opacity-25 rounded d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                             <i class="fas fa-play text-white xsmall"></i>
                        </div>
                        <div class="text-start">
                             <div class="fw-bold lh-1">Démarrer</div>
                             <div class="xsmall text-white-50">Commencer la tâche</div>
                        </div>
                    </button>
                    <button class="btn btn-primary p-3 rounded-3 d-flex align-items-center gap-3 flex-grow-1">
                        <div class="bg-white bg-opacity-25 rounded d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                             <i class="fas fa-check text-white xsmall"></i>
                        </div>
                        <div class="text-start">
                             <div class="fw-bold lh-1">Terminer</div>
                             <div class="xsmall text-white-50">Marquer comme fini</div>
                        </div>
                    </button>
                    <button class="btn btn-warning p-3 rounded-3 d-flex align-items-center gap-3 flex-grow-1">
                        <div class="bg-white bg-opacity-25 rounded d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                             <i class="fas fa-edit text-white xsmall"></i>
                        </div>
                        <div class="text-start">
                             <div class="fw-bold lh-1">Modifier</div>
                             <div class="xsmall text-white-50">Éditer la tâche</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- MODAL: RACHAT DETAILS (MOCKUP) -->
<div class="modal fade" id="rachatDetailsModal" tabindex="-1" aria-labelledby="rachatDetailsModalLabel" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg" style="background: #1e293b;">
            <!-- Header Blue -->
            <div class="modal-header border-0 py-3 px-4" style="background: #3b82f6;">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="far fa-eye fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0">Détails du rachat</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Body Dark -->
            <div class="modal-body p-4">
                <!-- Info Row -->
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fas fa-user text-white-50"></i>
                            <span class="text-white fw-bold">Client: <span id="rachat-client-name">guezquez saber</span></span>
                        </div>
                        <div class="text-white-50 small">Date: <span id="rachat-date">18/12/2025</span></div>
                    </div>
                    <div class="text-end">
                        <div class="text-success fw-bold fs-4" id="rachat-price">80.00 €</div>
                        <span class="badge bg-success rounded-pill px-3" id="rachat-status">NOUVEAU</span>
                    </div>
                </div>

                <!-- Grid Images -->
                <div class="row g-4">
                    <!-- 1. Piece d'identité -->
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 border border-white border-opacity-10 rounded-3 overflow-hidden h-100">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-white border-opacity-10">
                                <span class="text-white fw-bold"><i class="far fa-id-card me-2 text-primary"></i>Pièce d'identité</span>
                                <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50"><i class="fas fa-download"></i></button>
                            </div>
                            <div class="p-4 d-flex align-items-center justify-content-center" style="height: 300px; background: #0f172a;">
                                <img src="https://placehold.co/400x300/1e293b/white?text=ID+Card" class="img-fluid rounded-3 shadow-sm" style="max-height: 100%; object-fit: contain;">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Photo Appareil -->
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 border border-white border-opacity-10 rounded-3 overflow-hidden h-100">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-white border-opacity-10">
                                <span class="text-white fw-bold"><i class="fas fa-mobile-alt me-2 text-info"></i>Photo de l'appareil</span>
                                <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50"><i class="fas fa-download"></i></button>
                            </div>
                            <div class="p-4 d-flex align-items-center justify-content-center" style="height: 300px; background: #0f172a;">
                                <img src="https://placehold.co/400x300/1e293b/white?text=Device+Photo" class="img-fluid rounded-3 shadow-sm" style="max-height: 100%; object-fit: contain;">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Photo Client -->
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 border border-white border-opacity-10 rounded-3 overflow-hidden h-100">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-white border-opacity-10">
                                <span class="text-white fw-bold"><i class="fas fa-user me-2 text-primary"></i>Photo du client</span>
                                <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50"><i class="fas fa-download"></i></button>
                            </div>
                            <div class="p-4 d-flex align-items-center justify-content-center" style="height: 300px; background: #0f172a;">
                                <img src="https://placehold.co/400x300/1e293b/white?text=Client+Photo" class="img-fluid rounded-3 shadow-sm" style="max-height: 100%; object-fit: contain;">
                            </div>
                        </div>
                    </div>

                    <!-- 4. Signature -->
                    <div class="col-md-6">
                        <div class="bg-dark bg-opacity-50 border border-white border-opacity-10 rounded-3 overflow-hidden h-100">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-white border-opacity-10">
                                <span class="text-white fw-bold"><i class="fas fa-signature me-2 text-info"></i>Signature du client</span>
                                <button class="btn btn-sm btn-dark border border-white border-opacity-10 text-white-50"><i class="fas fa-download"></i></button>
                            </div>
                            <div class="p-4 d-flex align-items-center justify-content-center" style="height: 300px; background: #0f172a;">
                                <img src="https://placehold.co/400x200/0f172a/white?text=Signature" class="img-fluid rounded-3 shadow-sm" style="max-height: 100%; object-fit: contain; filter: invert(1);">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-top border-white border-opacity-10 bg-dark bg-opacity-50">
                <button type="button" class="btn btn-dark border border-white border-opacity-10 text-white" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function openRachatModal(client, date, price, status) {
    if(client) document.getElementById('rachat-client-name').textContent = client;
    if(date) document.getElementById('rachat-date').textContent = date;
    if(price) document.getElementById('rachat-price').textContent = price;
    
    // Show Modal
    const modal = new bootstrap.Modal(document.getElementById('rachatDetailsModal'));
    modal.show();
}


</script>

<!-- MODAL: LIVRE DE POLICE -->
<div class="modal fade" id="livrePoliceModal" tabindex="-1" aria-labelledby="livrePoliceModalLabel" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg" style="background: #1e293b;">
            <!-- Header -->
            <div class="modal-header border-0 py-3 px-4" style="background: rgba(30, 41, 59, 1);">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="fas fa-book fs-5 text-secondary"></i>
                    <h5 class="modal-title fw-bold mb-0">Livre de police</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Body -->
            <div class="modal-body p-4 text-center">
                <i class="fas fa-file-contract text-white-50 mb-3" style="font-size: 3rem;"></i>
                <p class="text-white mb-4">Exportez ou imprimez le registre de police légal pour votre conformité.</p>
                
                <div class="d-grid gap-3">
                    <button class="btn btn-warning p-3 rounded-3 d-flex align-items-center justify-content-center gap-2 fw-bold" onclick="alert('Impression lancée...')">
                        <i class="fas fa-print"></i> IMPRIMER LIVRE DE POLICE
                    </button>
                    <button class="btn btn-primary p-3 rounded-3 d-flex align-items-center justify-content-center gap-2 fw-bold" onclick="sendLivrePoliceEmail()">
                        <i class="fas fa-envelope"></i> ENVOYER PAR EMAIL
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openLivrePoliceModal() {
    const modal = new bootstrap.Modal(document.getElementById('livrePoliceModal'));
    modal.show();
}

function sendLivrePoliceEmail() {
    const email = prompt("Veuillez entrer l'adresse email du destinataire :");
    if (email) {
        // Here you would typically send an AJAX request
        alert("Le livre de police a été envoyé à : " + email);
        const modal = bootstrap.Modal.getInstance(document.getElementById('livrePoliceModal'));
        modal.hide();
    }
}

function openStatsModal() {
    console.log('Opening stats modal');
    const modal = new bootstrap.Modal(document.getElementById('advancedStatsModal'));
    modal.show();
}

function openCommandeStatutModal(title, provider) {
    if(title) document.getElementById('mock-cmd-title').textContent = title;
    if(provider) document.getElementById('mock-cmd-provider').textContent = provider;
    
    const modal = new bootstrap.Modal(document.getElementById('commandeStatutModal'));
    modal.show();
}

function openTaskModal(title, priority, status, desc, date, assignee) {
    document.getElementById('mock-task-title').textContent = title;
    document.getElementById('mock-task-priority').textContent = priority;
    document.getElementById('mock-task-status').textContent = status;
    document.getElementById('mock-task-desc').textContent = desc;
    document.getElementById('mock-task-date').textContent = date;
    document.getElementById('mock-task-assignee').textContent = assignee;
    
    const modal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
    modal.show();
}

function dragTask(ev) {
    ev.dataTransfer.setData("text", ev.target.id);
    ev.dataTransfer.effectAllowed = "move";
}

function filterTasks(status, element) {
    // Update active filter stat UI
    document.querySelectorAll('.filter-stat').forEach(el => el.classList.remove('active'));
    if (element) {
        element.classList.add('active');
    }

    // Update Label
    const labels = {
        'all': 'Toutes les tâches',
        'todo': 'À faire',
        'in-progress': 'En cours',
        'done': 'Terminées',
        'high-priority': 'Haute priorité'
    };
    if(labels[status]) {
        document.getElementById('current-filter-label').innerText = labels[status];
    } else {
        document.getElementById('current-filter-label').innerText = 'Filtres avancés';
    }

    // Filter Items
    const tasks = document.querySelectorAll('#task-list .task-card');
    tasks.forEach(task => {
        if (status === 'all') {
            task.style.display = 'block'; // Or 'flex' if needed, but 'block' works for divs in flex-col usually
            // Actually they are in a flex-column container, so display:block inside it is fine or default.
            // Let's reset to empty to pick up default or set explicit display if needed.
            // Since they are divs, display:block is default.
        } else if (status === 'high-priority') {
            if (task.dataset.priority === 'high') {
                task.style.display = 'block';
            } else {
                task.style.display = 'none';
            }
        } else {
            if (task.dataset.status === status) {
                task.style.display = 'block';
            } else {
                task.style.display = 'none';
            }
        }
    });

    // Add animation to visible items
    tasks.forEach(task => {
        if(task.style.display !== 'none') {
            task.classList.remove('fade-in');
            void task.offsetWidth; // trigger reflow
            task.classList.add('fade-in');
        }
    });
}
</script>