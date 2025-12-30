/**
 * Module de gestion du clavier numérique pour les prix
 */
window.PriceNumpad = window.PriceNumpad || {
    // Éléments DOM
    elements: {
        modal: null,
        display: null,
        numpadKeys: null,
        saveButton: null
    },

    // Données
    data: {
        repairId: null,
        initialPrice: '0'
    },

    /**
     * Initialise le module
     */
    init() {
        // Éviter la double initialisation
        if (this.initialized) {
            // console.log('PriceNumpad déjà initialisé');
            return;
        }

        // Créer la modal si elle n'existe pas
        this.createModal();

        // Récupérer les éléments
        this.elements.modal = document.getElementById('priceNumpadModal');
        this.elements.display = document.getElementById('priceDisplay');
        this.elements.numpadKeys = document.querySelectorAll('.numpad-key');
        this.elements.saveButton = document.getElementById('savePriceButton');

        if (!this.elements.modal || !this.elements.display || !this.elements.numpadKeys.length || !this.elements.saveButton) {
            console.error('Éléments du clavier numérique manquants');
            return;
        }

        // Initialiser les événements
        this.initEvents();

        // Marquer comme initialisé
        this.initialized = true;

        // console.log('PriceNumpad initialisé avec succès');
    },

    /**
     * Crée la structure HTML du modal si elle n'existe pas
     */
    createModal() {
        if (document.getElementById('priceNumpadModal')) {
            return;
        }

        const modalHTML = `
        <div class="modal fade" id="priceNumpadModal" tabindex="-1" aria-labelledby="priceNumpadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="priceNumpadModalLabel">
                            <i class="fas fa-euro-sign text-success me-2"></i>
                            Modifier le prix
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="price-display mb-4">
                            <div id="priceDisplay" class="text-center display-4 fw-bold text-success">0 €</div>
                        </div>
                        
                        <div class="custom-numpad">
                            <div class="numpad-row">
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="1">1</button>
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="2">2</button>
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="3">3</button>
                            </div>
                            <div class="numpad-row">
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="4">4</button>
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="5">5</button>
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="6">6</button>
                            </div>
                            <div class="numpad-row">
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="7">7</button>
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="8">8</button>
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="9">9</button>
                            </div>
                            <div class="numpad-row">
                                <button type="button" class="numpad-key btn btn-danger btn-lg" data-value="C">C</button>
                                <button type="button" class="numpad-key btn btn-light btn-lg" data-value="0">0</button>
                                <button type="button" class="numpad-key btn btn-success btn-lg" data-value="OK">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-success" id="savePriceButton">
                            <i class="fas fa-save me-1"></i>
                            Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        #priceNumpadModal {
            z-index: 10500 !important;
        }
        
        #priceNumpadModal .modal-dialog {
            z-index: 10501 !important;
        }
        
        #priceNumpadModal .modal-content {
            z-index: 10502 !important;
        }
        
        .custom-numpad {
            display: grid;
            width: 100%;
            gap: 10px;
        }
        
        .numpad-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .numpad-key {
            padding: 15px;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            border-radius: 10px;
            transition: all 0.15s ease;
            z-index: 10503 !important;
            position: relative;
            pointer-events: auto !important;
        }
        
        .numpad-key:active {
            transform: scale(0.95);
        }
        
        #priceNumpadModal .modal-header,
        #priceNumpadModal .modal-body,
        #priceNumpadModal .modal-footer,
        #priceNumpadModal button,
        #priceNumpadModal .btn,
        #priceNumpadModal .btn-close,
        #priceNumpadModal .modal-title,
        #priceNumpadModal #priceDisplay {
            z-index: 10503 !important;
            position: relative;
            pointer-events: auto !important;
        }
        
        #priceNumpadModal.show ~ .modal-backdrop,
        body:has(#priceNumpadModal.show) .modal-backdrop {
            z-index: 10499 !important;
            display: block !important;
            opacity: 0.5 !important;
            visibility: visible !important;
        }
        
        body.modal-open #priceNumpadModal.show {
            display: block !important;
            z-index: 10500 !important;
        }

        /* Dark Mode Support */
        html[data-theme='dark'] #priceNumpadModal .modal-content,
        .night-mode #priceNumpadModal .modal-content {
            background-color: #1e293b !important;
            color: #f8f9fa !important;
            border: 1px solid #334155 !important;
        }

        html[data-theme='dark'] #priceNumpadModal .modal-header,
        .night-mode #priceNumpadModal .modal-header {
            background-color: #0f172a !important;
            border-bottom: 1px solid #334155 !important;
        }

        html[data-theme='dark'] #priceNumpadModal .modal-body,
        .night-mode #priceNumpadModal .modal-body {
            background-color: #1e293b !important;
            color: #f8f9fa !important;
        }

        html[data-theme='dark'] #priceNumpadModal .modal-footer,
        .night-mode #priceNumpadModal .modal-footer {
            background-color: #0f172a !important;
            border-top: 1px solid #334155 !important;
        }

        html[data-theme='dark'] #priceNumpadModal .modal-title,
        .night-mode #priceNumpadModal .modal-title {
            color: #f8f9fa !important;
        }

        html[data-theme='dark'] #priceNumpadModal .btn-close,
        .night-mode #priceNumpadModal .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        html[data-theme='dark'] #priceNumpadModal .numpad-key.btn-light,
        .night-mode #priceNumpadModal .numpad-key.btn-light {
            background-color: #334155 !important;
            border-color: #475569 !important;
            color: #f8f9fa !important;
        }

        html[data-theme='dark'] #priceNumpadModal .numpad-key.btn-light:hover,
        .night-mode #priceNumpadModal .numpad-key.btn-light:hover {
            background-color: #475569 !important;
        }
        </style>
        `;

        // Ajouter au body
        const div = document.createElement('div');
        div.innerHTML = modalHTML;
        document.body.appendChild(div);
    },

    /**
     * Initialise les événements
     */
    initEvents() {
        // Événements pour les touches du clavier
        this.elements.numpadKeys.forEach(key => {
            const keyValue = key.getAttribute('data-value');

            // Gestionnaire pour les clics de souris
            key.addEventListener('click', e => {
                e.preventDefault();
                e.stopPropagation();

                // Traiter la touche
                this.handleNumpadKey(keyValue);
            });

            // Gestionnaire pour les événements tactiles - optimisé pour être passif quand possible
            key.addEventListener('touchend', e => {
                // Empêcher le clic fantôme qui pourrait suivre
                e.preventDefault();

                // Traiter la touche
                this.handleNumpadKey(keyValue);

                return false;
            });
        });

        // Événement de sauvegarde
        this.elements.saveButton.addEventListener('click', () => this.savePrice());

        // Événement d'ouverture du modal
        this.elements.modal.addEventListener('shown.bs.modal', () => {
            // Réinitialiser l'affichage si nécessaire
            if (this.data.initialPrice && this.data.initialPrice !== '0') {
                this.elements.display.textContent = this.data.initialPrice + ' €';
            } else {
                this.elements.display.textContent = '0 €';
            }
        });
    },

    /**
     * Traite les touches du clavier numérique
     * @param {string} keyValue - Valeur de la touche
     */
    handleNumpadKey(keyValue) {
        const display = this.elements.display;

        // Extraire le prix actuel (sans le symbole €)
        let currentPrice = display.textContent.replace(' €', '');

        // console.log('Touche pressée:', keyValue, 'Prix actuel:', currentPrice);

        if (keyValue === 'C') {
            // Touche Clear - réinitialiser à zéro
            display.textContent = '0 €';
        } else if (keyValue === 'OK') {
            // Touche OK - valider
            this.savePrice();
        } else {
            // Touches numériques
            if (currentPrice === '0') {
                // Si on part de zéro, remplacer complètement
                display.textContent = keyValue + ' €';
            } else {
                // Ajouter le chiffre
                display.textContent = currentPrice + keyValue + ' €';
            }
        }
    },

    /**
     * Affiche le modal pour modifier le prix
     * @param {string} repairId - ID de la réparation
     * @param {string} currentPrice - Prix actuel (sans symbole €)
     */
    show(repairId, currentPrice = '0') {
        // Stocker les données
        this.data.repairId = repairId;
        this.data.initialPrice = currentPrice.replace(' €', '');

        // Afficher le modal
        const modal = bootstrap.Modal.getOrCreateInstance(this.elements.modal);
        modal.show();

        // Mettre à jour l'affichage
        this.elements.display.textContent = this.data.initialPrice + ' €';
    },

    /**
     * Enregistre le nouveau prix
     */
    savePrice() {
        const nouveauPrix = this.elements.display.textContent.replace(' €', '');
        const repairId = this.data.repairId;

        if (!repairId) {
            console.error('ID de réparation manquant');
            return;
        }

        // console.log('Enregistrement du prix', nouveauPrix, 'pour la réparation', repairId);

        // Récupérer l'ID du magasin
        let shopId = null;
        if (typeof SessionHelper !== 'undefined' && SessionHelper.getShopId) {
            shopId = SessionHelper.getShopId();
        } else if (localStorage.getItem('shop_id')) {
            shopId = localStorage.getItem('shop_id');
        } else if (document.body.hasAttribute('data-shop-id')) {
            shopId = document.body.getAttribute('data-shop-id');
        }

        // Construire les données à envoyer
        let requestBody = `repair_id=${repairId}&price=${nouveauPrix}`;
        if (shopId) {
            requestBody += `&shop_id=${shopId}`;
        }

        // console.log('Envoi de la requête avec les paramètres:', requestBody);

        // Envoi de la requête AJAX
        fetch('ajax/update_repair_price.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: requestBody
        })
            .then(response => {
                // Vérifier si la réponse est de type JSON
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error("Réponse non-JSON:", text);
                        throw new Error("La réponse n'est pas au format JSON");
                    });
                }
            })
            .then(data => {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(this.elements.modal);
                modal.hide();

                if (data.success) {
                    // Mettre à jour l'affichage du prix dans la page
                    const priceElement = document.querySelector(`.price-value[data-repair-id="${repairId}"]`);
                    if (priceElement) {
                        priceElement.textContent = nouveauPrix + ' €';
                    }

                    // Notification de succès
                    this.showNotification('success', 'Prix mis à jour avec succès');

                    // Rafraîchir la page pour voir les modifications
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Notification d'erreur
                    const message = data.message || 'Erreur lors de la mise à jour du prix';
                    this.showNotification('error', message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                // Notification d'erreur
                this.showNotification('error', 'Erreur de connexion: ' + error.message);
            });
    },

    /**
     * Affiche une notification
     * @param {string} type - Type de notification (success, error, warning, info)
     * @param {string} message - Message à afficher
     */
    showNotification(type, message) {
        if (typeof toastr !== 'undefined' && toastr) {
            // Si toastr est disponible et initialisé correctement
            try {
                switch (type) {
                    case 'success':
                        toastr.success(message);
                        break;
                    case 'error':
                        toastr.error(message);
                        break;
                    case 'warning':
                        toastr.warning(message);
                        break;
                    case 'info':
                        toastr.info(message);
                        break;
                    default:
                        toastr.info(message);
                }
            } catch (e) {
                // Si toastr génère une erreur, utiliser alert
                console.error('Erreur avec toastr:', e);
                alert(message);
            }
        } else {
            // Fallback sur alert si toastr n'est pas disponible
            alert(message);
        }
    }
};

// Exposer le module globalement
window.priceModal = window.PriceNumpad;

// Fonction de compatibilité pour openPriceModal
window.openPriceModal = function (repairId, currentPrice = '0') {
    // console.log('🎯 [COMPAT] Redirection vers priceModal.show()');
    if (window.priceModal && typeof window.priceModal.show === 'function') {
        window.priceModal.show(repairId, currentPrice);
    } else {
        console.error('❌ [COMPAT] PriceNumpad non initialisé');
    }
};

// Initialiser le module au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    window.PriceNumpad.init();
});