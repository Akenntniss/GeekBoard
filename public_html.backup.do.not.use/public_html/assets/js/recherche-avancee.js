/**
 * Module de recherche avancée pour GestiRep
 * Permet de rechercher clients, réparations et commandes avec un seul champ de recherche
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const searchInput = document.getElementById('recherche_avancee');
    const searchButton = document.getElementById('btn-recherche-avancee');
    const resultsContainer = document.getElementById('resultats_recherche');
    const noResultsContainer = document.getElementById('aucun_resultat_trouve');
    
    // Compteurs de résultats
    const clientsCount = document.getElementById('count-clients');
    const reparationsCount = document.getElementById('count-reparations');
    const commandesCount = document.getElementById('count-commandes');
    
    // Conteneurs de résultats
    const clientsContainer = document.getElementById('liste_clients_recherche');
    const reparationsContainer = document.getElementById('liste_reparations_recherche');
    const commandesContainer = document.getElementById('liste_commandes_recherche');
    
    // Vérifier que les éléments existent
    if (!searchInput || !searchButton) {
        return;
    }
    
    // Variable pour le délai de recherche (debounce)
    let timeoutRecherche;
    
    // Fonction de recherche
    function rechercheAvancee(terme) {
        if (terme.length < 2) {
            if (resultsContainer) resultsContainer.classList.add('d-none');
            if (noResultsContainer) noResultsContainer.classList.add('d-none');
            return;
        }
        
        // Recherche AJAX
        fetch('ajax/recherche_avancee.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'terme=' + encodeURIComponent(terme)
        })
        .then(response => {
            if (!response.ok) {
                console.error('Erreur réseau:', response.status, response.statusText);
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                console.error('Erreur retournée par le serveur:', data.message);
                throw new Error(data.message || 'Erreur lors de la recherche');
            }
            
            // Mettre à jour les compteurs
            if (clientsCount) clientsCount.textContent = data.counts.clients;
            if (reparationsCount) reparationsCount.textContent = data.counts.reparations;
            if (commandesCount) commandesCount.textContent = data.counts.commandes;
            
            // Effacer les résultats précédents
            if (clientsContainer) clientsContainer.innerHTML = '';
            if (reparationsContainer) reparationsContainer.innerHTML = '';
            if (commandesContainer) commandesContainer.innerHTML = '';
            
            // Si aucun résultat
            if (data.counts.total === 0) {
                if (resultsContainer) resultsContainer.classList.add('d-none');
                if (noResultsContainer) noResultsContainer.classList.remove('d-none');
                return;
            }
            
            // Afficher les clients
            if (clientsContainer && data.resultats.clients.length > 0) {
                data.resultats.clients.forEach(client => {
                    const row = document.createElement('tr');
                    row.className = 'search-result-row'; // Ajouter une classe pour le styling
                    
                    // Préparer les données avec des valeurs par défaut
                    const nom = client.nom || 'Non spécifié';
                    const prenom = client.prenom || '';
                    const telephone = client.telephone || 'Non spécifié';
                    
                    row.innerHTML = `
                        <td class="ps-3 fw-medium">${nom}</td>
                        <td>${prenom}</td>
                        <td><i class="fas fa-phone-alt text-primary me-1"></i>${telephone}</td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill voir-client-btn me-1" 
                                data-id="${client.id}" 
                                data-nom="${nom}" 
                                data-prenom="${prenom}"
                                data-telephone="${telephone}">
                                <i class="fas fa-eye me-1"></i>Voir
                            </button>
                        </td>
                    `;
                    clientsContainer.appendChild(row);
                });
            }
            
            // Afficher les réparations
            if (reparationsContainer && data.resultats.reparations.length > 0) {
                data.resultats.reparations.forEach(reparation => {
                    const row = document.createElement('tr');
                    row.className = 'search-result-row'; // Ajouter une classe pour le styling
                    
                    // Préparer les données avec des valeurs par défaut pour éviter les valeurs null
                    const id = reparation.id || '';
                    const clientName = [reparation.client_nom || '', reparation.client_prenom || ''].filter(Boolean).join(' ');
                    const appareil = reparation.appareil || 'Non spécifié';
                    const modele = reparation.modele || 'Non spécifié';
                    const statut = reparation.statut || '';
                    
                    row.innerHTML = `
                        <td class="ps-3 fw-medium">${id}</td>
                        <td>${clientName}</td>
                        <td>${appareil}</td>
                        <td>${modele}</td>
                        <td><span class="badge bg-${getStatusColor(statut)} rounded-pill px-3 py-2">${formatStatus(statut)}</span></td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill voir-reparation-btn" 
                                data-id="${id}">
                                <i class="fas fa-eye me-1"></i>Voir
                            </button>
                        </td>
                    `;
                    reparationsContainer.appendChild(row);
                });
            }
            
            // Afficher les commandes
            if (commandesContainer && data.resultats.commandes.length > 0) {
                data.resultats.commandes.forEach(commande => {
                    const row = document.createElement('tr');
                    row.className = 'search-result-row'; // Ajouter une classe pour le styling
                    
                    // Préparer les données avec des valeurs par défaut
                    const id = commande.id || '';
                    const clientName = [commande.client_nom || '', commande.client_prenom || ''].filter(Boolean).join(' ');
                    const nomPiece = commande.nom_piece || 'Non spécifié';
                    const dateCreation = formatDate(commande.date_creation) || 'Non spécifié';
                    const statut = commande.statut || '';
                    
                    row.innerHTML = `
                        <td class="ps-3 fw-medium">${id}</td>
                        <td>${clientName}</td>
                        <td>${nomPiece}</td>
                        <td>${dateCreation}</td>
                        <td><span class="badge bg-${getCommandeStatusColor(statut)} rounded-pill px-3 py-2">${formatCommandeStatus(statut)}</span></td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill voir-commande-btn" 
                                data-id="${id}">
                                <i class="fas fa-eye me-1"></i>Voir
                            </button>
                        </td>
                    `;
                    commandesContainer.appendChild(row);
                });
            }
            
            // Afficher les résultats
            if (resultsContainer) {
                resultsContainer.classList.remove('d-none');
            }
            if (noResultsContainer) {
                noResultsContainer.classList.add('d-none');
            }
            
            // Activer automatiquement l'onglet approprié
            try {
                if (data.resultats.reparations.length > 0) {
                    window.showResultTab('reparations-container');
                } else if (data.resultats.clients.length > 0) {
                    window.showResultTab('clients-container');
                } else if (data.resultats.commandes.length > 0) {
                    window.showResultTab('commandes-container');
                }
            } catch (e) {
                console.error('Erreur lors de l\'activation des onglets:', e);
            }
            
            // Ajouter les gestionnaires d'événements pour les boutons
            attachEventListeners();
        })
        .catch(error => {
            console.error('Erreur détaillée lors de la recherche:', error);
            
            // Afficher un message d'erreur à l'utilisateur
            if (resultsContainer) resultsContainer.classList.add('d-none');
            if (noResultsContainer) noResultsContainer.classList.remove('d-none');
        });
    }
    
    // Fonctions utilitaires pour le formatage
    function formatStatus(statut) {
        switch(statut) {
            case 'en_attente':
                return 'En attente';
            case 'en_cours':
                return 'En cours';
            case 'termine':
                return 'Terminé';
            case 'livre':
                return 'Livré';
            case 'annule':
                return 'Annulé';
            default:
                return statut || 'Inconnu';
    }
    }
    
    function getStatusColor(statut) {
        switch(statut) {
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
    
    function formatCommandeStatus(statut) {
        switch(statut) {
            case 'en_attente':
                return 'En attente';
            case 'commande':
                return 'Commandé';
            case 'recu':
                return 'Reçu';
            case 'annule':
                return 'Annulé';
            default:
                return statut || 'Inconnu';
    }
    }
    
    function getCommandeStatusColor(statut) {
        switch(statut) {
            case 'en_attente':
                return 'warning';
            case 'commande':
                return 'info';
            case 'recu':
                return 'success';
            case 'annule':
                return 'danger';
            default:
                return 'secondary';
    }
    }
    
    function formatDate(dateString) {
        if (!dateString) return null;
        
        try {
        const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR');
        } catch (e) {
            return dateString;
        }
    }
    
    // Attacher les gestionnaires d'événements aux boutons des résultats
    function attachEventListeners() {
        // Gestionnaires pour les boutons "Voir client"
        document.querySelectorAll('.voir-client-btn').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                const nom = this.getAttribute('data-nom');
                const prenom = this.getAttribute('data-prenom');
                const telephone = this.getAttribute('data-telephone');
                
                // Vérifier si la fonction d'historique client existe
                if (typeof chargerHistoriqueClient === 'function') {
                chargerHistoriqueClient(clientId);
                } else {
                    // Redirection vers la page client
                    window.location.href = `?page=clients&action=voir&id=${clientId}`;
                }
            });
        });
        
        // Gestionnaires pour les boutons "Voir réparation"
        document.querySelectorAll('.voir-reparation-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reparationId = this.getAttribute('data-id');
                
                // Vérifier si la fonction de détails réparation existe
                if (typeof chargerDetailsReparation === 'function') {
                    chargerDetailsReparation(reparationId);
                } else {
                    // Redirection vers la page réparation
                    window.location.href = `?page=reparations&action=voir&id=${reparationId}`;
                }
            });
        });
        
        // Gestionnaires pour les boutons "Voir commande"
        document.querySelectorAll('.voir-commande-btn').forEach(button => {
            button.addEventListener('click', function() {
                const commandeId = this.getAttribute('data-id');
                
                // Vérifier si la fonction de détails commande existe
                if (typeof chargerDetailsCommande === 'function') {
                chargerDetailsCommande(commandeId);
                } else {
                    // Redirection vers la page commande
                    window.location.href = `?page=commandes&action=voir&id=${commandeId}`;
                }
            });
        });
    }
    
    // Charger l'historique d'un client
    function chargerHistoriqueClient(clientId) {
        // Changer d'onglet vers les résultats clients si nécessaire
        if (typeof window.showResultTab === 'function') {
            window.showResultTab('clients-container');
            }
        
        // Récupérer les données du client
        fetch(`ajax/get_client_details.php?id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.client) {
                // Afficher les informations du client sélectionné
                displayClientInfo(data.client);
                
                // Charger l'historique des réparations et commandes
                loadClientHistory(clientId);
            } else {
                console.error('Erreur lors du chargement des détails du client:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails du client:', error);
        });
    }
    
    function displayClientInfo(client) {
        // Créer ou mettre à jour la section d'informations du client
        let clientInfoSection = document.getElementById('client-info-section');
        if (!clientInfoSection) {
            clientInfoSection = document.createElement('div');
            clientInfoSection.id = 'client-info-section';
            clientInfoSection.className = 'mb-4';
            
            // Insérer avant les résultats de recherche
            if (resultsContainer) {
                resultsContainer.parentNode.insertBefore(clientInfoSection, resultsContainer);
            }
        }
        
        clientInfoSection.innerHTML = `
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        ${client.nom} ${client.prenom}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <strong>Téléphone:</strong> ${client.telephone || 'Non renseigné'}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <strong>Email:</strong> ${client.email || 'Non renseigné'}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm me-2" onclick="window.location.href='?page=ajouter_reparation&client_id=${client.id}'">
                            <i class="fas fa-plus me-1"></i>Nouvelle réparation
                        </button>
                        <button class="btn btn-success btn-sm me-2" onclick="window.location.href='?page=nouvelle_commande&client_id=${client.id}'">
                            <i class="fas fa-shopping-cart me-1"></i>Nouvelle commande
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('client-info-section').remove()">
                            <i class="fas fa-times me-1"></i>Fermer
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    function loadClientHistory(clientId) {
        // Cette fonction pourrait charger l'historique complet du client
        // Pour l'instant, on utilise la recherche existante pour afficher les réparations et commandes
    }
    
    // Charger les détails d'une réparation
    function chargerDetailsReparation(reparationId) {
        // Changer d'onglet vers les résultats réparations si nécessaire
        if (typeof window.showResultTab === 'function') {
            window.showResultTab('reparations-container');
        }
        
        // Récupérer les détails de la réparation
        fetch(`ajax/get_reparation_details.php?id=${reparationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.reparation) {
                // Afficher les détails de la réparation
                displayReparationDetails(data.reparation);
            } else {
                console.error('Erreur lors du chargement des détails de la réparation:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails de la réparation:', error);
        });
    }
    
    function displayReparationDetails(reparation) {
        // Créer ou mettre à jour la section de détails de la réparation
        let reparationDetailsSection = document.getElementById('reparation-details-section');
        if (!reparationDetailsSection) {
            reparationDetailsSection = document.createElement('div');
            reparationDetailsSection.id = 'reparation-details-section';
            reparationDetailsSection.className = 'mb-4';
            
            // Insérer avant les résultats de recherche
            if (resultsContainer) {
                resultsContainer.parentNode.insertBefore(reparationDetailsSection, resultsContainer);
            }
        }
        
        reparationDetailsSection.innerHTML = `
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Réparation #${reparation.id}
                            </h5>
                    </div>
                    <div class="card-body">
                    <div class="row">
                            <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Client:</strong> ${reparation.client_nom} ${reparation.client_prenom}
                            </p>
                            <p class="mb-2">
                                <strong>Appareil:</strong> ${reparation.appareil || 'Non spécifié'}
                            </p>
                            <p class="mb-2">
                                <strong>Modèle:</strong> ${reparation.modele || 'Non spécifié'}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Statut:</strong> 
                                <span class="badge bg-${getStatusColor(reparation.statut)} ms-2">
                                    ${formatStatus(reparation.statut)}
                                </span>
                            </p>
                            <p class="mb-2">
                                <strong>Date de réception:</strong> ${formatDate(reparation.date_reception)}
                            </p>
                            <p class="mb-2">
                                <strong>Problème:</strong> ${reparation.probleme || 'Non spécifié'}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm me-2" onclick="window.location.href='?page=reparations&action=modifier&id=${reparation.id}'">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </button>
                        <button class="btn btn-success btn-sm me-2" onclick="window.open('imprimer_etiquette.php?id=${reparation.id}', '_blank')">
                            <i class="fas fa-print me-1"></i>Imprimer étiquette
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('reparation-details-section').remove()">
                            <i class="fas fa-times me-1"></i>Fermer
                        </button>
                </div>
                </div>
                </div>
            `;
    }
    
    // Charger les détails d'une commande
    function chargerDetailsCommande(commandeId) {
        // Changer d'onglet vers les résultats commandes si nécessaire
        if (typeof window.showResultTab === 'function') {
            window.showResultTab('commandes-container');
        }
        
        // Récupérer les détails de la commande
        fetch(`ajax/get_commande_details.php?id=${commandeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.commande) {
                // Afficher les détails de la commande
                displayCommandeDetails(data.commande);
            } else {
                console.error('Erreur lors du chargement des détails de la commande:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails de la commande:', error);
        });
    }
    
    function displayCommandeDetails(commande) {
        // Créer ou mettre à jour la section de détails de la commande
        let commandeDetailsSection = document.getElementById('commande-details-section');
        if (!commandeDetailsSection) {
            commandeDetailsSection = document.createElement('div');
            commandeDetailsSection.id = 'commande-details-section';
            commandeDetailsSection.className = 'mb-4';
            
            // Insérer avant les résultats de recherche
            if (resultsContainer) {
                resultsContainer.parentNode.insertBefore(commandeDetailsSection, resultsContainer);
            }
        }
        
        commandeDetailsSection.innerHTML = `
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Commande #${commande.id}
                            </h5>
                    </div>
                    <div class="card-body">
                    <div class="row">
                            <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Client:</strong> ${commande.client_nom} ${commande.client_prenom}
                            </p>
                            <p class="mb-2">
                                <strong>Pièce:</strong> ${commande.nom_piece || 'Non spécifié'}
                            </p>
                            <p class="mb-2">
                                <strong>Quantité:</strong> ${commande.quantite || 'Non spécifié'}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Statut:</strong> 
                                <span class="badge bg-${getCommandeStatusColor(commande.statut)} ms-2">
                                    ${formatCommandeStatus(commande.statut)}
                                </span>
                            </p>
                            <p class="mb-2">
                                <strong>Date de création:</strong> ${formatDate(commande.date_creation)}
                            </p>
                            <p class="mb-2">
                                <strong>Fournisseur:</strong> ${commande.fournisseur || 'Non spécifié'}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm me-2" onclick="window.location.href='?page=commandes&action=modifier&id=${commande.id}'">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('commande-details-section').remove()">
                            <i class="fas fa-times me-1"></i>Fermer
                        </button>
                </div>
                </div>
                </div>
            `;
    }
    
    // Gestionnaires d'événements pour les interactions utilisateur
    searchButton.addEventListener('click', function() {
        const terme = searchInput.value.trim();
        rechercheAvancee(terme);
    });
    
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const terme = this.value.trim();
            rechercheAvancee(terme);
        } else {
            // Recherche avec délai (debounce)
            clearTimeout(timeoutRecherche);
            const terme = this.value.trim();
            
            if (terme.length >= 2) {
            timeoutRecherche = setTimeout(() => {
                rechercheAvancee(terme);
                }, 300); // Délai de 300ms
            } else {
                // Masquer les résultats si moins de 2 caractères
                if (resultsContainer) resultsContainer.classList.add('d-none');
                if (noResultsContainer) noResultsContainer.classList.add('d-none');
            }
        }
    });
    
    // Fonction pour forcer l'affichage d'un onglet de résultats
    function forceShowTabContent(containerId) {
        // Masquer tous les conteneurs d'abord
        const allContainers = [
            'clients-container',
            'reparations-container', 
            'commandes-container'
        ];
        
        allContainers.forEach(id => {
            const container = document.getElementById(id);
            if (container) {
                container.style.display = 'none';
                container.classList.add('d-none');
                }
        });
        
        // Afficher le conteneur souhaité
        const targetContainer = document.getElementById(containerId);
                            if (targetContainer) {
                                targetContainer.style.display = 'block';
            targetContainer.classList.remove('d-none');
        }
        
        // Mettre à jour les onglets si ils existent
        const allTabs = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');
        allTabs.forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });
        
        const targetTab = document.querySelector(`[data-bs-target="#${containerId}"]`);
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.setAttribute('aria-selected', 'true');
            }
    }
    
    // Exposer la fonction showResultTab pour les autres scripts
    window.showResultTab = forceShowTabContent;
            
    // Fonction d'urgence pour réparer l'affichage si besoin
    function addEmergencyFixButton() {
        const debugButton = document.createElement('button');
        debugButton.className = 'btn btn-sm btn-warning position-fixed';
        debugButton.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999;';
        debugButton.innerHTML = '<i class="fas fa-wrench"></i> Fix';
        debugButton.title = 'Réparer l\'affichage des résultats';
        
        debugButton.addEventListener('click', function() {
            // Forcer l'affichage des résultats
            if (resultsContainer) {
                resultsContainer.style.display = 'block';
                resultsContainer.classList.remove('d-none');
            }
            
            // Vérifier et afficher le premier onglet avec des résultats
            const containers = [
                { id: 'clients-container', count: clientsCount },
                { id: 'reparations-container', count: reparationsCount },
                { id: 'commandes-container', count: commandesCount }
            ];
            
            for (let container of containers) {
                if (container.count && parseInt(container.count.textContent) > 0) {
                    forceShowTabContent(container.id);
                    break;
                }
            }
            
            this.remove();
        });
        
        document.body.appendChild(debugButton);
        
        // Auto-supprimer le bouton après 10 secondes
        setTimeout(() => {
            if (debugButton.parentNode) {
                debugButton.remove();
            }
        }, 10000);
    }
    
    // Ajouter le bouton d'urgence si les résultats ne s'affichent pas bien
    setTimeout(addEmergencyFixButton, 5000);
}); 