<?php
// Déterminer le chemin des assets selon l'emplacement du fichier
$assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
?>

<!-- Modal Mobile Radial Menu -->
<div class="modal fade" id="mobile_radial_menu_modal" tabindex="-1" aria-labelledby="mobile_radial_menu_label" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content radial-modal-content">
            <div class="modal-body radial-modal-body">
                <!-- Menu Radial -->
                <div class="radial-menu">
                    <input type="checkbox" id="radial-toggle" />
                    <label id="radial-show-menu" for="radial-toggle">
                        <!-- Bouton central -->
                        <div class="radial-btn radial-center-btn">
                            <i class="fas fa-plus radial-icon radial-menu-icon"></i>
                            <i class="fas fa-times radial-icon radial-close-icon"></i>
                        </div>
                        
                        <!-- Nouvelle Réparation -->
                        <div class="radial-btn radial-action-btn" data-action="repair">
                            <i class="fas fa-tools"></i>
                            <span class="radial-label">Réparation</span>
                        </div>
                        
                        <!-- Nouvelle Tâche -->
                        <div class="radial-btn radial-action-btn" data-action="task">
                            <i class="fas fa-tasks"></i>
                            <span class="radial-label">Tâche</span>
                        </div>
                        
                        <!-- Nouvelle Commande -->
                        <div class="radial-btn radial-action-btn" data-action="order">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="radial-label">Commande</span>
                        </div>
                        
                        <!-- Scanner -->
                        <div class="radial-btn radial-action-btn" data-action="scanner">
                            <i class="fas fa-qrcode"></i>
                            <span class="radial-label">Scanner</span>
                        </div>
                        
                        <!-- Recherche -->
                        <div class="radial-btn radial-action-btn" data-action="search">
                            <i class="fas fa-search"></i>
                            <span class="radial-label">Recherche</span>
                        </div>
                        
                        <!-- Pointage -->
                        <div class="radial-btn radial-action-btn" data-action="timetracking" id="radial-timetracking-btn">
                            <i class="fas fa-clock"></i>
                            <span class="radial-label">Pointage</span>
                        </div>
                    </label>
                </div>
                
                <!-- Bouton de fermeture du modal -->
                <button type="button" class="btn-close radial-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-arrow-left"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ====================================================================
   MODAL RADIAL MOBILE - DESIGN MODERNE
==================================================================== */

/* Modal plein écran - Z-INDEX ULTRA ÉLEVÉ */
#mobile_radial_menu_modal {
    z-index: 99999 !important;
}

#mobile_radial_menu_modal .modal-dialog {
    margin: 0 !important;
    max-width: 100% !important;
    height: 100vh !important;
    z-index: 99999 !important;
}

#mobile_radial_menu_modal .radial-modal-content {
    background: rgba(33, 33, 33, 0.95) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: none !important;
    border-radius: 0 !important;
    height: 100vh !important;
    overflow: hidden !important;
    z-index: 99999 !important;
}

#mobile_radial_menu_modal .radial-modal-body {
    padding: 0 !important;
    height: 100vh !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
}

/* Bouton de fermeture du modal */
.radial-modal-close {
    position: absolute !important;
    top: 2rem !important;
    left: 2rem !important;
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 50% !important;
    width: 50px !important;
    height: 50px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: white !important;
    font-size: 1.2rem !important;
    backdrop-filter: blur(10px) !important;
    transition: all 0.3s ease !important;
    z-index: 100000 !important;
}

.radial-modal-close:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.4) !important;
    transform: scale(1.1) !important;
}

/* Désactiver la sélection */
#mobile_radial_menu_modal * {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Input caché */
#mobile_radial_menu_modal input {
    position: absolute;
    display: none;
}

/* Container du menu radial */
.radial-menu {
    margin: 0 auto;
    position: relative;
    width: 80px;
    height: 80px;
}

/* Boutons radiaux */
.radial-btn {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    position: absolute;
    overflow: hidden;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Bouton central */
.radial-center-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    z-index: 100001 !important;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

/* Icônes du bouton central */
.radial-icon {
    font-size: 2rem;
    transition: all 0.3s ease;
    position: absolute;
}

.radial-close-icon {
    transform: translateY(50px) rotate(180deg);
    opacity: 0;
}

.radial-menu-icon {
    transform: translateY(0px) rotate(0deg);
    opacity: 1;
}

/* Animation du bouton central quand ouvert */
input#radial-toggle:checked ~ #radial-show-menu .radial-center-btn .radial-menu-icon {
    transform: translateY(-50px) rotate(-180deg);
    opacity: 0;
}

input#radial-toggle:checked ~ #radial-show-menu .radial-center-btn .radial-close-icon {
    transform: translateY(0px) rotate(0deg);
    opacity: 1;
}

/* Boutons d'action */
.radial-action-btn {
    opacity: 0;
    z-index: -2;
    transition: all 0.6s cubic-bezier(0.87, -0.41, 0.19, 1.44);
    color: #333;
}

.radial-action-btn i {
    font-size: 1.8rem;
    margin-bottom: 0.25rem;
    color: #667eea;
}

.radial-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #555;
}

/* Délais d'animation */
.radial-btn:nth-child(2) { transition-delay: 0s; }
.radial-btn:nth-child(3) { transition-delay: 0.1s; }
.radial-btn:nth-child(4) { transition-delay: 0.2s; }
.radial-btn:nth-child(5) { transition-delay: 0.3s; }
.radial-btn:nth-child(6) { transition-delay: 0.4s; }
.radial-btn:nth-child(7) { transition-delay: 0.5s; }

/* Positions quand le menu est ouvert */
input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(2) {
    top: -140px;
    opacity: 1;
}

input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(3) {
    top: -100px;
    left: 100px;
    opacity: 1;
}

input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(4) {
    left: 140px;
    opacity: 1;
}

input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(5) {
    top: 100px;
    left: 100px;
    opacity: 1;
}

input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(6) {
    top: 140px;
    opacity: 1;
}

input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(7) {
    top: 100px;
    left: -100px;
    opacity: 1;
}

/* Effets de survol */
.radial-action-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.radial-action-btn:hover i,
.radial-action-btn:hover .radial-label {
    color: white;
}

/* Animations d'entrée du modal */
@keyframes radialModalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

#mobile_radial_menu_modal.show .radial-modal-content {
    animation: radialModalSlideIn 0.4s ease-out;
}

/* FORÇAGE ULTRA-AGRESSIF DE L'AFFICHAGE */
#mobile_radial_menu_modal.show,
#mobile_radial_menu_modal.fade.show,
.modal#mobile_radial_menu_modal.show,
.modal.fade#mobile_radial_menu_modal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    z-index: 99999 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(33, 33, 33, 0.95) !important;
}

/* Forcer l'affichage même sans la classe show */
#mobile_radial_menu_modal[style*="display: block"] {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    z-index: 99999 !important;
}

/* Responsive */
@media (max-width: 480px) {
    .radial-btn {
        width: 70px;
        height: 70px;
    }
    
    .radial-action-btn i {
        font-size: 1.6rem;
    }
    
    .radial-label {
        font-size: 0.65rem;
    }
    
    /* Positions ajustées pour petit écran */
    input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(2) {
        top: -120px;
    }
    
    input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(3) {
        top: -85px;
        left: 85px;
    }
    
    input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(4) {
        left: 120px;
    }
    
    input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(5) {
        top: 85px;
        left: 85px;
    }
    
    input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(6) {
        top: 120px;
    }
    
    input#radial-toggle:checked ~ #radial-show-menu .radial-btn:nth-child(7) {
        top: 85px;
        left: -85px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 [RADIAL-MODAL] Initialisation du modal radial');
    
    // Gestion des clics sur les boutons d'action
    const actionButtons = document.querySelectorAll('.radial-action-btn');
    const radialModal = document.getElementById('mobile_radial_menu_modal');
    const radialToggle = document.getElementById('radial-toggle');
    
    console.log('🎯 [RADIAL-MODAL] Modal trouvé:', radialModal);
    console.log('🎯 [RADIAL-MODAL] Toggle trouvé:', radialToggle);
    
    // Forcer l'affichage du modal si nécessaire
    if (radialModal) {
        // S'assurer que le modal est visible
        radialModal.style.display = 'block';
        radialModal.style.visibility = 'visible';
        radialModal.style.opacity = '1';
        radialModal.style.zIndex = '99999';
        
        console.log('🎯 [RADIAL-MODAL] Styles forcés sur le modal');
    }
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const action = this.getAttribute('data-action');
            
            // Fermer le menu radial
            radialToggle.checked = false;
            
            // Attendre un peu pour l'animation puis fermer le modal
            setTimeout(() => {
                const modalInstance = bootstrap.Modal.getInstance(radialModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                
                // Exécuter l'action
                executeRadialAction(action);
            }, 300);
        });
    });
    
    // Fonction pour exécuter les actions
    function executeRadialAction(action) {
        switch(action) {
            case 'repair':
                window.location.href = 'index.php?page=ajouter_reparation';
                break;
                
            case 'task':
                const taskModal = document.getElementById('ajouterTacheModal');
                if (taskModal) {
                    const taskModalInstance = new bootstrap.Modal(taskModal);
                    taskModalInstance.show();
                }
                break;
                
            case 'order':
                const orderModal = document.getElementById('ajouterCommandeModal');
                if (orderModal) {
                    const orderModalInstance = new bootstrap.Modal(orderModal);
                    orderModalInstance.show();
                }
                break;
                
            case 'scanner':
                if (typeof openUniversalScanner === 'function') {
                    openUniversalScanner();
                }
                break;
                
            case 'search':
                if (typeof ouvrirRechercheModerne === 'function') {
                    ouvrirRechercheModerne();
                }
                break;
                
            case 'timetracking':
                if (typeof toggleTimeTracking === 'function') {
                    toggleTimeTracking();
                }
                break;
                
            default:
                console.log('Action non reconnue:', action);
        }
    }
    
    // Forcer l'ouverture du modal quand Bootstrap l'ouvre
    if (radialModal) {
        radialModal.addEventListener('show.bs.modal', function() {
            console.log('🎯 [RADIAL-MODAL] Événement show.bs.modal déclenché');
            this.style.display = 'block !important';
            this.style.visibility = 'visible !important';
            this.style.opacity = '1 !important';
            this.style.zIndex = '99999 !important';
        });
        
        radialModal.addEventListener('shown.bs.modal', function() {
            console.log('🎯 [RADIAL-MODAL] Modal complètement affiché');
        });
    }
    
    // Fermer le menu radial quand on clique en dehors
    if (radialModal) {
        radialModal.addEventListener('click', function(e) {
            if (e.target === this) {
                radialToggle.checked = false;
            }
        });
        
        // Réinitialiser le menu quand le modal se ferme
        radialModal.addEventListener('hidden.bs.modal', function() {
            radialToggle.checked = false;
        });
    }
});
</script>
