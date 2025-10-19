<?php
// Connexion à la base de données du magasin
$pdo = getShopDBConnection();

if (!$pdo) {
    echo "<div style='text-align: center; padding: 50px; color: #e74c3c;'>
            <h2>Erreur de connexion à la base de données</h2>
            <p>Impossible de se connecter à la base de données du magasin.</p>
          </div>";
    exit;
}

// Récupération des employés avec leurs statistiques
try {
    // Vérifier d'abord quelles tables existent
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $has_reparation_attributions = in_array('reparation_attributions', $tables);
    $has_time_tracking = in_array('time_tracking', $tables);
    
    // Base query pour les utilisateurs
    $base_query = "
        SELECT u.*, 
               0 as total_reparations,
               0 as reparations_30j,
               0 as heures_travaillees,
               0 as total_pointages,
               0 as en_cours_travail,
               NULL as derniere_connexion
        FROM users u 
        WHERE u.role IN ('admin', 'technicien')
    ";
    
    // Vérifier si la table reparation_logs existe (meilleure source de données)
    $has_reparation_logs = in_array('reparation_logs', $tables);
    
    // Si on a les tables nécessaires, on peut récupérer les vraies statistiques
    if ($has_reparation_logs && $has_time_tracking) {
        // Utiliser les logs de réparation pour un comptage précis
        $stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE(COUNT(DISTINCT CASE 
                       WHEN rl.action_type = 'changement_statut' 
                       AND (rl.statut_apres LIKE '%effectue%' 
                            OR rl.statut_apres LIKE '%annule%' 
                            OR rl.statut_apres LIKE '%termine%'
                            OR rl.statut_apres LIKE '%fini%') 
                       THEN rl.reparation_id 
                   END), 0) as total_reparations,
                   COALESCE(COUNT(DISTINCT CASE 
                       WHEN rl.action_type = 'changement_statut' 
                       AND (rl.statut_apres LIKE '%effectue%' 
                            OR rl.statut_apres LIKE '%annule%' 
                            OR rl.statut_apres LIKE '%termine%'
                            OR rl.statut_apres LIKE '%fini%')
                       AND rl.date_action >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                       THEN rl.reparation_id 
                   END), 0) as reparations_30j,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' THEN tt.work_duration ELSE 0 END), 0) as heures_travaillees,
                   COALESCE(COUNT(DISTINCT tt.id), 0) as total_pointages,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE(tt.clock_in) = CURDATE() AND tt.clock_out IS NULL THEN tt.id END), 0) as en_cours_travail,
                   MAX(tt.clock_in) as derniere_connexion
            FROM users u 
            LEFT JOIN reparation_logs rl ON u.id = rl.employe_id 
            LEFT JOIN time_tracking tt ON u.id = tt.user_id
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else if ($has_reparation_logs) {
        // Utiliser uniquement les logs de réparation
        $stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE(COUNT(DISTINCT CASE 
                       WHEN rl.action_type = 'changement_statut' 
                       AND (rl.statut_apres LIKE '%effectue%' 
                            OR rl.statut_apres LIKE '%annule%' 
                            OR rl.statut_apres LIKE '%termine%'
                            OR rl.statut_apres LIKE '%fini%') 
                       THEN rl.reparation_id 
                   END), 0) as total_reparations,
                   COALESCE(COUNT(DISTINCT CASE 
                       WHEN rl.action_type = 'changement_statut' 
                       AND (rl.statut_apres LIKE '%effectue%' 
                            OR rl.statut_apres LIKE '%annule%' 
                            OR rl.statut_apres LIKE '%termine%'
                            OR rl.statut_apres LIKE '%fini%')
                       AND rl.date_action >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                       THEN rl.reparation_id 
                   END), 0) as reparations_30j,
                   0 as heures_travaillees,
                   0 as total_pointages,
                   0 as en_cours_travail,
                   NULL as derniere_connexion
            FROM users u 
            LEFT JOIN reparation_logs rl ON u.id = rl.employe_id 
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else if ($has_reparation_attributions && $has_time_tracking) {
        // Fallback sur l'ancienne méthode si les logs n'existent pas
        $stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE(COUNT(DISTINCT ra.reparation_id), 0) as total_reparations,
                   COALESCE(COUNT(DISTINCT CASE WHEN ra.date_debut >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN ra.reparation_id END), 0) as reparations_30j,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' THEN tt.work_duration ELSE 0 END), 0) as heures_travaillees,
                   COALESCE(COUNT(DISTINCT tt.id), 0) as total_pointages,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE(tt.clock_in) = CURDATE() AND tt.clock_out IS NULL THEN tt.id END), 0) as en_cours_travail,
                   MAX(tt.clock_in) as derniere_connexion
            FROM users u 
            LEFT JOIN reparation_attributions ra ON u.id = ra.employe_id 
            LEFT JOIN time_tracking tt ON u.id = tt.user_id
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else if ($has_reparation_attributions) {
        $stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE(COUNT(DISTINCT ra.reparation_id), 0) as total_reparations,
                   COALESCE(COUNT(DISTINCT CASE WHEN ra.date_debut >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN ra.reparation_id END), 0) as reparations_30j,
                   0 as heures_travaillees,
                   0 as total_pointages,
                   0 as en_cours_travail,
                   NULL as derniere_connexion
            FROM users u 
            LEFT JOIN reparation_attributions ra ON u.id = ra.employe_id 
            WHERE u.role IN ('admin', 'technicien')
            GROUP BY u.id 
            ORDER BY u.full_name ASC
        ");
    } else if ($has_time_tracking) {
        $stmt = $pdo->query("
            SELECT u.*, 
                   0 as total_reparations,
                   0 as reparations_30j,
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' THEN tt.work_duration ELSE 0 END), 0) as heures_travaillees,
                   COALESCE(COUNT(DISTINCT tt.id), 0) as total_pointages,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE(tt.clock_in) = CURDATE() AND tt.clock_out IS NULL THEN tt.id END), 0) as en_cours_travail,
                   MAX(tt.clock_in) as derniere_connexion
            FROM users u 
            LEFT JOIN time_tracking tt ON u.id = tt.user_id
            WHERE u.role IN ('admin', 'technicien')
        GROUP BY u.id 
        ORDER BY u.full_name ASC
    ");
    } else {
        // Aucune table de statistiques disponible
        $stmt = $pdo->query($base_query . " ORDER BY u.full_name ASC");
    }
    
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div style='color: #e74c3c; text-align: center; padding: 20px;'>
            Erreur lors de la récupération des employés : " . htmlspecialchars($e->getMessage()) . "
          </div>";
    $employees = [];
}

// Traitement de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Vérifier si l'utilisateur a des réparations associées
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reparation_attributions WHERE employe_id = ?");
        $stmt->execute([$id]);
        $has_repairs = $stmt->fetchColumn() > 0;
        
        if ($has_repairs) {
            echo "<script>
                    alert('Impossible de supprimer cet employé car il a des réparations associées.');
                    window.location.href = 'index.php?page=employes';
                  </script>";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo "<script>
                    alert('Employé supprimé avec succès!');
                    window.location.href = 'index.php?page=employes';
                  </script>";
        }
    } catch (PDOException $e) {
        echo "<script>
                alert('Erreur lors de la suppression : " . addslashes($e->getMessage()) . "');
                window.location.href = 'index.php?page=employes';
              </script>";
    }
}

// Traitement de l'ajout d'employé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $nom = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (!empty($nom) && !empty($username) && !empty($password)) {
        try {
            // Vérifier si le nom d'utilisateur existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetchColumn() > 0) {
                echo "<script>alert('Ce nom d\\'utilisateur existe déjà!');</script>";
            } else {
                // Hasher le mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer le nouvel employé
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, full_name, role, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$username, $hashed_password, $nom, $role]);
                
                echo "<script>
                        alert('Employé ajouté avec succès!');
                        window.location.href = 'index.php?page=employes';
                      </script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Erreur lors de l\\'ajout : " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Veuillez remplir tous les champs obligatoires!');</script>";
    }
}
?>

<style></style>

<?php
// Filtres simples
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
if ($q !== '' || ($role_filter === 'admin' || $role_filter === 'technicien')) {
    $employees = array_values(array_filter($employees, function($emp) use ($q, $role_filter) {
        if ($q !== '' && stripos(($emp['full_name'] ?? '') . ' ' . ($emp['username'] ?? ''), $q) === false) { return false; }
        if ($role_filter !== '' && ($emp['role'] ?? '') !== $role_filter) { return false; }
        return true;
    }));
}
?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h3 mb-0">Gestion des utilisateurs</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-user-plus me-2"></i>Nouvel utilisateur
        </button>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="employes">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-primary"></i></span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Rechercher par nom ou identifiant" value="<?php echo htmlspecialchars($q); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">Tous les rôles</option>
                        <option value="technicien" <?php echo $role_filter==='technicien'?'selected':''; ?>>Technicien</option>
                        <option value="admin" <?php echo $role_filter==='admin'?'selected':''; ?>>Administrateur</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit">Rechercher</button>
                </div>
            </form>
        </div>
</div>

    <?php if (empty($employees)): ?>
        <div class="text-center py-5">
            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
            <h5 class="mb-1">Aucun utilisateur trouvé</h5>
            <p class="text-muted mb-3">Ajoutez votre premier utilisateur pour commencer.</p>
            <a href="index.php?page=ajouter_employe" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Ajouter</a>
        </div>
                    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($employees as $employee): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                                    <span class="fw-bold"><?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?></span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                                    <div class="text-muted small">@<?php echo htmlspecialchars($employee['username']); ?></div>
                        </div>
                                <span class="badge <?php echo ($employee['role']==='admin')?'bg-danger':'bg-info'; ?> ms-auto">
                                <?php echo ucfirst($employee['role']); ?>
                                    </span>
                        </div>

                            <div class="row text-center g-2 mb-3">
                                <div class="col-6">
                                    <div class="border rounded py-2">
                                        <div class="fw-bold"><?php echo (int)$employee['total_reparations']; ?></div>
                                        <div class="text-muted small">Réparations</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded py-2">
                                        <div class="fw-bold"><?php echo (int)$employee['reparations_30j']; ?></div>
                                        <div class="text-muted small">30 derniers jours</div>
                    </div>
                    </div>
                                <div class="col-6">
                                    <div class="border rounded py-2">
                                        <div class="fw-bold"><?php echo round($employee['heures_travaillees'], 1); ?>h</div>
                                        <div class="text-muted small">Heures</div>
                        </div>
                        </div>
                                <div class="col-6">
                                    <div class="border rounded py-2">
                                        <div class="fw-bold"><?php echo (int)$employee['total_pointages']; ?></div>
                                        <div class="text-muted small">Pointages</div>
                        </div>
                        </div>
                    </div>

                            <div class="mt-auto d-flex gap-2">
                                <button type="button" data-user-id="<?php echo $employee['id']; ?>" class="btn btn-outline-primary btn-sm w-100 edit-user-btn"><i class="fas fa-edit me-1"></i>Modifier</button>
                        <?php if ($employee['username'] !== 'admin'): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="confirmDelete(<?php echo $employee['id']; ?>)"><i class="fas fa-trash me-1"></i>Supprimer</button>
                                        <?php endif; ?>
                            </div>
                        </div>
                                    </div>
                    <!-- Template formulaire d'édition (pré-rempli) -->
                    <div id="tmpl-edit-user-<?php echo $employee['id']; ?>" class="d-none">
                        <div id="editEmployeeErrors" class="alert alert-danger d-none"></div>
                        <form id="editEmployeeForm">
                            <input type="hidden" name="id" value="<?php echo (int)$employee['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($employee['username']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nouveau mot de passe (laisser vide si inchangé)</label>
                                <input type="password" class="form-control" name="password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars($employee['full_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rôle *</label>
                                <select class="form-select" name="role" required>
                                    <option value="technicien" <?php echo $employee['role']==='technicien'?'selected':''; ?>>Technicien</option>
                                    <option value="admin" <?php echo $employee['role']==='admin'?'selected':''; ?>>Administrateur</option>
                                </select>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Enregistrer</button>
                            </div>
                        </form>
                                    </div>
                </div>
                        <?php endforeach; ?>
        </div>
                    <?php endif; ?>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        window.location.href = 'index.php?page=employes&delete=' + id;
    }
}

// Modal d'édition utilisateur (chargement AJAX)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.edit-user-btn');
    if (!btn) return;
    const userId = btn.getAttribute('data-user-id');
    const modalEl = document.getElementById('editEmployeeModal');
    const modalBody = document.getElementById('editEmployeeModalBody');
    modalBody.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Chargement...</div>';
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
    // Essayer d'utiliser le template embarqué d'abord (instantané)
    const tpl = document.getElementById('tmpl-edit-user-' + userId);
    if (tpl) {
        modalBody.innerHTML = tpl.innerHTML;
    } else {
        fetch('ajax/get_employe_form.php?id=' + encodeURIComponent(userId), { credentials: 'same-origin' })
            .then(r => r.text())
            .then(html => { modalBody.innerHTML = html; })
            .catch(() => { modalBody.innerHTML = '<div class="alert alert-danger">Erreur de chargement du formulaire.</div>'; });
    }
});

// Soumission AJAX du formulaire d'édition
document.addEventListener('submit', function(e) {
    const form = e.target.closest('#editEmployeeForm');
    if (!form) return;
    e.preventDefault();
    const formData = new FormData(form);
    fetch('ajax/update_employe.php', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(r => r.json())
        .then(res => {
            if (res && res.success) {
                if (typeof toastr !== 'undefined') { toastr.success('Utilisateur mis à jour.'); }
                const modalEl = document.getElementById('editEmployeeModal');
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) bsModal.hide();
                window.location.reload();
            } else {
                const msg = (res && res.message) ? res.message : 'Erreur inconnue.';
                if (typeof toastr !== 'undefined') { toastr.error(msg); }
                const errorBox = document.getElementById('editEmployeeErrors');
                if (errorBox) { errorBox.textContent = msg; errorBox.classList.remove('d-none'); }
            }
        })
        .catch(() => {
            if (typeof toastr !== 'undefined') { toastr.error('Erreur réseau.'); }
        });
});

// Soumission AJAX - ajout utilisateur
document.addEventListener('submit', function(e) {
    const form = e.target.closest('#addEmployeeForm');
    if (!form) return;
    e.preventDefault();
    const formData = new FormData(form);
    fetch('ajax/create_employe.php', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(r => r.json())
        .then(res => {
            if (res && res.success) {
                if (typeof toastr !== 'undefined') { toastr.success('Utilisateur créé.'); }
                const modalEl = document.getElementById('addEmployeeModal');
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) bsModal.hide();
                window.location.reload();
            } else {
                const msg = (res && res.message) ? res.message : 'Erreur inconnue.';
                if (typeof toastr !== 'undefined') { toastr.error(msg); }
                const errorBox = document.getElementById('addEmployeeErrors');
                if (errorBox) { errorBox.textContent = msg; errorBox.classList.remove('d-none'); }
            }
        })
        .catch(() => { if (typeof toastr !== 'undefined') { toastr.error('Erreur réseau.'); } });
});
</script> 

<!-- Modal édition utilisateur -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Modifier l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editEmployeeModalBody"></div>
        </div>
    </div>
                </div>
                
<!-- Modal ajout utilisateur -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nouveau utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addEmployeeErrors" class="alert alert-danger d-none"></div>
                <form id="addEmployeeForm">
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nom complet *</label>
                        <input type="text" class="form-control" name="full_name" required>
                </div>
                    <div class="mb-3">
                        <label class="form-label">Rôle *</label>
                        <select class="form-select" name="role" required>
                            <option value="technicien" selected>Technicien</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Enregistrer</button>
            </div>
        </form>
    </div>
        </div>
    </div>
</div>