/* ====================================================================
   🎯 CORRECTION CARTES DASHBOARD "ÉTAT DES RÉPARATIONS"
   Corrige les conflits de clics entre les cartes statistiques
==================================================================== */

(function() {
    'use strict';
    
    console.log('🎯 [DASHBOARD-CARDS] Initialisation de la correction des cartes...');
    
    // Variables pour tracker les clics
    let lastCardClick = 0;
    let isCardClickInProgress = false;
    
    // Fonction de diagnostic des cartes
    function diagnoseDashboardCards() {
        console.log('🎯 [DASHBOARD-CARDS] === DIAGNOSTIC CARTES ===');
        
        const statsContainer = document.querySelector('.statistics-container');
        const statsGrid = document.querySelector('.statistics-grid');
        const statCards = document.querySelectorAll('.stat-card');
        
        console.log('📊 Container:', statsContainer ? '✅ Trouvé' : '❌ Non trouvé');
        console.log('📊 Grid:', statsGrid ? '✅ Trouvé' : '❌ Non trouvé');
        console.log('📊 Cartes trouvées:', statCards.length);
        
        statCards.forEach((card, index) => {
            const href = card.getAttribute('href');
            const rect = card.getBoundingClientRect();
            const zIndex = window.getComputedStyle(card).zIndex;
            
            console.log(`📊 Carte ${index + 1}:`, {
                href: href,
                position: `${rect.left.toFixed(0)}, ${rect.top.toFixed(0)}`,
                size: `${rect.width.toFixed(0)}x${rect.height.toFixed(0)}`,
                zIndex: zIndex,
                classes: card.className
            });
        });
        
        return { statsContainer, statsGrid, statCards };
    }
    
    // Fonction pour corriger les événements des cartes
    function fixCardEvents() {
        console.log('🎯 [DASHBOARD-CARDS] Correction des événements des cartes...');
        
        const statCards = document.querySelectorAll('.stat-card');
        
        statCards.forEach((card, index) => {
            // NE PAS cloner les cartes pour éviter le clignotement
            // Juste ajouter les event listeners sans remplacer l'élément
            
            // Supprimer les anciens event listeners spécifiques
            card.removeEventListener('click', arguments.callee);
            
            // Ajouter un event listener contrôlé
            card.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Prévenir les double-clics
                const now = Date.now();
                if (isCardClickInProgress || (now - lastCardClick < 500)) {
                    console.log('🎯 [DASHBOARD-CARDS] Double-clic ignoré');
                    return;
                }
                
                isCardClickInProgress = true;
                lastCardClick = now;
                
                const href = this.getAttribute('href');
                console.log(`🎯 [DASHBOARD-CARDS] Clic sur carte ${index + 1}:`, href);
                
                // Ajouter un effet visuel
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Naviguer vers le lien
                if (href) {
                    setTimeout(() => {
                        window.location.href = href;
                    }, 200);
                } else {
                    console.warn('⚠️ [DASHBOARD-CARDS] Aucun href trouvé pour cette carte');
                    isCardClickInProgress = false;
                }
            });
            
            // Ajouter des effets de survol améliorés
            card.addEventListener('mouseenter', function() {
                console.log(`🎯 [DASHBOARD-CARDS] Survol carte ${index + 1}`);
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                if (!isCardClickInProgress) {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                }
            });
        });
        
        console.log(`✅ [DASHBOARD-CARDS] ${statCards.length} cartes corrigées`);
    }
    
    // Fonction pour corriger les z-index des cartes
    function fixCardZIndex() {
        console.log('🎯 [DASHBOARD-CARDS] Correction des z-index des cartes...');
        
        const statsContainer = document.querySelector('.statistics-container');
        const statsGrid = document.querySelector('.statistics-grid');
        const statCards = document.querySelectorAll('.stat-card');
        
        if (statsContainer) {
            statsContainer.style.position = 'relative';
            statsContainer.style.zIndex = '50';
            statsContainer.style.isolation = 'isolate';
        }
        
        if (statsGrid) {
            statsGrid.style.position = 'relative';
            statsGrid.style.zIndex = '55';
            statsGrid.style.display = 'grid';
            statsGrid.style.gap = '1rem';
        }
        
        statCards.forEach((card, index) => {
            card.style.position = 'relative';
            card.style.zIndex = (60 + index).toString();
            card.style.isolation = 'isolate';
            card.style.display = 'block';
            card.style.cursor = 'pointer';
            card.style.transition = 'all 0.2s ease';
            
            // S'assurer que tous les enfants héritent du pointer-events
            const children = card.querySelectorAll('*');
            children.forEach(child => {
                child.style.pointerEvents = 'none';
            });
            card.style.pointerEvents = 'auto';
        });
        
        console.log('✅ [DASHBOARD-CARDS] Z-index des cartes corrigés');
    }
    
    // Fonction pour ajouter une protection contre les chevauchements
    function addCardProtection() {
        console.log('🎯 [DASHBOARD-CARDS] Ajout protection contre les chevauchements...');
        
        const statsGrid = document.querySelector('.statistics-grid');
        if (statsGrid) {
            // Forcer un espacement minimum entre les cartes
            statsGrid.style.gridTemplateColumns = 'repeat(auto-fit, minmax(200px, 1fr))';
            statsGrid.style.gap = '1.5rem';
            statsGrid.style.padding = '1rem';
        }
        
        // Ajouter des marges de sécurité
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.style.margin = '0.25rem';
            card.style.minHeight = '120px';
            card.style.minWidth = '180px';
        });
        
        console.log('✅ [DASHBOARD-CARDS] Protection ajoutée');
    }
    
    // Fonction de test pour les cartes
    window.testDashboardCards = function() {
        console.log('🧪 [DASHBOARD-CARDS] Test des cartes dashboard');
        
        const diagnosis = diagnoseDashboardCards();
        
        // Simuler des clics sur chaque carte
        diagnosis.statCards.forEach((card, index) => {
            setTimeout(() => {
                console.log(`🧪 Test clic carte ${index + 1}`);
                const event = new MouseEvent('click', { bubbles: true, cancelable: true });
                card.dispatchEvent(event);
            }, index * 1000);
        });
    };
    
    // Fonction de diagnostic complet
    window.diagnoseDashboardCards = function() {
        console.log('🔍 [DASHBOARD-CARDS] === DIAGNOSTIC COMPLET ===');
        
        const diagnosis = diagnoseDashboardCards();
        
        // Vérifier les chevauchements entre cartes
        const cards = Array.from(diagnosis.statCards);
        for (let i = 0; i < cards.length; i++) {
            for (let j = i + 1; j < cards.length; j++) {
                const rect1 = cards[i].getBoundingClientRect();
                const rect2 = cards[j].getBoundingClientRect();
                
                const overlap = !(rect1.right < rect2.left || 
                                rect2.right < rect1.left || 
                                rect1.bottom < rect2.top || 
                                rect2.bottom < rect1.top);
                
                if (overlap) {
                    console.warn(`⚠️ Chevauchement détecté entre carte ${i + 1} et carte ${j + 1}`);
                }
            }
        }
        
        // Tester la navigation
        diagnosis.statCards.forEach((card, index) => {
            const href = card.getAttribute('href');
            console.log(`🔗 Carte ${index + 1} → ${href}`);
        });
        
        console.log('🔍 [DASHBOARD-CARDS] === FIN DIAGNOSTIC ===');
    };
    
    // Fonction principale d'initialisation
    function initializeDashboardCardsFix() {
        console.log('🎯 [DASHBOARD-CARDS] Initialisation complète...');
        
        // Attendre que le DOM soit stable
        setTimeout(() => {
            fixCardZIndex();
            addCardProtection();
            fixCardEvents();
            console.log('✅ [DASHBOARD-CARDS] Correction des cartes terminée');
        }, 150);
    }
    
    // Initialisation selon l'état du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeDashboardCardsFix);
    } else {
        initializeDashboardCardsFix();
    }
    
    // Réinitialiser après les changements de mode (jour/nuit)
    document.addEventListener('themeChanged', function() {
        console.log('🎯 [DASHBOARD-CARDS] Thème changé, réinitialisation...');
        setTimeout(initializeDashboardCardsFix, 100);
    });
    
    // Observer désactivé temporairement pour éviter les boucles de réinitialisation
    /*
    const observer = new MutationObserver(function(mutations) {
        let shouldReinit = false;
        mutations.forEach(mutation => {
            if (mutation.type === 'childList' && mutation.target.classList.contains('statistics-grid')) {
                shouldReinit = true;
            }
        });
        
        if (shouldReinit) {
            console.log('🎯 [DASHBOARD-CARDS] Changement détecté dans la grille, réinitialisation...');
            setTimeout(initializeDashboardCardsFix, 50);
        }
    });
    
    const statsGrid = document.querySelector('.statistics-grid');
    if (statsGrid) {
        observer.observe(statsGrid, { childList: true, subtree: true });
    }
    */
    
    console.log('🎯 [DASHBOARD-CARDS] ✅ Script chargé');
    console.log('💡 Utilisez window.testDashboardCards() pour tester');
    console.log('🔍 Utilisez window.diagnoseDashboardCards() pour diagnostiquer');
    
})();
