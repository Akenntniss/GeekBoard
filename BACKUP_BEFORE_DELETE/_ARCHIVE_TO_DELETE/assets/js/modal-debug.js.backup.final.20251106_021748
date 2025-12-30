/**
 * Script de débogage pour les modals Bootstrap
 */

console.log('🔧 [MODAL-DEBUG] Initialisation du débogage modal');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [MODAL-DEBUG] DOM chargé, début du diagnostic...');
    
    // Vérifier Bootstrap
    if (typeof bootstrap === 'undefined') {
        console.error('❌ [MODAL-DEBUG] Bootstrap non disponible');
        return;
    } else {
        console.log('✅ [MODAL-DEBUG] Bootstrap disponible, version:', bootstrap.Modal ? 'Modal OK' : 'Modal manquant');
    }
    
    // Vérifier le modal
    const modalElement = document.getElementById('futuristicMenuModal');
    if (!modalElement) {
        console.error('❌ [MODAL-DEBUG] Modal futuristicMenuModal non trouvé');
        return;
    } else {
        console.log('✅ [MODAL-DEBUG] Modal futuristicMenuModal trouvé');
    }
    
    // Vérifier les boutons déclencheurs
    const triggers = document.querySelectorAll('[data-bs-target="#futuristicMenuModal"]');
    console.log('🔧 [MODAL-DEBUG] Boutons déclencheurs trouvés:', triggers.length);
        
        triggers.forEach((trigger, index) => {
        console.log(`🔧 [MODAL-DEBUG] Trigger ${index + 1}:`, trigger.tagName, trigger.className);
    });
    
    // Intercepter les clics sur les boutons
    triggers.forEach((trigger, index) => {
        trigger.addEventListener('click', function(e) {
            console.log(`🔧 [MODAL-DEBUG] Clic sur trigger ${index + 1}`);
            
            // Empêcher le comportement par défaut temporairement
            e.preventDefault();
            e.stopPropagation();
            
            // Essayer d'ouvrir le modal manuellement
            try {
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                modal.show();
                console.log('✅ [MODAL-DEBUG] Modal ouvert avec succès');
            } catch (error) {
                console.error('❌ [MODAL-DEBUG] Erreur lors de l\'ouverture:', error);
                
                // Fallback manuel
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
                modalElement.setAttribute('aria-hidden', 'false');
                
                // Créer backdrop manuellement
                let backdrop = document.querySelector('.modal-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.style.zIndex = '1040';
                    document.body.appendChild(backdrop);
                }
                
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
                
                console.log('✅ [MODAL-DEBUG] Modal ouvert en mode fallback');
                
                // Ajouter listener pour fermer
                const closeBtn = modalElement.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        modalElement.style.display = 'none';
                        modalElement.classList.remove('show');
                        modalElement.setAttribute('aria-hidden', 'true');
                        
                        if (backdrop) {
                            backdrop.remove();
                        }
                        
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        
                        console.log('✅ [MODAL-DEBUG] Modal fermé en mode fallback');
                    });
                }
                
                // Fermer en cliquant sur le backdrop
                if (backdrop) {
                    backdrop.addEventListener('click', function() {
                        closeBtn.click();
                    });
                }
            }
        });
    });
    
    console.log('✅ [MODAL-DEBUG] Débogage configuré');
});