/**
 * FONCTION POUR FORCER L'AFFICHAGE DU MODAL NOUVELLES ACTIONS
 * À utiliser dans la console pour diagnostiquer le problème
 */

console.log('🔧 [MODAL-FORCE-SHOW] Script de forçage d\'affichage chargé');

// Fonction pour forcer l'affichage du modal
function forceShowModal() {
    console.log('🚀 [MODAL-FORCE-SHOW] Forçage de l\'affichage du modal...');
    
    const modal = document.getElementById('nouvelles_actions_modal');
    if (!modal) {
        console.error('❌ Modal nouvelles_actions_modal non trouvé !');
        return false;
    }
    
    console.log('✅ Modal trouvé, application des corrections...');
    
    // 1. Supprimer toutes les classes problématiques
    modal.classList.remove('fade');
    modal.style.display = 'block';
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
    modal.style.pointerEvents = 'auto';
    modal.style.zIndex = '9999';
    modal.setAttribute('aria-hidden', 'false');
    
    // 2. Corriger le modal-dialog
    const modalDialog = modal.querySelector('.modal-dialog');
    if (modalDialog) {
        modalDialog.style.transform = 'none';
        modalDialog.style.opacity = '1';
        modalDialog.style.visibility = 'visible';
        modalDialog.style.pointerEvents = 'auto';
    }
    
    // 3. Corriger le modal-content
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
        modalContent.style.opacity = '1';
        modalContent.style.visibility = 'visible';
        modalContent.style.transform = 'none';
        modalContent.style.background = 'white';
    }
    
    // 4. CORRECTION CRITIQUE : Supprimer overflow: hidden du modal-body
    const modalBody = modal.querySelector('.modal-body');
    if (modalBody) {
        modalBody.style.overflow = 'visible !important';
        modalBody.style.height = 'auto !important';
        modalBody.style.maxHeight = 'none !important';
        modalBody.style.opacity = '1';
        modalBody.style.visibility = 'visible';
        modalBody.style.display = 'block';
        modalBody.style.padding = '20px';
        modalBody.style.background = '#f8f9fa';
        
        console.log('🔧 [MODAL-FORCE-SHOW] Modal-body corrigé - overflow: visible');
    }
    
    // 5. Forcer l'affichage de toutes les cartes d'action
    const actionCards = modal.querySelectorAll('.modern-action-card');
    console.log(`🎯 [MODAL-FORCE-SHOW] ${actionCards.length} cartes d'action trouvées`);
    
    actionCards.forEach((card, index) => {
        card.style.display = 'flex !important';
        card.style.visibility = 'visible !important';
        card.style.opacity = '1 !important';
        card.style.transform = 'none !important';
        card.style.position = 'relative !important';
        card.style.zIndex = 'auto !important';
        card.style.margin = '10px 0';
        card.style.padding = '15px';
        card.style.background = 'white';
        card.style.border = '1px solid #dee2e6';
        card.style.borderRadius = '8px';
        
        // Forcer l'affichage du contenu de chaque carte
        const title = card.querySelector('.action-title');
        const description = card.querySelector('.action-description');
        const icon = card.querySelector('.action-icon');
        
        if (title) {
            title.style.display = 'block';
            title.style.visibility = 'visible';
            title.style.opacity = '1';
            title.style.color = '#333';
            title.style.fontSize = '1.1rem';
            title.style.fontWeight = 'bold';
        }
        
        if (description) {
            description.style.display = 'block';
            description.style.visibility = 'visible';
            description.style.opacity = '1';
            description.style.color = '#666';
            description.style.fontSize = '0.9rem';
        }
        
        if (icon) {
            icon.style.display = 'block';
            icon.style.visibility = 'visible';
            icon.style.opacity = '1';
            icon.style.fontSize = '1.5rem';
            icon.style.color = '#007bff';
        }
        
        console.log(`✅ [MODAL-FORCE-SHOW] Carte ${index + 1} corrigée:`, {
            title: title ? title.textContent : 'N/A',
            display: card.style.display,
            visibility: card.style.visibility,
            opacity: card.style.opacity
        });
    });
    
    // 6. Supprimer le backdrop s'il existe
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.style.opacity = '0.5';
        backdrop.style.zIndex = '1040';
    }
    
    // 7. Ajouter la classe show pour Bootstrap
    modal.classList.add('show');
    
    console.log('✅ [MODAL-FORCE-SHOW] Modal forcé à s\'afficher !');
    
    // 8. Diagnostic final
    setTimeout(() => {
        const finalModalBody = modal.querySelector('.modal-body');
        const finalCards = modal.querySelectorAll('.modern-action-card');
        
        console.log('📊 [MODAL-FORCE-SHOW] État final:');
        console.log('  - Modal display:', modal.style.display);
        console.log('  - Modal-body overflow:', finalModalBody ? getComputedStyle(finalModalBody).overflow : 'N/A');
        console.log('  - Cartes visibles:', finalCards.length);
        
        finalCards.forEach((card, index) => {
            const computedStyle = getComputedStyle(card);
            console.log(`  - Carte ${index + 1}:`, {
                display: computedStyle.display,
                visibility: computedStyle.visibility,
                opacity: computedStyle.opacity,
                height: computedStyle.height
            });
        });
    }, 100);
    
    return true;
}

// Fonction pour diagnostiquer le problème
function diagnoseModal() {
    console.log('🔍 [MODAL-FORCE-SHOW] Diagnostic du modal...');
    
    const modal = document.getElementById('nouvelles_actions_modal');
    if (!modal) {
        console.error('❌ Modal non trouvé !');
        return;
    }
    
    const modalBody = modal.querySelector('.modal-body');
    const actionCards = modal.querySelectorAll('.modern-action-card');
    
    console.log('📊 [MODAL-FORCE-SHOW] État actuel:');
    console.log('  - Modal classes:', modal.className);
    console.log('  - Modal display:', getComputedStyle(modal).display);
    console.log('  - Modal visibility:', getComputedStyle(modal).visibility);
    console.log('  - Modal opacity:', getComputedStyle(modal).opacity);
    
    if (modalBody) {
        const bodyStyle = getComputedStyle(modalBody);
        console.log('  - Modal-body overflow:', bodyStyle.overflow);
        console.log('  - Modal-body height:', bodyStyle.height);
        console.log('  - Modal-body maxHeight:', bodyStyle.maxHeight);
        console.log('  - Modal-body display:', bodyStyle.display);
    }
    
    console.log('  - Nombre de cartes:', actionCards.length);
    
    actionCards.forEach((card, index) => {
        const cardStyle = getComputedStyle(card);
        const title = card.querySelector('.action-title');
        console.log(`  - Carte ${index + 1}:`, {
            title: title ? title.textContent : 'N/A',
            display: cardStyle.display,
            visibility: cardStyle.visibility,
            opacity: cardStyle.opacity,
            height: cardStyle.height,
            transform: cardStyle.transform
        });
    });
}

// Rendre les fonctions accessibles globalement
window.forceShowModal = forceShowModal;
window.diagnoseModal = diagnoseModal;

console.log('✅ [MODAL-FORCE-SHOW] Fonctions disponibles:');
console.log('  - forceShowModal() : Force l\'affichage du modal');
console.log('  - diagnoseModal() : Diagnostic complet du modal');


























