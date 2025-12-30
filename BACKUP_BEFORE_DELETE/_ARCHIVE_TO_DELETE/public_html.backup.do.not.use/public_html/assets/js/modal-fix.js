/**
 * Correction des problèmes "Illegal invocation" avec Bootstrap Modal
 * Ce script corrige les conflits entre différentes instances de modals
 * et améliore la stabilité de l'ouverture/fermeture des modals
 */

(function() {
    'use strict';
    
    console.log('🔧 Chargement du correctif Modal...');
    
    // Attendre que Bootstrap soit chargé
    function waitForBootstrap(callback) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            callback();
        } else {
            setTimeout(() => waitForBootstrap(callback), 100);
        }
    }
    
    waitForBootstrap(function() {
        console.log('✅ Bootstrap détecté, application des correctifs...');
        
        // Sauvegarder les méthodes originales de Bootstrap Modal
        const originalShow = bootstrap.Modal.prototype.show;
        const originalHide = bootstrap.Modal.prototype.hide;
        const originalDispose = bootstrap.Modal.prototype.dispose;
        
        // Correction pour la méthode show
        bootstrap.Modal.prototype.show = function() {
            try {
                // Exceptions pour les modals problématiques
                const problematicModals = ['updateStatusModal', 'relanceClientModal'];
                const isProblematicModal = problematicModals.includes(this._element.id);
                
                if (!isProblematicModal) {
                    // Nettoyer les backdrops existants avant d'ouvrir un nouveau modal
                    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                    if (existingBackdrops.length > 1) {
                        // Garder seulement le dernier backdrop
                        for (let i = 0; i < existingBackdrops.length - 1; i++) {
                            existingBackdrops[i].remove();
                        }
                    }
                }
                
                // Appeler la méthode originale avec le bon contexte
                return originalShow.call(this);
            } catch (error) {
                console.error('Erreur lors de l\'ouverture du modal:', error);
                // En cas d'erreur, nettoyer l'état et réessayer seulement si ce n'est pas un modal problématique
                const problematicModals = ['updateStatusModal', 'relanceClientModal'];
                const isProblematicModal = problematicModals.includes(this._element.id);
                
                if (!isProblematicModal) {
                    this._cleanupModal();
                }
                return originalShow.call(this);
            }
        };
        
        // Correction pour la méthode hide
        bootstrap.Modal.prototype.hide = function() {
            try {
                return originalHide.call(this);
            } catch (error) {
                console.error('Erreur lors de la fermeture du modal:', error);
                // En cas d'erreur, forcer le nettoyage
                this._cleanupModal();
            }
        };
        
        // Correction pour la méthode dispose
        bootstrap.Modal.prototype.dispose = function() {
            try {
                return originalDispose.call(this);
            } catch (error) {
                console.error('Erreur lors de la suppression du modal:', error);
                // En cas d'erreur, forcer le nettoyage
                this._cleanupModal();
            }
        };
        
        // Méthode de nettoyage personnalisée
        bootstrap.Modal.prototype._cleanupModal = function() {
            try {
                // Supprimer la classe modal-open du body
                document.body.classList.remove('modal-open');
                
                // Réinitialiser les styles du body
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Supprimer tous les backdrops
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                
                // Masquer le modal s'il est visible
                if (this._element) {
                    this._element.style.display = 'none';
                    this._element.classList.remove('show');
                    this._element.setAttribute('aria-hidden', 'true');
                    this._element.removeAttribute('aria-modal');
                }
                
                console.log('🧹 Modal nettoyé');
            } catch (error) {
                console.error('Erreur lors du nettoyage du modal:', error);
            }
        };
        
        // Fonction utilitaire globale pour ouvrir un modal de manière sécurisée
        window.openModalSafely = function(modalId, options = {}) {
            try {
                // Fermer tous les modals ouverts d'abord
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    const instance = bootstrap.Modal.getInstance(modal);
                    if (instance) {
                        instance.hide();
                    }
                });
                
                // Attendre un peu puis ouvrir le nouveau modal
                setTimeout(() => {
                    const modalElement = document.getElementById(modalId);
                    if (modalElement) {
                        const modal = new bootstrap.Modal(modalElement, {
                            backdrop: 'static',
                            keyboard: false,
                            ...options
                        });
                        modal.show();
                    } else {
                        console.error(`Modal avec l'ID ${modalId} non trouvé`);
                    }
                }, 100);
                
            } catch (error) {
                console.error('Erreur lors de l\'ouverture sécurisée du modal:', error);
            }
        };
        
        // Fonction utilitaire globale pour fermer tous les modals
        window.closeAllModals = function() {
            try {
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    const instance = bootstrap.Modal.getInstance(modal);
                    if (instance) {
                        instance.hide();
                    }
                });
                
                // Nettoyage forcé après un délai
                setTimeout(() => {
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                }, 300);
                
            } catch (error) {
                console.error('Erreur lors de la fermeture de tous les modals:', error);
            }
        };
        
        // Écouter les erreurs JavaScript globales et nettoyer si nécessaire
        window.addEventListener('error', function(event) {
            if (event.error && event.error.message && event.error.message.includes('Illegal invocation')) {
                console.warn('Erreur "Illegal invocation" détectée, nettoyage des modals...');
                setTimeout(() => {
                    window.closeAllModals();
                }, 100);
            }
        });
        
        // Nettoyage périodique des backdrops orphelins (plus conservateur)
        setInterval(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            const openModals = document.querySelectorAll('.modal.show');
            
            // Ne nettoyer que s'il y a vraiment beaucoup plus de backdrops que de modals
            if (backdrops.length > openModals.length + 1) {
                console.log('🧹 Nettoyage des backdrops orphelins...');
                const excessBackdrops = Array.from(backdrops).slice(openModals.length + 1);
                excessBackdrops.forEach(backdrop => backdrop.remove());
            }
        }, 10000); // Moins fréquent pour éviter les interférences
        
        console.log('✅ Correctifs Modal appliqués avec succès');
    });
    
})();