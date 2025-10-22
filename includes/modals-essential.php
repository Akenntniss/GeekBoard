<?php
/**
 * MODALS ESSENTIELS - Sans nouvelles_actions_modal
 * Contient seulement les modals nécessaires
 */
?>

<!-- ========================================= -->
<!-- MODAL: SCANNER UNIVERSEL -->
<!-- ========================================= -->
<div class="modal fade" id="universal_scanner_modal" tabindex="-1" aria-labelledby="universal_scanner_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg modern-modal">
            <div class="modal-header border-0 bg-gradient-scanner">
                <h5 class="modal-title text-white fw-bold" id="universal_scanner_modal_label">
                    <i class="fas fa-qrcode me-2 pulse-icon"></i>
                    Scanner Universel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="scanner-container">
                    <div class="scanner-preview mb-3">
                        <video id="scanner-video" class="w-100 rounded" style="max-height: 300px; object-fit: cover;"></video>
                        <canvas id="scanner-canvas" class="d-none"></canvas>
                    </div>
                    
                    <div class="scanner-controls mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-primary w-100" id="start-scanner">
                                    <i class="fas fa-play me-2"></i>Démarrer
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-secondary w-100" id="stop-scanner">
                                    <i class="fas fa-stop me-2"></i>Arrêter
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="scanner-result">
                        <div class="alert alert-info d-none" id="scanner-result-display">
                            <h6><i class="fas fa-check-circle me-2"></i>Code détecté :</h6>
                            <p class="mb-0" id="scanner-result-text"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================= -->
<!-- MODAL: MENU NAVIGATION -->
<!-- ========================================= -->
<div class="modal fade" id="menu_navigation_modal" tabindex="-1" aria-labelledby="menu_navigation_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg modern-modal">
            <div class="modal-header border-0 bg-gradient-primary">
                <h5 class="modal-title text-white fw-bold" id="menu_navigation_modal_label">
                    <i class="fas fa-compass me-2"></i>
                    Navigation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="navigation-grid">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="index.php?page=accueil" class="nav-card">
                                <i class="fas fa-home"></i>
                                <span>Accueil</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="index.php?page=reparations" class="nav-card">
                                <i class="fas fa-tools"></i>
                                <span>Réparations</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="index.php?page=clients" class="nav-card">
                                <i class="fas fa-users"></i>
                                <span>Clients</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="index.php?page=commandes" class="nav-card">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Commandes</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour les modals essentiels */
.modern-modal {
    border-radius: 20px !important;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.bg-gradient-scanner {
    background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
}

.pulse-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.scanner-container {
    text-align: center;
}

.nav-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    min-height: 100px;
}

.nav-card:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    color: #333;
}

.nav-card i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: #667eea;
}

.nav-card span {
    font-weight: 600;
    font-size: 0.9rem;
}
</style>














