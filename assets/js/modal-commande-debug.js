/**
 * Script de debug spécifique pour le modal ajouterCommandeModal
 * Pour diagnostiquer pourquoi il ne s'ouvre pas après la transition
 */

document.addEventListener('DOMContentLoaded', function () {

    // Vérifier que le modal existe
    const modal = document.getElementById('ajouterCommandeModal');
    if (!modal) {
        return;
    }

    console.log('✅ Modal ajouterCommandeModal trouvé:', modal);

    // Vérifier la structure HTML
    const dialog = modal.querySelector('.modal-dialog');
    const content = modal.querySelector('.modal-content');
    const header = modal.querySelector('.modal-header');
    const body = modal.querySelector('.modal-body');

    console.log('🔍 Structure du modal:', {
        dialog: !!dialog,
        content: !!content,
        header: !!header,
        body: !!body
    });

    // Écouter les événements du modal
    modal.addEventListener('show.bs.modal', function (e) {
        console.log('📣 Événement: show.bs.modal');
    });

    modal.addEventListener('shown.bs.modal', function (e) {
        console.log('📣 Événement: shown.bs.modal');
    });

    modal.addEventListener('hide.bs.modal', function (e) {
        console.log('📣 Événement: hide.bs.modal');
    });

    modal.addEventListener('hidden.bs.modal', function (e) {
        console.log('📣 Événement: hidden.bs.modal');
    });

    // Fonction de test global
    window.testModalAjouterCommande = function () {
        console.log('🧪 Test manuel du modal');
        try {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        } catch (error) {
            console.error('❌ Erreur lors de l\'ouverture:', error);
        }
    };

    console.log('💡 Utilisez window.testModalAjouterCommande() pour tester manuellement');
});
