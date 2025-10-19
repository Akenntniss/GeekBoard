/**
 * Correction du problème de focus trap Bootstrap
 * Empêche la boucle infinie dans focustrap.js
 */

console.log('🔧 [BOOTSTRAP-FOCUS-FIX] Initialisation de la correction du focus trap...');

// Intercepter et corriger les erreurs de focus trap
(function() {
    'use strict';
    
    // Désactiver temporairement le focus trap pour éviter les boucles infinies
    let originalFocusTrap = null;
    
    // Fonction pour désactiver le focus trap problématique
    function disableProblematicFocusTrap() {
        // Intercepter les erreurs de stack overflow
        const originalError = window.onerror;
        window.onerror = function(message, source, lineno, colno, error) {
            if (message && message.includes('Maximum call stack size exceeded') && 
                source && source.includes('focustrap.js')) {
                console.log('🚫 [BOOTSTRAP-FOCUS-FIX] Boucle infinie détectée dans focustrap.js - Correction...');
                
                // Arrêter tous les événements de focus problématiques
                document.removeEventListener('focusin', arguments.callee, true);
                
                // Réinitialiser le focus sur le body
                setTimeout(() => {
                    if (document.activeElement && document.activeElement.blur) {
                        document.activeElement.blur();
                    }
                    document.body.focus();
                }, 100);
                
                return true; // Empêcher l'affichage de l'erreur
            }
            
            if (originalError) {
                return originalError.apply(this, arguments);
            }
            return false;
        };
    }
    
    // Fonction pour corriger le focus trap Bootstrap
    function fixBootstrapFocusTrap() {
        // Intercepter la création des modals Bootstrap
        const originalModal = window.bootstrap?.Modal;
        if (originalModal) {
            window.bootstrap.Modal = class extends originalModal {
                constructor(element, config = {}) {
                    // Désactiver le focus trap par défaut
                    config.focus = false;
                    super(element, config);
                    console.log('🔧 [BOOTSTRAP-FOCUS-FIX] Modal créé sans focus trap:', element.id);
                }
            };
        }
    }
    
    // Fonction pour nettoyer les événements de focus problématiques
    function cleanupFocusEvents() {
        // Supprimer tous les événements focusin qui pourraient causer des problèmes
        const allElements = document.querySelectorAll('*');
        allElements.forEach(element => {
            if (element._focusinHandler) {
                element.removeEventListener('focusin', element._focusinHandler);
                delete element._focusinHandler;
            }
        });
    }
    
    // Initialisation
    function init() {
        disableProblematicFocusTrap();
        fixBootstrapFocusTrap();
        
        // Nettoyer périodiquement
        setInterval(cleanupFocusEvents, 5000);
        
        console.log('✅ [BOOTSTRAP-FOCUS-FIX] Correction du focus trap initialisée');
    }
    
    // Démarrer dès que possible
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Exposer pour le débogage
    window.bootstrapFocusFix = {
        cleanup: cleanupFocusEvents,
        disable: disableProblematicFocusTrap
    };
})();
