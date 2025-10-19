/**
 * Améliorations du scanner universel pour une meilleure détection
 */

console.log('🔧 [SCANNER-ENHANCEMENT] Script d\'amélioration du scanner chargé');

// Variables globales pour l'amélioration
let scannerEnhancement = {
    isActive: false,
    detectionAttempts: 0,
    lastFrameTime: 0,
    frameRate: 0,
    qualityMetrics: {
        brightness: 0,
        contrast: 0,
        sharpness: 0
    },
    tips: [],
    lastTipTime: 0,
    tipCooldown: 8000,  // 8 secondes entre les conseils
    silentMode: false   // Mode silencieux pour désactiver les conseils
};

/**
 * Initialiser les améliorations du scanner
 */
function initScannerEnhancement() {
    console.log('🔧 [SCANNER-ENHANCEMENT] Initialisation des améliorations...');
    
    // Observer le modal du scanner
    const scannerModal = document.getElementById('universal_scanner_modal');
    if (scannerModal) {
        scannerModal.addEventListener('shown.bs.modal', function() {
            console.log('🔧 [SCANNER-ENHANCEMENT] Scanner ouvert, activation des améliorations');
            scannerEnhancement.isActive = true;
            startQualityMonitoring();
            showScannerTips();
        });
        
        scannerModal.addEventListener('hidden.bs.modal', function() {
            console.log('🔧 [SCANNER-ENHANCEMENT] Scanner fermé, désactivation des améliorations');
            scannerEnhancement.isActive = false;
            stopQualityMonitoring();
            hideScannerTips();
        });
    }
}

/**
 * Démarrer le monitoring de qualité
 */
function startQualityMonitoring() {
    if (!scannerEnhancement.isActive) return;
    
    const video = document.getElementById('universal_scanner_video');
    if (!video) return;
    
    // Analyser la qualité de l'image toutes les 2 secondes
    const qualityInterval = setInterval(() => {
        if (!scannerEnhancement.isActive) {
            clearInterval(qualityInterval);
            return;
        }
        
        analyzeVideoQuality(video);
        updateScannerTips();
    }, 2000);
}

/**
 * Arrêter le monitoring de qualité
 */
function stopQualityMonitoring() {
    // Les intervals sont nettoyés automatiquement par la vérification isActive
}

/**
 * Analyser la qualité de la vidéo
 */
function analyzeVideoQuality(video) {
    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        
        // Calculer la luminosité moyenne
        let totalBrightness = 0;
        let minBrightness = 255;
        let maxBrightness = 0;
        
        for (let i = 0; i < imageData.data.length; i += 4) {
            const brightness = (imageData.data[i] + imageData.data[i + 1] + imageData.data[i + 2]) / 3;
            totalBrightness += brightness;
            minBrightness = Math.min(minBrightness, brightness);
            maxBrightness = Math.max(maxBrightness, brightness);
        }
        
        const avgBrightness = totalBrightness / (imageData.data.length / 4);
        const contrast = maxBrightness - minBrightness;
        
        scannerEnhancement.qualityMetrics = {
            brightness: avgBrightness,
            contrast: contrast,
            sharpness: calculateSharpness(imageData)
        };
        
        console.log('📊 [SCANNER-ENHANCEMENT] Qualité:', scannerEnhancement.qualityMetrics);
        
    } catch (error) {
        console.log('⚠️ [SCANNER-ENHANCEMENT] Erreur analyse qualité:', error);
    }
}

/**
 * Calculer la netteté de l'image (approximation)
 */
function calculateSharpness(imageData) {
    const width = imageData.width;
    const height = imageData.height;
    let sharpness = 0;
    let count = 0;
    
    // Calculer le gradient sur un échantillon de pixels
    for (let y = 1; y < height - 1; y += 10) {
        for (let x = 1; x < width - 1; x += 10) {
            const i = (y * width + x) * 4;
            const current = (imageData.data[i] + imageData.data[i + 1] + imageData.data[i + 2]) / 3;
            const right = (imageData.data[i + 4] + imageData.data[i + 5] + imageData.data[i + 6]) / 3;
            const bottom = (imageData.data[i + width * 4] + imageData.data[i + width * 4 + 1] + imageData.data[i + width * 4 + 2]) / 3;
            
            const gradientX = Math.abs(current - right);
            const gradientY = Math.abs(current - bottom);
            sharpness += Math.sqrt(gradientX * gradientX + gradientY * gradientY);
            count++;
        }
    }
    
    return count > 0 ? sharpness / count : 0;
}

/**
 * Mettre à jour les conseils du scanner
 */
function updateScannerTips() {
    // Mode silencieux : ne pas afficher de conseils
    if (scannerEnhancement.silentMode) {
        return;
    }
    
    const now = Date.now();
    
    // Respecter le cooldown entre les conseils
    if (now - scannerEnhancement.lastTipTime < scannerEnhancement.tipCooldown) {
        return;
    }
    
    const metrics = scannerEnhancement.qualityMetrics;
    const newTips = [];
    
    // Conseils basés sur la luminosité (seuils plus permissifs)
    if (metrics.brightness < 40) {
        newTips.push({
            type: 'warning',
            icon: '💡',
            message: 'Éclairage très faible - Ajoutez de la lumière'
        });
    } else if (metrics.brightness > 240) {
        newTips.push({
            type: 'warning',
            icon: '☀️',
            message: 'Éclairage très intense - Évitez la lumière directe'
        });
    }
    
    // Conseils basés sur le contraste (seuil plus permissif)
    if (metrics.contrast < 30) {
        newTips.push({
            type: 'info',
            icon: '🔍',
            message: 'Contraste très faible - Vérifiez la qualité du code-barres'
        });
    }
    
    // Conseils basés sur la netteté (seuil très permissif)
    if (metrics.sharpness < 1) {
        newTips.push({
            type: 'info',
            icon: '📱',
            message: 'Image extrêmement floue - Vérifiez la mise au point'
        });
    }
    
    // Conseils généraux après plusieurs tentatives (moins fréquents)
    scannerEnhancement.detectionAttempts++;
    if (scannerEnhancement.detectionAttempts > 25) {
        newTips.push({
            type: 'tip',
            icon: '🎯',
            message: 'Essayez différents angles et distances'
        });
    }
    
    if (scannerEnhancement.detectionAttempts > 40) {
        newTips.push({
            type: 'tip',
            icon: '🔄',
            message: 'Tournez légèrement le code-barres ou changez l\'éclairage'
        });
    }
    
    // Mettre à jour seulement s'il y a des conseils à afficher
    if (newTips.length > 0) {
        scannerEnhancement.tips = newTips;
        scannerEnhancement.lastTipTime = now;
        displayScannerTips();
    }
}

/**
 * Afficher les conseils du scanner
 */
function showScannerTips() {
    const modal = document.getElementById('universal_scanner_modal');
    if (!modal) return;
    
    // Créer le conteneur de conseils s'il n'existe pas
    let tipsContainer = modal.querySelector('.scanner-tips-container');
    if (!tipsContainer) {
        tipsContainer = document.createElement('div');
        tipsContainer.className = 'scanner-tips-container';
        tipsContainer.style.cssText = `
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            z-index: 1000;
            pointer-events: none;
        `;
        
        const modalBody = modal.querySelector('.modal-body');
        if (modalBody) {
            modalBody.style.position = 'relative';
            modalBody.appendChild(tipsContainer);
        }
    }
}

/**
 * Masquer les conseils du scanner
 */
function hideScannerTips() {
    const modal = document.getElementById('universal_scanner_modal');
    if (!modal) return;
    
    const tipsContainer = modal.querySelector('.scanner-tips-container');
    if (tipsContainer) {
        tipsContainer.remove();
    }
}

/**
 * Afficher les conseils actuels
 */
function displayScannerTips() {
    const modal = document.getElementById('universal_scanner_modal');
    if (!modal) return;
    
    const tipsContainer = modal.querySelector('.scanner-tips-container');
    if (!tipsContainer) return;
    
    // Effacer les anciens conseils
    tipsContainer.innerHTML = '';
    
    // Afficher les nouveaux conseils
    scannerEnhancement.tips.forEach((tip, index) => {
        const tipElement = document.createElement('div');
        tipElement.className = `scanner-tip scanner-tip-${tip.type}`;
        tipElement.style.cssText = `
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            margin-bottom: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            animation: fadeInTip 0.3s ease-in;
        `;
        
        tipElement.innerHTML = `
            <span style="margin-right: 8px; font-size: 16px;">${tip.icon}</span>
            <span>${tip.message}</span>
        `;
        
        tipsContainer.appendChild(tipElement);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (tipElement.parentNode) {
                tipElement.style.animation = 'fadeOutTip 0.3s ease-out';
                setTimeout(() => {
                    if (tipElement.parentNode) {
                        tipElement.remove();
                    }
                }, 300);
            }
        }, 5000);
    });
}

/**
 * Ajouter les styles CSS pour les animations
 */
function addScannerEnhancementStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInTip {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOutTip {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
        
        .scanner-tip-warning {
            border-left: 3px solid #ff6b6b;
        }
        
        .scanner-tip-info {
            border-left: 3px solid #4ecdc4;
        }
        
        .scanner-tip-tip {
            border-left: 3px solid #45b7d1;
        }
    `;
    document.head.appendChild(style);
}

// Initialiser quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    addScannerEnhancementStyles();
    initScannerEnhancement();
    
    console.log('✅ [SCANNER-ENHANCEMENT] Améliorations du scanner initialisées');
    console.log('💡 [SCANNER-ENHANCEMENT] Utilisez window.scannerEnhancement pour déboguer');
});

/**
 * Activer/désactiver le mode silencieux
 */
function toggleScannerTips(silent = true) {
    scannerEnhancement.silentMode = silent;
    console.log(`🔧 [SCANNER-ENHANCEMENT] Mode silencieux: ${silent ? 'ACTIVÉ' : 'DÉSACTIVÉ'}`);
    
    if (silent) {
        // Masquer les conseils existants
        hideScannerTips();
    }
}

// Exposer pour le débogage
window.scannerEnhancement = scannerEnhancement;
window.toggleScannerTips = toggleScannerTips;
