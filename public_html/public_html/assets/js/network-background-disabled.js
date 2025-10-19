/**
 * ==================== FOND FUTURISTE SOBRE - SYSTÈME DÉSACTIVÉ ====================
 * Version sobre sans particules ni lignes
 * Seuls les effets CSS subtils sont utilisés
 */

// Désactiver complètement le système de particules
document.addEventListener('DOMContentLoaded', () => {
    // Supprimer tous les éléments du réseau s'ils existent
    const networkCanvas = document.querySelector('.network-canvas-container');
    if (networkCanvas) {
        networkCanvas.remove();
    }
    
    const canvas = document.getElementById('networkCanvas');
    if (canvas) {
        canvas.remove();
    }
    
    // Supprimer les anciens éléments d'animation
    const elementsToRemove = [
        '.light-rays',
        '.floating-orbs', 
        '.scan-lines',
        '.connection-lines'
    ];
    
    elementsToRemove.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
            element.remove();
        }
    });
    
    // Ajouter les nouveaux éléments subtils
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        createSubtleElements();
    }
    
    // Écouter les changements de thème
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (e.matches) {
            createSubtleElements();
        } else {
            removeSubtleElements();
        }
    });
});

function createSubtleElements() {
    // Vérifier si les animations sont autorisées
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }
    
    // Supprimer les anciens éléments s'ils existent
    removeSubtleElements();
    
    // Points lumineux discrets
    const glowPoints = document.createElement('div');
    glowPoints.className = 'subtle-glow-points';
    document.body.appendChild(glowPoints);
    
    // Lueur ambiante
    const ambientGlow = document.createElement('div');
    ambientGlow.className = 'ambient-glow';
    document.body.appendChild(ambientGlow);
    
    // Dégradé de profondeur
    const depthGradient = document.createElement('div');
    depthGradient.className = 'depth-gradient';
    document.body.appendChild(depthGradient);
}

function removeSubtleElements() {
    const elementsToRemove = [
        '.subtle-glow-points',
        '.ambient-glow',
        '.depth-gradient'
    ];
    
    elementsToRemove.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
            element.remove();
        }
    });
}

// Nettoyage au déchargement
window.addEventListener('beforeunload', () => {
    removeSubtleElements();
});

// Désactiver l'ancien système s'il existe
if (window.networkBackground) {
    window.networkBackground.destroy();
    window.networkBackground = null;
}
