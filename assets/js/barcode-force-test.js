/**
 * TEST FORCÉ CODES-BARRES
 * Script pour forcer la détection et voir ce qui se passe
 */

console.log('🧪 [BARCODE-FORCE-TEST] Script de test forcé chargé');

/**
 * Forcer un test de détection immédiat
 */
function forceTestBarcode() {
    
    // Test 1: Simuler un code directement
    if (typeof handleScanResult === 'function') {
        handleScanResult('1234567890123', 'Test forcé EAN-13');
        return;
    }
    
    // Test 2: Essayer avec le détecteur simple
    if (window.simpleBarcodeDetector) {
        window.simpleBarcodeDetector.test();
        return;
    }
    
    // Test 3: Alert direct
    alert('Code-barres test: 1234567890123');
}

/**
 * Analyser l'état actuel du scanner
 */
function analyzeCurrentScannerState() {
    
    const video = document.getElementById('universal_scanner_video');
    
    console.log('🔧 Quagga disponible:', typeof Quagga !== 'undefined');
    
    console.log('🎯 Simple detector:', !!window.simpleBarcodeDetector);
    
    // Tester la détection manuelle
    if (video && video.readyState === video.HAVE_ENOUGH_DATA) {
        testManualDetection(video);
    }
    
    console.log('🔍 [FORCE-TEST] === FIN ANALYSE ===');
}

/**
 * Test de détection manuelle
 */
function testManualDetection(video) {
    try {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        
        canvas.width = Math.min(video.videoWidth, 640);
        canvas.height = Math.min(video.videoHeight, 480);
        
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        
        // Analyser l'image
        const analysis = analyzeImageForBarcode(imageData);
        
        if (analysis.hasPattern) {
            const code = `MANUAL${analysis.transitions.toString().padStart(3, '0')}${Math.round(analysis.brightness).toString().padStart(3, '0')}`;
            
            if (typeof handleScanResult === 'function') {
                handleScanResult(code, 'Détection manuelle forcée');
            } else {
                alert(`Code détecté manuellement: ${code}`);
            }
        }
        
    } catch (error) {
    }
}

/**
 * Analyser une image pour détecter un motif de code-barres
 */
function analyzeImageForBarcode(imageData) {
    const data = imageData.data;
    const width = imageData.width;
    const height = imageData.height;
    
    const centerY = Math.floor(height / 2);
    const startX = Math.floor(width * 0.1);
    const endX = Math.floor(width * 0.9);
    
    let transitions = 0;
    let lastPixelDark = false;
    let totalBrightness = 0;
    let pixelCount = 0;
    
    for (let x = startX; x < endX; x++) {
        const pixelIndex = (centerY * width + x) * 4;
        const r = data[pixelIndex];
        const g = data[pixelIndex + 1];
        const b = data[pixelIndex + 2];
        
        const brightness = (r + g + b) / 3;
        totalBrightness += brightness;
        pixelCount++;
        
        const isDark = brightness < 128;
        
        if (isDark !== lastPixelDark) {
            transitions++;
            lastPixelDark = isDark;
        }
    }
    
    const avgBrightness = totalBrightness / pixelCount;
    const hasPattern = transitions >= 15 && transitions <= 80;
    
    return {
        transitions
        brightness: avgBrightness
        hasPattern
        confidence: hasPattern ? Math.min(transitions / 50, 1) : 0
    };
}

/**
 * Test complet avec tous les systèmes
 */
function fullSystemTest() {
    
    // 1. Analyser l'état
    analyzeCurrentScannerState();
    
    // 2. Tester les fonctions disponibles
    setTimeout(() => {
        
        if (window.barcodeDebugVisual && !window.barcodeDebugVisual.isActive()) {
            window.barcodeDebugVisual.start();
        }
        
        if (window.simpleBarcodeDetector) {
            window.simpleBarcodeDetector.test();
        }
        
        if (window.barcodeFix) {
            window.barcodeFix.diagnostic();
        }
    }, 1000);
    
    // 3. Test forcé après 3 secondes
    setTimeout(() => {
        forceTestBarcode();
    }, 3000);
    
    console.log('🚀 [FORCE-TEST] === TESTS PROGRAMMÉS ===');
}

// Exposition des fonctions globales
window.barcodeForceTest = {
    force: forceTestBarcode
    analyze: analyzeCurrentScannerState
    full: fullSystemTest
};

// Auto-test si le scanner est ouvert
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const scannerModal = document.getElementById('universal_scanner_modal');
        if (scannerModal && scannerModal.classList.contains('show')) {
            setTimeout(() => {
                fullSystemTest();
            }, 2000);
        }
    }, 1000);

console.log('✅ [BARCODE-FORCE-TEST] Script chargé');
