<?php
/**
 * Modal Nouvelles Actions - Format Desktop/PC
 * Modal avec format liste classique pour les écrans PC
 */
?>

<!-- MODAL NOUVELLES ACTIONS - FORMAT DESKTOP/PC -->
<div class="modal fade" id="nouvelles_actions_modal_desktop" tabindex="-1" aria-labelledby="nouvelles_actions_modal_desktop_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg modern-modal">
            <div class="modal-header border-0 bg-gradient-primary">
                <h5 class="modal-title text-white fw-bold" id="nouvelles_actions_modal_desktop_label">
                    <i class="fas fa-sparkles me-2 pulse-icon"></i>
                    Créer quelque chose de nouveau
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative overflow-hidden">
                <!-- Effet de particules animées -->
                <div class="particles-container">
                    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
                    <div class="particle" style="left: 30%; animation-delay: 1s;"></div>
                    <div class="particle" style="left: 50%; animation-delay: 2s;"></div>
                    <div class="particle" style="left: 70%; animation-delay: 0.5s;"></div>
                    <div class="particle" style="left: 90%; animation-delay: 1.5s;"></div>
                </div>
                
                <!-- Actions modernes avec cartes -->
                <div class="modern-actions-grid p-4">
                    <!-- Nouvelle Réparation -->
                    <a href="index.php?page=ajouter_reparation" class="modern-action-card repair-card">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-primary">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Réparation</h6>
                            <p class="action-description">Créer un dossier de réparation complet</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <!-- Nouvelle Tâche -->
                    <button type="button" class="modern-action-card task-card" id="openNewTaskFromDesktop">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-success">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Tâche</h6>
                            <p class="action-description">Ajouter une tâche à accomplir</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>

                    <!-- Nouvelle Commande -->
                    <button type="button" class="modern-action-card order-card" id="openNewOrderFromDesktop">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-warning">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Commande</h6>
                            <p class="action-description">Commander des pièces et fournitures</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>

                    <!-- Pointage Dynamique -->
                    <div id="dynamic-timetracking-button-desktop">
                        <!-- Bouton de chargement temporaire -->
                        <div class="modern-action-card loading-card">
                            <div class="card-glow"></div>
                            <div class="action-icon-container">
                                <div class="action-icon bg-gradient-info">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <div class="pulse-ring"></div>
                            </div>
                            <div class="action-content">
                                <h6 class="action-title">Chargement...</h6>
                                <p class="action-description">Vérification de l'état du pointage</p>
                            </div>
                        </div>
                    </div>

                    <!-- Scanner Universel -->
                    <button type="button" class="modern-action-card scanner-card" id="openUniversalScannerFromDesktop">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-scanner">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Scanner</h6>
                            <p class="action-description">Scanner un produit ou une réparation</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🖥️ [DESKTOP-MODAL] Initialisation du modal desktop...');
    
    // Gestionnaire pour Nouvelle Tâche
    const taskBtn = document.getElementById('openNewTaskFromDesktop');
    if (taskBtn) {
        taskBtn.addEventListener('click', function() {
            console.log('🖥️ [DESKTOP-MODAL] Ouverture Nouvelle Tâche');
            // Fermer le modal actuel
            const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal_desktop'));
            if (modal) modal.hide();
            
            // Ouvrir le modal tâche
            setTimeout(() => {
                const taskModal = new bootstrap.Modal(document.getElementById('ajouterTacheModal'));
                taskModal.show();
            }, 300);
        });
    }
    
    // Gestionnaire pour Nouvelle Commande
    const orderBtn = document.getElementById('openNewOrderFromDesktop');
    if (orderBtn) {
        orderBtn.addEventListener('click', function() {
            console.log('🖥️ [DESKTOP-MODAL] Ouverture Nouvelle Commande');
            // Fermer le modal actuel
            const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal_desktop'));
            if (modal) modal.hide();
            
            // Ouvrir le modal commande
            setTimeout(() => {
                const orderModal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
                orderModal.show();
            }, 300);
        });
    }
    
    // Gestionnaire pour Scanner
    const scannerBtn = document.getElementById('openUniversalScannerFromDesktop');
    if (scannerBtn) {
        scannerBtn.addEventListener('click', function() {
            console.log('🖥️ [DESKTOP-MODAL] Ouverture Scanner');
            // Fermer le modal actuel
            const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal_desktop'));
            if (modal) modal.hide();
            
            // Ouvrir le modal scanner
            setTimeout(() => {
                const scannerModal = new bootstrap.Modal(document.getElementById('universal_scanner_modal'));
                scannerModal.show();
            }, 300);
        });
    }
    
    console.log('🖥️ [DESKTOP-MODAL] ✅ Modal desktop initialisé');
});
</script>














