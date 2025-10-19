// Diagnostic direct à copier-coller dans la console
console.log('🔬 [DIAGNOSTIC-DIRECT] Test immédiat de l\'erreur 500...');

// Test 1: Endpoint simple
fetch('ajax/simple_test.php')
.then(response => {
    console.log('📊 [TEST-1] Status test simple:', response.status);
    if (response.ok) {
        return response.json();
    } else {
        throw new Error('Test simple échoué: ' + response.status);
    }
})
.then(data => {
    console.log('✅ [TEST-1] Endpoint simple OK:', data);
    
    // Test 2: Session et auth
    return fetch('ajax/creer_devis_simple.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ test: true })
    });
})
.then(response => {
    console.log('📊 [TEST-2] Status test session:', response.status);
    if (response.ok) {
        return response.json();
    } else {
        return response.text().then(text => {
            console.error('❌ [TEST-2] Erreur session:', text);
            throw new Error('Test session échoué: ' + response.status);
        });
    }
})
.then(data => {
    console.log('✅ [TEST-2] Session OK:', data);
    
    // Test 3: Endpoint original problématique
    return fetch('ajax/creer_devis.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'test',
            reparation_id: 1000,
            titre: 'Test Debug',
            description: 'Test pour identifier erreur 500'
        })
    });
})
.then(response => {
    console.log('📊 [TEST-3] Status endpoint original:', response.status);
    
    return response.text().then(text => {
        if (response.status === 500) {
            console.error('💀 [ERREUR-500] Contenu de l\'erreur:');
            console.error(text);
            
            // Analyser l'erreur
            if (text.includes('Fatal error')) {
                const match = text.match(/Fatal error: (.+?) in (.+?) on line (\d+)/);
                if (match) {
                    console.error(`🎯 [SOLUTION] ERREUR FATALE TROUVÉE:`);
                    console.error(`   Erreur: ${match[1]}`);
                    console.error(`   Fichier: ${match[2]}`);
                    console.error(`   Ligne: ${match[3]}`);
                }
            } else if (text.includes('require_once') || text.includes('include')) {
                console.error('🎯 [SOLUTION] Problème d\'include/require de fichier');
            } else if (text.includes('Call to undefined')) {
                console.error('🎯 [SOLUTION] Fonction non définie');
            } else {
                console.error('🎯 [SOLUTION] Erreur PHP non identifiée, vérifiez les logs serveur');
            }
        } else {
            console.log('✅ [TEST-3] Pas d\'erreur 500');
            try {
                const json = JSON.parse(text);
                console.log('✅ [TEST-3] Réponse JSON:', json);
            } catch (e) {
                console.log('📋 [TEST-3] Réponse texte:', text.substring(0, 200));
            }
        }
    });
})
.catch(error => {
    console.error('💥 [DIAGNOSTIC] Erreur générale:', error);
});

console.log('⏳ [DIAGNOSTIC-DIRECT] Tests en cours... Attendez les résultats ci-dessus.');
















