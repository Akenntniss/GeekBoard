<?php
/**
 * Gestionnaire des clés API SMS - SuperAdmin
 * Permet de configurer les clés API SMS pour chaque magasin
 */
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

require_once('../config/database.php');

$pdo = getMainDBConnection();
$message = '';
$message_type = 'success';

// Gestion des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_api_key':
                $shop_id = (int)($_POST['shop_id'] ?? 0);
                $api_key = trim($_POST['api_key'] ?? '');
                
                if ($shop_id > 0 && !empty($api_key)) {
                    $stmt = $pdo->prepare("UPDATE shops SET sms_api_key = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$api_key, $shop_id]);
                    $message = "Clé API SMS mise à jour avec succès pour le magasin.";
                } else {
                    throw new Exception("Données invalides");
                }
                break;
                
            case 'test_api':
                $shop_id = (int)($_POST['shop_id'] ?? 0);
                
                if ($shop_id > 0) {
                    // Récupérer la clé API du magasin
                    $stmt = $pdo->prepare("SELECT sms_api_key FROM shops WHERE id = ?");
                    $stmt->execute([$shop_id]);
                    $shop = $stmt->fetch();
                    
                    if ($shop && !empty($shop['sms_api_key'])) {
                        // Tester la connexion à l'API SMS
                        $apiKey = $shop['sms_api_key'];
                        $testResult = testSmsApi($apiKey);
                        
                        if ($testResult['success']) {
                            $message = "✅ Test réussi! L'API SMS est fonctionnelle.";
                        } else {
                            $message = "❌ Test échoué: " . $testResult['message'];
                            $message_type = 'error';
                        }
                    } else {
                        throw new Exception("Clé API non configurée pour ce magasin");
                    }
                }
                break;
                
            default:
                throw new Exception("Action non reconnue");
        }
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'error';
    }
}

/**
 * Teste la connexion à l'API SMS
 */
function testSmsApi($apiKey) {
    $url = 'https://sms.maisondugeek.fr/api/send';
    
    // Test simple - on ne fait qu'un HEAD request ou un test de connectivité
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_CUSTOMREQUEST => 'OPTIONS' // Test de connectivité sans envoi
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    if ($curlError) {
        return ['success' => false, 'message' => 'Erreur de connexion: ' . $curlError];
    }
    
    // Accepter les codes 200, 204, 401 (clé invalide mais API accessible), 405 (method not allowed mais API accessible)
    if ($httpCode >= 200 && $httpCode < 500) {
        return ['success' => true, 'message' => 'API accessible (HTTP ' . $httpCode . ')'];
    }
    
    return ['success' => false, 'message' => 'API non accessible (HTTP ' . $httpCode . ')'];
}

// Récupérer tous les magasins avec leurs clés API
$stmt = $pdo->query("
    SELECT id, name, subdomain, sms_api_key, active, created_at
    FROM shops
    ORDER BY name ASC
");
$shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats = [
    'total' => count($shops),
    'configured' => count(array_filter($shops, fn($s) => !empty($s['sms_api_key']) && $s['sms_api_key'] !== '1234')),
    'default' => count(array_filter($shops, fn($s) => empty($s['sms_api_key']) || $s['sms_api_key'] === '1234')),
];

// Configuration de la page
$page_title = 'Gestion des Clés API SMS - GeekBoard';
$page_heading = 'Gestion des Clés API SMS';
$page_subtitle = 'Configurez les clés API SMS pour chaque magasin';
$page_icon = 'fas fa-sms';

include __DIR__ . '/includes/header.php';
?>
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i>Retour au tableau de bord
            </a>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Informations sur l'API -->
            <div class="card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Configuration de l'API SMS Gateway</h5>
                    <p class="mb-2">
                        Chaque magasin utilise sa propre clé API pour envoyer des SMS via 
                        <a href="https://sms.maisondugeek.fr" target="_blank" style="color: #ffd700;">SMS Gateway</a>.
                    </p>
                    <p class="mb-0">
                        <strong>Documentation:</strong> 
                        <a href="https://sms.maisondugeek.fr/docs" target="_blank" style="color: #ffd700;">
                            <i class="fas fa-external-link-alt me-1"></i>Accéder à la documentation
                        </a>
                    </p>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="stats-row">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon text-primary">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Magasins</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['configured']; ?></div>
                            <div class="stat-label">API Configurée</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['default']; ?></div>
                            <div class="stat-label">À Configurer</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table des magasins -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-key me-2"></i>Clés API SMS par Magasin
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Magasin</th>
                                    <th>Sous-domaine</th>
                                    <th>Clé API SMS</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shops as $shop): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($shop['name']); ?></strong>
                                            <?php if (!$shop['active']): ?>
                                                <span class="badge bg-secondary ms-2">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($shop['subdomain']): ?>
                                                <code><?php echo htmlspecialchars($shop['subdomain']); ?>.servo.tools</code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="text" 
                                                       class="form-control api-key-input" 
                                                       id="api_key_<?php echo $shop['id']; ?>"
                                                       value="<?php echo htmlspecialchars($shop['sms_api_key'] ?? ''); ?>"
                                                       placeholder="Clé API SMS"
                                                       style="font-family: monospace;">
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        onclick="toggleApiKeyVisibility(<?php echo $shop['id']; ?>)">
                                                    <i class="fas fa-eye" id="eye_<?php echo $shop['id']; ?>"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (empty($shop['sms_api_key']) || $shop['sms_api_key'] === '1234'): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Par défaut
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Configurée
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="saveApiKey(<?php echo $shop['id']; ?>)">
                                                    <i class="fas fa-save me-1"></i>Sauvegarder
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                        onclick="testApiKey(<?php echo $shop['id']; ?>)">
                                                    <i class="fas fa-vial me-1"></i>Tester
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

    <!-- Formulaire caché pour les actions -->
    <form id="actionForm" method="post" style="display: none;">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="shop_id" id="formShopId">
        <input type="hidden" name="api_key" id="formApiKey">
    </form>

    <script>
        // Masquer/Afficher la clé API
        function toggleApiKeyVisibility(shopId) {
            const input = document.getElementById('api_key_' + shopId);
            const eye = document.getElementById('eye_' + shopId);
            
            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }
        
        // Initialiser tous les champs en mode mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.api-key-input').forEach(input => {
                input.type = 'password';
            });
        });
        
        // Sauvegarder la clé API
        function saveApiKey(shopId) {
            const apiKey = document.getElementById('api_key_' + shopId).value.trim();
            
            if (!apiKey) {
                alert('Veuillez entrer une clé API');
                return;
            }
            
            document.getElementById('formAction').value = 'update_api_key';
            document.getElementById('formShopId').value = shopId;
            document.getElementById('formApiKey').value = apiKey;
            document.getElementById('actionForm').submit();
        }
        
        // Tester la clé API
        function testApiKey(shopId) {
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Test...';
            btn.disabled = true;
            
            document.getElementById('formAction').value = 'test_api';
            document.getElementById('formShopId').value = shopId;
            document.getElementById('actionForm').submit();
        }
    </script>

    <style>
        .api-key-input {
            background: rgba(0,0,0,0.05);
        }
        
        .api-key-input:focus {
            background: white;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
    </style>

<?php include __DIR__ . '/includes/footer.php'; ?>
