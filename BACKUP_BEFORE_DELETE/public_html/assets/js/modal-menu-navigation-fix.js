/**
 * Script de protection modal - VERSION SIMPLIFIÉE POUR MODAL FUTURISTE
 * Ne bloque plus l'ouverture du nouveau modal futuristicMenuModal
 */

console.log('🛡️ [MENU-NAV-FIX] Script de protection simplifié chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🛡️ [MENU-NAV-FIX] DOM chargé, protection simplifiée...');
    
    // Vérifier que le nouveau modal futuriste existe
    const futuristicModal = document.getElementById('futuristicMenuModal');
    if (futuristicModal) {
        console.log('🛡️ [MENU-NAV-FIX] ✅ Modal futuriste détecté et autorisé');
    } else {
        console.log('🛡️ [MENU-NAV-FIX] ⚠️ Modal futuriste non trouvé');
    }
    
    // Neutraliser complètement l'ancien modal s'il existe encore
    const oldModal = document.getElementById('menu_navigation_modal');
    if (oldModal) {
        console.log('🛡️ [MENU-NAV-FIX] 🔒 Neutralisation de l\'ancien modal');
        oldModal.style.display = 'none !important';
        oldModal.setAttribute('aria-hidden', 'true');
        
        // Empêcher toute tentative d'ouverture de l'ancien modal
        oldModal.addEventListener('show.bs.modal', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🛡️ [MENU-NAV-FIX] 🚫 Tentative d\'ouverture de l\'ancien modal bloquée');
        });
    }
    
    console.log('🛡️ [MENU-NAV-FIX] ✅ Protection simplifiée installée');
});

console.log('🛡️ [MENU-NAV-FIX] Script simplifié prêt');