// Test simple pour identifier l'erreur 500
console.log('🔬 [TEST-500-SIMPLE] Script de test simple chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔬 [TEST-500-SIMPLE] === TEST SIMPLE ERREUR 500 ===');
    
    // Test 1: Endpoint de test très simple
    window.testSimpleEndpoint = async function() {
        console.log('1️⃣ [TEST-500] Test endpoint simple...');
        
        try {
            const response = await fetch('ajax/simple_test.php');
            console.log('📊 [TEST-500] Status simple test:', response.status);
            
            if (response.ok) {
                const result = await response.json();
                console.log('✅ [TEST-500] Endpoint simple fonctionne:', result);
                return true;
            } else {
                console.error('❌ [TEST-500] Endpoint simple échoue:', response.status);
                return false;
            }
        } catch (error) {
            console.error('💥 [TEST-500] Erreur endpoint simple:', error);
            return false;
        }
    };
    
    // Test 2: Endpoint avec session
    window.testSessionEndpoint = async function() {
        console.log('2️⃣ [TEST-500] Test endpoint avec session...');
        
        try {
            const response = await fetch('ajax/creer_devis_simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ test: true })
            });
            
            console.log('📊 [TEST-500] Status session test:', response.status);
            
            if (response.ok) {
                const result = await response.json();
                console.log('✅ [TEST-500] Endpoint session fonctionne:', result);
                return result;
            } else {
                console.error('❌ [TEST-500] Endpoint session échoue:', response.status);
                const errorText = await response.text();
                console.error('📋 [TEST-500] Contenu erreur:', errorText);
                return false;
            }
        } catch (error) {
            console.error('💥 [TEST-500] Erreur endpoint session:', error);
            return false;
        }
    };
    
    // Test 3: Endpoint original problématique
    window.testOriginalEndpoint = async function() {
        console.log('3️⃣ [TEST-500] Test endpoint original...');
        
        const testData = {
            action: 'test',
            reparation_id: 1000,
            titre: 'Test Debug',
            description: 'Test'
        };
        
        try {
            const response = await fetch('ajax/creer_devis.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(testData)
            });
            
            console.log('📊 [TEST-500] Status original:', response.status);
            
            // Récupérer le contenu brut pour analyse
            const responseText = await response.text();
            console.log('📋 [TEST-500] Réponse brute original:', responseText.substring(0, 500) + '...');
            
            if (response.status === 500) {
                console.error('💀 [TEST-500] ERREUR 500 CONFIRMÉE sur endpoint original');
                
                // Analyser le type d'erreur
                if (responseText.includes('Fatal error')) {
                    console.error('⚠️ [FATAL] Erreur PHP fatale détectée');
                    const fatalMatch = responseText.match(/Fatal error: (.+?) in (.+?) on line (\d+)/);
                    if (fatalMatch) {
                        console.error(`📍 [FATAL] Erreur: ${fatalMatch[1]}`);
                        console.error(`📁 [FATAL] Fichier: ${fatalMatch[2]}`);
                        console.error(`📏 [FATAL] Ligne: ${fatalMatch[3]}`);
                    }
                } else if (responseText.includes('Parse error')) {
                    console.error('⚠️ [PARSE] Erreur de syntaxe PHP');
                } else if (responseText.includes('Warning')) {
                    console.error('⚠️ [WARNING] Avertissement PHP');
                } else {
                    console.error('🤔 [UNKNOWN] Erreur 500 sans message PHP visible');
                }
                
                return false;
            } else {
                console.log('✅ [TEST-500] Pas d\'erreur 500 sur endpoint original');
                return true;
            }
            
        } catch (error) {
            console.error('💥 [TEST-500] Erreur endpoint original:', error);
            return false;
        }
    };
    
    // Test complet avec diagnostic
    window.diagnosticErreur500Complete = async function() {
        console.log('🔬 [TEST-500] === DIAGNOSTIC COMPLET ===');
        
        // Test 1
        const simpleOk = await window.testSimpleEndpoint();
        
        // Test 2
        const sessionResult = await window.testSessionEndpoint();
        
        // Test 3
        const originalOk = await window.testOriginalEndpoint();
        
        console.log('📊 [RÉSUMÉ] Résultats des tests:');
        console.log(`  - Endpoint simple: ${simpleOk ? '✅ OK' : '❌ FAIL'}`);
        console.log(`  - Endpoint session: ${sessionResult ? '✅ OK' : '❌ FAIL'}`);
        console.log(`  - Endpoint original: ${originalOk ? '✅ OK' : '❌ FAIL'}`);
        
        if (simpleOk && sessionResult && !originalOk) {
            console.log('💡 [DIAGNOSTIC] Le problème est spécifiquement dans creer_devis.php');
            console.log('💡 [SOLUTION] Vérifier les includes et la logique métier');
        } else if (!sessionResult) {
            console.log('💡 [DIAGNOSTIC] Problème de session ou d\'authentification');
            console.log('💡 [SOLUTION] Vérifier la session et les cookies');
        } else if (!simpleOk) {
            console.log('💡 [DIAGNOSTIC] Problème PHP global');
            console.log('💡 [SOLUTION] Vérifier la configuration du serveur');
        }
        
        return { simpleOk, sessionResult, originalOk };
    };
    
    // Auto-diagnostic après 2 secondes
    setTimeout(() => {
        console.log('🔄 [AUTO-DIAG] Lancement du diagnostic automatique...');
        window.diagnosticErreur500Complete();
    }, 2000);
    
    console.log('✅ [TEST-500-SIMPLE] Fonctions disponibles:');
    console.log('  - testSimpleEndpoint() : Test endpoint basique');
    console.log('  - testSessionEndpoint() : Test avec session');
    console.log('  - testOriginalEndpoint() : Test endpoint problématique');
    console.log('  - diagnosticErreur500Complete() : Diagnostic complet');
});
















