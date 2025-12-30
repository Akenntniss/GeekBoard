<?php
/**
 * Landing Page - Commandes de Pièces
 * Workflow complet de commande fournisseur
 */
?>

<!-- Hero Section -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6">
                <div class="badge bg-info text-white mb-4 px-3 py-2">
                    <i class="fa-solid fa-box me-2"></i>
                    Workflow Complet
                </div>
                
                <h1 class="display-4 fw-black mb-4">
                    Commandes de pièces<br>de A à Z
                </h1>
                
                <p class="fs-5 mb-4 opacity-90">
                    Commandez, suivez, recevez. Workflow complet avec traçabilité totale 
                    depuis la commande jusqu'à la réception.
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                    <a href="/inscription" class="btn btn-info btn-lg">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Essai gratuit 30 jours
                    </a>
                    <a href="#demo-orders" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-truck me-2"></i>
                        Voir la démo
                    </a>
                </div>
                
                <div class="d-flex flex-wrap gap-4 text-white-50">
                    <div><i class="fa-solid fa-link me-2"></i>Lié aux réparations</div>
                    <div><i class="fa-solid fa-history me-2"></i>Historique complet</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card-modern p-4 bg-white text-dark">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Commande #CMD-2024-0042</h6>
                        <span class="badge bg-warning">En attente</span>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Fournisseur:</span>
                            <strong>Mobilax SAS</strong>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Pièce:</span>
                            <strong>Écran iPhone 14 Pro (Original)</strong>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Client:</span>
                            <strong>Martin Dubois</strong>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Réparation:</span>
                            <strong>#REP-1234</strong>
                        </div>
                    </div>
                    
                    <div class="border-top pt-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Prix unitaire:</span>
                            <strong>89,90 €</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total:</span>
                            <strong class="text-primary fs-5">89,90 €</strong>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm flex-fill">
                            <i class="fa-solid fa-check me-1"></i>Réceptionner
                        </button>
                        <button class="btn btn-outline-danger btn-sm flex-fill">
                            <i class="fa-solid fa-times me-1"></i>Annuler
                        </button>
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
                <div class="text-muted">Commandes perdues</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-success mb-1">-50%</div>
                <div class="text-muted">Temps de suivi</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-warning mb-1">100%</div>
                <div class="text-muted">Traçabilité</div>
            </div>
            <div class="col-lg-3">
                <div class="h2 fw-black text-info mb-1">Auto</div>
                <div class="text-muted">Lien réparation-client</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO -->
<section class="section" id="demo-orders">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Tableau de bord commandes</h2>
            <p class="text-muted">Suivez toutes vos commandes en temps réel</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>N° Commande</th>
                                <th>Fournisseur</th>
                                <th>Pièce</th>
                                <th>Client</th>
                                <th>Statut</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>CMD-0042</strong></td>
                                <td>Mobilax</td>
                                <td>Écran iPhone 14 Pro</td>
                                <td>Martin Dubois</td>
                                <td><span class="badge bg-warning">En attente</span></td>
                                <td>89,90 €</td>
                                <td>
                                    <button class="btn btn-sm btn-success" title="Réceptionner">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>CMD-0041</strong></td>
                                <td>Fixya</td>
                                <td>Batterie Samsung S23</td>
                                <td>Sophie Martin</td>
                                <td><span class="badge bg-success">Reçue</span></td>
                                <td>34,50 €</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary" title="Détails">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>CMD-0040</strong></td>
                                <td>eMagic</td>
                                <td>Connecteur charge iPad Air</td>
                                <td>Thomas Bernard</td>
                                <td><span class="badge bg-success">Reçue</span></td>
                                <td>12,90 €</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary" title="Détails">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Workflow -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Workflow simplifié</h2>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-cart-plus text-primary fs-2"></i>
                </div>
                <h5 class="fw-bold">1. Créer</h5>
                <p class="text-muted">Commande depuis une réparation ou manuellement</p>
            </div>
            
            <div class="col-lg-3 text-center">
                <div class="bg-warning bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-clock text-warning fs-2"></i>
                </div>
                <h5 class="fw-bold">2. Suivre</h5>
                <p class="text-muted">Statut "En attente" jusqu'à réception</p>
            </div>
            
            <div class="col-lg-3 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-box-open text-success fs-2"></i>
                </div>
                <h5 class="fw-bold">3. Réceptionner</h5>
                <p class="text-muted">Marquer comme "Reçue" en 1 clic</p>
            </div>
            
            <div class="col-lg-3 text-center">
                <div class="bg-info bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-wrench text-info fs-2"></i>
                </div>
                <h5 class="fw-bold">4. Réparer</h5>
                <p class="text-muted">Pièce directement liée à la réparation</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <h2 class="fw-black mb-4">Plus jamais une pièce commandée oubliée</h2>
        <a href="/inscription" class="btn btn-info btn-lg">
            <i class="fa-solid fa-rocket me-2"></i>
            Démarrer gratuitement
        </a>
    </div>
</section>
