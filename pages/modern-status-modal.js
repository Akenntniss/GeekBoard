/**
 * MODAL MODERNE DE CHANGEMENT DE STATUT
 * =====================================
 * 
 * Gestion complète du modal chooseStatusModal avec une interface moderne
 * et toutes les fonctionnalités de l'ancien système.
 * 
 * Fonctionnalités :
 * - Affichage des informations de la réparation
 * - Chargement et affichage des statuts par catégorie
 * - Sélection de statut avec interface moderne
 * - Toggle SMS avec switch moderne
 * - Confirmation et mise à jour du statut
 * - Gestion des erreurs et états de chargement
 */

class ModernStatusModal {
    constructor() {
        this.modal = null;
        this.currentRepairId = null;
        this.currentCategoryId = null;
        this.selectedStatusId = null;
        this.repairData = null;
        this.statusesData = null;
        
        this.initializeModal();
        this.bindEvents();
        
        console.log('✅ ModernStatusModal initialisé');
    }
    
    /**
     * Initialise le modal et ses éléments
     */
    initializeModal() {
        const modalElement = document.getElementById('chooseStatusModal');
        if (!modalElement) {
            console.error('❌ Modal chooseStatusModal non trouvé');
            return;
        }
        
        this.modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });
        
        // Éléments du DOM
        this.elements = {
            modal: modalElement,
            repairNumber: document.getElementById('currentRepairNumber'),
            repairClient: document.getElementById('currentRepairClient'),
            repairDevice: document.getElementById('currentRepairDevice'),
            currentStatus: document.getElementById('currentStatusDisplay'),
            statusContainer: document.getElementById('statusCategoriesContainer'),
            smsToggle: document.getElementById('sendSmsToggle'),
            // confirmBtn: document.getElementById('confirmStatusChange'), // Plus nécessaire
            repairIdInput: document.getElementById('chooseStatusRepairId'),
            categoryIdInput: document.getElementById('chooseStatusCategoryId'),
            selectedStatusInput: document.getElementById('selectedStatusId')
        };
    }
    
    /**
     * Lie les événements
     */
    bindEvents() {
        // Événement de fermeture
        this.elements.modal.addEventListener('hidden.bs.modal', () => {
            this.resetModal();
        });
        
        // Événement d'ouverture
        this.elements.modal.addEventListener('shown.bs.modal', () => {
            this.onModalShown();
        });
    }
    
    /**
     * Ouvre le modal avec les données de la réparation
     */
    async openModal(repairId, categoryId, repairData = null) {
        console.log('🔄 Ouverture du modal de statut pour réparation:', repairId);
        
        this.currentRepairId = repairId;
        this.currentCategoryId = categoryId;
        this.repairData = repairData;
        
        // Stocker les IDs dans les champs cachés
        if (this.elements.repairIdInput) this.elements.repairIdInput.value = repairId;
        if (this.elements.categoryIdInput) this.elements.categoryIdInput.value = categoryId;
        
        // Afficher les informations de la réparation
        this.displayRepairInfo();
        
        // Réinitialiser l'état
        this.resetSelection();
        
        // Afficher le modal
        this.modal.show();
        
        // Charger les statuts (sera fait dans onModalShown)
    }
    
    /**
     * Appelé quand le modal est affiché
     */
    async onModalShown() {
        try {
            await this.loadStatuses();
        } catch (error) {
            console.error('❌ Erreur lors du chargement des statuts:', error);
            this.showError('Erreur lors du chargement des statuts');
        }
    }
    
    /**
     * Affiche les informations de la réparation
     */
    displayRepairInfo() {
        if (!this.repairData) {
            // Si pas de données, essayer de récupérer depuis la page
            this.repairData = this.getRepairDataFromPage();
        }
        
        if (this.repairData) {
            if (this.elements.repairNumber) {
                this.elements.repairNumber.textContent = this.repairData.numero || this.currentRepairId;
            }
            if (this.elements.repairClient) {
                this.elements.repairClient.textContent = this.repairData.client || 'Client non spécifié';
            }
            if (this.elements.repairDevice) {
                this.elements.repairDevice.textContent = this.repairData.appareil || 'Appareil non spécifié';
            }
            if (this.elements.currentStatus) {
                this.elements.currentStatus.textContent = this.repairData.statut || 'Statut actuel';
            }
        } else {
            // Valeurs par défaut
            if (this.elements.repairNumber) this.elements.repairNumber.textContent = this.currentRepairId;
            if (this.elements.repairClient) this.elements.repairClient.textContent = 'Chargement...';
            if (this.elements.repairDevice) this.elements.repairDevice.textContent = 'Chargement...';
            if (this.elements.currentStatus) this.elements.currentStatus.textContent = 'Chargement...';
        }
    }
    
    /**
     * Récupère les données de la réparation depuis la page
     */
    getRepairDataFromPage() {
        console.log('🔍 Recherche des données pour la réparation:', this.currentRepairId);
        
        // Essayer différents sélecteurs pour trouver la carte/ligne de réparation
        const selectors = [
            `[data-repair-id="${this.currentRepairId}"]`,
            `[data-id="${this.currentRepairId}"]`,
            `.repair-card[data-repair-id="${this.currentRepairId}"]`,
            `.custom-table-row[data-repair-id="${this.currentRepairId}"]`,
            `.modern-card[data-repair-id="${this.currentRepairId}"]`,
            `.dashboard-card[data-repair-id="${this.currentRepairId}"]`
        ];
        
        let repairElement = null;
        for (const selector of selectors) {
            repairElement = document.querySelector(selector);
            if (repairElement) {
                console.log('✅ Élément trouvé avec le sélecteur:', selector);
                break;
            }
        }
        
        if (repairElement) {
            // Essayer différents sélecteurs pour les données
            const clientSelectors = [
                '.client-name', '.repair-client', '.nom-client', 
                '[data-client]', '.client-info', '.custom-table-cell:nth-child(2)',
                '.card-client', '.repair-card-client'
            ];
            
            const deviceSelectors = [
                '.device-name', '.repair-device', '.appareil', 
                '[data-device]', '.device-info', '.custom-table-cell:nth-child(3)',
                '.card-device', '.repair-card-device'
            ];
            
            const statusSelectors = [
                '.status-badge', '.repair-status', '.statut', 
                '[data-status]', '.status-info', '.custom-table-cell:nth-child(4)',
                '.card-status', '.repair-card-status', '.badge'
            ];
            
            const getTextFromSelectors = (selectors) => {
                for (const selector of selectors) {
                    const element = repairElement.querySelector(selector);
                    if (element && element.textContent.trim()) {
                        return element.textContent.trim();
                    }
                }
                return null;
            };
            
            const client = getTextFromSelectors(clientSelectors);
            const appareil = getTextFromSelectors(deviceSelectors);
            const statut = getTextFromSelectors(statusSelectors);
            
            console.log('📋 Données extraites:', { client, appareil, statut });
            
            return {
                numero: this.currentRepairId,
                client: client || 'Client non spécifié',
                appareil: appareil || 'Appareil non spécifié',
                statut: statut || 'Statut actuel'
            };
        }
        
        console.log('⚠️ Aucun élément trouvé pour la réparation:', this.currentRepairId);
        return {
            numero: this.currentRepairId,
            client: 'Client non spécifié',
            appareil: 'Appareil non spécifié',
            statut: 'Statut actuel'
        };
    }
    
    /**
     * Charge les statuts disponibles
     */
    async loadStatuses() {
        console.log('📡 Chargement des statuts...');
        
        this.showLoadingState();
        
        try {
            const response = await fetch('ajax/get_all_statuts.php');
            const data = await response.json();
            
            console.log('📥 Données des statuts reçues:', data);
            
            if (data.success && data.statuts) {
                this.statusesData = data.statuts;
                this.renderStatusCategories();
            } else {
                throw new Error(data.error || 'Erreur lors du chargement des statuts');
            }
        } catch (error) {
            console.error('❌ Erreur chargement statuts:', error);
            this.showError('Impossible de charger les statuts disponibles');
        }
    }
    
    /**
     * Affiche l'état de chargement
     */
    showLoadingState() {
        if (!this.elements.statusContainer) return;
        
        this.elements.statusContainer.innerHTML = `
            <div class="status-loading-container">
                <div class="status-loading-spinner">
                    <div class="modern-spinner"></div>
                </div>
                <h6 class="status-loading-title">Chargement des statuts...</h6>
                <p class="status-loading-text">Récupération des statuts disponibles</p>
            </div>
        `;
    }
    
    /**
     * Affiche une erreur
     */
    showError(message) {
        if (!this.elements.statusContainer) return;
        
        this.elements.statusContainer.innerHTML = `
            <div class="status-loading-container">
                <div class="status-loading-spinner">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444;"></i>
                </div>
                <h6 class="status-loading-title" style="color: #ef4444;">Erreur</h6>
                <p class="status-loading-text">${message}</p>
                <button type="button" class="btn btn-outline-primary mt-3" onclick="window.modernStatusModal.loadStatuses()">
                    <i class="fas fa-redo me-2"></i>Réessayer
                </button>
            </div>
        `;
    }
    
    /**
     * Rend les catégories de statuts
     */
    renderStatusCategories() {
        if (!this.elements.statusContainer || !this.statusesData) return;
        
        console.log('🎨 Rendu des catégories de statuts');
        
        let html = '';
        
        Object.entries(this.statusesData).forEach(([categoryCode, categoryData]) => {
            const categoryColor = this.getCategoryColor(categoryData.couleur);
            const categoryIcon = this.getCategoryIcon(categoryCode);
            
            html += `
                <div class="status-category">
                    <div class="status-category-title" style="background: ${categoryColor};">
                        <i class="fas fa-${categoryIcon} status-category-icon"></i>
                        <span>${categoryData.nom}</span>
                    </div>
                    <div class="status-buttons-grid">
            `;
            
            categoryData.statuts.forEach(statut => {
                const statusColor = this.getStatusColor(categoryData.couleur);
                
                html += `
                    <div class="status-option-btn" data-status-id="${statut.id}" data-status-code="${statut.code}">
                        <div class="status-option-icon" style="background: ${statusColor};">
                            <i class="fas fa-${this.getStatusIcon(statut.code)}"></i>
                        </div>
                        <div class="status-option-info">
                            <div class="status-option-title">${statut.nom}</div>
                            <div class="status-option-description">${this.getStatusDescription(statut.code)}</div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        });
        
        this.elements.statusContainer.innerHTML = html;
        
        // Ajouter les événements de clic
        this.bindStatusEvents();
    }
    
    /**
     * Lie les événements des boutons de statut
     */
    bindStatusEvents() {
        const statusButtons = this.elements.statusContainer.querySelectorAll('.status-option-btn');
        
        statusButtons.forEach(button => {
            button.addEventListener('click', () => {
                const statusId = button.dataset.statusId;
                const statusCode = button.dataset.statusCode;
                
                this.selectStatus(statusId, statusCode, button);
            });
        });
    }
    
    /**
     * Sélectionne un statut et l'applique automatiquement
     */
    async selectStatus(statusId, statusCode, buttonElement) {
        console.log('✅ Statut sélectionné:', { statusId, statusCode });
        
        // Désélectionner tous les boutons
        this.elements.statusContainer.querySelectorAll('.status-option-btn').forEach(btn => {
            btn.classList.remove('selected');
            btn.style.pointerEvents = 'none'; // Désactiver temporairement tous les boutons
        });
        
        // Sélectionner le bouton cliqué et afficher le chargement
        buttonElement.classList.add('selected');
        const originalContent = buttonElement.innerHTML;
        buttonElement.innerHTML = `
            <div class="status-option-icon" style="background: var(--day-primary);">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div class="status-option-info">
                <div class="status-option-title">Application en cours...</div>
                <div class="status-option-description">Mise à jour du statut</div>
            </div>
        `;
        
        // Stocker la sélection
        this.selectedStatusId = statusId;
        if (this.elements.selectedStatusInput) {
            this.elements.selectedStatusInput.value = statusId;
        }
        
        // Appliquer automatiquement le changement
        try {
            await this.confirmStatusChange();
        } catch (error) {
            // En cas d'erreur, restaurer l'état original
            buttonElement.innerHTML = originalContent;
            this.elements.statusContainer.querySelectorAll('.status-option-btn').forEach(btn => {
                btn.classList.remove('selected');
                btn.style.pointerEvents = 'auto';
            });
            console.error('❌ Erreur lors de l\'application automatique:', error);
        }
    }
    
    /**
     * Confirme le changement de statut
     */
    async confirmStatusChange() {
        if (!this.selectedStatusId || !this.currentRepairId) {
            console.error('❌ Données manquantes pour la confirmation:', {
                selectedStatusId: this.selectedStatusId,
                currentRepairId: this.currentRepairId
            });
            throw new Error('Données manquantes pour la confirmation');
        }
        
        const sendSms = this.elements.smsToggle ? this.elements.smsToggle.checked : true;
        
        console.log('🔄 Confirmation du changement de statut:', {
            repairId: this.currentRepairId,
            statusId: this.selectedStatusId,
            sendSms
        });
        
        // Désactiver le bouton et afficher le chargement
        const originalText = this.elements.confirmBtn.innerHTML;
        this.elements.confirmBtn.disabled = true;
        this.elements.confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mise à jour...';
        
        try {
            const formData = new FormData();
            formData.append('repair_id', this.currentRepairId);
            formData.append('status_id', this.selectedStatusId);
            formData.append('send_sms', sendSms ? '1' : '0');
            
            // Ajouter l'ID du magasin depuis différentes sources
            let shopId = null;
            
            // Essayer différentes sources pour l'ID du magasin
            if (window.currentShopId) {
                shopId = window.currentShopId;
            } else if (document.body.getAttribute('data-shop-id')) {
                shopId = document.body.getAttribute('data-shop-id');
            } else if (typeof getShopId === 'function') {
                shopId = getShopId();
            } else if (window.shopId) {
                shopId = window.shopId;
            } else if (window.SessionHelper && typeof window.SessionHelper.getShopId === 'function') {
                shopId = window.SessionHelper.getShopId();
            } else {
                // Essayer de récupérer depuis les métadonnées de la page
                const metaShopId = document.querySelector('meta[name="shop-id"]');
                if (metaShopId) {
                    shopId = metaShopId.content;
                }
            }
            
            if (shopId) {
                formData.append('shop_id', shopId);
                console.log('🏪 ID magasin trouvé:', shopId);
            } else {
                console.warn('⚠️ ID magasin non trouvé, l\'endpoint utilisera la session');
            }
            
            const response = await fetch('ajax/update_repair_status.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Statut mis à jour avec succès');
                
                // Fermer le modal
                this.modal.hide();
                
                // Actualiser la page ou la carte
                this.updateRepairDisplay();
                
                // Afficher une notification de succès
                this.showSuccessNotification('Statut mis à jour avec succès');
                
            } else {
                throw new Error(result.message || 'Erreur lors de la mise à jour');
            }
            
        } catch (error) {
            console.error('❌ Erreur lors de la mise à jour:', error);
            alert('Erreur lors de la mise à jour du statut : ' + error.message);
            
            // Restaurer le bouton
            this.elements.confirmBtn.disabled = false;
            this.elements.confirmBtn.innerHTML = originalText;
        }
    }
    
    /**
     * Met à jour l'affichage de la réparation
     */
    updateRepairDisplay() {
        // Recharger la page pour voir les changements
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    /**
     * Affiche une notification de succès
     */
    showSuccessNotification(message) {
        // Créer une notification temporaire
        const notification = document.createElement('div');
        notification.className = 'alert alert-success position-fixed';
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    /**
     * Réinitialise la sélection
     */
    resetSelection() {
        this.selectedStatusId = null;
        if (this.elements.selectedStatusInput) {
            this.elements.selectedStatusInput.value = '';
        }
    }
    
    /**
     * Réinitialise le modal
     */
    resetModal() {
        this.currentRepairId = null;
        this.currentCategoryId = null;
        this.selectedStatusId = null;
        this.repairData = null;
        this.statusesData = null;
        
        this.resetSelection();
        this.showLoadingState();
    }
    
    /**
     * Obtient la couleur d'une catégorie
     */
    getCategoryColor(couleur) {
        const colorMap = {
            'primary': '#3b82f6',
            'success': '#10b981',
            'warning': '#f59e0b',
            'danger': '#ef4444',
            'info': '#06b6d4',
            'secondary': '#6b7280'
        };
        
        return colorMap[couleur] || colorMap.primary;
    }
    
    /**
     * Obtient l'icône d'une catégorie
     */
    getCategoryIcon(categoryCode) {
        const iconMap = {
            'nouvelles': 'plus-circle',
            'en_cours': 'cog',
            'en_attente': 'clock',
            'terminees': 'check-circle',
            'annulees': 'times-circle'
        };
        
        return iconMap[categoryCode] || 'circle';
    }
    
    /**
     * Obtient la couleur d'un statut
     */
    getStatusColor(couleur) {
        return this.getCategoryColor(couleur);
    }
    
    /**
     * Obtient l'icône d'un statut
     */
    getStatusIcon(statusCode) {
        const iconMap = {
            'nouvelle': 'plus',
            'en_cours_diagnostic': 'search',
            'en_cours_intervention': 'wrench',
            'en_attente_piece': 'clock',
            'en_attente_client': 'user-clock',
            'termine': 'check',
            'restitue': 'hand-holding',
            'annule': 'times',
            'gardiennage': 'shield-alt'
        };
        
        return iconMap[statusCode] || 'circle';
    }
    
    /**
     * Obtient la description d'un statut
     */
    getStatusDescription(statusCode) {
        const descriptionMap = {
            'nouvelle': 'Nouvelle réparation à traiter',
            'en_cours_diagnostic': 'Diagnostic en cours',
            'en_cours_intervention': 'Intervention en cours',
            'en_attente_piece': 'En attente de pièce',
            'en_attente_client': 'En attente du client',
            'termine': 'Réparation terminée',
            'restitue': 'Appareil restitué',
            'annule': 'Réparation annulée',
            'gardiennage': 'En gardiennage'
        };
        
        return descriptionMap[statusCode] || 'Changer vers ce statut';
    }
}

// Initialiser le modal au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    window.modernStatusModal = new ModernStatusModal();
    console.log('✅ ModernStatusModal disponible globalement');
});

// Fonction de compatibilité pour l'ancien système
window.openChooseStatusModal = function(repairId, categoryId, repairData = null) {
    console.log('🔄 [COMPAT] Ouverture du modal via fonction legacy');
    if (window.modernStatusModal) {
        window.modernStatusModal.openModal(repairId, categoryId, repairData);
    } else {
        console.error('❌ ModernStatusModal non initialisé');
    }
};

console.log('✅ ModernStatusModal chargé');
