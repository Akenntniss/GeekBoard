<?php
// Vérifier si la récompense a été versée
$recompense_versee = !empty($mission['gain_reel']) || !empty($mission['points_reels']);
$date_completion = $mission['date_completion'] ? date('d/m/Y', strtotime($mission['date_completion'])) : 'Non définie';
?>

<div class="card mission-card shadow-sm h-100 border-success">
    <!-- Header avec badge de succès -->
    <div class="card-header border-0" style="background: linear-gradient(135deg, #2ecc7115, #27ae6015);">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 50px; height: 50px; background: #2ecc7120;">
                    <i class="fas fa-trophy fa-lg text-success"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-1 fw-bold text-success"><?= htmlspecialchars($mission['type_nom']) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($mission['titre']) ?></small>
            </div>
            <div class="text-end">
                <span class="badge bg-success">
                    <i class="fas fa-check-circle me-1"></i>Complétée
                </span>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Progression complète -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="fw-semibold text-success">Mission accomplie</small>
                <small class="text-success fw-bold">
                    <?= $mission['progression_actuelle'] ?> / <?= $mission['objectif_nombre'] ?> (100%)
                </small>
            </div>
            <div class="progress mission-progress" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>

        <!-- Date de completion -->
        <div class="mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-calendar-check text-success me-2"></i>
                <small class="text-muted">Complétée le </small>
                <small class="fw-semibold ms-1"><?= $date_completion ?></small>
            </div>
        </div>

        <!-- Récompenses -->
        <div class="row g-2 mb-3">
            <!-- Prime en euros -->
            <div class="col-6">
                <div class="text-center p-3 rounded border border-success" style="background: linear-gradient(135deg, #2ecc7110, #2ecc7108);">
                    <i class="fas fa-euro-sign fa-lg mb-2 <?= $recompense_versee ? 'text-success' : 'text-muted' ?>"></i>
                    <div class="h5 mb-1">
                        <?php if ($recompense_versee && !empty($mission['gain_reel'])): ?>
                            <span class="text-success"><?= number_format($mission['gain_reel'], 2) ?>€</span>
                        <?php else: ?>
                            <span class="text-muted"><?= number_format($mission['recompense_euros'], 2) ?>€</span>
                        <?php endif; ?>
                    </div>
                    <small class="<?= $recompense_versee ? 'text-success' : 'text-warning' ?>">
                        <?= $recompense_versee ? 'Versée' : 'En attente' ?>
                    </small>
                </div>
            </div>
            
            <!-- Points XP -->
            <div class="col-6">
                <div class="text-center p-3 rounded border border-warning" style="background: linear-gradient(135deg, #f1c40f10, #f1c40f08);">
                    <i class="fas fa-star fa-lg mb-2 <?= $recompense_versee ? 'text-warning' : 'text-muted' ?>"></i>
                    <div class="h5 mb-1">
                        <?php if ($recompense_versee && !empty($mission['points_reels'])): ?>
                            <span class="text-warning"><?= $mission['points_reels'] ?></span>
                        <?php else: ?>
                            <span class="text-muted"><?= $mission['recompense_points'] ?></span>
                        <?php endif; ?>
                    </div>
                    <small class="<?= $recompense_versee ? 'text-warning' : 'text-muted' ?>">
                        <?= $recompense_versee ? 'Attribués' : 'En attente' ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Statut de la récompense -->
        <?php if ($recompense_versee): ?>
            <div class="alert alert-success alert-sm mb-3 text-center">
                <i class="fas fa-check-double me-1"></i>
                <strong>Récompense versée !</strong>
                <br><small>Merci pour votre excellent travail</small>
            </div>
        <?php else: ?>
            <div class="alert alert-warning alert-sm mb-3 text-center">
                <i class="fas fa-hourglass-half me-1"></i>
                <strong>Validation en cours</strong>
                <br><small>Votre récompense sera versée sous peu</small>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer avec détails -->
    <div class="card-footer border-0 bg-transparent">
        <!-- Bouton pour voir les détails (optionnel) -->
        <button type="button" class="btn btn-outline-success w-100" 
                onclick="showMissionDetails(<?= $mission['mission_id'] ?>)">
            <i class="fas fa-eye me-2"></i>Voir les détails
        </button>
    </div>
</div>

<script>
function showMissionDetails(missionId) {
    // Cette fonction peut être développée pour afficher les détails de validation
    // dans une modal ou rediriger vers une page de détails
    console.log('Afficher détails mission:', missionId);
    // Exemple de redirection :
    // window.location.href = `?page=mission_details&id=${missionId}`;
}
</script>

<style>
.alert-sm {
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

.border-success {
    border-color: #28a745 !important;
    border-width: 2px !important;
}

.mission-card.border-success {
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1) !important;
}

.mission-card.border-success:hover {
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.15) !important;
    transform: translateY(-2px);
}
</style> 