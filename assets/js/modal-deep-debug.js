/**
 * Debug avancé pour analyser complètement pourquoi le modal ne s'affiche pas
 * malgré les propriétés correctes
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Debug avancé des modals initialisé');
    
    // Fonction pour analyser complètement un modal
    window.analyzeModalDisplay = function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('❌ Modal non trouvé:', modalId);
            return;
        }
        
        console.log(`🔍 === ANALYSE COMPLÈTE DU MODAL ${modalId} ===`);
        
        // 1. Propriétés de base
        console.log('📋 PROPRIÉTÉS DE BASE:');
        console.log('  - Classes:', modal.className);
        console.log('  - Style display:', modal.style.display);
        console.log('  - Style visibility:', modal.style.visibility);
        console.log('  - Style opacity:', modal.style.opacity);
        console.log('  - Style zIndex:', modal.style.zIndex);
        console.log('  - aria-hidden:', modal.getAttribute('aria-hidden'));
        
        // 2. Styles calculés
        const computedStyle = window.getComputedStyle(modal);
        console.log('🎨 STYLES CALCULÉS:');
        console.log('  - display:', computedStyle.display);
        console.log('  - visibility:', computedStyle.visibility);
        console.log('  - opacity:', computedStyle.opacity);
        console.log('  - zIndex:', computedStyle.zIndex);
        console.log('  - position:', computedStyle.position);
        console.log('  - top:', computedStyle.top);
        console.log('  - left:', computedStyle.left);
        console.log('  - width:', computedStyle.width);
        console.log('  - height:', computedStyle.height);
        console.log('  - transform:', computedStyle.transform);
        
        // 3. Position et dimensions
        const rect = modal.getBoundingClientRect();
        console.log('📐 POSITION ET DIMENSIONS:');
        console.log('  - getBoundingClientRect:', rect);
        console.log('  - offsetWidth:', modal.offsetWidth);
        console.log('  - offsetHeight:', modal.offsetHeight);
        console.log('  - scrollWidth:', modal.scrollWidth);
        console.log('  - scrollHeight:', modal.scrollHeight);
        
        // 4. Analyse des enfants
        const dialog = modal.querySelector('.modal-dialog');
        const content = modal.querySelector('.modal-content');
        
        if (dialog) {
            const dialogStyle = window.getComputedStyle(dialog);
            const dialogRect = dialog.getBoundingClientRect();
            console.log('🏗️ MODAL-DIALOG:');
            console.log('  - Classes:', dialog.className);
            console.log('  - display:', dialogStyle.display);
            console.log('  - visibility:', dialogStyle.visibility);
            console.log('  - opacity:', dialogStyle.opacity);
            console.log('  - transform:', dialogStyle.transform);
            console.log('  - position:', dialogRect);
        }
        
        if (content) {
            const contentStyle = window.getComputedStyle(content);
            const contentRect = content.getBoundingClientRect();
            console.log('📄 MODAL-CONTENT:');
            console.log('  - Classes:', content.className);
            console.log('  - display:', contentStyle.display);
            console.log('  - visibility:', contentStyle.visibility);
            console.log('  - opacity:', contentStyle.opacity);
            console.log('  - background:', contentStyle.backgroundColor);
            console.log('  - position:', contentRect);
        }
        
        // 5. Vérifier les backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        console.log('🎭 BACKDROPS:');
        backdrops.forEach((backdrop, index) => {
            const backdropStyle = window.getComputedStyle(backdrop);
            console.log(`  - Backdrop ${index + 1}:`, {
                classes: backdrop.className,
                zIndex: backdropStyle.zIndex,
                opacity: backdropStyle.opacity,
                display: backdropStyle.display
            });
        });
        
        // 6. Vérifier les éléments qui pourraient masquer le modal
        console.log('🚫 ÉLÉMENTS POTENTIELLEMENT MASQUANTS:');
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
        console.log('  - Éléments avec z-index élevé:', elementsWithHighZIndex);
        
        // 7. Test de visibilité
        console.log('👁️ TEST DE VISIBILITÉ:');
        const isVisible = modal.offsetWidth > 0 && modal.offsetHeight > 0;
        console.log('  - offsetWidth/Height > 0:', isVisible);
        
        const elementAtCenter = document.elementFromPoint(
            window.innerWidth / 2, 
            window.innerHeight / 2
        );
        console.log('  - Élément au centre de l\'écran:', elementAtCenter);
        
        // 8. Forcer l'affichage pour test
        console.log('🔧 TEST DE FORÇAGE:');
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
            border: modal.style.border
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
            console.log('  - Position après forçage:', afterForceRect);
            console.log('  - Visible après forçage:', modal.offsetWidth > 0 && modal.offsetHeight > 0);
            
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
            console.log('🔍 Auto-analyse du modal ajouterCommandeModal');
            setTimeout(() => {
                window.analyzeModalDisplay('ajouterCommandeModal');
            }, 100);
        });
        */
    }
    
    console.log('💡 Utilisez window.analyzeModalDisplay("ajouterCommandeModal") pour analyser manuellement');
});