// Variables globales pour le modal de statut
let currentCommandeId = null;
let currentStatut = null;

// Fonction pour ouvrir le modal de statut
function ouvrirModalStatut(event, commandeId, statutActuel, reference, nomPiece) {
    console.log("Ouverture modal statut pour commande:", commandeId);
    
    // Empêcher la propagation de l'événement pour éviter d'ouvrir le modal de détails
    event.stopPropagation();
    
    // Stocker les informations de la commande
    currentCommandeId = commandeId;
    currentStatut = statutActuel;
    
    // Remplir les informations de la commande
    document.getElementById('statut-commande-reference').textContent = reference || 'N/A';
    document.getElementById('statut-piece-nom').textContent = nomPiece || '';
    
    // Afficher le statut actuel
    const statutActuelElement = document.getElementById('statut-actuel');
    const statutInfo = getStatutInfo(statutActuel);
    statutActuelElement.textContent = statutInfo.text;
    statutActuelElement.className = 'modern-priority-badge';
    statutActuelElement.style.background = getStatutColor(statutInfo.class);
    statutActuelElement.style.color = 'white';
    
    // Réinitialiser la sélection des options
    resetStatusOptions();
    
    // Masquer les sections d'erreur et loader
    document.getElementById('statut-error-container').style.display = 'none';
    document.getElementById('statut-update-loader').style.display = 'none';
    
    // Afficher le modal
    const modal = document.getElementById('commandeStatutModal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

// Fonction pour réinitialiser les options de statut
function resetStatusOptions() {
    const options = document.querySelectorAll('.status-option-card');
    options.forEach(option => {
        option.classList.remove('selected');
    });
}

// Fonction pour gérer la sélection d'une option de statut
function handleStatusSelection(statusOption) {
    const nouveauStatut = statusOption.getAttribute('data-status');
    
    // Ne pas permettre de sélectionner le même statut
    if (nouveauStatut === currentStatut) {
        return;
    }
    
    // Réinitialiser toutes les sélections
    resetStatusOptions();
    
    // Marquer comme sélectionné
    statusOption.querySelector('.status-option-card').classList.add('selected');
    
    // Mettre à jour le statut immédiatement
    updateCommandeStatut(currentCommandeId, nouveauStatut);
}

// Fonction pour mettre à jour le statut de la commande
function updateCommandeStatut(commandeId, nouveauStatut) {
    console.log(`Mise à jour statut commande ${commandeId} vers ${nouveauStatut}`);
    
    // Fermer le modal immédiatement
    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('commandeStatutModal'));
    if (modalInstance) {
        modalInstance.hide();
    }
    
    // Envoyer la requête AJAX en arrière-plan
    fetch('ajax/update_commande_statut.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${commandeId}&statut=${nouveauStatut}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Réponse serveur:", data);
        
        if (data.success) {
            // Succès - recharger la page pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            // En cas d'erreur, afficher une notification ou recharger quand même
            console.error("Erreur lors de la mise à jour:", data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        // En cas d'erreur de connexion, recharger quand même
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    });
}

// Fonction pour afficher une erreur
function afficherErreurStatut(message) {
    document.getElementById('statut-update-loader').style.display = 'none';
    document.getElementById('statut-error-container').style.display = 'block';
    document.querySelector('#statut-error-container .error-message').textContent = message;
}

// Fonction pour obtenir les informations de statut (réutilisée depuis commandes-details.js)
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

// Fonction pour obtenir la couleur du statut (réutilisée depuis commandes-details.js)
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

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log("Script commande-statut.js chargé");
    
    // Ajouter les gestionnaires d'événements pour les options de statut
    const statusOptions = document.querySelectorAll('.status-option');
    statusOptions.forEach(option => {
        option.addEventListener('click', function() {
            handleStatusSelection(this);
        });
    });
});
