<!-- MODAL DE RECHERCHE UNIVERSEL - VERSION UNIFIÉE -->
<div class="modal fade" id="rechercheModal" tabindex="-1" aria-labelledby="rechercheModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="rechercheModalLabel">
                    <i class="fas fa-search me-2"></i>
                    Recherche Universelle
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            
            <!-- Body -->
            <div class="modal-body">
                <!-- Zone de recherche -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-lg" id="rechercheInput" 
                               placeholder="Rechercher par nom, téléphone, appareil, problème ou pièce...">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary btn-lg w-100" id="rechercheBtn">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                    </div>
                </div>
                
                <!-- Zone de chargement -->
                <div id="rechercheLoading" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Recherche en cours...</span>
                    </div>
                    <h5 class="text-primary mt-3">Recherche en cours...</h5>
                    <p class="text-muted">Analyse des données dans toutes les catégories</p>
                </div>
                
                <!-- Boutons onglets -->
                <div id="rechercheBtns" class="mb-3" style="display: none;">
                    <button type="button" class="btn btn-primary me-2" id="btn-clients" onclick="showTab('clients')">
                        <i class="fas fa-users me-2"></i>
                        Clients
                        <span class="badge bg-light text-dark ms-1" id="clientsCount">0</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary me-2" id="btn-reparations" onclick="showTab('reparations')">
                        <i class="fas fa-tools me-2"></i>
                        Réparations
                        <span class="badge bg-primary ms-1" id="reparationsCount">0</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btn-commandes" onclick="showTab('commandes')">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Commandes
                        <span class="badge bg-primary ms-1" id="commandesCount">0</span>
                    </button>
                </div>
                
                <!-- Résultats Clients -->
                <div id="clients-results" class="result-container" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                Clients trouvés
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user me-1"></i>Client</th>
                                            <th><i class="fas fa-phone me-1"></i>Téléphone</th>
                                            <th><i class="fas fa-envelope me-1"></i>Email</th>
                                            <th><i class="fas fa-calendar me-1"></i>Dernière visite</th>
                                            <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="clientsTableBody">
                                        <!-- Résultats générés dynamiquement -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Résultats Réparations -->
                <div id="reparations-results" class="result-container" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-tools me-2"></i>
                                Réparations trouvées
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                            <th><i class="fas fa-user me-1"></i>Client</th>
                                            <th><i class="fas fa-mobile-alt me-1"></i>Appareil</th>
                                            <th><i class="fas fa-exclamation-triangle me-1"></i>Problème</th>
                                            <th><i class="fas fa-clock me-1"></i>Statut</th>
                                            <th><i class="fas fa-calendar me-1"></i>Date</th>
                                            <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reparationsTableBody">
                                        <!-- Résultats générés dynamiquement -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Résultats Commandes -->
                <div id="commandes-results" class="result-container" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Commandes trouvées
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                            <th><i class="fas fa-box me-1"></i>Pièce</th>
                                            <th><i class="fas fa-mobile-alt me-1"></i>Appareil</th>
                                            <th><i class="fas fa-user me-1"></i>Client</th>
                                            <th><i class="fas fa-truck me-1"></i>Fournisseur</th>
                                            <th><i class="fas fa-info-circle me-1"></i>Statut</th>
                                            <th><i class="fas fa-calendar me-1"></i>Date</th>
                                            <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="commandesTableBody">
                                        <!-- Résultats générés dynamiquement -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Message aucun résultat -->
                <div id="rechercheEmpty" class="text-center" style="display: none;">
                    <div class="display-4 text-muted mb-3">
                        <i class="fas fa-search-minus"></i>
                    </div>
                    <h5 class="text-muted mb-2">Aucun résultat trouvé</h5>
                    <p class="lead text-muted">
                        Essayez avec d'autres mots-clés ou vérifiez l'orthographe
                    </p>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Conseils : Utilisez des mots partiels, numéros de téléphone, ou noms d'appareils
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles CSS -->
<style>
.action-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 15px;
    transition: all 0.3s ease;
    border: none;
    margin: 0 0.1rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.action-btn.btn-primary {
    background: linear-gradient(135deg, #4361ee, #6178f1);
}

.action-btn.btn-success {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.action-btn.btn-warning {
    background: linear-gradient(135deg, #f1c40f, #f39c12);
}

.action-btn.btn-info {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.phone-number {
    font-family: monospace;
    font-weight: 600;
    color: #4361ee;
}

.device-info {
    font-size: 0.9rem;
    color: #6c757d;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Animation pour les compteurs */
@keyframes pulse-counter {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.badge-updated {
    animation: pulse-counter 0.5s ease-in-out;
}

/* Animation des lignes de tableau */
.table tbody tr {
    position: relative;
    overflow: hidden;
}

.table tbody tr::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(67, 97, 238, 0.1), transparent);
    transition: left 0.5s ease;
}

.table tbody tr:hover::before {
    left: 100%;
}
</style>

<!-- Script JavaScript unifié -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Modal Recherche Universel : Initialisation...');
    
    // Éléments du DOM
    const modal = document.getElementById('rechercheModal');
    const input = document.getElementById('rechercheInput');
    const btn = document.getElementById('rechercheBtn');
    const loading = document.getElementById('rechercheLoading');
    const btnContainer = document.getElementById('rechercheBtns');
    const empty = document.getElementById('rechercheEmpty');
    
    // Compteurs
    const clientsCount = document.getElementById('clientsCount');
    const reparationsCount = document.getElementById('reparationsCount');
    const commandesCount = document.getElementById('commandesCount');
    
    // Conteneurs de résultats
    const clientsResults = document.getElementById('clients-results');
    const reparationsResults = document.getElementById('reparations-results');
    const commandesResults = document.getElementById('commandes-results');
    
    // Corps des tableaux
    const clientsTableBody = document.getElementById('clientsTableBody');
    const reparationsTableBody = document.getElementById('reparationsTableBody');
    const commandesTableBody = document.getElementById('commandesTableBody');
    
    // Variables globales
    let currentTab = 'clients';
    let searchResults = {};
    
    // Vérifier que tous les éléments existent
    console.log('🔍 Vérification des éléments DOM:');
    console.log('  - modal:', !!modal);
    console.log('  - input:', !!input);
    console.log('  - btn:', !!btn);
    console.log('  - loading:', !!loading);
    console.log('  - clientsTableBody:', !!clientsTableBody);
    
    if (!modal || !input || !btn) {
        console.error('❌ Éléments critiques manquants pour le modal de recherche');
        return;
    }
    
    console.log('✅ Modal de recherche universel initialisé avec succès');
    
    // Fonction pour cacher tous les états
    function hideAllStates() {
        if (loading) loading.style.display = 'none';
        if (btnContainer) btnContainer.style.display = 'none';
        if (empty) empty.style.display = 'none';
        if (clientsResults) clientsResults.style.display = 'none';
        if (reparationsResults) reparationsResults.style.display = 'none';
        if (commandesResults) commandesResults.style.display = 'none';
    }
    
    // Fonction pour afficher le chargement
    function showLoading() {
        hideAllStates();
        if (loading) loading.style.display = 'block';
    }
    
    // Fonction pour afficher les résultats vides
    function showEmpty() {
        hideAllStates();
        if (empty) empty.style.display = 'block';
    }
    
    // Fonction pour mettre à jour les compteurs
    function updateCounters(clients, reparations, commandes) {
        if (clientsCount) clientsCount.textContent = clients || 0;
        if (reparationsCount) reparationsCount.textContent = reparations || 0;
        if (commandesCount) commandesCount.textContent = commandes || 0;
    }
    
    // Fonction pour afficher un onglet
    window.showTab = function(tab) {
        currentTab = tab;
        
        // Mettre à jour les boutons
        document.querySelectorAll('#rechercheBtns .btn').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        const activeBtn = document.getElementById(`btn-${tab}`);
        if (activeBtn) {
            activeBtn.classList.remove('btn-outline-primary');
            activeBtn.classList.add('btn-primary');
        }
        
        // Cacher tous les conteneurs
        hideAllStates();
        
        // Afficher le conteneur approprié
        const resultContainer = document.getElementById(`${tab}-results`);
        if (resultContainer) {
            resultContainer.style.display = 'block';
        }
        
        if (btnContainer) btnContainer.style.display = 'block';
    };
    
    // Fonction de recherche
    function performSearch() {
        const searchTerm = input.value.trim();
        
        if (searchTerm.length < 2) {
            alert('Veuillez saisir au moins 2 caractères pour la recherche');
            return;
        }
        
        console.log('🔍 Recherche lancée:', searchTerm);
        showLoading();
        
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
            
            if (data.success || data.results) {
                displayResults(data);
            } else {
                console.error('❌ Erreur serveur:', data.error);
                showEmpty();
            }
        })
        .catch(error => {
            console.error('❌ Erreur AJAX:', error);
            showEmpty();
        });
    }
    
    // Fonction pour afficher les résultats
    function displayResults(data) {
        const results = data.results || data;
        
        searchResults = {
            clients: results.clients || [],
            reparations: results.reparations || [],
            commandes: results.commandes || []
        };
        
        // Mettre à jour les compteurs
        updateCounters(
            searchResults.clients.length,
            searchResults.reparations.length,
            searchResults.commandes.length
        );
        
        // Remplir les tableaux
        fillClientsTable(searchResults.clients);
        fillReparationsTable(searchResults.reparations);
        fillCommandesTable(searchResults.commandes);
        
        // Déterminer quel onglet afficher
        let tabToShow = 'clients';
        if (searchResults.reparations.length > searchResults.clients.length && 
            searchResults.reparations.length > searchResults.commandes.length) {
            tabToShow = 'reparations';
        } else if (searchResults.commandes.length > searchResults.clients.length && 
                   searchResults.commandes.length > searchResults.reparations.length) {
            tabToShow = 'commandes';
        }
        
        // Afficher les résultats
        if (btnContainer) btnContainer.style.display = 'block';
        showTab(tabToShow);
    }
    
    // Fonction pour remplir le tableau des clients
    function fillClientsTable(clients) {
        if (!clientsTableBody) return;
        
        clientsTableBody.innerHTML = '';
        
        clients.forEach(client => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${client.nom} ${client.prenom}</strong></td>
                <td><span class="phone-number">${formatPhoneNumber(client.telephone)}</span></td>
                <td>${client.email || '-'}</td>
                <td>${client.derniere_visite || '-'}</td>
                <td>
                    <button class="action-btn btn-primary" onclick="voirClient(${client.id})" title="Voir le client">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn btn-info" onclick="ajouterReparation(${client.id})" title="Nouvelle réparation">
                        <i class="fas fa-plus"></i>
                    </button>
                </td>
            `;
            clientsTableBody.appendChild(row);
        });
    }
    
    // Fonction pour remplir le tableau des réparations
    function fillReparationsTable(reparations) {
        if (!reparationsTableBody) return;
        
        reparationsTableBody.innerHTML = '';
        
        reparations.forEach(reparation => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>#${reparation.id}</strong></td>
                <td>${reparation.client_nom || '-'}</td>
                <td><span class="device-info">${reparation.appareil || '-'}</span></td>
                <td>${reparation.probleme || '-'}</td>
                <td><span class="status-badge badge bg-${getStatusColor(reparation.statut)}">${reparation.statut}</span></td>
                <td>${formatDate(reparation.date_reception)}</td>
                <td>
                    <button class="action-btn btn-primary" onclick="voirReparation(${reparation.id})" title="Voir la réparation">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn btn-success" onclick="imprimerEtiquette(${reparation.id})" title="Imprimer étiquette">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            `;
            reparationsTableBody.appendChild(row);
        });
    }
    
    // Fonction pour remplir le tableau des commandes
    function fillCommandesTable(commandes) {
        if (!commandesTableBody) return;
        
        commandesTableBody.innerHTML = '';
        
        commandes.forEach(commande => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>#${commande.id}</strong></td>
                <td>${commande.piece || '-'}</td>
                <td><span class="device-info">${commande.appareil || '-'}</span></td>
                <td>${commande.client_nom || '-'}</td>
                <td>${commande.fournisseur || '-'}</td>
                <td><span class="status-badge badge bg-${getStatusColor(commande.statut)}">${commande.statut}</span></td>
                <td>${formatDate(commande.date_commande)}</td>
                <td>
                    <button class="action-btn btn-primary" onclick="voirCommande(${commande.id})" title="Voir la commande">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn btn-warning" onclick="modifierCommande(${commande.id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            `;
            commandesTableBody.appendChild(row);
        });
    }
    
    // Fonctions utilitaires
    function formatPhoneNumber(phone) {
        if (!phone) return '';
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 10) {
            return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1.$2.$3.$4.$5');
        }
        return phone;
    }
    
    function formatDate(date) {
        if (!date) return '-';
        const d = new Date(date);
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
    }
    
    function getStatusColor(status) {
        const statusMap = {
            'en_attente': 'warning',
            'en_cours': 'info',
            'termine': 'success',
            'livre': 'primary',
            'annule': 'danger',
            'commande': 'info',
            'recu': 'success'
        };
        return statusMap[status] || 'secondary';
    }
    
    // Fonctions de navigation
    window.voirClient = function(id) {
        window.location.href = `?page=clients&action=voir&id=${id}`;
    };
    
    window.ajouterReparation = function(clientId) {
        window.location.href = `?page=ajouter_reparation&client_id=${clientId}`;
    };
    
    window.voirReparation = function(id) {
        window.location.href = `?page=reparations&action=voir&id=${id}`;
    };
    
    window.imprimerEtiquette = function(id) {
        window.open(`imprimer_etiquette.php?id=${id}`, '_blank');
    };
    
    window.voirCommande = function(id) {
        window.location.href = `?page=commandes&action=voir&id=${id}`;
    };
    
    window.modifierCommande = function(id) {
        window.location.href = `?page=commandes&action=modifier&id=${id}`;
    };
    
    // Événements
    if (btn) {
        btn.addEventListener('click', performSearch);
    }
    
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }
    
    // Reset quand le modal se ferme
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            input.value = '';
            hideAllStates();
            searchResults = {};
        });
    }
});
</script> 