/**
 * Stock QR Workflow - Global Functions
 * Workflow QR Scanner + Stock Adjustment disponible sur toutes les pages
 */

// Variables globales
let gbCurrentQuantity = 0;
let gbOriginalQuantity = 0;
let gbProductPrice = 0;
let gbCurrentProductId = null;
let gbQRScannerActive = false;
let gbQRAnimationFrame = null;

// === MODAL MANAGEMENT ===

function gbOpen(id) {
    document.getElementById(id).classList.add('show');
    document.body.classList.add('modern-modal-open');
}

function gbClose(id) {
    document.getElementById(id).classList.remove('show');
    const openModals = document.querySelectorAll('.modern-modal.show');
    if (openModals.length === 0) {
        document.body.classList.remove('modern-modal-open');
    }
}

// === MAIN ENTRY POINT ===

/**
 * Point d'entrée principal - appelé depuis scanner-modal-fix.js
 * @param {number} productId - ID du produit
 * @param {object} productData - Données du produit (nom, ref, quantite, prix)
 */
/**
 * Point d'entrée principal - appelé depuis scanner-modal-fix.js
 * @param {number} productId - ID du produit
 * @param {object} productData - Données du produit (nom, ref, quantite, prix)
 */
function gbOpenStockWorkflow(productId, productData) {
    // console.log('🎯 Ouverture workflow QR/Stock pour produit#', productId);

    gbCurrentProductId = productId;
    gbCurrentQuantity = productData.quantite || 0;
    gbOriginalQuantity = productData.quantite || 0;
    gbProductPrice = productData.prix_achat || 0;

    // DEBUG: Log des données reçues
    console.log('📦 [STOCK-WORKFLOW] Produit reçu:', { id: productId, data: productData });

    // Peupler les champs du modal Adjust
    const idInput = document.getElementById('gb_adjust_id');
    const nameInput = document.getElementById('gb_adjust_name');
    const refInput = document.getElementById('gb_adjust_ref');
    const currentInput = document.getElementById('gb_adjust_current');
    const originalInput = document.getElementById('gb_adjust_original');
    const newInput = document.getElementById('gb_adjust_new');

    if (idInput) idInput.value = productId;
    if (nameInput) nameInput.textContent = productData.nom || 'Produit Inconnu';
    if (refInput) refInput.textContent = productData.reference || productData.code_barre || 'N/A';
    if (currentInput) currentInput.textContent = gbCurrentQuantity;
    if (originalInput) originalInput.value = gbCurrentQuantity;
    if (newInput) newInput.value = gbCurrentQuantity;

    // Assurer que le DOM est à jour avant d'ouvrir
    setTimeout(() => {
        // Ouvrir le scanner QR
        gbOpen('gbQRRepairModal');
        gbInitQRScanner();
    }, 50);
}

// === QR SCANNER FUNCTIONS ===

function gbInitQRScanner() {
    const video = document.getElementById('gb_qr_video');
    const canvas = document.getElementById('gb_qr_canvas');
    const statusDiv = document.getElementById('gb_qr_status');

    if (!video || !canvas) {
        console.error('Éléments QR scanner introuvables');
        return;
    }

    const ctx = canvas.getContext('2d');
    gbQRScannerActive = true;

    // Démarrer la caméra
    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
    })
        .then(stream => {
            video.srcObject = stream;
            video.play();

            // Quand la vidéo est prête, démarrer le scan
            video.addEventListener('loadedmetadata', () => {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                gbScanQRFrame(video, canvas, ctx, statusDiv);
            });
        })
        .catch(err => {
            console.error('Erreur caméra:', err);
            statusDiv.innerHTML = '❌ Impossible d\'accéder à la caméra';
            statusDiv.style.background = 'rgba(239,68,68,0.1)';
            statusDiv.style.color = '#ef4444';
        });
}

function gbScanQRFrame(video, canvas, ctx, statusDiv) {
    if (!gbQRScannerActive) return;

    // Dessiner l'image vidéo sur le canvas
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Obtenir les données d'image
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

    // Utiliser jsQR pour détecter le QR code
    if (typeof jsQR !== 'undefined') {
        const code = jsQR(imageData.data, imageData.width, imageData.height);

        if (code && code.data) {
            // QR code détecté !
            gbOnQRDetected(code.data);
            return; // Arrêter le scan
        }
    }

    // Continuer le scan
    gbQRAnimationFrame = requestAnimationFrame(() => gbScanQRFrame(video, canvas, ctx, statusDiv));
}

function gbOnQRDetected(url) {
    // console.log('QR détecté:', url);

    const statusDiv = document.getElementById('gb_qr_status');

    // Extraire l'ID de réparation depuis l'URL (format: page=statut_rapide&id=1741 ou ?id=XX)
    const match = url.match(/[?&]id=(\d+)/i);

    if (!match) {
        statusDiv.innerHTML = '❌ QR code invalide (pas une URL de suivi)';
        statusDiv.style.background = 'rgba(239,68,68,0.1)';
        statusDiv.style.color = '#ef4444';

        // Réessayer après 2 secondes
        setTimeout(() => {
            statusDiv.innerHTML = '📱 Scannez le QR code de la réparation...';
            statusDiv.style.background = 'rgba(139,92,246,0.1)';
            statusDiv.style.color = '#8b5cf6';
        }, 2000);
        return;
    }

    const reparationId = match[1];

    // Afficher un indicateur de traitement
    statusDiv.innerHTML = `✅ Réparation #${reparationId} détectée! Association en cours...`;
    statusDiv.style.background = 'rgba(16,185,129,0.1)';
    statusDiv.style.color = '#10b981';

    // Arrêter le scanner
    gbStopQRScanner();

    // Associer la pièce à la réparation
    gbAssociatePieceToRepair(gbCurrentProductId, reparationId);
}

function gbAssociatePieceToRepair(produitId, reparationId) {
    const formData = new FormData();
    formData.append('produit_id', produitId);
    formData.append('reparation_id', reparationId);
    formData.append('quantite', 1);

    fetch('ajax/associer_piece_reparation.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                gbShowToast(`✅ ${data.produit_nom || 'Pièce'} associée à la réparation #${reparationId}`, 'success');
                gbClose('gbQRRepairModal');

                // Rafraîchir la page pour afficher le nouveau stock
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(err => {
            gbShowToast('❌ Erreur: ' + err.message, 'error');

            // Réouvrir le scanner après erreur
            setTimeout(() => {
                gbInitQRScanner();
            }, 2000);
        });
}

function gbStopQRScanner() {
    gbQRScannerActive = false;

    if (gbQRAnimationFrame) {
        cancelAnimationFrame(gbQRAnimationFrame);
        gbQRAnimationFrame = null;
    }

    const video = document.getElementById('gb_qr_video');
    if (video && video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
}

function gbCloseQRScanner() {
    gbStopQRScanner();
    gbClose('gbQRRepairModal');
}

function gbSkipQRScan() {
    gbStopQRScanner();
    gbClose('gbQRRepairModal');

    // Ouvrir directement le modal d'ajustement
    gbOpen('gbAdjustModal');
}

// === ADJUST MODAL FUNCTIONS ===

function gbDecreaseQuantity() {
    if (gbCurrentQuantity > 0) {
        gbCurrentQuantity--;
        gbUpdateQuantityDisplay();
    }
}

function gbIncreaseQuantity() {
    gbCurrentQuantity++;
    gbUpdateQuantityDisplay();
}

function gbUpdateQuantityDisplay() {
    document.getElementById('gb_adjust_current').textContent = gbCurrentQuantity;
    document.getElementById('gb_adjust_new').value = gbCurrentQuantity;
}

function gbUpdateStock() {
    const nouvelleQuantite = gbCurrentQuantity;
    const ancienneQuantite = gbOriginalQuantity;

    if (nouvelleQuantite === ancienneQuantite) {
        gbShowToast('⚠️ Aucun changement détecté', 'warning');
        return;
    }

    // Si réduction de stock, ouvrir modal de raison
    if (nouvelleQuantite < ancienneQuantite) {
        gbClose('gbAdjustModal'); // Fermer le modal précédent pour éviter les conflits de superposition
        setTimeout(() => {
            gbOpen('gbReasonModal');
        }, 300); // Petit délai pour l'animation
    } else {
        // Augmentation directe
        gbExecuteStockUpdate(gbCurrentProductId, nouvelleQuantite, 'Augmentation de stock');
    }
}

function gbExecuteStockUpdate(produitId, nouvelleQuantite, motif = '', callback = null) {
    const formData = new FormData();
    // CORRECTION MAJEURE: Le PHP attend 'produit_id', pas 'id'
    formData.append('produit_id', produitId);
    formData.append('nouvelle_quantite', nouvelleQuantite); // PHP attend 'nouvelle_quantite', pas 'quantite'

    // Debug
    console.log('🚀 [STOCK-UPDATE] Envoi requête:', {
        produit_id: produitId,
        nouvelle_quantite: nouvelleQuantite,
        motif: motif
    });

    if (motif) {
        formData.append('motif', motif);
    }

    fetch('ajax/ajuster_stock.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                gbShowToast('✅ Stock mis à jour avec succès', 'success');
                gbClose('gbReasonModal');
                gbClose('gbAdjustModal');

                if (callback) callback();
                else setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(err => {
            gbShowToast('❌ Erreur: ' + err.message, 'error');
        });
}

// === REASON WORKFLOW (Prêt partenaire OU Autre) ===

function gbOpenPartnerSelect() {
    gbClose('gbReasonModal');
    gbOpen('gbPartnerSelectModal');

    // Charger les partenaires
    fetch('ajax/get_partenaires_simple.php', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('gb_partner_select');
                select.innerHTML = '<option value="">Sélectionnez un partenaire...</option>';
                data.partenaires.forEach(p => {
                    select.innerHTML += `<option value="${p.id}">${p.nom}</option>`;
                });

                // Pré-remplir le montant
                const amountInput = document.getElementById('gb_partner_amount');
                amountInput.value = gbProductPrice;
            } else {
                gbShowToast('❌ Impossible de charger les partenaires', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            gbShowToast('❌ Erreur réseau', 'error');
        });
}

function gbConfirmPartnerTransaction() {
    const partnerId = document.getElementById('gb_partner_select').value;
    const transType = document.querySelector('input[name="gb_trans_type"]:checked')?.value;
    const amount = document.getElementById('gb_partner_amount').value;

    if (!partnerId || !transType || !amount) {
        gbShowToast('⚠️ Veuillez remplir tous les champs', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('partenaire_id', partnerId);
    formData.append('type', transType);
    formData.append('montant', amount);
    formData.append('description', `${transType} de pièce détachée`);

    fetch('ajax/add_transaction_partenaire.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                gbShowToast('✅ Transaction partenaire enregistrée', 'success');
                gbClose('gbPartnerSelectModal');

                // Maintenant ajuster le stock
                const produitId = document.getElementById('gb_adjust_id').value;
                const nouvelleQuantite = gbCurrentQuantity;
                gbExecuteStockUpdate(produitId, nouvelleQuantite, `Prêt de pièce détachée (${transType})`);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(err => {
            gbShowToast('❌ Erreur: ' + err.message, 'error');
        });
}

function gbOpenOtherReason() {
    gbClose('gbReasonModal');
    gbOpen('gbOtherReasonModal');
}

function gbConfirmOtherReason() {
    const reason = document.getElementById('gb_other_reason_text').value;
    if (!reason.trim()) {
        gbShowToast('⚠️ Veuillez indiquer une raison', 'warning');
        return;
    }

    const produitId = document.getElementById('gb_adjust_id').value;
    const nouvelleQuantite = gbCurrentQuantity;

    gbExecuteStockUpdate(produitId, nouvelleQuantite, reason);
}

// === TOAST NOTIFICATIONS ===

function gbShowToast(message, type = 'info') {
    // Supprimer les anciens toasts
    const existingToasts = document.querySelectorAll('.modern-toast');
    existingToasts.forEach(toast => toast.remove());

    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };

    const toast = document.createElement('div');
    toast.className = 'modern-toast';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        z-index: 100000;
        font-weight: 600;
        font-size: 14px;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

//  Expose globalement
window.gbOpenStockWorkflow = gbOpenStockWorkflow;
window.gbOpen = gbOpen;
window.gbClose = gbClose;
window.gbInitQRScanner = gbInitQRScanner;
window.gbCloseQRScanner = gbCloseQRScanner;
window.gbSkipQRScan = gbSkipQRScan;
window.gbDecreaseQuantity = gbDecreaseQuantity;
window.gbIncreaseQuantity = gbIncreaseQuantity;
window.gbUpdateStock = gbUpdateStock;
window.gbOpenPartnerSelect = gbOpenPartnerSelect;
window.gbConfirmPartnerTransaction = gbConfirmPartnerTransaction;
window.gbOpenOtherReason = gbOpenOtherReason;
window.gbConfirmOtherReason = gbConfirmOtherReason;
window.gbShowToast = gbShowToast;

// console.log('✅ Stock QR Workflow chargé');
