/**
 * Gestion des transitions entre modals
 * Ferme le modal nouvelles_actions_modal quand on ouvre un autre modal
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 Initialisation des transitions entre modals');

    const actionsModalEl = document.getElementById('nouvelles_actions_modal');
    const openOrderBtn = document.getElementById('openNewOrderFromActions');
    const openTaskBtn = document.getElementById('openNewTaskFromActions');

    function closeActionsAndOpen(targetId) {
        if (!actionsModalEl) return;
        const actionsInstance = bootstrap.Modal.getInstance(actionsModalEl) || new bootstrap.Modal(actionsModalEl);

        // Permettre la fermeture malgré le guard
        actionsModalEl.dataset.allowHide = '1';

        // Quand le modal d'actions est vraiment fermé, ouvrir la cible à la manière de chooseStatusModal
        const onHidden = function() {
            actionsModalEl.removeEventListener('hidden.bs.modal', onHidden);
            delete actionsModalEl.dataset.allowHide;

            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                console.log(`📋 Modal cible trouvé:`, targetEl);
                console.log(`📋 Classes avant ouverture:`, targetEl.className);
                console.log(`📋 Style display avant ouverture:`, targetEl.style.display);
                
                try {
                    const targetInstance = new bootstrap.Modal(targetEl);
                    targetInstance.show();
                    console.log(`✅ Modal ${targetId} ouvert`);
                    
                    // Forcer l'affichage du modal si nécessaire
                    setTimeout(() => {
                        const hasCorrectDimensions = targetEl.offsetWidth > 0 && targetEl.offsetHeight > 0;
                        
                        if (!targetEl.classList.contains('show') || targetEl.style.display !== 'block' || !hasCorrectDimensions) {
                            console.warn(`⚠️ Modal ${targetId} pas visible, correction forcée`);
                            console.warn(`  - Classes: ${targetEl.classList.contains('show')}`);
                            console.warn(`  - Display: ${targetEl.style.display}`);
                            console.warn(`  - Dimensions: ${targetEl.offsetWidth}x${targetEl.offsetHeight}`);
                            
                            // Corrections de base
                            targetEl.style.display = 'block';
                            targetEl.classList.add('show');
                            targetEl.setAttribute('aria-hidden', 'false');
                            targetEl.setAttribute('aria-modal', 'true');
                            
                            // CORRECTION CRITIQUE : Forcer les dimensions
                            if (!hasCorrectDimensions) {
                                console.warn(`🔧 Correction des dimensions pour ${targetId}`);
                                targetEl.style.width = '100vw';
                                targetEl.style.height = '100vh';
                                targetEl.style.minWidth = '100vw';
                                targetEl.style.minHeight = '100vh';
                                
                                const dialog = targetEl.querySelector('.modal-dialog');
                                if (dialog) {
                                    dialog.style.width = '90%';
                                    dialog.style.minWidth = '800px';
                                    dialog.style.height = 'auto';
                                    dialog.style.minHeight = '500px';
                                }
                                
                                const content = targetEl.querySelector('.modal-content');
                                if (content) {
                                    content.style.width = '100%';
                                    content.style.height = 'auto';
                                    content.style.minHeight = '400px';
                                    content.style.minWidth = '600px';
                                }
                            }
                            
                            // Ajouter la classe modal-open au body si nécessaire
                            if (!document.body.classList.contains('modal-open')) {
                                document.body.classList.add('modal-open');
                            }
                        }
                        
                        console.log(`🔍 Vérification post-ouverture:`, {
                            classes: targetEl.className,
                            display: targetEl.style.display,
                            visible: targetEl.classList.contains('show'),
                            zIndex: window.getComputedStyle(targetEl).zIndex
                        });
                    }, 500);
                    
                } catch (error) {
                    console.error(`❌ Erreur lors de l'ouverture de ${targetId}:`, error);
                }
            } else {
                console.error('❌ Modal cible introuvable:', targetId);
            }
        };

        actionsModalEl.addEventListener('hidden.bs.modal', onHidden);
        actionsInstance.hide();
    }

    if (openOrderBtn) {
        openOrderBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ Transition: ouvrir ajouterCommandeModal');
            closeActionsAndOpen('ajouterCommandeModal');
        });
    }

    if (openTaskBtn) {
        openTaskBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ Transition: ouvrir ajouterTacheModal');
            closeActionsAndOpen('ajouterTacheModal');
        });
    }

    console.log('✅ Transitions entre modals initialisées');
});
