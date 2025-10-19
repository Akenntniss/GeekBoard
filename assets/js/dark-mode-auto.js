/**
 * Mode Nuit Automatique GeekBoard
 * Applique automatiquement les classes CSS pour le mode sombre
 * basé sur les préférences système du navigateur
 */

(function() {
    'use strict';

    /**
     * Applique les classes de mode automatique aux éléments existants
     */
    function applyAutoDarkModeClasses() {
        // Elements de base
        const body = document.querySelector('body');
        if (body) {
            body.classList.add('gb-auto-theme');
        }

        // Navigation
        const navbars = document.querySelectorAll('.navbar');
        navbars.forEach(navbar => {
            navbar.classList.add('gb-auto-theme');
        });

        // Cartes
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.classList.add('gb-auto-theme');
        });

        // Modals
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.classList.add('gb-auto-theme');
        });

        // Boutons light
        const btnLights = document.querySelectorAll('.btn-light');
        btnLights.forEach(btn => {
            btn.classList.add('gb-auto-theme');
        });

        // Formulaires
        const formControls = document.querySelectorAll('.form-control, .form-select');
        formControls.forEach(control => {
            control.classList.add('gb-auto-theme');
        });

        // Tables
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            table.classList.add('gb-auto-theme');
        });

        // Dropdowns
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(dropdown => {
            dropdown.classList.add('gb-auto-theme');
        });

        const dropdownItems = document.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.classList.add('gb-auto-theme');
        });

        // Alertes
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.classList.add('gb-auto-theme');
        });

        // Badges
        const badges = document.querySelectorAll('.badge');
        badges.forEach(badge => {
            badge.classList.add('gb-auto-theme');
        });

        // Pagination
        const pageLinks = document.querySelectorAll('.page-link');
        pageLinks.forEach(link => {
            link.classList.add('gb-auto-theme');
        });

        // Éléments spécifiques avec des classes génériques
        const backgroundElements = document.querySelectorAll('.bg-white, .bg-light');
        backgroundElements.forEach(element => {
            element.classList.add('dark-auto');
        });

        const textElements = document.querySelectorAll('.text-dark');
        textElements.forEach(element => {
            element.classList.add('dark-auto-text');
        });

        const mutedElements = document.querySelectorAll('.text-muted');
        mutedElements.forEach(element => {
            element.classList.add('dark-auto-text-muted');
        });

        const borderElements = document.querySelectorAll('.border');
        borderElements.forEach(element => {
            element.classList.add('dark-auto-border');
        });
    }

    /**
     * Observe les changements dans le DOM pour appliquer les classes aux nouveaux éléments
     */
    function observeNewElements() {
        if (!window.MutationObserver) return;

        const observer = new MutationObserver(function(mutations) {
            let shouldReapply = false;

            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Vérifier s'il y a de nouveaux éléments HTML
                    for (let node of mutation.addedNodes) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            shouldReapply = true;
                            break;
                        }
                    }
                }
            });

            if (shouldReapply) {
                // Délai pour permettre à l'élément d'être complètement inséré
                setTimeout(applyAutoDarkModeClasses, 10);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Detecte les changements de préférences système (optionnel)
     */
    function watchSystemPreferences() {
        if (!window.matchMedia) return;

        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        darkModeQuery.addEventListener('change', function(e) {
            // Forcer une re-application des styles si nécessaire
            setTimeout(function() {
                // Dispatch un événement personnalisé pour informer d'autres scripts
                const event = new CustomEvent('geekboard:theme-changed', {
                    detail: { isDark: e.matches }
                });
                document.dispatchEvent(event);
            }, 50);
        });

        // Log initial state pour debug
        console.log('GeekBoard Auto Dark Mode:', darkModeQuery.matches ? 'Dark' : 'Light');
    }

    /**
     * Initialisation
     */
    function init() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    applyAutoDarkModeClasses();
                    observeNewElements();
                    watchSystemPreferences();
                }, 10);
            });
        } else {
            // DOM déjà prêt
            setTimeout(function() {
                applyAutoDarkModeClasses();
                observeNewElements();
                watchSystemPreferences();
            }, 10);
        }
    }

    /**
     * API publique
     */
    window.GeekBoardDarkMode = {
        apply: applyAutoDarkModeClasses,
        init: init,
        version: '1.0.0'
    };

    // Auto-initialisation
    init();

})();
