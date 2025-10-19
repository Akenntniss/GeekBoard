/**
 * ====================================================================
 * 📱 INTERACTIONS BOUTONS 2x2 MOBILE
 * Améliorations tactiles pour les boutons principaux
 * ====================================================================
 */

(function() {
    'use strict';
    
    // ====================================================================
    // CONFIGURATION
    // ====================================================================
    
    const config = {
        // Détection mobile
        isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
        isTouch: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
        
        // Paramètres d'interaction
        hapticFeedback: true,
        soundFeedback: false, // Désactivé par défaut
        visualFeedback: true,
        
        // Seuils tactiles
        touchThreshold: 10,
        longPressDelay: 500
    };
    
    // ====================================================================
    // FEEDBACK HAPTIQUE
    // ====================================================================
    
    function triggerHapticFeedback(type = 'light') {
        if (!config.hapticFeedback || !navigator.vibrate) return;
        
        // Patterns de vibration selon le type
        const patterns = {
            light: [10],
            medium: [20],
            heavy: [30],
            success: [10, 50, 10],
            error: [50, 100, 50]
        };
        
        try {
            navigator.vibrate(patterns[type] || patterns.light);
        } catch (e) {
            // Ignorer les erreurs de vibration
        }
    }
    
    // ====================================================================
    // FEEDBACK VISUEL
    // ====================================================================
    
    function addVisualFeedback(element) {
        if (!config.visualFeedback) return;
        
        // Ajouter une classe temporaire pour l'effet visuel
        element.classList.add('button-pressed');
        
        // Retirer la classe après l'animation
        setTimeout(() => {
            element.classList.remove('button-pressed');
        }, 150);
    }
    
    // ====================================================================
    // GESTION DES INTERACTIONS TACTILES
    // ====================================================================
    
    function initTouchInteractions() {
        const statCards = document.querySelectorAll('.statistics-grid .stat-card');
        
        if (!statCards.length) return;
        
        statCards.forEach((card, index) => {
            let touchStartTime = 0;
            let touchStartPos = { x: 0, y: 0 };
            let isLongPress = false;
            let longPressTimer = null;
            
            // ====================================================================
            // ÉVÉNEMENTS TACTILES
            // ====================================================================
            
            // Début du toucher
            card.addEventListener('touchstart', function(e) {
                if (!config.isTouch) return;
                
                touchStartTime = Date.now();
                const touch = e.touches[0];
                touchStartPos = { x: touch.clientX, y: touch.clientY };
                isLongPress = false;
                
                // Ajouter la classe de pression
                this.classList.add('touching');
                
                // Démarrer le timer pour la pression longue
                longPressTimer = setTimeout(() => {
                    isLongPress = true;
                    triggerHapticFeedback('medium');
                    showQuickPreview(this, index);
                }, config.longPressDelay);
                
            }, { passive: true });
            
            // Mouvement du toucher
            card.addEventListener('touchmove', function(e) {
                if (!config.isTouch || !longPressTimer) return;
                
                const touch = e.touches[0];
                const deltaX = Math.abs(touch.clientX - touchStartPos.x);
                const deltaY = Math.abs(touch.clientY - touchStartPos.y);
                
                // Si le mouvement dépasse le seuil, annuler la pression longue
                if (deltaX > config.touchThreshold || deltaY > config.touchThreshold) {
                    clearTimeout(longPressTimer);
                    longPressTimer = null;
                    this.classList.remove('touching');
                }
            }, { passive: true });
            
            // Fin du toucher
            card.addEventListener('touchend', function(e) {
                if (!config.isTouch) return;
                
                const touchDuration = Date.now() - touchStartTime;
                
                // Nettoyer
                clearTimeout(longPressTimer);
                this.classList.remove('touching');
                
                // Si c'était une pression longue, ne pas naviguer
                if (isLongPress) {
                    e.preventDefault();
                    return;
                }
                
                // Feedback pour un tap normal
                if (touchDuration < 300) {
                    triggerHapticFeedback('light');
                    addVisualFeedback(this);
                }
                
            }, { passive: false });
            
            // Annulation du toucher
            card.addEventListener('touchcancel', function() {
                clearTimeout(longPressTimer);
                this.classList.remove('touching');
            }, { passive: true });
            
            // ====================================================================
            // ÉVÉNEMENTS SOURIS (POUR DESKTOP)
            // ====================================================================
            
            if (!config.isMobile) {
                card.addEventListener('mousedown', function() {
                    this.classList.add('touching');
                });
                
                card.addEventListener('mouseup', function() {
                    this.classList.remove('touching');
                    addVisualFeedback(this);
                });
                
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('touching');
                });
            }
        });
    }
    
    // ====================================================================
    // APERÇU RAPIDE (PRESSION LONGUE)
    // ====================================================================
    
    function showQuickPreview(card, index) {
        // Récupérer les informations du bouton
        const value = card.querySelector('.stat-value')?.textContent || '0';
        const label = card.querySelector('.stat-label')?.textContent || 'Élément';
        const icon = card.querySelector('.stat-icon i')?.className || 'fas fa-info';
        
        // Créer l'aperçu rapide
        const preview = document.createElement('div');
        preview.className = 'quick-preview-tooltip';
        preview.innerHTML = `
            <div class="preview-content">
                <i class="${icon}"></i>
                <div class="preview-info">
                    <div class="preview-value">${value}</div>
                    <div class="preview-label">${label}</div>
                </div>
            </div>
        `;
        
        // Styles inline pour éviter les dépendances CSS
        preview.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        `;
        
        preview.querySelector('.preview-content').style.cssText = `
            display: flex;
            align-items: center;
            gap: 1rem;
        `;
        
        preview.querySelector('.preview-content i').style.cssText = `
            font-size: 2rem;
            opacity: 0.8;
        `;
        
        preview.querySelector('.preview-info').style.cssText = `
            display: flex;
            flex-direction: column;
        `;
        
        preview.querySelector('.preview-value').style.cssText = `
            font-size: 1.5rem;
            font-weight: bold;
            line-height: 1;
        `;
        
        preview.querySelector('.preview-label').style.cssText = `
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        `;
        
        // Ajouter au DOM
        document.body.appendChild(preview);
        
        // Animer l'apparition
        requestAnimationFrame(() => {
            preview.style.opacity = '1';
        });
        
        // Supprimer après 2 secondes
        setTimeout(() => {
            preview.style.opacity = '0';
            setTimeout(() => {
                if (preview.parentNode) {
                    preview.parentNode.removeChild(preview);
                }
            }, 200);
        }, 2000);
    }
    
    // ====================================================================
    // STYLES CSS DYNAMIQUES
    // ====================================================================
    
    function injectDynamicStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* Styles pour les interactions tactiles */
            @media (max-width: 768px) {
                .stat-card.touching {
                    transform: scale(0.95) !important;
                    transition: transform 0.1s ease !important;
                }
                
                .stat-card.button-pressed {
                    animation: buttonPress 0.15s ease !important;
                }
                
                @keyframes buttonPress {
                    0% { transform: scale(1); }
                    50% { transform: scale(0.98); }
                    100% { transform: scale(1); }
                }
                
                /* Améliorer la zone tactile */
                .stat-card {
                    position: relative !important;
                }
                
                .stat-card::after {
                    content: '' !important;
                    position: absolute !important;
                    top: -5px !important;
                    left: -5px !important;
                    right: -5px !important;
                    bottom: -5px !important;
                    border-radius: inherit !important;
                }
                
                /* Indicateur de pression longue */
                .stat-card.touching::before {
                    content: '' !important;
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    bottom: 0 !important;
                    background: rgba(255, 255, 255, 0.1) !important;
                    border-radius: inherit !important;
                    pointer-events: none !important;
                }
                
                body.dark-mode .stat-card.touching::before {
                    background: rgba(255, 255, 255, 0.05) !important;
                }
            }
            
            /* Styles pour l'aperçu rapide */
            .quick-preview-tooltip {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            }
        `;
        
        document.head.appendChild(style);
    }
    
    // ====================================================================
    // ANALYTICS ET SUIVI (OPTIONNEL)
    // ====================================================================
    
    function trackButtonInteraction(buttonIndex, interactionType) {
        // Envoyer des données d'usage (si nécessaire)
        const buttonNames = ['Réparation', 'Tâches', 'Commandes', 'Urgence'];
        const buttonName = buttonNames[buttonIndex] || 'Inconnu';
        
        // Exemple : console.log pour le debug
        if (window.console && window.console.log) {
            console.log(`Interaction ${interactionType} sur bouton ${buttonName}`);
        }
        
        // Ici on pourrait ajouter Google Analytics, etc.
        // gtag('event', 'button_interaction', {
        //     button_name: buttonName,
        //     interaction_type: interactionType
        // });
    }
    
    // ====================================================================
    // INITIALISATION
    // ====================================================================
    
    function init() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        try {
            // Injecter les styles dynamiques
            injectDynamicStyles();
            
            // Initialiser les interactions tactiles
            initTouchInteractions();
            
            // Marquer comme initialisé
            document.body.classList.add('mobile-buttons-initialized');
            
            console.log('Mobile buttons 2x2 interactions initialized');
            
        } catch (error) {
            console.warn('Erreur lors de l\'initialisation des interactions mobiles:', error);
        }
    }
    
    // ====================================================================
    // NETTOYAGE ET OPTIMISATIONS
    // ====================================================================
    
    // Désactiver les interactions sur les appareils moins performants
    if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 2) {
        config.hapticFeedback = false;
        config.visualFeedback = false;
    }
    
    // Démarrer l'initialisation
    init();
    
})();

/**
 * ====================================================================
 * UTILITAIRES GLOBAUX
 * ====================================================================
 */

// Exposer quelques fonctions utiles globalement
window.mobileButtons2x2 = {
    // Fonction pour déclencher manuellement un feedback
    triggerFeedback: function(type) {
        if (navigator.vibrate) {
            const patterns = {
                light: [10],
                medium: [20],
                heavy: [30]
            };
            navigator.vibrate(patterns[type] || patterns.light);
        }
    },
    
    // Fonction pour vérifier si les interactions tactiles sont actives
    isInitialized: function() {
        return document.body.classList.contains('mobile-buttons-initialized');
    }
};
