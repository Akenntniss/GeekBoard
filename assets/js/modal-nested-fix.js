/**
 * Correction automatique des z-index pour les modals imbriqués
 * Résout le problème du scanner caché derrière le backdrop
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [MODAL-NESTED-FIX] Initialisation de la correction des modals imbriqués...');
    
    let modalStack = [];
    let baseZIndex = 1050;
    let backdropZIndex = 1040;
    
    // Fonction pour ajuster les z-index
    function adjustModalZIndex(modal, level) {
        const modalZIndex = baseZIndex + (level * 100);
        const modalBackdropZIndex = modalZIndex - 10;
        
        console.log(`📐 [MODAL-NESTED-FIX] Ajustement modal ${modal.id}: z-index=${modalZIndex}, backdrop=${modalBackdropZIndex}`);
        
        // Ajuster le modal
        modal.style.zIndex = modalZIndex;
        
        // Ajuster le modal-dialog et modal-content
        const modalDialog = modal.querySelector('.modal-dialog');
        const modalContent = modal.querySelector('.modal-content');
        
        if (modalDialog) {
            modalDialog.style.zIndex = modalZIndex + 1;
        }
        
        if (modalContent) {
            modalContent.style.zIndex = modalZIndex + 2;
        }
        
        // Ajuster le backdrop correspondant
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 0) {
                const currentBackdrop = backdrops[backdrops.length - 1];
                if (currentBackdrop) {
                    currentBackdrop.style.zIndex = modalBackdropZIndex;
                    console.log(`🎭 [MODAL-NESTED-FIX] Backdrop ajusté: z-index=${modalBackdropZIndex}`);
                }
            }
        }, 50);
    }
    
    // Écouter l'ouverture des modals
    document.addEventListener('show.bs.modal', function(event) {
        const modal = event.target;
        
        if (!modal || !modal.id) return;
        
        console.log(`🚀 [MODAL-NESTED-FIX] Ouverture du modal: ${modal.id}`);
        
        // Ajouter à la pile
        modalStack.push(modal);
        
        // Ajuster les z-index de tous les modals dans la pile
        modalStack.forEach((stackedModal, index) => {
            adjustModalZIndex(stackedModal, index);
        });
        
        // Cas spécial pour le scanner universel
        if (modal.id === 'universal_scanner_modal') {
            console.log('🔍 [MODAL-NESTED-FIX] Scanner universel détecté - z-index prioritaire');
            modal.style.zIndex = '2100';
            
            const modalDialog = modal.querySelector('.modal-dialog');
            const modalContent = modal.querySelector('.modal-content');
            
            if (modalDialog) modalDialog.style.zIndex = '2101';
            if (modalContent) modalContent.style.zIndex = '2102';
            
            // Ajuster le backdrop
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 0) {
                    const lastBackdrop = backdrops[backdrops.length - 1];
                    if (lastBackdrop) {
                        lastBackdrop.style.zIndex = '2099';
                    }
                }
            }, 50);
        }
    });
    
    // Écouter la fermeture des modals
    document.addEventListener('hidden.bs.modal', function(event) {
        const modal = event.target;
        
        if (!modal || !modal.id) return;
        
        console.log(`❌ [MODAL-NESTED-FIX] Fermeture du modal: ${modal.id}`);
        
        // Retirer de la pile
        const index = modalStack.findIndex(m => m.id === modal.id);
        if (index > -1) {
            modalStack.splice(index, 1);
        }
        
        // Réajuster les z-index des modals restants
        modalStack.forEach((stackedModal, index) => {
            adjustModalZIndex(stackedModal, index);
        });
        
        // Nettoyage des backdrops orphelins
        setTimeout(() => {
            cleanupOrphanedBackdrops();
        }, 100);
        
        console.log(`📊 [MODAL-NESTED-FIX] Modals restants dans la pile: ${modalStack.length}`);
    });
    
    // Fonction pour nettoyer les backdrops orphelins
    function cleanupOrphanedBackdrops() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        const openModals = document.querySelectorAll('.modal.show');
        
        console.log(`🧹 [MODAL-NESTED-FIX] Nettoyage: ${backdrops.length} backdrops, ${openModals.length} modals ouverts`);
        
        // Si il y a plus de backdrops que de modals ouverts, supprimer les surplus
        if (backdrops.length > openModals.length) {
            const excessBackdrops = backdrops.length - openModals.length;
            console.log(`🗑️ [MODAL-NESTED-FIX] Suppression de ${excessBackdrops} backdrop(s) orphelin(s)`);
            
            // Supprimer les backdrops en surplus (les derniers)
            for (let i = backdrops.length - 1; i >= openModals.length; i--) {
                const backdrop = backdrops[i];
                if (backdrop) {
                    backdrop.remove();
                    console.log(`🗑️ [MODAL-NESTED-FIX] Backdrop supprimé`);
                }
            }
        }
        
        // Si aucun modal n'est ouvert, supprimer tous les backdrops
        if (openModals.length === 0) {
            console.log(`🧽 [MODAL-NESTED-FIX] Aucun modal ouvert - suppression de tous les backdrops`);
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });
            
            // Réactiver le scroll du body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    }
    
    // Fonction spéciale pour ouvrir le scanner depuis nouvelles_actions_modal
    window.openScannerFromActions = function() {
        console.log('🔗 [MODAL-NESTED-FIX] Ouverture du scanner depuis nouvelles_actions_modal');
        
        // Ne pas fermer le modal parent, juste ouvrir le scanner par-dessus
        const scannerModal = document.getElementById('universal_scanner_modal');
        if (scannerModal) {
            const modal = new bootstrap.Modal(scannerModal);
            modal.show();
        }
    };
    
    // Fonction de nettoyage forcé accessible globalement
    window.forceCleanupBackdrops = function() {
        console.log('🚨 [MODAL-NESTED-FIX] Nettoyage forcé des backdrops...');
        
        const backdrops = document.querySelectorAll('.modal-backdrop');
        const openModals = document.querySelectorAll('.modal.show');
        
        console.log(`🧹 [MODAL-NESTED-FIX] Nettoyage forcé: ${backdrops.length} backdrops, ${openModals.length} modals ouverts`);
        
        // Supprimer tous les backdrops
        backdrops.forEach((backdrop, index) => {
            backdrop.remove();
            console.log(`🗑️ [MODAL-NESTED-FIX] Backdrop ${index + 1} supprimé`);
        });
        
        // Réinitialiser le body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Vider la pile des modals
        modalStack = [];
        
        console.log('✅ [MODAL-NESTED-FIX] Nettoyage forcé terminé');
    };
    
    // Ajouter un raccourci clavier pour le nettoyage d'urgence (Ctrl+Alt+C)
    document.addEventListener('keydown', function(event) {
        if (event.ctrlKey && event.altKey && event.key === 'c') {
            console.log('⌨️ [MODAL-NESTED-FIX] Raccourci de nettoyage détecté');
            window.forceCleanupBackdrops();
        }
    });
    
    console.log('✅ [MODAL-NESTED-FIX] Correction des modals imbriqués initialisée');
    console.log('💡 [MODAL-NESTED-FIX] Raccourci d\'urgence: Ctrl+Alt+C pour nettoyer les backdrops');
});

