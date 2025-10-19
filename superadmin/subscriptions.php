<?php
// Page de gestion des abonnements - Super Admin
session_start();

// Debug minimal: activer les erreurs localement et logger vers un fichier dédié
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/subscriptions_error.log');
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure les classes nécessaires
try {
    require_once('../config/database.php');
} catch (Throwable $e) {
    error_log('SUBS: include database.php failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur config DB');
}
try {
    require_once('../classes/SubscriptionManager.php');
} catch (Throwable $e) {
    error_log('SUBS: include SubscriptionManager.php failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur SubscriptionManager');
}

try {
    $subscriptionManager = new SubscriptionManager();
} catch (Throwable $e) {
    error_log('SUBS: construct SubscriptionManager failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur initialisation SM');
}
try {
    $pdo = getMainDBConnection();
} catch (Throwable $e) {
    error_log('SUBS: getMainDBConnection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur DB principale');
}

// Gestion des actions POST
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $shop_id = $_POST['shop_id'] ?? '';
    
    try {
        switch ($action) {
            case 'activate_manual':
                $duration_type = $_POST['duration_type'] ?? 'months';
                $duration_value = (int)($_POST['duration_value'] ?? 1);
                $notes = $_POST['notes'] ?? 'Activation manuelle - Paiement espèces';
                
                // Calculer la date de fin
                if ($duration_type === 'days') {
                    $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_value} days"));
                } elseif ($duration_type === 'weeks') {
                    $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_value} weeks"));
                } elseif ($duration_type === 'months') {
                    $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_value} months"));
                } else { // years
                    $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_value} years"));
                }
                
                // Activer le shop
                $stmt = $pdo->prepare("
                    UPDATE shops 
                    SET active = 1, 
                        subscription_status = 'active',
                        trial_ends_at = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$end_date, $shop_id]);
                
                // Mettre à jour ou créer l'abonnement
                $stmt = $pdo->prepare("
                    INSERT INTO subscriptions (shop_id, plan_id, status, current_period_start, current_period_end, created_at)
                    VALUES (?, 2, 'active', NOW(), ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    status = 'active',
                    current_period_start = NOW(),
                    current_period_end = VALUES(current_period_end),
                    updated_at = NOW()
                ");
                $stmt->execute([$shop_id, $end_date]);
                
                // Ajouter une note dans l'historique
                $stmt = $pdo->prepare("
                    INSERT INTO payment_transactions (subscription_id, amount, currency, status, description, created_at)
                    SELECT s.id, 0.00, 'EUR', 'succeeded', ?, NOW()
                    FROM subscriptions s WHERE s.shop_id = ? ORDER BY s.id DESC LIMIT 1
                ");
                $stmt->execute([$notes, $shop_id]);
                
                $message = "Shop activé manuellement jusqu'au " . date('d/m/Y H:i', strtotime($end_date));
                break;
                
            case 'deactivate':
                $stmt = $pdo->prepare("
                    UPDATE shops 
                    SET active = 0, 
                        subscription_status = 'cancelled',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$shop_id]);
                
                $stmt = $pdo->prepare("
                    UPDATE subscriptions 
                    SET status = 'cancelled', updated_at = NOW()
                    WHERE shop_id = ? AND status = 'active'
                ");
                $stmt->execute([$shop_id]);
                
                $message = "Shop désactivé avec succès";
                break;
                
            case 'extend_trial':
                $additional_days = (int)($_POST['additional_days'] ?? 7);
                
                $stmt = $pdo->prepare("
                    UPDATE shops 
                    SET trial_ends_at = DATE_ADD(COALESCE(trial_ends_at, NOW()), INTERVAL ? DAY),
                        subscription_status = 'trial',
                        active = 1,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$additional_days, $shop_id]);
                
                $message = "Essai prolongé de {$additional_days} jours";
                break;
                
            default:
                throw new Exception("Action non reconnue");
        }
    } catch (Exception $e) {
        error_log('SUBS POST error: ' . $e->getMessage());
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'error';
    }
}

// Récupérer les informations d'abonnements avec jointures
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.name,
        s.subdomain,
        s.active,
        s.subscription_status,
        s.trial_started_at,
        s.trial_ends_at,
        s.created_at,
        DATEDIFF(s.trial_ends_at, NOW()) as days_remaining,
        so.prenom,
        so.nom,
        so.email,
        so.telephone,
        sub.id as subscription_id,
        sub.current_period_start,
        sub.current_period_end,
        sp.name as plan_name,
        sp.price as plan_price,
        sp.billing_period,
        COUNT(pt.id) as payment_count,
        SUM(CASE WHEN pt.status = 'succeeded' THEN pt.amount ELSE 0 END) as total_paid
    FROM shops s
    LEFT JOIN shop_owners so ON s.id = so.shop_id
    LEFT JOIN subscriptions sub ON s.id = sub.shop_id AND sub.status IN ('trial', 'active')
    LEFT JOIN subscription_plans sp ON sub.plan_id = sp.id
    LEFT JOIN payment_transactions pt ON sub.id = pt.subscription_id
    GROUP BY s.id, s.name, s.subdomain, s.active, s.subscription_status, s.trial_started_at, s.trial_ends_at, s.created_at,
             so.prenom, so.nom, so.email, so.telephone, sub.id, sub.current_period_start, sub.current_period_end, sp.name, sp.price, sp.billing_period
    ORDER BY s.created_at DESC
");
$stmt->execute();
$shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats = [
    'total' => count($shops),
    'trial' => count(array_filter($shops, fn($s) => $s['subscription_status'] === 'trial')),
    'active' => count(array_filter($shops, fn($s) => $s['subscription_status'] === 'active')),
    'expired' => count(array_filter($shops, fn($s) => $s['subscription_status'] === 'expired')),
    'cancelled' => count(array_filter($shops, fn($s) => $s['subscription_status'] === 'cancelled')),
];

// Récupérer les infos du super administrateur connecté
$stmt = $pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();
$page_title = 'Gestion des Abonnements - GeekBoard';
$page_heading = 'Gestion des Abonnements';
$page_subtitle = 'Suivi et gestion des abonnements SERVO';
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

            <!-- Statistiques -->
            <div class="stats-row">
                <div class="row">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon text-primary">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['trial']; ?></div>
                            <div class="stat-label">Essais</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['active']; ?></div>
                            <div class="stat-label">Actifs</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon text-danger">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['expired']; ?></div>
                            <div class="stat-label">Expirés</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon text-secondary">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                            <div class="stat-label">Annulés</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table des abonnements -->
            <div class="subscription-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Boutique</th>
                                <th>Propriétaire</th>
                                <th>Statut</th>
                                <th>Essai/Abonnement</th>
                                <th>Paiements</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shops as $shop): ?>
                                <tr>
                                    <td>
                                        <div class="shop-info"><?php echo htmlspecialchars($shop['name']); ?></div>
                                        <?php if ($shop['subdomain']): ?>
                                            <div class="shop-subdomain"><?php echo htmlspecialchars($shop['subdomain']); ?>.servo.tools</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="shop-owner">
                                            <?php echo htmlspecialchars($shop['prenom'] . ' ' . $shop['nom']); ?>
                                        </div>
                                        <small class="text-muted"><?php echo htmlspecialchars($shop['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $shop['subscription_status']; ?>">
                                            <?php 
                                            switch($shop['subscription_status']) {
                                                case 'trial': echo 'Essai'; break;
                                                case 'active': echo 'Actif'; break;
                                                case 'expired': echo 'Expiré'; break;
                                                case 'cancelled': echo 'Annulé'; break;
                                                default: echo ucfirst($shop['subscription_status']);
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                            <?php if ($shop['subscription_status'] === 'trial'): ?>
                                            <?php if ($shop['trial_ends_at']): ?>
                                                <div>Essai expire le :</div>
                                                <strong><?php echo date('d/m/Y', strtotime($shop['trial_ends_at'])); ?></strong>
                                                <?php if ($shop['days_remaining'] !== null): ?>
                                                    <div class="days-remaining <?php echo $shop['days_remaining'] <= 3 ? 'warning' : 'success'; ?>">
                                                        <?php 
                                                        if ($shop['days_remaining'] < 0) {
                                                            echo 'Expiré depuis ' . abs($shop['days_remaining']) . ' jour(s)';
                                                        } elseif ($shop['days_remaining'] == 0) {
                                                            echo 'Expire aujourd\'hui';
                                                        } else {
                                                            echo $shop['days_remaining'] . ' jour(s) restant(s)';
                                                        }
                                                        ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Dates non définies</span>
                                            <?php endif; ?>
                                        <?php elseif ($shop['subscription_status'] === 'active'): ?>
                                            <?php if ($shop['plan_name']): ?>
                                                <div><strong><?php echo htmlspecialchars($shop['plan_name']); ?></strong></div>
                                                <div><?php echo number_format($shop['plan_price'], 2); ?>€/<?php echo $shop['billing_period'] === 'yearly' ? 'an' : 'mois'; ?></div>
                                                <?php if ($shop['current_period_end']): ?>
                                                    <small>Expire le <?php echo date('d/m/Y', strtotime($shop['current_period_end'])); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-success">Actif (manuel)</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><strong><?php echo $shop['payment_count']; ?></strong> paiement(s)</div>
                                        <div class="text-success"><?php echo number_format($shop['total_paid'], 2); ?>€ reçus</div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button class="btn btn-action btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#manageModal"
                                                    data-shop-id="<?php echo $shop['id']; ?>"
                                                    data-shop-name="<?php echo htmlspecialchars($shop['name']); ?>"
                                                    data-subdomain="<?php echo htmlspecialchars($shop['subdomain']); ?>"
                                                    data-status="<?php echo htmlspecialchars($shop['subscription_status']); ?>"
                                                    data-trial-ends="<?php echo htmlspecialchars($shop['trial_ends_at']); ?>"
                                                    data-period-end="<?php echo htmlspecialchars($shop['current_period_end']); ?>"
                                                    data-plan-name="<?php echo htmlspecialchars($shop['plan_name']); ?>"
                                                    data-payments="<?php echo (int)$shop['payment_count']; ?>"
                                                    data-total-paid="<?php echo number_format($shop['total_paid'] ?? 0, 2); ?>">
                                                <i class="fas fa-sliders-h"></i>Gérer
                                            </button>
                                            <?php if ($shop['subscription_status'] === 'expired' || $shop['subscription_status'] === 'cancelled' || !$shop['active']): ?>
                                                <button class="btn btn-action btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#activateModal"
                                                        data-shop-id="<?php echo $shop['id']; ?>"
                                                        data-shop-name="<?php echo htmlspecialchars($shop['name']); ?>">
                                                    <i class="fas fa-play"></i>Activer
                                                </button>
                                            <?php else: ?>
                                                <?php if ($shop['subscription_status'] === 'trial'): ?>
                                                    <button class="btn btn-warning btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#extendTrialModal"
                                                            data-shop-id="<?php echo $shop['id']; ?>"
                                                            data-shop-name="<?php echo htmlspecialchars($shop['name']); ?>">
                                                        <i class="fas fa-clock"></i>Prolonger
                                                    </button>
                                                <?php endif; ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="shop_id" value="<?php echo $shop['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Désactiver ce shop ?');">
                                                        <i class="fas fa-stop"></i>Désactiver
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($shop['subdomain']): ?>
                                                <a href="https://<?php echo htmlspecialchars($shop['subdomain']); ?>.servo.tools" 
                                                   target="_blank" class="btn btn-action btn-sm">
                                                    <i class="fas fa-external-link-alt"></i>Visiter
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <!-- Modal Activation Manuelle -->
    <div class="modal fade" id="activateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-play me-2"></i>Activation Manuelle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="activate_manual">
                        <input type="hidden" name="shop_id" id="activate_shop_id">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Activation pour boutique : <strong id="activate_shop_name"></strong>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Durée d'activation</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="duration_value" min="1" value="1" required>
                                </div>
                                <div class="col-6">
                                    <select class="form-select" name="duration_type" required>
                                        <option value="days">Jour(s)</option>
                                        <option value="weeks">Semaine(s)</option>
                                        <option value="months" selected>Mois</option>
                                        <option value="years">Année(s)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes (optionnel)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Ex: Paiement espèces reçu le...">Activation manuelle - Paiement espèces</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Activer le Shop
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Prolonger Essai -->
    <div class="modal fade" id="extendTrialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clock me-2"></i>Prolonger l'Essai</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="extend_trial">
                        <input type="hidden" name="shop_id" id="extend_shop_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i>
                            Prolonger l'essai pour : <strong id="extend_shop_name"></strong>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre de jours supplémentaires</label>
                            <select class="form-select" name="additional_days" required>
                                <option value="3">3 jours</option>
                                <option value="7" selected>7 jours (1 semaine)</option>
                                <option value="14">14 jours (2 semaines)</option>
                                <option value="30">30 jours (1 mois)</option>
                                <option value="60">60 jours (2 mois)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-clock me-2"></i>Prolonger l'Essai
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Gestion Globale -->
    <div class="modal fade" id="manageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-sliders-h me-2"></i>Gestion de l'abonnement — <span id="m_shop_name"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card p-3">
                                <div class="small text-muted">Sous-domaine</div>
                                <div><strong id="m_subdomain">-</strong></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3">
                                <div class="small text-muted">Statut</div>
                                <div><span id="m_status_badge" class="status-badge">-</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3">
                                <div class="small text-muted">Plan actuel</div>
                                <div><strong id="m_plan">-</strong></div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card p-3">
                                <div class="small text-muted">Fin d'essai</div>
                                <div id="m_trial">-</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card p-3">
                                <div class="small text-muted">Fin de période</div>
                                <div id="m_period">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card p-3 h-100">
                                <h6 class="fw-bold mb-3"><i class="fas fa-play me-2 text-primary"></i>Activation manuelle (paiement espèces)</h6>
                                <form method="post" id="m_activate_form">
                                    <input type="hidden" name="action" value="activate_manual">
                                    <input type="hidden" name="shop_id" id="m_activate_shop_id">
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label">Durée</label>
                                            <input type="number" class="form-control" name="duration_value" min="1" value="1" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Unité</label>
                                            <select class="form-select" name="duration_type" required>
                                                <option value="days">Jour(s)</option>
                                                <option value="weeks">Semaine(s)</option>
                                                <option value="months" selected>Mois</option>
                                                <option value="years">Année(s)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="2">Activation manuelle — Paiement espèces</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-check me-2"></i>Activer</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card p-3 h-100">
                                <h6 class="fw-bold mb-3"><i class="fas fa-clock me-2 text-warning"></i>Prolonger l'essai</h6>
                                <form method="post" id="m_extend_form">
                                    <input type="hidden" name="action" value="extend_trial">
                                    <input type="hidden" name="shop_id" id="m_extend_shop_id">
                                    <div class="mb-3">
                                        <label class="form-label">Durée supplémentaire</label>
                                        <select class="form-select" name="additional_days" required>
                                            <option value="3">3 jours</option>
                                            <option value="7" selected>7 jours</option>
                                            <option value="14">14 jours</option>
                                            <option value="30">30 jours</option>
                                            <option value="60">60 jours</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning w-100"><i class="fas fa-clock me-2"></i>Prolonger</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-12">
                            <div class="card p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><i class="fas fa-stop me-2 text-danger"></i>Désactiver l'abonnement</h6>
                                        <small class="text-muted">Met le shop en inactif et annule l'abonnement en cours</small>
                                    </div>
                                    <form method="post" id="m_deactivate_form" onsubmit="return confirm('Désactiver ce shop ?');">
                                        <input type="hidden" name="action" value="deactivate">
                                        <input type="hidden" name="shop_id" id="m_deactivate_shop_id">
                                        <button type="submit" class="btn btn-danger"><i class="fas fa-stop me-2"></i>Désactiver</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestion des modals
        document.addEventListener('DOMContentLoaded', function() {
            const activateModal = document.getElementById('activateModal');
            const extendTrialModal = document.getElementById('extendTrialModal');
            const manageModal = document.getElementById('manageModal');
            
            activateModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const shopId = button.getAttribute('data-shop-id');
                const shopName = button.getAttribute('data-shop-name');
                
                document.getElementById('activate_shop_id').value = shopId;
                document.getElementById('activate_shop_name').textContent = shopName;
            });
            
            extendTrialModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const shopId = button.getAttribute('data-shop-id');
                const shopName = button.getAttribute('data-shop-name');
                
                document.getElementById('extend_shop_id').value = shopId;
                document.getElementById('extend_shop_name').textContent = shopName;
            });

            manageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const shopId = button.getAttribute('data-shop-id') || '';
                const shopName = button.getAttribute('data-shop-name') || '';
                const subdomain = button.getAttribute('data-subdomain') || '';
                const status = button.getAttribute('data-status') || '';
                const trialEnds = button.getAttribute('data-trial-ends') || '';
                const periodEnd = button.getAttribute('data-period-end') || '';
                const planName = button.getAttribute('data-plan-name') || '';
                const payments = button.getAttribute('data-payments') || '0';
                const totalPaid = button.getAttribute('data-total-paid') || '0.00';

                // Remplir les champs
                document.getElementById('m_shop_name').textContent = shopName;
                document.getElementById('m_subdomain').textContent = subdomain ? (subdomain + '.servo.tools') : '-';
                document.getElementById('m_plan').textContent = planName || '—';
                document.getElementById('m_trial').textContent = trialEnds ? new Date(trialEnds).toLocaleDateString('fr-FR') : '—';
                document.getElementById('m_period').textContent = periodEnd ? new Date(periodEnd).toLocaleDateString('fr-FR') : '—';

                const badge = document.getElementById('m_status_badge');
                badge.textContent = status ? status.charAt(0).toUpperCase() + status.slice(1) : '—';
                badge.className = 'status-badge ' + (status ? ('status-' + status) : '');

                // Affecter les shop_id aux formulaires
                document.getElementById('m_activate_shop_id').value = shopId;
                document.getElementById('m_extend_shop_id').value = shopId;
                document.getElementById('m_deactivate_shop_id').value = shopId;

                // Afficher/cacher sections selon statut
                const activateCard = document.getElementById('m_activate_form').closest('.card');
                const extendCard = document.getElementById('m_extend_form').closest('.card');

                if (status === 'trial') {
                    activateCard.style.opacity = '1';
                    activateCard.style.pointerEvents = 'auto';
                    extendCard.style.opacity = '1';
                    extendCard.style.pointerEvents = 'auto';
                } else if (status === 'active') {
                    // Activation manuelle utile pour réactiver après cash? On laisse actif
                    activateCard.style.opacity = '1';
                    activateCard.style.pointerEvents = 'auto';
                    // Prolongation d'essai non pertinente
                    extendCard.style.opacity = '0.5';
                    extendCard.style.pointerEvents = 'none';
                } else {
                    // expired/cancelled
                    activateCard.style.opacity = '1';
                    activateCard.style.pointerEvents = 'auto';
                    extendCard.style.opacity = '0.5';
                    extendCard.style.pointerEvents = 'none';
                }
            });
        });
    </script>
<?php include __DIR__ . '/includes/footer.php'; ?>
