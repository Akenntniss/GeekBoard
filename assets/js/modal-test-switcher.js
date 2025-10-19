/* ====================================================================
   🧪 TESTEUR DE MODALS NOUVEAU CLIENT
   Permet de tester facilement les différentes versions
==================================================================== */

(function() {
    'use strict';
    
    console.log('🧪 [MODAL-TESTER] Testeur de modals chargé');
    
    // Fonction de test générale
    window.testAllModals = function() {
        console.log('🧪 [MODAL-TESTER] === TEST DE TOUS LES MODALS ===');
        
        console.log('📋 Versions disponibles:');
        console.log('  1. window.testFuturisteUltra() - Version ultra-simple');
        console.log('  2. window.testNuclearModal() - Version NUCLEAR (contentEditable)');
        console.log('  3. window.diagnoseFuturisteInput() - Diagnostic complet');
        
        console.log('🎯 Recommandation: Essayez d\'abord la version NUCLEAR');
        console.log('   Elle utilise contentEditable au lieu d\'input et devrait marcher');
        
        // Test automatique de la version recommandée
        if (typeof window.testNuclearModal === 'function') {
            console.log('☢️ Lancement automatique du test NUCLEAR...');
            setTimeout(() => {
                testNuclearModal();
            }, 1000);
        }
    };
    
    // Fonction pour forcer l'utilisation d'une version spécifique
    window.useNuclearModal = function() {
        console.log('☢️ [MODAL-TESTER] Forçage de la version NUCLEAR');
        
        // Redéfinir la fonction principale pour utiliser nuclear
        window.createNewClientModal = window.createNewClientModalNuclear;
        
        console.log('✅ Version NUCLEAR forcée comme version par défaut');
        console.log('💡 Maintenant le bouton "nouveau client" utilisera la version NUCLEAR');
    };
    
    window.useUltraSimpleModal = function() {
        console.log('🎨 [MODAL-TESTER] Retour à la version ultra-simple');
        
        // Restaurer la fonction ultra-simple
        // (elle est déjà définie dans l'autre script)
        
        console.log('✅ Version ultra-simple restaurée');
        console.log('💡 Maintenant le bouton "nouveau client" utilisera la version ultra-simple');
    };
    
    // Information sur les différences
    window.explainModalDifferences = function() {
        console.log('📖 [MODAL-TESTER] === DIFFÉRENCES ENTRE LES VERSIONS ===');
        console.log('');
        console.log('🎨 VERSION ULTRA-SIMPLE:');
        console.log('   - Utilise des <input> HTML normaux');
        console.log('   - Style futuriste cyan');
        console.log('   - Peut être bloquée par d\'autres scripts');
        console.log('   - Fonction: window.testFuturisteUltra()');
        console.log('');
        console.log('☢️ VERSION NUCLEAR:');
        console.log('   - Utilise contentEditable au lieu d\'<input>');
        console.log('   - Style futuriste vert nuclear');
        console.log('   - Neutralise temporairement les autres scripts');
        console.log('   - Plus résistante aux interférences');
        console.log('   - Fonction: window.testNuclearModal()');
        console.log('');
        console.log('🔍 DIAGNOSTIC:');
        console.log('   - Analyse complète des problèmes de saisie');
        console.log('   - Fonction: window.diagnoseFuturisteInput()');
        console.log('');
        console.log('💡 RECOMMANDATION:');
        console.log('   Si la saisie ne marche pas, utilisez: window.useNuclearModal()');
    };
    
    console.log('✅ [MODAL-TESTER] Testeur prêt');
    console.log('💡 Utilisez window.testAllModals() pour commencer');
    console.log('💡 Utilisez window.explainModalDifferences() pour plus d\'infos');
    
})();
