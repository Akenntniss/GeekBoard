/**
 * Script pour forcer le rendu du modal ajouterCommandeModal
 * Résout le problème où les styles CSS sont corrects mais pas appliqués au rendu
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Script de forçage du rendu modal initialisé');
    
    // Fonction pour forcer le rendu d'un modal
    function forceModalRender(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`❌ Modal ${modalId} non trouvé`);
            return;
        }
        
        console.log(`🔧 Forçage du rendu pour ${modalId}`);
        
        // 1. Forcer le recalcul des styles
        modal.style.display = 'none';
        modal.offsetHeight; // Forcer le reflow
        modal.style.display = 'block';
        
        // 2. Forcer les dimensions explicitement
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.minWidth = '100vw';
        modal.style.minHeight = '100vh';
        modal.style.maxWidth = 'none';
        modal.style.maxHeight = 'none';
        
        // 3. Forcer la position
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.right = '0';
        modal.style.bottom = '0';
        
        // 4. Forcer la visibilité
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.zIndex = '9999';
        
        // 5. S'assurer que les enfants sont visibles (dimensions identiques à statut_rapide)
        const dialog = modal.querySelector('.modal-dialog');
        if (dialog) {
            dialog.style.display = 'flex';
            dialog.style.width = '95%';
            dialog.style.maxWidth = '1000px';
            dialog.style.height = 'auto';
            dialog.style.margin = '1.75rem auto';
            dialog.style.position = 'relative';
            dialog.style.zIndex = '10000';
        }
        
        const content = modal.querySelector('.modal-content');
        if (content) {
            content.style.display = 'flex';
            content.style.flexDirection = 'column';
            content.style.width = '100%';
            content.style.height = 'auto';
            content.style.border = '0';
            content.style.borderRadius = '1rem';
            content.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
            content.style.position = 'relative';
            content.style.zIndex = '10001';
            
            // Appliquer le style approprié selon le mode (clair/sombre)
            const isDarkMode = document.body.classList.contains('dark-mode');
            if (isDarkMode) {
                content.style.backgroundColor = '#111827';
                content.style.color = '#f9fafb';
            } else {
                content.style.backgroundColor = 'white';
                content.style.color = '#000';
            }
        }
        
        // 6. Forcer un nouveau reflow
        modal.offsetHeight;
        
        // 7. Vérifier le résultat
        setTimeout(() => {
            const rect = modal.getBoundingClientRect();
            const isVisible = modal.offsetWidth > 0 && modal.offsetHeight > 0;
            console.log(`📊 Résultat du forçage pour ${modalId}:`, {
                offsetWidth: modal.offsetWidth,
                offsetHeight: modal.offsetHeight,
                getBoundingClientRect: rect,
                visible: isVisible
            });
            
            if (!isVisible) {
                console.error(`❌ Forçage échoué pour ${modalId}`);
                // Dernière tentative : créer un nouveau modal visible
                createVisibleModal(modalId);
            } else {
                console.log(`✅ Forçage réussi pour ${modalId}`);
            }
        }, 100);
    }
    
    // Fonction de dernière chance : créer un modal visible de force
    function createVisibleModal(originalModalId) {
        console.log('🚨 Création d\'un modal de secours visible');
        
        const originalModal = document.getElementById(originalModalId);
        if (!originalModal) return;
        
        // Créer un overlay visible
        const overlay = document.createElement('div');
        overlay.id = 'emergency-modal-overlay';
        overlay.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.8) !important;
            z-index: 99999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        `;
        
        // Créer le contenu du modal (dimensions identiques à statut_rapide)
        const modalBox = document.createElement('div');
        const isDarkMode = document.body.classList.contains('dark-mode');
        modalBox.style.cssText = `
            background: ${isDarkMode ? '#111827' : 'white'} !important;
            color: ${isDarkMode ? '#f9fafb' : '#000'} !important;
            border: 0 !important;
            border-radius: 1rem !important;
            width: 95% !important;
            max-width: 1000px !important;
            height: auto !important;
            max-height: 80vh !important;
            padding: 20px !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            position: relative !important;
            overflow: auto !important;
        `;
        
        // Copier le contenu original
        const originalContent = originalModal.querySelector('.modal-content');
        if (originalContent) {
            modalBox.innerHTML = originalContent.innerHTML;
        } else {
            modalBox.innerHTML = `
                <h2 style="color: ${isDarkMode ? '#f9fafb' : '#000'};">Nouvelle commande de pièces</h2>
                <p>Modal de secours - Le contenu original sera chargé ici.</p>
                <button onclick="document.getElementById('emergency-modal-overlay').remove()" 
                        style="background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                    Fermer
                </button>
            `;
        }
        
        overlay.appendChild(modalBox);
        document.body.appendChild(overlay);
        
        // Supprimer automatiquement après 10 secondes
        setTimeout(() => {
            if (document.getElementById('emergency-modal-overlay')) {
                overlay.remove();
            }
        }, 10000);
        
        console.log('✅ Modal de secours créé et affiché');
    }
    
    // Écouter l'ouverture du modal ajouterCommandeModal
    const ajouterCommandeModal = document.getElementById('ajouterCommandeModal');
    if (ajouterCommandeModal) {
        ajouterCommandeModal.addEventListener('shown.bs.modal', function() {
            console.log('🚀 Modal ajouterCommandeModal ouvert, forçage du rendu...');
            setTimeout(() => {
                forceModalRender('ajouterCommandeModal');
            }, 50);
        });
    }
    
    // Fonction globale pour test manuel
    window.forceRenderModal = function(modalId) {
        forceModalRender(modalId || 'ajouterCommandeModal');
    };
    
    console.log('💡 Utilisez window.forceRenderModal() pour forcer le rendu manuellement');
});
