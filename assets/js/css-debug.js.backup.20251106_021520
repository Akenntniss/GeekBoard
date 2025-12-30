/**
 * Debug pour vérifier si les fichiers CSS sont correctement chargés
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 Vérification du chargement des CSS');
    
    // Lister tous les fichiers CSS chargés
    const stylesheets = Array.from(document.styleSheets);
    console.log('📋 Fichiers CSS chargés:', stylesheets.length);
    
    stylesheets.forEach((sheet, index) => {
        try {
            const href = sheet.href || 'inline';
            console.log(`  ${index + 1}. ${href}`);
            
            // Vérifier si c'est notre fichier de correction
            if (href.includes('modal-ajoutercommande-fix.css')) {
                console.log('✅ Fichier de correction trouvé:', href);
                
                // Essayer d'accéder aux règles
                try {
                    const rules = Array.from(sheet.cssRules || sheet.rules || []);
                    console.log('📏 Nombre de règles CSS:', rules.length);
                    
                    // Chercher nos règles spécifiques
                    const modalRules = rules.filter(rule => 
                        rule.selectorText && rule.selectorText.includes('ajouterCommandeModal')
                    );
                    console.log('🎯 Règles pour ajouterCommandeModal:', modalRules.length);
                    modalRules.forEach(rule => {
                        console.log('  - Règle:', rule.selectorText, rule.style.cssText);
                    });
                } catch (e) {
                    console.warn('⚠️ Impossible d\'accéder aux règles CSS (CORS?):', e.message);
                }
            }
        } catch (e) {
            console.warn('⚠️ Erreur lors de l\'analyse de la feuille de style:', e.message);
        }
    });
    
    // Vérifier si les styles sont appliqués sur le modal
    setTimeout(() => {
        const modal = document.getElementById('ajouterCommandeModal');
        if (modal) {
            const computedStyle = window.getComputedStyle(modal);
            console.log('🔍 Styles appliqués sur ajouterCommandeModal:');
            console.log('  - z-index:', computedStyle.zIndex);
            console.log('  - position:', computedStyle.position);
            
            // Vérifier si notre CSS de debug est appliqué
            if (modal.classList.contains('show')) {
                const showStyle = window.getComputedStyle(modal);
                console.log('📊 Styles quand .show est présent:');
                console.log('  - display:', showStyle.display);
                console.log('  - visibility:', showStyle.visibility);
                console.log('  - opacity:', showStyle.opacity);
                console.log('  - z-index:', showStyle.zIndex);
            }
        }
    }, 1000);
    
    // Fonction pour forcer le rechargement du CSS
    window.reloadModalCSS = function() {
        console.log('🔄 Rechargement forcé du CSS modal');
        
        // Trouver le lien CSS
        const cssLink = document.querySelector('link[href*="modal-ajoutercommande-fix.css"]');
        if (cssLink) {
            const newLink = cssLink.cloneNode();
            newLink.href = cssLink.href + '?v=' + Date.now();
            cssLink.parentNode.insertBefore(newLink, cssLink.nextSibling);
            setTimeout(() => cssLink.remove(), 100);
            console.log('✅ CSS rechargé');
        } else {
            console.error('❌ Lien CSS non trouvé');
        }
    };
    
    console.log('💡 Utilisez window.reloadModalCSS() pour recharger le CSS');
});
