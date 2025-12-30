// Script JavaScript correct pour le modal de recherche universelle
// Version 2 - Avec données de la vraie base de données

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Recherche Modal V2 : Initialisation...');
    
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
    
    console.log('✅ Recherche Modal V2 : Tous les éléments trouvés');
    
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
    
    // Fonction pour formater les dates
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
    
    // Fonction pour formater le téléphone
    function formatTelephone(telephone) {
        if (!telephone) return '';
        // Formater en XX.XX.XX.XX.XX si 10 chiffres
        if (telephone.length === 10) {
            return telephone.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1.$2.$3.$4.$5');
        }
        return telephone;
    }
    
    // Fonction pour afficher les résultats
    function displayResults(data) {
        console.log('📊 Affichage des résultats de la base de données:', data);
        
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
                row.className = 'search-result-row';
                row.innerHTML = `
                    <td>
                        <strong>${client.nom} ${client.prenom}</strong>
                        ${client.email ? `<br><small class="text-muted">${client.email}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge bg-info">${formatTelephone(client.telephone)}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary rounded-pill" onclick="window.location.href='index.php?page=details_client&id=${client.id}'">
                            <i class="fas fa-eye me-1"></i> Voir
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
                row.className = 'search-result-row';
                row.innerHTML = `
                    <td>
                        <strong>${reparation.client_nom}</strong>
                        ${reparation.telephone ? `<br><small class="text-muted">${formatTelephone(reparation.telephone)}</small>` : ''}
                    </td>
                    <td>
                        ${reparation.type_appareil || reparation.appareil || ''}
                        ${reparation.modele ? `<br><small class="text-muted">${reparation.modele}</small>` : ''}
                    </td>
                    <td>
                        <span class="text-truncate" style="max-width: 200px; display: block;" title="${reparation.probleme}">
                            ${reparation.probleme ? reparation.probleme.substring(0, 50) + (reparation.probleme.length > 50 ? '...' : '') : ''}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-${getStatusColor(reparation.statut)}">${reparation.statut}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary rounded-pill" onclick="window.location.href='index.php?page=details_reparation&id=${reparation.id}'">
                            <i class="fas fa-eye me-1"></i> Voir
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
                row.className = 'search-result-row';
                row.innerHTML = `
                    <td>
                        <strong>Réparation #${commande.reparation_id || 'N/A'}</strong>
                        ${commande.client_nom ? `<br><small class="text-muted">${commande.client_nom}</small>` : ''}
                    </td>
                    <td>
                        ${commande.piece_nom || 'Pièce non spécifiée'}
                        ${commande.type_appareil ? `<br><small class="text-muted">Pour ${commande.type_appareil}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge bg-${getStatusColor(commande.statut)}">${commande.statut || 'Non défini'}</span>
                        ${commande.fournisseur_nom ? `<br><small class="text-muted">${commande.fournisseur_nom}</small>` : ''}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary rounded-pill" onclick="window.location.href='index.php?page=details_commande&id=${commande.id}'">
                            <i class="fas fa-eye me-1"></i> Voir
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
                
                // Activer l'onglet avec le plus de résultats
                activateTabWithMostResults(data);
            }, 100);
        } else {
            empty.style.display = 'block';
        }
    }
    
    // Fonction pour activer l'onglet avec le plus de résultats
    function activateTabWithMostResults(data) {
        const clientsLength = data.clients?.length || 0;
        const reparationsLength = data.reparations?.length || 0;
        const commandesLength = data.commandes?.length || 0;
        
        // Désactiver tous les onglets
        document.querySelectorAll('#resultTabs .nav-link').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('#resultTabContent .tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
            pane.style.display = 'none';
        });
        
        let activeTabId, activePaneId;
        
        if (clientsLength >= reparationsLength && clientsLength >= commandesLength) {
            activeTabId = 'clients-tab';
            activePaneId = 'clients-pane';
        } else if (reparationsLength >= commandesLength) {
            activeTabId = 'reparations-tab';
            activePaneId = 'reparations-pane';
        } else {
            activeTabId = 'commandes-tab';
            activePaneId = 'commandes-pane';
        }
        
        // Activer l'onglet choisi
        const activeTab = document.getElementById(activeTabId);
        const activePane = document.getElementById(activePaneId);
        
        if (activeTab && activePane) {
            activeTab.classList.add('active');
            activePane.classList.add('show', 'active');
            activePane.style.display = 'block';
            activePane.style.visibility = 'visible';
            activePane.style.opacity = '1';
        }
    }
    
    // Fonction pour obtenir la couleur du statut
    function getStatusColor(statut) {
        if (!statut) return 'secondary';
        
        const statutLower = statut.toLowerCase();
        switch(statutLower) {
            case 'en cours':
            case 'en_cours':
            case 'nouvelle_intervention':
                return 'warning';
            case 'terminé':
            case 'termine':
            case 'livré':
            case 'livre':
            case 'reçue':
            case 'recue':
                return 'success';
            case 'annulé':
            case 'annule':
            case 'annulée':
                return 'danger';
            case 'en attente':
            case 'en_attente':
            case 'commandée':
            case 'commandee':
            case 'en transit':
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
        
        console.log('🔍 Recherche lancée dans la BDD:', searchTerm);
        
        // Afficher le chargement
        hideAllStates();
        loading.style.display = 'block';
        
        // Requête AJAX vers la vraie base de données
        fetch('ajax/recherche_universelle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `terme=${encodeURIComponent(searchTerm)}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('📡 Réponse de la BDD:', data);
            
            if (data.success) {
                displayResults(data);
            } else {
                console.error('❌ Erreur serveur:', data.error);
                hideAllStates();
                empty.style.display = 'block';
                
                // Afficher l'erreur dans le message vide
                const emptyMessage = empty.querySelector('h5');
                const emptyText = empty.querySelector('p');
                if (emptyMessage && emptyText) {
                    emptyMessage.textContent = 'Erreur de recherche';
                    emptyText.textContent = data.error || 'Une erreur est survenue lors de la recherche';
                }
            }
        })
        .catch(error => {
            console.error('❌ Erreur AJAX:', error);
            hideAllStates();
            empty.style.display = 'block';
            
            // Afficher l'erreur dans le message vide
            const emptyMessage = empty.querySelector('h5');
            const emptyText = empty.querySelector('p');
            if (emptyMessage && emptyText) {
                emptyMessage.textContent = 'Erreur de connexion';
                emptyText.textContent = 'Impossible de se connecter au serveur';
            }
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
            
            // Remettre les messages par défaut
            const emptyMessage = empty.querySelector('h5');
            const emptyText = empty.querySelector('p');
            if (emptyMessage && emptyText) {
                emptyMessage.textContent = 'Aucun résultat trouvé';
                emptyText.textContent = 'Essayez avec d\'autres termes de recherche';
            }
        });
    }
    
    // Test d'affichage forcé pour debug (avec données de test)
    window.testModalDisplay = function() {
        console.log('🧪 Test d\'affichage forcé des tableaux avec données BDD simulées');
        
        // Données de test qui simulent la structure de la vraie BDD
        const testData = {
            success: true,
            clients: [
                { id: 1, nom: 'Test', prenom: 'Client', telephone: '0123456789', email: 'test@example.com' }
            ],
            reparations: [
                { 
                    id: 1, 
                    client_nom: 'Test Client', 
                    telephone: '0123456789',
                    type_appareil: 'iPhone', 
                    modele: '13 Pro',
                    appareil: 'iPhone 13 Pro',
                    probleme: 'Écran cassé', 
                    statut: 'En cours' 
                }
            ],
            commandes: [
                { 
                    id: 1, 
                    reparation_id: 1, 
                    client_nom: 'Test Client',
                    piece_nom: 'Écran iPhone 13 Pro', 
                    statut: 'Commandée',
                    fournisseur_nom: 'Fournisseur Test',
                    type_appareil: 'iPhone'
                }
            ]
        };
        
        displayResults(testData);
    };
    
    console.log('✅ Recherche Modal V2 : Événements configurés');
    console.log('💡 Tapez window.testModalDisplay() dans la console pour tester l\'affichage');
    console.log('🔄 Maintenant connecté à la vraie base de données !');
}); 