/**
 * 📱 CORRECTION SLIDER MODE NUIT MOBILE
 * Gestion des interactions tactiles et du scroll
 */

(function() {
    'use strict';
    
    // Attendre que le DOM soit chargé
    document.addEventListener('DOMContentLoaded', function() {
        
        // Vérifier si on est en mode nuit
        const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (isDarkMode) {
            console.log('🌙 Mode nuit détecté - Activation des corrections slider');
            
            // Fonction pour corriger les problèmes de touch sur mobile
            function fixMobileTouch() {
                
                // Corriger les éléments avec overflow hidden
                const elementsToFix = [
                    '.stat-card',
                    '.action-card',
                    '.dashboard-action-button',
                    '.card',
                    '.carousel',
                    '.carousel-inner'
                ];
                
                elementsToFix.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(element => {
                        // Forcer le touch-action approprié
                        element.style.touchAction = 'pan-y';
                        element.style.webkitOverflowScrolling = 'touch';
                        
                        // S'assurer que les événements tactiles passent
                        element.style.pointerEvents = 'auto';
                    });
                });
                
                // Corriger spécifiquement les carousels Bootstrap
                const carousels = document.querySelectorAll('.carousel');
                carousels.forEach(carousel => {
                    // Forcer l'activation du swipe sur mobile
                    if (window.bootstrap && window.bootstrap.Carousel) {
                        const carouselInstance = window.bootstrap.Carousel.getInstance(carousel);
                        if (carouselInstance) {
                            // Réinitialiser le carousel avec les bonnes options
                            carouselInstance.dispose();
                            new window.bootstrap.Carousel(carousel, {
                                touch: true,
                                interval: false // Permettre le contrôle manuel
                            });
                        }
                    }
                });
            }
            
            // Fonction principale d'initialisation
            function initMobileFixes() {
                console.log('🔧 Initialisation des corrections mobile...');
                
                // Attendre un peu pour s'assurer que tout est chargé
                setTimeout(() => {
                    fixMobileTouch();
                    console.log('✅ Corrections mobile appliquées');
                }, 500);
            }
            
            // Initialiser les corrections
            initMobileFixes();
            
            // Réappliquer après le chargement complet
            window.addEventListener('load', initMobileFixes);
        }
    });
    
})();
