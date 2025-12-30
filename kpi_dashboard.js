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

    async fetchAPI(action) {
        const params = new URLSearchParams({
            action: action,
            date_start: this.currentFilters.date_start,
            date_end: this.currentFilters.date_end,
            ...(this.currentFilters.employee_id && { user_id: this.currentFilters.employee_id })
        });

        try {
            const response = await fetch(`../ajax/kpi_api.php?${params}`);

            // Vérifier si la réponse est OK
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const text = await response.text();

            // Vérifier si c'est du JSON valide
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Réponse non-JSON:', text.substring(0, 200));
                throw new Error('Réponse invalide du serveur');
            }

            if (!data.success) {
                throw new Error(data.error || 'Erreur API');
            }

            return data.data;

        } catch (error) {
            console.error(`Erreur API ${action}:`, error);
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
                            <button class="btn-ai-action" onclick="dashboard.generateAIAnalysis(${profile.id})">
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
        // Convertir markdown basique en HTML
        let html = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');

        return `<div class="ai-analysis-text"><p>${html}</p></div>`;
    }

    async loadAIProfiles() {
        // Gestion de l'onglet profils IA
        try {
            const response = await fetch('../ajax/get_ai_profiles.php');
            const data = await response.json();

            if (!data.success) {
                console.warn('Impossible de charger les profils IA:', data.error);
                return;
            }

            const profiles = data.data || [];
            // TODO: Afficher la liste des profils avec boutons d'action

        } catch (error) {
            console.error('Erreur chargement profils IA:', error);
        }
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
