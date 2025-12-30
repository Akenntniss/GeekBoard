/**
 * Fix Modal Dark Mode - Script de correction pour le modal taskDetailsModal en mode nuit
 * Corrige les problèmes d'affichage des futuristic-card en mode sombre
 */

(function() {
    'use strict';

    /**
     * Force l'application du mode sombre sur le modal taskDetailsModal
     */
    function forceModalDarkMode() {
        const modal = document.getElementById('taskDetailsModal');
        if (!modal) return;

        // Vérifier si le body a la classe dark-mode
        const isDarkMode = document.body.classList.contains('dark-mode');
        
        if (isDarkMode) {
            console.log('🌙 Mode sombre détecté - Application des styles futuristes au modal');
            
            // Forcer les styles sur le modal content
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.background = 'linear-gradient(145deg, #0f0f23 0%, #1a1a2e 100%)';
                modalContent.style.border = '2px solid rgba(0, 255, 255, 0.3)';
                modalContent.style.boxShadow = '0 0 30px rgba(0, 212, 255, 0.5)';
                modalContent.style.backdropFilter = 'blur(20px)';
                modalContent.style.borderRadius = '24px';
            }

            // Forcer les styles sur toutes les futuristic-card
            const futuristicCards = modal.querySelectorAll('.futuristic-card');
            futuristicCards.forEach(card => {
                card.style.background = 'linear-gradient(135deg, rgba(26, 26, 46, 0.9) 0%, rgba(22, 33, 62, 0.8) 100%)';
                card.style.border = '2px solid rgba(0, 255, 255, 0.3)';
                card.style.borderRadius = '20px';
                card.style.boxShadow = '0 0 20px rgba(0, 255, 255, 0.3)';
                card.style.backdropFilter = 'blur(15px)';
                card.style.color = '#ffffff';
            });

            // Forcer les styles sur les stat-card
            const statCards = modal.querySelectorAll('.stat-card, .futuristic-stat-card');
            statCards.forEach(card => {
                card.style.background = 'linear-gradient(135deg, rgba(26, 26, 46, 0.9) 0%, rgba(22, 33, 62, 0.8) 100%)';
                card.style.border = '2px solid rgba(0, 255, 255, 0.3)';
                card.style.borderRadius = '20px';
                card.style.boxShadow = '0 0 20px rgba(0, 255, 255, 0.3)';
                card.style.backdropFilter = 'blur(15px)';
                card.style.color = '#ffffff';
            });

            // Forcer les styles sur les titres
            const titles = modal.querySelectorAll('.section-title, .holographic-text');
            titles.forEach(title => {
                title.style.color = '#00ffff';
                title.style.textShadow = '0 0 20px rgba(0, 212, 255, 0.8)';
                title.style.fontFamily = "'Orbitron', monospace";
            });

            // Forcer les styles sur les labels
            const labels = modal.querySelectorAll('.stat-label, .info-card-label, .stat-label-futuristic');
            labels.forEach(label => {
                label.style.color = '#8080c0';
                label.style.fontFamily = "'Orbitron', monospace";
                label.style.textTransform = 'uppercase';
                label.style.letterSpacing = '0.1em';
                label.style.textShadow = '0 0 5px rgba(0, 255, 255, 0.2)';
            });

            // Forcer les styles sur les valeurs
            const values = modal.querySelectorAll('.stat-value, .info-card-value, .stat-value-futuristic');
            values.forEach(value => {
                value.style.color = '#ffffff';
                value.style.fontWeight = '600';
                value.style.textShadow = '0 0 8px rgba(255, 255, 255, 0.1)';
            });

            console.log('✅ Styles futuristes appliqués avec succès');
        }
    }

    /**
     * Observe les changements de classe sur le body pour détecter les changements de mode
     */
    function observeThemeChanges() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // Attendre un peu pour que les autres scripts se terminent
                    setTimeout(forceModalDarkMode, 100);
                }
            });
        });

        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    /**
     * Initialise le fix au chargement de la page
     */
    function init() {
        // Appliquer immédiatement si le modal existe
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(forceModalDarkMode, 500);
                observeThemeChanges();
            });
        } else {
            setTimeout(forceModalDarkMode, 500);
            observeThemeChanges();
        }

        // Observer l'ouverture du modal
        document.addEventListener('show.bs.modal', function(event) {
            if (event.target.id === 'taskDetailsModal') {
                setTimeout(forceModalDarkMode, 100);
            }
        });

        // Observer après l'ouverture complète du modal
        document.addEventListener('shown.bs.modal', function(event) {
            if (event.target.id === 'taskDetailsModal') {
                setTimeout(forceModalDarkMode, 200);
            }
        });
    }

    // Démarrer l'initialisation
    init();

    // Exposer la fonction pour debug
    window.forceModalDarkMode = forceModalDarkMode;

})();
