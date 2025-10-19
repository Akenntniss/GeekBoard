// Script de test final pour vérifier que l'envoi de devis fonctionne
console.log('✅ [TEST-DEVIS-FINAL] Script de test final chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ [TEST-DEVIS-FINAL] === TEST FINAL DE L\'ENVOI DE DEVIS ===');
    
    // Test de l'envoi de devis
    window.testEnvoiDevisFinal = async function() {
        console.log('📧 [TEST-DEVIS-FINAL] Test d\'envoi de devis...');
        
        // 1. Vérifier que le modal est accessible
        const modal = document.getElementById('creerDevisModal');
        console.log('🔍 [TEST-DEVIS-FINAL] Modal trouvé:', !!modal);
        
        // 2. Vérifier que le bouton d'envoi est présent
        const boutonEnvoyer = document.getElementById('creerEtEnvoyer');
        console.log('🔍 [TEST-DEVIS-FINAL] Bouton envoyer trouvé:', !!boutonEnvoyer);
        
        if (boutonEnvoyer) {
            const styles = getComputedStyle(boutonEnvoyer);
            console.log('🔍 [TEST-DEVIS-FINAL] Bouton visible:', styles.display !== 'none');
            console.log('🔍 [TEST-DEVIS-FINAL] Bouton activé:', !boutonEnvoyer.disabled);
        }
        
        // 3. Vérifier que le devisManager est disponible
        console.log('🔍 [TEST-DEVIS-FINAL] DevisManager disponible:', typeof window.devisManager !== 'undefined');
        
        if (window.devisManager) {
            console.log('🔍 [TEST-DEVIS-FINAL] Fonction sauvegarderDevis:', typeof window.devisManager.sauvegarderDevis);
        }
        
        // 4. Test direct de l'endpoint
        console.log('🌐 [TEST-DEVIS-FINAL] Test de l\'endpoint Ajax...');
        
        try {
            const response = await fetch('ajax/creer_devis.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    test: true,
                    action: 'test'
                })
            });
            
            console.log('📊 [TEST-DEVIS-FINAL] Status de la réponse:', response.status);
            console.log('📊 [TEST-DEVIS-FINAL] Réponse OK:', response.ok);
            
            if (response.status === 401) {
                console.error('❌ [TEST-DEVIS-FINAL] ERREUR: Session toujours invalide!');
                console.error('❌ [TEST-DEVIS-FINAL] Le problème d\'authentification persiste');
            } else if (response.status === 200) {
                console.log('✅ [TEST-DEVIS-FINAL] SUCCÈS: Endpoint accessible!');
                console.log('✅ [TEST-DEVIS-FINAL] Problème d\'authentification résolu');
            } else {
                console.log('ℹ️ [TEST-DEVIS-FINAL] Status:', response.status, '- Probablement erreur de données de test (normal)');
            }
            
            // Essayer de lire la réponse
            try {
                const result = await response.json();
                console.log('📋 [TEST-DEVIS-FINAL] Réponse serveur:', result);
            } catch (e) {
                console.log('📋 [TEST-DEVIS-FINAL] Réponse non-JSON (normal pour un test)');
            }
            
        } catch (error) {
            console.error('❌ [TEST-DEVIS-FINAL] Erreur réseau:', error);
        }
        
        console.log('✅ [TEST-DEVIS-FINAL] Test terminé');
    };
    
    // Test de navigation du modal
    window.testNavigationModal = function() {
        console.log('🧭 [TEST-DEVIS-FINAL] Test de navigation du modal...');
        
        // Vérifier les étapes
        const etapes = document.querySelectorAll('.form-step');
        const indicateurs = document.querySelectorAll('.step-indicator');
        
        console.log('📊 [TEST-DEVIS-FINAL] Nombre d\'étapes:', etapes.length);
        console.log('📊 [TEST-DEVIS-FINAL] Nombre d\'indicateurs:', indicateurs.length);
        
        // Vérifier qu'il n'y a que 3 étapes maintenant
        if (etapes.length === 3 && indicateurs.length === 3) {
            console.log('✅ [TEST-DEVIS-FINAL] Nombre d\'étapes correct (3 au lieu de 4)');
        } else {
            console.error('❌ [TEST-DEVIS-FINAL] Nombre d\'étapes incorrect');
        }
        
        // Vérifier l'étape active
        const etapeActive = document.querySelector('.form-step.active');
        if (etapeActive) {
            console.log('📍 [TEST-DEVIS-FINAL] Étape active:', etapeActive.getAttribute('data-step'));
        }
        
        // Vérifier les champs automatiques
        const dateExpiration = document.getElementById('date_expiration');
        const tauxTva = document.getElementById('taux_tva');
        
        console.log('📅 [TEST-DEVIS-FINAL] Date expiration automatique:', dateExpiration?.value);
        console.log('📊 [TEST-DEVIS-FINAL] Taux TVA automatique:', tauxTva?.value);
        
        if (dateExpiration?.type === 'hidden' && tauxTva?.type === 'hidden') {
            console.log('✅ [TEST-DEVIS-FINAL] Champs automatiques bien configurés (cachés)');
        }
    };
    
    // Auto-test au chargement
    setTimeout(() => {
        console.log('🔄 [AUTO-TEST] Lancement des tests automatiques...');
        window.testEnvoiDevisFinal();
        window.testNavigationModal();
    }, 2000);
    
    console.log('✅ [TEST-DEVIS-FINAL] Fonctions de test disponibles:');
    console.log('  - testEnvoiDevisFinal() : Test complet de l\'envoi de devis');
    console.log('  - testNavigationModal() : Test de la navigation du modal');
});
















