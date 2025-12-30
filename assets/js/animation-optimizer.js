/**
 * 🚀 ANIMATION OPTIMIZER - Phase 3
 * Optimise les performances en gérant intelligemment les animations
 * 
 * Fonctionnalités :
 * 1. Animation conditionnelle (uniquement éléments visibles)
 * 2. Mode Performance (désactivation totale)
 * 3. Détection automatique appareil peu performant
 */

(function () {
    'use strict';

    // ====================================================================
    // CONFIGURATION
    // ====================================================================

    const CONFIG = {
        // Seuil de visibilité pour activer/désactiver animations
        intersectionThreshold: 0.1,

        // Marge avant/après viewport pour précharger animations
        intersectionMargin: '50px',

        // Détection automatique de performance
        autoDetectPerformance: true,

        // Sélecteurs d'éléments à observer
        animatedSelectors: [
            '[class*="animate-"]',
            '[style*="animation"]',
            '.futuristic-enabled',
            '.stat-card',
            '.action-card'
        ]
    };

    // ====================================================================
    // INTERSECTION OBSERVER - Animation conditionnelle
    // ====================================================================

    const animationObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const element = entry.target;

            if (entry.isIntersecting) {
                // Élément visible → activer animation
                if (element.dataset.animationPaused === 'true') {
                    element.style.animationPlayState = 'running';
                    element.dataset.animationPaused = 'false';
                }
            } else {
                // Élément hors viewport → pause animation
                const computedStyle = window.getComputedStyle(element);
                if (computedStyle.animationName !== 'none') {
                    element.style.animationPlayState = 'paused';
                    element.dataset.animationPaused = 'true';
                }
            }
        });
    }, {
        threshold: CONFIG.intersectionThreshold,
        rootMargin: CONFIG.intersectionMargin
    });

    // ====================================================================
    // MODE PERFORMANCE
    // ====================================================================

    const PerformanceMode = {
        isActive: false,

        toggle() {
            this.isActive = !this.isActive;

            if (this.isActive) {
                this.enable();
            } else {
                this.disable();
            }

            // Sauvegarder préférence
            localStorage.setItem('performance_mode', this.isActive);

            return this.isActive;
        },

        enable() {
            document.body.classList.add('performance-mode');
            console.log('🚀 Mode Performance activé - Animations désactivées');

            // Event personnalisé
            document.dispatchEvent(new CustomEvent('performanceModeChanged', {
                detail: { enabled: true }
            }));
        },

        disable() {
            document.body.classList.remove('performance-mode');
            console.log('✨ Mode Performance désactivé - Animations réactivées');

            // Event personnalisé
            document.dispatchEvent(new CustomEvent('performanceModeChanged', {
                detail: { enabled: false }
            }));
        },

        init() {
            // Charger préférence sauvegardée
            const saved = localStorage.getItem('performance_mode');
            if (saved === 'true') {
                this.isActive = true;
                this.enable();
            }
        }
    };

    // ====================================================================
    // DÉTECTION AUTOMATIQUE PERFORMANCE FAIBLE
    // ====================================================================

    function detectLowPerformance() {
        if (!CONFIG.autoDetectPerformance) return false;

        // Vérifier si appareil mobile
        const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

        // Vérifier mémoire disponible (si API disponible)
        const hasLowMemory = navigator.deviceMemory && navigator.deviceMemory < 4;

        // Vérifier nombre de cœurs CPU (si API disponible)
        const hasLowCPU = navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4;

        // Vérifier mode économie d'énergie
        const hasBatterySaver = navigator.getBattery ? false : false; // À implémenter si nécessaire

        return isMobile && (hasLowMemory || hasLowCPU);
    }

    // ====================================================================
    // INITIALISATION
    // ====================================================================

    function initAnimationOptimizer() {
        console.log('🎨 Animation Optimizer initialisé');

        // Initialiser mode performance
        PerformanceMode.init();

        // Détection automatique appareil peu performant
        if (detectLowPerformance() && !PerformanceMode.isActive) {
            console.log('⚠️ Appareil peu performant détecté - Mode Performance recommandé');

            // Afficher suggestion (si toastr disponible)
            if (typeof toastr !== 'undefined') {
                toastr.info(
                    'Votre appareil semble avoir des ressources limitées. Activez le Mode Performance pour une meilleure expérience.',
                    'Optimisation suggérée',
                    {
                        timeOut: 10000,
                        closeButton: true,
                        onclick: () => {
                            PerformanceMode.toggle();
                        }
                    }
                );
            }
        }

        // Observer tous les éléments animés
        const observeAnimatedElements = () => {
            CONFIG.animatedSelectors.forEach(selector => {
                document.querySelectorAll(selector).forEach(element => {
                    animationObserver.observe(element);
                });
            });
        };

        // Observer immédiatement
        observeAnimatedElements();

        // Re-observer après chargement complet
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', observeAnimatedElements);
        }

        // Observer nouveaux éléments (MutationObserver)
        const mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // ELEMENT_NODE
                        CONFIG.animatedSelectors.forEach(selector => {
                            if (node.matches && node.matches(selector)) {
                                animationObserver.observe(node);
                            }
                            // Observer enfants
                            node.querySelectorAll && node.querySelectorAll(selector).forEach(child => {
                                animationObserver.observe(child);
                            });
                        });
                    }
                });
            });
        });

        mutationObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // ====================================================================
    // EXPOSITION GLOBALE
    // ====================================================================

    window.AnimationOptimizer = {
        PerformanceMode,
        init: initAnimationOptimizer
    };

    // ====================================================================
    // AUTO-INIT
    // ====================================================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAnimationOptimizer);
    } else {
        initAnimationOptimizer();
    }

})();
