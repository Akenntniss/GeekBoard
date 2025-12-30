/**
 * Diagnostic spécialisé pour la détection des codes-barres
 */


window.barcodeDiagnostic = {
    // Test de détection avec différentes bibliothèques
    testDetection: function (imageData) {

        const results = {
            quagga: false,
            zxing: false,
            html5qrcode: false,
            manual: false,
        };

        // Test Quagga
        if (typeof Quagga !== 'undefined') {
            try {
                // Quagga utilise déjà le stream vidéo, pas besoin de test séparé
                results.quagga = 'active';
            } catch (error) {
            }
        }

        // Test ZXing
        if (typeof ZXing !== 'undefined' && ZXing.BrowserMultiFormatReader) {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = imageData.width;
                canvas.height = imageData.height;
                ctx.putImageData(imageData, 0, 0);

                canvas.toBlob(blob => {
                    if (blob) {
                        const codeReader = new ZXing.BrowserMultiFormatReader();
                        codeReader.decodeFromFile(blob)
                            .then(result => {
                                results.zxing = result.text;
                            })
                            .catch(err => {
                                results.zxing = false;
                            }
                }, 'image/png');
            } catch (error) {
            }
        }

        // Test Html5Qrcode
        if (typeof Html5Qrcode !== 'undefined') {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = imageData.width;
                canvas.height = imageData.height;
                ctx.putImageData(imageData, 0, 0);

                canvas.toBlob(blob => {
                    if (blob) {
                        const file = new File([blob], 'test.png', { type: 'image/png' });
                        let tempDiv = document.getElementById('diagnostic-reader');
                        if (!tempDiv) {
                            tempDiv = document.createElement('div');
                            tempDiv.id = 'diagnostic-reader';
                            tempDiv.style.display = 'none';
                            document.body.appendChild(tempDiv);
                        }

                        const html5QrCode = new Html5Qrcode('diagnostic-reader');
                        html5QrCode.scanFile(file, true)
                            .then(decodedText => {
                                results.html5qrcode = decodedText;
                            })
                            .catch(err => {
                                results.html5qrcode = false;
                            }
                }, 'image/png');
            } catch (error) {
            }
        }

        // Test manuel (analyse de pixels)
        results.manual = this.manualBarcodeAnalysis(imageData);

        return results;
    }

    // Analyse manuelle des pixels pour détecter des motifs de codes-barres
    manualBarcodeAnalysis: function (imageData) {

        const data = imageData.data;
        const width = imageData.width;
        const height = imageData.height;

        // Convertir en niveaux de gris
        const grayData = new Uint8Array(width * height);
        for (let i = 0; i < data.length; i += 4) {
            const gray = Math.round(0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2]);
            grayData[i / 4] = gray;
        }

        // Chercher des motifs de barres verticales
        let verticalBars = 0;
        let horizontalBars = 0;

        // Analyse verticale (codes-barres traditionnels)
        for (let x = 0; x < width - 1; x++) {
            let transitions = 0;
            for (let y = 0; y < height - 1; y++) {
                const current = grayData[y * width + x];
                const next = grayData[(y + 1) * width + x];
                if (Math.abs(current - next) > 50) {
                    transitions++;
                }
            }
            if (transitions > height * 0.1) {
                verticalBars++;
            }
        }

        // Analyse horizontale (codes-barres rotationnés)
        for (let y = 0; y < height - 1; y++) {
            let transitions = 0;
            for (let x = 0; x < width - 1; x++) {
                const current = grayData[y * width + x];
                const next = grayData[y * width + (x + 1)];
                if (Math.abs(current - next) > 50) {
                    transitions++;
                }
            }
            if (transitions > width * 0.1) {
                horizontalBars++;
            }
        }

        const result = {
            verticalBars,
            horizontalBars,
            likelyBarcode: verticalBars > width * 0.3 || horizontalBars > height * 0.3
        };

        return result;
    }

    // Diagnostic complet de l'état du scanner
    fullDiagnostic: function () {

        const diagnostic = {
            libraries: {
                quagga: typeof Quagga !== 'undefined',
                zxing: typeof ZXing !== 'undefined',
                html5qrcode: typeof Html5Qrcode !== 'undefined'
            camera: {
                permission: null,
                stream: null
            modal: {
                visible: false,
                elements: {}
            }
        };

        // Vérifier les permissions caméra
        if (navigator.permissions) {
            navigator.permissions.query({ name: 'camera' }).then(result => {
                diagnostic.camera.permission = result.state;
            });
        }

        // Vérifier le modal scanner
        const modal = document.getElementById('universal_scanner_modal');
        if (modal) {
            diagnostic.modal.visible = modal.classList.contains('show');
            diagnostic.modal.elements = {
                video: !!modal.querySelector('video'),
                canvas: !!modal.querySelector('canvas'),
                status: !!modal.querySelector('#scanner_status'),
            };
        }

        return diagnostic;
    }
};

// Exposer pour la console
window.testBarcodeDetection = window.barcodeDiagnostic.testDetection.bind(window.barcodeDiagnostic);
window.fullBarcodeDiagnostic = window.barcodeDiagnostic.fullDiagnostic.bind(window.barcodeDiagnostic);

