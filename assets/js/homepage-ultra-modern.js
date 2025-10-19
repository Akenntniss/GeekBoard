/**
 * 🚀 HOMEPAGE ULTRA MODERNE - INTERACTIONS OPTIMISÉES
 * Dual Theme: Corporate Moderne + Hyper Futuriste
 * Performance maximale avec GPU acceleration
 */

(function() {
    'use strict';
    
    // Configuration optimisée
    const CONFIG = {
        debug: false,
        performance: {
            useRAF: true,
            debounceDelay: 16,
            throttleDelay: 100
        },
        effects: {
            enabled: true,
            intensity: 'adaptive', // adaptive, subtle, full
            respectMotionPreference: true
        },
        touch: {
            enabled: true,
            feedback: true,
            swipeThreshold: 50
        }
    };
    
    // Utilitaires optimisés
    const utils = {
        log: (...args) => CONFIG.debug && console.log('🚀 Ultra Modern:', ...args),
        isMobile: () => window.innerWidth <= 768,
        isDarkMode: () => window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches,
        isReducedMotion: () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        supportsBackdrop: () => CSS.supports('backdrop-filter', 'blur(10px)'),
        
        // Debounce optimisé
        debounce: (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Throttle avec RAF
        throttle: (func, limit) => {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    if (CONFIG.performance.useRAF) {
                        requestAnimationFrame(() => inThrottle = false);
                    } else {
                        setTimeout(() => inThrottle = false, limit);
                    }
                }
            };
        }
    };
    
    /**
     * 🎨 Gestionnaire d'effets visuels adaptatifs
     */
    class AdaptiveEffects {
        constructor() {
            this.initialized = false;
            this.observers = new Map();
            this.activeAnimations = new Set();
            this.performanceMode = this.detectPerformanceMode();
        }
        
        detectPerformanceMode() {
            // Détecter les capacités de l'appareil
            const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            const isLowEnd = connection && connection.effectiveType && 
                           (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g');
            const isReducedMotion = utils.isReducedMotion();
            
            if (isReducedMotion || isLowEnd) return 'minimal';
            if (utils.isMobile()) return 'optimized';
            return 'full';
        }
        
        init() {
            if (this.initialized) return;
            
            utils.log('Initialisation des effets adaptatifs...');
            utils.log('Mode performance:', this.performanceMode);
            
            this.setupIntersectionObserver();
            this.initCardEffects();
            this.initButtonEffects();
            this.initScrollEffects();
            this.initParallaxEffects();
            
            this.initialized = true;
            utils.log('✅ Effets adaptatifs initialisés');
        }
        
        /**
         * Observer d'intersection optimisé
         */
        setupIntersectionObserver() {
            if (!window.IntersectionObserver || this.performanceMode === 'minimal') return;
            
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
            
            // Observer les éléments principaux
            const elements = document.querySelectorAll(`
                .stat-card, .futuristic-stat-card,
                .statistics-container, .futuristic-card,
                .action-button, .dashboard-action-button
            `);
            
            elements.forEach(el => {
                observer.observe(el);
                // Pré-optimiser pour GPU
                el.style.willChange = 'transform, opacity';
            });
            
            this.observers.set('intersection', observer);
        }
        
        /**
         * Animation d'apparition optimisée
         */
        animateElementIn(element) {
            if (this.performanceMode === 'minimal') return;
            
            const animationId = `appear_${Date.now()}_${Math.random()}`;
            this.activeAnimations.add(animationId);
            
            // Configuration selon le mode performance
            const config = {
                minimal: { duration: 0, delay: 0 },
                optimized: { duration: 300, delay: 0 },
                full: { duration: 600, delay: Math.random() * 200 }
            };
            
            const { duration, delay } = config[this.performanceMode];
            
            if (duration === 0) return;
            
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px) scale(0.95)';
            element.style.transition = `all ${duration}ms cubic-bezier(0.4, 0, 0.2, 1)`;
            
            setTimeout(() => {
                if (this.activeAnimations.has(animationId)) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0) scale(1)';
                    
                    // Nettoyer après animation
                    setTimeout(() => {
                        element.style.willChange = 'auto';
                        this.activeAnimations.delete(animationId);
                    }, duration);
                }
            }, delay);
        }
        
        /**
         * Effets pour les cartes optimisés
         */
        initCardEffects() {
            const cards = document.querySelectorAll('.stat-card, .futuristic-stat-card');
            
            cards.forEach(card => {
                this.addCardInteractions(card);
            });
        }
        
        addCardInteractions(card) {
            if (this.performanceMode === 'minimal') return;
            
            let hoverTimeout;
            const isDark = utils.isDarkMode();
            
            // Hover effects
            card.addEventListener('mouseenter', () => {
                clearTimeout(hoverTimeout);
                
                if (CONFIG.performance.useRAF) {
                    requestAnimationFrame(() => {
                        card.style.transform = 'translateY(-4px) scale(1.02)';
                        if (this.performanceMode === 'full' && isDark) {
                            this.addShimmerEffect(card);
                        }
                    });
                } else {
                    card.style.transform = 'translateY(-4px) scale(1.02)';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                hoverTimeout = setTimeout(() => {
                    if (CONFIG.performance.useRAF) {
                        requestAnimationFrame(() => {
                            card.style.transform = 'translateY(0) scale(1)';
                            this.removeShimmerEffect(card);
                        });
                    } else {
                        card.style.transform = 'translateY(0) scale(1)';
                    }
                }, 50);
            });
            
            // Click effects
            card.addEventListener('click', (e) => {
                if (this.performanceMode !== 'minimal') {
                    this.createRippleEffect(e, card);
                }
            });
            
            // Touch feedback pour mobile
            if (utils.isMobile() && CONFIG.touch.feedback) {
                this.addTouchFeedback(card);
            }
        }
        
        /**
         * Effet de brillance optimisé
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
                    rgba(0, 212, 255, 0.15) 50%, 
                    transparent 100%);
                transition: left 0.8s cubic-bezier(0.4, 0, 0.2, 1);
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
                setTimeout(() => shimmer.remove(), 800);
            }
        }
        
        /**
         * Effet ripple optimisé
         */
        createRippleEffect(event, element) {
            const rect = element.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            
            const ripple = document.createElement('div');
            const isDark = utils.isDarkMode();
            const color = isDark ? 'rgba(0, 212, 255, 0.3)' : 'rgba(59, 130, 246, 0.2)';
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: radial-gradient(circle, ${color} 0%, transparent 70%);
                border-radius: 50%;
                transform: scale(0);
                animation: ultraRipple 0.6s cubic-bezier(0.4, 0, 0.2, 1);
                pointer-events: none;
                z-index: 2;
            `;
            
            // Ajouter l'animation CSS si nécessaire
            if (!document.querySelector('#ultra-ripple-animation')) {
                const style = document.createElement('style');
                style.id = 'ultra-ripple-animation';
                style.textContent = `
                    @keyframes ultraRipple {
                        0% {
                            transform: scale(0);
                            opacity: 1;
                        }
                        50% {
                            opacity: 0.5;
                        }
                        100% {
                            transform: scale(2.5);
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
         * Feedback tactile pour mobile
         */
        addTouchFeedback(element) {
            element.addEventListener('touchstart', () => {
                if (CONFIG.performance.useRAF) {
                    requestAnimationFrame(() => {
                        element.style.transform = 'scale(0.98)';
                        element.style.transition = 'transform 0.1s ease';
                    });
                }
            }, { passive: true });
            
            element.addEventListener('touchend', () => {
                setTimeout(() => {
                    if (CONFIG.performance.useRAF) {
                        requestAnimationFrame(() => {
                            element.style.transform = 'scale(1)';
                        });
                    }
                }, 100);
            }, { passive: true });
        }
        
        /**
         * Effets pour les boutons
         */
        initButtonEffects() {
            const buttons = document.querySelectorAll(`
                .action-button, .dashboard-action-button, .btn,
                .btn-primary, .btn-secondary
            `);
            
            buttons.forEach(button => {
                this.addButtonInteractions(button);
            });
        }
        
        addButtonInteractions(button) {
            if (this.performanceMode === 'minimal') return;
            
            // Effet de pression
            button.addEventListener('mousedown', () => {
                if (CONFIG.performance.useRAF) {
                    requestAnimationFrame(() => {
                        button.style.transform = 'scale(0.96)';
                    });
                }
            });
            
            button.addEventListener('mouseup', () => {
                if (CONFIG.performance.useRAF) {
                    requestAnimationFrame(() => {
                        button.style.transform = 'scale(1)';
                    });
                }
            });
            
            button.addEventListener('mouseleave', () => {
                if (CONFIG.performance.useRAF) {
                    requestAnimationFrame(() => {
                        button.style.transform = 'scale(1)';
                    });
                }
            });
            
            // Focus professionnel
            button.addEventListener('focus', () => {
                const isDark = utils.isDarkMode();
                const focusColor = isDark ? 'rgba(0, 212, 255, 0.3)' : 'rgba(59, 130, 246, 0.2)';
                button.style.boxShadow = `0 0 0 3px ${focusColor}`;
            });
            
            button.addEventListener('blur', () => {
                button.style.boxShadow = '';
            });
        }
        
        /**
         * Effets de scroll optimisés
         */
        initScrollEffects() {
            if (this.performanceMode === 'minimal') return;
            
            const handleScroll = utils.throttle(() => {
                this.updateScrollEffects();
            }, CONFIG.performance.throttleDelay);
            
            window.addEventListener('scroll', handleScroll, { passive: true });
        }
        
        updateScrollEffects() {
            const scrollY = window.scrollY;
            const cards = document.querySelectorAll('.stat-card, .futuristic-stat-card');
            
            if (this.performanceMode === 'full') {
                cards.forEach((card, index) => {
                    const rect = card.getBoundingClientRect();
                    const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
                    
                    if (isVisible) {
                        const parallaxOffset = scrollY * 0.01 * (index % 3 - 1);
                        if (CONFIG.performance.useRAF) {
                            requestAnimationFrame(() => {
                                card.style.transform = `translateY(${parallaxOffset}px)`;
                            });
                        }
                    }
                });
            }
        }
        
        /**
         * Effets parallax subtils
         */
        initParallaxEffects() {
            if (this.performanceMode !== 'full' || utils.isMobile()) return;
            
            const handleMouseMove = utils.throttle((e) => {
                const { clientX, clientY } = e;
                const { innerWidth, innerHeight } = window;
                
                const xPercent = (clientX / innerWidth - 0.5) * 2;
                const yPercent = (clientY / innerHeight - 0.5) * 2;
                
                const containers = document.querySelectorAll('.statistics-container, .futuristic-card');
                containers.forEach((container, index) => {
                    const intensity = (index % 2 + 1) * 0.5;
                    const translateX = xPercent * intensity;
                    const translateY = yPercent * intensity;
                    
                    if (CONFIG.performance.useRAF) {
                        requestAnimationFrame(() => {
                            container.style.transform = `translate(${translateX}px, ${translateY}px)`;
                        });
                    }
                });
            }, CONFIG.performance.debounceDelay);
            
            document.addEventListener('mousemove', handleMouseMove, { passive: true });
        }
        
        /**
         * Nettoyage des ressources
         */
        destroy() {
            this.observers.forEach(observer => observer.disconnect());
            this.observers.clear();
            this.activeAnimations.clear();
            this.initialized = false;
        }
    }
    
    /**
     * 📱 Optimisations tactiles avancées
     */
    class TouchOptimizer {
        constructor() {
            this.initialized = false;
        }
        
        init() {
            if (this.initialized || !CONFIG.touch.enabled) return;
            
            utils.log('Optimisation tactile avancée...');
            
            this.optimizeElements();
            this.setupCarouselTouch();
            this.addSwipeGestures();
            
            this.initialized = true;
            utils.log('✅ Optimisations tactiles appliquées');
        }
        
        optimizeElements() {
            const elements = document.querySelectorAll(`
                .stat-card, .futuristic-stat-card,
                .action-button, .dashboard-action-button, .btn,
                .statistics-container, .futuristic-card
            `);
            
            elements.forEach(element => {
                // Propriétés tactiles optimales
                element.style.touchAction = 'manipulation';
                element.style.webkitOverflowScrolling = 'touch';
                element.style.webkitTapHighlightColor = 'transparent';
                
                // Optimisation iOS
                if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                    element.style.webkitTransform = 'translateZ(0)';
                    element.style.webkitBackfaceVisibility = 'hidden';
                }
            });
        }
        
        setupCarouselTouch() {
            const carousels = document.querySelectorAll('.carousel');
            
            carousels.forEach(carousel => {
                carousel.style.touchAction = 'pan-y';
                carousel.style.overflow = 'visible';
                
                // Réinitialiser Bootstrap Carousel
                if (window.bootstrap && window.bootstrap.Carousel) {
                    const instance = window.bootstrap.Carousel.getInstance(carousel);
                    if (instance) instance.dispose();
                    
                    new window.bootstrap.Carousel(carousel, {
                        touch: true,
                        interval: false,
                        wrap: true,
                        keyboard: true
                    });
                }
            });
        }
        
        addSwipeGestures() {
            if (!utils.isMobile()) return;
            
            const cards = document.querySelectorAll('.stat-card, .futuristic-stat-card');
            
            cards.forEach(card => {
                let startX = 0;
                let startY = 0;
                
                card.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    startY = e.touches[0].clientY;
                }, { passive: true });
                
                card.addEventListener('touchend', (e) => {
                    const endX = e.changedTouches[0].clientX;
                    const endY = e.changedTouches[0].clientY;
                    
                    const deltaX = Math.abs(endX - startX);
                    const deltaY = Math.abs(endY - startY);
                    
                    // Détecter swipe horizontal
                    if (deltaX > CONFIG.touch.swipeThreshold && deltaX > deltaY) {
                        const direction = endX > startX ? 'right' : 'left';
                        this.handleSwipe(card, direction);
                    }
                }, { passive: true });
            });
        }
        
        handleSwipe(element, direction) {
            // Effet visuel de swipe
            const translateX = direction === 'right' ? '10px' : '-10px';
            
            if (CONFIG.performance.useRAF) {
                requestAnimationFrame(() => {
                    element.style.transform = `translateX(${translateX})`;
                    element.style.transition = 'transform 0.2s ease';
                    
                    setTimeout(() => {
                        requestAnimationFrame(() => {
                            element.style.transform = 'translateX(0)';
                        });
                    }, 200);
                });
            }
        }
    }
    
    /**
     * 🚀 Gestionnaire principal optimisé
     */
    class UltraModernHomepage {
        constructor() {
            this.effects = new AdaptiveEffects();
            this.touch = new TouchOptimizer();
            this.initialized = false;
        }
        
        init() {
            if (this.initialized) return;
            
            utils.log('🚀 Initialisation Ultra Modern Homepage...');
            
            // Attendre le DOM
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
                return;
            }
            
            // Initialiser les modules
            this.effects.init();
            this.touch.init();
            
            // Gérer les changements responsive
            this.setupResponsiveHandlers();
            
            // Optimisations post-chargement
            window.addEventListener('load', () => {
                setTimeout(() => {
                    this.optimizePostLoad();
                }, 500);
            });
            
            this.initialized = true;
            utils.log('✅ Ultra Modern Homepage initialisé');
        }
        
        setupResponsiveHandlers() {
            const handleResize = utils.debounce(() => {
                // Réinitialiser les optimisations tactiles si nécessaire
                if (utils.isMobile()) {
                    this.touch.init();
                }
            }, 250);
            
            window.addEventListener('resize', handleResize);
        }
        
        optimizePostLoad() {
            // Nettoyer les will-change après les animations initiales
            const elements = document.querySelectorAll('[style*="will-change"]');
            elements.forEach(el => {
                setTimeout(() => {
                    el.style.willChange = 'auto';
                }, 1000);
            });
            
            utils.log('🎯 Optimisations post-chargement appliquées');
        }
        
        destroy() {
            this.effects.destroy();
            this.initialized = false;
        }
    }
    
    // Initialisation automatique optimisée
    const homepage = new UltraModernHomepage();
    
    // Démarrage intelligent
    if (document.readyState === 'complete') {
        homepage.init();
    } else {
        homepage.init();
    }
    
    // API publique
    window.UltraModernHomepage = {
        init: () => homepage.init(),
        destroy: () => homepage.destroy(),
        config: CONFIG,
        utils
    };
    
})();
