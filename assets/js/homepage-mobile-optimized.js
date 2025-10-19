/**
 * ====================================================================
 * 📱 HOMEPAGE MOBILE OPTIMIZED - JAVASCRIPT
 * Script optimisé pour mobile sans animations bloquantes
 * ====================================================================
 */

(function() {
    'use strict';
    
    // ====================================================================
    // DÉTECTION DE L'ENVIRONNEMENT
    // ====================================================================
    
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    // ====================================================================
    // CONFIGURATION OPTIMISÉE
    // ====================================================================
    
    const config = {
        // Désactiver les animations sur mobile ou si l'utilisateur préfère moins de mouvement
        enableAnimations: !isMobile && !prefersReducedMotion,
        
        // Délais optimisés pour mobile
        transitionDuration: isMobile ? 100 : 200,
        debounceDelay: isMobile ? 100 : 300,
        
        // Seuils tactiles
        touchThreshold: 10,
        swipeThreshold: 50
    };
    
    // ====================================================================
    // UTILITAIRES DE PERFORMANCE
    // ====================================================================
    
    // Debounce optimisé pour mobile
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Throttle pour les événements de scroll
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // ====================================================================
    // GESTION DU MODE SOMBRE
    // ====================================================================
    
    function initDarkModeToggle() {
        const darkModeToggle = document.querySelector('#darkModeToggle');
        const body = document.body;
        
        if (!darkModeToggle) return;
        
        // Vérifier la préférence sauvegardée
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Appliquer le thème initial
        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }
        
        // Gestionnaire de changement de thème
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        });
        
        // Écouter les changements de préférence système
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                if (e.matches) {
                    body.classList.add('dark-mode');
                    darkModeToggle.checked = true;
                } else {
                    body.classList.remove('dark-mode');
                    darkModeToggle.checked = false;
                }
            }
        });
    }
    
    // ====================================================================
    // GESTION DES BOUTONS ET INTERACTIONS
    // ====================================================================
    
    function initButtonInteractions() {
        // Feedback tactile simple pour les boutons
        const buttons = document.querySelectorAll('.btn, .action-button, .filter-btn');
        
        buttons.forEach(button => {
            // Feedback tactile au toucher
            button.addEventListener('touchstart', function() {
                if (isTouch) {
                    this.style.transform = 'scale(0.98)';
                    this.style.transition = 'transform 0.1s ease';
                }
            }, { passive: true });
            
            button.addEventListener('touchend', function() {
                if (isTouch) {
                    this.style.transform = '';
                }
            }, { passive: true });
            
            // Annuler le feedback si le toucher est annulé
            button.addEventListener('touchcancel', function() {
                if (isTouch) {
                    this.style.transform = '';
                }
            }, { passive: true });
        });
    }
    
    // ====================================================================
    // GESTION DES MODALS OPTIMISÉE
    // ====================================================================
    
    function initModalOptimizations() {
        // Laisser Bootstrap gérer les modals normalement
        // Juste ajouter quelques optimisations non-intrusives
        
        // Améliorer la performance des modals sur mobile
        document.addEventListener('show.bs.modal', function(e) {
            // Désactiver le scroll du body quand un modal s'ouvre
            document.body.style.overflow = 'hidden';
        });
        
        document.addEventListener('hidden.bs.modal', function(e) {
            // Réactiver le scroll du body quand un modal se ferme
            document.body.style.overflow = '';
        });
        
        // Optimisation tactile pour les boutons de fermeture
        const closeButtons = document.querySelectorAll('.modal .btn-close, .modal [data-bs-dismiss="modal"]');
        closeButtons.forEach(button => {
            button.addEventListener('touchstart', function() {
                if (isTouch) {
                    this.style.transform = 'scale(0.95)';
                    this.style.transition = 'transform 0.1s ease';
                }
            }, { passive: true });
            
            button.addEventListener('touchend', function() {
                if (isTouch) {
                    this.style.transform = '';
                }
            }, { passive: true });
        });
    }
    
    // ====================================================================
    // GESTION DES TABLEAUX RESPONSIVES
    // ====================================================================
    
    function initResponsiveTables() {
        const tables = document.querySelectorAll('.table-responsive');
        
        tables.forEach(tableContainer => {
            const table = tableContainer.querySelector('table');
            if (!table) return;
            
            // Ajouter des indicateurs de scroll sur mobile
            if (isMobile) {
                // Vérifier si le tableau déborde
                function checkOverflow() {
                    const isOverflowing = table.scrollWidth > tableContainer.clientWidth;
                    tableContainer.classList.toggle('has-overflow', isOverflowing);
                }
                
                // Vérifier au chargement et au redimensionnement
                checkOverflow();
                window.addEventListener('resize', debounce(checkOverflow, config.debounceDelay));
            }
        });
    }
    
    // ====================================================================
    // GESTION DU SCROLL OPTIMISÉE
    // ====================================================================
    
    function initScrollOptimizations() {
        let isScrolling = false;
        
        const handleScroll = throttle(function() {
            // Logique de scroll minimale pour éviter les blocages
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Ajouter une classe pour indiquer qu'on scroll (utile pour le CSS)
            document.body.classList.toggle('is-scrolling', scrollTop > 50);
            
        }, isMobile ? 16 : 10); // 60fps sur mobile, plus fluide sur desktop
        
        window.addEventListener('scroll', handleScroll, { passive: true });
    }
    
    // ====================================================================
    // GESTION DES FORMULAIRES OPTIMISÉE
    // ====================================================================
    
    function initFormOptimizations() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Validation en temps réel optimisée
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                // Validation différée pour éviter les blocages
                const validateInput = debounce(function() {
                    // Logique de validation simple
                    if (input.checkValidity()) {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                    }
                }, config.debounceDelay);
                
                input.addEventListener('input', validateInput);
                input.addEventListener('blur', validateInput);
            });
        });
    }
    
    // ====================================================================
    // GESTION DES FILTRES ET RECHERCHE
    // ====================================================================
    
    function initFilterOptimizations() {
        const searchInputs = document.querySelectorAll('input[type="search"], .search-input');
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        // Optimiser la recherche
        searchInputs.forEach(input => {
            const performSearch = debounce(function() {
                const query = input.value.toLowerCase().trim();
                const targetContainer = document.querySelector(input.getAttribute('data-target') || '.searchable-content');
                
                if (targetContainer) {
                    const items = targetContainer.querySelectorAll('.searchable-item, .dashboard-card, .table tbody tr');
                    
                    items.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        const isVisible = query === '' || text.includes(query);
                        
                        // Utiliser des classes plutôt que des styles inline pour de meilleures performances
                        item.classList.toggle('hidden', !isVisible);
                    });
                }
            }, config.debounceDelay);
            
            input.addEventListener('input', performSearch);
        });
        
        // Optimiser les filtres
        filterButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Retirer la classe active des autres boutons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Ajouter la classe active au bouton cliqué
                this.classList.add('active');
                
                // Appliquer le filtre
                const filterValue = this.getAttribute('data-filter');
                const targetContainer = document.querySelector(this.getAttribute('data-target') || '.filterable-content');
                
                if (targetContainer && filterValue) {
                    const items = targetContainer.querySelectorAll('.filterable-item, .dashboard-card');
                    
                    items.forEach(item => {
                        const itemCategory = item.getAttribute('data-category') || item.getAttribute('data-status');
                        const isVisible = filterValue === 'all' || itemCategory === filterValue;
                        
                        item.classList.toggle('hidden', !isVisible);
                    });
                }
            });
        });
    }
    
    // ====================================================================
    // GESTION DES NOTIFICATIONS OPTIMISÉE
    // ====================================================================
    
    function initNotificationSystem() {
        // Système de notifications simple et performant
        window.showNotification = function(message, type = 'info', duration = 3000) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            // Style inline minimal pour éviter les dépendances CSS
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 9999;
                max-width: 300px;
                word-wrap: break-word;
                transition: opacity 0.2s ease;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
            `;
            
            document.body.appendChild(notification);
            
            // Supprimer automatiquement
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 200);
            }, duration);
        };
    }
    
    // ====================================================================
    // GESTION DES ERREURS ET PERFORMANCE
    // ====================================================================
    
    function initErrorHandling() {
        // Gestionnaire d'erreurs global amélioré
        window.addEventListener('error', function(e) {
            // Filtrer les erreurs null et les erreurs Bootstrap connues
            if (e.error === null || 
                (e.message && e.message.includes('backdrop')) ||
                (e.message && e.message.includes('classList'))) {
                // Erreurs Bootstrap connues - les ignorer silencieusement
                e.preventDefault();
                return;
            }
            console.warn('Erreur capturée:', e.error);
            // Ne pas bloquer l'interface en cas d'erreur
        });
        
        // Gestionnaire pour les promesses rejetées
        window.addEventListener('unhandledrejection', function(e) {
            console.warn('Promesse rejetée:', e.reason);
            e.preventDefault(); // Éviter les erreurs non gérées
        });
        
        // Gestionnaire spécifique pour les erreurs Bootstrap Modal
        const originalConsoleError = console.error;
        console.error = function(...args) {
            // Filtrer les erreurs Bootstrap Modal connues
            const message = args.join(' ');
            if (message.includes('backdrop') || 
                message.includes('Cannot read properties of undefined') ||
                message.includes('classList')) {
                // Ignorer ces erreurs spécifiques
                return;
            }
            originalConsoleError.apply(console, args);
        };
    }
    
    // ====================================================================
    // INITIALISATION PRINCIPALE
    // ====================================================================
    
    function init() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        try {
            // Initialiser tous les modules
            initErrorHandling();
            initDarkModeToggle();
            initButtonInteractions();
            initModalOptimizations();
            initResponsiveTables();
            initScrollOptimizations();
            initFormOptimizations();
            initFilterOptimizations();
            initNotificationSystem();
            
            // Marquer comme initialisé
            document.body.classList.add('js-initialized');
            
            console.log('Homepage mobile optimized script initialized');
            
        } catch (error) {
            console.warn('Erreur lors de l\'initialisation:', error);
            // Continuer même en cas d'erreur pour ne pas bloquer la page
        }
    }
    
    // ====================================================================
    // NETTOYAGE ET OPTIMISATIONS FINALES
    // ====================================================================
    
    // Désactiver les animations coûteuses sur les appareils moins performants
    if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) {
        document.documentElement.style.setProperty('--transition-duration', '0.1s');
    }
    
    // Optimiser pour les connexions lentes
    if (navigator.connection && navigator.connection.effectiveType && 
        (navigator.connection.effectiveType === 'slow-2g' || navigator.connection.effectiveType === '2g')) {
        config.enableAnimations = false;
        config.transitionDuration = 50;
    }
    
    // Démarrer l'initialisation
    init();
    
})();

/**
 * ====================================================================
 * UTILITAIRES GLOBAUX POUR LA COMPATIBILITÉ
 * ====================================================================
 */

// Fonction de compatibilité pour les anciens scripts
window.mobileOptimized = {
    isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
    isTouch: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
    showNotification: function(message, type, duration) {
        if (window.showNotification) {
            window.showNotification(message, type, duration);
        }
    }
};

// Polyfill pour les navigateurs plus anciens
if (!Element.prototype.closest) {
    Element.prototype.closest = function(s) {
        var el = this;
        do {
            if (el.matches(s)) return el;
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}

if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector || 
                                Element.prototype.webkitMatchesSelector;
}
