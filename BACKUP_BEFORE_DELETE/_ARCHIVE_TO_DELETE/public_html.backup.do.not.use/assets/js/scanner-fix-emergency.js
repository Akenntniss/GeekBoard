/**
 * Correctif d'urgence pour le scanner universel
 * Force l'attachement des événements si ils ne fonctionnent pas
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚨 [EMERGENCY-FIX] Correctif d\'urgence scanner activé...');
    
    // Attendre que tout soit chargé
    setTimeout(() => {
        console.log('🚨 [EMERGENCY-FIX] Application du correctif...');
        
        // Forcer l'attachement de l'événement au bouton scanner
        const scannerBtn = document.getElementById('openUniversalScanner');
        if (scannerBtn) {
            console.log('🚨 [EMERGENCY-FIX] Bouton scanner trouvé, force l\'événement...');
            
            // Supprimer tous les événements existants
            const newBtn = scannerBtn.cloneNode(true);
            scannerBtn.parentNode.replaceChild(newBtn, scannerBtn);
            
            // Attacher le nouvel événement
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🚨 [EMERGENCY-FIX] Clic sur scanner détecté!');
                
                try {
                    // Fermer le modal nouvelles actions
                    const nouvellesActionsModal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
                    if (nouvellesActionsModal) {
                        console.log('🚨 [EMERGENCY-FIX] Fermeture du modal nouvelles actions...');
                        nouvellesActionsModal.hide();
                    }
                    
                    // Ouvrir le modal scanner
                    setTimeout(() => {
                        console.log('🚨 [EMERGENCY-FIX] Ouverture du modal scanner...');
                        
                        const scannerModal = document.getElementById('universal_scanner_modal');
                        if (scannerModal) {
                            const modal = new bootstrap.Modal(scannerModal);
                            modal.show();
                            console.log('🚨 [EMERGENCY-FIX] ✅ Modal scanner ouvert!');
                            
                            // Démarrer le scanner après ouverture
                            setTimeout(() => {
                                if (typeof startUniversalScanner === 'function') {
                                    console.log('🚨 [EMERGENCY-FIX] Démarrage du scanner...');
                                    startUniversalScanner();
                                } else {
                                    console.warn('🚨 [EMERGENCY-FIX] Fonction startUniversalScanner non disponible');
                                }
                            }, 500);
                        } else {
                            console.error('🚨 [EMERGENCY-FIX] Modal scanner non trouvé!');
                        }
                    }, 300);
                    
                } catch (error) {
                    console.error('🚨 [EMERGENCY-FIX] Erreur:', error);
                }
            });
            
            console.log('🚨 [EMERGENCY-FIX] ✅ Événement forcé attaché au bouton scanner');
        } else {
            console.error('🚨 [EMERGENCY-FIX] ❌ Bouton scanner non trouvé!');
        }
        
        // Également attacher aux autres boutons scanner s'ils existent
        const otherScannerBtns = [
            'openUniversalScannerFromDesktop',
            'openUniversalScannerFromCircular'
        ];
        
        otherScannerBtns.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) {
                console.log(`🚨 [EMERGENCY-FIX] Bouton ${btnId} trouvé, force l'événement...`);
                
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log(`🚨 [EMERGENCY-FIX] Clic sur ${btnId} détecté!`);
                    
                    // Fermer le modal parent
                    const parentModalId = btnId.includes('Desktop') ? 'nouvelles_actions_modal_desktop' : 'nouvelles_actions_modal';
                    const parentModal = bootstrap.Modal.getInstance(document.getElementById(parentModalId));
                    if (parentModal) {
                        parentModal.hide();
                    }
                    
                    // Ouvrir le scanner
                    setTimeout(() => {
                        const scannerModal = new bootstrap.Modal(document.getElementById('universal_scanner_modal'));
                        scannerModal.show();
                        
                        setTimeout(() => {
                            if (typeof startUniversalScanner === 'function') {
                                startUniversalScanner();
                            }
                        }, 500);
                    }, 300);
                });
            }
        });
        
    }, 3000); // Attendre 3 secondes pour être sûr que tout est chargé
});

console.log('🚨 [EMERGENCY-FIX] Correctif d\'urgence scanner chargé');
