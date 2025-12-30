/**
 * ================================================================
 * 🎨 GeekBoard SuperAdmin - JavaScript Moderne 2024
 * ================================================================
 */

(function() {
    'use strict';

    // =============================================================
    // 🌓 Gestion du thème avancée
    // =============================================================

    const THEME_STORAGE_KEY = 'geekboard-sa-theme';
    const root = document.documentElement;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

    function applyTheme(theme) {
        if (theme === 'dark') {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }
        
        // Mettre à jour l'icône du bouton
        updateThemeButtonIcon(theme);
    }

    function getStoredTheme() {
        try {
            const stored = localStorage.getItem(THEME_STORAGE_KEY);
            if (stored) return stored;
            // Si pas de préférence stockée, utiliser la préférence système
            return prefersDark.matches ? 'dark' : 'light';
        } catch(e) {
            return 'light';
        }
    }

    function setStoredTheme(theme) {
        try {
            localStorage.setItem(THEME_STORAGE_KEY, theme);
        } catch(e) {
            console.warn('Impossible de sauvegarder le thème:', e);
        }
    }

    function updateThemeButtonIcon(theme) {
        const themeButtons = document.querySelectorAll('[onclick*="saToggleTheme"]');
        themeButtons.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun me-1' : 'fas fa-moon me-1';
            }
            const text = btn.childNodes[btn.childNodes.length - 1];
            if (text && text.nodeType === Node.TEXT_NODE) {
                text.textContent = theme === 'dark' ? 'Clair' : 'Sombre';
            }
        });
    }

    // Écouter les changements de préférence système
    prefersDark.addEventListener('change', (e) => {
        if (!localStorage.getItem(THEME_STORAGE_KEY)) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });

    // Initialiser le thème
    applyTheme(getStoredTheme());

    // Fonction globale pour basculer le thème
    window.saToggleTheme = function() {
        const currentTheme = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Animation de transition douce
        root.style.transition = 'all 0.3s ease';
        
        applyTheme(nextTheme);
        setStoredTheme(nextTheme);
        
        // Enlever la transition après l'animation
        setTimeout(() => {
            root.style.transition = '';
        }, 300);
    };

    // =============================================================
    // ✨ Animations et effets visuels
    // =============================================================

    function initAnimations() {
        // Animation d'entrée pour le conteneur principal
        const container = document.querySelector('.sa-container');
        if (container) {
            container.classList.add('sa-fade-enter');
            setTimeout(() => {
                container.classList.add('sa-fade-enter-active');
            }, 50);
            
            setTimeout(() => {
                container.classList.remove('sa-fade-enter', 'sa-fade-enter-active');
            }, 800);
        }

        // Animation en cascade pour les cartes
        const cards = document.querySelectorAll('.stat-card, .shop-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(2rem)';
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 200 + (index * 100));
        });

        // Effet parallax subtil pour l'en-tête
        const header = document.querySelector('.header-section');
        if (header) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                header.style.transform = `translateY(${rate}px)`;
            });
        }
    }

    // =============================================================
    // 🔍 Amélioration de la recherche
    // =============================================================

    function initSearchEnhancements() {
        const searchInput = document.getElementById('shop-search');
        if (!searchInput) return;

        // Debounce pour améliorer les performances
        let searchTimeout;
        const originalHandler = searchInput.oninput;
        
        searchInput.oninput = function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (originalHandler) originalHandler.call(this, e);
            }, 300);
        };

        // Raccourcis clavier
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K pour focus sur la recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
            
            // Échap pour vider la recherche
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.blur();
            }
        });

        // Indicateur de focus amélioré
        searchInput.addEventListener('focus', () => {
            searchInput.parentElement.classList.add('search-focused');
        });

        searchInput.addEventListener('blur', () => {
            searchInput.parentElement.classList.remove('search-focused');
        });
    }

    // =============================================================
    // 🎯 Interactions avancées
    // =============================================================

    function initInteractions() {
        // Tooltips pour les boutons d'action
        const actionButtons = document.querySelectorAll('.btn-action, .btn-shop');
        actionButtons.forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px) scale(1.02)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        // Animation click pour tous les boutons
        const allButtons = document.querySelectorAll('button, .btn-action, .btn-shop');
        allButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Effet ripple
                const rect = this.getBoundingClientRect();
                const ripple = document.createElement('span');
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255,255,255,0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Animation de chargement pour les liens externes
        const externalLinks = document.querySelectorAll('a[href^="http"], a[target="_blank"]');
        externalLinks.forEach(link => {
            link.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (icon && icon.classList.contains('fa-external-link-alt')) {
                    icon.style.animation = 'spin 0.5s ease-in-out';
                    setTimeout(() => {
                        icon.style.animation = '';
                    }, 500);
                }
            });
        });
    }

    // =============================================================
    // 📱 Responsive et adaptabilité
    // =============================================================

    function initResponsive() {
        // Détecter le type d'appareil
        const isMobile = window.innerWidth <= 768;
        const isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
        
        if (isMobile) {
            document.body.classList.add('is-mobile');
            // Optimisations pour mobile
            const cards = document.querySelectorAll('.shop-card, .stat-card');
            cards.forEach(card => {
                card.style.transition = 'transform 0.2s ease';
            });
        }
        
        if (isTablet) {
            document.body.classList.add('is-tablet');
        }

        // Réajuster au redimensionnement
        window.addEventListener('resize', () => {
            document.body.classList.toggle('is-mobile', window.innerWidth <= 768);
            document.body.classList.toggle('is-tablet', 
                window.innerWidth > 768 && window.innerWidth <= 1024);
        });
    }

    // =============================================================
    // 🚀 Initialisation
    // =============================================================

    document.addEventListener('DOMContentLoaded', function() {
        // Démarrer toutes les fonctionnalités
        initAnimations();
        initSearchEnhancements();
        initInteractions();
        initResponsive();

        // CSS pour l'animation ripple
        if (!document.getElementById('ripple-styles')) {
            const style = document.createElement('style');
            style.id = 'ripple-styles';
            style.textContent = `
                @keyframes ripple {
                    from { transform: scale(0); opacity: 1; }
                    to { transform: scale(2); opacity: 0; }
                }
                
                .search-focused {
                    transform: scale(1.02);
                }
                
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }

        console.log('🎨 GeekBoard SuperAdmin initialisé avec succès!');
    });

    // =============================================================
    // 🛠️ Utilitaires globaux
    // =============================================================

    window.GeekBoardSA = {
        theme: {
            toggle: window.saToggleTheme,
            get: () => root.getAttribute('data-theme') || 'light',
            set: (theme) => {
                applyTheme(theme);
                setStoredTheme(theme);
            }
        },
        
        animations: {
            fadeIn: (element, delay = 0) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(1rem)';
                setTimeout(() => {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, delay);
            }
        }
    };

})();




