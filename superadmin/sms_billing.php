<?php
// Vérification de l'authentification
session_start();
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Configuration de la page
$page_title = 'SMS Billing - GeekBoard SuperAdmin';
$page_heading = 'SMS Billing';
$page_subtitle = 'Gestion de la facturation SMS';
$page_icon = 'fas fa-comment-dollar';

// Inclure le header
require_once('includes/header.php');
require_once('../config/database.php');
require_once('../includes/sms_billing_functions.php');

$pdo = getMainDBConnection();

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_config':
                $sms_extra_price = floatval($_POST['sms_extra_price']);
                $quota_action = $_POST['quota_exceeded_action'];
                $trial_unlimited = isset($_POST['trial_unlimited_sms']) ? 1 : 0;
                $threshold_1 = intval($_POST['alert_threshold_1']);
                $threshold_2 = intval($_POST['alert_threshold_2']);
                $threshold_3 = intval($_POST['alert_threshold_3']);
                
                $stmt = $pdo->prepare("UPDATE sms_billing_config SET 
                    sms_extra_price = ?, quota_exceeded_action = ?, trial_unlimited_sms = ?,
                    alert_threshold_1 = ?, alert_threshold_2 = ?, alert_threshold_3 = ?
                    WHERE id = 1");
                $stmt->execute([$sms_extra_price, $quota_action, $trial_unlimited, $threshold_1, $threshold_2, $threshold_3]);
                $success = "Configuration mise à jour avec succès.";
                break;
                
            case 'add_pack':
                $name = trim($_POST['pack_name']);
                $count = intval($_POST['pack_sms_count']);
                $price = floatval($_POST['pack_price']);
                
                $stmt = $pdo->prepare("INSERT INTO sms_packs (name, sms_count, price) VALUES (?, ?, ?)");
                $stmt->execute([$name, $count, $price]);
                $success = "Pack SMS ajouté avec succès.";
                break;
                
            case 'gift_sms':
                $shopId = intval($_POST['shop_id']);
                $smsCount = intval($_POST['sms_count']);
                $reason = trim($_POST['reason']);
                $adjustmentType = $_POST['adjustment_type'];
                
                addBonusSMS($shopId, $smsCount, $reason, $_SESSION['superadmin_id'], $adjustmentType);
                $success = "$smsCount SMS offerts avec succès.";
                break;
        }
    }
}

// Récupérer les données
$config = getSMSBillingConfig();
$packs = $pdo->query("SELECT * FROM sms_packs WHERE active = 1 ORDER BY sms_count ASC")->fetchAll(PDO::FETCH_ASSOC);

// Stats globales
$globalStats = $pdo->query("
    SELECT 
        COALESCE(SUM(sms_sent_total), 0) as total_sms,
        COALESCE(SUM(sms_extra_billed), 0) as total_extra,
        COALESCE(SUM(extra_cost), 0) as total_revenue
    FROM sms_usage
    WHERE MONTH(period_start) = MONTH(CURRENT_DATE) AND YEAR(period_start) = YEAR(CURRENT_DATE)
")->fetch(PDO::FETCH_ASSOC);

// Shops avec leur consommation
$shops = $pdo->query("
    SELECT s.id, s.name, s.subdomain, sp.name as plan_name, sp.sms_credits,
           COALESCE(u.sms_sent_total, 0) as sms_sent,
           COALESCE(u.sms_extra_billed, 0) as extra_sms,
           COALESCE(u.extra_cost, 0) as extra_cost,
           COALESCE(ss.bonus_sms, 0) as bonus_sms
    FROM shops s
    LEFT JOIN subscriptions sub ON sub.shop_id = s.id AND sub.status = 'active'
    LEFT JOIN subscription_plans sp ON sp.id = sub.plan_id
    LEFT JOIN sms_usage u ON u.shop_id = s.id 
        AND MONTH(u.period_start) = MONTH(CURRENT_DATE) 
        AND YEAR(u.period_start) = YEAR(CURRENT_DATE)
    LEFT JOIN sms_shop_settings ss ON ss.shop_id = s.id
    WHERE s.active = 1
    ORDER BY s.name
")->fetchAll(PDO::FETCH_ASSOC);

$atLimitCount = 0;
foreach ($shops as $shop) {
    if ($shop['sms_credits'] > 0 && $shop['sms_sent'] >= ($shop['sms_credits'] + $shop['bonus_sms'])) {
        $atLimitCount++;
    }
}
?>

<!-- Extra CSS -->
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}
@media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }

.config-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}
@media (max-width: 992px) { .config-grid { grid-template-columns: 1fr; } }

.pack-cards {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.pack-card {
    flex: 1;
    min-width: 150px;
    background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}
.pack-card h4 { margin: 0 0 8px 0; font-size: 1.25rem; }
.pack-card .count { font-size: 2rem; font-weight: 700; }
.pack-card .price { opacity: 0.9; margin-top: 4px; }

.shop-table { width: 100%; border-collapse: collapse; }
.shop-table th, .shop-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--gray-200); }
.shop-table th { background: var(--gray-50); font-weight: 600; color: var(--gray-700); }
.shop-table tr:hover { background: var(--gray-50); }

.progress-bar-mini {
    height: 8px;
    background: var(--gray-200);
    border-radius: 4px;
    overflow: hidden;
    width: 100px;
}
.progress-bar-mini .fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s;
}
.fill-success { background: var(--success-500); }
.fill-warning { background: var(--warning-500); }
.fill-danger { background: var(--danger-500); }
</style>

<?php if (isset($success)): ?>
<div class="alert alert-success mb-4">
    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card animate-slideInUp">
        <div class="stat-card-icon primary"><i class="fas fa-paper-plane"></i></div>
        <div class="stat-card-value"><?= number_format($globalStats['total_sms']) ?></div>
        <div class="stat-card-label">SMS ce mois</div>
    </div>
    
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.1s;">
        <div class="stat-card-icon warning"><i class="fas fa-plus"></i></div>
        <div class="stat-card-value"><?= number_format($globalStats['total_extra']) ?></div>
        <div class="stat-card-label">SMS Extra</div>
    </div>
    
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.2s;">
        <div class="stat-card-icon success"><i class="fas fa-euro-sign"></i></div>
        <div class="stat-card-value"><?= number_format($globalStats['total_revenue'], 2) ?>€</div>
        <div class="stat-card-label">Revenue Extra</div>
    </div>
    
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.3s;">
        <div class="stat-card-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-card-value"><?= $atLimitCount ?></div>
        <div class="stat-card-label">Shops à la limite</div>
    </div>
</div>

<!-- Config + Packs -->
<div class="config-grid">
    <!-- Configuration globale -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold"><i class="fas fa-cog text-primary-600 me-2"></i>Configuration Globale</h3>
        </div>
        <div class="card-body">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_config">
                
                <div class="form-group">
                    <label class="form-label">Prix SMS supplémentaire (€)</label>
                    <input type="number" name="sms_extra_price" class="form-input" 
                           value="<?= $config['sms_extra_price'] ?>" step="0.001" min="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Action si quota dépassé</label>
                    <select name="quota_exceeded_action" class="form-select">
                        <option value="continue_bill" <?= $config['quota_exceeded_action'] == 'continue_bill' ? 'selected' : '' ?>>Continuer et facturer</option>
                        <option value="block" <?= $config['quota_exceeded_action'] == 'block' ? 'selected' : '' ?>>Bloquer les SMS</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="trial_unlimited_sms" <?= $config['trial_unlimited_sms'] ? 'checked' : '' ?>>
                        <span>SMS illimités pendant l'essai</span>
                    </label>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">Alerte 1 (%)</label>
                        <input type="number" name="alert_threshold_1" class="form-input" value="<?= $config['alert_threshold_1'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alerte 2 (%)</label>
                        <input type="number" name="alert_threshold_2" class="form-input" value="<?= $config['alert_threshold_2'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alerte 3 (%)</label>
                        <input type="number" name="alert_threshold_3" class="form-input" value="<?= $config['alert_threshold_3'] ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </form>
        </div>
    </div>
    
    <!-- Packs SMS -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold"><i class="fas fa-box text-success-600 me-2"></i>Packs SMS</h3>
        </div>
        <div class="card-body">
            <div class="pack-cards">
                <?php foreach ($packs as $pack): ?>
                <div class="pack-card">
                    <h4><?= htmlspecialchars($pack['name']) ?></h4>
                    <div class="count"><?= number_format($pack['sms_count']) ?></div>
                    <div class="price"><?= number_format($pack['price'], 2) ?>€</div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" class="border-t pt-4 mt-4">
                <input type="hidden" name="action" value="add_pack">
                <h5 class="font-semibold mb-3">Ajouter un pack</h5>
                <div class="grid grid-cols-3 gap-3">
                    <input type="text" name="pack_name" class="form-input" placeholder="Nom" required>
                    <input type="number" name="pack_sms_count" class="form-input" placeholder="Nb SMS" required>
                    <input type="number" name="pack_price" class="form-input" placeholder="Prix €" step="0.01" required>
                </div>
                <button type="submit" class="btn btn-success mt-3">
                    <i class="fas fa-plus me-2"></i>Ajouter
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Offrir SMS -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="font-semibold"><i class="fas fa-gift text-warning-600 me-2"></i>Offrir des SMS</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="action" value="gift_sms">
            
            <div class="form-group flex-1" style="min-width: 200px;">
                <label class="form-label">Magasin</label>
                <select name="shop_id" class="form-select" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($shops as $shop): ?>
                    <option value="<?= $shop['id'] ?>"><?= htmlspecialchars($shop['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="width: 120px;">
                <label class="form-label">Quantité</label>
                <input type="number" name="sms_count" class="form-input" min="1" required>
            </div>
            
            <div class="form-group" style="width: 150px;">
                <label class="form-label">Type</label>
                <select name="adjustment_type" class="form-select">
                    <option value="bonus">Bonus</option>
                    <option value="commercial">Commercial</option>
                    <option value="compensation">Compensation</option>
                </select>
            </div>
            
            <div class="form-group flex-1" style="min-width: 200px;">
                <label class="form-label">Raison</label>
                <input type="text" name="reason" class="form-input" placeholder="Raison du cadeau">
            </div>
            
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-gift me-2"></i>Offrir
            </button>
        </form>
    </div>
</div>

<!-- Tableau des shops -->
<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="font-semibold"><i class="fas fa-store text-primary-600 me-2"></i>Consommation par Magasin</h3>
        <button class="btn btn-secondary btn-sm" onclick="exportCSV()">
            <i class="fas fa-download me-2"></i>Export CSV
        </button>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <table class="shop-table" id="shopsTable">
            <thead>
                <tr>
                    <th>Magasin</th>
                    <th>Plan</th>
                    <th>Quota</th>
                    <th>Utilisés</th>
                    <th>%</th>
                    <th>Extra</th>
                    <th>Coût Extra</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shops as $shop): 
                    $quota = ($shop['sms_credits'] ?? 0) + ($shop['bonus_sms'] ?? 0);
                    $used = $shop['sms_sent'] ?? 0;
                    $percent = $quota > 0 ? min(100, round(($used / $quota) * 100)) : 0;
                    $isUnlimited = ($shop['sms_credits'] ?? 0) == -1;
                    
                    $fillClass = 'fill-success';
                    if ($percent >= 90) $fillClass = 'fill-danger';
                    elseif ($percent >= 80) $fillClass = 'fill-warning';
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($shop['name']) ?></strong>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($shop['subdomain']) ?>.mdgeek.top</div>
                    </td>
                    <td><?= htmlspecialchars($shop['plan_name'] ?? 'Aucun') ?></td>
                    <td>
                        <?php if ($isUnlimited): ?>
                            <span class="badge badge-success">∞</span>
                        <?php else: ?>
                            <?= number_format($quota) ?>
                            <?php if ($shop['bonus_sms'] > 0): ?>
                                <span class="text-xs text-success-600">(+<?= $shop['bonus_sms'] ?>)</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($used) ?></td>
                    <td>
                        <?php if (!$isUnlimited): ?>
                        <div class="flex items-center gap-2">
                            <div class="progress-bar-mini">
                                <div class="fill <?= $fillClass ?>" style="width: <?= $percent ?>%"></div>
                            </div>
                            <span class="text-sm"><?= $percent ?>%</span>
                        </div>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($shop['extra_sms'] ?? 0) ?></td>
                    <td><?= number_format($shop['extra_cost'] ?? 0, 2) ?>€</td>
                    <td>
                        <button class="btn btn-sm btn-ghost" onclick="quickGift(<?= $shop['id'] ?>)" title="Offrir SMS">
                            <i class="fas fa-gift text-warning-600"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function quickGift(shopId) {
    const count = prompt('Nombre de SMS à offrir:');
    if (count && parseInt(count) > 0) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="gift_sms">
            <input type="hidden" name="shop_id" value="${shopId}">
            <input type="hidden" name="sms_count" value="${count}">
            <input type="hidden" name="adjustment_type" value="bonus">
            <input type="hidden" name="reason" value="Cadeau rapide">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportCSV() {
    const table = document.getElementById('shopsTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        cells.forEach(cell => {
            rowData.push('"' + cell.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sms_billing_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}
</script>

<?php require_once('includes/footer.php'); ?>
