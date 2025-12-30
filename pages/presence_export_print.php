<?php
// Page d'export HTML optimisée pour l'impression (PDF via navigateur)
// Ce fichier génère une vue propre sans navigation pour l'impression CTRL+P

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/session_config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Variables d'authentification
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if (!$current_user_id) {
    header('Location: index.php');
    exit;
}

// Obtenir la connexion à la base de données
$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    die('Erreur de connexion à la base de données');
}

// Récupérer les paramètres
$export_user = $_GET['export_user'] ?? $_POST['export_user'] ?? '';
$export_type = $_GET['export_type'] ?? $_POST['export_type'] ?? '';
$export_status = $_GET['export_status'] ?? $_POST['export_status'] ?? '';
$export_date_start = $_GET['export_date_start'] ?? $_POST['export_date_start'] ?? '';
$export_date_end = $_GET['export_date_end'] ?? $_POST['export_date_end'] ?? '';
$report_title = $_GET['report_title'] ?? $_POST['report_title'] ?? 'Rapport des événements de présence';

// Construction de la requête
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
        pe.comment,
        pe.created_at
    FROM presence_events pe
    LEFT JOIN users u ON pe.employee_id = u.id
    LEFT JOIN presence_types pt ON pe.type_id = pt.id
    WHERE 1=1
";

$params = [];

// Filtres
if ($export_user && $export_user !== 'all') {
    $query .= " AND pe.employee_id = ?";
    $params[] = $export_user;
} elseif (!$is_admin && $current_user_id) {
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

// Récupérer le nom du magasin pour l'en-tête
$shop_name = $_SESSION['shop_name'] ?? 'Mon Magasin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($report_title); ?></title>
    <style>
        body { font-family: sans-serif; margin: 20px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0 0; color: #666; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        
        .badge { padding: 3px 6px; border-radius: 4px; color: white; font-weight: bold; font-size: 10px; }
        .status-pending { background-color: #f59e0b; }
        .status-approved { background-color: #10b981; }
        .status-rejected { background-color: #ef4444; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            h1 { font-size: 18px; }
            td, th { font-size: 11px; }
        }
        
        .actions { margin-bottom: 20px; text-align: right; }
        .btn { padding: 8px 15px; background: #3b82f6; color: white; text-decoration: none; border-radius: 4px; display: inline-block; cursor: pointer; }
        .btn-secondary { background: #6b7280; }
    </style>
</head>
<body>

    <div class="actions no-print">
        <a href="#" onclick="window.print()" class="btn">Imprimer / PDF</a>
        <a href="#" onclick="window.close()" class="btn btn-secondary">Fermer</a>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($report_title); ?></h1>
        <p>Magasin : <?php echo htmlspecialchars($shop_name); ?> | Généré le <?php echo date('d/m/Y H:i'); ?></p>
        <p>
            <?php if($export_date_start) echo "Du " . date('d/m/Y', strtotime($export_date_start)); ?>
            <?php if($export_date_end) echo " Au " . date('d/m/Y', strtotime($export_date_end)); ?>
            (Total: <?php echo count($events); ?>)
        </p>
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
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['user_name']); ?></td>
                        <td style="color: <?php echo $event['type_color']; ?>; font-weight:bold;">
                            <?php echo htmlspecialchars($event['type_name']); ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($event['date_start'])); ?></td>
                        <td><?php echo $event['date_end'] ? date('d/m/Y H:i', strtotime($event['date_end'])) : '-'; ?></td>
                        <td>
                        <?php 
                            // Calcul durée (copié de la logique principale)
                            $durationText = '-';
                            $typeName = strtolower($event['type_name'] ?? '');
                            $isAbsence = strpos($typeName, 'absence') !== false || strpos($typeName, 'congé') !== false;
                            
                            if ($isAbsence && $event['date_start'] && $event['date_end']) {
                                try {
                                    $start = new DateTime($event['date_start']);
                                    $end = new DateTime($event['date_end']);
                                    $start->setTime(0,0);
                                    $end->setTime(0,0);
                                    $diff = $start->diff($end);
                                    $days = $diff->days + 1;
                                    $durationText = ($days * 7) . 'h';
                                } catch(Exception $e) {}
                            } elseif ($event['duration_minutes']) {
                                $h = floor($event['duration_minutes'] / 60);
                                $m = $event['duration_minutes'] % 60;
                                $durationText = ($h > 0 ? $h.'h ' : '') . ($m > 0 ? $m.'min' : '');
                            }
                            echo $durationText;
                        ?>
                        </td>
                        <td>
                            <span class="badge status-<?php echo $event['status']; ?>">
                                <?php echo htmlspecialchars($event['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($event['comment']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Aucun événement trouvé.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
        // Auto-print optionnel
        if (window.location.search.includes('autoprint=1')) {
            setTimeout(() => window.print(), 500);
        }
    </script>
</body>
</html>
