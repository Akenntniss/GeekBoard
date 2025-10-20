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
        // Rendre le dock moins sensible
        const scrollThreshold = 60; // Seuil minimal de variation pour considérer un scroll
        const requiredUpScroll = 90; // Remontée cumulée nécessaire avant d'afficher
        const minShowIntervalMs = 1500; // Délai minimal entre deux apparitions
        const autoShowDelay = 45000; // 45 secondes avant réapparition automatique
        let accumulatedUpScroll = 0; // Remontée cumulée depuis le dernier point bas
        let lastShowTime = 0; // Timestamp de la dernière apparition effective
        
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
            lastShowTime = Date.now();
            accumulatedUpScroll = 0;
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

                /* Petite zone de rappel pour ré-afficher le dock */
                #dock-recall-zone {
                    position: fixed !important;
                    bottom: 0 !important;
                    left: 50% !important;
                    transform: translateX(-50%) !important;
                    width: 44% !important;
                    max-width: 420px !important;
                    height: 12px !important;
                    border-radius: 8px 8px 0 0 !important;
                    background: rgba(0, 212, 255, 0.2) !important;
                    backdrop-filter: blur(10px) saturate(160%) !important;
                    -webkit-backdrop-filter: blur(10px) saturate(160%) !important;
                    box-shadow: 0 0 14px rgba(0, 212, 255, 0.35) !important;
                    opacity: 0.65 !important;
                    transition: opacity 0.2s ease, transform 0.2s ease !important;
                    z-index: 9998 !important; /* juste sous le dock */
                    pointer-events: auto !important;
                }

                body.night-mode #dock-recall-zone {
                    background: rgba(0, 212, 255, 0.25) !important;
                    box-shadow: 0 0 16px rgba(0, 212, 255, 0.45) !important;
                    opacity: 0.6 !important;
                }

                #dock-recall-zone:hover { opacity: 0.9 !important; }
                #dock-recall-zone.hidden { opacity: 0 !important; pointer-events: none !important; }
            `;
            document.head.appendChild(styleElement);
        }
        
        // Créer la zone de rappel si absente
        let recallZone = document.getElementById('dock-recall-zone');
        if (!recallZone) {
            recallZone = document.createElement('div');
            recallZone.id = 'dock-recall-zone';
            recallZone.classList.add('hidden');
            document.body.appendChild(recallZone);
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
            const delta = scrollTop - lastScrollTop;
            if (Math.abs(delta) <= scrollThreshold) return;
            
            // Défilement vers le bas - masquer la barre (seulement après avoir défilé un peu)
            if (delta > 0 && scrollTop > 100) {
                // Défilement vers le bas: masquer et réinitialiser l'accumulation
                hideDock();
                accumulatedUpScroll = 0;
                if (recallZone) recallZone.classList.remove('hidden');
            } else if (delta < 0) {
                // Défilement vers le haut: cumuler la remontée
                accumulatedUpScroll += (lastScrollTop - scrollTop);
                const now = Date.now();
                if (accumulatedUpScroll >= requiredUpScroll && (now - lastShowTime) >= minShowIntervalMs) {
                    showDock();
                    if (recallZone) recallZone.classList.add('hidden');
                }
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
        if (recallZone) recallZone.classList.add('hidden');
        
        // Ajouter notre gestionnaire d'événements de défilement avec priorité élevée
        window.addEventListener('scroll', handleScroll, { passive: true, capture: true });
        
        // Forcer la réapparition si l'utilisateur touche l'écran - DÉSACTIVÉ
        // Ce listener global causait la réapparition du dock à chaque touch
        /*
        document.addEventListener('touchstart', function() {
            if (mobileDock.classList.contains('hidden') || mobileDock.classList.contains('dock-hidden')) {
                // La barre est masquée, on la montre immédiatement
                clearTimeout(scrollTimer);
                showDock();
            }
        }, { passive: true });
        */
        
        // Observer les changements du DOM qui pourraient affecter le dock
        const observer = new MutationObserver(function(mutations) {
            // Si le dock est présent mais nos classes ont été modifiées
            const dock = document.getElementById('mobile-dock');
            if (dock && dock.hasAttribute('data-dock-state')) {
                const state = dock.getAttribute('data-dock-state');
                if (state === 'hidden' && (!dock.classList.contains('hidden') && !dock.classList.contains('dock-hidden'))) {
                    // Notre état est 'caché' mais les classes ont disparu
                    hideDock();
                    if (recallZone) recallZone.classList.remove('hidden');
                } else if (state === 'visible' && (dock.classList.contains('hidden') || dock.classList.contains('dock-hidden'))) {
                    // Notre état est 'visible' mais les classes 'hidden' sont présentes
                    showDock();
                    if (recallZone) recallZone.classList.add('hidden');
                }
            }
        });

        // Interaction sur la zone de rappel
        if (recallZone) {
            const activate = () => {
                const now = Date.now();
                if ((now - lastShowTime) >= minShowIntervalMs) {
                    showDock();
                    recallZone.classList.add('hidden');
                }
            };
            recallZone.addEventListener('click', activate, { passive: true });
            recallZone.addEventListener('touchstart', activate, { passive: true });
        }
        
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