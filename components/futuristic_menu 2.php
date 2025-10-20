<?php
// Menu modal futuriste simple et fonctionnel
$navbar_assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';
?>

<!-- MENU FUTURISTE MODAL -->
<div class="modal fade" id="futuristicMenuModal" tabindex="-1" aria-labelledby="futuristicMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <!-- Header du menu -->
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <img src="<?php echo $navbar_assets_path; ?>images/logo/logoservo.png" alt="SERVO" class="me-3" height="40">
                    <div>
                        <h5 class="modal-title mb-0" id="futuristicMenuModalLabel">SERVO</h5>
                        <small class="text-muted">Command Center</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps du menu -->
            <div class="modal-body pt-2">
                <div class="row g-3">
                    <!-- Navigation principale -->
                    <div class="col-12">
                        <h6 class="text-uppercase text-muted small mb-3">Navigation</h6>
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <a href="index.php" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-home fa-2x mb-2"></i>
                                    <span>Accueil</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4">
                                <a href="index.php?page=reparations" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-tools fa-2x mb-2"></i>
                                    <span>Réparations</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4">
                                <a href="index.php?page=clients" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <span>Clients</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4">
                                <a href="index.php?page=devis" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-file-invoice fa-2x mb-2"></i>
                                    <span>Devis</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4">
                                <a href="index.php?page=taches" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-tasks fa-2x mb-2"></i>
                                    <span>Tâches</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4">
                                <a href="index.php?page=kpi_dashboard" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <span>KPI</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Actions rapides -->
                    <div class="col-12">
                        <hr class="my-3">
                        <h6 class="text-uppercase text-muted small mb-3">Actions Rapides</h6>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <button class="btn btn-success w-100 d-flex flex-column align-items-center p-3" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal">
                                    <i class="fas fa-plus fa-2x mb-2"></i>
                                    <span>Nouveau</span>
                                </button>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="index.php?page=ajouter_client" class="btn btn-info w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                                    <span>Client</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="index.php?page=ajouter_reparation" class="btn btn-warning w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-wrench fa-2x mb-2"></i>
                                    <span>Réparation</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="index.php?page=ajouter_tache" class="btn btn-secondary w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                                    <span>Tâche</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Paramètres -->
                    <div class="col-12">
                        <hr class="my-3">
                        <h6 class="text-uppercase text-muted small mb-3">Système</h6>
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <button class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center p-3" onclick="toggleDarkMode()">
                                    <i class="fas fa-moon fa-2x mb-2"></i>
                                    <span>Mode Nuit</span>
                                </button>
                            </div>
                            <div class="col-6 col-md-4">
                                <a href="logout.php" class="btn btn-outline-danger w-100 d-flex flex-column align-items-center p-3 text-decoration-none">
                                    <i class="fas fa-sign-out-alt fa-2x mb-2"></i>
                                    <span>Déconnexion</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour le modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // S'assurer que Bootstrap est chargé
    if (typeof bootstrap !== 'undefined') {
        console.log('✅ Bootstrap Modal disponible');
        
        // Initialiser le modal
        const modalElement = document.getElementById('futuristicMenuModal');
        if (modalElement) {
            // Créer l'instance du modal
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            
            // Stocker l'instance globalement
            window.futuristicModal = modal;
            
            console.log('✅ Modal futuristicMenuModal initialisé');
        }
    } else {
        console.error('❌ Bootstrap non disponible');
    }
});

// Fonction pour basculer le mode sombre
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    document.body.classList.toggle('dark-theme');
    
    // Sauvegarder la préférence
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
    
    // Fermer le modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('futuristicMenuModal'));
    if (modal) {
        modal.hide();
    }
}

// Appliquer le mode sombre au chargement
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode', 'dark-theme');
}
</script>

<style>
/* Styles pour le modal */
#futuristicMenuModal .modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

#futuristicMenuModal .btn {
    border-radius: 10px;
    transition: all 0.3s ease;
}

#futuristicMenuModal .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Mode sombre pour le modal */
.dark-mode #futuristicMenuModal .modal-content,
.dark-theme #futuristicMenuModal .modal-content {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #f8fafc;
}

.dark-mode #futuristicMenuModal .text-muted,
.dark-theme #futuristicMenuModal .text-muted {
    color: #94a3b8 !important;
}

.dark-mode #futuristicMenuModal .btn-outline-primary,
.dark-theme #futuristicMenuModal .btn-outline-primary {
    border-color: #0ea5e9;
    color: #0ea5e9;
}

.dark-mode #futuristicMenuModal .btn-outline-primary:hover,
.dark-theme #futuristicMenuModal .btn-outline-primary:hover {
    background-color: #0ea5e9;
    border-color: #0ea5e9;
    color: white;
}
</style>
