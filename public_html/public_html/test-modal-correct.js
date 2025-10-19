// Test pour vérifier que le bon modal s'ouvre
console.log('🔍 [TEST-MODAL] Vérification du modal qui s\'ouvre...');

// Fonction de test
window.testModalCorrect = function(reparationId = 123) {
    console.log('🧪 [TEST-MODAL] Test d\'ouverture du modal avec ID:', reparationId);
    
    // Observer les modals qui s'ouvrent
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const target = mutation.target;
                if (target.classList.contains('modal') && target.classList.contains('show')) {
                    console.log('👁️ [TEST-MODAL] Modal ouvert détecté:', target.id);
                    
                    // Vérifier quel modal est ouvert
                    if (target.id === 'creerDevisModal') {
                        console.log('✅ [TEST-MODAL] BON MODAL: creerDevisModal (moderne) est ouvert');
                        
                        // Vérifier le contenu
                        const titre = target.querySelector('#creerDevisModalLabel');
                        if (titre) {
                            console.log('📋 [TEST-MODAL] Titre du modal:', titre.textContent);
                        }
                        
                        // Vérifier les étapes
                        const etapes = target.querySelectorAll('.step-item');
                        console.log('📊 [TEST-MODAL] Nombre d\'étapes:', etapes.length);
                        
                        // Vérifier le bouton final
                        const btnSauvegarder = target.querySelector('#creerEtEnvoyer');
                        if (btnSauvegarder) {
                            console.log('🔘 [TEST-MODAL] Bouton final:', btnSauvegarder.textContent.trim());
                        }
                        
                    } else {
                        console.error('❌ [TEST-MODAL] MAUVAIS MODAL ouvert:', target.id);
                    }
                }
            }
        });
    });
    
    // Observer tous les modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        observer.observe(modal, { attributes: true });
    });
    
    // Arrêter l'observation après 5 secondes
    setTimeout(() => {
        observer.disconnect();
        console.log('🔚 [TEST-MODAL] Fin de l\'observation');
    }, 5000);
    
    // Déclencher l'ouverture
    console.log('🚀 [TEST-MODAL] Déclenchement de l\'ouverture...');
    if (typeof window.ouvrirNouveauModalDevis === 'function') {
        window.ouvrirNouveauModalDevis(reparationId);
    } else {
        console.error('❌ [TEST-MODAL] Fonction ouvrirNouveauModalDevis non disponible');
    }
};

// Fonction pour vérifier rapidement les modals présents
window.checkModalsPresents = function() {
    console.log('📋 [CHECK-MODALS] Vérification des modals présents:');
    
    const creerDevisModal = document.getElementById('creerDevisModal');
    if (creerDevisModal) {
        console.log('✅ [CHECK-MODALS] Modal moderne (creerDevisModal) trouvé');
        console.log('   - Classes:', creerDevisModal.className);
        console.log('   - Titre:', creerDevisModal.querySelector('#creerDevisModalLabel')?.textContent);
    } else {
        console.error('❌ [CHECK-MODALS] Modal moderne (creerDevisModal) MANQUANT');
    }
    
    // Chercher d'autres modals de devis
    const allModals = document.querySelectorAll('.modal');
    console.log('📊 [CHECK-MODALS] Tous les modals trouvés:', allModals.length);
    allModals.forEach((modal, index) => {
        if (modal.id.includes('devis') || modal.querySelector('[data-bs-target*="devis"]')) {
            console.log(`   ${index + 1}. ID: ${modal.id}, Classes: ${modal.className}`);
        }
    });
};

console.log('✅ [TEST-MODAL] Fonctions de test disponibles:');
console.log('  - testModalCorrect(reparationId) : Tester l\'ouverture du modal');
console.log('  - checkModalsPresents() : Vérifier les modals présents');















