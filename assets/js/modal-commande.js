// Gestion complète du modal de commande de pièces - Version 2.0 (Multiple Pieces Fix)
document.addEventListener('DOMContentLoaded', function () {

    // Éléments du modal
    const modal = document.getElementById('ajouterCommandeModal');
    const clientSearchInput = document.getElementById('nom_client_selectionne');
    const clientIdInput = document.getElementById('client_id');
    const clientSelectionne = document.getElementById('client_selectionne');
    const resultatsRecherche = document.getElementById('resultats_recherche_client_inline');
    const listeClients = document.getElementById('liste_clients_recherche_inline');
    const ajouterPieceBtn = document.getElementById('ajouter-piece-btn');
    const newClientBtn = document.getElementById('newClientBtn');

    // console.log('🛒 [MODAL-COMMANDE] Vérification des éléments:', {
    modal, clientSearchInput, clientIdInput, clientSelectionne,
        resultatsRecherche, listeClients, ajouterPieceBtn, newClientBtn
});
// RECHERCHE DE CLIENT
// ==========================================
// Éléments trouvés: clientSearchInput, resultatsRecherche, listeClients

if (clientSearchInput) {
    let searchTimeout;

    clientSearchInput.addEventListener('input', function () {
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
            if (data.success && Array.isArray(data.clients)) {
                afficherResultatsClients(data.clients);
            } else {
                resultatsRecherche.classList.add('d-none');
            }
        })
        .catch(err => {
            console.error('Erreur recherche clients:', err);
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
    newClientBtn.addEventListener('click', function (e) {
        e.preventDefault();

        // ===== DÉSACTIVÉ - Utilise maintenant le modal futuriste =====
        // Ne rien faire ici, le modal futuriste est géré par modal-commande-inject.js
    });
}

// ==========================================
// CHARGEMENT DES FOURNISSEURS
// ==========================================
const fournisseurSelect = document.getElementById('fournisseur_id_ajout');

function chargerFournisseurs() {

    if (!fournisseurSelect) {
        return;
    }

    fetch('ajax/get_fournisseurs.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
    })
        .then(response => response.json())
        .then(data => {

            if (data.success && Array.isArray(data.fournisseurs)) {
                // Vider le select
                fournisseurSelect.innerHTML = '<option value="">Sélectionner un fournisseur...</option>';

                // Ajouter les fournisseurs
                data.fournisseurs.forEach(fournisseur => {
                    const option = document.createElement('option');
                    option.value = fournisseur.id;
                    option.textContent = fournisseur.nom;
                    fournisseurSelect.appendChild(option);

                    // console.log(`${data.fournisseurs.length} fournisseur(s) chargé(s)`);
                });
            }
        })
        .catch(err => {
            console.error('Erreur chargement fournisseurs:', err);
        });
}

// Charger les fournisseurs quand le modal s'ouvre
modal.addEventListener('shown.bs.modal', function () {
    chargerFournisseurs();
});

// ==========================================
// GESTION DES STATUS PILLS
// ==========================================
const statusPills = document.querySelectorAll('#ajouterCommandeModal .status-pill');
statusPills.forEach(pill => {
    pill.addEventListener('click', function () {
        // Retirer la classe active de tous les pills du modal
        statusPills.forEach(p => {
            if (p && p.classList) {
                p.classList.remove('active');
            }

            // Ajouter la classe active au pill cliqué
            if (this && this.classList) {
                this.classList.add('active');
            }

            // Cocher le radio bouton correspondant
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                // Déclencher l'événement change pour mettre à jour l'interface
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
});

// ==========================================
// GESTION DES RADIOS STATUT
// ==========================================
const statusRadios = document.querySelectorAll('#ajouterCommandeModal input[name="statut"]');
statusRadios.forEach(radio => {
    radio.addEventListener('change', function () {
        // Réinitialiser tous les status-pills
        statusPills.forEach(p => {
            if (p && p.classList) {
                p.classList.remove('active');
            }

            // Activer le pill correspondant au radio sélectionné
            const parentPill = this.closest('.status-pill');
            if (parentPill && parentPill.classList) {
                parentPill.classList.add('active');
            }
        });
    });
});


// ==========================================
// GESTION DES BOUTONS D'OUVERTURE DU MODAL
// ==========================================
const openNewOrderBtn = document.getElementById('openNewOrderFromActions');
if (openNewOrderBtn) {
    openNewOrderBtn.addEventListener('click', function () {
        const modalElement = document.getElementById('ajouterCommandeModal');
        const modal = new bootstrap.Modal(modalElement);

        // Réinitialiser le modal quand il s'ouvre
        modalElement.addEventListener('shown.bs.modal', function () {

            // Vérifier que les éléments de recherche sont bien présents
            const searchInput = document.getElementById('nom_client_selectionne');
            const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
            const listeDiv = document.getElementById('liste_clients_recherche_inline');

            // console.log('Éléments de recherche:', {
            searchInput, resultatsDiv, listeDiv
        });

        // Focus sur le champ de recherche
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 100);
        }

        // Charger les fournisseurs
        chargerFournisseurs();
    }, { once: true });

    modal.show();
});
    }

const openNewTaskBtn = document.getElementById('openNewTaskFromActions');
if (openNewTaskBtn) {
    openNewTaskBtn.addEventListener('click', function () {
        const modal = new bootstrap.Modal(document.getElementById('ajouterTacheModal'));
        modal.show();
    });
}

// ==========================================
// GESTION DU BOUTON ENREGISTRER
// ==========================================
let isSubmitting = false; // Protection contre les soumissions multiples

const saveCommandeBtn = document.getElementById('saveCommandeBtn');
if (saveCommandeBtn && !saveCommandeBtn.hasAttribute('data-event-attached')) {
    // Marquer que l'événement a été attaché
    saveCommandeBtn.setAttribute('data-event-attached', 'true');

    saveCommandeBtn.addEventListener('click', function (e) {
        e.preventDefault();

        // Protection contre les soumissions multiples
        if (isSubmitting) {
            return;
        }

        saveCommande();
    });
}

// Fonction pour sauvegarder la commande
function saveCommande() {

    // Marquer comme en cours de soumission
    isSubmitting = true;

    // Collecter toutes les pièces du formulaire
    const pieces = collecterToutesLesPieces();

    if (pieces.length === 0) {
        alert('Veuillez ajouter au moins une pièce à la commande');
        isSubmitting = false;
        return;
    }

    // Vérifier que tous les champs communs obligatoires sont remplis
    const clientId = clientIdInput.value;
    const fournisseurId = document.getElementById('fournisseur_id_ajout').value;
    const statutRadio = document.querySelector('input[name="statut"]:checked');

    // console.log('Informations communes:', {
    clientId, fournisseurId,
        statut: statutRadio ? statutRadio.value : 'aucun'
});

// Validation des informations communes
if (!clientId) {
    alert('Veuillez sélectionner un client');
    isSubmitting = false;
    return;
}
if (!fournisseurId) {
    alert('Veuillez sélectionner un fournisseur');
    isSubmitting = false;
    return;
}
if (!statutRadio) {
    alert('Veuillez sélectionner un statut');
    isSubmitting = false;
    return;
}

// Désactiver le bouton pendant l'envoi
saveCommandeBtn.disabled = true;
saveCommandeBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';

// Préparer les informations communes
const informationsCommunes = {
    client_id: clientId,
    fournisseur_id: fournisseurId,
    statut: statutRadio.value,
    reparation_id: new URLSearchParams(window.location.search).get('id'),
};

// Utiliser la fonction de soumission multiple
// console.log('Soumission:', {
informationsCommunes,
    pieces,
    nombrePieces: pieces.length
        });
soumettreCommandesMultiples(informationsCommunes, pieces, modal);
    }

// Fonction pour collecter toutes les pièces du formulaire
function collecterToutesLesPieces() {
    const pieces = [];

    // Pièce principale (toujours présente)
    const nomPiecePrincipal = document.getElementById('nom_piece').value.trim();
    const quantitePrincipal = document.getElementById('quantite').value;
    const prixEstimePrincipal = document.getElementById('prix_estime').value;
    const codeBarrePrincipal = document.getElementById('code_barre').value.trim();

    if (nomPiecePrincipal && quantitePrincipal && prixEstimePrincipal) {
        if (parseInt(quantitePrincipal) > 0 && parseFloat(prixEstimePrincipal) > 0) {
            pieces.push({
                nom_piece: nomPiecePrincipal,
                code_barre: codeBarrePrincipal,
                quantite: parseInt(quantitePrincipal),
                prix_estime: parseFloat(prixEstimePrincipal)
            });
        }
    }

    // Pièces supplémentaires (ajoutées dynamiquement)
    const orderGrids = document.querySelectorAll('.order-grid.mt-2');
    orderGrids.forEach((grid, index) => {
        const nomPieceInput = grid.querySelector('input[name^="nom_piece_"]');
        const codeBarreInput = grid.querySelector('input[name^="code_barre_"]');
        const quantiteInput = grid.querySelector('input[name^="quantite_"]');
        const prixEstimeInput = grid.querySelector('input[name^="prix_estime_"]');

        if (nomPieceInput && quantiteInput && prixEstimeInput) {
            const nomPiece = nomPieceInput.value.trim();
            const quantite = quantiteInput.value;
            const prixEstime = prixEstimeInput.value;
            const codeBarre = codeBarreInput ? codeBarreInput.value.trim() : '';

            if (nomPiece && quantite && prixEstime) {
                if (parseInt(quantite) > 0 && parseFloat(prixEstime) > 0) {
                    pieces.push({
                        nom_piece: nomPiece,
                        code_barre: codeBarre,
                        quantite: parseInt(quantite),
                        prix_estime: parseFloat(prixEstime)
                    });
                }
            }
        }
    });

    return pieces;
}

// Fonction pour afficher les notifications (si elle n'existe pas déjà)
function showNotification(message, type) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        // Fallback simple
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }
}

// ==========================================
// GESTION DU BOUTON SMS (SUPPRIMÉ)
// ==========================================
// Section Notification client retirée du modal

// Neutraliser la fonction globale si appelée par erreur
window.getClientFromReparation = function () {
}

// ==========================================
// AJOUT DE PIÈCES SUPPLÉMENTAIRES
// ==========================================
let pieceCounter = 1; // Compteur pour les pièces supplémentaires

if (ajouterPieceBtn) {
    ajouterPieceBtn.addEventListener('click', function () {
        pieceCounter++;
        const grid = document.querySelector('.order-grid');
        if (!grid) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'order-grid mt-2';
        wrapper.innerHTML = `
                <input type="text" class="form-control" name="nom_piece_${pieceCounter}" placeholder="Désignation de la pièce" required>
                <input type="text" class="form-control" name="code_barre_${pieceCounter}" placeholder="Code barre">
                <div class="quantity-selector">
                    <button type="button" class="btn btn-outline-secondary quantity-decrease">–</button>
                    <input type="number" class="form-control text-center" name="quantite_${pieceCounter}" value="1" min="1">
                    <button type="button" class="btn btn-outline-secondary quantity-increase">+</button>
                </div>
                <input type="number" class="form-control" name="prix_estime_${pieceCounter}" placeholder="0.00" step="0.01" min="0" required>
                <button type="button" class="btn btn-outline-danger btn-sm remove-piece-btn" title="Supprimer cette pièce">
                    <i class="fas fa-trash"></i>
                </button>
            `;

        // Ajouter les événements pour les boutons quantité
        const decreaseBtn = wrapper.querySelector('.quantity-decrease');
        const increaseBtn = wrapper.querySelector('.quantity-increase');
        const quantityInput = wrapper.querySelector(`input[name="quantite_${pieceCounter}"]`);
        const removeBtn = wrapper.querySelector('.remove-piece-btn');

        decreaseBtn.addEventListener('click', function () {
            const currentValue = parseInt(quantityInput.value) || 1;
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        increaseBtn.addEventListener('click', function () {
            const currentValue = parseInt(quantityInput.value) || 1;
            quantityInput.value = currentValue + 1;
        });

        removeBtn.addEventListener('click', function () {
            wrapper.remove();
        });

        grid.parentNode.appendChild(wrapper);

        // Focus sur le champ nom de la nouvelle pièce
        const newNameInput = wrapper.querySelector(`input[name="nom_piece_${pieceCounter}"]`);
        if (newNameInput) {
            setTimeout(() => newNameInput.focus(), 100);
        }
    });
}

// Gestion des boutons quantité pour la pièce principale
const decreaseQtyBtn = document.getElementById('decrease-qty');
const increaseQtyBtn = document.getElementById('increase-qty');
const quantityMainInput = document.getElementById('quantite');

if (decreaseQtyBtn && increaseQtyBtn && quantityMainInput) {
    decreaseQtyBtn.addEventListener('click', function () {
        const currentValue = parseInt(quantityMainInput.value) || 1;
        if (currentValue > 1) {
            quantityMainInput.value = currentValue - 1;
        }
    });

    increaseQtyBtn.addEventListener('click', function () {
        const currentValue = parseInt(quantityMainInput.value) || 1;
        quantityMainInput.value = currentValue + 1;
    });
}

// Gestionnaire de soumission du formulaire supprimé pour éviter les doublons
// La soumission est maintenant gérée uniquement par le bouton "Enregistrer"
const form = document.getElementById('ajouterCommandeForm');
if (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Si le formulaire est soumis (par exemple par Entrée), déclencher le clic du bouton
        if (saveCommandeBtn && !isSubmitting) {
            saveCommandeBtn.click();
        }
    });
}

function soumettreCommandesMultiples(informationsCommunes, pieces, modal) {
    let commandesReussies = 0;
    let commandesEchouees = 0;
    const promesses = [];

    pieces.forEach((piece, index) => {
        const formData = new FormData();
        formData.append('client_id', informationsCommunes.client_id);
        formData.append('fournisseur_id', informationsCommunes.fournisseur_id);
        formData.append('statut', informationsCommunes.statut);

        // Ajouter reparation_id si fourni
        if (informationsCommunes.reparation_id) {
            formData.append('reparation_id', informationsCommunes.reparation_id);
        }

        // Ajouter les informations spécifiques de la pièce
        formData.append('nom_piece', piece.nom_piece);
        formData.append('code_barre', piece.code_barre || '');
        formData.append('quantite', piece.quantite);
        formData.append('prix_estime', piece.prix_estime);

        // Ajouter shop_id si disponible
        const shopIdMeta = document.querySelector('meta[name="shop-id"]');
        const shopIdInput = document.querySelector('input[name="shop_id"]');
        const shopId = shopIdMeta ? shopIdMeta.content : (shopIdInput ? shopIdInput.value : (window.shopId || ''));

        if (shopId) {
            formData.append('shop_id', shopId);
        }

        // Créer la promesse pour cette commande
        const promise = fetch('ajax/simple_commande_no_user.php', {
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
                } else {
                    commandesEchouees++;
                    console.error('Erreur création commande:', data);
                    if (data.message) {
                        alert('Erreur: ' + data.message);
                    }
                }
            })
            .catch(err => {
                console.error('Erreur fetch commande:', err);
                commandesEchouees++;
            });

        promesses.push(promise);
    });

    // Attendre que toutes les promesses soient résolues
    Promise.all(promesses).then(() => {

        // Réactiver le bouton
        isSubmitting = false;
        if (saveCommandeBtn) {
            saveCommandeBtn.disabled = false;
            saveCommandeBtn.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer';
        }

        if (commandesReussies > 0) {
            // Afficher un message de succès
            const message = commandesReussies === 1
                ? 'Commande ajoutée avec succès !'
                : `${commandesReussies} commande(s) ajoutée(s) avec succès !`;
            showNotification(message, 'success');

            // Fermer le modal
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }

            // Recharger la page après un délai
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Toutes les commandes ont échoué
            alert('Erreur: Aucune commande n\'a pu être créée. Veuillez vérifier vos données et réessayer.');
        }

        if (commandesEchouees > 0 && commandesReussies > 0) {
            // Certaines commandes ont échoué
            alert(`Attention: ${commandesEchouees} commande(s) n'ont pas pu être créées sur ${pieces.length} total.`);
        }
    });
}

});
