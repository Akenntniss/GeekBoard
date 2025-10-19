console.log('📱🖥️ [MODAL-RESPONSIVE] Script de gestion des modals responsifs chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('📱🖥️ [MODAL-RESPONSIVE] Initialisation...');
    
    // Fonction pour détecter si on est sur mobile/tablette
    function isMobileOrTablet() {
        return window.innerWidth <= 1024; // Seuil pour mobile/tablette
    }
    
    // Fonction pour ouvrir le bon modal selon l'écran
    function openResponsiveModal() {
        if (isMobileOrTablet()) {
            console.log('📱 [MODAL-RESPONSIVE] Ouverture modal circulaire (mobile/tablette)');
            const circularModal = new bootstrap.Modal(document.getElementById('nouvelles_actions_modal'));
            circularModal.show();
        } else {
            console.log('🖥️ [MODAL-RESPONSIVE] Ouverture modal desktop (PC)');
            const desktopModal = new bootstrap.Modal(document.getElementById('nouvelles_actions_modal_desktop'));
            desktopModal.show();
        }
    }
    
    // Remplacer le comportement des boutons "+"
    const navbarBtn = document.getElementById('btnNouvelle');
    const dockBtn = document.querySelector('.btn-nouvelle-action');
    
    if (navbarBtn) {
        // Supprimer l'attribut data-bs-target pour gérer manuellement
        navbarBtn.removeAttribute('data-bs-target');
        navbarBtn.removeAttribute('data-bs-toggle');
        
        navbarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖥️ [MODAL-RESPONSIVE] Clic sur bouton navbar');
            openResponsiveModal();
        });
    }
    
    if (dockBtn) {
        // Le dock reste toujours sur le modal circulaire
        dockBtn.removeAttribute('data-bs-target');
        dockBtn.removeAttribute('data-bs-toggle');
        
        dockBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('📱 [MODAL-RESPONSIVE] Clic sur bouton dock (toujours circulaire)');
            const circularModal = new bootstrap.Modal(document.getElementById('nouvelles_actions_modal'));
            circularModal.show();
        });
    }
    
    // Gérer le redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        // Fermer les modals ouverts si on change de type d'écran
        const circularModal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
        const desktopModal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal_desktop'));
        
        if (circularModal) circularModal.hide();
        if (desktopModal) desktopModal.hide();
    });
    
    console.log('📱🖥️ [MODAL-RESPONSIVE] ✅ Script initialisé');
    console.log('📱🖥️ [MODAL-RESPONSIVE] 💡 Navbar → Modal adaptatif selon écran');
    console.log('📱🖥️ [MODAL-RESPONSIVE] 💡 Dock → Toujours modal circulaire');
});
