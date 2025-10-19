// Script de test pour le nouveau design du bouton "Envoyer le devis"
console.log('🎨 [TEST-NOUVEAU-BOUTON] Script de test du nouveau design chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 [TEST-NOUVEAU-BOUTON] === TEST DU NOUVEAU DESIGN DU BOUTON ===');
    
    // Fonction pour forcer l'affichage du nouveau bouton
    window.testNouveauBouton = function() {
        console.log('🎨 [TEST-NOUVEAU-BOUTON] Test du nouveau design');
        
        const boutonEnvoyer = document.getElementById('creerEtEnvoyer');
        
        if (boutonEnvoyer) {
            console.log('✅ [TEST-NOUVEAU-BOUTON] Bouton trouvé');
            
            // Forcer l'affichage avec le nouveau style
            boutonEnvoyer.style.display = 'inline-flex';
            boutonEnvoyer.style.visibility = 'visible';
            boutonEnvoyer.style.opacity = '1';
            boutonEnvoyer.classList.add('force-show');
            boutonEnvoyer.disabled = false;
            
            console.log('🎨 [TEST-NOUVEAU-BOUTON] Nouveau design appliqué');
            console.log('✨ [TEST-NOUVEAU-BOUTON] Le bouton devrait maintenant avoir:');
            console.log('  - Design rectangulaire moderne');
            console.log('  - Gradient violet/bleu');
            console.log('  - Ombre portée');
            console.log('  - Animation de pulsation');
            console.log('  - Effet hover avec élévation');
            
            // Analyser les styles appliqués
            const styles = getComputedStyle(boutonEnvoyer);
            console.log('📊 [TEST-NOUVEAU-BOUTON] Styles appliqués:', {
                background: styles.background,
                borderRadius: styles.borderRadius,
                padding: styles.padding,
                fontSize: styles.fontSize,
                fontWeight: styles.fontWeight,
                minWidth: styles.minWidth,
                minHeight: styles.minHeight,
                boxShadow: styles.boxShadow,
                textTransform: styles.textTransform
            });
            
        } else {
            console.error('❌ [TEST-NOUVEAU-BOUTON] Bouton non trouvé');
        }
    };
    
    // Fonction pour tester l'animation hover
    window.testHoverEffect = function() {
        console.log('🖱️ [TEST-NOUVEAU-BOUTON] Test de l\'effet hover');
        
        const boutonEnvoyer = document.getElementById('creerEtEnvoyer');
        
        if (boutonEnvoyer) {
            // Simuler un hover
            boutonEnvoyer.style.background = 'linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%)';
            boutonEnvoyer.style.boxShadow = '0 12px 35px rgba(102, 126, 234, 0.4)';
            boutonEnvoyer.style.transform = 'translateY(-3px) scale(1.02)';
            
            console.log('✨ [TEST-NOUVEAU-BOUTON] Effet hover simulé');
            
            // Retour à l'état normal après 2 secondes
            setTimeout(() => {
                boutonEnvoyer.style.background = '';
                boutonEnvoyer.style.boxShadow = '';
                boutonEnvoyer.style.transform = '';
                console.log('↩️ [TEST-NOUVEAU-BOUTON] Retour à l\'état normal');
            }, 2000);
        }
    };
    
    // Fonction pour activer/désactiver l'animation de pulsation
    window.togglePulsation = function() {
        const boutonEnvoyer = document.getElementById('creerEtEnvoyer');
        
        if (boutonEnvoyer) {
            if (boutonEnvoyer.classList.contains('force-show')) {
                boutonEnvoyer.classList.remove('force-show');
                console.log('⏹️ [TEST-NOUVEAU-BOUTON] Animation de pulsation arrêtée');
            } else {
                boutonEnvoyer.classList.add('force-show');
                console.log('▶️ [TEST-NOUVEAU-BOUTON] Animation de pulsation activée');
            }
        }
    };
    
    // Fonction pour comparer ancien vs nouveau style
    window.compareStyles = function() {
        console.log('🔄 [TEST-NOUVEAU-BOUTON] Comparaison des styles');
        
        const boutonEnvoyer = document.getElementById('creerEtEnvoyer');
        
        if (boutonEnvoyer) {
            console.log('📋 [TEST-NOUVEAU-BOUTON] NOUVEAU DESIGN:');
            console.log('  ✅ Rectangulaire (border-radius: 12px)');
            console.log('  ✅ Gradient moderne (violet → bleu)');
            console.log('  ✅ Padding généreux (16px 32px)');
            console.log('  ✅ Typographie forte (uppercase, letterspacing)');
            console.log('  ✅ Ombre portée avec couleur');
            console.log('  ✅ Animations fluides');
            console.log('  ✅ Taille minimum garantie');
            console.log('  ✅ Effet hover avec élévation');
            console.log('  ✅ Animation de pulsation optionnelle');
            
            console.log('📋 [TEST-NOUVEAU-BOUTON] ANCIEN DESIGN (supprimé):');
            console.log('  ❌ Bouton rond générique');
            console.log('  ❌ Couleurs ternes');
            console.log('  ❌ Peu visible');
            console.log('  ❌ Pas d\'effets visuels');
        }
    };
    
    // Test automatique après 2 secondes
    setTimeout(() => {
        console.log('🔄 [AUTO-TEST] Test automatique du nouveau bouton...');
        window.testNouveauBouton();
        window.compareStyles();
    }, 2000);
    
    console.log('✅ [TEST-NOUVEAU-BOUTON] Fonctions de test disponibles:');
    console.log('  - testNouveauBouton() : Applique le nouveau design');
    console.log('  - testHoverEffect() : Teste l\'effet hover');
    console.log('  - togglePulsation() : Active/désactive la pulsation');
    console.log('  - compareStyles() : Compare ancien vs nouveau');
});
















