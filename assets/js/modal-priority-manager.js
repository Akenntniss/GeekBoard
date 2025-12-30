/* ====================================================================
   GESTIONNAIRE DE PRIORITÉ DES MODALS
   Gère l'ouverture/fermeture intelligente des modals pour éviter les conflits
==================================================================== */

(function() {
    'use strict';
    
    console.log('🎯 [MODAL-PRIORITY] Initialisation du gestionnaire de priorité...');
    
    // Configuration des priorités des modals
    const MODAL_PRIORITIES = {
        'rechercheModalModerne': 2000
        'recherche-modal-overlay': 2000
        'nouvelles_actions_modal': 1900
        'ajouterTacheModal': 1800
        'ajouterCommandeModal': 1700
        'default': 1600
    };
    
    // Pile des modals ouverts (LIFO - Last In, First Out)
    let modalStack = [];
    
    // Modal actuellement au premier plan
    let currentTopModal = null;
    
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
            
            console.log(`🎯 [MODAL-PRIORITY] Définition z-index ${zIndex} pour ${modal.id}`);
            
            if (modal.element) {
                modal.element.style.zIndex = zIndex;
                
                // Appliquer aussi aux éléments enfants
                const dialog = modal.element.querySelector('.modal-dialog, .recherche-modal');
                if (dialog) {
                    dialog.style.zIndex = zIndex + 1;
                }
                
                const content = modal.element.querySelector('.modal-content, .recherche-modal');
                if (content) {
                    content.style.zIndex = zIndex + 2;
                }
                
                // CORRECTION CRITIQUE - Gérer le backdrop
                fixModalBackdrop(modal.id, zIndex);
            }
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
                
                console.log(`🎯 [MODAL-PRIORITY] Backdrop ${modalId}: z-index ${backdropZIndex}`);
            }
        
        // Correction spéciale pour les backdrops Bootstrap génériques
        const genericBackdrops = document.querySelectorAll('.modal-backdrop:not([class*="-backdrop"])');
        if (genericBackdrops.length > 0) {
            // Si on a des backdrops génériques, s'assurer qu'ils sont en dessous
            genericBackdrops.forEach(backdrop => {
                const currentZIndex = parseInt(backdrop.style.zIndex) || 1050;
                if (currentZIndex >= modalZIndex) {
                    backdrop.style.zIndex = modalZIndex - 1;
                    console.log(`🎯 [MODAL-PRIORITY] Backdrop générique corrigé: z-index ${modalZIndex - 1}`);
                }
        }
    }
    
    /**
     * Ajouter un modal à la pile
     */
    function pushModal(modalId, element) {
        // Vérifier si le modal n'est pas déjà dans la pile
        const existingIndex = modalStack.findIndex(m => m.id === modalId);
        if (existingIndex !== -1) {
            console.log(`🎯 [MODAL-PRIORITY] Modal ${modalId} déjà dans la pile, mise à jour...`);
            modalStack[existingIndex].element = element;
        } else {
            console.log(`🎯 [MODAL-PRIORITY] Ajout du modal ${modalId} à la pile`);
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
        console.log(`🎯 [MODAL-PRIORITY] Retrait du modal ${modalId} de la pile`);
        
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
                console.log(`🎯 [MODAL-PRIORITY] Désactivation des interactions pour ${modal.id}`);
            }
        
        // Activer les interactions pour le modal au premier plan
        if (currentTopModal && modalStack.length > 0) {
            const topModal = modalStack[modalStack.length - 1];
            if (topModal.element) {
                topModal.element.style.pointerEvents = 'auto';
                console.log(`🎯 [MODAL-PRIORITY] Activation des interactions pour ${currentTopModal}`);
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
    }
    
    /**
     * Intercepter l'ouverture des modals Bootstrap
     */
    function interceptBootstrapModals() {
        document.addEventListener('show.bs.modal', function(event) {
            const modalId = event.target.id;
            console.log(`🎯 [MODAL-PRIORITY] Ouverture détectée: ${modalId}`);
            pushModal(modalId, event.target);
        
        document.addEventListener('shown.bs.modal', function(event) {
            const modalId = event.target.id;
            console.log(`🎯 [MODAL-PRIORITY] Modal affiché: ${modalId}`);
            
            // Corriger les backdrops après que le modal soit complètement affiché
            setTimeout(() => {
                const priority = getModalPriority(modalId);
                fixModalBackdrop(modalId, priority);
            }, 100);
        
        document.addEventListener('hide.bs.modal', function(event) {
            const modalId = event.target.id;
            console.log(`🎯 [MODAL-PRIORITY] Fermeture détectée: ${modalId}`);
            popModal(modalId);
    }
    
    /**
     * Intercepter l'ouverture du modal de recherche moderne
     */
    function interceptRechercheModerne() {
        // Observer les changements de classe sur le modal de recherche
        const rechercheOverlay = document.getElementById('rechercheModalModerne') || 
                                document.querySelector('.recherche-modal-overlay');
        
        if (rechercheOverlay) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const classList = mutation.target.classList;
                        
                        if (classList.contains('show')) {
                            console.log('🎯 [MODAL-PRIORITY] Modal recherche moderne ouvert');
                            pushModal('rechercheModalModerne', mutation.target);
                        } else if (!classList.contains('show')) {
                            console.log('🎯 [MODAL-PRIORITY] Modal recherche moderne fermé');
                            popModal('rechercheModalModerne');
                        }
                    }
            
            observer.observe(rechercheOverlay, {
                attributes: true
                attributeFilter: ['class', 'style']
        }
        
        // Observer aussi les changements de style display
        const rechercheModal = document.querySelector('.recherche-modal-overlay');
        if (rechercheModal) {
            const styleObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const display = window.getComputedStyle(mutation.target).display;
                        
                        if (display !== 'none') {
                            console.log('🎯 [MODAL-PRIORITY] Modal recherche visible via style');
                            pushModal('recherche-modal-overlay', mutation.target);
                        } else {
                            console.log('🎯 [MODAL-PRIORITY] Modal recherche masqué via style');
                            popModal('recherche-modal-overlay');
                        }
                    }
            
            styleObserver.observe(rechercheModal, {
                attributes: true
                attributeFilter: ['style']
        }
    }
    
    /**
     * Forcer la fermeture de tous les modals sauf celui spécifié
     */
    function closeAllExcept(keepModalId) {
        console.log(`🎯 [MODAL-PRIORITY] Fermeture de tous les modals sauf ${keepModalId}`);
        
        modalStack.forEach(modal => {
            if (modal.id !== keepModalId && modal.element) {
                // Fermer le modal Bootstrap
                if (modal.element.classList.contains('modal')) {
                    const bsModal = bootstrap.Modal.getInstance(modal.element);
                    if (bsModal) {
                        bsModal.hide();
                    }
                }
                
                // Fermer le modal de recherche moderne
                if (modal.id.includes('recherche')) {
                    modal.element.classList.remove('show');
                    modal.element.style.display = 'none';
                }
            }
    }
    
    /**
     * Obtenir des informations de debug
     */
    function getDebugInfo() {
        return {
            modalStack: modalStack.map(m => ({ id: m.id, zIndex: m.element?.style.zIndex }))
            currentTopModal: currentTopModal
            priorities: MODAL_PRIORITIES
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
                            
                            console.log('🎯 [MODAL-PRIORITY] Nouveau backdrop détecté');
                            
                            // Corriger immédiatement le z-index
                            setTimeout(() => {
                                // Trouver le modal correspondant
                                modalStack.forEach(modal => {
                                    const priority = getModalPriority(modal.id);
                                    fixModalBackdrop(modal.id, priority);
                            }, 10);
                        }
                }
        
        observer.observe(document.body, {
            childList: true
            subtree: true
        
        console.log('🎯 [MODAL-PRIORITY] Observer des backdrops installé');
    }
    
    /**
     * Initialisation
     */
    function init() {
        console.log('🎯 [MODAL-PRIORITY] Initialisation...');
        
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                interceptBootstrapModals();
                interceptRechercheModerne();
                observeBackdrops();
        } else {
            interceptBootstrapModals();
            interceptRechercheModerne();
            observeBackdrops();
        }
        
        console.log('🎯 [MODAL-PRIORITY] ✅ Gestionnaire initialisé');
    }
    
    // Exposer les fonctions globalement pour le debug
    window.modalPriorityManager = {
        pushModal: pushModal
        popModal: popModal
        closeAllExcept: closeAllExcept
        getDebugInfo: getDebugInfo
        applyZIndexes: applyZIndexes
    };
    
    // Initialiser
    init();
    
})();

console.log('🎯 [MODAL-PRIORITY] ✅ Script chargé');
console.log('🎯 [MODAL-PRIORITY] 💡 Utilisez window.modalPriorityManager.getDebugInfo() pour déboguer');
