<?php
// Page de modification d'événements de présence
?>

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