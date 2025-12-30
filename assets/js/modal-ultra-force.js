/**
 * SCRIPT ULTRA-AGRESSIF POUR FORCER L'AFFICHAGE DU MODAL
 * APPLIQUE LES CORRECTIONS IMMÉDIATEMENT AU CHARGEMENT
 */

console.log('🔥 [MODAL-ULTRA-FORCE] Script ultra-agressif chargé');

// Fonction pour appliquer les corrections de force brute
function applyUltraForce() {
    console.log('🔥 [MODAL-ULTRA-FORCE] Application des corrections de force brute...');
    
    const modal = document.getElementById('nouvelles_actions_modal');
    if (!modal) {
        console.error('❌ Modal nouvelles_actions_modal non trouvé !');
        return false;
    }
    
    console.log('✅ Modal trouvé, application des corrections ultra-agressives...');
    
    // 1. CORRECTION ULTRA-AGRESSIVE DU MODAL-BODY
    const modalBody = modal.querySelector('.modal-body');
    if (modalBody) {
        // Supprimer TOUTES les classes problématiques
        modalBody.classList.remove('p-0', 'position-relative', 'overflow-hidden');
        
        // Appliquer les styles de force
        modalBody.style.cssText = `
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
            min-height: 400px !important;
            padding: 20px !important;
            background: #f8f9fa !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: auto !important;
        `;
        
        console.log('🔥 [MODAL-ULTRA-FORCE] Modal-body forcé - classes supprimées et styles appliqués');
    }
    
    // 2. FORCER LA GRILLE D'ACTIONS
    const actionsGrid = modal.querySelector('.modern-actions-grid');
    if (actionsGrid) {
        actionsGrid.style.cssText = `
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
            gap: 15px !important;
            padding: 20px !important;
            background: transparent !important;
            visibility: visible !important;
            opacity: 1 !important;
        `;
        
        console.log('🔥 [MODAL-ULTRA-FORCE] Grille d\'actions forcée');
    }
    
    // 3. FORCER TOUTES LES CARTES D'ACTION
    const actionCards = modal.querySelectorAll('.modern-action-card');
    console.log(`🔥 [MODAL-ULTRA-FORCE] ${actionCards.length} cartes d'action trouvées`);
    
    actionCards.forEach((card, index) => {
        // Supprimer les classes d'animation
        card.classList.remove('loading-card');
        
        card.style.cssText = `
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
            min-height: 120px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            margin: 10px !important;
            background: white !important;
            border: 2px solid #007bff !important;
            border-radius: 8px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: auto !important;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        `;
        
        // Forcer le contenu de chaque carte
        const title = card.querySelector('.action-title');
        const description = card.querySelector('.action-description');
        const icon = card.querySelector('.action-icon');
        
        if (title) {
            title.style.cssText = `
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                color: #007bff !important;
                font-size: 1.1rem !important;
                font-weight: bold !important;
                text-align: center !important;
                margin: 5px 0 !important;
            `;
        }
        
        if (description) {
            description.style.cssText = `
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                color: #666 !important;
                font-size: 0.9rem !important;
                text-align: center !important;
                margin: 5px 0 !important;
            `;
        }
        
        if (icon) {
            icon.style.cssText = `
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                color: #007bff !important;
                font-size: 2rem !important;
                margin-bottom: 10px !important;
                text-align: center !important;
            `;
        }
        
        console.log(`✅ [MODAL-ULTRA-FORCE] Carte ${index + 1} forcée:`, {
            title: title ? title.textContent : 'N/A'
    
    // 4. SUPPRIMER TOUS LES EFFETS VISUELS PROBLÉMATIQUES
    const problematicElements = modal.querySelectorAll('.particles-container, .particle, .card-glow, .pulse-ring, .btn-glow, .loading-spinner');
    problematicElements.forEach(el => {
        el.style.display = 'none';
        el.style.visibility = 'hidden';
        el.style.opacity = '0';
    
    console.log(`🔥 [MODAL-ULTRA-FORCE] ${problematicElements.length} éléments problématiques supprimés`);
    
    // 5. DIAGNOSTIC FINAL
    setTimeout(() => {
        const finalModalBody = modal.querySelector('.modal-body');
        const finalCards = modal.querySelectorAll('.modern-action-card');
        
        console.log('📊 [MODAL-ULTRA-FORCE] État final après force brute:');
        console.log('  - Modal-body overflow:', finalModalBody ? getComputedStyle(finalModalBody).overflow : 'N/A');
        console.log('  - Modal-body height:', finalModalBody ? getComputedStyle(finalModalBody).height : 'N/A');
        console.log('  - Cartes visibles:', finalCards.length);
        
        finalCards.forEach((card, index) => {
            const computedStyle = getComputedStyle(card);
            const title = card.querySelector('.action-title');
            console.log(`  - Carte ${index + 1}:`, {
                title: title ? title.textContent : 'N/A'
                display: computedStyle.display
                visibility: computedStyle.visibility
                opacity: computedStyle.opacity
                overflow: computedStyle.overflow
    }, 100);
    
    return true;
}

// Fonction pour activer le mode debug visuel
function enableDebugMode() {
    document.body.classList.add('debug-modal-ultra');
    console.log('🔥 [MODAL-ULTRA-FORCE] Mode debug activé - bordures colorées visibles');
}

// Fonction pour désactiver le mode debug
function disableDebugMode() {
    document.body.classList.remove('debug-modal-ultra');
    console.log('🔥 [MODAL-ULTRA-FORCE] Mode debug désactivé');
}

// Appliquer les corrections dès que possible
function initUltraForce() {
    console.log('🔥 [MODAL-ULTRA-FORCE] Initialisation...');
    
    // Essayer immédiatement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyUltraForce);
    } else {
        applyUltraForce();
    }
    
    // Réessayer après un délai pour être sûr
    setTimeout(applyUltraForce, 500);
    setTimeout(applyUltraForce, 1000);
    setTimeout(applyUltraForce, 2000);
}

// Rendre les fonctions accessibles globalement
window.applyUltraForce = applyUltraForce;
window.enableDebugMode = enableDebugMode;
window.disableDebugMode = disableDebugMode;

// Initialiser immédiatement
initUltraForce();

console.log('✅ [MODAL-ULTRA-FORCE] Fonctions disponibles:');
console.log('  - applyUltraForce() : Applique les corrections de force brute');
console.log('  - enableDebugMode() : Active les bordures colorées de debug');
console.log('  - disableDebugMode() : Désactive le mode debug');
