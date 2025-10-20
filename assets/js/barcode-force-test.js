/**
 * TEST FORCÉ CODES-BARRES
 * Script pour forcer la détection et voir ce qui se passe
 */

console.log('🧪 [BARCODE-FORCE-TEST] Script de test forcé chargé');

/**
 * Forcer un test de détection immédiat
 */
function forceTestBarcode() {
    console.log('🚀 [FORCE-TEST] Test forcé démarré');
    
    // Test 1: Simuler un code directement
    console.log('🧪 [FORCE-TEST] Test 1: Simulation directe');
    if (typeof handleScanResult === 'function') {
        handleScanResult('1234567890123', 'Test forcé EAN-13');
        return;
    }
    
    // Test 2: Essayer avec le détecteur simple
    console.log('🧪 [FORCE-TEST] Test 2: Détecteur simple');
    if (window.simpleBarcodeDetector) {
        window.simpleBarcodeDetector.test();
        return;
    }
    
    // Test 3: Alert direct
    console.log('🧪 [FORCE-TEST] Test 3: Alert direct');
    alert('Code-barres test: 1234567890123');
}

/**
 * Analyser l'état actuel du scanner
 */
function analyzeCurrentScannerState() {
    console.log('🔍 [FORCE-TEST] === ANALYSE ÉTAT SCANNER ===');
    
    const video = document.getElementById('universal_scanner_video');
    console.log('📹 Vidéo trouvée:', !!video);
    console.log('📹 Vidéo active:', video?.srcObject?.active);
    console.log('📹 Dimensions:', video?.videoWidth + 'x' + video?.videoHeight);
    console.log('📹 ReadyState:', video?.readyState);
    
    console.log('🔧 Quagga disponible:', typeof Quagga !== 'undefined');
    console.log('🔧 jsQR disponible:', typeof jsQR !== 'undefined');
    console.log('🔧 handleScanResult disponible:', typeof handleScanResult === 'function');
    
    console.log('🎯 Simple detector:', !!window.simpleBarcodeDetector);
    console.log('🎯 Barcode fix:', !!window.barcodeFix);
    console.log('🎯 Debug visual:', !!window.barcodeDebugVisual);
    
    // Tester la détection manuelle
    if (video && video.readyState === video.HAVE_ENOUGH_DATA) {
        console.log('🧪 [FORCE-TEST] Test de détection manuelle...');
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
        console.log('📊 [FORCE-TEST] Analyse manuelle:', analysis);
        
        if (analysis.hasPattern) {
            const code = `MANUAL${analysis.transitions.toString().padStart(3, '0')}${Math.round(analysis.brightness).toString().padStart(3, '0')}`;
            console.log('✅ [FORCE-TEST] Code généré manuellement:', code);
            
            if (typeof handleScanResult === 'function') {
                handleScanResult(code, 'Détection manuelle forcée');
            } else {
                alert(`Code détecté manuellement: ${code}`);
            }
        }
        
    } catch (error) {
        console.error('❌ [FORCE-TEST] Erreur test manuel:', error);
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
        transitions,
        brightness: avgBrightness,
        hasPattern,
        confidence: hasPattern ? Math.min(transitions / 50, 1) : 0
    };
}

/**
 * Test complet avec tous les systèmes
 */
function fullSystemTest() {
    console.log('🚀 [FORCE-TEST] === TEST COMPLET SYSTÈME ===');
    
    // 1. Analyser l'état
    analyzeCurrentScannerState();
    
    // 2. Tester les fonctions disponibles
    setTimeout(() => {
        console.log('🧪 [FORCE-TEST] Test des fonctions...');
        
        if (window.barcodeDebugVisual && !window.barcodeDebugVisual.isActive()) {
            console.log('🔍 [FORCE-TEST] Démarrage debug visuel...');
            window.barcodeDebugVisual.start();
        }
        
        if (window.simpleBarcodeDetector) {
            console.log('🎯 [FORCE-TEST] Test détecteur simple...');
            window.simpleBarcodeDetector.test();
        }
        
        if (window.barcodeFix) {
            console.log('🔧 [FORCE-TEST] Diagnostic barcode fix...');
            window.barcodeFix.diagnostic();
        }
    }, 1000);
    
    // 3. Test forcé après 3 secondes
    setTimeout(() => {
        console.log('🚀 [FORCE-TEST] Test forcé final...');
        forceTestBarcode();
    }, 3000);
    
    console.log('🚀 [FORCE-TEST] === TESTS PROGRAMMÉS ===');
}

// Exposition des fonctions globales
window.barcodeForceTest = {
    force: forceTestBarcode,
    analyze: analyzeCurrentScannerState,
    full: fullSystemTest
};

// Auto-test si le scanner est ouvert
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const scannerModal = document.getElementById('universal_scanner_modal');
        if (scannerModal && scannerModal.classList.contains('show')) {
            console.log('🚀 [FORCE-TEST] Scanner ouvert détecté, test automatique...');
            setTimeout(() => {
                fullSystemTest();
            }, 2000);
        }
    }, 1000);
});

console.log('✅ [BARCODE-FORCE-TEST] Script chargé');
console.log('💡 [BARCODE-FORCE-TEST] Utilisez window.barcodeForceTest.full() pour test complet');
