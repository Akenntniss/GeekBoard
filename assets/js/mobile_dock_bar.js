/*
 * Mobile Dock Bar - JavaScript
 * Gestion de la barre de navigation mobile avec auto-hide et gestes
 */

document.addEventListener('DOMContentLoaded', function () {
    // console.log('🚀 Mobile Dock Bar - Initialisé');

    const dockBar = document.getElementById('mobile_dock_bar');

    if (!dockBar) {
        console.warn('⚠️ Mobile Dock Bar non trouvé');
        return;
    }

    // Variables pour la gestion des états
    let hideTimer = null;
    let lastScrollY = window.scrollY;
    let isVisible = true;
    let userInteracted = false;
    let touchStartY = 0;
    let touchEndY = 0;

    // Configuration
    const HIDE_DELAY = 5000; // 5 secondes
    const SCROLL_THRESHOLD = 10; // Seuil de déclenchement du scroll
    const TOUCH_THRESHOLD = 50; // Seuil pour les gestes tactiles

    // Forcer la visibilité initiale
    dockBar.style.display = 'block';
    dockBar.style.visibility = 'visible';
    dockBar.style.opacity = '1';
    dockBar.style.position = 'fixed';
    dockBar.style.bottom = '0';
    dockBar.style.left = '0';
    dockBar.style.right = '0';
    dockBar.style.zIndex = '99999';
    dockBar.style.minHeight = '80px';

    // console.log('✅ Mobile Dock Bar - Configuration appliquée');
    // console.log('📊 Position:', dockBar.getBoundingClientRect());

    /**
     * Affiche le dock
     */
    function showDock() {
        if (!isVisible) {
            // console.log('📱 Showing mobile dock bar');
            dockBar.classList.remove('dock-bar-hidden');
            dockBar.classList.add('dock-bar-visible');
            // Ne plus modifier directement les styles, laisser le CSS gérer
            isVisible = true;
        }
        resetHideTimer();
    }

    /**
     * Cache le dock
     */
    function hideDock() {
        if (isVisible && userInteracted) {
            // console.log('📱 Hiding mobile dock bar (avec indicateur)');
            dockBar.classList.remove('dock-bar-visible');
            dockBar.classList.add('dock-bar-hidden');
            // Ne plus modifier directement les styles, laisser le CSS gérer
            isVisible = false;
        }
        clearTimeout(hideTimer);
    }

    /**
     * Réinitialise le timer de masquage automatique
     */
    function resetHideTimer() {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => {
            hideDock();
        }, HIDE_DELAY);
    }

    /**
     * Gestion du scroll
     */
    function handleScroll() {
        const currentScrollY = window.scrollY;
        const scrollDifference = Math.abs(currentScrollY - lastScrollY);

        if (scrollDifference > SCROLL_THRESHOLD) {
            userInteracted = true;

            if (currentScrollY > lastScrollY) {
                // Scroll vers le bas - cacher
                hideDock();
            } else {
                // Scroll vers le haut - afficher
                showDock();
            }
        }

        lastScrollY = currentScrollY;
    }

    /**
     * Gestion des événements tactiles
     */
    function handleTouchStart(e) {
        touchStartY = e.touches[0].clientY;
    }

    function handleTouchEnd(e) {
        touchEndY = e.changedTouches[0].clientY;
        const touchDifference = Math.abs(touchEndY - touchStartY);

        if (touchDifference > TOUCH_THRESHOLD) {
            userInteracted = true;

            if (touchEndY < touchStartY) {
                // Swipe vers le haut - afficher
                showDock();
            } else {
                // Swipe vers le bas - cacher
                hideDock();
            }
        }
    }

    // Event listeners
    window.addEventListener('scroll', handleScroll, { passive: true });
    document.addEventListener('touchstart', handleTouchStart, { passive: true });
    document.addEventListener('touchend', handleTouchEnd, { passive: true });

    // Clic sur la barre cachée pour la faire réapparaître
    dockBar.addEventListener('click', function (e) {
        if (!isVisible) {
            // console.log('📱 Clic sur indicateur - réaffichage de la barre');
            showDock();
            e.preventDefault();
            e.stopPropagation();
        }
    });

    // Gestion des clics sur les boutons
    const buttons = dockBar.querySelectorAll('.dock-bar-item');
    buttons.forEach(button => {
        button.addEventListener('click', function (e) {
            userInteracted = true;
            showDock(); // Réafficher et réinitialiser le timer

            // Si c'est un bouton modal, laisser Bootstrap gérer
            if (this.hasAttribute('data-bs-toggle')) {
                // console.log('🎯 Ouverture modal:', this.getAttribute('data-bs-target'));
                return;
            }

            // Pour les liens normaux, ajouter une petite animation
            if (this.tagName === 'A' && this.href) {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            }
        });
    });

    // Gestion des interactions générales
    document.addEventListener('click', () => {
        userInteracted = true;
        resetHideTimer();
    });

    document.addEventListener('keydown', () => {
        userInteracted = true;
        resetHideTimer();
    });

    // Gestion de la visibilité de la page
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            showDock();
        } else {
            clearTimeout(hideTimer);
        }
    });

    // Initialisation du timer
    resetHideTimer();

    // console.log('✅ Mobile Dock Bar - Événements configurés');

    // Debug final
    setTimeout(() => {
        // console.log('🔍 État final du dock bar:');
        // console.log('  - Display:', window.getComputedStyle(dockBar).display);
        // console.log('  - Position:', window.getComputedStyle(dockBar).position);
        // console.log('  - Bottom:', window.getComputedStyle(dockBar).bottom);
        // console.log('  - Z-index:', window.getComputedStyle(dockBar).zIndex);
        // console.log('  - Height:', window.getComputedStyle(dockBar).height);
        // console.log('  - Min-height:', window.getComputedStyle(dockBar).minHeight);
        // console.log('  - Rect:', dockBar.getBoundingClientRect());
    }, 1000);
});

// API globale pour debug
window.MobileDockBar = {
    show: function () {
        const dock = document.getElementById('mobile_dock_bar');
        if (dock) {
            dock.style.display = 'block';
            dock.style.opacity = '1';
            dock.style.visibility = 'visible';
            // console.log('✅ Mobile Dock Bar forcé visible');
        }
    },

    hide: function () {
        const dock = document.getElementById('mobile_dock_bar');
        if (dock) {
            dock.style.display = 'none';
            // console.log('❌ Mobile Dock Bar masqué');
        }
    },

    debug: function () {
        const dock = document.getElementById('mobile_dock_bar');
        if (dock) {
            // console.log('🔍 Mobile Dock Bar Debug:', {
            element: dock,
                computed: window.getComputedStyle(dock),
                    rect: dock.getBoundingClientRect(),
                        innerHTML: dock.innerHTML.substring(0, 200)
        });
    }
}
};
