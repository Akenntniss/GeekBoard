/**
 * SCRIPT DE DEBUG POUR LE MENU HAMBURGER
 * Permet de diagnostiquer les problèmes d'affichage
 */

console.log('🔍 [HAMBURGER-DEBUG] Script de diagnostic chargé');

window.debugHamburgerMenu = function() {
    console.log('🔍 [HAMBURGER-DEBUG] === DIAGNOSTIC COMPLET ===');
    
    // 1. Vérifier les boutons hamburger
    const hamburgerButtons = document.querySelectorAll('.main-menu-btn, .hamburger-btn, .fas.fa-bars');
    console.log(`🔍 Boutons hamburger trouvés: ${hamburgerButtons.length}`);
    
    hamburgerButtons.forEach((btn, index) => {
        console.log(`🔍 Bouton ${index + 1}:`, {
            element: btn,
            classes: btn.className,
            id: btn.id,
            visible: btn.offsetParent !== null,
            style: window.getComputedStyle(btn)
        });
    });
    
    // 2. Vérifier le modal de navigation
    const navigationModal = document.getElementById('menu_navigation_modal');
    console.log('🔍 Modal de navigation:', {
        found: !!navigationModal,
        element: navigationModal,
        classes: navigationModal?.className,
        display: navigationModal ? window.getComputedStyle(navigationModal).display : 'N/A',
        visibility: navigationModal ? window.getComputedStyle(navigationModal).visibility : 'N/A'
    });
    
    // 3. Vérifier les cartes de navigation
    if (navigationModal) {
        const navCards = navigationModal.querySelectorAll('.modern-nav-card');
        console.log(`🔍 Cartes de navigation trouvées: ${navCards.length}`);
        
        navCards.forEach((card, index) => {
            const icon = card.querySelector('.nav-icon i');
            const title = card.querySelector('.nav-title');
            
            if (index < 5) { // Afficher seulement les 5 premières
                console.log(`🔍 Carte ${index + 1}:`, {
                    visible: card.offsetParent !== null,
                    icon: icon ? icon.className : 'Aucune icône',
                    iconVisible: icon ? icon.offsetParent !== null : false,
                    title: title ? title.textContent : 'Aucun titre',
                    titleVisible: title ? title.offsetParent !== null : false,
                    backgroundColor: window.getComputedStyle(card).backgroundColor,
                    color: window.getComputedStyle(card).color
                });
            }
        });
    }
    
    // 4. Vérifier le mode jour/nuit
    const isDarkMode = document.body.classList.contains('dark-mode');
    console.log('🔍 Mode sombre actif:', isDarkMode);
    
    // 5. Vérifier Bootstrap
    console.log('🔍 Bootstrap disponible:', typeof bootstrap !== 'undefined');
    
    // 6. Tester l'ouverture du modal
    console.log('🔍 Test d\'ouverture du modal...');
    if (navigationModal && typeof bootstrap !== 'undefined') {
        try {
            const modalInstance = bootstrap.Modal.getOrCreateInstance(navigationModal);
            modalInstance.show();
            console.log('✅ Modal ouvert avec Bootstrap');
            
            // Fermer après 3 secondes
            setTimeout(() => {
                modalInstance.hide();
                console.log('✅ Modal fermé automatiquement');
            }, 3000);
        } catch (error) {
            console.error('❌ Erreur Bootstrap:', error);
        }
    }
    
    console.log('🔍 [HAMBURGER-DEBUG] === FIN DU DIAGNOSTIC ===');
};

window.forceShowNavigationModal = function() {
    console.log('🔧 [HAMBURGER-DEBUG] Forçage de l\'affichage du modal...');
    
    const navigationModal = document.getElementById('menu_navigation_modal');
    if (navigationModal) {
        // Méthode forcée
        navigationModal.style.display = 'block';
        navigationModal.style.opacity = '1';
        navigationModal.style.visibility = 'visible';
        navigationModal.classList.add('show');
        
        // Ajouter backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'debug-backdrop';
        document.body.appendChild(backdrop);
        
        console.log('✅ Modal forcé à s\'afficher');
        
        // Fermer en cliquant sur le backdrop
        backdrop.addEventListener('click', function() {
            navigationModal.style.display = 'none';
            navigationModal.classList.remove('show');
            backdrop.remove();
            console.log('✅ Modal fermé via backdrop');
        });
    }
};

window.toggleDebugMode = function() {
    document.body.classList.toggle('debug-navigation');
    console.log('🔧 Mode debug navigation:', document.body.classList.contains('debug-navigation'));
};

// Auto-diagnostic au chargement
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        console.log('🔍 [HAMBURGER-DEBUG] Auto-diagnostic...');
        window.debugHamburgerMenu();
    }, 2000);
});

console.log('🔍 [HAMBURGER-DEBUG] Fonctions disponibles:');
console.log('💡 window.debugHamburgerMenu() - Diagnostic complet');
console.log('💡 window.forceShowNavigationModal() - Forcer l\'affichage');
console.log('💡 window.toggleDebugMode() - Mode debug visuel');
