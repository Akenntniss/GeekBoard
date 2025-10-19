// Fix pour l'intégration du modal de devis moderne
console.log('🔧 [FIX-DEVIS] Script de correction de l\'intégration chargé');

// Attendre que tous les scripts soient chargés
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 [FIX-DEVIS] DOM prêt, vérification des intégrations...');
    
    // Vérifier que toutes les fonctions nécessaires sont disponibles
    setTimeout(function() {
        console.log('🔍 [FIX-DEVIS] Vérification des fonctions:');
        
        const fonctions = [
            'ouvrirNouveauModalDevis',
            'openDevisModalModern',
            'ouvrirModalDevis',
            'openDevisModalSafely'
        ];
        
        fonctions.forEach(nomFonction => {
            const disponible = typeof window[nomFonction] === 'function';
            console.log(`  - ${nomFonction}: ${disponible ? '✅ Disponible' : '❌ Manquante'}`);
        });
        
        // Si ouvrirNouveauModalDevis n'est pas disponible, la créer
        if (typeof window.ouvrirNouveauModalDevis !== 'function') {
            console.log('🚀 [FIX-DEVIS] Création de la fonction ouvrirNouveauModalDevis manquante');
            
            window.ouvrirNouveauModalDevis = function(reparationId) {
                console.log('🎯 [FIX-DEVIS] ouvrirNouveauModalDevis appelée pour réparation:', reparationId);
                
                if (!reparationId) {
                    console.error('❌ [FIX-DEVIS] ID de réparation manquant');
                    return;
                }
                
                // Essayer d'utiliser les autres fonctions disponibles
                if (typeof window.openDevisModalSafely === 'function') {
                    console.log('✅ [FIX-DEVIS] Utilisation de openDevisModalSafely');
                    window.openDevisModalSafely(reparationId);
                } else if (typeof window.ouvrirModalDevis === 'function') {
                    console.log('✅ [FIX-DEVIS] Utilisation de ouvrirModalDevis');
                    window.ouvrirModalDevis(reparationId);
                } else {
                    console.log('🔧 [FIX-DEVIS] Ouverture manuelle du modal');
                    
                    // Fermer tous les modals ouverts
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modal => {
                        try {
                            const modalInstance = bootstrap.Modal.getInstance(modal);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        } catch (e) {
                            console.warn('⚠️ [FIX-DEVIS] Erreur fermeture modal:', e);
                        }
                    });
                    
                    // Attendre puis ouvrir le modal de devis
                    setTimeout(() => {
                        const modal = document.getElementById('creerDevisModal');
                        if (modal) {
                            // Créer un bouton temporaire avec l'ID de réparation
                            const tempButton = document.createElement('button');
                            tempButton.dataset.reparationId = reparationId;
                            
                            // Définir l'ID dans le formulaire
                            const reparationIdField = document.getElementById('reparation_id');
                            if (reparationIdField) {
                                reparationIdField.value = reparationId;
                            }
                            
                            // Ouvrir le modal
                            const modalInstance = new bootstrap.Modal(modal, {
                                backdrop: 'static',
                                keyboard: false
                            });
                            
                            // Déclencher l'événement d'ouverture
                            const event = new Event('show.bs.modal');
                            event.relatedTarget = tempButton;
                            modal.dispatchEvent(event);
                            
                            modalInstance.show();
                            
                            console.log('✅ [FIX-DEVIS] Modal ouvert manuellement');
                        } else {
                            console.error('❌ [FIX-DEVIS] Modal creerDevisModal introuvable');
                            alert('Erreur: Modal de devis introuvable');
                        }
                    }, 300);
                }
            };
            
            console.log('✅ [FIX-DEVIS] Fonction ouvrirNouveauModalDevis créée');
        }
        
        // Vérifier l'intégration avec RepairModal
        if (window.RepairModal && !window.RepairModal.ouvrirNouveauModalDevis) {
            window.RepairModal.ouvrirNouveauModalDevis = window.ouvrirNouveauModalDevis;
            console.log('🔗 [FIX-DEVIS] Fonction intégrée dans RepairModal');
        }
        
        console.log('✅ [FIX-DEVIS] Correction de l\'intégration terminée');
        
    }, 1000);
});

// Fonction de test globale
window.testDevisIntegration = function(reparationId = 1000) {
    console.log('🧪 [FIX-DEVIS] Test de l\'intégration pour réparation:', reparationId);
    
    if (typeof window.ouvrirNouveauModalDevis === 'function') {
        console.log('✅ [FIX-DEVIS] Test: ouverture du modal...');
        window.ouvrirNouveauModalDevis(reparationId);
    } else {
        console.error('❌ [FIX-DEVIS] Test échoué: fonction non disponible');
    }
};

console.log('🔧 [FIX-DEVIS] Script de correction initialisé');
















