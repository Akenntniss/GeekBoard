/**
 * CORRECTION DU PROBLÈME DE RECHERCHE
 * 
 * Problème identifié : Incompatibilité entre les IDs du modal et du script JavaScript
 * 
 * Modal utilise :
 * - rechercheInput
 * - rechercheBtn
 * - rechercheModal
 * 
 * Script recherche-universelle-new.js cherche :
 * - universalSearchInput
 * - universalSearchBtn
 * - rechercheUniverselleModal
 * 
 * SOLUTION : Créer un script de compatibilité
 */

class RechercheUniverselleCompatibility {
    constructor() {
        this.init();
    }
    
    init() {
        console.log('🔧 Initialisation de la compatibilité de recherche...');
        
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupCompatibility());
        } else {
            this.setupCompatibility();
        }
    }
    
    setupCompatibility() {
        // Vérifier si les éléments du modal existent
        const modalInput = document.getElementById('rechercheInput');
        const modalBtn = document.getElementById('rechercheBtn');
        const modal = document.getElementById('rechercheModal');
        
        console.log('🔍 Vérification des éléments du modal:');
        console.log('  - rechercheInput:', !!modalInput);
        console.log('  - rechercheBtn:', !!modalBtn);
        console.log('  - rechercheModal:', !!modal);
        
        if (!modalInput || !modalBtn) {
            console.error('❌ Éléments du modal de recherche non trouvés');
            return;
        }
        
        // Créer les alias pour la compatibilité
        this.createCompatibilityAliases(modalInput, modalBtn, modal);
        
        // Configurer les événements
        this.setupEvents(modalInput, modalBtn);
        
        console.log('✅ Compatibilité de recherche configurée');
    }
    
    createCompatibilityAliases(input, btn, modal) {
        // Créer des éléments cachés avec les IDs attendus par le script
        if (!document.getElementById('universalSearchInput')) {
            const hiddenInput = document.createElement('input');
            hiddenInput.id = 'universalSearchInput';
            hiddenInput.style.display = 'none';
            document.body.appendChild(hiddenInput);
            
            // Synchroniser les valeurs
            input.addEventListener('input', () => {
                hiddenInput.value = input.value;
        }
        
        if (!document.getElementById('universalSearchBtn')) {
            const hiddenBtn = document.createElement('button');
            hiddenBtn.id = 'universalSearchBtn';
            hiddenBtn.style.display = 'none';
            document.body.appendChild(hiddenBtn);
        }
        
        if (!document.getElementById('rechercheUniverselleModal')) {
            const hiddenModal = document.createElement('div');
            hiddenModal.id = 'rechercheUniverselleModal';
            hiddenModal.style.display = 'none';
            document.body.appendChild(hiddenModal);
        }
    }
    
    setupEvents(input, btn) {
        // Fonction de recherche unifiée
        const performSearch = () => {
            const searchTerm = input.value.trim();
            
            if (searchTerm.length < 2) {
                this.showAlert('Veuillez saisir au moins 2 caractères pour la recherche');
                return;
            }
            
            console.log('🔍 Recherche lancée:', searchTerm);
            this.showLoading();
            
            // Essayer les deux endpoints
            this.trySearchEndpoints(searchTerm);
        };
        
        // Événement du bouton
        btn.addEventListener('click', performSearch);
        
        // Événement de l'input (Entrée)
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        
        // Recherche en temps réel (optionnel)
        let searchTimeout;
        input.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    console.log('🔍 Recherche automatique:', query);
                    this.trySearchEndpoints(query);
                }, 500);
            }
    }
    
    async trySearchEndpoints(searchTerm) {
        const endpoints = [
            'ajax/recherche-universelle-new.php'
            'ajax/recherche_universelle.php'
            'ajax/recherche-simple.php'
        ];
        
        for (const endpoint of endpoints) {
            try {
                console.log(`📡 Test de l'endpoint: ${endpoint}`);
                
                const response = await fetch(endpoint, {
                    method: 'POST'
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                    body: `terme=${encodeURIComponent(searchTerm)}`
                
                console.log(`📊 ${endpoint} - Statut: ${response.status}`);
                
                if (response.ok) {
                    const text = await response.text();
                    
                    try {
                        const data = JSON.parse(text);
                        console.log(`✅ ${endpoint} - Réponse JSON valide`);
                        this.displayResults(data, endpoint);
                        return; // Succès, arrêter les tentatives
                    } catch (parseError) {
                        console.log(`❌ ${endpoint} - JSON invalide:`, parseError.message);
                        console.log(`📄 Contenu: ${text.substring(0, 200)}...`);
                    }
                } else {
                    console.log(`❌ ${endpoint} - Erreur HTTP: ${response.status}`);
                }
                
            } catch (error) {
                console.log(`❌ ${endpoint} - Erreur réseau:`, error.message);
            }
        }
        
        // Si aucun endpoint ne fonctionne
        console.error('❌ Aucun endpoint de recherche disponible');
        this.showError('Service de recherche temporairement indisponible');
    }
    
    displayResults(data, endpoint) {
        console.log(`📊 Affichage des résultats de ${endpoint}:`, data);
        
        // Cacher le loading
        this.hideLoading();
        
        // Extraire les résultats selon le format
        let clients = [];
        let reparations = [];
        let commandes = [];
        
        if (data.data) {
            clients = data.data.clients || [];
            reparations = data.data.reparations || [];
            commandes = data.data.commandes || [];
        } else if (data.results) {
            clients = data.results.clients || [];
            reparations = data.results.reparations || [];
            commandes = data.results.commandes || [];
        } else {
            clients = data.clients || [];
            reparations = data.reparations || [];
            commandes = data.commandes || [];
        }
        
        const totalResults = clients.length + reparations.length + commandes.length;
        
        if (totalResults === 0) {
            this.showEmpty();
            return;
        }
        
        // Mettre à jour les compteurs
        this.updateCounters(clients.length, reparations.length, commandes.length);
        
        // Remplir les tableaux
        this.fillTables(clients, reparations, commandes);
        
        // Afficher les résultats
        this.showResults();
        
        console.log(`✅ ${totalResults} résultats affichés`);
    }
    
    updateCounters(clientsCount, reparationsCount, commandesCount) {
        const clientsCountEl = document.getElementById('clientsCount');
        const reparationsCountEl = document.getElementById('reparationsCount');
        const commandesCountEl = document.getElementById('commandesCount');
        
        if (clientsCountEl) clientsCountEl.textContent = clientsCount;
        if (reparationsCountEl) reparationsCountEl.textContent = reparationsCount;
        if (commandesCountEl) commandesCountEl.textContent = commandesCount;
    }
    
    fillTables(clients, reparations, commandes) {
        // Remplir le tableau des clients
        const clientsTableBody = document.getElementById('clientsTableBody');
        if (clientsTableBody && clients.length > 0) {
            clientsTableBody.innerHTML = '';
            clients.forEach(client => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${client.nom || ''} ${client.prenom || ''}</strong></td>
                    <td><span class="phone-number">${this.formatPhone(client.telephone)}</span></td>
                    <td>${client.email || '-'}</td>
                    <td>${client.date_creation || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="voirClient(${client.id})" title="Voir le client">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                clientsTableBody.appendChild(row);
        }
        
        // Remplir le tableau des réparations
        const reparationsTableBody = document.getElementById('reparationsTableBody');
        if (reparationsTableBody && reparations.length > 0) {
            reparationsTableBody.innerHTML = '';
            reparations.forEach(reparation => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>#${reparation.id}</strong></td>
                    <td>${reparation.client_nom || ''} ${reparation.client_prenom || ''}</td>
                    <td>${reparation.type_appareil || ''} ${reparation.modele || ''}</td>
                    <td>${reparation.probleme_declare || '-'}</td>
                    <td><span class="badge bg-info">${reparation.statut || '-'}</span></td>
                    <td>${reparation.date_reception || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="voirReparation(${reparation.id})" title="Voir la réparation">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                reparationsTableBody.appendChild(row);
        }
        
        // Remplir le tableau des commandes
        const commandesTableBody = document.getElementById('commandesTableBody');
        if (commandesTableBody && commandes.length > 0) {
            commandesTableBody.innerHTML = '';
            commandes.forEach(commande => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>#${commande.id}</strong></td>
                    <td>${commande.piece || '-'}</td>
                    <td>${commande.appareil || '-'}</td>
                    <td>${commande.client_nom || '-'}</td>
                    <td>${commande.fournisseur || '-'}</td>
                    <td><span class="badge bg-warning">${commande.statut || '-'}</span></td>
                    <td>${commande.date_commande || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="voirCommande(${commande.id})" title="Voir la commande">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                commandesTableBody.appendChild(row);
        }
    }
    
    showLoading() {
        const loading = document.getElementById('rechercheLoading');
        const btnContainer = document.getElementById('rechercheBtns');
        const empty = document.getElementById('rechercheEmpty');
        const results = document.querySelectorAll('.result-container');
        
        if (loading) loading.style.display = 'block';
        if (btnContainer) btnContainer.style.display = 'none';
        if (empty) empty.style.display = 'none';
        results.forEach(el => el.style.display = 'none');
    }
    
    hideLoading() {
        const loading = document.getElementById('rechercheLoading');
        if (loading) loading.style.display = 'none';
    }
    
    showResults() {
        const btnContainer = document.getElementById('rechercheBtns');
        if (btnContainer) btnContainer.style.display = 'block';
        
        // Afficher le premier onglet avec des résultats
        if (document.getElementById('clientsCount')?.textContent !== '0') {
            this.showTab('clients');
        } else if (document.getElementById('reparationsCount')?.textContent !== '0') {
            this.showTab('reparations');
        } else if (document.getElementById('commandesCount')?.textContent !== '0') {
            this.showTab('commandes');
        }
    }
    
    showEmpty() {
        const empty = document.getElementById('rechercheEmpty');
        const btnContainer = document.getElementById('rechercheBtns');
        const results = document.querySelectorAll('.result-container');
        
        this.hideLoading();
        if (empty) empty.style.display = 'block';
        if (btnContainer) btnContainer.style.display = 'none';
        results.forEach(el => el.style.display = 'none');
    }
    
    showTab(tab) {
        // Cacher tous les conteneurs
        document.querySelectorAll('.result-container').forEach(el => {
            el.style.display = 'none';
        
        // Afficher le conteneur sélectionné
        const container = document.getElementById(`${tab}-results`);
        if (container) {
            container.style.display = 'block';
        }
        
        // Mettre à jour les boutons
        document.querySelectorAll('#rechercheBtns .btn').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        
        const activeBtn = document.getElementById(`btn-${tab}`);
        if (activeBtn) {
            activeBtn.classList.remove('btn-outline-primary');
            activeBtn.classList.add('btn-primary');
        }
    }
    
    showAlert(message) {
        alert(message);
    }
    
    showError(message) {
        console.error('❌ Erreur de recherche:', message);
        this.showEmpty();
    }
    
    formatPhone(phone) {
        if (!phone) return '';
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 10) {
            return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1.$2.$3.$4.$5');
        }
        return phone;
    }
}

// Fonctions globales pour la compatibilité
window.showTab = function(tab) {
    const instance = window.rechercheCompatibility;
    if (instance) {
        instance.showTab(tab);
    }
};

// Initialiser quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.rechercheCompatibility = new RechercheUniverselleCompatibility();

console.log('🔧 Script de compatibilité de recherche chargé');