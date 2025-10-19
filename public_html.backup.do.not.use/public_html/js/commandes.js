function editCommande(id) {
    console.log('Édition de la commande:', id);
    
    // Afficher un indicateur de chargement si disponible
    const loadingElement = document.querySelector('.loading-indicator');
    if (loadingElement) {
        loadingElement.style.display = 'block';
    }
    
    // Récupérer les informations de la commande
    fetch(`ajax/get_commande.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Masquer l'indicateur de chargement
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            
            if (data.success) {
                // Remplir le formulaire avec les données si les éléments existent
                const commandeIdEl = document.getElementById('commande_id');
                const fournisseurIdEl = document.getElementById('fournisseur_id');
                const clientIdEl = document.getElementById('client_id');
                const dateCommandeEl = document.getElementById('date_commande');
                const dateReceptionEl = document.getElementById('date_reception');
                const statutEl = document.getElementById('statut');
                const notesEl = document.getElementById('notes');
                
                if (commandeIdEl) commandeIdEl.value = data.commande.id;
                if (fournisseurIdEl) fournisseurIdEl.value = data.commande.fournisseur_id || '';
                if (clientIdEl) clientIdEl.value = data.commande.client_id || '';
                if (dateCommandeEl) dateCommandeEl.value = data.commande.date_commande || '';
                if (dateReceptionEl) dateReceptionEl.value = data.commande.date_reception || '';
                if (statutEl) statutEl.value = data.commande.statut || '';
                if (notesEl) notesEl.value = data.commande.notes || '';
                
                // Afficher le modal s'il existe
                const modalElement = document.getElementById('commandeModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                modal.show();
                }
            } else {
                // Vérifier si une redirection est demandée
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    const message = data.message || 'Erreur lors de la récupération des informations de la commande';
                    alert(message);
                }
            }
        })
        .catch(error => {
            // Masquer l'indicateur de chargement
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            console.error('Erreur serveur:', error);
            alert('Erreur de communication avec le serveur');
        });
} 