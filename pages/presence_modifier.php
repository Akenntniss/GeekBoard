<?php
// Page de modification d'événements de présence
include_once 'includes/night-mode-system.php';
?>

<style>
/* ========================================
   FOND ANIMÉ JOUR/NUIT
======================================== */
@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mode Jour - Fond animé */
body:not(.night-mode) {
    background: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff) !important;
    background-size: 400% 400% !important;
    animation: gradientFlow 15s ease infinite !important;
}

/* Mode Nuit - Fond animé */
body.night-mode {
    background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483) !important;
    background-size: 400% 400% !important;
    animation: gradientFlow 15s ease infinite !important;
}

/* ========================================
   CORRECTION ESPACEMENT NAVBAR
======================================== */
body {
    padding-top: 70px !important;
}

/* ========================================
   FIX NAVBAR DESKTOP
======================================== */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
    }
    
    #desktop-navbar, nav#desktop-navbar, .navbar {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 10000 !important;
        height: 70px !important;
        min-height: 70px !important;
        width: 100% !important;
        overflow: visible !important;
        align-items: center !important;
    }
    
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        overflow: visible !important;
    }
    
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
    }
}

/* ========================================
   MASQUER NAVBAR DESKTOP SUR MOBILE
======================================== */
@media (max-width: 767px) {
    #desktop-navbar,
    nav#desktop-navbar,
    .navbar,
    nav.navbar {
        display: none !important;
        visibility: hidden !important;
    }
    
    body {
        padding-top: 0 !important;
    }
    
    .container-fluid {
        padding-bottom: 100px !important;
    }
}

/* ========================================
   CARTES MODE JOUR (fond blanc explicite)
======================================== */
body:not(.night-mode) .card {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 1px solid rgba(148, 163, 184, 0.3) !important;
    color: #1e293b !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
}

body:not(.night-mode) .card-header {
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
    color: white !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2) !important;
}

body:not(.night-mode) .card-body {
    background: rgba(255, 255, 255, 0.95) !important;
    color: #1e293b !important;
}

/* ========================================
   FORMULAIRES MODE JOUR
======================================== */
body:not(.night-mode) .form-control,
body:not(.night-mode) .form-select {
    background: #ffffff !important;
    border-color: rgba(148, 163, 184, 0.5) !important;
    color: #1e293b !important;
}

body:not(.night-mode) .form-control:focus,
body:not(.night-mode) .form-select:focus {
    background: #ffffff !important;
    border-color: #667eea !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2) !important;
}

body:not(.night-mode) .form-label {
    color: #1e293b !important;
}

body:not(.night-mode) .form-text {
    color: #64748b !important;
}

body:not(.night-mode) h1,
body:not(.night-mode) h5,
body:not(.night-mode) h6 {
    color: #1e293b !important;
}

/* ========================================
   BOUTONS MODE JOUR
======================================== */
body:not(.night-mode) .btn-secondary {
    background: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
    color: #475569 !important;
}

body:not(.night-mode) .btn-secondary:hover {
    background: #e2e8f0 !important;
    border-color: #94a3b8 !important;
}

body:not(.night-mode) .btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
    border: none !important;
    color: white !important;
}

body:not(.night-mode) .btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    border: none !important;
    color: white !important;
}

/* ========================================
   TIMELINE MODE JOUR
======================================== */
body:not(.night-mode) .timeline-item::before {
    background-color: #cbd5e1 !important;
}

body:not(.night-mode) .timeline-title {
    color: #1e293b !important;
}

body:not(.night-mode) .timeline-text {
    color: #475569 !important;
}

body:not(.night-mode) .text-muted {
    color: #64748b !important;
}

/* ========================================
   CARTES MODE NUIT
======================================== */
body.night-mode .card {
    background: rgba(15, 15, 25, 0.95) !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
    color: #ffffff !important;
}

body.night-mode .card-header {
    background: linear-gradient(135deg, #00d4ff, #7c3aed) !important;
    color: white !important;
    border-bottom: 1px solid rgba(0, 212, 255, 0.3) !important;
}

body.night-mode .card-body {
    background: rgba(15, 15, 25, 0.95) !important;
    color: #ffffff !important;
}

/* ========================================
   FORMULAIRES MODE NUIT
======================================== */
body.night-mode .form-control,
body.night-mode .form-select {
    background: rgba(15, 23, 42, 0.8) !important;
    border-color: rgba(0, 212, 255, 0.3) !important;
    color: #ffffff !important;
}

body.night-mode .form-control:focus,
body.night-mode .form-select:focus {
    background: rgba(15, 23, 42, 0.9) !important;
    border-color: #00d4ff !important;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.5) !important;
    color: #ffffff !important;
}

body.night-mode .form-label {
    color: #ffffff !important;
}

body.night-mode .form-text {
    color: #a0aec0 !important;
}

body.night-mode h1,
body.night-mode h5,
body.night-mode h6 {
    color: #ffffff !important;
}

/* ========================================
   BOUTONS MODE NUIT
======================================== */
body.night-mode .btn-primary {
    background: linear-gradient(135deg, #00d4ff, #7c3aed) !important;
    border: none !important;
    color: white !important;
}

body.night-mode .btn-primary:hover {
    background: linear-gradient(135deg, #7c3aed, #00d4ff) !important;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.5) !important;
}

body.night-mode .btn-secondary {
    background: rgba(15, 23, 42, 0.8) !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
    color: #ffffff !important;
}

body.night-mode .btn-secondary:hover {
    background: rgba(0, 212, 255, 0.1) !important;
    border-color: #00d4ff !important;
}

body.night-mode .btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    border: none !important;
}

/* ========================================
   MODAL MODE NUIT
======================================== */
body.night-mode .modal-content {
    background: rgba(15, 15, 25, 0.95) !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
    color: #ffffff !important;
}

body.night-mode .modal-header {
    background: linear-gradient(135deg, #00d4ff, #7c3aed) !important;
    border-bottom: 1px solid rgba(0, 212, 255, 0.3) !important;
}

body.night-mode .modal-title {
    color: #ffffff !important;
}

body.night-mode .modal-body {
    background: rgba(15, 15, 25, 0.95) !important;
    color: #ffffff !important;
}

body.night-mode .modal-footer {
    background: rgba(15, 15, 25, 0.95) !important;
    border-top: 1px solid rgba(0, 212, 255, 0.3) !important;
}

body.night-mode .btn-close {
    filter: invert(1) !important;
}

/* ========================================
   TIMELINE MODE NUIT
======================================== */
body.night-mode .timeline-item::before {
    background-color: rgba(0, 212, 255, 0.3) !important;
}

body.night-mode .timeline-title {
    color: #ffffff !important;
}

body.night-mode .timeline-text {
    color: #a0aec0 !important;
}

body.night-mode .text-muted {
    color: #a0aec0 !important;
}

/* ========================================
   ANIMATIONS SERVO NAVBAR
   Note: Les keyframes sont définis dans servo-logo-animated.css (global)
======================================== */
.servo-logo-container {
    position: absolute !important;
    left: 50% !important;
    top: 50% !important;
    transform: translate(-50%, -50%) !important;
    z-index: 9999 !important;
    height: 48px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: transparent !important;
    pointer-events: auto !important;
    overflow: visible !important;
}

.servo-logo-container .loader {
    display: flex !important;
    margin: 0 !important;
    height: 48px !important;
    align-items: center !important;
    gap: 2px !important;
    background: transparent !important;
}

.servo-logo-container svg {
    background: transparent !important;
    display: inline-block !important;
}

/* Forcer la visibilité des animations */
.servo-logo-container .dash,
.servo-logo-container .spin {
    visibility: visible !important;
    opacity: 1 !important;
}

/* Corrections pour éviter la bande noire derrière le logo */
.servo-logo-container,
.servo-logo-container *,
.servo-logo-container svg,
.servo-logo-container path {
    background: none !important;
    background-color: transparent !important;
    backdrop-filter: none !important;
    box-shadow: none !important;
}

/* ========================================
   NAVBAR MODE NUIT
======================================== */
body.night-mode #desktop-navbar,
body.night-mode nav#desktop-navbar,
body.night-mode .navbar {
    background: rgba(15, 15, 25, 0.95) !important;
    border-bottom: 1px solid rgba(0, 212, 255, 0.3) !important;
}

body.night-mode #desktop-navbar .navbar-brand,
body.night-mode #desktop-navbar .nav-link,
body.night-mode #desktop-navbar .navbar-text {
    color: #ffffff !important;
}

body.night-mode #desktop-navbar .nav-link:hover {
    color: #00d4ff !important;
}

body.night-mode #desktop-navbar .btn {
    color: #ffffff !important;
    border-color: rgba(0, 212, 255, 0.3) !important;
}

body.night-mode #desktop-navbar .btn:hover {
    color: #00d4ff !important;
    border-color: #00d4ff !important;
    background: rgba(0, 212, 255, 0.1) !important;
}
</style>


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Modifier un Événement</h1>
                <a href="index.php?page=presence_gestion" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la gestion
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-edit me-2"></i>Modification de l'événement</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?page=presence_gestion">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="event_id" value="<?php echo $_GET['id'] ?? ''; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">Utilisateur *</label>
                                    <select class="form-select" id="employee_id" name="employee_id" required>
                                        <option value="">Sélectionner un utilisateur</option>
                                        <?php
                                        // Récupérer les utilisateurs depuis la base de données
                                        if (function_exists('getShopDBConnection')) {
                                            try {
                                                $shop_pdo = getShopDBConnection();
                                                $stmt = $shop_pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name, username");
                                                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($users as $user) {
                                                    $selected = ($user['id'] == 1) ? 'selected' : ''; // Premier utilisateur sélectionné par défaut
                                                    echo '<option value="' . $user['id'] . '" ' . $selected . '>' . 
                                                         htmlspecialchars($user['full_name'] ?: $user['username']) . 
                                                         '</option>';
                                                }
                                            } catch (Exception $e) {
                                                echo '<option value="">Erreur de chargement</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type_id" class="form-label">Type d'événement *</label>
                                    <select class="form-select" id="type_id" name="type_id" required>
                                        <option value="">Sélectionner un type</option>
                                        <option value="1" selected>Retard</option>
                                        <option value="2">Absence</option>
                                        <option value="3">Congé payé</option>
                                        <option value="4">Congé sans solde</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_start" class="form-label">Date et heure de début *</label>
                                    <input type="datetime-local" class="form-control" id="date_start" name="date_start" 
                                           value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_end" class="form-label">Date et heure de fin</label>
                                    <input type="datetime-local" class="form-control" id="date_end" name="date_end">
                                    <div class="form-text">Optionnel pour les retards ponctuels</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration_minutes" class="form-label">Durée (en minutes)</label>
                                    <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" 
                                           min="1" value="30">
                                    <div class="form-text">Se calcule automatiquement si date de fin fournie</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Statut</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="pending" selected>En attente</option>
                                        <option value="approved">Approuvé</option>
                                        <option value="rejected">Rejeté</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label">Commentaire</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" 
                                      placeholder="Détails, justification, notes...">Exemple de commentaire pour cet événement</textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="index.php?page=presence_gestion" class="btn btn-secondary">Annuler</a>
                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Sauvegarder les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historique des modifications -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6><i class="fas fa-history me-2"></i>Historique des modifications</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Événement créé</h6>
                                <p class="timeline-text">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y à H:i'); ?> par Admin
                                    </small>
                                </p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Statut modifié</h6>
                                <p class="timeline-text">
                                    Changé de "En attente" vers "Approuvé"
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y à H:i', strtotime('-1 hour')); ?> par Admin
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="index.php?page=presence_gestion" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="event_id" value="<?php echo $_GET['id'] ?? ''; ?>">
                    <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -21px;
    top: 20px;
    bottom: -20px;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item:last-child::before {
    display: none;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-content {
    margin-left: 15px;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 14px;
    font-weight: 600;
}

.timeline-text {
    margin-bottom: 0;
    font-size: 13px;
}
</style>

<script>
// Calcul automatique de la durée
document.addEventListener('DOMContentLoaded', function() {
    const dateStart = document.getElementById('date_start');
    const dateEnd = document.getElementById('date_end');
    const duration = document.getElementById('duration_minutes');

    function calculateDuration() {
        if (dateStart.value && dateEnd.value) {
            const start = new Date(dateStart.value);
            const end = new Date(dateEnd.value);
            const diffMs = end - start;
            const diffMins = Math.round(diffMs / (1000 * 60));
            
            if (diffMins > 0) {
                duration.value = diffMins;
            }
        }
    }

    dateStart.addEventListener('change', calculateDuration);
    dateEnd.addEventListener('change', calculateDuration);
});

// Fonction de confirmation de suppression
function confirmDelete() {
    var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    modal.show();
}
</script>