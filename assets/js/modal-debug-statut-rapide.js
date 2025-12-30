/**
 * Script de débogage spécifique pour les modals dans statut_rapide.php
 * Ce script aide à identifier pourquoi les modals se ferment automatiquement
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [MODAL-DEBUG] Initialisation du débogage des modals');
    
    // Modals à surveiller
    const modalIds = ['nouvelles_actions_modal', 'ajouterCommandeModal'];
    
    modalIds.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            console.log(`🔧 [MODAL-DEBUG] Modal trouvé: ${modalId}`);
            
            // Surveiller tous les événements du modal
            modal.addEventListener('show.bs.modal', function(e) {
                console.log(`🟢 [MODAL-DEBUG] ${modalId} - show.bs.modal`, e);
            
            modal.addEventListener('shown.bs.modal', function(e) {
                console.log(`✅ [MODAL-DEBUG] ${modalId} - shown.bs.modal`, e);
            
            modal.addEventListener('hide.bs.modal', function(e) {
                console.log(`🟡 [MODAL-DEBUG] ${modalId} - hide.bs.modal`, e);
                console.log(`🟡 [MODAL-DEBUG] Raison de fermeture:`, e.reason || 'Non spécifiée');
                
                // Afficher la stack trace pour voir d'où vient la fermeture
                console.trace(`🟡 [MODAL-DEBUG] Stack trace de fermeture pour ${modalId}`);
            
            modal.addEventListener('hidden.bs.modal', function(e) {
                console.log(`🔴 [MODAL-DEBUG] ${modalId} - hidden.bs.modal`, e);
            
            // Surveiller les clics sur le modal
            modal.addEventListener('click', function(e) {
                console.log(`👆 [MODAL-DEBUG] ${modalId} - Clic détecté`, e.target);
                
                // Vérifier si c'est un clic sur le backdrop
                if (e.target === modal) {
                    console.log(`🎯 [MODAL-DEBUG] ${modalId} - Clic sur backdrop détecté`);
                }
                
                // Vérifier si c'est un bouton de fermeture
                if (e.target.hasAttribute('data-bs-dismiss')) {
                    console.log(`❌ [MODAL-DEBUG] ${modalId} - Bouton de fermeture cliqué`, e.target);
                }
            
            // Surveiller les changements de classes CSS
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const classList = modal.className;
                        console.log(`🎨 [MODAL-DEBUG] ${modalId} - Classes changées:`, classList);
                        
                        if (classList.includes('show')) {
                            console.log(`✅ [MODAL-DEBUG] ${modalId} - Classe 'show' ajoutée`);
                        } else if (mutation.oldValue && mutation.oldValue.includes('show')) {
                            console.log(`❌ [MODAL-DEBUG] ${modalId} - Classe 'show' supprimée`);
                        }
                    }
            
            observer.observe(modal, {
                attributes: true
                attributeOldValue: true
                attributeFilter: ['class']
            
        } else {
            console.error(`❌ [MODAL-DEBUG] Modal non trouvé: ${modalId}`);
        }
    
    // Fonction globale pour tester les modals
    window.debugModal = function(modalId) {
        console.log(`🔧 [MODAL-DEBUG] Test manuel du modal: ${modalId}`);
        const modal = document.getElementById(modalId);
        if (modal) {
            const modalInstance = new bootstrap.Modal(modal, {
                backdrop: true
                keyboard: true
                focus: true
            modalInstance.show();
            
            setTimeout(() => {
                console.log(`🔧 [MODAL-DEBUG] État du modal après 1 seconde:`, {
                    isShown: modal.classList.contains('show')
                    display: getComputedStyle(modal).display
                    visibility: getComputedStyle(modal).visibility
                    opacity: getComputedStyle(modal).opacity
                    zIndex: getComputedStyle(modal).zIndex
            }, 1000);
        }
    };
    
    console.log('🔧 [MODAL-DEBUG] Débogage initialisé - Utilisez debugModal("modalId") pour tester');
