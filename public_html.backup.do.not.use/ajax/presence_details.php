<?php
session_start();

// Vérification des droits administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Accès refusé</div>';
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/presence_auto_init.php';

// Auto-initialiser le système de présence si nécessaire
if (!isPresenceSystemInitialized()) {
    initializePresenceSystem();
}

$event_id = $_GET['id'] ?? 0;

if (!$event_id) {
    echo '<div class="alert alert-warning">ID d\'événement manquant</div>';
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les détails de l'événement
    $stmt = $shop_pdo->prepare("
        SELECT pe.*, 
               u.full_name as employee_name,
               u.email as employee_email,
               pt.display_name as type_name,
               pt.color_code,
               pt.is_paid,
               pt.name as type_key,
               creator.full_name as created_by_name,
               approver.full_name as approved_by_name
        FROM presence_events pe
        JOIN users u ON pe.employee_id = u.id
        JOIN presence_types pt ON pe.presence_type_id = pt.id
        LEFT JOIN users creator ON pe.created_by = creator.id
        LEFT JOIN users approver ON pe.approved_by = approver.id
        WHERE pe.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        echo '<div class="alert alert-warning">Événement non trouvé</div>';
        exit;
    }
    
    // Récupérer les commentaires
    $stmt = $shop_pdo->prepare("
        SELECT pc.*, u.full_name as user_name
        FROM presence_comments pc
        JOIN users u ON pc.user_id = u.id
        WHERE pc.presence_event_id = ?
        ORDER BY pc.created_at DESC
    ");
    $stmt->execute([$event_id]);
    $comments = $stmt->fetchAll();
    
    // Récupérer l'historique
    $stmt = $shop_pdo->prepare("
        SELECT ph.*, u.full_name as user_name
        FROM presence_history ph
        JOIN users u ON ph.user_id = u.id
        WHERE ph.presence_event_id = ?
        ORDER BY ph.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$event_id]);
    $history = $stmt->fetchAll();

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur lors du chargement : ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// Fonctions d'aide
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}

function getStatusBadge($status) {
    $classes = [
        'pending' => 'bg-warning',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger'
    ];
    $texts = [
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté'
    ];
    
    $class = $classes[$status] ?? 'bg-secondary';
    $text = $texts[$status] ?? ucfirst($status);
    
    return '<span class="badge ' . $class . '">' . $text . '</span>';
}
?>

<div class="container-fluid">
    <!-- En-tête de l'événement -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0">
                    <span class="badge" style="background-color: <?php echo $event['color_code']; ?>;">
                        <?php echo htmlspecialchars($event['type_name']); ?>
                    </span>
                    <?php if ($event['is_paid']): ?>
                        <small class="text-success ms-1">
                            <i class="fas fa-dollar-sign" title="Payé"></i>
                        </small>
                    <?php endif; ?>
                </h6>
                <?php echo getStatusBadge($event['status']); ?>
            </div>
        </div>
    </div>

    <!-- Informations principales -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card border-0 bg-light">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-user me-2"></i>Employé
                    </h6>
                    <p class="mb-1">
                        <strong><?php echo htmlspecialchars($event['employee_name']); ?></strong>
                    </p>
                    <?php if ($event['employee_email']): ?>
                        <p class="small text-muted mb-0">
                            <i class="fas fa-envelope me-1"></i>
                            <?php echo htmlspecialchars($event['employee_email']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 bg-light">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-calendar me-2"></i>Période
                    </h6>
                    <p class="mb-1">
                        <?php if ($event['date_end'] && $event['date_end'] != $event['date_start']): ?>
                            Du <?php echo formatDate($event['date_start']); ?><br>
                            au <?php echo formatDate($event['date_end']); ?>
                        <?php else: ?>
                            <?php echo formatDate($event['date_start']); ?>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($event['time_start']): ?>
                        <p class="small text-muted mb-0">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo formatTime($event['time_start']); ?>
                            <?php if ($event['time_end']): ?>
                                - <?php echo formatTime($event['time_end']); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Durée -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 bg-light">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-hourglass-half me-2"></i>Durée
                    </h6>
                    <p class="mb-0">
                        <?php if ($event['duration_days']): ?>
                            <span class="badge bg-info"><?php echo $event['duration_days']; ?> jour(s)</span>
                        <?php endif; ?>
                        <?php if ($event['duration_minutes']): ?>
                            <span class="badge bg-warning"><?php echo $event['duration_minutes']; ?> minute(s)</span>
                        <?php endif; ?>
                        <?php if (!$event['duration_days'] && !$event['duration_minutes']): ?>
                            <span class="text-muted">Non définie</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Raison/Justification -->
    <?php if ($event['reason']): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 bg-light">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-comment me-2"></i>Raison/Justification
                    </h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($event['reason'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Informations administratives -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card border-0 bg-light">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-user-plus me-2"></i>Créé par
                    </h6>
                    <p class="mb-1"><?php echo htmlspecialchars($event['created_by_name']); ?></p>
                    <p class="small text-muted mb-0">
                        <?php echo formatDateTime($event['created_at']); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <?php if ($event['approved_by_name']): ?>
        <div class="col-md-6">
            <div class="card border-0 bg-light">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">
                        <i class="fas fa-user-check me-2"></i>
                        <?php echo $event['status'] == 'approved' ? 'Approuvé' : 'Traité'; ?> par
                    </h6>
                    <p class="mb-1"><?php echo htmlspecialchars($event['approved_by_name']); ?></p>
                    <?php if ($event['approved_at']): ?>
                        <p class="small text-muted mb-0">
                            <?php echo formatDateTime($event['approved_at']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Actions rapides -->
    <?php if ($event['status'] == 'pending'): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Actions rapides</h6>
                <div class="d-flex gap-2">
                    <form method="POST" action="index.php?page=presence_gestion" style="display: inline;">
                        <input type="hidden" name="action" value="approve_event">
                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                        <button type="submit" class="btn btn-success btn-sm" 
                                onclick="return confirm('Approuver cet événement ?')">
                            <i class="fas fa-check me-1"></i>Approuver
                        </button>
                    </form>
                    <form method="POST" action="index.php?page=presence_gestion" style="display: inline;">
                        <input type="hidden" name="action" value="reject_event">
                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Rejeter cet événement ?')">
                            <i class="fas fa-times me-1"></i>Rejeter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Commentaires -->
    <?php if (!empty($comments)): ?>
    <div class="row mb-3">
        <div class="col-12">
            <h6><i class="fas fa-comments me-2"></i>Commentaires</h6>
            <?php foreach ($comments as $comment): ?>
                <div class="card border-start border-4 border-primary mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong>
                                <?php if ($comment['is_internal']): ?>
                                    <span class="badge bg-warning ms-1">Interne</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?php echo formatDateTime($comment['created_at']); ?>
                            </small>
                        </div>
                        <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historique -->
    <?php if (!empty($history)): ?>
    <div class="row">
        <div class="col-12">
            <h6><i class="fas fa-history me-2"></i>Historique</h6>
            <div class="timeline">
                <?php foreach ($history as $h): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($h['user_name']); ?></strong>
                                <small class="text-muted">
                                    <?php echo formatDateTime($h['created_at']); ?>
                                </small>
                            </div>
                            <p class="mb-0">
                                <?php
                                $actions = [
                                    'created' => 'a créé l\'événement',
                                    'updated' => 'a modifié l\'événement',
                                    'approved' => 'a approuvé l\'événement',
                                    'rejected' => 'a rejeté l\'événement',
                                    'deleted' => 'a supprimé l\'événement'
                                ];
                                echo $actions[$h['action']] ?? $h['action'];
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item {
    position: relative;
    padding-bottom: 1rem;
}

.timeline-marker {
    position: absolute;
    left: -1rem;
    top: 0.25rem;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
    background-color: #007bff;
    border: 2px solid white;
}

.timeline-content {
    background-color: #f8f9fa;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    border-left: 3px solid #007bff;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.badge {
    font-size: 0.75em;
}
</style>

