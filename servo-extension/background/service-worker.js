/**
 * SERVO Extension - Background Service Worker
 * Gère les requêtes vers l'API SERVO
 */

// Écouteur de messages depuis les content scripts
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message.action === 'addToServo') {
        handleAddToServo(message.data)
            .then(result => sendResponse(result))
            .catch(error => sendResponse({ success: false, message: error.message }));
        return true;
    }

    if (message.action === 'checkSession') {
        handleCheckSession(message.servoUrl)
            .then(result => sendResponse(result))
            .catch(error => sendResponse({ success: false, message: error.message }));
        return true;
    }

    if (message.action === 'searchClients') {
        handleSearchClients(message.servoUrl, message.query)
            .then(result => sendResponse(result))
            .catch(error => sendResponse({ success: false, message: error.message }));
        return true;
    }

    if (message.action === 'requestSupplier') {
        handleRequestSupplier(message.servoUrl, message.data)
            .then(result => sendResponse(result))
            .catch(error => sendResponse({ success: false, message: error.message }));
        return true;
    }

    if (message.action === 'createClientAndAdd') {
        handleCreateClientAndAdd(message.servoUrl, message.data)
            .then(result => sendResponse(result))
            .catch(error => sendResponse({ success: false, message: error.message }));
        return true;
    }
});

// Créer un client et ajouter un produit
async function handleCreateClientAndAdd(servoUrl, data) {
    const apiUrl = `${servoUrl}/ajax/extension_create_client_and_add.php`;

    const formData = new FormData();
    formData.append('client_nom', data.clientNom);
    formData.append('client_prenom', data.clientPrenom);
    formData.append('client_phone', data.clientPhone || '');
    formData.append('client_email', data.clientEmail || '');
    formData.append('nom_piece', data.name);
    formData.append('prix', data.price);
    formData.append('reference', data.reference);
    formData.append('fournisseur_id', getFournisseurId(data.source));
    formData.append('source_url', data.url);

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-SERVO-Extension': '1.0'
            }
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('[SERVO] Erreur création client:', error);
        throw error;
    }
}


// Demander l'ajout d'un fournisseur (envoi email)
async function handleRequestSupplier(servoUrl, data) {
    const apiUrl = `${servoUrl}/ajax/extension_request_supplier.php`;

    const formData = new FormData();
    formData.append('supplier_name', data.supplierName);
    formData.append('supplier_url', data.supplierUrl);
    formData.append('notes', data.notes || '');

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-SERVO-Extension': '1.0'
            }
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('[SERVO] Erreur demande fournisseur:', error);
        throw error;
    }
}


// Rechercher des clients
async function handleSearchClients(servoUrl, query) {
    const apiUrl = `${servoUrl}/ajax/search_clients.php?q=${encodeURIComponent(query)}`;

    try {
        const response = await fetch(apiUrl, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-SERVO-Extension': '1.0'
            }
        });

        if (!response.ok) {
            return { success: false, clients: [] };
        }

        const result = await response.json();
        return {
            success: true,
            clients: result.clients || result.data || []
        };
    } catch (error) {
        console.error('[SERVO] Erreur recherche clients:', error);
        return { success: false, clients: [], message: error.message };
    }
}

// Ajouter un produit à SERVO
async function handleAddToServo(data) {
    const { name, reference, price, url, source, servoUrl, clientId } = data;

    const apiUrl = `${servoUrl}/ajax/add_catalogue_to_cart.php`;

    const formData = new FormData();
    formData.append('catalogue_id', 0);
    formData.append('fournisseur_id', getFournisseurId(source));
    formData.append('nom_piece', name);
    formData.append('prix', price);
    formData.append('reference', reference);
    formData.append('source_url', url);

    // Ajouter le client_id si spécifié
    if (clientId !== null && clientId !== undefined) {
        formData.append('client_id', clientId);
    }


    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'include', // Important: envoie les cookies de session
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-SERVO-Extension': '1.0'
            }
        });

        if (!response.ok) {
            if (response.status === 401 || response.status === 403) {
                throw new Error('Non connecté à SERVO');
            }
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            // Notification de succès
            chrome.notifications?.create({
                type: 'basic',
                iconUrl: 'icons/icon48.png',
                title: 'SERVO',
                message: `${name} ajouté avec succès !`
            });
        }

        return result;
    } catch (error) {
        console.error('[SERVO Background] Erreur:', error);
        throw error;
    }
}

// Vérifier la session SERVO
async function handleCheckSession(servoUrl) {
    // Utiliser le nouvel endpoint dédié aux extensions
    const apiUrl = `${servoUrl}/ajax/extension_check_session.php`;

    try {
        const response = await fetch(apiUrl, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-SERVO-Extension': '1.0'
            }
        });

        if (!response.ok) {
            console.error('[SERVO] Response not OK:', response.status);
            return { success: false, logged_in: false };
        }

        const result = await response.json();
        console.log('[SERVO] Session check result:', result);

        return {
            success: true,
            logged_in: result.logged_in === true,
            user_name: result.user_name || '',
            shop_id: result.shop_id || '',
            shop_name: result.shop_name || ''
        };
    } catch (error) {
        console.error('[SERVO Background] Erreur check session:', error);
        return { success: false, logged_in: false, message: error.message };
    }
}

// Mapper le nom de source vers un ID fournisseur
function getFournisseurId(source) {
    // IDs correspondant à la table `fournisseurs` dans geekboard_mdg
    const mapping = {
        'utopya': 2,
        'mobilax': 11,
        'wattiz': 17,
        'lcd-phone': 18,
        'jensmobiles': 19,
        'mobilesentrix': 20
    };
    return mapping[source] || 16; // 16 = "AUTRE" par défaut (auto-création côté serveur)
}

// Installation de l'extension
chrome.runtime.onInstalled.addListener((details) => {
    if (details.reason === 'install') {
        // Définir les paramètres par défaut
        chrome.storage.sync.set({
            servoUrl: 'https://mdg.servo.tools',
            defaultClientId: null // null = Magasin Atelier
        });

        console.log('[SERVO] Extension installée');
    }
});
