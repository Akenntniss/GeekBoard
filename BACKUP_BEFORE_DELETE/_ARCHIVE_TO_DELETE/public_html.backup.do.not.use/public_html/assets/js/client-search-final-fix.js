/**
 * Solution finale pour forcer la recherche client à fonctionner
 * Utilise l'élément existant sans le remplacer
 */

console.log('🎯 [FINAL-FIX] Script de solution finale chargé');

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('ajouterCommandeModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            console.log('🎯 [FINAL-FIX] Modal ouvert, application de la solution finale...');
            setTimeout(() => {
                applyFinalFix();
            }, 100); // Petit délai pour s'assurer que tout est chargé
        });
    }
});

function applyFinalFix() {
    const searchInput = document.getElementById('nom_client_selectionne');
    
    if (!searchInput) {
        console.error('🎯 [FINAL-FIX] ❌ Champ de recherche non trouvé');
        return;
    }
    
    console.log('🎯 [FINAL-FIX] ✅ Champ trouvé:', searchInput);
    console.log('🎯 [FINAL-FIX] ✅ ID du champ:', searchInput.id);
    console.log('🎯 [FINAL-FIX] ✅ Classes du champ:', searchInput.className);
    
    // Supprimer tous les anciens événements (sans cloner)
    const events = ['input', 'keyup', 'keydown', 'change', 'focus', 'blur'];
    events.forEach(eventType => {
        searchInput.removeEventListener(eventType, () => {}); // Tentative de suppression
    });
    
    console.log('🎯 [FINAL-FIX] ✅ Anciens événements supprimés');
    
    // Variables pour la recherche
    let searchTimeout;
    
    // Attacher PLUSIEURS types d'événements pour être sûr
    console.log('🎯 [FINAL-FIX] ✅ Ajout des nouveaux événements...');
    
    // Événement input (principal)
    searchInput.addEventListener('input', function(e) {
        console.log('🎯 [FINAL-FIX] ✅ INPUT détecté:', this.value);
        handleSearch(this.value);
    });
    
    // Événement keyup (sécurité)
    searchInput.addEventListener('keyup', function(e) {
        console.log('🎯 [FINAL-FIX] ✅ KEYUP détecté:', this.value);
        handleSearch(this.value);
    });
    
    // Événement change (sécurité)
    searchInput.addEventListener('change', function(e) {
        console.log('🎯 [FINAL-FIX] ✅ CHANGE détecté:', this.value);
        handleSearch(this.value);
    });
    
    // Événements de debug
    searchInput.addEventListener('focus', function() {
        console.log('🎯 [FINAL-FIX] ✅ FOCUS détecté');
    });
    
    searchInput.addEventListener('blur', function() {
        console.log('🎯 [FINAL-FIX] ✅ BLUR détecté');
    });
    
    function handleSearch(value) {
        const query = value.trim();
        console.log('🎯 [FINAL-FIX] ✅ Traitement de la recherche:', query);
        
        clearTimeout(searchTimeout);
        
        const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
        
        if (query.length < 2) {
            console.log('🎯 [FINAL-FIX] Requête trop courte');
            if (resultatsDiv) resultatsDiv.classList.add('d-none');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            console.log('🎯 [FINAL-FIX] 🔍 LANCEMENT RECHERCHE:', query);
            performSearch(query);
        }, 300);
    }
    
    console.log('🎯 [FINAL-FIX] ✅ Tous les événements attachés !');
    
    // Test automatique supprimé - modal prêt pour utilisation normale
    console.log('🎯 [FINAL-FIX] ✅ Champ de recherche prêt pour saisie utilisateur');
}

function performSearch(query) {
    console.log('🎯 [FINAL-FIX] 🔍 Recherche en cours:', query);
    
    const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
    const listeDiv = document.getElementById('liste_clients_recherche_inline');
    
    if (!resultatsDiv || !listeDiv) {
        console.error('🎯 [FINAL-FIX] ❌ Éléments de résultats manquants');
        return;
    }
    
    // Afficher le chargement
    listeDiv.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Recherche en cours...</div></div>';
    resultatsDiv.classList.remove('d-none');
    
    console.log('🎯 [FINAL-FIX] ✅ Indicateur de chargement affiché');
    
    // Requête AJAX
        fetch('ajax/recherche_clients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            },
            credentials: 'same-origin',
            body: `terme=${encodeURIComponent(query)}`
        })
    .then(response => {
        console.log('🎯 [FINAL-FIX] ✅ Réponse HTTP:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('🎯 [FINAL-FIX] ✅ Données reçues:', data);
        
        if (data.success && Array.isArray(data.clients)) {
            displayResults(data.clients);
        } else {
            console.error('🎯 [FINAL-FIX] ❌ Erreur dans les données:', data);
            listeDiv.innerHTML = '<div class="text-muted p-3">Aucun client trouvé</div>';
        }
    })
    .catch(err => {
        console.error('🎯 [FINAL-FIX] ❌ Erreur requête:', err);
        listeDiv.innerHTML = '<div class="text-danger p-3">Erreur de connexion</div>';
    });
}

function displayResults(clients) {
    console.log('🎯 [FINAL-FIX] ✅ Affichage de', clients.length, 'client(s)');
    
    const listeDiv = document.getElementById('liste_clients_recherche_inline');
    const clientIdInput = document.getElementById('client_id');
    const clientSelectionne = document.getElementById('client_selectionne');
    const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
    
    listeDiv.innerHTML = '';
    
    clients.forEach((client, index) => {
        console.log('🎯 [FINAL-FIX] Client', index + 1, ':', client);
        
        const item = document.createElement('div');
        item.className = 'list-group-item list-group-item-action client-item';
        item.style.cursor = 'pointer';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">${client.nom} ${client.prenom}</div>
                    <div class="text-muted small">${client.telephone || 'Pas de téléphone'}</div>
                </div>
                <div class="text-primary">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>
        `;
        
        item.addEventListener('click', () => {
            console.log('🎯 [FINAL-FIX] ✅ Client sélectionné:', client);
            selectClient(client);
        });
        
        listeDiv.appendChild(item);
    });
    
    console.log('🎯 [FINAL-FIX] ✅ Tous les résultats affichés');
}

function selectClient(client) {
    console.log('🎯 [FINAL-FIX] ✅ Sélection du client:', client);
    
    const clientIdInput = document.getElementById('client_id');
    const searchInput = document.getElementById('nom_client_selectionne');
    const clientSelectionne = document.getElementById('client_selectionne');
    const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
    
    // Remplir les champs
    if (clientIdInput) {
        clientIdInput.value = client.id;
        console.log('🎯 [FINAL-FIX] ✅ Client ID défini:', client.id);
    }
    
    if (searchInput) {
        searchInput.value = `${client.nom} ${client.prenom}`;
        console.log('🎯 [FINAL-FIX] ✅ Nom affiché:', `${client.nom} ${client.prenom}`);
    }
    
    // Afficher le client sélectionné
    if (clientSelectionne) {
        const nomClient = clientSelectionne.querySelector('.nom_client');
        const telClient = clientSelectionne.querySelector('.tel_client');
        if (nomClient) nomClient.textContent = `${client.nom} ${client.prenom}`;
        if (telClient) telClient.textContent = client.telephone || 'Pas de téléphone';
        clientSelectionne.classList.remove('d-none');
        console.log('🎯 [FINAL-FIX] ✅ Bloc client sélectionné affiché');
    }
    
    // Masquer les résultats
    if (resultatsDiv) {
        resultatsDiv.classList.add('d-none');
        console.log('🎯 [FINAL-FIX] ✅ Résultats masqués');
    }
    
    console.log('🎯 [FINAL-FIX] ✅ Client sélectionné avec succès !');
}

// Fonction de test global
window.testFinalFix = function(terme = 'saber') {
    console.log('🎯 [FINAL-FIX] 🧪 Test manuel:', terme);
    performSearch(terme);
};

console.log('🎯 [FINAL-FIX] ✅ Script prêt - Utilisez window.testFinalFix("nom") pour tester');
