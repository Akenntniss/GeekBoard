/* ====================================================================
   🔧 CORRECTION BACKDROP MODAL COMMANDE
   Résout le problème de backdrop bloqué après fermeture du modal ajouterCommandeModal
==================================================================== */

(function() {
    'use strict';
    
    console.log('🔧 [MODAL-COMMANDE] Initialisation du correctif backdrop...');
    
    /**
     * Nettoyer complètement tous les backdrops et restaurer l'état normal
     */
    function cleanupModalBackdrops() {
        console.log('🧹 [MODAL-COMMANDE] Nettoyage des backdrops...');
        
        // Supprimer tous les backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            console.log('🗑️ [MODAL-COMMANDE] Suppression backdrop:', backdrop);
            backdrop.remove();
        });
        
        // Restaurer l'état du body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.body.style.marginRight = '';
        
        // Supprimer les classes spécifiques au modal
        document.body.classList.remove('ajouterCommandeModal-open');
        
        console.log('✅ [MODAL-COMMANDE] Nettoyage terminé');
    }
    
    /**
     * Forcer la fermeture complète du modal
     */
    function forceCloseModal() {
        const modal = document.getElementById('ajouterCommandeModal');
        if (modal) {
            // Masquer le modal
            modal.style.display = 'none';
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');
            modal.removeAttribute('role');
            
            // Nettoyer les backdrops
            cleanupModalBackdrops();
            
            console.log('🔒 [MODAL-COMMANDE] Modal fermé de force');
        }
    }
    
    /**
     * Gérer les modals imbriqués (nouveau client)
     */
    function handleNestedModals() {
        // Surveiller l'ouverture du modal nouveau client
        const nouveauClientModals = [
            'nouveauClientModal_commande',
            'nouveauClientModal_temp',
            'nouveauClientModal',
            'nouveauClientModal_reparation',
            'nouveauClientModal_rachat'
        ];
        
        nouveauClientModals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.addEventListener('show.bs.modal', function() {
                    console.log(`🔝 [MODAL-COMMANDE] Modal imbriqué ${modalId} en cours d'ouverture`);
                    
                    // S'assurer que le modal a le bon z-index
                    this.style.zIndex = modalId === 'nouveauClientModal_temp' ? '999999999' : '10000';
                    
                    const modalDialog = this.querySelector('.modal-dialog');
                    if (modalDialog) {
                        modalDialog.style.zIndex = modalId === 'nouveauClientModal_temp' ? '999999999' : '10001';
                    }
                    
                    const modalContent = this.querySelector('.modal-content');
                    if (modalContent) {
                        modalContent.style.zIndex = modalId === 'nouveauClientModal_temp' ? '999999999' : '10002';
                    }
                });
                
                modal.addEventListener('hidden.bs.modal', function() {
                    console.log(`🔽 [MODAL-COMMANDE] Modal imbriqué ${modalId} fermé`);
                    
                    // Nettoyer les backdrops après fermeture du modal imbriqué
                    setTimeout(() => {
                        cleanupModalBackdrops();
                    }, 100);
                });
            }
        });
    }
    
    /**
     * Initialiser les événements de nettoyage
     */
    function initializeCleanupEvents() {
        const modal = document.getElementById('ajouterCommandeModal');
        if (!modal) {
            console.warn('⚠️ [MODAL-COMMANDE] Modal ajouterCommandeModal non trouvé');
            return;
        }
        
        // Événement de fermeture Bootstrap
        modal.addEventListener('hidden.bs.modal', function(event) {
            console.log('🔄 [MODAL-COMMANDE] Événement hidden.bs.modal déclenché');
            
            // Délai pour s'assurer que Bootstrap a terminé
            setTimeout(() => {
                cleanupModalBackdrops();
            }, 100);
        });
        
        // Événement de début de fermeture
        modal.addEventListener('hide.bs.modal', function(event) {
            console.log('🔄 [MODAL-COMMANDE] Événement hide.bs.modal déclenché');
        });
        
        // Intercepter les clics sur les boutons de fermeture
        const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                console.log('❌ [MODAL-COMMANDE] Bouton fermeture cliqué');
                
                // Délai pour laisser Bootstrap traiter l'événement
                setTimeout(() => {
                    cleanupModalBackdrops();
                }, 150);
            });
        });
        
        // Intercepter la touche Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                console.log('⌨️ [MODAL-COMMANDE] Touche Escape pressée');
                
                setTimeout(() => {
                    cleanupModalBackdrops();
                }, 150);
            }
        });
        
        console.log('✅ [MODAL-COMMANDE] Événements de nettoyage initialisés');
        
        // Initialiser la gestion des modals imbriqués
        handleNestedModals();
    }
    
    /**
     * Observer les mutations DOM pour détecter les backdrops persistants
     */
    function observeBackdropMutations() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && node.classList && node.classList.contains('modal-backdrop')) {
                            console.log('👀 [MODAL-COMMANDE] Nouveau backdrop détecté');
                            
                            // Vérifier si le modal est vraiment ouvert
                            const modal = document.getElementById('ajouterCommandeModal');
                            if (!modal || !modal.classList.contains('show')) {
                                console.log('🗑️ [MODAL-COMMANDE] Suppression backdrop orphelin');
                                setTimeout(() => {
                                    if (node.parentNode) {
                                        node.remove();
                                    }
                                }, 100);
                            }
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: false
        });
        
        console.log('👁️ [MODAL-COMMANDE] Observer de mutations activé');
    }
    
    /**
     * Fonction utilitaire pour nettoyer manuellement (accessible globalement)
     */
    window.cleanupCommandeModal = function() {
        console.log('🧹 [MODAL-COMMANDE] Nettoyage manuel déclenché');
        forceCloseModal();
    };
    
    /**
     * Fonction d'urgence pour débloquer l'interface
     */
    window.emergencyUnlockUI = function() {
        console.log('🚨 [MODAL-COMMANDE] Débloquage d\'urgence de l\'interface');
        
        // Supprimer tous les modals ouverts
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.style.display = 'none';
            modal.classList.remove('show');
        });
        
        // Nettoyage complet
        cleanupModalBackdrops();
        
        // Restaurer les interactions
        document.body.style.pointerEvents = '';
        document.documentElement.style.pointerEvents = '';
        
        console.log('✅ [MODAL-COMMANDE] Interface débloquée');
    };
    
    /**
     * Fonction pour forcer l'affichage du modal nouveau client au premier plan
     */
    window.forceShowNewClientModal = function() {
        console.log('🔝 [MODAL-COMMANDE] Forcer l\'affichage du modal nouveau client');
        
        // Chercher tous les modals nouveau client possibles
        const nouveauClientModals = [
            'nouveauClientModal_commande',
            'nouveauClientModal_temp',
            'nouveauClientModal',
            'nouveauClientModal_reparation',
            'nouveauClientModal_rachat'
        ];
        
        let modalFound = false;
        
        nouveauClientModals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && (modal.classList.contains('show') || modal.style.display !== 'none')) {
                console.log(`🎯 [MODAL-COMMANDE] Modal trouvé: ${modalId}`);
                modalFound = true;
                
                // Forcer le z-index au maximum
                modal.style.zIndex = modalId === 'nouveauClientModal_temp' ? '999999999' : '50000';
                modal.style.position = 'fixed';
                modal.style.display = 'block';
                
                const modalDialog = modal.querySelector('.modal-dialog');
                if (modalDialog) {
                    modalDialog.style.zIndex = modalId === 'nouveauClientModal_temp' ? '999999999' : '50001';
                    modalDialog.style.position = 'relative';
                }
                
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.style.zIndex = modalId === 'nouveauClientModal_temp' ? '999999999' : '50002';
                    modalContent.style.position = 'relative';
                }
                
                // Supprimer les backdrops qui pourraient gêner
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    if (backdrop.style.zIndex < (modalId === 'nouveauClientModal_temp' ? '999999998' : '49999')) {
                        backdrop.style.zIndex = modalId === 'nouveauClientModal_temp' ? '999999998' : '49999';
                    }
                });
                
                console.log(`✅ [MODAL-COMMANDE] Modal ${modalId} forcé au premier plan`);
            }
        });
        
        if (!modalFound) {
            console.warn('⚠️ [MODAL-COMMANDE] Aucun modal nouveau client trouvé');
        }
        
        return modalFound;
    };
    
    /**
     * Initialisation au chargement du DOM
     */
    function initialize() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    initializeCleanupEvents();
                    observeBackdropMutations();
                }, 500);
            });
        } else {
            setTimeout(() => {
                initializeCleanupEvents();
                observeBackdropMutations();
            }, 500);
        }
    }
    
    // Démarrer l'initialisation
    initialize();
    
    console.log('🚀 [MODAL-COMMANDE] Script de correction backdrop chargé');
    
})();








