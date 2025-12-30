/**
 * KPI Dashboard JavaScript - GeekBoard
 * Gestion complète du dashboard avec appels API et affichage dynamique
 */
console.log("🚀 KPI Dashboard script loaded!");

class KPIDashboard {
    constructor() {
        this.currentFilters = {
            employee_id: '',
            date_start: '',
            date_end: ''
        };

        this.charts = {};
        this.init();
    }

    init() {
        // Initialiser les filtres
        this.setupFilters();

        // Charger les données initiales
        this.loadDashboard();

        // Event listeners
        $('#btnRefresh').on('click', () => this.loadDashboard());

        // Event listener pour l'onglet Profils IA
        $('#profils-ia-tab').on('click', () => {
            console.log('🎯 Onglet Profils IA cliqué, chargement...');
            this.loadAIProfiles();
        });

        // Charger profils IA si l'onglet est déjà actif
        if ($('#profils-ia').hasClass('active')) {
            this.loadAIProfiles();
        }
    }

    setupFilters() {
        this.currentFilters.date_start = $('#filterDateStart').val();
        this.currentFilters.date_end = $('#filterDateEnd').val();
        this.currentFilters.employee_id = $('#filterEmployee').val() || '';
    }

    showLoading() {
        $('#loadingOverlay').addClass('show');
    }

    hideLoading() {
        $('#loadingOverlay').removeClass('show');
    }

    async loadDashboard() {
        this.setupFilters();
        this.showLoading();

        try {
            // Charger toutes les données en parallèle
            const [caGlobal, caEmploye, kpiRep, gardiennage, panierMoyen] = await Promise.all([
                this.fetchAPI('chiffre_affaires_global'),
                this.fetchAPI('chiffre_affaires_employe'),
                this.fetchAPI('kpi_reparations'),
                this.fetchAPI('analyse_gardiennage'),
                this.fetchAPI('panier_moyen')
            ]);

            // Afficher les KPI cards
            this.renderKPICards(caGlobal, kpiRep, gardiennage);

            // Afficher les graphiques
            this.renderCharts(panierMoyen, kpiRep);

            // Afficher tableau employés
            this.renderEmployeeTable(caEmploye);

            // Charger les profils IA pour l'accordéon
            this.loadAIProfilesAccordion();

        } catch (error) {
            console.error('Erreur chargement dashboard:', error);

            // Afficher un message d'erreur à l'utilisateur
            $('#kpiCardsContainer').html(`
                <div class="col-12">
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Erreur de chargement</h5>
                        <p class="mb-0">${error.message || 'Impossible de charger les données KPI'}</p>
                        <button class="btn btn-sm btn-outline-danger mt-2" onclick="location.reload()">
                            <i class="fas fa-sync me-1"></i>Recharger
                        </button>
                    </div>
                </div>
            `);
        } finally {
            this.hideLoading();
        }
    }

    async fetchAPI(endpoint, extraParams = {}) {
        try {
            const params = new URLSearchParams({
                action: endpoint,
                date_start: this.currentFilters.date_start || '2025-11-01',
                date_end: this.currentFilters.date_end || '2025-12-01',
                ...(this.currentFilters.employee_id && { user_id: this.currentFilters.employee_id }),
                ...extraParams  // Ajouter les paramètres supplémentaires
            });

            const url = `/ajax/kpi_api.php?${params}`;

            console.log(`🔍 Fetching: ${url}`);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${await response.text()}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Erreur API');
            }

            return data.data;

        } catch (error) {
            console.error(`Erreur API ${endpoint}:`, error);
            throw error;
        }
    }

    renderKPICards(caGlobal, kpiRep, gardiennage) {
        const html = `
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-label">
                        <i class="fas fa-euro-sign text-success me-1"></i> CA Encaissé
                    </div>
                    <div class="kpi-value">${this.formatCurrency(caGlobal.ca_encaisse)}</div>
                    <small class="text-muted">${caGlobal.nb_restituees} réparations</small>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-label">
                        <i class="fas fa-chart-line text-primary me-1"></i> CA Total
                    </div>
                    <div class="kpi-value">${this.formatCurrency(caGlobal.ca_total)}</div>
                    <small class="text-muted">${caGlobal.nb_total} réparations</small>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-label">
                        <i class="fas fa-wrench text-info me-1"></i> Réparations
                    </div>
                    <div class="kpi-value">${kpiRep.global.nb_effectuees}</div>
                    <small class="text-muted">${kpiRep.global.nb_nouvelles} nouvelles</small>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-label">
                        <i class="fas fa-warehouse text-warning me-1"></i> Gardiennage
                    </div>
                    <div class="kpi-value">${gardiennage.actifs.nb_appareils_actifs}</div>
                    <small class="text-muted">${this.formatCurrency(gardiennage.actifs.cout_total_actif)}</small>
                </div>
            </div>
        `;

        $('#kpiCardsContainer').html(html);
    }

    renderCharts(panierMoyen, kpiRep) {
        // Graphique CA
        const ctxCA = document.getElementById('chartCA');
        if (this.charts.ca) this.charts.ca.destroy();

        this.charts.ca = new Chart(ctxCA, {
            type: 'line',
            data: {
                labels: panierMoyen.map(d => d.mois),
                datasets: [{
                    label: 'Panier Moyen',
                    data: panierMoyen.map(d => parseFloat(d.panier_moyen)),
                    borderColor: '#0078e8',
                    backgroundColor: 'rgba(0, 120, 232, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => value + ' €'
                        }
                    }
                }
            }
        });

        // Graphique Réparations
        const ctxRep = document.getElementById('chartReparations');
        if (this.charts.rep) this.charts.rep.destroy();

        const repData = kpiRep.global;
        this.charts.rep = new Chart(ctxRep, {
            type: 'doughnut',
            data: {
                labels: ['Nouvelles', 'En cours', 'Effectuées', 'Restituées'],
                datasets: [{
                    data: [
                        repData.nb_nouvelles,
                        repData.nb_en_cours,
                        repData.nb_effectuees,
                        repData.nb_restituees
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#17a2b8',
                        '#28a745',
                        '#0078e8'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    renderEmployeeTable(caEmploye) {
        if (!caEmploye || caEmploye.length === 0) {
            $('#tableEmployees tbody').html('<tr><td colspan="6" class="text-center text-muted">Aucune donnée</td></tr>');
            return;
        }

        const rows = caEmploye.map(emp => `
            <tr>
                <td><strong>${this.escapeHtml(emp.employe_nom)}</strong></td>
                <td>${emp.nb_total}</td>
                <td class="text-success fw-bold">${this.formatCurrency(emp.ca_encaisse)}</td>
                <td class="text-primary fw-bold">${this.formatCurrency(emp.ca_total)}</td>
                <td>${this.formatCurrency(emp.panier_moyen_total)}</td>
                <td>
                    <span class="badge bg-info">-</span>
                </td>
            </tr>
        `).join('');

        $('#tableEmployees tbody').html(rows);
    }

    async loadAIProfilesAccordion() {
        try {
            // Récupérer les profils actifs
            const response = await fetch('../ajax/get_ai_profiles.php');
            const data = await response.json();

            if (!data.success) return;

            const profiles = data.data || [];
            const accordion = profiles.map((profile, index) => `
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse${profile.id}">
                            <i class="${profile.icon} me-2"></i>
                            ${this.escapeHtml(profile.name)}
                        </button>
                    </h2>
                    <div id="collapse${profile.id}" class="accordion-collapse collapse" 
                         data-bs-parent="#accordionIA">
                        <div class="accordion-body">
                            <div class="ai-analysis-content" id="ai-content-${profile.id}">
                                <div class="text-center text-muted">
                                    <button class="btn btn-outline-primary btn-sm" 
                                            onclick="dashboard.generateAIAnalysis(${profile.id})">
                                        <i class="fas fa-magic me-2"></i>Générer l'analyse
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');

            $('#accordionIA').html(accordion);

        } catch (error) {
            console.error('Erreur chargement profils IA:', error);
        }
    }

    // Fonction pour générer un avatar IA futuriste en SVG
    generateAIAvatar(icon, name, id) {
        const colors = {
            1: { primary: '#0078e8', secondary: '#00d4ff', glow: 'rgba(0,120,232,0.5)' }, // Expert Gestion
            2: { primary: '#28a745', secondary: '#00ff88', glow: 'rgba(40,167,69,0.5)' }, // Expert Ventes
            3: { primary: '#6f42c1', secondary: '#b794f6', glow: 'rgba(111,66,193,0.5)' }, // Expert Comptable
            4: { primary: '#17a2b8', secondary: '#00e5ff', glow: 'rgba(23,162,184,0.5)' }, // Manager Constructif
            5: { primary: '#ffc107', secondary: '#ffeb3b', glow: 'rgba(255,193,7,0.5)' }, // Coach Motivant
            6: { primary: '#dc3545', secondary: '#ff6b7a', glow: 'rgba(220,53,69,0.5)' }, // Manager Critique
            7: { primary: '#6610f2', secondary: '#a78bfa', glow: 'rgba(102,16,242,0.5)' }, // Directeur
            8: { primary: '#e91e63', secondary: '#ff4dd2', glow: 'rgba(233,30,99,0.5)' }  // Analyste Comportemental
        };

        const color = colors[id] || colors[1];
        const initials = name.split(' ').map(w => w[0]).join('').substring(0, 2);

        return `
            <svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="grad-${id}" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:${color.primary};stop-opacity:1" />
                        <stop offset="100%" style="stop-color:${color.secondary};stop-opacity:1" />
                    </linearGradient>
                    <filter id="glow-${id}">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>
                
                <!-- Outer glow circle -->
                <circle cx="50" cy="50" r="48" fill="none" stroke="url(#grad-${id})" stroke-width="2" opacity="0.3">
                    <animate attributeName="r" values="48;50;48" dur="2s" repeatCount="indefinite"/>
                </circle>
                
                <!-- Main circle with gradient -->
                <circle cx="50" cy="50" r="42" fill="url(#grad-${id})" filter="url(#glow-${id})"/>
                
                <!-- Icon or initials -->
                <text x="50" y="50" font-family="Arial, sans-serif" font-size="20" font-weight="bold" 
                      text-anchor="middle" dominant-baseline="central" fill="white" opacity="0.9">
                    ${initials}
                </text>
                
                <!-- Rotating ring -->
                <circle cx="50" cy="50" r="45" fill="none" stroke="url(#grad-${id})" stroke-width="1" 
                        stroke-dasharray="10 5" opacity="0.5">
                    <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" 
                                      dur="8s" repeatCount="indefinite"/>
                </circle>
            </svg>
        `;
    }

    async loadAIProfiles() {
        try {
            const response = await fetch('/ajax/get_ai_profiles.php');
            const data = await response.json();

            if (!data.success) {
                $('#profilesContainer').html('<div class="alert alert-danger">Erreur chargement profils IA</div>');
                return;
            }

            const profiles = data.data || [];

            if (profiles.length === 0) {
                $('#profilesContainer').html('<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun profil IA configuré</div>');
                return;
            }

            // Générer le HTML avec design futuriste
            const profilesHtml = profiles.map(profile => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="ai-profile-card" data-profile-id="${profile.id}">
                        <div class="ai-profile-header">
                            <div class="ai-avatar-container">
                                ${this.generateAIAvatar(profile.icon, profile.name, profile.id)}
                            </div>
                            <div class="ai-profile-status ${profile.active ? 'active' : 'inactive'}">
                                <span class="status-dot"></span>
                                ${profile.active ? 'ACTIF' : 'INACTIF'}
                            </div>
                        </div>
                        <div class="ai-profile-body">
                            <h5 class="ai-profile-name">
                                <i class="${profile.icon} me-2"></i>
                                ${this.escapeHtml(profile.name)}
                            </h5>
                            <p class="ai-profile-description">${this.escapeHtml(profile.description || '')}</p>
                        </div>
                        <div class="ai-profile-footer">
                            <button class="btn-ai-action btn-analyze-profile" 
                                    data-profile-id="${profile.id}"
                                    data-profile-name="${this.escapeHtml(profile.name)}"
                                    data-profile-desc="${this.escapeHtml(profile.description || '')}">
                                <i class="fas fa-magic me-2"></i>
                                Analyser
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');

            $('#profilesContainer').html(`
                <div class="row">
                    ${profilesHtml}
                </div>
            `);

            // Event delegation pour les boutons d'analyse des profils IA
            $(document).on('click', '.btn-analyze-profile', function () {
                const profileId = $(this).data('profile-id');
                const profileName = $(this).data('profile-name');
                const profileDescription = $(this).data('profile-description');

                console.log(`🤖 Profil cliqué #${profileId}: ${profileName}`);

                // Ouvrir directement le modal d'analyse
                openAIAnalysisModal(profileId, profileName, profileDescription);
            });

            console.log(`✅ ${profiles.length} profils IA affichés avec avatars futuristes`);

        } catch (error) {
            console.error('Erreur chargement liste profils IA:', error);
            $('#profilesContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur chargement profils IA</div>');
        }
    }

    async generateAIAnalysis(profileId) {
        const container = $(`#ai-content-${profileId}`);
        container.html('<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Génération en cours...</div>');

        try {
            // Récupérer toutes les données KPI
            const kpiData = {
                ca_global: await this.fetchAPI('chiffre_affaires_global'),
                ca_employe: await this.fetchAPI('chiffre_affaires_employe'),
                kpi_reparations: await this.fetchAPI('kpi_reparations'),
                gardiennage: await this.fetchAPI('analyse_gardiennage')
            };

            // Appeler l'API d'analyse IA
            const response = await fetch('../ajax/generate_ai_analysis.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    profile_id: profileId,
                    kpi_data: kpiData,
                    employee_id: this.currentFilters.employee_id,
                    date_start: this.currentFilters.date_start,
                    date_end: this.currentFilters.date_end
                })
            });

            const data = await response.json();

            if (data.success) {
                const formatted = this.formatAIAnalysis(data.data.analysis);
                container.html(formatted);
            } else {
                container.html(`<div class="alert alert-danger">${data.error}</div>`);
            }

        } catch (error) {
            console.error('Erreur génération IA:', error);
            container.html('<div class="alert alert-danger">Erreur lors de la génération</div>');
        }
    }

    formatAIAnalysis(text) {
        if (!text) return '<p class="text-muted">Analyse non disponible</p>';

        // Mapping des icônes selon le contenu du titre
        const iconMap = {
            'dynamique': 'fa-users',
            'comportement': 'fa-brain',
            'analyse': 'fa-chart-line',
            'recommandation': 'fa-lightbulb',
            'observation': 'fa-eye',
            'conclusion': 'fa-check-circle',
            'synthèse': 'fa-file-alt',
            'diagnostic': 'fa-stethoscope',
            'performance': 'fa-tachometer-alt',
            'point': 'fa-exclamation-circle',
            'action': 'fa-tasks',
            'risque': 'fa-exclamation-triangle',
            'opportunité': 'fa-star',
            'alerte': 'fa-bell',
            'force': 'fa-thumbs-up',
            'faiblesse': 'fa-thumbs-down'
        };

        // Fonction pour détecter l'icône selon le titre
        const getIconForTitle = (title) => {
            const lowerTitle = title.toLowerCase();
            for (const [keyword, icon] of Object.entries(iconMap)) {
                if (lowerTitle.includes(keyword)) return icon;
            }
            return 'fa-info-circle'; // Icône par défaut
        };

        // Fonction pour obtenir une couleur selon l'index de section
        const getSectionColor = (index) => {
            const colors = ['primary', 'success', 'warning', 'info', 'purple', 'teal'];
            return colors[index % colors.length];
        };

        let html = '';
        const lines = text.split('\n');
        let currentSection = null;
        let sectionIndex = 0;
        let inList = false;
        let listItems = [];

        const closeList = () => {
            if (inList && listItems.length > 0) {
                html += '<ul class="ai-list">' + listItems.join('') + '</ul>';
                listItems = [];
                inList = false;
            }
        };

        const closeSection = () => {
            closeList();
            if (currentSection) {
                html += '</div></div>'; // Fermer section-content et section
                currentSection = null;
            }
        };

        lines.forEach((line, idx) => {
            line = line.trim();
            if (!line) return;

            // Détecter titre principal (# Titre ou Titre avec numéro au début)
            const h1Match = line.match(/^#\s+(.+)$/);
            const numberedTitleMatch = line.match(/^(\d+)\.\s*(.+)$/);

            if (h1Match || (numberedTitleMatch && line.length < 60 && !line.includes(':'))) {
                closeSection();

                const titleText = h1Match ? h1Match[1] : numberedTitleMatch[2];
                const icon = getIconForTitle(titleText);
                const color = getSectionColor(sectionIndex);

                html += `
                    <div class="ai-section ai-section-${color}">
                        <div class="ai-section-header">
                            <i class="fas ${icon}"></i>
                            <h4>${titleText}</h4>
                        </div>
                        <div class="ai-section-content">
                `;

                currentSection = titleText;
                sectionIndex++;
                return;
            }

            // Détecter sous-titre (## ou lignes courtes en gras)
            const h2Match = line.match(/^##\s+(.+)$/);
            if (h2Match) {
                closeList();
                html += `<h5 class="ai-subtitle"><i class="fas fa-angle-right"></i> ${h2Match[1]}</h5>`;
                return;
            }

            // Détecter liste (-, *, •, ou numéro suivi de texte court)
            const listMatch = line.match(/^[-*•]\s+(.+)$/);
            const numberedListMatch = line.match(/^(\d+)\.\s+(.+)$/);

            if (listMatch || (numberedListMatch && line.length >= 60)) {
                inList = true;
                const content = listMatch ? listMatch[1] : numberedListMatch[2];
                const formatted = content
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>');
                listItems.push(`<li>${formatted}</li>`);
                return;
            }

            // Texte normal
            closeList();

            // Formater le texte (gras, italique)
            let formatted = line
                .replace(/\*\*(.+?)\*\*/g, '<strong class="text-primary">$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/`(.+?)`/g, '<code>$1</code>');

            // Si pas de section ouverte, créer une section par défaut
            if (!currentSection && idx === 0) {
                const icon = 'fa-file-alt';
                const color = getSectionColor(sectionIndex);
                html += `
                    <div class="ai-section ai-section-${color}">
                        <div class="ai-section-header">
                            <i class="fas ${icon}"></i>
                            <h4>Analyse</h4>
                        </div>
                        <div class="ai-section-content">
                `;
                currentSection = 'default';
                sectionIndex++;
            }

            html += `<p>${formatted}</p>`;
        });

        closeSection();

        return html;
    }



    // Utilitaires
    formatCurrency(value) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(value || 0);
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return (text || '').replace(/[&<>"']/g, m => map[m]);
    }

    showError(message) {
        // TODO: Afficher une notification d'erreur
        console.error(message);
    }
}

// Initialisation
let dashboard;
$(document).ready(function () {
    dashboard = new KPIDashboard();
});

//============================================================================
// GESTION DU MODAL INFO PROFIL IA
//============================================================================

let currentProfileData = null;

function openProfileInfoModal(profileId, profileName, profileDescription) {
    console.log(`ℹ️ Ouverture modal info profil #${profileId}: ${profileName}`);

    // Stocker les données du profil
    currentProfileData = {
        id: profileId,
        name: profileName,
        description: profileDescription
    };

    // Récupérer les infos complètes du profil depuis l'API
    fetch(`/ajax/ai_profiles_api.php?action=get_profile&id=${profileId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const profile = data.data;

                // Remplir le modal
                $('#profileInfoName').text(profile.profile_name);
                $('#profileInfoDescription').text(profile.description);
                $('#profileInfoExpertise').text(profile.expertise || 'Non spécifié');
                $('#profileInfoDate').text(new Date(profile.created_at).toLocaleDateString('fr-FR'));

                // Statut
                const statusBadge = profile.is_active == 1
                    ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Actif</span>'
                    : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Inactif</span>';
                $('#profileInfoStatus').html(statusBadge);

                // Générer l'avatar large
                const colors = ['#0078e8', '#00d4ff', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#ef4444', '#06b6d4'];
                const color = colors[profileId % colors.length];
                const avatarHtml = dashboard.generateAIAvatar(profile.profile_name, color);
                $('#profileInfoAvatar').html(avatarHtml);

                // Ouvrir le modal
                const modal = new bootstrap.Modal(document.getElementById('profileInfoModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Erreur chargement profil:', error);
            alert('Erreur lors du chargement du profil');
        });
}

function editAIProfile() {
    if (!currentProfileData) return;

    // Fermer le modal d'info
    bootstrap.Modal.getInstance(document.getElementById('profileInfoModal')).hide();

    // Ouvrir le modal de modification (à créer ou rediriger vers page d'édition)
    console.log('🖊️ Modification du profil #' + currentProfileData.id);

    // Pour l'instant, redirection simple (à adapter selon votre système)
    window.location.href = `/pages/ai_profiles.php?edit=${currentProfileData.id}`;
}

function analyzeFromProfileInfo() {
    if (!currentProfileData) return;

    // Fermer le modal d'info
    bootstrap.Modal.getInstance(document.getElementById('profileInfoModal')).hide();

    // Ouvrir le modal d'analyse
    openAIAnalysisModal(currentProfileData.id, currentProfileData.name, currentProfileData.description);
}

//============================================================================
// GESTION DES NOTES EMPLOYÉS
//============================================================================

function openEmployeeNoteModal(noteId = null) {
    const modal = new bootstrap.Modal(document.getElementById('employeeNoteModal'));

    if (noteId) {
        // Mode édition - charger la note
        fetch(`../ajax/employee_notes_api.php?action=get_note&id=${noteId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const note = data.data;
                    $('#noteModalTitle').text('Modifier la note');
                    $('#noteId').val(note.id);
                    $('#noteEmployeeId').val(note.employee_id);
                    $('#noteType').val(note.note_type);
                    $('#noteSeverity').val(note.severity);
                    $('#noteDateIncident').val(note.date_incident);
                    $('#noteTitle').val(note.title);
                    $('#noteDescription').val(note.description);
                    $('#noteIncludeAI').prop('checked', note.include_in_ai_analysis == 1);
                    $('#notePrivate').prop('checked', note.is_private == 1);
                    $('#noteResolved').prop('checked', note.is_resolved == 1);
                }
            });
    } else {
        // Mode création - réinitialiser
        $('#noteModalTitle').text('Ajouter une note employé');
        $('#employeeNoteForm')[0].reset();
        $('#noteId').val('');
    }

    modal.show();
}

function saveEmployeeNote() {
    const formData = new FormData($('#employeeNoteForm')[0]);
    const action = $('#noteId').val() ? 'update_note' : 'create_note';
    formData.append('action', action);

    // Convertir checkboxes
    formData.set('include_in_ai_analysis', $('#noteIncludeAI').is(':checked') ? 1 : 0);
    formData.set('is_private', $('#notePrivate').is(':checked') ? 1 : 0);
    formData.set('is_resolved', $('#noteResolved').is(':checked') ? 1 : 0);

    fetch('../ajax/employee_notes_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('employeeNoteModal')).hide();
                loadEmployeeNotes();
                showNotification('Note enregistrée avec succès', 'success');
            } else {
                showNotification('Erreur: ' + data.error, 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Erreur lors de l\'enregistrement', 'danger');
        });
}

function loadEmployeeNotes() {
    const filters = {
        employee_id: $('#filterNoteEmployee').val(),
        type: $('#filterNoteType').val(),
        severity: $('#filterNoteSeverity').val()
    };

    const params = new URLSearchParams({
        action: 'get_notes',
        ...filters
    });

    fetch(`../ajax/employee_notes_api.php?${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderEmployeeNotes(data.data);
            }
        })
        .catch(err => console.error(err));
}

function renderEmployeeNotes(notes) {
    if (!notes || notes.length === 0) {
        $('#employeeNotesContainer').html('<div class="alert alert-info">Aucune note trouvée</div>');
        return;
    }

    const severityColors = {
        info: 'info',
        low: 'success',
        medium: 'warning',
        high: 'danger',
        critical: 'dark'
    };

    const typeIcons = {
        avertissement: 'fa-exclamation-triangle',
        incident: 'fa-bolt',
        appreciation: 'fa-thumbs-up',
        remarque: 'fa-sticky-note',
        sanction: 'fa-gavel',
        autre: 'fa-info-circle'
    };

    const html = notes.map(note => `
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <i class="fas ${typeIcons[note.note_type]} me-2"></i>
                            ${escapeHtml(note.title)}
                            <span class="badge bg-${severityColors[note.severity]} ms-2">${note.severity}</span>
                            ${note.is_resolved == 1 ? '<span class="badge bg-success ms-1"><i class="fas fa-check"></i> Résolu</span>' : ''}
                        </h6>
                        <p class="text-muted mb-1">
                            <strong>${escapeHtml(note.employee_name)}</strong> - 
                            ${new Date(note.date_incident).toLocaleDateString('fr-FR')}
                        </p>
                        <p class="mb-0">${escapeHtml(note.description)}</p>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="openEmployeeNoteModal(${note.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteEmployeeNote(${note.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    $('#employeeNotesContainer').html(html);
}

function deleteEmployeeNote(noteId) {
    if (!confirm('Supprimer cette note ?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_note');
    formData.append('id', noteId);

    fetch('../ajax/employee_notes_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadEmployeeNotes();
                showNotification('Note supprimée', 'success');
            }
        });
}

//============================================================================
// GESTION DES NOTES MAGASIN
//============================================================================

function openShopNoteModal(noteId = null) {
    const modal = new bootstrap.Modal(document.getElementById('shopNoteModal'));

    if (noteId) {
        fetch(`../ajax/shop_notes_api.php?action=get_note&id=${noteId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const note = data.data;
                    $('#shopNoteModalTitle').text('Modifier l\'événement');
                    $('#shopNoteId').val(note.id);
                    $('#shopNoteType').val(note.note_type);
                    $('#shopImpactLevel').val(note.impact_level);
                    $('#shopDateStart').val(note.date_start);
                    $('#shopDateEnd').val(note.date_end || '');
                    $('#shopTitle').val(note.title);
                    $('#shopDescription').val(note.description);
                    $('#shopAffectsKPI').prop('checked', note.affects_kpi == 1);
                    $('#shopIncludeAI').prop('checked', note.include_in_ai_analysis == 1);
                }
            });
    } else {
        $('#shopNoteModalTitle').text('Ajouter un événement magasin');
        $('#shopNoteForm')[0].reset();
        $('#shopNoteId').val('');
    }

    modal.show();
}

function saveShopNote() {
    const formData = new FormData($('#shopNoteForm')[0]);
    const action = $('#shopNoteId').val() ? 'update_note' : 'create_note';
    formData.append('action', action);

    formData.set('affects_kpi', $('#shopAffectsKPI').is(':checked') ? 1 : 0);
    formData.set('include_in_ai_analysis', $('#shopIncludeAI').is(':checked') ? 1 : 0);

    fetch('../ajax/shop_notes_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('shopNoteModal')).hide();
                loadShopNotes();
                showNotification('Événement enregistré', 'success');
            } else {
                showNotification('Erreur: ' + data.error, 'danger');
            }
        });
}

function loadShopNotes() {
    fetch('../ajax/shop_notes_api.php?action=get_notes')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderShopNotes(data.data);
            }
        });
}

function renderShopNotes(notes) {
    if (!notes || notes.length === 0) {
        $('#shopNotesContainer').html('<div class="alert alert-info mt-3">Aucun événement</div>');
        return;
    }

    const html = notes.map(note => {
        const dateStr = new Date(note.date_start).toLocaleDateString('fr-FR');
        const dateEndStr = note.date_end ? ' au ' + new Date(note.date_end).toLocaleDateString('fr-FR') : '';
        const isActive = note.is_active == 1;

        return `
            <div class="card mb-2 ${isActive ? 'border-primary' : ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>
                                ${escapeHtml(note.title)}
                                ${isActive ? '<span class="badge bg-success ms-2">En cours</span>' : ''}
                            </h6>
                            <p class="text-muted mb-0">
                                ${dateStr}${dateEndStr} - ${note.duration_days} jour(s)
                            </p>
                            <p class="mb-0">${escapeHtml(note.description)}</p>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="openShopNoteModal(${note.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteShopNote(${note.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    $('#shopNotesContainer').html(html);
}

function deleteShopNote(noteId) {
    if (!confirm('Supprimer cet événement ?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_note');
    formData.append('id', noteId);

    fetch('../ajax/shop_notes_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadShopNotes();
            }
        });
}

//============================================================================
// GESTION DES PROFILS IA
//============================================================================

function openProfileModal(profileId = null) {
    const modal = new bootstrap.Modal(document.getElementById('profileModal'));

    if (profileId) {
        // Charger profil existant
        fetch(`../ajax/get_ai_profiles.php?id=${profileId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    const profile = data.data;
                    $('#profileModalTitle').text('Modifier le profil');
                    $('#profileId').val(profile.id);
                    $('#profileName').val(profile.name);
                    $('#profileIcon').val(profile.icon);
                    $('#profileDescription').val(profile.description);
                    $('#profilePrompt').val(profile.system_prompt);
                    $('#profileActive').prop('checked', profile.active == 1);
                }
            });
    } else {
        $('#profileModalTitle').text('Créer un profil d\'expert IA');
        $('#profileForm')[0].reset();
        $('#profileId').val('');
    }

    modal.show();
}

function saveProfile() {
    const formData = new FormData($('#profileForm')[0]);
    const action = $('#profileId').val() ? 'update' : 'create';
    formData.append('action', action);
    formData.set('active', $('#profileActive').is(':checked') ? 1 : 0);

    fetch('../ajax/manage_ai_profiles.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('profileModal')).hide();
                dashboard.loadAIProfiles();
                showNotification('Profil enregistré', 'success');
            } else {
                showNotification('Erreur: ' + data.error, 'danger');
            }
        });
}

function testProfile() {
    showNotification('Test de profil - fonctionnalité à venir', 'info');
}

// Fonctions globales pour les modals
function loadAllAIAnalyses() {
    // Générer toutes les analyses IA
    $('.accordion-item button[data-bs-toggle="collapse"]').each(function () {
        const profileId = $(this).data('bs-target').replace('#collapse', '');
        if (profileId) {
            dashboard.generateAIAnalysis(parseInt(profileId));
        }
    });
}

//============================================================================
// UTILITAIRES
//============================================================================

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return (text || '').replace(/[&<>"']/g, m => map[m]);
}

function showNotification(message, type = 'info') {
    const alertDiv = $(`
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
             style="top: 80px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);

    $('body').append(alertDiv);

    setTimeout(() => {
        alertDiv.alert('close');
    }, 3000);
}

//============================================================================
// MODAL ANALYSE IA
//============================================================================

let currentAIProfileId = null;

function openAIAnalysisModal(profileId, profileName, profileDescription) {
    console.log(`🤖 Ouverture modal analyse pour profil #${profileId}: ${profileName}`);

    // Stocker l'ID du profil
    currentAIProfileId = profileId;

    // Mettre à jour le modal
    $('#aiModalProfileName').text(profileName);
    $('#aiProfileDescription p').text(profileDescription);

    // Réinitialiser l'état du modal
    $('#aiChoiceSection').show();
    $('#aiEmployeeSection').hide();
    $('#aiLoadingSection').hide();
    $('#aiResultsSection').hide();
    $('#aiAnalysisContent').html('');
    $('#employeeList').html('');

    // IMPORTANT: Déplacer le modal dans le body pour éviter les problèmes d'overflow
    const modalElement = document.getElementById('aiAnalysisModal');
    if (modalElement && modalElement.parentElement !== document.body) {
        document.body.appendChild(modalElement);
        console.log('✅ Modal déplacé dans body');
    }

    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
    modal.show();
}

function selectAnalysisType(type) {
    console.log(`📊 Type d'analyse sélectionné: ${type}`);

    if (type === 'global') {
        // Analyse globale - afficher les KPI globaux
        $('#aiChoiceSection').fadeOut(300, () => {
            loadGlobalKPIs();
        });

    } else if (type === 'employee') {
        // Analyse par employé - afficher la liste
        loadEmployeesList();
    }
}

async function loadEmployeesList() {
    try {
        const response = await fetch('/ajax/get_employees_list.php');
        const data = await response.json();

        if (!data.success || !data.data || data.data.length === 0) {
            $('#employeeList').html('<div class="alert alert-info">Aucun employé trouvé</div>');
            $('#aiChoiceSection').fadeOut(300, () => {
                $('#aiEmployeeSection').fadeIn(300);
            });
            return;
        }

        const employeesHtml = data.data.map(emp => {
            const initials = emp.full_name.split(' ').map(w => w[0]).join('').substring(0, 2);
            return `
                <div class="employee-item" onclick="selectEmployee(${emp.id}, '${emp.full_name.replace(/'/g, "\\'")}')">
                    <div class="employee-info">
                        <div class="employee-avatar">${initials}</div>
                        <div>
                            <div class="employee-name">${emp.full_name}</div>
                            <div class="employee-role">${emp.role || 'Employé'}</div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #60a5fa;"></i>
                </div>
            `;
        }).join('');

        $('#employeeList').html(employeesHtml);

        $('#aiChoiceSection').fadeOut(300, () => {
            $('#aiEmployeeSection').fadeIn(300);
        });

    } catch (error) {
        console.error('Erreur chargement employés:', error);
        $('#employeeList').html('<div class="alert alert-danger">Erreur chargement employés</div>');
        $('#aiChoiceSection').fadeOut(300, () => {
            $('#aiEmployeeSection').fadeIn(300);
        });
    }
}

let selectedEmployeeId = null;
let selectedEmployeeName = null;
let currentEmployeeKPIData = null; // Stocker toutes les données KPI de l'employé
let globalKPIRawData = null; // Stocker les données brutes des KPI globaux
let globalKPICardsData = null; // Stocker les cartes KPI avec leurs données sources
let employeeKPICardsData = null; // Stocker les cartes KPI employé avec leurs dataKey

// Variables pour le mode comparaison de périodes
let isComparisonMode = false;
let periodBStartDate = null;
let periodBEndDate = null;
let periodBEmployeeKPIData = null;
let periodBEmployeeKPICardsData = null;

// Historique de conversation avec l'IA
let conversationHistory = [];

async function loadGlobalKPIs() {
    $('#globalKPIGrid').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    $('#aiGlobalKPISection').fadeIn(300);

    try {
        // Récupérer les KPI globaux
        const results = await Promise.allSettled([
            dashboard.fetchAPI('chiffre_affaires_global'),
            dashboard.fetchAPI('kpi_reparations'),
            dashboard.fetchAPI('analyse_gardiennage'),
            dashboard.fetchAPI('analyse_temps')
        ]);

        const ca = results[0].status === 'fulfilled' ? results[0].value : {};
        const reparations = results[1].status === 'fulfilled' ? results[1].value : {};
        const gardiennage = results[2].status === 'fulfilled' ? results[2].value : {};
        const temps = results[3].status === 'fulfilled' ? results[3].value : {};

        // STOCKER les données brutes pour filtrage ultérieur
        globalKPIRawData = { ca, reparations, gardiennage, temps };

        console.log('📊 KPI Globaux chargés:', globalKPIRawData);
        // Tableau complet des cartes KPI globales (20 cartes) avec leurs clés de données
        const globalKPICards = [
            // === CA ===
            { icon: 'fa-money-bill-wave', title: 'CA Total Encaissé', value: dashboard.formatCurrency(ca?.ca_encaisse || 0), subtitle: 'Réparations restituées', dataKey: 'ca.ca_encaisse' },
            { icon: 'fa-wallet', title: 'CA Total', value: dashboard.formatCurrency(ca?.ca_total || 0), subtitle: 'Encaissé + À encaisser', dataKey: 'ca.ca_total' },
            { icon: 'fa-receipt', title: 'Panier Moyen', value: dashboard.formatCurrency(ca?.panier_moyen_total || 0), subtitle: 'Prix moyen', dataKey: 'ca.panier_moyen_total' },
            { icon: 'fa-hand-holding-usd', title: 'Créances en Cours', value: dashboard.formatCurrency(ca?.ca_a_encaisser || 0), subtitle: 'Effectuées non restituées', color: 'warning', dataKey: 'ca.ca_a_encaisser' },

            // === RÉPARATIONS ===
            { icon: 'fa-plus-circle', title: 'Nouvelles Réparations', value: reparations?.total_nouvelles || 0, subtitle: 'Créées', dataKey: 'reparations.total_nouvelles' },
            { icon: 'fa-wrench', title: 'Réparations Effectuées', value: reparations?.total_effectuees || 0, subtitle: 'Terminées', dataKey: 'reparations.total_effectuees' },
            { icon: 'fa-check-double', title: 'Réparations Restituées', value: ca?.nb_restituees || 0, subtitle: 'Rendues', dataKey: 'ca.nb_restituees' },
            { icon: 'fa-user-check', title: 'Réparations en Autonomie', value: reparations?.total_autonomie || 0, subtitle: 'Un seul employé', color: 'success', dataKey: 'reparations.total_autonomie' },
            { icon: 'fa-percentage', title: 'Taux de Restitution', value: ca?.nb_restituees && ca?.nb_total ? ((ca.nb_restituees / ca.nb_total) * 100).toFixed(1) + '%' : '0%', subtitle: 'Restituées / Total', dataKey: 'ca.taux_restitution' },

            // === TEMPS ===
            { icon: 'fa-clock', title: 'Temps Technique Moyen', value: temps?.temps_moyen_technique_heures ? temps.temps_moyen_technique_heures.toFixed(1) + 'h' : '0h', subtitle: 'Création → Réparation effectuée', dataKey: 'temps.temps_moyen_technique_heures' },
            { icon: 'fa-hourglass-end', title: 'Temps Total Moyen', value: temps?.temps_moyen_total_heures ? temps.temps_moyen_total_heures.toFixed(1) + 'h' : '0h', subtitle: 'Création → Restitution finale', dataKey: 'temps.temps_moyen_total_heures' },

            // === GARDIENNAGE ===
            { icon: 'fa-box', title: 'Appareils en Gardiennage', value: gardiennage?.total_en_gardiennage || 0, subtitle: 'Actuellement', color: 'info', dataKey: 'gardiennage.total_en_gardiennage' },
            { icon: 'fa-calendar-alt', title: 'Temps Moyen Gardiennage', value: gardiennage?.temps_moyen_jours ? gardiennage.temps_moyen_jours.toFixed(0) + ' jours' : '0j', subtitle: 'Durée moyenne', dataKey: 'gardiennage.temps_moyen_jours' },
            { icon: 'fa-euro-sign', title: 'Coût Total Gardiennage', value: dashboard.formatCurrency(gardiennage?.cout_total || 0), subtitle: 'Sur la période', color: 'warning', dataKey: 'gardiennage.cout_total' },

            // === QUALITÉ ===
            { icon: 'fa-trophy', title: 'Taux Réussite 1ère Intervention', value: reparations?.taux_reussite_premiere ? reparations.taux_reussite_premiere.toFixed(1) + '%' : 'N/A', subtitle: 'Sans retour', color: 'success', dataKey: 'reparations.taux_reussite_premiere' },
            { icon: 'fa-redo', title: 'Taux de Reprise', value: reparations?.taux_reprise ? reparations.taux_reprise.toFixed(1) + '%' : '0%', subtitle: 'Réparations retournées', color: reparations?.taux_reprise > 5 ? 'danger' : 'success', dataKey: 'reparations.taux_reprise' },

            // === EFFICACITÉ ===
            { icon: 'fa-shopping-cart', title: 'Taux Commande Pièces', value: reparations?.taux_commande_pieces ? reparations.taux_commande_pieces.toFixed(1) + '%' : 'N/A', subtitle: 'Nécessitant commande', dataKey: 'reparations.taux_commande_pieces' },
            { icon: 'fa-tools', title: 'Top 10 Pannes', value: 'Analyse IA', subtitle: 'Regroupement sémantique', color: 'info', dataKey: 'reparations.top_pannes' },

            // === STATS ===
            { icon: 'fa-users', title: 'Employés Actifs', value: reparations?.nombre_employes_actifs || 0, subtitle: 'Sur la période', dataKey: 'reparations.nombre_employes_actifs' },
            { icon: 'fa-mobile-alt', title: 'Types d\'Appareils', value: reparations?.nombre_types_appareils || 0, subtitle: 'Catégories', dataKey: 'reparations.nombre_types_appareils' }
        ];

        // STOCKER les cartes avec leurs données
        globalKPICardsData = globalKPICards;

        const kpiHtml = globalKPICards.map((kpi, index) => `
            <div class="kpi-card ${kpi.color ? 'kpi-' + kpi.color : ''}" data-kpi-index="${index}" onclick="toggleKPICard(this)">
                <div class="kpi-card-title"><i class="fas ${kpi.icon}"></i> ${kpi.title}</div>
                <div class="kpi-card-value">${kpi.value}</div>
                <div class="kpi-card-subtitle">${kpi.subtitle}</div>
            </div>
        `).join('');

        $('#globalKPIGrid').html(kpiHtml);

    } catch (error) {
        console.error('Erreur chargement KPI globaux:', error);
        $('#globalKPIGrid').html('<div class="alert alert-danger">Erreur chargement KPI globaux</div>');
    }
}

function confirmAndLaunchGlobalAnalysis() {
    const selectedIndices = [];
    $('.kpi-card.selected').each(function () {
        selectedIndices.push($(this).data('kpi-index'));
    });

    if (selectedIndices.length === 0) {
        alert('⚠️ Veuillez sélectionner au moins un KPI pour l\'analyse');
        return;
    }

    console.log(`🎯 ${selectedIndices.length} KPI globaux sélectionnés:`, selectedIndices);

    // COLLECTER UNIQUEMENT les données des KPI sélectionnés
    const filteredKPIData = {};
    selectedIndices.forEach(index => {
        const card = globalKPICardsData[index];
        if (card && card.dataKey) {
            const [category, key] = card.dataKey.split('.');
            if (!filteredKPIData[category]) {
                filteredKPIData[category] = {};
            }
            // Récupérer la valeur depuis globalKPIRawData
            const value = globalKPIRawData[category]?.[key];
            if (value !== undefined) {
                filteredKPIData[category][key] = value;
            }
        }
    });

    console.log('📊 Données KPI filtrées:', filteredKPIData);

    // Pas de fadeOut/fadeIn ici, la modal de prévisualisation s'affichera
    // Le loading sera affiché après confirmation dans launchAIAnalysisWithCustomPrompt

    // Envoyer uniquement les données filtrées - AVEC PRÉVISUALISATION DU PROMPT
    previewPromptBeforeAnalysis(null, { filteredKPIData: filteredKPIData, selected_kpis: selectedIndices, analysis_type: 'global' });
}



function selectEmployee(employeeId, employeeName) {
    console.log(`👤 Employé sélectionné: ${employeeName} (ID: ${employeeId})`);

    selectedEmployeeId = employeeId;
    selectedEmployeeName = employeeName;

    $('#aiEmployeeSection').fadeOut(300, () => {
        loadEmployeeKPIs(employeeId, employeeName);
    });
}

async function loadEmployeeKPIs(employeeId, employeeName) {
    $('#selectedEmployeeName').text(employeeName);
    $('#employeeKPIGrid').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    $('#aiEmployeeKPISection').fadeIn(300);

    try {
        // Récupérer les KPI de l'employé via l'API existante
        // Utiliser Promise.allSettled pour que les KPI disponibles s'affichent même si certains échouent
        const results = await Promise.allSettled([
            dashboard.fetchAPI('chiffre_affaires_employe', { user_id: employeeId }),
            dashboard.fetchAPI('kpi_reparations', { user_id: employeeId }),
            dashboard.fetchAPI('analyse_autonomie', { user_id: employeeId }),
            dashboard.fetchAPI('analyse_temps', { user_id: employeeId }),
            dashboard.fetchAPI('analyse_comportement', { user_id: employeeId }),
            // Récupérer les notes de l'employé
            fetch(`/ajax/employee_notes_api.php?action=get_notes&employee_id=${employeeId}`).then(r => r.json()).then(d => d.data)
        ]);

        // Extraire les données réussies (les API retournent souvent des tableaux)
        const caData = results[0].status === 'fulfilled' ? results[0].value : null;
        const ca = Array.isArray(caData) ? caData[0] : caData;

        const reparationsData = results[1].status === 'fulfilled' ? results[1].value : null;
        const reparations = Array.isArray(reparationsData) ? reparationsData[0] : reparationsData;

        const autonomieData = results[2].status === 'fulfilled' ? results[2].value : null;
        const autonomie = Array.isArray(autonomieData) ? autonomieData[0] : autonomieData;

        const tempsData = results[3].status === 'fulfilled' ? results[3].value : null;
        const temps = Array.isArray(tempsData) ? tempsData[0] : tempsData;

        const comportementData = results[4].status === 'fulfilled' ? results[4].value : null;
        const comportement = Array.isArray(comportementData) ? comportementData[0] : comportementData;

        const notesData = results[5].status === 'fulfilled' ? results[5].value : null;
        const notes = Array.isArray(notesData) ? notesData : [];

        // Compter les notes par type
        const notesParType = {
            avertissement: notes.filter(n => n.note_type === 'avertissement').length,
            incident: notes.filter(n => n.note_type === 'incident').length,
            sanction: notes.filter(n => n.note_type === 'sanction').length,
            appreciation: notes.filter(n => n.note_type === 'appreciation').length,
            remarque: notes.filter(n => n.note_type === 'remarque').length,
            autre: notes.filter(n => n.note_type === 'autre').length
        };
        // Logger les erreurs sans bloquer
        results.forEach((result, index) => {
            const apiNames = ['CA', 'Réparations', 'Autonomie', 'Temps', 'Comportement', 'Notes'];
            if (result.status === 'rejected') {
                console.warn(`⚠️ ${apiNames[index]} KPI non disponible:`, result.reason);
            }
        });

        // Stocker toutes les données pour utilisation ultérieure
        currentEmployeeKPIData = {
            ca,
            reparations,
            autonomie,
            temps,
            comportement,
            notes,
            notesParType
        };

        console.log('📊 KPI chargés:', currentEmployeeKPIData);

        // Générer les cartes KPI avec les vraies données ET les dataKey pour filtrage
        const kpiCards = [
            {
                icon: 'fa-euro-sign',
                title: 'CA Encaissé',
                value: dashboard.formatCurrency(ca?.ca_encaisse || 0),
                subtitle: 'Montant encaissé',
                dataKey: 'ca.ca_encaisse'
            },
            {
                icon: 'fa-wallet',
                title: 'CA Total',
                value: dashboard.formatCurrency(ca?.ca_total || 0),
                subtitle: 'Encaissé + À encaisser',
                dataKey: 'ca.ca_total'
            },
            {
                icon: 'fa-tools',
                title: 'Nouvelles Réparations',
                value: reparations?.total_nouvelles || 0,
                subtitle: 'Créées sur la période',
                dataKey: 'reparations.total_nouvelles'
            },
            {
                icon: 'fa-check-circle',
                title: 'Réparations Effectuées',
                value: reparations?.total_effectuees || 0,
                subtitle: 'Terminées',
                dataKey: 'reparations.total_effectuees'
            },
            {
                icon: 'fa-check-double',
                title: 'Réparations Restituées',
                value: reparations?.restituees || 0,
                subtitle: 'Rendues aux clients',
                dataKey: 'reparations.restituees'
            },
            {
                icon: 'fa-user-check',
                title: 'Taux d\'Autonomie',
                value: (autonomie?.taux_autonomie || 0) + '%',
                subtitle: `${autonomie?.total_autonomie || 0} réparations en autonomie`,
                dataKey: 'autonomie.taux_autonomie'
            },
            {
                icon: 'fa-clock',
                title: 'Temps Moyen Total',
                value: (temps?.temps_moyen_total_heures || 0).toFixed(1) + 'h',
                subtitle: 'Création → Restitution',
                dataKey: 'temps.temps_moyen_total_heures'
            },
            {
                icon: 'fa-wrench',
                title: 'Temps Technique Moyen',
                value: (temps?.temps_moyen_technique_heures || 0).toFixed(1) + 'h',
                subtitle: 'Temps réel de réparation',
                dataKey: 'temps.temps_moyen_technique_heures'
            },
            {
                icon: 'fa-calendar-check',
                title: 'Taux de Présence',
                value: (comportement?.taux_presence || 100) + '%',
                subtitle: 'Jours travaillés',
                dataKey: 'comportement.taux_presence'
            },
            {
                icon: 'fa-calendar-times',
                title: 'Retards',
                value: comportement?.nb_retards || 0,
                subtitle: 'Sur la période',
                dataKey: 'comportement.nb_retards'
            },
            {
                icon: 'fa-chart-line',
                title: 'Panier Moyen',
                value: dashboard.formatCurrency(ca?.panier_moyen || 0),
                subtitle: 'Prix moyen par réparation',
                dataKey: 'ca.panier_moyen'
            },
            {
                icon: 'fa-percentage',
                title: 'Part du CA',
                value: (ca?.part_ca_total || 0).toFixed(1) + '%',
                subtitle: 'Contribution au CA total',
                dataKey: 'ca.part_ca_total'
            },
            // === NOTES EMPLOYÉ ===
            {
                icon: 'fa-exclamation-triangle',
                title: 'Avertissements',
                value: notesParType.avertissement,
                subtitle: `Note${notesParType.avertissement > 1 ? 's' : ''} d'avertissement`,
                color: 'warning',
                dataKey: 'notesParType.avertissement'
            },
            {
                icon: 'fa-flag',
                title: 'Incidents',
                value: notesParType.incident,
                subtitle: `Incident${notesParType.incident > 1 ? 's' : ''} signalé${notesParType.incident > 1 ? 's' : ''}`,
                color: 'danger',
                dataKey: 'notesParType.incident'
            },
            {
                icon: 'fa-gavel',
                title: 'Sanctions',
                value: notesParType.sanction,
                subtitle: `Sanction${notesParType.sanction > 1 ? 's' : ''} appliquée${notesParType.sanction > 1 ? 's' : ''}`,
                color: 'critical',
                dataKey: 'notesParType.sanction'
            },
            {
                icon: 'fa-thumbs-up',
                title: 'Appréciations',
                value: notesParType.appreciation,
                subtitle: `Note${notesParType.appreciation > 1 ? 's' : ''} positive${notesParType.appreciation > 1 ? 's' : ''}`,
                color: 'success',
                dataKey: 'notesParType.appreciation'
            },
            {
                icon: 'fa-sticky-note',
                title: 'Remarques',
                value: notesParType.remarque,
                subtitle: `Remarque${notesParType.remarque > 1 ? 's' : ''}`,
                color: 'info',
                dataKey: 'notesParType.remarque'
            },
            {
                icon: 'fa-clipboard',
                title: 'Autres Notes',
                value: notesParType.autre,
                subtitle: 'Notes diverses',
                color: 'neutral',
                dataKey: 'notesParType.autre'
            },
            // === PRÉSENCE ===
            {
                icon: 'fa-hourglass-half',
                title: 'Retards',
                value: comportement?.nb_retards || 0,
                subtitle: `Retard${(comportement?.nb_retards || 0) > 1 ? 's' : ''} sur la période`,
                color: 'warning',
                dataKey: 'comportement.nb_retards'
            },
            {
                icon: 'fa-user-slash',
                title: 'Absences',
                value: comportement?.nb_absences || 0,
                subtitle: `Absence${(comportement?.nb_absences || 0) > 1 ? 's' : ''}`,
                color: 'danger',
                dataKey: 'comportement.nb_absences'
            }
        ];

        // STOCKER les cartes employé pour filtrage ultérieur
        employeeKPICardsData = kpiCards;

        let kpiHtml;

        if (isComparisonMode && periodBEmployeeKPIData) {
            // Mode comparaison : affichage côte à côte
            kpiHtml = kpiCards.map((kpi, index) => {
                const periodBCard = periodBEmployeeKPICardsData[index];
                return `
                    <div class="kpi-card comparison ${kpi.color ? 'kpi-' + kpi.color : ''}" data-kpi-index="${index}" onclick="toggleKPICard(this)">
                        <div class="kpi-card-title">
                            <i class="fas ${kpi.icon}"></i>
                            ${kpi.title}
                        </div>
                        <div class="row mt-2">
                            <div class="col-6 border-end">
                                <small class="text-muted">Période A</small>
                                <div class="kpi-card-value">${kpi.value}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Période B</small>
                                <div class="kpi-card-value">${periodBCard ? periodBCard.value : 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            // Mode normal : affichage simple
            kpiHtml = kpiCards.map((kpi, index) => `
                <div class="kpi-card ${kpi.color ? 'kpi-' + kpi.color : ''}" data-kpi-index="${index}" data-color="${kpi.color || 'default'}" onclick="toggleKPICard(this)">
                    <div class="kpi-card-title">
                        <i class="fas ${kpi.icon}"></i>
                        ${kpi.title}
                    </div>
                    <div class="kpi-card-value">${kpi.value}</div>
                    <div class="kpi-card-subtitle">${kpi.subtitle}</div>
                </div>
            `).join('');
        }

        $('#employeeKPIGrid').html(kpiHtml);

        // Ajouter l'UI de comparaison après le grid
        if (!$('#comparisonControls').length) {
            const comparisonHTML = `
                <div id="comparisonControls" class="mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enableComparison" onchange="toggleComparisonMode()">
                        <label class="form-check-label fw-bold" for="enableComparison">
                            <i class="fas fa-balance-scale me-2"></i>Comparer avec une autre période
                        </label>
                    </div>
                    <div id="comparisonDateFields" class="mt-3" style="display: none;">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Période de comparaison (B)</h6>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Date début</label>
                                        <input type="date" class="form-control" id="periodBStart">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Date fin</label>
                                        <input type="date" class="form-control" id="periodBEnd">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button class="btn btn-primary w-100" onclick="loadPeriodBData()">
                                            <i class="fas fa-download me-2"></i>Charger
                                        </button>
                                    </div>
                                </div>
                                <div id="periodBStatus" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#employeeKPIGrid').after(comparisonHTML);
        }

    } catch (error) {
        console.error('Erreur chargement KPI:', error);
        $('#employeeKPIGrid').html('<div class="alert alert-danger">Erreur chargement KPI : ' + error.message + '</div>');
    }
}

// Fonction pour toggle la sélection d'une carte KPI
function toggleKPICard(card) {
    $(card).toggleClass('selected');
    const index = $(card).data('kpi-index');
    const isSelected = $(card).hasClass('selected');
    console.log(`📊 KPI ${index} ${isSelected ? 'sélectionné' : 'désélectionné'}`);
}

/**
 * Imprime le contenu de l'analyse IA
 */
function printAIAnalysis() {
    const printContent = document.getElementById('aiAnalysisPrintContent');
    if (!printContent) {
        alert('❌ Aucune analyse à imprimer');
        return;
    }

    // Créer une nouvelle fenêtre pour l'impression
    const printWindow = window.open('', '', 'height=600,width=800');

    // Construire le HTML complet avec styles
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Analyse IA - GeekBoard</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                body {
                    font-family: 'Inter', Arial, sans-serif;
                    padding: 20px;
                    background: white;
                }
                h1, h2, h3, h4 {
                    color: #0078e8;
                    margin-top: 1.5rem;
                }
                .alert {
                    border-radius: 8px;
                    padding: 15px;
                }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1 class="mb-4">
                    <i class="fas fa-chart-line me-2"></i>
                    Analyse IA - GeekBoard
                </h1>
                <p class="text-muted">
                    Généré le ${new Date().toLocaleString('fr-FR')}
                </p>
                <hr class="mb-4">
                ${printContent.innerHTML}
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    // Fermer après impression (optionnel)
                    // window.onafterprint = function() { window.close(); };
                };
            </script>
        </body>
        </html>
    `);

    printWindow.document.close();
}

/**
 * Active/désactive le mode comparaison
 */
function toggleComparisonMode() {
    isComparisonMode = $('#enableComparison').is(':checked');

    if (isComparisonMode) {
        $('#comparisonDateFields').slideDown();
        console.log('📊 Mode comparaison activé');
    } else {
        $('#comparisonDateFields').slideUp();
        // Réinitialiser les données période B
        periodBEmployeeKPIData = null;
        periodBEmployeeKPICardsData = null;
        periodBStartDate = null;
        periodBEndDate = null;
        $('#periodBStatus').html('');

        // Recharger l'affichage normal
        if (selectedEmployeeId) {
            loadEmployeeKPIs(selectedEmployeeId, selectedEmployeeName);
        }
        console.log('📊 Mode comparaison désactivé');
    }
}

/**
 * Charge les KPI de la période B pour comparaison
 */
async function loadPeriodBData() {
    if (!selectedEmployeeId) {
        alert('❌ Aucun employé sélectionné');
        return;
    }

    periodBStartDate = $('#periodBStart').val();
    periodBEndDate = $('#periodBEnd').val();

    if (!periodBStartDate || !periodBEndDate) {
        alert('❌ Veuillez saisir les dates de la période B');
        return;
    }

    // Vérifier que période B est différente de période A
    if (periodBStartDate === dashboard.currentFilters.date_start &&
        periodBEndDate === dashboard.currentFilters.date_end) {
        alert('⚠️ La période B doit être différente de la période A');
        return;
    }

    $('#periodBStatus').html('<i class="fas fa-spinner fa-spin"></i> Chargement des KPI période B...');

    try {
        // Charger les KPI période B (même logique que période A)
        const results = await Promise.allSettled([
            dashboard.fetchAPI('chiffre_affaires_employe', { user_id: selectedEmployeeId, date_start: periodBStartDate, date_end: periodBEndDate }),
            dashboard.fetchAPI('kpi_reparations', { user_id: selectedEmployeeId, date_start: periodBStartDate, date_end: periodBEndDate }),
            dashboard.fetchAPI('analyse_autonomie', { user_id: selectedEmployeeId, date_start: periodBStartDate, date_end: periodBEndDate }),
            dashboard.fetchAPI('analyse_temps', { user_id: selectedEmployeeId, date_start: periodBStartDate, date_end: periodBEndDate }),
            dashboard.fetchAPI('analyse_comportement', { user_id: selectedEmployeeId, date_start: periodBStartDate, date_end: periodBEndDate }),
            fetch(`/ajax/employee_notes_api.php?action=get_notes&employee_id=${selectedEmployeeId}`).then(r => r.json()).then(d => d.data)
        ]);

        // Extraire les données
        const caData = results[0].status === 'fulfilled' ? results[0].value : null;
        const ca = Array.isArray(caData) ? caData[0] : caData;

        const reparationsData = results[1].status === 'fulfilled' ? results[1].value : null;
        const reparations = Array.isArray(reparationsData) ? reparationsData[0] : reparationsData;

        const autonomieData = results[2].status === 'fulfilled' ? results[2].value : null;
        const autonomie = Array.isArray(autonomieData) ? autonomieData[0] : autonomieData;

        const tempsData = results[3].status === 'fulfilled' ? results[3].value : null;
        const temps = Array.isArray(tempsData) ? tempsData[0] : tempsData;

        const comportementData = results[4].status === 'fulfilled' ? results[4].value : null;
        const comportement = Array.isArray(comportementData) ? comportementData[0] : comportementData;

        const notesData = results[5].status === 'fulfilled' ? results[5].value : null;
        const notes = Array.isArray(notesData) ? notesData : [];

        // Compter les notes par type
        const notesParType = {
            avertissement: notes.filter(n => n.note_type === 'avertissement').length,
            incident: notes.filter(n => n.note_type === 'incident').length,
            sanction: notes.filter(n => n.note_type === 'sanction').length,
            appreciation: notes.filter(n => n.note_type === 'appreciation').length,
            remarque: notes.filter(n => n.note_type === 'remarque').length,
            autre: notes.filter(n => n.note_type === 'autre').length
        };

        // Stocker les données période B
        periodBEmployeeKPIData = {
            ca,
            reparations,
            autonomie,
            temps,
            comportement,
            notes,
            notesParType
        };

        // Créer les cartes période B avec les MÊMES dataKey que période A
        periodBEmployeeKPICardsData = [
            { icon: 'fa-euro-sign', title: 'CA Encaissé', value: dashboard.formatCurrency(ca?.ca_encaisse || 0), dataKey: 'ca.ca_encaisse' },
            { icon: 'fa-wallet', title: 'CA Total', value: dashboard.formatCurrency(ca?.ca_total || 0), dataKey: 'ca.ca_total' },
            { icon: 'fa-tools', title: 'Nouvelles Réparations', value: reparations?.total_nouvelles || 0, dataKey: 'reparations.total_nouvelles' },
            { icon: 'fa-check-circle', title: 'Réparations Effectuées', value: reparations?.total_effectuees || 0, dataKey: 'reparations.total_effectuees' },
            { icon: 'fa-check-double', title: 'Réparations Restituées', value: reparations?.restituees || 0, dataKey: 'reparations.restituees' },
            { icon: 'fa-user-check', title: 'Taux d\'Autonomie', value: (autonomie?.taux_autonomie || 0) + '%', dataKey: 'autonomie.taux_autonomie' },
            { icon: 'fa-clock', title: 'Temps Moyen Total', value: (temps?.temps_moyen_total_heures || 0).toFixed(1) + 'h', dataKey: 'temps.temps_moyen_total_heures' },
            { icon: 'fa-wrench', title: 'Temps Technique Moyen', value: (temps?.temps_moyen_technique_heures || 0).toFixed(1) + 'h', dataKey: 'temps.temps_moyen_technique_heures' },
            { icon: 'fa-calendar-check', title: 'Taux de Présence', value: (comportement?.taux_presence || 100) + '%', dataKey: 'comportement.taux_presence' },
            { icon: 'fa-calendar-times', title: 'Retards', value: comportement?.nb_retards || 0, dataKey: 'comportement.nb_retards' },
            { icon: 'fa-chart-line', title: 'Panier Moyen', value: dashboard.formatCurrency(ca?.panier_moyen || 0), dataKey: 'ca.panier_moyen' },
            { icon: 'fa-percentage', title: 'Part du CA', value: (ca?.part_ca_total || 0).toFixed(1) + '%', dataKey: 'ca.part_ca_total' },
            { icon: 'fa-exclamation-triangle', title: 'Avertissements', value: notesParType.avertissement, color: 'warning', dataKey: 'notesParType.avertissement' },
            { icon: 'fa-flag', title: 'Incidents', value: notesParType.incident, color: 'danger', dataKey: 'notesParType.incident' },
            { icon: 'fa-gavel', title: 'Sanctions', value: notesParType.sanction, color: 'critical', dataKey: 'notesParType.sanction' },
            { icon: 'fa-thumbs-up', title: 'Appréciations', value: notesParType.appreciation, color: 'success', dataKey: 'notesParType.appreciation' },
            { icon: 'fa-sticky-note', title: 'Remarques', value: notesParType.remarque, color: 'info', dataKey: 'notesParType.remarque' },
            { icon: 'fa-clipboard', title: 'Autres Notes', value: notesParType.autre, color: 'neutral', dataKey: 'notesParType.autre' },
            { icon: 'fa-hourglass-half', title: 'Retards', value: comportement?.nb_retards || 0, color: 'warning', dataKey: 'comportement.nb_retards' },
            { icon: 'fa-user-slash', title: 'Absences', value: comportement?.nb_absences || 0, color: 'danger', dataKey: 'comportement.nb_absences' }
        ];

        $('#periodBStatus').html('<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>KPI période B chargés avec succès</div>');

        // Recharger l'affichage avec comparaison
        loadEmployeeKPIs(selectedEmployeeId, selectedEmployeeName);

        console.log('✅ KPI période B chargés:', periodBEmployeeKPIData);

    } catch (error) {
        console.error('Erreur chargement période B:', error);
        $('#periodBStatus').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur chargement période B</div>');
    }
}

function backToEmployeeList() {
    $('#aiEmployeeKPISection').fadeOut(300, () => {
        $('#aiEmployeeSection').fadeIn(300);
    });
}

function confirmAndLaunchAnalysis() {
    if (!selectedEmployeeId) {
        console.error('Aucun employé sélectionné');
        return;
    }

    // Récupérer les indices des KPI sélectionnés
    const selectedIndices = [];
    $('.kpi-card.selected').each(function () {
        selectedIndices.push($(this).data('kpi-index'));
    });

    if (selectedIndices.length === 0) {
        alert('⚠️ Veuillez sélectionner au moins un KPI pour l\'analyse');
        return;
    }

    console.log(`🎯 ${selectedIndices.length} KPI sélectionnés pour l'analyse:`, selectedIndices);

    // FILTRER les données selon les KPI sélectionnés (comme pour analyse globale)
    const filteredEmployeeKPIData = {};

    selectedIndices.forEach(index => {
        const card = employeeKPICardsData[index];
        if (card && card.dataKey) {
            const [category, key] = card.dataKey.split('.');
            if (!filteredEmployeeKPIData[category]) {
                filteredEmployeeKPIData[category] = {};
            }
            // Récupérer la valeur depuis currentEmployeeKPIData
            const value = currentEmployeeKPIData[category]?.[key];
            if (value !== undefined) {
                filteredEmployeeKPIData[category][key] = value;
            }
        }
    });

    console.log('📊 Données KPI employé filtrées:', filteredEmployeeKPIData);

    // Pas de fadeOut/fadeIn ici, la modal de prévisualisation s'affichera
    // Le loading sera affiché après confirmation dans launchAIAnalysisWithCustomPrompt

    // Envoyer les données FILTRÉES - AVEC PRÉVISUALISATION DU PROMPT
    previewPromptBeforeAnalysis(selectedEmployeeId, {
        filteredKPIData: filteredEmployeeKPIData,
        employee_id: selectedEmployeeId,
        employee_name: selectedEmployeeName,
        selected_kpis: selectedIndices,
        analysis_type: 'employee'
    });
}

function backToChoice() {
    $('#aiEmployeeSection').fadeOut(300, () => {
        $('#aiChoiceSection').fadeIn(300);
    });
}

// Variables globales pour la prévisualisation du prompt
let pendingAnalysisData = null;
let pendingEmployeeId = null;

/**
 * Affiche la modal de prévisualisation du prompt avant d'envoyer à l'IA
 */
async function previewPromptBeforeAnalysis(employeeId = null, selectedKPIData = null) {
    if (!currentAIProfileId) {
        console.error('Aucun profil sélectionné');
        return;
    }

    // Fermer la modal d'analyse IA si elle est ouverte
    const analysisModal = document.getElementById('aiAnalysisModal');
    if (analysisModal) {
        const bsModal = bootstrap.Modal.getInstance(analysisModal);
        if (bsModal) {
            bsModal.hide();
        }
    }

    // Stocker les données pour utilisation ultérieure
    pendingAnalysisData = selectedKPIData;
    pendingEmployeeId = employeeId;

    try {
        // Récupérer le profil IA
        const profileResponse = await fetch(`/ajax/get_ai_profiles.php?id=${currentAIProfileId}`);
        const profileData = await profileResponse.json();

        if (!profileData.success) {
            throw new Error('Impossible de charger le profil');
        }

        const profile = profileData.data;

        // Récupérer les données KPI filtrées
        let kpiData;
        if (selectedKPIData && selectedKPIData.filteredKPIData) {
            kpiData = selectedKPIData.filteredKPIData;
        } else {
            kpiData = {};
        }

        // Charger les notes si analyse employé
        if (employeeId) {
            loadNotesIntoGrid(employeeId);
        } else {
            $('#notesGrid').html('<div class="text-center text-muted"><i class="fas fa-info-circle me-2"></i>Pas de notes pour analyse globale</div>');
        }

        // Construire le prompt complet
        const prompt = buildCompletePrompt(profile, kpiData, employeeId, selectedKPIData);

        // Afficher dans le textarea
        $('#promptEditor').val(prompt);

        // Afficher la modal
        const modal = new bootstrap.Modal(document.getElementById('promptPreviewModal'));
        modal.show();

    } catch (error) {
        console.error('Erreur prévisualisation prompt:', error);
        alert('❌ Erreur lors de la prévisualisation du prompt');
    }
}

/**
 * Charge les notes employé dans la grille cliquable
 */
async function loadNotesIntoGrid(employeeId) {
    try {
        $('#notesGrid').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>');

        const response = await fetch(`/ajax/employee_notes_api.php?action=get_notes&employee_id=${employeeId}`);
        const data = await response.json();

        if (!data.success || !data.data || data.data.length === 0) {
            $('#notesGrid').html('<div class="text-center text-muted"><i class="fas fa-sticky-note me-2"></i>Aucune note trouvée</div>');
            return;
        }

        const notes = data.data;

        const typeIcons = {
            avertissement: 'fa-exclamation-triangle',
            incident: 'fa-bolt',
            appreciation: 'fa-thumbs-up',
            remarque: 'fa-sticky-note',
            sanction: 'fa-gavel',
            autre: 'fa-info-circle'
        };

        const typeColors = {
            avertissement: 'warning',
            incident: 'danger',
            appreciation: 'success',
            remarque: 'info',
            sanction: 'dark',
            autre: 'secondary'
        };

        // Générer les cartes notes
        const notesHTML = notes.map(note => `
            <div class="note-card border rounded p-2 mb-2" 
                 style="cursor: pointer; transition: all 0.2s;" 
                 onclick="insertNoteIntoPrompt(${note.id}, '${escapeForJS(note.title)}', '${escapeForJS(note.description)}', '${note.note_type}')"
                 onmouseover="this.style.backgroundColor='#e9ecef'"
                 onmouseout="this.style.backgroundColor='white'">
                <div class="d-flex align-items-start">
                    <div class="me-2">
                        <i class="fas ${typeIcons[note.note_type]} text-${typeColors[note.note_type]}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold small">${escapeHtml(note.title)}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            ${new Date(note.date_incident).toLocaleDateString('fr-FR')} - 
                            <span class="badge bg-${typeColors[note.note_type]} badge-sm">${note.note_type}</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        $('#notesGrid').html(notesHTML);
        console.log(`✅ ${notes.length} notes chargées`);

    } catch (error) {
        console.error('Erreur chargement notes:', error);
        $('#notesGrid').html('<div class="alert alert-danger alert-sm">Erreur chargement notes</div>');
    }
}

/**
 * Insère une note dans le prompt
 */
function insertNoteIntoPrompt(noteId, title, description, type) {
    const currentPrompt = $('#promptEditor').val();
    const noteText = `\n\n=== NOTE ${type.toUpperCase()} ===\n${title}\n${description}\n`;

    // Insérer à la fin du prompt
    $('#promptEditor').val(currentPrompt + noteText);

    // Scroll vers le bas
    const textarea = document.getElementById('promptEditor');
    textarea.scrollTop = textarea.scrollHeight;

    // Feedback visuel
    showNotification(`Note "${title}" ajoutée au prompt`, 'success');
}

/**
 * Échappe les caractères pour utilisation dans attributs JS
 */
function escapeForJS(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\n/g, '\\n');
}

/**
 * Construit le prompt complet qui sera envoyé à l'IA
 */
function buildCompletePrompt(profile, kpiData, employeeId, selectedKPIData) {
    let prompt = profile.system_prompt || '';

    // Mode comparaison activé avec période B
    if (isComparisonMode && periodBEmployeeKPIData && employeeId) {
        prompt += "\n\n=== COMPARAISON DE PÉRIODES ===\n\n";
        prompt += "Voici les KPI de deux périodes différentes. Je veux que tu fasses une comparaison complète de ces deux périodes.\n\n";

        // Période A
        prompt += `**Période A** : Du ${dashboard.currentFilters.date_start} au ${dashboard.currentFilters.date_end}\n\n`;
        for (const [category, data] of Object.entries(kpiData)) {
            prompt += `${category.toUpperCase().replace(/_/g, ' ')}:\n`;
            for (const [key, value] of Object.entries(data)) {
                const formattedKey = key.replace(/_/g, ' ').charAt(0).toUpperCase() + key.replace(/_/g, ' ').slice(1);
                prompt += `  - ${formattedKey}: ${formatPromptValue(value)}\n`;
            }
        }

        // Période B - Filtrer selon les mêmes KPI que période A
        prompt += `\n**Période B** : Du ${periodBStartDate} au ${periodBEndDate}\n\n`;
        for (const [category, data] of Object.entries(kpiData)) {
            const categoryBData = periodBEmployeeKPIData[category];
            if (categoryBData) {
                prompt += `${category.toUpperCase().replace(/_/g, ' ')}:\n`;
                for (const [key, value] of Object.entries(data)) {
                    const valueB = categoryBData[key];
                    const formattedKey = key.replace(/_/g, ' ').charAt(0).toUpperCase() + key.replace(/_/g, ' ').slice(1);
                    prompt += `  - ${formattedKey}: ${formatPromptValue(valueB !== undefined ? valueB : 0)}\n`;
                }
            }
        }

        // Contexte employé
        if (selectedKPIData) {
            prompt += `\n=== CONTEXTE ===\n`;
            prompt += `Analyse comparative pour l'employé: ${selectedKPIData.employee_name || 'ID ' + employeeId}\n`;
        }

        prompt += `\n\nAnalyse l'évolution et la progression entre ces deux périodes. `;
        prompt += `Identifie les améliorations, les dégradations et les tendances. `;
        prompt += `Sois factuel, précis et constructif. Utilise des émojis pertinents.`;

    } else {
        // Mode normal (sans comparaison)
        prompt += "\n\n=== DONNÉES À ANALYSER ===\n\n";

        // Formater les données KPI
        for (const [category, data] of Object.entries(kpiData)) {
            prompt += `\n${category.toUpperCase().replace(/_/g, ' ')}:\n`;
            for (const [key, value] of Object.entries(data)) {
                const formattedKey = key.replace(/_/g, ' ').charAt(0).toUpperCase() + key.replace(/_/g, ' ').slice(1);
                prompt += `  - ${formattedKey}: ${formatPromptValue(value)}\n`;
            }
        }

        // Ajouter contexte si analyse par employé
        if (employeeId && selectedKPIData) {
            prompt += `\n\n=== CONTEXTE ===\n`;
            prompt += `Analyse pour l'employé: ${selectedKPIData.employee_name || 'ID ' + employeeId}\n`;
            prompt += `Type d'analyse: ${selectedKPIData.analysis_type}\n`;
        }

        prompt += `\n\n=== PÉRIODE ===\n`;
        prompt += `Du ${dashboard.currentFilters.date_start} au ${dashboard.currentFilters.date_end}\n`;

        prompt += `\n\nGénère une analyse détaillée et structurée selon ton rôle. `;
        prompt += `Sois factuel, précis et constructif. Utilise des émojis pertinents pour rendre l'analyse plus visuelle.`;
    }

    return prompt;
}

/**
 * Formate une valeur pour affichage dans le prompt
 */
function formatPromptValue(value) {
    if (typeof value === 'number') {
        if (value > 100 || value.toString().includes('.')) {
            return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
        }
        return value;
    }
    return value;
}

/**
 * Confirme et envoie le prompt (éventuellement modifié) à l'IA
 */
function confirmAndSendPrompt() {
    const editedPrompt = $('#promptEditor').val();

    // Fermer la modal de prévisualisation
    bootstrap.Modal.getInstance(document.getElementById('promptPreviewModal')).hide();

    // Rouvrir la modal d'analyse pour afficher le loader et les résultats
    const analysisModal = document.getElementById('aiAnalysisModal');
    if (analysisModal) {
        const bsModal = bootstrap.Modal.getInstance(analysisModal) || new bootstrap.Modal(analysisModal);
        bsModal.show();
    }

    // Lancer l'analyse avec le prompt modifié
    launchAIAnalysisWithCustomPrompt(pendingEmployeeId, pendingAnalysisData, editedPrompt);
}

/**
 * Lance l'analyse IA avec un prompt personnalisé
 */
async function launchAIAnalysisWithCustomPrompt(employeeId, selectedKPIData, customPrompt) {
    if (!currentAIProfileId) {
        console.error('Aucun profil sélectionné');
        return;
    }

    const analysisType = employeeId ? 'employé' : 'globale';
    console.log(`🚀 Lancement analyse ${analysisType} avec prompt personnalisé`);

    // Masquer la section KPI appropriée et afficher le loading
    if (employeeId) {
        $('#aiEmployeeKPISection').fadeOut(300, () => {
            $('#aiLoadingSection').fadeIn(300);
        });
    } else {
        $('#aiGlobalKPISection').fadeOut(300, () => {
            $('#aiLoadingSection').fadeIn(300);
        });
    }

    try {
        // Envoyer directement le prompt personnalisé au backend
        const response = await fetch('/ajax/generate_ai_analysis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                profile_id: currentAIProfileId,
                custom_prompt: customPrompt,  // Prompt personnalisé
                employee_id: employeeId,
                selected_kpi_data: selectedKPIData,
                date_start: dashboard.currentFilters.date_start,
                date_end: dashboard.currentFilters.date_end
            })
        });

        const data = await response.json();

        // Masquer le loading, afficher les résultats
        $('#aiLoadingSection').fadeOut(300, () => {
            if (data.success) {
                const formatted = dashboard.formatAIAnalysis(data.data.analysis);

                // Initialiser l'historique de conversation avec l'analyse initiale
                conversationHistory = [{
                    role: 'assistant',
                    content: data.data.analysis
                }];

                // Ajouter boutons + zone conversation
                const contentWithButtons = `
                    <div class="d-flex justify-content-end gap-2 mb-3">
                        <button class="btn btn-outline-primary" onclick="printAIAnalysis()">
                            <i class="fas fa-print me-2"></i>Imprimer
                        </button>
                        <button class="btn btn-outline-success" onclick="toggleConversationZone()">
                            <i class="fas fa-comment-dots me-2"></i>Répondre
                        </button>
                    </div>
                    <div id="aiAnalysisPrintContent">${formatted}</div>
                    
                    <!-- Zone de conversation -->
                    <div id="conversationZone" class="mt-4" style="display: none;">
                        <hr>
                        <div id="conversationHistory" class="mb-3"></div>
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-reply me-2"></i>Votre question / demande d'amélioration
                                </h6>
                                <textarea 
                                    id="conversationInput" 
                                    class="form-control mb-3" 
                                    rows="3" 
                                    placeholder="Ex: Peux-tu détailler davantage les points d'amélioration ?"></textarea>
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-secondary btn-sm" onclick="toggleConversationZone()">
                                        <i class="fas fa-times me-2"></i>Annuler
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="sendConversationMessage()">
                                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#aiAnalysisContent').html(contentWithButtons);
                $('#aiResultsSection').fadeIn(400);
                console.log('✅ Analyse IA générée avec succès (prompt personnalisé)');
            } else {
                $('#aiAnalysisContent').html(`<div class="alert alert-danger">${data.error || 'Erreur lors de la génération'}</div>`);
                $('#aiResultsSection').fadeIn(400);
                console.error('❌ Erreur API:', data.error);
            }
        });

    } catch (error) {
        console.error('💥 Erreur génération IA:', error);
        $('#aiLoadingSection').fadeOut(300, () => {
            $('#aiAnalysisContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur lors de la génération de l\'analyse</div>');
            $('#aiResultsSection').fadeIn(400);
        });
    }
}

async function launchAIAnalysis(employeeId = null, selectedKPIData = null) {
    if (!currentAIProfileId) {
        console.error('Aucun profil sélectionné');
        return;
    }

    const analysisType = employeeId ? 'employé' : 'globale';
    console.log(`🚀 Lancement analyse ${analysisType} pour profil #${currentAIProfileId}`);

    if (selectedKPIData) {
        console.log('📊 Données KPI sélectionnées:', selectedKPIData);
    }

    try {
        let kpiData;

        // SI des données filtrées sont fournies, les utiliser directement
        if (selectedKPIData && selectedKPIData.filteredKPIData) {
            console.log('✅ Utilisation des données KPI filtrées (pas d\'appels API)');
            kpiData = selectedKPIData.filteredKPIData;
        } else {
            // SINON, récupérer toutes les données KPI via API
            console.log('📡 Récupération de toutes les données KPI via API');
            kpiData = {
                ca_global: await dashboard.fetchAPI('chiffre_affaires_global'),
                ca_employe: await dashboard.fetchAPI('chiffre_affaires_employe'),
                kpi_reparations: await dashboard.fetchAPI('kpi_reparations'),
                gardiennage: await dashboard.fetchAPI('analyse_gardiennage')
            };
        }

        // Si analyse par employé, récupérer aussi ses notes
        let employeeNotes = null;
        if (employeeId) {
            try {
                const notesResponse = await fetch(`/ajax/employee_notes_api.php?action=get_notes&employee_id=${employeeId}`);
                const notesData = await notesResponse.json();
                if (notesData.success) {
                    employeeNotes = notesData.data;
                }
            } catch (err) {
                console.warn('Impossible de charger les notes employé:', err);
            }
        }

        // Appeler l'API d'analyse IA
        const response = await fetch('/ajax/generate_ai_analysis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                profile_id: currentAIProfileId,
                kpi_data: kpiData,
                employee_id: employeeId,
                employee_notes: employeeNotes,
                selected_kpi_data: selectedKPIData, // Données de sélection pour le backend
                date_start: dashboard.currentFilters.date_start,
                date_end: dashboard.currentFilters.date_end
            })
        });

        const data = await response.json();

        // Masquer le loading, afficher les résultats
        $('#aiLoadingSection').fadeOut(300, () => {
            if (data.success) {
                const formatted = dashboard.formatAIAnalysis(data.data.analysis);

                // Initialiser l'historique de conversation
                conversationHistory = [{
                    role: 'assistant',
                    content: data.data.analysis
                }];

                // Ajouter boutons + zone conversation
                const contentWithButtons = `
                    <div class="d-flex justify-content-end gap-2 mb-3">
                        <button class="btn btn-outline-primary" onclick="printAIAnalysis()">
                            <i class="fas fa-print me-2"></i>Imprimer
                        </button>
                        <button class="btn btn-outline-success" onclick="toggleConversationZone()">
                            <i class="fas fa-comment-dots me-2"></i>Répondre
                        </button>
                    </div>
                    <div id="aiAnalysisPrintContent">${formatted}</div>
                    
                    <!-- Zone de conversation -->
                    <div id="conversationZone" class="mt-4" style="display: none;">
                        <hr>
                        <div id="conversationHistory" class="mb-3"></div>
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-reply me-2"></i>Votre question / demande d'amélioration
                                </h6>
                                <textarea 
                                    id="conversationInput" 
                                    class="form-control mb-3" 
                                    rows="3" 
                                    placeholder="Ex: Peux-tu détailler davantage les points d'amélioration ?"></textarea>
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-secondary btn-sm" onclick="toggleConversationZone()">
                                        <i class="fas fa-times me-2"></i>Annuler
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="sendConversationMessage()">
                                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#aiAnalysisContent').html(contentWithButtons);
                $('#aiResultsSection').fadeIn(400);
                console.log('✅ Analyse IA générée avec succès');
            } else {
                $('#aiAnalysisContent').html(`<div class="alert alert-danger">${data.error || 'Erreur lors de la génération'}</div>`);
                $('#aiResultsSection').fadeIn(400);
                console.error('❌ Erreur API:', data.error);
            }
        });

    } catch (error) {
        console.error('💥 Erreur génération IA:', error);
        $('#aiLoadingSection').fadeOut(300, () => {
            $('#aiAnalysisContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur lors de la génération de l\'analyse</div>');
            $('#aiResultsSection').fadeIn(400);
        });
    }
}

/**
 * Toggle affichage zone conversation
 */
function toggleConversationZone() {
    const zone = $('#conversationZone');
    if (zone.is(':visible')) {
        zone.slideUp();
    } else {
        zone.slideDown();
        $('#conversationInput').focus();
    }
}

/**
 * Envoie un message dans la conversation avec l'IA
 */
async function sendConversationMessage() {
    const userMessage = $('#conversationInput').val().trim();

    if (!userMessage) {
        alert('⚠️ Veuillez saisir votre question ou demande');
        return;
    }

    // Ajouter le message utilisateur à l'historique
    conversationHistory.push({
        role: 'user',
        content: userMessage
    });

    // Afficher le message utilisateur
    appendConversationMessage('user', userMessage);

    // Vider le champ et désactiver le bouton
    $('#conversationInput').val('').prop('disabled', true);
    $('button[onclick="sendConversationMessage()"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Envoi...');

    try {
        // Construire le prompt de conversation
        let conversationPrompt = "Voici l'historique de notre conversation:\n\n";
        conversationHistory.forEach((msg, index) => {
            conversationPrompt += `${msg.role === 'user' ? '👤 Utilisateur' : '🤖 Assistant'}: ${msg.content}\n\n`;
        });
        conversationPrompt += "\nRéponds à la dernière question de l'utilisateur en te basant sur le contexte de l'analyse précédente.";

        // Appeler l'API avec le prompt de conversation
        const response = await fetch('/ajax/generate_ai_analysis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                profile_id: currentAIProfileId,
                custom_prompt: conversationPrompt,
                kpi_data: {},
                date_start: dashboard.currentFilters.date_start,
                date_end: dashboard.currentFilters.date_end
            })
        });

        const data = await response.json();

        if (data.success) {
            // Ajouter la réponse IA à l'historique
            conversationHistory.push({
                role: 'assistant',
                content: data.data.analysis
            });

            // Afficher la réponse
            appendConversationMessage('assistant', data.data.analysis);

            console.log('✅ Réponse IA reçue');
        } else {
            appendConversationMessage('error', 'Erreur: ' + (data.error || 'Impossible de générer une réponse'));
        }

    } catch (error) {
        console.error('Erreur conversation:', error);
        appendConversationMessage('error', 'Erreur lors de l\'envoi du message');
    } finally {
        // Réactiver les contrôles
        $('#conversationInput').prop('disabled', false);
        $('button[onclick="sendConversationMessage()"]').prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Envoyer');
    }
}

/**
 * Ajoute un message à l'historique de conversation affiché
 */
function appendConversationMessage(role, content) {
    const formatted = dashboard.formatAIAnalysis(content);
    const messageClass = role === 'user' ? 'bg-light' : role === 'error' ? 'bg-danger text-white' : 'bg-white border';
    const icon = role === 'user' ? 'fa-user' : role === 'error' ? 'fa-exclamation-triangle' : 'fa-robot';
    const roleLabel = role === 'user' ? 'Vous' : role === 'error' ? 'Erreur' : 'IA';

    const messageHTML = `
        <div class="card ${messageClass} mb-2">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas ${icon} me-2"></i>
                    <strong>${roleLabel}</strong>
                    <small class="text-muted ms-auto">${new Date().toLocaleTimeString('fr-FR')}</small>
                </div>
                <div>${formatted}</div>
            </div>
        </div>
    `;

    $('#conversationHistory').append(messageHTML);

    // Scroll vers le bas
    const historyDiv = document.getElementById('conversationHistory');
    historyDiv.scrollTop = historyDiv.scrollHeight;
}
