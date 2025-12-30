<?php
// Page d'export HTML optimisée pour l'impression de planning mensuel
// 1 page par employé avec calendrier mensuel

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Charger la configuration
if (file_exists(BASE_PATH . '/config/config.php')) {
    require_once BASE_PATH . '/config/config.php';
}
if (file_exists(BASE_PATH . '/config/database.php')) {
    require_once BASE_PATH . '/config/database.php';
}
if (file_exists(BASE_PATH . '/includes/functions.php')) {
    require_once BASE_PATH . '/includes/functions.php';
}

// Initialiser la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $shop_pdo = getShopDBConnection();
} catch (Exception $e) {
    die('Erreur de connexion à la base de données');
}

// Récupérer les paramètres
$user_ids = $_GET['user_ids'] ?? [];
$month = $_GET['month'] ?? date('Y-m'); // Format YYYY-MM

if (empty($user_ids)) {
    die('Aucun employé sélectionné');
}

// Calculer le premier et dernier jour du mois
$first_day = new DateTime($month . '-01');
$last_day = clone $first_day;
$last_day->modify('last day of this month');

// Récupérer les infos des utilisateurs sélectionnés
$placeholders = implode(',', array_fill(0, count($user_ids), '?'));
$stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users WHERE id IN ($placeholders)");
$stmt->execute($user_ids);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les plannings pour le mois
$stmt = $shop_pdo->prepare("
    SELECT es.*, u.full_name, u.username
    FROM employee_schedules es
    JOIN users u ON es.user_id = u.id
    WHERE es.user_id IN ($placeholders)
    AND es.schedule_date BETWEEN ? AND ?
    ORDER BY es.schedule_date, es.start_time
");
$params = array_merge($user_ids, [$first_day->format('Y-m-d'), $last_day->format('Y-m-d')]);
$stmt->execute($params);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser par utilisateur et date
$schedulesByUser = [];
foreach ($schedules as $s) {
    $schedulesByUser[$s['user_id']][$s['schedule_date']] = $s;
}

// Nom du mois en français
$months_fr = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$month_name = $months_fr[(int)$first_day->format('n') - 1] . ' ' . $first_day->format('Y');

$shop_name = $_SESSION['shop_name'] ?? 'Entreprise';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning <?php echo $month_name; ?></title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: #333;
        }
        .no-print {
            margin-bottom: 20px;
        }
        @media print {
            .no-print { display: none; }
            .page-break { page-break-after: always; }
            body { padding: 0; }
        }
        .btn {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn-secondary {
            background: #6b7280;
        }
        .employee-page {
            margin-bottom: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3b82f6;
        }
        .header h1 {
            margin: 0 0 5px;
            font-size: 24px;
            color: #1e40af;
        }
        .header h2 {
            margin: 0 0 5px;
            font-size: 18px;
            color: #333;
        }
        .header p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        .calendar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .calendar th {
            background: #3b82f6;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
        }
        .calendar td {
            border: 1px solid #e5e7eb;
            padding: 5px;
            vertical-align: top;
            height: 80px;
            width: 14.28%;
            font-size: 11px;
        }
        .calendar .day-number {
            font-weight: bold;
            font-size: 14px;
            color: #374151;
            margin-bottom: 5px;
        }
        .calendar .other-month {
            background: #f9fafb;
            color: #9ca3af;
        }
        .calendar .schedule-slot {
            background: #22c55e;
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 10px;
            margin-top: 3px;
            display: block;
        }
        .calendar .break-slot {
            background: #f97316;
            color: white;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 9px;
            margin-top: 2px;
            display: block;
        }
        .calendar .schedule-slot.rest { background: #3b82f6; }
        .calendar .schedule-slot.vacation { background: #f59e0b; }
        .calendar .schedule-slot.sick { background: #ef4444; }
        .calendar .schedule-slot.training { background: #8b5cf6; }
        .calendar .weekend {
            background: #fef3c7;
        }
        .legend {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: 11px;
            margin-top: 10px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.print()">🖨️ Imprimer / PDF</button>
        <button class="btn btn-secondary" onclick="window.close()">✖ Fermer</button>
    </div>

<?php foreach ($users as $index => $user): ?>
    <div class="employee-page <?php echo ($index < count($users) - 1) ? 'page-break' : ''; ?>">
        <div class="header">
            <h1><?php echo htmlspecialchars($shop_name); ?></h1>
            <h2>Planning de <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h2>
            <p><?php echo $month_name; ?></p>
        </div>

        <table class="calendar">
            <thead>
                <tr>
                    <th>Lundi</th>
                    <th>Mardi</th>
                    <th>Mercredi</th>
                    <th>Jeudi</th>
                    <th>Vendredi</th>
                    <th>Samedi</th>
                    <th>Dimanche</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Calculer le premier lundi à afficher
                $calStart = clone $first_day;
                $dayOfWeek = (int)$calStart->format('N'); // 1=Lun, 7=Dim
                if ($dayOfWeek > 1) {
                    $calStart->modify('-' . ($dayOfWeek - 1) . ' days');
                }
                
                // Calculer le dernier dimanche à afficher
                $calEnd = clone $last_day;
                $dayOfWeek = (int)$calEnd->format('N');
                if ($dayOfWeek < 7) {
                    $calEnd->modify('+' . (7 - $dayOfWeek) . ' days');
                }
                
                $currentDate = clone $calStart;
                $userSchedules = $schedulesByUser[$user['id']] ?? [];
                
                while ($currentDate <= $calEnd):
                    echo '<tr>';
                    for ($i = 0; $i < 7; $i++):
                        $dateStr = $currentDate->format('Y-m-d');
                        $isCurrentMonth = ($currentDate->format('Y-m') === $month);
                        $isWeekend = in_array((int)$currentDate->format('N'), [6, 7]);
                        
                        $classes = [];
                        if (!$isCurrentMonth) $classes[] = 'other-month';
                        if ($isWeekend) $classes[] = 'weekend';
                        
                        echo '<td class="' . implode(' ', $classes) . '">';
                        echo '<div class="day-number">' . $currentDate->format('j') . '</div>';
                        
                        // Afficher le planning s'il existe
                        if (isset($userSchedules[$dateStr])) {
                            $s = $userSchedules[$dateStr];
                            $typeClass = $s['schedule_type'] ?? 'work';
                            $startTime = substr($s['start_time'], 0, 5);
                            $endTime = substr($s['end_time'], 0, 5);
                            echo '<span class="schedule-slot ' . $typeClass . '">';
                            echo $startTime . ' - ' . $endTime;
                            echo '</span>';
                            
                            // Afficher la pause si elle existe
                            if (!empty($s['break_start']) && !empty($s['break_end'])) {
                                $breakStart = substr($s['break_start'], 0, 5);
                                $breakEnd = substr($s['break_end'], 0, 5);
                                echo '<span class="break-slot">☕ ' . $breakStart . '-' . $breakEnd . '</span>';
                            }
                        }
                        
                        echo '</td>';
                        $currentDate->modify('+1 day');
                    endfor;
                    echo '</tr>';
                endwhile;
                ?>
            </tbody>
        </table>

        <div class="legend">
            <div class="legend-item"><span class="legend-color" style="background:#22c55e;"></span> Travail</div>
            <div class="legend-item"><span class="legend-color" style="background:#3b82f6;"></span> Repos</div>
            <div class="legend-item"><span class="legend-color" style="background:#f59e0b;"></span> Congés</div>
            <div class="legend-item"><span class="legend-color" style="background:#ef4444;"></span> Maladie</div>
            <div class="legend-item"><span class="legend-color" style="background:#8b5cf6;"></span> Formation</div>
        </div>
    </div>
<?php endforeach; ?>

    <script>
        // Auto-print optionnel
        if (window.location.search.includes('autoprint=1')) {
            setTimeout(() => window.print(), 500);
        }
    </script>
</body>
</html>
