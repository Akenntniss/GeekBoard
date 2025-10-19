// CORRECTIONS POUR LE MODAL HISTORIQUE SEULEMENT

// Fonction pour afficher l'historique des transactions
window.afficherHistoriqueTransactions = function(partenaireId, partenaireNom) {
    console.log('Affichage historique pour:', partenaireId, partenaireNom);
    
    try {
        // Mettre à jour le nom du partenaire dans le modal
        const partenaireNomEl = document.getElementById('partenaireNom');
        if (partenaireNomEl) {
            partenaireNomEl.textContent = partenaireNom;
        }
        
        // Stocker l'ID du partenaire dans le modal pour le rechargement
        const modalEl = document.getElementById('historiqueTransactionsModal');
        if (modalEl) {
            modalEl.dataset.partenaireId = partenaireId;
        }
        
        // Afficher le modal - essayer plusieurs méthodes
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            console.log('Utilisation de Bootstrap Modal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else if (typeof $ !== 'undefined') {
            console.log('Utilisation de jQuery Modal');
            $('#historiqueTransactionsModal').modal('show');
        } else {
            console.log('Affichage direct du modal');
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Ajouter backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modal-backdrop-temp';
            document.body.appendChild(backdrop);
        }
        
        // Charger les transactions
        chargerTransactionsPartenaire(partenaireId);
        
    } catch (error) {
        console.error('Erreur afficherHistoriqueTransactions:', error);
        alert('Erreur lors de l\'ouverture du modal: ' + error.message);
    }
}

// Fonction pour charger les transactions d'un partenaire
function chargerTransactionsPartenaire(partenaireId) {
    console.log('Chargement transactions pour partenaire:', partenaireId);
    
    const historiqueDiv = document.getElementById('historiqueTransactions');
    if (!historiqueDiv) {
        console.error('Élément historiqueTransactions non trouvé');
        return;
    }
    
    // Afficher le loading
    historiqueDiv.innerHTML = `
        <div class="loading-state">
            <div class="loading-spinner"></div>
            <p>Chargement de l'historique...</p>
        </div>
    `;
    
    // ... reste de la fonction
}

// Au chargement de la page, vérifier que tout est prêt
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - vérification des éléments...');
    console.log('Modal historique:', document.getElementById('historiqueTransactionsModal') ? 'TROUVÉ' : 'MANQUANT');
    console.log('Bootstrap disponible:', typeof bootstrap !== 'undefined' ? 'OUI' : 'NON');
    console.log('Fonction afficherHistoriqueTransactions:', typeof window.afficherHistoriqueTransactions !== 'undefined' ? 'DÉFINIE' : 'MANQUANTE');
});

