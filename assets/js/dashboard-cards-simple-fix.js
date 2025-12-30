/* ====================================================================
   🎯 CORRECTION SIMPLE CARTES DASHBOARD - SANS CLIGNOTEMENT
   Version ultra-simplifiée pour éviter les conflits d'animations
==================================================================== */

(function() {
    'use strict';
    
    console.log('🎯 [CARDS-SIMPLE] Initialisation correction simple...');
    
    // Fonction de correction ultra-simple
    function simpleCardsFix() {
        console.log('🎯 [CARDS-SIMPLE] Application des corrections...');
        
        const statCards = document.querySelectorAll('.stat-card');
        console.log(`🎯 [CARDS-SIMPLE] ${statCards.length} cartes trouvées`);
        
        statCards.forEach((card, index) => {
            // Correction CSS directe sans animations
            card.style.position = 'relative';
            card.style.zIndex = (60 + index).toString();
            card.style.display = 'block';
            card.style.pointerEvents = 'auto';
            card.style.cursor = 'pointer';
            
            // Désactiver toutes les transitions pour éviter le clignotement
            card.style.transition = 'none';
            
            // S'assurer que les enfants n'interfèrent pas
            const children = card.querySelectorAll('*');
            children.forEach(child => {
                child.style.pointerEvents = 'none';
            
            // Ajouter un simple event listener de clic
            card.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const href = this.getAttribute('href');
                console.log(`🎯 [CARDS-SIMPLE] Clic carte ${index + 1}: ${href}`);
                
                if (href) {
                    window.location.href = href;
                } else {
                    console.warn('⚠️ [CARDS-SIMPLE] Pas de href trouvé');
                }
            }, { once: false, passive: false });
            
            console.log(`✅ [CARDS-SIMPLE] Carte ${index + 1} corrigée`);
        
        // Corriger le conteneur parent
        const statsContainer = document.querySelector('.statistics-container');
        if (statsContainer) {
            statsContainer.style.position = 'relative';
            statsContainer.style.zIndex = '50';
        }
        
        const statsGrid = document.querySelector('.statistics-grid');
        if (statsGrid) {
            statsGrid.style.position = 'relative';
            statsGrid.style.zIndex = '55';
        }
        
        console.log('✅ [CARDS-SIMPLE] Correction terminée');
    }
    
    // Fonction de test simple
    window.testSimpleCards = function() {
        console.log('🧪 [CARDS-SIMPLE] Test des cartes');
        const cards = document.querySelectorAll('.stat-card');
        cards.forEach((card, index) => {
            console.log(`Carte ${index + 1}:`, {
                href: card.getAttribute('href')
                zIndex: card.style.zIndex
                pointerEvents: card.style.pointerEvents
    };
    
    // Initialisation immédiate et simple
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', simpleCardsFix);
    } else {
        simpleCardsFix();
    }
    
    // Une seule réinitialisation après un délai pour s'assurer que tout est chargé
    setTimeout(simpleCardsFix, 500);
    
    console.log('🎯 [CARDS-SIMPLE] ✅ Script simple chargé');
    console.log('💡 Utilisez window.testSimpleCards() pour tester');
    
})();
