/**
 * Script de diagnostic pour la recherche client dans ajouterCommandeModal
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Attendre que le modal soit ouvert pour faire le diagnostic
    const modal = document.getElementById('ajouterCommandeModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            diagnosticElementsRecherche();
    }
    
    function diagnosticElementsRecherche() {
        const elements = {
            modal: document.getElementById('ajouterCommandeModal'),
            clientSearchInput: document.getElementById('nom_client_selectionne'),
            clientIdInput: document.getElementById('client_id'),
            clientSelectionne: document.getElementById('client_selectionne'),
            resultatsRecherche: document.getElementById('resultats_recherche_client_inline'),
            listeClients: document.getElementById('liste_clients_recherche_inline'),
            newClientBtn: document.getElementById('newClientBtn'),
        };
        
        console.log('🔍 [CLIENT-SEARCH-DEBUG] Diagnostic des éléments:');
        Object.keys(elements).forEach(key => {
            const element = elements[key];
            if (element) {
                if (key === 'clientSearchInput') {
                }
            }
        
        // Test de l'événement input sur le champ de recherche
        const clientSearchInput = elements.clientSearchInput;
        if (clientSearchInput) {
            
            clientSearchInput.addEventListener('input', function() {
            
            clientSearchInput.addEventListener('focus', function() {
            
            clientSearchInput.addEventListener('blur', function() {
        }
        
        // Test manuel de recherche
        window.testClientSearch = function(terme = 'test') {
            
            fetch('ajax/recherche_clients.php', {
                method: 'POST',
                headers: {,
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
                credentials: 'same-origin',
                body: `terme=${encodeURIComponent(terme)}`
            })
            .then(response => {
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                } catch (e) {
                }
            })
            .catch(err => {
        };
        
        console.log('🔍 [CLIENT-SEARCH-DEBUG] Utilisez window.testClientSearch("nom") pour tester manuellement');
    }
