/**
 * Debug en temps réel pour la détection de codes-barres
 */

console.log('🔍 [BARCODE-DEBUG-REAL] Script de debug en temps réel chargé');

// Intercepter toutes les détections
let detectionLog = [];
let lastRealDetection = null;

// Intercepter handleBarcodeDetected
if (typeof window.handleBarcodeDetected === 'function') {
    const originalHandler = window.handleBarcodeDetected;
    window.handleBarcodeDetected = function(code) {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = {
            timestamp,
            code,
            source: 'unknown',
            stackTrace: new Error().stack
        };
        
        // Déterminer la source
        if (logEntry.stackTrace.includes('Quagga.onDetected')) {
            logEntry.source = 'Quagga';
        } else if (logEntry.stackTrace.includes('tryAdvancedBarcodeDetection')) {
            logEntry.source = 'Advanced';
        } else if (logEntry.stackTrace.includes('Html5Qrcode')) {
            logEntry.source = 'Html5Qrcode';
        } else if (logEntry.stackTrace.includes('scanBarcode')) {
            logEntry.source = 'Fallback';
        }
        
        detectionLog.push(logEntry);
        
        console.log(`🎯 [DETECTION] ${timestamp} - Source: ${logEntry.source} - Code: ${code}`);
        
        // Garder trace de la vraie détection (Quagga)
        if (logEntry.source === 'Quagga') {
            lastRealDetection = logEntry;
            console.log('✅ [REAL-DETECTION] Vraie détection Quagga:', code);
        } else {
            console.log('⚠️ [FAKE-DETECTION] Détection factice de', logEntry.source, ':', code);
            
            // Si on a une vraie détection récente (moins de 2 secondes), l'utiliser à la place
            if (lastRealDetection && (Date.now() - new Date(lastRealDetection.timestamp).getTime()) < 2000) {
                console.log('🔄 [CORRECTION] Utilisation de la vraie détection:', lastRealDetection.code);
                code = lastRealDetection.code;
            }
        }
        
        return originalHandler.call(this, code);
    };
    
    console.log('✅ [BARCODE-DEBUG-REAL] Intercepteur installé sur handleBarcodeDetected');
}

// Intercepter Quagga.onDetected pour capturer les vraies détections
if (typeof Quagga !== 'undefined') {
    const originalOnDetected = Quagga.onDetected;
    Quagga.onDetected = function(callback) {
        const wrappedCallback = function(result) {
            const code = result.codeResult.code.trim();
            const confidence = result.codeResult.confidence || 0;
            
            console.log('🎯 [QUAGGA-INTERCEPT] Vraie détection:', code, 'Confiance:', confidence);
            
            // Stocker la vraie détection
            lastRealDetection = {
                timestamp: new Date().toLocaleTimeString(),
                code: code,
                confidence: confidence,
                source: 'Quagga'
            };
            
            return callback(result);
        };
        
        return originalOnDetected.call(this, wrappedCallback);
    };
    
    console.log('✅ [BARCODE-DEBUG-REAL] Intercepteur Quagga installé');
}

// Fonctions de debug
window.barcodeDebugReal = {
    getLog: function() {
        return detectionLog;
    },
    
    getLastReal: function() {
        return lastRealDetection;
    },
    
    clearLog: function() {
        detectionLog = [];
        lastRealDetection = null;
        console.log('🧹 [BARCODE-DEBUG-REAL] Log nettoyé');
    },
    
    showStats: function() {
        console.log('📊 [BARCODE-DEBUG-REAL] Statistiques:');
        console.log('Total détections:', detectionLog.length);
        
        const sources = {};
        detectionLog.forEach(entry => {
            sources[entry.source] = (sources[entry.source] || 0) + 1;
        });
        
        console.log('Par source:', sources);
        console.log('Dernière vraie détection:', lastRealDetection);
        
        return {
            total: detectionLog.length,
            sources: sources,
            lastReal: lastRealDetection,
            log: detectionLog
        };
    }
};

// Exposer pour la console
window.showBarcodeStats = window.barcodeDebugReal.showStats.bind(window.barcodeDebugReal);
window.clearBarcodeLog = window.barcodeDebugReal.clearLog.bind(window.barcodeDebugReal);

console.log('✅ [BARCODE-DEBUG-REAL] Debug prêt');
console.log('💡 [BARCODE-DEBUG-REAL] Utilisez showBarcodeStats() pour voir les statistiques');
console.log('💡 [BARCODE-DEBUG-REAL] Utilisez clearBarcodeLog() pour nettoyer le log');
