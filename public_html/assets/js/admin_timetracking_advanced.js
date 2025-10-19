/**
 * JavaScript avancé pour l'interface admin de pointage
 * Fonctionnalités modernes et interactives
 */

class AdminTimeTrackingDashboard {
    constructor() {
        this.charts = {};
        this.refreshInterval = null;
        this.settings = {
            autoRefresh: true,
            refreshRate: 60000, // 1 minute
            enableAnimations: true,
            enableNotifications: true,
            theme: 'light'
        };
        this.filters = {
            date: new Date().toISOString().split('T')[0],
            user: '',
            status: '',
            department: ''
        };
        this.realTimeData = {
            activeUsers: [],
            stats: {},
            alerts: []
        };
        
        this.init();
    }

    async init() {
        await this.loadSettings();
        this.initializeCharts();
        this.bindEvents();
        this.startRealTimeUpdates();
        this.initializeNotifications();
        this.setupKeyboardShortcuts();
        this.initializeTooltips();
        
        console.log('Admin Time Tracking Dashboard initialized');
    }

    // === GESTION DES PARAMÈTRES ===
    async loadSettings() {
        try {
            const savedSettings = localStorage.getItem('adminTimeTrackingSettings');
            if (savedSettings) {
                this.settings = { ...this.settings, ...JSON.parse(savedSettings) };
            }
        } catch (error) {
            console.warn('Erreur chargement paramètres:', error);
        }
    }

    saveSettings() {
        try {
            localStorage.setItem('adminTimeTrackingSettings', JSON.stringify(this.settings));
            this.showNotification('Paramètres sauvegardés', 'success');
        } catch (error) {
            console.error('Erreur sauvegarde paramètres:', error);
            this.showNotification('Erreur de sauvegarde', 'error');
        }
    }

    // === INITIALISATION DES GRAPHIQUES ===
    initializeCharts() {
        this.initWeeklyChart();
        this.initTeamChart();
        this.initProductivityChart();
        this.initEmployeeChart();
        this.initAttendanceChart();
    }

    initWeeklyChart() {
        const ctx = document.getElementById('weeklyChart');
        if (!ctx) return;

        this.charts.weekly = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Heures travaillées',
                    data: [],
                    borderColor: 'rgb(0, 102, 204)',
                    backgroundColor: 'rgba(0, 102, 204, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(0, 102, 204)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }, {
                    label: 'Employés actifs',
                    data: [],
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1',
                    pointBackgroundColor: 'rgb(40, 167, 69)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(0, 102, 204, 0.8)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Heures',
                            color: '#6c757d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: '#6c757d'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Employés',
                            color: '#6c757d'
                        },
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            color: '#6c757d'
                        }
                    }
                },
                animation: {
                    duration: this.settings.enableAnimations ? 800 : 0,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    initTeamChart() {
        const ctx = document.getElementById('teamChart');
        if (!ctx) return;

        this.charts.team = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Actifs', 'En pause', 'Hors ligne'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        'rgb(40, 167, 69)',
                        'rgb(255, 193, 7)',
                        'rgb(108, 117, 125)'
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            generateLabels: (chart) => {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: `${label}: ${data.datasets[0].data[i]}`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].backgroundColor[i],
                                    pointStyle: 'circle',
                                    hidden: false
                                }));
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.parsed / total) * 100);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    duration: this.settings.enableAnimations ? 1000 : 0,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    initProductivityChart() {
        const ctx = document.getElementById('productivityChart');
        if (!ctx) return;

        this.charts.productivity = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Productivité (%)',
                    data: [],
                    backgroundColor: 'rgba(0, 102, 204, 0.8)',
                    borderColor: 'rgb(0, 102, 204)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Productivité (%)',
                            color: '#6c757d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: '#6c757d',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: this.settings.enableAnimations ? 1200 : 0,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    initEmployeeChart() {
        const ctx = document.getElementById('employeeAvgChart');
        if (!ctx) return;

        this.charts.employeeAvg = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Temps moyen (h)',
                    data: [],
                    borderColor: 'rgb(23, 162, 184)',
                    backgroundColor: 'rgba(23, 162, 184, 0.2)',
                    pointBackgroundColor: 'rgb(23, 162, 184)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(23, 162, 184)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            color: '#6c757d',
                            callback: function(value) {
                                return value + 'h';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        angleLines: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                animation: {
                    duration: this.settings.enableAnimations ? 1000 : 0
                }
            }
        });
    }

    initAttendanceChart() {
        const ctx = document.getElementById('attendanceChart');
        if (!ctx) return;

        this.charts.attendance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                datasets: [{
                    label: 'Présence',
                    data: [85, 92, 88, 90, 86, 45, 20],
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(40, 167, 69)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Présence (%)',
                            color: '#6c757d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: '#6c757d',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: this.settings.enableAnimations ? 800 : 0
                }
            }
        });
    }

    // === GESTION DES ÉVÉNEMENTS ===
    bindEvents() {
        // Onglets
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.handleTabChange(e.target.id);
            });
        });

        // Filtres
        document.getElementById('date')?.addEventListener('change', (e) => {
            this.filters.date = e.target.value;
            this.applyFilters();
        });

        document.getElementById('user')?.addEventListener('change', (e) => {
            this.filters.user = e.target.value;
            this.applyFilters();
        });

        document.getElementById('status')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.applyFilters();
        });

        // Paramètres
        document.getElementById('refreshInterval')?.addEventListener('change', (e) => {
            this.settings.refreshRate = parseInt(e.target.value) * 1000;
            this.restartRealTimeUpdates();
        });

        document.getElementById('enableAnimations')?.addEventListener('change', (e) => {
            this.settings.enableAnimations = e.target.checked;
            this.saveSettings();
        });

        // Checkbox sélection multiple
        document.getElementById('selectAll')?.addEventListener('change', (e) => {
            const checkboxes = document.querySelectorAll('.entry-checkbox');
            checkboxes.forEach(cb => cb.checked = e.target.checked);
        });

        // Recherche en temps réel
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 300);
            });
        }

        // Boutons d'action
        this.bindActionButtons();
    }

    bindActionButtons() {
        // Export
        document.getElementById('exportBtn')?.addEventListener('click', () => {
            this.exportData();
        });

        // Actualisation
        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            this.refreshDashboard();
        });

        // Actions groupées
        document.getElementById('approveAllBtn')?.addEventListener('click', () => {
            this.approveSelectedEntries();
        });

        document.getElementById('deleteSelectedBtn')?.addEventListener('click', () => {
            this.deleteSelectedEntries();
        });
    }

    handleTabChange(tabId) {
        switch(tabId) {
            case 'live-tab':
                this.focusRealTimeUpdates();
                break;
            case 'reports-tab':
                this.loadReportsData();
                break;
            case 'alerts-tab':
                this.refreshAlerts();
                break;
        }
    }

    // === MISES À JOUR EN TEMPS RÉEL ===
    startRealTimeUpdates() {
        if (this.settings.autoRefresh) {
            this.refreshInterval = setInterval(() => {
                this.updateRealTimeData();
            }, this.settings.refreshRate);
        }
    }

    stopRealTimeUpdates() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    restartRealTimeUpdates() {
        this.stopRealTimeUpdates();
        this.startRealTimeUpdates();
    }

    async updateRealTimeData() {
        try {
            const response = await fetch('admin_timetracking_improved.php?ajax=realtime_data');
            const data = await response.json();
            
            if (data.success) {
                this.realTimeData = data.data;
                this.updateUIWithRealTimeData();
                this.updateLastUpdate();
            }
        } catch (error) {
            console.error('Erreur mise à jour temps réel:', error);
        }
    }

    updateUIWithRealTimeData() {
        // Mettre à jour les stats
        this.updateStatsCards();
        
        // Mettre à jour les utilisateurs actifs
        this.updateActiveUsersDisplay();
        
        // Mettre à jour les graphiques
        this.updateChartsData();
        
        // Mettre à jour les alertes
        this.updateAlertsDisplay();
    }

    updateStatsCards() {
        const stats = this.realTimeData.stats;
        
        document.querySelector('[data-stat="currently_working"]')?.textContent = stats.currently_working || 0;
        document.querySelector('[data-stat="on_break"]')?.textContent = stats.on_break || 0;
        document.querySelector('[data-stat="total_hours"]')?.textContent = (stats.total_work_hours || 0).toFixed(1) + 'h';
        document.querySelector('[data-stat="pending_approvals"]')?.textContent = stats.pending_approvals || 0;
    }

    updateActiveUsersDisplay() {
        const container = document.getElementById('activeUsersContainer');
        if (!container) return;

        const users = this.realTimeData.activeUsers;
        
        // Mettre à jour les durées existantes
        users.forEach(user => {
            const durationElement = document.getElementById(`duration-${user.user_id}`);
            if (durationElement) {
                durationElement.textContent = user.formatted_duration;
                
                // Mettre à jour la barre de progression
                const progressBar = durationElement.closest('.card').querySelector('.progress-bar');
                if (progressBar) {
                    const percentage = Math.min((user.current_duration / 8) * 100, 100);
                    progressBar.style.width = percentage + '%';
                    
                    // Changer la couleur selon la durée
                    progressBar.className = 'progress-bar ';
                    if (user.duration_status === 'overtime') {
                        progressBar.classList.add('bg-danger');
                    } else if (user.duration_status === 'normal') {
                        progressBar.classList.add('bg-success');
                    } else {
                        progressBar.classList.add('bg-info');
                    }
                }
            }
        });
    }

    updateChartsData() {
        // Mettre à jour le graphique en donut des équipes
        if (this.charts.team) {
            const stats = this.realTimeData.stats;
            this.charts.team.data.datasets[0].data = [
                stats.currently_working || 0,
                stats.on_break || 0,
                Math.max(0, (stats.active_employees || 0) - (stats.currently_working || 0) - (stats.on_break || 0))
            ];
            this.charts.team.update('none');
        }
    }

    updateAlertsDisplay() {
        const alertsContainer = document.querySelector('#alerts .card-body');
        if (!alertsContainer) return;

        const alerts = this.realTimeData.alerts;
        
        if (alerts.length === 0) {
            alertsContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4 class="text-success">Aucune alerte active</h4>
                    <p class="text-muted">Tout semble fonctionner normalement</p>
                </div>
            `;
        } else {
            const alertsHTML = alerts.map(alert => `
                <div class="alert-item ${alert.type} p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="${alert.icon} fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-1">${alert.title}</h6>
                                <p class="mb-0">${alert.message}</p>
                            </div>
                        </div>
                        <div>
                            ${alert.action ? `
                                <button class="btn btn-sm btn-outline-dark" 
                                        onclick="adminDashboard.handleAlert('${alert.action}', ${alert.user_id || null})">
                                    Résoudre
                                </button>
                            ` : ''}
                            <button class="btn btn-sm btn-outline-secondary" onclick="this.closest('.alert-item').style.display='none'">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            alertsContainer.innerHTML = alertsHTML;
        }

        // Mettre à jour le badge sur l'onglet
        const alertTab = document.getElementById('alerts-tab');
        if (alertTab) {
            const badge = alertTab.querySelector('.badge');
            if (alerts.length > 0) {
                if (badge) {
                    badge.textContent = alerts.length;
                } else {
                    alertTab.insertAdjacentHTML('beforeend', `<span class="badge bg-danger ms-1">${alerts.length}</span>`);
                }
            } else if (badge) {
                badge.remove();
            }
        }
    }

    updateLastUpdate() {
        const element = document.getElementById('lastUpdate');
        if (element) {
            element.textContent = new Date().toLocaleTimeString();
        }
    }

    // === GESTION DES ACTIONS ===
    async forceClockOut(userId, userName) {
        if (!confirm(`Forcer le pointage de sortie de ${userName} ?`)) return;
        
        this.showLoadingState(true);
        
        try {
            const formData = new FormData();
            formData.append('action', 'force_clock_out');
            formData.append('user_id', userId);
            
            const response = await fetch('admin_timetracking_improved.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(result.message, 'success');
                this.updateRealTimeData();
                this.refreshActiveUsers();
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Erreur force clock out:', error);
            this.showNotification('Erreur de connexion', 'error');
        } finally {
            this.showLoadingState(false);
        }
    }

    async approveEntry(entryId) {
        try {
            const formData = new FormData();
            formData.append('action', 'approve_entry');
            formData.append('entry_id', entryId);
            
            const response = await fetch('admin_timetracking_improved.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(result.message, 'success');
                this.updateEntryVisualStatus(entryId, 'approved');
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Erreur approve entry:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    async approveSelectedEntries() {
        const selectedEntries = Array.from(document.querySelectorAll('.entry-checkbox:checked'))
            .map(cb => cb.value);
            
        if (selectedEntries.length === 0) {
            this.showNotification('Aucune entrée sélectionnée', 'warning');
            return;
        }
        
        if (!confirm(`Approuver ${selectedEntries.length} entrée(s) ?`)) return;
        
        this.showLoadingState(true);
        
        try {
            const promises = selectedEntries.map(entryId => this.approveEntry(entryId));
            await Promise.all(promises);
            
            this.showNotification(`${selectedEntries.length} entrée(s) approuvée(s)`, 'success');
            this.refreshTableData();
        } catch (error) {
            console.error('Erreur approbation groupée:', error);
            this.showNotification('Erreur lors de l\'approbation groupée', 'error');
        } finally {
            this.showLoadingState(false);
        }
    }

    async sendNotification(userId, message) {
        try {
            const formData = new FormData();
            formData.append('action', 'send_notification');
            formData.append('user_id', userId);
            formData.append('message', message);
            
            const response = await fetch('admin_timetracking_improved.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(result.message, 'success');
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Erreur envoi notification:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    // === GESTION DES MODALS ===
    openEditModal(entryId) {
        const modal = new bootstrap.Modal(document.getElementById('editEntryModal'));
        document.getElementById('edit_entry_id').value = entryId;
        
        // Charger les données de l'entrée (à implémenter)
        this.loadEntryData(entryId).then(data => {
            if (data) {
                document.getElementById('edit_clock_in').value = data.clock_in;
                document.getElementById('edit_clock_out').value = data.clock_out || '';
                document.getElementById('edit_notes').value = data.notes || '';
            }
        });
        
        modal.show();
    }

    openNotificationModal(userId) {
        const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
        document.getElementById('notification_user_id').value = userId;
        document.getElementById('notification_message').value = '';
        modal.show();
    }

    // === UTILITAIRES ===
    showNotification(message, type = 'info', duration = 5000) {
        if (!this.settings.enableNotifications) return;
        
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;
        
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        
        const colorMap = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info'
        };
        
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${colorMap[type]} border-0" id="${toastId}" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${iconMap[type]} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: duration });
        
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    showLoadingState(show) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = show ? 'flex' : 'none';
        }
        
        // Désactiver les boutons pendant le chargement
        const buttons = document.querySelectorAll('button:not([data-bs-dismiss])');
        buttons.forEach(btn => {
            if (show) {
                btn.disabled = true;
                btn.classList.add('loading');
            } else {
                btn.disabled = false;
                btn.classList.remove('loading');
            }
        });
    }

    async loadEntryData(entryId) {
        try {
            const response = await fetch(`admin_timetracking_improved.php?ajax=get_entry&id=${entryId}`);
            const result = await response.json();
            return result.success ? result.data : null;
        } catch (error) {
            console.error('Erreur chargement entrée:', error);
            return null;
        }
    }

    updateEntryVisualStatus(entryId, status) {
        const row = document.querySelector(`tr[data-entry-id="${entryId}"]`);
        if (!row) return;
        
        const statusCell = row.cells[6]; // Colonne statut
        
        if (status === 'approved') {
            statusCell.innerHTML += '<br><i class="fas fa-check-circle text-success mt-1" title="Approuvé"></i>';
            row.querySelector('.btn-outline-success')?.remove();
        }
    }

    // === RECHERCHE ET FILTRES ===
    performSearch(query) {
        const rows = document.querySelectorAll('#historyTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(query.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
        
        // Mettre à jour le compteur de résultats
        const visibleRows = document.querySelectorAll('#historyTable tbody tr:not([style*="display: none"])');
        const counter = document.getElementById('searchResults');
        if (counter) {
            counter.textContent = `${visibleRows.length} résultat(s) trouvé(s)`;
        }
    }

    applyFilters() {
        // Reconstruire l'URL avec les nouveaux filtres
        const params = new URLSearchParams();
        
        if (this.filters.date) params.set('date', this.filters.date);
        if (this.filters.user) params.set('user', this.filters.user);
        if (this.filters.status) params.set('status', this.filters.status);
        
        // Recharger la page avec les nouveaux filtres
        window.location.search = params.toString();
    }

    // === EXPORT ET RAPPORTS ===
    exportData(format = 'csv') {
        const params = new URLSearchParams(window.location.search);
        params.set('export', format);
        
        this.showNotification('Export en cours...', 'info');
        
        window.open('admin_timetracking_improved.php?' + params.toString(), '_blank');
    }

    generateReport(type) {
        this.showNotification(`Génération du rapport ${type} en cours...`, 'info');
        
        // Simulation de génération de rapport
        setTimeout(() => {
            this.showNotification(`Rapport ${type} généré avec succès`, 'success');
        }, 2000);
    }

    // === RACCOURCIS CLAVIER ===
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+R : Actualiser
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.refreshDashboard();
            }
            
            // Ctrl+E : Export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                this.exportData();
            }
            
            // Ctrl+A : Sélectionner tout
            if (e.ctrlKey && e.key === 'a' && e.target.closest('table')) {
                e.preventDefault();
                const selectAll = document.getElementById('selectAll');
                if (selectAll) {
                    selectAll.checked = true;
                    selectAll.dispatchEvent(new Event('change'));
                }
            }
            
            // Échap : Fermer les modals
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    bootstrap.Modal.getInstance(modal)?.hide();
                });
            }
        });
    }

    // === TOOLTIPS ===
    initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // === MÉTHODES PUBLIQUES ===
    refreshDashboard() {
        this.showLoadingState(true);
        this.updateRealTimeData().finally(() => {
            this.showLoadingState(false);
            this.showNotification('Dashboard actualisé', 'success');
        });
    }

    refreshActiveUsers() {
        this.updateRealTimeData();
    }

    refreshTableData() {
        // Recharger les données du tableau
        window.location.reload();
    }

    handleAlert(action, userId) {
        switch(action) {
            case 'force_clock_out':
                if (userId) {
                    this.forceClockOut(userId, 'cet employé');
                }
                break;
            case 'view_pending':
                document.querySelector('#history-tab').click();
                break;
        }
    }

    focusRealTimeUpdates() {
        // Augmenter la fréquence de mise à jour pour l'onglet temps réel
        this.stopRealTimeUpdates();
        this.refreshInterval = setInterval(() => {
            this.updateRealTimeData();
        }, 15000); // 15 secondes
    }

    loadReportsData() {
        // Charger les données spécifiques aux rapports
        this.showNotification('Chargement des rapports...', 'info');
    }

    refreshAlerts() {
        // Actualiser uniquement les alertes
        this.updateRealTimeData();
    }
}

// Initialisation globale
let adminDashboard;

document.addEventListener('DOMContentLoaded', function() {
    adminDashboard = new AdminTimeTrackingDashboard();
    
    // Rendre accessible globalement pour les événements inline
    window.adminDashboard = adminDashboard;
    
    // Fonctions globales pour compatibilité avec le HTML existant
    window.forceClockOut = (userId, userName) => adminDashboard.forceClockOut(userId, userName);
    window.approveEntry = (entryId) => adminDashboard.approveEntry(entryId);
    window.sendNotification = (userId) => adminDashboard.openNotificationModal(userId);
    window.editEntry = (entryId) => adminDashboard.openEditModal(entryId);
    window.refreshDashboard = () => adminDashboard.refreshDashboard();
    window.exportData = () => adminDashboard.exportData();
    window.generateReport = (type) => adminDashboard.generateReport(type);
    window.handleAlert = (action, userId) => adminDashboard.handleAlert(action, userId);
    window.saveSettings = () => adminDashboard.saveSettings();
    window.approveAll = () => adminDashboard.approveSelectedEntries();
    window.setNotificationMessage = (message) => {
        document.getElementById('notification_message').value = message;
    };
    window.viewUserDetails = (userId) => {
        window.open(`user_timetracking_details.php?user_id=${userId}`, '_blank');
    };
    window.viewEntryDetails = (entryId) => {
        adminDashboard.showNotification('Fonctionnalité en développement', 'info');
    };
    window.exportFilteredData = () => adminDashboard.exportData();
    window.dismissAlert = (button) => {
        button.closest('.alert-item').style.display = 'none';
    };
});

// Gestion responsive des graphiques
window.addEventListener('resize', function() {
    if (adminDashboard && adminDashboard.charts) {
        Object.values(adminDashboard.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }
});

// Gestion de la visibilité de la page (pause/reprise des mises à jour)
document.addEventListener('visibilitychange', function() {
    if (adminDashboard) {
        if (document.hidden) {
            adminDashboard.stopRealTimeUpdates();
        } else {
            adminDashboard.startRealTimeUpdates();
            adminDashboard.updateRealTimeData(); // Mise à jour immédiate au retour
        }
    }
});

// Export de la classe pour usage externe
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminTimeTrackingDashboard;
}
