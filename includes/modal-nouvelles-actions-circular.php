<?php
/**
 * MODAL NOUVELLES ACTIONS - DESIGN CIRCULAIRE ANIMÉ
 * Style moderne avec disposition circulaire des actions
 */
?>

<!-- ========================================= -->
<!-- MODAL: NOUVELLES ACTIONS - DESIGN CIRCULAIRE -->
<!-- ========================================= -->
<div class="modal fade" id="nouvelles_actions_modal" tabindex="-1" aria-labelledby="nouvelles_actions_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content circular-modal-content">
            <div class="modal-header circular-modal-header">
                <h5 class="modal-title circular-modal-title" id="nouvelles_actions_modal_label">
                    <i class="fas fa-plus-circle me-2"></i>
                    Nouvelles Actions
                </h5>
                <button type="button" class="btn-close circular-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body circular-modal-body">
                <!-- Container circulaire -->
                <div class="circular-container">
                    <div class="links">
                        <ul class="links__list" style="--item-total: 5;">
                            
                            <!-- Nouvelle Réparation -->
                            <li class="links__item" style="--item-count: 0;">
                                <a href="index.php?page=ajouter_reparation" class="links__link" data-bs-dismiss="modal">
                                    <svg class="links__icon" viewBox="0 0 24 24">
                                        <path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>
                                    </svg>
                                    <span class="links__text">Nouvelle Réparation</span>
                                </a>
                            </li>

                            <!-- Nouvelle Tâche -->
                            <li class="links__item" style="--item-count: 1;">
                                <button type="button" class="links__link" id="openNewTaskFromCircular">
                                    <svg class="links__icon" viewBox="0 0 24 24">
                                        <path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                    </svg>
                                    <span class="links__text">Nouvelle Tâche</span>
                                </button>
                            </li>

                            <!-- Nouvelle Commande -->
                            <li class="links__item" style="--item-count: 2;">
                                <button type="button" class="links__link" id="openNewOrderFromCircular">
                                    <svg class="links__icon" viewBox="0 0 24 24">
                                        <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                    </svg>
                                    <span class="links__text">Nouvelle Commande</span>
                                </button>
                            </li>

                            <!-- Scanner -->
                            <li class="links__item" style="--item-count: 3;">
                                <button type="button" class="links__link" id="openUniversalScannerFromCircular">
                                    <svg class="links__icon" viewBox="0 0 24 24">
                                        <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5zm-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6zm6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5zm-6.5 9.5v3h-3v-3h3M13 13h-2v6h6v-6h-4z"/>
                                    </svg>
                                    <span class="links__text">Scanner</span>
                                </button>
                            </li>

                            <!-- Recherche -->
                            <li class="links__item" style="--item-count: 4;">
                                <button type="button" class="links__link" id="openSearchFromCircular">
                                    <svg class="links__icon" viewBox="0 0 24 24">
                                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                                    </svg>
                                    <span class="links__text">Recherche</span>
                                </button>
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ====================================================================
   MODAL NOUVELLES ACTIONS - STYLE CIRCULAIRE ANIMÉ
==================================================================== */

/* Variables CSS pour le design circulaire */
.circular-modal-content {
    --base-grid: 8px;
    --colour-white: #fff;
    --colour-black: #1a1a1a;
    --colour-primary: #064997;
    --colour-secondary: #2b67ac;
    
    background: linear-gradient(-170deg, var(--colour-primary) 20%, #105ba7);
    border: none;
    border-radius: 20px;
    overflow: hidden;
}

.circular-modal-header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: none;
    padding: 1.5rem;
    text-align: center;
}

.circular-modal-title {
    color: var(--colour-white);
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.circular-btn-close {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    filter: invert(1);
    opacity: 0.8;
    transition: all 0.3s ease;
}

.circular-btn-close:hover {
    background: rgba(255, 255, 255, 0.3);
    opacity: 1;
    transform: scale(1.1);
}

.circular-modal-body {
    background-image: 
        linear-gradient(270deg, var(--colour-secondary) 3px, transparent 0),
        linear-gradient(var(--colour-secondary) 3px, transparent 0),
        linear-gradient(270deg, rgba(43,103,172,.4) 1px, transparent 0),
        linear-gradient(var(--colour-secondary) 1px, transparent 0),
        linear-gradient(270deg, rgba(43,103,172,.4) 1px, transparent 0),
        linear-gradient(var(--colour-secondary) 1px, transparent 0);
    background-size: 112px 112px, 112px 112px, 56px 56px, 56px 56px, 28px 28px, 28px 28px;
    padding: 2rem;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.circular-container {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Styles pour la disposition circulaire */
.links {
    --link-size: calc(var(--base-grid) * 20);
    color: var(--colour-black);
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    min-height: 300px;
}

.links__list {
    position: relative;
    list-style: none;
    margin: 0;
    padding: 0;
}

.links__item {
    width: var(--link-size);
    height: var(--link-size);
    position: absolute;
    top: 0;
    left: 0;
    margin-top: calc(var(--link-size) / -2);
    margin-left: calc(var(--link-size) / -2);
    --angle: calc(360deg / var(--item-total));
    --rotation: calc(140deg + var(--angle) * var(--item-count));
    transform: rotate(var(--rotation)) translate(calc(var(--link-size) + var(--base-grid) * 2)) rotate(calc(var(--rotation) * -1));
}

.links__link {
    opacity: 0;
    animation: on-load 0.3s ease-in-out forwards;
    animation-delay: calc(var(--item-count) * 150ms);
    width: 100%;
    height: 100%;
    border-radius: 50%;
    position: relative;
    background-color: var(--colour-white);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-decoration: none;
    color: inherit;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.links__icon {
    width: calc(var(--base-grid) * 8);
    height: calc(var(--base-grid) * 8);
    transition: all 0.3s ease-in-out;
    fill: var(--colour-black);
}

.links__text {
    position: absolute;
    width: 120px;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
    height: calc(var(--base-grid) * 2);
    font-size: calc(var(--base-grid) * 1.8);
    font-weight: 600;
    display: none;
    bottom: calc(var(--base-grid) * 8.5);
    animation: text 0.3s ease-in-out forwards;
    color: var(--colour-white);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    white-space: nowrap;
}

.links__link::after {
    content: "";
    background-color: transparent;
    width: var(--link-size);
    height: var(--link-size);
    border: 2px dashed var(--colour-white);
    display: block;
    border-radius: 50%;
    position: absolute;
    top: 0;
    left: 0;
    transition: all 0.3s cubic-bezier(0.53, -0.67, 0.73, 0.74);
    transform: none;
    opacity: 0;
}

.links__link:hover .links__icon {
    transition: all 0.3s ease-in-out;
    transform: translateY(calc(var(--base-grid) * -1));
    fill: var(--colour-primary);
}

.links__link:hover .links__text {
    display: block;
}

.links__link:hover::after {
    transition: all 0.3s cubic-bezier(0.37, 0.74, 0.15, 1.65);
    transform: scale(1.1);
    opacity: 1;
}

.links__link:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

/* Animations */
@keyframes on-load {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    70% {
        opacity: 0.7;
        transform: scale(1.1);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes text {
    0% {
        opacity: 0;
        transform: scale(0.3) translateY(0) translateX(-50%);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(calc(var(--base-grid) * 5)) translateX(-50%);
    }
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .circular-modal-body {
        padding: 1rem;
        min-height: 350px;
    }
    
    .links {
        --link-size: calc(var(--base-grid) * 16);
    }
    
    .links__icon {
        width: calc(var(--base-grid) * 6);
        height: calc(var(--base-grid) * 6);
    }
    
    .links__text {
        font-size: calc(var(--base-grid) * 1.5);
        width: 100px;
    }
}

/* Mode sombre - Amélioration complète */
@media (prefers-color-scheme: dark) {
    .circular-modal-content {
        --colour-primary: #0a0f1a;
        --colour-secondary: #1e293b;
        --colour-white: #ffffff;
        --colour-black: #000000;
        
        /* Fond plus sombre et contrasté */
        background: linear-gradient(-170deg, #0a0f1a 20%, #1e293b 80%) !important;
        border: 2px solid rgba(59, 130, 246, 0.3) !important;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8) !important;
    }
    
    .circular-modal-header {
        background: rgba(0, 0, 0, 0.3) !important;
        backdrop-filter: blur(15px) !important;
        border-bottom: 1px solid rgba(59, 130, 246, 0.2) !important;
    }
    
    .circular-modal-title {
        color: #ffffff !important;
        text-shadow: 0 0 10px rgba(59, 130, 246, 0.5) !important;
    }
    
    .circular-btn-close {
        background: rgba(59, 130, 246, 0.2) !important;
        border: 1px solid rgba(59, 130, 246, 0.4) !important;
        filter: none !important;
    }
    
    .circular-btn-close:hover {
        background: rgba(59, 130, 246, 0.4) !important;
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.6) !important;
    }
    
    .circular-modal-body {
        background-image: 
            linear-gradient(270deg, #1e293b 3px, transparent 0),
            linear-gradient(#1e293b 3px, transparent 0),
            linear-gradient(270deg, rgba(59, 130, 246, 0.3) 1px, transparent 0),
            linear-gradient(rgba(59, 130, 246, 0.2) 1px, transparent 0),
            linear-gradient(270deg, rgba(59, 130, 246, 0.1) 1px, transparent 0),
            linear-gradient(rgba(30, 41, 59, 0.5) 1px, transparent 0) !important;
    }
    
    .links__link {
        background-color: rgba(15, 23, 42, 0.8) !important;
        border: 2px solid rgba(59, 130, 246, 0.3) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5) !important;
    }
    
    .links__link:hover {
        background-color: rgba(59, 130, 246, 0.2) !important;
        border-color: rgba(59, 130, 246, 0.6) !important;
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4) !important;
        transform: translateY(-2px) scale(1.05) !important;
    }
    
    .links__link:after {
        border-color: rgba(59, 130, 246, 0.8) !important;
    }
    
    .links__icon {
        fill: #ffffff !important;
        filter: drop-shadow(0 0 5px rgba(59, 130, 246, 0.5)) !important;
    }
    
    .links__text {
        color: #ffffff !important;
        text-shadow: 0 0 8px rgba(59, 130, 246, 0.6) !important;
        font-weight: 600 !important;
    }
    
    /* Animation d'apparition plus visible en mode sombre */
    @keyframes on-load-dark {
        0% {
            opacity: 0;
            transform: scale(0.3);
            box-shadow: 0 0 0 rgba(59, 130, 246, 0);
        }
        70% {
            opacity: 0.8;
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
        }
        100% {
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }
    }
    
    .links__link {
        animation: on-load-dark 0.3s ease-in-out forwards !important;
    }
}
</style>

<script>
// JavaScript pour gérer les actions du modal circulaire
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 [CIRCULAR-MODAL] Initialisation du modal circulaire...');
    
    // Gestionnaire pour Nouvelle Tâche
    const newTaskBtn = document.getElementById('openNewTaskFromCircular');
    if (newTaskBtn) {
        newTaskBtn.addEventListener('click', function() {
            console.log('🎯 [CIRCULAR-MODAL] Ouverture Nouvelle Tâche');
            // Fermer le modal actuel
            const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
            if (modal) modal.hide();
            
            // Ouvrir le modal de tâche
            setTimeout(() => {
                const taskModal = new bootstrap.Modal(document.getElementById('ajouterTacheModal'));
                taskModal.show();
            }, 300);
        });
    }
    
    // Gestionnaire pour Nouvelle Commande
    const newOrderBtn = document.getElementById('openNewOrderFromCircular');
    if (newOrderBtn) {
        newOrderBtn.addEventListener('click', function() {
            console.log('🎯 [CIRCULAR-MODAL] Ouverture Nouvelle Commande');
            // Fermer le modal actuel
            const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
            if (modal) modal.hide();
            
            // Ouvrir le modal de commande
            setTimeout(() => {
                const orderModal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
                orderModal.show();
            }, 300);
        });
    }
    
    // Gestionnaire pour Scanner
    const scannerBtn = document.getElementById('openUniversalScannerFromCircular');
    if (scannerBtn) {
        scannerBtn.addEventListener('click', function() {
            console.log('🎯 [CIRCULAR-MODAL] Ouverture Scanner');
            // Fermer le modal actuel
            const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
            if (modal) modal.hide();
            
            // Ouvrir le modal scanner
            setTimeout(() => {
                const scannerModal = new bootstrap.Modal(document.getElementById('universal_scanner_modal'));
                scannerModal.show();
            }, 300);
        });
    }
    
    // Gestionnaire pour Recherche
    const searchBtn = document.getElementById('openSearchFromCircular');
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            console.log('🎯 [CIRCULAR-MODAL] Ouverture Recherche');
            // Fermer le modal actuel
            const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
            if (modal) modal.hide();
            
            // Ouvrir le modal de recherche
            setTimeout(() => {
                const searchModal = new bootstrap.Modal(document.getElementById('rechercheModal'));
                searchModal.show();
            }, 300);
        });
    }
    
    console.log('✅ [CIRCULAR-MODAL] Modal circulaire initialisé');
});
</script>
