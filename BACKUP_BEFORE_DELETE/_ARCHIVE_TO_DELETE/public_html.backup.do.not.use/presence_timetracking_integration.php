<?php
/**
 * Intégration du système de pointage avec presence_gestion.php
 * Code à ajouter à presence_gestion.php pour les fonctionnalités de pointage
 */

// Ce code doit être ajouté à presence_gestion.php

// Récupérer les pointages de l'utilisateur actuel
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Récupérer les pointages de la semaine en cours
$stmt = $shop_pdo->prepare("
    SELECT tt.*, u.full_name, u.username,
           DATE(tt.clock_in) as work_date,
           TIME(tt.clock_in) as clock_in_time,
           TIME(tt.clock_out) as clock_out_time
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE tt.user_id = ? 
      AND tt.clock_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY tt.clock_in DESC
");
$stmt->execute([$current_user_id]);
$user_timetracking = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le statut actuel de pointage
$stmt = $shop_pdo->prepare("
    SELECT * FROM time_tracking 
    WHERE user_id = ? AND status IN ('active', 'break') 
    ORDER BY clock_in DESC LIMIT 1
");
$stmt->execute([$current_user_id]);
$current_tracking = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement des demandes de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_modification') {
    $entry_id = intval($_POST['entry_id']);
    $requested_clock_in = $_POST['requested_clock_in'];
    $requested_clock_out = $_POST['requested_clock_out'];
    $reason = $_POST['reason'];
    
    // Insérer la demande de modification dans presence_events (utilise le système existant)
    $stmt = $shop_pdo->prepare("
        INSERT INTO presence_events (employee_id, type_id, date_start, date_end, comment, created_by, status) 
        VALUES (?, 
                (SELECT id FROM presence_types WHERE name = 'modification_pointage' LIMIT 1), 
                ?, ?, 
                CONCAT('Demande de modification pointage ID: ', ?, '\nNouvelle arrivée: ', ?, '\nNouvelle sortie: ', IFNULL(?, 'Non définie'), '\nRaison: ', ?),
                ?, 'pending')
    ");
    
    // Créer le type de présence pour les modifications de pointage s'il n'existe pas
    $stmt_type = $shop_pdo->prepare("
        INSERT IGNORE INTO presence_types (name, description, color) 
        VALUES ('modification_pointage', 'Demande de modification de pointage', '#17a2b8')
    ");
    $stmt_type->execute();
    
    if ($stmt->execute([
        $current_user_id, 
        $requested_clock_in, 
        $requested_clock_out, 
        $entry_id,
        $requested_clock_in, 
        $requested_clock_out, 
        $reason, 
        $current_user_id
    ])) {
        $success_message = "Demande de modification envoyée avec succès.";
    } else {
        $error_message = "Erreur lors de l'envoi de la demande.";
    }
}

// Calculer les statistiques de la semaine
$weekly_stats = [
    'total_hours' => 0,
    'total_days' => 0,
    'avg_daily_hours' => 0,
    'total_break_hours' => 0
];

foreach ($user_timetracking as $entry) {
    if ($entry['status'] === 'completed' && $entry['work_duration']) {
        $weekly_stats['total_hours'] += $entry['work_duration'];
        $weekly_stats['total_days']++;
        $weekly_stats['total_break_hours'] += $entry['break_duration'] ?? 0;
    }
}

if ($weekly_stats['total_days'] > 0) {
    $weekly_stats['avg_daily_hours'] = $weekly_stats['total_hours'] / $weekly_stats['total_days'];
}
?>

<!-- Section à ajouter dans presence_gestion.php après le contenu existant -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-clock"></i> Mon Système de Pointage</h4>
            </div>
            <div class="card-body">
                
                <!-- Statut actuel -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-<?php echo $current_tracking ? ($current_tracking['status'] === 'break' ? 'warning' : 'success') : 'secondary'; ?>">
                            <div class="card-body text-center">
                                <h5>Statut actuel</h5>
                                <?php if ($current_tracking): ?>
                                    <?php if ($current_tracking['status'] === 'active'): ?>
                                        <i class="fas fa-clock text-success fa-3x mb-2"></i>
                                        <h4 class="text-success">En cours de travail</h4>
                                        <p>Pointé depuis: <?php echo date('H:i', strtotime($current_tracking['clock_in'])); ?></p>
                                    <?php elseif ($current_tracking['status'] === 'break'): ?>
                                        <i class="fas fa-pause text-warning fa-3x mb-2"></i>
                                        <h4 class="text-warning">En pause</h4>
                                        <p>Début pause: <?php echo $current_tracking['break_start'] ? date('H:i', strtotime($current_tracking['break_start'])) : 'N/A'; ?></p>
                                    <?php endif; ?>
                                    
                                    <!-- Boutons d'action -->
                                    <div class="mt-3">
                                        <?php if ($current_tracking['status'] === 'active'): ?>
                                            <button class="btn btn-warning btn-sm me-2" onclick="timeTracking?.startBreak()">
                                                <i class="fas fa-pause"></i> Commencer pause
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="timeTracking?.clockOut()">
                                                <i class="fas fa-sign-out-alt"></i> Pointer sortie
                                            </button>
                                        <?php elseif ($current_tracking['status'] === 'break'): ?>
                                            <button class="btn btn-success btn-sm me-2" onclick="timeTracking?.endBreak()">
                                                <i class="fas fa-play"></i> Reprendre travail
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="timeTracking?.clockOut()">
                                                <i class="fas fa-sign-out-alt"></i> Pointer sortie
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-user-clock text-secondary fa-3x mb-2"></i>
                                    <h4 class="text-secondary">Non pointé</h4>
                                    <p>Vous n'êtes pas actuellement pointé</p>
                                    <button class="btn btn-success" onclick="timeTracking?.clockIn()">
                                        <i class="fas fa-sign-in-alt"></i> Pointer arrivée
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistiques de la semaine -->
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-body">
                                <h5><i class="fas fa-chart-line text-info"></i> Statistiques de la semaine</h5>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-primary"><?php echo number_format($weekly_stats['total_hours'], 1); ?>h</h4>
                                        <small class="text-muted">Total travaillé</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success"><?php echo $weekly_stats['total_days']; ?></h4>
                                        <small class="text-muted">Jours travaillés</small>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <h4 class="text-info"><?php echo number_format($weekly_stats['avg_daily_hours'], 1); ?>h</h4>
                                        <small class="text-muted">Moyenne/jour</small>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <h4 class="text-warning"><?php echo number_format($weekly_stats['total_break_hours'], 1); ?>h</h4>
                                        <small class="text-muted">Temps de pause</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Historique des pointages -->
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-history"></i> Mes pointages récents</h5>
                        
                        <?php if (empty($user_timetracking)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Aucun pointage trouvé pour cette semaine.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Arrivée</th>
                                            <th>Sortie</th>
                                            <th>Temps travaillé</th>
                                            <th>Pause</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_timetracking as $entry): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($entry['work_date'])); ?></td>
                                            <td>
                                                <strong><?php echo $entry['clock_in_time']; ?></strong>
                                                <?php if ($entry['location']): ?>
                                                    <i class="fas fa-map-marker-alt text-info ms-1" title="Géolocalisé"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($entry['clock_out_time']): ?>
                                                    <strong><?php echo $entry['clock_out_time']; ?></strong>
                                                <?php else: ?>
                                                    <span class="text-muted">En cours</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($entry['work_duration']): ?>
                                                    <span class="badge bg-success"><?php echo number_format($entry['work_duration'], 2); ?>h</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($entry['break_duration'] > 0): ?>
                                                    <span class="badge bg-warning"><?php echo number_format($entry['break_duration'], 2); ?>h</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'active' => 'success',
                                                    'break' => 'warning', 
                                                    'completed' => 'secondary'
                                                ];
                                                $status_labels = [
                                                    'active' => 'Actif',
                                                    'break' => 'Pause',
                                                    'completed' => 'Terminé'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_colors[$entry['status']] ?? 'secondary'; ?>">
                                                    <?php echo $status_labels[$entry['status']] ?? $entry['status']; ?>
                                                </span>
                                                <?php if ($entry['admin_approved']): ?>
                                                    <i class="fas fa-check-circle text-success ms-1" title="Approuvé"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($entry['status'] === 'completed' && !$entry['admin_approved']): ?>
                                                    <button class="btn btn-outline-primary btn-sm" 
                                                            onclick="requestModification(<?php echo $entry['id']; ?>)">
                                                        <i class="fas fa-edit"></i> Modifier
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour demande de modification -->
<div class="modal fade" id="modificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Demander une modification de pointage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="request_modification">
                    <input type="hidden" name="entry_id" id="mod_entry_id">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Cette demande sera envoyée à votre administrateur pour validation.
                    </div>
                    
                    <div class="mb-3">
                        <label for="requested_clock_in" class="form-label">Nouvelle heure d'arrivée</label>
                        <input type="datetime-local" class="form-control" name="requested_clock_in" id="requested_clock_in" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requested_clock_out" class="form-label">Nouvelle heure de sortie</label>
                        <input type="datetime-local" class="form-control" name="requested_clock_out" id="requested_clock_out">
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Raison de la modification</label>
                        <textarea class="form-control" name="reason" id="reason" rows="3" required 
                                  placeholder="Expliquez pourquoi vous demandez cette modification..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Envoyer la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript pour les interactions -->
<script>
// Assurer que le système de pointage est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Actualiser le statut toutes les minutes
    setInterval(async function() {
        if (window.timeTracking) {
            await window.timeTracking.getCurrentStatus();
            window.timeTracking.updateUI();
        }
    }, 60000);
});

function requestModification(entryId) {
    document.getElementById('mod_entry_id').value = entryId;
    
    // Optionnel: pré-remplir avec les valeurs actuelles
    // Ici vous pourriez faire un appel AJAX pour récupérer les données
    
    new bootstrap.Modal(document.getElementById('modificationModal')).show();
}

// Fonction pour afficher les notifications de succès/erreur
function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : type === 'success' ? 'alert-success' : 'alert-info';
    const icon = type === 'error' ? 'fas fa-exclamation-circle' : type === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// Afficher les messages de succès/erreur PHP
<?php if (isset($success_message)): ?>
showNotification('<?php echo addslashes($success_message); ?>', 'success');
<?php endif; ?>

<?php if (isset($error_message)): ?>
showNotification('<?php echo addslashes($error_message); ?>', 'error');
<?php endif; ?>
</script>

<!-- Styles CSS additionnels -->
<style>
.card-body .badge {
    font-size: 0.8em;
}

.table td {
    vertical-align: middle;
}

.time-display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.9em;
    }
    
    .btn-sm {
        font-size: 0.7em;
        padding: 0.2em 0.4em;
    }
}
</style>

