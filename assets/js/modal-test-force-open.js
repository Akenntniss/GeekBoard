console.log('🧪 [MODAL-TEST-FORCE-OPEN] Script de test d\'ouverture forcée chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 [MODAL-TEST-FORCE-OPEN] DOM chargé, installation du test...');
    
    // Fonction pour forcer l'ouverture du modal
    window.forceOpenModal = function() {
        console.log('🧪 [MODAL-TEST-FORCE-OPEN] Tentative d\'ouverture forcée...');
        
        const modal = document.getElementById('nouvelles_actions_modal');
        if (!modal) {
            console.error('❌ Modal nouvelles_actions_modal non trouvé');
            return false;
        }
        
        console.log('✅ Modal trouvé, tentative d\'ouverture...');
        
        try {
            // Méthode 1: Bootstrap Modal
            const bootstrapModal = new bootstrap.Modal(modal, {
                backdrop: 'static'
                keyboard: false
            bootstrapModal.show();
            console.log('✅ Modal ouvert via Bootstrap');
            
            // Forcer l'affichage après un délai
            setTimeout(() => {
                modal.style.display = 'block';
                modal.style.opacity = '1';
                modal.classList.add('show');
                console.log('✅ Styles forcés appliqués');
                
                // Forcer les animations des liens
                const links = modal.querySelectorAll('.links__link');
                console.log(`🔗 ${links.length} liens trouvés pour animation`);
                
                links.forEach((link, index) => {
                    setTimeout(() => {
                        link.style.opacity = '1';
                        link.style.transform = 'scale(1)';
                        link.style.animation = 'on-load-dark 0.3s ease-in-out forwards';
                        console.log(`✅ Animation appliquée au lien ${index + 1}`);
                    }, index * 150);
                
            }, 100);
            
            return true;
            
        } catch (error) {
            console.error('❌ Erreur lors de l\'ouverture:', error);
            return false;
        }
    };
    
    // Test automatique après 2 secondes
    setTimeout(() => {
        console.log('🧪 [MODAL-TEST-FORCE-OPEN] Test automatique dans 3 secondes...');
        console.log('🧪 [MODAL-TEST-FORCE-OPEN] Utilisez window.forceOpenModal() pour tester manuellement');
    }, 2000);
    
    console.log('🧪 [MODAL-TEST-FORCE-OPEN] ✅ Script prêt');
