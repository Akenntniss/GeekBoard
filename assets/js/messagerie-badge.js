/**
 * Script pour gérer le badge de notifications de la messagerie
 */

(function () {
    'use strict';

    /**
 * Met à jour le badge de notifications
 */
    function updateNotificationBadge() {
        fetch('messagerie/api/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const count = parseInt(data.count);

                // Liste des IDs de badges possibles
                const badgeIds = ['nav-messages-badge', 'nav-messages-badge-2', 'nav-messages-badge-modal'];

                badgeIds.forEach(id => {
                    const badge = document.getElementById(id);
                    if (badge) {
                        if (count > 0) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.classList.remove('d-none');
                            // Animation pulse si nouveau message
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
                        }
                    }
                });

                // Mettre à jour le titre de la page
                if (count > 0) {
                    if (!document.title.startsWith('(')) {
                        document.title = `(${count}) ${document.title}`;
                    } else {
                        document.title = `(${count}) ${document.title.split(') ')[1]}`;
                    }
                } else if (document.title.startsWith('(')) {
                    document.title = document.title.split(') ')[1];
                }
            })
            .catch(error => console.error('Erreur badge messagerie:', error));
    }

    // Mettre à jour au chargement de la page
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateNotificationBadge);
    } else {
        updateNotificationBadge();
    }

    // Mettre à jour toutes les 30 secondes
    setInterval(updateNotificationBadge, 30000);

    // Mettre à jour quand on revient sur l'onglet
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            updateNotificationBadge();
        }
    });
})();
