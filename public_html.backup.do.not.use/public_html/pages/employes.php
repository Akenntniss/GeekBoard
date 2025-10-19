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
               0 as heures_mois,
               0 as pointages_mois,
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
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' AND DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.work_duration ELSE 0 END), 0) as heures_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.id END), 0) as pointages_mois,
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
                   0 as heures_mois,
                   0 as pointages_mois,
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
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' AND DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.work_duration ELSE 0 END), 0) as heures_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.id END), 0) as pointages_mois,
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
                   0 as heures_mois,
                   0 as pointages_mois,
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
                   COALESCE(SUM(CASE WHEN tt.status = 'completed' AND DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.work_duration ELSE 0 END), 0) as heures_mois,
                   COALESCE(COUNT(DISTINCT CASE WHEN DATE_FORMAT(tt.clock_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN tt.id END), 0) as pointages_mois,
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

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <!-- Loader Mode Sombre (par défaut) -->
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    
    <!-- Loader Mode Clair -->
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<div class="content-wrapper" id="mainContent" style="display: none;">
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
                                        <div class="fw-bold"><?php echo round($employee['heures_mois'] ?? 0, 1); ?>h</div>
                                        <div class="text-muted small">Heures (mois)</div>
                        </div>
                        </div>
                                <div class="col-6">
                                    <div class="border rounded py-2">
                                        <div class="fw-bold"><?php echo (int)($employee['pointages_mois'] ?? 0); ?></div>
                                        <div class="text-muted small">Pointages (mois)</div>
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

</div> <!-- Fermeture de mainContent -->

<style>
.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

/* Masquer le loader quand la page est chargée */
.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

/* Gestion des deux types de loaders */
.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

/* En mode clair, inverser l'affichage */
body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

/* Loader Mode Clair - Cercle avec couleurs sombres */
.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

/* Texte du loader mode clair */
.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

/* Appliquer le fond du loader à la page - MODE JOUR ET NUIT */
body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.content-wrapper,
.content-wrapper * {
  background: transparent !important;
}

/* Forcer le fond pour tous les éléments principaux */
.main-content,
.container-fluid {
  background: transparent !important;
}

/* S'assurer que les cartes et éléments restent visibles */
.card,
.modal-content {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .card,
.dark-mode .modal-content {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,3 seconde puis masquer le loader et afficher le contenu
    setTimeout(function() {
        // Commencer l'animation de disparition du loader
        loader.classList.add('fade-out');
        
        // Après l'animation de disparition, masquer complètement le loader et afficher le contenu
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500); // Durée de l'animation de disparition
        
    }, 300); // 0,3 seconde comme demandé
});
</script>