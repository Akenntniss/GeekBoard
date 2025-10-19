/**
 * Module de gestion du modal des réparations
 */
window.RepairModal = window.RepairModal || {
    // Éléments DOM
    elements: {
        modal: null,
        detailsContainer: null,
        loader: null
    },

    // Configuration
    config: {
        apiUrl: 'ajax/get_repair_details.php',
    },
    
    // Flag d'initialisation
    _isInitialized: false,

    /**
     * Initialise le module
     */
    init() {
        // Vérifier si déjà initialisé ET que les éléments sont bien présents
        if (this._isInitialized && this.elements.modal) {
            return;
        }
        
        // Forcer la réinitialisation si les éléments ne sont pas présents
        if (this._isInitialized && !this.elements.modal) {
            this._isInitialized = false;
        }
        
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
    initElements() {
        // Récupérer les éléments
        this.elements.modal = document.getElementById('repairDetailsModal');
        this.elements.detailsContainer = document.getElementById('repairDetailsContent');
        this.elements.loader = document.getElementById('repairDetailsLoader');
        
        if (!this.elements.modal || !this.elements.detailsContainer || !this.elements.loader) {
            // Réessayer après un délai (max 3 fois)
            if (!this._retryCount) this._retryCount = 0;
            if (this._retryCount < 3) {
                this._retryCount++;
                setTimeout(() => this.initElements(), 1000);
            }
            return;
        }
        
        // Ajouter les écouteurs d'événements pour les boutons de détails
        document.querySelectorAll('.view-repair-details').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const repairId = button.getAttribute('data-id');
                if (repairId) {
                    this.loadRepairDetails(repairId);
                }
            });
        });
        
        // Écouter les événements de clic sur les cartes réparation
        document.querySelectorAll('.repair-card, .draggable-card').forEach(card => {
            card.addEventListener('click', (e) => {
                // Ne pas déclencher si on clique sur un bouton
                if (e.target.closest('button, a')) return;
                
                const repairId = card.getAttribute('data-repair-id');
                if (repairId) {
                    this.loadRepairDetails(repairId);
                }
            });
        });
        
        // Initialiser les écouteurs pour les actions du modal
        this.initModalActions();
        
        // Marquer comme initialisé
        this._isInitialized = true;
    },

    /**
     * Charge les détails d'une réparation
     * @param {string} repairId - ID de la réparation
     */
    loadRepairDetails(repairId) {
        // Afficher le loader
        this.showLoader();
        
        // Vérifier si bootstrap est défini
        if (typeof bootstrap === 'undefined') {
            console.log('Bootstrap non défini, chargement dynamique...');
            // Créer un élément script pour charger bootstrap
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js';
            script.onload = () => {
                console.log('Bootstrap chargé avec succès');
                // Continuer avec l'ouverture du modal une fois Bootstrap chargé
                this.showModal(repairId);
            };
            script.onerror = () => {
                console.error('Erreur lors du chargement de Bootstrap');
                alert('Erreur lors du chargement des ressources nécessaires. Veuillez rafraîchir la page.');
            };
            document.head.appendChild(script);
        } else {
            // Bootstrap est déjà défini, ouvrir directement le modal
            this.showModal(repairId);
        }
    },

    /**
     * Affiche le modal et charge les détails de la réparation
     * @param {string} repairId - ID de la réparation
     */
    showModal(repairId) {
        // Méthode d'ouverture robuste - essayer Bootstrap d'abord, puis fallback direct
        
        // Vérifier que l'élément modal existe
        if (!this.elements.modal) {
            return;
        }
        
        // Nettoyer d'abord tout backdrop résiduel
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Méthode 1: Essayer Bootstrap standard
        try {
            const modalInstance = new bootstrap.Modal(this.elements.modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modalInstance.show();
        } catch (err) {
            
            // Méthode 2: Fallback direct avec classes Bootstrap
            try {
                const el = this.elements.modal;
                
                // Forcer l'affichage direct
                el.style.display = 'block';
                el.style.visibility = 'visible';
                el.style.opacity = '1';
                el.style.zIndex = '1055';
                el.classList.add('show');
                el.setAttribute('aria-hidden', 'false');
                el.setAttribute('aria-modal', 'true');
                el.setAttribute('role', 'dialog');
                
                // Ajouter un backdrop manuel
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.style.zIndex = '1050';
                document.body.appendChild(backdrop);
                
                // Configurer le body
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
                
                // Gérer la fermeture via backdrop ou ESC
                const closeModal = () => {
                    el.style.display = 'none';
                    el.classList.remove('show');
                    el.setAttribute('aria-hidden', 'true');
                    el.removeAttribute('aria-modal');
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    backdrop.remove();
                };
                
                backdrop.addEventListener('click', closeModal);
                el.querySelector('.btn-close')?.addEventListener('click', closeModal);
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') closeModal();
                }, { once: true });
                
            } catch (e2) {
                console.error('[RepairModal] ❌ Fallback modal également échoué:', e2);
            }
        }
        
        console.log('Chargement des détails pour la réparation ID:', repairId);
        console.log('URL de l\'API:', this.config.apiUrl);
        
        // Récupérer l'ID du magasin depuis les données de session ou un attribut data
        let shopId = null;
        
        // Tenter de récupérer l'ID du magasin depuis l'élément HTML
        if (document.body.hasAttribute('data-shop-id')) {
            shopId = document.body.getAttribute('data-shop-id');
            console.log('ID du magasin trouvé dans data-shop-id:', shopId);
        } 
        // Sinon, essayer de le récupérer depuis le localStorage ou sessionStorage
        else if (localStorage.getItem('shop_id')) {
            shopId = localStorage.getItem('shop_id');
            console.log('ID du magasin trouvé dans localStorage:', shopId);
        } else if (sessionStorage.getItem('shop_id')) {
            shopId = sessionStorage.getItem('shop_id');
            console.log('ID du magasin trouvé dans sessionStorage:', shopId);
        }
        
        // Récupérer l'ID utilisateur pour l'envoyer à l'API
        const userId = window.currentUserId || 0;
        
        // Construire l'URL avec l'ID de la réparation et l'ID du magasin s'il est disponible
        let apiUrl = `${this.config.apiUrl}?id=${repairId}`;
        if (shopId) {
            apiUrl += `&shop_id=${shopId}`;
        }
        if (userId) {
            apiUrl += `&user_id=${userId}`;
        }
        console.log('URL de l\'API complète:', apiUrl);
        
        // Récupérer les données
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP ${response.status}`);
                }
                
                // Vérifier si la réponse est du JSON valide
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Réponse non-JSON reçue:', text);
                        throw new Error('La réponse n\'est pas au format JSON');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Données reçues:', data);
                
                if (!data.success) {
                    throw new Error(data.error || 'Erreur lors du chargement des détails');
                }
                
                // Mettre à jour le titre du modal avec l'ID de la réparation et les informations de garantie
                this.updateModalTitle(repairId, data.repair);
                
                // Afficher les détails
                this.renderRepairDetails(data);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des détails:', error);
                this.showError(`Erreur lors du chargement des détails: ${error.message}`);
            });
    },

    /**
     * Vérifie si la réparation est la réparation active de l'utilisateur connecté
     * @param {Object} repair - Objet réparation
     * @returns {boolean} - true si c'est la réparation active de l'utilisateur
     */
    isUserActiveRepair(repair) {
        // Récupérer l'ID utilisateur depuis la variable globale window uniquement
        let userId = null;
        
        // Essayer de récupérer l'ID utilisateur depuis window
        if (typeof window !== 'undefined' && window.currentUserId) {
            userId = window.currentUserId;
        } else if (document.body.getAttribute('data-user-id')) {
            userId = document.body.getAttribute('data-user-id');
        }
        
        if (!userId) {
            console.warn('ID utilisateur non trouvé pour vérifier la réparation active');
            return false;
        }
        
        console.log('Vérification réparation active:', {
            repairId: repair.id,
            employeId: repair.employe_id,
            userId: userId,
            activeRepairId: repair.active_repair_id,
            employeIdType: typeof repair.employe_id,
            userIdType: typeof userId,
            activeRepairIdType: typeof repair.active_repair_id,
            repairIdType: typeof repair.id
        });
        
        // Vérifier si les données nécessaires sont présentes
        if (!repair.employe_id || repair.active_repair_id === null || repair.active_repair_id === undefined || repair.active_repair_id === false) {
            console.log('Données manquantes ou nulles pour vérifier la réparation active:', {
                employeId: repair.employe_id,
                activeRepairId: repair.active_repair_id,
                activeRepairIdType: typeof repair.active_repair_id
            });
            return false;
        }

        // Conversion en nombres pour s'assurer de la comparaison
        const employeId = parseInt(repair.employe_id);
        const currentUserId = parseInt(userId);
        const activeRepairId = parseInt(repair.active_repair_id);
        const repairId = parseInt(repair.id);
        
        // Vérifier que activeRepairId n'est pas NaN
        if (isNaN(activeRepairId)) {
            console.log('activeRepairId est NaN après conversion, réparation non active');
            return false;
        }
        
        console.log('Comparaisons après conversion:', {
            'employeId == currentUserId': employeId == currentUserId,
            'activeRepairId == repairId': activeRepairId == repairId,
            'activeRepairId isNaN': isNaN(activeRepairId),
            employeId, currentUserId, activeRepairId, repairId
        });
        
        // Même logique que dans statut_rapide.php
        const isActive = employeId == currentUserId && activeRepairId == repairId;
        console.log('Résultat final isUserActiveRepair:', isActive);
        
        return isActive;
    },

    /**
     * Met à jour le titre du modal avec les informations de garantie
     * @param {string} repairId - ID de la réparation
     * @param {Object} repair - Données de la réparation
     */
    updateModalTitle(repairId, repair) {
        console.log('🔍 Données de garantie:', {
            garantie_etat: repair.garantie_etat,
            garantie_id: repair.garantie_id,
            garantie_statut: repair.garantie_statut,
            garantie_debut: repair.garantie_debut,
            garantie_fin: repair.garantie_fin
        });

        // Mettre à jour le titre principal
        const repairTitleText = document.getElementById('repairTitleText');
        const warrantyBadge = document.getElementById('warrantyBadge');
        
        if (repairTitleText) {
            repairTitleText.textContent = `Réparation #${repairId}`;
        }
        
        // Afficher le badge de garantie selon l'état
        console.log('🎯 Badge de garantie:', {
            warrantyBadge: !!warrantyBadge,
            garantie_etat: repair.garantie_etat,
            condition: !!(warrantyBadge && repair.garantie_etat)
        });
        
        if (warrantyBadge && repair.garantie_etat) {
            const warrantyText = warrantyBadge.querySelector('.warranty-text');
            console.log('🔧 Mise à jour du badge pour état:', repair.garantie_etat);
            
            // Réinitialiser les classes
            warrantyBadge.className = 'warranty-badge';
            
            switch (repair.garantie_etat) {
                case 'active':
                    warrantyBadge.classList.add('warranty-active');
                    warrantyText.textContent = 'GARANTIE';
                    warrantyBadge.classList.remove('d-none');
                    break;
                case 'expiree':
                    warrantyBadge.classList.add('warranty-expired');
                    warrantyText.textContent = 'GARANTIE EXPIRÉE';
                    warrantyBadge.classList.remove('d-none');
                    break;
                case 'expire_bientot':
                    warrantyBadge.classList.add('warranty-expiring');
                    warrantyText.textContent = 'GARANTIE EXPIRE BIENTÔT';
                    warrantyBadge.classList.remove('d-none');
                    break;
                case 'annulee':
                    warrantyBadge.classList.add('warranty-expired');
                    warrantyText.textContent = 'GARANTIE ANNULÉE';
                    warrantyBadge.classList.remove('d-none');
                    break;
                case 'aucune':
                    warrantyBadge.classList.add('warranty-none');
                    warrantyText.textContent = 'HORS GARANTIE';
                    warrantyBadge.classList.remove('d-none');
                    break;
                default:
                    // État inconnu - masquer le badge
                    warrantyBadge.classList.add('d-none');
                    break;
            }
            
            // Déclencher l'animation d'entrée
            if (!warrantyBadge.classList.contains('d-none')) {
                warrantyBadge.style.animation = 'none';
                setTimeout(() => {
                    warrantyBadge.style.animation = 'fadeInBounce 0.8s ease-out';
                }, 100);
            }
        } else {
            // Fallback: mettre à jour le titre de l'ancienne façon si les éléments ne sont pas trouvés
            const modalLabel = document.getElementById('repairDetailsModalLabel');
            if (modalLabel) {
                modalLabel.innerHTML = `
                    <i class="fas fa-tools me-2 text-primary"></i>
                    Réparation #${repairId}
                `;
            }
        }
    },

    /**
     * Affiche les détails de la réparation dans le modal
     * @param {Object} data - Données de la réparation
     */
    renderRepairDetails(data) {
        // Sécuriser les références DOM au cas où
        if (!this.elements.modal) this.elements.modal = document.getElementById('repairDetailsModal');
        if (!this.elements.detailsContainer) this.elements.detailsContainer = document.getElementById('repairDetailsContent');
        if (!this.elements.loader) this.elements.loader = document.getElementById('repairDetailsLoader');

        const repair = data.repair;
        const photos = data.photos || [];
        const pieces = data.pieces || [];
        const logs = data.logs || [];
        
        console.log('[RepairModal] Rendering details. Repair data:', repair); // Log repair data
        console.log('[RepairModal] Photos data:', photos); // Log photos data
        console.log('[RepairModal] Mot de passe:', repair.mot_de_passe); // Déboguer le mot de passe

        // Vérifier si l'appareil a une photo et l'ajouter au début des photos s'il y en a une
        let appareilPhoto = null;
        if (repair.photo_appareil) {
            console.log('[RepairModal] Found photo_appareil:', repair.photo_appareil); // Log if found
            appareilPhoto = {
                id: 'appareil-' + repair.id,
                url: repair.photo_appareil,
                chemin: repair.photo_appareil,
                description: 'Photo de l\'appareil ' + repair.type_appareil + ' ' + repair.marque + ' ' + repair.modele,
                is_device_photo: true
            };
            console.log('[RepairModal] Created appareilPhoto object:', appareilPhoto); // Log created object
        } else {
            console.log('[RepairModal] No photo_appareil found in repair data.'); // Log if not found
        }
        
        // Traiter les photos pour s'assurer qu'elles ont une URL valide
        const processedPhotos = photos.map(photo => {
            // Vérifier si la photo a une URL valide
            const photoUrl = photo.url || photo.chemin || '';
            console.log(`[RepairModal] Processing photo ID: ${photo.id}, URL: ${photoUrl}`);
            
            // Si l'URL ne commence pas par http:// ou https:// ou /, on ajoute un / au début
            let finalUrl = photoUrl;
            if (photoUrl && !photoUrl.startsWith('http://') && !photoUrl.startsWith('https://') && !photoUrl.startsWith('/')) {
                finalUrl = '/' + photoUrl;
                console.log(`[RepairModal] Adding leading slash to URL: ${finalUrl}`);
            }
            
            return {
                ...photo,
                url: finalUrl,
                description: photo.description || 'Photo'
            };
        });
        
        // Stocker l'ID de la réparation dans le modal (si présent)
        if (this.elements.modal) {
            this.elements.modal.setAttribute('data-repair-id', repair.id);
        }
        
        // Générer le contenu HTML
        let html = `
            <div class="row g-4">
                <!-- Actions -->
                <div class="col-12 mb-2">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs text-primary me-2"></i>
                                Actions
                            </h5>
                        </div>
                        <div class="card-body pb-0">
                            <!-- Actions principales - 6 boutons sur une ligne -->
                            <div class="row g-1 mb-3">
                                <div class="col-2">
                                    <button class="btn btn-outline-primary w-100 action-btn" data-action="devis" style="height: 60px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-file-invoice-dollar mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">DEVIS</span>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-2">
                                    <button class="btn btn-outline-success w-100 action-btn" data-action="status" style="height: 60px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-tasks mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">STATUT</span>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-2">
                                    <button class="btn btn-outline-warning w-100 action-btn" data-action="price" style="height: 60px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-euro-sign mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">PRIX</span>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-2">
                                    <button class="btn btn-outline-info w-100 action-btn" data-action="order" style="height: 60px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-shopping-cart mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">COMMANDER</span>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-2">
                                    <button class="btn btn-outline-secondary w-100 action-btn" data-action="print" style="height: 60px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-print mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">IMPRIMER</span>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-2">
                                    <a href="index.php?page=clients&id=${repair.client_id}" class="btn btn-outline-dark w-100 text-decoration-none" style="height: 60px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-user mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">CLIENT</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Actions de communication - 3 boutons sur une ligne -->
                            <div class="row g-1 mb-3">
                                <div class="col-4">
                                    <a href="tel:${repair.client_telephone}" class="btn btn-success w-100 text-decoration-none" style="height: 50px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-phone-alt mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">APPEL</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-primary w-100 client-action-btn send-sms-btn" 
                                            data-client-id="${repair.client_id}"
                                            data-client-nom="${repair.client_nom}"
                                            data-client-prenom="${repair.client_prenom}"
                                            data-client-tel="${repair.client_telephone}"
                                            style="height: 50px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-comment-alt mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">SMS</span>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-info w-100 client-action-btn" onclick="showRepairSmsModal(${repair.id}, '${repair.client_nom} ${repair.client_prenom}', '${repair.client_telephone}')" style="height: 50px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas fa-sms mb-1" style="font-size: 1rem;"></i>
                                            <span class="small fw-medium">HISTORIQUE</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Action de réparation - Bouton principal -->
                            <div class="row g-1">
                                <div class="col-12">
                                    <button class="btn ${(repair.employe_id == window.currentUserId && repair.active_repair_id == repair.id) ? 'btn-danger stop-repair-btn' : 'btn-success start-repair-btn'} w-100" data-repair-id="${repair.id}" style="height: 60px;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                            <i class="fas ${(repair.employe_id == window.currentUserId && repair.active_repair_id == repair.id) ? 'fa-stop-circle' : 'fa-play-circle'} mb-1" style="font-size: 1.3rem;"></i>
                                            <span class="fw-bold small">${(repair.employe_id == window.currentUserId && repair.active_repair_id == repair.id) ? 'ARRÊTER LA RÉPARATION' : 'DÉMARRER LA RÉPARATION'}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations client et appareil -->
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-circle text-primary me-2"></i>
                                Client
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="contact-info">
                                <div class="contact-info-item small">
                                    <i class="fas fa-user text-primary"></i>
                                    <div>
                                        <div class="fw-medium">${repair.client_nom} ${repair.client_prenom}</div>
                                    </div>
                                </div>
                                
                                <div class="contact-info-item small">
                                    <i class="fas fa-phone-alt text-success"></i>
                                    <div>
                                        <a href="tel:${repair.client_telephone}" class="text-decoration-none">
                                            ${repair.client_telephone}
                                        </a>
                                    </div>
                                </div>
                                
                                ${repair.client_email ? `
                                <div class="contact-info-item small">
                                    <i class="fas fa-envelope text-primary"></i>
                                    <div>
                                        <a href="mailto:${repair.client_email}" class="text-decoration-none">
                                            ${repair.client_email}
                                        </a>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <div class="contact-info-item small">
                                    <i class="fas fa-calendar-alt text-primary"></i>
                                    <div>
                                        <div>
                                            <span class="fw-medium">Date:</span> ${repair.date_reception || 'Non spécifiée'}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="contact-info-item small">
                                    <i class="fas fa-tasks ${repair.statut_couleur ? 'text-'+repair.statut_couleur : 'text-secondary'}"></i>
                                    <div>
                                        <div>
                                            <span class="fw-medium">Statut:</span> 
                                            ${repair.statut_nom || repair.statut}
                                            ${repair.urgent == 1 ? '<span class="badge bg-danger ms-2">URGENT</span>' : ''}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="contact-info-item small">
                                    <i class="fas fa-euro-sign text-success"></i>
                                    <div>
                                        <div>
                                            <span class="fw-medium">Prix:</span> <span class="price-value clickable" data-repair-id="${repair.id}" style="cursor: pointer;">${repair.prix_reparation_formatte ? repair.prix_reparation_formatte + ' €' : 'Non spécifié'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-mobile-alt text-primary me-2"></i>
                                Appareil
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="device-info">
                                <div class="device-info-item">
                                    <div class="device-info-label">Modèle</div>
                                    <div class="device-info-value">${repair.modele || 'Non spécifié'}</div>
                                </div>
                                
                                <div class="device-info-item">
                                    <div class="device-info-label">Mot de passe</div>
                                    <div class="device-info-value">${repair.mot_de_passe || 'Aucun mot de passe'}</div>
                                </div>
                                
                                <div class="device-info-item">
                                    <div class="device-info-label">Problème</div>
                                    <div class="device-info-value small problem-description">
                                        ${repair.description_probleme || 'Non spécifié'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes techniques -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clipboard-list text-primary me-2"></i>
                                Notes techniques
                            </h5>
                            <button class="btn btn-sm btn-outline-primary edit-notes-btn">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="card-body py-2">
                            <div class="technical-notes small">
                                ${repair.notes_techniques 
                                    ? repair.notes_techniques.replace(/\\n/g, '<br>') 
                                    : '<p class="text-muted">Aucune note technique</p>'}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Photos -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-images text-primary me-2"></i>
                                Photos ${(appareilPhoto || processedPhotos.length > 0) ? `(${processedPhotos.length + (appareilPhoto ? 1 : 0)})` : ''}
                            </h5>
                            <button class="btn btn-sm btn-outline-primary add-photo-btn">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <div class="card-body py-2">
                            ${(appareilPhoto || processedPhotos.length > 0) ? `
                            <div class="row g-2 photo-gallery">
                                ${appareilPhoto ? `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="photo-item photo-appareil">
                                        <div class="badge-appareil">Appareil</div>
                                        <img src="${appareilPhoto.url}" alt="${appareilPhoto.description}" class="img-fluid rounded">
                                        <div class="photo-overlay">
                                            <div class="photo-actions">
                                                <button class="btn btn-sm btn-light view-photo-btn" data-photo-id="${appareilPhoto.id}">
                                                    <i class="fas fa-search-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${processedPhotos.map(photo => `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="photo-item">
                                        <img src="${photo.url}" alt="${photo.description}" class="img-fluid rounded">
                                        <div class="photo-overlay">
                                            <div class="photo-actions">
                                                <button class="btn btn-sm btn-light view-photo-btn" data-photo-id="${photo.id}">
                                                    <i class="fas fa-search-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-photo-btn" data-photo-id="${photo.id}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                `).join('')}
                            </div>
                            ` : `
                            <div class="text-center py-3">
                                <div class="empty-state">
                                    <i class="fas fa-camera text-muted fa-2x mb-2"></i>
                                    <p class="text-muted small">Aucune photo disponible</p>
                                </div>
                            </div>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Injecter le HTML si le conteneur est présent
        if (this.elements.detailsContainer) {
            this.elements.detailsContainer.innerHTML = html;
        }
        
        // Cacher le loader et afficher le contenu
        this.hideLoader();
        
        // Initialiser les comportements spécifiques
        this.initRepairDetailsActions();
    },

    /**
     * Initialise les actions du modal
     */
    initModalActions() {
        if (!this.elements.modal) return;
        
        // Réinitialiser à la fermeture du modal
        this.elements.modal.addEventListener('hidden.bs.modal', () => {
            this.elements.detailsContainer.innerHTML = '';
        });
    },

    /**
     * Initialise les actions spécifiques aux détails d'une réparation
     */
    initRepairDetailsActions() {
        const repairId = this.elements.modal.getAttribute('data-repair-id');
        if (!repairId) return;
        
        // Bouton d'envoi de SMS
        document.querySelectorAll('.send-sms-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const clientId = btn.getAttribute('data-client-id');
                const clientNom = btn.getAttribute('data-client-nom');
                const clientPrenom = btn.getAttribute('data-client-prenom');
                const clientTel = btn.getAttribute('data-client-tel');
                if (window.openSmsModal && clientId) {
                    window.openSmsModal(clientId, clientNom, clientPrenom, clientTel);
                }
            });
        });
        
        // Bouton de modification des prix
        document.querySelectorAll('.price-value.clickable').forEach(element => {
            element.addEventListener('click', () => {
                // Récupérer le prix actuel (sans le symbole €)
                let currentPrice = element.textContent.trim().replace(' €', '');
                if (currentPrice === 'Non spécifié') currentPrice = '0';
                
                // Ouvrir le modal de clavier numérique
                if (window.priceModal) {
                    window.priceModal.show(repairId, currentPrice);
                }
            });
        });
        
        // Bouton de modification des notes
        document.querySelectorAll('.edit-notes-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Récupérer les notes techniques depuis l'élément DOM
                const technicalNotesElement = document.querySelector('.technical-notes');
                let currentNotes = '';
                
                if (technicalNotesElement) {
                    // Récupérer le contenu HTML
                    const htmlContent = technicalNotesElement.innerHTML;
                    
                    // Si le contenu contient un message indiquant qu'il n'y a pas de notes
                    if (htmlContent.includes('Aucune note technique')) {
                        currentNotes = '';
                    } else {
                        // Sinon, extraire le texte et remplacer les <br> par des sauts de ligne
                        currentNotes = htmlContent.replace(/<br\s*\/?>/gi, '\n').trim();
                    }
                }
                
                // Ouvrir le modal des notes
                this.openNotesModal(repairId, currentNotes);
            });
        });
        
        // Bouton d'ajout de photo
        document.querySelectorAll('.add-photo-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Ouvrir le modal d'ajout de photo
                console.log('Ajouter une photo pour la réparation', repairId);
                this.openPhotoModal(repairId);
            });
        });
        
        // Boutons démarrer/arrêter - utiliser exactement la même logique que les cartes
        console.log('🔧 Initialisation des boutons démarrer/arrêter...');
        const repairButtons = document.querySelectorAll('.start-repair-btn, .stop-repair-btn');
        console.log('🔍 Boutons trouvés:', repairButtons.length);
        
        repairButtons.forEach((btn, index) => {
            console.log(`🔘 Bouton ${index}:`, btn.className, 'data-repair-id:', btn.getAttribute('data-repair-id'));
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('🎯 Clic sur bouton réparation détecté!');
                const repairId = btn.getAttribute('data-repair-id');
                const isStopBtn = btn.classList.contains('stop-repair-btn');
                
                console.log('🔍 repairId:', repairId, 'isStopBtn:', isStopBtn);
                
                if (isStopBtn) {
                    // Bouton arrêter
                    if (confirm('Êtes-vous sûr de vouloir arrêter cette réparation ?')) {
                       // Appel direct à l'API comme dans les cartes
                       fetch('ajax/repair_assignment.php', {
                           method: 'POST',
                           headers: {
                               'Content-Type': 'application/json',
                           },
                           credentials: 'same-origin',
                           body: JSON.stringify({
                               action: 'complete_active_repair',
                               reparation_id: repairId
                           }),
                       })
                       .then(response => {
                           console.log('🔍 Réponse arrêter:', response.status, response.statusText);
                           return response.json();
                       })
                       .then(data => {
                           console.log('📋 Données arrêter:', data);
                           if (data.success) {
                               alert('Réparation terminée avec succès !');
                               location.reload();
                           } else {
                               alert('Erreur lors de l\'arrêt : ' + data.message);
                           }
                       })
                       .catch(error => {
                           console.error('❌ Erreur arrêter:', error);
                           alert('Erreur de connexion lors de l\'arrêt');
                       });
                    }
                } else {
                    // Bouton démarrer - utiliser exactement la même logique que les cartes
                    if (confirm('Êtes-vous sûr de vouloir démarrer cette réparation ?')) {
                        // Vérifier d'abord si l'utilisateur a déjà une réparation active
                       fetch('ajax/repair_assignment.php', {
                           method: 'POST',
                           headers: {
                               'Content-Type': 'application/json',
                           },
                           credentials: 'same-origin',
                           body: JSON.stringify({
                                action: 'check_active_repair',
                                reparation_id: repairId
                            }),
                        })
                        .then(response => {
                            console.log('🔍 Réponse check_active_repair:', response.status, response.statusText);
                            return response.json();
                        })
                        .then(data => {
                            console.log('📋 Données check_active_repair:', data);
                            if (data.success) {
                                console.log('🔍 Vérification des conditions:');
                                console.log('  - has_active_repair:', data.has_active_repair);
                                console.log('  - active_repair.id:', data.active_repair?.id);
                                console.log('  - repairId:', repairId);
                                console.log('  - active_repair.id != repairId:', data.active_repair?.id != repairId);
                                
                            if (data.has_active_repair) {
                                if (data.active_repair.id != repairId) {
                                    // L'utilisateur a déjà une réparation active différente
                                    console.log('🔄 Réparation active différente détectée:', data.active_repair);
                                    
                                    // Remplir le modal activeRepairModal comme dans les cartes
                                    const activeRepair = data.active_repair;
                                    document.getElementById('activeRepairId').textContent = `#${activeRepair.id}`;
                                    document.getElementById('activeRepairDevice').textContent = activeRepair.modele || 'Non renseigné';
                                    document.getElementById('activeRepairClient').textContent = `${activeRepair.client_nom || ''} ${activeRepair.client_prenom || ''}`.trim() || 'Non renseigné';
                                    document.getElementById('activeRepairProblem').textContent = activeRepair.description_probleme || 'Non renseigné';
                                    
                                    // Ajouter des écouteurs aux boutons de statut
                                    const completeButtons = document.querySelectorAll(".complete-btn");
                                    completeButtons.forEach(button => {
                                        // Créer un clone du bouton pour éviter les doublons d'écouteurs
                                        const newButton = button.cloneNode(true);
                                        button.parentNode.replaceChild(newButton, button);
                                        
                                        // Ajouter l'écouteur d'événement qui appelle completeActiveRepair avec le statut
                                        newButton.addEventListener("click", function() {
                                            const status = this.getAttribute("data-status");
                                            completeActiveRepair(activeRepair.id, status);
                                        });
                                    });
                                    
                                    // Fermer d'abord le modal RepairModal
                                    const repairModal = bootstrap.Modal.getInstance(document.getElementById('repairDetailsModal'));
                                    if (repairModal) {
                                        repairModal.hide();
                                    }
                                    
                                    // Attendre que le modal se ferme puis ouvrir activeRepairModal
                                    setTimeout(() => {
                                        const activeRepairModal = new bootstrap.Modal(document.getElementById('activeRepairModal'));
                                        activeRepairModal.show();
                                    }, 300);
                                    
                                    return; // Sortir de la fonction
                                } else {
                                    // L'utilisateur essaie de démarrer sa propre réparation active
                                    console.log('🔄 Réparation déjà active détectée:', data.active_repair);
                                    
                                    // Remplir le modal activeRepairModal avec la réparation actuelle
                                    const activeRepair = data.active_repair;
                                    document.getElementById('activeRepairId').textContent = `#${activeRepair.id}`;
                                    document.getElementById('activeRepairDevice').textContent = activeRepair.modele || 'Non renseigné';
                                    document.getElementById('activeRepairClient').textContent = `${activeRepair.client_nom || ''} ${activeRepair.client_prenom || ''}`.trim() || 'Non renseigné';
                                    document.getElementById('activeRepairProblem').textContent = activeRepair.description_probleme || 'Non renseigné';
                                    
                                    // Ajouter des écouteurs aux boutons de statut
                                    const completeButtons = document.querySelectorAll(".complete-btn");
                                    completeButtons.forEach(button => {
                                        // Créer un clone du bouton pour éviter les doublons d'écouteurs
                                        const newButton = button.cloneNode(true);
                                        button.parentNode.replaceChild(newButton, button);
                                        
                                        // Ajouter l'écouteur d'événement qui appelle completeActiveRepair avec le statut
                                        newButton.addEventListener("click", function() {
                                            const status = this.getAttribute("data-status");
                                            completeActiveRepair(activeRepair.id, status);
                                        });
                                    });
                                    
                                    // Fermer d'abord le modal RepairModal
                                    const repairModal = bootstrap.Modal.getInstance(document.getElementById('repairDetailsModal'));
                                    if (repairModal) {
                                        repairModal.hide();
                                    }
                                    
                                    // Attendre que le modal se ferme puis ouvrir activeRepairModal
                                    setTimeout(() => {
                                        const activeRepairModal = new bootstrap.Modal(document.getElementById('activeRepairModal'));
                                        activeRepairModal.show();
                                    }, 300);
                                    
                                    return; // Sortir de la fonction
                                }
                            } else {
                                    // L'utilisateur n'a pas de réparation active, attribuer la réparation
                       fetch('ajax/repair_assignment.php', {
                           method: 'POST',
                           headers: {
                               'Content-Type': 'application/json',
                           },
                           credentials: 'same-origin',
                           body: JSON.stringify({
                                            action: 'assign_repair',
                                            reparation_id: repairId
                                        }),
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert('Réparation démarrée avec succès !');
                                            location.reload();
                                        } else {
                                            alert('Erreur lors du démarrage : ' + data.message);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Erreur:', error);
                                        alert('Erreur de connexion lors du démarrage');
                                    });
                                }
                            } else {
                                alert(data.message || 'Une erreur est survenue lors de la vérification des réparations actives.');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert('Erreur de connexion lors de la vérification');
                        });
                    }
                }
            });
        });
        
        // Boutons d'action
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.getAttribute('data-action');
                if (!action) return;
                
                // Exécuter l'action
                this.executeAction(action, repairId);
            });
        });
        
        // Initialiser les écouteurs d'événements pour les boutons d'action client
        document.querySelectorAll('.client-action-btn.send-sms-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const clientId = btn.getAttribute('data-client-id');
                const clientNom = btn.getAttribute('data-client-nom');
                const clientPrenom = btn.getAttribute('data-client-prenom');
                const clientTel = btn.getAttribute('data-client-tel');
                if (window.openSmsModal && clientId) {
                    window.openSmsModal(clientId, clientNom, clientPrenom, clientTel);
                }
            });
        });
    },

    /**
     * Exécute une action sur une réparation
     * @param {string} action - Action à exécuter
     * @param {string} repairId - ID de la réparation
     */
    executeAction(action, repairId) {
        console.log(`Exécution de l'action ${action} pour la réparation ${repairId}`);
        
        switch (action) {
            case 'devis':
                console.log('🎯 [REPAIR-MODAL] Redirection vers le nouveau modal de devis moderne pour réparation', repairId);
                
                // Fermer le modal actuel
                const currentModal = bootstrap.Modal.getInstance(document.getElementById('repairDetailsModal'));
                if (currentModal) {
                    console.log('🔄 [REPAIR-MODAL] Fermeture du modal de détails');
                    currentModal.hide();
                }
                
                // Attendre que le modal soit fermé puis ouvrir le nouveau modal de devis
                setTimeout(() => {
                    if (typeof window.ouvrirNouveauModalDevis === 'function') {
                        console.log('✅ [REPAIR-MODAL] Ouverture du nouveau modal de devis moderne');
                        window.ouvrirNouveauModalDevis(repairId);
                                        } else {
                        console.error('❌ [REPAIR-MODAL] Fonction ouvrirNouveauModalDevis non disponible');
                        alert('Erreur: Le nouveau système de devis n\'est pas disponible');
                        }
                }, 200);
                break;
                
            case 'edit':
                // Rediriger vers la page de modification
                window.location.href = `index.php?page=modifier_reparation&id=${repairId}`;
                break;
                
            case 'status':
                // Ouvrir la modal de changement de statut
                if (window.statusModal) {
                    window.statusModal.show(repairId);
                }
                break;
                
            case 'price':
                // Ouvrir la modal de modification du prix
                if (window.priceModal) {
                    window.priceModal.show(repairId);
                }
                break;
                
            case 'order':
                // Ouvrir le modal de nouvelle commande de pièces qui est dans le footer
                const modalElement = document.getElementById('ajouterCommandeModal');
                if (modalElement) {
                    // Préparer le modal avec les infos de la réparation
                    this.prepareCommandeModal(repairId);
                    
                    // Afficher le modal
                    const commandeModal = new bootstrap.Modal(modalElement);
                    commandeModal.show();
                } else {
                    console.error("Modal de commande non trouvé dans le DOM");
                }
                break;
                
            case 'print':
                // Ouvrir la page d'impression d'étiquette avec le domaine actuel
                window.open(`https://${window.location.host}/index.php?page=imprimer_etiquette&id=${repairId}`, '_blank');
                break;
        }
    },

    /**
     * Prépare le modal de commande avec les informations de la réparation
     * @param {string} repairId - ID de la réparation
     */
    prepareCommandeModal(repairId) {
        // Récupérer les données de la réparation
        fetch(`ajax/get_repair_details.php?id=${repairId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.repair) {
                    const repair = data.repair;
                    
                    // Remplir le formulaire avec les données de la réparation
                    const reparationSelect = document.querySelector('#ajouterCommandeModal select[name="reparation_id"]');
                    const clientIdInput = document.querySelector('#ajouterCommandeModal #client_id');
                    const nomClientElement = document.querySelector('#ajouterCommandeModal #nom_client_selectionne');
                    const clientSelectElement = document.querySelector('#ajouterCommandeModal #client_selectionne');
                    
                    if (reparationSelect) {
                        // Trouver ou créer l'option pour cette réparation
                        let option = Array.from(reparationSelect.options).find(opt => opt.value === repairId);
                        
                        if (!option) {
                            option = document.createElement('option');
                            option.value = repairId;
                            option.text = `Réparation #${repairId} - ${repair.type_appareil} ${repair.marque} ${repair.modele}`;
                            reparationSelect.appendChild(option);
                        }
                        
                        // Sélectionner cette réparation
                        option.selected = true;
                        
                        // Déclencher l'événement change pour activer les éventuels listeners
                        const event = new Event('change');
                        reparationSelect.dispatchEvent(event);
                    }
                    
                    // Remplir les infos du client
                    if (clientIdInput && repair.client_id) {
                        clientIdInput.value = repair.client_id;
                    }
                    
                    if (nomClientElement && clientSelectElement && repair.client_nom && repair.client_prenom) {
                        nomClientElement.textContent = `${repair.client_prenom} ${repair.client_nom}`;
                        clientSelectElement.classList.remove('d-none');
                    }
                    
                    console.log('Modal de commande préparé avec les données de la réparation', repairId);
                } else {
                    console.error('Erreur lors de la récupération des détails de la réparation');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    },

    /**
     * Affiche le loader et cache le contenu
     */
    showLoader() {
        // Assurer la présence des éléments même si init a été fait tôt
        if (!this.elements.loader) {
            this.elements.loader = document.getElementById('repairDetailsLoader');
        }
        if (!this.elements.detailsContainer) {
            this.elements.detailsContainer = document.getElementById('repairDetailsContent');
        }
        if (this.elements.loader) {
            this.elements.loader.style.display = 'block';
        }
        if (this.elements.detailsContainer) {
            this.elements.detailsContainer.style.display = 'none';
        }
    },

    /**
     * Cache le loader et affiche le contenu
     */
    hideLoader() {
        if (!this.elements.loader) {
            this.elements.loader = document.getElementById('repairDetailsLoader');
        }
        if (!this.elements.detailsContainer) {
            this.elements.detailsContainer = document.getElementById('repairDetailsContent');
        }
        if (this.elements.loader) {
            this.elements.loader.style.display = 'none';
        }
        if (this.elements.detailsContainer) {
            this.elements.detailsContainer.style.display = 'block';
        }
    },

    /**
     * Affiche un message d'erreur
     * @param {string} message - Message d'erreur
     */
    showError(message) {
        this.elements.detailsContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `;
        this.hideLoader();
    },
    
    /**
     * Ouvre le modal d'édition des notes techniques
     * @param {string} repairId - ID de la réparation
     * @param {string} currentNotes - Notes techniques actuelles
     */
    openNotesModal(repairId, currentNotes) {
        // Vérifier si le modal existe déjà
        let modal = document.getElementById('notesModal');
        
        // Si le modal n'existe pas, le créer
        if (!modal) {
            const modalHTML = `
                <div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="notesModalLabel">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Modifier les notes techniques
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form id="notesForm">
                                    <input type="hidden" id="notes_repair_id" name="repair_id">
                                    <div class="mb-3">
                                        <label for="notes_content" class="form-label">Notes techniques</label>
                                        <textarea class="form-control" id="notes_content" name="notes" rows="6" placeholder="Saisissez vos notes techniques ici..."></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" id="saveNotesBtn">
                                    <i class="fas fa-save me-1"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter le modal au document
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('notesModal');
        }
        
        // Récupérer les éléments du modal
        const notesContent = document.getElementById('notes_content');
        const notesRepairId = document.getElementById('notes_repair_id');
        const saveBtn = document.getElementById('saveNotesBtn');
        
        // Remplir le formulaire avec les données existantes
        notesRepairId.value = repairId;
        notesContent.value = currentNotes;
        
        // Gérer l'événement de sauvegarde
        const saveHandler = () => {
            // Récupérer les données du formulaire
            const notes = notesContent.value;
            
            // Désactiver le bouton pendant l'envoi
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sauvegarde en cours...';
            
            // Récupérer l'ID du magasin
            let shopId = null;
            if (typeof SessionHelper !== 'undefined' && SessionHelper.getShopId) {
                shopId = SessionHelper.getShopId();
            } else if (localStorage.getItem('shop_id')) {
                shopId = localStorage.getItem('shop_id');
            } else if (document.body.hasAttribute('data-shop-id')) {
                shopId = document.body.getAttribute('data-shop-id');
            }
            
            // Créer le corps de la requête
            let requestBody = `repair_id=${repairId}&notes=${encodeURIComponent(notes)}`;
            
            // Ajouter l'ID du magasin s'il est disponible
            if (shopId) {
                requestBody += `&shop_id=${shopId}`;
                console.log("ID du magasin ajouté à la requête de notes:", shopId);
            }
            
            console.log("Données à envoyer pour notes:", requestBody);
            
            // Envoyer les données via AJAX
            fetch('ajax/update_repair_notes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: requestBody
            })
            .then(response => {
                // Vérifier si la réponse est de type JSON
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error("Réponse non-JSON:", text);
                        throw new Error("La réponse n'est pas au format JSON");
                    });
                }
            })
            .then(data => {
                // Fermer le modal
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                
                // Réactiver le bouton
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
                
                // Afficher une notification avec alert au lieu de toastr
                if (data.success) {
                    alert('Notes techniques mises à jour avec succès');
                    
                    // Rafraîchir la page pour voir les modifications
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Erreur: ' + (data.message || 'Erreur lors de la mise à jour des notes'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
                
                // Utiliser alert au lieu de toastr
                alert('Erreur de connexion: ' + error.message);
            });
        };
        
        // Supprimer les anciens écouteurs d'événements si nécessaire
        saveBtn.removeEventListener('click', saveHandler);
        
        // Ajouter le nouvel écouteur d'événements
        saveBtn.addEventListener('click', saveHandler);
        
        // Afficher le modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    },

    /**
     * Ouvre le modal d'ajout de photo
     * @param {string} repairId - ID de la réparation
     */
    openPhotoModal(repairId) {
        // Vérifier si le modal existe déjà
        let modal = document.getElementById('photoModal');
        
        // Si le modal n'existe pas, le créer
        if (!modal) {
            const modalHTML = `
                <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="photoModalLabel">
                                    <i class="fas fa-camera me-2"></i>
                                    Ajouter une photo
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form id="photoForm">
                                    <input type="hidden" id="photo_repair_id" name="repair_id" value="${repairId}">
                                    
                                    <!-- Zone de la caméra -->
                                    <div id="cameraContainer" class="text-center mb-4">
                                        <video id="cameraVideo" autoplay playsinline class="img-fluid rounded" style="max-height: 300px; background-color: #f8f9fa;"></video>
                                        <canvas id="cameraCanvas" class="d-none"></canvas>
                                    </div>
                                    
                                    <!-- Prévisualisation de la photo -->
                                    <div id="photoPreviewContainer" class="text-center mb-4 d-none">
                                        <div class="position-relative d-inline-block">
                                            <img id="photoPreviewImage" src="" alt="Prévisualisation" class="img-fluid rounded" style="max-height: 300px;">
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" id="retakePhotoBtn">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Description de la photo -->
                                    <div class="mb-3">
                                        <label for="photoDescription" class="form-label">Description (optionnelle)</label>
                                        <input type="text" class="form-control" id="photoDescription" name="description" placeholder="Description de la photo">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" id="capturePhotoBtn">
                                    <i class="fas fa-camera me-1"></i> Prendre la photo
                                </button>
                                <button type="button" class="btn btn-success d-none" id="savePhotoBtn">
                                    <i class="fas fa-save me-1"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter le modal au document
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('photoModal');
        }
        
        // Variables pour la gestion de la caméra
        let stream = null;
        let photoData = null;
        
        // Récupérer les éléments du modal
        const cameraContainer = document.getElementById('cameraContainer');
        const previewContainer = document.getElementById('photoPreviewContainer');
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const previewImage = document.getElementById('photoPreviewImage');
        const retakeBtn = document.getElementById('retakePhotoBtn');
        const captureBtn = document.getElementById('capturePhotoBtn');
        const saveBtn = document.getElementById('savePhotoBtn');
        
        // Fonction pour démarrer la caméra
        const startCamera = async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                
                video.srcObject = stream;
                cameraContainer.classList.remove('d-none');
                previewContainer.classList.add('d-none');
                captureBtn.classList.remove('d-none');
                saveBtn.classList.add('d-none');
                
            } catch (err) {
                console.error('Erreur d\'accès à la caméra:', err);
                alert('Impossible d\'accéder à la caméra: ' + err.message);
            }
        };
        
        // Fonction pour arrêter la caméra
        const stopCamera = () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        };
        
        // Fonction pour capturer une photo
        const capturePhoto = () => {
            // Configurer le canvas aux dimensions de la vidéo
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Dessiner l'image de la vidéo sur le canvas
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Récupérer les données de l'image
            photoData = canvas.toDataURL('image/jpeg');
            
            // Afficher la prévisualisation
            previewImage.src = photoData;
            cameraContainer.classList.add('d-none');
            previewContainer.classList.remove('d-none');
            captureBtn.classList.add('d-none');
            saveBtn.classList.remove('d-none');
        };
        
        // Fonction pour reprendre une photo
        const retakePhoto = () => {
            photoData = null;
            previewImage.src = '';
            
            cameraContainer.classList.remove('d-none');
            previewContainer.classList.add('d-none');
            captureBtn.classList.remove('d-none');
            saveBtn.classList.add('d-none');
        };
        
        // Fonction pour enregistrer la photo
        const savePhoto = () => {
            if (!photoData) {
                alert('Aucune photo à enregistrer');
                return;
            }
            
            const description = document.getElementById('photoDescription').value;
            
            // Désactiver le bouton pendant l'envoi
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
            
            // Créer le formulaire à envoyer
            const formData = new FormData();
            formData.append('repair_id', repairId);
            formData.append('photo', photoData);
            formData.append('description', description);
            
            // Récupérer l'ID du magasin
            let shopId = null;
            if (typeof SessionHelper !== 'undefined' && SessionHelper.getShopId) {
                shopId = SessionHelper.getShopId();
            } else if (localStorage.getItem('shop_id')) {
                shopId = localStorage.getItem('shop_id');
            } else if (document.body.hasAttribute('data-shop-id')) {
                shopId = document.body.getAttribute('data-shop-id');
            }
            
            // Ajouter l'ID du magasin s'il est disponible
            if (shopId) {
                formData.append('shop_id', shopId);
                console.log("ID du magasin ajouté à la requête photo:", shopId);
            }
            
            console.log('Envoi de la photo pour la réparation ID:', repairId);
            
            // Envoyer la requête
            fetch('ajax/upload_repair_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Vérifier si la réponse est de type JSON
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error("Réponse non-JSON:", text);
                        throw new Error("La réponse n'est pas au format JSON");
                    });
                }
            })
            .then(data => {
                console.log('Réponse du serveur:', data);
                
                // Fermer le modal
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                
                // Arrêter la caméra
                stopCamera();
                
                // Afficher une notification
                if (data.success) {
                    // Utiliser alert au lieu de toastr pour éviter les erreurs
                    alert('Photo ajoutée avec succès');
                    
                    // Rafraîchir la page pour voir les modifications
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Erreur: ' + (data.message || 'Erreur lors de l\'ajout de la photo'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
                
                // Utiliser alert au lieu de toastr
                alert('Erreur de connexion: ' + error.message);
            });
        };
        
        // Configurer les écouteurs d'événements
        captureBtn.onclick = capturePhoto;
        retakeBtn.onclick = retakePhoto;
        saveBtn.onclick = savePhoto;
        
        // Gérer la fermeture du modal
        modal.addEventListener('hidden.bs.modal', () => {
            stopCamera();
        });
        
        // Afficher le modal et démarrer la caméra
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Démarrer la caméra après l'affichage du modal
        modal.addEventListener('shown.bs.modal', () => {
            startCamera();
        });
    }
};

// Fonctions pour gérer les actions démarrer/arrêter
function startRepairAction(repairId) {
    // Vérifier d'abord si l'utilisateur a déjà une réparation active
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
                           credentials: 'same-origin',
        body: JSON.stringify({
            action: 'check_active_repair',
            reparation_id: repairId
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.has_active_repair && data.active_repair.id != repairId) {
                const activeRepair = data.active_repair;
                
                // Afficher le message d'erreur d'abord
                // Utiliser la même approche que les cartes : un simple confirm
                console.log('🔍 Vérification de la fonction completeActiveRepairAndStartNew...');
                console.log('🔍 window.completeActiveRepairAndStartNew:', typeof window.completeActiveRepairAndStartNew);
                console.log('🔍 activeRepair.id:', activeRepair.id);
                console.log('🔍 repairId:', repairId);
                
                if (confirm('Vous avez déjà une réparation active (#' + activeRepair.id + '). Voulez-vous la terminer et démarrer cette nouvelle réparation ?')) {
                    console.log('✅ Utilisateur a confirmé, appel de completeActiveRepairAndStartNew...');
                    
                    // Terminer d'abord la réparation active et démarrer la nouvelle
                    if (window.completeActiveRepairAndStartNew) {
                        console.log('🚀 Appel de completeActiveRepairAndStartNew...');
                        window.completeActiveRepairAndStartNew(activeRepair.id, repairId);
                    } else {
                        console.error('❌ Fonction completeActiveRepairAndStartNew non disponible');
                        console.log('🔍 Fonctions disponibles sur window:', Object.keys(window).filter(key => key.includes('Repair')));
                        alert('Erreur : Fonction completeActiveRepairAndStartNew non disponible');
                    }
                } else {
                    console.log('❌ Utilisateur a annulé');
                }
                
            } else {
                // L'utilisateur n'a pas de réparation active, attribuer la réparation
                assignRepairAction(repairId);
            }
        } else {
            alert(data.message || 'Une erreur est survenue lors de la vérification des réparations actives.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors de la vérification');
    });
}

function stopRepairAction(repairId) {
    // Au lieu d'appeler directement l'API, ouvrir le modal activeRepairModal
    // D'abord, récupérer les informations de la réparation active
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
                           credentials: 'same-origin',
        body: JSON.stringify({
            action: 'check_active_repair',
            reparation_id: repairId
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.has_active_repair) {
            const activeRepair = data.active_repair;
            
            // Remplir les informations dans le modal activeRepairModal
            document.getElementById('activeRepairId').textContent = `#${activeRepair.id}`;
            document.getElementById('activeRepairDevice').textContent = activeRepair.modele || 'Non renseigné';
            document.getElementById('activeRepairClient').textContent = `${activeRepair.client_nom || ''} ${activeRepair.client_prenom || ''}`.trim() || 'Non renseigné';
            
            // Ajouter le problème
            const activeRepairProblemEl = document.getElementById('activeRepairProblem');
            if (activeRepairProblemEl) activeRepairProblemEl.textContent = activeRepair.description_probleme || 'Non renseigné';
            
            // Fermer le modal de détails de réparation
            const detailsModal = bootstrap.Modal.getInstance(document.getElementById('repairDetailsModal'));
            if (detailsModal) {
                detailsModal.hide();
            }
            
            // Attendre que le modal se ferme puis ouvrir le modal activeRepairModal
            setTimeout(() => {
                // Ajouter des écouteurs aux boutons de statut
                const completeButtons = document.querySelectorAll(".complete-btn");
                completeButtons.forEach(button => {
                    // Créer un clone du bouton pour éviter les doublons d'écouteurs
                    const newButton = button.cloneNode(true);
                    button.parentNode.replaceChild(newButton, button);
                    
                    // Ajouter l'écouteur d'événement qui appelle completeActiveRepair avec le statut
                    newButton.addEventListener("click", function() {
                        const status = this.getAttribute("data-status");
                        completeActiveRepair(activeRepair.id, status);
                    });
                });
                
                // Afficher le modal activeRepairModal
                const activeRepairModal = new bootstrap.Modal(document.getElementById('activeRepairModal'));
                activeRepairModal.show();
            }, 300);
            
        } else {
            alert('Erreur: Aucune réparation active trouvée.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors de la vérification de la réparation active');
    });
}

function assignRepairAction(repairId) {
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
                           credentials: 'same-origin',
        body: JSON.stringify({
            action: 'assign_repair',
            reparation_id: repairId
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Réparation démarrée avec succès !');
            // Fermer le modal et recharger la page
            const modal = bootstrap.Modal.getInstance(document.getElementById('repairDetailsModal'));
            if (modal) {
                modal.hide();
            }
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            alert('Erreur lors du démarrage : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors du démarrage');
    });
}

function completeActiveRepairAndStartNew(activeRepairId, newRepairId, finalStatus = null) {
    // Fermer le modal activeRepairModal d'abord
    const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
    if (activeRepairModal) {
        activeRepairModal.hide();
    }
    
    // Préparer les données pour terminer la réparation active
    const requestData = {
        action: 'complete_active_repair',
        reparation_id: activeRepairId
    };
    
    // Ajouter le statut final si fourni
    if (finalStatus) {
        requestData.final_status = finalStatus;
    }
    
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Maintenant attribuer la nouvelle réparation
            assignRepairAction(newRepairId);
        } else {
            alert('Erreur lors de la finalisation : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors de la finalisation');
    });
}

// Fonction pour terminer une réparation active avec un statut final
function completeActiveRepair(repairId, finalStatus) {
    // Vérifier si nous avons un statut
    if (!finalStatus) {
        alert('Veuillez sélectionner un statut final');
        return;
    }
    
    console.log('Finalisation de la réparation:', repairId, 'avec statut:', finalStatus);
    
    // Si le statut est "en_attente_accord_client", ouvrir le modal d'envoi de devis
    if (finalStatus === 'en_attente_accord_client') {
        // Fermer le modal actif
        const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
        activeRepairModal.hide();
        
        // D'abord changer le statut de la réparation en "en_attente_accord_client"
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
                           credentials: 'same-origin',
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès après avoir mis à jour le statut
                alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
                
                // Utiliser la fonction executeAction du module RepairModal pour ouvrir le modal d'envoi de devis
                if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                    window.RepairModal.executeAction('devis', repairId);
                } else {
                    alert("Le module d'envoi de devis n'est pas disponible. La réparation a été mise en attente d'accord client.");
                    // Recharger la page après un court délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
            window.location.reload();
        });
        
        return;
    }
    
    // Si le statut est "nouvelle_commande", ouvrir le modal de commande de pièces
    if (finalStatus === 'nouvelle_commande') {
        // Fermer le modal actif
        const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
        activeRepairModal.hide();
        
        // D'abord changer le statut de la réparation
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
                           credentials: 'same-origin',
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès après avoir mis à jour le statut
                alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
                
                // Utiliser la fonction executeAction du module RepairModal pour ouvrir le modal de commande
                if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                    window.RepairModal.executeAction('order', repairId);
                } else {
                    alert("Le module de commande n'est pas disponible. La réparation a été mise en statut nouvelle commande.");
                    // Recharger la page après un court délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
            window.location.reload();
        });
        
        return;
    }
    
    // Pour tous les autres statuts, finaliser directement
    fetch('ajax/repair_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
                           credentials: 'same-origin',
        body: JSON.stringify({
            action: 'complete_active_repair',
            reparation_id: repairId,
            final_status: finalStatus
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
            activeRepairModal.hide();
            
            // Afficher un message de succès
            alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
            
            // Recharger la page pour refléter les changements
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue lors de la complétion de la réparation.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la communication avec le serveur.');
    });
}

// Initialiser le module au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    RepairModal.init();
}); 