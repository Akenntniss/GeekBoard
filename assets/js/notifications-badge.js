/**
 * notifications-badge.js
 * Gère la mise à jour en temps réel du badge de notifications dans la navbar
 */

(function () {
    'use strict';

    /**
     * Met à jour les badges de notifications (Desktop et Mobile)
     */
    function updateNavbarNotifications() {
        // On utilise l'endpoint existant dans pages/notifications.php?action=get_unread_count
        // ou ajax/get_notifications.php si on veut plus de détails
        fetch('ajax/get_notifications.php')
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const count = parseInt(data.count);

                    // IDs des badges à mettre à jour
                    const badgeIds = ['nav-notifications-badge', 'nav-notifications-badge-mobile'];

                    badgeIds.forEach(id => {
                        const badge = document.getElementById(id);
                        if (badge) {
                            if (count > 0) {
                                badge.textContent = count > 99 ? '99+' : count;
                                badge.classList.remove('d-none');

                                // Animation si le compte a changé
                                if (badge.dataset.lastCount != count) {
                                    badge.classList.add('animate__animated', 'animate__pulse');
                                    setTimeout(() => {
                                        badge.classList.remove('animate__animated', 'animate__pulse');
                                    }, 1000);
                                    badge.dataset.lastCount = count;
                                }
                            } else {
                                badge.classList.add('d-none');
                                badge.textContent = '0';
                                badge.dataset.lastCount = 0;
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Erreur badge notifications:', error));
    }

    // Premier chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateNavbarNotifications);
    } else {
        updateNavbarNotifications();
    }

    // Polling toutes les 45 secondes (un peu plus long que la messagerie pour économiser les ressources)
    setInterval(updateNavbarNotifications, 45000);

    // Mise à jour au retour sur l'onglet
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            updateNavbarNotifications();
        }
    });

})();
