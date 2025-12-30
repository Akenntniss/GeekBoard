<!-- MODAL: AJOUTER TÂCHE (REVAMPED) -->
<div class="modal fade" id="ajouterTacheModal" tabindex="-1" aria-labelledby="ajouterTacheModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content task-modal-light">
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="ajouterTacheModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nouvelle Tâche
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <form id="taskForm" enctype="multipart/form-data">
                    <!-- Titre -->
                    <div class="task-form-group">
                        <label for="taskTitle" class="task-label">Titre de la tâche</label>
                        <input type="text" class="task-input" id="taskTitle" name="titre" placeholder="Ex: Réparation iPhone 12..." required>
                    </div>

                    <!-- Description -->
                    <div class="task-form-group">
                        <label for="taskDescription" class="task-label">Description détaillée</label>
                        <textarea class="task-textarea" id="taskDescription" name="description" rows="4" placeholder="Détails de la tâche à effectuer..." required></textarea>
                    </div>

                    <div class="row">
                        <!-- Priorité -->
                        <div class="col-md-6">
                            <div class="task-form-group">
                                <label class="task-label">Priorité</label>
                                <input type="hidden" id="taskPriority" name="priorite" value="moyenne">
                                <div class="priority-selector">
                                    <button type="button" class="priority-btn" data-value="basse" onclick="setTaskPriority('basse')">Basse</button>
                                    <button type="button" class="priority-btn active" data-value="moyenne" onclick="setTaskPriority('moyenne')">Moyenne</button>
                                    <button type="button" class="priority-btn" data-value="haute" onclick="setTaskPriority('haute')">Haute</button>
                                    <button type="button" class="priority-btn" data-value="urgente" onclick="setTaskPriority('urgente')">Urgente</button>
                                </div>
                            </div>
                        </div>

                        <!-- Date Limite -->
                        <div class="col-md-6">
                            <div class="task-form-group">
                                <label for="taskDeadline" class="task-label">Date Limite</label>
                                <input type="datetime-local" class="task-input" id="taskDeadline" name="date_limite">
                            </div>
                        </div>
                    </div>

                    <!-- Assigné à -->
                    <div class="task-form-group">
                        <label for="taskAssignee" class="task-label">Assigné à</label>
                        <select class="task-select" id="taskAssignee" name="employe_id">
                            <option value="">-- Sélectionner un employé --</option>
                            <?php
                            // Charger les utilisateurs depuis la table users via getShopDBConnection
                            try {
                                $shop_db = getShopDBConnection();
                                $stmt_users = $shop_db->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
                                $modal_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($modal_users as $user) {
                                    echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['full_name']) . ' (' . $user['role'] . ')</option>';
                                }
                            } catch (Exception $e) {
                                // Fallback silencieux
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Pièces Jointes -->
                    <div class="task-form-group">
                        <label class="task-label">Pièces Jointes</label>
                        <div class="file-upload-zone" id="taskDropZone">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <p class="mb-1">Glissez-déposez vos fichiers ici</p>
                            <p class="text-muted small">ou cliquez pour parcourir</p>
                            <input type="file" id="taskAttachments" name="attachments[]" multiple style="display: none;">
                        </div>
                        <div id="taskFileList" class="mt-3"></div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn-task-cancel" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn-task-save" id="btnSaveTask">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
