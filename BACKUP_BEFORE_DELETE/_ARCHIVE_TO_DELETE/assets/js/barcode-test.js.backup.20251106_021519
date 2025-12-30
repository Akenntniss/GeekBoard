/**
 * Script de test pour la détection de codes-barres
 */

console.log('🧪 [BARCODE-TEST] Script de test chargé');

window.barcodeTest = {
    // Générer une image de test avec un motif de code-barres
    generateTestBarcode: function() {
        console.log('🧪 [BARCODE-TEST] Génération d\'un code-barres de test...');
        
        const canvas = document.createElement('canvas');
        canvas.width = 400;
        canvas.height = 100;
        const ctx = canvas.getContext('2d');
        
        // Fond blanc
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, 400, 100);
        
        // Générer un motif de barres simple (EAN-13 simulé)
        ctx.fillStyle = 'black';
        
        // Motif de départ (101)
        let x = 50;
        ctx.fillRect(x, 20, 2, 60); x += 4;  // 1
        x += 2;                              // 0
        ctx.fillRect(x, 20, 2, 60); x += 4;  // 1
        
        // Quelques barres de données simulées
        for (let i = 0; i < 20; i++) {
            const barWidth = Math.random() > 0.5 ? 2 : 4;
            const spaceWidth = Math.random() > 0.5 ? 2 : 4;
            
            ctx.fillRect(x, 20, barWidth, 60);
            x += barWidth + spaceWidth;
        }
        
        // Motif central (01010)
        x += 4;                              // 0
        ctx.fillRect(x, 20, 2, 60); x += 4;  // 1
        x += 2;                              // 0
        ctx.fillRect(x, 20, 2, 60); x += 4;  // 1
        x += 2;                              // 0
        
        // Plus de barres de données
        for (let i = 0; i < 20; i++) {
            const barWidth = Math.random() > 0.5 ? 2 : 4;
            const spaceWidth = Math.random() > 0.5 ? 2 : 4;
            
            ctx.fillRect(x, 20, barWidth, 60);
            x += barWidth + spaceWidth;
        }
        
        // Motif de fin (101)
        ctx.fillRect(x, 20, 2, 60); x += 4;  // 1
        x += 2;                              // 0
        ctx.fillRect(x, 20, 2, 60);          // 1
        
        return canvas;
    },
    
    // Tester la détection avec l'image générée
    testDetection: function() {
        console.log('🧪 [BARCODE-TEST] Test de détection...');
        
        const canvas = this.generateTestBarcode();
        const ctx = canvas.getContext('2d');
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        
        // Afficher l'image de test
        document.body.appendChild(canvas);
        canvas.style.border = '2px solid red';
        canvas.style.margin = '10px';
        canvas.title = 'Image de test - Code-barres simulé';
        
        // Tester notre fonction de détection avancée
        if (typeof tryAdvancedBarcodeDetection === 'function') {
            const result = tryAdvancedBarcodeDetection(imageData);
            if (result) {
                console.log('✅ [BARCODE-TEST] Détection réussie:', result);
                alert('✅ Test réussi! Code détecté: ' + result);
            } else {
                console.log('❌ [BARCODE-TEST] Aucun code détecté');
                alert('❌ Test échoué - Aucun code détecté');
            }
        } else {
            console.log('⚠️ [BARCODE-TEST] Fonction tryAdvancedBarcodeDetection non disponible');
            alert('⚠️ Fonction de détection non disponible');
        }
        
        // Nettoyer après 5 secondes
        setTimeout(() => {
            if (canvas.parentNode) {
                canvas.parentNode.removeChild(canvas);
            }
        }, 5000);
    },
    
    // Tester avec un vrai scanner
    testWithScanner: function() {
        console.log('🧪 [BARCODE-TEST] Test avec le scanner...');
        
        if (typeof openUniversalScanner === 'function') {
            openUniversalScanner();
            console.log('📱 Scanner ouvert - Testez avec un vrai code-barres');
        } else {
            console.log('⚠️ Fonction openUniversalScanner non disponible');
        }
    },
    
    // Diagnostic complet
    fullTest: function() {
        console.log('🧪 [BARCODE-TEST] Test complet...');
        
        // 1. Test des bibliothèques
        console.log('📚 Bibliothèques disponibles:');
        console.log('  - Quagga:', typeof Quagga !== 'undefined');
        console.log('  - jsQR:', typeof jsQR !== 'undefined');
        console.log('  - ZXing:', typeof ZXing !== 'undefined');
        console.log('  - Html5Qrcode:', typeof Html5Qrcode !== 'undefined');
        
        // 2. Test de détection avancée
        this.testDetection();
        
        // 3. Test du diagnostic
        if (typeof fullBarcodeDiagnostic === 'function') {
            setTimeout(() => {
                fullBarcodeDiagnostic();
            }, 1000);
        }
    }
};

// Exposer pour la console
window.testBarcodeGeneration = window.barcodeTest.testDetection.bind(window.barcodeTest);
window.testFullBarcode = window.barcodeTest.fullTest.bind(window.barcodeTest);

console.log('✅ [BARCODE-TEST] Test prêt');
console.log('💡 [BARCODE-TEST] Utilisez window.testBarcodeGeneration() pour tester');
console.log('💡 [BARCODE-TEST] Utilisez window.testFullBarcode() pour un test complet');
