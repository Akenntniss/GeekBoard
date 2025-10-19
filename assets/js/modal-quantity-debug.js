/**
 * Script de diagnostic pour le problème d'affichage des quantités dans le modal
 */

console.log('🔧 [MODAL-DEBUG] Script de diagnostic chargé');

// Fonction pour diagnostiquer l'état du modal
function diagnoseModalState() {
    console.log('🔍 [MODAL-DEBUG] === DIAGNOSTIC COMPLET ===');
    
    const modal = document.getElementById('productInfoModal');
    const quantityDisplay = document.getElementById('current_stock_display');
    const quantityInput = document.getElementById('product_current_quantity');
    const decreaseBtn = document.getElementById('decrease_stock_quantity');
    const increaseBtn = document.getElementById('increase_stock_quantity');
    
    console.log('📋 [MODAL-DEBUG] Éléments DOM:', {
        modal: !!modal,
        quantityDisplay: !!quantityDisplay,
        quantityInput: !!quantityInput,
        decreaseBtn: !!decreaseBtn,
        increaseBtn: !!increaseBtn
    });
    
    if (quantityDisplay) {
        console.log('📊 [MODAL-DEBUG] État quantityDisplay:', {
            textContent: quantityDisplay.textContent,
            innerText: quantityDisplay.innerText,
            innerHTML: quantityDisplay.innerHTML,
            className: quantityDisplay.className,
            style: quantityDisplay.style.cssText,
            computedStyle: window.getComputedStyle(quantityDisplay).display
        });
    }
    
    if (quantityInput) {
        console.log('📊 [MODAL-DEBUG] État quantityInput:', {
            value: quantityInput.value,
            type: quantityInput.type,
            style: quantityInput.style.cssText
        });
    }
    
    // Vérifier s'il y a des modals dupliqués
    const allModals = document.querySelectorAll('#productInfoModal');
    console.log('🔍 [MODAL-DEBUG] Nombre de modals trouvés:', allModals.length);
    
    if (allModals.length > 1) {
        console.warn('⚠️ [MODAL-DEBUG] PROBLÈME: Plusieurs modals détectés!');
        allModals.forEach((modal, index) => {
            console.log(`Modal ${index + 1}:`, {
                display: window.getComputedStyle(modal).display,
                visibility: window.getComputedStyle(modal).visibility,
                zIndex: window.getComputedStyle(modal).zIndex
            });
        });
    }
}

// Fonction pour forcer la mise à jour de l'affichage
function forceUpdateDisplay(newValue) {
    console.log('🔄 [MODAL-DEBUG] Forçage mise à jour:', newValue);
    
    const quantityDisplay = document.getElementById('current_stock_display');
    const quantityInput = document.getElementById('product_current_quantity');
    
    if (quantityDisplay && quantityInput) {
        // Méthode 1: Mise à jour directe
        quantityDisplay.textContent = newValue;
        quantityDisplay.innerText = newValue;
        quantityInput.value = newValue;
        
        // Méthode 2: Forcer le re-rendu
        quantityDisplay.style.display = 'none';
        quantityDisplay.offsetHeight; // Force reflow
        quantityDisplay.style.display = '';
        
        // Méthode 3: Déclencher des événements
        quantityDisplay.dispatchEvent(new Event('change'));
        quantityInput.dispatchEvent(new Event('input'));
        
        // Méthode 4: Mise à jour avec délai
        setTimeout(() => {
            quantityDisplay.textContent = newValue;
            quantityDisplay.innerHTML = newValue;
        }, 50);
        
        console.log('✅ [MODAL-DEBUG] Mise à jour forcée terminée');
        
        // Vérifier le résultat
        setTimeout(() => {
            console.log('🔍 [MODAL-DEBUG] Vérification post-mise à jour:', {
                textContent: quantityDisplay.textContent,
                innerText: quantityDisplay.innerText,
                inputValue: quantityInput.value
            });
        }, 100);
    } else {
        console.error('❌ [MODAL-DEBUG] Éléments non trouvés pour la mise à jour');
    }
}

// Intercepter les fonctions de quantité pour diagnostiquer
function interceptQuantityFunctions() {
    console.log('🔧 [MODAL-DEBUG] Interception des fonctions de quantité');
    
    // Sauvegarder les fonctions originales
    const originalDecrease = window.decreaseProductQuantity;
    const originalIncrease = window.increaseProductQuantity;
    
    if (originalDecrease) {
        window.decreaseProductQuantity = function() {
            console.log('🔍 [MODAL-DEBUG] === DECREASE INTERCEPTÉE ===');
            diagnoseModalState();
            
            const quantityInput = document.getElementById('product_current_quantity');
            const oldValue = quantityInput ? quantityInput.value : 'N/A';
            
            // Appeler la fonction originale
            originalDecrease();
            
            setTimeout(() => {
                const newValue = quantityInput ? quantityInput.value : 'N/A';
                console.log('📊 [MODAL-DEBUG] Changement decrease:', oldValue, '→', newValue);
                diagnoseModalState();
                
                // Si l'affichage ne s'est pas mis à jour, forcer
                const quantityDisplay = document.getElementById('current_stock_display');
                if (quantityDisplay && quantityDisplay.textContent !== newValue) {
                    console.warn('⚠️ [MODAL-DEBUG] Affichage non synchronisé, forçage...');
                    forceUpdateDisplay(newValue);
                }
            }, 100);
        };
    }
    
    if (originalIncrease) {
        window.increaseProductQuantity = function() {
            console.log('🔍 [MODAL-DEBUG] === INCREASE INTERCEPTÉE ===');
            diagnoseModalState();
            
            const quantityInput = document.getElementById('product_current_quantity');
            const oldValue = quantityInput ? quantityInput.value : 'N/A';
            
            // Appeler la fonction originale
            originalIncrease();
            
            setTimeout(() => {
                const newValue = quantityInput ? quantityInput.value : 'N/A';
                console.log('📊 [MODAL-DEBUG] Changement increase:', oldValue, '→', newValue);
                diagnoseModalState();
                
                // Si l'affichage ne s'est pas mis à jour, forcer
                const quantityDisplay = document.getElementById('current_stock_display');
                if (quantityDisplay && quantityDisplay.textContent !== newValue) {
                    console.warn('⚠️ [MODAL-DEBUG] Affichage non synchronisé, forçage...');
                    forceUpdateDisplay(newValue);
                }
            }, 100);
        };
    }
}

// Observer les changements dans le DOM pour détecter les nouveaux modals
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id === 'productInfoModal') {
                    console.log('🔍 [MODAL-DEBUG] Nouveau modal détecté!');
                    setTimeout(() => {
                        diagnoseModalState();
                        interceptQuantityFunctions();
                    }, 200);
                }
            });
        }
    });
});

// Démarrer l'observation
observer.observe(document.body, {
    childList: true,
    subtree: true
});

// Exposer les fonctions pour le debug manuel
window.diagnoseModalState = diagnoseModalState;
window.forceUpdateDisplay = forceUpdateDisplay;
window.interceptQuantityFunctions = interceptQuantityFunctions;

console.log('✅ [MODAL-DEBUG] Diagnostic prêt. Utilisez diagnoseModalState() pour diagnostiquer.');
