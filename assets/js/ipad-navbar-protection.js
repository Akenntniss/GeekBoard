/**
 * IPAD NAVBAR PROTECTION
 * Script de protection pour empêcher les autres scripts de masquer la navbar sur iPad
 * Version: 1.0
 */

(function() {
    'use strict';
    
    console.log('🛡️ [IPAD-PROTECTION] Initialisation de la protection navbar iPad...');
    
    // Variables de détection
    let isIPad = false;
    let isLandscape = false;
    let protectionActive = false;
    
    // Fonction de détection iPad améliorée
    function detectIPad() {
        const userAgent = navigator.userAgent.toLowerCase();
        const platform = navigator.platform;
        const maxTouchPoints = navigator.maxTouchPoints;
        
        // Détection iPad classique et moderne (iPadOS 13+)
        isIPad = /ipad/i.test(userAgent) || 
                 (platform === 'MacIntel' && maxTouchPoints > 1) ||
                 (/macintosh/i.test(userAgent) && 'ontouchend' in document);
        
        return isIPad;
    }
    
    // Fonction de détection d'orientation
    function detectOrientation() {
        isLandscape = window.innerWidth > window.innerHeight;
        
        console.log('🔍 [IPAD-PROTECTION] Orientation:', {
            width: window.innerWidth,
            height: window.innerHeight,
            isLandscape: isLandscape,
            isIPad: isIPad
        });
        
        return isLandscape;
    }
    
    // Fonction pour activer la protection
    function activateProtection() {
        if (!isIPad || !isLandscape) return;
        
        protectionActive = true;
        console.log('🛡️ [IPAD-PROTECTION] Protection activée pour iPad paysage');
        
        // Forcer l'affichage de la navbar
        const navbar = document.getElementById('desktop-navbar');
        const mobileDock = document.getElementById('mobile-dock');
        
        if (navbar) {
            // Styles forcés pour la navbar
            navbar.style.cssText = `
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 9999 !important;
                transform: translateY(0) !important;
                transition: none !important;
            `;
            
            // Ajouter une classe de protection
            navbar.classList.add('ipad-protected');
            
            // Marquer comme protégé
            navbar.setAttribute('data-ipad-protected', 'true');
        }
        
        if (mobileDock) {
            mobileDock.style.display = 'none !important';
            mobileDock.style.visibility = 'hidden !important';
            mobileDock.style.opacity = '0 !important';
        }
        
        // Ajouter classes au body
        document.body.classList.add('ipad-landscape-protected', 'ipad-device', 'ipad-landscape');
        document.body.classList.remove('ipad-portrait', 'tablet-device');
        
        // Désactiver les scripts interférents
        disableInterferingScripts();
    }
    
    // Fonction pour désactiver la protection
    function deactivateProtection() {
        if (!protectionActive) return;
        
        protectionActive = false;
        console.log('🛡️ [IPAD-PROTECTION] Protection désactivée');
        
        const navbar = document.getElementById('desktop-navbar');
        if (navbar) {
            navbar.classList.remove('ipad-protected');
            navbar.removeAttribute('data-ipad-protected');
        }
        
        document.body.classList.remove('ipad-landscape-protected');
    }
    
    // Fonction pour désactiver les scripts interférents
    function disableInterferingScripts() {
        console.log('🚫 [IPAD-PROTECTION] Désactivation des scripts interférents...');
        
        // Désactiver modern-interactions.js
        if (window.hideNavbar) {
            const originalHideNavbar = window.hideNavbar;
            window.hideNavbar = function() {
                if (protectionActive) {
                    console.log('🚫 [IPAD-PROTECTION] Blocage hideNavbar()');
                    return;
                }
                return originalHideNavbar.apply(this, arguments);
            };
        }
        
        // Désactiver dock-effects.js
        if (window.hideDock) {
            const originalHideDock = window.hideDock;
            window.hideDock = function() {
                if (protectionActive) {
                    console.log('🚫 [IPAD-PROTECTION] Blocage hideDock()');
                    return;
                }
                return originalHideDock.apply(this, arguments);
            };
        }
        
        // Protéger contre les modifications de style
        const navbar = document.getElementById('desktop-navbar');
        if (navbar) {
            // Observer les changements de style
            const observer = new MutationObserver(function(mutations) {
                if (!protectionActive) return;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const target = mutation.target;
                        if (target.id === 'desktop-navbar') {
                            // Vérifier si quelqu'un essaie de cacher la navbar
                            const style = target.style;
                            if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
                                console.log('🛡️ [IPAD-PROTECTION] Tentative de masquage détectée - restauration');
                                activateProtection(); // Re-forcer les styles
                            }
                        }
                    }
                });
            });
            
            observer.observe(navbar, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        }
    }
    
    // Fonction principale de gestion
    function handleNavbarDisplay() {
        detectIPad();
        detectOrientation();
        
        if (isIPad && isLandscape) {
            activateProtection();
        } else if (isIPad && !isLandscape) {
            deactivateProtection();
            // En portrait, laisser le dock mobile s'afficher
            const mobileDock = document.getElementById('mobile-dock');
            if (mobileDock) {
                mobileDock.style.display = 'block';
                mobileDock.style.visibility = 'visible';
                mobileDock.style.opacity = '1';
            }
        }
    }
    
    // Fonction de surveillance continue
    function startProtectionMonitoring() {
        // Vérifier toutes les 500ms si la navbar est toujours visible
        setInterval(function() {
            if (!protectionActive) return;
            
            const navbar = document.getElementById('desktop-navbar');
            if (navbar) {
                const computedStyle = window.getComputedStyle(navbar);
                if (computedStyle.display === 'none' || 
                    computedStyle.visibility === 'hidden' || 
                    computedStyle.opacity === '0') {
                    
                    console.log('⚠️ [IPAD-PROTECTION] Navbar masquée détectée - restauration forcée');
                    activateProtection();
                }
            }
        }, 500);
    }
    
    // Initialisation
    function init() {
        console.log('🚀 [IPAD-PROTECTION] Initialisation...');
        
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', handleNavbarDisplay);
        } else {
            handleNavbarDisplay();
        }
        
        // Écouter les changements d'orientation
        window.addEventListener('orientationchange', function() {
            console.log('🔄 [IPAD-PROTECTION] Changement d\'orientation détecté');
            setTimeout(handleNavbarDisplay, 200);
        });
        
        // Écouter les redimensionnements
        window.addEventListener('resize', function() {
            setTimeout(handleNavbarDisplay, 100);
        });
        
        // Démarrer la surveillance
        startProtectionMonitoring();
        
        // Exposer les fonctions globalement pour le debug
        window.iPadNavbarProtection = {
            activate: activateProtection,
            deactivate: deactivateProtection,
            isActive: () => protectionActive,
            isIPad: () => isIPad,
            isLandscape: () => isLandscape,
            forceCheck: handleNavbarDisplay
        };
        
        console.log('✅ [IPAD-PROTECTION] Protection iPad initialisée');
    }
    
    // Démarrer immédiatement
    init();
    
})();
