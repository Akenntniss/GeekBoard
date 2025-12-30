/**
 * CORRECTION SPÉCIALISÉE SCANNER CODES-BARRES
 * Diagnostic et correction des problèmes de détection Quagga
 */

console.log('🔧 [BARCODE-FIX] Initialisation de la correction codes-barres...');

// Variables de diagnostic
let barcodeStats = {
    framesProcessed: 0
    detectionAttempts: 0
    successfulDetections: 0
    failedDetections: 0
    averageProcessingTime: 0
    lastDetectionTime: null
};

// Configuration Quagga ultra-allégée
const OPTIMIZED_QUAGGA_CONFIG = {
    inputStream: {
        name: "Live"
        type: "LiveStream"
        constraints: {
            width: { min: 320, ideal: 640, max: 1280 }
            height: { min: 240, ideal: 480, max: 720 }
            facingMode: "environment"
        }
    }
    locator: {
        patchSize: "small",      // Plus petit pour plus de rapidité
        halfSample: true,        // Réduction de résolution pour vitesse
        showCanvas: false
        showPatches: false
        showFoundPatches: false
        showSkeleton: false
        showLabels: false
        showPatchLabels: false
        showBoundingBox: false
    }
    numOfWorkers: 1,            // Un seul worker pour éviter la surcharge
    frequency: 5,               // Fréquence réduite
    decoder: {
        readers: [
            "ean_reader",        // EAN-13 (le plus commun)
            "ean_8_reader",      // EAN-8
            "code_128_reader"    // Code 128 (commun)
        ]
        debug: {
            drawBoundingBox: false
            showFrequency: false
            drawScanline: false
            showPattern: false
        }
        multiple: false
    }
    locate: true
};

/**
 * Réinitialiser Quagga avec configuration optimisée
 */
function reinitializeQuagga() {

    // Arrêter Quagga s'il est en cours
    if (window.quaggaInitialized) {
        try {
            Quagga.stop();
        } catch (e) {
        }
        window.quaggaInitialized = false;
    }

    const video = document.getElementById('universal_scanner_video');
    if (!video) {
        return;
    }

    // Configuration avec la vidéo existante
    const config = {
        ...OPTIMIZED_QUAGGA_CONFIG
        inputStream: {
            ...OPTIMIZED_QUAGGA_CONFIG.inputStream
            target: video
        }
    };

    console.log('🔧 [BARCODE-FIX] Configuration Quagga:', config);

    Quagga.init(config, function (err) {
        if (err) {

            // Fallback ultra-simple
            Quagga.init({
                inputStream: {
                    name: "Live"
                    type: "LiveStream"
                    target: video
                }
                decoder: {
                    readers: ["ean_reader"]  // Seulement EAN
                }
            }, function (fallbackErr) {
                if (fallbackErr) {
                    return;
                }

                Quagga.start();
                window.quaggaInitialized = true;
                setupQuaggaHandlers();
                return;
            }
        
        Quagga.start();
            window.quaggaInitialized = true;
            setupQuaggaHandlers();
        }

        /**
         * Configuration des gestionnaires Quagga
         */
        function setupQuaggaHandlers() {
            // Gestionnaire de détection
            Quagga.onDetected(function (result) {
                const startTime = performance.now();
                barcodeStats.detectionAttempts++;

                if (result && result.codeResult && result.codeResult.code) {
                    const code = result.codeResult.code;
                    const format = result.codeResult.format;

                    console.log(`🎯 [BARCODE-FIX] Code détecté: ${code} (${format})`);

                    // Validation plus stricte
                    if (isValidBarcode(code, format)) {
                        barcodeStats.successfulDetections++;
                        barcodeStats.lastDetectionTime = new Date();

                        const processingTime = performance.now() - startTime;
                        barcodeStats.averageProcessingTime =
                            (barcodeStats.averageProcessingTime + processingTime) / 2;

                        console.log(`✅ [BARCODE-FIX] Code valide: ${code}`);

                        // Appeler la fonction de traitement globale
                        if (typeof handleScanResult === 'function') {
                            handleScanResult(code, `Code-barres ${format}`);
                        } else {
                            alert(`Code-barres scanné: ${code}`);
                        }
                    } else {
                        barcodeStats.failedDetections++;
                    }
                }

                // Gestionnaire de traitement
                Quagga.onProcessed(function (result) {
                    barcodeStats.framesProcessed++;

                    // Log périodique des stats
                    if (barcodeStats.framesProcessed % 100 === 0) {
                        frames: barcodeStats.framesProcessed
                        tentatives: barcodeStats.detectionAttempts
                        succès: barcodeStats.successfulDetections
                        échecs: barcodeStats.failedDetections
                        // Debug supprimé
                    }
                }

/**
 * Validation stricte des codes-barres
 */
function isValidBarcode(code, format) {
                        if (!code || code.length < 3) return false;

                        // Validation par format
                        switch (format) {
                            case 'ean_13':
                                return /^\d{13}$/.test(code) && code.length === 13;
                            case 'ean_8':
                                return /^\d{8}$/.test(code) && code.length === 8;
                            case 'code_128':
                                return /^[0-9A-Za-z\-\+\.\/\s]{3,}$/.test(code) && code.length >= 3;
                            default:
                                return /^[0-9A-Za-z\-\+\.\/\s]{3,}$/.test(code);
                        }
                    }

/**
 * Test de scan manuel avec image fixe
 */
function testBarcodeWithStaticImage() {

                        const video = document.getElementById('universal_scanner_video');
                        if (!video) return;

                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        ctx.drawImage(video, 0, 0);

                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

                        // Test avec Quagga sur image fixe
                        Quagga.decodeSingle({
                            decoder: {
                                readers: ["ean_reader", "code_128_reader"]
                            }
        locate: true
        src: canvas.toDataURL()
                        }, function (result) {
                            if (result && result.codeResult) {
                            } else {
                            }
                        }

/**
 * Amélioration de l'éclairage et du contraste
 */
function enhanceVideoForBarcode() {
                                const video = document.getElementById('universal_scanner_video');
                                if (!video) return;

                                // Appliquer des filtres CSS pour améliorer la détection
                                video.style.filter = 'contrast(1.2) brightness(1.1) saturate(0.8)';
                            }

/**
 * Diagnostic complet
 */
function fullBarcodeDiagnostic() {

                                // Vérifications de base

                                // Stats actuelles

                                // Test de performance
                                const startTime = performance.now();
                                setTimeout(() => {
                                    const endTime = performance.now();
                                }, 100);

                                // Test d'image statique
                                testBarcodeWithStaticImage();

                                console.log('🔍 [BARCODE-FIX] === FIN DIAGNOSTIC ===');
                            }

/**
 * Réinitialisation complète du scanner
 */
function resetBarcodeScanner() {

                                // Reset des stats
                                barcodeStats = {
                                    framesProcessed: 0
        detectionAttempts: 0
        successfulDetections: 0
        failedDetections: 0
        averageProcessingTime: 0
        lastDetectionTime: null
                                };

                                // Réinitialiser Quagga
                                reinitializeQuagga();

                                // Améliorer la vidéo
                                setTimeout(() => {
                                    enhanceVideoForBarcode();
                                }, 1000);

                                console.log('✅ [BARCODE-FIX] Réinitialisation terminée');
                            }

// Exposition des fonctions globales
window.barcodeFix = {
                                reinitialize: reinitializeQuagga
    diagnostic: fullBarcodeDiagnostic
    reset: resetBarcodeScanner
    enhance: enhanceVideoForBarcode
    stats: () => barcodeStats
    test: testBarcodeWithStaticImage
                            };

                        // Auto-initialisation si le scanner est ouvert
                        document.addEventListener('DOMContentLoaded', function () {
                            // Écouter l'ouverture du modal scanner
                            const scannerModal = document.getElementById('universal_scanner_modal');
                            if (scannerModal) {
                                scannerModal.addEventListener('shown.bs.modal', function () {

                                    setTimeout(() => {
                                        enhanceVideoForBarcode();

                                        // Réinitialiser Quagga après 2 secondes
                                        setTimeout(() => {
                                            if (window.quaggaInitialized) {
                                                reinitializeQuagga();
                                            }
                                        }, 2000);
                                    }, 1000);
                                }

console.log('✅ [BARCODE-FIX] Correction codes-barres chargée');
