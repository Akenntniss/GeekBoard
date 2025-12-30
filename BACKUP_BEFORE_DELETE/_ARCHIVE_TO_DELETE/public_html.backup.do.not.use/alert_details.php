<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    require_once __DIR__ . '/config/database.php';
    if (!function_exists('getShopDBConnection')) {
        throw new Exception('Fonctions DB manquantes');
    }
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Connexion BDD impossible');
    }

    $alert_code = $_POST['alert_code'] ?? $_GET['alert_code'] ?? '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0);
    if (!$alert_code || !$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }

    $settings = [
        'window_days' => 14,
        'lateness_tolerance_min' => 10,
        'overtime_hours_threshold' => 8.5,
        'lunch_max_minutes' => 90,
        'short_session_hours' => 0.5,
        'no_clockout_hours' => 10,
        'early_departure_tolerance_min' => 15,
        'low_lunch_minutes' => 20,
        'night_start' => '21:00:00',
        'night_end' => '06:00:00'
    ];
    $windowStart = date('Y-m-d', strtotime('-' . (int)$settings['window_days'] . ' days'));

    $details = [];
    switch ($alert_code) {
        case 'early_departure_frequent':
            // Agréger par jour: plus petite heure de sortie du jour, fin prévue depuis slots user ou global
            $stmt = $shop_pdo->prepare(
                "SELECT t.date_jour, t.sortie,
                        COALESCE(
                            (SELECT end_time FROM time_slots WHERE user_id = ? AND slot_type = 'afternoon' AND is_active = TRUE LIMIT 1),
                            (SELECT end_time FROM time_slots WHERE user_id IS NULL AND slot_type = 'afternoon' AND is_active = TRUE LIMIT 1)
                        ) AS fin_prevue
                 FROM (
                     SELECT DATE(clock_in) AS date_jour, MIN(TIME(clock_out)) AS sortie
                     FROM time_tracking
                     WHERE DATE(clock_in) >= ?
                       AND user_id = ?
                       AND status = 'completed'
                       AND clock_out IS NOT NULL
                     GROUP BY DATE(clock_in)
                 ) t
                 ORDER BY t.date_jour DESC"
            );
            $stmt->execute([$user_id, $windowStart, $user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tol = (int)$settings['early_departure_tolerance_min'] * 60;
            foreach ($rows as $r) {
                if (!empty($r['fin_prevue']) && !empty($r['sortie'])) {
                    $out = strtotime($r['date_jour'] . ' ' . $r['sortie']);
                    $scheduledEnd = strtotime($r['date_jour'] . ' ' . $r['fin_prevue']);
                    if ($out < ($scheduledEnd - $tol)) {
                        $details[] = [
                            'date' => $r['date_jour'],
                            'heure_sortie' => substr($r['sortie'], 0, 5),
                            'fin_prevue' => substr($r['fin_prevue'], 0, 5)
                        ];
                    }
                }
            }
            break;

        case 'lateness_frequent':
            // Agréger par jour: première entrée du jour, début prévu depuis slots user ou global
            $stmt = $shop_pdo->prepare(
                "SELECT t.date_jour, t.premiere_entree,
                        COALESCE(
                            (SELECT start_time FROM time_slots WHERE user_id = ? AND slot_type = 'morning' AND is_active = TRUE LIMIT 1),
                            (SELECT start_time FROM time_slots WHERE user_id IS NULL AND slot_type = 'morning' AND is_active = TRUE LIMIT 1)
                        ) AS debut_prevu
                 FROM (
                     SELECT DATE(clock_in) AS date_jour, MIN(TIME(clock_in)) AS premiere_entree
                     FROM time_tracking
                     WHERE DATE(clock_in) >= ?
                       AND user_id = ?
                       AND status IN ('completed','active','break')
                     GROUP BY DATE(clock_in)
                 ) t
                 ORDER BY t.date_jour DESC"
            );
            $stmt->execute([$user_id, $windowStart, $user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tol = (int)$settings['lateness_tolerance_min'] * 60;
            foreach ($rows as $r) {
                if (!empty($r['premiere_entree']) && !empty($r['debut_prevu'])) {
                    $firstIn = strtotime($r['date_jour'] . ' ' . $r['premiere_entree']);
                    $scheduled = strtotime($r['date_jour'] . ' ' . $r['debut_prevu']);
                    if ($firstIn > ($scheduled + $tol)) {
                        $details[] = [
                            'date' => $r['date_jour'],
                            'premiere_entree' => substr($r['premiere_entree'], 0, 5),
                            'debut_prevu' => substr($r['debut_prevu'], 0, 5)
                        ];
                    }
                }
            }
            break;

        case 'overtime_frequent':
            $stmt = $shop_pdo->prepare(
                "SELECT DATE(tt.clock_in) as date_jour,
                       ROUND(COALESCE(tt.work_duration, 0), 2) as heures_travail,
                       TIME(tt.clock_in) as entree,
                       TIME(tt.clock_out) as sortie
                FROM time_tracking tt
                WHERE DATE(tt.clock_in) >= ?
                  AND tt.user_id = ?
                  AND tt.status = 'completed'
                  AND COALESCE(tt.work_duration, 0) > ?
                ORDER BY tt.clock_in DESC"
            );
            $stmt->execute([$windowStart, $user_id, (float)$settings['overtime_hours_threshold']]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'lunch_overrun_frequent':
            $stmt = $shop_pdo->prepare(
                "SELECT DATE(tt.clock_in) as date_jour,
                       ROUND(COALESCE(tt.break_duration, 0) * 60, 0) as minutes_pause,
                       TIME(tt.clock_in) as entree,
                       TIME(tt.clock_out) as sortie
                FROM time_tracking tt
                WHERE DATE(tt.clock_in) >= ?
                  AND tt.user_id = ?
                  AND tt.status = 'completed'
                  AND COALESCE(tt.break_duration, 0) > ?
                ORDER BY tt.clock_in DESC"
            );
            $stmt->execute([$windowStart, $user_id, ((int)$settings['lunch_max_minutes'])/60.0]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'short_sessions_frequent':
            $stmt = $shop_pdo->prepare(
                "SELECT DATE(tt.clock_in) as date_jour,
                       ROUND(COALESCE(tt.work_duration, 0), 2) as heures_travail,
                       TIME(tt.clock_in) as entree,
                       TIME(tt.clock_out) as sortie
                FROM time_tracking tt
                WHERE DATE(tt.clock_in) >= ?
                  AND tt.user_id = ?
                  AND tt.status = 'completed'
                  AND COALESCE(tt.work_duration, 0) < ?
                ORDER BY tt.clock_in DESC"
            );
            $stmt->execute([$windowStart, $user_id, (float)$settings['short_session_hours']]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'weekend_work_frequent':
            $stmt = $shop_pdo->prepare(
                "SELECT DATE(tt.clock_in) as date_jour,
                       TIME(tt.clock_in) as entree,
                       TIME(tt.clock_out) as sortie,
                       ROUND(COALESCE(tt.work_duration, 0), 2) as heures_travail,
                       DAYNAME(tt.clock_in) as jour
                FROM time_tracking tt
                WHERE DATE(tt.clock_in) >= ?
                  AND tt.user_id = ?
                  AND tt.status = 'completed'
                  AND DAYOFWEEK(tt.clock_in) IN (1,7)
                ORDER BY tt.clock_in DESC"
            );
            $stmt->execute([$windowStart, $user_id]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'no_clockout_long':
            $stmt = $shop_pdo->prepare(
                "SELECT DATE(tt.clock_in) as date_jour,
                       TIME(tt.clock_in) as entree,
                       TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) as heures_ouvertes,
                       tt.status
                FROM time_tracking tt
                WHERE tt.user_id = ?
                  AND tt.status IN ('active','break')
                ORDER BY tt.clock_in ASC"
            );
            $stmt->execute([$user_id]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'low_lunch_frequent':
            $stmt = $shop_pdo->prepare(
                "SELECT DATE(tt.clock_in) as date_jour,
                       ROUND(COALESCE(tt.break_duration, 0) * 60, 0) as minutes_pause,
                       TIME(tt.clock_in) as entree,
                       TIME(tt.clock_out) as sortie
                FROM time_tracking tt
                WHERE DATE(tt.clock_in) >= ?
                  AND tt.user_id = ?
                  AND tt.status = 'completed'
                  AND COALESCE(tt.break_duration, 0) > 0
                  AND COALESCE(tt.break_duration, 0) < ?
                ORDER BY tt.clock_in DESC"
            );
            $stmt->execute([$windowStart, $user_id, ((int)$settings['low_lunch_minutes'])/60.0]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'night_work_frequent':
            $stmt = $shop_pdo->prepare(
                "SELECT DATE(tt.clock_in) as date_jour,
                       TIME(tt.clock_in) as entree,
                       TIME(tt.clock_out) as sortie,
                       ROUND(COALESCE(tt.work_duration, 0), 2) as heures_travail
                FROM time_tracking tt
                WHERE DATE(tt.clock_in) >= ?
                  AND tt.user_id = ?
                  AND tt.status = 'completed'
                  AND (TIME(tt.clock_in) >= ? OR TIME(tt.clock_in) < ? OR (tt.clock_out IS NOT NULL AND (TIME(tt.clock_out) >= ? OR TIME(tt.clock_out) < ?)))
                ORDER BY tt.clock_in DESC"
            );
            $stmt->execute([$windowStart, $user_id, $settings['night_start'], $settings['night_end'], $settings['night_start'], $settings['night_end']]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }

    echo json_encode(['success' => true, 'details' => $details]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


