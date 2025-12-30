<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/subdomain_config.php';

if (function_exists('initializeShopSession')) {
    initializeShopSession();
}
$pdo = getShopDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo '<div class="alert alert-danger">ID invalide</div>';
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo '<div class="alert alert-danger">Utilisateur introuvable</div>';
        exit;
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>
<div id="editEmployeeErrors" class="alert alert-danger d-none"></div>
<form id="editEmployeeForm">
    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
    <div class="mb-3">
        <label class="form-label">Nom d'utilisateur *</label>
        <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Nouveau mot de passe (laisser vide si inchangé)</label>
        <input type="password" class="form-control" name="password">
    </div>
    <div class="mb-3">
        <label class="form-label">Nom complet *</label>
        <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Rôle *</label>
        <select class="form-select" name="role" required>
            <option value="technicien" <?php echo $user['role']==='technicien'?'selected':''; ?>>Technicien</option>
            <option value="admin" <?php echo $user['role']==='admin'?'selected':''; ?>>Administrateur</option>
        </select>
    </div>
    <div class="text-end">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Enregistrer</button>
    </div>
</form>

