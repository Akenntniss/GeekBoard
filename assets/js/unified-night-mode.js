/* ====================================================================
   🌙 SYSTÈME UNIFIÉ DE MODE NUIT - GEEKBOARD
   Détection automatique et application cohérente du mode nuit
==================================================================== */

(function () {
    'use strict';

    // Configuration globale
    const NIGHT_MODE_CONFIG = {
        // Classes CSS utilisées
        classes: {
            body: 'night-mode',
            html: 'night-mode',
            fallback: 'dark-mode' // Pour compatibilité
        },

        // Sélecteurs d'éléments à surveiller
        selectors: {
            dashboard: '#dashboard',
            container: '.container, .container-fluid',
            cards: '.card, .stat-card, .stats-card, .daily-stats-card',
            modals: '.modal-content',
            navbar: '.navbar, .top-nav, #desktop-navbar',
            buttons: '.btn, .action-btn',
            forms: '.form-control, .form-select'
        },

        // Stockage localStorage
        storage: {
            key: 'geekboard_theme',
            userKey: (userId) => `geekboard_theme_user_${userId}`
        }
    };

    // Variables globales
    let currentUserId = null;
    let themeObserver = null;
    let isInitialized = false;

    /**
     * Détecter l'ID utilisateur actuel
     */
    function detectUserId() {
        // Essayer de récupérer l'ID depuis différentes sources
        if (window.current_user_id) {
            return window.current_user_id;
        }

        // Essayer depuis les éléments DOM
        const userElement = document.querySelector('[data-user-id]');
        if (userElement) {
            return userElement.getAttribute('data-user-id');
        }

        // Essayer depuis les variables PHP injectées
        if (typeof php_user_id !== 'undefined') {
            return php_user_id;
        }

        return null;
    }

    /**
     * Obtenir la clé de stockage appropriée
     */
    function getStorageKey() {
        const userId = detectUserId();
        return userId ?
            NIGHT_MODE_CONFIG.storage.userKey(userId) :
            NIGHT_MODE_CONFIG.storage.key;
    }

    /**
     * Détecter les préférences système
     */
    function getSystemPreference() {
        if (window.matchMedia) {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        return false;
    }

    /**
     * Obtenir la préférence stockée
     */
    function getStoredPreference() {
        try {
            const storageKey = getStorageKey();
            const stored = localStorage.getItem(storageKey);

            // Migration des anciennes préférences si nécessaire
            if (!stored && currentUserId) {
                const oldTheme = localStorage.getItem('theme');
                if (oldTheme) {
                    localStorage.setItem(storageKey, oldTheme);
                    return oldTheme;
                }
            }

            return stored;
        } catch (e) {
            console.warn('🚨 Erreur lecture localStorage:', e);
            return null;
        }
    }

    /**
     * Sauvegarder la préférence
     */
    function savePreference(theme) {
        try {
            const storageKey = getStorageKey();
            localStorage.setItem(storageKey, theme);
        } catch (e) {
            console.warn('🚨 Erreur sauvegarde localStorage:', e);
        }
    }

    /**
     * Déterminer si le mode nuit doit être activé
     */
    function shouldEnableNightMode() {
        const stored = getStoredPreference();
        const systemPrefers = getSystemPreference();

        // Priorité : préférence stockée > préférence système
        if (stored === 'dark' || stored === 'night') {
            return true;
        } else if (stored === 'light' || stored === 'day') {
            return false;
        } else {
            // Aucune préférence stockée, suivre le système
            return systemPrefers;
        }
    }

    /**
     * Appliquer le mode nuit immédiatement
     */
    function applyNightMode() {
        const html = document.documentElement;
        const body = document.body;

        // Appliquer les classes principales
        if (html) html.classList.add(NIGHT_MODE_CONFIG.classes.html);
        if (body) {
            body.classList.add(NIGHT_MODE_CONFIG.classes.body);
            body.classList.add(NIGHT_MODE_CONFIG.classes.fallback); // Compatibilité
        }

        // Appliquer aux éléments spécifiques
        applyToElements(true);

        // console.log('🌙 Mode nuit activé');
    }

    /**
     * Appliquer le mode jour immédiatement
     */
    function applyDayMode() {
        const html = document.documentElement;
        const body = document.body;

        // Retirer les classes principales
        if (html) html.classList.remove(NIGHT_MODE_CONFIG.classes.html);
        if (body) {
            body.classList.remove(NIGHT_MODE_CONFIG.classes.body);
            body.classList.remove(NIGHT_MODE_CONFIG.classes.fallback);
        }

        // Nettoyer tous les éléments
        applyToElements(false);

        // console.log('☀️ Mode jour activé');
    }

    /**
     * Appliquer le thème aux éléments spécifiques
     */
    function applyToElements(isNightMode) {
        Object.values(NIGHT_MODE_CONFIG.selectors).forEach(selector => {
            try {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    if (isNightMode) {
                        element.classList.add(NIGHT_MODE_CONFIG.classes.body);
                    } else {
                        element.classList.remove(NIGHT_MODE_CONFIG.classes.body);
                        element.classList.remove(NIGHT_MODE_CONFIG.classes.fallback);
                    }
                });
            } catch (e) {
                // Ignorer les erreurs de sélecteur
            }
        });
    }

    /**
     * Appliquer le thème approprié
     */
    function applyTheme() {
        const shouldEnable = shouldEnableNightMode();

        if (shouldEnable) {
            applyNightMode();
        } else {
            applyDayMode();
        }

        // Déclencher un événement personnalisé
        try {
            const event = new CustomEvent('themeChanged', {
                detail: { theme: shouldEnable ? 'night' : 'day' }
            });
            document.dispatchEvent(event);
        } catch (e) {
            // Fallback pour les navigateurs plus anciens
        }

        return shouldEnable;
    }

    /**
     * Basculer manuellement le thème
     */
    function toggleTheme() {
        const currentlyNight = document.body.classList.contains(NIGHT_MODE_CONFIG.classes.body);
        const newTheme = currentlyNight ? 'light' : 'dark';

        savePreference(newTheme);
        applyTheme();

        return !currentlyNight;
    }

    /**
     * Configurer l'écoute des changements système
     */
    function setupSystemListener() {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            const handleChange = (e) => {
                // Ne réagir que si aucune préférence n'est stockée
                const stored = getStoredPreference();
                if (!stored) {
                    // console.log('🔄 Changement préférence système détecté');
                    applyTheme();
                }
            };

            // Méthode moderne
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleChange);
            }
            // Fallback pour anciens navigateurs
            else if (mediaQuery.addListener) {
                mediaQuery.addListener(handleChange);
            }
        }
    }

    /**
     * Observer les changements DOM pour maintenir le thème
     */
    function setupDOMObserver() {
        if (window.MutationObserver) {
            themeObserver = new MutationObserver((mutations) => {
                let shouldReapply = false;

                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        // Vérifier si de nouveaux éléments ont été ajoutés
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                shouldReapply = true;
                            }
                        });
                    }
                });

                if (shouldReapply) {
                    // Réappliquer le thème aux nouveaux éléments avec un délai
                    setTimeout(() => {
                        const isNightMode = document.body.classList.contains(NIGHT_MODE_CONFIG.classes.body);
                        applyToElements(isNightMode);
                    }, 100);
                }
            });

            // Observer les changements dans le body
            themeObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    /**
     * Initialiser le système de thème
     */
    function initialize() {
        if (isInitialized) return;

        // console.log('🎨 Initialisation système unifié de mode nuit');

        // Détecter l'utilisateur actuel
        currentUserId = detectUserId();

        // Appliquer le thème immédiatement
        const isNight = applyTheme();

        // Configurer les écouteurs
        setupSystemListener();
        setupDOMObserver();

        // Exposer les fonctions globalement
        window.GeekBoardTheme = {
            toggle: toggleTheme,
            apply: applyTheme,
            isNightMode: () => document.body.classList.contains(NIGHT_MODE_CONFIG.classes.body),
            setPreference: (theme) => {
                savePreference(theme);
                applyTheme();
            }
        };

        isInitialized = true;

        // console.log('✅ Système de thème initialisé', {
        userId: currentUserId,
            theme: isNight ? 'night' : 'day',
                systemPreference: getSystemPreference(),
                    storedPreference: getStoredPreference()
    });
}

    /**
     * Nettoyer les ressources
     */
    function cleanup() {
    if (themeObserver) {
        themeObserver.disconnect();
        themeObserver = null;
    }
    isInitialized = false;
}

// Initialisation immédiate
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize();
}

// Réinitialiser si la page change (pour les SPA)
window.addEventListener('beforeunload', cleanup);

// Application immédiate pour éviter le flash
applyTheme();

}) ();
