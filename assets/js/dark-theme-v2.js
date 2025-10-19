/**
 * 🌙 GESTIONNAIRE THÈME SOMBRE V2
 * Optimisé pour mobile et desktop
 * Gestion des interactions tactiles et animations
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        debug: false,
        touchOptimization: true,
        animations: true,
        carouselTouch: true,
        ripple: false, // Désactivé par défaut pour un rendu plus professionnel
        loader: true /* afficher le loader pro à l'init */
    };
    
    // Utilitaires
    const log = (...args) => CONFIG.debug && console.log('🌙 Dark Theme V2:', ...args);
    const isMobile = () => window.innerWidth <= 768;
    const isDarkMode = () => window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    /**
     * 📱 Optimisations tactiles pour mobile
     */
    function optimizeTouchInteractions() {
        if (!CONFIG.touchOptimization || !isDarkMode()) return;
        
        log('Optimisation des interactions tactiles...');
        
        // Sélecteurs d'éléments à optimiser
        const touchElements = [
            '.card', '.stat-card', '.action-card', '.dashboard-card',
            '.btn', '.action-button', '.dashboard-action-button',
            '.carousel', '.carousel-inner', '.carousel-item',
            '.form-control', '.form-select'
        ];
        
        touchElements.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                // Propriétés tactiles essentielles
                element.style.touchAction = 'manipulation';
                element.style.webkitOverflowScrolling = 'touch';
                element.style.pointerEvents = 'auto';
                
                // Supprimer le highlight bleu sur mobile
                element.style.webkitTapHighlightColor = 'transparent';
                
                // Optimisation pour iOS Safari
                if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                    element.style.webkitTransform = 'translateZ(0)';
                    element.style.transform = 'translateZ(0)';
                }
            });
        });
        
        log('✅ Interactions tactiles optimisées');
    }
    
    /**
     * 🎠 Optimisation des carousels Bootstrap
     */
    function optimizeCarousels() {
        if (!CONFIG.carouselTouch || !isDarkMode()) return;
        
        log('Optimisation des carousels...');
        
        const carousels = document.querySelectorAll('.carousel');
        carousels.forEach(carousel => {
            // Propriétés tactiles spécifiques aux carousels
            carousel.style.touchAction = 'pan-y';
            carousel.style.overflow = 'visible';
            carousel.style.position = 'relative';
            
            // Optimiser les éléments internes
            const inner = carousel.querySelector('.carousel-inner');
            if (inner) {
                inner.style.touchAction = 'pan-y';
                inner.style.overflow = 'visible';
            }
            
            const items = carousel.querySelectorAll('.carousel-item');
            items.forEach(item => {
                item.style.touchAction = 'pan-y';
                item.style.overflow = 'visible';
                item.style.position = 'relative';
            });
            
            // Réinitialiser Bootstrap Carousel avec les bonnes options
            if (window.bootstrap && window.bootstrap.Carousel) {
                const existingInstance = window.bootstrap.Carousel.getInstance(carousel);
                if (existingInstance) {
                    existingInstance.dispose();
                }
                
                // Créer une nouvelle instance avec touch activé
                new window.bootstrap.Carousel(carousel, {
                    touch: true,
                    interval: false,
                    wrap: true,
                    keyboard: true
                });
            }
        });
        
        log('✅ Carousels optimisés');
    }
    
    /**
     * ✨ Effets visuels et animations
     */
    function initVisualEffects() {
        if (!CONFIG.animations || !isDarkMode()) return;
        
        // Réduire l'animation d'apparition
        const cards = document.querySelectorAll('body[data-page="accueil"] .card, body[data-page="accueil"] .stat-card, body[data-page="accueil"] .action-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(8px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, Math.min(index * 80, 400));
        });

        // Ripple optionnel (désactivé par défaut)
        if (CONFIG.ripple) {
            const buttons = document.querySelectorAll('body[data-page="accueil"] .btn, body[data-page="accueil"] .action-button');
            buttons.forEach(button => {
                button.addEventListener('click', createRippleEffect);
            });
        }
        
        log('✅ Effets visuels initialisés');
    }
    
    /**
     * 🌊 Effet de ripple
     */
    function createRippleEffect(event) {
        const button = event.currentTarget;
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(0, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
            z-index: 1;
        `;
        
        // Ajouter l'animation CSS si elle n'existe pas
        if (!document.querySelector('#ripple-animation')) {
            const style = document.createElement('style');
            style.id = 'ripple-animation';
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        button.style.position = 'relative';
        button.style.overflow = 'hidden';
        button.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    }
    
    /**
     * 🔧 Corrections spécifiques pour iOS
     */
    function applyIOSFixes() {
        if (!/iPad|iPhone|iPod/.test(navigator.userAgent) || !isDarkMode()) return;
        
        log('Application des corrections iOS...');
        
        // Fix pour le scroll momentum sur iOS
        document.body.style.webkitOverflowScrolling = 'touch';
        
        // Fix pour les éléments position fixed sur iOS
        const fixedElements = document.querySelectorAll('.fixed-top, .fixed-bottom, .navbar-fixed-top');
        fixedElements.forEach(element => {
            element.style.webkitTransform = 'translateZ(0)';
            element.style.transform = 'translateZ(0)';
        });
        
        log('✅ Corrections iOS appliquées');
    }

    function mountAccueilLoader() {
        if (!CONFIG.loader) return null;
        if (document.getElementById('accueilPageLoader')) return document.getElementById('accueilPageLoader');
        const html = `
            <div id="accueilPageLoader" class="accueil-page-loader">
              <div class="loader-wrapper">
                <div class="loader-circle"></div>
                <div class="loader-text">
                  <span class="loader-letter">S</span>
                  <span class="loader-letter">E</span>
                  <span class="loader-letter">R</span>
                  <span class="loader-letter">V</span>
                  <span class="loader-letter">O</span>
                </div>
              </div>
            </div>`;
        const container = document.createElement('div');
        container.innerHTML = html;
        const node = container.firstElementChild;
        document.body.appendChild(node);
        return node;
    }

    function hideAccueilLoader() {
        const loader = document.getElementById('accueilPageLoader');
        if (!loader) return;
        loader.classList.add('fade-out');
        setTimeout(() => loader.classList.add('hidden'), 500);
    }

    function removeLegacyLoaders() {
        const selectors = [
            '.loader', '.loading', '.loading-overlay', '.page-loader', '.preloader', '.global-loader',
            '.spinner', '#loader', '#pageLoader', '#globalLoader'
        ];
        selectors.forEach(sel => {
            document.querySelectorAll(sel).forEach(el => {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
                el.style.opacity = '0';
                el.style.pointerEvents = 'none';
            });
        });
    }

    function forceButtonCentering() {
        log('Forçage du centrage des boutons...');
        
        // Sélecteurs des conteneurs de boutons
        const containerSelectors = [
            '.quick-actions-grid', '.futuristic-action-grid',
            '.action-buttons-container', '.dashboard-actions',
            '.actions-row', '.quick-actions', '.actions-container'
        ];
        
        containerSelectors.forEach(selector => {
            const containers = document.querySelectorAll(selector);
            containers.forEach(container => {
                container.style.display = 'flex';
                container.style.flexWrap = 'wrap';
                container.style.justifyContent = 'center';
                container.style.alignItems = 'center';
                container.style.gap = '20px';
                container.style.margin = '0 auto';
                container.style.textAlign = 'center';
                container.style.width = '100%';
            });
        });
        
        // Forcer le centrage des rangées Bootstrap
        const rows = document.querySelectorAll('.row');
        rows.forEach(row => {
            const hasActionButtons = row.querySelector('.action-card, .futuristic-action-btn, .dashboard-action-button');
            if (hasActionButtons) {
                row.style.display = 'flex';
                row.style.flexWrap = 'wrap';
                row.style.justifyContent = 'center';
                row.style.alignItems = 'center';
                row.style.gap = '20px';
                row.style.textAlign = 'center';
                
                // Centrer les colonnes dans cette rangée
                const cols = row.querySelectorAll('[class*="col-"]');
                cols.forEach(col => {
                    col.style.display = 'flex';
                    col.style.justifyContent = 'center';
                    col.style.alignItems = 'center';
                    col.style.margin = '0';
                    col.style.flex = '0 0 auto';
                });
            }
        });
        
        log('✅ Centrage des boutons forcé');
    }

    function normalizeActionIcons() {
        // Uniformiser les dimensions des conteneurs d'icône
        const iconBoxes = document.querySelectorAll('.action-card .action-icon, .futuristic-action-btn .action-icon');
        iconBoxes.forEach(box => {
            box.style.width = '60px';
            box.style.height = '60px';
            box.style.display = 'flex';
            box.style.alignItems = 'center';
            box.style.justifyContent = 'center';
        });
        // Uniformiser la taille des icônes internes
        const icons = document.querySelectorAll('.action-card .action-icon i, .futuristic-action-btn .action-icon i');
        icons.forEach(i => {
            i.style.fontSize = '24px';
            i.style.width = '24px';
            i.style.height = '24px';
            i.style.lineHeight = '1';
        });
    }
    
    /**
     * 🚀 Initialisation principale
     */
    function init() {
        // Restreindre à la page accueil
        if (document.body.getAttribute('data-page') !== 'accueil') return;
        if (!isDarkMode()) return;

        // Masquer tout loader hérité avant d'afficher le nôtre
        removeLegacyLoaders();

        // Loader dès le début
        const loaderNode = mountAccueilLoader();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        optimizeTouchInteractions();
        optimizeCarousels();
        initVisualEffects();
        applyIOSFixes();
        forceButtonCentering();
        normalizeActionIcons();

        // Masquer le loader après le chargement complet
        window.addEventListener('load', () => {
            // Nettoyer encore une fois d'éventuels loaders démarrés tard
            removeLegacyLoaders();
            // Forcer le centrage après le chargement complet
            setTimeout(() => {
                forceButtonCentering();
                normalizeActionIcons();
                hideAccueilLoader();
            }, 300);
        });

        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                optimizeTouchInteractions();
                optimizeCarousels();
                forceButtonCentering();
                normalizeActionIcons();
            }, 200);
        });
        
        log('✅ Thème sombre V2 initialisé avec succès');
    }
    
    /**
     * 🎯 Démarrage automatique
     */
    init();
    
    // Exposer l'API publique si nécessaire
    window.DarkThemeV2 = {
        init,
        optimizeTouchInteractions,
        optimizeCarousels,
        config: CONFIG
    };
    
})();
