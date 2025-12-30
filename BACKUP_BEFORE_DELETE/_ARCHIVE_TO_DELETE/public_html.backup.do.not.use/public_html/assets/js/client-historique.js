/**
 * Gestion de la recherche de client et de l'affichage de son historique
 * Compatible avec la recherche avancée
 */

// Initialisation lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Injecter le CSS pour corriger les problèmes d'affichage
    injectFixCSS();
    
    // Configurer les boutons d'action
    setupActionButtons();
    
    // Adaptation pour vérifier si nous sommes en mode recherche avancée
    if (document.getElementById('recherche_avancee')) {
        // Mode recherche avancée détecté - pas de diagnostic d'anciens éléments nécessaire
    } else {
        // Test d'initialisation pour vérifier les éléments de l'ancienne recherche
        runDiagnostics();
        
        // Configurer l'ancienne recherche de client si elle existe
        setupClientHistorySearch();
    }
});

// Injecter du CSS pour corriger les problèmes d'affichage
function injectFixCSS() {
    const style = document.createElement('style');
    style.textContent = `
        /* Fix pour les résultats de recherche client */
        #resultats_recherche_client {
            display: block !important;
        }
        
        #resultats_recherche_client.d-none {
            display: block !important;
        }
        
        /* Amélioration de la visibilité des lignes de résultat */
        #liste_clients_recherche tr {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        #liste_clients_recherche tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        
        /* Style pour les messages d'erreur */
        .text-danger {
            color: #dc3545 !important;
        }
    `;
    document.head.appendChild(style);
}

// Exécuter des tests de diagnostic pour l'ancienne UI
function runDiagnostics() {
    // Éléments essentiels à vérifier
    const elementsToCheck = [
        { id: 'recherche_client_historique', type: 'input', name: 'Champ de recherche' },
        { id: 'btn-recherche-client-historique', type: 'button', name: 'Bouton de recherche' },
        { id: 'resultats_recherche_client', type: 'div', name: 'Conteneur des résultats' },
        { id: 'liste_clients_recherche', type: 'tbody', name: 'Liste des clients trouvés' },
        { id: 'aucun_client_trouve', type: 'div', name: 'Message aucun résultat' },
        { id: 'info_client_selectionne', type: 'div', name: 'Information du client sélectionné' },
        { id: 'rechercheClientModal', type: 'div', name: 'Modal de recherche' }
    ];
    
    // Vérifier chaque élément
    let allElementsFound = true;
    elementsToCheck.forEach(element => {
        const el = document.getElementById(element.id);
        const found = el !== null;
        
        if (!found) {
            allElementsFound = false;
        }
    });
    
    if (!allElementsFound) {
        // Certains éléments de l'ancienne recherche sont manquants - Vous utilisez probablement la recherche avancée
    }
}

// Fonction utilitaire pour vérifier si un élément est visible
function isElementVisible(el) {
    if (!el) return false;
    
    const rect = el.getBoundingClientRect();
    const isNotHidden = window.getComputedStyle(el).display !== 'none';
    const hasSize = rect.width > 0 && rect.height > 0;
    
    // Vérifier également tous les parents
    let currentEl = el;
    while (currentEl) {
        const style = window.getComputedStyle(currentEl);
        if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
            return false;
        }
        currentEl = currentEl.parentElement;
    }
    
    return isNotHidden && hasSize;
}

// Configurer la recherche de client (ancienne version)
function setupClientHistorySearch() {
    // Champ de recherche
    const searchInput = document.getElementById('recherche_client_historique');
    const searchButton = document.getElementById('btn-recherche-client-historique');
    
    // Si les éléments n'existent pas, on ignore (recherche avancée utilisée à la place)
    if (!searchInput || !searchButton) {
        return;
    }
    
    // Recherche au clic sur le bouton
    searchButton.addEventListener('click', function() {
        const query = searchInput.value.trim();
        
        if (query.length >= 2) {
            searchClientsHistory(query);
        } else {
            alert('Veuillez saisir au moins 2 caractères pour la recherche');
        }
    });
    
    // Recherche en appuyant sur Entrée
    searchInput.addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchClientsHistory(query);
            } else {
                alert('Veuillez saisir au moins 2 caractères pour la recherche');
            }
        } else if (this.value.trim().length >= 2) {
            // Recherche automatique après 500ms d'inactivité
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                searchClientsHistory(this.value.trim());
            }, 500);
        }
    });
}

// Rechercher des clients (ancienne version)
function searchClientsHistory(query) {
    // Récupérer les éléments du DOM
    const resultsContainer = document.getElementById('resultats_recherche_client');
    const clientsList = document.getElementById('liste_clients_recherche');
    const noResultsContainer = document.getElementById('aucun_client_trouve');
    
    // Vérification des éléments critiques
    if (!resultsContainer || !clientsList) {
        return;
    }
    
    // Forcer l'affichage du conteneur des résultats
    resultsContainer.style.display = 'block';
    resultsContainer.classList.remove('d-none');
    
    // Vider la liste existante
    clientsList.innerHTML = '';
    
    // Masquer le message "aucun résultat" pendant la recherche
    if (noResultsContainer) {
        noResultsContainer.style.display = 'none';
        noResultsContainer.classList.add('d-none');
    }
    
    // Recherche AJAX
    const formData = new FormData();
    formData.append('recherche', query);
    
    fetch('ajax/recherche_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            processClientHistorySearchResults(data);
        } else {
            console.error('Erreur lors de la recherche:', data.message);
            if (noResultsContainer) {
                noResultsContainer.style.display = 'block';
                noResultsContainer.classList.remove('d-none');
            }
        }
    })
    .catch(error => {
        console.error('Erreur de communication:', error);
        if (noResultsContainer) {
            noResultsContainer.style.display = 'block';
            noResultsContainer.classList.remove('d-none');
        }
    });
}

// Traiter les résultats de recherche client
function processClientHistorySearchResults(data) {
    const clientsList = document.getElementById('liste_clients_recherche');
    const noResultsContainer = document.getElementById('aucun_client_trouve');
    
    if (!clientsList) return;
    
    // Vider la liste
    clientsList.innerHTML = '';
    
    if (data.clients && data.clients.length > 0) {
        // Masquer le message "aucun résultat"
        if (noResultsContainer) {
            noResultsContainer.style.display = 'none';
            noResultsContainer.classList.add('d-none');
        }
        
        // Afficher les résultats
        data.clients.forEach(client => {
            const row = document.createElement('tr');
            row.className = 'client-row';
            row.style.cursor = 'pointer';
            
            // Créer le contenu de la ligne
            const nomComplet = `${client.nom} ${client.prenom}`.trim();
            const telephone = client.telephone || 'Non renseigné';
            const email = client.email || 'Non renseigné';
            
            row.innerHTML = `
                <td class="fw-bold">${nomComplet}</td>
                <td>${telephone}</td>
                <td class="text-muted">${email}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="afficherHistoriqueClient(${client.id}, '${client.nom}', '${client.prenom}', '${client.telephone}')">
                        <i class="fas fa-eye me-1"></i>Historique
                    </button>
                </td>
            `;
            
            // Ajouter l'événement de clic pour la ligne entière
            row.addEventListener('click', function() {
                afficherHistoriqueClient(client.id, client.nom, client.prenom, client.telephone);
            });
            
            clientsList.appendChild(row);
        });
    } else {
        // Aucun résultat trouvé
        if (noResultsContainer) {
            noResultsContainer.style.display = 'block';
            noResultsContainer.classList.remove('d-none');
        }
    }
}

// Afficher l'historique du client sélectionné
function afficherHistoriqueClient(clientId, nom, prenom, telephone) {
    // Mettre à jour les informations du client sélectionné
    const infoClientElement = document.getElementById('info_client_selectionne');
    if (infoClientElement) {
        infoClientElement.innerHTML = `
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        ${nom} ${prenom}
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <i class="fas fa-phone me-2"></i>
                        <strong>Téléphone:</strong> ${telephone || 'Non renseigné'}
                    </p>
                </div>
            </div>
        `;
        
        // Afficher la section des informations client
        infoClientElement.style.display = 'block';
        infoClientElement.classList.remove('d-none');
    }
    
    // Initialiser les onglets d'historique
    initializeClientHistoryTabs();
    
    // Charger l'historique des réparations
    chargerHistoriqueReparations(clientId);
    
    // Charger l'historique des commandes
    chargerHistoriqueCommandes(clientId);
}

// Initialiser les onglets d'historique
function initializeClientHistoryTabs() {
    // Code pour initialiser les onglets Bootstrap ou autre système d'onglets
}

// Charger l'historique des réparations
function chargerHistoriqueReparations(clientId) {
    const reparationsContainer = document.getElementById('historique_reparations');
    if (!reparationsContainer) return;
    
    // Afficher un indicateur de chargement
    reparationsContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
    
    // Requête AJAX pour charger les réparations
    fetch(`ajax/get_client_reparations.php?client_id=${clientId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.reparations) {
            let html = '';
            
            if (data.reparations.length > 0) {
                html = `
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Appareil</th>
                                    <th>Marque</th>
                                    <th>Modèle</th>
                                    <th>Problème</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.reparations.forEach(reparation => {
                    html += `
                        <tr>
                            <td><strong>#${reparation.id}</strong></td>
                            <td>${reparation.type_appareil || 'Non spécifié'}</td>
                            <td>${reparation.marque || 'Non spécifié'}</td>
                            <td>${reparation.modele || 'Non spécifié'}</td>
                            <td>${reparation.probleme_decrit || 'Non spécifié'}</td>
                            <td>
                                <span class="badge bg-${getStatusColor(reparation.statut)} rounded-pill">
                                    ${reparation.statut || 'Inconnu'}
                                </span>
                            </td>
                            <td>${formatDate(reparation.date_reception)}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="?page=reparations&action=voir&id=${reparation.id}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?page=reparations&action=modifier&id=${reparation.id}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                html = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucune réparation trouvée pour ce client.
                    </div>
                `;
            }
            
            reparationsContainer.innerHTML = html;
        } else {
            reparationsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erreur lors du chargement des réparations.
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des réparations:', error);
        reparationsContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Erreur de communication avec le serveur.
            </div>
        `;
    });
}

// Charger l'historique des commandes
function chargerHistoriqueCommandes(clientId) {
    const commandesContainer = document.getElementById('historique_commandes');
    if (!commandesContainer) return;
    
    // Afficher un indicateur de chargement
    commandesContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
    
    // Requête AJAX pour charger les commandes
    fetch(`ajax/get_client_commandes.php?client_id=${clientId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.commandes) {
            let html = '';
            
            if (data.commandes.length > 0) {
                html = `
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Pièce</th>
                                    <th>Quantité</th>
                                    <th>Prix estimé</th>
                                    <th>Fournisseur</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.commandes.forEach(commande => {
                    html += `
                        <tr>
                            <td><strong>#${commande.id}</strong></td>
                            <td>${commande.nom_piece || 'Non spécifié'}</td>
                            <td>${commande.quantite || 0}</td>
                            <td>${formatPrix(commande.prix_estime)}</td>
                            <td>${commande.fournisseur_nom || 'Non spécifié'}</td>
                            <td>
                                <span class="badge bg-${getStatusColor(commande.statut)} rounded-pill">
                                    ${commande.statut || 'Inconnu'}
                                </span>
                            </td>
                            <td>${formatDate(commande.date_creation)}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="?page=commandes&action=voir&id=${commande.id}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?page=commandes&action=modifier&id=${commande.id}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                html = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucune commande trouvée pour ce client.
                    </div>
                `;
            }
            
            commandesContainer.innerHTML = html;
        } else {
            commandesContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erreur lors du chargement des commandes.
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des commandes:', error);
        commandesContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Erreur de communication avec le serveur.
            </div>
        `;
    });
}

// Configurer les boutons d'action
function setupActionButtons() {
    // Bouton "Nouvelle commande"
    const nouveauCommandeBtn = document.getElementById('nouvelle_commande_btn');
    if (nouveauCommandeBtn) {
        // Bouton nouvelle commande trouvé
    }
}

// Fonction pour obtenir la couleur du statut
function getStatusColor(statut) {
    if (!statut) return 'secondary';
    
    switch(statut.toLowerCase()) {
        case 'en_attente':
            return 'warning';
        case 'en_cours':
            return 'info';
        case 'termine':
            return 'success';
        case 'livre':
            return 'primary';
        case 'annule':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Fonction pour formater les dates
function formatDate(dateStr) {
    if (!dateStr) return 'Non spécifié';
    
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR');
}

// Fonction pour formater les prix
function formatPrix(prix) {
    if (!prix || prix == 0) return 'Non spécifié';
    
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(prix);
} 