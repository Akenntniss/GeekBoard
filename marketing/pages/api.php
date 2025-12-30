<?php include 'marketing/shared/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden pt-5 pb-5">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: -1;">
         <div class="position-absolute bottom-0 end-0 bg-secondary opacity-10 rounded-circle blur-3xl" style="width: 800px; height: 800px; filter: blur(100px); transform: translate(30%, 30%);"></div>
    </div>

    <div class="container position-relative pt-5 mt-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="badge bg-secondary bg-opacity-20 text-secondary border border-secondary border-opacity-20 rounded-pill px-3 py-2 mb-4 fade-in-up">
                    <i class="fa-solid fa-server me-2"></i>Developer Portal
                </div>
                <h1 class="display-3 fw-bold mb-4 fade-in-up delay-1">API REST pour les développeurs</h1>
                <p class="fs-5 text-muted mb-4 fade-in-up delay-2">
                    Intégrez toute la puissance de SERVO dans vos propres applications. Gestion des clients, réparations, stocks et webhooks en temps réel.
                </p>
                <div class="d-flex gap-3 fade-in-up delay-3">
                    <a href="#" class="btn btn-primary btn-lg rounded-pill fw-bold">Obtenir une clé API</a>
                    <a href="#" class="btn btn-outline-light btn-lg rounded-pill">Documentation complète</a>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0 fade-in-up delay-2">
                <!-- Code Snippet -->
                <div class="card-glass bg-dark border-secondary border-opacity-25 overflow-hidden shadow-lg position-relative">
                    <div class="d-flex align-items-center px-4 py-2 bg-white bg-opacity-5 border-bottom border-light border-opacity-10">
                        <div class="d-flex gap-2 me-4">
                            <div class="rounded-circle bg-danger" style="width: 10px; height: 10px;"></div>
                            <div class="rounded-circle bg-warning" style="width: 10px; height: 10px;"></div>
                            <div class="rounded-circle bg-success" style="width: 10px; height: 10px;"></div>
                        </div>
                        <span class="text-muted small font-monospace">create_repair.js</span>
                    </div>
                    <div class="p-4 font-monospace small" style="color: #a5b4fc;">
<pre class="mb-0">
<span class="text-secondary opacity-50">// Initialize Servo Client</span>
const servo = new ServoClient('YOUR_API_KEY');

<span class="text-secondary opacity-50">// Create a new repair ticket</span>
const ticket = await servo.repairs.create({
  client_id: <span class="text-success">'cli_892301'</span>,
  device: <span class="text-success">'iPhone 13 Pro'</span>,
  issue: <span class="text-success">'Screen Replacement'</span>,
  priority: <span class="text-warning">'high'</span>,
  notify_sms: <span class="text-primary">true</span>
});

console.log(ticket.id);
<span class="text-secondary opacity-50">// Output: rep_9928374</span>
</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Endpoints Overview -->
<section class="section pt-0">
    <div class="container">
        
         <div class="row mb-5">
             <div class="col-12">
                 <h2 class="fw-bold mb-4">Endpoints Principaux</h2>
             </div>
             
             <div class="col-md-4 mb-4">
                 <div class="card-glass p-4 h-100 hover-up transition-all">
                     <div class="badge bg-primary bg-opacity-20 text-primary mb-3">/clients</div>
                     <h5 class="fw-bold">Gestion Clients</h5>
                     <ul class="list-unstyled text-muted small mb-0">
                         <li class="mb-2"><span class="badge bg-success bg-opacity-25 text-success me-2">GET</span> Lister les clients</li>
                         <li class="mb-2"><span class="badge bg-primary bg-opacity-25 text-primary me-2">POST</span> Créer un client</li>
                         <li><span class="badge bg-warning bg-opacity-25 text-warning me-2">PUT</span> Mettre à jour</li>
                     </ul>
                 </div>
             </div>
             
             <div class="col-md-4 mb-4">
                 <div class="card-glass p-4 h-100 hover-up transition-all">
                     <div class="badge bg-secondary bg-opacity-20 text-secondary mb-3">/repairs</div>
                     <h5 class="fw-bold">Réparations</h5>
                     <ul class="list-unstyled text-muted small mb-0">
                         <li class="mb-2"><span class="badge bg-success bg-opacity-25 text-success me-2">GET</span> Statut réparation</li>
                         <li class="mb-2"><span class="badge bg-primary bg-opacity-25 text-primary me-2">POST</span> Nouveau ticket</li>
                         <li><span class="badge bg-danger bg-opacity-25 text-danger me-2">DEL</span> Archiver</li>
                     </ul>
                 </div>
             </div>
             
             <div class="col-md-4 mb-4">
                 <div class="card-glass p-4 h-100 hover-up transition-all">
                     <div class="badge bg-info bg-opacity-20 text-info mb-3">/webhooks</div>
                     <h5 class="fw-bold">Webhooks</h5>
                     <ul class="list-unstyled text-muted small mb-0">
                         <li class="mb-2">repair.created</li>
                         <li class="mb-2">repair.status_updated</li>
                         <li>inventory.low_stock</li>
                     </ul>
                 </div>
             </div>
         </div>
    </div>
</section>

<!-- Authentication & Rate Limit -->
<section class="section bg-dark bg-opacity-30 pt-5 pb-5">
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-6">
                <h3 class="fw-bold mb-3">Authentification</h3>
                <p class="text-muted mb-4">
                    L'API SERVO utilise des clés API Bearer Token. Vos clés doivent être incluses dans le header de chaque requête.
                </p>
                <div class="card-glass bg-black p-3 font-monospace text-light small">
                    Authorization: Bearer sk_live_839283948239048...
                </div>
            </div>
             <div class="col-lg-6">
                <h3 class="fw-bold mb-3">Limites (Rate Limits)</h3>
                <p class="text-muted mb-4">
                    Pour garantir la stabilité de la plateforme, l'API est limitée par défaut.
                </p>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2"><i class="fa-solid fa-check text-primary me-2"></i>1000 requêtes / minute (Standard)</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-primary me-2"></i>10k requêtes / minute (Enterprise)</li>
                    <li><i class="fa-solid fa-check text-primary me-2"></i>Mode Burst autorisé</li>
                </ul>
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
.hover-up:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.08);
}
</style>

<?php include 'marketing/shared/footer.php'; ?>
