<!-- NOUVEAU MODAL DE MISE À JOUR DES STATUTS PAR LOTS -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(30, 144, 255, 0.3);">
            <!-- Header du modal -->
            <div class="modal-header" style="background: linear-gradient(135deg, #1e90ff 0%, #0066cc 100%); color: white; padding: 20px;">
                <h5 class="modal-title d-flex align-items-center" id="updateStatusModalLabel" style="font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                    <i class="fas fa-tasks me-3" style="font-size: 1.2em;"></i>
                    📊 Mise à jour des statuts par lots
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer" style="filter: brightness(0) invert(1);"></button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body" style="padding: 25px; background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);">
                
                <!-- Section d'information -->
                <div class="alert alert-info mb-4" style="border-radius: 12px; border: none; background: linear-gradient(135deg, rgba(30, 144, 255, 0.1) 0%, rgba(30, 144, 255, 0.05) 100%); border-left: 4px solid #1e90ff;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-3" style="color: #1e90ff; font-size: 1.2em;"></i>
                        <div>
                            <strong style="color: #1e90ff;">Instructions</strong><br>
                            <span style="color: #666;">Sélectionnez les réparations terminées et choisissez le nouveau statut à appliquer.</span>
                        </div>
                    </div>
                </div>
                
                <!-- Formulaire -->
                <form id="newBatchUpdateForm" method="post">
                    
                    <!-- Section de sélection des réparations -->
                    <div class="card mb-4" style="border-radius: 12px; border: 1px solid rgba(30, 144, 255, 0.2); box-shadow: 0 2px 8px rgba(30, 144, 255, 0.1);">
                        <div class="card-header" style="background: linear-gradient(135deg, rgba(30, 144, 255, 0.1) 0%, rgba(30, 144, 255, 0.05) 100%); border-radius: 12px 12px 0 0; border-bottom: 1px solid rgba(30, 144, 255, 0.2);">
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="mb-0" style="color: #1e90ff; font-weight: 600;">
                                    <i class="fas fa-list me-2"></i>Réparations disponibles
                                </h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newSelectAllRepairs" style="border-color: #1e90ff;">
                                    <label class="form-check-label" for="newSelectAllRepairs" style="color: #666; font-size: 0.9em;">
                                        Tout sélectionner
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="newCompletedRepairsTable">
                                    <thead style="background: rgba(30, 144, 255, 0.05);">
                                        <tr>
                                            <th width="50" style="border-color: rgba(30, 144, 255, 0.1);">
                                                <span style="color: #666; font-size: 0.85em;">Sél.</span>
                                            </th>
                                            <th width="80" style="border-color: rgba(30, 144, 255, 0.1); color: #1e90ff; font-weight: 600;">ID</th>
                                            <th style="border-color: rgba(30, 144, 255, 0.1); color: #1e90ff; font-weight: 600;">Client</th>
                                            <th style="border-color: rgba(30, 144, 255, 0.1); color: #1e90ff; font-weight: 600;">Appareil</th>
                                            <th style="border-color: rgba(30, 144, 255, 0.1); color: #1e90ff; font-weight: 600;">Date</th>
                                            <th style="border-color: rgba(30, 144, 255, 0.1); color: #1e90ff; font-weight: 600;">Statut actuel</th>
                                        </tr>
                                    </thead>
                                    <tbody id="repairsTableBody">
                                        <!-- Les données seront chargées dynamiquement -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="loadingRepairs" class="text-center py-4">
                                <i class="fas fa-spinner fa-spin me-2" style="color: #1e90ff;"></i>
                                <span style="color: #666;">Chargement des réparations...</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section de choix du statut -->
                    <div class="card mb-4" style="border-radius: 12px; border: 1px solid rgba(30, 144, 255, 0.2); box-shadow: 0 2px 8px rgba(30, 144, 255, 0.1);">
                        <div class="card-header" style="background: linear-gradient(135deg, rgba(30, 144, 255, 0.1) 0%, rgba(30, 144, 255, 0.05) 100%); border-radius: 12px 12px 0 0; border-bottom: 1px solid rgba(30, 144, 255, 0.2);">
                            <h6 class="mb-0" style="color: #1e90ff; font-weight: 600;">
                                <i class="fas fa-exchange-alt me-2"></i>Nouveau statut à appliquer
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 20px;">
                            <div class="row g-3">
                                <!-- Option Restitué -->
                                <div class="col-md-4">
                                    <div class="status-option-new" data-status="restitue" style="border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="new_status" id="newStatusRestitue" value="restitue" style="display: none;">
                                            <label class="form-check-label d-flex align-items-center" for="newStatusRestitue" style="cursor: pointer; width: 100%;">
                                                <div class="status-icon-new me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);">
                                                    <i class="fas fa-check-circle text-white" style="font-size: 1.4em;"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: #28a745; font-size: 1.1em;">Restitué</div>
                                                    <div style="color: #666; font-size: 0.9em; margin-top: 4px;">L'appareil a été rendu au client</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Option Annulé -->
                                <div class="col-md-4">
                                    <div class="status-option-new" data-status="annule" style="border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="new_status" id="newStatusAnnule" value="annule" style="display: none;">
                                            <label class="form-check-label d-flex align-items-center" for="newStatusAnnule" style="cursor: pointer; width: 100%;">
                                                <div class="status-icon-new me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);">
                                                    <i class="fas fa-times-circle text-white" style="font-size: 1.4em;"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: #dc3545; font-size: 1.1em;">Annulé</div>
                                                    <div style="color: #666; font-size: 0.9em; margin-top: 4px;">La réparation a été annulée</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Option Gardiennage -->
                                <div class="col-md-4">
                                    <div class="status-option-new" data-status="gardiennage" style="border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="new_status" id="newStatusGardiennage" value="gardiennage" style="display: none;">
                                            <label class="form-check-label d-flex align-items-center" for="newStatusGardiennage" style="cursor: pointer; width: 100%;">
                                                <div class="status-icon-new me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);">
                                                    <i class="fas fa-warehouse text-white" style="font-size: 1.4em;"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: #ffc107; font-size: 1.1em;">Gardiennage</div>
                                                    <div style="color: #666; font-size: 0.9em; margin-top: 4px;">L'appareil est conservé en boutique</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section SMS -->
                    <div class="card" style="border-radius: 12px; border: 1px solid rgba(30, 144, 255, 0.2); box-shadow: 0 2px 8px rgba(30, 144, 255, 0.1);">
                        <div class="card-body" style="padding: 20px;">
                            <div class="form-check form-switch d-flex align-items-center">
                                <input class="form-check-input me-3" type="checkbox" id="newSendSmsCheckbox" name="send_sms" value="1" style="transform: scale(1.2); border-color: #1e90ff;">
                                <div class="d-flex align-items-center">
                                    <div class="icon-container me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #1e90ff 0%, #0066cc 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 144, 255, 0.3);">
                                        <i class="fas fa-sms text-white" style="font-size: 1.2em;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1e90ff; font-size: 1.1em;">Envoyer un SMS aux clients</div>
                                        <div style="color: #666; font-size: 0.9em; margin-top: 4px;">Le modèle de SMS correspondant au statut sélectionné sera envoyé</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </form>
                
            </div>
            
            <!-- Footer du modal -->
            <div class="modal-footer" style="padding: 20px; background: rgba(30, 144, 255, 0.05); border-top: 1px solid rgba(30, 144, 255, 0.1);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div id="selectionInfo" style="color: #666; font-size: 0.9em;">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="selectedCount">0</span> réparation(s) sélectionnée(s)
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal" style="border-radius: 8px; padding: 10px 20px;">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="button" class="btn btn-primary" id="newUpdateBatchStatus" disabled style="background: linear-gradient(135deg, #1e90ff 0%, #0066cc 100%); border: none; border-radius: 8px; padding: 10px 25px; box-shadow: 0 4px 15px rgba(30, 144, 255, 0.3);">
                            <i class="fas fa-save me-1"></i>Mettre à jour les statuts
                        </button>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- CSS spécifique pour le nouveau modal -->
<style>
/* Styles pour les options de statut */
.status-option-new:hover {
    border-color: #1e90ff !important;
    background: linear-gradient(135deg, rgba(30, 144, 255, 0.1) 0%, rgba(30, 144, 255, 0.05) 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(30, 144, 255, 0.2) !important;
}

.status-option-new.selected {
    border-color: #1e90ff !important;
    background: linear-gradient(135deg, rgba(30, 144, 255, 0.15) 0%, rgba(30, 144, 255, 0.1) 100%) !important;
    box-shadow: 0 4px 15px rgba(30, 144, 255, 0.3) !important;
}

.status-option-new.selected .status-icon-new {
    transform: scale(1.1);
}

/* Styles pour le tableau */
#newCompletedRepairsTable tbody tr:hover {
    background-color: rgba(30, 144, 255, 0.05) !important;
}

#newCompletedRepairsTable .form-check-input:checked {
    background-color: #1e90ff !important;
    border-color: #1e90ff !important;
}

/* Animation pour les badges de statut */
.status-badge {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Styles pour les boutons */
.btn:hover {
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .status-option-new {
        margin-bottom: 15px;
    }
    
    .modal-dialog {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
}
</style>

