/**
 * CORRECTION SIMPLE ET EFFICACE DU MODAL NOUVELLES ACTIONS
 * Supprime les classes problématiques et force l'affichage
 */

console.log('🔧 [MODAL-SIMPLE-FIX] Script de correction simple chargé');

function fixModalNouvelles() {
    console.log('🔧 [MODAL-SIMPLE-FIX] Correction du modal nouvelles_actions_modal...');
    
    const modal = document.getElementById('nouvelles_actions_modal');
    if (!modal) {
        console.error('❌ Modal nouvelles_actions_modal non trouvé !');
        return false;
    }
    
    const modalBody = modal.querySelector('.modal-body');
    if (!modalBody) {
        console.error('❌ Modal-body non trouvé !');
        return false;
    }
    
    console.log('🔧 [MODAL-SIMPLE-FIX] Classes avant correction:', modalBody.className);
    
    // Supprimer les classes problématiques
    modalBody.classList.remove('p-0', 'position-relative', 'overflow-hidden');
    
    // Ajouter des classes correctes
    modalBody.classList.add('p-3');
    
    // Forcer les styles essentiels
    modalBody.style.overflow = 'visible';
    modalBody.style.height = 'auto';
    modalBody.style.maxHeight = 'none';
    
    console.log('✅ [MODAL-SIMPLE-FIX] Classes après correction:', modalBody.className);
    console.log('✅ [MODAL-SIMPLE-FIX] Overflow forcé à:', modalBody.style.overflow);
    
    // Vérifier les cartes d'action
    const actionCards = modal.querySelectorAll('.modern-action-card');
    console.log(`🔧 [MODAL-SIMPLE-FIX] ${actionCards.length} cartes d'action trouvées`);
    
    actionCards.forEach((card, index) => {
        const title = card.querySelector('.action-title');
        console.log(`  - Carte ${index + 1}: ${title ? title.textContent : 'Sans titre'}`);
    
    return true;
}

// Fonction pour intercepter l'ouverture du modal
function interceptModalShow() {
    const modal = document.getElementById('nouvelles_actions_modal');
    if (!modal) return;
    
    // Écouter l'événement d'ouverture du modal
    modal.addEventListener('show.bs.modal', function() {
        console.log('🔧 [MODAL-SIMPLE-FIX] Modal en cours d\'ouverture - application de la correction...');
        setTimeout(fixModalNouvelles, 50); // Petit délai pour laisser Bootstrap s'initialiser
    
    // Écouter l'événement après ouverture
    modal.addEventListener('shown.bs.modal', function() {
        console.log('🔧 [MODAL-SIMPLE-FIX] Modal ouvert - vérification finale...');
        setTimeout(fixModalNouvelles, 100);
    
    console.log('✅ [MODAL-SIMPLE-FIX] Intercepteurs installés sur le modal');
}

// Initialisation
function initSimpleFix() {
    console.log('🔧 [MODAL-SIMPLE-FIX] Initialisation...');
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            interceptModalShow();
            // Correction immédiate aussi
            setTimeout(fixModalNouvelles, 1000);
    } else {
        interceptModalShow();
        // Correction immédiate
        setTimeout(fixModalNouvelles, 100);
    }
}

// Rendre les fonctions accessibles globalement
window.fixModalNouvelles = fixModalNouvelles;

// Initialiser immédiatement
initSimpleFix();

console.log('✅ [MODAL-SIMPLE-FIX] Script initialisé - utilisez fixModalNouvelles() pour corriger manuellement');
