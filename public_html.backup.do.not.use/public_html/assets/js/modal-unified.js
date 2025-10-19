/**
 * Gestionnaire unifié des modales - Version simplifiée et sans conflits
 * Remplace tous les autres fichiers de correction des modales
 */

(function() {
    'use strict';
    
    let isInitialized = false;
    
    // Fonction principale d'initialisation
    function initModalManager() {
        if (isInitialized) {
            return;
        }
        
        // Attendre que Bootstrap soit chargé
        if (typeof bootstrap === 'undefined') {
            setTimeout(initModalManager, 500);
            return;
        }
        
        // Configuration simple des modales
        setupModals();
        setupModalButtons();
        
        isInitialized = true;
    }
    
    // Configuration simple des modales
    function setupModals() {
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            try {
                // S'assurer que la modale a les bonnes classes
                if (!modal.classList.contains('fade')) {
                    modal.classList.add('fade');
                }
                
                // Initialiser avec Bootstrap si pas déjà fait
                let modalInstance = bootstrap.Modal.getInstance(modal);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modal, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                }
            } catch (error) {
                console.warn(`Erreur lors de l'initialisation de la modale ${modal.id}:`, error);
            }
        });
    }
    
    // Configuration simple des boutons
    function setupModalButtons() {
        const buttons = document.querySelectorAll('[data-bs-toggle="modal"]');
        
        buttons.forEach(button => {
            const targetId = button.getAttribute('data-bs-target');
            if (!targetId) return;
            
            const modal = document.querySelector(targetId);
            if (!modal) return;
            
            // Nettoyer les anciens événements
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Ajouter un gestionnaire simple
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                try {
                    const modalInstance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                    modalInstance.show();
                } catch (error) {
                    console.warn(`Erreur lors de l'ouverture de ${targetId}:`, error);
                    // Fallback simple
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');
                }
            });
        });
    }
    
    // Gestionnaire d'erreur simplifié
    function setupErrorHandler() {
        const originalError = window.onerror;
        
        window.onerror = function(message, source, lineno, colno, error) {
            const messageStr = String(message || '');
            
            // Ignorer silencieusement les erreurs Bootstrap connues
            if (messageStr.includes('Illegal invocation') && 
                (source && source.includes('selector-engine'))) {
                return true; // Empêche l'affichage
            }
            
            // Laisser passer les autres erreurs
            if (originalError) {
                return originalError(message, source, lineno, colno, error);
            }
            return false;
        };
    }
    
    // Installation du gestionnaire d'erreur
    setupErrorHandler();
    
    // Initialisation au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalManager);
    } else {
        initModalManager();
    }
    
    // Réinitialisation après un délai (pour s'assurer que tout est chargé)
    setTimeout(initModalManager, 1000);
    
    // Export pour débogage
    window.modalManager = {
        reinit: initModalManager,
        isInitialized: () => isInitialized
    };
    
})(); 