/*
 * Système d'auto-masquage et de gestes pour le dock mobile
 * - Cache automatiquement après 5 secondes d'inactivité
 * - Cache lors du scroll vers le bas
 * - Affiche lors du scroll vers le haut
 * - Gestion tactile pour les appareils mobiles
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎛️ Mobile dock auto-hide system initialized');
    
    // Nettoyer les anciens docks mobiles pour éviter les conflits
    cleanupOldMobileDocks();
    
    initializeMobileDockAutoHide();
});

/**
 * Supprime complètement les anciens docks mobiles
 */
function cleanupOldMobileDocks() {
    // Masquer et désactiver tous les anciens mobile-dock
    const oldDocks = document.querySelectorAll('#mobile-dock');
    oldDocks.forEach(dock => {
        if (dock.id !== 'mobile-dock-clean') {
            dock.style.display = 'none';
            dock.style.visibility = 'hidden';
            dock.style.opacity = '0';
            dock.style.height = '0';
            dock.style.overflow = 'hidden';
            dock.style.position = 'absolute';
            dock.style.left = '-9999px';
            dock.style.pointerEvents = 'none';
            dock.setAttribute('aria-hidden', 'true');
            
            // Supprimer du DOM si possible
            try {
                dock.remove();
                console.log('🗑️ Ancien mobile-dock supprimé du DOM');
            } catch (e) {
                console.log('⚠️ Impossible de supprimer l\'ancien dock, masqué à la place');
            }
        }
    });
    
    // S'assurer que mobile-dock-clean est le seul visible sur mobile
    const cleanDock = document.getElementById('mobile-dock-clean');
    console.log('🔍 Recherche de mobile-dock-clean:', cleanDock);
    
    if (cleanDock) {
        // Forcer l'affichage avec tous les styles possibles
        cleanDock.style.display = 'block';
        cleanDock.style.visibility = 'visible';
        cleanDock.style.position = 'fixed';
        cleanDock.style.bottom = '0';
        cleanDock.style.left = '0';
        cleanDock.style.right = '0';
        cleanDock.style.width = '100vw';
        cleanDock.style.height = 'auto';
        cleanDock.style.zIndex = '99999';
        cleanDock.style.opacity = '1';
        cleanDock.style.transform = 'translateY(0)';
        cleanDock.style.pointerEvents = 'auto';
        // cleanDock.style.background = 'rgba(255, 0, 0, 0.3)'; // Debug rouge retiré
        
        // Forcer aussi les classes Bootstrap
        cleanDock.classList.remove('d-none', 'd-lg-none');
        cleanDock.classList.add('d-block');
        
        console.log('✅ mobile-dock-clean forcé visible');
        console.log('📊 Styles appliqués:', {
            display: cleanDock.style.display,
            visibility: cleanDock.style.visibility,
            position: cleanDock.style.position,
            zIndex: cleanDock.style.zIndex
        });
        } else {
            // L'ancien mobile-dock-clean n'existe plus, c'est normal avec le nouveau système
            console.log('ℹ️ Ancien mobile-dock-clean non trouvé - utilisation du nouveau mobile_dock_bar');
        }
}

/**
 * Initialise le système d'auto-masquage du dock mobile
 */
function initializeMobileDockAutoHide() {
    const mobileDock = document.getElementById('mobile-dock-clean') || document.getElementById('mobile-dock');
    
    if (!mobileDock) {
        console.warn('⚠️ Mobile dock not found');
        return;
    }
    
    // Variables pour la gestion des états
    let hideTimer = null;
    let lastScrollY = window.scrollY;
    let isScrolling = false;
    let touchStartY = 0;
    let touchEndY = 0;
    let isVisible = true;
    let userInteracted = false;
    
    console.log('📱 État initial - isVisible:', isVisible, 'userInteracted:', userInteracted);
    
    // Forcer la visibilité dès le début
    ensureInitialVisibility();
    
    // Configuration - DÉSACTIVÉ TEMPORAIREMENT POUR DEBUG
    const HIDE_DELAY = 999999999; // Désactivé pour debug
    const SCROLL_THRESHOLD = 10; // Seuil de déclenchement du scroll
    const TOUCH_THRESHOLD = 50; // Seuil pour les gestes tactiles
    
    /**
     * Force la visibilité initiale du dock
     */
    function ensureInitialVisibility() {
        console.log('📱 Forcer la visibilité initiale du dock');
        mobileDock.classList.remove('dock-hidden');
        mobileDock.classList.add('dock-visible');
        mobileDock.style.display = 'block';
        mobileDock.style.visibility = 'visible';
        mobileDock.style.opacity = '1';
        mobileDock.style.transform = 'translateY(0)';
        mobileDock.style.pointerEvents = 'auto';
        isVisible = true;
        
        // Debug détaillé
        console.log('📱 Dock forcé visible - position:', mobileDock.getBoundingClientRect());
        console.log('📱 Dock HTML content:', mobileDock.innerHTML.substring(0, 200));
        console.log('📱 Dock computed styles:', {
            display: window.getComputedStyle(mobileDock).display,
            height: window.getComputedStyle(mobileDock).height,
            minHeight: window.getComputedStyle(mobileDock).minHeight,
            position: window.getComputedStyle(mobileDock).position,
            bottom: window.getComputedStyle(mobileDock).bottom
        });
        
        // Vérifier le container
        const container = mobileDock.querySelector('.mobile-dock-container');
        if (container) {
            console.log('📱 Container styles:', {
                display: window.getComputedStyle(container).display,
                height: window.getComputedStyle(container).height,
                minHeight: window.getComputedStyle(container).minHeight,
                padding: window.getComputedStyle(container).padding
            });
            console.log('📱 Container position:', container.getBoundingClientRect());
        } else {
            console.error('❌ Container .mobile-dock-container NOT FOUND!');
        }
    }
    
    /**
     * Affiche le dock
     */
    function showDock() {
        if (!isVisible) {
            console.log('📱 Showing mobile dock');
            mobileDock.classList.remove('dock-hidden');
            mobileDock.classList.add('dock-visible');
            isVisible = true;
        }
        resetHideTimer();
    }
    
    /**
     * Cache le dock
     */
    function hideDock() {
        if (mobileDock) {
            console.log('📱 Hiding mobile dock - isVisible:', isVisible, 'userInteracted:', userInteracted);
            if (isVisible && userInteracted) {
                console.log('📱 Actually hiding dock');
                mobileDock.classList.remove('dock-visible');
                mobileDock.classList.add('dock-hidden');
                isVisible = false;
            } else {
                console.log('📱 Skip hiding - conditions not met');
            }
        }
        clearTimeout(hideTimer);
    }
    
    /**
     * Réinitialise le timer de masquage automatique
     */
    function resetHideTimer() {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => {
            if (userInteracted) {
                hideDock();
            }
        }, HIDE_DELAY);
    }
    
    /**
     * Gestion du scroll
     */
    function handleScroll() {
        const currentScrollY = window.scrollY;
        const scrollDiff = currentScrollY - lastScrollY;
        
        // Éviter les micro-scrolls
        if (Math.abs(scrollDiff) < SCROLL_THRESHOLD) {
            return;
        }
        
        isScrolling = true;
        userInteracted = true;
        
        if (scrollDiff > 0) {
            // Scroll vers le bas - cacher le dock
            hideDock();
        } else {
            // Scroll vers le haut - afficher le dock
            showDock();
        }
        
        lastScrollY = currentScrollY;
        
        // Réinitialiser le flag de scroll après un délai
        setTimeout(() => {
            isScrolling = false;
        }, 100);
    }
    
    /**
     * Gestion des événements tactiles
     */
    function handleTouchStart(e) {
        touchStartY = e.touches[0].clientY;
    }
    
    function handleTouchMove(e) {
        touchEndY = e.touches[0].clientY;
    }
    
    function handleTouchEnd() {
        const touchDiff = touchStartY - touchEndY;
        
        // Éviter les micro-gestes
        if (Math.abs(touchDiff) < TOUCH_THRESHOLD) {
            return;
        }
        
        userInteracted = true;
        
        if (touchDiff > 0) {
            // Glissement vers le haut - afficher le dock
            showDock();
        } else {
            // Glissement vers le bas - cacher le dock
            hideDock();
        }
    }
    
    /**
     * Gestion des interactions avec le dock
     */
    function handleDockInteraction() {
        userInteracted = true;
        showDock();
    }
    
    /**
     * Gestion du survol de la zone du dock
     */
    function handleDockHover() {
        if (!isVisible) {
            showDock();
        }
    }
    
    /**
     * Détection de mouvement de la souris près du bas de l'écran
     */
    function handleMouseMove(e) {
        const windowHeight = window.innerHeight;
        const mouseY = e.clientY;
        const bottomZone = windowHeight - 100; // Zone de 100px en bas
        
        if (mouseY > bottomZone && !isVisible) {
            showDock();
        }
    }
    
    // Ajouter les classes CSS nécessaires
    mobileDock.classList.add('dock-auto-hide');
    
    // Event listeners pour le scroll
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(handleScroll, 10);
    }, { passive: true });
    
    // Event listeners pour les gestes tactiles
    document.addEventListener('touchstart', handleTouchStart, { passive: true });
    document.addEventListener('touchmove', handleTouchMove, { passive: true });
    document.addEventListener('touchend', handleTouchEnd, { passive: true });
    
    // Event listeners pour les interactions avec le dock
    mobileDock.addEventListener('click', handleDockInteraction);
    mobileDock.addEventListener('touchstart', handleDockInteraction);
    mobileDock.addEventListener('mouseenter', handleDockHover);
    
    // Event listener pour le mouvement de la souris (desktop)
    if (!('ontouchstart' in window)) {
        document.addEventListener('mousemove', handleMouseMove);
    }
    
    // Event listeners pour les interactions générales de la page
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
    
    // Gestion du redimensionnement de la fenêtre
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            // Sur desktop, toujours afficher
            showDock();
            clearTimeout(hideTimer);
        } else {
            // Sur mobile, reprendre le comportement normal
            resetHideTimer();
        }
    });
    
    // Initialisation
    ensureInitialVisibility(); // Double sécurité
    showDock();
    
    console.log('✅ Mobile dock auto-hide system configured');
}

/**
 * API publique pour contrôler le dock
 */
window.MobileDockController = {
    show: function() {
        const mobileDock = document.getElementById('mobile-dock-clean') || document.getElementById('mobile-dock');
        if (mobileDock) {
            mobileDock.classList.remove('dock-hidden');
            mobileDock.classList.add('dock-visible');
        }
    },
    
    hide: function() {
        const mobileDock = document.getElementById('mobile-dock-clean') || document.getElementById('mobile-dock');
        if (mobileDock) {
            mobileDock.classList.remove('dock-visible');
            mobileDock.classList.add('dock-hidden');
        }
    },
    
    toggle: function() {
        const mobileDock = document.getElementById('mobile-dock-clean') || document.getElementById('mobile-dock');
        if (mobileDock) {
            if (mobileDock.classList.contains('dock-hidden')) {
                this.show();
            } else {
                this.hide();
            }
        }
    }
};

console.log('🎛️ Mobile dock auto-hide - JavaScript loaded');
