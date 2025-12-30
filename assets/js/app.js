// Désactivation du service worker qui cause des problèmes
// Le service worker est désormais géré par le script principal dans header.php

// Amélioration des champs de recherche
document.addEventListener('DOMContentLoaded', function () {
    // Ajouter des effets d'animation aux champs de recherche
    const searchInputs = document.querySelectorAll('.search-form .form-control, [id*="recherche_client"]');

    searchInputs.forEach(input => {
        // Effet lors du focus
        input.addEventListener('focus', function () {
            const inputGroup = this.closest('.input-group');
            if (inputGroup) {
                inputGroup.style.boxShadow = '0 0 0 0.2rem rgba(13, 110, 253, 0.25)';
                const searchIcon = inputGroup.querySelector('.fa-search');
                if (searchIcon) {
                    searchIcon.classList.add('text-primary');
                }
            }
        });

        // Effet lors de la perte du focus
        input.addEventListener('blur', function () {
            const inputGroup = this.closest('.input-group');
            if (inputGroup) {
                inputGroup.style.boxShadow = '';
                const searchIcon = inputGroup.querySelector('.fa-search');
                if (searchIcon && this.value === '') {
                    searchIcon.classList.remove('text-primary');
                }
            }
        });
    });

    // Fonction pour corriger l'espace en haut de façon définitive
    function fixTopSpacing() {
        const elementsToFix = [
            document.documentElement,
            document.body,
            document.querySelector('.container-fluid'),
            document.querySelector('main'),
            document.querySelector('.main-container'),
            document.querySelector('.content'),
            document.querySelector('.row')
        ];

        elementsToFix.forEach(el => {
            if (el) {
                el.style.marginTop = '0';
                el.style.paddingTop = '0';
            }
        });

        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.style.top = '0';
        }

        const rows = document.querySelectorAll('.row');
        rows.forEach(row => {
            row.style.margin = '0';
            if (window.innerWidth >= 992) {
                const content = document.querySelector('.content');
                if (content) {
                    content.style.paddingTop = '1rem';
                }
            }
        });
    }

    // Exécuter immédiatement
    fixTopSpacing();
    setTimeout(fixTopSpacing, 100);
    window.addEventListener('load', fixTopSpacing);
    window.addEventListener('resize', fixTopSpacing);
});
