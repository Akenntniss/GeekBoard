/**
 * PROTECTION DES TABLEAUX CONTRE LES MODIFICATIONS DYNAMIQUES
 * 
 * Ce script empêche les autres scripts (notamment responsive.js) de modifier
 * la structure des tableaux et causer le décalage d'alignement.
 * 
 * PAGES CONCERNÉES :
 * - clients.php
 * - reparations.php  
 * - rachat_appareils.php / rachat_appareils_advanced.php
 */

(function() {
    'use strict';
    
    console.log('🛡️ Protection des tableaux activée');
    
    // =============================================================================
    // DÉTECTION DE LA PAGE COURANTE
    // =============================================================================
    
    function detectCurrentPage() {
        const url = window.location.href;
        const params = new URLSearchParams(window.location.search);
        const page = params.get('page');
        
        let currentPage = 'unknown';
        
        if (page === 'clients' || url.includes('clients')) {
            currentPage = 'clients';
        } else if (page === 'reparations' || url.includes('reparations')) {
            currentPage = 'reparations';
        } else if (page === 'rachat_appareils' || url.includes('rachat')) {
            currentPage = 'rachat';
        }
        
        // Ajouter l'attribut data-page au body
        if (document.body && currentPage !== 'unknown') {
            document.body.setAttribute('data-page', currentPage);
            console.log('📄 Page détectée:', currentPage);
        }
        
        return currentPage;
    }
    
    // =============================================================================
    // PROTECTION CONTRE LES MODIFICATIONS DOM
    // =============================================================================
    
    let protectionActive = false;
    
    function enableProtection() {
        if (protectionActive) return;
        protectionActive = true;
        
        console.log('🔒 Activation de la protection DOM');
        
        // Bloquer la modification des classes sur les tableaux
        const originalAddClass = Element.prototype.classList.add;
        Element.prototype.classList.add = function(...classes) {
            // Si c'est un table-responsive et qu'on essaie d'ajouter d-none d-md-block
            if (this.classList.contains('table-responsive') && 
                classes.includes('d-none') && classes.includes('d-md-block')) {
                console.log('🚫 Bloqué: Tentative d\'ajout de classes d-none d-md-block sur table-responsive');
                return;
            }
            return originalAddClass.apply(this, classes);
        };
        
        const originalRemoveClass = Element.prototype.classList.remove;
        Element.prototype.classList.remove = function(...classes) {
            // Empêcher la suppression de classes importantes
            if (this.classList.contains('table-responsive') && 
                (classes.includes('table-responsive') || classes.includes('d-block'))) {
                console.log('🚫 Bloqué: Tentative de suppression de classes importantes sur table-responsive');
                return;
            }
            return originalRemoveClass.apply(this, classes);
        };
        
        // Bloquer l'insertion d'éléments mobile-cards-container
        const originalInsertBefore = Node.prototype.insertBefore;
        Node.prototype.insertBefore = function(newNode, referenceNode) {
            if (newNode.nodeType === Node.ELEMENT_NODE && 
                newNode.classList && newNode.classList.contains('mobile-cards-container')) {
                console.log('🚫 Bloqué: Tentative d\'insertion de mobile-cards-container');
                return newNode; // Retourner le noeud sans l'insérer
            }
            return originalInsertBefore.call(this, newNode, referenceNode);
        };
        
        const originalAppendChild = Node.prototype.appendChild;
        Node.prototype.appendChild = function(child) {
            if (child.nodeType === Node.ELEMENT_NODE && 
                child.classList && child.classList.contains('mobile-cards-container')) {
                console.log('🚫 Bloqué: Tentative d\'ajout de mobile-cards-container');
                return child; // Retourner le noeud sans l'ajouter
            }
            return originalAppendChild.call(this, child);
        };
    }
    
    // =============================================================================
    // MUTATION OBSERVER POUR SURVEILLER LES CHANGEMENTS
    // =============================================================================
    
    function startObserver() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Supprimer les mobile-cards-container ajoutés
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE && 
                            node.classList && node.classList.contains('mobile-cards-container')) {
                            console.log('🗑️ Suppression de mobile-cards-container détecté');
                            node.remove();
                        }
                }
                
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    // Restaurer les classes si elles ont été modifiées sur table-responsive
                    if (target.classList.contains('table-responsive')) {
                        if (target.classList.contains('d-none') && target.classList.contains('d-md-block')) {
                            console.log('🔄 Restauration des classes table-responsive');
                            target.classList.remove('d-none', 'd-md-block');
                            
                            // Forcer l'affichage
                            target.style.display = 'block';
                            target.style.visibility = 'visible';
                            target.style.opacity = '1';
                        }
                    }
                }
                
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const target = mutation.target;
                    // Restaurer l'affichage si un script essaie de cacher le tableau
                    if (target.classList.contains('table-responsive') && 
                        (target.style.display === 'none' || target.style.visibility === 'hidden')) {
                        console.log('🔄 Restauration de l\'affichage table-responsive');
                        target.style.display = 'block';
                        target.style.visibility = 'visible';
                        target.style.opacity = '1';
                    }
                }
        
        // Démarrer l'observation
        observer.observe(document.body, {
            childList: true
            subtree: true
            attributes: true
            attributeFilter: ['class', 'style']
        
        console.log('👀 Observer activé pour protéger les tableaux');
        
        // Arrêter l'observation après 10 secondes (le temps que les autres scripts finissent)
        setTimeout(() => {
            observer.disconnect();
            console.log('✅ Observer désactivé - Protection terminée');
        }, 10000);
    }
    
    // =============================================================================
    // CORRECTION IMMÉDIATE DES TABLEAUX EXISTANTS
    // =============================================================================
    
    function fixExistingTables() {
        console.log('🔧 Correction des tableaux existants');
        
        const tableResponsives = document.querySelectorAll('.table-responsive');
        tableResponsives.forEach(function(element) {
            // Supprimer les classes problématiques
            element.classList.remove('d-none', 'd-md-block');
            
            // Forcer l'affichage correct
            element.style.display = 'block';
            element.style.visibility = 'visible';
            element.style.opacity = '1';
            element.style.width = '100%';
            element.style.overflowX = 'auto';
            
            console.log('✅ Table responsive corrigée');
        
        // Supprimer tous les mobile-cards-container existants
        const mobileContainers = document.querySelectorAll('.mobile-cards-container');
        mobileContainers.forEach(function(container) {
            console.log('🗑️ Suppression de mobile-cards-container existant');
            container.remove();
        
        // Forcer l'alignement des colonnes
        const tables = document.querySelectorAll('.table');
        tables.forEach(function(table) {
            // Réinitialiser les styles qui pourraient causer le problème
            table.style.tableLayout = 'auto';
            table.style.width = '100%';
            table.style.borderCollapse = 'separate';
            table.style.borderSpacing = '0';
            
            // Forcer le padding sur toutes les cellules
            const cells = table.querySelectorAll('th, td');
            cells.forEach(function(cell) {
                cell.style.padding = '0.75rem';
                cell.style.boxSizing = 'border-box';
                cell.style.verticalAlign = 'middle';
            
            console.log('✅ Tableau corrigé');
    }
    
    // =============================================================================
    // INITIALISATION
    // =============================================================================
    
    function initialize() {
        console.log('🚀 Initialisation de la protection des tableaux');
        
        // Détecter la page courante
        const currentPage = detectCurrentPage();
        
        // Activer seulement sur les pages concernées
        if (['clients', 'reparations', 'rachat'].includes(currentPage)) {
            console.log('✅ Page concernée détectée, activation de la protection');
            
            // Activer la protection
            enableProtection();
            
            // Corriger les tableaux existants
            fixExistingTables();
            
            // Démarrer l'observer
            startObserver();
            
            // Re-corriger après 1 seconde au cas où d'autres scripts modifient les tables
            setTimeout(fixExistingTables, 1000);
            
            // Et encore après 3 secondes pour être sûr
            setTimeout(fixExistingTables, 3000);
            
        } else {
            console.log('ℹ️ Page non concernée, protection non activée');
        }
    }
    
    // =============================================================================
    // DÉMARRAGE
    // =============================================================================
    
    // Si le DOM est déjà prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        // DOM déjà prêt, initialiser immédiatement
        initialize();
    }
    
    // Également initialiser au chargement complet de la page
    if (document.readyState !== 'complete') {
        window.addEventListener('load', function() {
            // Re-corriger une dernière fois après le chargement complet
            setTimeout(fixExistingTables, 500);
    }
    
})(); 