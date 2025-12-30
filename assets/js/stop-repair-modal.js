/**
 * Gestion du modal de confirmation d'arrêt de réparation
 * ET des actions de réparation (consolidé pour éviter les problèmes de chargement)
 */

// console.log('🛑 Script stop-repair-modal.js chargé (VERSION FLUX STANDARD: STOP -> STATUT -> PRIX)');

// Variable globale pour stocker l'ID de la nouvelle réparation à démarrer après l'arrêt
let pendingNewRepairId = null;

// =============================================================================
// 1. DÉFINITION DES FONCTIONS GLOBALES (CRITIQUE)
// =============================================================================

// Fonction pour démarrer une réparation
window.startRepairAction = function (repairId) {
    // console.log(`▶️ Tentative de démarrage de la réparation #${repairId}`);

    // Vérifier d'abord si l'utilisateur a déjà une réparation active
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'check_active_repair',
            reparation_id: repairId
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.has_active_repair && data.active_repair.id != repairId) {
                    // console.log(`⚠️ Réparation active détectée: #${data.active_repair.id}`);

                    // Au lieu d'afficher une erreur, ouvrir le modal d'arrêt
                    // Stocker l'ID de la nouvelle réparation à démarrer
                    pendingNewRepairId = repairId;

                    if (typeof showToast === 'function') {
                        showToast(`Vous avez une réparation active (#${data.active_repair.id}). Veuillez d'abord la terminer.`, 'warning');
                    }

                    // Ouvrir directement le modal d'arrêt avec l'ID de la réparation active
                    if (typeof openStopRepairModal === 'function') {
                        openStopRepairModal(data.active_repair.id);
                    } else {
                        // Fallback
                        const stopModal = document.getElementById('stopRepairConfirmModal');
                        if (stopModal) {
                            const modalInstance = new bootstrap.Modal(stopModal);
                            modalInstance.show();

                            const confirmBtn = document.getElementById('confirmStopRepairBtn');
                            if (confirmBtn) {
                                const newConfirmBtn = confirmBtn.cloneNode(true);
                                confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

                                newConfirmBtn.addEventListener('click', () => {
                                    const modalInstance = bootstrap.Modal.getInstance(stopModal);
                                    if (modalInstance) modalInstance.hide();

                                    // Ouvrir le modal de statut
                                    openStatusSelectionModal(data.active_repair.id);
                                });
                            }
                        }
                    }
                    return;
                } else {
                    // Pas de réparation active, démarrer normalement
                    window.assignRepairAction(repairId);
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Une erreur est survenue.', 'error');
                } else {
                    alert(data.message || 'Une erreur est survenue.');
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            if (typeof showToast === 'function') {
                showToast('Erreur de connexion', 'error');
            } else {
                alert('Erreur de connexion');
            }
        });
};

// Fonction pour terminer la réparation active
window.completeActiveRepair = function (repairId, finalStatus) {
    // console.log(`🏁 completeActiveRepair appelée pour #${repairId} avec statut ${finalStatus}`);

    // Vérifier si nous avons un statut
    if (!finalStatus) {
        if (typeof showToast === 'function') {
            showToast('Veuillez sélectionner un statut final', 'warning');
        } else {
            alert('Veuillez sélectionner un statut final');
        }
        return;
    }

    // VÉRIFICATION DU PRIX AVANT DE TERMINER
    // On ne vérifie le prix que si le statut est "reparation_effectue" (ou équivalent final)
    // et pas pour les statuts intermédiaires comme "en_attente_accord_client" ou "nouvelle_commande"
    // ID 8 = Réparation effectuée (généralement)
    const skipPriceCheckStatuses = ['en_attente_accord_client', 'nouvelle_commande', 'devis_refuse', 'irreparable', 'diagnostique_cours'];

    if (!skipPriceCheckStatuses.includes(finalStatus)) {
        // console.log('💰 Vérification du prix en cours...');

        // Récupérer les détails de la réparation pour vérifier le prix
        fetch(`ajax/get_repair_details.php?id=${repairId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const price = parseFloat(data.repair.montant || 0);
                    // console.log(`💰 Prix actuel: ${price}€`);

                    if (price === 0) {
                        // console.log('⚠️ Prix à 0€ détecté, ouverture du modal de vérification');

                        // Fermer le modal actif (activeRepairModal)
                        const activeRepairModalElement = document.getElementById('activeRepairModal');
                        if (activeRepairModalElement) {
                            const activeRepairModal = bootstrap.Modal.getInstance(activeRepairModalElement);
                            if (activeRepairModal) activeRepairModal.hide();
                        }

                        // Ouvrir le modal de vérification de prix
                        openPriceVerificationModal(repairId, finalStatus);
                        return;
                    } else {
                        // Prix OK, on continue
                        proceedWithCompletion(repairId, finalStatus);
                    }
                } else {
                    console.error('Erreur récupération détails:', data.message);
                    // En cas d'erreur, on continue quand même pour ne pas bloquer
                    proceedWithCompletion(repairId, finalStatus);
                }
            })
            .catch(error => {
                console.error('Erreur vérification prix:', error);
                // En cas d'erreur réseau, on continue quand même
                proceedWithCompletion(repairId, finalStatus);
            });

        return; // On arrête ici en attendant la vérification asynchrone
    }

    // Si pas de vérification de prix nécessaire
    proceedWithCompletion(repairId, finalStatus);
};

// Fonction interne pour exécuter la fin de réparation (après vérification prix)
function proceedWithCompletion(repairId, finalStatus) {
    // console.log(`🚀 Exécution de la fin de réparation #${repairId}`);

    // Si le statut est "en_attente_accord_client", ouvrir le modal d'envoi de devis
    if (finalStatus === 'en_attente_accord_client') {
        const activeRepairModalElement = document.getElementById('activeRepairModal');
        if (activeRepairModalElement) {
            const activeRepairModal = bootstrap.Modal.getInstance(activeRepairModalElement);
            if (activeRepairModal) activeRepairModal.hide();
        }

        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') showToast('Réparation terminée avec succès.', 'success');

                    if (pendingNewRepairId) {
                        const newRepairId = pendingNewRepairId;
                        pendingNewRepairId = null;
                        window.assignRepairAction(newRepairId);
                        return;
                    }

                    if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                        window.RepairModal.executeAction('devis', repairId);
                    } else {
                        setTimeout(() => {
                            const currentView = localStorage.getItem('repairViewMode') || 'cards';
                            window.location.href = `index.php?page=reparations&statut_ids=6,7,8&view=${currentView}`;
                        }, 1500);
                    }
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Erreur.', 'error');
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                window.location.reload();
            });
        return;
    }

    // Si le statut est "nouvelle_commande"
    if (finalStatus === 'nouvelle_commande') {
        const activeRepairModalElement = document.getElementById('activeRepairModal');
        if (activeRepairModalElement) {
            const activeRepairModal = bootstrap.Modal.getInstance(activeRepairModalElement);
            if (activeRepairModal) activeRepairModal.hide();
        }

        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') showToast('Réparation terminée avec succès.', 'success');

                    if (pendingNewRepairId) {
                        const newRepairId = pendingNewRepairId;
                        pendingNewRepairId = null;
                        window.assignRepairAction(newRepairId);
                        return;
                    }

                    if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                        window.RepairModal.executeAction('order', repairId);
                    } else {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Erreur.', 'error');
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                window.location.reload();
            });
        return;
    }

    // Cas par défaut (Réparation effectuée, etc.)
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'complete_active_repair',
            reparation_id: repairId,
            final_status: finalStatus
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const activeRepairModalElement = document.getElementById('activeRepairModal');
                if (activeRepairModalElement) {
                    const activeRepairModal = bootstrap.Modal.getInstance(activeRepairModalElement);
                    if (activeRepairModal) activeRepairModal.hide();
                }

                if (typeof showToast === 'function') showToast('Réparation terminée avec succès.', 'success');

                if (pendingNewRepairId) {
                    const newRepairId = pendingNewRepairId;
                    pendingNewRepairId = null;
                    window.assignRepairAction(newRepairId);
                } else {
                    window.location.reload();
                }
            } else {
                if (typeof showToast === 'function') showToast(data.message || 'Erreur.', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            if (typeof showToast === 'function') showToast('Erreur de connexion', 'error');
        });
}

// Fonction pour ouvrir le modal de vérification de prix
function openPriceVerificationModal(repairId, finalStatus) {
    const modal = document.getElementById('priceVerificationModal');
    if (!modal) {
        console.error('❌ Modal priceVerificationModal introuvable');
        proceedWithCompletion(repairId, finalStatus);
        return;
    }

    const confirmBtn = document.getElementById('confirmZeroPriceBtn');
    const updateBtn = document.getElementById('updatePriceAndFinishBtn');
    const priceInput = document.getElementById('verificationPriceInput');

    if (priceInput) priceInput.value = '';

    if (confirmBtn) {
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', function () {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();
            proceedWithCompletion(repairId, finalStatus);
        });
    }

    if (updateBtn && priceInput) {
        const newUpdateBtn = updateBtn.cloneNode(true);
        updateBtn.parentNode.replaceChild(newUpdateBtn, updateBtn);

        // Restaurer le texte original "Mettre à jour et terminer"
        newUpdateBtn.innerHTML = '<i class="fas fa-save me-2"></i>Mettre à jour et terminer';

        newUpdateBtn.addEventListener('click', function () {
            const newPrice = parseFloat(priceInput.value);
            if (isNaN(newPrice) || newPrice < 0) {
                if (typeof showToast === 'function') showToast('Veuillez entrer un prix valide', 'warning');
                return;
            }

            fetch('ajax/update_repair_price.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `repair_id=${repairId}&price=${newPrice}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showToast === 'function') showToast('Prix mis à jour avec succès', 'success');
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) modalInstance.hide();
                        proceedWithCompletion(repairId, finalStatus);
                    } else {
                        if (typeof showToast === 'function') showToast('Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    if (typeof showToast === 'function') showToast('Erreur de connexion', 'error');
                });
        });
    }

    const modalInstance = new bootstrap.Modal(modal, {
        backdrop: 'static',
        keyboard: false
    });
    modalInstance.show();
}

// Fonction pour attribuer une réparation
window.assignRepairAction = function (repairId) {
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'assign_repair',
            reparation_id: repairId
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof showToast === 'function') showToast('Réparation démarrée avec succès !', 'success');
                location.reload();
            } else {
                if (typeof showToast === 'function') showToast('Erreur : ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            if (typeof showToast === 'function') showToast('Erreur de connexion', 'error');
        });
};

// Fonction pour terminer une réparation active et en démarrer une nouvelle
window.completeActiveRepairAndStartNew = function (activeRepairId, newRepairId, finalStatus = 'reparation_effectue') {
    // console.log('🚀 completeActiveRepairAndStartNew appelée');

    const activeRepairModalElement = document.getElementById('activeRepairModal');
    if (activeRepairModalElement) {
        const activeRepairModal = bootstrap.Modal.getInstance(activeRepairModalElement);
        if (activeRepairModal) activeRepairModal.hide();
    }

    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'complete_active_repair',
            reparation_id: activeRepairId,
            final_status: finalStatus
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.assignRepairAction(newRepairId);
            } else {
                if (typeof showToast === 'function') showToast('Erreur : ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            if (typeof showToast === 'function') showToast('Erreur de connexion', 'error');
        });
};

// =============================================================================
// 2. LOGIQUE DU MODAL D'ARRÊT
// =============================================================================

document.addEventListener('DOMContentLoaded', function () {
    // console.log('🛑 DOM loaded, initialisation des boutons...');

    function attachRepairListeners() {
        // Boutons "Arrêter"
        const stopButtons = document.querySelectorAll('.stop-repair-btn');
        stopButtons.forEach(function (button) {
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            newButton.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const repairId = this.getAttribute('data-id');
                openStopRepairModal(repairId);
            });
        });

        // Boutons "Démarrer"
        const startButtons = document.querySelectorAll('.start-repair, .start-repair-btn');
        startButtons.forEach(function (button) {
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            newButton.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const repairId = this.getAttribute('data-id');
                if (typeof window.startRepairAction === 'function') {
                    window.startRepairAction(repairId);
                } else {
                    alert('Erreur: Fonction de démarrage non disponible');
                }
            });
        });
    }

    // Fonction pour ouvrir le modal de confirmation
    window.openStopRepairModal = function (repairId) {
        // console.log(`🚀 Ouverture modal confirmation pour #${repairId}`);

        const modal = document.getElementById('stopRepairConfirmModal');
        if (!modal) {
            alert('Erreur: Modal de confirmation introuvable');
            return;
        }

        const confirmBtn = document.getElementById('confirmStopRepairBtn');
        if (!confirmBtn) return;

        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', function () {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();

            // FLUX STANDARD : On ouvre le modal de statut après confirmation
            openStatusSelectionModal(repairId);
        });

        try {
            const modalInstance = new bootstrap.Modal(modal, {
                backdrop: 'static',
                keyboard: false
            });
            modalInstance.show();
        } catch (error) {
            console.error('❌ Erreur ouverture modal:', error);
        }
    };

    // Fonction pour ouvrir le modal de sélection de statut
    window.openStatusSelectionModal = function (repairId) {
        // console.log(`📋 Ouverture modal sélection statut pour #${repairId}`);

        const idElement = document.getElementById('activeRepairId');
        if (idElement) idElement.textContent = `#${repairId}`;

        const completeButtons = document.querySelectorAll("#activeRepairModal .complete-btn");
        completeButtons.forEach(function (button) {
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            newButton.addEventListener("click", function () {
                const status = this.getAttribute("data-status");
                if (typeof window.completeActiveRepair === 'function') {
                    window.completeActiveRepair(repairId, status);
                }
            });
        });

        const activeModal = document.getElementById('activeRepairModal');
        if (activeModal) {
            const modalInstance = new bootstrap.Modal(activeModal);
            modalInstance.show();
        }
    };

    attachRepairListeners();

    window.stopRepairAction = function (repairId) {
        openStopRepairModal(repairId);
    };

    // console.log('✅ Système initialisé');
});
