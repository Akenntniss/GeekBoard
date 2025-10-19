/**
 * SYSTÈME DE STATISTIQUES AVANCÉ - GEEKBOARD
 * Système complet et professionnel de statistiques avec modals avancés
 */

class AdvancedStatsSystem {
    constructor() {
        this.currentPeriod = 'today';
        this.currentStatType = null;
        this.charts = {};
        this.colors = {
            primary: '#667eea',
            secondary: '#764ba2',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6'
        };
        
        this.init();
    }

    init() {
        console.log('🚀 Initialisation du système de statistiques avancé');
        this.createModal();
        this.bindEvents();
    }

    /**
     * Créer le modal principal des statistiques
     */
    createModal() {
        const modalHTML = `
        <div class="modal fade" id="advancedStatsModal" tabindex="-1" aria-labelledby="advancedStatsModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content advanced-stats-modal">
                    <!-- Header -->
                    <div class="modal-header advanced-stats-header">
                        <div class="header-content">
                            <div class="stat-icon-large">
                                <i class="fas fa-chart-line" id="modalStatIcon"></i>
                            </div>
                            <div class="header-text">
                                <h1 class="modal-title" id="advancedStatsModalLabel">Statistiques Avancées</h1>
                                <p class="modal-subtitle" id="modalSubtitle">Analyse détaillée des performances</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body advanced-stats-body">
                        <!-- Contrôles de période -->
                        <div class="period-controls">
                            <div class="period-buttons">
                                <button class="period-btn active" data-period="today">Aujourd'hui</button>
                                <button class="period-btn" data-period="week">Cette semaine</button>
                                <button class="period-btn" data-period="month">Ce mois</button>
                                <button class="period-btn" data-period="quarter">Ce trimestre</button>
                                <button class="period-btn" data-period="year">Cette année</button>
                                <button class="period-btn" data-period="custom">Personnalisé</button>
                            </div>
                            <div class="custom-period" id="customPeriod" style="display: none;">
                                <input type="date" id="startDate" class="form-control">
                                <span>à</span>
                                <input type="date" id="endDate" class="form-control">
                                <button class="btn btn-primary" onclick="advancedStats.applyCustomPeriod()">Appliquer</button>
                            </div>
                        </div>

                        <!-- Indicateurs principaux -->
                        <div class="main-indicators">
                            <div class="indicator-card">
                                <div class="indicator-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="indicator-content">
                                    <div class="indicator-value" id="mainValue">0</div>
                                    <div class="indicator-label" id="mainLabel">Valeur principale</div>
                                    <div class="indicator-change" id="mainChange">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>+0%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="indicator-card">
                                <div class="indicator-icon secondary">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="indicator-content">
                                    <div class="indicator-value" id="periodValue">0</div>
                                    <div class="indicator-label" id="periodLabel">Période précédente</div>
                                    <div class="indicator-change" id="periodChange">
                                        <i class="fas fa-arrow-down"></i>
                                        <span>-0%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="indicator-card">
                                <div class="indicator-icon success">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="indicator-content">
                                    <div class="indicator-value" id="bestValue">0</div>
                                    <div class="indicator-label" id="bestLabel">Meilleur jour</div>
                                    <div class="indicator-change positive">
                                        <i class="fas fa-star"></i>
                                        <span id="bestDate">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contenu principal -->
                        <div class="stats-content">
                            <!-- Navigation des onglets -->
                            <div class="stats-tabs">
                                <button class="stats-tab active" data-tab="overview">Vue d'ensemble</button>
                                <button class="stats-tab" data-tab="timeline">Évolution</button>
                                <button class="stats-tab" data-tab="breakdown">Répartition</button>
                                <button class="stats-tab" data-tab="performance">Performance</button>
                                <button class="stats-tab" data-tab="details">Détails</button>
                            </div>

                            <!-- Contenu des onglets -->
                            <div class="tab-content-container">
                                <!-- Onglet Vue d'ensemble -->
                                <div class="tab-pane active" id="overview">
                                    <div class="overview-grid">
                                        <div class="chart-container">
                                            <h3>Tendance générale</h3>
                                            <canvas id="overviewChart"></canvas>
                                        </div>
                                        <div class="metrics-container">
                                            <h3>Métriques clés</h3>
                                            <div class="metrics-list" id="keyMetrics">
                                                <!-- Métriques générées dynamiquement -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Onglet Évolution -->
                                <div class="tab-pane" id="timeline">
                                    <div class="timeline-controls">
                                        <select id="timelineGranularity" class="form-select">
                                            <option value="hour">Par heure</option>
                                            <option value="day" selected>Par jour</option>
                                            <option value="week">Par semaine</option>
                                            <option value="month">Par mois</option>
                                        </select>
                                    </div>
                                    <div class="chart-container full-width">
                                        <canvas id="timelineChart"></canvas>
                                    </div>
                                </div>

                                <!-- Onglet Répartition -->
                                <div class="tab-pane" id="breakdown">
                                    <div class="breakdown-grid">
                                        <div class="chart-container">
                                            <h3>Répartition principale</h3>
                                            <canvas id="breakdownChart"></canvas>
                                        </div>
                                        <div class="breakdown-details">
                                            <h3>Détails par catégorie</h3>
                                            <div class="breakdown-list" id="breakdownList">
                                                <!-- Liste générée dynamiquement -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Onglet Performance -->
                                <div class="tab-pane" id="performance">
                                    <div class="performance-grid">
                                        <div class="chart-container">
                                            <h3>Performance par employé</h3>
                                            <canvas id="performanceChart"></canvas>
                                        </div>
                                        <div class="performance-ranking">
                                            <h3>Classement</h3>
                                            <div class="ranking-list" id="performanceRanking">
                                                <!-- Classement généré dynamiquement -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Onglet Détails -->
                                <div class="tab-pane" id="details">
                                    <div class="details-container">
                                        <div class="details-filters">
                                            <input type="text" id="detailsSearch" class="form-control" placeholder="Rechercher...">
                                            <select id="detailsSort" class="form-select">
                                                <option value="date_desc">Plus récent</option>
                                                <option value="date_asc">Plus ancien</option>
                                                <option value="value_desc">Valeur décroissante</option>
                                                <option value="value_asc">Valeur croissante</option>
                                            </select>
                                        </div>
                                        <div class="details-table-container">
                                            <table class="table details-table" id="detailsTable">
                                                <thead>
                                                    <tr id="detailsTableHeader">
                                                        <!-- En-têtes générés dynamiquement -->
                                                    </tr>
                                                </thead>
                                                <tbody id="detailsTableBody">
                                                    <!-- Données générées dynamiquement -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="details-pagination">
                                            <button class="btn btn-outline-primary" id="prevPage">Précédent</button>
                                            <span id="pageInfo">Page 1 sur 1</span>
                                            <button class="btn btn-outline-primary" id="nextPage">Suivant</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer advanced-stats-footer">
                        <div class="footer-info">
                            <span id="lastUpdate">Dernière mise à jour : maintenant</span>
                        </div>
                        <div class="footer-actions">
                            <button type="button" class="btn btn-outline-light" onclick="advancedStats.exportData()">
                                <i class="fas fa-download"></i> Exporter
                            </button>
                            <button type="button" class="btn btn-outline-light" onclick="advancedStats.refreshData()">
                                <i class="fas fa-sync-alt"></i> Actualiser
                            </button>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

        // Ajouter le modal au DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    /**
     * Lier les événements
     */
    bindEvents() {
        // Boutons de période
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.changePeriod(e.target.dataset.period);
            });
        });

        // Onglets
        document.querySelectorAll('.stats-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.changeTab(e.target.dataset.tab);
            });
        });

        // Granularité de la timeline
        document.getElementById('timelineGranularity')?.addEventListener('change', (e) => {
            this.updateTimelineChart(e.target.value);
        });

        // Recherche et tri des détails
        document.getElementById('detailsSearch')?.addEventListener('input', (e) => {
            this.filterDetails(e.target.value);
        });

        document.getElementById('detailsSort')?.addEventListener('change', (e) => {
            this.sortDetails(e.target.value);
        });
    }

    /**
     * Ouvrir le modal avec un type de statistique
     */
    openModal(statType) {
        this.currentStatType = statType;
        console.log('📊 Ouverture du modal pour:', statType);

        // Configuration selon le type
        const config = this.getStatConfig(statType);
        
        // Mettre à jour l'interface
        document.getElementById('advancedStatsModalLabel').textContent = config.title;
        document.getElementById('modalSubtitle').textContent = config.subtitle;
        document.getElementById('modalStatIcon').className = config.icon;

        // Charger les données
        this.loadData(statType);

        // Afficher le modal
        const modal = new bootstrap.Modal(document.getElementById('advancedStatsModal'));
        modal.show();
    }

    /**
     * Configuration des types de statistiques
     */
    getStatConfig(statType) {
        const configs = {
            'nouvelles_reparations': {
                title: 'Nouvelles Réparations',
                subtitle: 'Analyse des nouvelles réparations reçues',
                icon: 'fas fa-plus-circle',
                color: '#667eea'
            },
            'reparations_effectuees': {
                title: 'Réparations Effectuées',
                subtitle: 'Analyse des réparations terminées',
                icon: 'fas fa-wrench',
                color: '#4facfe'
            },
            'reparations_restituees': {
                title: 'Réparations Restituées',
                subtitle: 'Analyse des réparations rendues aux clients',
                icon: 'fas fa-handshake',
                color: '#43e97b'
            },
            'devis_envoyes': {
                title: 'Devis Envoyés',
                subtitle: 'Analyse des devis transmis aux clients',
                icon: 'fas fa-file-invoice-dollar',
                color: '#fa709a'
            }
        };

        return configs[statType] || configs['nouvelles_reparations'];
    }

    /**
     * Charger les données
     */
    async loadData(statType) {
        try {
            console.log('🔄 Chargement des données pour:', statType);
            
            // Afficher le loader
            this.showLoader();

            // Appel AJAX pour récupérer les données
            const response = await fetch('ajax/get_advanced_stats.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: statType,
                    period: this.currentPeriod,
                    start_date: this.getStartDate(),
                    end_date: this.getEndDate()
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.updateInterface(data.data);
            } else {
                this.showError(data.message || 'Erreur lors du chargement des données');
            }

        } catch (error) {
            console.error('❌ Erreur:', error);
            this.showError('Erreur de connexion');
        } finally {
            this.hideLoader();
        }
    }

    /**
     * Mettre à jour l'interface avec les données
     */
    updateInterface(data) {
        console.log('📊 Mise à jour de l\'interface avec:', data);

        // Mettre à jour les indicateurs principaux
        this.updateMainIndicators(data.indicators);

        // Mettre à jour les graphiques
        this.updateCharts(data.charts);

        // Mettre à jour les métriques
        this.updateMetrics(data.metrics);

        // Mettre à jour les détails
        this.updateDetails(data.details);
    }

    /**
     * Mettre à jour les indicateurs principaux
     */
    updateMainIndicators(indicators) {
        document.getElementById('mainValue').textContent = indicators.main.value;
        document.getElementById('mainLabel').textContent = indicators.main.label;
        
        const mainChange = document.getElementById('mainChange');
        const changePercent = indicators.main.change;
        const changeIcon = mainChange.querySelector('i');
        const changeText = mainChange.querySelector('span');
        
        if (changePercent > 0) {
            changeIcon.className = 'fas fa-arrow-up';
            mainChange.className = 'indicator-change positive';
            changeText.textContent = `+${changePercent}%`;
        } else if (changePercent < 0) {
            changeIcon.className = 'fas fa-arrow-down';
            mainChange.className = 'indicator-change negative';
            changeText.textContent = `${changePercent}%`;
        } else {
            changeIcon.className = 'fas fa-minus';
            mainChange.className = 'indicator-change neutral';
            changeText.textContent = '0%';
        }

        // Indicateurs secondaires
        document.getElementById('periodValue').textContent = indicators.period.value;
        document.getElementById('periodLabel').textContent = indicators.period.label;
        
        document.getElementById('bestValue').textContent = indicators.best.value;
        document.getElementById('bestLabel').textContent = indicators.best.label;
        document.getElementById('bestDate').textContent = indicators.best.date;
    }

    /**
     * Mettre à jour les graphiques
     */
    updateCharts(chartsData) {
        // Graphique vue d'ensemble
        this.updateOverviewChart(chartsData.overview);
        
        // Graphique timeline
        this.updateTimelineChart('day', chartsData.timeline);
        
        // Graphique répartition
        this.updateBreakdownChart(chartsData.breakdown);
        
        // Graphique performance
        this.updatePerformanceChart(chartsData.performance);
    }

    /**
     * Graphique vue d'ensemble
     */
    updateOverviewChart(data) {
        const ctx = document.getElementById('overviewChart');
        if (!ctx) return;
        
        // Vérifier que Chart.js est disponible
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js non disponible pour le graphique overview');
            ctx.innerHTML = '<p style="text-align: center; padding: 2rem; color: #718096;">Graphique en cours de chargement...</p>';
            return;
        }
        
        const context = ctx.getContext('2d');
        
        if (this.charts.overview) {
            this.charts.overview.destroy();
        }

        this.charts.overview = new Chart(context, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label,
                    data: data.values,
                    borderColor: this.colors.primary,
                    backgroundColor: this.colors.primary + '20',
                    fill: true,
                    tension: 0.4
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
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Graphique timeline
     */
    updateTimelineChart(granularity, data) {
        const ctx = document.getElementById('timelineChart');
        if (!ctx) return;
        
        // Vérifier que Chart.js est disponible
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js non disponible pour le graphique timeline');
            ctx.innerHTML = '<p style="text-align: center; padding: 2rem; color: #718096;">Graphique en cours de chargement...</p>';
            return;
        }
        
        const context = ctx.getContext('2d');
        
        if (this.charts.timeline) {
            this.charts.timeline.destroy();
        }

        this.charts.timeline = new Chart(context, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label,
                    data: data.values,
                    backgroundColor: this.colors.primary,
                    borderColor: this.colors.primary,
                    borderWidth: 1
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
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Graphique répartition
     */
    updateBreakdownChart(data) {
        const ctx = document.getElementById('breakdownChart');
        if (!ctx) return;
        
        // Vérifier que Chart.js est disponible
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js non disponible pour le graphique breakdown');
            ctx.innerHTML = '<p style="text-align: center; padding: 2rem; color: #718096;">Graphique en cours de chargement...</p>';
            return;
        }
        
        const context = ctx.getContext('2d');
        
        if (this.charts.breakdown) {
            this.charts.breakdown.destroy();
        }

        this.charts.breakdown = new Chart(context, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        this.colors.primary,
                        this.colors.success,
                        this.colors.warning,
                        this.colors.danger,
                        this.colors.info
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

    /**
     * Graphique performance
     */
    updatePerformanceChart(data) {
        const ctx = document.getElementById('performanceChart');
        if (!ctx) return;
        
        // Vérifier que Chart.js est disponible
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js non disponible pour le graphique performance');
            ctx.innerHTML = '<p style="text-align: center; padding: 2rem; color: #718096;">Graphique en cours de chargement...</p>';
            return;
        }
        
        const context = ctx.getContext('2d');
        
        if (this.charts.performance) {
            this.charts.performance.destroy();
        }

        this.charts.performance = new Chart(context, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label,
                    data: data.values,
                    backgroundColor: this.colors.success,
                    borderColor: this.colors.success,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // Ceci rend le graphique horizontal
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Mettre à jour les métriques
     */
    updateMetrics(metrics) {
        const container = document.getElementById('keyMetrics');
        if (!container) return;
        
        container.innerHTML = '';
        
        metrics.forEach(metric => {
            const metricElement = document.createElement('div');
            metricElement.className = 'metric-item';
            metricElement.innerHTML = `
                <span class="label">${metric.label}</span>
                <span class="value">${metric.value}</span>
            `;
            container.appendChild(metricElement);
        });
    }

    /**
     * Mettre à jour les détails
     */
    updateDetails(details) {
        const tableHeader = document.getElementById('detailsTableHeader');
        const tableBody = document.getElementById('detailsTableBody');
        
        if (!tableHeader || !tableBody) return;
        
        // En-têtes
        if (details.length > 0) {
            const headers = Object.keys(details[0]);
            tableHeader.innerHTML = headers.map(header => 
                `<th>${header.charAt(0).toUpperCase() + header.slice(1)}</th>`
            ).join('');
            
            // Corps du tableau
            tableBody.innerHTML = details.map(row => {
                const cells = Object.values(row).map(value => 
                    `<td>${value}</td>`
                ).join('');
                return `<tr>${cells}</tr>`;
            }).join('');
        } else {
            tableHeader.innerHTML = '<th>Aucune donnée disponible</th>';
            tableBody.innerHTML = '<tr><td>Aucun résultat pour cette période</td></tr>';
        }
    }

    /**
     * Changer de période
     */
    changePeriod(period) {
        this.currentPeriod = period;
        
        // Mettre à jour l'interface
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-period="${period}"]`).classList.add('active');

        // Afficher/masquer la période personnalisée
        const customPeriod = document.getElementById('customPeriod');
        if (period === 'custom') {
            customPeriod.style.display = 'flex';
        } else {
            customPeriod.style.display = 'none';
            // Recharger les données
            this.loadData(this.currentStatType);
        }
    }

    /**
     * Changer d'onglet
     */
    changeTab(tab) {
        // Mettre à jour les boutons
        document.querySelectorAll('.stats-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');

        // Mettre à jour le contenu
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        document.getElementById(tab).classList.add('active');
    }

    /**
     * Obtenir la date de début selon la période
     */
    getStartDate() {
        const now = new Date();
        switch (this.currentPeriod) {
            case 'today':
                return now.toISOString().split('T')[0];
            case 'week':
                const weekStart = new Date(now.setDate(now.getDate() - now.getDay()));
                return weekStart.toISOString().split('T')[0];
            case 'month':
                return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            case 'quarter':
                const quarter = Math.floor(now.getMonth() / 3);
                return new Date(now.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
            case 'year':
                return new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
            case 'custom':
                return document.getElementById('startDate').value;
            default:
                return now.toISOString().split('T')[0];
        }
    }

    /**
     * Obtenir la date de fin selon la période
     */
    getEndDate() {
        const now = new Date();
        switch (this.currentPeriod) {
            case 'today':
                return now.toISOString().split('T')[0];
            case 'week':
                const weekEnd = new Date(now.setDate(now.getDate() - now.getDay() + 6));
                return weekEnd.toISOString().split('T')[0];
            case 'month':
                return new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
            case 'quarter':
                const quarter = Math.floor(now.getMonth() / 3);
                return new Date(now.getFullYear(), (quarter + 1) * 3, 0).toISOString().split('T')[0];
            case 'year':
                return new Date(now.getFullYear(), 11, 31).toISOString().split('T')[0];
            case 'custom':
                return document.getElementById('endDate').value;
            default:
                return now.toISOString().split('T')[0];
        }
    }

    /**
     * Appliquer une période personnalisée
     */
    applyCustomPeriod() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (!startDate || !endDate) {
            alert('Veuillez sélectionner une date de début et de fin');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('La date de début doit être antérieure à la date de fin');
            return;
        }

        this.loadData(this.currentStatType);
    }

    /**
     * Actualiser les données
     */
    refreshData() {
        this.loadData(this.currentStatType);
    }

    /**
     * Exporter les données
     */
    exportData() {
        // TODO: Implémenter l'export
        console.log('📤 Export des données');
        alert('Fonctionnalité d\'export en cours de développement');
    }

    /**
     * Afficher le loader
     */
    showLoader() {
        console.log('⏳ Affichage du loader');
        
        // Créer un overlay de chargement dans le modal
        const modalBody = document.querySelector('.advanced-stats-body');
        if (modalBody) {
            const loader = document.createElement('div');
            loader.id = 'statsLoader';
            loader.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                backdrop-filter: blur(5px);
            `;
            loader.innerHTML = `
                <div style="text-align: center;">
                    <div style="width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                    <p style="color: #667eea; font-weight: 500;">Chargement des statistiques...</p>
                </div>
            `;
            
            // Ajouter l'animation CSS
            if (!document.getElementById('loaderStyles')) {
                const style = document.createElement('style');
                style.id = 'loaderStyles';
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
            }
            
            modalBody.appendChild(loader);
        }
    }

    /**
     * Masquer le loader
     */
    hideLoader() {
        console.log('✅ Masquage du loader');
        
        const loader = document.getElementById('statsLoader');
        if (loader) {
            loader.remove();
        }
    }

    /**
     * Afficher une erreur
     */
    showError(message) {
        console.error('❌ Erreur:', message);
        alert('Erreur: ' + message);
    }
}

// Initialiser le système
let advancedStats;

// Initialisation immédiate pour éviter les délais
function initAdvancedStatsSystem() {
    console.log('🚀 Initialisation du système de statistiques avancé...');
    
    advancedStats = new AdvancedStatsSystem();
    
    // Exposer le système globalement IMMÉDIATEMENT
    window.advancedStats = advancedStats;
    
    // Fonction globale pour ouvrir le modal (remplace la fonction temporaire)
    window.openStatsModal = function(statType) {
        console.log('📊 Ouverture du modal via système avancé pour:', statType);
        advancedStats.openModal(statType);
    };
    
    console.log('✅ Système de statistiques avancé initialisé et exposé globalement');
    
    // Notifier que le système est prêt
    window.dispatchEvent(new CustomEvent('advancedStatsReady'));
}

// Initialiser dès que possible
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdvancedStatsSystem);
} else {
    // DOM déjà chargé, initialiser immédiatement
    initAdvancedStatsSystem();
}
