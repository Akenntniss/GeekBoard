<!-- Modal de confirmation pour arrêter une réparation -->
<div class="modal fade" id="stopRepairConfirmModal" tabindex="-1" aria-labelledby="stopRepairConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="stopRepairConfirmModalLabel">
                    <i class="fas fa-stop-circle me-2"></i>
                    Arrêter la réparation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-3">Êtes-vous sûr de vouloir arrêter cette réparation ?</h5>
                <p class="text-muted">Vous pourrez choisir le statut après confirmation.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-lg px-5" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-warning btn-lg px-5" id="confirmStopRepairBtn">
                    <i class="fas fa-check me-2"></i>Oui, arrêter
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le modal de confirmation d'arrêt */
#stopRepairConfirmModal .modal-content {
    border-radius: 16px;
    overflow: hidden;
}

#stopRepairConfirmModal .modal-header {
    border-bottom: none;
    padding: 1.5rem;
}

#stopRepairConfirmModal .modal-body {
    padding: 2rem;
}

#stopRepairConfirmModal .modal-footer {
    border-top: none;
    padding: 1.5rem;
}

#stopRepairConfirmModal .btn {
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

#stopRepairConfirmModal .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

/* Mode nuit */
.dark-mode #stopRepairConfirmModal .modal-content,
.night-mode #stopRepairConfirmModal .modal-content,
body.dark-mode #stopRepairConfirmModal .modal-content,
body.night-mode #stopRepairConfirmModal .modal-content {
    background: #1a202c;
    color: #e2e8f0;
}

.dark-mode #stopRepairConfirmModal .modal-header,
.night-mode #stopRepairConfirmModal .modal-header,
body.dark-mode #stopRepairConfirmModal .modal-header,
body.night-mode #stopRepairConfirmModal .modal-header {
    background: #f59e0b !important;
    color: #1a202c !important;
}

.dark-mode #stopRepairConfirmModal .text-muted,
.night-mode #stopRepairConfirmModal .text-muted,
body.dark-mode #stopRepairConfirmModal .text-muted,
body.night-mode #stopRepairConfirmModal .text-muted {
    color: #a0aec0 !important;
}

.dark-mode #stopRepairConfirmModal .btn-secondary,
.night-mode #stopRepairConfirmModal .btn-secondary,
body.dark-mode #stopRepairConfirmModal .btn-secondary,
body.night-mode #stopRepairConfirmModal .btn-secondary {
    background: #2d3748;
    border-color: #2d3748;
    color: #e2e8f0;
}

.dark-mode #stopRepairConfirmModal .btn-secondary:hover,
.night-mode #stopRepairConfirmModal .btn-secondary:hover,
body.dark-mode #stopRepairConfirmModal .btn-secondary:hover,
body.night-mode #stopRepairConfirmModal .btn-secondary:hover {
    background: #4a5568;
    border-color: #4a5568;
}
</style>
