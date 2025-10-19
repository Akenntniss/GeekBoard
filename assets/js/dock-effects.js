/**
 * Dock Effects - Gestion des effets de la barre de navigation
 * Contrôle l'affichage/masquage de la barre lors du défilement
 * Version 2.0 - Robuste contre les conflits de scripts
 */

(function() {
    // Fonction principale d'initialisation (peut être appelée plusieurs fois)
    function initDockEffects() {
        // Sélectionner la barre de navigation
        const mobileDock = document.getElementById('mobile-dock');
        if (!mobileDock) {
            setTimeout(initDockEffects, 1000);
            return;
        }
        
        // Éviter les doubles initialisations
        if (mobileDock.hasAttribute('data-dock-effects-initialized')) {
            return;
        }
        
        // Marquer comme initialisé
        mobileDock.setAttribute('data-dock-effects-initialized', 'true');
        
        let lastScrollTop = 0;
        let scrollTimer = null;
        const scrollThreshold = 50; // Seuil de défilement pour déclencher l'action
        const autoShowDelay = 20000; // 20 secondes avant réapparition automatique
        
        // Fonction pour masquer la barre
        function hideDock() {
            // 🛡️ EXCEPTION IPAD - Ne pas masquer le dock si c'est la navbar desktop sur iPad paysage
            const isIPad = /ipad/i.test(navigator.userAgent.toLowerCase()) || 
                           (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            const isLandscape = window.innerWidth > window.innerHeight;
            
            if (isIPad && isLandscape && mobileDock.id === 'desktop-navbar') {
                console.log('🛡️ [DOCK-EFFECTS] Masquage bloqué - iPad paysage navbar détecté');
                return;
            }
            
            // Utiliser 'hidden' et 'dock-hidden' pour la compatibilité
            mobileDock.classList.remove('show', 'dock-visible');
            mobileDock.classList.add('hidden', 'dock-hidden');
            mobileDock.setAttribute('data-dock-state', 'hidden');
        }
        
        // Fonction pour afficher la barre
        function showDock() {
            mobileDock.classList.remove('hidden', 'dock-hidden');
            mobileDock.classList.add('show', 'dock-visible');
            mobileDock.setAttribute('data-dock-state', 'visible');
        }
        
        // Forcer les styles initiaux pour la barre de menu
        mobileDock.style.transition = "transform 0.4s ease, opacity 0.3s ease";
        mobileDock.style.display = "block";
        mobileDock.style.visibility = "visible";
        
        // Ajouter une feuille de style dans le document avec des styles prioritaires
        const styleId = 'dock-effects-styles';
        if (!document.getElementById(styleId)) {
            const styleElement = document.createElement('style');
            styleElement.id = styleId;
            styleElement.textContent = `
                #mobile-dock.hidden, #mobile-dock.dock-hidden {
                    transform: translateY(100%) !important;
                    opacity: 0 !important;
                    pointer-events: none !important;
                }
                
                #mobile-dock.show, #mobile-dock.dock-visible {
                    transform: translateY(0) !important;
                    opacity: 1 !important;
                    pointer-events: auto !important;
                }
                
                #mobile-dock {
                    transition: transform 0.4s ease, opacity 0.3s ease !important;
                    will-change: transform, opacity !important;
                    display: block !important;
                    visibility: visible !important;
                    z-index: 9999 !important;
                }

                /* Styles forcés pour assurer la bonne position */
                #mobile-dock {
                    position: fixed !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                }
            `;
            document.head.appendChild(styleElement);
        }
        
        // Gestionnaire d'événement pour le défilement
        const handleScroll = function(e) {
            // Si le dock n'existe plus dans le DOM, arrêter 
            if (!document.getElementById('mobile-dock')) {
                window.removeEventListener('scroll', handleScroll, { capture: true });
                // Tenter de réinitialiser au cas où le dock est ajouté à nouveau
                setTimeout(initDockEffects, 1000);
                return;
            }
            
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Si le défilement est trop petit, ignorer
            if (Math.abs(scrollTop - lastScrollTop) <= scrollThreshold) return;
            
            // Défilement vers le bas - masquer la barre (seulement après avoir défilé un peu)
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                hideDock();
            } 
            // Défilement vers le haut - afficher la barre
            else if (scrollTop < lastScrollTop) {
                showDock();
            }
            
            lastScrollTop = scrollTop;
            
            // Réinitialiser le timer à chaque défilement
            clearTimeout(scrollTimer);
            
            // Réapparaître après le délai configuré
            scrollTimer = setTimeout(function() {
                showDock();
            }, autoShowDelay);
        };
        
        // Assurer que la barre est visible au chargement initial
        showDock();
        
        // Ajouter notre gestionnaire d'événements de défilement avec priorité élevée
        window.addEventListener('scroll', handleScroll, { passive: true, capture: true });
        
        // Forcer la réapparition si l'utilisateur touche l'écran
        document.addEventListener('touchstart', function() {
            if (mobileDock.classList.contains('hidden') || mobileDock.classList.contains('dock-hidden')) {
                // La barre est masquée, on la montre immédiatement
                clearTimeout(scrollTimer);
                showDock();
            }
        }, { passive: true });
        
        // Observer les changements du DOM qui pourraient affecter le dock
        const observer = new MutationObserver(function(mutations) {
            // Si le dock est présent mais nos classes ont été modifiées
            const dock = document.getElementById('mobile-dock');
            if (dock && dock.hasAttribute('data-dock-state')) {
                const state = dock.getAttribute('data-dock-state');
                if (state === 'hidden' && (!dock.classList.contains('hidden') && !dock.classList.contains('dock-hidden'))) {
                    // Notre état est 'caché' mais les classes ont disparu
                    hideDock();
                } else if (state === 'visible' && (dock.classList.contains('hidden') || dock.classList.contains('dock-hidden'))) {
                    // Notre état est 'visible' mais les classes 'hidden' sont présentes
                    showDock();
                }
            }
        });
        
        // Observer le dock et le document body pour les changements
        observer.observe(document.body, { 
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    }

    // Démarrer l'initialisation quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDockEffects);
    } else {
        initDockEffects();
    }
    
    // Réessayer après un délai pour s'assurer que tous les éléments du DOM sont présents
    setTimeout(initDockEffects, 1000);
})(); 