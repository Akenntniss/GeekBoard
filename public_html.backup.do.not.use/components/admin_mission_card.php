<?php
// Calcul du taux de completion
$taux_completion = $mission['participants'] > 0 ? ($mission['completions'] / $mission['participants']) * 100 : 0;

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

// Couleur du statut
$status_colors = [
    'active' => 'success',
    'inactive' => 'secondary', 
    'archivee' => 'dark'
];
$status_color = $status_colors[$mission['statut']] ?? 'secondary';
?>

<div class="card h-100 shadow-sm">
    <!-- Header avec statut et actions -->
    <div class="card-header border-0" style="background: <?= htmlspecialchars($mission['couleur']) ?>15;">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px; background: <?= htmlspecialchars($mission['couleur']) ?>20;">
                        <i class="<?= htmlspecialchars($mission['icon']) ?> fa-lg" style="color: <?= htmlspecialchars($mission['couleur']) ?>;"></i>
                    </div>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($mission['type_nom']) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($mission['titre']) ?></small>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_mission_status">
                            <input type="hidden" name="mission_id" value="<?= $mission['id'] ?>">
                            <input type="hidden" name="new_status" value="<?= $mission['statut'] === 'active' ? 'inactive' : 'active' ?>">
                            <button class="dropdown-item" type="submit">
                                <i class="fas fa-toggle-<?= $mission['statut'] === 'active' ? 'off' : 'on' ?> me-2"></i>
                                <?= $mission['statut'] === 'active' ? 'Désactiver' : 'Activer' ?>
                            </button>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_mission_status">
                            <input type="hidden" name="mission_id" value="<?= $mission['id'] ?>">
                            <input type="hidden" name="new_status" value="archivee">
                            <button class="dropdown-item text-warning" type="submit">
                                <i class="fas fa-archive me-2"></i>Archiver
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Statut et urgence -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="badge bg-<?= $status_color ?> text-uppercase"><?= htmlspecialchars($mission['statut']) ?></span>
            <?php if ($jours_restants !== null): ?>
                <?php if ($jours_restants > 0): ?>
                    <span class="badge bg-<?= $jours_restants <= 7 ? 'warning' : 'info' ?>">
                        <i class="fas fa-clock me-1"></i><?= $jours_restants ?> jour<?= $jours_restants > 1 ? 's' : '' ?>
                    </span>
                <?php elseif ($jours_restants === 0): ?>
                    <span class="badge bg-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>Dernier jour
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary">Expirée</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <p class="text-muted small mb-3"><?= htmlspecialchars(substr($mission['description'], 0, 100)) ?><?= strlen($mission['description']) > 100 ? '...' : '' ?></p>

        <!-- Métriques principales -->
        <div class="row g-2 mb-3 text-center">
            <div class="col-3">
                <div class="border rounded p-2">
                    <div class="h6 mb-0 text-primary"><?= $mission['participants'] ?></div>
                    <small class="text-muted">Participants</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border rounded p-2">
                    <div class="h6 mb-0 text-success"><?= $mission['completions'] ?></div>
                    <small class="text-muted">Complétées</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border rounded p-2">
                    <div class="h6 mb-0 text-warning"><?= $mission['validations_en_attente'] ?></div>
                    <small class="text-muted">En attente</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border rounded p-2">
                    <div class="h6 mb-0 text-info"><?= number_format($taux_completion, 1) ?>%</div>
                    <small class="text-muted">Taux</small>
                </div>
            </div>
        </div>

        <!-- Barre de progression -->
        <?php if ($mission['participants'] > 0): ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="fw-semibold">Progression globale</small>
                    <small class="text-muted"><?= $mission['completions'] ?>/<?= $mission['participants'] ?></small>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" style="width: <?= $taux_completion ?>%; background: <?= htmlspecialchars($mission['couleur']) ?>;" 
                         role="progressbar" aria-valuenow="<?= $taux_completion ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Objectif et récompenses -->
        <div class="row g-2 mb-3">
            <div class="col-4">
                <small class="text-muted d-block">Objectif</small>
                <span class="fw-bold"><?= $mission['objectif_nombre'] ?></span>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Prime</small>
                <span class="fw-bold text-success"><?= number_format($mission['recompense_euros'], 2) ?>€</span>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Points</small>
                <span class="fw-bold text-warning"><?= $mission['recompense_points'] ?></span>
            </div>
        </div>

        <!-- Période -->
        <?php if ($mission['date_debut'] || $mission['date_fin']): ?>
            <div class="row g-2 mb-3 text-center">
                <?php if ($mission['date_debut']): ?>
                    <div class="col-6">
                        <small class="text-muted d-block">Début</small>
                        <small class="fw-semibold"><?= date('d/m/Y', strtotime($mission['date_debut'])) ?></small>
                    </div>
                <?php endif; ?>
                <?php if ($mission['date_fin']): ?>
                    <div class="col-6">
                        <small class="text-muted d-block">Fin</small>
                        <small class="fw-semibold"><?= date('d/m/Y', strtotime($mission['date_fin'])) ?></small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Coût total -->
        <?php if ($mission['total_recompenses'] > 0): ?>
            <div class="alert alert-info alert-sm mb-3 text-center">
                <i class="fas fa-euro-sign me-1"></i>
                <strong><?= number_format($mission['total_recompenses'], 2) ?>€</strong> versés
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer avec actions -->
    <div class="card-footer border-0 bg-transparent">
        <div class="row g-2">
            <div class="col-6">
                <button class="btn btn-outline-primary btn-sm w-100" onclick="showMissionParticipants(<?= $mission['id'] ?>)">
                    <i class="fas fa-users me-1"></i>Participants
                </button>
            </div>
            <div class="col-6">
                <button class="btn btn-outline-secondary btn-sm w-100" onclick="showMissionDetails(<?= $mission['id'] ?>)">
                    <i class="fas fa-eye me-1"></i>Détails
                </button>
            </div>
        </div>
        
        <!-- Alerte si des validations sont en attente -->
        <?php if ($mission['validations_en_attente'] > 0): ?>
            <div class="mt-2">
                <button class="btn btn-warning btn-sm w-100" onclick="document.getElementById('validations-tab').click()">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <?= $mission['validations_en_attente'] ?> validation<?= $mission['validations_en_attente'] > 1 ? 's' : '' ?> en attente
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showMissionParticipants(missionId) {
    // Fonction pour afficher les participants d'une mission
    console.log('Afficher participants mission:', missionId);
    // Implémentation future : modal avec liste des participants
}

function showMissionDetails(missionId) {
    // Fonction pour afficher les détails d'une mission
    console.log('Afficher détails mission:', missionId);
    // Implémentation future : modal ou page de détails
}
</script>

<style>
.alert-sm {
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

.progress {
    border-radius: 3px;
}

.border {
    border-color: #e9ecef !important;
}

.border:hover {
    border-color: #dee2e6 !important;
    background-color: #f8f9fa !important;
}
</style> 