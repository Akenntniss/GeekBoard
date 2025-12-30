/**
 * Diagnostic pour les modals de stock QR
 * Vérifie pourquoi gbQRRepairModal n'est pas visible
 */

(function () {
    'use strict';

    console.log('🔍 [STOCK-MODAL-DEBUG] Script de diagnostic V3 (Temps Réel) chargé');

    // Intervalle de surveillance
    let monitorInterval = null;

    // Attendre que le DOM soit chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDiagnostic);
    } else {
        initDiagnostic();
    }

    function initDiagnostic() {
        // Démarrer la surveillance continue (toutes les 500ms)
        startMonitoring();

        // Exposer une fonction de test globale immédiate
        window.forceShowStockModal = function () {
            const modal = document.getElementById('gbQRRepairModal');
            if (modal) {
                modal.style.setProperty('display', 'flex', 'important');
                modal.style.setProperty('z-index', '20000', 'important');
                modal.style.setProperty('opacity', '1', 'important');
                modal.style.setProperty('visibility', 'visible', 'important');
                modal.classList.add('show');
                console.log('🔥 [STOCK-MODAL-DEBUG] Force Show appliqué sur #gbQRRepairModal');
            } else {
                console.error('❌ Modal introuvable');
            }
        };
    }

    function startMonitoring() {
        let lastState = '';

        monitorInterval = setInterval(() => {
            const modal = document.getElementById('gbQRRepairModal');
            if (!modal) return;

            const computed = window.getComputedStyle(modal);
            const currentState = `Display: ${computed.display}, Z-Index: ${computed.zIndex}, Opacity: ${computed.opacity}, Visibility: ${computed.visibility}, Classes: ${modal.className}`;

            // Loguer seulement si l'état change ou si le modal est censé être ouvert (classe show présente)
            if (modal.classList.contains('show')) {
                // Si c'est ouvert, on log systématiquement pour voir si ça clignote ou si des propriétés changent
                if (lastState !== currentState || Math.random() < 0.1) { // Log 10% du temps même si pas de changement pour heartbeat
                    console.log(`📊 [STOCK-MODAL-DEBUG-LIVE] gbQRRepairModal OPEN: ${currentState}`);

                    // Vérifier la position
                    const rect = modal.getBoundingClientRect();
                    if (rect.width === 0 || rect.height === 0) {
                        console.error('❌ [STOCK-MODAL-DEBUG-LIVE] Modal ouvert mais dimensions nulles !');
                    }
                }
                lastState = currentState;
            } else if (lastState.includes('show')) {
                console.log(`🔒 [STOCK-MODAL-DEBUG-LIVE] Modal fermé (classe .show retirée)`);
                lastState = currentState;
            }

        }, 500);
    }
})();
