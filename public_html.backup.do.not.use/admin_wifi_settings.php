<?php
/**
 * Page d'administration avec gestion des SSID WiFi autorisés
 * Version simplifiée et respectueuse de la vie privée
 */

// Configuration de base
require_once __DIR__ . '/config/database.php';
session_start();

try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Connexion à la base de données échouée");
    }
} catch (Exception $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

// Récupérer les SSID autorisés
try {
    $stmt = $shop_pdo->prepare("
        SELECT * FROM wifi_authorized_ssids 
        ORDER BY is_active DESC, ssid ASC
    ");
    $stmt->execute();
    $authorized_ssids = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $authorized_ssids = [];
}

// Récupérer les utilisateurs
try {
    $stmt = $shop_pdo->prepare("SELECT id, full_name FROM users ORDER BY full_name");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $all_users = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📶 Administration Pointage WiFi - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .nav-tabs-custom .nav-link {
            border: none;
            color: white !important;
            background: transparent;
            border-radius: 8px;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.75rem 1.25rem;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .nav-tabs-custom .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .nav-tabs-custom .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            font-weight: 700;
        }
        
        .wifi-ssid-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .wifi-ssid-item:hover {
            background: #e9ecef;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .wifi-ssid-item.disabled {
            opacity: 0.6;
            background: #f1f3f4;
        }
        
        .btn-wifi {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-wifi:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .status-inactive {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="text-center mb-4">
                    <h1 class="text-white mb-3">
                        <i class="fas fa-wifi"></i>
                        Administration Pointage WiFi
                    </h1>
                    <p class="text-white-50 lead">
                        Système de pointage simplifié basé sur la vérification du réseau WiFi
                    </p>
                </div>

                <!-- Messages Container -->
                <div id="messages-container"></div>

                <!-- Navigation -->
                <div class="main-container p-4 mb-4">
                    <ul class="nav nav-tabs nav-tabs-custom" id="adminTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="wifi-settings-tab" data-bs-toggle="tab" data-bs-target="#wifi-settings" type="button">
                                <i class="fas fa-wifi"></i> Réseaux WiFi Autorisés
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="time-slots-tab" data-bs-toggle="tab" data-bs-target="#time-slots" type="button">
                                <i class="fas fa-clock"></i> Créneaux Horaires
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="test-tab" data-bs-toggle="tab" data-bs-target="#test" type="button">
                                <i class="fas fa-test-tube"></i> Test de Pointage
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Contenu des onglets -->
                <div class="main-container p-4">
                    <div class="tab-content" id="adminTabsContent">
                        
                        <!-- Gestion des SSID WiFi -->
                        <div class="tab-pane fade show active" id="wifi-settings">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3><i class="fas fa-wifi text-primary"></i> Réseaux WiFi Autorisés</h3>
                                    <p class="text-muted">Configurez les réseaux WiFi depuis lesquels les employés peuvent pointer.</p>
                                    
                                    <!-- Formulaire d'ajout de SSID -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5><i class="fas fa-plus"></i> Ajouter un nouveau réseau</h5>
                                        </div>
                                        <div class="card-body">
                                            <form id="addSSIDForm" onsubmit="addSSID(event)">
                                                <div class="mb-3">
                                                    <label class="form-label">Nom du réseau (SSID) *</label>
                                                    <input type="text" class="form-control" name="ssid" required 
                                                           placeholder="Ex: WiFi-Magasin-Principal">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Description</label>
                                                    <input type="text" class="form-control" name="description" 
                                                           placeholder="Ex: WiFi principal du magasin">
                                                </div>
                                                <button type="submit" class="btn btn-wifi">
                                                    <i class="fas fa-plus"></i> Ajouter
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h4><i class="fas fa-list"></i> Réseaux configurés</h4>
                                    <div id="ssid-list">
                                        <?php if (empty($authorized_ssids)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-wifi fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Aucun réseau WiFi configuré</p>
                                            <small class="text-muted">Ajoutez un SSID pour commencer</small>
                                        </div>
                                        <?php else: ?>
                                        <?php foreach ($authorized_ssids as $ssid_config): ?>
                                        <div class="wifi-ssid-item <?php echo $ssid_config['is_active'] ? '' : 'disabled'; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-wifi <?php echo $ssid_config['is_active'] ? 'text-success' : 'text-muted'; ?>"></i>
                                                        <?php echo htmlspecialchars($ssid_config['ssid']); ?>
                                                    </h6>
                                                    <?php if ($ssid_config['description']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($ssid_config['description']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <span class="status-badge <?php echo $ssid_config['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $ssid_config['is_active'] ? 'Actif' : 'Inactif'; ?>
                                                    </span>
                                                    <button class="btn btn-sm btn-outline-secondary" 
                                                            onclick="toggleSSID(<?php echo $ssid_config['id']; ?>, <?php echo $ssid_config['is_active'] ? 'false' : 'true'; ?>)">
                                                        <i class="fas fa-<?php echo $ssid_config['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="removeSSID(<?php echo $ssid_config['id']; ?>, '<?php echo htmlspecialchars($ssid_config['ssid']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Créneaux Horaires (version simplifiée) -->
                        <div class="tab-pane fade" id="time-slots">
                            <h3><i class="fas fa-clock text-warning"></i> Créneaux Horaires</h3>
                            <p class="text-muted">Les créneaux horaires fonctionnent normalement avec le système WiFi.</p>
                            
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Information</h5>
                                <p class="mb-0">
                                    Le système de créneaux horaires reste actif. Les employés doivent :
                                </p>
                                <ul class="mt-2 mb-0">
                                    <li>Être connectés à un WiFi autorisé</li>
                                    <li>Pointer dans les créneaux horaires définis</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Test de Pointage -->
                        <div class="tab-pane fade" id="test">
                            <h3><i class="fas fa-test-tube text-success"></i> Test du Système de Pointage</h3>
                            <p class="text-muted">Testez le pointage WiFi avec votre réseau actuel.</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-wifi"></i> Informations WiFi</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">SSID du réseau actuel</label>
                                                <input type="text" class="form-control" id="wifi-ssid-input" 
                                                       placeholder="Saisissez le nom de votre WiFi">
                                                <small class="form-text text-muted">
                                                    Le navigateur ne peut pas détecter automatiquement le SSID pour des raisons de sécurité.
                                                </small>
                                            </div>
                                            
                                            <div id="tracking-status" class="mb-3">
                                                <div class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Chargement...</span>
                                                    </div>
                                                    <p class="mt-2">Vérification du statut...</p>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid gap-2">
                                                <button id="clock-in-btn" class="btn btn-wifi btn-lg" disabled>
                                                    <i class="fas fa-sign-in-alt"></i> Pointer l'Arrivée
                                                </button>
                                                <button id="clock-out-btn" class="btn btn-outline-secondary btn-lg" disabled>
                                                    <i class="fas fa-sign-out-alt"></i> Pointer le Départ
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-shield-alt"></i> Avantages du Système WiFi</h5>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                <li class="mb-2">
                                                    <i class="fas fa-check text-success"></i>
                                                    <strong>Respectueux de la vie privée</strong><br>
                                                    <small class="text-muted">Aucune géolocalisation ni tracking personnel</small>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check text-success"></i>
                                                    <strong>Simple et efficace</strong><br>
                                                    <small class="text-muted">Vérification basée sur le réseau uniquement</small>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check text-success"></i>
                                                    <strong>Conforme RGPD</strong><br>
                                                    <small class="text-muted">Collecte minimale de données</small>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check text-success"></i>
                                                    <strong>Facile à configurer</strong><br>
                                                    <small class="text-muted">Ajout/suppression de réseaux en quelques clics</small>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="time_tracking_wifi.js"></script>
    
    <script>
        // Gestion des SSID WiFi
        async function addSSID(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            formData.append('action', 'add_ssid');
            
            try {
                const response = await fetch('time_tracking_api_wifi.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('✅ SSID ajouté avec succès', 'success');
                    event.target.reset();
                    loadSSIDList();
                } else {
                    showMessage(`❌ ${result.message}`, 'error');
                }
            } catch (error) {
                showMessage(`❌ Erreur: ${error.message}`, 'error');
            }
        }
        
        async function removeSSID(id, ssid) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer le SSID "${ssid}" ?`)) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'remove_ssid');
                    formData.append('id', id);
                    
                    const response = await fetch('time_tracking_api_wifi.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showMessage('✅ SSID supprimé avec succès', 'success');
                        loadSSIDList();
                    } else {
                        showMessage(`❌ ${result.message}`, 'error');
                    }
                } catch (error) {
                    showMessage(`❌ Erreur: ${error.message}`, 'error');
                }
            }
        }
        
        async function toggleSSID(id, isActive) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_ssid');
                formData.append('id', id);
                formData.append('is_active', isActive);
                
                const response = await fetch('time_tracking_api_wifi.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage(`✅ SSID ${isActive === 'true' ? 'activé' : 'désactivé'} avec succès`, 'success');
                    loadSSIDList();
                } else {
                    showMessage(`❌ ${result.message}`, 'error');
                }
            } catch (error) {
                showMessage(`❌ Erreur: ${error.message}`, 'error');
            }
        }
        
        async function loadSSIDList() {
            // Recharger la page pour mettre à jour la liste
            window.location.reload();
        }
        
        function showMessage(message, type = 'info') {
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';

            const messageHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            const container = document.getElementById('messages-container');
            container.insertAdjacentHTML('afterbegin', messageHtml);

            // Auto-supprimer après 5 secondes
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
