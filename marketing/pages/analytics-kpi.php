<?php
/**
 * Landing Page - Analytics & KPI
 * Dashboard temps réel avec graphiques interactifs
 */
?>

<!-- Hero Section Analytics -->
<section class="bg-gradient-hero text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75 py-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <div class="pe-lg-5">
                    <div class="badge bg-info text-dark mb-4 px-3 py-2 fade-in-left">
                        <i class="fa-solid fa-chart-line me-2"></i>
                        Dashboard temps réel
                    </div>
                    
                    <h1 class="display-4 fw-black mb-4 fade-in-left">
                        KPI & Analytics<br>en temps réel
                    </h1>
                    
                    <p class="fs-5 mb-4 opacity-90 fade-in-left">
                        CA, nombre de réparations, taux de conversion, délais moyens... 
                        Tous vos indicateurs mis à jour en temps réel.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4 fade-in-left">
                        <a href="/inscription" class="btn btn-info btn-lg text-dark">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Essai gratuit 30 jours
                        </a>
                        <a href="#demo-analytics" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-chart-pie me-2"></i>
                            Voir le dashboard
                        </a>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-bolt text-info"></i>
                            <small>Mise à jour instantanée</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-mobile"></i>
                            <small>Mobile-friendly</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="position-relative">
                    <div class="card-modern p-4 bg-white bg-opacity-95 text-dark fade-in-right" style="max-width: 400px; margin: 0 auto;">
                        <h6 class="fw-bold mb-3">Tableau de bord aujourd'hui</h6>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-primary bg-opacity-10 rounded p-3 text-center">
                                    <div class="h4 fw-black text-primary mb-0">2 845€</div>
                                    <small class="text-muted">CA du jour</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-success bg-opacity-10 rounded p-3 text-center">
                                    <div class="h4 fw-black text-success mb-0">23</div>
                                    <small class="text-muted">Réparations</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-warning bg-opacity-10 rounded p-3 text-center">
                                    <div class="h4 fw-black text-warning mb-0">78%</div>
                                    <small class="text-muted">Conv. devis</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-info bg-opacity-10 rounded p-3 text-center">
                                    <div class="h4 fw-black text-info mb-0">24h</div>
                                    <small class="text-muted">Délai moyen</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="small text-muted">
                            <i class="fa-solid fa-clock me-1"></i> Mis à jour il y a 2 secondes
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-primary mb-1">20+</div>
                <div class="text-muted">Indicateurs</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-success mb-1">Temps réel</div>
                <div class="text-muted">Mises à jour</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-warning mb-1">365j</div>
                <div class="text-muted">Historique</div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="h2 fw-black text-info mb-1">Excel</div>
                <div class="text-muted">Export facile</div>
            </div>
        </div>
    </div>
</section>

<!-- DÉMO INTERACTIVE -->
<section class="section" id="demo-analytics" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="badge bg-info text-dark mb-3 px-3 py-2">
                <i class="fa-solid fa-chart-bar me-2"></i>
                Dashboard interactif
            </div>
            <h2 class="fw-black mb-3">Vos KPI en temps réel</h2>
            <p class="text-muted fs-5">Cliquez sur les périodes pour voir les données</p>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card-modern p-3">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-primary active" onclick="changePeriod('today')">Aujourd'hui</button>
                        <button class="btn btn-outline-primary" onclick="changePeriod('week')">7 jours</button>
                        <button class="btn btn-outline-primary" onclick="changePeriod('month')">30 jours</button>
                        <button class="btn btn-outline-primary" onclick="changePeriod('year')">1 an</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="card-modern p-4 text-center">
                    <div class="text-muted small mb-1">Chiffre d'affaires</div>
                    <div class="h2 fw-black text-primary mb-2" id="kpi-ca">2 845€</div>
                    <div class="small"><span class="text-success">↑ +12%</span> vs hier</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-modern p-4 text-center">
                    <div class="text-muted small mb-1">Réparations</div>
                    <div class="h2 fw-black text-success mb-2" id="kpi-repairs">23</div>
                    <div class="small"><span class="text-success">↑ +5</span> vs hier</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-modern p-4 text-center">
                    <div class="text-muted small mb-1">Taux conversion</div>
                    <div class="h2 fw-black text-warning mb-2" id="kpi-conversion">78%</div>
                    <div class="small"><span class="text-danger">↓ -2%</span> vs hier</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-modern p-4 text-center">
                    <div class="text-muted small mb-1">Délai moyen</div>
                    <div class="h2 fw-black text-info mb-2" id="kpi-delay">24h</div>
                    <div class="small"><span class="text-success">↓ -3h</span> vs hier</div>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mt-2">
            <div class="col-lg-8">
                <div class="card-modern p-4">
                    <h6 class="fw-bold mb-3">Évolution du CA</h6>
                    <canvas id="chart-revenue" style="max-height: 300px;"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-modern p-4">
                    <h6 class="fw-bold mb-3">Top Réparations</h6>
                    <canvas id="chart-repairs" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- KPI disponibles -->
<section class="section bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-black mb-3">20+ indicateurs de performance</h2>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="d-flex gap-3">
                    <i class="fa-solid fa-euro-sign text-primary fs-4"></i>
                    <div>
                        <h6 class="fw-bold mb-1">CA par période</h6>
                        <small class="text-muted">Jour, semaine, mois, année</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="d-flex gap-3">
                    <i class="fa-solid fa-wrench text-success fs-4"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Nombre de réparations</h6>
                        <small class="text-muted">Par statut, par technicien</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="d-flex gap-3">
                    <i class="fa-solid fa-percent text-warning fs-4"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Taux de conversion</h6>
                        <small class="text-muted">Devis acceptés vs refusés</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="d-flex gap-3">
                    <i class="fa-solid fa-clock text-info fs-4"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Délai moyen</h6>
                        <small class="text-muted">Temps de traitement</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="d-flex gap-3">
                    <i class="fa-solid fa-users text-primary fs-4"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Top techniciens</h6>
                        <small class="text-muted">Performance par employé</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="d-flex gap-3">
                    <i class="fa-solid fa-mobile text-success fs-4"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Top appareils</h6>
                        <small class="text-muted">Marques les plus réparées</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section bg-gradient-primary text-white">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="fw-black mb-4">Pilotez votre activité en temps réel</h2>
                <p class="fs-5 mb-4 opacity-90">
                    Prenez les bonnes décisions grâce à des données précises et actualisées.
                </p>
                <a href="/inscription" class="btn btn-light btn-lg">
                    <i class="fa-solid fa-rocket me-2"></i>
                    Démarrer l'essai gratuit
                </a>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const data = {
    today: {ca: 2845, repairs: 23, conversion: 78, delay: 24},
    week: {ca: 18240, repairs: 156, conversion: 75, delay: 26},
    month: {ca: 72100, repairs: 589, conversion: 72, delay: 28},
    year: {ca: 856000, repairs: 6842, conversion: 71, delay: 30}
};

let currentPeriod = 'today';
let revenueChart, repairsChart;

function changePeriod(period) {
    currentPeriod = period;
    document.querySelectorAll('.btn-group button').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    const d = data[period];
    document.getElementById('kpi-ca').textContent = d.ca.toLocaleString() + '€';
    document.getElementById('kpi-repairs').textContent = d.repairs;
    document.getElementById('kpi-conversion').textContent = d.conversion + '%';
    document.getElementById('kpi-delay').textContent = d.delay + 'h';
    
    updateCharts(period);
}

function updateCharts(period) {
    const labels = period === 'today' ? ['9h', '11h', '13h', '15h', '17h'] :
                   period === 'week' ? ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] :
                   period === 'month' ? ['S1', 'S2', 'S3', 'S4'] :
                   ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aout', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    const revenueData = period === 'today' ? [450, 680, 920, 550, 245] :
                        period === 'week' ? [2400, 2800, 2600, 3100, 2900, 2200, 2240] :
                        period === 'month' ? [16000, 18500, 19200, 18400] :
                        [65000, 68000, 71000, 73000, 75000, 72000, 70000, 69000, 71000, 73000, 75000, 78000];
    
    revenueChart.data.labels = labels;
    revenueChart.data.datasets[0].data = revenueData;
    revenueChart.update();
}

document.addEventListener('DOMContentLoaded', function() {
    const ctxRevenue = document.getElementById('chart-revenue').getContext('2d');
    revenueChart = new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: ['9h', '11h', '13h', '15h', '17h'],
            datasets: [{
                label: 'CA (€)',
                data: [450, 680, 920, 550, 245],
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {responsive: true, maintainAspectRatio: true}
    });
    
    const ctxRepairs = document.getElementById('chart-repairs').getContext('2d');
    repairsChart = new Chart(ctxRepairs, {
        type: 'doughnut',
        data: {
            labels: ['iPhone', 'Samsung', 'iPad', 'Autres'],
            datasets: [{
                data: [45, 30, 15, 10],
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#6c757d']
            }]
        },
        options: {responsive: true, maintainAspectRatio: true}
    });
});
</script>
