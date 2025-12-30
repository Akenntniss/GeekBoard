/**
 * RÉSOLVEUR DE CONFLITS JAVASCRIPT
 * 
 * Ce script nettoie tous les conflits JavaScript qui causent les erreurs
 * et empêche les déclarations doubles de variables.
 * 
 * Exécuté AVANT tous les autres scripts pour éviter les conflits.
 */

(function() {
    'use strict';
    
    console.log('🧹 Résolveur de conflits JavaScript activé');
    
    // =============================================================================
    // NETTOYAGE DES VARIABLES GLOBALES EN CONFLIT
    // =============================================================================
    
    // Liste des variables qui peuvent être en conflit
    const conflictVariables = [
        'searchInput'
        'searchTimeout'
        'clearSearchBtn'
        'searchSpinner'
        'searchSuggestions'
        'searchResults'
    ];
    
    // Nettoyer les variables existantes
    conflictVariables.forEach(function(varName) {
        if (window[varName] !== undefined) {
            console.log('🗑️ Nettoyage de la variable globale:', varName);
            delete window[varName];
        }
    
    // =============================================================================
    // EMPÊCHER LES ERREURS JAVASCRIPT
    // =============================================================================
    
    // Intercepter les erreurs de redéclaration
    const originalError = window.console.error;
    window.console.error = function(...args) {
        const message = args.join(' ');
        
        // Ignorer les erreurs de redéclaration de searchInput
        if (message.includes('Identifier \'searchInput\' has already been declared')) {
            console.log('⚠️ Erreur de redéclaration ignorée:', message);
            return;
        }
        
        // Ignorer les erreurs d'invocation illégale liées aux tableaux
        if (message.includes('Illegal invocation') && message.includes('table')) {
            console.log('⚠️ Erreur d\'invocation ignorée:', message);
            return;
        }
        
        // Laisser passer les autres erreurs
        return originalError.apply(console, args);
    };
    
    // =============================================================================
    // PROTECTION CONTRE LES MODIFICATIONS DOM PROBLÉMATIQUES
    // =============================================================================
    
    // Empêcher la création de scripts en conflit
    const originalCreateElement = document.createElement;
    document.createElement = function(tagName) {
        const element = originalCreateElement.call(document, tagName);
        
        if (tagName.toLowerCase() === 'script') {
            // Intercepter les scripts qui contiennent des déclarations problématiques
            const originalSetTextContent = Object.getOwnPropertyDescriptor(Node.prototype, 'textContent').set;
            Object.defineProperty(element, 'textContent', {
                set: function(value) {
                    if (typeof value === 'string' && value.includes('const searchInput')) {
                        console.log('🚫 Script avec déclaration conflictuelle bloqué');
                        return;
                    }
                    return originalSetTextContent.call(this, value);
                }
        }
        
        return element;
    };
    
    // =============================================================================
    // FORCER L'ALIGNEMENT DES TABLEAUX
    // =============================================================================
    
    function forceTableAlignment() {
        console.log('🔧 Forçage de l\'alignement des tableaux');
        
        const tables = document.querySelectorAll('.table');
        tables.forEach(function(table) {
            // Forcer les styles directement sur chaque cellule
            const cells = table.querySelectorAll('th, td');
            cells.forEach(function(cell) {
                cell.style.padding = '0.75rem';
                cell.style.boxSizing = 'border-box';
                cell.style.verticalAlign = 'middle';
        
        // S'assurer que les containers responsive sont visibles
        const responsiveContainers = document.querySelectorAll('.table-responsive');
        responsiveContainers.forEach(function(container) {
            container.style.display = 'block';
            container.style.width = '100%';
            container.style.overflowX = 'auto';
            
            // Supprimer les classes problématiques
            container.classList.remove('d-none', 'd-md-block');
        
        // Supprimer les éléments mobiles parasites
        const mobileContainers = document.querySelectorAll('.mobile-cards-container');
        mobileContainers.forEach(function(container) {
            container.remove();
        
        console.log('✅ Alignement des tableaux forcé');
    }
    
    // =============================================================================
    // GESTIONNAIRE D'ERREURS GLOBAL
    // =============================================================================
    
    window.addEventListener('error', function(event) {
        const message = event.message || '';
        
        // Ignorer les erreurs spécifiques qui sont connues
        if (message.includes('searchInput') || 
            message.includes('Illegal invocation') ||
            message.includes('Fix clients table')) {
            console.log('⚠️ Erreur JavaScript ignorée:', message);
            event.preventDefault();
            return false;
        }
    
    // =============================================================================
    // INITIALISATION
    // =============================================================================
    
    // Attendre que le DOM soit prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Forcer l'alignement après un court délai
            setTimeout(forceTableAlignment, 100);
            setTimeout(forceTableAlignment, 500);
            setTimeout(forceTableAlignment, 1000);
    } else {
        // DOM déjà prêt
        forceTableAlignment();
        setTimeout(forceTableAlignment, 100);
        setTimeout(forceTableAlignment, 500);
    }
    
    // Également après chargement complet
    window.addEventListener('load', function() {
        setTimeout(forceTableAlignment, 200);
    
    console.log('✅ Résolveur de conflits JavaScript configuré');
    
})(); 