/**
 * Solution pour gérer l'ouverture propre des modals
 * Ferme automatiquement le RepairModal quand d'autres modals s'ouvrent
 */

document.addEventListener('DOMContentLoaded', function () {

    // Attendre que Bootstrap soit initialisé
    setTimeout(() => {
        initModalStacking();
    }, 1000);

    function initModalStacking() {
        const repairModal = document.getElementById('repairDetailsModal');
        const commandeModal = document.getElementById('ajouterCommandeModal');

        if (!repairModal) {
            return;
        }

        // Variables pour tracking
        let repairModalOpen = false;

        // Écouter l'ouverture du modal de réparation
        repairModal.addEventListener('shown.bs.modal', function () {
            repairModalOpen = true;
        });

        repairModal.addEventListener('hidden.bs.modal', function () {
            repairModalOpen = false;
        });

        // Fonction pour fermer proprement le modal de réparation
        function closeRepairModalGracefully() {
            try {
                // Utiliser l'instance Bootstrap existante
                const repairModalInstance = bootstrap.Modal.getInstance(repairModal);
                if (repairModalInstance) {
                    repairModalInstance.hide();
                } else {
                    const bsModal = new bootstrap.Modal(repairModal);
                    bsModal.hide();
                }
                repairModalOpen = false;
            } catch (error) {
                console.error("Erreur lors de la fermeture du modal repar:", error);
                repairModal.classList.remove('show');
                repairModal.style.display = 'none';
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
            }
        }

        // Liste des IDs de modals qui doivent fermer le repair modal
        const conflictModals = [
            'ajouterCommandeModal',
            'photoModal',
            'notesModal',
            'priceModal',
            'chooseStatusModal',
            'smsModal',
            'gbQRRepairModal',
            'gbAdjustModal'
        ];

        // Observer pour détecter l'apparition de ces modals dans le DOM
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1 && conflictModals.includes(node.id)) {
                        setupConflictModalListener(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });

        // Configurer les listeners pour les modals déjà présents
        conflictModals.forEach(id => {
            const el = document.getElementById(id);
            if (el) setupConflictModalListener(el);
        });

        function setupConflictModalListener(modalEl) {
            modalEl.addEventListener('show.bs.modal', function () {
                if (repairModalOpen) {
                    closeRepairModalGracefully();
                }
            });
        }
    }
});
