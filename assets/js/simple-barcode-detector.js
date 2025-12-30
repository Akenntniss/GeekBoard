/**
 * DÉTECTEUR DE CODES-BARRES ULTRA-SIMPLE
 * Solution de fallback sans Quagga - Analyse directe d'image
 */

console.log('🔧 [SIMPLE-BARCODE] Initialisation du détecteur simple...');

// Variables globales
let simpleBarcodeActive = false;
let simpleBarcodeCanvas = null;
let simpleBarcodeContext = null;
let detectionInterval = null;
let simpleBarcodeFound = false;
let detectionCounts = {}; // { key: { count, lastTs } }

/**
 * Initialiser le détecteur simple
 */
function initSimpleBarcodeDetector() {
    
    const video = document.getElementById('universal_scanner_video');
    if (!video) {
        return false;
    }
    
    // Créer le canvas pour l'analyse
    simpleBarcodeCanvas = document.createElement('canvas');
    simpleBarcodeContext = simpleBarcodeCanvas.getContext('2d');
    
    console.log('✅ [SIMPLE-BARCODE] Détecteur simple initialisé');
    return true;
}

/**
 * Démarrer la détection simple
 */
function startSimpleBarcodeDetection() {
    if (simpleBarcodeActive) return;
    
    console.log('🎬 [SIMPLE-BARCODE] Démarrage de la détection simple');
    
    const video = document.getElementById('universal_scanner_video');
    if (!video || !simpleBarcodeCanvas) {
        return;
    }
    
    simpleBarcodeActive = true;
    simpleBarcodeFound = false;
    detectionCounts = {};
    
    // Détecter toutes les 500ms pour éviter la surcharge
    detectionInterval = setInterval(() => {
        if (!simpleBarcodeActive) return;
        
        try {
            analyzeVideoFrame(video);
        } catch (error) {
        }
    }, 500);
    
    console.log('✅ [SIMPLE-BARCODE] Détection démarrée');
}

/**
 * Arrêter la détection simple
 */
function stopSimpleBarcodeDetection() {
    
    simpleBarcodeActive = false;
    simpleBarcodeFound = false;
    detectionCounts = {};
    
    if (detectionInterval) {
        clearInterval(detectionInterval);
        detectionInterval = null;
    }
}

/**
 * Analyser une frame vidéo
 */
function analyzeVideoFrame(video) {
    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
    
    // Redimensionner le canvas
    const width = Math.min(video.videoWidth, 640);
    const height = Math.min(video.videoHeight, 480);
    
    simpleBarcodeCanvas.width = width;
    simpleBarcodeCanvas.height = height;
    
    // Dessiner la frame vidéo
    simpleBarcodeContext.drawImage(video, 0, 0, width, height);
    
    // Obtenir les données d'image
    const imageData = simpleBarcodeContext.getImageData(0, 0, width, height);
    
    // Analyser pour détecter des motifs de codes-barres
    const barcodePattern = detectBarcodePattern(imageData);
    
    if (barcodePattern.detected) {
        
        // Essayer de décoder avec différentes méthodes
        tryDecodeBarcode(imageData, barcodePattern);
    }
}

/**
 * Valider checksum et appliquer stabilisation (2 lectures en <1500ms)
 */
function validateAndMaybeAccept(code, format, sourceLabel) {
    if (!code) return false;
    const raw = String(code).trim();
    let fmt = (format || '').toUpperCase();
    let valid = false;
    if (fmt.includes('EAN_13') || raw.length === 13) {
        fmt = 'EAN_13';
        valid = !!(window.realBarcodeDecoder && window.realBarcodeDecoder.validateEAN13(raw));
    } else if (fmt.includes('EAN_8') || raw.length === 8) {
        fmt = 'EAN_8';
        valid = !!(window.realBarcodeDecoder && window.realBarcodeDecoder.validateEAN8(raw));
    }
    if (!valid) {
        return false;
    }
    const key = `${fmt}:${raw}`;
    const now = Date.now();
    const stat = detectionCounts[key] || { count: 0, lastTs: 0 };
    if (now - stat.lastTs < 1500) {
        stat.count += 1;
    } else {
        stat.count = 1;
    }
    stat.lastTs = now;
    detectionCounts[key] = stat;
    if (stat.count >= 2 && !simpleBarcodeFound) {
        simpleBarcodeFound = true;
        if (typeof handleScanResult === 'function') {
            handleScanResult(raw, `${fmt} (${sourceLabel})`);
        }
        stopSimpleBarcodeDetection();
        return true;
    }
    return false;
}

/**
 * Tentative native via BarcodeDetector (si disponible)
 */
function tryNativeBarcode(imageData) {
    try {
        if (!('BarcodeDetector' in window)) return;
        const canvas = document.createElement('canvas');
        canvas.width = imageData.width;
        canvas.height = imageData.height;
        const ctx = canvas.getContext('2d');
        ctx.putImageData(imageData, 0, 0);
        const detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8'] });
        detector.detect(canvas).then(results => {
            if (results && results.length) {
                const r = results[0];
                validateAndMaybeAccept(r.rawValue, (r.format || '').toUpperCase(), 'Native');
            }
        }).catch(() => {});
    } catch (e) {
    }
}

/**
 * Détecter un motif de code-barres dans l'image
 */
function detectBarcodePattern(imageData) {
    const data = imageData.data;
    const width = imageData.width;
    const height = imageData.height;
    
    // Analyser le centre de l'image
    const centerY = Math.floor(height / 2);
    const startX = Math.floor(width * 0.1);
    const endX = Math.floor(width * 0.9);
    
    let transitions = 0;
    let lastPixelDark = false;
    let darkBars = 0;
    let lightBars = 0;
    let currentBarLength = 0;
    let barLengths = [];
    
    // Analyser une ligne horizontale
    for (let x = startX; x < endX; x++) {
        const pixelIndex = (centerY * width + x) * 4;
        const r = data[pixelIndex];
        const g = data[pixelIndex + 1];
        const b = data[pixelIndex + 2];
        
        // Calculer la luminosité
        const brightness = (r + g + b) / 3;
        const isDark = brightness < 128;
        
        if (isDark !== lastPixelDark) {
            // Transition détectée
            transitions++;
            
            if (currentBarLength > 0) {
                barLengths.push(currentBarLength);
            }
            
            if (isDark) {
                darkBars++;
            } else {
                lightBars++;
            }
            
            currentBarLength = 1;
            lastPixelDark = isDark;
        } else {
            currentBarLength++;
        }
    }
    
    // Ajouter la dernière barre
    if (currentBarLength > 0) {
        barLengths.push(currentBarLength);
    }
    
    // Critères de détection d'un code-barres
    const hasEnoughTransitions = transitions >= 20 && transitions <= 100;
    const hasBalancedBars = Math.abs(darkBars - lightBars) <= 5;
    const hasVariedBarLengths = barLengths.length >= 10;
    
    const detected = hasEnoughTransitions && hasBalancedBars && hasVariedBarLengths;
    
    if (detected) {
    }
    
    return {
        detected
        transitions
        darkBars
        lightBars
        barLengths
        confidence: detected ? Math.min(transitions / 50, 1) : 0
    };
}

/**
 * Essayer de décoder le code-barres RÉEL
 */
function tryDecodeBarcode(imageData, pattern) {
    
    // 0) Native prioritaire
    tryNativeBarcode(imageData);

    // Méthode 1: Décodeur réel si disponible
    if (window.realBarcodeDecoder && pattern.confidence > 0.3) {
        
        const result = window.realBarcodeDecoder.decodeImage(imageData);
        if (result && result.code) {
            if (validateAndMaybeAccept(result.code, (result.format || '').toUpperCase(), 'Décodeur réel')) return;
        } else {
        }
    }
    
    // Méthode 2: Essayer avec Quagga si disponible (limité aux EAN)
    if (typeof Quagga !== 'undefined') {
        tryQuaggaDecode(imageData);
    }
    
    // Méthode 3: Fallback simulé désactivé par défaut (uniquement si autorisé explicitement)
    setTimeout(() => {
        if (window.ALLOW_SIMULATED_BARCODES === true && pattern.confidence > 0.95) {
            const simulatedCode = generateSimulatedBarcode(pattern);
            if (typeof handleScanResult === 'function') {
                handleScanResult(simulatedCode, 'Code-barres (SIMULÉ)');
            }
            stopSimpleBarcodeDetection();
        } else {
        }
    }, 250);
}

/**
 * Essayer de décoder avec Quagga sur image fixe
 */
function tryQuaggaDecode(imageData) {
    try {
        // Préparer un crop centré pour réduire les faux positifs
        const full = document.createElement('canvas');
        full.width = imageData.width;
        full.height = imageData.height;
        full.getContext('2d').putImageData(imageData, 0, 0);

        const crop = document.createElement('canvas');
        const cw = Math.floor(full.width * 0.6);
        const ch = Math.floor(full.height * 0.5);
        crop.width = cw;
        crop.height = ch;
        const sx = Math.floor((full.width - cw) / 2);
        const sy = Math.floor((full.height - ch) / 2);
        crop.getContext('2d').drawImage(full, sx, sy, cw, ch, 0, 0, cw, ch);
        
        Quagga.decodeSingle({
            decoder: {
                readers: ["ean_reader", "ean_8_reader"]
            }
            locate: true
            src: crop.toDataURL()
        }, function(result) {
            if (result && result.codeResult && result.codeResult.code) {
                const fmt = (result.codeResult.format || '').toUpperCase();
                const code = result.codeResult.code;
                if (!validateAndMaybeAccept(code, fmt, 'Quagga')) {
                }
            }
    } catch (error) {
    }
}

/**
 * Générer un code-barres simulé basé sur le motif détecté
 */
function generateSimulatedBarcode(pattern) {
    // Générer un code EAN-13 simulé basé sur les caractéristiques du motif
    const baseCode = '123456789';
    const confidence = Math.floor(pattern.confidence * 100);
    const transitions = pattern.transitions.toString().padStart(2, '0');
    
    return baseCode + confidence.toString().padStart(2, '0') + transitions;
}

/**
 * Test manuel du détecteur
 */
function testSimpleBarcodeDetector() {
    
    const video = document.getElementById('universal_scanner_video');
    if (!video) {
        return;
    }
    
    if (!simpleBarcodeCanvas) {
        initSimpleBarcodeDetector();
    }
    
    // Analyser la frame actuelle
    analyzeVideoFrame(video);
    
    console.log('✅ [SIMPLE-BARCODE] Test terminé');
}

/**
 * Diagnostic du détecteur simple
 */
function diagnosticSimpleBarcodeDetector() {
    
    const video = document.getElementById('universal_scanner_video');
    
    if (video && video.videoWidth > 0) {
        testSimpleBarcodeDetector();
    }
    
    console.log('🔍 [SIMPLE-BARCODE] === FIN DIAGNOSTIC ===');
}

// Exposition des fonctions globales
window.simpleBarcodeDetector = {
    init: initSimpleBarcodeDetector
    start: startSimpleBarcodeDetection
    stop: stopSimpleBarcodeDetection
    test: testSimpleBarcodeDetector
    diagnostic: diagnosticSimpleBarcodeDetector
};

// Auto-initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Écouter l'ouverture du modal scanner
    const scannerModal = document.getElementById('universal_scanner_modal');
    if (scannerModal) {
        scannerModal.addEventListener('shown.bs.modal', function() {
            
            setTimeout(() => {
                if (initSimpleBarcodeDetector()) {
                    // Démarrer la détection simple après 3 secondes
                    setTimeout(() => {
                        startSimpleBarcodeDetection();
                    }, 3000);
                }
            }, 1000);
        
        scannerModal.addEventListener('hidden.bs.modal', function() {
            stopSimpleBarcodeDetection();
    }

console.log('✅ [SIMPLE-BARCODE] Détecteur simple chargé');
