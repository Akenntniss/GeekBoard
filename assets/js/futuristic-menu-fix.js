/**
 * Script de correction pour forcer l'ouverture du modal futuriste
 */

console.log('🚀 [FUTURISTIC-MENU-FIX] Initialisation du correctif pour le menu futuriste');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [FUTURISTIC-MENU-FIX] DOM chargé, installation des correctifs...');
    
    // Fonction pour ouvrir le modal futuriste
    function openFuturisticModal() {
        console.log('🚀 [FUTURISTIC-MENU-FIX] Tentative d\'ouverture du modal futuriste');
        
        const modal = document.getElementById('futuristicMenuModal');
        if (!modal) {
            console.error('🚀 [FUTURISTIC-MENU-FIX] ❌ Modal futuristicMenuModal non trouvé');
            return false;
        }
        
        try {
            // Utiliser Bootstrap pour ouvrir le modal
            const bsModal = new bootstrap.Modal(modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            bsModal.show();
            console.log('🚀 [FUTURISTIC-MENU-FIX] ✅ Modal ouvert avec Bootstrap');
            return true;
        } catch (error) {
            console.error('🚀 [FUTURISTIC-MENU-FIX] ❌ Erreur Bootstrap:', error);
            
            // Fallback manuel
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            
            // Ajouter backdrop
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.style.zIndex = '99998';
                document.body.appendChild(backdrop);
            }
            
            // Bloquer le scroll
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            
            console.log('🚀 [FUTURISTIC-MENU-FIX] ✅ Modal ouvert manuellement');
            return true;
        }
    }
    
    // Intercepter tous les boutons hamburger
    const hamburgerButtons = [
        ...document.querySelectorAll('.main-menu-btn'),
        ...document.querySelectorAll('[data-bs-target="#futuristicMenuModal"]'),
        ...document.querySelectorAll('[data-bs-target="#menu_navigation_modal"]')
    ];
    
    console.log(`🚀 [FUTURISTIC-MENU-FIX] ${hamburgerButtons.length} boutons hamburger détectés`);
    
    hamburgerButtons.forEach((button, index) => {
        // Supprimer les anciens listeners
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Ajouter le nouveau listener
        newButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log(`🚀 [FUTURISTIC-MENU-FIX] Clic sur bouton ${index + 1}`);
            openFuturisticModal();
        });
        
        console.log(`🚀 [FUTURISTIC-MENU-FIX] ✅ Bouton ${index + 1} configuré`);
    });
    
    // Fonction globale pour tester
    window.openFuturisticModal = openFuturisticModal;
    
    console.log('🚀 [FUTURISTIC-MENU-FIX] ✅ Correctif installé');
    console.log('🚀 [FUTURISTIC-MENU-FIX] 💡 Utilisez window.openFuturisticModal() pour tester');
});
