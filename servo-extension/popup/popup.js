/**
 * SERVO Extension - Popup Logic
 */

document.addEventListener('DOMContentLoaded', async () => {
    const servoUrlInput = document.getElementById('servoUrl');
    const saveBtn = document.getElementById('saveBtn');
    const checkBtn = document.getElementById('checkBtn');
    const statusIndicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');

    // Charger les paramètres sauvegardés
    const settings = await chrome.storage.sync.get(['servoUrl']);
    servoUrlInput.value = settings.servoUrl || 'https://mdg.servo.tools';

    // Vérifier la session au chargement
    checkSession();

    // Événement de sauvegarde
    saveBtn.addEventListener('click', async () => {
        const url = servoUrlInput.value.trim();

        if (!url) {
            showStatus('error', 'URL requise');
            return;
        }

        // Valider l'URL
        try {
            new URL(url);
        } catch {
            showStatus('error', 'URL invalide');
            return;
        }

        // Sauvegarder
        await chrome.storage.sync.set({ servoUrl: url });
        showStatus('success', 'Paramètres enregistrés');

        // Vérifier la nouvelle URL
        setTimeout(checkSession, 500);
    });

    // Événement de vérification
    checkBtn.addEventListener('click', checkSession);

    // Vérifier la session SERVO
    async function checkSession() {
        showStatus('checking', 'Vérification...');

        const settings = await chrome.storage.sync.get(['servoUrl']);
        const servoUrl = settings.servoUrl || 'https://mdg.servo.tools';

        try {
            const response = await chrome.runtime.sendMessage({
                action: 'checkSession',
                servoUrl: servoUrl
            });

            if (response.success && response.logged_in) {
                showStatus('connected', `Connecté : ${response.user_name || 'Utilisateur'}`);
            } else {
                showStatus('disconnected', 'Non connecté à SERVO');
            }
        } catch (error) {
            console.error('Erreur check session:', error);
            showStatus('error', 'Erreur de connexion');
        }
    }

    // Afficher le statut
    function showStatus(type, text) {
        statusIndicator.className = 'status-indicator ' + type;
        statusText.textContent = text;
    }

    // Toggle pour afficher/cacher le formulaire fournisseur
    const toggleSupplierBtn = document.getElementById('toggleSupplierForm');
    const supplierFormContainer = document.getElementById('supplierFormContainer');

    toggleSupplierBtn.addEventListener('click', () => {
        const isHidden = supplierFormContainer.classList.contains('supplier-form-hidden');

        if (isHidden) {
            supplierFormContainer.classList.remove('supplier-form-hidden');
            supplierFormContainer.classList.add('supplier-form-visible');
            toggleSupplierBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Fermer
            `;
        } else {
            supplierFormContainer.classList.remove('supplier-form-visible');
            supplierFormContainer.classList.add('supplier-form-hidden');
            toggleSupplierBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Ajouter un fournisseur
            `;
        }
    });

    // Gestion du formulaire de demande de fournisseur
    const requestSupplierBtn = document.getElementById('requestSupplierBtn');
    const supplierName = document.getElementById('supplierName');
    const supplierUrl = document.getElementById('supplierUrl');
    const supplierNotes = document.getElementById('supplierNotes');
    const supplierFeedback = document.getElementById('supplierFeedback');

    requestSupplierBtn.addEventListener('click', async () => {
        const name = supplierName.value.trim();
        const url = supplierUrl.value.trim();
        const notes = supplierNotes.value.trim();

        if (!name) {
            showFeedback('error', 'Le nom du fournisseur est requis');
            return;
        }

        if (!url) {
            showFeedback('error', 'L\'URL du site est requise');
            return;
        }

        // Désactiver le bouton pendant l'envoi
        requestSupplierBtn.disabled = true;
        requestSupplierBtn.textContent = 'Envoi en cours...';

        try {
            const settings = await chrome.storage.sync.get(['servoUrl']);
            const servoUrl = settings.servoUrl || 'https://mdg.servo.tools';

            const response = await chrome.runtime.sendMessage({
                action: 'requestSupplier',
                servoUrl: servoUrl,
                data: {
                    supplierName: name,
                    supplierUrl: url,
                    notes: notes
                }
            });

            if (response.success) {
                showFeedback('success', 'Demande envoyée avec succès !');
                // Réinitialiser le formulaire
                supplierName.value = '';
                supplierUrl.value = '';
                supplierNotes.value = '';
            } else {
                showFeedback('error', response.message || 'Erreur lors de l\'envoi');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showFeedback('error', 'Erreur de connexion au serveur');
        } finally {
            requestSupplierBtn.disabled = false;
            requestSupplierBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4z"/>
                </svg>
                Envoyer la demande
            `;
        }
    });

    function showFeedback(type, message) {
        supplierFeedback.className = 'supplier-feedback ' + type;
        supplierFeedback.textContent = message;

        // Cacher après 5 secondes
        setTimeout(() => {
            supplierFeedback.className = 'supplier-feedback';
        }, 5000);
    }
});
