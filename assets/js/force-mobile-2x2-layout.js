/**
 * ====================================================================
 * 🔧 FORCER LE LAYOUT 2x2 MOBILE - SCRIPT DE SECOURS
 * Force le layout 2x2 si le CSS ne suffit pas
 * ====================================================================
 */

(function() {
    'use strict';
    
    // ====================================================================
    // CONFIGURATION
    // ====================================================================
    
    const config = {
        isMobile: window.innerWidth <= 768,
        debug: false, // Debug désactivé
        retryAttempts: 5,
        retryDelay: 500
    };
    
    // ====================================================================
    // FONCTION PRINCIPALE POUR FORCER LE LAYOUT 2x2
    // ====================================================================
    
    function forceMobile2x2Layout() {
        if (!config.isMobile) {
            if (config.debug) console.log('🖥️ Desktop détecté - pas de layout 2x2 nécessaire');
            return;
        }
        
        const statisticsGrids = document.querySelectorAll('.statistics-grid, .futuristic-stats-grid');
        
        if (!statisticsGrids.length) {
            if (config.debug) console.log('❌ Aucun .statistics-grid trouvé');
            return;
        }
        
        statisticsGrids.forEach((grid, index) => {
            if (config.debug) console.log(`🔧 Traitement du grid ${index + 1}:`, grid);
            
            // Forcer les styles CSS via JavaScript
            grid.style.setProperty('display', 'grid', 'important');
            grid.style.setProperty('grid-template-columns', 'repeat(2, 1fr)', 'important');
            grid.style.setProperty('grid-template-rows', 'repeat(2, 1fr)', 'important');
            grid.style.setProperty('gap', '1rem', 'important');
            grid.style.setProperty('width', '100%', 'important');
            grid.style.setProperty('max-width', '100%', 'important');
            grid.style.setProperty('margin', '0', 'important');
            grid.style.setProperty('padding', '0', 'important');
            
            // Annuler les propriétés flex
            grid.style.setProperty('flex-direction', 'unset', 'important');
            grid.style.setProperty('flex-wrap', 'unset', 'important');
            grid.style.setProperty('justify-content', 'unset', 'important');
            grid.style.setProperty('align-items', 'unset', 'important');
            
            // Traiter les cartes
            const statCards = grid.querySelectorAll('.stat-card');
            if (config.debug) console.log(`📊 ${statCards.length} cartes trouvées dans le grid ${index + 1}`);
            
            statCards.forEach((card, cardIndex) => {
                if (cardIndex < 4) {
                    // Afficher les 4 premiers boutons
                    card.style.setProperty('display', 'flex', 'important');
                    card.style.setProperty('flex-direction', 'column', 'important');
                    card.style.setProperty('align-items', 'center', 'important');
                    card.style.setProperty('justify-content', 'center', 'important');
                    card.style.setProperty('text-align', 'center', 'important');
                    card.style.setProperty('min-height', '120px', 'important');
                    card.style.setProperty('padding', '1.5rem', 'important');
                    card.style.setProperty('border-radius', '16px', 'important');
                    card.style.setProperty('visibility', 'visible', 'important');
                    card.style.setProperty('opacity', '1', 'important');
                    
                    // Désactiver les animations
                    card.style.setProperty('animation', 'none', 'important');
                    card.style.setProperty('transform', 'none', 'important');
                    card.style.setProperty('will-change', 'auto', 'important');
                    
                    if (config.debug) {
                        console.log(`✅ Carte ${cardIndex + 1} configurée:`, card);
                    }
                } else {
                    // Masquer les boutons supplémentaires
                    card.style.setProperty('display', 'none', 'important');
                    if (config.debug) console.log(`🚫 Carte ${cardIndex + 1} masquée`);
                }
            });
            
        });
        
        if (config.debug) {
            console.log('🎯 Layout 2x2 mobile forcé avec succès !');
        }
    }
    
    // ====================================================================
    // FONCTION DE VÉRIFICATION
    // ====================================================================
    
    function verifyLayout() {
        const grids = document.querySelectorAll('.statistics-grid, .futuristic-stats-grid');
        let allGood = true;
        
        grids.forEach((grid, index) => {
            const computedStyle = window.getComputedStyle(grid);
            const display = computedStyle.getPropertyValue('display');
            const gridTemplateColumns = computedStyle.getPropertyValue('grid-template-columns');
            
            if (config.debug) {
                console.log(`🔍 Vérification grid ${index + 1}:`);
                console.log(`  - Display: ${display}`);
                console.log(`  - Grid columns: ${gridTemplateColumns}`);
            }
            
            if (display !== 'grid' || !gridTemplateColumns.includes('1fr')) {
                allGood = false;
                if (config.debug) console.log(`❌ Grid ${index + 1} n'est pas correctement configuré`);
            }
        });
        
        return allGood;
    }
    
    // ====================================================================
    // SYSTÈME DE RETRY
    // ====================================================================
    
    function attemptLayoutFix(attempt = 1) {
        if (config.debug) console.log(`🔄 Tentative ${attempt}/${config.retryAttempts} de correction du layout`);
        
        forceMobile2x2Layout();
        
        // Vérifier si ça a marché
        setTimeout(() => {
            if (verifyLayout()) {
                if (config.debug) console.log('✅ Layout 2x2 vérifié et fonctionnel !');
                return;
            }
            
            // Si ça n'a pas marché et qu'on a encore des tentatives
            if (attempt < config.retryAttempts) {
                if (config.debug) console.log(`⏳ Nouvelle tentative dans ${config.retryDelay}ms...`);
                setTimeout(() => attemptLayoutFix(attempt + 1), config.retryDelay);
            } else {
                if (config.debug) console.log('❌ Impossible de forcer le layout 2x2 après toutes les tentatives');
            }
        }, 100);
    }
    
    // ====================================================================
    // GESTION DES ÉVÉNEMENTS
    // ====================================================================
    
    function handleResize() {
        const wasMobile = config.isMobile;
        config.isMobile = window.innerWidth <= 768;
        
        if (!wasMobile && config.isMobile) {
            // Passage en mobile
            if (config.debug) console.log('📱 Passage en mode mobile détecté');
            attemptLayoutFix();
        } else if (wasMobile && !config.isMobile) {
            // Passage en desktop
            if (config.debug) console.log('🖥️ Passage en mode desktop détecté');
            // Nettoyer les styles forcés
            cleanupForcedStyles();
        }
    }
    
    function cleanupForcedStyles() {
        const grids = document.querySelectorAll('.statistics-grid, .futuristic-stats-grid');
        grids.forEach(grid => {
            // Retirer les bordures de debug
            grid.style.removeProperty('border');
            grid.style.removeProperty('background');
            
            const cards = grid.querySelectorAll('.stat-card');
            cards.forEach(card => {
                card.style.removeProperty('border');
                card.style.removeProperty('background');
            });
        });
        
        if (config.debug) console.log('🧹 Styles de debug nettoyés');
    }
    
    // ====================================================================
    // INITIALISATION
    // ====================================================================
    
    function init() {
        if (config.debug) {
            console.log('🚀 Initialisation du forçage de layout 2x2 mobile');
            console.log(`📱 Mode mobile: ${config.isMobile}`);
            console.log(`🖼️ Taille écran: ${window.innerWidth}x${window.innerHeight}`);
        }
        
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        // Forcer le layout si on est sur mobile
        if (config.isMobile) {
            attemptLayoutFix();
        }
        
        // Écouter les changements de taille d'écran
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(handleResize, 250);
        });
        
        // Observer les changements DOM au cas où le contenu se charge dynamiquement
        const observer = new MutationObserver((mutations) => {
            let shouldRecheck = false;
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    const addedNodes = Array.from(mutation.addedNodes);
                    if (addedNodes.some(node => 
                        node.nodeType === Node.ELEMENT_NODE && 
                        (node.classList?.contains('statistics-grid') || 
                         node.querySelector?.('.statistics-grid'))
                    )) {
                        shouldRecheck = true;
                    }
                }
            });
            
            if (shouldRecheck && config.isMobile) {
                if (config.debug) console.log('🔄 Changement DOM détecté, re-vérification du layout...');
                setTimeout(() => attemptLayoutFix(), 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        if (config.debug) console.log('✅ Forçage de layout 2x2 mobile initialisé');
    }
    
    // ====================================================================
    // UTILITAIRES GLOBAUX
    // ====================================================================
    
    // Exposer quelques fonctions utiles
    window.forceMobile2x2 = {
        force: forceMobile2x2Layout,
        verify: verifyLayout,
        cleanup: cleanupForcedStyles,
        debug: config.debug
    };
    
    // Démarrer
    init();
    
})();
