/**
 * Script de diagnostic pour le scanner universel
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 [DIAGNOSTIC] Démarrage du diagnostic scanner...');
    
    // Vérifier l'existence des éléments
    setTimeout(() => {
        console.log('🔍 [DIAGNOSTIC] Vérification des éléments...');
        
        // Vérifier le modal nouvelles actions
        const nouvellesActionsModal = document.getElementById('nouvelles_actions_modal');
        console.log('🔍 [DIAGNOSTIC] Modal nouvelles actions:', nouvellesActionsModal ? '✅ Trouvé' : '❌ Non trouvé');
        
        // Vérifier le bouton scanner
        const scannerBtn = document.getElementById('openUniversalScanner');
        console.log('🔍 [DIAGNOSTIC] Bouton scanner:', scannerBtn ? '✅ Trouvé' : '❌ Non trouvé');
        
        if (scannerBtn) {
            console.log('🔍 [DIAGNOSTIC] Texte du bouton:', scannerBtn.textContent.trim());
            console.log('🔍 [DIAGNOSTIC] Classes du bouton:', scannerBtn.className);
            
            // Vérifier les événements attachés
            const events = getEventListeners ? getEventListeners(scannerBtn) : 'Non disponible en production';
            console.log('🔍 [DIAGNOSTIC] Événements attachés:', events);
            
            // Ajouter un événement de test
            scannerBtn.addEventListener('click', function(e) {
                console.log('🔍 [DIAGNOSTIC] Clic détecté sur le bouton scanner!');
                console.log('🔍 [DIAGNOSTIC] Event:', e);
            });
        }
        
        // Vérifier le modal scanner
        const scannerModal = document.getElementById('universal_scanner_modal');
        console.log('🔍 [DIAGNOSTIC] Modal scanner:', scannerModal ? '✅ Trouvé' : '❌ Non trouvé');
        
        // Vérifier les fonctions
        console.log('🔍 [DIAGNOSTIC] Fonction initUniversalScanner:', typeof initUniversalScanner);
        console.log('🔍 [DIAGNOSTIC] Fonction openUniversalScanner:', typeof openUniversalScanner);
        
        // Vérifier Bootstrap
        console.log('🔍 [DIAGNOSTIC] Bootstrap Modal:', typeof bootstrap?.Modal);
        
        // Vérifier les bibliothèques de scan
        console.log('🔍 [DIAGNOSTIC] jsQR:', typeof jsQR);
        console.log('🔍 [DIAGNOSTIC] Quagga:', typeof Quagga);
        
        console.log('🔍 [DIAGNOSTIC] Diagnostic terminé');
    }, 2000);
});

// Fonction pour tester manuellement l'ouverture du scanner
window.testOpenScanner = function() {
    console.log('🧪 [TEST] Test manuel d\'ouverture du scanner...');
    
    try {
        if (typeof openUniversalScanner === 'function') {
            openUniversalScanner();
            console.log('🧪 [TEST] ✅ Fonction appelée avec succès');
        } else {
            console.log('🧪 [TEST] ❌ Fonction openUniversalScanner non disponible');
        }
    } catch (error) {
        console.error('🧪 [TEST] ❌ Erreur lors de l\'ouverture:', error);
    }
};

// Fonction pour tester l'ouverture du modal directement
window.testOpenModal = function() {
    console.log('🧪 [TEST] Test manuel d\'ouverture du modal...');
    
    try {
        const modal = new bootstrap.Modal(document.getElementById('universal_scanner_modal'));
        modal.show();
        console.log('🧪 [TEST] ✅ Modal ouvert avec succès');
    } catch (error) {
        console.error('🧪 [TEST] ❌ Erreur lors de l\'ouverture du modal:', error);
    }
};

console.log('🔍 [DIAGNOSTIC] Script de diagnostic chargé');
console.log('🔍 [DIAGNOSTIC] Utilisez testOpenScanner() ou testOpenModal() dans la console pour tester');
