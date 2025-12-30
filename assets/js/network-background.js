/**
 * ==================== FOND FUTURISTE SOBRE - ANIMATIONS VISIBLES ====================
 * Version sobre avec animations subtiles mais visibles
 * Correction des z-index et visibilité
 */

// Désactiver complètement le système de particules
document.addEventListener('DOMContentLoaded', () => {
    console.log('🎨 Initialisation du fond futuriste sobre...');
    
    // Supprimer tous les éléments du réseau s'ils existent
    const networkCanvas = document.querySelector('.network-canvas-container');
    if (networkCanvas) {
        networkCanvas.remove();
        console.log('🗑️ Canvas réseau supprimé');
    }
    
    const canvas = document.getElementById('networkCanvas');
    if (canvas) {
        canvas.remove();
        console.log('🗑️ Canvas particules supprimé');
    }
    
    // Supprimer les anciens éléments d'animation
    const elementsToRemove = [
        '.light-rays'
        '.floating-orbs'
        '.scan-lines'
        '.connection-lines'
    ];
    
    elementsToRemove.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
            element.remove();
            console.log(`🗑️ Élément ${selector} supprimé`);
        }
    
    // Ajouter les nouveaux éléments subtils
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        console.log('🌙 Mode sombre détecté - création des éléments subtils');
        createSubtleElements();
    } else {
        console.log('🌅 Mode jour détecté - pas d\'éléments de fond');
    }
    
    // Écouter les changements de thème
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (e.matches) {
            console.log('🌙 Passage en mode sombre - création des éléments');
            createSubtleElements();
        } else {
            console.log('🌅 Passage en mode jour - suppression des éléments');
            removeSubtleElements();
        }

function createSubtleElements() {
    // Vérifier si les animations sont autorisées
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        console.log('⚠️ Animations réduites - pas d\'éléments de fond');
        return;
    }
    
    // Supprimer les anciens éléments s'ils existent
    removeSubtleElements();
    
    // Points lumineux discrets
    const glowPoints = document.createElement('div');
    glowPoints.className = 'subtle-glow-points';
    glowPoints.style.zIndex = '1';
    glowPoints.style.position = 'fixed';
    glowPoints.style.top = '0';
    glowPoints.style.left = '0';
    glowPoints.style.width = '100vw';
    glowPoints.style.height = '100vh';
    glowPoints.style.pointerEvents = 'none';
    document.body.appendChild(glowPoints);
    console.log('✨ Points lumineux créés');
    
    // Lueur ambiante
    const ambientGlow = document.createElement('div');
    ambientGlow.className = 'ambient-glow';
    ambientGlow.style.zIndex = '1';
    ambientGlow.style.position = 'fixed';
    ambientGlow.style.top = '0';
    ambientGlow.style.left = '0';
    ambientGlow.style.width = '100vw';
    ambientGlow.style.height = '100vh';
    ambientGlow.style.pointerEvents = 'none';
    document.body.appendChild(ambientGlow);
    console.log('🌊 Lueur ambiante créée');
    
    // Dégradé de profondeur
    const depthGradient = document.createElement('div');
    depthGradient.className = 'depth-gradient';
    depthGradient.style.zIndex = '1';
    depthGradient.style.position = 'fixed';
    depthGradient.style.top = '0';
    depthGradient.style.left = '0';
    depthGradient.style.width = '100vw';
    depthGradient.style.height = '100vh';
    depthGradient.style.pointerEvents = 'none';
    document.body.appendChild(depthGradient);
    console.log('📐 Dégradé de profondeur créé');
    
    // Vérifier que les éléments sont bien créés
    setTimeout(() => {
        const createdElements = [
            document.querySelector('.subtle-glow-points')
            document.querySelector('.ambient-glow')
            document.querySelector('.depth-gradient')
        ];
        
        const visibleCount = createdElements.filter(el => el !== null).length;
        console.log(`✅ ${visibleCount}/3 éléments de fond créés et visibles`);
        
        if (visibleCount < 3) {
            console.warn('⚠️ Certains éléments de fond ne sont pas créés');
        }
    }, 100);
}

function removeSubtleElements() {
    const elementsToRemove = [
        '.subtle-glow-points'
        '.ambient-glow'
        '.depth-gradient'
    ];
    
    let removedCount = 0;
    elementsToRemove.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
            element.remove();
            removedCount++;
        }
    
    if (removedCount > 0) {
        console.log(`🗑️ ${removedCount} éléments de fond supprimés`);
    }
}

// Nettoyage au déchargement
window.addEventListener('beforeunload', () => {
    removeSubtleElements();
    console.log('🧹 Nettoyage des éléments de fond');

// Désactiver l'ancien système s'il existe
if (window.networkBackground) {
    window.networkBackground.destroy();
    window.networkBackground = null;
    console.log('🗑️ Ancien système de particules désactivé');
}

// Debug : vérifier les éléments après 2 secondes
setTimeout(() => {
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        const elements = [
            document.querySelector('.subtle-glow-points')
            document.querySelector('.ambient-glow')
            document.querySelector('.depth-gradient')
        ];
        
        console.log('🔍 État des éléments de fond après 2s:');
        elements.forEach((el, index) => {
            const names = ['Points lumineux', 'Lueur ambiante', 'Dégradé profondeur'];
            if (el) {
                const styles = window.getComputedStyle(el);
                console.log(`  ✅ ${names[index]}: visible (opacity: ${styles.opacity}, z-index: ${styles.zIndex})`);
            } else {
                console.log(`  ❌ ${names[index]}: non trouvé`);
            }
    }
}, 2000);
