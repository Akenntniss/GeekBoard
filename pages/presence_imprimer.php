<?php
// Page d'impression des événements de présence
// URL: index.php?page=presence_imprimer

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/session_config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if (!$current_user_id) {
    header('Location: index.php');
    exit;
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
$report_title = $_REQUEST['report_title'] ?? 'Rapport Présence';

// Requête
$query = "
    SELECT 
        pe.id,
        COALESCE(u.full_name, u.username) as user_name,
        pt.name as type_name,
        pt.color as type_color,
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($report_title); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0 0 5px; font-size: 18px; }
        .header p { margin: 0; color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f0f0f0; font-size: 11px; }
        .status-pending { color: #d97706; }
        .status-approved { color: #059669; }
        .status-rejected { color: #dc2626; }
        .no-print { margin-bottom: 15px; }
        @media print { .no-print { display: none !important; } }
        .btn { padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px; }
        .btn-secondary { background: #6b7280; }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.print()">🖨️ Imprimer</button>
        <a href="index.php?page=presence_gestion_moderne" class="btn btn-secondary">← Retour</a>
    </div>
    
    <div class="header">
        <h1><?php echo htmlspecialchars($report_title); ?></h1>
        <p><?php echo htmlspecialchars($shop_name); ?> | Généré le <?php echo date('d/m/Y H:i'); ?> | <?php echo count($events); ?> événement(s)</p>
    </div>
    
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
            <?php if (empty($events)): ?>
                <tr><td colspan="7" style="text-align:center; padding: 20px;">Aucun événement trouvé.</td></tr>
            <?php else: ?>
                <?php foreach ($events as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['user_name']); ?></td>
                    <td style="color: <?php echo $e['type_color'] ?? '#333'; ?>; font-weight: bold;">
                        <?php echo htmlspecialchars($e['type_name']); ?>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($e['date_start'])); ?></td>
                    <td><?php echo $e['date_end'] ? date('d/m/Y H:i', strtotime($e['date_end'])) : '-'; ?></td>
                    <td>
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
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="status-<?php echo $e['status']; ?>">
                        <?php 
                        $labels = ['pending' => 'En attente', 'approved' => 'Approuvé', 'rejected' => 'Rejeté'];
                        echo $labels[$e['status']] ?? $e['status'];
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($e['comment']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
