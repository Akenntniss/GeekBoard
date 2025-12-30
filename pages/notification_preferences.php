<?php
require_once __DIR__ . '/../config/database.php';
// Inclure les fonctions de notification
$shop_pdo = getShopDBConnection();
require_once 'includes/notification_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];
// Check both possible session variable names for role
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'technicien';

// Vérifier si l'utilisateur est admin pour afficher le sélecteur de rôle
$is_admin = ($user_role === 'admin');

// Déterminer le mode d'édition (personal, admin, technicien)
$edit_mode = 'personal'; // Default: edit personal preferences
if ($is_admin && isset($_GET['role'])) {
    $requested_role = $_GET['role'];
    if (in_array($requested_role, ['admin', 'technicien'])) {
        $edit_mode = $requested_role;
    }
}

// Traitement des soumissions de formulaire
$redirect_url = null; // Will store redirect URL if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupérer les types de notification
    $stmt = $shop_pdo->prepare("SELECT type_code FROM notification_types");
    $stmt->execute();
    $notification_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Action: Mise à jour des préférences
    if (isset($_POST['action']) && $_POST['action'] === 'update_preferences') {
        $target_mode = $_POST['edit_mode'] ?? 'personal';
        
        foreach ($notification_types as $type) {
            $active = isset($_POST['active_' . $type]) ? 1 : 0;
            $email = isset($_POST['email_' . $type]) ? 1 : 0;
            $push = isset($_POST['push_' . $type]) ? 1 : 0;
            
            if ($target_mode === 'personal') {
                update_notification_preference($user_id, $type, $active, $email, $push);
            } else {
                // Role-based update
                update_role_notification_preference($target_mode, $type, $active, $email, $push);
            }
        }
        
        if ($target_mode === 'personal') {
            set_message('Vos préférences personnelles ont été mises à jour', 'success');
        } else {
            set_message('Les préférences du groupe "' . ucfirst($target_mode) . '" ont été mises à jour', 'success');
        }
        
        $redirect_url = 'index.php?page=notification_preferences' . ($target_mode !== 'personal' ? '&role=' . $target_mode : '');
    }
    
    // Action: Appliquer les préférences du rôle à tous les utilisateurs
    if (isset($_POST['action']) && $_POST['action'] === 'apply_to_users' && $is_admin) {
        $target_role = $_POST['target_role'] ?? '';
        if (in_array($target_role, ['admin', 'technicien'])) {
            $count = apply_role_preferences_to_users($target_role);
            set_message("Préférences appliquées à $count utilisateur(s) du groupe \"" . ucfirst($target_role) . "\"", 'success');
        }
        $redirect_url = 'index.php?page=notification_preferences&role=' . $target_role;
    }
}

// Charger les préférences selon le mode
if ($edit_mode === 'personal') {
    set_default_notification_preferences($user_id);
    $preferences = get_notification_preferences($user_id);
} else {
    set_default_role_notification_preferences($edit_mode);
    $preferences = get_role_notification_preferences($edit_mode);
}

// Récupérer les types de notification
$stmt = $shop_pdo->prepare("SELECT * FROM notification_types ORDER BY importance DESC, description ASC");
$stmt->execute();
$notification_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les préférences par type pour un accès facile
$preferences_by_type = [];
foreach ($preferences as $pref) {
    $preferences_by_type[$pref['type_notification']] = $pref;
}

// Récupérer les paramètres email (SMTP/IMAP)
$email_keys = [
    'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption',
    'imap_host', 'imap_port', 'imap_encryption', 'email_from_name',
    'email_notifications_enabled'
];
$email_settings = [];
foreach ($email_keys as $key) {
    $stmt = $shop_pdo->prepare("SELECT valeur FROM parametres WHERE cle = ?");
    $stmt->execute([$key]);
    $email_settings[$key] = $stmt->fetchColumn() ?: '';
}
?>

<style>
/* CSS Variables for Day/Night Compatibility */
:root {
    --notif-bg: var(--gb-bg-primary, #ffffff);
    --notif-text: var(--gb-text-primary, #1f2937);
    --notif-text-muted: var(--gb-text-muted, #6b7280);
    --notif-card-bg: var(--gb-card-bg, rgba(255, 255, 255, 0.9));
    --notif-border: var(--gb-border-color, #e2e8f0);
    --notif-header-bg: var(--gb-bg-secondary, #f8fafc);
    --notif-accent: var(--gb-accent-primary, #4361ee);
    --notif-accent-hover: var(--gb-accent-secondary, #3730a3);
    --notif-glass-blur: blur(12px);
    --notif-shadow: var(--gb-shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1));
}

.night-mode .notif-wrapper {
    --notif-card-bg: rgba(30, 41, 59, 0.6);
    --notif-header-bg: rgba(15, 23, 42, 0.4);
    --notif-border: rgba(255, 255, 255, 0.08);
}

.notif-wrapper {
    color: var(--notif-text);
    min-height: calc(100vh - 85px);
    transition: var(--gb-transition, all 0.3s ease);
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.notif-header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.5rem;
    animation: fadeInDown 0.5s ease-out;
}

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.notif-title {
    font-size: 2.25rem;
    font-weight: 800;
    margin: 0;
    background: linear-gradient(135deg, var(--notif-text) 0%, var(--notif-accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.025em;
}

.notif-container {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .notif-container {
        grid-template-columns: 1fr;
    }
    .notif-sidebar {
        order: 2;
    }
    .notif-main {
        order: 1;
    }
}

/* Glassmorphism Cards */
.notif-card {
    background: var(--notif-card-bg);
    backdrop-filter: var(--notif-glass-blur);
    -webkit-backdrop-filter: var(--notif-glass-blur);
    border: 1px solid var(--notif-border);
    border-radius: 1.25rem;
    box-shadow: var(--notif-shadow);
    overflow: hidden;
    margin-bottom: 2rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.notif-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.notif-card-header {
    background: var(--notif-header-bg);
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--notif-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notif-card-body {
    padding: 1.5rem;
}

/* Custom Table-like structure without Bootstrap Table */
.notif-list {
    display: flex;
    flex-direction: column;
}

.notif-item {
    display: grid;
    grid-template-columns: 1fr 100px 100px 100px;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--notif-border);
    transition: background-color 0.2s ease;
}

.notif-item:last-child {
    border-bottom: none;
}

.notif-item:hover {
    background-color: var(--notif-header-bg);
}

.notif-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.notif-icon-box {
    width: 40px;
    height: 40px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.notif-details {
    display: flex;
    flex-direction: column;
}

.notif-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.notif-badges {
    display: flex;
    gap: 0.5rem;
}

.notif-badge {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-weight: 500;
}

.badge-critique { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }
.badge-haute { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.badge-normale { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.badge-basse { background-color: rgba(107, 114, 128, 0.1); color: #6b7280; }

/* Custom Switch Toggle */
.notif-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.notif-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: var(--notif-accent);
}

input:focus + .slider {
    box-shadow: 0 0 1px var(--notif-accent);
}

input:checked + .slider:before {
    transform: translateX(24px);
}

input:disabled + .slider {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Sidebar Helpers */
.sidebar-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.sidebar-card {
    background: var(--notif-card-bg);
    border: 1px solid var(--notif-border);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--notif-shadow);
}

.sidebar-title {
    font-weight: 700;
    font-size: 1.125rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.help-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.help-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.help-item:last-child {
    margin-bottom: 0;
}

.help-icon {
    width: 32px;
    height: 32px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.help-text strong {
    display: block;
    font-size: 0.95rem;
}

.help-text p {
    font-size: 0.875rem;
    color: var(--notif-text-muted);
    margin: 0;
}

/* Column headers for the list */
.notif-list-header {
    display: grid;
    grid-template-columns: 1fr 100px 100px 100px;
    padding: 1rem 1.5rem;
    background: var(--notif-header-bg);
    border-bottom: 1px solid var(--notif-border);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--notif-text-muted);
    letter-spacing: 0.05em;
}

.col-center {
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
}

/* Save Button */
.btn-save {
    background: var(--notif-accent);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn-save:hover {
    background: var(--notif-accent-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
}

.notif-item.item-importance-critique {
    background-color: rgba(239, 68, 68, 0.03);
}

.notif-item.item-importance-haute {
    background-color: rgba(245, 158, 11, 0.02);
}

</style>

<div class="page-container">
    <div class="notif-wrapper">
        <div class="notif-header-section">
            <h1 class="notif-title"><i class="fas fa-bell-slash me-2"></i>Préférences de notification</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#emailConfigModal">
                    <i class="fas fa-envelope me-2"></i>Configuration MAIL
                </button>
                <a href="index.php?page=admin_notifications" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>

        <?php echo display_message(); ?>

        <?php if ($is_admin): ?>
        <!-- Role Selector for Admins -->
        <div class="role-selector mb-4 p-3 rounded-3" style="background: var(--notif-card-bg); border: 1px solid var(--notif-border);">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-bold"><i class="fas fa-users-cog me-2"></i>Configurer pour :</span>
                    <div class="btn-group" role="group">
                        <a href="index.php?page=notification_preferences" 
                           class="btn <?php echo $edit_mode === 'personal' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                            <i class="fas fa-user me-1"></i>Mes préférences
                        </a>
                        <a href="index.php?page=notification_preferences&role=admin" 
                           class="btn <?php echo $edit_mode === 'admin' ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                            <i class="fas fa-user-shield me-1"></i>Admins
                        </a>
                        <a href="index.php?page=notification_preferences&role=technicien" 
                           class="btn <?php echo $edit_mode === 'technicien' ? 'btn-info' : 'btn-outline-secondary'; ?>">
                            <i class="fas fa-tools me-1"></i>Employés
                        </a>
                    </div>
                </div>
                
                <?php if ($edit_mode !== 'personal'): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="apply_to_users">
                    <input type="hidden" name="target_role" value="<?php echo htmlspecialchars($edit_mode); ?>">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Appliquer ces préférences à TOUS les utilisateurs du groupe <?php echo ucfirst($edit_mode); ?> ?');">
                        <i class="fas fa-sync me-1"></i>Appliquer à tous les <?php echo $edit_mode === 'admin' ? 'Admins' : 'Employés'; ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            
            <?php if ($edit_mode !== 'personal'): ?>
            <div class="alert alert-warning mt-3 mb-0 small">
                <i class="fas fa-info-circle me-2"></i>
                Vous modifiez les préférences <strong>par défaut</strong> du groupe "<?php echo ucfirst($edit_mode); ?>".
                Cliquez sur "Appliquer à tous" pour synchroniser avec les utilisateurs existants.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_preferences">
            <input type="hidden" name="edit_mode" value="<?php echo htmlspecialchars($edit_mode); ?>">
            
            <div class="notif-container">
                <!-- Main List -->
                <div class="notif-main">
                    <div class="notif-card">
                        <div class="notif-card-header">
                            <span style="font-weight: 700;">
                                <?php if ($edit_mode === 'personal'): ?>
                                    <i class="fas fa-user me-2"></i>Mes préférences personnelles
                                <?php elseif ($edit_mode === 'admin'): ?>
                                    <i class="fas fa-user-shield me-2 text-warning"></i>Préférences groupe Admins
                                <?php else: ?>
                                    <i class="fas fa-tools me-2 text-info"></i>Préférences groupe Employés
                                <?php endif; ?>
                            </span>
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        </div>
                        
                        <div class="notif-list-header">
                            <div>Type de notification</div>
                            <div class="col-center">Activer</div>
                            <div class="col-center">Email</div>
                            <div class="col-center">Push</div>
                        </div>

                        <div class="notif-list">
                            <?php foreach ($notification_types as $type): ?>
                                <?php 
                                $pref = $preferences_by_type[$type['type_code']] ?? [
                                    'active' => 1, 
                                    'email_notification' => 0, 
                                    'push_notification' => 1
                                ];
                                ?>
                                <div class="notif-item item-importance-<?php echo $type['importance']; ?>">
                                    <div class="notif-info">
                                        <div class="notif-icon-box" style="background-color: <?php echo $type['color'] ?? '#4361ee'; ?>">
                                            <i class="<?php echo $type['icon'] ?? 'fas fa-bell'; ?>"></i>
                                        </div>
                                        <div class="notif-details">
                                            <span class="notif-name"><?php echo htmlspecialchars($type['description']); ?></span>
                                            <div class="notif-badges">
                                                <span class="notif-badge badge-<?php echo $type['importance']; ?>">
                                                    <?php 
                                                    $imp_label = 'Normale';
                                                    if($type['importance'] >= 3) $imp_label = 'Critique';
                                                    elseif($type['importance'] >= 2) $imp_label = 'Haute';
                                                    echo $imp_label; 
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-center">
                                        <label class="notif-switch">
                                            <input type="checkbox" 
                                                   class="notif-toggle" 
                                                   name="active_<?php echo $type['type_code']; ?>" 
                                                   id="active_<?php echo $type['type_code']; ?>"
                                                   data-type="<?php echo $type['type_code']; ?>"
                                                   <?php echo ($pref['active'] ? 'checked' : ''); ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>

                                    <div class="col-center">
                                        <label class="notif-switch">
                                            <input type="checkbox" 
                                                   class="notif-method" 
                                                   name="email_<?php echo $type['type_code']; ?>" 
                                                   id="email_<?php echo $type['type_code']; ?>"
                                                   data-type="<?php echo $type['type_code']; ?>"
                                                   <?php echo ($pref['email_notification'] ? 'checked' : ''); ?>
                                                   <?php echo (!$pref['active'] ? 'disabled' : ''); ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>

                                    <div class="col-center">
                                        <label class="notif-switch">
                                            <input type="checkbox" 
                                                   class="notif-method" 
                                                   name="push_<?php echo $type['type_code']; ?>" 
                                                   id="push_<?php echo $type['type_code']; ?>"
                                                   data-type="<?php echo $type['type_code']; ?>"
                                                   <?php echo ($pref['push_notification'] ? 'checked' : ''); ?>
                                                   <?php echo (!$pref['active'] ? 'disabled' : ''); ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="notif-card-body" style="background: var(--notif-header-bg); text-align: right; padding: 1.5rem;">
                            <button type="submit" class="btn-save" style="display: inline-flex; margin-left: auto;">
                                <i class="fas fa-save me-2"></i> Enregistrer les préférences
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar-section">
                    <!-- How it works -->
                    <div class="sidebar-card">
                        <div class="sidebar-title">
                            <i class="fas fa-question-circle text-primary"></i> Fonctionnement
                        </div>
                        <ul class="help-list">
                            <li class="help-item">
                                <div class="help-icon" style="background-color: rgba(67, 97, 238, 0.1); color: var(--notif-accent);">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <div class="help-text">
                                    <strong>Dans l'application</strong>
                                    <p>Visibles dans votre centre de notifications.</p>
                                </div>
                            </li>
                            <li class="help-item">
                                <div class="help-icon" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="help-text">
                                    <strong>Par email</strong>
                                    <p>Alertes envoyées sur votre boîte mail.</p>
                                </div>
                            </li>
                            <li class="help-item">
                                <div class="help-icon" style="background-color: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="help-text">
                                    <strong>Notifications Push</strong>
                                    <p>Alertes navigateur même si l'onglet est fermé.</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Importance Levels -->
                    <div class="sidebar-card">
                        <div class="sidebar-title">
                            <i class="fas fa-layer-group text-primary"></i> Importance
                        </div>
                        <ul class="help-list">
                            <li class="help-item">
                                <div class="help-icon badge-critique">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="help-text">
                                    <strong>Critique</strong>
                                    <p>Attention immédiate requise.</p>
                                </div>
                            </li>
                            <li class="help-item">
                                <div class="help-icon badge-haute">
                                    <i class="fas fa-exclamation"></i>
                                </div>
                                <div class="help-text">
                                    <strong>Haute</strong>
                                    <p>Action rapide recommandée.</p>
                                </div>
                            </li>
                            <li class="help-item">
                                <div class="help-icon badge-normale">
                                    <i class="fas fa-info"></i>
                                </div>
                                <div class="help-text">
                                    <strong>Normale</strong>
                                    <p>Activités quotidiennes standards.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'activation/désactivation des lignes
    const toggles = document.querySelectorAll('.notif-toggle');
    toggles.forEach(toggle => {
        const handleToggle = function(el, isInitial = false) {
            const type = el.dataset.type;
            const isActive = el.checked;
            
            // Activer/désactiver les méthodes de notification
            const emailToggle = document.getElementById(`email_${type}`);
            const pushToggle = document.getElementById(`push_${type}`);
            
            if (emailToggle) {
                emailToggle.disabled = !isActive;
                if (!isActive) emailToggle.checked = false;
            }
            
            if (pushToggle) {
                pushToggle.disabled = !isActive;
                if (!isActive) pushToggle.checked = false;
            }

            // Visuel de la ligne
            const row = el.closest('.notif-item');
            if (row) {
                if (!isActive) {
                    row.style.opacity = '0.6';
                    row.classList.add('is-disabled');
                } else {
                    row.style.opacity = '1';
                    row.classList.remove('is-disabled');
                }
            }
        };

        toggle.addEventListener('change', function() { handleToggle(this); });
        
        // Initial state
        handleToggle(toggle, true);
    });
    
    // S'assurer qu'au moins une méthode est active si la notification est activée
    const methodToggles = document.querySelectorAll('.notif-method');
    methodToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const type = this.dataset.type;
            const activeToggle = document.getElementById(`active_${type}`);
            
            if (activeToggle && activeToggle.checked) {
                const emailToggle = document.getElementById(`email_${type}`);
                const pushToggle = document.getElementById(`push_${type}`);
                
                // Si les deux sont décochés, on empêche cela ou on réactive Push
                if (emailToggle && pushToggle && !emailToggle.checked && !pushToggle.checked) {
                    this.checked = true; // Empêcher de tout décocher
                }
            }
        });
    });

    // Gestion du formulaire Email Config
    const emailConfigForm = document.getElementById('emailConfigForm');
    if (emailConfigForm) {
        emailConfigForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';
            
            fetch('ajax/save_email_config.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Configuration enregistrée avec succès');
                    bootstrap.Modal.getInstance(document.getElementById('emailConfigModal')).hide();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de l\'enregistrement');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    // Bouton de test email
    const btnTestEmail = document.getElementById('btnTestEmail');
    if (btnTestEmail) {
        btnTestEmail.addEventListener('click', function() {
            const formData = new FormData(emailConfigForm);
            const btn = this;
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Test en cours...';
            
            fetch('ajax/test_email_connection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors du test');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
});
</script>

<!-- Modal Configuration Email -->
<div class="modal fade" id="emailConfigModal" tabindex="-1" aria-labelledby="emailConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--notif-card-bg); backdrop-filter: var(--notif-glass-blur); border: 1px solid var(--notif-border); border-radius: 1.25rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="emailConfigModalLabel" style="font-weight: 800; color: var(--notif-text);">
                    <i class="fas fa-envelope-open-text me-2 text-primary"></i>Configuration Mail du Magasin
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="emailConfigForm">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Section SMTP -->
                        <div class="col-12">
                            <h6 class="text-uppercase small fw-bold mb-3 border-bottom pb-2" style="color: var(--notif-accent);">
                                <i class="fas fa-paper-plane me-2"></i>Serveur Sortant (SMTP)
                            </h6>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small">Hôte SMTP</label>
                            <input type="text" class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($email_settings['smtp_host']); ?>" placeholder="ex: smtp.hostinger.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Port SMTP</label>
                            <input type="number" class="form-control" name="smtp_port" value="<?php echo htmlspecialchars($email_settings['smtp_port']); ?>" placeholder="ex: 465">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Utilisateur SMTP</label>
                            <input type="email" class="form-control" name="smtp_user" value="<?php echo htmlspecialchars($email_settings['smtp_user']); ?>" placeholder="votre@email.fr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Mot de passe SMTP</label>
                            <input type="password" class="form-control" name="smtp_pass" value="<?php echo htmlspecialchars($email_settings['smtp_pass']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Chiffrement</label>
                            <select class="form-select" name="smtp_encryption">
                                <option value="ssl" <?php echo ($email_settings['smtp_encryption'] === 'ssl' ? 'selected' : ''); ?>>SSL</option>
                                <option value="tls" <?php echo ($email_settings['smtp_encryption'] === 'tls' ? 'selected' : ''); ?>>TLS</option>
                                <option value="none" <?php echo ($email_settings['smtp_encryption'] === 'none' ? 'selected' : ''); ?>>Aucun</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Nom d'expéditeur</label>
                            <input type="text" class="form-control" name="email_from_name" value="<?php echo htmlspecialchars($email_settings['email_from_name']); ?>" placeholder="ex: Mdg Geekboard">
                        </div>

                        <!-- Section IMAP -->
                        <div class="col-12 mt-4">
                            <h6 class="text-uppercase small fw-bold mb-3 border-bottom pb-2" style="color: var(--notif-accent);">
                                <i class="fas fa-inbox me-2"></i>Serveur Entrant (IMAP)
                            </h6>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small">Hôte IMAP</label>
                            <input type="text" class="form-control" name="imap_host" value="<?php echo htmlspecialchars($email_settings['imap_host']); ?>" placeholder="ex: imap.hostinger.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Port IMAP</label>
                            <input type="number" class="form-control" name="imap_port" value="<?php echo htmlspecialchars($email_settings['imap_port']); ?>" placeholder="ex: 993">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Chiffrement IMAP</label>
                            <select class="form-select" name="imap_encryption">
                                <option value="ssl" <?php echo ($email_settings['imap_encryption'] === 'ssl' ? 'selected' : ''); ?>>SSL</option>
                                <option value="tls" <?php echo ($email_settings['imap_encryption'] === 'tls' ? 'selected' : ''); ?>>TLS</option>
                                <option value="none" <?php echo ($email_settings['imap_encryption'] === 'none' ? 'selected' : ''); ?>>Aucun</option>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="form-check form-switch card p-3 shadow-none bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="email_notifications_enabled" id="email_notifications_enabled" <?php echo ($email_settings['email_notifications_enabled'] == '1' ? 'checked' : ''); ?>>
                                <label class="form-check-label fw-bold" for="email_notifications_enabled">Activer les notifications par email</label>
                                <small class="d-block text-muted mt-1">Si décoché, aucun email ne sera envoyé même si les préférences individuelles sont actives.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-info" id="btnTestEmail">
                        <i class="fas fa-vial me-2"></i>Tester la connexion
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($redirect_url): ?>
<script>
    // Redirect after successful form submission
    window.location.href = '<?php echo htmlspecialchars($redirect_url); ?>';
</script>
<?php endif; ?>
 