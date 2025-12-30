/**
 * 🚨 SERVO Emergency Fix - Correction d'urgence pour débloquer l'animation SERVO
 * Version: 1.0
 * Date: 2024-10-12
 * 
 * Ce script peut être exécuté directement dans la console du navigateur
 */

console.log('🚨 [SERVO-EMERGENCY] Script d\'urgence chargé');

// Fonction d'urgence pour débloquer SERVO
function emergencyServoFix() {
    console.log('🚨 [SERVO-EMERGENCY] Démarrage de la correction d\'urgence...');
    
    // 1. Masquer immédiatement l'animation SERVO
    const servoElements = document.querySelectorAll('.servo-animation, .loading-animation, [class*="servo"], [class*="loading"]');
    servoElements.forEach(element => {
        element.style.display = 'none';
        element.style.visibility = 'hidden';
        element.style.opacity = '0';
        element.style.animation = 'none';
        element.style.transition = 'none';
    });
    
    // 2. Supprimer les scripts dupliqués
    const duplicateScripts = document.querySelectorAll('script[src*="status-modal.js"], script[src*="devis-clean.js"], script[src*="session-helper.js"], script[src*="update-status-modal.js"], script[src*="modal-helper.js"]');
    duplicateScripts.forEach((script, index) => {
        if (index > 0) {
            script.remove();
        }
    });
    
    // 3. Nettoyer les variables globales en conflit
    try {
        delete window.StatusModal;
        delete window.DevisCleanManager;
        delete window.SessionHelper;
        delete window.UpdateStatusModal;
        delete window.ModalHelper;
    } catch (e) {
        console.log('🚨 [SERVO-EMERGENCY] Erreur lors du nettoyage des variables:', e);
    }
    
    // 4. Fermer tous les modals ouverts
    const openModals = document.querySelectorAll('.modal.show, .modal[style*="display: block"]');
    openModals.forEach(modal => {
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
    });
    
    // 5. Supprimer tous les backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // 6. Réinitialiser le body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // 7. Masquer tous les éléments de chargement
    const loadingElements = document.querySelectorAll('.loading, .spinner, .loader, [class*="loading"], [class*="spinner"]');
    loadingElements.forEach(element => {
        element.style.display = 'none';
        element.style.visibility = 'hidden';
    });
    
    // 8. Forcer l'affichage du contenu principal
    const mainContent = document.querySelector('.main-content, .content, .container, #main-content, #content');
    if (mainContent) {
        mainContent.style.display = 'block';
        mainContent.style.visibility = 'visible';
        mainContent.style.opacity = '1';
    }
    
    console.log('🚨 [SERVO-EMERGENCY] ✅ Correction d\'urgence terminée');
    console.log('🚨 [SERVO-EMERGENCY] La page devrait maintenant être débloquée');
}

// Exécuter automatiquement la correction
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', emergencyServoFix);
} else {
    emergencyServoFix();
}

// Exposer la fonction globalement
window.emergencyServoFix = emergencyServoFix;

console.log('🚨 [SERVO-EMERGENCY] ✅ Script d\'urgence prêt');
console.log('🚨 [SERVO-EMERGENCY] 💡 Utilisez window.emergencyServoFix() pour forcer la correction');








