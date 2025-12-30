// Script simple pour la recherche universelle avec onglets
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Recherche Simple : Initialisation...');
    
    // Éléments du DOM
    const modal = document.getElementById('rechercheModal');
    const input = document.getElementById('searchInput');
    const btn = document.getElementById('btnSearch');
    const loading = document.querySelector('.search-status');
    const results = document.getElementById('searchTabContent');
    const empty = document.getElementById('rechercheEmpty');
    
    // Onglets et compteurs
    const clientsCount = document.getElementById('count-clients');
    const reparationsCount = document.getElementById('count-reparations');
    const commandesCount = document.getElementById('count-commandes');
    
    // Corps des tableaux
    const clientsTableBody = document.getElementById('clientsResults');
    const reparationsTableBody = document.getElementById('reparationsResults');
    const commandesTableBody = document.getElementById('commandesResults');
    
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
    
    if (!modal || !input || !btn || !loading || !results || 
        !clientsCount || !reparationsCount || !commandesCount ||
        !clientsTableBody || !reparationsTableBody || !commandesTableBody) {
        console.error('❌ Recherche Simple : Éléments manquants dans le DOM');
        console.error('Elements manquants:', {
            modal: !!modal,
            input: !!input, 
            btn: !!btn,
            loading: !!loading,
            results: !!results,
            empty: !!empty,
            clientsCount: !!clientsCount,
            reparationsCount: !!reparationsCount,
            commandesCount: !!commandesCount,
            clientsTableBody: !!clientsTableBody,
            reparationsTableBody: !!reparationsTableBody,
            commandesTableBody: !!commandesTableBody
        });
        return;
    }
    
    console.log('✅ Recherche Simple : Tous les éléments trouvés');
    
    // Test rapide d'injection HTML pour vérifier que les éléments fonctionnent
    console.log('🧪 Test rapide d\'injection HTML...');
    if (clientsTableBody) {
        clientsTableBody.innerHTML = '<div style="background: red; color: white; padding: 10px;">TEST HTML INJECTION</div>';
        setTimeout(() => {
            clientsTableBody.innerHTML = '';
            console.log('🧪 Test HTML terminé - éléments fonctionnels');
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
        clientsTableBody.innerHTML = '';
        reparationsTableBody.innerHTML = '';
        commandesTableBody.innerHTML = '';
        
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
        
        console.log('🔍 Recherche pour :', terme);
        
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
            console.log('📋 Résultats reçus :', data);
            
            hideAllStates();
            
            console.log('📋 Conditions de vérification:');
            console.log('  - data.success:', data.success);
            console.log('  - data.resultats exists:', !!data.resultats);
            console.log('  - data.resultats.length:', data.resultats ? data.resultats.length : 'N/A');
            console.log('  - Condition globale:', !!(data.success && data.resultats && data.resultats.length > 0));
            
            if (data.success && data.resultats && data.resultats.length > 0) {
                console.log('✅ CONDITION REMPLIE - Appel de displayResults');
                // Distribuer les résultats par type
                displayResults(data.resultats);
                if (results) results.style.display = 'block';
            } else {
                console.log('❌ CONDITION NON REMPLIE - Affichage message vide');
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
        console.log('🎯 DISPLAY RESULTS APPELÉE avec:', resultats);
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
        console.log('🔧 displayClients appelée avec:', clients);
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
            html = '<div class="text-center py-4 text-muted">Aucun client trouvé</div>';
        }
        
        console.log('🔧 HTML généré pour clients:', html.length, 'caractères');
        console.log('🔧 clientsTableBody element:', clientsTableBody);
        
        if (clientsTableBody) {
            clientsTableBody.innerHTML = html;
            console.log('✅ HTML injecté dans clientsTableBody');
            console.log('🔧 Parent element display:', getComputedStyle(clientsTableBody.parentElement).display);
        } else {
            console.error('❌ clientsTableBody non trouvé');
        }
        
        if (clientsCount) {
            clientsCount.textContent = clients.length;
            console.log('✅ Compteur clients mis à jour:', clients.length);
        }
        
        // Mettre à jour la couleur du badge
        if (clientsCount) clientsCount.className = clients.length > 0 ? 'badge bg-success ms-2' : 'badge bg-primary ms-2';
    }
    
    // Fonction pour afficher les réparations
    function displayReparations(reparations) {
        let html = '';
        
        if (reparations.length > 0) {
            html = `
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Client</th>
                            <th>Appareil</th>
                            <th>Problème</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            reparations.forEach(reparation => {
                const statutClass = getStatutClass(reparation.statut);
                html += `
                    <tr>
                        <td>${reparation.client}</td>
                        <td>${reparation.appareil}</td>
                        <td>${reparation.probleme}</td>
                        <td><span class="badge ${statutClass}">${reparation.statut}</span></td>
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
            html = '<div class="text-center py-4 text-muted">Aucune réparation trouvée</div>';
        }
        
        if (reparationsTableBody) reparationsTableBody.innerHTML = html;
        if (reparationsCount) reparationsCount.textContent = reparations.length;
        
        // Mettre à jour la couleur du badge
        if (reparationsCount) reparationsCount.className = reparations.length > 0 ? 'badge bg-success ms-2' : 'badge bg-primary ms-2';
    }
    
    // Fonction pour afficher les commandes
    function displayCommandes(commandes) {
        let html = '';
        
        if (commandes.length > 0) {
            html = `
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Réparation</th>
                            <th>Pièce</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            commandes.forEach(commande => {
                const statutClass = getStatutClass(commande.statut);
                html += `
                    <tr>
                        <td>Réparation #${commande.reparation_id}</td>
                        <td>${commande.piece}</td>
                        <td><span class="badge ${statutClass}">${commande.statut}</span></td>
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
            html = '<div class="text-center py-4 text-muted">Aucune commande trouvée</div>';
        }
        
        if (commandesTableBody) commandesTableBody.innerHTML = html;
        if (commandesCount) commandesCount.textContent = commandes.length;
        
        // Mettre à jour la couleur du badge
        if (commandesCount) commandesCount.className = commandes.length > 0 ? 'badge bg-success ms-2' : 'badge bg-primary ms-2';
    }
    
    // Fonction pour déterminer la classe CSS du statut
    function getStatutClass(statut) {
        const statutLower = statut.toLowerCase();
        
        if (statutLower.includes('termine') || statutLower.includes('complete') || statutLower.includes('fini')) {
            return 'bg-success';
        } else if (statutLower.includes('en_cours') || statutLower.includes('progress') || statutLower.includes('en cours')) {
            return 'bg-warning';
        } else if (statutLower.includes('attente') || statutLower.includes('pending')) {
            return 'bg-info';
        } else if (statutLower.includes('annule') || statutLower.includes('cancelled')) {
            return 'bg-danger';
        } else {
            return 'bg-secondary';
        }
    }
    
    // Fonction pour activer le premier onglet non vide
    function activateFirstNonEmptyTab(groupes) {
        // Réinitialiser tous les onglets
        document.querySelectorAll('#searchTabs .nav-link').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });
        
        // Activer le premier onglet qui a des résultats
        if (groupes.clients.length > 0) {
            const clientsTab = document.getElementById('clients-tab');
            const clientsPane = document.getElementById('clients-container');
            if (clientsTab) clientsTab.classList.add('active');
            if (clientsPane) clientsPane.classList.add('show', 'active');
        } else if (groupes.reparations.length > 0) {
            const reparationsTab = document.getElementById('reparations-tab');
            const reparationsPane = document.getElementById('reparations-container');
            if (reparationsTab) reparationsTab.classList.add('active');
            if (reparationsPane) reparationsPane.classList.add('show', 'active');
        } else if (groupes.commandes.length > 0) {
            const commandesTab = document.getElementById('commandes-tab');
            const commandesPane = document.getElementById('commandes-container');
            if (commandesTab) commandesTab.classList.add('active');
            if (commandesPane) commandesPane.classList.add('show', 'active');
        } else {
            // Si aucun résultat, activer l'onglet clients par défaut
            const clientsTab = document.getElementById('clients-tab');
            const clientsPane = document.getElementById('clients-container');
            if (clientsTab) clientsTab.classList.add('active');
            if (clientsPane) clientsPane.classList.add('show', 'active');
        }
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
    
    console.log('✅ Recherche Simple : Événements configurés');
}); 