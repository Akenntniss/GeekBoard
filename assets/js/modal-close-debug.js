/**
 * SCRIPT DE DÉBOGAGE POUR LE BOUTON DE FERMETURE
 * Diagnostic et correction automatique du bouton de fermeture
 */

(function() {
    'use strict';
    
    console.log('🔧 Script de débogage du bouton de fermeture chargé');
    
    function debugCloseButton() {
        const modal = document.getElementById('nouvelles_actions_modal');
        if (!modal) {
            console.error('🔧 Modal nouvelles_actions_modal non trouvé');
            return;
        }
        
        const closeButton = modal.querySelector('.btn-close');
        if (!closeButton) {
            console.error('🔧 Bouton de fermeture non trouvé dans le modal');
            return;
        }
        
        console.log('🔧 Diagnostic du bouton de fermeture:');
        console.log('- Élément trouvé:', closeButton);
        console.log('- Classes:', Array.from(closeButton.classList));
        console.log('- Style display:', getComputedStyle(closeButton).display);
        console.log('- Style visibility:', getComputedStyle(closeButton).visibility);
        console.log('- Style opacity:', getComputedStyle(closeButton).opacity);
        console.log('- Style background:', getComputedStyle(closeButton).background);
        console.log('- Style width:', getComputedStyle(closeButton).width);
        console.log('- Style height:', getComputedStyle(closeButton).height);
        console.log('- Attribut data-bs-dismiss:', closeButton.getAttribute('data-bs-dismiss'));
        
        // Vérifier la position
        const rect = closeButton.getBoundingClientRect();
        console.log('- Position:', {
            top: rect.top,
            left: rect.left,
            width: rect.width,
            height: rect.height
        });
        
        // Forcer la correction
        console.log('🔧 Application des corrections...');
        
        closeButton.style.cssText = `
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            width: 48px !important;
            height: 48px !important;
            background: rgba(255, 255, 255, 0.2) !important;
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 12px !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            z-index: 10 !important;
        `;
        
        // Ajouter l'icône si elle n'existe pas
        if (!closeButton.querySelector('::before') && closeButton.innerHTML.trim() === '') {
            closeButton.innerHTML = '<span style="color: white; font-size: 1.2rem; font-weight: bold;">✕</span>';
        }
        
        console.log('🔧 Corrections appliquées');
        
        // Test de clic
        closeButton.addEventListener('click', function(e) {
            console.log('🔧 Clic détecté sur le bouton de fermeture');
            e.preventDefault();
            e.stopPropagation();
            
            // Fermer le modal
            if (window.bootstrap && window.bootstrap.Modal) {
                const modalInstance = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
                modalInstance.hide();
                console.log('🔧 Modal fermé via Bootstrap');
            } else {
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
                console.log('🔧 Modal fermé manuellement');
            }
        });
    }
    
    // Exécuter le diagnostic quand le modal s'ouvre
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('nouvelles_actions_modal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', function() {
                setTimeout(debugCloseButton, 100);
            });
            
            // Diagnostic immédiat si le modal est déjà ouvert
            if (modal.classList.contains('show')) {
                setTimeout(debugCloseButton, 100);
            }
        }
    });
    
    // Fonction globale pour diagnostic manuel
    window.debugModalCloseButton = debugCloseButton;
    
})();



