// Script de test pour vérifier le changement de statut après envoi de devis
console.log('🧪 [TEST-DEVIS-STATUT] Script de test du changement de statut chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 [TEST-DEVIS-STATUT] === TEST DU CHANGEMENT DE STATUT APRÈS ENVOI DEVIS ===');
    
    // Fonction pour surveiller les envois de devis
    window.monitorDevisStatus = function() {
        console.log('👀 [TEST-DEVIS-STATUT] Surveillance des envois de devis activée');
        
        // Intercepter les requêtes vers creer_devis.php
        const originalFetch = window.fetch;
        
        window.fetch = function(...args) {
            const url = args[0];
            
            if (url && url.toString().includes('creer_devis.php')) {
                console.log('🎯 [TEST-DEVIS-STATUT] Envoi de devis détecté');
                
                // Vérifier le contenu de la requête
                if (args[1] && args[1].body) {
                    try {
                        const bodyData = JSON.parse(args[1].body);
                        console.log('📤 [TEST-DEVIS-STATUT] Action:', bodyData.action);
                        console.log('📤 [TEST-DEVIS-STATUT] ID réparation:', bodyData.reparation_id);
                        
                        if (bodyData.action === 'envoyer') {
                            console.log('📧 [TEST-DEVIS-STATUT] ENVOI DE DEVIS - Le statut devrait changer vers "En attente de l\'accord client"');
                        }
                    } catch (e) {
                        console.log('📤 [TEST-DEVIS-STATUT] Body non-JSON:', args[1].body);
                    }
                }
                
                // Appeler la requête originale et surveiller la réponse
                return originalFetch.apply(this, args)
                    .then(response => {
                        const responseClone = response.clone();
                        
                        responseClone.json().then(data => {
                            console.log('📥 [TEST-DEVIS-STATUT] Réponse reçue:', data);
                            
                            if (data.success) {
                                console.log('✅ [TEST-DEVIS-STATUT] Devis envoyé avec succès');
                                console.log('📋 [TEST-DEVIS-STATUT] Numéro de devis:', data.numero_devis);
                                
                                // Attendre un peu puis vérifier le statut de la réparation
                                setTimeout(() => {
                                    window.checkRepairStatus(data.reparation_id || 'ID non disponible');
                                }, 2000);
                            } else {
                                console.error('❌ [TEST-DEVIS-STATUT] Échec de l\'envoi:', data.message);
                            }
                        }).catch(err => {
                            console.error('❌ [TEST-DEVIS-STATUT] Erreur parsing réponse:', err);
                        });
                        
                        return response;
                    });
            }
            
            return originalFetch.apply(this, args);
        };
        
        console.log('✅ [TEST-DEVIS-STATUT] Surveillance activée - envoyez un devis pour tester');
    };
    
    // Fonction pour vérifier le statut d'une réparation
    window.checkRepairStatus = function(reparationId) {
        console.log(`🔍 [TEST-DEVIS-STATUT] Vérification du statut de la réparation ${reparationId}`);
        
        // Essayer de trouver la carte de réparation sur la page
        const repairCards = document.querySelectorAll('[data-repair-id], [data-reparation-id]');
        
        repairCards.forEach(card => {
            const cardId = card.getAttribute('data-repair-id') || card.getAttribute('data-reparation-id');
            
            if (cardId == reparationId) {
                const statusElement = card.querySelector('.status-indicator, .badge, .statut, .repair-status');
                
                if (statusElement) {
                    const currentStatus = statusElement.textContent.trim();
                    console.log(`📊 [TEST-DEVIS-STATUT] Statut actuel: "${currentStatus}"`);
                    
                    if (currentStatus.includes('En attente') && currentStatus.includes('accord')) {
                        console.log('✅ [TEST-DEVIS-STATUT] SUCCÈS - Le statut a bien été changé vers "En attente de l\'accord client"');
                    } else {
                        console.log('❌ [TEST-DEVIS-STATUT] Le statut n\'a pas été changé correctement');
                        console.log('❌ [TEST-DEVIS-STATUT] Statut attendu: "En attente de l\'accord client"');
                        console.log('❌ [TEST-DEVIS-STATUT] Statut trouvé:', currentStatus);
                    }
                } else {
                    console.log('❌ [TEST-DEVIS-STATUT] Élément de statut non trouvé dans la carte');
                }
            }
        });
        
        // Si pas trouvé sur la page, suggérer de recharger
        console.log('💡 [TEST-DEVIS-STATUT] Si le statut n\'est pas visible, rechargez la page pour voir les changements');
    };
    
    // Fonction pour simuler un envoi de devis (pour test)
    window.testDevisStatusChange = function(reparationId = 1000) {
        console.log('🧪 [TEST-DEVIS-STATUT] Test de changement de statut pour la réparation', reparationId);
        
        const testData = {
            action: 'envoyer',
            reparation_id: reparationId,
            titre: 'Test changement statut',
            description: 'Test pour vérifier le changement de statut',
            pannes: [{
                titre: 'Test panne',
                description: 'Test description',
                gravite: 'moyenne'
            }],
            solutions: [{
                titre: 'Test solution',
                description: 'Test description solution',
                prix: 50.00,
                elements: []
            }]
        };
        
        fetch('ajax/creer_devis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(testData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('📧 [TEST-DEVIS-STATUT] Test envoyé:', data);
            
            if (data.success) {
                setTimeout(() => {
                    console.log('🔄 [TEST-DEVIS-STATUT] Recharger la page pour voir le changement de statut');
                    // window.location.reload();
                }, 3000);
            }
        })
        .catch(error => {
            console.error('❌ [TEST-DEVIS-STATUT] Erreur de test:', error);
        });
    };
    
    // Activer automatiquement la surveillance
    setTimeout(() => {
        window.monitorDevisStatus();
    }, 1000);
    
    console.log('✅ [TEST-DEVIS-STATUT] Fonctions de test disponibles:');
    console.log('  - monitorDevisStatus() : Active la surveillance des envois');
    console.log('  - checkRepairStatus(id) : Vérifie le statut d\'une réparation');
    console.log('  - testDevisStatusChange(id) : Test manuel d\'envoi de devis');
});
















