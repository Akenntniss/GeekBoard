/**
 * GEEKBOARD - OPTIMISEUR DE PERFORMANCE AUTOMATIQUE
 * Détecte les performances faibles et active automatiquement les optimisations
 */

(function() {
    'use strict';
    
    // Configuration
    const PERFORMANCE_CONFIG = {
        // Seuils de performance (en ms)
        SLOW_FRAME_THRESHOLD: 50,  // Frame plus lente que 20 FPS
        VERY_SLOW_THRESHOLD: 100,  // Frame plus lente que 10 FPS
        
        // Nombre de frames lentes avant activation
        SLOW_FRAME_COUNT: 5,
        
        // Intervalle de vérification (ms)
        CHECK_INTERVAL: 2000,
        
        // Durée d'observation initiale (ms)
        OBSERVATION_PERIOD: 10000
    };
    
    // État global
    let performanceData = {
        slowFrames: 0,
        verySlowFrames: 0,
        lastFrameTime: performance.now(),
        isOptimized: false,
        isObserving: true
    };
    
    /**
     * Détecte si l'utilisateur est en mode nuit
     */
    function isDarkMode() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    
    /**
     * Active le mode performance ultra
     */
    function enablePerformanceMode() {
        if (performanceData.isOptimized) return;
        
        console.log('🚀 GeekBoard: Activation du mode performance');
        
        // Ajouter la classe performance-mode
        document.body.classList.add('performance-mode');
        
        // Désactiver les animations CSS coûteuses
        const style = document.createElement('style');
        style.id = 'performance-boost';
        style.textContent = `
            /* Mode performance ultra activé */
            @media (prefers-color-scheme: dark) {
                * {
                    animation-duration: 0.1s !important;
                    transition-duration: 0.1s !important;
                }
                
                .modern-dashboard *::before,
                .modern-dashboard *::after {
                    display: none !important;
                }
                
                .futuristic-card,
                .stat-card {
                    background: #1a1a2e !important;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
                    backdrop-filter: none !important;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Réduire la fréquence des mises à jour
        if (window.dashboard && window.dashboard.statsManager) {
            window.dashboard.statsManager.stopAutoUpdate();
            window.dashboard.statsManager.startAutoUpdate(60000); // 1 minute au lieu de 30s
        }
        
        performanceData.isOptimized = true;
        
        // Notification utilisateur
        if (window.DashboardAPI && window.DashboardAPI.showNotification) {
            window.DashboardAPI.showNotification(
                'Mode performance activé pour améliorer la fluidité', 
                'info'
            );
        }
    }
    
    /**
     * Désactive le mode performance
     */
    function disablePerformanceMode() {
        if (!performanceData.isOptimized) return;
        
        console.log('🎨 GeekBoard: Désactivation du mode performance');
        
        document.body.classList.remove('performance-mode');
        
        const performanceStyle = document.getElementById('performance-boost');
        if (performanceStyle) {
            performanceStyle.remove();
        }
        
        // Restaurer la fréquence normale des mises à jour
        if (window.dashboard && window.dashboard.statsManager) {
            window.dashboard.statsManager.stopAutoUpdate();
            window.dashboard.statsManager.startAutoUpdate(30000);
        }
        
        performanceData.isOptimized = false;
    }
    
    /**
     * Mesure les performances des frames
     */
    function measureFramePerformance() {
        const now = performance.now();
        const frameTime = now - performanceData.lastFrameTime;
        performanceData.lastFrameTime = now;
        
        // Ignorer la première mesure
        if (frameTime > 1000) return;
        
        // Compter les frames lentes
        if (frameTime > PERFORMANCE_CONFIG.SLOW_FRAME_THRESHOLD) {
            performanceData.slowFrames++;
        }
        
        if (frameTime > PERFORMANCE_CONFIG.VERY_SLOW_THRESHOLD) {
            performanceData.verySlowFrames++;
        }
        
        // Activer le mode performance si nécessaire
        if (performanceData.verySlowFrames >= 3 || performanceData.slowFrames >= PERFORMANCE_CONFIG.SLOW_FRAME_COUNT) {
            if (isDarkMode() && !performanceData.isOptimized) {
                enablePerformanceMode();
            }
        }
        
        // Continuer la mesure si on observe encore
        if (performanceData.isObserving) {
            requestAnimationFrame(measureFramePerformance);
        }
    }
    
    /**
     * Détecte les appareils peu performants
     */
    function detectLowEndDevice() {
        // Vérifications basiques
        const checks = {
            // Moins de 4 cœurs CPU
            lowCores: navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4,
            
            // Connexion lente
            slowConnection: navigator.connection && 
                           (navigator.connection.effectiveType === 'slow-2g' || 
                            navigator.connection.effectiveType === '2g'),
            
            // Mémoire limitée (moins de 4GB)
            lowMemory: navigator.deviceMemory && navigator.deviceMemory < 4,
            
            // User agent suggère un appareil mobile ancien
            oldMobile: /Android [1-6]\.|iPhone OS [1-9]_/.test(navigator.userAgent)
        };
        
        const lowEndIndicators = Object.values(checks).filter(Boolean).length;
        
        return lowEndIndicators >= 2;
    }
    
    /**
     * Initialise l'optimiseur de performance
     */
    function initPerformanceOptimizer() {
        // Activation immédiate pour les appareils peu performants en mode nuit
        if (isDarkMode() && detectLowEndDevice()) {
            console.log('📱 GeekBoard: Appareil peu performant détecté, activation du mode performance');
            enablePerformanceMode();
            return;
        }
        
        // Sinon, observer les performances pendant 10 secondes
        console.log('📊 GeekBoard: Observation des performances...');
        
        // Démarrer la mesure des frames
        requestAnimationFrame(measureFramePerformance);
        
        // Arrêter l'observation après la période définie
        setTimeout(() => {
            performanceData.isObserving = false;
            console.log(`📈 GeekBoard: Observation terminée - Frames lentes: ${performanceData.slowFrames}, Très lentes: ${performanceData.verySlowFrames}`);
        }, PERFORMANCE_CONFIG.OBSERVATION_PERIOD);
        
        // Vérification périodique
        setInterval(() => {
            // Réinitialiser les compteurs
            performanceData.slowFrames = 0;
            performanceData.verySlowFrames = 0;
            
            // Reprendre l'observation si pas encore optimisé
            if (!performanceData.isOptimized && isDarkMode()) {
                performanceData.isObserving = true;
                requestAnimationFrame(measureFramePerformance);
                
                setTimeout(() => {
                    performanceData.isObserving = false;
                }, 5000); // 5 secondes d'observation
            }
        }, PERFORMANCE_CONFIG.CHECK_INTERVAL);
    }
    
    /**
     * Écouter les changements de mode sombre/clair
     */
    function setupThemeListener() {
        if (window.matchMedia) {
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            darkModeQuery.addListener((e) => {
                if (e.matches) {
                    // Passage en mode sombre - réactiver l'observation
                    if (detectLowEndDevice()) {
                        enablePerformanceMode();
                    } else {
                        performanceData.isObserving = true;
                        requestAnimationFrame(measureFramePerformance);
                    }
                } else {
                    // Passage en mode clair - désactiver les optimisations
                    disablePerformanceMode();
                    performanceData.isObserving = false;
                }
            });
        }
    }
    
    /**
     * API publique pour contrôle manuel
     */
    window.GeekBoardPerformance = {
        enable: enablePerformanceMode,
        disable: disablePerformanceMode,
        isOptimized: () => performanceData.isOptimized,
        getStats: () => ({ ...performanceData })
    };
    
    // Initialisation quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initPerformanceOptimizer, 1000); // Attendre 1 seconde après le chargement
        });
    } else {
        setTimeout(initPerformanceOptimizer, 1000);
    }
    
    // Configuration des listeners
    setupThemeListener();
    
    console.log('⚡ GeekBoard Performance Optimizer chargé');
})();
