/**
 * Solution définitive pour corriger l'affichage du modal principal ajouterCommandeModal
 * Sans modal d'urgence - correction directe du problème de rendu
 */

console.log('🔧 [MAIN-FIX] Script de correction principal chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [MAIN-FIX] DOM chargé, initialisation de la correction principale');
    
    // Attendre que Bootstrap soit initialisé
    setTimeout(() => {
        initMainModalFix();
    }, 1000);
});

function initMainModalFix() {
    const modal = document.getElementById('ajouterCommandeModal');
    if (!modal) {
        console.error('🔧 [MAIN-FIX] Modal ajouterCommandeModal non trouvé');
        return;
    }
    
    console.log('🔧 [MAIN-FIX] ✅ Modal trouvé, installation des correctifs...');
    
    // Écouter l'événement d'ouverture
    modal.addEventListener('show.bs.modal', function(e) {
        console.log('🔧 [MAIN-FIX] Modal en cours d\'ouverture, préparation...');
        prepareModalForDisplay(modal);
    });
    
    // Écouter l'événement d'ouverture complète
    modal.addEventListener('shown.bs.modal', function(e) {
        console.log('🔧 [MAIN-FIX] Modal ouvert, application des corrections...');
        setTimeout(() => {
            fixModalRendering(modal);
        }, 50);
    });
    
    console.log('🔧 [MAIN-FIX] ✅ Correctifs installés sur le modal');
}

function prepareModalForDisplay(modal) {
    console.log('🔧 [MAIN-FIX] 🛠️ Préparation du modal pour l\'affichage...');
    
    // Supprimer tous les styles inline problématiques
    modal.style.removeProperty('width');
    modal.style.removeProperty('height');
    modal.style.removeProperty('min-width');
    modal.style.removeProperty('min-height');
    
    // S'assurer que le modal est dans le DOM à la bonne place
    if (modal.parentNode !== document.body) {
        console.log('🔧 [MAIN-FIX] 📍 Déplacement du modal vers body...');
        document.body.appendChild(modal);
    }
    
    // Nettoyer les classes problématiques
    modal.classList.remove('modal-fade-out');
    
    console.log('🔧 [MAIN-FIX] ✅ Modal préparé');
}

function fixModalRendering(modal) {
    console.log('🔧 [MAIN-FIX] 🔧 Correction du rendu du modal...');
    
    const dialog = modal.querySelector('.modal-dialog');
    const content = modal.querySelector('.modal-content');
    
    if (!dialog || !content) {
        console.error('🔧 [MAIN-FIX] Éléments du modal manquants');
        return;
    }
    
    // Vérifier les dimensions actuelles
    const dimensions = {
        modal: { width: modal.offsetWidth, height: modal.offsetHeight },
        dialog: { width: dialog.offsetWidth, height: dialog.offsetHeight },
        content: { width: content.offsetWidth, height: content.offsetHeight }
    };
    
    console.log('🔧 [MAIN-FIX] 📊 Dimensions avant correction:', dimensions);
    
    // Si les dimensions sont nulles, forcer le rendu
    if (modal.offsetWidth === 0 || modal.offsetHeight === 0) {
        console.log('🔧 [MAIN-FIX] ⚠️ Dimensions nulles détectées, correction en cours...');
        
        // Méthode 1: Forcer le recalcul via CSS
        forceLayoutRecalculation(modal, dialog, content);
        
        // Attendre un peu et vérifier
        setTimeout(() => {
            const newDimensions = {
                modal: { width: modal.offsetWidth, height: modal.offsetHeight },
                dialog: { width: dialog.offsetWidth, height: dialog.offsetHeight },
                content: { width: content.offsetWidth, height: content.offsetHeight }
            };
            
            console.log('🔧 [MAIN-FIX] 📊 Dimensions après correction:', newDimensions);
            
            if (modal.offsetWidth === 0 || modal.offsetHeight === 0) {
                console.log('🔧 [MAIN-FIX] ⚠️ Première méthode échouée, application de la méthode alternative...');
                applyAlternativeRendering(modal, dialog, content);
            } else {
                console.log('🔧 [MAIN-FIX] ✅ SUCCESS! Modal maintenant visible');
                finalizeModalDisplay(modal);
            }
        }, 100);
        
    } else {
        console.log('🔧 [MAIN-FIX] ✅ Modal déjà visible, dimensions OK');
        finalizeModalDisplay(modal);
    }
}

function forceLayoutRecalculation(modal, dialog, content) {
    console.log('🔧 [MAIN-FIX] 🔄 Forçage du recalcul de layout...');
    
    // Technique 1: Hide/Show pour forcer le rendu
    const originalDisplay = modal.style.display;
    modal.style.display = 'none';
    modal.offsetHeight; // Force reflow
    modal.style.display = originalDisplay || 'block';
    
    // Technique 2: Modifier temporairement la position
    const originalPosition = modal.style.position;
    modal.style.position = 'absolute';
    modal.offsetHeight; // Force reflow
    modal.style.position = originalPosition || 'fixed';
    
    // Technique 3: Forcer le recalcul des enfants
    dialog.style.display = 'none';
    dialog.offsetHeight; // Force reflow
    dialog.style.display = 'flex';
    
    // Technique 4: Ajouter/supprimer une classe temporaire
    modal.classList.add('force-render');
    modal.offsetHeight; // Force reflow
    modal.classList.remove('force-render');
    
    console.log('🔧 [MAIN-FIX] ✅ Recalcul de layout terminé');
}

function applyAlternativeRendering(modal, dialog, content) {
    console.log('🔧 [MAIN-FIX] 🆘 Application de la méthode alternative...');
    
    // Créer un style temporaire très spécifique
    const tempStyle = document.createElement('style');
    tempStyle.id = 'modal-main-fix-temp';
    tempStyle.textContent = `
        #ajouterCommandeModal.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 1060 !important;
            background: rgba(0, 0, 0, 0.5) !important;
        }
        
        #ajouterCommandeModal.show .modal-dialog {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            margin: 1.75rem auto !important;
            max-width: 1000px !important;
            width: 95% !important;
            height: auto !important;
            pointer-events: auto !important;
            transform: none !important;
        }
        
        #ajouterCommandeModal.show .modal-content {
            display: flex !important;
            flex-direction: column !important;
            visibility: visible !important;
            opacity: 1 !important;
            background: white !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            width: 100% !important;
            height: auto !important;
            min-height: 400px !important;
        }
        
        #ajouterCommandeModal.show .modal-header,
        #ajouterCommandeModal.show .modal-body,
        #ajouterCommandeModal.show .modal-footer {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
    `;
    
    document.head.appendChild(tempStyle);
    
    // Forcer l'application du style
    modal.classList.remove('show');
    modal.offsetHeight; // Force reflow
    modal.classList.add('show');
    
    // Vérifier après un court délai
    setTimeout(() => {
        const finalDimensions = {
            modal: { width: modal.offsetWidth, height: modal.offsetHeight },
            dialog: { width: dialog.offsetWidth, height: dialog.offsetHeight },
            content: { width: content.offsetWidth, height: content.offsetHeight }
        };
        
        console.log('🔧 [MAIN-FIX] 📊 Dimensions finales:', finalDimensions);
        
        if (modal.offsetWidth > 0 && modal.offsetHeight > 0) {
            console.log('🔧 [MAIN-FIX] ✅ SUCCESS! Méthode alternative réussie');
            finalizeModalDisplay(modal);
        } else {
            console.error('🔧 [MAIN-FIX] ❌ ÉCHEC TOTAL - Toutes les méthodes ont échoué');
        }
    }, 200);
}

function finalizeModalDisplay(modal) {
    console.log('🔧 [MAIN-FIX] 🎯 Finalisation de l\'affichage du modal...');
    
    // Indicateur visuel supprimé pour éviter les messages gênants
    // L'indicateur de succès est maintenant uniquement dans la console
    
    // Supprimer le style temporaire s'il existe
    const tempStyle = document.getElementById('modal-main-fix-temp');
    if (tempStyle) {
        setTimeout(() => tempStyle.remove(), 5000); // Garder 5s pour stabilité
    }
    
    console.log('🔧 [MAIN-FIX] ✅ Modal principal entièrement fonctionnel !');
}

// Fonction utilitaire pour debug manuel
window.debugMainModal = function() {
    const modal = document.getElementById('ajouterCommandeModal');
    if (!modal) {
        console.error('Modal non trouvé');
        return;
    }
    
    console.log('🔧 [DEBUG] État actuel du modal:', {
        classes: modal.className,
        display: getComputedStyle(modal).display,
        visibility: getComputedStyle(modal).visibility,
        opacity: getComputedStyle(modal).opacity,
        zIndex: getComputedStyle(modal).zIndex,
        position: getComputedStyle(modal).position,
        dimensions: {
            offset: { width: modal.offsetWidth, height: modal.offsetHeight },
            client: { width: modal.clientWidth, height: modal.clientHeight },
            scroll: { width: modal.scrollWidth, height: modal.scrollHeight }
        },
        boundingRect: modal.getBoundingClientRect()
    });
};

console.log('🔧 [MAIN-FIX] ✅ Script principal prêt');
console.log('🔧 [MAIN-FIX] 💡 Utilisez window.debugMainModal() pour diagnostiquer manuellement');

