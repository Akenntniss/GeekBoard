/**
 * JavaScript moderne pour la barre de navigation mobile GeekBoard
 * Gestion du modal nouvelles_actions_modal et interactions tactiles
 */

(function() {
    'use strict';
    
    let mobileNavbar = {
        initialized: false,
        modalInstance: null,
        
        /**
         * Initialisation de la barre de navigation mobile
         */
        init: function() {
            if (this.initialized) return;
            
            console.log('🚀 Initialisation de la barre de navigation mobile moderne');
            
            // Attendre que le DOM et Bootstrap soient prêts
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
                return;
            }
            
            if (typeof bootstrap === 'undefined') {
                console.log('⏳ Attente de Bootstrap...');
                setTimeout(() => this.init(), 200);
                return;
            }
            
            this.setupPlusButton();
            this.setupTouchEffects();
            this.setupActiveStates();
            this.setupAccessibility();
            
            this.initialized = true;
            console.log('✅ Barre de navigation mobile initialisée');
        },
        
        /**
         * Configuration du bouton + pour ouvrir le modal nouvelles_actions_modal
         */
        setupPlusButton: function() {
            const plusButton = document.querySelector('.dock-item.plus-button');
            const modal = document.getElementById('nouvelles_actions_modal');
            
            if (!plusButton || !modal) {
                console.warn('⚠️ Bouton + ou modal nouvelles_actions_modal non trouvé');
                return;
            }
            
            // Créer l'instance du modal
            try {
                this.modalInstance = new bootstrap.Modal(modal, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                
                console.log('✅ Instance modal créée avec succès');
            } catch (error) {
                console.error('❌ Erreur création instance modal:', error);
                return;
            }
            
            // Gestionnaire de clic pour le bouton +
            plusButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🎯 Clic sur le bouton +');
                
                // Effet visuel de clic
                this.addClickEffect(plusButton);
                
                // Ouvrir le modal
                this.openModal();
            });
            
            // Gestionnaire tactile pour les appareils mobiles
            plusButton.addEventListener('touchstart', (e) => {
                plusButton.classList.add('touching');
            }, { passive: true });
            
            plusButton.addEventListener('touchend', (e) => {
                setTimeout(() => {
                    plusButton.classList.remove('touching');
                }, 150);
            }, { passive: true });
            
            console.log('✅ Bouton + configuré');
        },
        
        /**
         * Ouvrir le modal nouvelles_actions_modal
         */
        openModal: function() {
            if (!this.modalInstance) {
                console.error('❌ Instance modal non disponible');
                return;
            }
            
            try {
                // Nettoyer les éventuels backdrops résiduels
                const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                existingBackdrops.forEach(backdrop => backdrop.remove());
                
                // Réinitialiser l'état du body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Ouvrir le modal
                this.modalInstance.show();
                
                console.log('✅ Modal ouvert avec succès');
                
                // Ajouter une classe temporaire pour identifier l'ouverture depuis mobile
                document.body.classList.add('modal-opened-from-mobile');
                
                // Retirer la classe après fermeture
                const modal = document.getElementById('nouvelles_actions_modal');
                modal.addEventListener('hidden.bs.modal', () => {
                    document.body.classList.remove('modal-opened-from-mobile');
                }, { once: true });
                
            } catch (error) {
                console.error('❌ Erreur ouverture modal:', error);
                
                // Fallback : ouverture manuelle
                this.openModalFallback();
            }
        },
        
        /**
         * Fallback pour ouvrir le modal manuellement
         */
        openModalFallback: function() {
            const modal = document.getElementById('nouvelles_actions_modal');
            if (!modal) return;
            
            console.log('🔄 Ouverture modal en mode fallback');
            
            // Ouvrir manuellement
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-modal', 'true');
            modal.removeAttribute('aria-hidden');
            
            // Créer le backdrop
            const backdrop = document.createElement('div');
            backdrop.classList.add('modal-backdrop', 'fade', 'show');
            document.body.appendChild(backdrop);
            
            // Empêcher le défilement
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            
            // Gestionnaire de fermeture
            const closeModal = () => {
                modal.classList.remove('show');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                modal.removeAttribute('aria-modal');
                
                backdrop.remove();
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            };
            
            // Boutons de fermeture
            const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
            closeButtons.forEach(btn => {
                btn.addEventListener('click', closeModal, { once: true });
            });
            
            // Fermeture sur clic backdrop
            backdrop.addEventListener('click', closeModal, { once: true });
            
            console.log('✅ Modal ouvert en mode fallback');
        },
        
        /**
         * Configuration des effets tactiles pour tous les éléments de navigation
         */
        setupTouchEffects: function() {
            const dockItems = document.querySelectorAll('.dock-item:not(.plus-button)');
            
            dockItems.forEach(item => {
                // Effet tactile au toucher
                item.addEventListener('touchstart', (e) => {
                    item.classList.add('touching');
                    this.addClickEffect(item);
                }, { passive: true });
                
                item.addEventListener('touchend', (e) => {
                    setTimeout(() => {
                        item.classList.remove('touching');
                    }, 150);
                }, { passive: true });
                
                // Effet de survol pour les appareils qui le supportent
                if (window.matchMedia('(hover: hover)').matches) {
                    item.addEventListener('mouseenter', () => {
                        item.classList.add('hovering');
                    });
                    
                    item.addEventListener('mouseleave', () => {
                        item.classList.remove('hovering');
                    });
                }
            });
            
            console.log(`✅ Effets tactiles configurés pour ${dockItems.length} éléments`);
        },
        
        /**
         * Gestion des états actifs basés sur la page courante
         */
        setupActiveStates: function() {
            const currentPage = this.getCurrentPage();
            const dockItems = document.querySelectorAll('.dock-item[href]');
            
            dockItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && href.includes(`page=${currentPage}`)) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
            
            console.log(`✅ États actifs mis à jour pour la page: ${currentPage}`);
        },
        
        /**
         * Configuration de l'accessibilité
         */
        setupAccessibility: function() {
            const dockItems = document.querySelectorAll('.dock-item');
            
            dockItems.forEach(item => {
                // S'assurer que tous les éléments sont focusables
                if (!item.hasAttribute('tabindex')) {
                    item.setAttribute('tabindex', '0');
                }
                
                // Ajouter des labels ARIA si manquants
                if (!item.hasAttribute('aria-label')) {
                    const text = item.querySelector('span');
                    if (text) {
                        item.setAttribute('aria-label', text.textContent);
                    }
                }
                
                // Gestion du clavier
                item.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        item.click();
                    }
                });
            });
            
            // Label spécial pour le bouton +
            const plusButton = document.querySelector('.dock-item.plus-button');
            if (plusButton) {
                plusButton.setAttribute('aria-label', 'Ouvrir le menu des nouvelles actions');
                plusButton.setAttribute('role', 'button');
            }
            
            console.log('✅ Accessibilité configurée');
        },
        
        /**
         * Effet visuel de clic/tap
         */
        addClickEffect: function(element) {
            // Retirer l'effet précédent s'il existe
            element.classList.remove('click-effect');
            
            // Forcer le reflow pour s'assurer que la classe est retirée
            element.offsetHeight;
            
            // Ajouter l'effet
            element.classList.add('click-effect');
            
            // Retirer l'effet après l'animation
            setTimeout(() => {
                element.classList.remove('click-effect');
            }, 300);
        },
        
        /**
         * Obtenir la page courante depuis l'URL
         */
        getCurrentPage: function() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('page') || 'accueil';
        },
        
        /**
         * Gestion du mode sombre/clair
         */
        handleThemeChange: function() {
            // Observer les changements de thème
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && 
                        (mutation.attributeName === 'data-theme' || mutation.attributeName === 'class')) {
                        this.updateThemeStyles();
                    }
                });
            });
            
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-theme', 'class']
            });
            
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Observer les changements de préférences système
            if (window.matchMedia) {
                const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
                darkModeQuery.addListener(() => this.updateThemeStyles());
            }
            
            console.log('✅ Observateur de thème configuré');
        },
        
        /**
         * Mise à jour des styles selon le thème
         */
        updateThemeStyles: function() {
            const isDark = document.documentElement.hasAttribute('data-theme') && 
                          document.documentElement.getAttribute('data-theme') === 'dark' ||
                          document.body.classList.contains('dark-mode') ||
                          window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            const navbar = document.getElementById('mobile-dock');
            if (navbar) {
                navbar.classList.toggle('dark-theme', isDark);
            }
            
            console.log(`🎨 Thème mis à jour: ${isDark ? 'sombre' : 'clair'}`);
        }
    };
    
    // CSS pour les effets supplémentaires
    const style = document.createElement('style');
    style.textContent = `
        .dock-item.touching {
            transform: scale(0.95) !important;
            transition: transform 0.1s ease;
        }
        
        .dock-item.plus-button.touching {
            transform: translateY(-6px) scale(0.95) !important;
        }
        
        .dock-item.hovering {
            transform: translateY(-2px);
        }
        
        .dock-item.click-effect::after {
            width: 80px !important;
            height: 80px !important;
            opacity: 0.6 !important;
        }
        
        @media (prefers-reduced-motion: reduce) {
            .dock-item.touching,
            .dock-item.hovering,
            .dock-item.click-effect::after {
                transform: none !important;
                transition: none !important;
                animation: none !important;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Nettoyer les barres de navigation en conflit
    function cleanupConflictingNavbars() {
        // Supprimer toutes les autres barres de navigation mobiles
        const conflictingElements = document.querySelectorAll(
            '.bottom-nav, .neo-dock, .mobile-welcome-banner, .mobile-time-tracking, ' +
            '[class*="dock"]:not(#mobile-dock):not(.mobile-dock-container):not(.dock-item):not(.dock-icon-wrapper), ' +
            '[class*="bottom-nav"], [class*="mobile-nav"]:not(#mobile-dock):not(.mobile-dock-container)'
        );
        
        conflictingElements.forEach(element => {
            if (element && element.id !== 'mobile-dock') {
                element.style.display = 'none';
                element.style.visibility = 'hidden';
                element.style.opacity = '0';
                element.style.height = '0';
                element.style.overflow = 'hidden';
                console.log('🧹 Élément de navigation en conflit supprimé:', element);
            }
        });
        
        // Supprimer les éléments avec des couleurs orange/beige
        const orangeElements = document.querySelectorAll(
            '*[style*="background-color: orange"], *[style*="background-color: #ffa500"], ' +
            '*[style*="background-color: #ffb366"], *[style*="background-color: #f4a261"], ' +
            '.bg-warning, .bg-orange, .alert-warning'
        );
        
        orangeElements.forEach(element => {
            element.style.display = 'none';
            console.log('🧹 Élément orange supprimé:', element);
        });
    }

    // Initialiser la barre de navigation
    mobileNavbar.init();
    mobileNavbar.handleThemeChange();
    
    // Nettoyer les conflits
    cleanupConflictingNavbars();
    
    // Nettoyer périodiquement au cas où des éléments seraient ajoutés dynamiquement
    setInterval(cleanupConflictingNavbars, 2000);
    
    // Exporter pour usage global si nécessaire
    window.mobileNavbar = mobileNavbar;
    
    console.log('🎉 Module barre de navigation mobile chargé et nettoyé');
    
})();
