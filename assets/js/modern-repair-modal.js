/**
 * Modal de réparation moderne - Version complètement refaite
 * Gère l'affichage et les interactions avec le modal de détails des réparations
 */

class ModernRepairModal {
    constructor() {
        this.modal = null;
        this.currentRepairId = null;
        this.currentRepairData = null;
        this.isInitialized = false;

        this.init();
    }

    init() {
        if (this.isInitialized) return;

        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initElements());
        } else {
            this.initElements();
        }
    }

    initElements() {
        this.modal = document.getElementById('repairDetailsModal');
        if (!this.modal) {
            console.error('Modal repairDetailsModal non trouvé');
            return;
        }

        this.isInitialized = true;
        this.attachEventListeners();
        // console.log('✅ ModernRepairModal initialisé');
    }

    attachEventListeners() {
        // Écouter les clics sur les boutons de détails
        document.addEventListener('click', (e) => {
            // Boutons de détails dans les cartes
            if (e.target.closest('.view-repair-details')) {
                e.preventDefault();
                const btn = e.target.closest('.view-repair-details');
                const repairId = btn.getAttribute('data-repair-id') ||
                    btn.closest('[data-repair-id]')?.getAttribute('data-repair-id') ||
                    btn.closest('[data-id]')?.getAttribute('data-id');
                if (repairId) {
                    this.openModal(repairId);
                }
            }

            // Clics sur les cartes elles-mêmes
            if (e.target.closest('.modern-card, .draggable-card, .dashboard-card')) {
                // Vérifier que ce n'est pas un bouton ou un lien
                if (!e.target.closest('button, a, .btn, [onclick]')) {
                    const card = e.target.closest('.modern-card, .draggable-card, .dashboard-card');
                    const repairId = card.getAttribute('data-repair-id') || card.getAttribute('data-id');
                    if (repairId) {
                        this.openModal(repairId);
                    }
                }
            }
        });

        // Écouter la fermeture du modal
        this.modal.addEventListener('hidden.bs.modal', () => {
            this.currentRepairId = null;
            this.currentRepairData = null;
        });
    }

    async openModal(repairId) {
        if (!repairId) {
            console.error('ID de réparation manquant');
            return;
        }

        this.currentRepairId = repairId;

        // Ouvrir le modal immédiatement
        const modalInstance = new bootstrap.Modal(this.modal, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modalInstance.show();

        // Afficher l'état de chargement
        this.showLoadingState();

        try {
            // Charger les données
            const data = await this.loadRepairData(repairId);
            if (data.success && data.repair) {
                this.currentRepairData = data.repair;
                // Ajouter les photos à l'objet repair pour simplifier l'accès
                data.repair.photos = data.photos || [];

                // Ajouter la photo principale de l'appareil au début si elle existe
                if (data.repair.photo_appareil) {
                    data.repair.photos.unshift({
                        id: 'main',
                        url: data.repair.photo_appareil,
                        description: 'Photo principale de l\'appareil',
                        is_main: true,
                        date_upload: data.repair.date_reception || 'Prise en charge'
                    });
                }

                this.renderModalContent(data.repair);
            } else {
                this.showErrorState(data.error || 'Erreur lors du chargement des données');
            }
        } catch (error) {
            console.error('Erreur lors du chargement:', error);
            this.showErrorState('Erreur de connexion');
        }
    }

    async loadRepairData(repairId) {
        const shopId = document.body.getAttribute('data-shop-id') ||
            window.currentShopId ||
            (typeof current_shop_id !== 'undefined' ? current_shop_id : '');

        const apiUrl = `ajax/get_repair_details.php?id=${repairId}${shopId ? '&shop_id=' + shopId : ''}`;

        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        // console.log('📸 Photos récupérées pour la réparation', repairId, ':', data.photos ? data.photos.length : 0);
        if (data.photos && data.photos.length > 0) {
            // console.log('📸 Première photo:', data.photos[0]);
        }
        return data;
    }

    showLoadingState() {
        const content = this.modal.querySelector('#repairDetailsContent');
        const statusIndicator = this.modal.querySelector('#repairStatusIndicator');
        const titleText = this.modal.querySelector('#repairTitleText');
        const subtitle = this.modal.querySelector('#repairSubtitle');

        // Mettre à jour le header
        if (statusIndicator) {
            statusIndicator.style.background = '#6b7280';
            statusIndicator.style.boxShadow = '0 0 10px rgba(107, 114, 128, 0.5)';
        }

        if (titleText) {
            titleText.innerHTML = `Réparation #<span id="repairIdDisplay">${this.currentRepairId}</span>`;
        }

        if (subtitle) {
            subtitle.textContent = 'Chargement des détails...';
        }

        // Afficher le loader
        if (content) {
            content.innerHTML = `
                <div class="repair-section text-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <h5 class="text-muted">Chargement des détails de la réparation...</h5>
                    <p class="text-muted mb-0">Veuillez patienter</p>
                </div>
            `;
        }
    }

    showErrorState(errorMessage) {
        const content = this.modal.querySelector('#repairDetailsContent');
        const subtitle = this.modal.querySelector('#repairSubtitle');

        if (subtitle) {
            subtitle.textContent = 'Erreur de chargement';
        }

        if (content) {
            content.innerHTML = `
                <div class="repair-section text-center">
                    <div class="text-danger mb-3">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-danger">Erreur de chargement</h5>
                    <p class="text-muted mb-3">${errorMessage}</p>
                    <button class="btn btn-primary" onclick="window.modernRepairModal.openModal(${this.currentRepairId})">
                        <i class="fas fa-redo me-2"></i>Réessayer
                    </button>
                </div>
            `;
        }
    }

    renderModalContent(repair) {
        const content = this.modal.querySelector('#repairDetailsContent');
        const statusIndicator = this.modal.querySelector('#repairStatusIndicator');
        const titleText = this.modal.querySelector('#repairTitleText');
        const subtitle = this.modal.querySelector('#repairSubtitle');
        const warrantyBadge = this.modal.querySelector('#warrantyBadge');

        // Mettre à jour le header
        if (statusIndicator) {
            const statusColor = this.getStatusColor(repair.statut_nom);
            statusIndicator.style.background = statusColor;
            statusIndicator.style.boxShadow = `0 0 10px ${statusColor}50`;
        }

        if (titleText) {
            titleText.innerHTML = `Réparation #<span id="repairIdDisplay">${repair.id}</span>`;
        }

        if (subtitle) {
            subtitle.textContent = `${repair.type_appareil} ${repair.marque} ${repair.modele} - ${repair.statut_nom}`;
        }

        // Badge de garantie
        if (warrantyBadge && repair.sous_garantie) {
            warrantyBadge.classList.add('active');
            warrantyBadge.querySelector('.warranty-text').textContent = 'Sous garantie';
        } else if (warrantyBadge) {
            warrantyBadge.classList.remove('active');
        }

        // Générer le contenu principal
        if (content) {
            content.innerHTML = this.generateModalHTML(repair);
            this.attachModalEventListeners(repair);
        }
    }

    generateModalHTML(repair) {
        const isActiveRepair = repair.employe_id == window.currentUserId && repair.active_repair_id == repair.id;

        return `
            <!-- Section Actions -->
            <div class="repair-section">
                <div class="repair-section-title">
                    <i class="fas fa-cogs"></i>
                    Actions rapides
                </div>
                
                <!-- Actions principales -->
                <div class="repair-actions-grid">
                    <button class="repair-action-btn devis" data-action="devis" data-repair-id="${repair.id}" data-tooltip="Envoyer un devis au client">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>DEVIS</span>
                    </button>
                    <button class="repair-action-btn status" data-action="status" data-repair-id="${repair.id}" data-tooltip="Mise à jour manuel du statut">
                        <i class="fas fa-tasks"></i>
                        <span>STATUT</span>
                    </button>
                    <button class="repair-action-btn price" data-action="price" data-repair-id="${repair.id}" data-tooltip="Mise à jour du prix de la réparation">
                        <i class="fas fa-euro-sign"></i>
                        <span>PRIX</span>
                    </button>
                    <button class="repair-action-btn order" data-action="order" data-repair-id="${repair.id}" data-tooltip="Ajouter une pièce au bon de commande">
                        <i class="fas fa-shopping-cart"></i>
                        <span>COMMANDER</span>
                    </button>
                    <button class="repair-action-btn print" data-action="print" data-repair-id="${repair.id}" data-tooltip="Imprimer l'étiquette avec le QR CODE">
                        <i class="fas fa-print"></i>
                        <span>IMPRIMER</span>
                    </button>
                    <button class="repair-action-btn" onclick="showRepairHistoryModal(${repair.id}, '${repair.client_nom} ${repair.client_prenom}', '${repair.client_telephone}')"
                            data-tooltip="Consulter l'historique de cette réparation"
                            style="background: linear-gradient(135deg, #b45309 0%, #92400e 100%); color: white;">
                        <i class="fas fa-history" style="color: white;"></i>
                        <span>HISTORIQUE</span>
                    </button>
                </div>
                
                <!-- Bouton principal de réparation -->
                <button class="repair-main-action w-100 ${isActiveRepair ? 'stop' : ''}" 
                        data-repair-id="${repair.id}"
                        data-action="${isActiveRepair ? 'stop' : 'start'}">
                    <i class="fas ${isActiveRepair ? 'fa-stop-circle' : 'fa-play-circle'} me-2"></i>
                    ${isActiveRepair ? 'ARRÊTER LA RÉPARATION' : 'DÉMARRER LA RÉPARATION'}
                </button>
            </div>
            
            <!-- Section Informations -->
            <div class="repair-section">
                <div class="repair-section-title">
                    <i class="fas fa-info-circle"></i>
                    Informations
                </div>
                
                <div class="repair-info-grid">
                    <!-- Client -->
                    <div class="repair-info-item">
                        <i class="fas fa-user"></i>
                        <div class="info-content">
                            <div class="info-label">Client</div>
                            <div class="info-value">${repair.client_nom} ${repair.client_prenom}</div>
                        </div>
                    </div>
                    
                    <!-- Téléphone -->
                    <div class="repair-info-item">
                        <i class="fas fa-phone"></i>
                        <div class="info-content">
                            <div class="info-label">Téléphone</div>
                            <div class="info-value">${repair.client_telephone || 'Non renseigné'}</div>
                        </div>
                    </div>
                    
                    <!-- Appareil -->
                    <div class="repair-info-item">
                        <i class="fas fa-mobile-alt"></i>
                        <div class="info-content">
                            <div class="info-label">Appareil</div>
                            <div class="info-value">${repair.type_appareil} ${repair.marque} ${repair.modele}</div>
                        </div>
                    </div>
                    
                    <!-- Créé par -->
                    <div class="repair-info-item">
                        <i class="fas fa-user-plus"></i>
                        <div class="info-content">
                            <div class="info-label">Créé par</div>
                            <div class="info-value">${repair.created_by_name && repair.date_creation_formatted ?
                repair.created_by_name + ', le ' + repair.date_creation_formatted :
                (repair.created_by_name || repair.created_by_username || 'Non renseigné')
            }</div>
                        </div>
                    </div>
                    
                    <!-- Statut -->
                    <div class="repair-info-item">
                        <i class="fas fa-flag"></i>
                        <div class="info-content">
                            <div class="info-label">Statut</div>
                            <div class="info-value">${repair.statut_nom}</div>
                        </div>
                    </div>
                    
                    <!-- Prix -->
                    <div class="repair-info-item">
                        <i class="fas fa-euro-sign"></i>
                        <div class="info-content">
                            <div class="info-label">Prix</div>
                            <div class="info-value">${repair.prix_formatte || (repair.prix_reparation ? repair.prix_reparation + ' €' : (repair.prix ? repair.prix + ' €' : 'Non défini'))}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Description -->
            <div class="repair-section">
                <div class="repair-section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Description du problème
                    <div class="ms-auto">
                        <button class="btn btn-sm btn-outline-primary edit-description-btn" data-repair-id="${repair.id}" style="display: inline-block;">
                            <i class="fas fa-edit"></i>
                            Modifier
                        </button>
                        <button class="btn btn-sm btn-success save-description-btn" data-repair-id="${repair.id}" style="display: none;">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                        <button class="btn btn-sm btn-secondary cancel-description-btn" data-repair-id="${repair.id}" style="display: none;">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                    </div>
                </div>
                <div class="repair-info-item" style="display: block;">
                    <div class="info-value description-content" style="margin-top: 0; line-height: 1.6;">
                        ${repair.description_probleme ? repair.description_probleme.replace(/\n/g, '<br>') : '<span style="color: #6c757d; font-style: italic;">Aucune description du problème</span>'}
                    </div>
                    <textarea class="form-control description-textarea" rows="4" style="display: none; margin-top: 0;" placeholder="Décrire le problème rencontré...">${repair.description_probleme || ''}</textarea>
                </div>
            </div>
            
            <!-- Section Notes internes -->
            <div class="repair-section">
                <div class="repair-section-title">
                    <i class="fas fa-sticky-note"></i>
                    Notes internes
                    <div class="ms-auto">
                        <button class="btn btn-sm btn-outline-primary edit-internal-notes-btn" data-repair-id="${repair.id}" style="display: inline-block;">
                            <i class="fas fa-edit"></i>
                            Modifier
                        </button>
                        <button class="btn btn-sm btn-success save-internal-notes-btn" data-repair-id="${repair.id}" style="display: none;">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                        <button class="btn btn-sm btn-secondary cancel-internal-notes-btn" data-repair-id="${repair.id}" style="display: none;">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                    </div>
                </div>
                <div class="repair-info-item" style="display: block;">
                    <div class="info-value internal-notes" style="margin-top: 0; line-height: 1.6;">
                        ${repair.notes_finales ? repair.notes_finales.replace(/\n/g, '<br>') : '<span style="color: #6c757d; font-style: italic;">Aucune note interne</span>'}
                    </div>
                    <textarea class="form-control internal-notes-textarea" rows="4" style="display: none; margin-top: 0;" placeholder="Saisir les notes internes (visibles uniquement par l'équipe)...">${repair.notes_finales || ''}</textarea>
                </div>
            </div>
            
            <!-- Section Photos -->
            <div class="repair-section">
                <div class="repair-section-title">
                    <i class="fas fa-images"></i>
                    Photos (${repair.photos ? repair.photos.length : 0})
                </div>
                
                <style>
                    .repair-photos-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                        gap: 12px;
                        margin-top: 12px;
                    }
                    .repair-photo-item {
                        position: relative;
                        aspect-ratio: 1;
                        border-radius: 8px;
                        overflow: hidden;
                        cursor: pointer;
                        transition: transform 0.2s ease;
                    }
                    .repair-photo-item:hover {
                        transform: scale(1.05);
                    }
                    .repair-photo-item img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }
                    .photo-overlay {
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0,0,0,0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        opacity: 0;
                        transition: opacity 0.2s ease;
                        color: white;
                        font-size: 1.2rem;
                    }
                    .repair-photo-item:hover .photo-overlay {
                        opacity: 1;
                    }
                    .repair-photo-item.main-photo {
                        border: 2px solid #28a745;
                        position: relative;
                    }
                    .repair-photo-item.main-photo::before {
                        content: 'APPAREIL';
                        position: absolute;
                        top: 4px;
                        left: 4px;
                        background: #28a745;
                        color: white;
                        font-size: 10px;
                        font-weight: bold;
                        padding: 2px 6px;
                        border-radius: 4px;
                        z-index: 2;
                    }
                    body.dark-mode .repair-photo-item.main-photo {
                        border-color: #34d058;
                    }
                    body.dark-mode .repair-photo-item.main-photo::before {
                        background: #34d058;
                    }
                    .repair-add-photo {
                        aspect-ratio: 1;
                        border: 2px dashed #dee2e6;
                        border-radius: 8px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        color: #6c757d;
                    }
                    .repair-add-photo:hover {
                        border-color: #007bff;
                        color: #007bff;
                        background: rgba(0,123,255,0.05);
                    }
                    .repair-add-photo i {
                        font-size: 1.5rem;
                        margin-bottom: 4px;
                    }
                    .repair-add-photo span {
                        font-size: 0.8rem;
                        font-weight: 500;
                    }
                </style>
                
                <div class="repair-photos-grid">
                    ${repair.photos && repair.photos.length > 0 ?
                repair.photos.map(photo => `
                            <div class="repair-photo-item ${photo.is_main ? 'main-photo' : ''}" onclick="openPhotoViewerSafe('${photo.url}', '${(photo.description || 'Photo réparation').replace(/'/g, '&apos;')}')">
                                <img src="${photo.url}" alt="${photo.description || 'Photo réparation'}" loading="lazy">
                                <div class="photo-overlay">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            </div>
                        `).join('') : ''
            }
                    
                    <div class="repair-add-photo add-photo-btn" data-repair-id="${repair.id}">
                        <i class="fas fa-plus"></i>
                        <span>Ajouter</span>
                    </div>
                </div>
            </div>
        `;
    }

    attachModalEventListeners(repair) {
        // Actions principales
        document.querySelectorAll('.repair-action-btn[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const action = btn.getAttribute('data-action');
                const repairId = btn.getAttribute('data-repair-id');
                this.handleAction(action, repairId);
            });
        });

        // Bouton principal de réparation
        document.querySelectorAll('.repair-main-action').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const action = btn.getAttribute('data-action');
                const repairId = btn.getAttribute('data-repair-id');

                if (action === 'start') {
                    this.handleStartRepair(repairId);
                } else if (action === 'stop') {
                    this.handleStopRepair(repairId);
                }
            });
        });

        // Bouton SMS
        document.querySelectorAll('.send-sms-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const clientId = btn.getAttribute('data-client-id');
                const nom = btn.getAttribute('data-client-nom');
                const prenom = btn.getAttribute('data-client-prenom');
                const tel = btn.getAttribute('data-client-tel');

                if (typeof openSmsModal === 'function') {
                    openSmsModal(clientId, nom, prenom, tel);
                }
            });
        });

        // Bouton d'édition de la description
        document.querySelectorAll('.edit-description-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.enableDescriptionEdit();
            });
        });

        // Bouton de sauvegarde de la description
        document.querySelectorAll('.save-description-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const repairId = btn.getAttribute('data-repair-id');
                this.saveDescriptionInline(repairId);
            });
        });

        // Bouton d'annulation de la description
        document.querySelectorAll('.cancel-description-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.cancelDescriptionEdit();
            });
        });

        // Bouton d'édition des notes internes
        document.querySelectorAll('.edit-internal-notes-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.enableInternalNotesEdit();
            });
        });

        // Bouton de sauvegarde des notes internes
        document.querySelectorAll('.save-internal-notes-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const repairId = btn.getAttribute('data-repair-id');
                this.saveInternalNotesInline(repairId);
            });
        });

        // Bouton d'annulation des notes internes
        document.querySelectorAll('.cancel-internal-notes-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.cancelInternalNotesEdit();
            });
        });

        // Bouton d'ajout de photo
        document.querySelectorAll('.add-photo-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const repairId = btn.getAttribute('data-repair-id');
                this.openPhotoModal(repairId);
            });
        });
    }

    handleAction(action, repairId) {
        switch (action) {
            case 'devis':
                // console.log('🎯 Ouverture du modal de devis pour réparation', repairId);

                // Debug : vérifier les fonctions disponibles
                // console.log('🔍 Debug fonctions devis:', {
                ouvrirNouveauModalDevis: typeof window.ouvrirNouveauModalDevis,
                    ouvrirDevisClean: typeof window.ouvrirDevisClean,
                        ouvrirModalDevis: typeof window.ouvrirModalDevis,
                            devisCleanManager: typeof window.devisCleanManager
        });

        // Vérifier si le modal existe
        const devisModal = document.getElementById('devisModalClean');
        // console.log('🔍 Modal devisModalClean existe:', !!devisModal);

        if (typeof window.openDevisModalSafely === 'function') {
            // console.log('✅ Utilisation de openDevisModalSafely');
            // Fermer le modal actuel d'abord
            const currentModal = bootstrap.Modal.getInstance(this.modal);
            if (currentModal) {
                currentModal.hide();
            }

            // Attendre que le modal soit fermé puis ouvrir le modal de devis
            setTimeout(() => {
                window.openDevisModalSafely(repairId);
            }, 200);
        } else if (typeof window.ouvrirDevisClean === 'function') {
            // console.log('✅ Utilisation de ouvrirDevisClean');
            const currentModal = bootstrap.Modal.getInstance(this.modal);
            if (currentModal) {
                currentModal.hide();
            }
            setTimeout(() => {
                window.ouvrirDevisClean(repairId);
            }, 200);
        } else if (typeof window.ouvrirNouveauModalDevis === 'function') {
            // console.log('✅ Utilisation de ouvrirNouveauModalDevis');
            const currentModal = bootstrap.Modal.getInstance(this.modal);
            if (currentModal) {
                currentModal.hide();
            }
            setTimeout(() => {
                window.ouvrirNouveauModalDevis(repairId);
            }, 200);
        } else if (typeof window.ouvrirModalDevis === 'function') {
            // console.log('✅ Utilisation de ouvrirModalDevis');
            const currentModal = bootstrap.Modal.getInstance(this.modal);
            if (currentModal) {
                currentModal.hide();
            }
            setTimeout(() => {
                window.ouvrirModalDevis(repairId);
            }, 200);
        } else {
            console.error('❌ Aucune fonction de devis disponible');
            // console.log('🔍 Fonctions window disponibles:', Object.keys(window).filter(key => key.toLowerCase().includes('devis')));
            alert('Erreur: Le système de devis n\'est pas disponible');
        }
        break;

            case 'status':
        // console.log('🎯 Ouverture du modal de statut pour réparation', repairId);
        if (typeof window.openStatusModal === 'function') {
            window.openStatusModal(repairId);
        } else if (typeof openStatusModal === 'function') {
            openStatusModal(repairId);
        } else {
            console.error('❌ Fonction de statut non disponible');
        }
        break;

            case 'price':
        // console.log('🎯 Ouverture du modal de prix pour réparation', repairId);
        if (window.priceModal && typeof window.priceModal.show === 'function') {
            // Récupérer le prix actuel depuis le modal de détails
            const priceElement = document.querySelector('.price-value');
            const currentPrice = priceElement ? priceElement.textContent.replace(' €', '').replace('€', '') : '0';
            window.priceModal.show(repairId, currentPrice);
        } else if (typeof window.openPriceModal === 'function') {
            window.openPriceModal(repairId);
        } else if (typeof openPriceModal === 'function') {
            openPriceModal(repairId);
        } else {
            console.error('❌ Fonction de prix non disponible');
            // console.log('🔍 Fonctions disponibles:', {
            priceModal: typeof window.priceModal,
                priceModalShow: window.priceModal ? typeof window.priceModal.show : 'undefined',
                    openPriceModal: typeof window.openPriceModal
        });
    }
    break;

            case 'order':
        // console.log('🎯 Ouverture du modal de commande pour réparation', repairId);

        // Fermer le modal de détails d'abord
        const repairModal = document.getElementById('repairDetailsModal');
    if(repairModal && repairModal.classList.contains('show')) {
    const repairModalInstance = bootstrap.Modal.getInstance(repairModal);
    if (repairModalInstance) {
        repairModalInstance.hide();
        // console.log('🔄 Modal de détails fermé');
    }
}

// Attendre un peu puis ouvrir le modal de commande
setTimeout(() => {
    const commandeModal = document.getElementById('ajouterCommandeModal');
    if (commandeModal) {
        try {
            const bootstrapModal = new bootstrap.Modal(commandeModal, {
                backdrop: true,
                keyboard: true
            });

            // Forcer les pointer-events et initialiser les fonctionnalités après l'ouverture
            commandeModal.addEventListener('shown.bs.modal', function () {
                // Forcer les interactions sur le modal
                commandeModal.style.pointerEvents = 'auto';
                const dialog = commandeModal.querySelector('.modal-dialog');
                const content = commandeModal.querySelector('.modal-content');
                if (dialog) dialog.style.pointerEvents = 'auto';
                if (content) content.style.pointerEvents = 'auto';

                // Désactiver les pointer-events sur tous les backdrops
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                    backdrop.style.pointerEvents = 'none';
                });

                // Stocker l'ID de réparation pour la soumission du formulaire
                window.currentRepairId = repairId;
                // console.log('🔗 ID de réparation défini:', repairId);

                // Initialiser les fonctionnalités du modal de commande
                initializeCommandeModalFunctions();

                // console.log('🔧 Pointer-events forcés et fonctionnalités initialisées sur le modal de commande');
            }, { once: true });

            bootstrapModal.show();
            // console.log('✅ Modal ajouterCommandeModal ouvert');
        } catch (error) {
            console.error('❌ Erreur ouverture modal commande:', error);
        }
    } else if (typeof window.openCommandeModal === 'function') {
        window.openCommandeModal(repairId);
    } else if (typeof openCommandeModal === 'function') {
        openCommandeModal(repairId);
    } else {
        console.error('❌ Fonction de commande non disponible');
        // console.log('🔍 Modal ajouterCommandeModal trouvé:', !!commandeModal);
        // console.log('🔍 Fonctions disponibles:', {
        openCommandeModal: typeof window.openCommandeModal,
            bootstrap: typeof bootstrap
    });
                    }
                }, 300);
break;

            case 'print':
window.open(`https://${window.location.host}/index.php?page=imprimer_etiquette&id=${repairId}`, '_blank');
break;
        }
    }

handleStartRepair(repairId) {
    // console.log('🎯 Démarrage de la réparation', repairId);
    if (typeof window.startRepairAction === 'function') {
        window.startRepairAction(repairId);
    } else if (typeof startRepairAction === 'function') {
        startRepairAction(repairId);
    } else {
        console.error('❌ Fonction de démarrage de réparation non disponible');
        alert('Erreur: Impossible de démarrer la réparation');
    }
}

handleStopRepair(repairId) {
    // console.log('🎯 Arrêt de la réparation', repairId);
    if (typeof window.stopRepairAction === 'function') {
        window.stopRepairAction(repairId);
    } else if (typeof stopRepairAction === 'function') {
        stopRepairAction(repairId);
    } else {
        console.error('❌ Fonction d\'arrêt de réparation non disponible');
        alert('Erreur: Impossible d\'arrêter la réparation');
    }
}

openNotesModal(repairId, currentNotes) {
    // Créer un modal simple pour l'édition des notes
    const modalHtml = `
            <div class="modal fade" id="notesModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Notes techniques</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <textarea class="form-control" id="notesTextarea" rows="6" placeholder="Saisir les notes techniques...">${currentNotes}</textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" onclick="window.modernRepairModal.saveNotes(${repairId})">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Supprimer l'ancien modal s'il existe
    const existingModal = document.getElementById('notesModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Ajouter le nouveau modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('notesModal'));
    modal.show();

    // Supprimer le modal après fermeture
    document.getElementById('notesModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

    async saveNotes(repairId) {
    const textarea = document.getElementById('notesTextarea');
    const notes = textarea.value.trim();

    try {
        const response = await fetch('ajax/update_repair_internal_notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                repair_id: repairId,
                notes: notes
            })
        });

        const result = await response.json();

        if (result.success) {
            // Fermer le modal des notes
            const notesModal = bootstrap.Modal.getInstance(document.getElementById('notesModal'));
            notesModal.hide();

            // Recharger le modal principal
            this.openModal(repairId);

            // Afficher un message de succès
            if (typeof showToast === 'function') {
                showToast('Notes techniques mises à jour avec succès', 'success');
            }
        } else {
            alert('Erreur lors de la sauvegarde: ' + (result.error || 'Erreur inconnue'));
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde des notes:', error);
        alert('Erreur de connexion lors de la sauvegarde');
    }
}

enableDescriptionEdit() {
    const descriptionContent = document.querySelector('.description-content');
    const descriptionTextarea = document.querySelector('.description-textarea');
    const editBtn = document.querySelector('.edit-description-btn');
    const saveBtn = document.querySelector('.save-description-btn');
    const cancelBtn = document.querySelector('.cancel-description-btn');

    if (descriptionContent && descriptionTextarea) {
        // Récupérer le texte sans les balises HTML
        const currentText = descriptionContent.textContent.trim();
        const cleanText = currentText === 'Aucune description du problème' ? '' : currentText;

        // Stocker la valeur originale pour l'annulation
        descriptionTextarea.setAttribute('data-original-value', cleanText);
        descriptionTextarea.value = cleanText;

        // Basculer l'affichage
        descriptionContent.style.display = 'none';
        descriptionTextarea.style.display = 'block';
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'inline-block';

        // Focus sur le textarea
        descriptionTextarea.focus();
    }
}

cancelDescriptionEdit() {
    const descriptionContent = document.querySelector('.description-content');
    const descriptionTextarea = document.querySelector('.description-textarea');
    const editBtn = document.querySelector('.edit-description-btn');
    const saveBtn = document.querySelector('.save-description-btn');
    const cancelBtn = document.querySelector('.cancel-description-btn');

    if (descriptionContent && descriptionTextarea) {
        // Restaurer la valeur originale
        const originalValue = descriptionTextarea.getAttribute('data-original-value') || '';
        descriptionTextarea.value = originalValue;

        // Basculer l'affichage
        descriptionContent.style.display = 'block';
        descriptionTextarea.style.display = 'none';
        editBtn.style.display = 'inline-block';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    }
}

    async saveDescriptionInline(repairId) {
    const descriptionTextarea = document.querySelector('.description-textarea');
    const description = descriptionTextarea.value.trim();

    try {
        const response = await fetch('ajax/update_repair_description.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                repair_id: repairId,
                description: description
            })
        });

        const result = await response.json();

        if (result.success) {
            // Mettre à jour l'affichage
            const descriptionContent = document.querySelector('.description-content');
            if (description) {
                descriptionContent.innerHTML = description.replace(/\n/g, '<br>');
            } else {
                descriptionContent.innerHTML = '<span style="color: #6c757d; font-style: italic;">Aucune description du problème</span>';
            }

            // Revenir en mode lecture
            this.cancelDescriptionEdit();

            // Afficher un message de succès
            if (typeof showToast === 'function') {
                showToast('Description du problème mise à jour avec succès', 'success');
            }
        } else {
            alert('Erreur lors de la sauvegarde: ' + (result.error || 'Erreur inconnue'));
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde de la description:', error);
        alert('Erreur de connexion lors de la sauvegarde');
    }
}

enableInternalNotesEdit() {
    const notesContent = document.querySelector('.internal-notes');
    const notesTextarea = document.querySelector('.internal-notes-textarea');
    const editBtn = document.querySelector('.edit-internal-notes-btn');
    const saveBtn = document.querySelector('.save-internal-notes-btn');
    const cancelBtn = document.querySelector('.cancel-internal-notes-btn');

    if (notesContent && notesTextarea) {
        // Récupérer le texte sans les balises HTML
        const currentText = notesContent.textContent.trim();
        const cleanText = currentText === 'Aucune note interne' ? '' : currentText;

        // Stocker la valeur originale pour l'annulation
        notesTextarea.setAttribute('data-original-value', cleanText);
        notesTextarea.value = cleanText;

        // Basculer l'affichage
        notesContent.style.display = 'none';
        notesTextarea.style.display = 'block';
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'inline-block';

        // Focus sur le textarea
        notesTextarea.focus();
    }
}

cancelInternalNotesEdit() {
    const notesContent = document.querySelector('.internal-notes');
    const notesTextarea = document.querySelector('.internal-notes-textarea');
    const editBtn = document.querySelector('.edit-internal-notes-btn');
    const saveBtn = document.querySelector('.save-internal-notes-btn');
    const cancelBtn = document.querySelector('.cancel-internal-notes-btn');

    if (notesContent && notesTextarea) {
        // Restaurer la valeur originale
        const originalValue = notesTextarea.getAttribute('data-original-value') || '';
        notesTextarea.value = originalValue;

        // Basculer l'affichage
        notesContent.style.display = 'block';
        notesTextarea.style.display = 'none';
        editBtn.style.display = 'inline-block';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    }
}

    async saveInternalNotesInline(repairId) {
    const notesTextarea = document.querySelector('.internal-notes-textarea');
    const notes = notesTextarea.value.trim();

    try {
        const response = await fetch('ajax/update_repair_internal_notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                repair_id: repairId,
                notes: notes
            })
        });

        const result = await response.json();

        if (result.success) {
            // Mettre à jour l'affichage
            const notesContent = document.querySelector('.internal-notes');
            if (notes) {
                notesContent.innerHTML = notes.replace(/\n/g, '<br>');
            } else {
                notesContent.innerHTML = '<span style="color: #6c757d; font-style: italic;">Aucune note interne</span>';
            }

            // Revenir en mode lecture
            this.cancelInternalNotesEdit();

            // Afficher un message de succès
            if (typeof showToast === 'function') {
                showToast('Notes internes mises à jour avec succès', 'success');
            }
        } else {
            alert('Erreur lors de la sauvegarde: ' + (result.error || 'Erreur inconnue'));
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde des notes internes:', error);
        alert('Erreur de connexion lors de la sauvegarde');
    }
}

openPhotoModal(repairId) {
    // Créer un modal simple pour l'upload de photo
    const modalHtml = `
            <div class="modal fade" id="photoUploadModal" tabindex="-1" style="z-index: 25000 !important;">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-camera me-2"></i>
                                Ajouter une photo
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <style>
                            /* Styles pour le modal photo en mode nuit */
                            body.dark-mode #photoUploadModal .modal-content {
                                background-color: #1e293b !important;
                                border: 1px solid #334155 !important;
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoUploadModal .modal-header {
                                background-color: #0f172a !important;
                                border-bottom: 1px solid #334155 !important;
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoUploadModal .modal-title {
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoUploadModal .btn-close {
                                filter: invert(1) !important;
                            }
                            body.dark-mode #photoUploadModal .modal-body {
                                background-color: #1e293b !important;
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoUploadModal .modal-footer {
                                background-color: #1e293b !important;
                                border-top: 1px solid #334155 !important;
                            }
                            body.dark-mode #photoUploadModal .form-label {
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoUploadModal .form-control {
                                background-color: #334155 !important;
                                border: 1px solid #475569 !important;
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoUploadModal .form-control:focus {
                                background-color: #334155 !important;
                                border-color: #3b82f6 !important;
                                box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoUploadModal .form-control::placeholder {
                                color: #94a3b8 !important;
                            }
                            body.dark-mode #photoUploadModal #cameraPlaceholder {
                                background: #334155 !important;
                                border-color: #475569 !important;
                                color: #94a3b8 !important;
                            }
                            body.dark-mode #photoUploadModal #cameraVideo {
                                background: #334155 !important;
                            }
                            body.dark-mode #photoUploadModal .btn-primary {
                                background-color: #3b82f6 !important;
                                border-color: #3b82f6 !important;
                            }
                            body.dark-mode #photoUploadModal .btn-primary:hover {
                                background-color: #2563eb !important;
                                border-color: #2563eb !important;
                            }
                            body.dark-mode #photoUploadModal .btn-success {
                                background-color: #10b981 !important;
                                border-color: #10b981 !important;
                            }
                            body.dark-mode #photoUploadModal .btn-success:hover {
                                background-color: #059669 !important;
                                border-color: #059669 !important;
                            }
                            body.dark-mode #photoUploadModal .btn-secondary {
                                background-color: #6b7280 !important;
                                border-color: #6b7280 !important;
                                color: #f9fafb !important;
                            }
                            body.dark-mode #photoUploadModal .btn-secondary:hover {
                                background-color: #4b5563 !important;
                                border-color: #4b5563 !important;
                            }
                        </style>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <video id="cameraVideo" autoplay playsinline style="width: 100%; max-width: 400px; height: 300px; background: #f8f9fa; border-radius: 8px; display: none;"></video>
                                <canvas id="cameraCanvas" style="display: none;"></canvas>
                                <div id="photoPreview" style="display: none;">
                                    <img id="previewImage" style="width: 100%; max-width: 400px; height: 300px; object-fit: cover; border-radius: 8px;">
                                </div>
                                <div id="cameraPlaceholder" class="d-flex align-items-center justify-content-center" style="width: 100%; max-width: 400px; height: 300px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px;">
                                    <div class="text-center">
                                        <i class="fas fa-camera fa-3x text-muted mb-2"></i>
                                        <p class="text-muted">Cliquez pour démarrer la caméra</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="photoDescription" class="form-label">Description (optionnelle)</label>
                                <input type="text" class="form-control" id="photoDescription" placeholder="Description de la photo">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" id="startCameraBtn">
                                <i class="fas fa-video me-1"></i> Démarrer caméra
                            </button>
                            <button type="button" class="btn btn-success" id="capturePhotoBtn" style="display: none;">
                                <i class="fas fa-camera me-1"></i> Prendre photo
                            </button>
                            <button type="button" class="btn btn-success" id="savePhotoBtn" style="display: none;">
                                <i class="fas fa-save me-1"></i> Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Supprimer l'ancien modal s'il existe
    const existingModal = document.getElementById('photoUploadModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Ajouter le nouveau modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Initialiser les événements du modal photo
    this.initPhotoModalEvents(repairId);

    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('photoUploadModal'));
    modal.show();

    // Ajuster le z-index du backdrop
    setTimeout(() => {
        const backdrop = document.querySelector('.modal-backdrop:last-child');
        if (backdrop) {
            backdrop.style.setProperty('z-index', '24999', 'important');
        }
    }, 100);

    // Supprimer le modal après fermeture
    document.getElementById('photoUploadModal').addEventListener('hidden.bs.modal', function () {
        // Arrêter la caméra si elle est active
        const video = document.getElementById('cameraVideo');
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
        }
        this.remove();
    });
}

initPhotoModalEvents(repairId) {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');
    const preview = document.getElementById('photoPreview');
    const previewImage = document.getElementById('previewImage');
    const placeholder = document.getElementById('cameraPlaceholder');
    const startBtn = document.getElementById('startCameraBtn');
    const captureBtn = document.getElementById('capturePhotoBtn');
    const saveBtn = document.getElementById('savePhotoBtn');

    let stream = null;
    let capturedPhoto = null;

    // Référence à l'instance pour les callbacks
    const modalInstance = this;

    // Démarrer la caméra
    startBtn.addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
            startBtn.style.display = 'none';
            captureBtn.style.display = 'inline-block';
        } catch (error) {
            console.error('Erreur caméra:', error);
            alert('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
        }
    });

    // Prendre une photo
    captureBtn.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        capturedPhoto = canvas.toDataURL('image/jpeg', 0.8);
        previewImage.src = capturedPhoto;

        video.style.display = 'none';
        preview.style.display = 'block';
        captureBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
    });

    // Sauvegarder la photo
    saveBtn.addEventListener('click', async () => {
        if (!capturedPhoto) return;

        const description = document.getElementById('photoDescription').value.trim();

        try {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...';

            const response = await fetch('ajax/upload_repair_photo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    repair_id: repairId,
                    photo: capturedPhoto,
                    description: description
                })
            });

            const result = await response.json();

            if (result.success) {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('photoUploadModal'));
                modal.hide();

                // Attendre que le modal se ferme puis recharger le modal principal
                setTimeout(() => {
                    // console.log('🔄 Rechargement du modal pour afficher les nouvelles photos, repairId:', repairId);
                    modalInstance.openModal(repairId);
                }, 300);

                if (typeof showToast === 'function') {
                    showToast('Photo ajoutée avec succès', 'success');
                }
            } else {
                alert('Erreur lors de l\'upload: ' + (result.message || 'Erreur inconnue'));
            }
        } catch (error) {
            console.error('Erreur upload photo:', error);
            alert('Erreur de connexion lors de l\'upload');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
        }
    });
}

    // Fonction globale pour visualiser les photos
    static openPhotoViewer(photoUrl, description) {
    // console.log('🖼️ Ouverture photo viewer:', { photoUrl, description });
    const modalHtml = `
            <div class="modal fade" id="photoViewerModal" tabindex="-1" style="z-index: 25100 !important;">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-image me-2"></i>
                                ${description}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <style>
                            /* Styles pour le modal visualiseur photo en mode nuit */
                            body.dark-mode #photoViewerModal .modal-content {
                                background-color: #1e293b !important;
                                border: 1px solid #334155 !important;
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoViewerModal .modal-header {
                                background-color: #0f172a !important;
                                border-bottom: 1px solid #334155 !important;
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoViewerModal .modal-title {
                                color: #e2e8f0 !important;
                            }
                            body.dark-mode #photoViewerModal .btn-close {
                                filter: invert(1) !important;
                            }
                            body.dark-mode #photoViewerModal .modal-body {
                                background-color: #1e293b !important;
                                color: #e2e8f0 !important;
                            }
                        </style>
                        <div class="modal-body text-center p-0">
                            <img src="${photoUrl}" alt="${description}" style="width: 100%; height: auto; max-height: 70vh; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Supprimer l'ancien modal s'il existe
    const existingModal = document.getElementById('photoViewerModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Ajouter le nouveau modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('photoViewerModal'));
    modal.show();

    // Ajuster le z-index du backdrop
    setTimeout(() => {
        const backdrop = document.querySelector('.modal-backdrop:last-child');
        if (backdrop) {
            backdrop.style.setProperty('z-index', '25099', 'important');
        }
    }, 100);

    // Supprimer le modal après fermeture
    document.getElementById('photoViewerModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

getStatusColor(statusName) {
    const statusColors = {
        'Nouvelle': '#3b82f6',
        'En cours': '#f59e0b',
        'En attente': '#ef4444',
        'Terminée': '#10b981',
        'Livrée': '#8b5cf6',
        'Annulée': '#6b7280'
    };

    return statusColors[statusName] || '#6b7280';
}

formatDate(dateString) {
    if (!dateString) return 'Non définie';

    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
}

// Fonction globale sécurisée pour ouvrir le visualiseur de photos
window.openPhotoViewerSafe = function (photoUrl, description) {
    // console.log('🖼️ Ouverture photo viewer sécurisée:', { photoUrl, description });

    // Nettoyer la description
    const cleanDescription = description.replace(/&apos;/g, "'");

    // Créer le modal directement dans le DOM
    const existingModal = document.getElementById('photoViewerModal');
    if (existingModal) {
        existingModal.remove();
    }

    const modalHtml = `
        <div class="modal fade" id="photoViewerModal" tabindex="-1" style="z-index: 25100 !important;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-image me-2"></i>
                            ${cleanDescription}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-0">
                        <img src="${photoUrl}" alt="${cleanDescription}" style="width: 100%; height: auto; max-height: 70vh; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('photoViewerModal'));
    modal.show();

    // Nettoyer après fermeture
    document.getElementById('photoViewerModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
};

// Initialiser le modal moderne
window.modernRepairModal = new ModernRepairModal();

// Fonction globale pour ouvrir le modal (compatibilité)
window.openRepairDetailsModal = function (repairId) {
    window.modernRepairModal.openModal(repairId);
};

// Fonction pour ouvrir une photo en grand
window.openPhotoModal = function (photoPath) {
    const modalHtml = `
        <div class="modal fade" id="photoViewModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Photo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-0">
                        <img src="${photoPath}" class="img-fluid" alt="Photo réparation">
                    </div>
                </div>
            </div>
        </div>
    `;

    // Supprimer l'ancien modal s'il existe
    const existingModal = document.getElementById('photoViewModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Ajouter le nouveau modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('photoViewModal'));
    modal.show();

    // Supprimer le modal après fermeture
    document.getElementById('photoViewModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
};

// Fonction pour initialiser les fonctionnalités du modal de commande
function initializeCommandeModalFunctions() {
    // console.log('🔧 Initialisation des fonctionnalités du modal de commande...');

    // Initialiser la recherche client
    initializeClientSearch();

    // Initialiser le bouton ajouter pièce
    initializeAddPieceButton();

    // Initialiser le bouton nouveau client
    initializeNewClientButton();

    // Initialiser les fournisseurs
    initializeSuppliersDropdown();

    // Initialiser la soumission du formulaire
    initializeCommandeFormSubmission();

    // console.log('✅ Fonctionnalités du modal de commande initialisées');
}

// Fonction pour initialiser la recherche client
function initializeClientSearch() {
    const clientSearchInput = document.getElementById('nom_client_selectionne');
    const resultatsDiv = document.getElementById('resultats_recherche_client_inline');
    const listeClientsDiv = document.getElementById('liste_clients_recherche_inline');

    if (!clientSearchInput || !resultatsDiv || !listeClientsDiv) {
        console.warn('⚠️ Éléments de recherche client manquants:', {
            clientSearchInput: !!clientSearchInput,
            resultatsDiv: !!resultatsDiv,
            listeClientsDiv: !!listeClientsDiv
        });
        return;
    }

    // Supprimer les anciens événements
    const newInput = clientSearchInput.cloneNode(true);
    clientSearchInput.parentNode.replaceChild(newInput, clientSearchInput);

    let searchTimeout;

    newInput.addEventListener('input', function () {
        const query = this.value.trim();

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            resultatsDiv.classList.add('d-none');
            return;
        }

        searchTimeout = setTimeout(() => {
            // console.log('🔍 Recherche client:', query);

            fetch('ajax/recherche_clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: `terme=${encodeURIComponent(query)}`
            })
                .then(response => response.json())
                .then(data => {
                    // console.log('📋 Résultats recherche client:', data);

                    listeClientsDiv.innerHTML = '';

                    if (data.success && Array.isArray(data.clients) && data.clients.length > 0) {
                        data.clients.forEach(client => {
                            const item = document.createElement('div');
                            item.className = 'list-group-item list-group-item-action client-item';
                            item.style.cursor = 'pointer';
                            item.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">${client.nom} ${client.prenom || ''}</div>
                                    <div class="text-muted small">${client.telephone || 'Pas de téléphone'}</div>
                                </div>
                            </div>
                        `;

                            item.addEventListener('click', () => {
                                // Sélectionner le client
                                const clientIdInput = document.getElementById('client_id');
                                if (clientIdInput) clientIdInput.value = client.id;
                                newInput.value = `${client.nom} ${client.prenom || ''}`;

                                // Afficher les infos du client sélectionné
                                const clientSelectionne = document.getElementById('client_selectionne');
                                if (clientSelectionne) {
                                    const nomElement = clientSelectionne.querySelector('.nom_client');
                                    const telElement = clientSelectionne.querySelector('.tel_client');
                                    if (nomElement) nomElement.textContent = `${client.nom} ${client.prenom || ''}`;
                                    if (telElement) telElement.textContent = client.telephone || 'Pas de téléphone';
                                    clientSelectionne.classList.remove('d-none');
                                }

                                // Masquer les résultats
                                resultatsDiv.classList.add('d-none');

                                // console.log('✅ Client sélectionné:', client);
                            });

                            listeClientsDiv.appendChild(item);
                        });

                        resultatsDiv.classList.remove('d-none');
                    } else {
                        resultatsDiv.classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('❌ Erreur recherche client:', error);
                    resultatsDiv.classList.add('d-none');
                });
        }, 300);
    });

    // console.log('✅ Recherche client initialisée');
}

// Fonction pour initialiser le bouton ajouter pièce
function initializeAddPieceButton() {
    const ajouterPieceBtn = document.getElementById('ajouter-piece-btn');

    if (!ajouterPieceBtn) {
        console.warn('⚠️ Bouton ajouter pièce manquant');
        return;
    }

    // Supprimer les anciens événements
    const newBtn = ajouterPieceBtn.cloneNode(true);
    ajouterPieceBtn.parentNode.replaceChild(newBtn, ajouterPieceBtn);

    newBtn.addEventListener('click', function (e) {
        e.preventDefault();
        // console.log('🔧 Ajout d\'une nouvelle pièce');

        // Logique pour ajouter une pièce
        ajouterNouvellePiece();
    });

    // console.log('✅ Bouton ajouter pièce initialisé');
}

// Fonction pour ajouter une nouvelle pièce
function ajouterNouvellePiece() {
    // Dans ce modal, on duplique la section pièce existante
    const orderSection = document.querySelector('.order-section:has(#nom_piece)');

    if (!orderSection) {
        console.error('❌ Section pièce non trouvée');
        return;
    }

    // Créer une nouvelle section pièce
    const newSection = orderSection.cloneNode(true);

    // Générer un index unique
    const pieceIndex = document.querySelectorAll('.order-section:has([name*="nom_piece"])').length;

    // Mettre à jour les IDs et noms des champs
    const inputs = newSection.querySelectorAll('input, select');
    inputs.forEach(input => {
        if (input.name) {
            // Transformer les noms en array pour supporter plusieurs pièces
            if (input.name === 'nom_piece') input.name = `pieces[${pieceIndex}][nom]`;
            if (input.name === 'code_barre') input.name = `pieces[${pieceIndex}][code_barre]`;
            if (input.name === 'quantite') input.name = `pieces[${pieceIndex}][quantite]`;
            if (input.name === 'prix_estime') input.name = `pieces[${pieceIndex}][prix]`;
        }

        if (input.id) {
            input.id = input.id + '_' + pieceIndex;
        }

        // Vider les valeurs sauf quantité
        if (input.name !== `pieces[${pieceIndex}][quantite]`) {
            input.value = '';
        }
    });

    // Ajouter un bouton de suppression
    const titleDiv = newSection.querySelector('.order-section-title');
    if (titleDiv) {
        titleDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center w-100">
                <div><i class="fas fa-cog"></i> Pièce commandée #${pieceIndex + 1}</div>
                <button type="button" class="btn btn-outline-danger btn-sm supprimer-piece-btn">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        `;
    }

    // Insérer la nouvelle section après la dernière section pièce
    const lastPieceSection = document.querySelector('.order-section:has([name*="nom_piece"]):last-of-type') || orderSection;
    lastPieceSection.insertAdjacentElement('afterend', newSection);

    // Ajouter l'événement de suppression
    const supprimerBtn = newSection.querySelector('.supprimer-piece-btn');
    if (supprimerBtn) {
        supprimerBtn.addEventListener('click', function () {
            newSection.remove();
            // console.log('🗑️ Pièce supprimée');
            // Renuméroter les pièces restantes
            renumerPieces();
        });
    }

    // console.log('✅ Nouvelle pièce ajoutée');
}

// Fonction pour renuméroter les pièces
function renumerPieces() {
    const pieceSections = document.querySelectorAll('.order-section:has([name*="nom_piece"])');
    pieceSections.forEach((section, index) => {
        const title = section.querySelector('.order-section-title');
        if (title && index > 0) { // Ne pas modifier la première pièce
            title.querySelector('div').innerHTML = `<i class="fas fa-cog"></i> Pièce commandée #${index + 1}`;
        }
    });
}

// Fonction pour initialiser le bouton nouveau client
function initializeNewClientButton() {
    const newClientBtn = document.getElementById('newClientBtn');

    if (!newClientBtn) {
        console.warn('⚠️ Bouton nouveau client manquant');
        return;
    }

    // Supprimer les anciens événements
    const newBtn = newClientBtn.cloneNode(true);
    newClientBtn.parentNode.replaceChild(newBtn, newClientBtn);

    newBtn.addEventListener('click', function (e) {
        e.preventDefault();
        // console.log('👤 Ouverture modal nouveau client');

        const nouveauClientModal = document.getElementById('nouveauClientModal_commande');
        if (nouveauClientModal) {
            const modal = new bootstrap.Modal(nouveauClientModal);
            modal.show();
        } else {
            console.error('❌ Modal nouveau client non trouvé');
        }
    });

    // console.log('✅ Bouton nouveau client initialisé');
}

// Fonction pour initialiser les fournisseurs
function initializeSuppliersDropdown() {
    const fournisseurSelect = document.getElementById('fournisseur_id_ajout');

    if (!fournisseurSelect) {
        console.warn('⚠️ Select fournisseur manquant');
        return;
    }

    // Charger les fournisseurs si nécessaire
    if (fournisseurSelect.options.length <= 1) {
        // console.log('📦 Chargement des fournisseurs...');

        fetch('ajax/get_fournisseurs.php')
            .then(response => response.json())
            .then(data => {
                // console.log('📦 Réponse fournisseurs:', data);

                if (data.success && Array.isArray(data.fournisseurs)) {
                    fournisseurSelect.innerHTML = '<option value="">Sélectionner un fournisseur...</option>';

                    data.fournisseurs.forEach(fournisseur => {
                        const option = document.createElement('option');
                        option.value = fournisseur.id;
                        option.textContent = fournisseur.nom;
                        fournisseurSelect.appendChild(option);
                    });

                    // console.log('✅ Fournisseurs chargés:', data.fournisseurs.length);
                } else {
                    console.warn('⚠️ Aucun fournisseur trouvé ou erreur:', data);
                }
            })
            .catch(error => {
                console.error('❌ Erreur chargement fournisseurs:', error);
            });
    } else {
        // console.log('✅ Fournisseurs déjà chargés');
    }
}

// Fonction pour initialiser la soumission du formulaire de commande
function initializeCommandeFormSubmission() {
    const form = document.getElementById('ajouterCommandeForm');
    const saveBtn = document.getElementById('saveCommandeBtn');

    if (!form || !saveBtn) {
        console.warn('⚠️ Formulaire ou bouton de sauvegarde manquant');
        return;
    }

    // Supprimer les anciens événements
    const newSaveBtn = saveBtn.cloneNode(true);
    saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);

    newSaveBtn.addEventListener('click', function (e) {
        e.preventDefault();
        // console.log('💾 Soumission du formulaire de commande');

        // Valider et soumettre le formulaire
        submitCommandeForm(form);
    });

    // console.log('✅ Soumission du formulaire initialisée');
}

// Fonction pour soumettre le formulaire de commande
function submitCommandeForm(form) {
    const formData = new FormData();

    // Collecter les données du formulaire principal
    const clientId = document.getElementById('client_id')?.value;
    const fournisseurId = document.getElementById('fournisseur_id_ajout')?.value;
    const nomPiece = document.getElementById('nom_piece')?.value;
    const codeBarre = document.getElementById('code_barre')?.value;
    const quantite = document.getElementById('quantite')?.value;
    const prixEstime = document.getElementById('prix_estime')?.value;
    const statut = document.querySelector('input[name="statut"]:checked')?.value;

    // Validation côté client
    const errors = [];

    if (!clientId) {
        errors.push('Veuillez sélectionner un client');
    }

    if (!fournisseurId) {
        errors.push('Veuillez sélectionner un fournisseur');
    }

    if (!nomPiece || nomPiece.trim() === '') {
        errors.push('Veuillez saisir le nom de la pièce');
    }

    if (!quantite || isNaN(quantite) || parseFloat(quantite) <= 0) {
        errors.push('Veuillez saisir une quantité valide');
    }

    if (prixEstime && (isNaN(prixEstime) || parseFloat(prixEstime) < 0)) {
        errors.push('Le prix estimé doit être un nombre positif');
    }

    if (errors.length > 0) {
        alert('Erreurs de validation :\n' + errors.join('\n'));
        return;
    }

    // Préparer les données
    formData.append('client_id', clientId);
    formData.append('fournisseur_id', fournisseurId);
    formData.append('nom_piece', nomPiece.trim());
    if (codeBarre) formData.append('code_barre', codeBarre.trim());
    formData.append('quantite', quantite);
    if (prixEstime) formData.append('prix_estime', prixEstime);
    formData.append('statut', statut || 'en_attente');

    // Ajouter l'ID de réparation si disponible (depuis le contexte global)
    if (window.currentRepairId) {
        formData.append('reparation_id', window.currentRepairId);
    }

    // Collecter les pièces supplémentaires (si ajoutées)
    const piecesSupplementaires = document.querySelectorAll('.order-section:has([name*="pieces["])');
    if (piecesSupplementaires.length > 0) {
        // console.log('📦 Pièces supplémentaires détectées:', piecesSupplementaires.length);

        piecesSupplementaires.forEach((section, index) => {
            const inputs = section.querySelectorAll('input');
            inputs.forEach(input => {
                if (input.name && input.value) {
                    formData.append(input.name, input.value);
                }
            });
        });
    }

    // Récupérer le bouton de sauvegarde
    const saveBtn = document.getElementById('saveCommandeBtn');

    if (!saveBtn) {
        console.error('❌ Bouton de sauvegarde non trouvé');
        return;
    }

    // Afficher un indicateur de chargement
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';

    // console.log('📤 Envoi des données de commande...');

    // Envoyer la requête
    fetch('ajax/add_commande.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            // console.log('📥 Réponse du serveur:', data);

            if (data.success) {
                alert('✅ Commande enregistrée avec succès !');

                // Fermer le modal
                const modal = document.getElementById('ajouterCommandeModal');
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }

                // Réinitialiser le formulaire
                form.reset();

                // Masquer les infos client sélectionné
                const clientSelectionne = document.getElementById('client_selectionne');
                if (clientSelectionne) {
                    clientSelectionne.classList.add('d-none');
                }

                // Supprimer les pièces supplémentaires
                document.querySelectorAll('.order-section:has(.supprimer-piece-btn)').forEach(section => {
                    section.remove();
                });

            } else {
                alert('❌ Erreur lors de l\'enregistrement :\n' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors de l\'envoi:', error);
            alert('❌ Erreur de communication avec le serveur');
        })
        .finally(() => {
            // Restaurer le bouton
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
}

// console.log('✅ ModernRepairModal chargé');
