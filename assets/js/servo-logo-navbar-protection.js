(function() {
    console.log('🛡️ [SERVO-NAVBAR-PROTECTION] Protection du logo SERVO contre les modifications de navbar');

    // Fonction pour forcer le logo visible même si la navbar est cachée
    function protectServoLogo() {
        const servoContainer = document.querySelector('.servo-logo-container');
        const desktopNavbar = document.getElementById('desktop-navbar');
        
        if (servoContainer && desktopNavbar) {
            // Vérifier si la navbar est cachée
            const navbarStyle = window.getComputedStyle(desktopNavbar);
            const isNavbarHidden = navbarStyle.display === 'none' || 
                                  navbarStyle.visibility === 'hidden' || 
                                  navbarStyle.opacity === '0';
            
            if (isNavbarHidden) {
                console.log('⚠️ [SERVO-NAVBAR-PROTECTION] Navbar cachée détectée, protection du logo SERVO');
                
                // Forcer le logo visible même si la navbar est cachée
                servoContainer.style.cssText = `
                    position: fixed !important;
                    left: 50% !important;
                    top: 35px !important;
                    transform: translateX(-50%) !important;
                    z-index: 9999999999 !important;
                    display: flex !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    pointer-events: none !important;
                    background: transparent !important;
                    height: 32px !important;
                    align-items: center !important;
                    justify-content: center !important;
                `;
                
                // Ajouter une classe pour identifier l'état
                servoContainer.classList.add('servo-navbar-hidden-mode');
                
            } else {
                // Navbar visible, position normale
                servoContainer.style.cssText = `
                    position: absolute !important;
                    left: 50% !important;
                    top: 50% !important;
                    transform: translate(-50%, -50%) !important;
                    z-index: 9999999999 !important;
                    display: flex !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    pointer-events: none !important;
                    background: transparent !important;
                    height: 32px !important;
                    align-items: center !important;
                    justify-content: center !important;
                `;
                
                servoContainer.classList.remove('servo-navbar-hidden-mode');
            }
            
            // Forcer tous les éléments enfants
            const allChildren = servoContainer.querySelectorAll('*');
            allChildren.forEach(child => {
                child.style.opacity = '1';
                child.style.visibility = 'visible';
                child.style.zIndex = '9999999999';
            });
            
            return true;
        }
        
        return false;
    }

    // Observer les changements de style sur la navbar
    function setupNavbarObserver() {
        const desktopNavbar = document.getElementById('desktop-navbar');
        
        if (desktopNavbar) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        console.log('🔍 [SERVO-NAVBAR-PROTECTION] Changement de style navbar détecté');
                        setTimeout(protectServoLogo, 10);
                    }
                });
            });
            
            observer.observe(desktopNavbar, {
                attributes: true,
                attributeFilter: ['style']
            });
            
            console.log('✅ [SERVO-NAVBAR-PROTECTION] Observer installé sur desktop-navbar');
        }
    }

    // Intercepter les modifications de style sur la navbar
    function interceptNavbarStyleChanges() {
        const desktopNavbar = document.getElementById('desktop-navbar');
        
        if (desktopNavbar && desktopNavbar.style) {
            // Sauvegarder la méthode originale
            const originalSetProperty = desktopNavbar.style.setProperty;
            const originalDisplay = Object.getOwnPropertyDescriptor(desktopNavbar.style, 'display');
            
            // Intercepter setProperty
            desktopNavbar.style.setProperty = function(property, value, priority) {
                if (property === 'display' && value === 'none') {
                    console.log('🛡️ [SERVO-NAVBAR-PROTECTION] Interception display: none sur navbar');
                    setTimeout(protectServoLogo, 10);
                }
                return originalSetProperty.call(this, property, value, priority);
            };
            
            // Intercepter la propriété display directe
            if (originalDisplay && originalDisplay.set) {
                Object.defineProperty(desktopNavbar.style, 'display', {
                    set: function(value) {
                        if (value === 'none') {
                            console.log('🛡️ [SERVO-NAVBAR-PROTECTION] Interception style.display = none');
                            setTimeout(protectServoLogo, 10);
                        }
                        return originalDisplay.set.call(this, value);
                    },
                    get: originalDisplay.get
                });
            }
            
            console.log('✅ [SERVO-NAVBAR-PROTECTION] Interception des styles installée');
        }
    }

    // Initialisation
    function init() {
        protectServoLogo();
        setupNavbarObserver();
        interceptNavbarStyleChanges();
        
        // Protection périodique
        setInterval(protectServoLogo, 2000);
        
        console.log('🛡️ [SERVO-NAVBAR-PROTECTION] Protection complète activée');
    }

    // Démarrer la protection
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Protection supplémentaire après chargement
    setTimeout(init, 1000);
    setTimeout(init, 3000);

    // Fonction globale pour debug
    window.protectServoLogo = protectServoLogo;
    
})();
