<?php
// Calcul du pourcentage de progression
$pourcentage = ($mission['progression_actuelle'] / $mission['objectif_nombre']) * 100;
$pourcentage = min(100, $pourcentage); // Max 100%

// Calcul des jours restants
$jours_restants = null;
if ($mission['date_fin']) {
    $date_fin = new DateTime($mission['date_fin']);
    $aujourd_hui = new DateTime();
    $diff = $aujourd_hui->diff($date_fin);
    $jours_restants = $diff->days;
    if ($date_fin < $aujourd_hui) {
        $jours_restants = -$jours_restants;
    }
}
?>

<div class="card mission-card shadow-sm h-100">
    <!-- Header avec icône et type -->
    <div class="card-header border-0" style="background: <?= htmlspecialchars($mission['couleur']) ?>15;">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 50px; height: 50px; background: <?= htmlspecialchars($mission['couleur']) ?>20;">
                    <i class="<?= htmlspecialchars($mission['icon']) ?> fa-lg" style="color: <?= htmlspecialchars($mission['couleur']) ?>;"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($mission['type_nom']) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($mission['titre']) ?></small>
            </div>
            <?php if ($jours_restants !== null): ?>
                <div class="text-end">
                    <?php if ($jours_restants > 0): ?>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-clock me-1"></i><?= $jours_restants ?> jour<?= $jours_restants > 1 ? 's' : '' ?>
                        </span>
                    <?php elseif ($jours_restants === 0): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>Dernier jour
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary">
                            <i class="fas fa-times-circle me-1"></i>Expirée
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body">
        <!-- Description -->
        <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($mission['description'])) ?></p>

        <!-- Progression -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="fw-semibold">Progression</small>
                <small class="text-muted">
                    <?= $mission['progression_actuelle'] ?> / <?= $mission['objectif_nombre'] ?>
                    (<?= number_format($pourcentage, 1) ?>%)
                </small>
            </div>
            <div class="progress mission-progress" style="height: 8px;">
                <div class="progress-bar" role="progressbar" 
                     style="width: <?= $pourcentage ?>%; background: linear-gradient(90deg, <?= htmlspecialchars($mission['couleur']) ?>, <?= htmlspecialchars($mission['couleur']) ?>80);"
                     aria-valuenow="<?= $pourcentage ?>" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>

        <!-- Récompenses -->
        <div class="row g-2 mb-3">
            <?php if ($mission['recompense_euros'] > 0): ?>
                <div class="col-6">
                    <div class="text-center p-2 rounded" style="background: #f8f9fa;">
                        <i class="fas fa-euro-sign text-success mb-1"></i>
                        <div class="fw-bold text-success"><?= number_format($mission['recompense_euros'], 2) ?>€</div>
                        <small class="text-muted">Prime</small>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($mission['recompense_points'] > 0): ?>
                <div class="col-6">
                    <div class="text-center p-2 rounded" style="background: #f8f9fa;">
                        <i class="fas fa-star text-warning mb-1"></i>
                        <div class="fw-bold text-warning"><?= $mission['recompense_points'] ?></div>
                        <small class="text-muted">Points</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dernières validations -->
        <?php if ($mission['validations_count'] > 0): ?>
            <div class="mb-3">
                <small class="text-muted">
                    <i class="fas fa-check-circle text-success me-1"></i>
                    <?= $mission['validations_count'] ?> validation<?= $mission['validations_count'] > 1 ? 's' : '' ?> en attente
                </small>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer avec actions -->
    <div class="card-footer border-0 bg-transparent">
        <?php if ($mission['progression_actuelle'] < $mission['objectif_nombre']): ?>
            <button type="button" class="btn btn-primary w-100" 
                    onclick="validateTask(<?= $mission['id'] ?>, <?= $mission['mission_id'] ?>)">
                <i class="fas fa-plus me-2"></i>Valider une tâche
            </button>
        <?php else: ?>
            <div class="alert alert-success mb-0 text-center">
                <i class="fas fa-trophy me-2"></i>Mission terminée !
                <br><small>En attente de validation admin</small>
            </div>
        <?php endif; ?>
    </div>
</div> 