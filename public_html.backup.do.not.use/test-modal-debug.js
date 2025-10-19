// Script de debug pour tester le modal de devis
console.log('🧪 [TEST-MODAL-DEBUG] Chargement du script de test...');

// Fonction pour tester l'ouverture du modal
window.testModalDebug = function(reparationId = 1000) {
    console.log('🔬 [TEST-DEBUG] Test d\'ouverture du modal avec ID:', reparationId);
    
    // 1. Vérifier que les fonctions sont disponibles
    console.log('📋 [TEST-DEBUG] Vérification des fonctions:');
    console.log('  - ouvrirNouveauModalDevis:', typeof window.ouvrirNouveauModalDevis);
    console.log('  - ouvrirModalDevis:', typeof window.ouvrirModalDevis);
    console.log('  - devisManager:', typeof window.devisManager);
    
    // 2. Vérifier que le modal existe dans le DOM
    const modal = document.getElementById('creerDevisModal');
    console.log('🏗️ [TEST-DEBUG] Modal dans le DOM:', !!modal);
    
    if (modal) {
        // 3. Vérifier la structure du modal
        const steps = modal.querySelectorAll('.step-content');
        const stepIndicators = modal.querySelectorAll('.step-item');
        const nextBtn = modal.querySelector('#nextStep');
        const prevBtn = modal.querySelector('#prevStep');
        const saveBtn = modal.querySelector('#creerEtEnvoyer');
        
        console.log('📊 [TEST-DEBUG] Structure du modal:');
        console.log('  - Étapes de contenu:', steps.length);
        console.log('  - Indicateurs d\'étapes:', stepIndicators.length);
        console.log('  - Bouton Suivant:', !!nextBtn);
        console.log('  - Bouton Précédent:', !!prevBtn);
        console.log('  - Bouton Sauvegarder:', !!saveBtn);
        
        // 4. Tester l'ouverture
        console.log('🚀 [TEST-DEBUG] Test d\'ouverture...');
        try {
            if (typeof window.ouvrirNouveauModalDevis === 'function') {
                window.ouvrirNouveauModalDevis(reparationId);
                
                // 5. Vérifier l'état après ouverture (après un délai)
                setTimeout(() => {
                    console.log('✅ [TEST-DEBUG] Vérification post-ouverture:');
                    console.log('  - Modal visible:', modal.classList.contains('show'));
                    
                    const activeStep = modal.querySelector('.step-content[style*="block"]');
                    const activeIndicator = modal.querySelector('.step-item.active');
                    
                    console.log('  - Étape active visible:', !!activeStep);
                    console.log('  - Indicateur actif:', !!activeIndicator);
                    
                    if (activeIndicator) {
                        console.log('  - Numéro d\'étape active:', activeIndicator.dataset.step);
                    }
                    
                    // Test des boutons
                    if (nextBtn) {
                        console.log('  - Bouton Suivant visible:', nextBtn.style.display !== 'none');
                        console.log('  - Bouton Suivant cliquable:', !nextBtn.disabled);
                    }
                    
                }, 1000);
                
            } else {
                console.error('❌ [TEST-DEBUG] Fonction ouvrirNouveauModalDevis non disponible');
            }
        } catch (error) {
            console.error('💥 [TEST-DEBUG] Erreur lors du test:', error);
        }
    } else {
        console.error('❌ [TEST-DEBUG] Modal non trouvé dans le DOM');
    }
};

// Fonction pour tester la navigation
window.testNavigation = function() {
    console.log('🧭 [TEST-NAV] Test de navigation...');
    
    const modal = document.getElementById('creerDevisModal');
    if (!modal) {
        console.error('❌ [TEST-NAV] Modal non trouvé');
        return;
    }
    
    const nextBtn = modal.querySelector('#nextStep');
    const prevBtn = modal.querySelector('#prevStep');
    
    if (nextBtn) {
        console.log('🔽 [TEST-NAV] Test du bouton Suivant...');
        nextBtn.click();
        
        setTimeout(() => {
            const activeIndicator = modal.querySelector('.step-item.active');
            console.log('  - Étape après clic Suivant:', activeIndicator ? activeIndicator.dataset.step : 'aucune');
        }, 500);
    }
    
    setTimeout(() => {
        if (prevBtn) {
            console.log('🔼 [TEST-NAV] Test du bouton Précédent...');
            prevBtn.click();
            
            setTimeout(() => {
                const activeIndicator = modal.querySelector('.step-item.active');
                console.log('  - Étape après clic Précédent:', activeIndicator ? activeIndicator.dataset.step : 'aucune');
            }, 500);
        }
    }, 1000);
};

// Auto-test si appelé depuis la console
if (window.location.href.includes('reparations')) {
    console.log('🎯 [TEST-DEBUG] Script chargé sur la page réparations');
    console.log('📋 [TEST-DEBUG] Fonctions disponibles:');
    console.log('  - testModalDebug(reparationId) : Test complet du modal');
    console.log('  - testNavigation() : Test de navigation entre étapes');
    console.log('💡 [TEST-DEBUG] Exemple: testModalDebug(1000)');
}















