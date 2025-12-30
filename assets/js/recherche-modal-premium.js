/**
 * Script JavaScript pour le Modal de Recherche Premium
 * Compatible avec la structure du modal existant et gestion des onglets Bootstrap
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 Recherche Modal Premium : Initialisation...');
    
    // Variables globales
    let currentResults = {
        clients: []
        reparations: []
        commandes: []
    };
    
    // Éléments du DOM
    const modal = document.getElementById('rechercheModal');
    const rechercheInput = document.getElementById('rechercheInput');
    const rechercheBtn = document.getElementById('rechercheBtn');
    const clientsTab = document.getElementById('clients-tab');
    const reparationsTab = document.getElementById('reparations-tab');
    const commandesTab = document.getElementById('commandes-tab');
    const clientsTableBody = document.getElementById('clients-results').querySelector('tbody');
    const reparationsTableBody = document.getElementById('reparations-results').querySelector('tbody');
    const commandesTableBody = document.getElementById('commandes-results').querySelector('tbody');
    const rechercheEmpty = document.getElementById('rechercheEmpty');
    
    // Vérification des éléments DOM
    console.log('🔍 Vérification des éléments DOM:');
    console.log('  - modal:', !!modal);
    console.log('  - input:', !!rechercheInput);
    console.log('  - btn:', !!rechercheBtn);
    console.log('  - clientsTab:', !!clientsTab);
    console.log('  - reparationsTab:', !!reparationsTab);
    console.log('  - commandesTab:', !!commandesTab);
    console.log('  - clientsTableBody:', !!clientsTableBody);
    console.log('  - reparationsTableBody:', !!reparationsTableBody);
    console.log('  - commandesTableBody:', !!commandesTableBody);
    
    if (!modal || !rechercheInput || !rechercheBtn || !clientsTab || !reparationsTab || !commandesTab) {
        console.error('❌ Éléments manquants dans le DOM');
        return;
    }
    
    console.log('✅ Recherche Premium : Tous les éléments trouvés');
    
    // Fonction pour nettoyer les résultats
    function clearResults() {
        if (clientsTableBody) clientsTableBody.innerHTML = '';
        if (reparationsTableBody) reparationsTableBody.innerHTML = '';
        if (commandesTableBody) commandesTableBody.innerHTML = '';
        
        // Masquer tous les conteneurs de résultats
        ['clients', 'reparations', 'commandes'].forEach(tab => {
            const content = document.getElementById(tab + '-results');
            if (content) {
                content.style.display = 'none';
            }
        
        // Réinitialiser les compteurs
        updateCounter('clients', 0);
        updateCounter('reparations', 0);
        updateCounter('commandes', 0);
    }
    
    // Fonction pour mettre à jour les compteurs
    function updateCounter(type, count) {
        const tab = document.getElementById(type + '-tab');
        if (tab) {
            const badge = tab.querySelector('.badge');
            if (badge) {
                badge.textContent = count;
            }
        }
    }
    
    // Fonction pour gérer l'activation automatique des onglets
    function activateTabWithMostResults() {
        const counts = {
            clients: currentResults.clients.length
            reparations: currentResults.reparations.length
            commandes: currentResults.commandes.length
        };
        
        let maxCount = 0;
        let activeTab = "clients";
        
        Object.entries(counts).forEach(([tab, count]) => {
            if (count > maxCount) {
                maxCount = count;
                activeTab = tab;
            }
        
        if (maxCount > 0) {
            ["clients", "reparations", "commandes"].forEach(tab => {
                const btn = document.getElementById(tab + "-tab");
                if (btn) {
                    btn.classList.remove("btn-primary");
                    btn.classList.add("btn-outline-primary");
                }
            
            const activeBtn = document.getElementById(activeTab + "-tab");
            if (activeBtn) {
                activeBtn.classList.remove("btn-outline-primary");
                activeBtn.classList.add("btn-primary");
            }
            
            showTabContent(activeTab);
        }
    }
    
    function showTabContent(activeTab) {
        ["clients", "reparations", "commandes"].forEach(tab => {
            const content = document.getElementById(tab + "-results");
            if (content) {
                content.style.display = "none";
            }
        
        const activeContent = document.getElementById(activeTab + "-results");
        if (activeContent) {
            activeContent.style.display = "block";
        }
    }
    
    // Fonction pour formater le téléphone
    function formatPhone(phone) {
        if (!phone) return 'Non renseigné';
        return phone.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
    }
    
    // Fonction pour obtenir la couleur du statut
    function getStatusColor(statut) {
        switch(statut) {
            case 'en_cours':
            case 'EN_COURS':
                return 'warning';
            case 'termine':
            case 'TERMINE':
                return 'success';
            case 'annule':
            case 'ANNULE':
                return 'danger';
            default:
                return 'primary';
        }
    }
    
    // Fonction pour afficher les clients
    function displayClients(clients) {
        console.log('📋 Affichage des clients:', clients.length);
        
        if (!clientsTableBody) return;
        
        clientsTableBody.innerHTML = '';
        
        clients.forEach(client => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="fw-bold">${client.nom || 'Client non renseigné'}</div>
                    <small class="text-muted">${formatPhone(client.telephone)}</small>
                </td>
                <td>
                    <div class="fw-bold">${client.email || 'Email non renseigné'}</div>
                    <small class="text-muted">${client.adresse || 'Adresse non renseignée'}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="voirClient(${client.id})">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                        <button class="btn btn-outline-success" onclick="ajouterReparation(${client.id})">
                            <i class="fas fa-plus"></i> Réparation
                        </button>
                    </div>
                </td>
            `;
            clientsTableBody.appendChild(row);
        
        updateCounter('clients', clients.length);
        console.log('✅ Clients affichés avec succès');
    }
    
    // Fonction pour afficher les réparations
    function displayReparations(reparations) {
        console.log('🔧 Affichage des réparations:', reparations.length);
        
        if (!reparationsTableBody) return;
        
        reparationsTableBody.innerHTML = '';
        
        reparations.forEach(reparation => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="fw-bold">#${reparation.id || 'N/A'}</div>
                </td>
                <td>
                    <div class="fw-bold">${reparation.client || 'Client non renseigné'}</div>
                    <small class="text-muted">${formatPhone(reparation.telephone)}</small>
                </td>
                <td>
                    <div class="fw-bold">${reparation.appareil || 'Type non spécifié'}</div>
                    <small class="text-muted">${reparation.marque || ''} ${reparation.modele || ''}</small>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 200px;" title="${reparation.probleme || 'Problème non spécifié'}">
                        ${reparation.probleme || 'Problème non spécifié'}
                    </div>
                </td>
                <td>
                    <span class="badge bg-${getStatusColor(reparation.statut)}">${reparation.statut || 'Statut non défini'}</span>
                </td>
                <td>
                    <div class="fw-bold">${reparation.date || 'Non datée'}</div>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="voirReparation(${reparation.id})">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                        <button class="btn btn-outline-info" onclick="imprimerEtiquette(${reparation.id})">
                            <i class="fas fa-print"></i> Étiquette
                        </button>
                    </div>
                </td>
            `;
            reparationsTableBody.appendChild(row);
        
        updateCounter('reparations', reparations.length);
        console.log('✅ Réparations affichées avec succès');
    }
    
    // Fonction pour afficher les commandes
    function displayCommandes(commandes) {
        console.log('📦 Affichage des commandes:', commandes.length);
        
        if (!commandesTableBody) return;
        
        commandesTableBody.innerHTML = '';
        
        commandes.forEach(commande => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="fw-bold">${commande.client || 'Client non renseigné'}</div>
                    <small class="text-muted">Commande</small>
                </td>
                <td>
                    <div class="fw-bold">${commande.piece || 'Pièce non spécifiée'}</div>
                    <small class="text-muted">${commande.quantite || 1} x ${commande.prix || 'Prix non défini'}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="voirCommande(${commande.id})">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                        <button class="btn btn-outline-secondary" onclick="modifierCommande(${commande.id})">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                    </div>
                </td>
            `;
            commandesTableBody.appendChild(row);
        
        updateCounter('commandes', commandes.length);
        console.log('✅ Commandes affichées avec succès');
    }
    
    // Fonction pour afficher les résultats
    function displayResults(data) {
        console.log('📊 Affichage des résultats:', data);
        
        if (!data || !data.success) {
            console.error('❌ Données invalides:', data);
            return;
        }
        
        // Sauvegarder les résultats
        currentResults = {
            clients: data.clients || []
            reparations: data.reparations || []
            commandes: data.commandes || []
        };
        
        // Afficher les résultats
        displayClients(currentResults.clients);
        displayReparations(currentResults.reparations);
        displayCommandes(currentResults.commandes);
        
        // Activer l'onglet avec le plus de résultats
        activateTabWithMostResults();
        
        // Masquer le message vide
        if (rechercheEmpty) {
            rechercheEmpty.style.display = 'none';
        }
    }
    
    // Fonction de recherche
    function performSearch() {
        console.log('🚀 performSearch appelée');
        
        const searchTerm = rechercheInput.value.trim();
        console.log('🔍 Terme:', searchTerm);
        
        if (searchTerm.length < 2) {
            console.log('⚠️ Terme de recherche trop court');
            alert('Veuillez saisir au moins 2 caractères');
            return;
        }
        
        console.log('✅ Recherche lancée pour:', searchTerm);
        
        // Effacer les résultats précédents
        clearResults();
        
        // Requête AJAX
        fetch('ajax/recherche_universelle_complete.php', {
            method: 'POST'
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
            body: `terme=${encodeURIComponent(searchTerm)}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('📊 Résultats:', data);
            displayResults(data);
        })
        .catch(error => {
            console.error('❌ Erreur:', error);
            alert('Erreur lors de la recherche');
    }
    
    // Event listeners
    const btn = document.getElementById('rechercheBtn');
    const input = document.getElementById('rechercheInput');
    
    if (btn) {
        btn.addEventListener('click', performSearch);
    }
    
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
    }
    
    // Event listeners pour les onglets
    ["clients", "reparations", "commandes"].forEach(tabName => {
        const tabBtn = document.getElementById(tabName + "-tab");
        if (tabBtn) {
            tabBtn.addEventListener("click", function() {
                ["clients", "reparations", "commandes"].forEach(tab => {
                    const btn = document.getElementById(tab + "-tab");
                    if (btn) {
                        btn.classList.remove("btn-primary");
                        btn.classList.add("btn-outline-primary");
                    }
                
                this.classList.remove("btn-outline-primary");
                this.classList.add("btn-primary");
                
                showTabContent(tabName);
        }
    
    console.log('🚀 Recherche Modal Premium initialisé avec succès');

// Fonctions globales pour les actions
function voirClient(id) {
    window.location.href = `/pages/voir_client.php?id=${id}`;
}

function ajouterReparation(clientId) {
    window.location.href = `/pages/ajouter_reparation.php?client_id=${clientId}`;
}

function voirReparation(id) {
    window.location.href = `/pages/voir_reparation.php?id=${id}`;
}

function imprimerEtiquette(id) {
    window.open(`/pages/imprimer_etiquette.php?id=${id}`, '_blank');
}

function voirCommande(id) {
    window.location.href = `/pages/voir_commande.php?id=${id}`;
}

function modifierCommande(id) {
    window.location.href = `/pages/modifier_commande.php?id=${id}`;
} 