/**
 * DÉCODEUR OCR ULTRA-SIMPLE
 * Se concentre uniquement sur la lecture des chiffres imprimés sous le code-barres
 */

console.log('📖 [SIMPLE-OCR] Initialisation du décodeur OCR ultra-simple...');

/**
 * Décodeur OCR principal - lit les chiffres sous le code-barres
 */
function simpleOCRDecoder(imageData) {
    console.log('📖 [SIMPLE-OCR] Démarrage décodage OCR simple...');
    
    const data = imageData.data;
    const width = imageData.width;
    const height = imageData.height;
    
    // Chercher dans la partie inférieure de l'image (où sont les chiffres)
    const searchStartY = Math.floor(height * 0.7);  // 70% vers le bas
    const searchEndY = Math.floor(height * 0.95);   // 95% vers le bas
    
    console.log(`📖 [SIMPLE-OCR] Recherche de texte entre Y=${searchStartY} et Y=${searchEndY}`);
    
    let bestCode = null;
    let bestScore = 0;
    
    // Analyser plusieurs lignes horizontales
    for (let y = searchStartY; y < searchEndY; y += 3) {
        const lineResult = analyzeTextLine(data, width, height, y);
        
        if (lineResult && lineResult.score > bestScore) {
            bestScore = lineResult.score;
            bestCode = lineResult.code;
            console.log(`📖 [SIMPLE-OCR] Nouveau meilleur code trouvé: ${bestCode} (score: ${bestScore})`);
        }
    }
    
    if (bestCode && bestCode.length >= 8) {
        console.log(`✅ [SIMPLE-OCR] Code final détecté: ${bestCode}`);
        return {
            code: bestCode,
            format: 'EAN-13',
            method: 'OCR Simple',
            confidence: bestScore
        };
    }
    
    console.log('❌ [SIMPLE-OCR] Aucun code valide trouvé');
    return null;
}

/**
 * Analyser une ligne horizontale pour détecter du texte
 */
function analyzeTextLine(data, width, height, y) {
    const startX = Math.floor(width * 0.1);
    const endX = Math.floor(width * 0.9);
    
    // Convertir la ligne en niveaux de gris
    let grayLine = [];
    for (let x = startX; x < endX; x++) {
        const pixelIndex = (y * width + x) * 4;
        const r = data[pixelIndex];
        const g = data[pixelIndex + 1];
        const b = data[pixelIndex + 2];
        const gray = Math.round((r + g + b) / 3);
        grayLine.push(gray);
    }
    
    // Calculer le seuil de binarisation
    const threshold = calculateThreshold(grayLine);
    
    // Convertir en binaire (0 = blanc, 1 = noir)
    let binaryLine = grayLine.map(gray => gray < threshold ? 1 : 0);
    
    // Nettoyer le bruit
    binaryLine = cleanNoise(binaryLine);
    
    // Segmenter en caractères potentiels
    const segments = segmentDigits(binaryLine);
    
    if (segments.length === 0) {
        return null;
    }
    
    // Reconnaître chaque segment comme un chiffre
    let recognizedCode = '';
    let totalConfidence = 0;
    
    for (let segment of segments) {
        const digitResult = recognizeSimpleDigit(segment);
        if (digitResult) {
            recognizedCode += digitResult.digit;
            totalConfidence += digitResult.confidence;
        }
    }
    
    // Calculer le score final
    const avgConfidence = segments.length > 0 ? totalConfidence / segments.length : 0;
    const lengthScore = recognizedCode.length >= 8 ? 1.0 : recognizedCode.length / 8;
    const finalScore = avgConfidence * lengthScore;
    
    console.log(`📖 [SIMPLE-OCR] Ligne Y=${y}: "${recognizedCode}" (${segments.length} segments, score: ${finalScore.toFixed(2)})`);
    
    return recognizedCode.length >= 4 ? {
        code: recognizedCode,
        score: finalScore
    } : null;
}

/**
 * Calculer le seuil de binarisation optimal
 */
function calculateThreshold(grayLine) {
    // Méthode simple: moyenne entre min et max
    const minGray = Math.min(...grayLine);
    const maxGray = Math.max(...grayLine);
    const threshold = (minGray + maxGray) / 2;
    
    console.log(`📖 [SIMPLE-OCR] Seuil calculé: ${threshold} (min: ${minGray}, max: ${maxGray})`);
    return threshold;
}

/**
 * Nettoyer le bruit dans la ligne binaire
 */
function cleanNoise(binaryLine) {
    let cleaned = [...binaryLine];
    
    // Éliminer les pixels isolés
    for (let i = 1; i < cleaned.length - 1; i++) {
        if (cleaned[i] !== cleaned[i-1] && cleaned[i] !== cleaned[i+1]) {
            cleaned[i] = cleaned[i-1]; // Remplacer par le pixel précédent
        }
    }
    
    return cleaned;
}

/**
 * Segmenter la ligne en chiffres potentiels
 */
function segmentDigits(binaryLine) {
    const segments = [];
    let currentSegment = [];
    let inDigit = false;
    let whiteSpaceCount = 0;
    
    for (let i = 0; i < binaryLine.length; i++) {
        const pixel = binaryLine[i];
        
        if (pixel === 1) { // Pixel noir
            if (!inDigit) {
                inDigit = true;
                whiteSpaceCount = 0;
            }
            currentSegment.push(pixel);
        } else { // Pixel blanc
            if (inDigit) {
                whiteSpaceCount++;
                
                // Si trop d'espaces blancs, considérer que le chiffre est fini
                if (whiteSpaceCount > 8) {
                    if (currentSegment.length > 10) { // Segment assez large pour être un chiffre
                        segments.push([...currentSegment]);
                    }
                    currentSegment = [];
                    inDigit = false;
                    whiteSpaceCount = 0;
                } else {
                    currentSegment.push(pixel);
                }
            }
        }
    }
    
    // Ajouter le dernier segment
    if (currentSegment.length > 10) {
        segments.push(currentSegment);
    }
    
    console.log(`📖 [SIMPLE-OCR] ${segments.length} segments trouvés`);
    return segments;
}

/**
 * Reconnaître un chiffre simple depuis un segment binaire
 */
function recognizeSimpleDigit(segment) {
    if (segment.length < 10) {
        return null;
    }
    
    // Calculer des caractéristiques simples
    const totalPixels = segment.length;
    const blackPixels = segment.filter(p => p === 1).length;
    const density = blackPixels / totalPixels;
    
    // Analyser la distribution verticale (approximative)
    const topHalf = segment.slice(0, Math.floor(segment.length / 2));
    const bottomHalf = segment.slice(Math.floor(segment.length / 2));
    
    const topDensity = topHalf.filter(p => p === 1).length / topHalf.length;
    const bottomDensity = bottomHalf.filter(p => p === 1).length / bottomHalf.length;
    
    // Analyser les transitions (changements noir/blanc)
    let transitions = 0;
    for (let i = 1; i < segment.length; i++) {
        if (segment[i] !== segment[i-1]) {
            transitions++;
        }
    }
    
    const transitionRatio = transitions / segment.length;
    
    console.log(`📖 [SIMPLE-OCR] Segment: densité=${density.toFixed(2)}, top=${topDensity.toFixed(2)}, bottom=${bottomDensity.toFixed(2)}, transitions=${transitionRatio.toFixed(2)}`);
    
    // Règles de reconnaissance simples basées sur les caractéristiques
    let digit = null;
    let confidence = 0;
    
    if (density < 0.3) {
        // Chiffres fins: 1, 7
        if (transitionRatio < 0.1) {
            digit = '1';
            confidence = 0.8;
        } else {
            digit = '7';
            confidence = 0.7;
        }
    } else if (density > 0.7) {
        // Chiffres denses: 8, 6, 9
        if (topDensity > bottomDensity) {
            digit = '9';
            confidence = 0.7;
        } else if (bottomDensity > topDensity) {
            digit = '6';
            confidence = 0.7;
        } else {
            digit = '8';
            confidence = 0.8;
        }
    } else {
        // Chiffres moyens: 0, 2, 3, 4, 5
        if (topDensity < 0.3 && bottomDensity < 0.3) {
            digit = '0';
            confidence = 0.8;
        } else if (topDensity > bottomDensity + 0.2) {
            digit = '2';
            confidence = 0.6;
        } else if (bottomDensity > topDensity + 0.2) {
            digit = '3';
            confidence = 0.6;
        } else if (transitionRatio > 0.3) {
            digit = '4';
            confidence = 0.6;
        } else {
            digit = '5';
            confidence = 0.5;
        }
    }
    
    console.log(`📖 [SIMPLE-OCR] Chiffre reconnu: ${digit} (confiance: ${confidence})`);
    
    return {
        digit: digit,
        confidence: confidence
    };
}

/**
 * Interface principale pour décoder depuis une vidéo
 */
function decodeFromVideoSimpleOCR(video) {
    return new Promise((resolve, reject) => {
        try {
            if (!video || video.readyState !== video.HAVE_ENOUGH_DATA) {
                reject(new Error('Vidéo non prête'));
                return;
            }
            
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            
            // Utiliser une résolution plus élevée pour l'OCR
            canvas.width = Math.min(video.videoWidth, 1200);
            canvas.height = Math.min(video.videoHeight, 900);
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            
            const result = simpleOCRDecoder(imageData);
            
            if (result) {
                resolve(result);
            } else {
                reject(new Error('Aucun code-barres décodé par OCR'));
            }
            
        } catch (error) {
            reject(error);
        }
    });
}

// Exposition des fonctions globales
window.simpleOCRDecoder = {
    decode: decodeFromVideoSimpleOCR,
    decodeImage: simpleOCRDecoder,
    test: function() {
        const video = document.getElementById('universal_scanner_video');
        if (video) {
            return decodeFromVideoSimpleOCR(video);
        } else {
            return Promise.reject(new Error('Vidéo non trouvée'));
        }
    }
};

console.log('✅ [SIMPLE-OCR] Décodeur OCR ultra-simple chargé');
console.log('💡 [SIMPLE-OCR] Utilisez window.simpleOCRDecoder.test() pour tester');
