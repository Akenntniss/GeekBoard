/**
 * Solution d'urgence pour forcer l'affichage du modal ajouterCommandeModal
 */

console.log('🚨 [EMERGENCY-FIX] Script d\'urgence chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚨 [EMERGENCY-FIX] DOM chargé, préparation du fix d\'urgence');
    
    // Attendre que Bootstrap soit initialisé
    setTimeout(() => {
        const modal = document.getElementById('ajouterCommandeModal');
        if (!modal) {
            console.error('🚨 [EMERGENCY-FIX] Modal ajouterCommandeModal non trouvé');
            return;
        }
        
        // Écouter l'événement d'ouverture du modal
        modal.addEventListener('shown.bs.modal', function() {
            console.log('🚨 [EMERGENCY-FIX] Modal ouvert, application du fix d\'urgence...');
            
            // Forcer l'affichage avec des styles inline très agressifs
            setTimeout(() => {
                forceModalDisplay();
            }, 100);
        
        console.log('🚨 [EMERGENCY-FIX] ✅ Écouteur d\'urgence attaché au modal');
        
    }, 1000);

function forceModalDisplay() {
    const modal = document.getElementById('ajouterCommandeModal');
    const dialog = modal?.querySelector('.modal-dialog');
    const content = modal?.querySelector('.modal-content');
    
    if (!modal || !dialog || !content) {
        console.error('🚨 [EMERGENCY-FIX] Éléments du modal manquants');
        return;
    }
    
    console.log('🚨 [EMERGENCY-FIX] 🔧 Application du fix d\'urgence...');
    
    // Styles d'urgence pour le modal principal
    const modalStyles = {
        'display': 'block'
        'visibility': 'visible'
        'opacity': '1'
        'z-index': '9999'
        'position': 'fixed'
        'top': '0'
        'left': '0'
        'width': '100vw'
        'height': '100vh'
        'background-color': 'rgba(0, 0, 0, 0.5)'
        'pointer-events': 'auto'
    };
    
    // Styles d'urgence pour le dialog
    const dialogStyles = {
        'display': 'flex'
        'visibility': 'visible'
        'opacity': '1'
        'position': 'relative'
        'margin': '50px auto'
        'max-width': '1000px'
        'width': '95%'
        'height': 'auto'
        'pointer-events': 'auto'
        'transform': 'none'
    };
    
    // Styles d'urgence pour le content
    const contentStyles = {
        'display': 'flex'
        'flex-direction': 'column'
        'visibility': 'visible'
        'opacity': '1'
        'background-color': 'white'
        'border': '1px solid #dee2e6'
        'border-radius': '0.5rem'
        'box-shadow': '0 0.5rem 1rem rgba(0, 0, 0, 0.15)'
        'width': '100%'
        'height': 'auto'
        'min-height': '400px'
        'pointer-events': 'auto'
    };
    
    // Appliquer les styles au modal
    Object.entries(modalStyles).forEach(([prop, value]) => {
        modal.style.setProperty(prop, value, 'important');
    
    // Appliquer les styles au dialog
    Object.entries(dialogStyles).forEach(([prop, value]) => {
        dialog.style.setProperty(prop, value, 'important');
    
    // Appliquer les styles au content
    Object.entries(contentStyles).forEach(([prop, value]) => {
        content.style.setProperty(prop, value, 'important');
    
    // Forcer le recalcul du layout
    modal.offsetHeight;
    dialog.offsetHeight;
    content.offsetHeight;
    
    console.log('🚨 [EMERGENCY-FIX] ✅ Fix d\'urgence appliqué');
    console.log('🚨 [EMERGENCY-FIX] 📊 Dimensions finales:', {
        modal: {
            width: modal.offsetWidth
            height: modal.offsetHeight
            display: getComputedStyle(modal).display
            visibility: getComputedStyle(modal).visibility
        }
        dialog: {
            width: dialog.offsetWidth
            height: dialog.offsetHeight
            display: getComputedStyle(dialog).display
        }
        content: {
            width: content.offsetWidth
            height: content.offsetHeight
            display: getComputedStyle(content).display
        }
    
    // Vérifier si le modal est maintenant visible
    if (modal.offsetWidth > 0 && modal.offsetHeight > 0) {
        console.log('🚨 [EMERGENCY-FIX] ✅ SUCCESS! Modal maintenant visible');
        
        // Indicateur visuel supprimé - modal corrigé sans bordure
        
    } else {
        console.error('🚨 [EMERGENCY-FIX] ❌ ÉCHEC: Modal toujours invisible');
        console.log('🚨 [EMERGENCY-FIX] ℹ️ Le script principal va prendre le relais...');
    }
}

function createEmergencyModal() {
    console.log('🚨 [EMERGENCY-FIX] 🆘 Création d\'un modal d\'urgence visible...');
    
    // Supprimer l'ancien modal d'urgence s'il existe
    const existingEmergency = document.getElementById('emergencyModal');
    if (existingEmergency) {
        existingEmergency.remove();
    }
    
    const emergencyModal = document.createElement('div');
    emergencyModal.id = 'emergencyModal';
    emergencyModal.innerHTML = `
        <div style="
            position: fixed;
            top: 50px;
            left: 50px;
            right: 50px;
            bottom: 50px;
            background: white;
            z-index: 10000;
            border: 3px solid red;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            overflow: auto;
            padding: 20px;
        ">
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
                <h3 style="margin: 0; color: #dc3545;">🚨 Modal d'Urgence - Nouvelle Commande</h3>
                <button onclick="document.getElementById('emergencyModal').remove()" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">✕ Fermer</button>
            </div>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <p style="margin: 0; color: #6c757d;">Le modal principal a un problème d'affichage. Utilisez ce modal temporaire pour rechercher des clients.</p>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Rechercher un client :</label>
                <input type="text" id="emergencyClientSearch" placeholder="Tapez le nom du client..." style="
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                    font-size: 16px;
                ">
                <div id="emergencyResults" style="
                    margin-top: 10px;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                    max-height: 200px;
                    overflow-y: auto;
                    display: none;
                "></div>
            </div>
            <div style="text-align: center; color: #6c757d;">
                <p>Tapez "saber" pour tester la recherche</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(emergencyModal);
    
    // Ajouter la fonctionnalité de recherche au modal d'urgence
    const searchInput = document.getElementById('emergencyClientSearch');
    const resultsDiv = document.getElementById('emergencyResults');
    
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            console.log('🚨 [EMERGENCY-FIX] Recherche:', query);
            
            fetch('ajax/recherche_clients.php', {
                method: 'POST'
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                    'X-Requested-With': 'XMLHttpRequest'
                }
                credentials: 'same-origin'
                body: `terme=${encodeURIComponent(query)}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('🚨 [EMERGENCY-FIX] Résultats:', data);
                
                if (data.success && data.clients && data.clients.length > 0) {
                    let html = '';
                    data.clients.forEach(client => {
                        html += `
                            <div style="
                                padding: 10px;
                                border-bottom: 1px solid #eee;
                                cursor: pointer;
                                background: white;
                            " onclick="selectEmergencyClient(${client.id}, '${client.nom}', '${client.prenom}', '${client.telephone || ''}')">
                                <strong>${client.nom} ${client.prenom}</strong><br>
                                <small style="color: #6c757d;">${client.telephone || 'Pas de téléphone'}</small>
                            </div>
                        `;
                    resultsDiv.innerHTML = html;
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div style="padding: 10px; color: #6c757d;">Aucun client trouvé</div>';
                    resultsDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('🚨 [EMERGENCY-FIX] Erreur:', error);
                resultsDiv.innerHTML = '<div style="padding: 10px; color: #dc3545;">Erreur de recherche</div>';
                resultsDiv.style.display = 'block';
        }, 300);
    
    console.log('🚨 [EMERGENCY-FIX] ✅ Modal d\'urgence créé et visible');
}

// Fonction globale pour sélectionner un client dans le modal d'urgence
window.selectEmergencyClient = function(id, nom, prenom, telephone) {
    console.log('🚨 [EMERGENCY-FIX] Client sélectionné:', {id, nom, prenom, telephone});
    
    // Essayer de remplir le formulaire principal s'il est accessible
    const clientIdInput = document.getElementById('client_id');
    const clientSearchInput = document.getElementById('nom_client_selectionne');
    
    if (clientIdInput && clientSearchInput) {
        clientIdInput.value = id;
        clientSearchInput.value = `${nom} ${prenom}`;
        console.log('🚨 [EMERGENCY-FIX] ✅ Formulaire principal rempli');
    }
    
    alert(`Client sélectionné: ${nom} ${prenom}\nTéléphone: ${telephone}\nID: ${id}`);
};

console.log('🚨 [EMERGENCY-FIX] ✅ Script d\'urgence prêt');
