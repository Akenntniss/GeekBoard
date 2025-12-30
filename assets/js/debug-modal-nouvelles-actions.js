/**
 * Script de debug pour le modal nouvelles_actions_modal
 * Vérifie pourquoi le contenu n'apparaît pas sur la page d'accueil
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 [DEBUG-MODAL] Initialisation du debug du modal nouvelles_actions_modal...');
    
    // Vérifier l'existence du modal
    const modal = document.getElementById('nouvelles_actions_modal');
    console.log('🔍 [DEBUG-MODAL] Modal trouvé:', !!modal);
    
    if (modal) {
        console.log('🔍 [DEBUG-MODAL] Structure du modal:', {
            id: modal.id
            classes: modal.className
            children: modal.children.length
            innerHTML_length: modal.innerHTML.length
        
        // Vérifier le modal-body
        const modalBody = modal.querySelector('.modal-body');
        console.log('🔍 [DEBUG-MODAL] Modal body trouvé:', !!modalBody);
        
        if (modalBody) {
            console.log('🔍 [DEBUG-MODAL] Modal body:', {
                classes: modalBody.className
                children: modalBody.children.length
                innerHTML_length: modalBody.innerHTML.length
                display: getComputedStyle(modalBody).display
                visibility: getComputedStyle(modalBody).visibility
                opacity: getComputedStyle(modalBody).opacity
            
            // Vérifier les cartes d'action
            const actionCards = modalBody.querySelectorAll('.modern-action-card');
            console.log('🔍 [DEBUG-MODAL] Cartes d\'action trouvées:', actionCards.length);
            
            actionCards.forEach((card, index) => {
                const title = card.querySelector('.action-title');
                const titleText = title ? title.textContent : 'Pas de titre';
                console.log(`🔍 [DEBUG-MODAL] Carte ${index + 1}:`, {
                    title: titleText
                    classes: card.className
                    display: getComputedStyle(card).display
                    visibility: getComputedStyle(card).visibility
                    opacity: getComputedStyle(card).opacity
                    width: getComputedStyle(card).width
                    height: getComputedStyle(card).height
        }
        
        // Écouter l'ouverture du modal
        modal.addEventListener('show.bs.modal', function() {
            console.log('🚀 [DEBUG-MODAL] Modal en cours d\'ouverture...');
            
            setTimeout(() => {
                const modalBody = modal.querySelector('.modal-body');
                if (modalBody) {
                    console.log('🔍 [DEBUG-MODAL] État après ouverture:', {
                        modal_display: getComputedStyle(modal).display
                        modal_visibility: getComputedStyle(modal).visibility
                        modal_opacity: getComputedStyle(modal).opacity
                        body_display: getComputedStyle(modalBody).display
                        body_visibility: getComputedStyle(modalBody).visibility
                        body_opacity: getComputedStyle(modalBody).opacity
                    
                    // FORCER L'AFFICHAGE SYSTÉMATIQUEMENT
                    console.log('🔧 [DEBUG-MODAL] Forçage de l\'affichage du modal body...');
                    modalBody.style.display = 'block !important';
                    modalBody.style.visibility = 'visible !important';
                    modalBody.style.opacity = '1 !important';
                    modalBody.style.height = 'auto !important';
                    modalBody.style.overflow = 'visible !important';
                    
                    // Vérifier et forcer l'affichage des cartes d'action
                    const actionCards = modalBody.querySelectorAll('.modern-action-card');
                    console.log(`🔍 [DEBUG-MODAL] Forçage de ${actionCards.length} cartes d'action...`);
                    
                    actionCards.forEach((card, index) => {
                        console.log(`🔧 [DEBUG-MODAL] Forçage carte ${index + 1}...`);
                        card.style.display = 'block !important';
                        card.style.visibility = 'visible !important';
                        card.style.opacity = '1 !important';
                        card.style.height = 'auto !important';
                        card.style.width = 'auto !important';
                        card.style.position = 'relative !important';
                    
                    // Forcer l'affichage de la grille d'actions
                    const actionsGrid = modalBody.querySelector('.modern-actions-grid');
                    if (actionsGrid) {
                        console.log('🔧 [DEBUG-MODAL] Forçage de la grille d\'actions...');
                        actionsGrid.style.display = 'block !important';
                        actionsGrid.style.visibility = 'visible !important';
                        actionsGrid.style.opacity = '1 !important';
                    }
                }
            }, 100);
        
        modal.addEventListener('shown.bs.modal', function() {
            console.log('✅ [DEBUG-MODAL] Modal complètement ouvert');
    }
    
    // Vérifier les conflits CSS
    const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
    console.log('🔍 [DEBUG-MODAL] Feuilles de style chargées:', stylesheets.length);
    
    stylesheets.forEach((sheet, index) => {
        if (sheet.href && sheet.href.includes('modal')) {
            console.log(`🔍 [DEBUG-MODAL] CSS Modal ${index + 1}:`, sheet.href);
        }
    
    // Fonction de debug accessible globalement
    window.debugModalNouvelles = function() {
        const modal = document.getElementById('nouvelles_actions_modal');
        if (modal) {
            console.log('🔧 [DEBUG-MODAL] Debug manuel du modal...');
            
            // Forcer l'affichage de tous les éléments
            const modalBody = modal.querySelector('.modal-body');
            const actionCards = modal.querySelectorAll('.modern-action-card');
            const actionsGrid = modal.querySelector('.modern-actions-grid');
            
            console.log('🔍 [DEBUG-MODAL] Éléments trouvés:', {
                actionCards: actionCards.length
            
            if (modalBody) {
                modalBody.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; overflow: visible !important;';
                console.log('✅ [DEBUG-MODAL] Modal body forcé visible');
            }
            
            if (actionsGrid) {
                actionsGrid.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
                console.log('✅ [DEBUG-MODAL] Grille d\'actions forcée visible');
            }
            
            actionCards.forEach((card, index) => {
                card.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; width: auto !important; position: relative !important;';
                console.log(`✅ [DEBUG-MODAL] Carte ${index + 1} forcée visible`);
            
            console.log('✅ [DEBUG-MODAL] Correction forcée appliquée sur tous les éléments');
            
            // Ouvrir le modal si il n'est pas ouvert
            if (!modal.classList.contains('show')) {
                console.log('🚀 [DEBUG-MODAL] Ouverture du modal...');
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
            }
        } else {
            console.error('❌ [DEBUG-MODAL] Modal nouvelles_actions_modal non trouvé !');
        }
    };
    
    console.log('✅ [DEBUG-MODAL] Debug initialisé - Utilisez debugModalNouvelles() pour forcer l\'affichage');
