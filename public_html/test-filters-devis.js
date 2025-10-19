// Script de test pour vérifier que les filtres incluent bien les devis acceptés/refusés
console.log('🧪 [TEST-FILTERS] Script de test des filtres de devis chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 [TEST-FILTERS] === TEST DES FILTRES DEVIS ===');
    
    // Fonction pour tester les filtres
    window.testFiltersDevis = function() {
        console.log('🔍 [TEST-FILTERS] Test des filtres pour les devis acceptés/refusés');
        
        // Vérifier que le bouton "Nouvelle" inclut les bons statuts
        const nouvelleButton = document.querySelector('.modern-filter[data-category-id="1"]');
        
        if (nouvelleButton) {
            console.log('✅ [TEST-FILTERS] Bouton "Nouvelle" trouvé');
            
            // Simuler un clic pour vérifier la logique
            const clickEvent = new Event('click', { bubbles: true });
            nouvelleButton.dispatchEvent(clickEvent);
            
            // Vérifier l'URL générée ou les paramètres
            setTimeout(() => {
                const currentUrl = window.location.href;
                console.log('📋 [TEST-FILTERS] URL actuelle après clic:', currentUrl);
                
                if (currentUrl.includes('statut_ids=1,2,3,19,20')) {
                    console.log('✅ [TEST-FILTERS] Les statuts 19 et 20 sont bien inclus');
                } else if (currentUrl.includes('statut_ids=1%2C2%2C3%2C19%2C20')) {
                    console.log('✅ [TEST-FILTERS] Les statuts 19 et 20 sont bien inclus (URL encodée)');
                } else {
                    console.log('❌ [TEST-FILTERS] Les statuts 19 et 20 ne sont pas inclus');
                    console.log('📋 [TEST-FILTERS] URL actuelle:', currentUrl);
                }
            }, 500);
        } else {
            console.error('❌ [TEST-FILTERS] Bouton "Nouvelle" non trouvé');
        }
    };
    
    // Fonction pour vérifier le comptage
    window.checkNouvelleCounting = function() {
        console.log('🔢 [TEST-FILTERS] Vérification du comptage des "nouvelles"');
        
        const countElement = document.querySelector('.modern-filter[data-category-id="1"] .filter-count');
        
        if (countElement) {
            const count = countElement.textContent.trim();
            console.log('📊 [TEST-FILTERS] Nombre de réparations "nouvelles":', count);
            console.log('📝 [TEST-FILTERS] Ce nombre devrait maintenant inclure les devis acceptés et refusés');
        } else {
            console.error('❌ [TEST-FILTERS] Élément de comptage non trouvé');
        }
    };
    
    // Fonction pour lister toutes les réparations visibles avec leurs statuts
    window.listVisibleRepairs = function() {
        console.log('📋 [TEST-FILTERS] Liste des réparations visibles:');
        
        const cards = document.querySelectorAll('.repair-card, .reparation-card');
        
        cards.forEach((card, index) => {
            const statusElement = card.querySelector('.status-indicator, .badge, .statut');
            const clientElement = card.querySelector('.client-name, .client');
            
            const status = statusElement ? statusElement.textContent.trim() : 'Statut non trouvé';
            const client = clientElement ? clientElement.textContent.trim() : 'Client non trouvé';
            
            console.log(`  ${index + 1}. ${client} - Statut: ${status}`);
        });
        
        console.log(`📊 Total réparations visibles: ${cards.length}`);
    };
    
    // Auto-test après 2 secondes
    setTimeout(() => {
        console.log('🔄 [AUTO-TEST] Démarrage des tests automatiques...');
        window.checkNouvelleCounting();
        window.listVisibleRepairs();
    }, 2000);
    
    console.log('✅ [TEST-FILTERS] Fonctions de test disponibles:');
    console.log('  - testFiltersDevis() : Teste le filtre "Nouvelle"');
    console.log('  - checkNouvelleCounting() : Vérifie le comptage');
    console.log('  - listVisibleRepairs() : Liste les réparations visibles');
});









