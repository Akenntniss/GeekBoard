/**
 * Scanner Modal Logic - Complete & Clean Version
 * Extracted from modals.php to ensure proper loading and functionality
 */

// Global variables
let universalScannerStream = null;
let universalScannerAnimation = null;
let currentCamera = 'environment';
let lastBarcodeScanTime = 0;

/**
 * Ouvrir le scanner universel
 */
function openUniversalScanner() {
    console.log('🔍 Opening Universal Scanner...');

    const modalEl = document.getElementById('universal_scanner_modal');
    if (!modalEl) {
        console.error('❌ Modal element not found!');
        return;
    }

    // Charger les bibliothèques si nécessaire
    if (typeof window.loadScannerLibraries === 'function') {
        window.loadScannerLibraries(() => {
            // Bibliothèques chargées, ouvrir le modal
            console.log('📸 Ouverture modal scanner');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            // Démarrer le scanner après l'ouverture du modal
            setTimeout(() => {
                startUniversalScanner();
            }, 500);
        });
    } else {
        // Fallback: ouvrir directement si les bibliothèques sont déjà présentes
        console.log('📸 Ouverture directe du modal scanner');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Démarrer le scanner après l'ouverture du modal
        setTimeout(() => {
            startUniversalScanner();
        }, 500);
    }
}

/**
 * Démarrer le scanner
 */
async function startUniversalScanner() {
    console.log('📸 Starting scanner camera...');

    const video = document.getElementById('universal_scanner_video');
    const status = document.getElementById('universal_scanner_status');

    if (!video || !status) {
        console.error('❌ Video or status element not found');
        return;
    }

    try {
        status.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Démarrage de la caméra...';
        status.className = 'scanner-status';

        // Arrêter le stream précédent s'il existe
        stopUniversalScanner();

        // Détecter si on est sur mobile/tablette
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
            window.innerWidth <= 768;
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

        // Configuration de la caméra
        let constraints = {
            video: {
                facingMode: currentCamera,
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };

        try {
            universalScannerStream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = universalScannerStream;

            // Attendre que la vidéo soit prête pour lancer la boucle de scan
            video.onloadedmetadata = () => {
                video.play();
                status.innerHTML = '<i class="fas fa-camera me-2"></i>Caméra active - Scannez un code';
                status.className = 'scanner-status success';
                startScanningLoop();
            };

        } catch (error) {
            console.error('Camera error:', error);
            status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur caméra: ' + error.message;
            status.className = 'scanner-status error';
        }

    } catch (e) {
        console.error('Scanner start error:', e);
        if (status) {
            status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur critique';
            status.className = 'scanner-status error';
        }
    }
}

/**
 * Boucle de scan (Detection)
 */
function startScanningLoop() {
    const video = document.getElementById('universal_scanner_video');
    if (!video || !universalScannerStream) return;

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    function scanFrame() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);

            // 1. Essayer jsQR (QR Codes)
            if (typeof jsQR !== 'undefined') {
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "attemptBoth",
                });

                if (code && code.data) {
                    handleQRCodeDetected(code.data);
                    return; // Stop scanning
                }
            }

            // 2. Essayer Quagga (Code-barres) - Throttled
            const now = Date.now();
            if (typeof Quagga !== 'undefined' && (now - lastBarcodeScanTime > 500)) {
                lastBarcodeScanTime = now;

                Quagga.decodeSingle({
                    decoder: {
                        readers: ["ean_reader", "code_128_reader", "code_39_reader", "ean_8_reader"]
                    },
                    locate: true,
                    src: canvas.toDataURL() // Quagga needs image source
                }, function (result) {
                    if (result && result.codeResult) {
                        handleBarcodeDetected(result.codeResult.code);
                    }
                });
            }
        }

        if (universalScannerStream) {
            universalScannerAnimation = requestAnimationFrame(scanFrame);
        }
    }

    universalScannerAnimation = requestAnimationFrame(scanFrame);
}

/**
 * Arrêter le scanner
 */
function stopUniversalScanner() {
    if (universalScannerAnimation) {
        cancelAnimationFrame(universalScannerAnimation);
        universalScannerAnimation = null;
    }

    if (universalScannerStream) {
        universalScannerStream.getTracks().forEach(track => track.stop());
        universalScannerStream = null;
    }
}

/**
 * Fermer le scanner
 */
function closeUniversalScanner() {
    stopUniversalScanner();
    const modalEl = document.getElementById('universal_scanner_modal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
}

/**
 * Handlers
 */
function handleQRCodeDetected(data) {
    console.log('✅ QR Code detected:', data);
    stopUniversalScanner();

    const status = document.getElementById('universal_scanner_status');
    if (status) {
        status.innerHTML = '<i class="fas fa-check me-2"></i>QR Code détecté !';
        status.className = 'scanner-status success';
    }

    if (data.startsWith('http://') || data.startsWith('https://')) {
        setTimeout(() => { window.location.href = data; }, 1000);
    } else {
        setTimeout(() => { handleProductCode(data); }, 1000);
    }
}

function handleBarcodeDetected(code) {
    console.log('✅ Barcode detected:', code);
    stopUniversalScanner();

    const status = document.getElementById('universal_scanner_status');
    if (status) {
        status.innerHTML = '<i class="fas fa-check me-2"></i>Code-barres détecté !';
        status.className = 'scanner-status success';
    }

    setTimeout(() => { handleProductCode(code); }, 1000);
}

function handleProductCode(code) {
    closeUniversalScanner();

    // Vérifier si le produit existe
    const url = `ajax/verifier_produit.php?code=${encodeURIComponent(code)}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.existe && data.id) {
                // Toujours utiliser le workflow QR/Stock moderne
                if (typeof gbOpenStockWorkflow === 'function') {
                    console.log('🎯 Produit détecté - Ouverture workflow QR/Stock');
                    gbOpenStockWorkflow(data.id, data); // Ouvre QR scanner -> ajustement
                } else {
                    console.error('❌ gbOpenStockWorkflow non disponible - Rechargez stock-qr-workflow.js');
                    alert('Fonction non disponible. Rechargez la page.');
                }
            } else if (data.error) {
                alert(`Erreur serveur: ${data.error}`);
            } else {
                if (confirm(`Produit non trouvé: ${code}\n\nSouhaitez-vous ajouter ce produit au stock ?`)) {
                    openAddProductModal(code);
                }
            }
        })
        .catch(error => {
            console.error('API Error:', error);
            alert('Erreur lors de la vérification du produit');
        });
}

function showProductInfo(productData) {
    const modalHtml = `
        <div id="productInfoModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-box me-2"></i>Produit Trouvé</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="fw-bold text-primary">${productData.nom}</h6>
                                <p class="text-muted mb-2">Référence: <code>${productData.reference}</code></p>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-boxes text-info me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Quantité</small>
                                        <span class="fw-bold fs-5 ${productData.quantite > 0 ? 'text-success' : 'text-danger'}">${productData.quantite}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tag text-warning me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">ID</small>
                                        <span class="fw-bold">#${productData.id}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" onclick="window.location.href='index.php?page=inventaire#product-${productData.id}'">
                            <i class="fas fa-edit me-1"></i>Ajuster Stock
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const existingModal = document.getElementById('productInfoModal');
    if (existingModal) existingModal.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('productInfoModal'));
    modal.show();

    document.getElementById('productInfoModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

function openAddProductModal(code) {
    window.location.href = `index.php?page=inventaire&action=add&code=${encodeURIComponent(code)}`;
}

// Expose functions globally
window.openUniversalScanner = openUniversalScanner;
window.startUniversalScanner = startUniversalScanner;
window.stopUniversalScanner = stopUniversalScanner;
window.closeUniversalScanner = closeUniversalScanner;
