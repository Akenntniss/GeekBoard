/**
 * 🚨 SERVO Console Fix - Script de console pour débloquer immédiatement SERVO
 * Version: 1.0
 * Date: 2024-10-12
 * 
 * Copiez et collez ce code dans la console du navigateur pour débloquer immédiatement
 */

(function() {
    console.log('🚨 [SERVO-CONSOLE] Démarrage de la correction immédiate...');
    
    // 1. Masquer immédiatement l'animation SERVO
    const servoElements = document.querySelectorAll('.servo-animation, .loading-animation, [class*="servo"], [class*="loading"]');
    console.log('🚨 [SERVO-CONSOLE] Éléments SERVO trouvés:', servoElements.length);
    
    servoElements.forEach((element, index) => {
        console.log(`🚨 [SERVO-CONSOLE] Masquage de l'élément ${index + 1}:`, element);
        element.style.display = 'none';
        element.style.visibility = 'hidden';
        element.style.opacity = '0';
        element.style.animation = 'none';
        element.style.transition = 'none';
        element.style.height = '0';
        element.style.width = '0';
        element.style.overflow = 'hidden';
    });
    
    // 2. Supprimer les scripts dupliqués
    const duplicateScripts = document.querySelectorAll('script[src*="status-modal.js"], script[src*="devis-clean.js"], script[src*="session-helper.js"], script[src*="update-status-modal.js"], script[src*="modal-helper.js"]');
    console.log('🚨 [SERVO-CONSOLE] Scripts dupliqués trouvés:', duplicateScripts.length);
    
    duplicateScripts.forEach((script, index) => {
        if (index > 0) {
            console.log(`🚨 [SERVO-CONSOLE] Suppression du script dupliqué ${index + 1}:`, script.src);
            script.remove();
        }
    });
    
    // 3. Nettoyer les variables globales en conflit
    console.log('🚨 [SERVO-CONSOLE] Nettoyage des variables globales...');
    try {
        if (typeof window.StatusModal !== 'undefined') {
            delete window.StatusModal;
            console.log('🚨 [SERVO-CONSOLE] StatusModal supprimé');
        }
        if (typeof window.DevisCleanManager !== 'undefined') {
            delete window.DevisCleanManager;
            console.log('🚨 [SERVO-CONSOLE] DevisCleanManager supprimé');
        }
        if (typeof window.SessionHelper !== 'undefined') {
            delete window.SessionHelper;
            console.log('🚨 [SERVO-CONSOLE] SessionHelper supprimé');
        }
        if (typeof window.UpdateStatusModal !== 'undefined') {
            delete window.UpdateStatusModal;
            console.log('🚨 [SERVO-CONSOLE] UpdateStatusModal supprimé');
        }
        if (typeof window.ModalHelper !== 'undefined') {
            delete window.ModalHelper;
            console.log('🚨 [SERVO-CONSOLE] ModalHelper supprimé');
        }
    } catch (e) {
        console.log('🚨 [SERVO-CONSOLE] Erreur lors du nettoyage des variables:', e);
    }
    
    // 4. Fermer tous les modals ouverts
    const openModals = document.querySelectorAll('.modal.show, .modal[style*="display: block"]');
    console.log('🚨 [SERVO-CONSOLE] Modals ouverts trouvés:', openModals.length);
    
    openModals.forEach((modal, index) => {
        console.log(`🚨 [SERVO-CONSOLE] Fermeture du modal ${index + 1}:`, modal.id || modal.className);
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
    });
    
    // 5. Supprimer tous les backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    console.log('🚨 [SERVO-CONSOLE] Backdrops trouvés:', backdrops.length);
    
    backdrops.forEach((backdrop, index) => {
        console.log(`🚨 [SERVO-CONSOLE] Suppression du backdrop ${index + 1}`);
        backdrop.remove();
    });
    
    // 6. Réinitialiser le body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    console.log('🚨 [SERVO-CONSOLE] Body réinitialisé');
    
    // 7. Masquer tous les éléments de chargement
    const loadingElements = document.querySelectorAll('.loading, .spinner, .loader, [class*="loading"], [class*="spinner"]');
    console.log('🚨 [SERVO-CONSOLE] Éléments de chargement trouvés:', loadingElements.length);
    
    loadingElements.forEach((element, index) => {
        console.log(`🚨 [SERVO-CONSOLE] Masquage de l'élément de chargement ${index + 1}`);
        element.style.display = 'none';
        element.style.visibility = 'hidden';
    });
    
    // 8. Forcer l'affichage du contenu principal
    const mainContent = document.querySelector('.main-content, .content, .container, #main-content, #content');
    if (mainContent) {
        console.log('🚨 [SERVO-CONSOLE] Contenu principal trouvé, forçage de l\'affichage');
        mainContent.style.display = 'block';
        mainContent.style.visibility = 'visible';
        mainContent.style.opacity = '1';
    } else {
        console.log('🚨 [SERVO-CONSOLE] Aucun contenu principal trouvé');
    }
    
    // 9. Forcer le rechargement des styles
    const styleSheets = document.querySelectorAll('link[rel="stylesheet"]');
    styleSheets.forEach(link => {
        const href = link.href;
        link.href = href + '?v=' + Date.now();
    });
    
    console.log('🚨 [SERVO-CONSOLE] ✅ Correction immédiate terminée');
    console.log('🚨 [SERVO-CONSOLE] La page devrait maintenant être débloquée');
    console.log('🚨 [SERVO-CONSOLE] Si le problème persiste, rechargez la page');
    
    // Retourner un message de succès
    return 'SERVO débloqué avec succès !';
})();








