<?php
/**
 * Landing Page - Gestion Fournisseurs
 * Centraliser tous les partenaires et fournisseurs
 */
?>

<!-- Hero Section -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-success text-white mb-4 px-3 py-2">
                        <i class="fa-solid fa-handshake me-2"></i>
                        Relations Fournisseurs
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4">
                        Gérez tous vos fournisseurs<br>en un seul endroit
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90">
                        Comptes, soldes, transactions, contacts : centralisez la gestion de tous vos partenaires fournisseurs.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                        <a href="/inscription" class="btn btn-success btn-lg">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-suppliers" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-eye me-2"></i>
                            Voir la démo
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div><i class="fa-solid fa-check-circle me-2"></i>Suivi soldes temps réel</div>
                        <div><i class="fa-solid fa-check-circle me-2"></i>Historique complet</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="card-modern p-4 bg-white text-dark" style="max-width: 400px; margin: 0 auto;">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-building text-success me-2"></i>Mobilax SAS</h6>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Solde actuel</span>
                            <strong class="text-danger">-1,247.50 €</strong>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-danger" style="width: 65%"></div>
                        </div>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-success bg-opacity-10 p-2 rounded text-center">
                                <div class="small text-muted">Achats mois</div>
                                <div class="fw-bold text-success">€3,450</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-primary bg-opacity-10 p-2 rounded text-center">
                                <div class="small text-muted">Commandes</div>
                                <div class="fw-bold text-primary">127</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success flex-fill">
                            <i class="fa-solid fa-euro-sign me-1"></i>Paiement
                        </button>
                        <button class="btn btn-sm btn-outline-secondary flex-fill">
                            <i class="fa-solid fa-history me-1"></i>Historique
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
            <div class="col-lg-4">
                <div class="h2 fw-black text-primary mb-1">1 clic</div>
                <div class="text-muted">Accès à tout l'historique</div>
            </div>
            <div class="col-lg-4">
                <div class="h2 fw-black text-success mb-1">-40%</div>
                <div class="text-muted">Temps de gestion admin</div>
            </div>
            <div class="col-lg-4">
                <div class="h2 fw-black text-warning mb-1">100%</div>
                <div class="text-muted">Traçabilité garantie</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO -->
<section class="section" id="demo-suppliers">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">Tableau de bord fournisseurs</h2>
            <p class="text-muted">Vue d'ensemble centralisée</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="card-modern p-3 text-center h-100">
                    <div class="bg-success bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-building text-success"></i>
                    </div>
                    <h6 class="fw-bold">Mobilax</h6>
                    <div class="text-danger small mb-2">-1,247€</div>
                    <button class="btn btn-sm btn-success w-100">Régler</button>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="card-modern p-3 text-center h-100">
                    <div class="bg-primary bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-building text-primary"></i>
                    </div>
                    <h6 class="fw-bold">Fixya</h6>
                    <div class="text-success small mb-2">+340€</div>
                    <button class="btn btn-sm btn-outline-secondary w-100">Voir</button>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="card-modern p-3 text-center h-100">
                    <div class="bg-warning bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-building text-warning"></i>
                    </div>
                    <h6 class="fw-bold">eMagic</h6>
                    <div class="text-muted small mb-2">0€</div>
                    <button class="btn btn-sm btn-outline-secondary w-100">Voir</button>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="card-modern p-3 text-center h-100 border-2 border-primary" style="border-style: dashed !important;">
                    <div class="bg-primary bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-plus text-primary"></i>
                    </div>
                    <h6 class="fw-bold">Nouveau</h6>
                    <div class="text-muted small mb-2">Ajouter fournisseur</div>
                    <button class="btn btn-sm btn-primary w-100">Créer</button>
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
                <div class="text-center">
                    <i class="fa-solid fa-receipt text-primary fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-2">Suivi des soldes</h5>
                    <p class="text-muted">Crédit/débit en temps réel pour chaque fournisseur</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <i class="fa-solid fa-history text-success fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-2">Historique complet</h5>
                    <p class="text-muted">Toutes les transactions tracées et datées</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <i class="fa-solid fa-address-book text-warning fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-2">Contacts centralisés</h5>
                    <p class="text-muted">Email, téléphone, adresse : tout au même endroit</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <h2 class="fw-black mb-4">Un fournisseur, une fiche, zéro oubli</h2>
        <a href="/inscription" class="btn btn-success btn-lg">
            <i class="fa-solid fa-rocket me-2"></i>
            Démarrer maintenant
        </a>
    </div>
</section>
