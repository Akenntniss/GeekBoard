// Test de disponibilité des fonctions de devis
console.log('🔍 [TEST-DEVIS] Vérification des fonctions disponibles...');

const functionsToTest = [
    'ouvrirNouveauModalDevis',
    'openDevisModalModern', 
    'ouvrirModalDevis',
    'openDevisModalSafely'
];

functionsToTest.forEach(funcName => {
    if (typeof window[funcName] === 'function') {
        console.log(`✅ [TEST-DEVIS] ${funcName} - DISPONIBLE`);
    } else {
        console.error(`❌ [TEST-DEVIS] ${funcName} - MANQUANTE`);
    }
});

// Test spécifique pour ouvrirNouveauModalDevis
if (typeof window.ouvrirNouveauModalDevis === 'function') {
    console.log('🎯 [TEST-DEVIS] Test de la fonction ouvrirNouveauModalDevis:');
    
    // Test avec un ID de test (sans l'exécuter réellement)
    console.log('  - Type:', typeof window.ouvrirNouveauModalDevis);
    console.log('  - Source:', window.ouvrirNouveauModalDevis.toString().substring(0, 100) + '...');
    
    // Simuler un appel pour voir les logs
    console.log('🚀 [TEST-DEVIS] Simulation d\'appel avec ID test...');
    try {
        // Ne pas exécuter réellement, juste tester la disponibilité
        console.log('  - Fonction accessible et prête à être appelée');
    } catch (e) {
        console.error('  - Erreur:', e);
    }
} else {
    console.error('💥 [TEST-DEVIS] PROBLÈME CRITIQUE: ouvrirNouveauModalDevis non disponible');
    
    // Diagnostiquer pourquoi elle n'est pas disponible
    console.log('🔧 [TEST-DEVIS] Diagnostic:');
    console.log('  - window:', typeof window);
    console.log('  - window.ouvrirNouveauModalDevis:', window.ouvrirNouveauModalDevis);
    
    // Vérifier les scripts chargés
    const scripts = document.querySelectorAll('script[src*="devis"]');
    console.log('  - Scripts devis chargés:', scripts.length);
    scripts.forEach((script, index) => {
        console.log(`    ${index + 1}. ${script.src}`);
    });
}

console.log('🏁 [TEST-DEVIS] Test terminé.');

// Fonction pour tester manuellement depuis la console
window.testDevisFunction = function(repairId = 123) {
    console.log('🧪 [TEST-MANUEL] Test manuel avec ID:', repairId);
    if (typeof window.ouvrirNouveauModalDevis === 'function') {
        window.ouvrirNouveauModalDevis(repairId);
    } else {
        console.error('❌ [TEST-MANUEL] Fonction non disponible');
    }
};















