/**
 * Script de test pour forcer l'attachement des événements de recherche client
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 [CLIENT-SEARCH-TEST] Test de forçage des événements');
    
    // Attendre que le modal soit visible
    const modal = document.getElementById('ajouterCommandeModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            console.log('🧪 [CLIENT-SEARCH-TEST] Modal ouvert, test des événements...');
            testAndFixClientSearch();
        });
    }
    
    function testAndFixClientSearch() {
        const clientSearchInput = document.getElementById('nom_client_selectionne');
        
        if (!clientSearchInput) {
            console.error('🧪 [CLIENT-SEARCH-TEST] ❌ Champ de recherche introuvable');
            return;
        }
        
        console.log('🧪 [CLIENT-SEARCH-TEST] Test du champ de recherche...');
        
        // Vérifier si des événements sont déjà attachés
        const existingListeners = getEventListeners ? getEventListeners(clientSearchInput) : null;
        console.log('🧪 [CLIENT-SEARCH-TEST] Événements existants:', existingListeners);
        
        // Forcer l'attachement d'un nouvel événement
        console.log('🧪 [CLIENT-SEARCH-TEST] Ajout d\'un événement de test...');
        
        let searchTimeout;
        
        // Supprimer tous les anciens événements
        const newInput = clientSearchInput.cloneNode(true);
        clientSearchInput.parentNode.replaceChild(newInput, clientSearchInput);
        
        console.log('🧪 [CLIENT-SEARCH-TEST] Champ cloné et remplacé');
        
        // Attacher le nouvel événement
        newInput.addEventListener('input', function() {
            const query = this.value.trim();
            console.log('🧪 [CLIENT-SEARCH-TEST] ✅ Input détecté:', query);
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                console.log('🧪 [CLIENT-SEARCH-TEST] Requête trop courte');
                const resultatsRecherche = document.getElementById('resultats_recherche_client_inline');
                if (resultatsRecherche) resultatsRecherche.classList.add('d-none');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                console.log('🧪 [CLIENT-SEARCH-TEST] Lancement de la recherche:', query);
                performClientSearch(query);
            }, 300);
        });
        
        // Attacher aussi un événement de focus pour debug
        newInput.addEventListener('focus', function() {
            console.log('🧪 [CLIENT-SEARCH-TEST] Focus sur le champ de recherche');
        });
        
        console.log('🧪 [CLIENT-SEARCH-TEST] ✅ Nouveaux événements attachés');
    }
    
    function performClientSearch(query) {
        console.log('🧪 [CLIENT-SEARCH-TEST] 🔍 Recherche client:', query);
        
        const resultatsRecherche = document.getElementById('resultats_recherche_client_inline');
        const listeClients = document.getElementById('liste_clients_recherche_inline');
        
        if (!resultatsRecherche || !listeClients) {
            console.error('🧪 [CLIENT-SEARCH-TEST] ❌ Éléments de résultats introuvables');
            return;
        }
        
        // Afficher un indicateur de chargement
        listeClients.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>';
        resultatsRecherche.classList.remove('d-none');
        
        fetch('ajax/recherche_clients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: `terme=${encodeURIComponent(query)}`
        })
        .then(response => {
            console.log('🧪 [CLIENT-SEARCH-TEST] Réponse HTTP:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('🧪 [CLIENT-SEARCH-TEST] Réponse brute:', text);
            try {
                const data = JSON.parse(text);
                console.log('🧪 [CLIENT-SEARCH-TEST] Données parsées:', data);
                
                if (data.success && Array.isArray(data.clients)) {
                    displayClientResults(data.clients);
                } else {
                    console.error('🧪 [CLIENT-SEARCH-TEST] ❌ Pas de résultats ou erreur:', data);
                    listeClients.innerHTML = '<div class="text-muted p-3">Aucun client trouvé</div>';
                }
            } catch (e) {
                console.error('🧪 [CLIENT-SEARCH-TEST] ❌ Erreur JSON:', e);
                listeClients.innerHTML = '<div class="text-danger p-3">Erreur de parsing JSON</div>';
            }
        })
        .catch(err => {
            console.error('🧪 [CLIENT-SEARCH-TEST] ❌ Erreur requête:', err);
            listeClients.innerHTML = '<div class="text-danger p-3">Erreur de connexion</div>';
        });
    }
    
    function displayClientResults(clients) {
        console.log('🧪 [CLIENT-SEARCH-TEST] Affichage des résultats:', clients.length, 'client(s)');
        
        const listeClients = document.getElementById('liste_clients_recherche_inline');
        const clientIdInput = document.getElementById('client_id');
        const clientSelectionne = document.getElementById('client_selectionne');
        const resultatsRecherche = document.getElementById('resultats_recherche_client_inline');
        
        listeClients.innerHTML = '';
        
        clients.forEach((client, index) => {
            console.log('🧪 [CLIENT-SEARCH-TEST] Client', index + 1, ':', client);
            
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action client-item';
            item.style.cursor = 'pointer';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">${client.nom} ${client.prenom}</div>
                        <div class="text-muted small">${client.telephone || 'Pas de téléphone'}</div>
                    </div>
                </div>
            `;
            
            item.addEventListener('click', () => {
                console.log('🧪 [CLIENT-SEARCH-TEST] ✅ Client sélectionné:', client);
                
                // Remplir les champs
                if (clientIdInput) clientIdInput.value = client.id;
                
                const searchInput = document.getElementById('nom_client_selectionne');
                if (searchInput) searchInput.value = `${client.nom} ${client.prenom}`;
                
                // Afficher le client sélectionné
                if (clientSelectionne) {
                    const nomClient = clientSelectionne.querySelector('.nom_client');
                    const telClient = clientSelectionne.querySelector('.tel_client');
                    if (nomClient) nomClient.textContent = `${client.nom} ${client.prenom}`;
                    if (telClient) telClient.textContent = client.telephone || 'Pas de téléphone';
                    clientSelectionne.classList.remove('d-none');
                }
                
                // Masquer les résultats
                if (resultatsRecherche) resultatsRecherche.classList.add('d-none');
            });
            
            listeClients.appendChild(item);
        });
        
        console.log('🧪 [CLIENT-SEARCH-TEST] ✅ Résultats affichés');
    }
    
    // Fonction de test manuel
    window.testClientSearchForced = function(terme = 'test') {
        console.log('🧪 [CLIENT-SEARCH-TEST] Test manuel forcé:', terme);
        performClientSearch(terme);
    };
    
    console.log('🧪 [CLIENT-SEARCH-TEST] ✅ Script de test initialisé');
    console.log('🧪 [CLIENT-SEARCH-TEST] 💡 Utilisez window.testClientSearchForced("nom") pour tester');
});
