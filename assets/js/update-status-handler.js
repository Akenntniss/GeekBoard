/**
 * GESTIONNAIRE POUR LE MODAL UPDATE STATUS
 * Gère les clics sur le bouton de mise à jour par lots
 * Avec envoi SMS asynchrone par tranches de 10 secondes
 */

document.addEventListener('DOMContentLoaded', function () {
    // console.log('🔄 update-status-handler.js chargé');

    // Créer le loader overlay s'il n'existe pas
    function createLoaderOverlay() {
        if (document.getElementById('bulk-update-loader')) return;

        const overlay = document.createElement('div');
        overlay.id = 'bulk-update-loader';
        overlay.innerHTML = `
            <div class="bulk-loader-content">
                <div class="bulk-loader-spinner"></div>
                <div class="bulk-loader-title">Mise à jour en cours...</div>
                <div class="bulk-loader-message" id="bulk-loader-message">Préparation des réparations</div>
                <div class="bulk-loader-progress" id="bulk-loader-progress"></div>
            </div>
        `;
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            backdrop-filter: blur(5px);
        `;

        const style = document.createElement('style');
        style.textContent = `
            .bulk-loader-content {
                text-align: center;
                color: white;
                padding: 40px;
                border-radius: 20px;
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                max-width: 400px;
            }
            .bulk-loader-spinner {
                width: 60px;
                height: 60px;
                border: 4px solid rgba(255, 255, 255, 0.2);
                border-top-color: #4ade80;
                border-radius: 50%;
                animation: bulk-spin 1s linear infinite;
                margin: 0 auto 20px;
            }
            @keyframes bulk-spin {
                to { transform: rotate(360deg); }
            }
            .bulk-loader-title {
                font-size: 1.5rem;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .bulk-loader-message {
                font-size: 1rem;
                color: #a0aec0;
                margin-bottom: 15px;
            }
            .bulk-loader-progress {
                font-size: 0.9rem;
                color: #4ade80;
            }
            .bulk-loader-success {
                background: linear-gradient(135deg, #065f46 0%, #047857 100%) !important;
            }
            .bulk-loader-success .bulk-loader-spinner {
                display: none;
            }
            .bulk-loader-success .bulk-loader-icon {
                font-size: 50px;
                color: #4ade80;
                margin-bottom: 20px;
            }
            .bulk-loader-btn {
                margin-top: 20px;
                padding: 12px 30px;
                background: #4ade80;
                color: #000;
                border: none;
                border-radius: 10px;
                font-size: 1rem;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.3s;
            }
            .bulk-loader-btn:hover {
                background: #22c55e;
                transform: scale(1.05);
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(overlay);
    }

    function showLoader(message) {
        createLoaderOverlay();
        const loader = document.getElementById('bulk-update-loader');
        const msgEl = document.getElementById('bulk-loader-message');
        if (loader) {
            loader.style.display = 'flex';
            if (msgEl) msgEl.textContent = message || 'Traitement en cours...';
        }
    }

    function updateLoaderMessage(message) {
        const msgEl = document.getElementById('bulk-loader-message');
        if (msgEl) msgEl.textContent = message;
    }

    function updateLoaderProgress(progress) {
        const progressEl = document.getElementById('bulk-loader-progress');
        if (progressEl) progressEl.textContent = progress;
    }

    function showSuccessModal(message, smsQueued) {
        const loader = document.getElementById('bulk-update-loader');
        if (!loader) return;

        const content = loader.querySelector('.bulk-loader-content');

        // Si pas de SMS à envoyer, afficher directement le succès
        if (smsQueued <= 0) {
            content.classList.add('bulk-loader-success');
            content.innerHTML = `
                <div class="bulk-loader-icon animate-success">✅</div>
                <div class="bulk-loader-title">Mise à jour réussie !</div>
                <div class="bulk-loader-message">${message}</div>
                <button class="bulk-loader-btn" id="bulk-loader-ok-btn">OK</button>
            `;
            document.getElementById('bulk-loader-ok-btn').addEventListener('click', function () {
                hideLoader();
                const modal = bootstrap.Modal.getInstance(document.getElementById('updateStatusModal'));
                if (modal) modal.hide();
                window.location.reload();
            });
            return;
        }

        // Animation d'envoi des SMS avec décompte
        const totalSeconds = smsQueued * 10; // 10 secondes par SMS
        let currentSms = 0;
        let remainingSeconds = totalSeconds;

        // Ajouter les styles d'animation avancés
        if (!document.getElementById('sms-animation-styles')) {
            const animStyles = document.createElement('style');
            animStyles.id = 'sms-animation-styles';
            animStyles.textContent = `
                @keyframes pulse-ring {
                    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
                    70% { transform: scale(1); box-shadow: 0 0 0 15px rgba(74, 222, 128, 0); }
                    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
                }
                @keyframes float-phone {
                    0%, 100% { transform: translateY(0) rotate(0deg); }
                    25% { transform: translateY(-8px) rotate(-5deg); }
                    75% { transform: translateY(-8px) rotate(5deg); }
                }
                @keyframes progress-glow {
                    0%, 100% { box-shadow: 0 0 10px rgba(74, 222, 128, 0.5); }
                    50% { box-shadow: 0 0 25px rgba(74, 222, 128, 0.9); }
                }
                @keyframes count-pop {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.3); }
                    100% { transform: scale(1); }
                }
                @keyframes success-bounce {
                    0%, 100% { transform: scale(1); }
                    25% { transform: scale(1.2); }
                    50% { transform: scale(0.9); }
                    75% { transform: scale(1.1); }
                }
                .sms-animation-container {
                    text-align: center;
                    padding: 30px;
                }
                .sms-phone-icon {
                    font-size: 60px;
                    animation: float-phone 2s ease-in-out infinite;
                    margin-bottom: 20px;
                }
                .sms-title {
                    font-size: 1.4rem;
                    font-weight: bold;
                    color: #4ade80;
                    margin-bottom: 15px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .sms-counter {
                    font-size: 2.5rem;
                    font-weight: bold;
                    color: white;
                    margin-bottom: 10px;
                }
                .sms-counter .current {
                    color: #4ade80;
                    display: inline-block;
                }
                .sms-counter .current.pop {
                    animation: count-pop 0.3s ease-out;
                }
                .sms-progress-bar {
                    width: 100%;
                    height: 8px;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 10px;
                    overflow: hidden;
                    margin: 20px 0;
                }
                .sms-progress-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #4ade80 0%, #22c55e 100%);
                    border-radius: 10px;
                    transition: width 1s linear;
                    animation: progress-glow 1.5s ease-in-out infinite;
                }
                .sms-timer {
                    font-size: 1.2rem;
                    color: #fbbf24;
                    margin-top: 15px;
                }
                .sms-timer-value {
                    font-weight: bold;
                    font-size: 1.5rem;
                }
                .animate-success {
                    animation: success-bounce 0.5s ease-out;
                }
            `;
            document.head.appendChild(animStyles);
        }

        // Afficher le modal d'animation
        content.classList.remove('bulk-loader-success');
        content.innerHTML = `
            <div class="sms-animation-container">
                <div class="sms-phone-icon">📱</div>
                <div class="sms-title">Mise à jour en cours</div>
                <div class="sms-counter">
                    Envoi des SMS : <span class="current" id="sms-current">0</span> / ${smsQueued}
                </div>
                <div class="sms-progress-bar">
                    <div class="sms-progress-fill" id="sms-progress-fill" style="width: 0%"></div>
                </div>
                <div class="sms-timer">
                    ⏱️ <span class="sms-timer-value" id="sms-timer">${remainingSeconds}</span> secondes restantes
                </div>
            </div>
        `;

        const currentEl = document.getElementById('sms-current');
        const progressEl = document.getElementById('sms-progress-fill');
        const timerEl = document.getElementById('sms-timer');

        // Intervalle pour le décompte
        const countdownInterval = setInterval(() => {
            remainingSeconds--;

            // Mettre à jour le timer
            if (timerEl) timerEl.textContent = remainingSeconds;

            // Calculer le SMS actuel (1 SMS toutes les 10 secondes)
            const newCurrentSms = Math.min(smsQueued, Math.floor((totalSeconds - remainingSeconds) / 10) + (remainingSeconds % 10 === 0 ? 0 : 1));

            if (newCurrentSms !== currentSms) {
                currentSms = newCurrentSms;
                if (currentEl) {
                    currentEl.textContent = currentSms;
                    currentEl.classList.remove('pop');
                    void currentEl.offsetWidth; // Forcer le reflow
                    currentEl.classList.add('pop');
                }
            }

            // Mettre à jour la barre de progression
            const progressPercent = ((totalSeconds - remainingSeconds) / totalSeconds) * 100;
            if (progressEl) progressEl.style.width = progressPercent + '%';

            // Quand le décompte est terminé
            if (remainingSeconds <= 0) {
                clearInterval(countdownInterval);

                // Afficher le succès final
                setTimeout(() => {
                    content.classList.add('bulk-loader-success');
                    content.innerHTML = `
                        <div class="bulk-loader-icon animate-success">✅</div>
                        <div class="bulk-loader-title">Mise à jour terminée !</div>
                        <div class="bulk-loader-message">${message}</div>
                        <div class="bulk-loader-progress" style="color: #4ade80; font-size: 1.1rem; margin-bottom: 15px;">
                            📱 ${smsQueued} SMS envoyé(s) avec succès
                        </div>
                        <button class="bulk-loader-btn" id="bulk-loader-ok-btn">OK</button>
                    `;

                    document.getElementById('bulk-loader-ok-btn').addEventListener('click', function () {
                        hideLoader();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('updateStatusModal'));
                        if (modal) modal.hide();
                        window.location.reload();
                    });
                }, 500);
            }
        }, 1000);
    }

    function hideLoader() {
        const loader = document.getElementById('bulk-update-loader');
        if (loader) {
            loader.style.display = 'none';
            // Réinitialiser le contenu
            const content = loader.querySelector('.bulk-loader-content');
            if (content) {
                content.classList.remove('bulk-loader-success');
                content.innerHTML = `
                    <div class="bulk-loader-spinner"></div>
                    <div class="bulk-loader-title">Mise à jour en cours...</div>
                    <div class="bulk-loader-message" id="bulk-loader-message">Préparation des réparations</div>
                    <div class="bulk-loader-progress" id="bulk-loader-progress"></div>
                `;
            }
        }
    }

    // Attendre que le bouton soit disponible (max 5 tentatives)
    let attachAttempts = 0;
    const maxAttempts = 5;

    function attachUpdateHandler() {
        const updateBtn = document.getElementById('update-selected-repairs');

        if (!updateBtn) {
            attachAttempts++;
            if (attachAttempts < maxAttempts) {
                setTimeout(attachUpdateHandler, 500);
            }
            // Silencieux - pas de log si le bouton n'existe pas sur cette page
            return;
        }

        // console.log('✅ Bouton #update-selected-repairs trouvé, attaching handler...');

        updateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // console.log('🔄 Bouton "Mettre à jour" cliqué');

            // Récupérer toutes les checkboxes cochées
            const selectedCheckboxes = document.querySelectorAll('.repair-checkbox:checked');

            if (selectedCheckboxes.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Veuillez sélectionner au moins une réparation à mettre à jour');
                } else {
                    alert('Veuillez sélectionner au moins une réparation à mettre à jour');
                }
                return;
            }

            // Récupérer le nouveau statut sélectionné
            const newStatusSelect = document.getElementById('new-status-select');
            if (!newStatusSelect || !newStatusSelect.value) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Veuillez sélectionner un nouveau statut');
                } else {
                    alert('Veuillez sélectionner un nouveau statut');
                }
                return;
            }

            const newStatus = newStatusSelect.value;
            const sendSMS = document.getElementById('send-sms-checkbox')?.checked || false;

            // Collecter les IDs des réparations sélectionnées
            const repairIds = [];
            selectedCheckboxes.forEach(checkbox => {
                repairIds.push(checkbox.value);
            });

            // console.log('📋 Réparations sélectionnées:', repairIds);
            // console.log('🆕 Nouveau statut:', newStatus);
            // console.log('📱 Envoyer SMS:', sendSMS);

            // Afficher le loader fullscreen
            showLoader(`Mise à jour de ${repairIds.length} réparation(s)...`);
            updateBtn.disabled = true;

            // Envoyer la requête AJAX
            fetch('ajax/update_bulk_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    repair_ids: repairIds,
                    new_status: newStatus,
                    send_sms: sendSMS
                })
            })
                .then(response => {
                    if (!response.ok) {
                        console.error('❌ Erreur HTTP:', response.status, response.statusText);
                    }
                    return response.text();
                })
                .then(text => {
                    // console.log('📥 Réponse brute du serveur:', text);

                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('❌ Erreur de parsing JSON:', e);
                        throw new Error('Réponse invalide du serveur');
                    }

                    // console.log('✅ Réponse serveur:', data);

                    if (data.success) {
                        // Afficher le modal de succès avec bouton OK
                        showSuccessModal(
                            data.message || 'Statuts mis à jour avec succès',
                            data.sms_queued || 0
                        );
                    } else {
                        hideLoader();
                        if (typeof toastr !== 'undefined') {
                            toastr.error(data.message || 'Erreur lors de la mise à jour');
                        } else {
                            alert(data.message || 'Erreur lors de la mise à jour');
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Erreur:', error);
                    hideLoader();
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Une erreur est survenue lors de la mise à jour');
                    } else {
                        alert('Une erreur est survenue lors de la mise à jour');
                    }
                })
                .finally(() => {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = '<i class="fas fa-save"></i> Mettre à jour';
                });
        });

        // console.log('✅ Gestionnaire de clic attaché au bouton #update-selected-repairs');
    }

    // Lancer l'attachement
    attachUpdateHandler();
});
