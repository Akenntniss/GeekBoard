/**
 * Module simplifié de gestion du modal des réparations
 */
window.RepairModal = {
    // Éléments DOM
    elements: {
        modal: null,
        detailsContainer: null,
        loader: null
    },

    // Configuration
    config: {
        apiUrl: 'ajax/get_repair_details.php'
    },
    
    // Flag d'initialisation
    _isInitialized: false,
    _isLoading: false,

    /**
     * Initialise le module
     */
    init: function() {
        console.log('🔧 [RepairModal] Initialisation...');
        
        // Attendre que le DOM soit complètement chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initElements());
        } else {
            this.initElements();
        }
    },
    
    /**
     * Initialise les éléments DOM
     */
    initElements: function() {
        console.log('🔍 [RepairModal] Recherche des éléments DOM...');
        
        // Récupérer les éléments
        this.elements.modal = document.getElementById('repairDetailsModal');
        this.elements.detailsContainer = document.getElementById('repairDetailsContent');
        this.elements.loader = document.getElementById('repairDetailsLoader');
        
        console.log('📋 [RepairModal] Éléments trouvés:', {
            modal: !!this.elements.modal,
            detailsContainer: !!this.elements.detailsContainer,
            loader: !!this.elements.loader
        });
        
        if (this.elements.modal && this.elements.detailsContainer && this.elements.loader) {
            this._isInitialized = true;
            console.log('✅ [RepairModal] Initialisation réussie');
        } else {
            console.error('❌ [RepairModal] Éléments manquants');
        }
    },

    /**
     * Charge les détails d'une réparation
     */
    loadRepairDetails: function(repairId) {
        console.log('🔄 [RepairModal] Chargement réparation:', repairId);
        
        // Empêcher les appels multiples
        if (this._isLoading) {
            console.warn('⚠️ [RepairModal] Chargement déjà en cours');
            return;
        }
        
        this._isLoading = true;
        
        // Vérifier l'initialisation
        if (!this._isInitialized) {
            console.log('🔧 [RepairModal] Réinitialisation...');
            this.initElements();
        }
        
        // Afficher le modal
        this.showModal();
        
        // Afficher le loader
        this.showLoader();
        
        // Construire l'URL de l'API
        const shopId = window.shopId || 63;
        const userId = window.currentUserId || 6;
        const apiUrl = `${this.config.apiUrl}?id=${repairId}&shop_id=${shopId}&user_id=${userId}`;
        
        console.log('📡 [RepairModal] URL API:', apiUrl);
        
        // Timeout de sécurité
        const timeoutId = setTimeout(() => {
            if (this._isLoading) {
                console.error('⏱️ [RepairModal] Timeout');
                this.hideLoader();
                this._isLoading = false;
                this.showError('Timeout: Le chargement prend trop de temps');
            }
        }, 15000);
        
        // Faire la requête
        fetch(apiUrl)
            .then(response => {
                console.log('📡 [RepairModal] Réponse:', response.status);
                if (!response.ok) {
                    throw new Error(`Erreur HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                clearTimeout(timeoutId);
                console.log('✅ [RepairModal] Données reçues:', data);
                
                this.hideLoader();
                this._isLoading = false;
                
                if (!data.success) {
                    throw new Error(data.error || 'Erreur inconnue');
                }
                
                this.renderRepairDetails(data);
            })
            .catch(error => {
                clearTimeout(timeoutId);
                console.error('❌ [RepairModal] Erreur:', error);
                
                this.hideLoader();
                this._isLoading = false;
                this.showError('Erreur: ' + error.message);
            });
    },

    /**
     * Affiche le modal
     */
    showModal: function() {
        if (this.elements.modal && typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(this.elements.modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modalInstance.show();
            console.log('✅ [RepairModal] Modal affiché');
        }
    },

    /**
     * Affiche le loader
     */
    showLoader: function() {
        if (this.elements.loader) {
            this.elements.loader.style.display = 'block';
            console.log('📊 [RepairModal] Loader affiché');
        }
        if (this.elements.detailsContainer) {
            this.elements.detailsContainer.innerHTML = '<div class="text-center p-4">Chargement des détails...</div>';
        }
    },

    /**
     * Cache le loader
     */
    hideLoader: function() {
        if (this.elements.loader) {
            this.elements.loader.style.display = 'none';
            console.log('📊 [RepairModal] Loader caché');
        }
    },

    /**
     * Affiche une erreur
     */
    showError: function(message) {
        if (this.elements.detailsContainer) {
            this.elements.detailsContainer.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${message}
                </div>
            `;
        }
    },

    /**
     * Affiche les détails de la réparation
     */
    renderRepairDetails: function(data) {
        console.log('🎨 [RepairModal] Rendu des détails...');
        
        if (!this.elements.detailsContainer) {
            console.error('❌ [RepairModal] Conteneur non trouvé');
            return;
        }
        
        const repair = data.repair;
        
        // HTML simplifié pour les détails
        const html = `
            <div class="repair-details p-3">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Informations Client</h5>
                        <p><strong>Nom:</strong> ${repair.client_nom || ''} ${repair.client_prenom || ''}</p>
                        <p><strong>Téléphone:</strong> ${repair.client_telephone || ''}</p>
                        <p><strong>Email:</strong> ${repair.client_email || ''}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Informations Réparation</h5>
                        <p><strong>Appareil:</strong> ${repair.type_appareil || ''}</p>
                        <p><strong>Marque:</strong> ${repair.marque || ''}</p>
                        <p><strong>Modèle:</strong> ${repair.modele || ''}</p>
                        <p><strong>Statut:</strong> ${repair.statut_nom || repair.statut || ''}</p>
                        <p><strong>Prix:</strong> ${repair.prix_reparation_formatte || repair.prix_reparation || ''}€</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Description du problème</h5>
                        <p>${repair.description_probleme || ''}</p>
                    </div>
                </div>
            </div>
        `;
        
        this.elements.detailsContainer.innerHTML = html;
        console.log('✅ [RepairModal] Détails affichés');
    }
};

// Auto-initialisation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.RepairModal.init();
    });
} else {
    window.RepairModal.init();
}

console.log('📦 [RepairModal] Module chargé');
