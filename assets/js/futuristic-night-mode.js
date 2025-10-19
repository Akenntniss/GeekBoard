/* ====================================================================
   🌙 MODE NUIT FUTURISTE - DÉTECTION SYSTÈME IMMÉDIATE
   Application immédiate pour éviter le flash
==================================================================== */

// SCRIPT CRITIQUE - EXÉCUTÉ IMMÉDIATEMENT
(function() {
    'use strict';
    
    // Fonction pour appliquer le mode nuit IMMÉDIATEMENT
    function applyDarkModeImmediate() {
        // Appliquer sur HTML et BODY immédiatement
        if (document.documentElement) {
            document.documentElement.classList.add('dark-mode');
        }
        if (document.body) {
            document.body.classList.add('dark-mode');
        }
        console.log('🌙 Mode nuit futuriste activé immédiatement');
    }
    
    // Fonction pour appliquer le mode clair IMMÉDIATEMENT
    function applyLightModeImmediate() {
        if (document.documentElement) {
            document.documentElement.classList.remove('dark-mode');
        }
        if (document.body) {
            document.body.classList.remove('dark-mode');
        }
        console.log('☀️ Mode clair activé immédiatement');
    }
    
    // Fonction pour détecter et appliquer le mode système IMMÉDIATEMENT
    function detectAndApplySystemModeImmediate() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            applyDarkModeImmediate();
        } else {
            applyLightModeImmediate();
        }
    }
    
    // APPLIQUER IMMÉDIATEMENT - AVANT MÊME LE DOM
    detectAndApplySystemModeImmediate();
    
    // Écouter les changements de préférence système
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Méthode moderne
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', detectAndApplySystemModeImmediate);
        } 
        // Méthode de fallback pour les anciens navigateurs
        else if (mediaQuery.addListener) {
            mediaQuery.addListener(detectAndApplySystemModeImmediate);
        }
    }
    
    // Réappliquer au chargement du DOM pour s'assurer
    document.addEventListener('DOMContentLoaded', detectAndApplySystemModeImmediate);
    
})();
