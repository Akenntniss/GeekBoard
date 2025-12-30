/**
 * DÉCODEUR RÉEL DE CODES-BARRES
 * Analyse les barres pour extraire les vrais chiffres
 */

console.log('🔍 [REAL-DECODER] Initialisation du décodeur réel...');

// Tables de décodage EAN-13
const EAN_PATTERNS = {
    // Motifs pour les chiffres de gauche (groupe A et B)
    LEFT_A: [
        '0001101', // 0
        '0011001', // 1
        '0010011', // 2
        '0111101', // 3
        '0100011', // 4
        '0110001', // 5
        '0101111', // 6
        '0111011', // 7
        '0110111', // 8
        '0001011'  // 9
    ],
    LEFT_B: [
        '0100111', // 0
        '0110011', // 1
        '0011011', // 2
        '0100001', // 3
        '0011101', // 4
        '0111001', // 5
        '0000101', // 6
        '0010001', // 7
        '0001001', // 8
        '0010111'  // 9
    ],
    // Motifs pour les chiffres de droite
    RIGHT: [
        '1110010', // 0
        '1100110', // 1
        '1101100', // 2
        '1000010', // 3
        '1011100', // 4
        '1001110', // 5
        '1010000', // 6
        '1000100', // 7
        '1001000', // 8
        '1110100'  // 9
    ]
};

// Motifs pour déterminer le premier chiffre (groupe A/B)
const FIRST_DIGIT_PATTERNS = [
    'AAAAAA', // 0
    'AABABB', // 1
    'AABBAB', // 2
    'AABBBA', // 3
    'ABAABB', // 4
    'ABBAAB', // 5
    'ABBBAA', // 6
    'ABABAB', // 7
    'ABABBA', // 8
    'ABBABA'  // 9
];

/**
 * Décoder un code-barres EAN depuis une image
 */
function decodeRealBarcode(imageData) {
    console.log('🔍 [REAL-DECODER] Décodage réel démarré...');
    
    const data = imageData.data;
    const width = imageData.width;
    const height = imageData.height;
    
    // Analyser plusieurs lignes pour trouver le meilleur signal
    const lines = [
        Math.floor(height * 0.4),
        Math.floor(height * 0.5),
        Math.floor(height * 0.6)
    ];
    
    for (let lineY of lines) {
        console.log(`🔍 [REAL-DECODER] Analyse ligne ${lineY}...`);
        
        const binaryString = extractBinaryFromLine(data, width, height, lineY);
        if (binaryString) {
            console.log('📊 [REAL-DECODER] Signal binaire:', binaryString.substring(0, 50) + '...');
            
            const decodedCode = decodeEAN13(binaryString);
            if (decodedCode) {
                console.log('✅ [REAL-DECODER] Code décodé:', decodedCode);
                return {
                    code: decodedCode,
                    format: 'EAN-13',
                    confidence: 0.9
                };
            }
        }
    }
    
    console.log('❌ [REAL-DECODER] Aucun code valide trouvé');
    return null;
}

/**
 * Extraire le signal binaire d'une ligne
 */
function extractBinaryFromLine(data, width, height, y) {
    const startX = Math.floor(width * 0.1);
    const endX = Math.floor(width * 0.9);
    
    // Convertir en niveaux de gris et créer un signal binaire
    let grayValues = [];
    for (let x = startX; x < endX; x++) {
        const pixelIndex = (y * width + x) * 4;
        const r = data[pixelIndex];
        const g = data[pixelIndex + 1];
        const b = data[pixelIndex + 2];
        const gray = (r + g + b) / 3;
        grayValues.push(gray);
    }
    
    // Trouver le seuil optimal
    const threshold = findOptimalThreshold(grayValues);
    
    // Convertir en binaire
    let binaryString = '';
    for (let gray of grayValues) {
        binaryString += gray < threshold ? '1' : '0'; // 1 = noir, 0 = blanc
    }
    
    // Nettoyer le signal (éliminer le bruit)
    binaryString = cleanBinarySignal(binaryString);
    
    return binaryString;
}

/**
 * Trouver le seuil optimal pour la binarisation
 */
function findOptimalThreshold(grayValues) {
    // Méthode d'Otsu simplifiée
    const histogram = new Array(256).fill(0);
    
    // Créer l'histogramme
    for (let gray of grayValues) {
        histogram[Math.floor(gray)]++;
    }
    
    // Trouver le seuil qui maximise la variance inter-classes
    let bestThreshold = 128;
    let maxVariance = 0;
    
    for (let t = 50; t < 200; t++) {
        let w1 = 0, w2 = 0, sum1 = 0, sum2 = 0;
        
        for (let i = 0; i < t; i++) {
            w1 += histogram[i];
            sum1 += i * histogram[i];
        }
        
        for (let i = t; i < 256; i++) {
            w2 += histogram[i];
            sum2 += i * histogram[i];
        }
        
        if (w1 > 0 && w2 > 0) {
            const mean1 = sum1 / w1;
            const mean2 = sum2 / w2;
            const variance = w1 * w2 * Math.pow(mean1 - mean2, 2);
            
            if (variance > maxVariance) {
                maxVariance = variance;
                bestThreshold = t;
            }
        }
    }
    
    console.log('🎯 [REAL-DECODER] Seuil optimal:', bestThreshold);
    return bestThreshold;
}

/**
 * Nettoyer le signal binaire
 */
function cleanBinarySignal(binaryString) {
    // Éliminer les transitions trop courtes (bruit)
    let cleaned = '';
    let currentChar = binaryString[0];
    let count = 1;
    
    for (let i = 1; i < binaryString.length; i++) {
        if (binaryString[i] === currentChar) {
            count++;
        } else {
            // Si la séquence est trop courte, la considérer comme du bruit
            if (count >= 2) {
                cleaned += currentChar.repeat(count);
            } else {
                // Remplacer par le caractère précédent
                cleaned += cleaned[cleaned.length - 1] || currentChar;
            }
            currentChar = binaryString[i];
            count = 1;
        }
    }
    
    // Ajouter la dernière séquence
    if (count >= 2) {
        cleaned += currentChar.repeat(count);
    }
    
    return cleaned;
}

/**
 * Décoder un code EAN-13 depuis un signal binaire
 */
function decodeEAN13(binaryString) {
    console.log('🔍 [REAL-DECODER] Recherche des marqueurs EAN-13...');
    
    // Chercher les marqueurs de début (101)
    const startPattern = '101';
    const centerPattern = '01010';
    const endPattern = '101';
    
    let startIndex = binaryString.indexOf(startPattern);
    if (startIndex === -1) {
        console.log('❌ [REAL-DECODER] Marqueur de début non trouvé');
        return null;
    }
    
    // Chercher le marqueur central
    let centerIndex = binaryString.indexOf(centerPattern, startIndex + 3);
    if (centerIndex === -1) {
        console.log('❌ [REAL-DECODER] Marqueur central non trouvé');
        return null;
    }
    
    // Chercher le marqueur de fin
    let endIndex = binaryString.indexOf(endPattern, centerIndex + 5);
    if (endIndex === -1) {
        console.log('❌ [REAL-DECODER] Marqueur de fin non trouvé');
        return null;
    }
    
    console.log('✅ [REAL-DECODER] Marqueurs trouvés:', { startIndex, centerIndex, endIndex });
    
    // Extraire les données
    const leftData = binaryString.substring(startIndex + 3, centerIndex);
    const rightData = binaryString.substring(centerIndex + 5, endIndex);
    
    console.log('📊 [REAL-DECODER] Données gauche:', leftData);
    console.log('📊 [REAL-DECODER] Données droite:', rightData);
    
    // Décoder les chiffres
    const leftDigits = decodeLeftDigits(leftData);
    const rightDigits = decodeRightDigits(rightData);
    
    if (!leftDigits || !rightDigits) {
        console.log('❌ [REAL-DECODER] Échec du décodage des chiffres');
        return null;
    }
    
    // Déterminer le premier chiffre
    const firstDigit = determineFirstDigit(leftDigits.pattern);
    
    if (firstDigit === -1) {
        console.log('❌ [REAL-DECODER] Premier chiffre non déterminable');
        return null;
    }
    
    const fullCode = firstDigit + leftDigits.digits + rightDigits;
    
    // Vérifier la somme de contrôle
    if (validateEAN13Checksum(fullCode)) {
        console.log('✅ [REAL-DECODER] Code valide avec somme de contrôle correcte');
        return fullCode;
    } else {
        console.log('❌ [REAL-DECODER] Somme de contrôle incorrecte');
        return null;
    }
}

/**
 * Décoder les chiffres de gauche
 */
function decodeLeftDigits(leftData) {
    if (leftData.length !== 42) { // 6 chiffres × 7 bits
        console.log('❌ [REAL-DECODER] Longueur incorrecte pour les données de gauche:', leftData.length);
        return null;
    }
    
    let digits = '';
    let pattern = '';
    
    for (let i = 0; i < 6; i++) {
        const digitBits = leftData.substring(i * 7, (i + 1) * 7);
        const result = decodeLeftDigit(digitBits);
        
        if (result === null) {
            console.log(`❌ [REAL-DECODER] Échec décodage chiffre gauche ${i + 1}:`, digitBits);
            return null;
        }
        
        digits += result.digit;
        pattern += result.group;
    }
    
    console.log('✅ [REAL-DECODER] Chiffres gauche:', digits, 'Motif:', pattern);
    return { digits, pattern };
}

/**
 * Décoder un chiffre de gauche
 */
function decodeLeftDigit(bits) {
    // Essayer groupe A
    for (let i = 0; i < EAN_PATTERNS.LEFT_A.length; i++) {
        if (bits === EAN_PATTERNS.LEFT_A[i]) {
            return { digit: i.toString(), group: 'A' };
        }
    }
    
    // Essayer groupe B
    for (let i = 0; i < EAN_PATTERNS.LEFT_B.length; i++) {
        if (bits === EAN_PATTERNS.LEFT_B[i]) {
            return { digit: i.toString(), group: 'B' };
        }
    }
    
    return null;
}

/**
 * Décoder les chiffres de droite
 */
function decodeRightDigits(rightData) {
    if (rightData.length !== 42) { // 6 chiffres × 7 bits
        console.log('❌ [REAL-DECODER] Longueur incorrecte pour les données de droite:', rightData.length);
        return null;
    }
    
    let digits = '';
    
    for (let i = 0; i < 6; i++) {
        const digitBits = rightData.substring(i * 7, (i + 1) * 7);
        const digit = decodeRightDigit(digitBits);
        
        if (digit === null) {
            console.log(`❌ [REAL-DECODER] Échec décodage chiffre droite ${i + 1}:`, digitBits);
            return null;
        }
        
        digits += digit;
    }
    
    console.log('✅ [REAL-DECODER] Chiffres droite:', digits);
    return digits;
}

/**
 * Décoder un chiffre de droite
 */
function decodeRightDigit(bits) {
    for (let i = 0; i < EAN_PATTERNS.RIGHT.length; i++) {
        if (bits === EAN_PATTERNS.RIGHT[i]) {
            return i.toString();
        }
    }
    return null;
}

/**
 * Déterminer le premier chiffre depuis le motif A/B
 */
function determineFirstDigit(pattern) {
    for (let i = 0; i < FIRST_DIGIT_PATTERNS.length; i++) {
        if (pattern === FIRST_DIGIT_PATTERNS[i]) {
            return i.toString();
        }
    }
    return -1;
}

/**
 * Valider la somme de contrôle EAN-13
 */
function validateEAN13Checksum(code) {
    if (!code || code.length !== 13 || /\D/.test(code)) return false;
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        const digit = parseInt(code[i], 10);
        sum += (i % 2 === 0) ? digit : digit * 3;
    }
    const checksum = (10 - (sum % 10)) % 10;
    return checksum === parseInt(code[12], 10);
}

function validateEAN8Checksum(code) {
    if (!code || code.length !== 8 || /\D/.test(code)) return false;
    let sum = 0;
    for (let i = 0; i < 7; i++) {
        const digit = parseInt(code[i], 10);
        sum += (i % 2 === 0) ? digit * 3 : digit; // pondérations 3,1,3,1,3,1,3
    }
    const checksum = (10 - (sum % 10)) % 10;
    return checksum === parseInt(code[7], 10);
}

/**
 * Interface principale pour décoder depuis une vidéo
 */
function decodeFromVideo(video) {
    return new Promise(async (resolve, reject) => {
        try {
            if (!video || video.readyState !== video.HAVE_ENOUGH_DATA) {
                reject(new Error('Vidéo non prête'));
                return;
            }
            
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            
            canvas.width = Math.min(video.videoWidth, 800);
            canvas.height = Math.min(video.videoHeight, 600);
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // 1) Détection native si disponible (prioritaire)
            if (window.BarcodeDetector) {
                try {
                    const detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8'] });
                    const results = await detector.detect(canvas);
                    if (results && results.length) {
                        const best = results[0];
                        const raw = (best.rawValue || '').trim();
                        const fmt = best.format || (raw.length === 8 ? 'ean_8' : 'ean_13');
                        console.log('✅ [REAL-DECODER] Native détecté:', raw, fmt);
                        
                        if ((fmt === 'ean_13' && validateEAN13Checksum(raw)) || (fmt === 'ean_8' && validateEAN8Checksum(raw))) {
                            resolve({ code: raw, format: fmt.toUpperCase(), confidence: 0.98 });
                            return;
                        }
                    }
                } catch (e) {
                    console.warn('⚠️ [REAL-DECODER] BarcodeDetector échec:', e);
                }
            }
            
            // 2) Fallback: extraction manuelle
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const result = decodeRealBarcode(imageData);
            if (result) {
                resolve(result);
                return;
            }
            
            reject(new Error('Aucun code-barres décodé'));
        } catch (error) {
            reject(error);
        }
    });
}

// Exposition des fonctions globales
window.realBarcodeDecoder = {
    decode: decodeFromVideo,
    decodeImage: decodeRealBarcode,
    validateEAN13: validateEAN13Checksum,
    validateEAN8: validateEAN8Checksum,
    test: function() {
        const video = document.getElementById('universal_scanner_video');
        if (video) {
            return decodeFromVideo(video);
        } else {
            return Promise.reject(new Error('Vidéo non trouvée'));
        }
    }
};

console.log('✅ [REAL-DECODER] Décodeur réel chargé');
console.log('💡 [REAL-DECODER] Utilisez window.realBarcodeDecoder.test() pour tester');
