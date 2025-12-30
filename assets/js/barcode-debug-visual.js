/**
 * DEBUG VISUEL SCANNER CODES-BARRES
 * Affichage en temps réel de ce qui est détecté et analysé
 */

console.log('🔍 [BARCODE-DEBUG-VISUAL] Initialisation du debug visuel...');

// Variables de debug
let debugOverlay = null;
let debugCanvas = null;
let debugContext = null;
let debugActive = false;
let detectionLog = [];
let debugInterval = null;

/**
 * Créer l'overlay de debug
 */
function createDebugOverlay() {
    // Supprimer l'ancien overlay s'il existe
    if (debugOverlay) {
        debugOverlay.remove();
    }

    // Créer le conteneur de debug
    debugOverlay = document.createElement('div');
    debugOverlay.id = 'barcode-debug-overlay';
    debugOverlay.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        width: 300px;
        max-height: 80vh;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 15px;
        border-radius: 10px;
        font-family: monospace;
        font-size: 12px;
        z-index: 10000;
        overflow-y: auto;
        border: 2px solid #00ff00;
        box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
    `;

    // Ajouter le titre
    const title = document.createElement('div');
    title.innerHTML = '🔍 DEBUG CODES-BARRES';
    title.style.cssText = `
        font-weight: bold;
        color: #00ff00;
        margin-bottom: 10px;
        text-align: center;
        border-bottom: 1px solid #00ff00;
        padding-bottom: 5px;
    `;
    debugOverlay.appendChild(title);

    // Zone de statut en temps réel
    const statusDiv = document.createElement('div');
    statusDiv.id = 'debug-status';
    statusDiv.style.cssText = `
        background: rgba(0, 100, 0, 0.3);
        padding: 8px;
        border-radius: 5px;
        margin-bottom: 10px;
        border-left: 3px solid #00ff00;
    `;
    debugOverlay.appendChild(statusDiv);

    // Zone de log des détections
    const logDiv = document.createElement('div');
    logDiv.id = 'debug-log';
    logDiv.style.cssText = `
        max-height: 200px;
        overflow-y: auto;
        background: rgba(50, 50, 50, 0.8);
        padding: 8px;
        border-radius: 5px;
        margin-bottom: 10px;
    `;
    debugOverlay.appendChild(logDiv);

    // Boutons de contrôle
    const controlsDiv = document.createElement('div');
    controlsDiv.style.cssText = `
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    `;

    // Bouton Clear Log
    const clearBtn = document.createElement('button');
    clearBtn.innerHTML = '🗑️ Clear';
    clearBtn.style.cssText = `
        background: #ff4444;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
    `;
    clearBtn.onclick = clearDebugLog;
    controlsDiv.appendChild(clearBtn);

    // Bouton Toggle
    const toggleBtn = document.createElement('button');
    toggleBtn.innerHTML = '⏸️ Pause';
    toggleBtn.id = 'debug-toggle-btn';
    toggleBtn.style.cssText = `
        background: #4444ff;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
    `;
    toggleBtn.onclick = toggleDebugPause;
    controlsDiv.appendChild(toggleBtn);

    // Bouton Fermer
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '❌ Fermer';
    closeBtn.style.cssText = `
        background: #666;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
    `;
    closeBtn.onclick = hideDebugOverlay;
    controlsDiv.appendChild(closeBtn);

    debugOverlay.appendChild(controlsDiv);

    // Ajouter à la page
    document.body.appendChild(debugOverlay);

    console.log('✅ [BARCODE-DEBUG-VISUAL] Overlay créé');
}

/**
 * Démarrer le debug visuel
 */
function startVisualDebug() {
    if (debugActive) return;

    console.log('🚀 [BARCODE-DEBUG-VISUAL] Démarrage du debug visuel');

    debugActive = true;
    createDebugOverlay();

    // Créer le canvas pour l'analyse
    debugCanvas = document.createElement('canvas');
    debugContext = debugCanvas.getContext('2d');

    // Démarrer l'analyse en temps réel
    debugInterval = setInterval(() => {
        if (debugActive) {
            analyzeCurrentFrame();
        }
    }, 1000); // Toutes les secondes

    updateDebugStatus('🟢 Debug actif - Analyse en cours...');
}

/**
 * Arrêter le debug visuel
 */
function stopVisualDebug() {
    console.log('🛑 [BARCODE-DEBUG-VISUAL] Arrêt du debug visuel');

    debugActive = false;

    if (debugInterval) {
        clearInterval(debugInterval);
        debugInterval = null;
    }

    if (debugOverlay) {
        debugOverlay.remove();
        debugOverlay = null;
    }
}

/**
 * Analyser la frame actuelle
 */
function analyzeCurrentFrame() {
    const video = document.getElementById('universal_scanner_video');
    if (!video || video.readyState !== video.HAVE_ENOUGH_DATA) {
        updateDebugStatus('🔴 Vidéo non disponible');
        return;
    }

    // Redimensionner le canvas
    const width = Math.min(video.videoWidth, 640);
    const height = Math.min(video.videoHeight, 480);

    debugCanvas.width = width;
    debugCanvas.height = height;

    // Dessiner la frame
    debugContext.drawImage(video, 0, 0, width, height);
    const imageData = debugContext.getImageData(0, 0, width, height);

    // Analyser l'image
    const analysis = performDetailedAnalysis(imageData);

    // Mettre à jour l'affichage
    updateDebugDisplay(analysis);

    // Tenter la détection avec différentes méthodes
    tryAllDetectionMethods(imageData, analysis);
}

/**
 * Analyse détaillée de l'image
 */
function performDetailedAnalysis(imageData) {
    const data = imageData.data;
    const width = imageData.width;
    const height = imageData.height;

    // Analyser plusieurs lignes
    const lines = [
        Math.floor(height * 0.3),  // 30%
        Math.floor(height * 0.5),  // 50% (centre)
        Math.floor(height * 0.7)   // 70%
    ];

    const analysis = {
        timestamp: new Date().toLocaleTimeString()
        dimensions: `${width}x${height}`
        lines: []
    };

    lines.forEach((y, index) => {
        const lineAnalysis = analyzeHorizontalLine(data, width, height, y);
        lineAnalysis.position = `${Math.round((y / height) * 100)}%`;
        analysis.lines.push(lineAnalysis);

        // Analyse globale
        analysis.avgBrightness = analysis.lines.reduce((sum, line) => sum + line.avgBrightness, 0) / analysis.lines.length;
        analysis.maxTransitions = Math.max(...analysis.lines.map(line => line.transitions));
        analysis.hasBarcodeLikePattern = analysis.lines.some(line => line.transitions >= 15 && line.transitions <= 80);

        return analysis;
    }

/**
 * Analyser une ligne horizontale
 */
function analyzeHorizontalLine(data, width, height, y) {
            const startX = Math.floor(width * 0.1);
            const endX = Math.floor(width * 0.9);

            let transitions = 0;
            let lastPixelDark = false;
            let darkBars = 0;
            let lightBars = 0;
            let totalBrightness = 0;
            let pixelCount = 0;
            let barLengths = [];
            let currentBarLength = 0;

            for (let x = startX; x < endX; x++) {
                const pixelIndex = (y * width + x) * 4;
                const r = data[pixelIndex];
                const g = data[pixelIndex + 1];
                const b = data[pixelIndex + 2];

                const brightness = (r + g + b) / 3;
                totalBrightness += brightness;
                pixelCount++;

                const isDark = brightness < 128;

                if (isDark !== lastPixelDark) {
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

            return {
                transitions
        darkBars
        lightBars
        avgBrightness: Math.round(totalBrightness / pixelCount)
        barLengths: barLengths.slice(0, 10), // Garder seulement les 10 premières
                minBarLength: Math.min(...barLengths)
        maxBarLength: Math.max(...barLengths)
        barCount: barLengths.length
            };
        }

/**
 * Essayer toutes les méthodes de détection
 */
function tryAllDetectionMethods(imageData, analysis) {
            const results = [];

            // Méthode 1: Quagga si disponible
            if (typeof Quagga !== 'undefined') {
                tryQuaggaDetection(imageData, (result) => {
                    if (result) {
                        results.push({
                            method: 'Quagga'
                    code: result.code
                    format: result.format
                    confidence: result.confidence || 'N/A'
                logDetection('Quagga', result.code, result.format, 'ACCEPTÉ');
                    }
                }

    // Méthode 2: Détection simple par motif
    if (analysis.hasBarcodeLikePattern) {
                    const simulatedCode = generateCodeFromPattern(analysis);
                    results.push({
                        method: 'Motif'
            code: simulatedCode
            format: 'Simulé'
            confidence: 'Pattern'
        logDetection('Motif', simulatedCode, 'Simulé', 'GÉNÉRÉ');
                    }

    // Log si aucune détection
    if (results.length === 0) {
                        logDetection('Aucune', 'N/A', 'N/A', 'ÉCHEC - ' + getFailureReason(analysis));
                    }
                }

                /**
                 * Essayer la détection Quagga
                 */
                function tryQuaggaDetection(imageData, callback) {
                    try {
                        const canvas = document.createElement('canvas');
                        canvas.width = imageData.width;
                        canvas.height = imageData.height;
                        const ctx = canvas.getContext('2d');
                        ctx.putImageData(imageData, 0, 0);

                        Quagga.decodeSingle({
                            decoder: {
                                readers: ["ean_reader", "code_128_reader", "code_39_reader", "upc_reader"]
                            }
            locate: true
            src: canvas.toDataURL()
                        }, function (result) {
                            if (result && result.codeResult) {
                                callback({
                                    code: result.codeResult.code
                    format: result.codeResult.format
                    confidence: result.codeResult.confidence
                                } else {
                                    callback(null);
                                }
    } catch (error) {
                                console.warn('⚠️ [BARCODE-DEBUG-VISUAL] Erreur Quagga:', error);
                                callback(null);
                            }
                        }

/**
 * Générer un code à partir du motif détecté
 */
function generateCodeFromPattern(analysis) {
                                const maxTransitions = analysis.maxTransitions;
                                const avgBrightness = Math.round(analysis.avgBrightness);

                                // Générer un code basé sur les caractéristiques
                                return `PAT${maxTransitions.toString().padStart(3, '0')}${avgBrightness.toString().padStart(3, '0')}`;
                            }

/**
 * Obtenir la raison de l'échec
 */
function getFailureReason(analysis) {
                                if (analysis.maxTransitions < 15) {
                                    return `Pas assez de transitions (${analysis.maxTransitions} < 15)`;
                                }
                                if (analysis.maxTransitions > 80) {
                                    return `Trop de transitions (${analysis.maxTransitions} > 80)`;
                                }
                                if (analysis.avgBrightness < 50) {
                                    return `Image trop sombre (${Math.round(analysis.avgBrightness)})`;
                                }
                                if (analysis.avgBrightness > 200) {
                                    return `Image trop claire (${Math.round(analysis.avgBrightness)})`;
                                }
                                return 'Motif non reconnu';
                            }

/**
 * Logger une détection
 */
function logDetection(method, code, format, status) {
                                const logEntry = {
                                    time: new Date().toLocaleTimeString()
        method
        code
        format
        status
                                };

                                detectionLog.unshift(logEntry); // Ajouter au début

                                // Garder seulement les 20 dernières entrées
                                if (detectionLog.length > 20) {
                                    detectionLog = detectionLog.slice(0, 20);
                                }

                                updateDebugLog();
                            }

/**
 * Mettre à jour l'affichage de debug
 */
function updateDebugDisplay(analysis) {
                                updateDebugStatus(`
        🕐 ${analysis.timestamp}<br>
        📐 ${analysis.dimensions}<br>
        💡 Luminosité: ${Math.round(analysis.avgBrightness)}<br>
        🔄 Max transitions: ${analysis.maxTransitions}<br>
        📊 Motif détecté: ${analysis.hasBarcodeLikePattern ? '✅ OUI' : '❌ NON'}
    `);
                            }

/**
 * Mettre à jour le statut
 */
function updateDebugStatus(html) {
                                const statusDiv = document.getElementById('debug-status');
                                if (statusDiv) {
                                    statusDiv.innerHTML = html;
                                }
                            }

/**
 * Mettre à jour le log
 */
function updateDebugLog() {
                                const logDiv = document.getElementById('debug-log');
                                if (!logDiv) return;

                                let html = '';
                                detectionLog.forEach(entry => {
                                    const color = entry.status.includes('ACCEPTÉ') ? '#00ff00' :
                                        entry.status.includes('GÉNÉRÉ') ? '#ffaa00' : '#ff4444';

                                    html += `
            <div style="margin-bottom: 5px; padding: 3px; border-left: 2px solid ${color}; padding-left: 5px;">
                <strong>${entry.time}</strong> [${entry.method}]<br>
                Code: <code>${entry.code}</code> (${entry.format})<br>
                <span style="color: ${color};">${entry.status}</span>
            </div>
        `;

                                    logDiv.innerHTML = html || '<em>Aucune détection encore...</em>';
                                    logDiv.scrollTop = 0; // Scroll vers le haut
                                }

/**
 * Vider le log
 */
function clearDebugLog() {
                                        detectionLog = [];
                                        updateDebugLog();
                                    }

/**
 * Basculer pause/reprise
 */
function toggleDebugPause() {
                                        const btn = document.getElementById('debug-toggle-btn');
                                        if (!btn) return;

                                        if (debugInterval) {
                                            clearInterval(debugInterval);
                                            debugInterval = null;
                                            btn.innerHTML = '▶️ Reprendre';
                                            updateDebugStatus('⏸️ Debug en pause');
                                        } else {
                                            debugInterval = setInterval(() => {
                                                if (debugActive) {
                                                    analyzeCurrentFrame();
                                                }
                                            }, 1000);
                                            btn.innerHTML = '⏸️ Pause';
                                            updateDebugStatus('🟢 Debug repris');
                                        }
                                    }

/**
 * Masquer l'overlay
 */
function hideDebugOverlay() {
                                        stopVisualDebug();
                                    }

// Exposition des fonctions globales
window.barcodeDebugVisual = {
                                        start: startVisualDebug
    stop: stopVisualDebug
    isActive: () => debugActive
    getLog: () => detectionLog
    clearLog: clearDebugLog
                                    };

                                // Auto-initialisation sur ouverture du scanner
                                document.addEventListener('DOMContentLoaded', function () {
                                    console.log('🔍 [BARCODE-DEBUG-VISUAL] DOM chargé, installation des événements...');

                                    const scannerModal = document.getElementById('universal_scanner_modal');
                                    if (scannerModal) {
                                        console.log('✅ [BARCODE-DEBUG-VISUAL] Modal scanner trouvé');

                                        scannerModal.addEventListener('shown.bs.modal', function () {
                                            console.log('🚀 [BARCODE-DEBUG-VISUAL] Modal scanner ouvert, démarrage debug...');

                                            // Démarrer le debug automatiquement après 2 secondes
                                            setTimeout(() => {
                                                if (!debugActive) {
                                                    console.log('🔍 [BARCODE-DEBUG-VISUAL] Démarrage automatique du debug');
                                                    startVisualDebug();
                                                } else {
                                                    console.log('🔍 [BARCODE-DEBUG-VISUAL] Debug déjà actif');
                                                }
                                            }, 2000);

                                            scannerModal.addEventListener('hidden.bs.modal', function () {
                                                console.log('🛑 [BARCODE-DEBUG-VISUAL] Modal fermé, arrêt debug');
                                                stopVisualDebug();
                                            } else {
                                                console.warn('⚠️ [BARCODE-DEBUG-VISUAL] Modal scanner non trouvé');
                                            }

// Fallback: démarrage manuel si le modal est déjà ouvert
setTimeout(() => {
                                                const scannerModal = document.getElementById('universal_scanner_modal');
                                                if (scannerModal && scannerModal.classList.contains('show') && !debugActive) {
                                                    console.log('🔍 [BARCODE-DEBUG-VISUAL] Fallback: Modal déjà ouvert, démarrage debug');
                                                    startVisualDebug();
                                                }
                                            }, 3000);

                                            console.log('✅ [BARCODE-DEBUG-VISUAL] Debug visuel chargé');
                                            console.log('💡 [BARCODE-DEBUG-VISUAL] Utilisez window.barcodeDebugVisual.start() pour démarrer');
