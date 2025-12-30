/**
 * Lazy Loading Images - Automatic
 * Ajoute automatiquement loading="lazy" à toutes les images
 * sauf celles qui sont above-the-fold (visibles immédiatement)
 */

(function () {
    'use strict';

    console.log('🖼️ [LAZY-LOADING] Initialisation...');

    function applyLazyLoading() {
        // Sélectionner toutes les images
        const images = document.querySelectorAll('img:not([loading])');

        let above_fold_count = 0;
        let lazy_count = 0;

        images.forEach((img, index) => {
            // Vérifier si l'image est above-the-fold (700px premiers pixels)
            const rect = img.getBoundingClientRect();
            const isAboveFold = rect.top < 700;

            // Exceptions: ne pas lazy load
            const isLogo = img.closest('.navbar, .servo-logo-container, .menu-logo');
            const hasEagerAttribute = img.hasAttribute('loading') && img.getAttribute('loading') === 'eager';

            if (isLogo || hasEagerAttribute || isAboveFold) {
                // Images critiques: chargement immédiat
                img.setAttribute('loading', 'eager');
                above_fold_count++;
            } else {
                // Images secondaires: lazy loading
                img.setAttribute('loading', 'lazy');
                lazy_count++;
            }
        });

        console.log(`✅ [LAZY-LOADING] ${above_fold_count} images eager, ${lazy_count} images lazy`);
    }

    // Appliquer au chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyLazyLoading);
    } else {
        applyLazyLoading();
    }

    // Observer pour les images ajoutées dynamiquement
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    if (node.tagName === 'IMG' && !node.hasAttribute('loading')) {
                        const rect = node.getBoundingClientRect();
                        const isAboveFold = rect.top < 700;
                        node.setAttribute('loading', isAboveFold ? 'eager' : 'lazy');
                        console.log(`🖼️ [LAZY-LOADING] Image dynamique: ${node.src} → ${isAboveFold ? 'eager' : 'lazy'}`);
                    }
                    // Chercher des images dans les enfants
                    const imgs = node.querySelectorAll && node.querySelectorAll('img:not([loading])');
                    if (imgs) {
                        imgs.forEach((img) => {
                            const rect = img.getBoundingClientRect();
                            const isAboveFold = rect.top < 700;
                            img.setAttribute('loading', isAboveFold ? 'eager' : 'lazy');
                        });
                    }
                }
            });
        });
    });

    // Commencer à observer
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    console.log('✅ [LAZY-LOADING] Observer actif pour images dynamiques');
})();
