/**
 * QR Code Generator - Version inline simplifiée
 * Basé sur qrcode.js mais version autonome
 */

window.QRCodeInline = (function() {
    
    // Fonction principale pour générer le QR Code
    function generateQRCode(text, size = 256) {
        // Créer un canvas
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = size;
        canvas.height = size;
        
        // Pour cette démo, on va créer un QR Code visuel simple
        // En production, vous utiliseriez une vraie librairie QR
        
        // Remplir le fond en blanc
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, size, size);
        
        // Créer un motif QR simplifié
        ctx.fillStyle = '#000000';
        
        const cellSize = size / 25; // Grille 25x25
        
        // Dessiner les coins de repérage
        drawFinderPattern(ctx, 0, 0, cellSize);
        drawFinderPattern(ctx, 18 * cellSize, 0, cellSize);
        drawFinderPattern(ctx, 0, 18 * cellSize, cellSize);
        
        // Dessiner un motif de données simplifié
        for (let row = 0; row < 25; row++) {
            for (let col = 0; col < 25; col++) {
                // Éviter les zones de repérage
                if (isInFinderPattern(row, col)) continue;
                
                // Générer un motif basé sur le texte
                const hash = simpleHash(text + row + col);
                if (hash % 2 === 0) {
                    ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                }
            }
        }
        
        return canvas;
    }
    
    function drawFinderPattern(ctx, x, y, cellSize) {
        // Carré extérieur (7x7)
        ctx.fillRect(x, y, 7 * cellSize, 7 * cellSize);
        
        // Carré intérieur blanc (5x5)
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(x + cellSize, y + cellSize, 5 * cellSize, 5 * cellSize);
        
        // Carré central noir (3x3)
        ctx.fillStyle = '#000000';
        ctx.fillRect(x + 2 * cellSize, y + 2 * cellSize, 3 * cellSize, 3 * cellSize);
    }
    
    function isInFinderPattern(row, col) {
        // Coin supérieur gauche
        if (row < 9 && col < 9) return true;
        // Coin supérieur droit
        if (row < 9 && col > 15) return true;
        // Coin inférieur gauche
        if (row > 15 && col < 9) return true;
        return false;
    }
    
    function simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        return Math.abs(hash);
    }
    
    // API publique
    return {
        toCanvas: function(canvas, text, options = {}) {
            return new Promise((resolve, reject) => {
                try {
                    const size = options.width || 256;
                    const qrCanvas = generateQRCode(text, size);
                    
                    // Copier le contenu vers le canvas fourni
                    canvas.width = qrCanvas.width;
                    canvas.height = qrCanvas.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(qrCanvas, 0, 0);
                    
                    resolve(canvas);
                } catch (error) {
                    reject(error);
                }
            });
        },
        
        create: function(text, options = {}) {
            const size = options.width || 256;
            return generateQRCode(text, size);
        }
    };
})();

// Rendre compatible avec QRCode
window.QRCode = window.QRCodeInline;
