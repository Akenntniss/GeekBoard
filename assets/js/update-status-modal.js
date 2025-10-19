/**
 * Gestionnaire du modal de mise à jour des statuts par lots
 * Gère les onglets, la sélection des réparations et la soumission
 */

class UpdateStatusModal {
    constructor() {
        this.modal = null;
        this.currentTab = 'nouvelles';
        this.selectedRepairs = new Set();
        this.repairs = {};
        this.statuses = [];
        
        this.init();
    }

    init() {
        // Initialiser le modal
        this.modal = document.getElementById('updateStatusModal');
        if (!this.modal) {
            console.error('Modal updateStatusModal non trouvé');
            return;
        }

        // Écouter l'ouverture du modal
        this.modal.addEventListener('show.bs.modal', () => {
            this.loadData();
        });

        // Initialiser les événements
        this.initTabEvents();
        this.initSelectionEvents();
        this.initActionEvents();
    }

    initTabEvents() {
        // Écouter les clics sur les onglets CSS modernes
        const tabs = document.querySelectorAll('#statusTabs .modern-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Désactiver tous les onglets
                tabs.forEach(t => t.classList.remove('active'));
                // Activer l'onglet cliqué
                tab.classList.add('active');
                
                // Masquer tous les panneaux
                document.querySelectorAll('#statusTabsContent .tab-panel').forEach(panel => {
                    panel.classList.remove('active');
                });
                
                // Afficher le panneau correspondant
                const targetId = tab.getAttribute('data-tab');
                const targetPanel = document.getElementById(targetId);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
                
                // Charger les données pour cet onglet
                this.currentTab = targetId;
                this.loadRepairsForTab(targetId);
            });
        });
    }

    initSelectionEvents() {
        // Boutons de sélection globale
        document.getElementById('select-all-visible')?.addEventListener('click', () => {
            this.selectAllVisible();
        });

        document.getElementById('deselect-all')?.addEventListener('click', () => {
            this.deselectAll();
        });

        // Écouter les changements de sélection individuelle
        document.addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains('repair-checkbox')) {
                this.handleRepairSelection(e.target);
            } else if (e.target && e.target.id.startsWith('select-all-')) {
                this.handleSelectAllTab(e.target);
            }
        });
    }

    initActionEvents() {
        // Bouton de mise à jour
        document.getElementById('update-selected-repairs')?.addEventListener('click', () => {
            this.updateSelectedRepairs();
        });
    }

    async loadData() {
        console.log('🔄 Chargement des données du modal...');
        
        try {
            // Charger les statuts disponibles
            await this.loadAvailableStatuses();
            
            // Charger tous les onglets au démarrage
            const tabNames = ['nouvelles', 'en-cours', 'en-attente', 'terminees'];
            
            console.log('🔄 Chargement de tous les onglets au démarrage...');
            
            // Charger tous les onglets en parallèle
            await Promise.all(tabNames.map(tabName => this.loadRepairsForTab(tabName)));
            
            console.log('✅ Tous les onglets chargés avec succès');
            
            // Désactiver les glissements dans le tableau
            this.disableDragAndDrop();
            
        } catch (error) {
            console.error('❌ Erreur lors du chargement des données:', error);
            this.showError('Erreur lors du chargement des données');
        }
    }

    disableDragAndDrop() {
        console.log('🚫 Désactivation des glissements dans le tableau...');
        
        // Sélectionner TOUS les éléments du modal qui pourraient être draggables
        const tableElements = document.querySelectorAll('#updateStatusModal *:not(input):not(select):not(button):not(textarea)');
        
        tableElements.forEach(element => {
            // Désactiver complètement le drag & drop
            element.draggable = false;
            element.setAttribute('draggable', 'false');
            element.style.userDrag = 'none';
            element.style.webkitUserDrag = 'none';
            element.style.mozUserDrag = 'none';
            element.style.msUserDrag = 'none';
            
            // Désactiver la sélection de texte
            element.style.userSelect = 'none';
            element.style.webkitUserSelect = 'none';
            element.style.mozUserSelect = 'none';
            element.style.msUserSelect = 'none';
            
            // Événements pour bloquer complètement le drag
            const preventDrag = function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();
                return false;
            };
            
            // Supprimer d'abord les anciens listeners pour éviter les doublons
            element.removeEventListener('dragstart', preventDrag, true);
            element.removeEventListener('drag', preventDrag, true);
            element.removeEventListener('dragenter', preventDrag, true);
            element.removeEventListener('dragover', preventDrag, true);
            element.removeEventListener('dragleave', preventDrag, true);
            element.removeEventListener('drop', preventDrag, true);
            element.removeEventListener('dragend', preventDrag, true);
            element.removeEventListener('selectstart', preventDrag, true);
            element.removeEventListener('mousedown', preventDrag, true);
            
            // Ajouter les nouveaux listeners
            element.addEventListener('dragstart', preventDrag, true);
            element.addEventListener('drag', preventDrag, true);
            element.addEventListener('dragenter', preventDrag, true);
            element.addEventListener('dragover', preventDrag, true);
            element.addEventListener('dragleave', preventDrag, true);
            element.addEventListener('drop', preventDrag, true);
            element.addEventListener('dragend', preventDrag, true);
            element.addEventListener('selectstart', preventDrag, true);
            
            // Empêcher aussi le mousedown sur les éléments du tableau (sauf controls)
            if (element.closest('.modern-table-container') && !element.matches('input, select, button, textarea')) {
                element.addEventListener('mousedown', function(e) {
                    if (!e.target.matches('input, select, button, textarea')) {
                        e.preventDefault();
                    }
                }, true);
            }
        });
        
        console.log('✅ Glissements désactivés pour', tableElements.length, 'éléments');
        
        // Ajouter une protection globale sur le modal
        const modal = document.getElementById('updateStatusModal');
        if (modal) {
            modal.style.userDrag = 'none';
            modal.style.webkitUserDrag = 'none';
            modal.ondragstart = function() { return false; };
            modal.ondrag = function() { return false; };
            modal.ondrop = function() { return false; };
        }
    }

    async loadAvailableStatuses() {
        try {
            const response = await fetch('ajax/get_available_statuses.php');
            const data = await response.json();

            if (data.success) {
                this.statuses = data.statuses;
                this.populateStatusSelect();
            } else {
                throw new Error(data.error || 'Erreur lors du chargement des statuts');
            }
        } catch (error) {
            console.error('❌ Erreur chargement statuts:', error);
            throw error;
        }
    }

    populateStatusSelect() {
        const select = document.getElementById('new-status-select');
        if (!select) return;

        // Ajouter les classes CSS modernes au select
        select.className = 'modern-status-select';

        // Vider les options existantes (sauf la première)
        select.innerHTML = '<option value="">-- Choisir un statut --</option>';

        // Définir les groupes de statuts avec leurs libellés personnalisés
        const statusGroups = {
            'Nouvelle': {
                label: '🆕 Nouvelle',
                statuses: [
                    { label: 'Nouvelle', keywords: ['nouvelle', 'nouveau', 'reparation'] },
                    { label: 'Nouveau diagnostique', keywords: ['diagnostique', 'diagnostic', 'evaluation'] },
                    { label: 'Nouvelle commande', keywords: ['commande', 'order'] }
                ]
            },
            'En attente': {
                label: '⏳ En attente',
                statuses: [
                    { label: 'En attente', keywords: ['attente', 'waiting', 'validation'] },
                    { label: 'En attente de livraison', keywords: ['livraison', 'pieces', 'delivery'] },
                    { label: 'En attente d\'acceptation client', keywords: ['acceptation', 'client', 'devis'] }
                ]
            },
            'Terminer': {
                label: '✅ Terminer',
                statuses: [
                    { label: 'Reparation effectuee', keywords: ['réparation effectuée', 'effectuee', 'effectué', 'terminee', 'finie', 'complete'] },
                    { label: 'Reparation annulee', keywords: ['réparation annulée', 'annulee', 'annulé', 'cancelled', 'abandon'] }
                ]
            },
            'Archiver': {
                label: '📦 Archiver',
                statuses: [
                    { label: 'Restituee', keywords: ['restitué', 'restitue', 'cloturer', 'close', 'fermer'] },
                    { label: 'Cloturer', keywords: ['gardiennage', 'archiver', 'archive', 'stocker'] },
                    { label: 'Archiver', keywords: ['annulé', 'annule', 'cancel'] }
                ]
            }
        };

        // Créer les groupes d'options
        Object.entries(statusGroups).forEach(([groupKey, groupData]) => {
            // Créer le groupe optgroup
            const optgroup = document.createElement('optgroup');
            optgroup.label = groupData.label;
            optgroup.className = 'modern-optgroup';

            // Ajouter les statuts du groupe
            groupData.statuses.forEach(statusConfig => {
                // Trouver le statut correspondant dans la liste des statuts disponibles
                const matchingStatus = this.statuses.find(status => {
                    const statusLower = status.libelle.toLowerCase();
                    // Normaliser les accents et caractères spéciaux
                    const normalizeText = (text) => text.toLowerCase()
                        .replace(/[àáâãäå]/g, 'a')
                        .replace(/[èéêë]/g, 'e')
                        .replace(/[ìíîï]/g, 'i')
                        .replace(/[òóôõö]/g, 'o')
                        .replace(/[ùúûü]/g, 'u')
                        .replace(/[ç]/g, 'c')
                        .replace(/[ñ]/g, 'n')
                        .replace(/\s+/g, ' ')
                        .trim();
                    
                    const normalizedStatus = normalizeText(statusLower);
                    
                    return statusConfig.keywords.some(keyword => {
                        const normalizedKeyword = normalizeText(keyword.toLowerCase());
                        return normalizedStatus.includes(normalizedKeyword) ||
                               normalizedKeyword.includes(normalizedStatus) ||
                               // Correspondance exacte sans accents
                               normalizedStatus === normalizedKeyword;
                    });
                });

                if (matchingStatus) {
            const option = document.createElement('option');
                    option.value = matchingStatus.code;
                    option.textContent = statusConfig.label;
                    option.className = 'modern-option';
                    option.setAttribute('data-group', groupKey.toLowerCase());
                    // Utiliser la couleur du statut ou une couleur par défaut
                    if (matchingStatus.couleur && matchingStatus.couleur !== '#000000') {
                        option.style.color = matchingStatus.couleur;
                    }
                    optgroup.appendChild(option);
                    
                    // Debug log pour voir les correspondances
                    console.log(`✅ Correspondance trouvée: "${matchingStatus.libelle}" -> "${statusConfig.label}"`);
                } else {
                    // Debug log pour voir les échecs
                    console.log(`❌ Aucune correspondance pour: "${statusConfig.label}" avec keywords:`, statusConfig.keywords);
                    console.log('📋 Statuts disponibles:', this.statuses.map(s => s.libelle));
                }
            });

            // Ajouter le groupe au select seulement s'il contient des options
            if (optgroup.children.length > 0) {
                select.appendChild(optgroup);
            }
        });

        // Appliquer le style moderne au select après population
        this.applyModernSelectStyles(select);
    }

    applyModernSelectStyles(select) {
        // Ajouter des styles dynamiques si pas déjà présents
        if (!document.getElementById('modern-status-select-styles')) {
            const styleSheet = document.createElement('style');
            styleSheet.id = 'modern-status-select-styles';
            styleSheet.innerHTML = `
                /* Amélioration générale du modal */
                #updateStatusModal .modal-content {
                    border-radius: 20px;
                    border: none;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                    overflow: hidden;
                }

                #updateStatusModal .modal-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                    padding: 24px 30px;
                }

                #updateStatusModal .modal-body {
                    padding: 30px;
                    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
                }

                #updateStatusModal .modal-footer-modern {
                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                    border-top: 1px solid #e2e8f0;
                    padding: 24px 30px;
                }

                /* Amélioration du footer */
                .footer-controls {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 24px;
                    flex-wrap: wrap;
                }

                .status-selector {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    flex: 1;
                    min-width: 280px;
                }

                .status-selector label {
                    font-weight: 600;
                    color: #4a5568;
                    font-size: 14px;
                    margin: 0;
                }

                .sms-toggle {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    flex-shrink: 0;
                }

                .action-buttons {
                    display: flex;
                    gap: 12px;
                    flex-shrink: 0;
                }

                /* Select moderne amélioré */
                .modern-status-select {
                    background-color: #ffffff;
                    border: 2px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 14px 18px;
                    font-size: 15px;
                    font-weight: 500;
                    color: #2d3748;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    width: 100%;
                    appearance: none;
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
                    background-position: right 16px center;
                    background-repeat: no-repeat;
                    background-size: 18px;
                    padding-right: 50px;
                    min-height: 52px;
                }

                .modern-status-select:focus {
                    outline: none;
                    border-color: #667eea;
                    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    background: #ffffff;
                    transform: translateY(-1px);
                }

                .modern-status-select:hover {
                    border-color: #cbd5e0;
                    box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.1), 0 4px 8px -2px rgba(0, 0, 0, 0.06);
                    transform: translateY(-1px);
                }

                /* Amélioration des optgroups */
                .modern-optgroup {
                    font-weight: 700;
                    font-size: 12px;
                    color: #4a5568;
                    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
                    padding: 12px 16px;
                    margin: 6px 0;
                    border-radius: 8px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    border-left: 4px solid #667eea;
                }

                .modern-option {
                    padding: 14px 20px;
                    font-size: 14px;
                    font-weight: 500;
                    color: #2d3748;
                    background-color: #ffffff;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    border-left: 4px solid transparent;
                    margin: 2px 0;
                }

                .modern-option:hover {
                    background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
                    border-left-color: #667eea;
                    padding-left: 24px;
                    transform: translateX(4px);
                }

                .modern-option[data-group="nouvelle"] {
                    border-left-color: #48bb78;
                }

                .modern-option[data-group="nouvelle"]:hover {
                    border-left-color: #38a169;
                    background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
                }

                .modern-option[data-group="en attente"] {
                    border-left-color: #ed8936;
                }

                .modern-option[data-group="en attente"]:hover {
                    border-left-color: #dd6b20;
                    background: linear-gradient(135deg, #fffaf0 0%, #fbd38d 100%);
                }

                .modern-option[data-group="terminer"] {
                    border-left-color: #4299e1;
                }

                .modern-option[data-group="terminer"]:hover {
                    border-left-color: #3182ce;
                    background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%);
                }

                .modern-option[data-group="archiver"] {
                    border-left-color: #9f7aea;
                }

                .modern-option[data-group="archiver"]:hover {
                    border-left-color: #805ad5;
                    background: linear-gradient(135deg, #faf5ff 0%, #e9d8fd 100%);
                }

                /* Amélioration du toggle SMS */
                .modern-switch {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    cursor: pointer;
                    user-select: none;
                    padding: 8px 16px;
                    border-radius: 12px;
                    background: linear-gradient(135deg, #f7fafc 0%, #ffffff 100%);
                    border: 2px solid #e2e8f0;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }

                .modern-switch:hover {
                    border-color: #cbd5e0;
                    box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1);
                    transform: translateY(-1px);
                }

                .modern-switch input[type="checkbox"] {
                    display: none;
                }

                .switch-slider {
                    width: 48px;
                    height: 24px;
                    background: #e2e8f0;
                    border-radius: 24px;
                    position: relative;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .switch-slider::before {
                    content: '';
                    position: absolute;
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    background: #ffffff;
                    top: 2px;
                    left: 2px;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                }

                .modern-switch input[type="checkbox"]:checked + .switch-slider {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }

                .modern-switch input[type="checkbox"]:checked + .switch-slider::before {
                    transform: translateX(24px);
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
                }

                .switch-label {
                    font-weight: 600;
                    color: #4a5568;
                    font-size: 14px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .switch-label i {
                    color: #667eea;
                    font-size: 16px;
                }

                /* Mode sombre - texte SMS en noir */
                @media (prefers-color-scheme: dark) {
                    .switch-label {
                        color: #000000 !important;
                    }
                }

                /* Classe pour forcer le mode sombre */
                .dark-mode .switch-label,
                [data-theme="dark"] .switch-label,
                body.dark .switch-label,
                .dark .switch-label {
                    color: #000000 !important;
                }

                /* Mode sombre - compteur de sélection en noir */
                @media (prefers-color-scheme: dark) {
                    #selected-count {
                        color: #000000 !important;
                    }
                }

                /* Classe pour forcer le mode sombre - compteur */
                .dark-mode #selected-count,
                [data-theme="dark"] #selected-count,
                body.dark #selected-count,
                .dark #selected-count {
                    color: #000000 !important;
                }

                /* Amélioration des boutons d'action */
                .modern-btn {
                    padding: 12px 24px;
                    border-radius: 12px;
                    font-weight: 600;
                    font-size: 14px;
                    border: 2px solid transparent;
                    cursor: pointer;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    min-height: 48px;
                }

                .modern-btn.primary {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
                }

                .modern-btn.primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
                }

                .modern-btn.secondary {
                    background: #ffffff;
                    color: #4a5568;
                    border-color: #e2e8f0;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .modern-btn.secondary:hover {
                    border-color: #cbd5e0;
                    background: #f7fafc;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                }

                /* Style pour les navigateurs WebKit */
                .modern-status-select::-webkit-scrollbar {
                    width: 8px;
                }

                .modern-status-select::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 4px;
                }

                .modern-status-select::-webkit-scrollbar-thumb {
                    background: #cbd5e0;
                    border-radius: 4px;
                }

                .modern-status-select::-webkit-scrollbar-thumb:hover {
                    background: #a0aec0;
                }

                /* Animation d'ouverture */
                @keyframes selectOpen {
                    from {
                        opacity: 0;
                        transform: translateY(-4px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .modern-status-select[aria-expanded="true"] {
                    animation: selectOpen 0.2s ease-out;
                }

                /* Compteur de sélection */
                #selected-count {
                    font-weight: 600;
                    color: #667eea;
                    background: linear-gradient(135deg, #edf2f7 0%, #ffffff 100%);
                    padding: 8px 16px;
                    border-radius: 8px;
                    border: 2px solid #e2e8f0;
                    font-size: 14px;
                    margin: 16px 0;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .footer-controls {
                        flex-direction: column;
                        align-items: stretch;
                        gap: 16px;
                    }

                    .status-selector {
                        min-width: auto;
                    }

                    .modern-status-select {
                        font-size: 16px; /* Évite le zoom sur iOS */
                        padding: 16px 18px;
                        padding-right: 50px;
                    }

                    .action-buttons {
                        justify-content: stretch;
                    }

                    .modern-btn {
                        flex: 1;
                        justify-content: center;
                    }

                    .sms-toggle {
                        justify-content: center;
                    }
                }

                @media (max-width: 480px) {
                    #updateStatusModal .modal-body,
                    #updateStatusModal .modal-footer-modern {
                        padding: 20px;
                    }

                    #updateStatusModal .modal-header {
                        padding: 20px;
                    }

                    .action-buttons {
                        flex-direction: column;
                    }

                    .modern-btn {
                        width: 100%;
                    }
                }
            `;
            document.head.appendChild(styleSheet);
        }
    }

    async loadRepairsForTab(tabName) {
        console.log(`🔄 Chargement des réparations pour l'onglet: ${tabName}`);
        
        const tbody = document.getElementById(`repairs-${tabName}`);
        if (!tbody) return;

        // Afficher le chargement
        tbody.innerHTML = `
            <div class="loading-row">
                <div class="loading-spinner"></div>
                <span>Chargement des réparations...</span>
            </div>
        `;

        try {
            const response = await fetch(`ajax/get_repairs_by_status.php?status=${tabName}`);
            const data = await response.json();

            console.log(`📊 Réponse API pour ${tabName}:`, data);

            if (data.success) {
                console.log(`✅ ${data.repairs.length} réparations trouvées pour ${tabName}`);
                this.repairs[tabName] = data.repairs;
                this.renderRepairsTable(tabName, data.repairs);
                this.updateTabCount(tabName, data.count);
            } else {
                console.error(`❌ Erreur API pour ${tabName}:`, data.error);
                throw new Error(data.error || 'Erreur lors du chargement');
            }
        } catch (error) {
            console.error(`❌ Erreur chargement ${tabName}:`, error);
            tbody.innerHTML = `
                <div class="empty-row">
                    <span style="color: #ef4444;">Erreur: ${error.message}</span>
                </div>
            `;
        }
    }

    renderRepairsTable(tabName, repairs) {
        console.log(`🎨 Rendu du tableau pour ${tabName} avec ${repairs.length} réparations`);
        
        const tbody = document.getElementById(`repairs-${tabName}`);
        if (!tbody) {
            console.error(`❌ Élément tbody non trouvé: repairs-${tabName}`);
            return;
        }

        if (repairs.length === 0) {
            console.log(`⚠️ Aucune réparation pour ${tabName}`);
            tbody.innerHTML = `
                <div class="empty-row">
                    <span>Aucune réparation trouvée</span>
                </div>
            `;
            return;
        }

        console.log(`📋 Génération du HTML pour ${repairs.length} réparations`);
        let html = '';
        repairs.forEach(repair => {
            const isSelected = this.selectedRepairs.has(repair.id);
            const phoneIcon = repair.has_phone ? '<i class="fas fa-phone" style="color: #059669; margin-left: 8px;" title="Téléphone disponible"></i>' : '';
            
            html += `
                <div class="table-row ${isSelected ? 'selected' : ''}" data-repair-id="${repair.id}">
                    <div class="table-cell checkbox-cell">
                        <input type="checkbox" class="modern-checkbox repair-checkbox" 
                               value="${repair.id}" ${isSelected ? 'checked' : ''}>
                    </div>
                    <div class="table-cell">${repair.client}${phoneIcon}</div>
                    <div class="table-cell">${repair.modele}</div>
                    <div class="table-cell" title="${repair.probleme}">
                        ${repair.probleme.length > 50 ? repair.probleme.substring(0, 50) + '...' : repair.probleme}
                    </div>
                    <div class="table-cell price-cell">${repair.prix}</div>
                    <div class="table-cell">
                        <span class="status-badge">
                            ${repair.statut}
                        </span>
                    </div>
                </div>
            `;
        });

        console.log(`🔧 HTML généré (${html.length} caractères):`, html.substring(0, 200) + '...');
        tbody.innerHTML = html;
        console.log(`✅ HTML injecté dans l'élément:`, tbody);
        
        // Désactiver les glissements pour les nouveaux éléments
        this.disableDragAndDrop();
    }

    updateTabCount(tabName, count) {
        const badge = document.getElementById(`count-${tabName}`);
        if (badge) {
            badge.textContent = count;
        }
    }

    handleRepairSelection(checkbox) {
        const repairId = parseInt(checkbox.value);
        const row = checkbox.closest('.table-row');
        
        if (checkbox.checked) {
            this.selectedRepairs.add(repairId);
            row?.classList.add('selected');
        } else {
            this.selectedRepairs.delete(repairId);
            row?.classList.remove('selected');
        }

        this.updateSelectedCount();
        this.updateSelectAllCheckboxes();
    }

    handleSelectAllTab(checkbox) {
        const tabName = checkbox.id.replace('select-all-', '');
        const repairCheckboxes = document.querySelectorAll(`#repairs-${tabName} .repair-checkbox`);
        
        repairCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
            this.handleRepairSelection(cb);
        });
    }

    selectAllVisible() {
        const visibleCheckboxes = document.querySelectorAll(`#repairs-${this.currentTab} .repair-checkbox`);
        visibleCheckboxes.forEach(cb => {
            cb.checked = true;
            this.handleRepairSelection(cb);
        });
    }

    deselectAll() {
        // Désélectionner tous les checkboxes
        document.querySelectorAll('.repair-checkbox').forEach(cb => {
            cb.checked = false;
            this.handleRepairSelection(cb);
        });

        // Désélectionner les checkboxes "select-all"
        document.querySelectorAll('[id^="select-all-"]').forEach(cb => {
            cb.checked = false;
        });
    }

    updateSelectedCount() {
        const count = this.selectedRepairs.size;
        const countElement = document.getElementById('selected-count');
        if (countElement) {
            countElement.textContent = `${count} réparation(s) sélectionnée(s)`;
        }

        // Activer/désactiver le bouton de mise à jour
        const updateBtn = document.getElementById('update-selected-repairs');
        if (updateBtn) {
            updateBtn.disabled = count === 0;
        }
    }

    updateSelectAllCheckboxes() {
        // Mettre à jour les checkboxes "select-all" pour chaque onglet
        ['nouvelles', 'en-cours', 'en-attente', 'terminees'].forEach(tabName => {
            const selectAllCheckbox = document.getElementById(`select-all-${tabName}`);
            const repairCheckboxes = document.querySelectorAll(`#repairs-${tabName} .repair-checkbox`);
            
            if (selectAllCheckbox && repairCheckboxes.length > 0) {
                const checkedCount = document.querySelectorAll(`#repairs-${tabName} .repair-checkbox:checked`).length;
                selectAllCheckbox.checked = checkedCount === repairCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < repairCheckboxes.length;
            }
        });
    }

    async updateSelectedRepairs() {
        const selectedIds = Array.from(this.selectedRepairs);
        const newStatus = document.getElementById('new-status-select').value;
        const sendSms = document.getElementById('send-sms-checkbox').checked;

        // Validations
        if (selectedIds.length === 0) {
            this.showError('Veuillez sélectionner au moins une réparation');
            return;
        }

        if (!newStatus) {
            this.showError('Veuillez choisir un nouveau statut');
            return;
        }

        // Confirmation
        const statusLabel = this.statuses.find(s => s.code === newStatus)?.libelle || newStatus;
        const smsText = sendSms ? ' avec envoi de SMS' : '';
        const message = `Êtes-vous sûr de vouloir mettre à jour ${selectedIds.length} réparation(s) vers "${statusLabel}"${smsText} ?`;
        
        if (!confirm(message)) {
            return;
        }

        // Désactiver le bouton pendant le traitement
        const updateBtn = document.getElementById('update-selected-repairs');
        const originalText = updateBtn.innerHTML;
        updateBtn.disabled = true;
        updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mise à jour...';

        try {
            const response = await fetch('ajax/update_batch_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    repair_ids: selectedIds,
                    new_status: newStatus,
                    send_sms: sendSms
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                
                // Réinitialiser les sélections
                this.selectedRepairs.clear();
                this.updateSelectedCount();
                
                // Recharger les données
                await this.loadData();
                
                // Fermer le modal après un délai
                setTimeout(() => {
                    const modalInstance = bootstrap.Modal.getInstance(this.modal);
                    modalInstance?.hide();
                }, 2000);
                
            } else {
                throw new Error(data.error || 'Erreur lors de la mise à jour');
            }

        } catch (error) {
            console.error('❌ Erreur mise à jour:', error);
            this.showError('Erreur lors de la mise à jour: ' + error.message);
        } finally {
            // Réactiver le bouton
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalText;
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'danger');
    }

    showNotification(message, type = 'info') {
        // Créer une notification Bootstrap
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Ajouter au début du modal body
        const modalBody = this.modal.querySelector('.modal-body');
        modalBody.insertBefore(alertDiv, modalBody.firstChild);

        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

}

// Initialiser le modal au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation du modal de mise à jour des statuts...');
    window.updateStatusModal = new UpdateStatusModal();
});
