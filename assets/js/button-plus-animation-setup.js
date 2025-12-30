/**
 * BOUTON + ANIMATION SETUP
 * Script pour configurer les éléments d'animation du bouton +
 * Version: 1.0
 */

(function () {
    console.log("🚀 [BUTTON-PLUS-ANIMATION] Script d'animation avancée chargé.");

    function setupButtonAnimation() {
        const plusButtons = document.querySelectorAll('.btn-nouvelle-improved');

        if (plusButtons.length === 0) {
            console.log("🔍 [BUTTON-PLUS-ANIMATION] Aucun bouton '+' trouvé, réessai...");
            return;
        }

        console.log(`💪 [BUTTON-PLUS-ANIMATION] Configuration de ${plusButtons.length} bouton(s) '+'...`);

        plusButtons.forEach((button, index) => {
            // Vérifier si déjà configuré
            if (button.hasAttribute('data-animation-setup')) {
                return;
            }

            // Créer le conteneur dots_border
            const dotsBorder = document.createElement('div');
            dotsBorder.className = 'dots_border';
            button.appendChild(dotsBorder);

            // Créer l'élément sparkle pour l'icône
            const sparkle = document.createElement('div');
            sparkle.className = 'sparkle';

            // Créer les paths SVG pour l'effet sparkle
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('fill', 'none');
            svg.style.width = '100%';
            svg.style.height = '100%';

            // Path 1
            const path1 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path1.setAttribute('d', 'M12 2L13.09 8.26L19 7L14.74 12L19 17L13.09 15.74L12 22L10.91 15.74L5 17L9.26 12L5 7L10.91 8.26L12 2Z');
            path1.className = 'path';
            svg.appendChild(path1);

            // Path 2
            const path2 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path2.setAttribute('d', 'M8 8L16 8L16 16L8 16L8 8Z');
            path2.className = 'path';
            svg.appendChild(path2);

            // Path 3
            const path3 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path3.setAttribute('d', 'M12 6L14 10L18 10L15 13L16 17L12 15L8 17L9 13L6 10L10 10L12 6Z');
            path3.className = 'path';
            svg.appendChild(path3);

            sparkle.appendChild(svg);

            // Remplacer l'icône existante par le sparkle
            const existingIcon = button.querySelector('i');
            if (existingIcon) {
                existingIcon.style.display = 'none';
            }

            button.appendChild(sparkle);

            // Marquer comme configuré
            button.setAttribute('data-animation-setup', 'true');

            console.log(`✅ [BUTTON-PLUS-ANIMATION] Bouton ${index + 1} configuré avec succès.`);
        });
    }

    // Appliquer la configuration au chargement et lors des changements
    document.addEventListener('DOMContentLoaded', setupButtonAnimation);
    window.addEventListener('resize', setupButtonAnimation);
    window.addEventListener('orientationchange', setupButtonAnimation);

    // Réappliquer toutes les 3 secondes pour s'assurer que les nouveaux boutons sont configurés
    setInterval(setupButtonAnimation, 3000);

    // Indicateur de debug
    const debugIndicator = document.createElement('div');
    debugIndicator.id = 'button-plus-animation-debug';
    debugIndicator.style.cssText = `
        position: fixed;
        top: 50px;
        right: 10px;
        background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
        color: white;
        padding: 5px 10px;
        font-size: 12px;
        border-radius: 5px;
        z-index: 9999999;
        opacity: 0.8;
        pointer-events: none;
        font-weight: bold;
        text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    `;
    debugIndicator.textContent = 'ANIMATION + ACTIVE';
    document.body.appendChild(debugIndicator);

    console.log("🎯 [BUTTON-PLUS-ANIMATION] Configuration terminée avec indicateur de debug.");
})();
