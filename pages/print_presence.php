<?php
// Standalone print page - access directly at /pages/print_presence.php

define('BASE_PATH', dirname(__DIR__));

// 1. Config session (doit être inclus en premier pour gérer le cookie MDGEEK_SESSION)
require_once BASE_PATH . '/config/session_config.php';

// 2. Base de données et fonctions
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// 3. Initialiser la session du magasin (connexion à la DB du shop)
if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if (!$current_user_id) {
    die('<h1>Veuillez vous connecter</h1><a href="/index.php">Connexion</a>');
}

$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    die('Erreur de connexion à la base de données');
}

// Paramètres
$export_user = $_REQUEST['export_user'] ?? '';
$export_type = $_REQUEST['export_type'] ?? '';
$export_status = $_REQUEST['export_status'] ?? '';
$export_date_start = $_REQUEST['export_date_start'] ?? '';
$export_date_end = $_REQUEST['export_date_end'] ?? '';
$report_title = $_REQUEST['report_title'] ?? 'Rapport de Présence';

// Requête
$query = "
    SELECT 
        pe.id,
        COALESCE(u.full_name, u.username) as user_name,
        pt.name as type_name,
        pt.color_code as type_color,
        pe.date_start,
        pe.date_end,
        pe.duration_minutes,
        pe.status,
        pe.comment
    FROM presence_events pe
    LEFT JOIN users u ON pe.employee_id = u.id
    LEFT JOIN presence_types pt ON pe.type_id = pt.id
    WHERE 1=1
";
$params = [];

if ($export_user && $export_user !== 'all') {
    $query .= " AND pe.employee_id = ?";
    $params[] = $export_user;
} elseif (!$is_admin) {
    $query .= " AND pe.employee_id = ?";
    $params[] = $current_user_id;
}

if ($export_type && $export_type !== 'all') {
    $query .= " AND pe.type_id = ?";
    $params[] = $export_type;
}

if ($export_status && $export_status !== 'all') {
    $query .= " AND pe.status = ?";
    $params[] = $export_status;
}

if ($export_date_start) {
    $query .= " AND pe.date_start >= ?";
    $params[] = $export_date_start . ' 00:00:00';
}

if ($export_date_end) {
    $query .= " AND pe.date_start <= ?";
    $params[] = $export_date_end . ' 23:59:59';
}

$query .= " ORDER BY pe.date_start DESC";
$stmt = $shop_pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$shop_name = $_SESSION['shop_name'] ?? 'GeekBoard';

// Statistiques
$total = count($events);
$pending = count(array_filter($events, fn($e) => $e['status'] === 'pending'));
$approved = count(array_filter($events, fn($e) => $e['status'] === 'approved'));
$rejected = count(array_filter($events, fn($e) => $e['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            color: white;
            padding: 30px 40px;
            position: relative;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--success), var(--warning));
        }
        
        .header h1 { 
            font-size: 28px; 
            font-weight: 700; 
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1::before {
            content: '📊';
            font-size: 32px;
        }
        
        .header-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .header-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 25px 40px;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--gray-200);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-total .stat-number { color: var(--primary); }
        .stat-pending .stat-number { color: var(--warning); }
        .stat-approved .stat-number { color: var(--success); }
        .stat-rejected .stat-number { color: var(--danger); }
        
        .content { padding: 30px 40px; }
        
        table { 
            width: 100%; 
            border-collapse: collapse;
            font-size: 14px;
        }
        
        th {
            background: var(--gray-700);
            color: white;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        th:first-child { border-radius: 8px 0 0 0; }
        th:last-child { border-radius: 0 8px 0 0; }
        
        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-700);
        }
        
        tr:nth-child(even) { background: var(--gray-50); }
        tr:hover { background: #eef2ff; }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        
        .type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            color: white;
        }
        
        .duration {
            font-weight: 600;
            color: var(--primary);
        }
        
        .comment {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--gray-500);
            font-size: 13px;
        }
        
        .footer {
            padding: 20px 40px;
            background: var(--gray-100);
            text-align: center;
            font-size: 12px;
            color: var(--gray-500);
            border-top: 1px solid var(--gray-200);
        }
        
        .actions {
            display: flex;
            gap: 12px;
            padding: 20px 40px;
            background: var(--gray-50);
            justify-content: flex-end;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }
        
        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border: 2px solid var(--gray-300);
        }
        
        .btn-secondary:hover {
            background: var(--gray-100);
            border-color: var(--gray-400);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        @media print {
            body { 
                background: white; 
                padding: 0;
            }
            .container { 
                box-shadow: none; 
                border-radius: 0;
            }
            .actions { display: none !important; }
            .stat-card:hover { transform: none; }
            tr:hover { background: inherit; }
            .header { padding: 20px; }
            .content { padding: 20px; }
        }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .header, .content, .actions { padding-left: 20px; padding-right: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="actions no-print">
            <a href="/index.php?page=presence_gestion_moderne" class="btn btn-secondary">← Retour</a>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimer / PDF</button>
        </div>
        
        <div class="header">
            <h1><?php echo htmlspecialchars($report_title); ?></h1>
            <div class="header-meta">
                <span>🏪 <?php echo htmlspecialchars($shop_name); ?></span>
                <span>📅 Généré le <?php echo date('d/m/Y à H:i'); ?></span>
                <?php if ($export_date_start || $export_date_end): ?>
                    <span>📆 Période: <?php 
                        echo $export_date_start ? date('d/m/Y', strtotime($export_date_start)) : 'Début';
                        echo ' → ';
                        echo $export_date_end ? date('d/m/Y', strtotime($export_date_end)) : 'Aujourd\'hui';
                    ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-number"><?php echo $total; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-number"><?php echo $pending; ?></div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-card stat-approved">
                <div class="stat-number"><?php echo $approved; ?></div>
                <div class="stat-label">Approuvés</div>
            </div>
            <div class="stat-card stat-rejected">
                <div class="stat-number"><?php echo $rejected; ?></div>
                <div class="stat-label">Rejetés</div>
            </div>
        </div>
        
        <div class="content">
            <?php if (empty($events)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>Aucun événement trouvé pour les critères sélectionnés.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Type</th>
                            <th>Début</th>
                            <th>Fin</th>
                            <th>Durée</th>
                            <th>Statut</th>
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $e): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($e['user_name']); ?></strong></td>
                            <td>
                                <span class="type-badge" style="background: <?php echo $e['type_color'] ?? '#6b7280'; ?>;">
                                    <?php echo htmlspecialchars($e['type_name']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($e['date_start'])); ?></td>
                            <td><?php echo $e['date_end'] ? date('d/m/Y H:i', strtotime($e['date_end'])) : '—'; ?></td>
                            <td class="duration">
                                <?php
                                $typeName = strtolower($e['type_name'] ?? '');
                                if ((strpos($typeName, 'absence') !== false || strpos($typeName, 'congé') !== false) && $e['date_start'] && $e['date_end']) {
                                    $start = new DateTime($e['date_start']);
                                    $end = new DateTime($e['date_end']);
                                    $start->setTime(0,0);
                                    $end->setTime(0,0);
                                    echo (($start->diff($end)->days + 1) * 7) . 'h';
                                } elseif ($e['duration_minutes']) {
                                    $h = floor($e['duration_minutes'] / 60);
                                    $m = $e['duration_minutes'] % 60;
                                    echo ($h > 0 ? $h.'h ' : '') . ($m > 0 ? $m.'min' : '');
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $statusLabels = [
                                    'pending' => ['En attente', 'badge-pending', '⏳'],
                                    'approved' => ['Approuvé', 'badge-approved', '✅'],
                                    'rejected' => ['Rejeté', 'badge-rejected', '❌']
                                ];
                                $s = $statusLabels[$e['status']] ?? ['Inconnu', 'badge-pending', '❓'];
                                ?>
                                <span class="badge <?php echo $s[1]; ?>">
                                    <?php echo $s[2]; ?> <?php echo $s[0]; ?>
                                </span>
                            </td>
                            <td class="comment" title="<?php echo htmlspecialchars($e['comment']); ?>">
                                <?php echo htmlspecialchars($e['comment'] ?: '—'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            Document généré par SERVO • <?php echo count($events); ?> événement(s) • <?php echo date('Y'); ?>
        </div>
    </div>
</body>
</html>
