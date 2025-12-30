<?php
// Page de sélection du type d'événement - Dashboard ludique
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-plus-circle me-2"></i>Ajouter un Événement</h1>
                <a href="index.php?page=presence_gestion" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la gestion
                </a>
            </div>

            <!-- Dashboard de sélection -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-lg border-0">
                        <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h3 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Que souhaitez-vous déclarer ?</h3>
                            <p class="mb-0 mt-2 opacity-75">Sélectionnez le type d'événement à enregistrer</p>
                        </div>
                        <div class="card-body p-5">
                            <div class="row g-4">
                                <!-- RETARD -->
                                <div class="col-md-6 col-lg-3">
                                    <a href="index.php?page=presence_ajouter&type=retard" class="text-decoration-none">
                                        <div class="event-card card h-100 border-0 shadow-sm hover-lift">
                                            <div class="card-body text-center p-4">
                                                <div class="event-icon mb-3" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <h5 class="card-title text-dark mb-2">Retard</h5>
                                                <p class="card-text text-muted small mb-3">
                                                    Déclarer une arrivée tardive ou un départ anticipé
                                                </p>
                                                <div class="badge bg-warning text-dark">
                                                    <i class="fas fa-stopwatch me-1"></i>Ponctuel
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- ABSENCE -->
                                <div class="col-md-6 col-lg-3">
                                    <a href="index.php?page=presence_ajouter&type=absence" class="text-decoration-none">
                                        <div class="event-card card h-100 border-0 shadow-sm hover-lift">
                                            <div class="card-body text-center p-4">
                                                <div class="event-icon mb-3" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);">
                                                    <i class="fas fa-user-times"></i>
                                                </div>
                                                <h5 class="card-title text-dark mb-2">Absence</h5>
                                                <p class="card-text text-muted small mb-3">
                                                    Absence non planifiée ou urgence
                                                </p>
                                                <div class="badge bg-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Période
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- CONGÉ PAYÉ -->
                                <div class="col-md-6 col-lg-3">
                                    <a href="index.php?page=presence_ajouter&type=conge_paye" class="text-decoration-none">
                                        <div class="event-card card h-100 border-0 shadow-sm hover-lift">
                                            <div class="card-body text-center p-4">
                                                <div class="event-icon mb-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                                    <i class="fas fa-umbrella-beach"></i>
                                                </div>
                                                <h5 class="card-title text-dark mb-2">Congé Payé</h5>
                                                <p class="card-text text-muted small mb-3">
                                                    Vacances, RTT, congé avec rémunération
                                                </p>
                                                <div class="badge bg-success">
                                                    <i class="fas fa-money-bill-wave me-1"></i>Rémunéré
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- CONGÉ SANS SOLDE -->
                                <div class="col-md-6 col-lg-3">
                                    <a href="index.php?page=presence_ajouter&type=conge_sans_solde" class="text-decoration-none">
                                        <div class="event-card card h-100 border-0 shadow-sm hover-lift">
                                            <div class="card-body text-center p-4">
                                                <div class="event-icon mb-3" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                                    <i class="fas fa-hand-paper"></i>
                                                </div>
                                                <h5 class="card-title text-dark mb-2">Congé Sans Solde</h5>
                                                <p class="card-text text-muted small mb-3">
                                                    Congé personnel non rémunéré
                                                </p>
                                                <div class="badge bg-secondary">
                                                    <i class="fas fa-ban me-1"></i>Non rémunéré
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aide rapide -->
            <div class="row justify-content-center mt-5">
                <div class="col-lg-8">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center py-4">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-question-circle me-2"></i>Besoin d'aide ?
                            </h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <strong>Retard :</strong> Quelques minutes à quelques heures
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <strong>Absence :</strong> Journée complète ou plus
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <strong>Congé payé :</strong> Décompté du solde
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <strong>Sans solde :</strong> Non rémunéré
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.event-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border-radius: 15px !important;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175) !important;
}

.hover-lift:hover {
    transform: translateY(-5px);
}

.event-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    font-size: 2rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}

.card {
    border-radius: 15px;
}

.bg-gradient {
    border-radius: 15px 15px 0 0 !important;
}

.opacity-75 {
    opacity: 0.75;
}

/* Animation d'apparition */
.event-card {
    animation: fadeInUp 0.6s ease;
}

.event-card:nth-child(1) { animation-delay: 0.1s; }
.event-card:nth-child(2) { animation-delay: 0.2s; }
.event-card:nth-child(3) { animation-delay: 0.3s; }
.event-card:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .event-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .card-body.p-5 {
        padding: 2rem !important;
    }
}
</style>

<?php
// Si un type est sélectionné, inclure la page spécialisée
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    $allowed_types = ['retard', 'absence', 'conge_paye', 'conge_sans_solde'];
    
    if (in_array($type, $allowed_types)) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                // Masquer le dashboard et afficher le formulaire spécialisé
                setTimeout(function() {
                    window.location.href = "index.php?page=presence_form&type=' . $type . '";
                }, 500);
            });
        </script>';
    }
}
?>