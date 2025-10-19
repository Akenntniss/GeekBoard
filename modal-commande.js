// Gestion complète du modal de commande de pièces
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation du modal de commande...');
    
    // Éléments du modal
    const modal = document.getElementById('ajouterCommandeModal');
    const clientSearchInput = document.getElementById('nom_client_selectionne');
    const clientIdInput = document.getElementById('client_id');
    const clientSelectionne = document.getElementById('client_selectionne');
    const resultatsRecherche = document.getElementById('resultats_recherche_client_inline');
    const listeClients = document.getElementById('liste_clients_recherche_inline');
    const ajouterPieceBtn = document.getElementById('ajouter-piece-btn');
    const newClientBtn = document.getElementById('newClientBtn');

    // ==========================================
    // RECHERCHE DE CLIENT
    // ==========================================
    if (clientSearchInput) {
        let searchTimeout;
        
        clientSearchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                resultatsRecherche.classList.add('d-none');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                rechercherClients(query);
            }, 300);
        });
    }

    function rechercherClients(query) {
        console.log('Recherche de clients:', query);
        
        fetch('ajax/recherche_clients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: `terme=${encodeURIComponent(query)}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Résultats recherche:', data);
            if (data.success && Array.isArray(data.clients)) {
                afficherResultatsClients(data.clients);
            } else {
                resultatsRecherche.classList.add('d-none');
            }
        })
        .catch(err => {
            console.error('Erreur recherche client:', err);
        });
    }

    function afficherResultatsClients(clients) {
        listeClients.innerHTML = '';
        clients.forEach(client => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action client-item';
            item.style.cursor = 'pointer';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">${client.nom} ${client.prenom}</div>
                        <div class="text-muted small">${client.telephone || ''}</div>
                    </div>
                </div>
            `;
            item.addEventListener('click', () => selectionnerClient(client));
            listeClients.appendChild(item);
        });
        
        resultatsRecherche.classList.remove('d-none');
    }

    function masquerResultatsClients() {
        resultatsRecherche.classList.add('d-none');
    }

    function selectionnerClient(client) {
        console.log('Client sélectionné:', client);
        
        // Mettre à jour les champs
        clientIdInput.value = client.id;
        clientSearchInput.value = `${client.nom} ${client.prenom}`;
        
        // Afficher les infos du client sélectionné
        clientSelectionne.querySelector('.nom_client').textContent = `${client.nom} ${client.prenom}`;
        clientSelectionne.querySelector('.tel_client').textContent = client.telephone || 'Pas de téléphone';
        clientSelectionne.classList.remove('d-none');
        
        // Masquer les résultats
        masquerResultatsClients();
    }

    // ==========================================
    // BOUTON NOUVEAU CLIENT
    // ==========================================
    if (newClientBtn) {
        newClientBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Clic sur nouveau client');
            
            // Vérifier si le modal nouveau client existe
            const nouveauClientModal = document.getElementById('nouveauClientModal_commande');
            if (nouveauClientModal) {
                // Fermer le modal actuel
                const currentModal = bootstrap.Modal.getInstance(modal);
                if (currentModal) {
                    currentModal.hide();
                }
                
                // Ouvrir le modal nouveau client
                setTimeout(() => {
                    const newClientModalInstance = new bootstrap.Modal(nouveauClientModal);
                    newClientModalInstance.show();
                }, 300);
            } else {
                alert('Le modal de création de client n\'est pas disponible. Veuillez contacter l\'administrateur.');
            }
        });
    }

    // ==========================================
    // CHARGEMENT DES FOURNISSEURS
    // ==========================================
    modal.addEventListener('shown.bs.modal', function() {
        chargerFournisseurs();
    });
    const fournisseurSelect = document.getElementById('fournisseur_id_ajout');
        
        function chargerFournisseurs() {
            console.log('Chargement des fournisseurs...');
            
            fetch('ajax/get_fournisseurs.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Fournisseurs reçus:', data);
                
                if (data.success && Array.isArray(data.fournisseurs)) {
                    // Vider le select
                    fournisseurSelect.innerHTML = '<option value="">Sélectionner un fournisseur...</option>';
                    
                    // Ajouter les fournisseurs
                    data.fournisseurs.forEach(fournisseur => {
                        const option = document.createElement('option');
                        option.value = fournisseur.id;
                        option.textContent = fournisseur.nom;
                        fournisseurSelect.appendChild(option);
                    });
                    
                    console.log(`${data.fournisseurs.length} fournisseur(s) chargé(s)`);
                } else {
                    console.error('Erreur lors du chargement des fournisseurs:', data.message || 'Format de réponse invalide');
                }
            })
            .catch(err => {
                console.error('Erreur réseau lors du chargement des fournisseurs:', err);
            });
        }
    }

    // ==========================================
    // GESTION DES STATUS PILLS
    // ==========================================
    const statusPills = document.querySelectorAll('.status-pill');
    statusPills.forEach(pill => {
        pill.addEventListener('click', function() {
            // Retirer la classe active de tous les pills
            statusPills.forEach(p => p.classList.remove('active'));
            
            // Ajouter la classe active au pill cliqué
            this.classList.add('active');
            
            // Cocher le radio bouton correspondant
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        });
    });

    // ==========================================
    // GESTION DU BOUTON SMS (SUPPRIMÉ)
    // ==========================================
    // Section Notification client retirée du modal

    // Neutraliser la fonction globale si appelée par erreur
    window.getClientFromReparation = function() {
        console.log('getClientFromReparation ignoré (champ supprimé)');
    }

    // ==========================================
    // AJOUT DE PIÈCES SUPPLÉMENTAIRES
    // ==========================================
    if (ajouterPieceBtn) {
        ajouterPieceBtn.addEventListener('click', function() {
            console.log('Ajout d\'une nouvelle pièce');
            const grid = document.querySelector('.order-grid');
            if (!grid) return;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'order-grid mt-2';
            wrapper.innerHTML = `
                <input type="text" class="form-control" name="nom_piece_2" placeholder="Désignation de la pièce" required>
                <input type="text" class="form-control" name="code_barre_2" placeholder="Code barre">
                <div class="quantity-selector">
                    <button type="button" class="btn btn-outline-secondary">–</button>
                    <input type="number" class="form-control text-center" name="quantite_2" value="1" min="1">
                    <button type="button" class="btn btn-outline-secondary">+</button>
                </div>
                <input type="number" class="form-control" name="prix_estime_2" placeholder="0.00" step="0.01" min="0" required>
            `;
            grid.parentNode.appendChild(wrapper);
        });
    }

    // Soumission
    const form = document.getElementById('ajouterCommandeForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Soumission du formulaire');
            
            const infos = {
                client_id: document.getElementById('client_id').value,
                fournisseur_id: document.getElementById('fournisseur_id_ajout').value,
                // reparation_id supprimé
                statut: document.querySelector('input[name="statut"]:checked').value,
                send_sms: '0' // Valeur par défaut (pas de SMS)
            };
            
            // Récupérer une pièce (basique)
            const piece = {
                nom_piece: document.getElementById('nom_piece').value.trim(),
                code_barre: document.getElementById('code_barre').value.trim(),
                quantite: document.getElementById('quantite').value,
                prix_estime: document.getElementById('prix_estime').value
            };
            
            soumettreCommandesMultiples(infos, [piece], modal);
        });
    }

function soumettreCommandesMultiples(informationsCommunes, pieces, modal) {
        console.log(`Soumission de ${pieces.length} commande(s)`);
    let commandesReussies = 0;
        const promesses = [];
    
    pieces.forEach((piece, index) => {
        const formData = new FormData();
        formData.append('client_id', informationsCommunes.client_id);
        formData.append('fournisseur_id', informationsCommunes.fournisseur_id);
        formData.append('statut', informationsCommunes.statut);
        formData.append('send_sms', informationsCommunes.send_sms);
        
            // reparation_id supprimé (laisser optionnel si backend l'accepte)
        if (informationsCommunes.reparation_id) {
            formData.append('reparation_id', informationsCommunes.reparation_id);
        }
        
        // Ajouter les informations spécifiques de la pièce
        formData.append('nom_piece', piece.nom_piece);
        formData.append('code_barre', piece.code_barre);
        formData.append('quantite', piece.quantite);
        formData.append('prix_estime', piece.prix_estime);
        
        // Créer la promesse pour cette commande
        const promise = fetch('ajax/add_commande.php', {
            method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                commandesReussies++;
                console.log(`Commande ${index + 1} ajoutée avec succès: ${piece.nom_piece}`);
            } else {
                    console.error(`Erreur commande ${index + 1}:`, data.message || data);
                }
            })
            .catch(err => {
                console.error(`Erreur réseau commande ${index + 1}:`, err);
            });
            
            promesses.push(promise);
        });

        Promise.all(promesses).then(() => {
            console.log('Toutes les commandes traitées:', pieces);
        if (commandesReussies > 0) {
                const instance = bootstrap.Modal.getInstance(modal);
                if (instance) instance.hide();
        }
    });
} 
}); 