/**
 * CORRECTION BOUTON HAMBURGER - MENU FUTURISTE
 * Corrige le problème d'affichage du menu hamburger pour le nouveau modal futuriste
 */

console.log('🍔 [HAMBURGER-FIX] Initialisation de la correction du menu hamburger...');

document.addEventListener('DOMContentLoaded', function() {
    // Attendre que Bootstrap soit chargé
    setTimeout(function() {
        console.log('🍔 [HAMBURGER-FIX] Correction du bouton hamburger...');
        
        // Trouver tous les boutons hamburger
        const hamburgerButtons = document.querySelectorAll('.main-menu-btn, .hamburger-btn');
        
        hamburgerButtons.forEach(function(button, index) {
            console.log(`🍔 [HAMBURGER-FIX] Bouton ${index + 1} trouvé:`, button);
            
            // Supprimer les anciens événements
            button.removeAttribute('data-bs-toggle');
            button.removeAttribute('data-bs-target');
            button.removeAttribute('aria-controls');
            
            // Ajouter le nouvel événement pour ouvrir le modal futuriste
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🍔 [HAMBURGER-FIX] Clic détecté sur le bouton hamburger');
                
                // Trouver le nouveau modal futuriste
                const futuristicModal = document.getElementById('futuristicMenuModal');
                
                if (futuristicModal) {
                    console.log('🍔 [HAMBURGER-FIX] Modal futuriste trouvé, ouverture...');
                    
                    try {
                        // Utiliser Bootstrap pour ouvrir le modal
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(futuristicModal);
                        modalInstance.show();
                        
                        console.log('✅ [HAMBURGER-FIX] Modal futuriste ouvert avec succès');
                    } catch (error) {
                        console.error('❌ [HAMBURGER-FIX] Erreur lors de l\'ouverture du modal:', error);
                        
                        // Méthode de fallback
                        futuristicModal.classList.add('show');
                        futuristicModal.style.display = 'block';
                        futuristicModal.setAttribute('aria-hidden', 'false');
                        futuristicModal.setAttribute('aria-modal', 'true');
                        futuristicModal.setAttribute('role', 'dialog');
                        document.body.classList.add('modal-open');
                        
                        // Ajouter le backdrop
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        backdrop.id = 'hamburger-backdrop';
                        document.body.appendChild(backdrop);
                        
                        console.log('✅ [HAMBURGER-FIX] Modal futuriste ouvert avec méthode de fallback');
                    }
                } else {
                    console.error('❌ [HAMBURGER-FIX] Modal futuriste non trouvé');
                }
            });
            
            console.log(`✅ [HAMBURGER-FIX] Bouton ${index + 1} configuré pour le modal futuriste`);
        });
        
        // Vérifier que le modal futuriste existe
        const futuristicModal = document.getElementById('futuristicMenuModal');
        if (futuristicModal) {
            console.log('✅ [HAMBURGER-FIX] Modal futuriste détecté:', futuristicModal);
            
            // S'assurer que le modal a les bonnes classes
            if (!futuristicModal.classList.contains('modal')) {
                futuristicModal.classList.add('modal', 'fade');
            }
            
            // Ajouter un gestionnaire pour fermer le modal avec le backdrop de fallback
            futuristicModal.addEventListener('click', function(e) {
                if (e.target === futuristicModal) {
                    const backdrop = document.getElementById('hamburger-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    futuristicModal.classList.remove('show');
                    futuristicModal.style.display = 'none';
                    futuristicModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                }
            });
            
        } else {
            console.error('❌ [HAMBURGER-FIX] Modal futuriste non trouvé dans le DOM');
        }
        
        console.log('🍔 [HAMBURGER-FIX] ✅ Correction terminée pour le modal futuriste');
        
    }, 1000); // Attendre 1 seconde pour que tout soit chargé
});

// Fonction de test pour déboguer
window.testHamburgerMenu = function() {
    console.log('🧪 [HAMBURGER-FIX] Test du menu hamburger futuriste...');
    
    const buttons = document.querySelectorAll('.main-menu-btn, .hamburger-btn');
    const modal = document.getElementById('futuristicMenuModal');
    
    console.log('Boutons trouvés:', buttons.length);
    console.log('Modal futuriste trouvé:', !!modal);
    
    if (modal && buttons.length > 0) {
        console.log('Simulation d\'un clic...');
        buttons[0].click();
    }
};

console.log('🍔 [HAMBURGER-FIX] Script chargé pour le modal futuriste');
console.log('💡 Utilisez window.testHamburgerMenu() pour tester');
