// Script de diagnostic pour l'erreur 500 lors de l'envoi de devis
console.log('🔧 [DEBUG-500] Script de diagnostic erreur 500 chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [DEBUG-500] === DIAGNOSTIC ERREUR 500 ===');
    
    // Test de la session et authentification
    window.testSession500 = async function() {
        console.log('🔍 [DEBUG-500] Test de session et authentification...');
        
        try {
            const response = await fetch('ajax/test_session_debug.php?shop_id=63', {
                method: 'GET'
            });
            
            console.log('📊 [DEBUG-500] Status test session:', response.status);
            
            if (response.ok) {
                const result = await response.json();
                console.log('📋 [DEBUG-500] Données de session:', result);
                
                if (result.would_return_401) {
                    console.error('❌ [DEBUG-500] Problème d\'authentification détecté !');
                    console.error('❌ [DEBUG-500] shop_id manquant ou invalide');
                } else {
                    console.log('✅ [DEBUG-500] Authentification OK');
                }
                
                return result;
            } else {
                console.error('❌ [DEBUG-500] Erreur lors du test de session:', response.status);
            }
            
        } catch (error) {
            console.error('❌ [DEBUG-500] Erreur réseau test session:', error);
        }
    };
    
    // Test minimal de l'endpoint creer_devis
    window.testEndpoint500 = async function() {
        console.log('🌐 [DEBUG-500] Test minimal de l\'endpoint creer_devis...');
        
        // Données minimales pour le test
        const testData = {
            action: 'test',
            reparation_id: 1000,
            titre: 'Test Debug 500',
            description: 'Test pour diagnostiquer erreur 500'
        };
        
        try {
            const response = await fetch('ajax/creer_devis.php?shop_id=63', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(testData)
            });
            
            console.log('📊 [DEBUG-500] Status endpoint:', response.status);
            console.log('📊 [DEBUG-500] Headers:', [...response.headers.entries()]);
            
            // Essayer de lire la réponse même en cas d'erreur
            const responseText = await response.text();
            console.log('📋 [DEBUG-500] Réponse brute:', responseText);
            
            if (response.status === 500) {
                console.error('❌ [DEBUG-500] ERREUR 500 CONFIRMÉE');
                console.error('❌ [DEBUG-500] Erreur interne du serveur PHP');
                
                // Analyser si c'est une erreur PHP
                if (responseText.includes('Fatal error') || responseText.includes('Parse error')) {
                    console.error('💀 [DEBUG-500] ERREUR PHP FATALE détectée');
                } else if (responseText.includes('Warning') || responseText.includes('Notice')) {
                    console.error('⚠️ [DEBUG-500] AVERTISSEMENT PHP détecté');
                } else {
                    console.error('🤔 [DEBUG-500] Erreur 500 sans message PHP visible');
                }
            }
            
            // Essayer de parser en JSON si possible
            try {
                const jsonResult = JSON.parse(responseText);
                console.log('📋 [DEBUG-500] Réponse JSON:', jsonResult);
            } catch (e) {
                console.log('📋 [DEBUG-500] Réponse non-JSON (normal en cas d\'erreur PHP)');
            }
            
        } catch (error) {
            console.error('❌ [DEBUG-500] Erreur réseau endpoint:', error);
        }
    };
    
    // Test complet de diagnostic
    window.diagnosticComplet500 = async function() {
        console.log('🔬 [DEBUG-500] === DIAGNOSTIC COMPLET ERREUR 500 ===');
        
        console.log('🔍 [DEBUG-500] 1. Test de session...');
        const sessionResult = await window.testSession500();
        
        console.log('🔍 [DEBUG-500] 2. Test de l\'endpoint...');
        await window.testEndpoint500();
        
        console.log('🔍 [DEBUG-500] 3. Recommandations...');
        
        if (sessionResult && sessionResult.would_return_401) {
            console.log('💡 [DEBUG-500] SOLUTION: Problème d\'authentification');
            console.log('💡 [DEBUG-500] - Rechargez la page pour renouveler la session');
            console.log('💡 [DEBUG-500] - Vérifiez que vous êtes bien connecté');
        } else {
            console.log('💡 [DEBUG-500] SOLUTIONS POSSIBLES:');
            console.log('💡 [DEBUG-500] - Erreur dans le code PHP du serveur');
            console.log('💡 [DEBUG-500] - Problème de base de données');
            console.log('💡 [DEBUG-500] - Fichier PHP corrompu');
            console.log('💡 [DEBUG-500] - Permissions de fichier incorrectes');
        }
        
        console.log('✅ [DEBUG-500] Diagnostic terminé');
    };
    
    // Auto-diagnostic au chargement
    setTimeout(() => {
        console.log('🔄 [AUTO-DIAG] Lancement du diagnostic automatique...');
        window.diagnosticComplet500();
    }, 3000);
    
    console.log('✅ [DEBUG-500] Fonctions de diagnostic disponibles:');
    console.log('  - testSession500() : Test de session');
    console.log('  - testEndpoint500() : Test endpoint creer_devis');
    console.log('  - diagnosticComplet500() : Diagnostic complet');
});



