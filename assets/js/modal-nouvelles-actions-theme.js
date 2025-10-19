/**
 * MODAL NOUVELLES ACTIONS - GESTIONNAIRE DE THÈME
 * Gère l'application du thème clair corporate par défaut
 * et la détection du changement de thème
 */

class ModalNouvellesActionsTheme {
    constructor() {
        console.log('🎨 Initialisation du gestionnaire de thème pour nouvelles_actions_modal');
        
        this.modal = document.getElementById('nouvelles_actions_modal');
        this.currentTheme = 'clair'; // Par défaut en mode clair
        
        this.init();
    }
    
    init() {
        if (!this.modal) {
            console.warn('🎨 Modal nouvelles_actions_modal non trouvé');
            return;
        }
        
        // Appliquer le thème clair par défaut
        this.applyTheme('clair');
        
        // Écouter les événements de changement de thème
        this.setupThemeListeners();
        
        // Écouter l'ouverture du modal pour s'assurer que le thème est appliqué
        this.setupModalListeners();
        
        console.log('🎨 Gestionnaire de thème initialisé avec succès');
    }
    
    setupThemeListeners() {
        // Écouter les changements de thème système (optionnel)
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener((e) => {
                // Ne pas changer automatiquement, garder le mode clair par défaut
                console.log('🎨 Changement de thème système détecté, mais mode clair maintenu');
            });
        }
        
        // Écouter les événements personnalisés de changement de thème
        document.addEventListener('themeChange', (e) => {
            const newTheme = e.detail?.theme || 'clair';
            console.log('🎨 Changement de thème détecté:', newTheme);
            this.applyTheme(newTheme);
        });
        
        // Écouter les clics sur les boutons de thème (si ils existent)
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-theme]')) {
                const theme = e.target.getAttribute('data-theme');
                console.log('🎨 Bouton de thème cliqué:', theme);
                this.applyTheme(theme);
            }
        });
    }
    
    setupModalListeners() {
        // Écouter l'ouverture du modal
        this.modal.addEventListener('show.bs.modal', () => {
            console.log('🎨 Modal en cours d\'ouverture, application du thème:', this.currentTheme);
            this.applyTheme(this.currentTheme);
        });
        
        this.modal.addEventListener('shown.bs.modal', () => {
            console.log('🎨 Modal ouvert, vérification du thème');
            this.verifyTheme();
            this.fixCloseButton();
        });
    }
    
    applyTheme(theme) {
        if (!this.modal) return;
        
        console.log('🎨 Application du thème:', theme);
        
        // Supprimer toutes les classes de thème existantes
        this.modal.classList.remove('dark-mode', 'light-mode', 'corporate-mode');
        
        // Appliquer le nouveau thème
        switch (theme) {
            case 'nuit':
            case 'dark':
                this.modal.classList.add('dark-mode');
                this.currentTheme = 'nuit';
                console.log('🎨 Mode nuit appliqué');
                break;
                
            case 'clair':
            case 'light':
            case 'corporate':
            default:
                // Mode clair corporate par défaut
                this.modal.classList.add('corporate-mode');
                this.currentTheme = 'clair';
                console.log('🎨 Mode clair corporate appliqué');
                break;
        }
        
        // Déclencher un événement personnalisé
        const event = new CustomEvent('modalThemeChanged', {
            detail: {
                modal: 'nouvelles_actions_modal',
                theme: this.currentTheme
            }
        });
        document.dispatchEvent(event);
    }
    
    verifyTheme() {
        if (!this.modal) return;
        
        const hasLight = this.modal.classList.contains('corporate-mode');
        const hasDark = this.modal.classList.contains('dark-mode');
        
        console.log('🎨 Vérification du thème - Clair:', hasLight, 'Nuit:', hasDark);
        
        // Si aucun thème n'est appliqué, forcer le mode clair
        if (!hasLight && !hasDark) {
            console.log('🎨 Aucun thème détecté, application du mode clair par défaut');
            this.applyTheme('clair');
        }
    }
    
    fixCloseButton() {
        if (!this.modal) return;
        
        const closeButton = this.modal.querySelector('.btn-close');
        if (!closeButton) {
            console.warn('🎨 Bouton de fermeture non trouvé');
            return;
        }
        
        // S'assurer que le bouton est visible et fonctionnel
        closeButton.style.display = 'flex';
        closeButton.style.visibility = 'visible';
        closeButton.style.opacity = '1';
        closeButton.style.pointerEvents = 'auto';
        
        // Vérifier que l'événement de fermeture est attaché
        if (!closeButton.hasAttribute('data-bs-dismiss')) {
            closeButton.setAttribute('data-bs-dismiss', 'modal');
        }
        
        // Ajouter un gestionnaire d'événement de secours
        closeButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Fermer le modal manuellement si Bootstrap ne fonctionne pas
            if (window.bootstrap && window.bootstrap.Modal) {
                const modalInstance = window.bootstrap.Modal.getInstance(this.modal);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    // Créer une nouvelle instance et fermer
                    const newModalInstance = new window.bootstrap.Modal(this.modal);
                    newModalInstance.hide();
                }
            } else {
                // Fallback : fermeture manuelle
                this.modal.classList.remove('show');
                this.modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                
                // Supprimer le backdrop
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        });
        
        console.log('🎨 Bouton de fermeture corrigé et vérifié');
    }
    
    getCurrentTheme() {
        return this.currentTheme;
    }
    
    setTheme(theme) {
        this.applyTheme(theme);
    }
    
    toggleTheme() {
        const newTheme = this.currentTheme === 'clair' ? 'nuit' : 'clair';
        this.applyTheme(newTheme);
        return newTheme;
    }
}

// Initialisation automatique
let modalNouvellesActionsTheme;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 Initialisation du gestionnaire de thème nouvelles_actions_modal');
    modalNouvellesActionsTheme = new ModalNouvellesActionsTheme();
    
    // Fonction globale pour changer le thème
    window.setNouvellesActionsTheme = function(theme) {
        if (modalNouvellesActionsTheme) {
            modalNouvellesActionsTheme.setTheme(theme);
        }
    };
    
    // Fonction globale pour basculer le thème
    window.toggleNouvellesActionsTheme = function() {
        if (modalNouvellesActionsTheme) {
            return modalNouvellesActionsTheme.toggleTheme();
        }
        return 'clair';
    };
    
    console.log('✅ Gestionnaire de thème nouvelles_actions_modal initialisé');
});

// Écouter les changements de thème depuis modal-recherche-moderne.js
document.addEventListener('DOMContentLoaded', function() {
    // Observer les mutations pour détecter les changements de thème
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                // Vérifier si c'est un changement de thème sur un autre modal
                const target = mutation.target;
                if (target.classList.contains('recherche-modal-overlay')) {
                    const isDark = target.classList.contains('dark-mode');
                    const theme = isDark ? 'nuit' : 'clair';
                    
                    console.log('🎨 Changement de thème détecté sur modal recherche:', theme);
                    
                    // Appliquer le même thème au modal nouvelles actions
                    if (modalNouvellesActionsTheme) {
                        modalNouvellesActionsTheme.setTheme(theme);
                    }
                }
            }
        });
    });
    
    // Observer les changements sur le document
    observer.observe(document.body, {
        attributes: true,
        subtree: true,
        attributeFilter: ['class']
    });
});

// Export pour utilisation dans d'autres scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModalNouvellesActionsTheme;
}
