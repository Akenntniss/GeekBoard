/**
 * Script de diagnostic avancé pour le modal nouvelles_actions_modal
 * Pour identifier et résoudre le problème d'affichage
 */

console.log('🧪 Script de diagnostic avancé chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 DOM chargé, diagnostic complet du modal');
    
    // Vérifier que Bootstrap est disponible
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap non disponible');
        return;
    }
    console.log('✅ Bootstrap version:', bootstrap.Modal.VERSION || 'inconnue');
    
    // Vérifier que le modal existe
    const modal = document.getElementById('nouvelles_actions_modal');
    if (!modal) {
        console.error('❌ Modal nouvelles_actions_modal non trouvé');
        return;
    }
    console.log('✅ Modal trouvé:', modal);
    console.log('📋 Classes du modal:', modal.className);
    console.log('📋 Style display:', getComputedStyle(modal).display);
    console.log('📋 Style visibility:', getComputedStyle(modal).visibility);
    
    // Vérifier tous les boutons possibles
    const buttons = [
        document.querySelector('.btn-nouvelle-action'),
        document.querySelector('#btnNouvelle'),
        document.querySelector('[data-bs-target="#nouvelles_actions_modal"]'),
        document.querySelector('button[data-bs-target="#nouvelles_actions_modal"]')
    ].filter(btn => btn !== null);
    
    console.log(`✅ ${buttons.length} bouton(s) d'ouverture trouvé(s):`, buttons);
    
    // Ajouter des écouteurs d'événements détaillés
    modal.addEventListener('show.bs.modal', function(e) {
        console.log('🚀 [SHOW] Modal en cours d\'ouverture');
        console.log('📋 Événement show:', e);
        console.log('📋 Classes avant show:', modal.className);
    });
    
    modal.addEventListener('shown.bs.modal', function(e) {
        console.log('✅ [SHOWN] Modal ouvert avec succès');
        console.log('📋 Événement shown:', e);
        console.log('📋 Classes après shown:', modal.className);
        console.log('📋 Style display après shown:', getComputedStyle(modal).display);
    });
    
    modal.addEventListener('hide.bs.modal', function(e) {
        console.log('🔄 [HIDE] Modal en cours de fermeture');
        console.log('📋 Événement hide:', e);
        console.log('📋 Raison de fermeture:', e.target);
    });
    
    modal.addEventListener('hidden.bs.modal', function(e) {
        console.log('❌ [HIDDEN] Modal fermé');
        console.log('📋 Événement hidden:', e);
        console.log('📋 Classes après hidden:', modal.className);
    });
    
    // Surveiller les clics sur les boutons
    buttons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            console.log(`🖱️ Clic sur le bouton ${index + 1}:`, button);
            console.log('📋 Attributs du bouton:', {
                'data-bs-toggle': button.getAttribute('data-bs-toggle'),
                'data-bs-target': button.getAttribute('data-bs-target'),
                'class': button.className,
                'type': button.type
            });
        });
    });
    
    // Fonction de test manuel
    window.testModalNouvellesActions = function() {
        console.log('🧪 Test manuel d\'ouverture du modal...');
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
            
            console.log('📋 Instance créée:', modalInstance);
            
            modalInstance.show();
            console.log('✅ Commande show() exécutée');
            
            return modalInstance;
        } catch (error) {
            console.error('❌ Erreur lors du test manuel:', error);
            return null;
        }
    };
    
    // Diagnostic des autres modals pour comparaison
    const otherModals = document.querySelectorAll('.modal');
    console.log(`📊 Total de ${otherModals.length} modals trouvés dans la page`);
    
    otherModals.forEach((m, index) => {
        if (m.id !== 'nouvelles_actions_modal') {
            console.log(`📋 Modal ${index}: ${m.id} - Classes: ${m.className}`);
        }
    });
    
    // Test d'ouverture automatique après 5 secondes (désactivé par défaut)
    // setTimeout(() => window.testModalNouvellesActions(), 5000);
    
    console.log('🧪 Diagnostic complet initialisé');
    console.log('💡 Utilisez window.testModalNouvellesActions() pour tester manuellement');
});
