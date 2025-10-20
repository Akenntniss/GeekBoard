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
        
        // Trouver le bouton d'ouverture (tous sélecteurs possibles)
        const openButton = document.querySelector('#nouvelle-action-trigger, .btn-nouvelle-action, [data-bs-target="#nouvelles_actions_modal"]');
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
                       // Désactiver transitions CSS le temps de l'ouverture
                       modal.classList.add('no-anim');
                       modalInstance.show();
                       // Retirer le flag juste après l'affichage
                       setTimeout(() => modal.classList.remove('no-anim'), 50);
                
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
            // Forcer le thème jour pour ce modal si le body n'est pas en night-mode
            const isNight = document.body.classList.contains('night-mode');
            if (!isNight) {
                modal.classList.remove('night');
            } else {
                modal.classList.add('night');
            }
            console.log('🔄 Ouverture modal nouvelles_actions - Mise à jour bouton pointage...');
            updateTimeTrackingButton();
        });
        
        // Fonction pour mettre à jour le bouton de pointage
        function updateTimeTrackingButton() {
            const dynamicButton = document.getElementById('dynamic-timetracking-button');
            if (!dynamicButton) return;
            
            // Afficher le chargement
            dynamicButton.innerHTML = `
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
            </div>`;
            
            // Utiliser l'API standard avec timeout
            const timeoutPromise = new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Timeout - API ne répond pas')), 5000)
            );
            
            const fetchPromise = fetch('time_tracking_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_status'
            });
            
            Promise.race([fetchPromise, timeoutPromise])
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur réseau: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📊 Données API reçues:', data);
                    
                    if (data && data.success && data.data) {
                        const state = data.data;
                        
                        // Gérer le cas "no_entry" (aucun pointage)
                        if (state.status === 'no_entry') {
                            // Afficher bouton d'entrée pour commencer le pointage
                            dynamicButton.innerHTML = `
                                <button type="button" class="modern-action-card clock-in-card" onclick="toggleTimeTracking()" data-bs-dismiss="modal">
                                    <div class="card-glow"></div>
                                    <div class="action-icon-container">
                                        <div class="action-icon bg-gradient-success">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </div>
                                        <div class="pulse-ring"></div>
                                    </div>
                                    <div class="action-content">
                                        <h6 class="action-title">Pointage Arrivée</h6>
                                        <p class="action-description">Commencer votre journée de travail</p>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </button>
                            `;
                        } else {
                            // Gérer les autres statuts (active, completed, etc.)
                            const isClockedIn = state.is_clocked_in || state.status === 'active';
                            const buttonText = isClockedIn ? 'Pointer la sortie' : 'Pointer l\'entrée';
                            const iconClass = isClockedIn ? 'fas fa-sign-out-alt' : 'fas fa-sign-in-alt';
                            const gradientClass = isClockedIn ? 'bg-gradient-danger' : 'bg-gradient-success';
                            
                            dynamicButton.innerHTML = `
                                <button type="button" class="modern-action-card timetracking-card" onclick="toggleTimeTracking()" data-bs-dismiss="modal">
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
                    } else {
                        // Afficher bouton de fallback
                        console.log('⚠️ Données API invalides, affichage du fallback');
                        dynamicButton.innerHTML = generateFallbackButton();
                    }
                })
                .catch(error => {
                    console.error('❌ Erreur lors de la récupération de l\'état du pointage:', error);
                    // Afficher bouton de fallback en cas d'erreur
                    dynamicButton.innerHTML = generateFallbackButton();
                });
        }
        
        // Fonction pour générer un bouton de fallback
        function generateFallbackButton() {
            return `
            <button type="button" class="modern-action-card clock-in-card" onclick="toggleTimeTracking()" data-bs-dismiss="modal">
                <div class="card-glow"></div>
                <div class="action-icon-container">
                    <div class="action-icon bg-gradient-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="pulse-ring"></div>
                </div>
                <div class="action-content">
                    <h6 class="action-title">Pointage</h6>
                    <p class="action-description">Gérer votre pointage (mode dégradé)</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </button>`;
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
