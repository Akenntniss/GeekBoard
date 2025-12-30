/**
 * Script de debug spécifique pour le modal ajouterCommandeModal
 * Pour diagnostiquer pourquoi il ne s'ouvre pas après la transition
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 Debug modal ajouterCommandeModal initialisé');
    
    // Vérifier que le modal existe
    const modal = document.getElementById('ajouterCommandeModal');
    if (!modal) {
        console.error('❌ Modal ajouterCommandeModal non trouvé');
        return;
    }
    
    console.log('✅ Modal ajouterCommandeModal trouvé:', modal);
    console.log('📋 Classes du modal:', modal.className);
    console.log('📋 Style display:', modal.style.display);
    
    // Vérifier la structure HTML
    const dialog = modal.querySelector('.modal-dialog');
    const content = modal.querySelector('.modal-content');
    const header = modal.querySelector('.modal-header');
    const body = modal.querySelector('.modal-body');
    
    console.log('🔍 Structure du modal:');
    console.log('  - dialog:', !!dialog);
    console.log('  - content:', !!content);
    console.log('  - header:', !!header);
    console.log('  - body:', !!body);
    
    // Écouter les événements du modal
    modal.addEventListener('show.bs.modal', function(e) {
        console.log('🚀 [ajouterCommandeModal] Événement show déclenché');
        console.log('  - relatedTarget:', e.relatedTarget);
        console.log('  - target:', e.target);
    });
    
    modal.addEventListener('shown.bs.modal', function(e) {
        console.log('✅ [ajouterCommandeModal] Modal ouvert avec succès');
        console.log('  - Classes:', modal.className);
        console.log('  - Display:', modal.style.display);
    });
    
    modal.addEventListener('hide.bs.modal', function(e) {
        console.log('🔄 [ajouterCommandeModal] Événement hide déclenché');
    });
    
    modal.addEventListener('hidden.bs.modal', function(e) {
        console.log('❌ [ajouterCommandeModal] Modal fermé');
    });
    
    // Fonction de test global
    window.testModalAjouterCommande = function() {
        console.log('🧪 Test manuel du modal ajouterCommandeModal');
        
        try {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            console.log('✅ Ouverture manuelle réussie');
        } catch (error) {
            console.error('❌ Erreur lors de l\'ouverture manuelle:', error);
        }
    };
    
    console.log('💡 Utilisez window.testModalAjouterCommande() pour tester manuellement');
});
