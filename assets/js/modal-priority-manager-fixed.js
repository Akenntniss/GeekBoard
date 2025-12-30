/* ====================================================================
   GESTIONNAIRE DE PRIORITÉ DES MODALS - VERSION CORRIGÉE
   Gère l'ouverture/fermeture intelligente des modals Bootstrap UNIQUEMENT
   EXCLUT le modal de recherche moderne pour éviter les conflits
==================================================================== */

(function () {
    'use strict';

    // Configuration des priorités des modals BOOTSTRAP UNIQUEMENT
    const MODAL_PRIORITIES = {
        'nouvelles_actions_modal': 1900,
        'ajouterTacheModal': 1800,
        'ajouterCommandeModal': 1700,
        'ajouterReparationModal': 1600,
        'gbQRRepairModal': 2000,
        'gbAdjustModal': 2000,
        'gbReasonModal': 2100,
        'gbPartnerSelectModal': 2100,
        'gbOtherReasonModal': 2100,
        'default': 1500
    };

    // EXCLUSIONS - Modals qui ne doivent PAS être gérés par ce système
    const EXCLUDED_MODALS = [
        'rechercheModalModerne',
        'recherche-modal-overlay'
    ];

    // Pile des modals ouverts
    let modalStack = [];
    let currentTopModal = null;

    function isExcludedModal(modalId) {
        if (!modalId) return true;
        return EXCLUDED_MODALS.includes(modalId) ||
            modalId.includes('recherche') ||
            modalId.includes('Recherche');
    }

    function getModalPriority(modalId) {
        return MODAL_PRIORITIES[modalId] || MODAL_PRIORITIES.default;
    }

    function applyZIndexes() {
        modalStack.forEach((modal, index) => {
            const priority = getModalPriority(modal.id);
            const zIndex = priority + index;

            // console.log(`🎯 [MODAL-PRIORITY-FIXED] Définition z-index ${zIndex} pour ${modal.id}`);

            if (modal.element) {
                modal.element.style.zIndex = zIndex;

                const dialog = modal.element.querySelector('.modal-dialog');
                if (dialog) dialog.style.zIndex = zIndex + 1;

                const content = modal.element.querySelector('.modal-content');
                if (content) content.style.zIndex = zIndex + 2;

                fixModalBackdrop(modal.id, zIndex);
            }
        });
    }

    function fixModalBackdrop(modalId, modalZIndex) {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            if (backdrop.classList.contains(`${modalId}-backdrop`) ||
                backdrop.getAttribute('data-modal-id') === modalId) {
                backdrop.style.zIndex = modalZIndex - 1;
            }
        });

        // Backdrops génériques Bootstrap
        const genericBackdrops = document.querySelectorAll('.modal-backdrop:not([class*="-backdrop"])');
        genericBackdrops.forEach(backdrop => {
            // Si c'est le dernier backdrop ajouté, on suppose qu'il appartient au modal courant
            // On le force juste en dessous du modalZIndex
            const currentZ = parseInt(window.getComputedStyle(backdrop).zIndex);
            if (currentZ >= modalZIndex) {
                backdrop.style.zIndex = modalZIndex - 1;
            }
        });
    }

    function pushModal(modalId, element) {
        if (isExcludedModal(modalId)) return;

        const existingIndex = modalStack.findIndex(m => m.id === modalId);
        if (existingIndex !== -1) {
            modalStack[existingIndex].element = element;
        } else {
            modalStack.push({ id: modalId, element: element });
        }

        currentTopModal = modalId;
        applyZIndexes();
    }

    function popModal(modalId) {
        if (isExcludedModal(modalId)) return;

        modalStack = modalStack.filter(m => m.id !== modalId);
        if (modalStack.length > 0) {
            currentTopModal = modalStack[modalStack.length - 1].id;
        } else {
            currentTopModal = null;
        }
        applyZIndexes();
    }

    function interceptBootstrapModals() {
        document.addEventListener('show.bs.modal', function (event) {
            if (event.target && event.target.id) {
                pushModal(event.target.id, event.target);
            }
        });

        document.addEventListener('shown.bs.modal', function (event) {
            if (event.target && event.target.id) {
                const modalId = event.target.id;
                // Forcer la mise à jour des z-index après affichage complet
                setTimeout(() => {
                    const priority = getModalPriority(modalId);
                    fixModalBackdrop(modalId, priority);
                }, 50);
            }
        });

        document.addEventListener('hide.bs.modal', function (event) {
            if (event.target && event.target.id) {
                popModal(event.target.id);
            }
        });
    }

    // Initialisation
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', interceptBootstrapModals);
    } else {
        interceptBootstrapModals();
    }

    // Exposure globale
    window.modalPriorityManagerFixed = {
        pushModal: pushModal,
        popModal: popModal
    };

})();
