/**
 * Synchronisation automatique du thème avec les préférences système
 * Remplace le basculement manuel comme demandé par l'utilisateur.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Détection de la préférence système
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    function applySystemTheme(isDark) {
        if (isDark) {
            document.body.classList.add('night-mode');
            document.body.classList.add('dark-mode');
            // Forcer la mise à jour du localStorage pour que les autres scripts (comme le loader) soient cohérents
            localStorage.setItem('theme', 'dark');
            localStorage.setItem('nightMode', 'true');

            // Mettre à jour les meta tags pour la barre d'état mobile
            updateMetaThemeColor('#1f2937'); // Couleur sombre
        } else {
            document.body.classList.remove('night-mode');
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            localStorage.setItem('nightMode', 'false');

            // Mettre à jour les meta tags pour la barre d'état mobile
            updateMetaThemeColor('#ffffff'); // Couleur claire
        }

        console.log('🎨 Thème synchronisé avec le système:', isDark ? 'Mode Nuit 🌙' : 'Mode Jour ☀️');
    }

    function updateMetaThemeColor(color) {
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', color);
        }
    }

    // Appliquer immédiatement au chargement
    applySystemTheme(darkModeMediaQuery.matches);

    // Écouter les changements de préférence système en temps réel
    try {
        darkModeMediaQuery.addEventListener('change', (e) => {
            applySystemTheme(e.matches);
        });
    } catch (e1) {
        // Fallback pour les vieux navigateurs (Safari < 14)
        try {
            darkModeMediaQuery.addListener((e) => {
                applySystemTheme(e.matches);
            });
        } catch (e2) {
            console.error('Impossible d\'écouter les changements de thème système', e2);
        }
    }

    // Observer les changements de classe sur le body pour empêcher les scripts tiers de forcer le mauvais thème
    // Si l'utilisateur veut STRICTEMENT le mode système, on empêche les autres scripts de le changer
    const observer = new MutationObserver(function (mutations) {
        const systemIsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const bodyIsDark = document.body.classList.contains('night-mode');

        if (systemIsDark && !bodyIsDark) {
            console.warn('⚠️ Tentative de passage en mode jour bloquée (Mode système forcé)');
            document.body.classList.add('night-mode');
            document.body.classList.add('dark-mode');
        } else if (!systemIsDark && bodyIsDark) {
            console.warn('⚠️ Tentative de passage en mode nuit bloquée (Mode système forcé)');
            document.body.classList.remove('night-mode');
            document.body.classList.remove('dark-mode');
        }
    });

    // Activer l'observateur après un court délai pour laisser l'initialisation se faire
    setTimeout(() => {
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }, 1000);
});
