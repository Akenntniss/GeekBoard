<?php include 'marketing/shared/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden pt-5 pb-5">
    <div class="container position-relative pt-5 mt-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-success bg-opacity-20 border border-success border-opacity-20 text-success mb-4 fade-in-up">
                    <span class="position-relative d-flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-success"></span>
                    </span>
                    <i class="fa-solid fa-circle small me-2"></i>Tous les systèmes opérationnels
                </div>
                <h1 class="display-3 fw-bold mb-4 fade-in-up delay-1">État des Services</h1>
                <p class="fs-5 text-muted mb-0 fade-in-up delay-2">
                    Surveillance en temps réel de la disponibilité et des performances de l'infrastructure SERVO.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Status Grid -->
<section class="section pt-0">
    <div class="container">
        
        <!-- Main Status Card -->
        <div class="card-glass p-5 mb-5 text-center bg-success bg-opacity-5 border-success border-opacity-20 fade-in-up">
            <i class="fa-solid fa-circle-check text-success display-1 mb-4"></i>
            <h2 class="fw-bold mb-2">Tout fonctionne normalement</h2>
            <p class="text-muted">Dernière mise à jour : il y a 20 secondes</p>
        </div>

        <div class="row g-4 mb-5">
            <!-- Service 1 -->
            <div class="col-md-4 fade-in-up delay-1">
                <div class="card-glass p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">API Core</h5>
                        <span class="badge bg-success bg-opacity-20 text-success"><i class="fa-solid fa-check me-1"></i>Opérationnel</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Uptime 99.99%</span>
                        <span>45ms</span>
                    </div>
                    <!-- Fake historical bars -->
                    <div class="d-flex gap-1 mt-3 justify-content-end align-items-end" style="height: 30px;">
                         <?php for($i=0; $i<30; $i++): ?>
                            <div class="bg-success rounded-pill" style="width: 4px; height: <?php echo rand(20, 100); ?>%; opacity: 0.8;"></div>
                         <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Service 2 -->
            <div class="col-md-4 fade-in-up delay-2">
                <div class="card-glass p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Dashboard App</h5>
                        <span class="badge bg-success bg-opacity-20 text-success"><i class="fa-solid fa-check me-1"></i>Opérationnel</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Uptime 99.95%</span>
                        <span>120ms</span>
                    </div>
                     <div class="d-flex gap-1 mt-3 justify-content-end align-items-end" style="height: 30px;">
                         <?php for($i=0; $i<30; $i++): ?>
                            <div class="bg-success rounded-pill" style="width: 4px; height: <?php echo rand(20, 100); ?>%; opacity: 0.8;"></div>
                         <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Service 3 -->
            <div class="col-md-4 fade-in-up delay-3">
                <div class="card-glass p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">SMS Gateway</h5>
                        <span class="badge bg-success bg-opacity-20 text-success"><i class="fa-solid fa-check me-1"></i>Opérationnel</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Delivery 99.8%</span>
                        <span>Instant</span>
                    </div>
                     <div class="d-flex gap-1 mt-3 justify-content-end align-items-end" style="height: 30px;">
                         <?php for($i=0; $i<30; $i++): ?>
                            <div class="bg-success rounded-pill" style="width: 4px; height: <?php echo rand(20, 100); ?>%; opacity: 0.8;"></div>
                         <?php endfor; ?>
                    </div>
                </div>
            </div>
            
             <!-- Service 4 -->
            <div class="col-md-4 fade-in-up delay-4">
                <div class="card-glass p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Database Clusters</h5>
                         <span class="badge bg-success bg-opacity-20 text-success"><i class="fa-solid fa-check me-1"></i>Opérationnel</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Réplication OK</span>
                        <span>12ms</span>
                    </div>
                </div>
            </div>

             <!-- Service 5 -->
            <div class="col-md-4 fade-in-up delay-5">
                <div class="card-glass p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Catalogue Fournisseurs</h5>
                        <span class="badge bg-warning bg-opacity-20 text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>Maintenance</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Maintenance planifiée</span>
                        <span>-</span>
                    </div>
                </div>
            </div>

             <!-- Service 6 -->
            <div class="col-md-4 fade-in-up delay-6">
                <div class="card-glass p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Email Delivery</h5>
                        <span class="badge bg-success bg-opacity-20 text-success"><i class="fa-solid fa-check me-1"></i>Opérationnel</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Queue vide</span>
                        <span>SMTP OK</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Incident History -->
        <h3 class="fw-bold mb-4">Incidents Passés</h3>
        <div class="d-flex flex-column gap-3">
            
            <div class="card-glass p-4 border-start border-4 border-success">
                <div class="d-flex justify-content-between">
                    <h5 class="fw-bold mb-1">Aucun incident ce jour</h5>
                    <span class="text-muted small">29 Décembre 2025</span>
                </div>
                <p class="text-muted mb-0 small">Tous les systèmes sont opérationnels.</p>
            </div>

            <div class="card-glass p-4 border-start border-4 border-warning">
                <div class="d-flex justify-content-between">
                    <h5 class="fw-bold mb-1">Latence API (Résolu)</h5>
                    <span class="text-muted small">25 Décembre 2025</span>
                </div>
                <p class="text-muted mb-0 small">Une augmentation du trafic a causé des ralentissements mineurs entre 14h00 et 14h15.</p>
            </div>

        </div>

    </div>
</section>

<style>
.card-glass {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
}
.animate-ping {
    animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
}
@keyframes ping {
    75%, 100% {
        transform: scale(2);
        opacity: 0;
    }
}
</style>

<?php include 'marketing/shared/footer.php'; ?>
