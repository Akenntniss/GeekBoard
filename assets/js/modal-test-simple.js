/**
 * Script de diagnostic avancé pour le modal nouvelles_actions_modal
 * Pour identifier et résoudre le problème d'affichage
 */

// Script de diagnostic chargé

document.addEventListener('DOMContentLoaded', function () {

    // Vérifier que Bootstrap est disponible
    if (typeof bootstrap === 'undefined') {
        return;
    }

    // Vérifier que le modal existe
    const modal = document.getElementById('nouvelles_actions_modal');
    if (!modal) {
        return;
    }
    // Liste debug supprimée, modal.className);
    // Liste debug supprimée, getComputedStyle(modal).display);
    // Liste debug supprimée, getComputedStyle(modal).visibility);

    // Vérifier tous les boutons possibles
    const buttons = [
        document.querySelector('.btn-nouvelle-action'),
        document.querySelector('#btnNouvelle'),
        document.querySelector('[data-bs-target="#nouvelles_actions_modal"]'),
        document.querySelector('button[data-bs-target="#nouvelles_actions_modal"]')
    ].filter(btn => btn !== null);

    console.log(`✅ ${buttons.length} bouton(s) d'ouverture trouvé(s):`, buttons);

    // Ajouter des écouteurs d'événements détaillés
    modal.addEventListener('show.bs.modal', function (e) {
        // Debug: Initialisation supprimée
    });

    modal.addEventListener('shown.bs.modal', function (e) {
        // Debug: Succès supprimé
    });

    modal.addEventListener('hide.bs.modal', function (e) {
        // Debug: Stacking supprimé
    });

    modal.addEventListener('hidden.bs.modal', function (e) {
        // Liste debug supprimée
    });

    // Surveiller les clics sur les boutons
    buttons.forEach((button, index) => {
        button.addEventListener('click', function (e) {
            // Attributs du bouton supprimés
        });
    });

    // Fonction de test manuel
    window.testModalNouvellesActions = function () {
        try {
            // Nettoyer d'abord
            const existingBackdrops = document.querySelectorAll('.modal-backdrop');
            existingBackdrops.forEach(backdrop => backdrop.remove());

            // Créer une nouvelle instance
            const modalInstance = new bootstrap.Modal(modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            });

            // Liste debug supprimée, modalInstance);

            modalInstance.show();

            return modalInstance;
        } catch (error) {
            return null;
        }
    };

    // Diagnostic des autres modals pour comparaison
    const otherModals = document.querySelectorAll('.modal');

    otherModals.forEach((m, index) => {
        if (m.id !== 'nouvelles_actions_modal') {
        }
    });

    // Test d'ouverture automatique après 5 secondes (désactivé par défaut)
    // setTimeout(() => window.testModalNouvellesActions(), 5000);

    console.log('🧪 Diagnostic complet initialisé');
});
