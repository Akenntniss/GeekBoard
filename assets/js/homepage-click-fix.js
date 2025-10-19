/* ====================================================================
   🖱️ CORRECTION PROBLÈME CLICS PAGE D'ACCUEIL
   Corrige les conflits de clics entre les différentes sections
==================================================================== */

(function() {
    'use strict';
    
    console.log('🖱️ [CLICK-FIX] Initialisation de la correction des clics...');
    
    // Variables pour tracker les clics
    let lastClickTime = 0;
    let lastClickTarget = null;
    
    // Fonction de diagnostic des éléments qui se chevauchent
    function diagnoseOverlappingElements() {
        console.log('🖱️ [CLICK-FIX] === DIAGNOSTIC CHEVAUCHEMENTS ===');
        
        const elements = document.querySelectorAll('.modern-table-row, .table-section, [onclick]');
        const overlaps = [];
        
        elements.forEach((el, index) => {
            const rect = el.getBoundingClientRect();
            const info = {
                element: el,
                rect: rect,
                id: el.id || el.className || `element-${index}`,
                onclick: el.onclick ? 'OUI' : 'NON',
                zIndex: window.getComputedStyle(el).zIndex
            };
            
            console.log(`🖱️ [CLICK-FIX] ${info.id}: onclick=${info.onclick}, z-index=${info.zIndex}`);
            
            // Vérifier les chevauchements
            elements.forEach((other, otherIndex) => {
                if (index !== otherIndex) {
                    const otherRect = other.getBoundingClientRect();
                    if (rectsOverlap(rect, otherRect)) {
                        overlaps.push({ el1: el, el2: other });
                    }
                }
            });
        });
        
        if (overlaps.length > 0) {
            console.warn('⚠️ [CLICK-FIX] Chevauchements détectés:', overlaps.length);
            overlaps.forEach(overlap => {
                console.warn('⚠️ Chevauchement entre:', overlap.el1.className, 'et', overlap.el2.className);
            });
        }
        
        return { elements, overlaps };
    }
    
    // Fonction pour vérifier si deux rectangles se chevauchent
    function rectsOverlap(rect1, rect2) {
        return !(rect1.right < rect2.left || 
                rect2.right < rect1.left || 
                rect1.bottom < rect2.top || 
                rect2.bottom < rect1.top);
    }
    
    // Fonction pour corriger les événements de clic
    function fixClickEvents() {
        console.log('🖱️ [CLICK-FIX] Correction des événements de clic...');
        
        // 1. Nettoyer tous les onclick existants problématiques
        const elementsWithOnclick = document.querySelectorAll('[onclick]');
        elementsWithOnclick.forEach(el => {
            if (el.closest('.modern-table-row')) {
                // Sauvegarder la fonction onclick pour la réutiliser proprement
                const onclickStr = el.getAttribute('onclick');
                console.log('🖱️ [CLICK-FIX] Nettoyage onclick:', onclickStr);
                
                // Supprimer l'onclick et ajouter un event listener propre
                el.removeAttribute('onclick');
                
                // Ajouter un event listener contrôlé
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const now = Date.now();
                    if (now - lastClickTime < 300 && lastClickTarget === this) {
                        console.log('🖱️ [CLICK-FIX] Double-clic ignoré');
                        return;
                    }
                    
                    lastClickTime = now;
                    lastClickTarget = this;
                    
                    console.log('🖱️ [CLICK-FIX] Clic contrôlé sur:', this.className);
                    
                    // Exécuter la fonction originale
                    try {
                        eval(onclickStr);
                    } catch (error) {
                        console.error('❌ [CLICK-FIX] Erreur onclick:', error);
                    }
                });
            }
        });
        
        // 2. Fixer spécifiquement les tableaux
        fixTableClicks();
        
        // 3. Ajouter une protection globale contre les mauvais clics
        addGlobalClickProtection();
    }
    
    // Fonction pour corriger les clics dans les tableaux
    function fixTableClicks() {
        console.log('🖱️ [CLICK-FIX] Correction des clics dans les tableaux...');
        
        // Identifier les sections de tableau
        const tableSections = document.querySelectorAll('.table-section, .simple-table-section');
        
        tableSections.forEach((section, index) => {
            console.log(`🖱️ [CLICK-FIX] Section ${index}:`, section.querySelector('h4')?.textContent?.trim());
            
            // Ajouter une zone de clic claire pour chaque section
            section.style.position = 'relative';
            section.style.zIndex = (100 + index).toString();
            
            // Empêcher la propagation des clics entre sections
            section.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Corriger les lignes de tableau dans cette section
            const tableRows = section.querySelectorAll('.modern-table-row');
            tableRows.forEach(row => {
                // S'assurer que chaque ligne a un z-index correct
                row.style.position = 'relative';
                row.style.zIndex = (200 + index).toString();
                
                // Ajouter un indicateur visuel au survol pour debug
                row.addEventListener('mouseenter', function() {
                    console.log('🖱️ [CLICK-FIX] Survol ligne:', this.dataset.commandeId || this.dataset.taskId || 'inconnue');
                });
            });
        });
    }
    
    // Fonction de protection globale contre les mauvais clics
    function addGlobalClickProtection() {
        console.log('🖱️ [CLICK-FIX] Ajout protection globale...');
        
        document.addEventListener('click', function(e) {
            const clickInfo = {
                target: e.target,
                className: e.target.className,
                id: e.target.id,
                tagName: e.target.tagName,
                onclick: e.target.onclick ? 'OUI' : 'NON',
                parentClassName: e.target.parentElement?.className,
                timestamp: Date.now()
            };
            
            console.log('🖱️ [CLICK-FIX] Clic détecté:', clickInfo);
            
            // Vérifier si le clic est sur un élément problématique
            const problemElements = e.target.closest('.modern-table-row, .status-clickable, [data-commande-id], [data-task-id]');
            if (problemElements) {
                console.log('🖱️ [CLICK-FIX] Clic sur élément surveillé:', problemElements.className);
            }
        }, true); // Capture phase pour intercepter avant les autres handlers
    }
    
    // Fonction pour corriger les z-index
    function fixZIndexIssues() {
        console.log('🖱️ [CLICK-FIX] Correction des z-index...');
        
        // Réorganiser les z-index de manière logique
        const layers = {
            background: 1,
            tables: 10,
            tableRows: 20,
            overlays: 1000,
            modals: 2000
        };
        
        // Appliquer aux sections de tableau
        document.querySelectorAll('.table-section, .simple-table-section').forEach((section, index) => {
            section.style.zIndex = (layers.tables + index).toString();
        });
        
        // Appliquer aux lignes de tableau
        document.querySelectorAll('.modern-table-row').forEach((row, index) => {
            row.style.zIndex = (layers.tableRows + index).toString();
        });
        
        console.log('✅ [CLICK-FIX] Z-index corrigés');
    }
    
    // Fonction de test pour vérifier les corrections
    window.testHomepageClicks = function() {
        console.log('🧪 [CLICK-FIX] Test des clics page d\'accueil');
        
        const diagnosis = diagnoseOverlappingElements();
        
        // Simuler des clics sur différents éléments
        const testElements = document.querySelectorAll('.modern-table-row');
        testElements.forEach((el, index) => {
            if (index < 3) { // Tester seulement les 3 premiers
                console.log(`🧪 Test clic ${index + 1}:`, el.className);
                const event = new MouseEvent('click', { bubbles: true, cancelable: true });
                el.dispatchEvent(event);
            }
        });
    };
    
    // Fonction de diagnostic avancé
    window.diagnoseHomepageClicks = function() {
        console.log('🔍 [CLICK-FIX] === DIAGNOSTIC COMPLET ===');
        
        const diagnosis = diagnoseOverlappingElements();
        
        // Tester la position des éléments critiques
        const commandesSection = document.querySelector('h4 a[href*="commandes_pieces"]')?.closest('.table-section, .simple-table-section');
        const reparationsSection = document.querySelector('h4:contains("Réparations")')?.closest('.table-section, .simple-table-section');
        
        if (commandesSection) {
            const rect = commandesSection.getBoundingClientRect();
            console.log('📍 Section Commandes:', rect);
        }
        
        if (reparationsSection) {
            const rect = reparationsSection.getBoundingClientRect();
            console.log('📍 Section Réparations:', rect);
        }
        
        // Lister tous les événements onclick
        const onclickElements = document.querySelectorAll('[onclick]');
        console.log('🖱️ Éléments avec onclick:', onclickElements.length);
        onclickElements.forEach((el, index) => {
            console.log(`${index + 1}. ${el.tagName}.${el.className}: ${el.getAttribute('onclick')}`);
        });
        
        console.log('🔍 [CLICK-FIX] === FIN DIAGNOSTIC ===');
    };
    
    // Fonction principale d'initialisation
    function initializeClickFix() {
        console.log('🖱️ [CLICK-FIX] Initialisation complète...');
        
        // Attendre que le DOM soit complètement chargé
        setTimeout(() => {
            fixZIndexIssues();
            fixClickEvents();
            console.log('✅ [CLICK-FIX] Correction des clics terminée');
        }, 100);
    }
    
    // Initialisation selon l'état du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeClickFix);
    } else {
        initializeClickFix();
    }
    
    // Réinitialiser après les ajouts dynamiques de contenu
    const observer = new MutationObserver(function(mutations) {
        let shouldReinit = false;
        mutations.forEach(mutation => {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && (node.classList.contains('modern-table-row') || node.querySelector('.modern-table-row'))) {
                        shouldReinit = true;
                    }
                });
            }
        });
        
        if (shouldReinit) {
            console.log('🖱️ [CLICK-FIX] Contenu dynamique détecté, réinitialisation...');
            setTimeout(initializeClickFix, 50);
        }
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
    
    console.log('🖱️ [CLICK-FIX] ✅ Script chargé');
    console.log('💡 Utilisez window.testHomepageClicks() pour tester');
    console.log('🔍 Utilisez window.diagnoseHomepageClicks() pour diagnostiquer');
    
})();
