/**
 * Script ultra-simple pour forcer la recherche client à fonctionner
 */

console.log('🚀 [FORCE-FIX] Script de forçage simple chargé');

// Attendre que le modal soit ouvert
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('ajouterCommandeModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            console.log('🚀 [FORCE-FIX] Modal ouvert, forçage de l\'événement...');
            forceClientSearchEvent();
        });
    }
});

function forceClientSearchEvent() {
    const searchInput = document.getElementById('nom_client_selectionne');
    
    if (!searchInput) {
        console.error('🚀 [FORCE-FIX] ❌ Champ de recherche non trouvé');
        return;
    }
    
    console.log('🚀 [FORCE-FIX] ✅ Champ trouvé, ajout de l\'événement...');
    
    // Supprimer tous les anciens événements en clonant l'élément
    const newInput = searchInput.cloneNode(true);
    searchInput.parentNode.replaceChild(newInput, searchInput);
    
    console.log('🚀 [FORCE-FIX] ✅ Champ remplacé, ajout du nouvel événement...');
    
    // Variables pour la recherche
    let searchTimeout;
    
    // Attacher l'événement input
    newInput.addEventListener('input', function(e) {
        const query = this.value.trim();
        console.log('🚀 [FORCE-FIX] ✅ INPUT DÉTECTÉ:', query);
        
        clearTimeout(searchTimeout);
        
        const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
        
        if (query.length < 2) {
            console.log('🚀 [FORCE-FIX] Requête trop courte');
            if (resultatsDiv) resultatsDiv.classList.add('d-none');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            console.log('🚀 [FORCE-FIX] 🔍 LANCEMENT RECHERCHE:', query);
            performClientSearch(query);
        }, 300);
    });
    
    // Test de focus
    newInput.addEventListener('focus', function() {
        console.log('🚀 [FORCE-FIX] Focus sur le champ');
    });
    
    // Test de blur
    newInput.addEventListener('blur', function() {
        console.log('🚀 [FORCE-FIX] Blur sur le champ');
    });
    
    console.log('🚀 [FORCE-FIX] ✅ Tous les événements attachés !');
}

function performClientSearch(query) {
    console.log('🚀 [FORCE-FIX] 🔍 Recherche en cours:', query);
    
    const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
    const listeDiv = document.getElementById('liste_clients_recherche_inline');
    
    if (!resultatsDiv || !listeDiv) {
        console.error('🚀 [FORCE-FIX] ❌ Éléments de résultats manquants');
        return;
    }
    
    // Afficher le chargement
    listeDiv.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>';
    resultatsDiv.classList.remove('d-none');
    
    // Requête AJAX
    fetch('ajax/recherche_clients.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: `terme=${encodeURIComponent(query)}`
    })
    .then(response => {
        console.log('🚀 [FORCE-FIX] Réponse HTTP:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('🚀 [FORCE-FIX] Données reçues:', data);
        
        if (data.success && Array.isArray(data.clients)) {
            displayResults(data.clients);
        } else {
            console.error('🚀 [FORCE-FIX] ❌ Erreur dans les données:', data);
            listeDiv.innerHTML = '<div class="text-muted p-3">Aucun client trouvé</div>';
        }
    })
    .catch(err => {
        console.error('🚀 [FORCE-FIX] ❌ Erreur requête:', err);
        listeDiv.innerHTML = '<div class="text-danger p-3">Erreur de connexion</div>';
    });
}

function displayResults(clients) {
    console.log('🚀 [FORCE-FIX] Affichage de', clients.length, 'client(s)');
    
    const listeDiv = document.getElementById('liste_clients_recherche_inline');
    const clientIdInput = document.getElementById('client_id');
    const clientSelectionne = document.getElementById('client_selectionne');
    const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
    
    listeDiv.innerHTML = '';
    
    clients.forEach((client, index) => {
        console.log('🚀 [FORCE-FIX] Client', index + 1, ':', client);
        
        const item = document.createElement('div');
        item.className = 'list-group-item list-group-item-action client-item';
        item.style.cursor = 'pointer';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">${client.nom} ${client.prenom}</div>
                    <div class="text-muted small">${client.telephone || 'Pas de téléphone'}</div>
                </div>
            </div>
        `;
        
        item.addEventListener('click', () => {
            console.log('🚀 [FORCE-FIX] ✅ Client sélectionné:', client);
            
            // Remplir les champs
            if (clientIdInput) clientIdInput.value = client.id;
            
            const searchInput = document.getElementById('nom_client_selectionne');
            if (searchInput) searchInput.value = `${client.nom} ${client.prenom}`;
            
            // Afficher le client sélectionné
            if (clientSelectionne) {
                const nomClient = clientSelectionne.querySelector('.nom_client');
                const telClient = clientSelectionne.querySelector('.tel_client');
                if (nomClient) nomClient.textContent = `${client.nom} ${client.prenom}`;
                if (telClient) telClient.textContent = client.telephone || 'Pas de téléphone';
                clientSelectionne.classList.remove('d-none');
            }
            
            // Masquer les résultats
            if (resultatsDiv) resultatsDiv.classList.add('d-none');
            
            console.log('🚀 [FORCE-FIX] ✅ Client sélectionné avec succès');
        });
        
        listeDiv.appendChild(item);
    });
    
    console.log('🚀 [FORCE-FIX] ✅ Tous les résultats affichés');
}

console.log('🚀 [FORCE-FIX] Script prêt à fonctionner');


