// Script de test pour l'API de réparation
console.log('🧪 [TEST-API] Script de test API réparation chargé');

// Fonction pour tester l'API de détails de réparation
window.testRepairAPI = async function(repairId = 1000) {
    console.log('🔍 [TEST-API] Test de l\'API pour réparation:', repairId);
    
    try {
        const response = await fetch(`ajax/get_repair_details.php?id=${repairId}`);
        const data = await response.json();
        
        console.log('📊 [TEST-API] Réponse complète de l\'API:', data);
        
        if (data.success && data.repair) {
            const repair = data.repair;
            
            console.log('✅ [TEST-API] Données de réparation reçues:');
            console.log('  - ID:', repair.id);
            console.log('  - Type appareil:', repair.type_appareil);
            console.log('  - Marque:', repair.marque);
            console.log('  - Modèle:', repair.modele);
            console.log('  - Client nom:', repair.client_nom);
            console.log('  - Client prénom:', repair.client_prenom);
            console.log('  - Date réception (brute):', repair.date_reception);
            console.log('  - Description problème:', repair.description_probleme);
            
            // Tester le formatage de date
            console.log('📅 [TEST-API] Test du formatage de date:');
            try {
                const date = new Date(repair.date_reception);
                console.log('  - Date objet:', date);
                console.log('  - Date valide:', !isNaN(date.getTime()));
                
                if (!isNaN(date.getTime())) {
                    const formatted = new Intl.DateTimeFormat('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    }).format(date);
                    console.log('  - Date formatée:', formatted);
                } else {
                    console.error('❌ [TEST-API] Date invalide détectée');
                }
            } catch (dateError) {
                console.error('❌ [TEST-API] Erreur de formatage date:', dateError);
            }
            
            return data;
        } else {
            console.error('❌ [TEST-API] Erreur API:', data.message);
            return null;
        }
    } catch (error) {
        console.error('❌ [TEST-API] Erreur AJAX:', error);
        return null;
    }
};

// Fonction pour tester et ouvrir le modal avec vérification
window.testAndOpenDevisModal = async function(repairId = 1000) {
    console.log('🎯 [TEST-API] Test et ouverture sécurisée du modal pour:', repairId);
    
    // Tester l'API d'abord
    const apiResult = await window.testRepairAPI(repairId);
    
    if (apiResult && apiResult.success) {
        console.log('✅ [TEST-API] API OK, ouverture du modal...');
        
        // Ouvrir le modal avec les différentes méthodes disponibles
        if (typeof window.ouvrirNouveauModalDevis === 'function') {
            window.ouvrirNouveauModalDevis(repairId);
        } else if (typeof window.openDevisModalModern === 'function') {
            window.openDevisModalModern(repairId);
        } else if (typeof window.ouvrirModalDevis === 'function') {
            window.ouvrirModalDevis(repairId);
        } else {
            console.error('❌ [TEST-API] Aucune fonction d\'ouverture de modal disponible');
        }
    } else {
        console.error('❌ [TEST-API] Échec du test API, modal non ouvert');
        alert('Erreur: Impossible de charger les données de la réparation');
    }
};

// Fonction pour nettoyer et corriger les dates dans les données
window.fixRepairDate = function(dateString) {
    if (!dateString) return 'Non spécifiée';
    
    // Essayer différents formats de date
    const formats = [
        dateString,
        dateString.replace(' ', 'T'), // ISO format
        dateString.split(' ')[0], // Seulement la partie date
    ];
    
    for (const format of formats) {
        try {
            const date = new Date(format);
            if (!isNaN(date.getTime())) {
                return new Intl.DateTimeFormat('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }).format(date);
            }
        } catch (e) {
            continue;
        }
    }
    
    return 'Format invalide';
};

// Test automatique au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 [TEST-API] DOM chargé, fonctions de test disponibles:');
    console.log('  - testRepairAPI(repairId) : Teste l\'API de réparation');
    console.log('  - testAndOpenDevisModal(repairId) : Teste et ouvre le modal');
    console.log('  - fixRepairDate(dateString) : Corrige le formatage de date');
});

console.log('🧪 [TEST-API] Script de test API initialisé');



