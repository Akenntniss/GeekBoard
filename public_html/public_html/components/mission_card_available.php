<?php
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

// Vérifier si la mission est urgente (moins de 7 jours)
$is_urgent = $jours_restants !== null && $jours_restants <= 7 && $jours_restants >= 0;
?>

<div class="card mission-card shadow-sm h-100 <?= $is_urgent ? 'border-warning' : '' ?>">
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
                    <?php if ($jours_restants > 7): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-calendar-check me-1"></i><?= $jours_restants ?> jours
                        </span>
                    <?php elseif ($jours_restants > 0): ?>
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

        <!-- Objectif -->
        <div class="mb-3">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-target text-primary me-2"></i>
                <small class="fw-semibold">Objectif</small>
            </div>
            <div class="bg-light p-2 rounded">
                <span class="fw-bold text-primary"><?= $mission['objectif_nombre'] ?></span>
                <span class="text-muted">tâches à accomplir</span>
                <?php if ($mission['periode_jours']): ?>
                    <span class="text-muted">en <?= $mission['periode_jours'] ?> jours</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Récompenses -->
        <div class="row g-2 mb-3">
            <?php if ($mission['recompense_euros'] > 0): ?>
                <div class="col-6">
                    <div class="text-center p-3 rounded border" style="background: linear-gradient(135deg, #2ecc7115, #2ecc7110);">
                        <i class="fas fa-euro-sign text-success fa-lg mb-2"></i>
                        <div class="h5 mb-1 text-success"><?= number_format($mission['recompense_euros'], 2) ?>€</div>
                        <small class="text-muted">Prime garantie</small>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($mission['recompense_points'] > 0): ?>
                <div class="col-6">
                    <div class="text-center p-3 rounded border" style="background: linear-gradient(135deg, #f1c40f15, #f1c40f10);">
                        <i class="fas fa-star text-warning fa-lg mb-2"></i>
                        <div class="h5 mb-1 text-warning"><?= $mission['recompense_points'] ?></div>
                        <small class="text-muted">Points XP</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dates importantes -->
        <div class="mb-3">
            <div class="row g-2 text-center">
                <?php if ($mission['date_debut']): ?>
                    <div class="col-6">
                        <small class="text-muted d-block">Début</small>
                        <small class="fw-semibold"><?= date('d/m', strtotime($mission['date_debut'])) ?></small>
                    </div>
                <?php endif; ?>
                <?php if ($mission['date_fin']): ?>
                    <div class="col-6">
                        <small class="text-muted d-block">Fin</small>
                        <small class="fw-semibold"><?= date('d/m', strtotime($mission['date_fin'])) ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badge d'urgence -->
        <?php if ($is_urgent): ?>
            <div class="alert alert-warning alert-sm mb-3 text-center">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>Mission urgente !</strong> Fin dans <?= $jours_restants ?> jour<?= $jours_restants > 1 ? 's' : '' ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer avec action -->
    <div class="card-footer border-0 bg-transparent">
        <?php if ($jours_restants === null || $jours_restants >= 0): ?>
            <form method="POST" class="mb-0">
                <input type="hidden" name="action" value="rejoindre_mission">
                <input type="hidden" name="mission_id" value="<?= $mission['id'] ?>">
                <button type="submit" class="btn btn-outline-primary w-100 btn-lg">
                    <i class="fas fa-plus-circle me-2"></i>Rejoindre la mission
                </button>
            </form>
        <?php else: ?>
            <button class="btn btn-secondary w-100" disabled>
                <i class="fas fa-times-circle me-2"></i>Mission expirée
            </button>
        <?php endif; ?>
    </div>
</div>

<style>
.alert-sm {
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

.border-warning {
    border-color: #ffc107 !important;
    border-width: 2px !important;
}

.card:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease;
}
</style> 