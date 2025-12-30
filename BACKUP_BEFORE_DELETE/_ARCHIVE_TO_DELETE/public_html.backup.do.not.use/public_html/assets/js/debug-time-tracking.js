/**
 * Script de diagnostic pour le système de pointage
 */

// Fonction pour tester l'API de diagnostic
async function testTimeTrackingDebug() {
    console.log('🔍 [DEBUG] Test de l\'API de diagnostic...');
    
    try {
        const response = await fetch('debug_time_tracking.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        console.log('📡 [DEBUG] Réponse reçue:', response.status);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📋 [DEBUG] Données de diagnostic:', data);
        
        return data;
        
    } catch (error) {
        console.error('❌ [DEBUG] Erreur lors du test:', error);
        return { error: error.message };
    }
}

// Fonction pour tester l'API de pointage originale
async function testTimeTrackingAPI() {
    console.log('🔍 [DEBUG] Test de l\'API de pointage originale...');
    
    try {
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_status'
        });
        
        console.log('📡 [DEBUG] Réponse API pointage:', response.status);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📋 [DEBUG] Données API pointage:', data);
        
        return data;
        
    } catch (error) {
        console.error('❌ [DEBUG] Erreur API pointage:', error);
        return { error: error.message };
    }
}

// Fonction pour tester avec timeout
async function testWithTimeout(testFunction, timeout = 5000) {
    return Promise.race([
        testFunction(),
        new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Timeout')), timeout)
        )
    ]);
}

// Fonction principale de diagnostic
async function runTimeTrackingDiagnostic() {
    console.log('🚀 [DEBUG] Démarrage du diagnostic complet...');
    
    const results = {};
    
    // Test 1: API de diagnostic
    console.log('🔍 [DEBUG] Test 1: API de diagnostic');
    try {
        results.diagnostic = await testWithTimeout(testTimeTrackingDebug, 10000);
    } catch (error) {
        results.diagnostic = { error: error.message };
    }
    
    // Test 2: API de pointage
    console.log('🔍 [DEBUG] Test 2: API de pointage');
    try {
        results.timetracking = await testWithTimeout(testTimeTrackingAPI, 10000);
    } catch (error) {
        results.timetracking = { error: error.message };
    }
    
    // Test 3: Vérifier les éléments DOM
    console.log('🔍 [DEBUG] Test 3: Éléments DOM');
    results.dom = {
        modal_exists: !!document.getElementById('nouvelles_actions_modal'),
        button_container_exists: !!document.getElementById('dynamic-timetracking-button'),
        bootstrap_version: typeof bootstrap !== 'undefined' ? 'Chargé' : 'Non chargé'
    };
    
    console.log('📊 [DEBUG] Résultats complets:', results);
    
    // Afficher un résumé
    displayDiagnosticSummary(results);
    
    return results;
}

// Fonction pour afficher un résumé du diagnostic
function displayDiagnosticSummary(results) {
    console.log('\n=== RÉSUMÉ DU DIAGNOSTIC ===');
    
    if (results.diagnostic) {
        if (results.diagnostic.error) {
            console.log('❌ API Diagnostic: ERREUR -', results.diagnostic.error);
        } else {
            console.log('✅ API Diagnostic: OK');
            console.log('   - Base de données:', results.diagnostic.database_connection);
            console.log('   - Table time_tracking:', results.diagnostic.time_tracking_table_exists ? 'Existe' : 'Manquante');
            console.log('   - Utilisateur actuel:', results.diagnostic.current_user_id || 'Non défini');
        }
    }
    
    if (results.timetracking) {
        if (results.timetracking.error) {
            console.log('❌ API Pointage: ERREUR -', results.timetracking.error);
        } else {
            console.log('✅ API Pointage: OK');
            console.log('   - Statut:', results.timetracking.success ? 'Succès' : 'Échec');
        }
    }
    
    if (results.dom) {
        console.log('📋 DOM:');
        console.log('   - Modal existe:', results.dom.modal_exists ? '✅' : '❌');
        console.log('   - Container bouton existe:', results.dom.button_container_exists ? '✅' : '❌');
        console.log('   - Bootstrap:', results.dom.bootstrap_version);
    }
    
    console.log('===============================\n');
}

// Fonction pour corriger le problème de pointage
async function fixTimeTrackingIssue() {
    console.log('🔧 [FIX] Tentative de correction du problème de pointage...');
    
    const container = document.getElementById('dynamic-timetracking-button');
    if (!container) {
        console.error('❌ [FIX] Container du bouton de pointage introuvable');
        return;
    }
    
    // Afficher un bouton de fallback
    container.innerHTML = `
    <div class="modern-action-card fallback-card">
        <div class="card-glow"></div>
        <div class="action-icon-container">
            <div class="action-icon bg-gradient-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="pulse-ring"></div>
        </div>
        <div class="action-content">
            <h6 class="action-title">Pointage temporairement indisponible</h6>
            <p class="action-description">Cliquez pour diagnostiquer</p>
        </div>
        <div class="action-arrow">
            <i class="fas fa-chevron-right"></i>
        </div>
    </div>`;
    
    // Ajouter un gestionnaire de clic pour le diagnostic
    container.onclick = function() {
        runTimeTrackingDiagnostic();
    };
    
    console.log('✅ [FIX] Bouton de fallback installé');
}

// Exposer les fonctions globalement pour les tests manuels
window.testTimeTrackingDebug = testTimeTrackingDebug;
window.testTimeTrackingAPI = testTimeTrackingAPI;
window.runTimeTrackingDiagnostic = runTimeTrackingDiagnostic;
window.fixTimeTrackingIssue = fixTimeTrackingIssue;

console.log('🔧 [DEBUG] Script de diagnostic du pointage chargé');
console.log('💡 [DEBUG] Utilisez window.runTimeTrackingDiagnostic() pour lancer le diagnostic complet');
console.log('💡 [DEBUG] Utilisez window.fixTimeTrackingIssue() pour installer un bouton de fallback');
