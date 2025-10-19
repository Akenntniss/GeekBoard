/* ====================================================================
   GESTIONNAIRE DE PRIORITÉ DES MODALS - VERSION CORRIGÉE
   Gère l'ouverture/fermeture intelligente des modals Bootstrap UNIQUEMENT
   EXCLUT le modal de recherche moderne pour éviter les conflits
==================================================================== */

(function() {
    'use strict';
    
    console.log('🎯 [MODAL-PRIORITY-FIXED] Initialisation du gestionnaire de priorité corrigé...');
    
    // Configuration des priorités des modals BOOTSTRAP UNIQUEMENT
    const MODAL_PRIORITIES = {
        'nouvelles_actions_modal': 1900,
        'ajouterTacheModal': 1800,
        'ajouterCommandeModal': 1700,
        'ajouterReparationModal': 1600,
        'default': 1500
    };
    
    // EXCLUSIONS - Modals qui ne doivent PAS être gérés par ce système
    const EXCLUDED_MODALS = [
        'rechercheModalModerne',
        'recherche-modal-overlay'
    ];
    
    // Pile des modals ouverts (LIFO - Last In, First Out)
    let modalStack = [];
    
    // Modal actuellement au premier plan
    let currentTopModal = null;
    
    /**
     * Vérifier si un modal doit être exclu
     */
    function isExcludedModal(modalId) {
        return EXCLUDED_MODALS.includes(modalId) || 
               modalId.includes('recherche') ||
               modalId.includes('Recherche');
    }
    
    /**
     * Obtenir la priorité d'un modal
     */
    function getModalPriority(modalId) {
        return MODAL_PRIORITIES[modalId] || MODAL_PRIORITIES.default;
    }
    
    /**
     * Appliquer les z-index selon les priorités
     */
    function applyZIndexes() {
        modalStack.forEach((modal, index) => {
            const priority = getModalPriority(modal.id);
            const zIndex = priority + index; // Ajouter l'index pour l'ordre dans la pile
            
            console.log(`🎯 [MODAL-PRIORITY-FIXED] Définition z-index ${zIndex} pour ${modal.id}`);
            
            if (modal.element) {
                modal.element.style.zIndex = zIndex;
                
                // Appliquer aussi aux éléments enfants
                const dialog = modal.element.querySelector('.modal-dialog');
                if (dialog) {
                    dialog.style.zIndex = zIndex + 1;
                }
                
                const content = modal.element.querySelector('.modal-content');
                if (content) {
                    content.style.zIndex = zIndex + 2;
                }
                
                // CORRECTION CRITIQUE - Gérer le backdrop
                fixModalBackdrop(modal.id, zIndex);
            }
        });
    }
    
    /**
     * Corriger le z-index du backdrop pour qu'il soit toujours derrière le modal
     */
    function fixModalBackdrop(modalId, modalZIndex) {
        // Chercher le backdrop correspondant
        const backdrops = document.querySelectorAll('.modal-backdrop');
        
        backdrops.forEach(backdrop => {
            // Vérifier si ce backdrop correspond au modal
            if (backdrop.classList.contains(`${modalId}-backdrop`) || 
                backdrop.getAttribute('data-modal-id') === modalId) {
                
                const backdropZIndex = modalZIndex - 1; // Toujours 1 niveau en dessous
                backdrop.style.zIndex = backdropZIndex;
                
                console.log(`🎯 [MODAL-PRIORITY-FIXED] Backdrop ${modalId}: z-index ${backdropZIndex}`);
            }
        });
        
        // Correction spéciale pour les backdrops Bootstrap génériques
        const genericBackdrops = document.querySelectorAll('.modal-backdrop:not([class*="-backdrop"])');
        if (genericBackdrops.length > 0) {
            // Si on a des backdrops génériques, s'assurer qu'ils sont en dessous
            genericBackdrops.forEach(backdrop => {
                const currentZIndex = parseInt(backdrop.style.zIndex) || 1050;
                if (currentZIndex >= modalZIndex) {
                    backdrop.style.zIndex = modalZIndex - 1;
                    console.log(`🎯 [MODAL-PRIORITY-FIXED] Backdrop générique corrigé: z-index ${modalZIndex - 1}`);
                }
            });
        }
    }
    
    /**
     * Ajouter un modal à la pile
     */
    function pushModal(modalId, element) {
        // VÉRIFICATION CRITIQUE - Exclure les modals non-Bootstrap
        if (isExcludedModal(modalId)) {
            console.log(`🎯 [MODAL-PRIORITY-FIXED] ⚠️ Modal ${modalId} exclu du gestionnaire`);
            return;
        }
        
        // Vérifier si le modal n'est pas déjà dans la pile
        const existingIndex = modalStack.findIndex(m => m.id === modalId);
        if (existingIndex !== -1) {
            console.log(`🎯 [MODAL-PRIORITY-FIXED] Modal ${modalId} déjà dans la pile, mise à jour...`);
            modalStack[existingIndex].element = element;
        } else {
            console.log(`🎯 [MODAL-PRIORITY-FIXED] Ajout du modal ${modalId} à la pile`);
            modalStack.push({ id: modalId, element: element });
        }
        
        currentTopModal = modalId;
        applyZIndexes();
        
        // Désactiver les interactions avec les modals en arrière-plan
        disableBackgroundModals();
    }
    
    /**
     * Retirer un modal de la pile
     */
    function popModal(modalId) {
        // VÉRIFICATION CRITIQUE - Exclure les modals non-Bootstrap
        if (isExcludedModal(modalId)) {
            console.log(`🎯 [MODAL-PRIORITY-FIXED] ⚠️ Modal ${modalId} exclu du gestionnaire`);
            return;
        }
        
        console.log(`🎯 [MODAL-PRIORITY-FIXED] Retrait du modal ${modalId} de la pile`);
        
        modalStack = modalStack.filter(m => m.id !== modalId);
        
        // Mettre à jour le modal au premier plan
        if (modalStack.length > 0) {
            currentTopModal = modalStack[modalStack.length - 1].id;
        } else {
            currentTopModal = null;
        }
        
        applyZIndexes();
        
        // Réactiver les interactions si nécessaire
        if (modalStack.length === 0) {
            enableAllModals();
        } else {
            disableBackgroundModals();
        }
    }
    
    /**
     * Désactiver les interactions avec les modals en arrière-plan
     */
    function disableBackgroundModals() {
        modalStack.forEach((modal, index) => {
            if (index < modalStack.length - 1 && modal.element) {
                modal.element.style.pointerEvents = 'none';
                console.log(`🎯 [MODAL-PRIORITY-FIXED] Désactivation des interactions pour ${modal.id}`);
            }
        });
        
        // Activer les interactions pour le modal au premier plan
        if (currentTopModal && modalStack.length > 0) {
            const topModal = modalStack[modalStack.length - 1];
            if (topModal.element) {
                topModal.element.style.pointerEvents = 'auto';
                console.log(`🎯 [MODAL-PRIORITY-FIXED] Activation des interactions pour ${currentTopModal}`);
            }
        }
    }
    
    /**
     * Réactiver toutes les interactions
     */
    function enableAllModals() {
        modalStack.forEach(modal => {
            if (modal.element) {
                modal.element.style.pointerEvents = 'auto';
            }
        });
    }
    
    /**
     * Intercepter l'ouverture des modals Bootstrap UNIQUEMENT
     */
    function interceptBootstrapModals() {
        document.addEventListener('show.bs.modal', function(event) {
            const modalId = event.target.id;
            
            // VÉRIFICATION CRITIQUE - Ignorer les modals exclus
            if (isExcludedModal(modalId)) {
                console.log(`🎯 [MODAL-PRIORITY-FIXED] ⚠️ Ignorer modal exclu: ${modalId}`);
                return;
            }
            
            console.log(`🎯 [MODAL-PRIORITY-FIXED] Ouverture détectée: ${modalId}`);
            pushModal(modalId, event.target);
        });
        
        document.addEventListener('shown.bs.modal', function(event) {
            const modalId = event.target.id;
            
            // VÉRIFICATION CRITIQUE - Ignorer les modals exclus
            if (isExcludedModal(modalId)) {
                return;
            }
            
            console.log(`🎯 [MODAL-PRIORITY-FIXED] Modal affiché: ${modalId}`);
            
            // Corriger les backdrops après que le modal soit complètement affiché
            setTimeout(() => {
                const priority = getModalPriority(modalId);
                fixModalBackdrop(modalId, priority);
            }, 100);
        });
        
        document.addEventListener('hide.bs.modal', function(event) {
            const modalId = event.target.id;
            
            // VÉRIFICATION CRITIQUE - Ignorer les modals exclus
            if (isExcludedModal(modalId)) {
                return;
            }
            
            console.log(`🎯 [MODAL-PRIORITY-FIXED] Fermeture détectée: ${modalId}`);
            popModal(modalId);
        });
    }
    
    /**
     * Forcer la fermeture de tous les modals sauf celui spécifié
     */
    function closeAllExcept(keepModalId) {
        console.log(`🎯 [MODAL-PRIORITY-FIXED] Fermeture de tous les modals sauf ${keepModalId}`);
        
        modalStack.forEach(modal => {
            if (modal.id !== keepModalId && modal.element) {
                // Fermer le modal Bootstrap
                if (modal.element.classList.contains('modal')) {
                    const bsModal = bootstrap.Modal.getInstance(modal.element);
                    if (bsModal) {
                        bsModal.hide();
                    }
                }
            }
        });
    }
    
    /**
     * Obtenir des informations de debug
     */
    function getDebugInfo() {
        return {
            modalStack: modalStack.map(m => ({ id: m.id, zIndex: m.element?.style.zIndex })),
            currentTopModal: currentTopModal,
            priorities: MODAL_PRIORITIES,
            excludedModals: EXCLUDED_MODALS
        };
    }
    
    /**
     * Observer pour surveiller la création dynamique des backdrops
     */
    function observeBackdrops() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE && 
                            node.classList && node.classList.contains('modal-backdrop')) {
                            
                            console.log('🎯 [MODAL-PRIORITY-FIXED] Nouveau backdrop détecté');
                            
                            // Corriger immédiatement le z-index
                            setTimeout(() => {
                                // Trouver le modal correspondant
                                modalStack.forEach(modal => {
                                    const priority = getModalPriority(modal.id);
                                    fixModalBackdrop(modal.id, priority);
                                });
                            }, 10);
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('🎯 [MODAL-PRIORITY-FIXED] Observer des backdrops installé');
    }
    
    /**
     * Initialisation
     */
    function init() {
        console.log('🎯 [MODAL-PRIORITY-FIXED] Initialisation...');
        
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                interceptBootstrapModals();
                observeBackdrops();
            });
        } else {
            interceptBootstrapModals();
            observeBackdrops();
        }
        
        console.log('🎯 [MODAL-PRIORITY-FIXED] ✅ Gestionnaire initialisé (Bootstrap uniquement)');
    }
    
    // Exposer les fonctions globalement pour le debug
    window.modalPriorityManagerFixed = {
        pushModal: pushModal,
        popModal: popModal,
        closeAllExcept: closeAllExcept,
        getDebugInfo: getDebugInfo,
        applyZIndexes: applyZIndexes
    };
    
    // Initialiser
    init();
    
})();

console.log('🎯 [MODAL-PRIORITY-FIXED] ✅ Script chargé - Gestion Bootstrap uniquement');
console.log('🎯 [MODAL-PRIORITY-FIXED] 💡 Utilisez window.modalPriorityManagerFixed.getDebugInfo() pour déboguer');
