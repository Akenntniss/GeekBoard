/**
 * Script de test pour les modals problématiques
 * À utiliser temporairement pour diagnostiquer les problèmes
 */

(function() {
    'use strict';
    
    console.log('🔍 Script de test des modals chargé');
    
    // Fonction de diagnostic
    function diagnoseModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`❌ Modal ${modalId} non trouvé`);
            return;
        }
        
        console.log(`🔍 Diagnostic du modal ${modalId}:`);
        console.log('  - Élément existe:', !!modal);
        console.log('  - Display:', window.getComputedStyle(modal).display);
        console.log('  - Visibility:', window.getComputedStyle(modal).visibility);
        console.log('  - Opacity:', window.getComputedStyle(modal).opacity);
        console.log('  - Z-index:', window.getComputedStyle(modal).zIndex);
        console.log('  - Classes:', modal.className);
        console.log('  - Aria-hidden:', modal.getAttribute('aria-hidden'));
        console.log('  - Aria-modal:', modal.getAttribute('aria-modal'));
        
        // Vérifier le bouton qui déclenche ce modal
        const button = document.querySelector(`[data-bs-target="#${modalId}"]`);
        if (button) {
            console.log('  - Bouton trouvé:', !!button);
            console.log('  - Bouton classes:', button.className);
            console.log('  - Data-bs-target:', button.getAttribute('data-bs-target'));
        } else {
            console.log('  - ❌ Bouton non trouvé');
        }
    }
    
    // Fonction de test d'ouverture forcée
    function testModalOpening(modalId) {
        console.log(`🧪 Test d'ouverture forcée du modal ${modalId}`);
        
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`❌ Modal ${modalId} non trouvé pour le test`);
            return;
        }
        
        // Forcer l'ouverture
        modal.style.display = 'block';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.zIndex = '1055';
        modal.classList.add('show');
        modal.setAttribute('aria-modal', 'true');
        modal.removeAttribute('aria-hidden');
        
        // Ajouter backdrop si nécessaire
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.zIndex = '1050';
            document.body.appendChild(backdrop);
        }
        
        document.body.classList.add('modal-open');
        
        console.log(`✅ Modal ${modalId} ouvert en mode test`);
        
        // Fermer après 3 secondes
        setTimeout(() => {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');
            
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            console.log(`🔒 Modal ${modalId} fermé après test`);
        }, 3000);
    }
    
    // Attendre que le DOM soit chargé
    document.addEventListener('DOMContentLoaded', function() {
        
        // Ajouter des commandes de test dans la console
        window.diagnoseUpdateStatusModal = () => diagnoseModal('updateStatusModal');
        window.diagnoseRelanceClientModal = () => diagnoseModal('relanceClientModal');
        window.testUpdateStatusModal = () => testModalOpening('updateStatusModal');
        window.testRelanceClientModal = () => testModalOpening('relanceClientModal');
        
        console.log('🔧 Commandes de test disponibles:');
        console.log('  - diagnoseUpdateStatusModal()');
        console.log('  - diagnoseRelanceClientModal()');
        console.log('  - testUpdateStatusModal()');
        console.log('  - testRelanceClientModal()');
        
        // Diagnostic automatique après chargement
        setTimeout(() => {
            console.log('🔍 Diagnostic automatique des modals:');
            diagnoseModal('updateStatusModal');
            diagnoseModal('relanceClientModal');
        }, 2000);
    
})();
