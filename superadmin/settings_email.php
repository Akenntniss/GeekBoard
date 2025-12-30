<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de la page
$page_title = 'Configuration Email - GeekBoard SuperAdmin';
$page_heading = 'Configuration Email';
$page_subtitle = 'Gérez les paramètres SMTP et notifications';
$page_icon = 'fas fa-envelope';

require_once('includes/header.php');
require_once('../config/database.php');
require_once('../classes/EmailService.php');

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getMainDBConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_config':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE email_config SET
                            smtp_host = ?,
                            smtp_port = ?,
                            smtp_encryption = ?,
                            smtp_username = ?,
                            smtp_password = ?,
                            from_email = ?,
                            from_name = ?,
                            admin_email = ?,
                            enabled = ?,
                            updated_by = ?,
                            updated_at = NOW()
                        WHERE id = 1
                    ");
                    
                    $stmt->execute([
                        $_POST['smtp_host'],
                        (int)$_POST['smtp_port'],
                        $_POST['smtp_encryption'],
                        $_POST['smtp_username'],
                        $_POST['smtp_password'],
                        $_POST['from_email'],
                        $_POST['from_name'],
                        $_POST['admin_email'],
                        isset($_POST['enabled']) ? 1 : 0,
                        $_SESSION['superadmin_id']
                    ]);
                    
                    // Recharger config dans EmailService
                    $emailService = EmailService::getInstance();
                    $emailService->reloadConfig();
                    
                    $message = 'Configuration enregistrée avec succès !';
                    $message_type = 'success';
                    
                } catch (Exception $e) {
                    $message = 'Erreur : ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'test_connection':
                try {
                    $emailService = EmailService::getInstance();
                    $result = $emailService->testConnection();
                    
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                    
                } catch (Exception $e) {
                    $message = 'Erreur : ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'send_test_email':
                try {
                    $emailService = EmailService::getInstance();
                    $email = $_POST['test_email'] ?? null;
                    
                    if ($emailService->sendTestEmail($email)) {
                        $message = 'Email de test envoyé avec succès à ' . ($email ?: 'l\'admin') . ' !';
                        $message_type = 'success';
                    } else {
                        $message = 'Échec d\'envoi de l\'email de test. Vérifiez les logs.';
                        $message_type = 'danger';
                    }
                    
                } catch (Exception $e) {
                    $message = 'Erreur : ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Récupérer la configuration actuelle
$pdo = getMainDBConnection();

// Vérifier si la config existe, sinon créer
$stmt = $pdo->query("SELECT * FROM email_config WHERE id = 1 LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    // Créer la config par défaut
    $pdo->exec("
        INSERT INTO email_config (smtp_password) 
        VALUES ('Maisondugeek06$')
    ");
    $stmt = $pdo->query("SELECT * FROM email_config WHERE id = 1 LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupérer les derniers logs (peut être vide)
try {
    $stmt = $pdo->query("SELECT * FROM email_logs ORDER BY created_at DESC LIMIT 10");
    $recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_logs = [];
}

// DEBUG: Afficher si config existe
echo "<!-- DEBUG: Config loaded: " . ($config ? 'YES' : 'NO') . " -->";
echo "<!-- DEBUG: Config data: " . json_encode($config) . " -->";
echo "<!-- DEBUG: Logs count: " . count($recent_logs) . " -->";
?>

<!-- Alert Messages -->
<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> mb-6" data-auto-dismiss="5000">
    <div class="alert-icon">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    </div>
    <div><?php echo htmlspecialchars($message); ?></div>
</div>
<?php endif; ?>

<!-- Form Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Configuration Form -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-lg">Paramètres SMTP</h3>
            </div>
            
            <form method="POST" class="card-body" data-loading>
                <input type="hidden" name="action" value="save_config">
                
                <!-- Enabled Toggle -->
                <div class="form-group">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" 
                               name="enabled" 
                               <?php echo $config['enabled'] ? 'checked' : ''; ?>
                               class="w-5 h-5">
                        <span class="text-sm font-medium">
                            Activer les notifications email
                        </span>
                    </label>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- SMTP Host -->
                    <div class="form-group">
                        <label class="form-label">Serveur SMTP</label>
                        <input type="text" 
                               name="smtp_host" 
                               value="<?php echo htmlspecialchars($config['smtp_host']); ?>" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <!-- SMTP Port -->
                    <div class="form-group">
                        <label class="form-label">Port</label>
                        <input type="number" 
                               name="smtp_port" 
                               value="<?php echo $config['smtp_port']; ?>" 
                               class="form-control" 
                               required>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- Username -->
                    <div class="form-group">
                        <label class="form-label">Nom d'utilisateur</label>
                        <input type="text" 
                               name="smtp_username" 
                               value="<?php echo htmlspecialchars($config['smtp_username']); ?>" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <!-- Encryption -->
                    <div class="form-group">
                        <label class="form-label">Cryptage</label>
                        <select name="smtp_encryption" class="form-select" required>
                            <option value="ssl" <?php echo $config['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="tls" <?php echo $config['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        </select>
                    </div>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <div class="flex gap-2">
                        <input type="password" 
                               name="smtp_password" 
                               id="smtp_password"
                               value="<?php echo htmlspecialchars($config['smtp_password']); ?>" 
                               class="form-control flex-1" 
                               required>
                        <button type="button" 
                                class="btn btn-ghost btn-sm" 
                                onclick="togglePassword('smtp_password')">
                            <i class="fas fa-eye" id="smtp_password_icon"></i>
                        </button>
                    </div>
                </div>
                
                <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--gray-200);">
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- From Email -->
                    <div class="form-group">
                        <label class="form-label">Email expéditeur</label>
                        <input type="email" 
                               name="from_email" 
                               value="<?php echo htmlspecialchars($config['from_email']); ?>" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <!-- From Name -->
                    <div class="form-group">
                        <label class="form-label">Nom expéditeur</label>
                        <input type="text" 
                               name="from_name" 
                               value="<?php echo htmlspecialchars($config['from_name']); ?>" 
                               class="form-control" 
                               required>
                    </div>
                </div>
                
                <!-- Admin Email -->
                <div class="form-group">
                    <label class="form-label">Email de l'administrateur (destinataire des notifications)</label>
                    <input type="email" 
                           name="admin_email" 
                           value="<?php echo htmlspecialchars($config['admin_email']); ?>" 
                           class="form-control" 
                           required>
                    <small class="text-xs text-gray-500 mt-1">
                        Les notifications de nouveaux magasins et expirations seront envoyées à cette adresse.
                    </small>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Test Panel -->
    <div class="lg:col-span-1">
        <!-- Test Connection -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="font-semibold">Tester la Connexion</h3>
            </div>
            <div class="card-body">
                <p class="text-sm text-gray-600 mb-4">
                    Vérifiez que la connexion au serveur SMTP fonctionne.
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_connection">
                    <button type="submit" class="btn btn-secondary w-full">
                        <i class="fas fa-plug"></i>
                        Tester SMTP
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Send Test Email -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Envoyer un Test</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="send_test_email">
                    
                    <div class="form-group">
                        <label class="form-label text-sm">Email (optionnel)</label>
                        <input type="email" 
                               name="test_email" 
                               placeholder="<?php echo htmlspecialchars($config['admin_email']); ?>"
                               class="form-control">
                        <small class="text-xs text-gray-500 mt-1">
                            Laissez vide pour utiliser l'email admin
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer Test
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Recent Logs -->
<div class="card mt-6">
    <div class="card-header">
        <div class="flex justify-between items-center">
            <h3 class="font-semibold text-lg">Historique des Emails</h3>
            <span class="badge-gray"><?php echo count($recent_logs); ?> récents</span>
        </div>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Destinataire</th>
                    <th>Sujet</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_logs)): ?>
                <tr>
                    <td colspan="5" class="text-center text-gray-500 py-8">
                        Aucun email envoyé pour le moment
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td>
                            <span class="badge-primary text-xs">
                                <?php echo htmlspecialchars($log['type']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['recipient_email']); ?></td>
                        <td><?php echo htmlspecialchars(substr($log['subject'], 0, 50)); ?><?php echo strlen($log['subject']) > 50 ? '...' : ''; ?></td>
                        <td>
                            <?php if ($log['status'] === 'sent'): ?>
                                <span class="status-badge status-active">Envoyé</span>
                            <?php elseif ($log['status'] === 'failed'): ?>
                                <span class="status-badge status-expired" title="<?php echo htmlspecialchars($log['error_message']); ?>">Échec</span>
                            <?php else: ?>
                                <span class="badge-gray">En attente</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-gray-600">
                            <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?php require_once('includes/footer.php'); ?>
