<?php
// Récupération des utilisateurs
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des utilisateurs: " . $e->getMessage(), "error");
    $utilisateurs = [];
}

// Traitement du formulaire d'ajout de tâche
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et nettoyage des données
    $titre = cleanInput($_POST['titre']);
    $description = cleanInput($_POST['description']);
    $priorite = cleanInput($_POST['priorite']);
    $statut = cleanInput($_POST['statut']);
    $date_limite = cleanInput($_POST['date_limite']);
    $employe_id = isset($_POST['employe_id']) ? (int)$_POST['employe_id'] : null;
    
    // Validation des données
    $errors = [];
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire.";
    }
    
    if (empty($description)) {
        $errors[] = "La description est obligatoire.";
    }
    
    if (empty($priorite)) {
        $errors[] = "La priorité est obligatoire.";
    }
    
    if (empty($statut)) {
        $errors[] = "Le statut est obligatoire.";
    }
    
    // Validation des fichiers uploadés
    $uploadedFiles = [];
    if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'xlsx', 'xls', 'zip', 'rar'];
        
        for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['attachments']['name'][$i];
                $fileSize = $_FILES['attachments']['size'][$i];
                $fileTmpName = $_FILES['attachments']['tmp_name'][$i];
                $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Vérifier la taille du fichier
                if ($fileSize > $maxFileSize) {
                    $errors[] = "Le fichier '{$fileName}' est trop volumineux (max 10MB).";
                    continue;
                }
                
                // Vérifier le type de fichier
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = "Le type de fichier '{$fileType}' n'est pas autorisé pour '{$fileName}'.";
                    continue;
                }
                
                $uploadedFiles[] = [
                    'name' => $fileName,
                    'tmp_name' => $fileTmpName,
                    'size' => $fileSize,
                    'type' => $fileType
                ];
            } elseif ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Erreur lors de l'upload du fichier: " . $_FILES['attachments']['name'][$i];
            }
        }
    }
    
    // Si pas d'erreurs, insertion de la tâche
    if (empty($errors)) {
        try {
            $shop_pdo = getShopDBConnection();
            
            // Commencer une transaction
            $shop_pdo->beginTransaction();
            
            // Insérer la tâche
            $stmt = $shop_pdo->prepare("
                INSERT INTO taches (titre, description, priorite, statut, date_limite, employe_id, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $titre, 
                $description, 
                $priorite, 
                $statut, 
                $date_limite ?: null, 
                $employe_id ?: null,
                $_SESSION['user_id']
            ]);
            
            $tacheId = $shop_pdo->lastInsertId();
            
            // Traiter les fichiers uploadés
            if (!empty($uploadedFiles)) {
                // Créer le dossier d'upload s'il n'existe pas
                $uploadDir = 'uploads/taches/' . $tacheId . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach ($uploadedFiles as $file) {
                    // Générer un nom de fichier unique
                    $uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
                    $filePath = $uploadDir . $uniqueName;
                    
                    // Déplacer le fichier
                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        // Déterminer si c'est une image
                        $isImage = in_array($file['type'], ['jpg', 'jpeg', 'png', 'gif']) ? 1 : 0;
                        
                        // Insérer dans la base de données
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO tache_attachments 
                            (tache_id, file_path, file_name, file_type, file_size, est_image, uploaded_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $tacheId,
                            $filePath,
                            $file['name'],
                            $file['type'],
                            $file['size'],
                            $isImage,
                            $_SESSION['user_id']
                        ]);
                    }
                }
            }
            
            // Confirmer la transaction
            $shop_pdo->commit();
            
            set_message("Tâche ajoutée avec succès" . (!empty($uploadedFiles) ? " avec " . count($uploadedFiles) . " pièce(s) jointe(s)" : "") . "!", "success");
            // Si affiché dans un modal, fermer le modal côté parent et ne pas rediriger
            if (isset($_GET['modal']) && $_GET['modal'] == '1') {
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
                echo '<script>
                    try {
                        var parentDoc = window.parent && window.parent.document ? window.parent.document : null;
                        if (parentDoc && window.parent.bootstrap) {
                            var modalEl = parentDoc.getElementById("ajouterTacheModal");
                            if (modalEl) {
                                var modal = window.parent.bootstrap.Modal.getInstance(modalEl) || new window.parent.bootstrap.Modal(modalEl);
                                modal.hide();
                            }
                        }
                        if (window.parent && window.parent.toastr) {
                            window.parent.toastr.success("Tâche ajoutée avec succès");
                        }
                        // Optionnel: notifier le parent pour rafraîchir la liste si besoin
                        if (window.parent) {
                            window.parent.dispatchEvent(new CustomEvent("tache:added", { detail: { success: true } }));
                        }
                    } catch(e) { /* noop */ }
                </script>';
                echo '</body></html>';
                exit;
            }
            redirect("accueil");
        } catch (PDOException $e) {
            $shop_pdo->rollBack();
            $errors[] = "Erreur lors de l'ajout de la tâche: " . $e->getMessage();
        }
    }
}
?>

<?php if (isset($_GET['modal']) && $_GET['modal'] == '1'): ?>
<style>
/* Reset de base quand affiché dans un iframe modal sans header/footer */
body{background:#fff;}
.container-fluid{padding:16px !important;}
.card{border-radius:12px;border:1px solid #e9ecef;box-shadow:0 4px 12px rgba(0,0,0,0.05);} 
.form-control,.input-group-text{border-radius:10px}
.btn{border-radius:10px}
.page-title{font-size:1.25rem;font-weight:600;margin:0}
/* Masquer l'entête locale (titre + bouton retour) dans le modal */
.modal & .page-title, .modal & a.btn.btn-outline-secondary { display: none !important; }
/* Variante robuste: cacher la ligne qui contient le titre et le bouton retour */
.modal & .d-flex.justify-content-between.align-items-center.mb-3.w-100.px-3 { display: none !important; }
/* Masquer le dock/bottom menu si injecté par CSS/app */
#mobile-dock, .mobile-dock-container, .dock-item-center { display: none !important; visibility: hidden !important; }
/* Supprimer le padding bas laissé par le dock */
body { padding-bottom: 0 !important; }
</style>
<?php endif; ?>

<!-- CSS amélioré pour la page ajouter tâche - Design dual -->
<link rel="stylesheet" href="<?php echo $assets_path ?? 'assets/'; ?>css/ajouter-tache-enhanced.css">

<div class="container-fluid p-0">
    <div class="row justify-content-center g-0">
        <div class="col-12 col-lg-10 col-xl-8 px-0" style="display: flex; flex-direction: column; align-items: center;">
            <?php if (!isset($_GET['modal']) || $_GET['modal'] != '1'): ?>
            <div class="d-flex justify-content-between align-items-center mb-3 w-100 px-3">
                <h1 class="page-title">Nouvelle Tâche</h1>
                <a href="index.php?page=taches" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
            <?php endif; ?>
            
            <div class="card mb-4" style="width: 96%; max-width: 900px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-radius: 15px; margin: 0 auto;">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=ajouter_tache<?php echo (isset($_GET['modal']) && $_GET['modal']=='1') ? '&modal=1' : ''; ?>" id="taskForm" enctype="multipart/form-data">
                        <?php if (isset($_GET['modal']) && $_GET['modal']=='1'): ?>
                        <input type="hidden" name="modal" value="1">
                        <?php endif; ?>
                        <input type="hidden" name="priorite" id="priorite" value="<?php echo isset($_POST['priorite']) ? htmlspecialchars($_POST['priorite']) : ''; ?>">
                        <input type="hidden" name="statut" id="statut" value="<?php echo isset($_POST['statut']) ? htmlspecialchars($_POST['statut']) : ''; ?>">
                        <input type="hidden" name="employe_id" id="employe_id" value="<?php echo isset($_POST['employe_id']) ? htmlspecialchars($_POST['employe_id']) : ''; ?>">
                        
                        <!-- Titre de la tâche -->
                        <div class="mb-4">
                            <label for="titre" class="form-label fw-bold">Titre de la tâche *</label>
                            <input type="text" class="form-control form-control-lg" id="titre" name="titre" required
                                value="<?php echo isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : ''; ?>"
                                placeholder="Saisissez un titre clair et concis">
                        </div>
                        
                        <!-- Description de la tâche -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required
                                placeholder="Détaillez la tâche à accomplir..."><?php 
                                echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                            ?></textarea>
                        </div>
                        
                        <!-- Priorité avec boutons -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-bold d-block">Priorité *</label>
                                    <div class="priority-buttons d-flex flex-nowrap">
                                        <button type="button" class="btn btn-priority btn-outline-success flex-grow-1" data-value="basse">
                                            <i class="fas fa-angle-down me-1"></i><span class="d-none d-md-inline">Basse</span>
                                        </button>
                                        <button type="button" class="btn btn-priority btn-outline-primary flex-grow-1" data-value="moyenne">
                                            <i class="fas fa-equals me-1"></i><span class="d-none d-md-inline">Moyenne</span>
                                        </button>
                                        <button type="button" class="btn btn-priority btn-outline-warning flex-grow-1" data-value="haute">
                                            <i class="fas fa-angle-up me-1"></i><span class="d-none d-md-inline">Haute</span>
                                        </button>
                                        <button type="button" class="btn btn-priority btn-outline-danger flex-grow-1" data-value="urgente">
                                            <i class="fas fa-exclamation-triangle me-1"></i><span class="d-none d-md-inline">Urgente</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6 mt-3 mt-md-0">
                                    <!-- Statut avec boutons -->
                                    <label class="form-label fw-bold d-block">Statut *</label>
                                    <div class="status-buttons d-flex flex-nowrap">
                                        <button type="button" class="btn btn-status btn-outline-secondary flex-grow-1" data-value="a_faire">
                                            <i class="far fa-circle me-1"></i><span class="d-none d-md-inline">À faire</span>
                                        </button>
                                        <button type="button" class="btn btn-status btn-outline-info flex-grow-1" data-value="en_cours">
                                            <i class="fas fa-spinner me-1"></i><span class="d-none d-md-inline">En cours</span>
                                        </button>
                                        <button type="button" class="btn btn-status btn-outline-success flex-grow-1" data-value="termine">
                                            <i class="fas fa-check me-1"></i><span class="d-none d-md-inline">Terminé</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Date limite -->
                        <div class="mb-4">
                            <label for="date_limite" class="form-label fw-bold">Date limite</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control form-control-lg" id="date_limite" name="date_limite"
                                    value="<?php echo isset($_POST['date_limite']) ? htmlspecialchars($_POST['date_limite']) : ''; ?>">
                            </div>
                        </div>
                        
                        <!-- Assigner la tâche -->
                        <div class="mb-4">
                            <label class="form-label fw-bold d-block">Assigner à</label>
                            <div class="user-selection">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-secondary btn-lg user-btn" data-value="">
                                        <i class="fas fa-user-slash me-2"></i>Non assigné
                                    </button>
                                    
                                    <?php foreach ($utilisateurs as $index => $utilisateur): ?>
                                        <?php if ($index < 3): ?>
                                            <button type="button" class="btn btn-outline-primary btn-lg user-btn" 
                                                    data-value="<?php echo $utilisateur['id']; ?>">
                                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($utilisateur['full_name']); ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($utilisateurs) > 3): ?>
                                        <button type="button" class="btn btn-outline-secondary btn-lg" id="showAllUsersBtn">
                                            <i class="fas fa-users me-2"></i>Voir tous
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div id="allUsersList" class="mt-3" style="display: none;">
                                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                        <?php foreach ($utilisateurs as $utilisateur): ?>
                                            <div class="col">
                                                <button type="button" class="btn btn-outline-primary w-100 text-start user-btn py-2" 
                                                        data-value="<?php echo $utilisateur['id']; ?>">
                                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($utilisateur['full_name']); ?>
                                                    <small class="d-block text-muted ms-4"><?php echo ucfirst($utilisateur['role']); ?></small>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pièces jointes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold d-block">
                                <i class="fas fa-paperclip me-2"></i>Pièces jointes <small class="text-muted">(facultatif)</small>
                            </label>
                            <div class="attachment-section">
                                <div class="file-drop-zone" id="fileDropZone">
                                    <div class="text-center py-4">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <p class="mb-2">Glissez-déposez vos fichiers ici ou</p>
                                        <button type="button" class="btn btn-outline-primary" id="selectFilesBtn">
                                            <i class="fas fa-folder-open me-2"></i>Sélectionner des fichiers
                                        </button>
                                        <input type="file" name="attachments[]" id="fileInput" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.xlsx,.xls,.zip,.rar" style="display: none;">
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Types autorisés: JPG, PNG, PDF, DOC, TXT, XLS, ZIP (max 10MB par fichier)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="selectedFiles" class="mt-3" style="display: none;">
                                    <h6 class="fw-bold mb-2">Fichiers sélectionnés :</h6>
                                    <div id="filesList" class="list-group">
                                        <!-- Les fichiers sélectionnés apparaîtront ici -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-5">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Enregistrer la tâche
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles généraux */
body {
    background-color: #f8f9fa;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.form-control, .input-group-text {
    border-radius: 10px;
}

.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Styles pour les boutons de priorité et statut */
.priority-buttons .btn, .status-buttons .btn {
    border-width: 2px;
    transition: all 0.2s;
    margin: 0;
    border-radius: 0;
    padding: 0.5rem 0.25rem;
    font-size: 0.9rem;
}

.priority-buttons .btn:first-child, .status-buttons .btn:first-child {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

.priority-buttons .btn:last-child, .status-buttons .btn:last-child {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

.user-btn {
    border-width: 2px;
    transition: all 0.2s;
}

.btn-priority.active, .btn-status.active, .user-btn.active {
    transform: translateY(-2px);
    font-weight: 500;
}

.btn-priority[data-value="basse"].active {
    background-color: #198754;
    color: white;
    border-color: #198754;
}

.btn-priority[data-value="moyenne"].active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.btn-priority[data-value="haute"].active {
    background-color: #ffc107;
    color: #212529;
    border-color: #ffc107;
}

.btn-priority[data-value="urgente"].active {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

.btn-status[data-value="a_faire"].active {
    background-color: #6c757d;
    color: white;
    border-color: #6c757d;
}

.btn-status[data-value="en_cours"].active {
    background-color: #0dcaf0;
    color: white;
    border-color: #0dcaf0;
}

.btn-status[data-value="termine"].active {
    background-color: #198754;
    color: white;
    border-color: #198754;
}

/* Responsive designs */
@media (max-width: 992px) {
    .card {
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .btn {
        font-size: 1rem;
        padding: 10px 15px;
    }
    
    .form-control, .form-select {
        font-size: 16px; /* Évite le zoom sur mobile */
        height: auto;
        padding: 12px 15px;
    }
    
    .priority-buttons .btn, .status-buttons .btn {
        padding: 0.75rem 0.5rem;
    }
}

@media (max-width: 768px) {
    .priority-buttons, .status-buttons {
        width: 100%;
    }
    
    .priority-buttons .btn, .status-buttons .btn {
        padding: 0.75rem 0.25rem;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.3rem;
    }
    
    .card-body {
        padding: 15px;
    }
    
    .user-selection .btn {
        width: 100%;
        margin-bottom: 8px;
        text-align: left;
    }
    
    .btn-lg {
        font-size: 1rem;
        padding: 10px 15px;
    }
}

/* Styles pour les pièces jointes */
.attachment-section {
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    padding: 20px;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.file-drop-zone {
    border: 2px dashed #ced4da;
    border-radius: 8px;
    background-color: #ffffff;
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-drop-zone:hover,
.file-drop-zone.dragover {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
    transform: translateY(-2px);
}

.file-drop-zone.dragover {
    border-style: solid;
    box-shadow: 0 0 20px rgba(13, 110, 253, 0.2);
}

.file-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background-color: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: all 0.2s ease;
}

.file-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.file-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    margin-right: 12px;
    font-size: 1.2em;
}

.file-icon.image {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.file-icon.document {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.file-icon.archive {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.file-icon.other {
    background-color: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.file-info {
    flex: 1;
}

.file-name {
    font-weight: 500;
    color: #212529;
    margin-bottom: 2px;
}

.file-size {
    font-size: 0.85em;
    color: #6c757d;
}

.file-remove {
    background: none;
    border: none;
    color: #dc3545;
    font-size: 1.1em;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.file-remove:hover {
    background-color: rgba(220, 53, 69, 0.1);
    transform: scale(1.1);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(30px); }
    to { opacity: 1; transform: translateX(0); }
}

#allUsersList {
    animation: fadeIn 0.3s ease-out;
}

.file-item {
    animation: slideInRight 0.3s ease-out;
}

/* Styles pour le mode nuit */
.dark-mode {
    background-color: #111827;
}

.dark-mode .page-title {
    color: #f8fafc;
}

.dark-mode .card {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.dark-mode .card-body {
    background-color: #1f2937;
    color: #f8fafc;
}

.dark-mode .form-control,
.dark-mode .form-select,
.dark-mode .input-group-text {
    background-color: #111827;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .form-control:focus,
.dark-mode .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.dark-mode .form-label {
    color: #f8fafc;
}

.dark-mode .text-muted {
    color: #94a3b8 !important;
}

.dark-mode .alert-warning {
    background-color: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.3);
    color: #f8fafc;
}

.dark-mode .btn-priority,
.dark-mode .btn-status,
.dark-mode .user-btn {
    color: #f8fafc;
    border-color: #374151;
}

.dark-mode .btn-outline-primary {
    color: #60a5fa;
    border-color: #60a5fa;
}

.dark-mode .btn-outline-secondary {
    color: #94a3b8;
    border-color: #4b5563;
}

.dark-mode .btn-outline-success {
    color: #34d399;
    border-color: #34d399;
}

.dark-mode .btn-outline-warning {
    color: #fbbf24;
    border-color: #fbbf24;
}

.dark-mode .btn-outline-danger {
    color: #f87171;
    border-color: #f87171;
}

.dark-mode .btn-outline-info {
    color: #38bdf8;
    border-color: #38bdf8;
}

.dark-mode .btn-priority[data-value="basse"].active {
    background-color: #10b981;
    color: #f8fafc;
    border-color: #10b981;
}

.dark-mode .btn-priority[data-value="moyenne"].active {
    background-color: #3b82f6;
    color: #f8fafc;
    border-color: #3b82f6;
}

.dark-mode .btn-priority[data-value="haute"].active {
    background-color: #f59e0b;
    color: #111827;
    border-color: #f59e0b;
}

.dark-mode .btn-priority[data-value="urgente"].active {
    background-color: #ef4444;
    color: #f8fafc;
    border-color: #ef4444;
}

.dark-mode .btn-status[data-value="a_faire"].active {
    background-color: #4b5563;
    color: #f8fafc;
    border-color: #4b5563;
}

.dark-mode .btn-status[data-value="en_cours"].active {
    background-color: #0ea5e9;
    color: #f8fafc;
    border-color: #0ea5e9;
}

.dark-mode .btn-status[data-value="termine"].active {
    background-color: #10b981;
    color: #f8fafc;
    border-color: #10b981;
}

.dark-mode .btn-primary {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.dark-mode .btn-success {
    background-color: #10b981;
    border-color: #10b981;
}

.dark-mode .btn-lg {
    color: #f8fafc;
}

.dark-mode small.text-muted {
    color: #94a3b8 !important;
}

/* Styles pour les pièces jointes en mode sombre */
.dark-mode .attachment-section {
    border-color: #374151;
    background-color: #111827;
}

.dark-mode .file-drop-zone {
    border-color: #4b5563;
    background-color: #1f2937;
}

.dark-mode .file-drop-zone:hover,
.dark-mode .file-drop-zone.dragover {
    border-color: #3b82f6;
    background-color: rgba(59, 130, 246, 0.1);
}

.dark-mode .file-item {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode .file-item:hover {
    background-color: #111827;
}

.dark-mode .file-name {
    color: #f8fafc;
}

.dark-mode .file-size {
    color: #94a3b8;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Priorité
    const priorityButtons = document.querySelectorAll('.btn-priority');
    const priorityInput = document.getElementById('priorite');
    
    // Statut
    const statusButtons = document.querySelectorAll('.btn-status');
    const statusInput = document.getElementById('statut');
    
    // Utilisateurs
    const userButtons = document.querySelectorAll('.user-btn');
    const employeInput = document.getElementById('employe_id');
    const showAllUsersBtn = document.getElementById('showAllUsersBtn');
    const allUsersList = document.getElementById('allUsersList');
    
    // Activation des boutons de priorité
    priorityButtons.forEach(button => {
        // Préselectionner une valeur si elle existe déjà
        if (priorityInput.value === button.dataset.value) {
            button.classList.add('active');
        }
        
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            priorityButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur du champ caché
            priorityInput.value = this.dataset.value;
            
            // Effet visuel de feedback
            this.classList.add('pulse');
            setTimeout(() => {
                this.classList.remove('pulse');
            }, 500);
        });
    });
    
    // Activation des boutons de statut
    statusButtons.forEach(button => {
        // Préselectionner une valeur si elle existe déjà
        if (statusInput.value === button.dataset.value) {
            button.classList.add('active');
        }
        
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            statusButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur du champ caché
            statusInput.value = this.dataset.value;
            
            // Effet visuel de feedback
            this.classList.add('pulse');
            setTimeout(() => {
                this.classList.remove('pulse');
            }, 500);
        });
    });
    
    // Activation des boutons d'utilisateurs
    userButtons.forEach(button => {
        // Préselectionner une valeur si elle existe déjà
        if (employeInput.value === button.dataset.value) {
            button.classList.add('active');
        }
        
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            userButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur du champ caché
            employeInput.value = this.dataset.value;
            
            // Effet visuel de feedback
            this.classList.add('pulse');
            setTimeout(() => {
                this.classList.remove('pulse');
            }, 500);
        });
    });
    
    // Afficher/masquer la liste complète des utilisateurs
    if (showAllUsersBtn) {
        showAllUsersBtn.addEventListener('click', function() {
            if (allUsersList.style.display === 'none') {
                allUsersList.style.display = 'block';
                this.innerHTML = '<i class="fas fa-users-slash me-2"></i>Masquer';
            } else {
                allUsersList.style.display = 'none';
                this.innerHTML = '<i class="fas fa-users me-2"></i>Voir tous';
            }
        });
    }
    
    // Validation du formulaire
    document.getElementById('taskForm').addEventListener('submit', function(e) {
        if (!priorityInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner une priorité pour la tâche');
            return;
        }
        
        if (!statusInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un statut pour la tâche');
            return;
        }
    });
    
    // Définir des valeurs par défaut si aucune n'est sélectionnée
    if (!priorityInput.value && priorityButtons.length > 0) {
        // Sélectionner 'moyenne' par défaut
        const defaultPriority = document.querySelector('.btn-priority[data-value="moyenne"]');
        if (defaultPriority) {
            defaultPriority.click();
        } else {
            priorityButtons[0].click();
        }
    }
    
    if (!statusInput.value && statusButtons.length > 0) {
        // Sélectionner 'a_faire' par défaut
        const defaultStatus = document.querySelector('.btn-status[data-value="a_faire"]');
        if (defaultStatus) {
            defaultStatus.click();
        } else {
            statusButtons[0].click();
        }
    }
    
    // Ajouter du feedback tactile pour les appareils mobiles
    const allButtons = document.querySelectorAll('.btn');
    
    function addTouchFeedback(buttons) {
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });
            
            button.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    }
    
    addTouchFeedback(allButtons);
    
    // Gestion des pièces jointes
    const fileInput = document.getElementById('fileInput');
    const fileDropZone = document.getElementById('fileDropZone');
    const selectFilesBtn = document.getElementById('selectFilesBtn');
    const selectedFiles = document.getElementById('selectedFiles');
    const filesList = document.getElementById('filesList');
    let filesArray = [];
    
    // Fonction pour formater la taille des fichiers
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Fonction pour obtenir l'icône et la classe selon le type de fichier
    function getFileIcon(fileType) {
        const imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
        const documentTypes = ['pdf', 'doc', 'docx', 'txt'];
        const archiveTypes = ['zip', 'rar'];
        
        if (imageTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-image', class: 'image' };
        } else if (documentTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-file-alt', class: 'document' };
        } else if (archiveTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-file-archive', class: 'archive' };
        } else {
            return { icon: 'fas fa-file', class: 'other' };
        }
    }
    
    // Fonction pour valider un fichier
    function validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'xlsx', 'xls', 'zip', 'rar'];
        const fileType = file.name.split('.').pop().toLowerCase();
        
        if (file.size > maxSize) {
            return { valid: false, error: `Le fichier "${file.name}" est trop volumineux (max 10MB)` };
        }
        
        if (!allowedTypes.includes(fileType)) {
            return { valid: false, error: `Le type de fichier "${fileType}" n'est pas autorisé pour "${file.name}"` };
        }
        
        return { valid: true };
    }
    
    // Fonction pour afficher les fichiers sélectionnés
    function displayFiles() {
        if (filesArray.length === 0) {
            selectedFiles.style.display = 'none';
            return;
        }
        
        selectedFiles.style.display = 'block';
        filesList.innerHTML = '';
        
        filesArray.forEach((file, index) => {
            const fileIcon = getFileIcon(file.name.split('.').pop());
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <div class="file-icon ${fileIcon.class}">
                    <i class="${fileIcon.icon}"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${formatFileSize(file.size)}</div>
                </div>
                <button type="button" class="file-remove" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            filesList.appendChild(fileItem);
        });
        
        // Ajouter les événements de suppression
        document.querySelectorAll('.file-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                filesArray.splice(index, 1);
                updateFileInput();
                displayFiles();
            });
        });
    }
    
    // Fonction pour mettre à jour l'input file
    function updateFileInput() {
        const dt = new DataTransfer();
        filesArray.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    // Fonction pour ajouter des fichiers
    function addFiles(files) {
        const newFiles = Array.from(files);
        let hasErrors = false;
        
        newFiles.forEach(file => {
            const validation = validateFile(file);
            if (!validation.valid) {
                alert(validation.error);
                hasErrors = true;
                return;
            }
            
            // Vérifier si le fichier n'est pas déjà ajouté
            const exists = filesArray.some(existingFile => 
                existingFile.name === file.name && existingFile.size === file.size
            );
            
            if (!exists) {
                filesArray.push(file);
            }
        });
        
        if (!hasErrors) {
            updateFileInput();
            displayFiles();
        }
    }
    
    // Événement pour le bouton de sélection
    selectFilesBtn.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Événement pour le changement de fichier
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            addFiles(this.files);
        }
    });
    
    // Événements de drag & drop
    fileDropZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileDropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    fileDropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    fileDropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            addFiles(files);
        }
    });
});
</script> 