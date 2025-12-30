/**
 * 🔧 SERVO Animation Fix - Correction des erreurs bloquant l'animation SERVO
 * Version: 1.0
 * Date: 2024-10-12
 */

console.log('🔧 [SERVO-FIX] Script de correction de l\'animation SERVO chargé');

(function() {
    'use strict';
    
    // Attendre que le DOM soit chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initServoFix);
    } else {
        initServoFix();
    }
    
    function initServoFix() {
        console.log('🔧 [SERVO-FIX] Initialisation de la correction...');
        
        // 1. Nettoyer les erreurs de redéclaration
        cleanDuplicateDeclarations();
        
        // 2. Forcer la fin de l'animation SERVO
        forceServoAnimationComplete();
        
        // 3. Corriger les scripts en conflit
        fixConflictingScripts();
        
        // 4. Réinitialiser les modals
        resetModals();
        
        console.log('🔧 [SERVO-FIX] ✅ Correction terminée');
    }
    
    function cleanDuplicateDeclarations() {
        console.log('🔧 [SERVO-FIX] Nettoyage des redéclarations...');
        
        // Supprimer les scripts dupliqués
        const scripts = document.querySelectorAll('script[src*="status-modal.js"], script[src*="devis-clean.js"], script[src*="session-helper.js"], script[src*="update-status-modal.js"], script[src*="modal-helper.js"]');
        
        scripts.forEach((script, index) => {
            if (index > 0) { // Garder seulement le premier
                console.log('🔧 [SERVO-FIX] Suppression du script dupliqué:', script.src);
                script.remove();
            }
        });
    }
    
    function forceServoAnimationComplete() {
        console.log('🔧 [SERVO-FIX] Forçage de la fin de l\'animation SERVO...');
        
        // Chercher l'élément SERVO
        const servoElement = document.querySelector('.servo-animation, .loading-animation, [class*="servo"], [class*="loading"]');
        
        if (servoElement) {
            console.log('🔧 [SERVO-FIX] Élément SERVO trouvé, forçage de la fin...');
            
            // Forcer la fin de l'animation
            servoElement.style.animation = 'none';
            servoElement.style.opacity = '0';
            servoElement.style.visibility = 'hidden';
            servoElement.style.display = 'none';
            
            // Supprimer l'élément après un délai
            setTimeout(() => {
                if (servoElement.parentNode) {
                    servoElement.parentNode.removeChild(servoElement);
                }
            }, 100);
        }
        
        // Chercher et masquer tous les éléments de chargement
        const loadingElements = document.querySelectorAll('.loading, .spinner, .loader, [class*="loading"], [class*="spinner"]');
        loadingElements.forEach(element => {
            element.style.display = 'none';
            element.style.visibility = 'hidden';
        });
    }
    
    function fixConflictingScripts() {
        console.log('🔧 [SERVO-FIX] Correction des scripts en conflit...');
        
        // Réinitialiser les variables globales en conflit
        if (typeof window.StatusModal !== 'undefined') {
            console.log('🔧 [SERVO-FIX] Correction de StatusModal...');
            try {
                delete window.StatusModal;
            } catch (e) {
                console.log('🔧 [SERVO-FIX] Impossible de supprimer StatusModal:', e);
            }
        }
        
        if (typeof window.DevisCleanManager !== 'undefined') {
            console.log('🔧 [SERVO-FIX] Correction de DevisCleanManager...');
            try {
                delete window.DevisCleanManager;
            } catch (e) {
                console.log('🔧 [SERVO-FIX] Impossible de supprimer DevisCleanManager:', e);
            }
        }
        
        if (typeof window.SessionHelper !== 'undefined') {
            console.log('🔧 [SERVO-FIX] Correction de SessionHelper...');
            try {
                delete window.SessionHelper;
            } catch (e) {
                console.log('🔧 [SERVO-FIX] Impossible de supprimer SessionHelper:', e);
            }
        }
        
        if (typeof window.UpdateStatusModal !== 'undefined') {
            console.log('🔧 [SERVO-FIX] Correction de UpdateStatusModal...');
            try {
                delete window.UpdateStatusModal;
            } catch (e) {
                console.log('🔧 [SERVO-FIX] Impossible de supprimer UpdateStatusModal:', e);
            }
        }
        
        if (typeof window.ModalHelper !== 'undefined') {
            console.log('🔧 [SERVO-FIX] Correction de ModalHelper...');
            try {
                delete window.ModalHelper;
            } catch (e) {
                console.log('🔧 [SERVO-FIX] Impossible de supprimer ModalHelper:', e);
            }
        }
    }
    
    function resetModals() {
        console.log('🔧 [SERVO-FIX] Réinitialisation des modals...');
        
        // Fermer tous les modals ouverts
        const openModals = document.querySelectorAll('.modal.show, .modal[style*="display: block"]');
        openModals.forEach(modal => {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
        });
        
        // Supprimer tous les backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        
        // Réinitialiser le body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // Fonction de diagnostic
    window.debugServoFix = function() {
        console.log('🔧 [SERVO-FIX] Diagnostic complet:');
        console.log('- Éléments SERVO:', document.querySelectorAll('[class*="servo"], [class*="loading"]'));
        console.log('- Scripts dupliqués:', document.querySelectorAll('script[src*="status-modal.js"]').length);
        console.log('- Modals ouverts:', document.querySelectorAll('.modal.show').length);
        console.log('- Backdrops:', document.querySelectorAll('.modal-backdrop').length);
    };
    
    // Fonction de nettoyage manuel
    window.forceServoComplete = function() {
        console.log('🔧 [SERVO-FIX] Nettoyage manuel forcé...');
        forceServoAnimationComplete();
        resetModals();
    };
    
    console.log('🔧 [SERVO-FIX] ✅ Script de correction prêt');
    console.log('🔧 [SERVO-FIX] 💡 Utilisez window.debugServoFix() pour diagnostiquer');
    console.log('🔧 [SERVO-FIX] 💡 Utilisez window.forceServoComplete() pour forcer la fin');
    
})();








