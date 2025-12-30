<?php
/**
 * Landing Page - Missions Gamifiées
 * Fonctionnalité UNIQUE sur le marché - à mettre en avant !
 */
?>

<!-- Hero Section Missions -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-warning text-dark mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-trophy me-2"></i>
                        Exclusivité SERVO
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        Motivez vos équipes<br>pendant les heures creuses
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        Transformez le temps mort en productivité avec un système de missions gamifié. 
                        Vos employés gagnent des bonus en réalisant des tâches qui génèrent du CA.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-warning btn-lg text-dark">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-missions" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-gamepad me-2"></i>
                            Voir la démo
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-star text-warning"></i>
                            <small>Unique sur le marché</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-euro-sign"></i>
                            <small>Récompenses en euros</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <!-- Card Mission Preview -->
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right animate-float" style="max-width: 350px; margin: 0 auto;">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-success px-3 py-2">
                                <i class="fa-solid fa-fire me-1"></i> Mission active
                            </span>
                            <span class="text-warning fw-bold">
                                <i class="fa-solid fa-coins me-1"></i> +15€
                            </span>
                        </div>
                        
                        <h5 class="fw-bold mb-2">🧹 Nettoyage vitrines</h5>
                        <p class="text-muted small mb-3">Nettoyer les vitrines du magasin avant 12h</p>
                        
                        <!-- Barre de progression -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Progression</span>
                                <span class="fw-bold">2/3 tâches</span>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 10px;">
                                <div class="progress-bar bg-success" style="width: 66%; border-radius: 10px;"></div>
                            </div>
                        </div>
                        
                        <!-- Participants -->
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-size: 12px; margin-right: -8px; border: 2px solid white;">JD</div>
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-size: 12px; margin-right: -8px; border: 2px solid white;">ML</div>
                                <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-dark" style="width: 32px; height: 32px; font-size: 12px; border: 2px solid white;">+2</div>
                            </div>
                            <small class="text-muted">
                                <i class="fa-solid fa-clock me-1"></i> 2h restantes
                            </small>
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
                <div class="h2 fw-black text-primary mb-1">+40%</div>
                <div class="text-muted">Productivité heures creuses</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-success mb-1">+25%</div>
                <div class="text-muted">Engagement employés</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-warning mb-1">100%</div>
                <div class="text-muted">Personnalisable</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-primary mb-1">€ ou 🎁</div>
                <div class="text-muted">Récompenses flexibles</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE MISSIONS -->
<section class="section" id="demo-missions" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-warning text-dark mb-3 px-3 py-2">
                <i class="fa-solid fa-gamepad me-2"></i>
                Démo interactive
            </div>
            <h2 class="fw-black mb-3">Créez une mission en direct</h2>
            <p class="text-muted fs-5">Configurez une mission et voyez comment vos employés la verront</p>
        </div>
        
        <div class="row g-4">
            <!-- Panneau création mission -->
            <div class="col-lg-5">
                <div class="card-modern p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="fa-solid fa-plus-circle text-warning me-2"></i>
                        Nouvelle mission
                    </h5>
                    
                    <!-- Type de mission -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-tag text-muted me-2"></i>
                            Type de mission
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-outline-primary btn-sm mission-type-btn active" data-type="nettoyage" data-icon="🧹" onclick="selectMissionType(this)">
                                🧹 Nettoyage
                            </button>
                            <button class="btn btn-outline-success btn-sm mission-type-btn" data-type="relance" data-icon="📞" onclick="selectMissionType(this)">
                                📞 Relance clients
                            </button>
                            <button class="btn btn-outline-warning btn-sm mission-type-btn" data-type="rangement" data-icon="📦" onclick="selectMissionType(this)">
                                📦 Rangement
                            </button>
                            <button class="btn btn-outline-info btn-sm mission-type-btn" data-type="formation" data-icon="📚" onclick="selectMissionType(this)">
                                📚 Formation
                            </button>
                        </div>
                    </div>
                    
                    <!-- Titre mission -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-heading text-muted me-2"></i>
                            Titre de la mission
                        </label>
                        <input type="text" id="mission-title" class="form-control" value="Nettoyage complet des vitrines"
                               style="border-radius: var(--border-radius); border: 2px solid var(--border-color);"
                               oninput="updateMissionPreview()">
                    </div>
                    
                    <!-- Objectif -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-bullseye text-muted me-2"></i>
                            Nombre de tâches à compléter
                        </label>
                        <input type="range" id="mission-tasks" class="form-range" min="1" max="10" value="3"
                               oninput="updateMissionPreview()">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>1 tâche</span>
                            <span id="mission-tasks-value" class="fw-bold text-primary">3 tâches</span>
                            <span>10 tâches</span>
                        </div>
                    </div>
                    
                    <!-- Récompense -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-gift text-muted me-2"></i>
                            Récompense
                        </label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">€</span>
                                    <input type="number" id="mission-euros" class="form-control" value="15" min="0" max="100"
                                           style="border-radius: 0 var(--border-radius) var(--border-radius) 0;"
                                           oninput="updateMissionPreview()">
                                </div>
                                <small class="text-muted">Euros</small>
                            </div>
                            <div class="col-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-warning text-dark">⭐</span>
                                    <input type="number" id="mission-points" class="form-control" value="50" min="0" max="500"
                                           style="border-radius: 0 var(--border-radius) var(--border-radius) 0;"
                                           oninput="updateMissionPreview()">
                                </div>
                                <small class="text-muted">Points</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Durée -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-clock text-muted me-2"></i>
                            Durée de la mission
                        </label>
                        <select id="mission-duration" class="form-select" 
                                style="border-radius: var(--border-radius); border: 2px solid var(--border-color);"
                                onchange="updateMissionPreview()">
                            <option value="1h">1 heure</option>
                            <option value="2h" selected>2 heures</option>
                            <option value="4h">4 heures</option>
                            <option value="1j">1 journée</option>
                            <option value="1s">1 semaine</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-warning w-100" onclick="launchMission()">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Lancer la mission !
                    </button>
                </div>
            </div>
            
            <!-- Prévisualisation côté employé -->
            <div class="col-lg-7">
                <div class="card-modern p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">
                            <i class="fa-solid fa-mobile-screen-button me-2"></i>
                            Vue employé
                        </h5>
                        <span class="badge bg-white text-dark">
                            <i class="fa-solid fa-eye me-1"></i>
                            Aperçu en direct
                        </span>
                    </div>
                    
                    <!-- Simulation écran employé -->
                    <div class="bg-white rounded-4 p-4 text-dark" style="min-height: 400px;">
                        <!-- Header app -->
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                            <div>
                                <small class="text-muted">Bonjour,</small>
                                <h6 class="fw-bold mb-0">Jean Dupont</h6>
                            </div>
                            <div class="text-end">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fa-solid fa-coins"></i> <span id="preview-total-euros">45</span>€
                                    </span>
                                    <span class="badge bg-primary">
                                        <i class="fa-solid fa-star"></i> <span id="preview-total-points">320</span> pts
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mission card -->
                        <div id="mission-preview-card" class="border rounded-3 p-3 mb-3" style="border-color: var(--border-color) !important; transition: all 0.3s ease;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-success">
                                    <i class="fa-solid fa-fire"></i> Nouvelle mission !
                                </span>
                                <div class="text-end">
                                    <span class="text-success fw-bold" id="preview-euros">+15€</span>
                                    <span class="text-warning fw-bold ms-2" id="preview-points">+50⭐</span>
                                </div>
                            </div>
                            
                            <h5 class="fw-bold mb-1" id="preview-title">
                                <span id="preview-icon">🧹</span> Nettoyage complet des vitrines
                            </h5>
                            <p class="text-muted small mb-3">Complétez cette mission pour gagner des récompenses</p>
                            
                            <!-- Progress -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Progression</span>
                                    <span class="fw-bold">0/<span id="preview-tasks-total">3</span> tâches</span>
                                </div>
                                <div class="progress" style="height: 8px; border-radius: 8px;">
                                    <div id="preview-progress" class="progress-bar bg-success" style="width: 0%; border-radius: 8px; transition: width 0.5s ease;"></div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fa-solid fa-clock me-1"></i>
                                    <span id="preview-duration">2h</span> restantes
                                </small>
                                <button class="btn btn-primary btn-sm" id="btn-join-mission" onclick="joinMission()">
                                    <i class="fa-solid fa-hand-point-up me-1"></i>
                                    Rejoindre
                                </button>
                            </div>
                        </div>
                        
                        <!-- Message feedback -->
                        <div id="mission-feedback" class="alert alert-success d-none" style="border-radius: var(--border-radius);">
                            <i class="fa-solid fa-check-circle me-2"></i>
                            <span id="feedback-message">Mission rejointe avec succès !</span>
                        </div>
                        
                        <!-- Actions après avoir rejoint -->
                        <div id="mission-actions" class="d-none">
                            <h6 class="fw-bold mb-3">
                                <i class="fa-solid fa-tasks text-primary me-2"></i>
                                Soumettre une tâche
                            </h6>
                            <div class="mb-3">
                                <input type="text" id="task-description" class="form-control" placeholder="Description de la tâche effectuée..."
                                       style="border-radius: var(--border-radius);">
                            </div>
                            <button class="btn btn-success w-100" onclick="submitTask()">
                                <i class="fa-solid fa-paper-plane me-2"></i>
                                Soumettre pour validation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Comment ça marche -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Comment ça marche ?</h2>
            <p class="text-muted fs-5">Un système simple et motivant pour toute l'équipe</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6 text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <span class="fs-2 fw-black text-primary">1</span>
                </div>
                <h5 class="fw-bold">Créer une mission</h5>
                <p class="text-muted small">L'admin crée une mission avec objectif et récompense</p>
            </div>
            
            <div class="col-lg-3 col-md-6 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <span class="fs-2 fw-black text-success">2</span>
                </div>
                <h5 class="fw-bold">Les employés rejoignent</h5>
                <p class="text-muted small">Notification push, les volontaires s'inscrivent</p>
            </div>
            
            <div class="col-lg-3 col-md-6 text-center">
                <div class="bg-warning bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <span class="fs-2 fw-black text-warning">3</span>
                </div>
                <h5 class="fw-bold">Soumission + validation</h5>
                <p class="text-muted small">L'employé soumet sa tâche avec preuve photo/texte</p>
            </div>
            
            <div class="col-lg-3 col-md-6 text-center">
                <div class="bg-danger bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <span class="fs-2 fw-black text-danger">4</span>
                </div>
                <h5 class="fw-bold">Récompense versée</h5>
                <p class="text-muted small">Bonus ajouté au compte, retrait mensuel</p>
            </div>
        </div>
    </div>
</section>

<!-- Types de missions -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Idées de missions qui génèrent du CA</h2>
            <p class="text-muted fs-5">Exemples concrets pour rentabiliser les heures creuses</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="fs-2">📞</span>
                        <div>
                            <h5 class="fw-bold mb-0">Relance clients</h5>
                            <small class="text-success">+CA direct</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Appeler les clients dont l'appareil attend depuis 7+ jours. 
                        Objectif : 10 appels, 5€ de bonus.
                    </p>
                    <div class="bg-light rounded p-2">
                        <small><strong>Impact:</strong> Réduit les gardiennages, libère du stock</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="fs-2">⭐</span>
                        <div>
                            <h5 class="fw-bold mb-0">Avis Google</h5>
                            <small class="text-success">+Réputation</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Demander un avis aux clients satisfaits du jour. 
                        Objectif : 3 avis, 10€ de bonus.
                    </p>
                    <div class="bg-light rounded p-2">
                        <small><strong>Impact:</strong> Améliore le référencement local</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="fs-2">📸</span>
                        <div>
                            <h5 class="fw-bold mb-0">Contenu réseaux</h5>
                            <small class="text-success">+Visibilité</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Prendre des photos/vidéos de réparations pour Instagram/TikTok. 
                        Objectif : 3 contenus, 15€.
                    </p>
                    <div class="bg-light rounded p-2">
                        <small><strong>Impact:</strong> Marketing gratuit et authentique</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="fs-2">📦</span>
                        <div>
                            <h5 class="fw-bold mb-0">Inventaire stock</h5>
                            <small class="text-warning">+Organisation</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Vérifier et ranger le stock pièces détachées. 
                        Objectif : 1 rayon complet, 8€.
                    </p>
                    <div class="bg-light rounded p-2">
                        <small><strong>Impact:</strong> Moins de pièces perdues, stock à jour</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="fs-2">🎓</span>
                        <div>
                            <h5 class="fw-bold mb-0">Formation technique</h5>
                            <small class="text-info">+Compétences</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Suivre une formation vidéo sur une nouvelle réparation. 
                        Objectif : 1 module, 5€.
                    </p>
                    <div class="bg-light rounded p-2">
                        <small><strong>Impact:</strong> Montée en compétence équipe</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card-feature p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="fs-2">🧹</span>
                        <div>
                            <h5 class="fw-bold mb-0">Nettoyage boutique</h5>
                            <small class="text-secondary">+Image</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Nettoyer vitrines, comptoir, zone d'attente. 
                        Objectif : zone complète, 5€.
                    </p>
                    <div class="bg-light rounded p-2">
                        <small><strong>Impact:</strong> Meilleure expérience client</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="badge bg-warning text-dark mb-4 px-4 py-2 fs-6">
                    <i class="fa-solid fa-trophy me-2"></i>
                    Fonctionnalité exclusive SERVO
                </div>
                <h2 class="fw-black mb-4">Transformez vos heures creuses en profit</h2>
                <p class="fs-5 mb-4 opacity-90">
                    Le seul logiciel de réparation avec un système de missions gamifié intégré.
                    Vos équipes sont motivées, vous générez du CA même pendant les temps morts.
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="/inscription" class="btn btn-warning btn-lg text-dark">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Démarrer l'essai gratuit
                    </a>
                    <a href="/features" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-arrow-right me-2"></i>
                        Autres fonctionnalités
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript pour la démo interactive -->
<script>
let currentMissionType = { type: 'nettoyage', icon: '🧹' };
let tasksCompleted = 0;
let hasJoined = false;

function selectMissionType(btn) {
    document.querySelectorAll('.mission-type-btn').forEach(b => {
        b.classList.remove('active', 'btn-primary', 'btn-success', 'btn-warning', 'btn-info');
        b.classList.add('btn-outline-' + b.classList[1].replace('btn-outline-', ''));
    });
    
    btn.classList.add('active');
    currentMissionType = {
        type: btn.dataset.type,
        icon: btn.dataset.icon
    };
    
    updateMissionPreview();
}

function updateMissionPreview() {
    const title = document.getElementById('mission-title').value;
    const tasks = document.getElementById('mission-tasks').value;
    const euros = document.getElementById('mission-euros').value;
    const points = document.getElementById('mission-points').value;
    const duration = document.getElementById('mission-duration').value;
    
    // Update tasks value display
    document.getElementById('mission-tasks-value').textContent = tasks + ' tâches';
    
    // Update preview
    document.getElementById('preview-icon').textContent = currentMissionType.icon;
    document.getElementById('preview-title').innerHTML = currentMissionType.icon + ' ' + title;
    document.getElementById('preview-euros').textContent = '+' + euros + '€';
    document.getElementById('preview-points').textContent = '+' + points + '⭐';
    document.getElementById('preview-tasks-total').textContent = tasks;
    document.getElementById('preview-duration').textContent = duration;
}

function launchMission() {
    const card = document.getElementById('mission-preview-card');
    
    // Animation de lancement
    card.style.transform = 'scale(1.05)';
    card.style.boxShadow = '0 10px 40px rgba(16, 185, 129, 0.3)';
    
    setTimeout(() => {
        card.style.transform = 'scale(1)';
        card.style.boxShadow = 'none';
    }, 300);
    
    // Reset state
    hasJoined = false;
    tasksCompleted = 0;
    document.getElementById('mission-actions').classList.add('d-none');
    document.getElementById('btn-join-mission').classList.remove('d-none');
    document.getElementById('preview-progress').style.width = '0%';
    
    showFeedback('🚀 Mission lancée ! En attente de participants...', 'info');
}

function joinMission() {
    hasJoined = true;
    document.getElementById('btn-join-mission').classList.add('d-none');
    document.getElementById('mission-actions').classList.remove('d-none');
    
    showFeedback('✅ Vous avez rejoint la mission !', 'success');
    
    // Update totals (simulation)
    const currentEuros = parseInt(document.getElementById('preview-total-euros').textContent);
    // Les euros seront ajoutés à la fin
}

function submitTask() {
    const totalTasks = parseInt(document.getElementById('mission-tasks').value);
    const taskDesc = document.getElementById('task-description').value;
    
    if (!taskDesc.trim()) {
        showFeedback('⚠️ Veuillez décrire la tâche effectuée', 'warning');
        return;
    }
    
    tasksCompleted++;
    const progress = (tasksCompleted / totalTasks) * 100;
    document.getElementById('preview-progress').style.width = progress + '%';
    
    if (tasksCompleted >= totalTasks) {
        // Mission terminée !
        const euros = parseInt(document.getElementById('mission-euros').value);
        const points = parseInt(document.getElementById('mission-points').value);
        
        // Update totals
        const currentEuros = parseInt(document.getElementById('preview-total-euros').textContent);
        const currentPoints = parseInt(document.getElementById('preview-total-points').textContent);
        
        document.getElementById('preview-total-euros').textContent = currentEuros + euros;
        document.getElementById('preview-total-points').textContent = currentPoints + points;
        
        showFeedback('🎉 Mission terminée ! +' + euros + '€ et +' + points + ' points ajoutés !', 'success');
        document.getElementById('mission-actions').classList.add('d-none');
    } else {
        showFeedback('👍 Tâche ' + tasksCompleted + '/' + totalTasks + ' soumise ! En attente de validation...', 'info');
    }
    
    document.getElementById('task-description').value = '';
}

function showFeedback(message, type) {
    const feedback = document.getElementById('mission-feedback');
    feedback.className = 'alert alert-' + type;
    feedback.classList.remove('d-none');
    document.getElementById('feedback-message').textContent = message;
    
    setTimeout(() => {
        feedback.classList.add('d-none');
    }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateMissionPreview();
});
</script>
