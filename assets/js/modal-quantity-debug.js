/**
 * Script de diagnostic pour le problème d'affichage des quantités dans le modal
 */

// Debug: Configuration supprimée);,

// Fonction pour diagnostiquer l'état du modal
function diagnoseModalState() {
    // Debug: Diagnostic supprimé);,
    
    const modal = document.getElementById('productInfoModal');
    const quantityDisplay = document.getElementById('current_stock_display');
    const quantityInput = document.getElementById('product_current_quantity');
    const decreaseBtn = document.getElementById('decrease_stock_quantity');
    const increaseBtn = document.getElementById('increase_stock_quantity');
    
    // Éléments trouvés: modal, quantityDisplay, quantityInput, decreaseBtn, increaseBtn
    
    if (quantityDisplay) {
    }
    
    if (quantityInput) {
    }
    
    // Vérifier s'il y a des modals dupliqués
    const allModals = document.querySelectorAll('#productInfoModal');
    // Debug: Diagnostic supprimé, allModals.length);
    
    if (allModals.length > 1) {
        allModals.forEach((modal, index) => {
            console.log(`Modal ${index}:`, {
                display: window.getComputedStyle(modal).display,
                visibility: window.getComputedStyle(modal).visibility,
                zIndex: window.getComputedStyle(modal).zIndex
            });
        });
    }

// Fonction pour forcer la mise à jour de l'affichage
function forceUpdateDisplay(newValue) {
    // Debug: Stacking supprimé, newValue);
    
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
        
        // Debug: Succès supprimé);,
        
        // Vérifier le résultat
        setTimeout(() => {
        }, 100);
    } else {
    }
}

// Intercepter les fonctions de quantité pour diagnostiquer
function interceptQuantityFunctions() {
    // Debug: Configuration supprimée);,
    
    // Sauvegarder les fonctions originales
    const originalDecrease = window.decreaseProductQuantity;
    const originalIncrease = window.increaseProductQuantity;
    
    if (originalDecrease) {
        window.decreaseProductQuantity = function() {
            // Debug: Diagnostic supprimé);,
            diagnoseModalState();
            
            const quantityInput = document.getElementById('product_current_quantity');
            const oldValue = quantityInput ? quantityInput.value : 'N/A';
            
            // Appeler la fonction originale
            originalDecrease();
            
            setTimeout(() => {
                const newValue = quantityInput ? quantityInput.value : 'N/A';
                // Stats debug supprimées, newValue);
                diagnoseModalState();
                
                // Si l'affichage ne s'est pas mis à jour, forcer
                const quantityDisplay = document.getElementById('current_stock_display');
                if (quantityDisplay && quantityDisplay.textContent !== newValue) {
                    forceUpdateDisplay(newValue);
                }
            }, 100);
        };
    }
    
    if (originalIncrease) {
        window.increaseProductQuantity = function() {
            // Debug: Diagnostic supprimé);,
            diagnoseModalState();
            
            const quantityInput = document.getElementById('product_current_quantity');
            const oldValue = quantityInput ? quantityInput.value : 'N/A';
            
            // Appeler la fonction originale
            originalIncrease();
            
            setTimeout(() => {
                const newValue = quantityInput ? quantityInput.value : 'N/A';
                // Stats debug supprimées, newValue);
                diagnoseModalState();
                
                // Si l'affichage ne s'est pas mis à jour, forcer
                const quantityDisplay = document.getElementById('current_stock_display');
                if (quantityDisplay && quantityDisplay.textContent !== newValue) {
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
                    // Debug: Diagnostic supprimé);,
                    setTimeout(() => {
                        diagnoseModalState();
                        interceptQuantityFunctions();
                    }, 200);
                }
        }

// Démarrer l'observation
observer.observe(document.body, {
    childList: true,
    subtree: true,

// Exposer les fonctions pour le debug manuel
window.diagnoseModalState = diagnoseModalState;
window.forceUpdateDisplay = forceUpdateDisplay;
window.interceptQuantityFunctions = interceptQuantityFunctions;

// Debug: Succès supprimé);,
