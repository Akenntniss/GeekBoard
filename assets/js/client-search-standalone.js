/**
 * Recherche client standalone - Sans dépendances
 */
(function () {
    'use strict';

    // console.log('🔍 Initialisation recherche client standalone');

    document.addEventListener('DOMContentLoaded', function () {
        const clientSearchInput = document.getElementById('nom_client_selectionne');
        const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
        const listeClientsDiv = document.getElementById('liste_clients_recherche_inline');

        if (!clientSearchInput || !resultatsDiv || !listeClientsDiv) {
            // console.log('❌ Éléments de recherche non trouvés');
            return;
        }

        // console.log('✅ Éléments de recherche trouvés');

        let searchTimeout;

        clientSearchInput.addEventListener('input', function () {
            const query = this.value.trim();

            clearTimeout(searchTimeout);

            if (query.length < 2) {
                resultatsDiv.style.setProperty('display', 'none', 'important');
                return;
            }

            searchTimeout = setTimeout(function () {
                // console.log('🔍 Recherche:', query);

                fetch('ajax/recherche_clients.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'terme=' + encodeURIComponent(query)
                })
                    .then(function (response) {
                        // console.log('📡 Réponse:', response.status);
                        return response.json();
                    })
                    .then(function (data) {
                        // console.log('📊 Données:', data);
                        listeClientsDiv.innerHTML = '';

                        if (data.success && data.clients && data.clients.length > 0) {
                            // console.log('✅ Clients trouvés:', data.clients.length);

                            data.clients.forEach(function (client) {
                                const clientItem = document.createElement('div');
                                clientItem.className = 'list-group-item list-group-item-action';
                                clientItem.style.cursor = 'pointer';
                                clientItem.style.padding = '10px';
                                clientItem.style.borderBottom = '1px solid #ddd';

                                const nameDiv = document.createElement('div');
                                nameDiv.innerHTML = '<strong>' + client.nom + '</strong>';
                                clientItem.appendChild(nameDiv);

                                const phoneDiv = document.createElement('div');
                                phoneDiv.innerHTML = '<small class="text-muted">' + (client.telephone || 'Pas de téléphone') + '</small>';
                                clientItem.appendChild(phoneDiv);

                                clientItem.addEventListener('click', function () {
                                    // console.log('✅ Client sélectionné:', client.nom);
                                    document.getElementById('client_id').value = client.id;
                                    clientSearchInput.value = client.nom;

                                    const clientSelectionne = document.getElementById('client_selectionne');
                                    if (clientSelectionne) {
                                        const nomEl = clientSelectionne.querySelector('.nom_client');
                                        const telEl = clientSelectionne.querySelector('.tel_client');
                                        if (nomEl) nomEl.textContent = client.nom;
                                        if (telEl) telEl.textContent = client.telephone || 'N/A';
                                        clientSelectionne.classList.remove('d-none');
                                    }

                                    resultatsDiv.style.setProperty('display', 'none', 'important');
                                });

                                listeClientsDiv.appendChild(clientItem);
                            });

                            // Forcer l'affichage avec setProperty pour que !important fonctionne
                            resultatsDiv.classList.remove('d-none');
                            resultatsDiv.style.setProperty('display', 'block', 'important');
                            resultatsDiv.style.setProperty('position', 'relative', 'important');
                            resultatsDiv.style.setProperty('z-index', '10000', 'important');
                            resultatsDiv.style.setProperty('background-color', '#ffffff', 'important');
                            resultatsDiv.style.setProperty('border', '2px solid #007bff', 'important');
                            resultatsDiv.style.setProperty('border-radius', '4px', 'important');
                            resultatsDiv.style.setProperty('max-height', '300px', 'important');
                            resultatsDiv.style.setProperty('overflow-y', 'auto', 'important');
                            resultatsDiv.style.setProperty('padding', '10px', 'important');
                            resultatsDiv.style.setProperty('margin-top', '5px', 'important');
                            resultatsDiv.style.setProperty('box-shadow', '0 4px 6px rgba(0,0,0,0.1)', 'important');

                            // Forcer aussi la liste
                            listeClientsDiv.style.setProperty('display', 'block', 'important');
                            listeClientsDiv.style.setProperty('min-height', '50px', 'important');

                            // console.log('👁️ Résultats affichés');
                            // console.log('📏 Dimensions resultatsDiv:', {
                            display: window.getComputedStyle(resultatsDiv).display,
                                visibility: window.getComputedStyle(resultatsDiv).visibility,
                                    height: resultatsDiv.offsetHeight,
                                        width: resultatsDiv.offsetWidth,
                                            top: resultatsDiv.offsetTop,
                                                left: resultatsDiv.offsetLeft
                        });
                // console.log('📏 Dimensions listeClientsDiv:', {
                display: window.getComputedStyle(listeClientsDiv).display,
                    childCount: listeClientsDiv.children.length,
                        innerHTML: listeClientsDiv.innerHTML.substring(0, 200)
            });
        } else {
            // console.log('❌ Aucun client trouvé');
            resultatsDiv.classList.add('d-none');
            resultatsDiv.style.setProperty('display', 'none', 'important');
        }
                    })
        .catch(function (error) {
            console.error('❌ Erreur:', error);
            resultatsDiv.style.setProperty('display', 'none', 'important');
        });
}, 300);
        });

// Empêcher la propagation des clics
resultatsDiv.addEventListener('click', function (e) {
    e.stopPropagation();
});

clientSearchInput.addEventListener('click', function (e) {
    e.stopPropagation();
});

        // console.log('✅ Recherche client standalone initialisée');
    });
}) ();
