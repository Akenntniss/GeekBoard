/**
 * Debug avancé pour analyser complètement pourquoi le modal ne s'affiche pas
 * malgré les propriétés correctes
 */

document.addEventListener('DOMContentLoaded', function () {

    // Fonction pour analyser complètement un modal
    window.analyzeModalDisplay = function (modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        console.log(`🔍 === ANALYSE COMPLÈTE DU MODAL ${modalId} ===`);

        // 1. Propriétés de base

        // 2. Styles calculés
        const computedStyle = window.getComputedStyle(modal);

        // 3. Position et dimensions
        const rect = modal.getBoundingClientRect();

        // 4. Analyse des enfants
        const dialog = modal.querySelector('.modal-dialog');
        const content = modal.querySelector('.modal-content');

        if (dialog) {
            const dialogStyle = window.getComputedStyle(dialog);
            const dialogRect = dialog.getBoundingClientRect();
        }

        if (content) {
            const contentStyle = window.getComputedStyle(content);
            const contentRect = content.getBoundingClientRect();
        }

        // 5. Vérifier les backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach((backdrop, index) => {
            const backdropStyle = window.getComputedStyle(backdrop);
            // Debug info removed
        });

        // 6. Vérifier les éléments qui pourraient masquer le modal
        const elementsWithHighZIndex = [];
        document.querySelectorAll('*').forEach(el => {
            const style = window.getComputedStyle(el);
            const zIndex = parseInt(style.zIndex);
            if (zIndex > 1050 && el !== modal) {
                elementsWithHighZIndex.push({
                    element: el,
                    zIndex: zIndex,
                    tagName: el.tagName,
                    id: el.id,
                    classes: el.className
                });
            }
        });

        // 7. Test de visibilité
        const isVisible = modal.offsetWidth > 0 && modal.offsetHeight > 0;

        const elementAtCenter = document.elementFromPoint(
            window.innerWidth / 2
            window.innerHeight / 2
        );

        // 8. Forcer l'affichage pour test
        const originalStyles = {
            display: modal.style.display,
            visibility: modal.style.visibility,
            opacity: modal.style.opacity,
            zIndex: modal.style.zIndex,
            position: modal.style.position,
            top: modal.style.top,
            left: modal.style.left,
            width: modal.style.width,
            height: modal.style.height,
            backgroundColor: modal.style.backgroundColor,
            border: modal.style.border,
        };

        // Forcer tous les styles
        modal.style.display = 'block';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.zIndex = '9999';
        modal.style.position = 'fixed';
        modal.style.top = '50px';
        modal.style.left = '50px';
        modal.style.width = '80%';
        modal.style.height = '80%';
        modal.style.backgroundColor = 'rgba(255, 0, 0, 0.8)';
        modal.style.border = '5px solid red';

        setTimeout(() => {
            const afterForceRect = modal.getBoundingClientRect();

            // Restaurer les styles - Version améliorée
            Object.keys(originalStyles).forEach(key => {
                if (originalStyles[key]) {
                    modal.style[key] = originalStyles[key];
                } else {
                    modal.style.removeProperty(key.replace(/([A-Z])/g, '-$1').toLowerCase());
                }
            });
            // S'assurer que les styles de debug sont complètement supprimés
            modal.style.removeProperty('background-color');
            modal.style.removeProperty('border');
            modal.style.removeProperty('position');
            modal.style.removeProperty('top');
            modal.style.removeProperty('left');
            modal.style.removeProperty('width');
            modal.style.removeProperty('height');
        }, 2000);

        console.log('🔍 === FIN ANALYSE ===');
    };

    // Analyser automatiquement quand ajouterCommandeModal s'ouvre - DISABLED
    const ajouterCommandeModal = document.getElementById('ajouterCommandeModal');
    if (ajouterCommandeModal) {
        // Debug automatique désactivé pour éviter la bordure rouge
        /*
        ajouterCommandeModal.addEventListener('shown.bs.modal', function() {
            setTimeout(() => {
                window.analyzeModalDisplay('ajouterCommandeModal');
            }, 100);
        */
    }

    console.log('💡 Utilisez window.analyzeModalDisplay("ajouterCommandeModal") pour analyser manuellement');
