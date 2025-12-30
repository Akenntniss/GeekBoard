/**
 * Correction spécifique pour le modal nouvelles_actions_modal
 * Ce script remplace tous les autres scripts de correction des modales
 * pour éviter les conflits et assurer un fonctionnement optimal
 */

(function() {
    'use strict';
    
    let isInitialized = false;
    
    function initNewActionsModal() {
        if (isInitialized) return;
        
        console.log('🔧 Initialisation spécifique du modal nouvelles_actions_modal');
        
        // Attendre que Bootstrap soit disponible
        if (typeof bootstrap === 'undefined') {
            setTimeout(initNewActionsModal, 500);
            return;
        }
        
        // Trouver le modal
        const modal = document.getElementById('nouvelles_actions_modal');
        if (!modal) {
            console.error('❌ Modal nouvelles_actions_modal non trouvé');
            return;
        }
        
        // Trouver le bouton d'ouverture
        const openButton = document.querySelector('.btn-nouvelle-action, [data-bs-target="#nouvelles_actions_modal"]');
        if (!openButton) {
            console.error('❌ Bouton d\'ouverture du modal non trouvé');
            return;
        }
        
        // Supprimer tous les gestionnaires d'événements existants
        const newButton = openButton.cloneNode(true);
        openButton.parentNode.replaceChild(newButton, openButton);
        
        // Créer l'instance Bootstrap du modal
        let modalInstance;
        try {
            modalInstance = new bootstrap.Modal(modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
        } catch (error) {
            console.error('❌ Erreur lors de la création de l\'instance modal:', error);
            return;
        }
        
        // Ajouter le gestionnaire d'événement au nouveau bouton
        newButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🚀 Ouverture du modal nouvelles_actions_modal');
            
            try {
                // Nettoyer d'abord les éventuels backdrops résiduels
                const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                existingBackdrops.forEach(backdrop => backdrop.remove());
                
                // Réinitialiser l'état du body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Ouvrir le modal
                modalInstance.show();
                
            } catch (error) {
                console.error('❌ Erreur lors de l\'ouverture:', error);
                
                // Fallback : ouverture manuelle
                modal.classList.add('show');
                modal.style.display = 'block';
                modal.setAttribute('aria-modal', 'true');
                modal.removeAttribute('aria-hidden');
                
                // Créer le backdrop
                const backdrop = document.createElement('div');
                backdrop.classList.add('modal-backdrop', 'fade', 'show');
                document.body.appendChild(backdrop);
                
                // Empêcher le défilement
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
                
                console.log('✅ Modal ouvert manuellement (fallback)');
            }
        });
        
        // Gérer la mise à jour du bouton de pointage dynamique
        modal.addEventListener('show.bs.modal', function() {
            console.log('🔄 Ouverture modal nouvelles_actions - Mise à jour bouton pointage...');
            updateTimeTrackingButton();
        });
        
        // Fonction pour mettre à jour le bouton de pointage
        function updateTimeTrackingButton() {
            const dynamicButton = document.getElementById('dynamic-timetracking-button');
            if (!dynamicButton) return;
            
            // Récupérer l'état du pointage
            const shopId = getShopId();
            if (!shopId) return;
            
            fetch(`time_tracking_api.php?shop_id=${shopId}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.shop_info) {
                        const isClockedIn = data.is_clocked_in;
                        const buttonText = isClockedIn ? 'Pointer la sortie' : 'Pointer l\'entrée';
                        const iconClass = isClockedIn ? 'fas fa-clock' : 'fas fa-play';
                        const gradientClass = isClockedIn ? 'bg-gradient-danger' : 'bg-gradient-success';
                        
                        dynamicButton.innerHTML = `
                            <button type="button" class="modern-action-card timetracking-card" onclick="toggleTimeTracking()">
                                <div class="card-glow"></div>
                                <div class="action-icon-container">
                                    <div class="action-icon ${gradientClass}">
                                        <i class="${iconClass}"></i>
                                    </div>
                                    <div class="pulse-ring"></div>
                                </div>
                                <div class="action-content">
                                    <h6 class="action-title">${buttonText}</h6>
                                    <p class="action-description">Système de pointage</p>
                                </div>
                                <div class="action-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </button>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération de l\'état du pointage:', error);
                });
        }
        
        // Fonction utilitaire pour récupérer l'ID du magasin
        function getShopId() {
            // Essayer plusieurs méthodes pour récupérer l'ID du magasin
            const shopIdMeta = document.querySelector('meta[name="shop-id"]');
            if (shopIdMeta) return shopIdMeta.content;
            
            const shopIdInput = document.querySelector('input[name="shop_id"]');
            if (shopIdInput) return shopIdInput.value;
            
            // Récupérer depuis l'URL ou une variable globale
            if (window.shopId) return window.shopId;
            
            return null;
        }
        
        isInitialized = true;
        console.log('✅ Modal nouvelles_actions_modal initialisé avec succès');
    }
    
    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNewActionsModal);
    } else {
        initNewActionsModal();
    }
    
})();

