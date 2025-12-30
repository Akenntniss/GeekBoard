/* ====================================================================
   SCRIPT POUR SUPPRIMER LE BACKDROP DU MODAL AJOUTER TÂCHE
   Solution JavaScript pour compléter la solution CSS
==================================================================== */

(function() {
    'use strict';
    
    console.log('🚫 [NO-BACKDROP] Initialisation de la suppression du backdrop...');
    
    /**
     * Supprimer tous les backdrops existants pour le modal spécifique
     */
    function removeBackdropsForModal(modalId) {
        // Chercher tous les backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        
        backdrops.forEach(backdrop => {
            // Vérifier si ce backdrop est lié au modal
            if (backdrop.classList.contains(`${modalId}-backdrop`) ||
                backdrop.getAttribute('data-modal-id') === modalId ||
                (modalId === 'ajouterTacheModal' && backdrop.classList.contains('modal-backdrop'))) {
                
                console.log(`🚫 [NO-BACKDROP] Suppression du backdrop pour ${modalId}`);
                backdrop.remove();
            }
        });
    }
    
    /**
     * Observer pour supprimer automatiquement les backdrops créés
     */
    function observeBackdropCreation() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE && 
                            node.classList && node.classList.contains('modal-backdrop')) {
                            
                            // Vérifier si le modal ajouterTacheModal est ouvert
                            const modalTache = document.getElementById('ajouterTacheModal');
                            if (modalTache && modalTache.classList.contains('show')) {
                                console.log('🚫 [NO-BACKDROP] Backdrop détecté pendant que ajouterTacheModal est ouvert - suppression');
                                node.remove();
                            }
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('🚫 [NO-BACKDROP] Observer des backdrops installé');
    }
    
    /**
     * Intercepter les événements Bootstrap pour le modal ajouterTacheModal
     */
    function interceptModalEvents() {
        document.addEventListener('show.bs.modal', function(event) {
            if (event.target.id === 'ajouterTacheModal') {
                console.log('🚫 [NO-BACKDROP] Modal ajouterTacheModal en cours d\'ouverture');
                
                // Supprimer immédiatement tout backdrop existant
                setTimeout(() => {
                    removeBackdropsForModal('ajouterTacheModal');
                }, 10);
            }
        });
        
        document.addEventListener('shown.bs.modal', function(event) {
            if (event.target.id === 'ajouterTacheModal') {
                console.log('🚫 [NO-BACKDROP] Modal ajouterTacheModal affiché');
                
                // Double vérification - supprimer tout backdrop créé
                setTimeout(() => {
                    removeBackdropsForModal('ajouterTacheModal');
                }, 100);
                
                // Ajouter une classe au body pour le CSS
                document.body.classList.add('ajouterTacheModal-open');
            }
        });
        
        document.addEventListener('hide.bs.modal', function(event) {
            if (event.target.id === 'ajouterTacheModal') {
                console.log('🚫 [NO-BACKDROP] Modal ajouterTacheModal en cours de fermeture');
                
                // Retirer la classe du body
                document.body.classList.remove('ajouterTacheModal-open');
            }
        });
        
        document.addEventListener('hidden.bs.modal', function(event) {
            if (event.target.id === 'ajouterTacheModal') {
                console.log('🚫 [NO-BACKDROP] Modal ajouterTacheModal fermé');
                
                // Nettoyage final
                removeBackdropsForModal('ajouterTacheModal');
                document.body.classList.remove('ajouterTacheModal-open');
            }
        });
    }
    
    /**
     * Configuration Bootstrap pour désactiver le backdrop
     */
    function configureBootstrapModal() {
        // Attendre que Bootstrap soit chargé
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalElement = document.getElementById('ajouterTacheModal');
            
            if (modalElement) {
                // S'assurer que l'attribut data-bs-backdrop est défini
                modalElement.setAttribute('data-bs-backdrop', 'false');
                modalElement.setAttribute('data-bs-keyboard', 'true'); // Garder la fermeture avec Escape
                
                console.log('🚫 [NO-BACKDROP] Configuration Bootstrap appliquée');
                
                // Si une instance Bootstrap existe déjà, la reconfigurer
                const existingInstance = bootstrap.Modal.getInstance(modalElement);
                if (existingInstance) {
                    existingInstance._config.backdrop = false;
                    console.log('🚫 [NO-BACKDROP] Instance Bootstrap reconfigurée');
                }
            }
        } else {
            // Réessayer plus tard si Bootstrap n'est pas encore chargé
            setTimeout(configureBootstrapModal, 100);
        }
    }
    
    /**
     * Fonction de nettoyage forcé (pour le debug)
     */
    function forceCleanBackdrops() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        console.log(`🚫 [NO-BACKDROP] Nettoyage forcé - ${backdrops.length} backdrop(s) trouvé(s)`);
        
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        
        // Retirer les classes du body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    /**
     * Initialisation
     */
    function init() {
        console.log('🚫 [NO-BACKDROP] Initialisation...');
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                interceptModalEvents();
                observeBackdropCreation();
                configureBootstrapModal();
            });
        } else {
            interceptModalEvents();
            observeBackdropCreation();
            configureBootstrapModal();
        }
        
        console.log('🚫 [NO-BACKDROP] ✅ Script initialisé');
    }
    
    // Exposer les fonctions globalement pour le debug
    window.modalNoBackdrop = {
        removeBackdrops: () => removeBackdropsForModal('ajouterTacheModal'),
        forceClean: forceCleanBackdrops,
        configure: configureBootstrapModal
    };
    
    // Initialiser
    init();
    
})();

console.log('🚫 [NO-BACKDROP] ✅ Script chargé');
console.log('🚫 [NO-BACKDROP] 💡 Utilisez window.modalNoBackdrop.forceClean() pour nettoyer manuellement');
