/**
 * 🌙 EFFETS HYPER FUTURISTES PROFESSIONNELS
 * Interactions subtiles et élégantes pour un environnement corporate
 * Optimisé pour performance et accessibilité
 */

(function() {
    'use strict';
    
    // Configuration professionnelle
    const CONFIG = {
        debug: false,
        effects: {
            enabled: true,
            intensity: 'subtle', // subtle, medium, high
            performance: 'optimized' // basic, optimized, premium
        },
        animations: {
            duration: {
                fast: 150,
                normal: 300,
                slow: 500
            },
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
        },
        touch: {
            enabled: true,
            feedback: true
        }
    };
    
    // Utilitaires
    const log = (...args) => CONFIG.debug && console.log('🌙 Hyper Futuristic Pro:', ...args);
    const isMobile = () => window.innerWidth <= 768;
    const isDarkMode = () => window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isReducedMotion = () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    /**
     * 🎨 Gestionnaire d'effets visuels professionnels
     */
    class ProfessionalEffects {
        constructor() {
            this.initialized = false;
            this.activeEffects = new Set();
            this.observers = new Map();
        }
        
        init() {
            if (!isDarkMode() || this.initialized) return;
            
            log('Initialisation des effets professionnels...');
            
            this.setupIntersectionObserver();
            this.initCardEffects();
            this.initButtonEffects();
            this.initFormEffects();
            this.initScrollEffects();
            
            this.initialized = true;
            log('✅ Effets professionnels initialisés');
        }
        
        /**
         * Observer d'intersection pour les animations d'apparition
         */
        setupIntersectionObserver() {
            if (!window.IntersectionObserver) return;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateElementIn(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
            
            // Observer les cartes et éléments principaux
            const elements = document.querySelectorAll('.card, .stat-card, .action-card, .dashboard-card');
            elements.forEach(el => observer.observe(el));
            
            this.observers.set('intersection', observer);
        }
        
        /**
         * Animation d'apparition élégante
         */
        animateElementIn(element) {
            if (isReducedMotion()) return;
            
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px) scale(0.95)';
            element.style.transition = `all ${CONFIG.animations.duration.normal}ms ${CONFIG.animations.easing}`;
            
            requestAnimationFrame(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0) scale(1)';
            });
        }
        
        /**
         * Effets pour les cartes
         */
        initCardEffects() {
            const cards = document.querySelectorAll('.card, .stat-card, .action-card, .dashboard-card');
            
            cards.forEach(card => {
                this.addCardHoverEffect(card);
                this.addCardClickEffect(card);
            });
        }
        
        addCardHoverEffect(card) {
            let hoverTimeout;
            
            card.addEventListener('mouseenter', () => {
                if (isReducedMotion()) return;
                
                clearTimeout(hoverTimeout);
                card.style.transition = `all ${CONFIG.animations.duration.normal}ms ${CONFIG.animations.easing}`;
                card.style.transform = 'translateY(-4px) scale(1.01)';
                
                // Effet de brillance subtile
                this.addShimmerEffect(card);
            });
            
            card.addEventListener('mouseleave', () => {
                if (isReducedMotion()) return;
                
                hoverTimeout = setTimeout(() => {
                    card.style.transform = 'translateY(0) scale(1)';
                    this.removeShimmerEffect(card);
                }, 50);
            });
        }
        
        addCardClickEffect(card) {
            card.addEventListener('click', (e) => {
                if (isReducedMotion()) return;
                
                // Effet de ripple professionnel
                this.createProfessionalRipple(e, card);
            });
        }
        
        /**
         * Effet de brillance subtile
         */
        addShimmerEffect(element) {
            if (element.querySelector('.shimmer-overlay')) return;
            
            const shimmer = document.createElement('div');
            shimmer.className = 'shimmer-overlay';
            shimmer.style.cssText = `
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, 
                    transparent 0%, 
                    rgba(59, 130, 246, 0.1) 50%, 
                    transparent 100%);
                transition: left 0.6s ease;
                pointer-events: none;
                z-index: 1;
            `;
            
            element.style.position = 'relative';
            element.style.overflow = 'hidden';
            element.appendChild(shimmer);
            
            requestAnimationFrame(() => {
                shimmer.style.left = '100%';
            });
        }
        
        removeShimmerEffect(element) {
            const shimmer = element.querySelector('.shimmer-overlay');
            if (shimmer) {
                setTimeout(() => shimmer.remove(), 600);
            }
        }
        
        /**
         * Effets pour les boutons
         */
        initButtonEffects() {
            const buttons = document.querySelectorAll('.btn, .action-button, .dashboard-action-button');
            
            buttons.forEach(button => {
                this.addButtonEffects(button);
            });
        }
        
        addButtonEffects(button) {
            // Effet de pression
            button.addEventListener('mousedown', () => {
                if (isReducedMotion()) return;
                button.style.transform = 'scale(0.98)';
            });
            
            button.addEventListener('mouseup', () => {
                if (isReducedMotion()) return;
                button.style.transform = 'scale(1)';
            });
            
            button.addEventListener('mouseleave', () => {
                if (isReducedMotion()) return;
                button.style.transform = 'scale(1)';
            });
            
            // Effet de focus professionnel
            button.addEventListener('focus', () => {
                if (isReducedMotion()) return;
                button.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.2), 0 4px 16px rgba(0, 0, 0, 0.25)';
            });
            
            button.addEventListener('blur', () => {
                button.style.boxShadow = '';
            });
        }
        
        /**
         * Ripple effect professionnel
         */
        createProfessionalRipple(event, element) {
            const rect = element.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
                border-radius: 50%;
                transform: scale(0);
                animation: professionalRipple 0.6s linear;
                pointer-events: none;
                z-index: 2;
            `;
            
            // Ajouter l'animation CSS si elle n'existe pas
            if (!document.querySelector('#professional-ripple-animation')) {
                const style = document.createElement('style');
                style.id = 'professional-ripple-animation';
                style.textContent = `
                    @keyframes professionalRipple {
                        0% {
                            transform: scale(0);
                            opacity: 1;
                        }
                        50% {
                            opacity: 0.5;
                        }
                        100% {
                            transform: scale(2);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
            
            element.style.position = 'relative';
            element.style.overflow = 'hidden';
            element.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        }
        
        /**
         * Effets pour les formulaires
         */
        initFormEffects() {
            const inputs = document.querySelectorAll('input, textarea, select, .form-control, .form-select');
            
            inputs.forEach(input => {
                this.addInputEffects(input);
            });
        }
        
        addInputEffects(input) {
            // Effet de focus élégant
            input.addEventListener('focus', () => {
                if (isReducedMotion()) return;
                
                input.style.transition = `all ${CONFIG.animations.duration.normal}ms ${CONFIG.animations.easing}`;
                input.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', () => {
                if (isReducedMotion()) return;
                
                input.style.transform = 'scale(1)';
            });
            
            // Effet de validation visuelle
            input.addEventListener('input', () => {
                this.validateInputVisually(input);
            });
        }
        
        validateInputVisually(input) {
            if (!input.value) return;
            
            // Effet de validation subtil
            const isValid = input.checkValidity ? input.checkValidity() : true;
            
            if (isValid) {
                input.style.borderColor = 'rgba(16, 185, 129, 0.5)';
                input.style.boxShadow = '0 0 0 1px rgba(16, 185, 129, 0.2)';
            } else {
                input.style.borderColor = 'rgba(239, 68, 68, 0.5)';
                input.style.boxShadow = '0 0 0 1px rgba(239, 68, 68, 0.2)';
            }
        }
        
        /**
         * Effets de scroll
         */
        initScrollEffects() {
            let ticking = false;
            
            const handleScroll = () => {
                if (!ticking) {
                    requestAnimationFrame(() => {
                        this.updateScrollEffects();
                        ticking = false;
                    });
                    ticking = true;
                }
            };
            
            window.addEventListener('scroll', handleScroll, { passive: true });
        }
        
        updateScrollEffects() {
            const scrollY = window.scrollY;
            const cards = document.querySelectorAll('.card, .stat-card, .action-card');
            
            cards.forEach((card, index) => {
                if (isReducedMotion()) return;
                
                const rect = card.getBoundingClientRect();
                const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
                
                if (isVisible) {
                    const parallaxOffset = scrollY * 0.02 * (index % 3 - 1);
                    card.style.transform = `translateY(${parallaxOffset}px)`;
                }
            });
        }
        
        /**
         * Nettoyage des ressources
         */
        destroy() {
            this.observers.forEach(observer => observer.disconnect());
            this.observers.clear();
            this.activeEffects.clear();
            this.initialized = false;
        }
    }
    
    /**
     * 📱 Optimisations tactiles professionnelles
     */
    class ProfessionalTouch {
        constructor() {
            this.touchOptimized = false;
        }
        
        init() {
            if (!isDarkMode() || this.touchOptimized) return;
            
            log('Optimisation tactile professionnelle...');
            
            this.optimizeTouchElements();
            this.setupCarouselTouch();
            this.addTouchFeedback();
            
            this.touchOptimized = true;
            log('✅ Optimisations tactiles appliquées');
        }
        
        optimizeTouchElements() {
            const touchElements = document.querySelectorAll(`
                .card, .stat-card, .action-card, .dashboard-card,
                .btn, .action-button, .dashboard-action-button,
                .carousel, .carousel-inner, .carousel-item,
                .form-control, .form-select, input, textarea, select
            `);
            
            touchElements.forEach(element => {
                // Propriétés tactiles essentielles
                element.style.touchAction = 'manipulation';
                element.style.webkitOverflowScrolling = 'touch';
                element.style.webkitTapHighlightColor = 'transparent';
                
                // Optimisation iOS Safari
                if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                    element.style.webkitTransform = 'translateZ(0)';
                }
            });
        }
        
        setupCarouselTouch() {
            const carousels = document.querySelectorAll('.carousel');
            
            carousels.forEach(carousel => {
                // Propriétés tactiles spécifiques
                carousel.style.touchAction = 'pan-y';
                carousel.style.overflow = 'visible';
                
                // Réinitialiser Bootstrap Carousel avec touch
                if (window.bootstrap && window.bootstrap.Carousel) {
                    const existingInstance = window.bootstrap.Carousel.getInstance(carousel);
                    if (existingInstance) {
                        existingInstance.dispose();
                    }
                    
                    new window.bootstrap.Carousel(carousel, {
                        touch: true,
                        interval: false,
                        wrap: true,
                        keyboard: true
                    });
                }
            });
        }
        
        addTouchFeedback() {
            if (!CONFIG.touch.feedback) return;
            
            const interactiveElements = document.querySelectorAll('.btn, .action-button, .card');
            
            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', () => {
                    if (isReducedMotion()) return;
                    element.style.transform = 'scale(0.98)';
                    element.style.transition = 'transform 0.1s ease';
                }, { passive: true });
                
                element.addEventListener('touchend', () => {
                    if (isReducedMotion()) return;
                    setTimeout(() => {
                        element.style.transform = 'scale(1)';
                    }, 100);
                }, { passive: true });
            });
        }
    }
    
    /**
     * 🚀 Gestionnaire principal
     */
    class HyperFuturisticPro {
        constructor() {
            this.effects = new ProfessionalEffects();
            this.touch = new ProfessionalTouch();
            this.initialized = false;
        }
        
        init() {
            if (!isDarkMode()) {
                log('Mode clair détecté, pas d\'initialisation nécessaire');
                return;
            }
            
            if (this.initialized) return;
            
            log('🌙 Initialisation Hyper Futuristic Pro...');
            
            // Attendre que le DOM soit prêt
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
                return;
            }
            
            // Initialiser les modules
            this.effects.init();
            this.touch.init();
            
            // Gérer les changements de taille d'écran
            this.setupResponsiveHandlers();
            
            // Réinitialiser après chargement complet
            window.addEventListener('load', () => {
                setTimeout(() => {
                    this.effects.init();
                    this.touch.init();
                }, 500);
            });
            
            this.initialized = true;
            log('✅ Hyper Futuristic Pro initialisé avec succès');
        }
        
        setupResponsiveHandlers() {
            let resizeTimeout;
            
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.touch.init();
                }, 250);
            });
        }
        
        destroy() {
            this.effects.destroy();
            this.initialized = false;
        }
    }
    
    // Initialisation automatique
    const hyperFuturisticPro = new HyperFuturisticPro();
    hyperFuturisticPro.init();
    
    // Exposer l'API publique
    window.HyperFuturisticPro = {
        init: () => hyperFuturisticPro.init(),
        destroy: () => hyperFuturisticPro.destroy(),
        config: CONFIG
    };
    
})();
