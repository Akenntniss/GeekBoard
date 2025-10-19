// Script JavaScript correct pour le modal de recherche universelle
// Correspondance exacte avec les IDs du modal-recherche-simple.php

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Recherche Modal : Initialisation...');
    
    // Éléments du DOM avec les IDs corrects
    const modal = document.getElementById('rechercheModal');
    const input = document.getElementById('rechercheInput');
    const btn = document.getElementById('rechercheBtn');
    const loading = document.getElementById('rechercheLoading');
    const results = document.getElementById('rechercheResults');
    const empty = document.getElementById('rechercheEmpty');
    
    // Compteurs avec les IDs corrects
    const clientsCount = document.getElementById('clientsCount');
    const reparationsCount = document.getElementById('reparationsCount');
    const commandesCount = document.getElementById('commandesCount');
    
    // Corps des tableaux avec les IDs corrects
    const clientsTableBody = document.getElementById('clientsTableBody');
    const reparationsTableBody = document.getElementById('reparationsTableBody');
    const commandesTableBody = document.getElementById('commandesTableBody');
    
    // Vérifier que tous les éléments existent
    console.log('🔍 Vérification des éléments DOM:');
    console.log('  - modal:', !!modal);
    console.log('  - input:', !!input);
    console.log('  - btn:', !!btn);
    console.log('  - loading:', !!loading);
    console.log('  - results:', !!results);
    console.log('  - empty:', !!empty);
    console.log('  - clientsCount:', !!clientsCount);
    console.log('  - clientsTableBody:', !!clientsTableBody);
    console.log('  - reparationsTableBody:', !!reparationsTableBody);
    console.log('  - commandesTableBody:', !!commandesTableBody);
    
    if (!modal || !input || !btn || !loading || !results || !empty || 
        !clientsCount || !reparationsCount || !commandesCount ||
        !clientsTableBody || !reparationsTableBody || !commandesTableBody) {
        console.error('❌ Recherche Modal : Éléments manquants dans le DOM');
        return;
    }
    
    console.log('✅ Recherche Modal : Tous les éléments trouvés');
    
    // Fonction pour cacher tous les états
    function hideAllStates() {
        loading.style.display = 'none';
        results.style.display = 'none';
        empty.style.display = 'none';
    }
    
    // Fonction pour vider tous les résultats
    function clearAllResults() {
        clientsTableBody.innerHTML = '';
        reparationsTableBody.innerHTML = '';
        commandesTableBody.innerHTML = '';
        
        clientsCount.textContent = '0';
        reparationsCount.textContent = '0';
        commandesCount.textContent = '0';
    }
    
    // Fonction pour afficher les résultats
    function displayResults(data) {
        console.log('📊 Affichage des résultats:', data);
        
        // Vider les résultats précédents
        clearAllResults();
        
        // Cacher les états de chargement et vide
        hideAllStates();
        
        // Afficher les clients
        if (data.clients && data.clients.length > 0) {
            clientsCount.textContent = data.clients.length;
            clientsCount.classList.remove('bg-secondary');
            clientsCount.classList.add('bg-primary');
            
            data.clients.forEach(client => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${client.nom} ${client.prenom}</td>
                    <td>${client.telephone}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='pages/detail_client.php?id=${client.id}'">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                    </td>
                `;
                clientsTableBody.appendChild(row);
            });
        }
        
        // Afficher les réparations
        if (data.reparations && data.reparations.length > 0) {
            reparationsCount.textContent = data.reparations.length;
            reparationsCount.classList.remove('bg-secondary');
            reparationsCount.classList.add('bg-primary');
            
            data.reparations.forEach(reparation => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${reparation.client_nom} ${reparation.client_prenom}</td>
                    <td>${reparation.modele_appareil}</td>
                    <td>${reparation.probleme_declare}</td>
                    <td>
                        <span class="badge bg-${getStatusColor(reparation.statut)}">${reparation.statut}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='pages/detail_reparation.php?id=${reparation.id}'">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                    </td>
                `;
                reparationsTableBody.appendChild(row);
            });
        }
        
        // Afficher les commandes
        if (data.commandes && data.commandes.length > 0) {
            commandesCount.textContent = data.commandes.length;
            commandesCount.classList.remove('bg-secondary');
            commandesCount.classList.add('bg-primary');
            
            data.commandes.forEach(commande => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${commande.reparation_id}</td>
                    <td>${commande.piece_nom}</td>
                    <td>
                        <span class="badge bg-${getStatusColor(commande.statut)}">${commande.statut}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='pages/detail_commande.php?id=${commande.id}'">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                    </td>
                `;
                commandesTableBody.appendChild(row);
            });
        }
        
        // Afficher les résultats ou le message vide
        const totalResults = (data.clients?.length || 0) + (data.reparations?.length || 0) + (data.commandes?.length || 0);
        
        if (totalResults > 0) {
            results.style.display = 'block';
            
            // Forcer l'affichage des tableaux
            setTimeout(() => {
                const tabContent = document.getElementById('resultTabContent');
                if (tabContent) {
                    tabContent.style.display = 'block';
                    tabContent.style.visibility = 'visible';
                    tabContent.style.opacity = '1';
                }
                
                // Forcer l'affichage de l'onglet clients par défaut
                const clientsPane = document.getElementById('clients-pane');
                if (clientsPane) {
                    clientsPane.style.display = 'block';
                    clientsPane.style.visibility = 'visible';
                    clientsPane.style.opacity = '1';
                }
            }, 100);
        } else {
            empty.style.display = 'block';
        }
    }
    
    // Fonction pour obtenir la couleur du statut
    function getStatusColor(statut) {
        switch(statut.toLowerCase()) {
            case 'en cours':
            case 'en_cours':
                return 'warning';
            case 'terminé':
            case 'termine':
                return 'success';
            case 'annulé':
            case 'annule':
                return 'danger';
            case 'en attente':
            case 'en_attente':
                return 'info';
            default:
                return 'secondary';
        }
    }
    
    // Fonction de recherche
    function performSearch() {
        const searchTerm = input.value.trim();
        
        if (searchTerm.length < 2) {
            alert('Veuillez saisir au moins 2 caractères pour la recherche');
            return;
        }
        
        console.log('🔍 Recherche lancée:', searchTerm);
        
        // Afficher le chargement
        hideAllStates();
        loading.style.display = 'block';
        
        // Requête AJAX
        fetch('ajax/recherche_universelle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `terme=${encodeURIComponent(searchTerm)}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('📡 Réponse reçue:', data);
            
            if (data.success) {
                displayResults(data);
            } else {
                console.error('❌ Erreur serveur:', data.error);
                hideAllStates();
                empty.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('❌ Erreur AJAX:', error);
            hideAllStates();
            empty.style.display = 'block';
        });
    }
    
    // Événements
    btn.addEventListener('click', performSearch);
    
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    // Reset quand le modal se ferme
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            input.value = '';
            hideAllStates();
            clearAllResults();
        });
    }
    
    // Test d'affichage forcé pour debug
    window.testModalDisplay = function() {
        console.log('🧪 Test d\'affichage forcé des tableaux');
        
        // Données de test
        const testData = {
            clients: [
                { id: 1, nom: 'Test', prenom: 'Client', telephone: '0123456789' }
            ],
            reparations: [
                { id: 1, client_nom: 'Test', client_prenom: 'Client', modele_appareil: 'iPhone 13', probleme_declare: 'Écran cassé', statut: 'En cours' }
            ],
            commandes: [
                { id: 1, reparation_id: 1, piece_nom: 'Écran iPhone 13', statut: 'Commandée' }
            ]
        };
        
        displayResults(testData);
    };
    
    console.log('✅ Recherche Modal : Événements configurés');
    console.log('💡 Tapez window.testModalDisplay() dans la console pour tester l\'affichage');
}); 