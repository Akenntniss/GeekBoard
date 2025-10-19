/**
 * FORCE BUTTON PLUS CSS
 * Script pour forcer l'application du CSS du bouton +
 * Version: 1.0
 */

(function() {
    console.log('🔧 [FORCE-BUTTON-PLUS] Script de forçage CSS chargé.');

    function forceButtonPlusCSS() {
        const buttons = document.querySelectorAll('.btn-nouvelle-improved');
        
        if (buttons.length === 0) {
            console.log('🔧 [FORCE-BUTTON-PLUS] Aucun bouton + trouvé, réessai...');
            setTimeout(forceButtonPlusCSS, 100);
            return;
        }

        console.log(`🔧 [FORCE-BUTTON-PLUS] ${buttons.length} bouton(s) + trouvé(s), application du forçage...`);

        buttons.forEach((button, index) => {
            // Forcer les styles de base
            button.style.cssText = `
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 0 !important;
                border-radius: 50% !important;
                font-weight: 700 !important;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
                position: relative !important;
                z-index: 1000 !important;
                width: 48px !important;
                height: 48px !important;
                min-width: 48px !important;
                min-height: 48px !important;
                border: none !important;
                overflow: hidden !important;
                cursor: pointer !important;
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
                color: white !important;
                box-shadow: 
                    0 4px 15px rgba(59, 130, 246, 0.3),
                    0 2px 4px rgba(0, 0, 0, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
                border: 2px solid #2563eb !important;
            `;

            // Forcer l'icône - CENTRAGE PARFAIT - SANS FOND
            const icon = button.querySelector('i');
            if (icon) {
                icon.style.cssText = `
                    font-size: 16px !important;
                    font-weight: 900 !important;
                    transition: all 0.3s ease !important;
                    position: absolute !important;
                    top: 50% !important;
                    left: 50% !important;
                    transform: translate(-50%, -50%) !important;
                    z-index: 2 !important;
                    display: block !important;
                    line-height: 1 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    color: white !important;
                    width: auto !important;
                    height: auto !important;
                    background: none !important;
                    background-color: transparent !important;
                    border: none !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                `;
            }

            // Ajouter des classes pour le mode sombre
            if (document.body.classList.contains('dark-mode')) {
                button.style.cssText = `
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    padding: 0 !important;
                    border-radius: 50% !important;
                    font-weight: 700 !important;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    position: relative !important;
                    z-index: 1000 !important;
                    width: 40px !important;
                    height: 40px !important;
                    min-width: 40px !important;
                    min-height: 40px !important;
                    border: none !important;
                    overflow: hidden !important;
                    cursor: pointer !important;
                    background: linear-gradient(135deg, #00d4ff 0%, #00ffff 100%) !important;
                    color: #000000 !important;
                    box-shadow: 
                        0 0 20px rgba(0, 212, 255, 0.6),
                        0 0 40px rgba(0, 255, 255, 0.3),
                        0 4px 15px rgba(0, 0, 0, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
                    border: 2px solid #00ffff !important;
                `;
                
                if (icon) {
                    icon.style.cssText = `
                        font-size: 16px !important;
                        font-weight: 900 !important;
                        transition: all 0.3s ease !important;
                        position: absolute !important;
                        top: 50% !important;
                        left: 50% !important;
                        transform: translate(-50%, -50%) !important;
                        z-index: 2 !important;
                        display: block !important;
                        line-height: 1 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        color: #000000 !important;
                        width: auto !important;
                        height: auto !important;
                        background: none !important;
                        background-color: transparent !important;
                        border: none !important;
                        border-radius: 0 !important;
                        box-shadow: none !important;
                    `;
                }
            }

            // Ajouter un indicateur de debug
            button.setAttribute('data-force-applied', 'true');
            
            console.log(`🔧 [FORCE-BUTTON-PLUS] Bouton ${index + 1} forcé avec succès.`);
        });

        // Ajouter un indicateur visuel
        const navbar = document.querySelector('#desktop-navbar, .navbar');
        if (navbar && !navbar.querySelector('.force-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'force-indicator';
            indicator.style.cssText = `
                position: absolute !important;
                top: -25px !important;
                right: 10px !important;
                background: #ff6b6b !important;
                color: white !important;
                padding: 2px 6px !important;
                font-size: 10px !important;
                border-radius: 3px !important;
                z-index: 10000 !important;
                content: 'FORCE CSS ACTIF' !important;
            `;
            indicator.textContent = 'FORCE CSS ACTIF';
            navbar.appendChild(indicator);
        }
    }

    // Appliquer immédiatement
    forceButtonPlusCSS();

    // Réappliquer après le chargement complet
    document.addEventListener('DOMContentLoaded', forceButtonPlusCSS);
    window.addEventListener('load', forceButtonPlusCSS);

    // Observer les changements de mode sombre
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                if (mutation.target.classList.contains('dark-mode') || 
                    !mutation.target.classList.contains('dark-mode')) {
                    setTimeout(forceButtonPlusCSS, 100);
                }
            }
        });
    });

    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    // Réappliquer périodiquement pour contrer les autres scripts
    setInterval(forceButtonPlusCSS, 2000);

})();
