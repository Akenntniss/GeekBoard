/**
 * QR Code Wrapper - Version compatible avec Canvas API
 * Utilise qrcode.min.js avec une interface compatible
 */

window.QRCodeWrapper = (function() {
    
    // Vérifier si QRCode est disponible
    if (typeof QRCode === 'undefined') {
        console.error('QRCode library not loaded');
        return null;
    }
    
    return {
        toCanvas: function(canvas, text, options = {}) {
            return new Promise((resolve, reject) => {
                try {
                    // Créer un div temporaire pour QRCode.js
                    const tempDiv = document.createElement('div');
                    tempDiv.style.display = 'none';
                    document.body.appendChild(tempDiv);
                    
                    const size = options.width || 256;
                    
                    // Générer le QR Code
                    const qr = new QRCode(tempDiv, {
                        text: text,
                        width: size,
                        height: size,
                        colorDark: options.color?.dark || '#000000',
                        colorLight: options.color?.light || '#FFFFFF',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                    
                    // Attendre que l'image soit générée
                    setTimeout(() => {
                        try {
                            const img = tempDiv.querySelector('img');
                            if (img && img.complete) {
                                // Copier vers le canvas
                                canvas.width = size;
                                canvas.height = size;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0, size, size);
                                
                                // Nettoyer
                                document.body.removeChild(tempDiv);
                                resolve(canvas);
                            } else {
                                throw new Error('QR Code generation failed');
                            }
                        } catch (error) {
                            document.body.removeChild(tempDiv);
                            reject(error);
                        }
                    }, 100);
                    
                } catch (error) {
                    reject(error);
                }
            });
        }
    };
})();

// Override QRCode pour compatibilité
if (window.QRCodeWrapper) {
    window.QRCode = window.QRCodeWrapper;
}
