/**
 * SERVO Extension - Content Script pour Wattiz
 * Injecte un bouton "Ajouter à SERVO" sur les fiches produits
 * Avec popover de sélection de client
 */

(function () {
    'use strict';

    let currentProductData = null;
    let currentButton = null;

    // Vérifier si on est sur une page produit Wattiz
    function isProductPage() {
        // Wattiz utilise Elementor - vérifier la présence du titre produit
        return document.querySelector('h1.ce-product-name') !== null ||
            document.querySelector('h1.elementor-heading-title') !== null;
    }

    // Extraire les données du produit
    function extractProductData() {
        // Nom du produit
        const nameEl = document.querySelector('h1.ce-product-name') ||
            document.querySelector('h1.elementor-heading-title');

        // Référence/SKU
        const skuEl = document.querySelector('span.ce-product-meta__reference') ||
            document.querySelector('.ce-product-meta__detail');

        // Prix (B2B ou normal)
        const priceEl = document.querySelector('.ce-product-price .price') ||
            document.querySelector('.current-price .price') ||
            document.querySelector('span.price');

        if (!nameEl) {
            console.log('[SERVO Wattiz] Impossible de trouver le nom du produit');
            return null;
        }

        let price = priceEl ? priceEl.textContent.trim() : '0';
        // Nettoyer le prix: retirer €, HT, espaces et convertir la virgule
        price = price.replace('€', '').replace('HT', '').replace(/\s/g, '').replace(',', '.').trim();

        // Extraire la référence du texte (ex: "SKU PN-168" -> "PN-168")
        let reference = '';
        if (skuEl) {
            // Nettoyer le texte en retirant "SKU " du début
            reference = skuEl.textContent.trim().replace(/^SKU\s+/i, '');
        } else {
            // Chercher dans tous les spans pour un pattern de référence
            const allSpans = document.querySelectorAll('span');
            for (const span of allSpans) {
                const text = span.textContent.trim();
                if (/^[A-Z]{2,3}-\d+$/.test(text)) {
                    reference = text;
                    break;
                }
            }
        }

        return {
            name: nameEl.textContent.trim(),
            reference: reference,
            price: parseFloat(price) || 0,
            url: window.location.href,
            source: 'wattiz'
        };
    }

    // Créer le popover de sélection de client
    function createClientPopover() {
        if (document.querySelector('.servo-client-popover')) return;

        const popover = document.createElement('div');
        popover.className = 'servo-client-popover';
        popover.innerHTML = `
            <div class="servo-popover-header">
                <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Sélectionnez un client</span>
                <button class="servo-popover-close">×</button>
            </div>
            <div class="servo-popover-body">
                <button class="servo-client-btn servo-client-none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Aucun Client (Magasin)
                </button>
                <div class="servo-divider"><span>OU</span></div>
                <div class="servo-search-group">
                    <input type="text" class="servo-search-input" id="servoClientSearch" placeholder="Rechercher un client..." autocomplete="off">
                    <div class="servo-search-results" id="servoSearchResults"></div>
                </div>
            </div>
        `;

        document.body.appendChild(popover);

        // Event listener pour le bouton fermer
        popover.querySelector('.servo-popover-close').addEventListener('click', (e) => {
            e.stopPropagation();
            hidePopover();
        });

        // Event listener pour le bouton "Aucun Client"
        popover.querySelector('.servo-client-none').addEventListener('click', (e) => {
            e.stopPropagation();
            confirmAddToServo(null);
        });

        // Event listener pour la recherche
        const searchInput = popover.querySelector('#servoClientSearch');
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => searchClients(e.target.value), 300);
        });

        // Fermer en cliquant dehors
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.servo-client-popover') && !e.target.closest('.servo-add-button')) {
                hidePopover();
            }
        });

    }

    // Afficher le popover
    function showPopover(button, productData) {
        currentButton = button;
        currentProductData = productData;

        const popover = document.querySelector('.servo-client-popover');
        if (!popover) return;

        const rect = button.getBoundingClientRect();
        popover.style.top = (window.scrollY + rect.bottom + 10) + 'px';
        popover.style.left = (window.scrollX + rect.left) + 'px';
        popover.classList.add('servo-popover-visible');

        // Reset search
        const searchInput = popover.querySelector('#servoClientSearch');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        const results = popover.querySelector('#servoSearchResults');
        if (results) results.innerHTML = '';
    }

    // Cacher le popover
    function hidePopover() {
        const popover = document.querySelector('.servo-client-popover');
        if (popover) {
            popover.classList.remove('servo-popover-visible');
        }
    }

    // Rechercher des clients
    async function searchClients(query) {
        if (query.length < 2) {
            document.querySelector('#servoSearchResults').innerHTML = '';
            return;
        }

        const resultsDiv = document.querySelector('#servoSearchResults');
        resultsDiv.innerHTML = '<div class="servo-loading-text">Recherche...</div>';

        try {
            const settings = await chrome.storage.sync.get(['servoUrl']);
            const servoUrl = settings.servoUrl || 'https://mdg.servo.tools';

            const response = await chrome.runtime.sendMessage({
                action: 'searchClients',
                servoUrl: servoUrl,
                query: query
            });

            if (response.success && response.clients) {
                resultsDiv.innerHTML = '';

                if (response.clients.length === 0) {
                    // Afficher option de création de client
                    const createDiv = document.createElement('div');
                    createDiv.className = 'servo-create-client';
                    createDiv.innerHTML = `
                        <div class="servo-create-text">Aucun client trouvé - Créer un nouveau</div>
                        <input type="text" class="servo-create-input" id="newClientNom" placeholder="Nom *">
                        <input type="text" class="servo-create-input" id="newClientPrenom" placeholder="Prénom *">
                        <input type="tel" class="servo-create-input" id="newClientPhone" placeholder="Téléphone *">
                        <input type="email" class="servo-create-input" id="newClientEmail" placeholder="Email (facultatif)">
                        <button class="servo-create-btn" id="createClientBtn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/>
                                <line x1="17" y1="11" x2="23" y2="11"/>
                            </svg>
                            Créer et ajouter
                        </button>
                    `;
                    resultsDiv.appendChild(createDiv);

                    // Event listener pour créer le client
                    createDiv.querySelector('#createClientBtn').addEventListener('click', async () => {
                        const nom = createDiv.querySelector('#newClientNom').value.trim();
                        const prenom = createDiv.querySelector('#newClientPrenom').value.trim();
                        const phone = createDiv.querySelector('#newClientPhone').value.trim();
                        const email = createDiv.querySelector('#newClientEmail').value.trim();

                        if (!nom || !prenom || !phone) {
                            alert('Nom, prénom et téléphone sont requis');
                            return;
                        }

                        await createAndAddClient(nom, prenom, phone, email);
                    });
                } else {
                    // Afficher les résultats
                    response.clients.forEach(client => {
                        const div = document.createElement('div');
                        div.className = 'servo-client-result';
                        div.innerHTML = `
                            <strong>${client.nom} ${client.prenom || ''}</strong>
                            <span>${client.telephone || ''}</span>
                        `;
                        div.addEventListener('click', () => {
                            confirmAddToServo(client.id);
                        });
                        resultsDiv.appendChild(div);
                    });
                }
            } else {
                resultsDiv.innerHTML = '<div class="servo-no-results">Erreur de connexion<br><small>Vérifiez l\'URL de votre Dashboard dans les paramètres de l\'extension</small></div>';
            }
        } catch (error) {
            console.error('[SERVO Wattiz] Erreur recherche:', error);
            resultsDiv.innerHTML = '<div class="servo-no-results">Erreur de connexion<br><small>Vérifiez l\'URL de votre Dashboard dans les paramètres de l\'extension</small></div>';
        }
    }

    // Créer un nouveau client et ajouter le produit
    async function createAndAddClient(nom, prenom, phone, email) {
        if (!currentButton || !currentProductData) return;

        const button = currentButton;
        const productData = currentProductData;
        const originalText = button.innerHTML;

        hidePopover();

        button.classList.add('servo-loading');
        button.innerHTML = `
            <svg class="servo-spinner" width="20" height="20" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="60" stroke-linecap="round"/>
            </svg>
            <span>Création...</span>
        `;
        button.disabled = true;

        try {
            const settings = await chrome.storage.sync.get(['servoUrl']);
            const servoUrl = settings.servoUrl || 'https://mdg.servo.tools';

            // Créer le client et ajouter le produit en une seule requête
            const response = await chrome.runtime.sendMessage({
                action: 'createClientAndAdd',
                servoUrl: servoUrl,
                data: {
                    ...productData,
                    clientNom: nom,
                    clientPrenom: prenom,
                    clientPhone: phone,
                    clientEmail: email
                }
            });

            if (response.success) {
                button.classList.remove('servo-loading');
                button.classList.add('servo-success');
                button.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    <span>Ajouté !</span>
                `;

                setTimeout(() => {
                    button.classList.remove('servo-success');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 3000);
            } else {
                throw new Error(response.message || 'Erreur');
            }
        } catch (error) {
            console.error('[SERVO Wattiz] Erreur création client:', error);

            button.classList.remove('servo-loading');
            button.classList.add('servo-error');
            button.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
                <span>${error.message || 'Erreur'}</span>
            `;

            setTimeout(() => {
                button.classList.remove('servo-error');
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        }
    }

    // Confirmer l'ajout avec un client (ou null pour Magasin)
    async function confirmAddToServo(clientId) {
        hidePopover();

        if (!currentButton || !currentProductData) return;

        const button = currentButton;
        const productData = currentProductData;
        const originalText = button.innerHTML;

        // État de chargement
        button.classList.add('servo-loading');
        button.innerHTML = `
            <svg class="servo-spinner" width="20" height="20" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="60" stroke-linecap="round"/>
            </svg>
            <span>Envoi...</span>
        `;
        button.disabled = true;

        try {
            const settings = await chrome.storage.sync.get(['servoUrl']);
            const servoUrl = settings.servoUrl || 'https://mdg.servo.tools';

            const response = await chrome.runtime.sendMessage({
                action: 'addToServo',
                data: {
                    ...productData,
                    servoUrl: servoUrl,
                    clientId: clientId
                }
            });

            if (response.success) {
                button.classList.remove('servo-loading');
                button.classList.add('servo-success');
                button.innerHTML = `
                    <span class="servo-fold"></span>
                    <span class="servo-inner">
                        <svg class="servo-icon servo-checkmark" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" stroke-width="3">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span>Ajouté !</span>
                    </span>
                `;

                setTimeout(() => {
                    button.classList.remove('servo-success');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 3000);
            } else {
                throw new Error(response.message || 'Erreur inconnue');
            }
        } catch (error) {
            console.error('[SERVO Wattiz] Erreur:', error);

            button.classList.remove('servo-loading');
            button.classList.add('servo-error');
            button.innerHTML = `
                <span class="servo-fold"></span>
                <span class="servo-inner">
                    <svg class="servo-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                    <span>${error.message || 'Erreur'}</span>
                </span>
            `;

            setTimeout(() => {
                button.classList.remove('servo-error');
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        }
    };

    window.servoClosePopover = hidePopover;

    // Créer et injecter le bouton SERVO
    function injectServoButton() {
        if (document.querySelector('.servo-add-button')) return;

        const productData = extractProductData();
        if (!productData) return;

        // Chercher le bouton "Ajouter au panier" de Wattiz (Elementor)
        let visibleContainer = null;

        // Stratégie 1: Chercher le bouton addToCart
        const addToCartBtn = document.querySelector('a.elementor-button[href*="addToCart"]') ||
            document.querySelector('.ce-product-add-to-cart') ||
            document.querySelector('button.add-to-cart');

        if (addToCartBtn && addToCartBtn.offsetHeight > 0) {
            visibleContainer = addToCartBtn.parentElement;
        }

        // Stratégie 2: Zone de prix
        if (!visibleContainer) {
            const priceZone = document.querySelector('.ce-product-price');
            if (priceZone && priceZone.offsetHeight > 0) {
                visibleContainer = priceZone.parentElement;
            }
        }

        // Stratégie 3: Container Elementor général
        if (!visibleContainer) {
            const widgets = document.querySelectorAll('.elementor-widget-container');
            for (const widget of widgets) {
                if (widget.querySelector('h1') || widget.querySelector('.price')) {
                    visibleContainer = widget;
                    break;
                }
            }
        }

        if (!visibleContainer) {
            console.log('[SERVO Wattiz] Aucun conteneur trouvé pour injecter le bouton');
            return;
        }

        // Créer le popover
        createClientPopover();

        // Créer le bouton avec design premium
        const button = document.createElement('button');
        button.className = 'servo-add-button';
        button.type = 'button';
        button.innerHTML = `
            <span class="servo-fold"></span>
            <div class="servo-points-wrapper">
                <i class="servo-point"></i>
                <i class="servo-point"></i>
                <i class="servo-point"></i>
                <i class="servo-point"></i>
                <i class="servo-point"></i>
                <i class="servo-point"></i>
                <i class="servo-point"></i>
                <i class="servo-point"></i>
            </div>
            <span class="servo-inner">
                <svg class="servo-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    <line x1="12" y1="9" x2="12" y2="15"/>
                    <line x1="9" y1="12" x2="15" y2="12"/>
                </svg>
                <span>Ajouter à SERVO</span>
            </span>
        `;

        // Clic = afficher popover de sélection client
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            showPopover(button, productData);
        });

        visibleContainer.appendChild(button);
        console.log('[SERVO Wattiz] Bouton injecté', productData);
    }

    // Initialisation
    function init() {
        if (isProductPage()) {
            if (document.readyState === 'complete') {
                injectServoButton();
            } else {
                window.addEventListener('load', injectServoButton);
            }
        }
    }

    const observer = new MutationObserver(() => {
        if (isProductPage() && !document.querySelector('.servo-add-button')) {
            injectServoButton();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });

    init();
})();
