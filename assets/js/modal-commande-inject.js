
document.addEventListener('DOMContentLoaded', function () {
    // Fetch et injection du modal depuis modals.php
    fetch('modals.php')
        .then(response => response.text())
        .then(html => {
            // Parser le HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Extraire le modal ajouterCommandeModal
            const modal = doc.getElementById('ajouterCommandeModal');
            if (modal) {
                // Injecter le modal dans le DOM
                document.body.appendChild(modal);

                // Extraire et injecter les scripts associés
                const scripts = doc.querySelectorAll('script');
                scripts.forEach(script => {
                    if (script.textContent && (
                        script.textContent.includes('ajouterCommandeModal') ||
                        script.textContent.includes('quantite') ||
                        script.textContent.includes('ajouter-piece-btn')
                    )) {
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.head.appendChild(newScript);
                    }
                });

                // Initialiser le modal existant
                initializeExistingModal();
            }
        })
        .catch(error => {
            console.error('Erreur chargement modal:', error);
        });
});

function initializeExistingModal() {

    // Vérifier la présence du bouton "Ajouter une autre pièce"
    const addPieceBtn = document.getElementById('ajouter-piece-btn');
    if (addPieceBtn) {
    } else {
    }

    // Attacher les événements aux boutons d'ouverture
    const commandeButtons = document.querySelectorAll('[data-bs-target="#ajouterCommandeModal"], .action-order, .order-card');

    commandeButtons.forEach(button => {

        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Utiliser Bootstrap pour ouvrir le modal
            const modal = document.getElementById('ajouterCommandeModal');
            if (modal) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            }
        });
    });

    // Initialiser les fonctionnalités du modal
    initializeClientSearch();
    initializeNewClientButton();
    initializeSuppliersDropdown();

    console.log('✅ Modal ajouterCommandeModal configuré');
}

// FONCTION D'INITIALISATION DE LA RECHERCHE CLIENT
function initializeClientSearch() {

    const clientSearchInput = document.getElementById('nom_client_selectionne');
    const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
    const listeClientsDiv = document.getElementById('liste_clients_recherche_inline');

    if (!clientSearchInput || !resultatsDiv || !listeClientsDiv) {
        return;
    }

    let searchTimeout;

    clientSearchInput.addEventListener('input', function () {
        const query = this.value.trim();

        // Effacer le timeout précédent
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            resultatsDiv.classList.add('d-none');
            return;
        }

        // Délai de 300ms avant la recherche
        searchTimeout = setTimeout(() => {
            // Faire la requête AJAX
            fetch('ajax/recherche_clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'query=' + encodeURIComponent(query)
            })
                .then(response => response.json())
                .then(data => {
                    listeClientsDiv.innerHTML = '';

                    if (data.success && data.clients.length > 0) {
                        data.clients.forEach(client => {
                            const clientItem = document.createElement('div');
                            clientItem.className = 'list-group-item list-group-item-action';
                            clientItem.style.cursor = 'pointer';
                            clientItem.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${client.nom}</strong>
                                    <small class="text-muted d-block">${client.telephone || 'Pas de téléphone'}</small>
                                </div>
                            </div>
                        `;

                            clientItem.addEventListener('click', function () {
                                // Sélectionner le client
                                document.getElementById('client_id').value = client.id;
                                clientSearchInput.value = client.nom;

                                // Afficher les infos du client sélectionné
                                const clientSelectionne = document.getElementById('client_selectionne');
                                if (clientSelectionne) {
                                    clientSelectionne.querySelector('.nom_client').textContent = client.nom;
                                    clientSelectionne.querySelector('.tel_client').textContent = client.telephone || 'N/A';
                                    clientSelectionne.classList.remove('d-none');
                                }

                                // Masquer les résultats
                                resultatsDiv.classList.add('d-none');
                            });

                            listeClientsDiv.appendChild(clientItem);
                        });


                        resultatsDiv.classList.remove('d-none');
                        resultatsDiv.style.display = 'block';
                        resultatsDiv.style.position = 'relative';
                        resultatsDiv.style.zIndex = '9999';
                    } else {
                        resultatsDiv.classList.add('d-none');
                        resultatsDiv.style.display = 'none';
                    }
                })
                .catch(error => {
                    resultatsDiv.classList.add('d-none');
                });
        }, 300);
    });

    // Empêcher que les clics sur les résultats ne ferment la div
    resultatsDiv.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    // Empêcher que les clics sur le champ de recherche ne ferment les résultats
    clientSearchInput.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    console.log('✅ Recherche client initialisée');
}

// FONCTION D'INITIALISATION DU BOUTON NOUVEAU CLIENT
function initializeNewClientButton() {

    const newClientBtn = document.getElementById('newClientBtn');
    if (!newClientBtn) {
        return;
    }

    newClientBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        // S'assurer que la fonction ultra-simple est utilisée
        if (typeof window.createNewClientModal === 'function') {
            window.createNewClientModal();
        } else {
        }
    });

    console.log('✅ Bouton nouveau client initialisé');
}

function createNewClientModal_OLD() {

    // Supprimer tout modal existant
    const existingModal = document.getElementById('nouveauClientModal_temp');
    if (existingModal) {
        existingModal.remove();
    }

    // FORCER LE MODAL AU PREMIER PLAN ABSOLU
    // 1. Masquer TOUT le reste
    document.body.style.overflow = 'hidden';

    // 2. Créer un overlay avec z-index maximum
    const overlay = document.createElement('div');
    overlay.id = 'modal-overlay-ultra';
    overlay.style.cssText = `
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(0,0,0,0.95) !important;
        z-index: 15000 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        backdrop-filter: blur(15px) !important;
    `;

    // 3. Modal HTML ultra-simple
    const modalHTML = `
        <div id="nouveauClientModal_temp" style="
            width: 450px;
            max-width: 90%;
            background: #0a0a0a;
            border: 3px solid #00d4ff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 100px rgba(0, 212, 255, 0.8), 0 0 200px rgba(0, 212, 255, 0.4);
            position: relative;
            z-index: 15001 !important;
        ">
            <!-- Header -->
            <div style="
                padding: 2rem;
                text-align: center;
                background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);
                color: #ffffff;
                position: relative;
                border-bottom: 2px solid #00d4ff;
            ">
                <h2 style="
                    margin: 0;
                    font-size: 1.8rem;
                    font-weight: 700;
                    font-family: 'Orbitron', monospace;
                    text-shadow: 0 0 20px rgba(0, 212, 255, 0.8);
                    color: #00d4ff;
                ">👤 NOUVEAU CLIENT</h2>
                <button id="simple_close" style="
                    position: absolute;
                    top: 1rem;
                    right: 1rem;
                    background: rgba(255, 0, 0, 0.2);
                    border: 2px solid #ff0040;
                    color: #ff0040;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 1.5rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    z-index: 15002 !important;
                ">✕</button>
            </div>
            
            <!-- Body -->
            <div style="
                padding: 2.5rem;
                background: linear-gradient(145deg, #0f0f23 0%, #1a1a2e 100%);
                color: #ffffff;
            ">
                <div style="margin-bottom: 2rem;">
                    <label style="
                        display: block;
                        margin-bottom: 0.8rem;
                        font-weight: 700;
                        font-size: 1rem;
                        color: #00d4ff;
                        font-family: 'Orbitron', monospace;
                        text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
                    ">👤 NOM COMPLET *</label>
                    <input type="text" id="simple_nom" placeholder="Saisir le nom du client" style="
                        width: 100%;
                        padding: 1rem;
                        border: 3px solid #00d4ff;
                        border-radius: 15px;
                        font-size: 1.1rem;
                        box-sizing: border-box;
                        background: rgba(0, 20, 40, 0.8);
                        color: #ffffff;
                        outline: none;
                        font-weight: 600;
                        z-index: 15002 !important;
                        position: relative;
                    ">
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <label style="
                        display: block;
                        margin-bottom: 0.8rem;
                        font-weight: 700;
                        font-size: 1rem;
                        color: #00d4ff;
                        font-family: 'Orbitron', monospace;
                        text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
                    ">📞 TÉLÉPHONE</label>
                    <input type="tel" id="simple_telephone" placeholder="Numéro de téléphone" style="
                        width: 100%;
                        padding: 1rem;
                        border: 3px solid #00d4ff;
                        border-radius: 15px;
                        font-size: 1.1rem;
                        box-sizing: border-box;
                        background: rgba(0, 20, 40, 0.8);
                        color: #ffffff;
                        outline: none;
                        font-weight: 600;
                        z-index: 15002 !important;
                        position: relative;
                    ">
                </div>
                
                <div style="margin-bottom: 2.5rem;">
                    <label style="
                        display: block;
                        margin-bottom: 0.8rem;
                        font-weight: 700;
                        font-size: 1rem;
                        color: #00d4ff;
                        font-family: 'Orbitron', monospace;
                        text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
                    ">✉️ EMAIL</label>
                    <input type="email" id="simple_email" placeholder="Adresse email" style="
                        width: 100%;
                        padding: 1rem;
                        border: 3px solid #00d4ff;
                        border-radius: 15px;
                        font-size: 1.1rem;
                        box-sizing: border-box;
                        background: rgba(0, 20, 40, 0.8);
                        color: #ffffff;
                        outline: none;
                        font-weight: 600;
                        z-index: 15002 !important;
                        position: relative;
                    ">
                </div>
                
                <!-- Footer -->
                <div style="
                    display: flex;
                    gap: 1.5rem;
                    justify-content: center;
                ">
                    <button id="simple_cancel" style="
                        background: rgba(255, 0, 0, 0.2);
                        border: 2px solid #ff0040;
                        color: #ff0040;
                        padding: 1rem 2rem;
                        border-radius: 15px;
                        cursor: pointer;
                        font-size: 1rem;
                        font-weight: 700;
                        font-family: 'Orbitron', monospace;
                        z-index: 15002 !important;
                        position: relative;
                    ">❌ ANNULER</button>
                    <button id="simple_save" style="
                        background: linear-gradient(135deg, #00d4ff 0%, #0080ff 100%);
                        border: 2px solid #00d4ff;
                        color: #000000;
                        padding: 1rem 2rem;
                        border-radius: 15px;
                        cursor: pointer;
                        font-size: 1rem;
                        font-weight: 700;
                        font-family: 'Orbitron', monospace;
                        text-shadow: none;
                        box-shadow: 0 0 30px rgba(0, 212, 255, 0.6);
                        z-index: 15002 !important;
                        position: relative;
                    ">💾 ENREGISTRER</button>
                </div>
            </div>
        </div>`;

    // 4. Injecter l'overlay et le modal
    overlay.innerHTML = modalHTML;
    document.body.appendChild(overlay);

    console.log('✅ Modal créé avec z-index MAXIMUM et overlay complet');

    // 5. Récupérer les éléments
    const modal = document.getElementById('nouveauClientModal_temp');
    const nomInput = document.getElementById('simple_nom');
    const telInput = document.getElementById('simple_telephone');
    const emailInput = document.getElementById('simple_email');
    const closeBtn = document.getElementById('simple_close');
    const cancelBtn = document.getElementById('simple_cancel');
    const saveBtn = document.getElementById('simple_save');

    // 6. Focus automatique ET désactivation de tous les autres listeners
    setTimeout(() => {
        if (nomInput) {
            // DÉSACTIVER TOUS LES EVENT LISTENERS EXISTANTS
            disableAllEventListeners();

            nomInput.focus();

            // FORCER LA SAISIE NATIVE
            enableNativeInput(nomInput);
            enableNativeInput(telInput);
            enableNativeInput(emailInput);
        }
    }, 200);

    // FONCTION POUR DÉSACTIVER TOUS LES EVENT LISTENERS
    function disableAllEventListeners() {

        // Sauvegarder les fonctions originales
        const originalAddEventListener = EventTarget.prototype.addEventListener;
        const originalRemoveEventListener = EventTarget.prototype.removeEventListener;

        // Bloquer temporairement l'ajout de nouveaux listeners
        EventTarget.prototype.addEventListener = function () {
            // Ne rien faire - bloquer tous les nouveaux listeners
        };

        // Restaurer après 1 seconde
        setTimeout(() => {
            EventTarget.prototype.addEventListener = originalAddEventListener;
            EventTarget.prototype.removeEventListener = originalRemoveEventListener;
        }, 1000);
    }

    // FONCTION POUR FORCER LA SAISIE NATIVE ULTRA-AGRESSIVE
    function enableNativeInput(input) {
        if (!input) return;

        console.log('🔧 ACTIVATION ULTRA-AGRESSIVE pour:', input.id);

        // 1. NETTOYER COMPLÈTEMENT L'INPUT
        input.removeAttribute('readonly');
        input.removeAttribute('disabled');
        input.removeAttribute('autocomplete');
        input.contentEditable = false;
        input.disabled = false;
        input.readOnly = false;
        input.tabIndex = 0;

        // 2. FORCER LES STYLES POUR ASSURER LA VISIBILITÉ
        input.style.pointerEvents = 'auto';
        input.style.userSelect = 'text';
        input.style.webkitUserSelect = 'text';
        input.style.mozUserSelect = 'text';
        input.style.msUserSelect = 'text';
        input.style.opacity = '1';
        input.style.visibility = 'visible';
        input.style.display = 'block';

        // 3. INTERCEPTEUR GLOBAL ULTRA-AGRESSIF
        if (!window.currentModalInput) {
            window.currentModalInput = null;
        }

        // Fonction pour capturer TOUS les événements clavier (accessible globalement)
        window.globalKeyboardHandler = function (e) {
            if (window.currentModalInput && document.getElementById('nouveauClientModal_temp')) {

                // Empêcher la propagation vers d'autres scripts
                e.stopImmediatePropagation();
                e.stopPropagation();

                // Traitement direct selon le type d'événement
                if (e.type === 'keydown') {
                    // Laisser passer les touches spéciales
                    if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Tab'].includes(e.key)) {
                        return true;
                    }
                }

                if (e.type === 'keypress' || (e.type === 'keydown' && e.key.length === 1)) {
                    // Pour les caractères normaux, les ajouter directement
                    if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                        e.preventDefault();

                        // Ajouter le caractère directement à la valeur
                        const cursorPos = window.currentModalInput.selectionStart || window.currentModalInput.value.length;
                        const newValue = window.currentModalInput.value.slice(0, cursorPos) + e.key + window.currentModalInput.value.slice(cursorPos);
                        window.currentModalInput.value = newValue;

                        // Repositionner le curseur
                        setTimeout(() => {
                            window.currentModalInput.setSelectionRange(cursorPos + 1, cursorPos + 1);
                        }, 0);

                        console.log('✏️ CARACTÈRE AJOUTÉ:', e.key, 'nouvelle valeur:', window.currentModalInput.value);

                        // Déclencher l'événement input manuellement
                        const inputEvent = new Event('input', { bubbles: true });
                        window.currentModalInput.dispatchEvent(inputEvent);

                        return false;
                    }
                }

                // Gestion du Backspace
                if (e.key === 'Backspace') {
                    e.preventDefault();
                    const cursorPos = window.currentModalInput.selectionStart || window.currentModalInput.value.length;
                    if (cursorPos > 0) {
                        const newValue = window.currentModalInput.value.slice(0, cursorPos - 1) + window.currentModalInput.value.slice(cursorPos);
                        window.currentModalInput.value = newValue;
                        setTimeout(() => {
                            window.currentModalInput.setSelectionRange(cursorPos - 1, cursorPos - 1);
                        }, 0);
                    }
                    return false;
                }
            }
            return true;
        }

        // Attacher l'intercepteur global avec capture (une seule fois)
        if (!window.keyboardHandlerAttached) {
            document.addEventListener('keydown', window.globalKeyboardHandler, { capture: true, passive: false });
            document.addEventListener('keypress', window.globalKeyboardHandler, { capture: true, passive: false });
            document.addEventListener('keyup', window.globalKeyboardHandler, { capture: true, passive: false });
            window.keyboardHandlerAttached = true;
        }

        // 4. GESTION DU FOCUS
        input.addEventListener('focus', function () {
            window.currentModalInput = this;
            this.style.outline = '2px solid #00d4ff';
        });

        input.addEventListener('blur', function () {
            // Ne pas désactiver currentModalInput immédiatement pour permettre la saisie
            setTimeout(() => {
                if (document.activeElement && document.activeElement.closest('#nouveauClientModal_temp')) {
                } else {
                    window.currentModalInput = null;
                }
            }, 100);
            this.style.outline = '';
        });

        // 5. CLIC POUR FOCUS
        input.addEventListener('click', function () {
            this.focus();
            window.currentModalInput = this;
        });

        console.log('✅ SAISIE ULTRA-AGRESSIVE activée pour:', input.id);
    }

    // 7. Fermeture
    function closeModal() {

        // Supprimer les event listeners globaux
        if (window.globalKeyboardHandler && window.keyboardHandlerAttached) {
            document.removeEventListener('keydown', window.globalKeyboardHandler, { capture: true });
            document.removeEventListener('keypress', window.globalKeyboardHandler, { capture: true });
            document.removeEventListener('keyup', window.globalKeyboardHandler, { capture: true });
            window.globalKeyboardHandler = null;
            window.keyboardHandlerAttached = false;
            window.currentModalInput = null;
        }

        document.body.style.overflow = '';
        overlay.remove();

        console.log('✅ Modal fermé et event listeners nettoyés');
    }

    // 8. Événements
    closeBtn.onclick = closeModal;
    cancelBtn.onclick = closeModal;

    // Fermeture sur clic backdrop
    overlay.onclick = function (e) {
        if (e.target === overlay) {
            closeModal();
        }
    };

    // Fermeture sur Escape
    document.addEventListener('keydown', function escapeHandler(e) {
        if (e.key === 'Escape') {
            closeModal();
            document.removeEventListener('keydown', escapeHandler);
        }
    });

    // 9. Sauvegarde
    saveBtn.onclick = function () {
        const nom = nomInput.value.trim();
        const telephone = telInput.value.trim();
        const email = emailInput.value.trim();

        if (!nom) {
            alert('Le nom est obligatoire !');
            nomInput.focus();
            return;
        }

        console.log('💾 Client créé:', { nom, telephone, email });

        // Mettre à jour le modal principal
        const clientSearchInput = document.getElementById('nom_client_selectionne');
        const clientIdInput = document.getElementById('client_id');

        if (clientSearchInput) {
            clientSearchInput.value = nom;
        }
        if (clientIdInput) {
            clientIdInput.value = 'new_' + Date.now();
        }

        alert(`✅ Client "${nom}" créé avec succès !`);
        closeModal();
    };

    // ===== CORRECTION POUR LA SAISIE =====
    // Forcer l'interactivité des champs
    [nomInput, telInput, emailInput].forEach((input, index) => {
        if (input) {
            // Rendre les champs interactifs
            input.style.pointerEvents = 'auto';
            input.style.userSelect = 'text';
            input.tabIndex = index + 1;

            // Événements de saisie explicites
            input.addEventListener('input', function (e) {
                e.stopPropagation();
            }, true);

            input.addEventListener('keydown', function (e) {
                e.stopPropagation();
                // Permettre tous les caractères normaux
                if (e.key.length === 1 || ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Tab'].includes(e.key)) {
                    // Laisser l'événement se propager normalement
                }
            }, true);

            input.addEventListener('keyup', function (e) {
                e.stopPropagation();
            }, true);

            // Styles de focus améliorés
            input.addEventListener('focus', function () {
                this.style.borderColor = '#00ffff';
                this.style.boxShadow = '0 0 20px rgba(0, 255, 255, 0.5)';
            });

            input.addEventListener('blur', function () {
                this.style.borderColor = '#00d4ff';
                this.style.boxShadow = '0 0 100px rgba(0, 212, 255, 0.8), 0 0 200px rgba(0, 212, 255, 0.4)';
            });
        }
    });

    // Forcer le focus sur le premier champ avec un délai
    setTimeout(() => {
        if (nomInput) {
            nomInput.focus();
            nomInput.click(); // Double assurance
        }
    }, 200);

    console.log('✅ Modal futuriste créé et CORRIGÉ pour la saisie');

    // Test simple
    window.testUltraModal = function () {
        if (nomInput) {
            nomInput.value = 'Test Client Ultra';
            nomInput.focus();
        }
    };

    console.log('💡 Utilisez window.testUltraModal() pour tester');

    // Fonction de test spécifique pour la saisie
    window.testFuturisticInput = function () {

        if (nomInput) {
            nomInput.focus();
            nomInput.value = 'Test Futuriste';
            nomInput.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
        }

        if (telInput) {
            telInput.value = '0123456789';
            telInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        if (emailInput) {
            emailInput.value = 'test@futuriste.com';
            emailInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        console.log('✅ Test de saisie terminé - vérifiez que vous pouvez maintenant saisir normalement');
    };
}

// FONCTION D'INITIALISATION DU DROPDOWN FOURNISSEUR
function initializeSuppliersDropdown() {

    const fournisseurSelect = document.getElementById('fournisseur_id_ajout');
    if (!fournisseurSelect) {
        return;
    }

    // Charger les fournisseurs via AJAX
    fetch('ajax/get_fournisseurs.php')
        .then(response => response.json())
        .then(data => {

            if (data.success && data.fournisseurs) {
                // Vider le select
                fournisseurSelect.innerHTML = '<option value="">Sélectionner un fournisseur...</option>';

                // Ajouter les fournisseurs
                data.fournisseurs.forEach(fournisseur => {
                    const option = document.createElement('option');
                    option.value = fournisseur.id;
                    option.textContent = fournisseur.nom;
                    fournisseurSelect.appendChild(option);
                });

                console.log(`✅ ${data.fournisseurs.length} fournisseurs chargés`);
            }
        })
        .catch(error => {
            console.error('Erreur chargement fournisseurs:', error);
        });

    console.log('✅ Dropdown fournisseur initialisé');
}

// Fonction de test globale
window.testCommandeModal = function () {
    const modal = document.getElementById('ajouterCommandeModal');
    if (modal) {
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    } else {
    }
};
