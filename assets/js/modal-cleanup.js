// Script pour nettoyer les modals au chargement
(function() {
    console.log('🧹 [MODAL-CLEANUP] Nettoyage des modals...');
    
    function cleanupModals() {
        // Fermer tous les modals Bootstrap
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            // Forcer la fermeture
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
            modal.style.zIndex = '-1';
            modal.style.pointerEvents = 'none';
            modal.style.transform = 'translateY(-100vh)';
            
            // Supprimer les attributs Bootstrap
            modal.removeAttribute('aria-modal');
            modal.removeAttribute('role');
            modal.setAttribute('aria-hidden', 'true');
        });
        
        // Supprimer tous les backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        
        // Nettoyer le body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        console.log('✅ [MODAL-CLEANUP] Modals nettoyés:', modals.length);
    }
    
    // Nettoyer immédiatement
    cleanupModals();
    
    // Nettoyer au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', cleanupModals);
    }
    
    // Nettoyer après un délai pour être sûr
    setTimeout(cleanupModals, 500);
    setTimeout(cleanupModals, 1000);
    
    // Fonction globale pour nettoyer manuellement
    window.cleanupAllModals = cleanupModals;
    
    console.log('🧹 [MODAL-CLEANUP] Script installé');
})();
