/**
 * Script de correction spécifique pour le modal SMS
 * Force l'affichage correct et corrige les problèmes de backdrop
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 [SMS-MODAL-FIX] ⚠️ Script SMS désactivé pour éviter l\'ouverture automatique');
    
    // DÉSACTIVÉ : Ce script causait l'ouverture automatique du modal SMS
    // setTimeout(function() {
    //     initSmsModalFix();
    // }, 1000);
});

function initSmsModalFix() {
    console.log('🔄 [SMS-MODAL-FIX] 🚀 Initialisation des corrections SMS...');
    
    // Observer pour détecter la création du modal SMS
    const smsModalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id === 'smsModal') {
                    console.log('🔄 [SMS-MODAL-FIX] 📱 Modal SMS détecté, application des corrections...');
                    applySmsModalFixes(node);
                }
            });
        });
    });
    
    smsModalObserver.observe(document.body, { childList: true, subtree: true });
    
    // Vérifier si le modal existe déjà
    const existingSmsModal = document.getElementById('smsModal');
    if (existingSmsModal) {
        console.log('🔄 [SMS-MODAL-FIX] 📱 Modal SMS existant trouvé');
        applySmsModalFixes(existingSmsModal);
    }
    
    // Intercepter la fonction openSmsModal si elle existe
    if (window.openSmsModal && typeof window.openSmsModal === 'function') {
        const originalOpenSms = window.openSmsModal;
        window.openSmsModal = function() {
            console.log('🔄 [SMS-MODAL-FIX] 🎯 Interception openSmsModal');
            
            // Appliquer les corrections avant l'ouverture
            setTimeout(function() {
                const smsModal = document.getElementById('smsModal');
                if (smsModal) {
                    applySmsModalFixes(smsModal);
                    forceSmsModalDisplay(smsModal);
                }
            }, 100);
            
            // DÉSACTIVÉ : Vérification qui causait l'ouverture automatique
            // setTimeout(function() {
            //     const smsModal = document.getElementById('smsModal');
            //     if (smsModal && smsModal.classList.contains('show')) {
            //         const rect = smsModal.getBoundingClientRect();
            //         if (rect.width === 0 || rect.height === 0) {
            //             console.log('🔄 [SMS-MODAL-FIX] 🚨 Modal original défaillant, activation du modal de secours automatique...');
            //             window.createEmergencySmsModal();
            //         }
            //     } else {
            //         console.log('🔄 [SMS-MODAL-FIX] 🚨 Modal SMS ne s\'est pas ouvert, activation du modal de secours...');
            //         window.createEmergencySmsModal();
            //     }
            // }, 1500);
            
            return originalOpenSms.apply(this, arguments);
        };
        console.log('🔄 [SMS-MODAL-FIX] ✅ Intercepteur openSmsModal installé');
    }
}

function applySmsModalFixes(smsModal) {
    console.log('🔄 [SMS-MODAL-FIX] 🔧 Application des corrections au modal SMS...');
    
    // Ajouter la classe de correction
    smsModal.classList.add('modal-over-repair');
    
    // Écouter les événements du modal
    smsModal.addEventListener('show.bs.modal', function() {
        console.log('🔄 [SMS-MODAL-FIX] 📱 Modal SMS en cours d\'ouverture');
        setTimeout(function() {
            forceSmsModalDisplay(smsModal);
        }, 50);
    });
    
    smsModal.addEventListener('shown.bs.modal', function() {
        console.log('🔄 [SMS-MODAL-FIX] ✅ Modal SMS ouvert avec succès');
        forceSmsModalDisplay(smsModal);
    });
    
    smsModal.addEventListener('hide.bs.modal', function() {
        console.log('🔄 [SMS-MODAL-FIX] 📱 Modal SMS en cours de fermeture');
    });
    
    smsModal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 [SMS-MODAL-FIX] 📱 Modal SMS fermé');
    });
}

function forceSmsModalDisplay(smsModal) {
    console.log('🔄 [SMS-MODAL-FIX] 💪 Forçage de l\'affichage du modal SMS...');
    
    if (!smsModal) {
        console.log('🔄 [SMS-MODAL-FIX] ⚠️ Modal SMS non trouvé');
        return;
    }
    
    // Vérifier que le modal a bien la classe 'show' avant de forcer l'affichage
    if (!smsModal.classList.contains('show')) {
        console.log('🔄 [SMS-MODAL-FIX] ⚠️ Modal sans classe show - affichage annulé');
        return;
    }
    
    // Forcer les styles CSS SEULEMENT si le modal doit être affiché
    smsModal.style.cssText += `
        z-index: 1070 !important;
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        justify-content: center !important;
        align-items: center !important;
    `;
    
    const modalDialog = smsModal.querySelector('.modal-dialog');
    if (modalDialog) {
        modalDialog.style.cssText += `
            z-index: 1071 !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: none !important;
            pointer-events: auto !important;
            width: 500px !important;
            max-width: 90vw !important;
            margin: 1.75rem auto !important;
        `;
    }
    
    const modalContent = smsModal.querySelector('.modal-content');
    if (modalContent) {
        modalContent.style.cssText += `
            z-index: 1072 !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            min-width: 400px !important;
            min-height: 300px !important;
            background-color: ${document.body.classList.contains('dark-mode') ? '#2d3748' : '#ffffff'} !important;
            border: 1px solid ${document.body.classList.contains('dark-mode') ? '#4a5568' : '#dee2e6'} !important;
            border-radius: 0.375rem !important;
        `;
    }
    
    // Forcer le recalcul du layout
    smsModal.offsetHeight;
    if (modalDialog) modalDialog.offsetHeight;
    if (modalContent) modalContent.offsetHeight;
    
    // Vérifier les dimensions
    const rect = smsModal.getBoundingClientRect();
    console.log('🔄 [SMS-MODAL-FIX] 📏 Dimensions du modal SMS:', {
        width: rect.width,
        height: rect.height,
        display: getComputedStyle(smsModal).display,
        visibility: getComputedStyle(smsModal).visibility,
        opacity: getComputedStyle(smsModal).opacity,
        zIndex: getComputedStyle(smsModal).zIndex
    });
    
    if (rect.width === 0 || rect.height === 0) {
        console.log('🔄 [SMS-MODAL-FIX] ⚠️ Modal SMS a des dimensions nulles mais pas de modal de secours automatique');
        
        // DÉSACTIVÉ : Le modal de secours automatique causait des problèmes
        // setTimeout(function() {
        //     console.log('🔄 [SMS-MODAL-FIX] 🚨 Échec du modal original, création du modal de secours...');
        //     window.createEmergencySmsModal();
        // }, 500);
        
    } else {
        console.log('🔄 [SMS-MODAL-FIX] ✅ Modal SMS affiché avec succès');
    }
}

// Fonction de debug spécifique au modal SMS
window.debugSmsModal = function() {
    const smsModal = document.getElementById('smsModal');
    if (!smsModal) {
        console.log('🔄 [SMS-MODAL-DEBUG] ❌ Modal SMS non trouvé');
        return;
    }
    
    const modalDialog = smsModal.querySelector('.modal-dialog');
    const modalContent = smsModal.querySelector('.modal-content');
    const rect = smsModal.getBoundingClientRect();
    
    console.log('🔄 [SMS-MODAL-DEBUG] État du modal SMS:', {
        element: !!smsModal,
        classes: smsModal.className,
        isOpen: smsModal.classList.contains('show'),
        display: getComputedStyle(smsModal).display,
        visibility: getComputedStyle(smsModal).visibility,
        opacity: getComputedStyle(smsModal).opacity,
        zIndex: getComputedStyle(smsModal).zIndex,
        dimensions: {
            width: rect.width,
            height: rect.height
        },
        dialog: {
            element: !!modalDialog,
            display: modalDialog ? getComputedStyle(modalDialog).display : 'N/A',
            dimensions: modalDialog ? modalDialog.getBoundingClientRect() : 'N/A'
        },
        content: {
            element: !!modalContent,
            display: modalContent ? getComputedStyle(modalContent).display : 'N/A',
            dimensions: modalContent ? modalContent.getBoundingClientRect() : 'N/A',
            htmlLength: modalContent ? modalContent.innerHTML.length : 0
        },
        backdrops: document.querySelectorAll('.modal-backdrop').length,
        parent: smsModal.parentElement ? smsModal.parentElement.tagName : 'N/A'
    });
    
    if (modalContent) {
        console.log('🔄 [SMS-MODAL-DEBUG] Contenu HTML:', modalContent.innerHTML.substring(0, 500));
    }
    
    console.log('🔄 [SMS-MODAL-DEBUG] 🚨 Création d\'un modal de secours...');
    window.createEmergencySmsModal();
};

// Fonction pour créer un modal SMS de secours
window.createEmergencySmsModal = function() {
    // Supprimer le modal existant s'il existe
    const existingModal = document.getElementById('smsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Créer un nouveau modal SMS fonctionnel
    const emergencyModal = document.createElement('div');
    emergencyModal.id = 'smsModal';
    emergencyModal.className = 'modal fade show';
    emergencyModal.setAttribute('data-emergency-modal', 'true');
    emergencyModal.style.cssText = `
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        z-index: 1070 !important;
        width: 100vw !important;
        height: 100vh !important;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        background-color: rgba(0, 0, 0, 0.5) !important;
    `;
    
    emergencyModal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered" style="
            position: relative !important;
            width: 500px !important;
            max-width: 90vw !important;
            margin: 1.75rem auto !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            transform: none !important;
        ">
            <div class="modal-content" style="
                position: relative !important;
                width: 100% !important;
                min-width: 400px !important;
                min-height: 300px !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                background-color: ${document.body.classList.contains('dark-mode') ? '#2d3748' : '#ffffff'} !important;
                border: 1px solid ${document.body.classList.contains('dark-mode') ? '#4a5568' : '#dee2e6'} !important;
                border-radius: 0.375rem !important;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
            ">
                <div class="modal-header" style="background-color: ${document.body.classList.contains('dark-mode') ? '#1f2937' : '#f8f9fa'} !important; padding: 15px; border-bottom: 1px solid #ddd;">
                    <h5 class="modal-title" style="margin: 0; color: ${document.body.classList.contains('dark-mode') ? '#f9fafb' : '#333'};">📱 Envoyer un SMS</h5>
                    <button type="button" class="btn-close" onclick="closeEmergencySmsModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: ${document.body.classList.contains('dark-mode') ? '#f9fafb' : '#333'};">×</button>
                </div>
                 <div class="modal-body" style="padding: 20px; min-height: 200px; color: ${document.body.classList.contains('dark-mode') ? '#f9fafb' : '#333'};">
                     <div class="form-group" style="margin-bottom: 15px;">
                        <label for="emergency-sms-message" style="display: block; margin-bottom: 5px; font-weight: bold;">Message SMS :</label>
                        <textarea id="emergency-sms-message" class="form-control" rows="4" placeholder="Tapez votre message ici..." style="
                            width: 100%; 
                            padding: 10px; 
                            border: 1px solid #ccc; 
                            border-radius: 4px; 
                            resize: vertical;
                            background-color: ${document.body.classList.contains('dark-mode') ? '#374151' : '#ffffff'};
                            color: ${document.body.classList.contains('dark-mode') ? '#f9fafb' : '#333'};
                         "></textarea>
                     </div>
                 </div>
                <div class="modal-footer" style="background-color: ${document.body.classList.contains('dark-mode') ? '#374151' : '#f8f9fa'} !important; padding: 15px; border-top: 1px solid #ddd; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeEmergencySmsModal()" style="
                        padding: 8px 16px; 
                        margin-right: 10px; 
                        background-color: #6c757d; 
                        color: white; 
                        border: none; 
                        border-radius: 4px; 
                        cursor: pointer;
                    ">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="sendEmergencySms()" style="
                        padding: 8px 16px; 
                        background-color: #007bff; 
                        color: white; 
                        border: none; 
                        border-radius: 4px; 
                        cursor: pointer;
                    ">Envoyer SMS</button>
                </div>
            </div>
        </div>
    `;
    
    // Ajouter le modal au body
    document.body.appendChild(emergencyModal);
    
    console.log('🔄 [SMS-MODAL-FIX] ✅ Modal SMS de secours créé et affiché');
    return emergencyModal;
};

// Fonction pour envoyer un SMS depuis le modal de secours
window.sendEmergencySms = function() {
    const messageTextarea = document.getElementById('emergency-sms-message');
    const message = messageTextarea ? messageTextarea.value.trim() : '';
    
    if (!message) {
        alert('Veuillez saisir un message SMS');
        return;
    }
    
    // Récupérer les informations du client depuis le RepairModal qui était ouvert
    const repairId = getActiveRepairId();
    const clientPhone = getClientPhoneFromRepair(repairId);
    const clientId = getClientIdFromRepair(repairId);
    
    // Si pas de téléphone ET pas de repairId, on ne peut pas envoyer
    if (!clientPhone && !repairId) {
        alert('Impossible de récupérer le numéro de téléphone du client et aucun ID de réparation disponible');
        return;
    }
    
    console.log('🔄 [SMS-MODAL-FIX] 📱 Envoi SMS de secours:', {
        repairId: repairId,
        phone: clientPhone,
        message: message.substring(0, 50) + '...'
    });
    
    // Désactiver le bouton pendant l'envoi
    const sendButton = document.querySelector('#smsModal .btn-primary');
    if (sendButton) {
        sendButton.disabled = true;
        sendButton.textContent = 'Envoi en cours...';
    }
    
    // Préparer les données (selon l'API send_sms.php)
    const formData = new FormData();
    if (clientPhone) {
        formData.append('telephone', clientPhone);  // Paramètre correct pour l'API
    }
    formData.append('message', message);
    if (repairId) {
        formData.append('reparation_id', repairId);  // Paramètre correct pour l'API
    }
    if (clientId) {
        formData.append('client_id', clientId);  // Ajouter client_id si disponible
    }
    
    // Ajouter l'ID du magasin si disponible
    const shopId = getShopId();
    if (shopId) {
        formData.append('shop_id', shopId);
    }
    
    // Envoyer le SMS
    fetch('ajax/send_sms.php', {
        method: 'POST',
        body: formData,
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('SMS envoyé avec succès !');
            closeEmergencySmsModal();
        } else {
            alert('Erreur lors de l\'envoi du SMS : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('🔄 [SMS-MODAL-FIX] ❌ Erreur envoi SMS:', error);
        alert('Erreur lors de l\'envoi du SMS : ' + error.message);
    })
    .finally(() => {
        // Réactiver le bouton
        if (sendButton) {
            sendButton.disabled = false;
            sendButton.textContent = 'Envoyer SMS';
        }
    });
};

// Fonction utilitaire pour récupérer l'ID de réparation active
function getActiveRepairId() {
    // Chercher dans les données du RepairModal qui était ouvert
    const repairModalElement = document.getElementById('repairDetailsModal');
    if (repairModalElement && repairModalElement.dataset.repairId) {
        return repairModalElement.dataset.repairId;
    }
    
    // Chercher dans l'URL ou d'autres sources
    const urlParams = new URLSearchParams(window.location.search);
    const repairId = urlParams.get('repair_id');
    if (repairId) {
        return repairId;
    }
    
    // Dernière tentative : chercher dans les variables globales
    if (typeof window.currentRepairId !== 'undefined') {
        return window.currentRepairId;
    }
    
    return null;
}

// Fonction utilitaire pour récupérer le téléphone du client
function getClientPhoneFromRepair(repairId) {
    console.log('🔄 [SMS-MODAL-FIX] 📞 Récupération du téléphone client pour réparation:', repairId);
    
    // 1. Chercher dans les données globales de la réparation
    if (typeof window.repairData !== 'undefined' && window.repairData.client_telephone) {
        console.log('🔄 [SMS-MODAL-FIX] ✅ Téléphone trouvé dans repairData:', window.repairData.client_telephone);
        return window.repairData.client_telephone;
    }
    
    // 2. Chercher dans le bouton SMS qui a déclenché le modal (data-client-tel)
    const smsButton = document.querySelector('.btn-sms[data-client-tel]');
    if (smsButton && smsButton.dataset.clientTel) {
        console.log('🔄 [SMS-MODAL-FIX] ✅ Téléphone trouvé dans bouton SMS:', smsButton.dataset.clientTel);
        return smsButton.dataset.clientTel;
    }
    
    // 3. Chercher dans le DOM du RepairModal - plusieurs sélecteurs possibles
    const phoneSelectors = [
        '#repairDetailsModal .client-phone',
        '#repairDetailsModal [data-client-phone]',
        '#repairDetailsModal [data-client-tel]',
        '#repairDetailsModal .client-telephone',
        '#repairDetailsModal .phone',
        '#repairDetailsModal .telephone'
    ];
    
    for (const selector of phoneSelectors) {
        try {
            const phoneElement = document.querySelector(selector);
            if (phoneElement) {
                const phoneText = phoneElement.textContent.trim() || phoneElement.dataset.clientPhone || phoneElement.dataset.clientTel || phoneElement.value;
                if (phoneText && phoneText !== '' && phoneText !== 'N/A' && phoneText !== 'Non renseigné') {
                    console.log('🔄 [SMS-MODAL-FIX] ✅ Téléphone trouvé via sélecteur', selector, ':', phoneText);
                    return phoneText;
                }
            }
        } catch (e) {
            // Ignorer les erreurs de sélecteur
        }
    }
    
    // 4. Chercher dans le texte du modal qui contient "Téléphone :"
    const allElements = document.querySelectorAll('#repairDetailsModal *');
    for (const element of allElements) {
        const text = element.textContent.trim();
        if (text.includes('Téléphone :') || text.includes('Téléphone:')) {
            // Extraire le numéro qui suit
            const phoneMatch = text.match(/Téléphone\s*:\s*([0-9\s\+\-\.]+)/);
            if (phoneMatch && phoneMatch[1]) {
                const phone = phoneMatch[1].trim();
                console.log('🔄 [SMS-MODAL-FIX] ✅ Téléphone trouvé par regex:', phone);
                return phone;
            }
        }
    }
    
    // 5. Chercher dans toutes les cellules qui contiennent un numéro de téléphone
    const allCells = document.querySelectorAll('#repairDetailsModal td, #repairDetailsModal .info-value, #repairDetailsModal .field-value, #repairDetailsModal p');
    for (const cell of allCells) {
        const text = cell.textContent.trim();
        // Regex pour détecter un numéro de téléphone français
        const phoneRegex = /^(\+33|0)[1-9](\d{8})$/;
        const cleanText = text.replace(/\s/g, '');
        if (phoneRegex.test(cleanText)) {
            console.log('🔄 [SMS-MODAL-FIX] ✅ Téléphone trouvé par regex:', text);
            return text;
        }
    }
    
    // 6. Chercher dans les attributs data-* de tous les éléments
    const elementsWithData = document.querySelectorAll('#repairDetailsModal [data-client-tel], #repairDetailsModal [data-telephone], #repairDetailsModal [data-phone]');
    for (const element of elementsWithData) {
        const phone = element.dataset.clientTel || element.dataset.telephone || element.dataset.phone;
        if (phone && phone !== '' && phone !== 'N/A') {
            console.log('🔄 [SMS-MODAL-FIX] ✅ Téléphone trouvé dans data attributes:', phone);
            return phone;
        }
    }
    
    // 7. Si on a un repairId, faire un appel AJAX pour récupérer les données
    if (repairId) {
        console.log('🔄 [SMS-MODAL-FIX] ⏳ Tentative de récupération par AJAX pour repairId:', repairId);
        // Cette fonction sera synchrone pour simplifier, mais on pourrait l'améliorer
        try {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `ajax/get_repair_details.php?id=${repairId}`, false); // Synchrone
            xhr.send();
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                if (data.success && data.repair && data.repair.client_telephone) {
                    console.log('🔄 [SMS-MODAL-FIX] ✅ Téléphone récupéré par AJAX:', data.repair.client_telephone);
                    return data.repair.client_telephone;
                }
            }
        } catch (e) {
            console.log('🔄 [SMS-MODAL-FIX] ❌ Erreur AJAX:', e.message);
        }
    }
    
    // 8. Dernière tentative : chercher dans window.currentRepair si disponible
    if (typeof window.currentRepair !== 'undefined' && window.currentRepair.client_telephone) {
        console.log('🔄 [SMS-MODAL-FIX] ✅ Téléphone trouvé dans currentRepair:', window.currentRepair.client_telephone);
        return window.currentRepair.client_telephone;
    }
    
    console.log('🔄 [SMS-MODAL-FIX] ❌ Aucun téléphone trouvé, impossible d\'envoyer le SMS');
    return null; // Retourner null au lieu d'un numéro par défaut
}

// Fonction utilitaire pour récupérer l'ID du client
function getClientIdFromRepair(repairId) {
    console.log('🔄 [SMS-MODAL-FIX] 👤 Récupération de l\'ID client pour réparation:', repairId);
    
    // 1. Chercher dans les données globales de la réparation
    if (typeof window.repairData !== 'undefined' && window.repairData.client_id) {
        console.log('🔄 [SMS-MODAL-FIX] ✅ Client ID trouvé dans repairData:', window.repairData.client_id);
        return window.repairData.client_id;
    }
    
    // 2. Chercher dans le bouton SMS qui a déclenché le modal (data-client-id)
    const smsButton = document.querySelector('.btn-sms[data-client-id]');
    if (smsButton && smsButton.dataset.clientId) {
        console.log('🔄 [SMS-MODAL-FIX] ✅ Client ID trouvé dans bouton SMS:', smsButton.dataset.clientId);
        return smsButton.dataset.clientId;
    }
    
    // 3. Chercher dans les attributs data-* de tous les éléments du modal
    const elementsWithData = document.querySelectorAll('#repairDetailsModal [data-client-id]');
    for (const element of elementsWithData) {
        const clientId = element.dataset.clientId;
        if (clientId && clientId !== '' && clientId !== '0') {
            console.log('🔄 [SMS-MODAL-FIX] ✅ Client ID trouvé dans data attributes:', clientId);
            return clientId;
        }
    }
    
    // 4. Chercher dans window.currentRepair si disponible
    if (typeof window.currentRepair !== 'undefined' && window.currentRepair.client_id) {
        console.log('🔄 [SMS-MODAL-FIX] ✅ Client ID trouvé dans currentRepair:', window.currentRepair.client_id);
        return window.currentRepair.client_id;
    }
    
    console.log('🔄 [SMS-MODAL-FIX] ⚠️ Aucun client ID trouvé');
    return null;
}

// Fonction utilitaire pour récupérer l'ID du magasin
function getShopId() {
    // Utiliser la même logique que les autres scripts
    if (typeof window.sessionHelper !== 'undefined' && window.sessionHelper.getShopId) {
        return window.sessionHelper.getShopId();
    }
    
    // Chercher dans les données de la page
    const shopIdElement = document.querySelector('[data-shop-id]');
    if (shopIdElement) {
        return shopIdElement.dataset.shopId;
    }
    
    // Chercher dans les variables globales
    if (typeof window.shopId !== 'undefined') {
        return window.shopId;
    }
    
    return '63'; // Valeur par défaut basée sur les logs
}

// Fonction pour fermer proprement le modal SMS de secours
window.closeEmergencySmsModal = function() {
    console.log('🔄 [SMS-MODAL-FIX] 🚪 Fermeture du modal SMS de secours...');
    
    // Chercher le modal par différents moyens
    let modal = document.getElementById('smsModal');
    
    if (!modal) {
        // Chercher par classe si l'ID ne fonctionne pas
        modal = document.querySelector('.modal[id*="sms"]');
        console.log('🔄 [SMS-MODAL-FIX] 🔍 Modal trouvé par classe:', modal ? 'Oui' : 'Non');
    }
    
    if (!modal) {
        // Chercher tous les modals visibles
        const visibleModals = document.querySelectorAll('.modal[style*="display: block"], .modal[style*="display:block"]');
        if (visibleModals.length > 0) {
            modal = visibleModals[visibleModals.length - 1]; // Prendre le dernier ouvert
            console.log('🔄 [SMS-MODAL-FIX] 🔍 Modal trouvé parmi les visibles:', modal.id || 'sans ID');
        }
    }
    
    if (modal) {
        console.log('🔄 [SMS-MODAL-FIX] 📋 Modal trouvé - ID:', modal.id, 'Classes:', modal.className);
        
        // Masquer le modal de toutes les façons possibles
        modal.style.display = 'none !important';
        modal.style.visibility = 'hidden !important';
        modal.style.opacity = '0 !important';
        modal.style.zIndex = '-1 !important';
        
        // Supprimer les classes Bootstrap si présentes
        modal.classList.remove('show', 'd-block');
        modal.classList.add('d-none');
        
        // Supprimer complètement le modal s'il a été créé dynamiquement
        if (modal.dataset.emergencyModal === 'true') {
            console.log('🔄 [SMS-MODAL-FIX] 🗑️ Suppression du modal de secours...');
            modal.remove();
        }
        
        console.log('🔄 [SMS-MODAL-FIX] ✅ Modal SMS masqué');
    } else {
        console.log('🔄 [SMS-MODAL-FIX] ❌ Aucun modal SMS trouvé');
        
        // Debug : lister tous les modals présents
        const allModals = document.querySelectorAll('.modal, [id*="modal"], [id*="Modal"]');
        console.log('🔄 [SMS-MODAL-FIX] 📋 Modals présents:', Array.from(allModals).map(m => ({
            id: m.id,
            classes: m.className,
            display: m.style.display,
            visible: m.offsetWidth > 0 && m.offsetHeight > 0
        })));
    }
    
    // Nettoyer les backdrops dans tous les cas
    const backdrops = document.querySelectorAll('.modal-backdrop, [class*="backdrop"]');
    console.log('🔄 [SMS-MODAL-FIX] 🧹 Backdrops trouvés:', backdrops.length);
    backdrops.forEach((backdrop, index) => {
        console.log(`🔄 [SMS-MODAL-FIX] 🗑️ Suppression backdrop ${index + 1}:`, backdrop.className);
        backdrop.remove();
    });
    
    // Restaurer le scroll du body dans tous les cas
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    console.log('🔄 [SMS-MODAL-FIX] ✅ Nettoyage terminé');
};

// Fonction de test pour diagnostiquer les problèmes de fermeture
window.testCloseModal = function() {
    console.log('🧪 [SMS-TEST] Test de fermeture du modal...');
    
    // Lister tous les modals
    const allModals = document.querySelectorAll('.modal, [id*="modal"], [id*="Modal"]');
    console.log('🧪 [SMS-TEST] Modals trouvés:', allModals.length);
    
    allModals.forEach((modal, index) => {
        console.log(`🧪 [SMS-TEST] Modal ${index + 1}:`, {
            id: modal.id,
            classes: modal.className,
            display: modal.style.display,
            visibility: modal.style.visibility,
            opacity: modal.style.opacity,
            zIndex: modal.style.zIndex,
            visible: modal.offsetWidth > 0 && modal.offsetHeight > 0,
            emergencyModal: modal.dataset.emergencyModal
        });
    });
    
    // Tenter de fermer
    closeEmergencySmsModal();
};

console.log('🔄 [SMS-MODAL-FIX] ✅ Script prêt');
console.log('🔄 [SMS-MODAL-FIX] 💡 Utilisez window.debugSmsModal() pour diagnostiquer');
console.log('🔄 [SMS-MODAL-FIX] 🧪 Utilisez window.testCloseModal() pour tester la fermeture');
