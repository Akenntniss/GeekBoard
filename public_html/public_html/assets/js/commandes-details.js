// Fonction pour afficher les détails d'une commande
function afficherDetailsCommande(event, commandeId) {
    console.log("Fonction afficherDetailsCommande appelée avec commandeId:", commandeId);
    
    // Empêcher la propagation de l'événement
    event.stopPropagation();
    
    // Réinitialiser le modal
    resetCommandeModal();
    
    // Afficher le modal
    const commandeModal = document.getElementById('commandeDetailsModal');
    if (commandeModal) {
        const bsModal = new bootstrap.Modal(commandeModal);
        bsModal.show();
        
        // Charger les détails de la commande via AJAX
        fetch(`ajax/get_commande_details.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${commandeId}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Données reçues:", data);
            
            if (data.success && data.commande) {
                // Masquer le loader et afficher le contenu
                document.getElementById('commande-description-loader').style.display = 'none';
                document.getElementById('commande-details-content').style.display = 'block';
                
                // Remplir le modal avec les données
                remplirModalCommande(data.commande);
            } else {
                // Afficher l'erreur
                afficherErreurCommande(data.message || "Erreur lors du chargement des détails de la commande");
            }
        })
        .catch(error => {
            console.error("Erreur lors du chargement des détails de la commande:", error);
            afficherErreurCommande("Erreur de connexion lors du chargement des détails");
        });
    } else {
        console.error("Le modal de commande n'a pas été trouvé dans le DOM");
    }
}

// Fonction pour réinitialiser le modal
function resetCommandeModal() {
    // Afficher le loader et masquer le contenu
    document.getElementById('commande-description-loader').style.display = 'block';
    document.getElementById('commande-details-content').style.display = 'none';
    document.getElementById('commande-error-container').style.display = 'none';
    
    // Réinitialiser les sections conditionnelles
    document.getElementById('commande-code-barre-section').style.display = 'none';
    document.getElementById('commande-description-section').style.display = 'none';
    document.getElementById('commande-date-commande-section').style.display = 'none';
    document.getElementById('commande-date-reception-section').style.display = 'none';
    document.getElementById('commande-notes-section').style.display = 'none';
    document.getElementById('commande-commentaire-section').style.display = 'none';
    
    // Réinitialiser la largeur de la date de création
    const dateCreationContainer = document.getElementById('commande-date-creation').closest('.col-md-6, .col-md-12');
    if (dateCreationContainer) {
        dateCreationContainer.className = 'col-md-6';
    }
}

// Fonction pour remplir le modal avec les données de la commande
function remplirModalCommande(commande) {
    // En-tête : Référence et nom de la pièce
    document.getElementById('commande-reference').textContent = commande.reference || 'N/A';
    document.getElementById('commande-piece-nom').textContent = commande.nom_piece || '';
    
    // Statut avec couleur appropriée
    const statutElement = document.getElementById('commande-statut');
    const statutInfo = getStatutInfo(commande.statut);
    statutElement.textContent = statutInfo.text;
    statutElement.className = `modern-priority-badge`;
    statutElement.style.background = getStatutColor(statutInfo.class);
    statutElement.style.color = 'white';
    
    // Urgence
    const urgenceInfo = getUrgenceInfo(commande.urgence);
    const urgenceElement = document.getElementById('commande-urgence');
    urgenceElement.textContent = urgenceInfo.text;
    urgenceElement.className = 'modern-status-badge';
    urgenceElement.style.background = getUrgenceColor(urgenceInfo.class);
    urgenceElement.style.color = 'white';
    
    // Section Client
    const clientNom = [commande.client_prenom, commande.client_nom].filter(Boolean).join(' ') || 'Client non spécifié';
    document.getElementById('commande-client').textContent = clientNom;
    
    const clientTel = commande.client_telephone || '';
    const clientTelElement = document.getElementById('commande-client-tel');
    if (clientTel) {
        clientTelElement.textContent = `Tél: ${clientTel}`;
        clientTelElement.style.display = 'block';
    } else {
        clientTelElement.style.display = 'none';
    }
    
    // Section Fournisseur
    document.getElementById('commande-fournisseur').textContent = commande.fournisseur_nom || 'Fournisseur non spécifié';
    
    // Section Détails de la pièce
    document.getElementById('commande-piece-nom-detail').textContent = commande.nom_piece || 'Non spécifié';
    document.getElementById('commande-quantite').textContent = commande.quantite || '1';
    
    const prix = commande.prix_estime ? parseFloat(commande.prix_estime).toFixed(2) + ' €' : 'Non spécifié';
    document.getElementById('commande-prix').textContent = prix;
    
    // Code-barres (conditionnel) - ajuster la largeur de la date de création
    const dateCreationContainer = document.getElementById('commande-date-creation').closest('.col-md-6');
    if (commande.code_barre) {
        document.getElementById('commande-code-barre').textContent = commande.code_barre;
        document.getElementById('commande-code-barre-section').style.display = 'block';
        // Date de création prend la moitié de la largeur
        dateCreationContainer.className = 'col-md-6';
    } else {
        // Date de création prend toute la largeur quand il n'y a pas de code-barres
        dateCreationContainer.className = 'col-md-12';
    }
    
    // Description (conditionnel)
    if (commande.description) {
        document.getElementById('commande-description').textContent = commande.description;
        document.getElementById('commande-description-section').style.display = 'block';
    }
    
    // Dates
    document.getElementById('commande-date-creation').textContent = formatDate(commande.date_creation);
    
    if (commande.date_commande) {
        document.getElementById('commande-date-commande').textContent = formatDate(commande.date_commande);
        document.getElementById('commande-date-commande-section').style.display = 'block';
    }
    
    if (commande.date_reception) {
        document.getElementById('commande-date-reception').textContent = formatDate(commande.date_reception);
        document.getElementById('commande-date-reception-section').style.display = 'block';
    }
    
    // Notes (conditionnel)
    if (commande.notes) {
        document.getElementById('commande-notes').textContent = commande.notes;
        document.getElementById('commande-notes-section').style.display = 'block';
    }
    
    // Commentaire interne (conditionnel)
    if (commande.commentaire_interne) {
        document.getElementById('commande-commentaire').textContent = commande.commentaire_interne;
        document.getElementById('commande-commentaire-section').style.display = 'block';
    }
}

// Fonction pour afficher une erreur
function afficherErreurCommande(message) {
    document.getElementById('commande-description-loader').style.display = 'none';
    document.getElementById('commande-details-content').style.display = 'none';
    document.getElementById('commande-error-container').style.display = 'block';
    document.querySelector('#commande-error-container .error-message').textContent = message;
}

// Fonction pour obtenir la couleur du statut
function getStatutColor(statutClass) {
    switch(statutClass) {
        case 'warning': return 'linear-gradient(135deg, #ffa502, #ff6348)';
        case 'primary': return 'linear-gradient(135deg, #3742fa, #2f3542)';
        case 'success': return 'linear-gradient(135deg, #2ed573, #1e90ff)';
        case 'danger': return 'linear-gradient(135deg, #ff4757, #c44569)';
        case 'info': return 'linear-gradient(135deg, #70a1ff, #5352ed)';
        case 'secondary': return 'linear-gradient(135deg, #57606f, #3d4454)';
        default: return 'linear-gradient(135deg, #57606f, #3d4454)';
    }
}

// Fonction pour obtenir la couleur de l'urgence
function getUrgenceColor(urgenceClass) {
    switch(urgenceClass) {
        case 'warning': return 'linear-gradient(135deg, #ffa502, #ff6348)';
        case 'danger': return 'linear-gradient(135deg, #ff4757, #c44569)';
        case 'secondary': return 'linear-gradient(135deg, #57606f, #3d4454)';
        default: return 'linear-gradient(135deg, #57606f, #3d4454)';
    }
}

// Fonction pour obtenir les informations de statut
function getStatutInfo(statut) {
    switch(statut) {
        case 'en_attente':
            return { text: 'En attente', class: 'warning' };
        case 'commande':
            return { text: 'Commandé', class: 'primary' };
        case 'recue':
            return { text: 'Reçu', class: 'success' };
        case 'annulee':
            return { text: 'Annulé', class: 'danger' };
        case 'urgent':
            return { text: 'URGENT', class: 'danger' };
        case 'termine':
            return { text: 'Terminé', class: 'success' };
        case 'utilise':
            return { text: 'Utilisé', class: 'info' };
        case 'a_retourner':
            return { text: 'À retourner', class: 'secondary' };
        default:
            return { text: statut || 'Inconnu', class: 'secondary' };
    }
}

// Fonction pour obtenir les informations d'urgence
function getUrgenceInfo(urgence) {
    switch(urgence) {
        case 'urgent':
            return { text: 'Urgent', class: 'warning' };
        case 'tres_urgent':
            return { text: 'Très urgent', class: 'danger' };
        case 'normal':
        default:
            return { text: 'Normal', class: 'secondary' };
    }
}

// Fonction pour formater les dates
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        console.error('Erreur lors du formatage de la date:', error);
        return dateString;
    }
}

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log("Script commandes-details.js chargé");
});
