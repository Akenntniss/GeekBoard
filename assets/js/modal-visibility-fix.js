/**
 * Correctif pour les problèmes de visibilité des modals
 * Spécifiquement pour updateStatusModal et relanceClientModal
 */

(function() {
    'use strict';
    
    console.log('🔧 Initialisation du correctif de visibilité des modals...');
    
    // Attendre que le DOM soit chargé
    document.addEventListener('DOMContentLoaded', function() {
        
        // Fonction pour forcer l'affichage d'un modal
        function forceShowModal(modalId) {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.error(`Modal ${modalId} non trouvé`);
                return;
            }
            
            // Nettoyer l'état précédent
            modalElement.style.display = '';
            modalElement.style.visibility = '';
            modalElement.style.opacity = '';
            modalElement.classList.remove('d-none');
            
            // Forcer les styles d'affichage
            modalElement.style.display = 'block';
            modalElement.style.visibility = 'visible';
            modalElement.style.opacity = '1';
            modalElement.classList.add('show');
            modalElement.setAttribute('aria-modal', 'true');
            modalElement.removeAttribute('aria-hidden');
            
            // S'assurer que le z-index est correct
            modalElement.style.zIndex = '1055';
            
            // Ajouter le backdrop si nécessaire
            if (!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.style.zIndex = '1050';
                document.body.appendChild(backdrop);
            }
            
            // Ajouter la classe modal-open au body
            document.body.classList.add('modal-open');
            
            console.log(`✅ Modal ${modalId} forcé à l'affichage`);
        }
        
        // Fonction pour gérer les clics sur les boutons de modal
        function setupModalButton(buttonSelector, modalId) {
            const button = document.querySelector(buttonSelector);
            if (!button) {
                console.warn(`Bouton ${buttonSelector} non trouvé`);
                return;
            }
            
            // Supprimer les anciens event listeners
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Ajouter le nouveau event listener
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log(`🔄 Tentative d'ouverture du modal ${modalId}`);
                
                // Fermer tous les autres modals d'abord
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                });
                
                // Supprimer tous les backdrops existants
                const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                existingBackdrops.forEach(backdrop => backdrop.remove());
                
                // Attendre un peu puis forcer l'affichage
                setTimeout(() => {
                    forceShowModal(modalId);
                }, 100);
            });
            
            console.log(`✅ Bouton ${buttonSelector} configuré pour ${modalId}`);
        }
        
        // Configuration des boutons problématiques
        setTimeout(() => {
            setupModalButton('button[data-bs-target="#updateStatusModal"]', 'updateStatusModal');
            setupModalButton('button[data-bs-target="#relanceClientModal"]', 'relanceClientModal');
            
            // Gérer les boutons de fermeture
            document.addEventListener('click', function(e) {
                if (e.target.matches('[data-bs-dismiss="modal"]') || 
                    e.target.closest('[data-bs-dismiss="modal"]')) {
                    
                    // Fermer tous les modals
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modal => {
                        modal.classList.remove('show');
                        modal.style.display = 'none';
                        modal.setAttribute('aria-hidden', 'true');
                        modal.removeAttribute('aria-modal');
                    });
                    
                    // Supprimer les backdrops
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    
                    // Supprimer la classe modal-open
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    
                    console.log('🔒 Modals fermés');
                }
            });
            
            // Gérer les clics sur le backdrop
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-backdrop')) {
                    // Fermer le modal actif
                    const activeModal = document.querySelector('.modal.show');
                    if (activeModal) {
                        activeModal.classList.remove('show');
                        activeModal.style.display = 'none';
                        activeModal.setAttribute('aria-hidden', 'true');
                        activeModal.removeAttribute('aria-modal');
                    }
                    
                    // Supprimer les backdrops
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    
                    // Supprimer la classe modal-open
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            });
            
            console.log('✅ Correctif de visibilité des modals activé');
            
        }, 500); // Attendre que tous les scripts soient chargés
    });
    
})();
