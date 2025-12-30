(function() {
    console.log('🚀 [SERVO-FORCE] Script de forçage visibilité SERVO chargé');

    function forceServoLogoVisibility() {
        console.log('🔍 [SERVO-FORCE] Recherche du logo SERVO...');
        
        // Debug : lister tous les éléments avec "servo" dans leur classe ou ID
        const allServoElements = document.querySelectorAll('[class*="servo"], [id*="servo"]');
        console.log('🔍 [SERVO-FORCE] Éléments avec "servo" trouvés:', allServoElements.length, allServoElements);
        
        // Debug : vérifier si la navbar existe
        const navbar = document.getElementById('desktop-navbar');
        console.log('🔍 [SERVO-FORCE] Navbar trouvée:', !!navbar, navbar);
        
        const servoContainer = document.querySelector('.servo-logo-container');
        console.log('🔍 [SERVO-FORCE] Container SERVO:', !!servoContainer, servoContainer);
        
        if (servoContainer) {
            // Forcer tous les styles de visibilité
            servoContainer.style.cssText = `
                z-index: 9999999999 !important;
                position: absolute !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
                display: flex !important;
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: none !important;
                background: transparent !important;
                height: 32px !important;
                align-items: center !important;
                justify-content: center !important;
            `;

            // Forcer tous les éléments enfants
            const allChildren = servoContainer.querySelectorAll('*');
            allChildren.forEach(child => {
                child.style.opacity = '1';
                child.style.visibility = 'visible';
                child.style.display = child.style.display || 'inherit';
                child.style.zIndex = '9999999999';

            console.log('✅ [SERVO-FORCE] Logo SERVO forcé visible');
            return true;
        } else {
            console.log('⚠️ [SERVO-FORCE] Logo SERVO non trouvé');
            return false;
        }
    }

    // Forcer immédiatement
    forceServoLogoVisibility();

    // Forcer au chargement du DOM
    document.addEventListener('DOMContentLoaded', forceServoLogoVisibility);

    // Forcer après un délai (plus de tentatives)
    setTimeout(forceServoLogoVisibility, 500);
    setTimeout(forceServoLogoVisibility, 1000);
    setTimeout(forceServoLogoVisibility, 2000);
    setTimeout(forceServoLogoVisibility, 3000);
    setTimeout(forceServoLogoVisibility, 5000);

    // Observer les mutations pour contrer les scripts qui cachent
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && 
                (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                const target = mutation.target;
                if (target.classList && target.classList.contains('servo-logo-container')) {
                    console.log('🛡️ [SERVO-FORCE] Mutation détectée sur le logo, re-forçage...');
                    setTimeout(forceServoLogoVisibility, 10);
                }
            }

    // Observer le body pour les changements
    if (document.body) {
        observer.observe(document.body, {
            attributes: true
            subtree: true
            attributeFilter: ['style', 'class']
    }

    // Forcer périodiquement
    setInterval(forceServoLogoVisibility, 5000);

    // Fonction globale pour debug
    window.forceServoLogo = forceServoLogoVisibility;

    console.log('🚀 [SERVO-FORCE] Protection active - Logo SERVO forcé visible');
})();
