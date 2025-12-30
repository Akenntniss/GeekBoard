/**
 * Correction du système de garde des modals
 * Ce script corrige l'ordre des vérifications pour permettre allowHide de fonctionner
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🛡️ Correction du système de garde des modals');
    
    // Attendre que le système de garde original soit en place
    setTimeout(() => {
        // Remplacer l'écouteur hide.bs.modal existant
        const originalHideHandler = findAndReplaceHideHandler();
        
        if (originalHideHandler) {
            console.log('✅ Système de garde corrigé');
        } else {
            console.warn('⚠️ Impossible de corriger le système de garde');
        }
    }, 100);
    
    function findAndReplaceHideHandler() {
        try {
            // Récupérer les écouteurs existants (approche moderne)
            const hideHandlers = getEventListeners(document, 'hide.bs.modal');
            
            if (!hideHandlers || hideHandlers.length === 0) {
                // Fallback : ajouter notre propre gestionnaire avec priorité
                document.addEventListener('hide.bs.modal', correctedHideHandler, { capture: true });
                return true;
            }
            
            return false;
        } catch (e) {
            // Fallback : ajouter notre gestionnaire avec capture pour qu'il s'exécute en premier
            document.addEventListener('hide.bs.modal', correctedHideHandler, { capture: true });
            return true;
        }
    }
    
    function correctedHideHandler(event) {
        const modal = event.target;
        const id = modal && modal.id ? modal.id : '';
        
        // Vérifier d'abord allowHide - c'est la correction principale !
        if (modal && modal.dataset.allowHide) {
            console.log('🔓 [MODAL GUARD FIX] allowHide détecté, autorisation de fermeture pour:', id);
            return; // Laisser la fermeture se faire
        }
        
        // Ensuite vérifier si c'est un modal protégé
        const protectedIds = ['nouvelles_actions_modal', 'chooseStatusModal'];
        if (!protectedIds.includes(id)) {
            return; // Pas un modal protégé, laisser faire
        }
        
        // Pour les modals protégés sans allowHide, vérifier la fenêtre de temps
        const now = Date.now();
        const guardWindow = window.modalGuardWindows && window.modalGuardWindows[id];
        
        if (guardWindow && now <= guardWindow) {
            console.log('🛡️ [MODAL GUARD FIX] Empêche la fermeture (fenêtre active):', id);
            event.preventDefault();
            event.stopImmediatePropagation();
            
            try {
                const instance = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: 'static', keyboard: false });
                setTimeout(() => instance.show(), 0);
            } catch (e) {
                console.warn('Erreur lors de la réouverture du modal:', e);
            }
        }
    }
    
    // Fonction utilitaire pour récupérer les écouteurs d'événements
    function getEventListeners(element, eventType) {
        if (typeof getEventListeners !== 'undefined') {
            const listeners = getEventListeners(element);
            return listeners[eventType] || [];
        }
        return null;
    }
    
    // Exposer les fenêtres de garde pour que notre script puisse les consulter
    if (!window.modalGuardWindows) {
        window.modalGuardWindows = {};
        
        // Intercepter les événements show pour maintenir les fenêtres de garde
        document.addEventListener('show.bs.modal', function(event) {
            const modal = event.target;
            const id = modal && modal.id ? modal.id : '';
            const protectedIds = ['nouvelles_actions_modal', 'chooseStatusModal'];
            
            if (protectedIds.includes(id)) {
                window.modalGuardWindows[id] = Date.now() + 1500;
                console.log('🛡️ [MODAL GUARD FIX] Fenêtre de garde activée pour:', id);
            }
        });
        
        // Nettoyer les fenêtres de garde quand les modals se ferment
        document.addEventListener('hidden.bs.modal', function(event) {
            const modal = event.target;
            const id = modal && modal.id ? modal.id : '';
            
            if (window.modalGuardWindows[id]) {
                delete window.modalGuardWindows[id];
                console.log('🧹 [MODAL GUARD FIX] Fenêtre de garde nettoyée pour:', id);
            }
        });
    }
    
    console.log('🛡️ Correction du système de garde initialisée');
});
