/**
 * Correction spécifique pour le modal nouvelles_actions_modal
 * Ce script remplace tous les autres scripts de correction des modales
 * pour éviter les conflits et assurer un fonctionnement optimal
 */

(function () {
    'use strict';

    let isInitialized = false;

    function initNewActionsModal() {
        if (isInitialized) return;

        // console.log('🔧 Initialisation spécifique du modal nouvelles_actions_modal');

        // Attendre que Bootstrap soit disponible
        if (typeof bootstrap === 'undefined') {
            setTimeout(initNewActionsModal, 500);
            return;
        }

        // Trouver le modal
        const modal = document.getElementById('nouvelles_actions_modal');
        if (!modal) {
            return;
        }

        // Trouver le bouton d'ouverture (tous sélecteurs possibles)
        const openButton = document.querySelector('#nouvelle-action-trigger, .btn-nouvelle-action, [data-bs-target="#nouvelles_actions_modal"]');
        if (!openButton) {
            return;
        }

        // Supprimer tous les gestionnaires d'événements existants
        const newButton = openButton.cloneNode(true);
        openButton.parentNode.replaceChild(newButton, openButton);

        // Créer l'instance Bootstrap du modal
        let modalInstance;
        try {
            modalInstance = new bootstrap.Modal(modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
        } catch (error) {
            return;
        }

        // Ajouter le gestionnaire d'événement au nouveau bouton
        newButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // console.log('🚀 Ouverture du modal nouvelles_actions_modal');

            try {
                // Nettoyer d'abord les éventuels backdrops résiduels
                const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                existingBackdrops.forEach(backdrop => backdrop.remove());

                // Réinitialiser l'état du body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';

                // Ouvrir le modal
                // Désactiver transitions CSS le temps de l'ouverture
                modal.classList.add('no-anim');
                modalInstance.show();
                // Retirer le flag juste après l'affichage
                setTimeout(() => modal.classList.remove('no-anim'), 50);

            } catch (error) {

                // Fallback : ouverture manuelle
                modal.classList.add('show');
                modal.style.display = 'block';
                modal.setAttribute('aria-modal', 'true');
                modal.removeAttribute('aria-hidden');

                // Créer le backdrop
                const backdrop = document.createElement('div');
                backdrop.classList.add('modal-backdrop', 'fade', 'show');
                document.body.appendChild(backdrop);

                // Empêcher le défilement
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';

                // console.log('✅ Modal ouvert manuellement (fallback)');
            }
        }); // Added closing parenthesis and semicolon

        // Gérer la mise à jour du bouton de pointage dynamique
        modal.addEventListener('show.bs.modal', function () {
            // Forcer le thème jour pour ce modal si le body n'est pas en night-mode
            const isNight = document.body.classList.contains('night-mode');
            if (!isNight) {
                modal.classList.remove('night');
            } else {
                modal.classList.add('night');
            }
            // Les boutons de pointage sont maintenant statiques dans le HTML
            // updateTimeTrackingButton();
        });
        // Fonction pour mettre à jour le bouton de pointage
        function updateTimeTrackingButton() {
            const dynamicButton = document.getElementById('dynamic-timetracking-button');
            if (!dynamicButton) return;

            // Afficher le chargement
            dynamicButton.innerHTML = `
            <div class="modern-action-card loading-card">
                <div class="card-glow"></div>
                <div class="action-icon-container">
                    <div class="action-icon bg-gradient-info">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="pulse-ring"></div>
                </div>
                <div class="action-content">
                    <h6 class="action-title">Chargement...</h6>
                    <p class="action-description">Vérification de l'état du pointage</p>
                </div>
            </div>`;

            // Utiliser l'API standard avec timeout
            const timeoutPromise = new Promise((_, reject) =>
                setTimeout(() => reject(new Error('Timeout - API ne répond pas')), 5000)
            );

            const fetchPromise = fetch('time_tracking_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_status'
            });

            Promise.race([fetchPromise, timeoutPromise])
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur réseau: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {

                    if (data && data.success && data.data) {
                        const state = data.data;

                        // Gérer le cas "no_entry" (aucun pointage)
                        if (state.status === 'no_entry') {
                            // Afficher bouton d'entrée pour commencer le pointage
                            dynamicButton.innerHTML = `
                                <button type="button" class="modern-action-card clock-in-card" onclick="modalClockIn()">
                                    <div class="card-glow"></div>
                                    <div class="action-icon-container">
                                        <div class="action-icon bg-gradient-success">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </div>
                                        <div class="pulse-ring"></div>
                                    </div>
                                    <div class="action-content">
                                        <h6 class="action-title">Pointage Arrivée</h6>
                                        <p class="action-description">Commencer votre journée de travail</p>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </button>
                            `;
                        } else {
                            // Gérer les autres statuts (active, completed, etc.)
                            const isClockedIn = state.is_clocked_in || state.status === 'active';
                            const buttonText = isClockedIn ? 'Pointage Départ' : 'Pointage Arrivée';
                            const iconClass = isClockedIn ? 'fas fa-sign-out-alt' : 'fas fa-sign-in-alt';
                            const gradientClass = isClockedIn ? 'bg-gradient-danger' : 'bg-gradient-success';
                            const cardClass = isClockedIn ? 'clock-out-card' : 'clock-in-card';
                            const onclickFunction = isClockedIn ? 'modalClockOut()' : 'modalClockIn()';

                            dynamicButton.innerHTML = `
                                <button type="button" class="modern-action-card ${cardClass}" onclick="${onclickFunction}">
                                    <div class="card-glow"></div>
                                    <div class="action-icon-container">
                                        <div class="action-icon ${gradientClass}">
                                            <i class="${iconClass}"></i>
                                        </div>
                                        <div class="pulse-ring"></div>
                                    </div>
                                    <div class="action-content">
                                        <h6 class="action-title">${buttonText}</h6>
                                        <p class="action-description">Système de pointage</p>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </button>
                            `;
                        }
                    } else {
                        // Afficher bouton de fallback
                        dynamicButton.innerHTML = generateFallbackButton();
                    }
                })
                .catch(error => {
                    // Afficher bouton de fallback en cas d'erreur
                    dynamicButton.innerHTML = generateFallbackButton();
                });
        }

        // Fonction pour générer un bouton de fallback
        function generateFallbackButton() {
            return `
            <button type="button" class="modern-action-card clock-in-card" onclick="modalClockIn()">
                <div class="card-glow"></div>
                <div class="action-icon-container">
                    <div class="action-icon bg-gradient-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="pulse-ring"></div>
                </div>
                <div class="action-content">
                    <h6 class="action-title">Pointage Arrivée</h6>
                    <p class="action-description">Commencer votre pointage (mode dégradé)</p>
                </div>
                <div class="action-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </button>`;
        }

        // Fonction utilitaire pour récupérer l'ID du magasin
        function getShopId() {
            // Essayer plusieurs méthodes pour récupérer l'ID du magasin
            const shopIdMeta = document.querySelector('meta[name="shop-id"]');
            if (shopIdMeta) return shopIdMeta.content;

            const shopIdInput = document.querySelector('input[name="shop_id"]');
            if (shopIdInput) return shopIdInput.value;

            // Récupérer depuis l'URL ou une variable globale
            if (window.shopId) return window.shopId;

            return null;
        }

        isInitialized = true;
    }

    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNewActionsModal);
    } else {
        initNewActionsModal();
    }

})();

/**
 * Fonctions globales pour le pointage
 * Définies ici pour garantir leur disponibilité même si modals.php a des erreurs JS
 */
window.modalClockIn = async function () {
    // console.log('🕒 Tentative de pointage entrée...');

    try {
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clock_in'
        });

        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }

        const data = await response.json();
        // console.log('✅ Réponse API:', data);

        if (data.success) {
            // Fermer le modal
            closeNewActionsModal();

            // Afficher un message de succès
            let message = '✅ Pointage d\'arrivée enregistré !';
            if (data.data.auto_approved) {
                message += '<br><small>🟢 Approuvé automatiquement</small>';
            } else {
                message += '<br><small>🟡 En attente d\'approbation</small>';
            }

            safeShowToast(message, data.data.auto_approved ? 'success' : 'warning');
        } else {
            throw new Error(data.message);
        }

    } catch (error) {
        console.error('❌ Erreur pointage:', error);
        safeShowToast('❌ Erreur: ' + error.message, 'error');
    }
};

window.modalClockOut = async function () {
    // console.log('🕒 Tentative de pointage sortie...');

    try {
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clock_out'
        });

        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }

        const data = await response.json();
        // console.log('✅ Réponse API:', data);

        if (data.success) {
            // Fermer le modal
            closeNewActionsModal();

            // Afficher un message de succès
            let message = '✅ Pointage de départ enregistré !';
            if (data.data.work_duration) {
                message += `<br><small>⏱️ Durée: ${data.data.work_duration}h</small>`;
            }

            safeShowToast(message, 'success');
        } else {
            throw new Error(data.message);
        }

    } catch (error) {
        console.error('❌ Erreur pointage:', error);
        safeShowToast('❌ Erreur: ' + error.message, 'error');
    }
};

// Fonction utilitaire pour fermer le modal proprement
function closeNewActionsModal() {
    const modal = document.getElementById('nouvelles_actions_modal');
    if (modal) {
        // Essayer via Bootstrap
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
                return;
            }
        }

        // Fallback manuel
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');

        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();

        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
}

// Fonction utilitaire pour afficher des toasts de manière sécurisée
function safeShowToast(message, type) {
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else if (typeof toastr !== 'undefined') {
        toastr[type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info')](message);
    } else {
        alert(message.replace(/<br>/g, '\n').replace(/<[^>]*>/g, ''));
    }
}
