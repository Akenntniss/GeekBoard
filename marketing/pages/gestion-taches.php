<?php
/**
 * Landing Page - Gestion des Tâches
 * Todo list collaboratif pour ateliers
 */
?>

<!-- Hero Section -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6">
                <div class="badge bg-info text-white mb-4 px-3 py-2">
                    <i class="fa-solid fa-list-check me-2"></i>
                    Organisation & Productivité
                </div>
                
                <h1 class="display-4 fw-black mb-4">
                    Rien n'est oublié,<br>tout est sous contrôle
                </h1>
                
                <p class="fs-5 mb-4 opacity-90">
                    Créez, assignez et suivez toutes les tâches de votre atelier. 
                    Vue complète par employé, priorité et statut.
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                    <a href="/inscription" class="btn btn-info btn-lg">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Essai gratuit 30 jours
                    </a>
                    <a href="#demo-tasks" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-check-double me-2"></i>
                        Voir la démo
                    </a>
                </div>
                
                <div class="d-flex flex-wrap gap-4 text-white-50">
                    <div><i class="fa-solid fa-user me-2"></i>Assignation employés</div>
                    <div><i class="fa-solid fa-calendar me-2"></i>Dates d'échéance</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card-modern p-3 bg-white text-dark">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Tâches du jour</h6>
                        <span class="badge bg-danger">3 urgentes</span>
                    </div>
                    
                    <div class="border-start border-danger border-3 ps-3 mb-3 py-2">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <input type="checkbox" class="form-check-input">
                            <strong class="flex-grow-1">Commander écran iPhone 14</strong>
                            <span class="badge bg-danger">Urgent</span>
                        </div>
                        <small class="text-muted">Assigné à Marc • Échéance: Aujourd'hui</small>
                    </div>
                    
                    <div class="border-start border-warning border-3 ps-3 mb-3 py-2">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <input type="checkbox" class="form-check-input">
                            <strong class="flex-grow-1">Rappeler client réparation #3456</strong>
                            <span class="badge bg-warning">Moyenne</span>
                        </div>
                        <small class="text-muted">Assigné à Sophie • Échéance: Demain</small>
                    </div>
                    
                    <div class="border-start border-success border-3 ps-3 py-2">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <input type="checkbox" checked class="form-check-input">
                            <strong class="flex-grow-1 text-decoration-line-through opacity-50">Inventaire mensuel</strong>
                            <span class="badge bg-success">Terminée</span>
                        </div>
                        <small class="text-muted">Terminé par Marc</small>
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
                <div class="h2 fw-black text-primary mb-1">0</div>
                <div class="text-muted">Tâches oubliées</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-success mb-1">+35%</div>
                <div class="text-muted">Tâches terminées à temps</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-warning mb-1">Vue 360°</div>
                <div class="text-muted">Charge de travail équipe</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-info mb-1">3 vues</div>
                <div class="text-muted">Liste, Carte, Kanban</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO -->
<section class="section" id="demo-tasks">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Simulateur de tâches</h2>
            <p class="text-muted">Créez et gérez vos tâches</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-modern p-4">
                    <h6 class="fw-bold mb-3">Nouvelle tâche</h6>
                    
                    <div class="mb-3">
                        <label class="form-label small">Titre</label>
                        <input type="text" class="form-control" placeholder="Ex: Commander pièces pour réparation #1234">
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small">Priorité</label>
                            <select class="form-select">
                                <option>Haute</option>
                                <option selected>Moyenne</option>
                                <option>Basse</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Assigné à</label>
                            <select class="form-select">
                                <option>Marc (Technicien)</option>
                                <option>Sophie (Accueil)</option>
                                <option>Thomas (Manager)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Échéance</label>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small">Description (optionnel)</label>
                        <textarea class="form-control" rows="2" placeholder="Détails supplémentaires..."></textarea>
                    </div>
                    
                    <button class="btn btn-info w-100">
                        <i class="fa-solid fa-plus me-2"></i>
                        Créer la tâche
                    </button>
                </div>
                
                <div class="mt-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card-modern p-3 bg-danger bg-opacity-10">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>À faire</strong>
                                    <span class="badge bg-danger">8</span>
                                </div>
                                <div class="small text-muted">3 urgentes aujourd'hui</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-modern p-3 bg-warning bg-opacity-10">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>En cours</strong>
                                    <span class="badge bg-warning">5</span>
                                </div>
                                <div class="small text-muted">Assignées à 3 employés</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-modern p-3 bg-success bg-opacity-10">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Terminées</strong>
                                    <span class="badge bg-success">47</span>
                                </div>
                                <div class="small text-muted">Ce mois</div>
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
            <div class="col-lg-4">
                <i class="fa-solid fa-users text-info fs-1 mb-3 d-block"></i>
                <h5 class="fw-bold mb-2">Assignation intelligente</h5>
                <p class="text-muted">Répartissez équitablement la charge de travail</p>
            </div>
            <div class="col-lg-4">
                <i class="fa-solid fa-bell text-warning fs-1 mb-3 d-block"></i>
                <h5 class="fw-bold mb-2">Rappels automatiques</h5>
                <p class="text-muted">Notification avant chaque échéance</p>
            </div>
            <div class="col-lg-4">
                <i class="fa-solid fa-chart-pie text-primary fs-1 mb-3 d-block"></i>
                <h5 class="fw-bold mb-2">Statistiques productivité</h5>
                <p class="text-muted">Taux de complétion par employé</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <h2 class="fw-black mb-4">Transformez le chaos en organisation</h2>
        <a href="/inscription" class="btn btn-info btn-lg">
            <i class="fa-solid fa-rocket me-2"></i>
            Commencer gratuitement
        </a>
    </div>
</section>
