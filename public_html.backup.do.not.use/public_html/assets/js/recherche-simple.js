// Script simple pour la recherche universelle avec onglets
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du modal (IDs corrigés pour correspondre à quick-actions.php)
    const input = document.getElementById('searchInput'); // Corrigé : rechercheInput -> searchInput
    const button = document.getElementById('btnSearch'); // Corrigé : rechercheBtn -> btnSearch
    const loading = document.querySelector('.search-status'); // Indicateur de chargement
    const results = document.getElementById('searchTabContent'); // Conteneur des résultats
    const empty = document.querySelector('.search-empty'); // Message vide
    
    // Conteneurs des résultats (IDs corrigés)
    const clientsResults = document.getElementById('clientsResults'); // Corrigé : clientsTableBody -> clientsResults
    const reparationsResults = document.getElementById('reparationsResults'); // Corrigé : reparationsTableBody -> reparationsResults
    const commandesResults = document.getElementById('commandesResults'); // Corrigé : commandesTableBody -> commandesResults
    
    // Compteurs (IDs corrigés)
    const clientsCount = document.getElementById('count-clients'); // Corrigé : clientsCount -> count-clients
    const reparationsCount = document.getElementById('count-reparations'); // Corrigé : reparationsCount -> count-reparations
    const commandesCount = document.getElementById('count-commandes'); // Corrigé : commandesCount -> count-commandes
    
    if (!input || !button || !clientsResults || !reparationsResults || !commandesResults || 
        !clientsCount || !reparationsCount || !commandesCount) {
        console.error('❌ Recherche Simple : Éléments manquants');
        return;
    }
    
    // Test rapide d'injection HTML pour vérifier que les éléments fonctionnent
    if (clientsResults) {
        clientsResults.innerHTML = '<div style="background: red; color: white; padding: 10px;">TEST HTML INJECTION</div>';
        setTimeout(() => {
            clientsResults.innerHTML = '';
        }, 1000);
    }
    
    // Fonction pour cacher tous les états
    function hideAllStates() {
        if (loading) loading.classList.add('d-none');
        if (results) results.style.display = 'block';
        if (empty) empty.style.display = 'none';
    }
    
    // Fonction pour vider tous les résultats
    function clearAllResults() {
        clientsResults.innerHTML = '';
        reparationsResults.innerHTML = '';
        commandesResults.innerHTML = '';
        
        clientsCount.textContent = '0';
        reparationsCount.textContent = '0';
        commandesCount.textContent = '0';
    }
    
    // Fonction pour effectuer la recherche
    function performSearch() {
        const terme = input.value.trim();
        
        if (!terme || terme.length < 2) {
            alert('Veuillez saisir au moins 2 caractères pour la recherche');
            return;
        }
        
        // Afficher le chargement
        hideAllStates();
        clearAllResults();
        if (loading) loading.classList.remove('d-none');
        
        // Effectuer la recherche AJAX
        fetch('ajax/recherche-simple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'terme=' + encodeURIComponent(terme)
        })
        .then(response => response.json())
        .then(data => {
            hideAllStates();
            
            if (data.success && data.resultats && data.resultats.length > 0) {
                // Distribuer les résultats par type
                displayResults(data.resultats);
                if (results) results.style.display = 'block';
            } else {
                // Aucun résultat
                if (empty) empty.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('❌ Erreur recherche :', error);
            hideAllStates();
            alert('Erreur lors de la recherche. Veuillez réessayer.');
        });
    }
    
    // Fonction pour afficher les résultats dans les onglets appropriés
    function displayResults(resultats) {
        // Grouper les résultats par type
        const groupes = {
            clients: [],
            reparations: [],
            commandes: []
        };
        
        resultats.forEach(item => {
            if (item.type === 'client') {
                groupes.clients.push(item);
            } else if (item.type === 'reparation') {
                groupes.reparations.push(item);
            } else if (item.type === 'commande') {
                groupes.commandes.push(item);
            }
        });
        
        // Afficher les clients
        displayClients(groupes.clients);
        
        // Afficher les réparations
        displayReparations(groupes.reparations);
        
        // Afficher les commandes
        displayCommandes(groupes.commandes);
        
        // Activer le premier onglet qui a des résultats
        activateFirstNonEmptyTab(groupes);
    }
    
    // Fonction pour afficher les clients
    function displayClients(clients) {
        let html = '';
        
        if (clients.length > 0) {
            html = `
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Nom</th>
                            <th>Téléphone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            clients.forEach(client => {
                html += `
                    <tr>
                        <td><strong>${client.nom}</strong></td>
                        <td>${client.telephone}</td>
                        <td>
                            <a href="pages/clients.php?id=${client.id}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
        } else {
            html = '<p class="text-muted">Aucun client trouvé</p>';
        }
        
        clientsResults.innerHTML = html;
        clientsCount.textContent = clients.length;
    }
    
    // Fonction pour afficher les réparations
    function displayReparations(reparations) {
        let html = '';
        
        if (reparations.length > 0) {
            html = `
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Appareil</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            reparations.forEach(reparation => {
                html += `
                    <tr>
                        <td><strong>#${reparation.id}</strong></td>
                        <td>${reparation.client_nom}</td>
                        <td>${reparation.appareil}</td>
                        <td><span class="badge bg-${getStatutClass(reparation.statut)}">${reparation.statut}</span></td>
                        <td>
                            <a href="pages/reparations.php?id=${reparation.id}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
        } else {
            html = '<p class="text-muted">Aucune réparation trouvée</p>';
        }
        
        reparationsResults.innerHTML = html;
        reparationsCount.textContent = reparations.length;
    }
    
    // Fonction pour afficher les commandes
    function displayCommandes(commandes) {
        let html = '';
        
        if (commandes.length > 0) {
            html = `
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Pièce</th>
                            <th>Client</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            commandes.forEach(commande => {
                html += `
                    <tr>
                        <td><strong>#${commande.id}</strong></td>
                        <td>${commande.piece}</td>
                        <td>${commande.client_nom}</td>
                        <td><span class="badge bg-${getStatutClass(commande.statut)}">${commande.statut}</span></td>
                        <td>
                            <a href="pages/commandes.php?id=${commande.id}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
        } else {
            html = '<p class="text-muted">Aucune commande trouvée</p>';
        }
        
        commandesResults.innerHTML = html;
        commandesCount.textContent = commandes.length;
    }
    
    // Fonction pour déterminer la classe CSS du statut
    function getStatutClass(statut) {
        switch(statut) {
            case 'En attente':
                return 'warning';
            case 'En cours':
                return 'info';
            case 'Terminé':
                return 'success';
            case 'Livré':
                return 'primary';
            default:
                return 'secondary';
        }
    }
    
    // Fonction pour activer le premier onglet avec des résultats
    function activateFirstNonEmptyTab(groupes) {
        // Logique pour activer l'onglet approprié
        if (groupes.clients.length > 0) {
            // Activer l'onglet clients
        } else if (groupes.reparations.length > 0) {
            // Activer l'onglet réparations
        } else if (groupes.commandes.length > 0) {
            // Activer l'onglet commandes
        }
    }
    
    // Gestionnaires d'événements
    button.addEventListener('click', performSearch);
    
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
}); 