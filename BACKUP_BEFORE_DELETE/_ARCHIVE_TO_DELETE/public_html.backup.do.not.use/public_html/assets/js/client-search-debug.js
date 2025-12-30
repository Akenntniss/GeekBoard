/**
 * Script de diagnostic pour la recherche client dans ajouterCommandeModal
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 [CLIENT-SEARCH-DEBUG] Script de diagnostic chargé');
    
    // Attendre que le modal soit ouvert pour faire le diagnostic
    const modal = document.getElementById('ajouterCommandeModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            console.log('🔍 [CLIENT-SEARCH-DEBUG] Modal ouvert, diagnostic des éléments...');
            diagnosticElementsRecherche();
        });
    }
    
    function diagnosticElementsRecherche() {
        const elements = {
            modal: document.getElementById('ajouterCommandeModal'),
            clientSearchInput: document.getElementById('nom_client_selectionne'),
            clientIdInput: document.getElementById('client_id'),
            clientSelectionne: document.getElementById('client_selectionne'),
            resultatsRecherche: document.getElementById('resultats_recherche_client_inline'),
            listeClients: document.getElementById('liste_clients_recherche_inline'),
            newClientBtn: document.getElementById('newClientBtn')
        };
        
        console.log('🔍 [CLIENT-SEARCH-DEBUG] Diagnostic des éléments:');
        Object.keys(elements).forEach(key => {
            const element = elements[key];
            console.log(`  - ${key}: ${element ? '✅ Trouvé' : '❌ MANQUANT'}`);
            if (element) {
                console.log(`    ID: ${element.id}, Classes: ${element.className}`);
                if (key === 'clientSearchInput') {
                    console.log(`    Placeholder: ${element.placeholder}`);
                    console.log(`    Value: "${element.value}"`);
                }
            }
        });
        
        // Test de l'événement input sur le champ de recherche
        const clientSearchInput = elements.clientSearchInput;
        if (clientSearchInput) {
            console.log('🔍 [CLIENT-SEARCH-DEBUG] Ajout d\'un écouteur de test...');
            
            clientSearchInput.addEventListener('input', function() {
                console.log(`🔍 [CLIENT-SEARCH-DEBUG] Input détecté: "${this.value}"`);
            });
            
            clientSearchInput.addEventListener('focus', function() {
                console.log('🔍 [CLIENT-SEARCH-DEBUG] Champ de recherche en focus');
            });
            
            clientSearchInput.addEventListener('blur', function() {
                console.log('🔍 [CLIENT-SEARCH-DEBUG] Champ de recherche perdu le focus');
            });
        }
        
        // Test manuel de recherche
        window.testClientSearch = function(terme = 'test') {
            console.log(`🔍 [CLIENT-SEARCH-DEBUG] Test manuel avec le terme: "${terme}"`);
            
            fetch('ajax/recherche_clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: `terme=${encodeURIComponent(terme)}`
            })
            .then(response => {
                console.log('🔍 [CLIENT-SEARCH-DEBUG] Réponse HTTP:', response.status, response.statusText);
                return response.text();
            })
            .then(text => {
                console.log('🔍 [CLIENT-SEARCH-DEBUG] Réponse brute:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('🔍 [CLIENT-SEARCH-DEBUG] Données parsées:', data);
                } catch (e) {
                    console.error('🔍 [CLIENT-SEARCH-DEBUG] Erreur parsing JSON:', e);
                }
            })
            .catch(err => {
                console.error('🔍 [CLIENT-SEARCH-DEBUG] Erreur requête:', err);
            });
        };
        
        console.log('🔍 [CLIENT-SEARCH-DEBUG] Utilisez window.testClientSearch("nom") pour tester manuellement');
    }
});


