<?php
// Inclure la configuration de la base de données
require_once('config/database.php');
require_once('includes/functions.php');

$shop_pdo = getShopDBConnection();

// Authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

// Paramètres de filtre
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_employee = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

// Récupérer la liste des employés
$employees_query = "SELECT id, full_name, username FROM users WHERE role IN ('admin', 'employee', 'technicien') ORDER BY full_name";
$employees_stmt = $shop_pdo->prepare($employees_query);
$employees_stmt->execute();
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

// Si aucun employé sélectionné, prendre le premier de la liste ou l'utilisateur actuel
if ($selected_employee == 0) {
    $selected_employee = $_SESSION['user_id'];
}

// Récupérer les données de pointage pour la date sélectionnée
$time_tracking_query = "
    SELECT 
        tt.*,
        u.full_name,
        u.username
    FROM time_tracking tt
    JOIN users u ON tt.user_id = u.id
    WHERE DATE(tt.clock_in) = ? AND tt.user_id = ?
    ORDER BY tt.clock_in ASC
";
$time_tracking_stmt = $shop_pdo->prepare($time_tracking_query);
$time_tracking_stmt->execute([$selected_date, $selected_employee]);
$time_tracking_data = $time_tracking_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les logs de réparation pour la date sélectionnée
$repair_logs_query = "
    SELECT 
        rl.*,
        r.reference,
        r.type_appareil,
        r.modele,
        CONCAT(c.nom, ' ', c.prenom) as client_nom,
        u.full_name as employe_nom
    FROM reparation_logs rl
    JOIN reparations r ON rl.reparation_id = r.id
    JOIN clients c ON r.client_id = c.id
    JOIN users u ON rl.employe_id = u.id
    WHERE DATE(rl.date_action) = ? 
    AND rl.employe_id = ?
    AND rl.action_type IN ('demarrage', 'terminer')
    ORDER BY rl.date_action ASC
";
$repair_logs_stmt = $shop_pdo->prepare($repair_logs_query);
$repair_logs_stmt->execute([$selected_date, $selected_employee]);
$repair_logs_data = $repair_logs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour convertir une heure en minutes depuis minuit
function timeToMinutes($time) {
    $dt = new DateTime($time);
    return $dt->format('H') * 60 + $dt->format('i');
}

// Fonction pour formater l'heure
function formatTime($datetime) {
    return date('H:i', strtotime($datetime));
}

// Créer un tableau combiné des événements
$events = [];

// Ajouter les événements de pointage
foreach ($time_tracking_data as $tt) {
    $events[] = [
        'type' => 'clock_in',
        'time' => $tt['clock_in'],
        'title' => 'Arrivée',
        'data' => $tt
    ];
    
    if ($tt['break_start']) {
        $events[] = [
            'type' => 'break_start',
            'time' => $tt['break_start'],
            'title' => 'Pause début',
            'data' => $tt
        ];
    }
    
    if ($tt['break_end']) {
        $events[] = [
            'type' => 'break_end',
            'time' => $tt['break_end'],
            'title' => 'Pause fin',
            'data' => $tt
        ];
    }
    
    if ($tt['clock_out']) {
        $events[] = [
            'type' => 'clock_out',
            'time' => $tt['clock_out'],
            'title' => 'Sortie',
            'data' => $tt
        ];
    }
}

// Ajouter les événements de réparation
foreach ($repair_logs_data as $rl) {
    $events[] = [
        'type' => $rl['action_type'] == 'demarrage' ? 'repair_start' : 'repair_end',
        'time' => $rl['date_action'],
        'title' => $rl['action_type'] == 'demarrage' ? 'Réparation ' . $rl['reference'] : 'Fin ' . $rl['reference'],
        'data' => $rl
    ];
}

// Trier les événements par heure
usort($events, function($a, $b) {
    return strtotime($a['time']) - strtotime($b['time']);
});

// Obtenir l'employé sélectionné
$selected_employee_data = null;
foreach ($employees as $emp) {
    if ($emp['id'] == $selected_employee) {
        $selected_employee_data = $emp;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frise Chronologique - <?php echo $selected_employee_data['full_name'] ?? 'Employé'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        
        .timeline-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin: 20px 0;
        }
        
        .timeline-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }
        
        .timeline-row {
            position: relative;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin: 10px 0;
            padding: 10px;
            min-height: 80px;
            display: flex;
            align-items: center;
        }
        
        .timeline-track {
            position: relative;
            height: 60px;
            background: #ffffff;
            border-radius: 8px;
            margin: 15px 0;
            border: 2px solid #e9ecef;
            overflow: visible;
            padding: 5px;
        }
        
        .timeline-hours {
            position: absolute;
            top: -30px;
            left: 0;
            right: 0;
            height: 25px;
            display: flex;
        }
        
        .hour-mark {
            flex: 1;
            font-size: 11px;
            text-align: center;
            color: #495057;
            border-left: 1px solid #dee2e6;
            padding-top: 2px;
            font-weight: 500;
        }
        
        .timeline-event {
            position: absolute;
            top: 5px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            color: white;
            padding: 0 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            cursor: pointer;
            z-index: 10;
            border: 2px solid rgba(255,255,255,0.3);
            min-width: 80px;
        }
        
        .event-clock-in { 
            background: linear-gradient(135deg, #28a745, #34ce57); 
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }
        .event-clock-out { 
            background: linear-gradient(135deg, #dc3545, #e85d75); 
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }
        .event-break-start { 
            background: linear-gradient(135deg, #ffc107, #ffcd39); 
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
            color: #212529 !important;
        }
        .event-break-end { 
            background: linear-gradient(135deg, #17a2b8, #3dd5f3); 
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
        }
        .event-repair-start { 
            background: linear-gradient(135deg, #007bff, #4dabf7); 
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }
        .event-repair-end { 
            background: linear-gradient(135deg, #6c757d, #8d959f); 
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }
        
        .work-period {
            position: absolute;
            top: 5px;
            height: 30px;
            background: linear-gradient(90deg, rgba(40, 167, 69, 0.3), rgba(32, 201, 151, 0.3));
            border-radius: 15px;
            border: 2px solid #28a745;
        }
        
        .break-period {
            position: absolute;
            top: 5px;
            height: 30px;
            background: linear-gradient(90deg, rgba(255, 193, 7, 0.3), rgba(253, 126, 20, 0.3));
            border-radius: 15px;
            border: 2px solid #ffc107;
        }
        
        .employee-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .filters {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .tooltip-custom {
            position: absolute;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
            display: none;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="filters">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="employee_select" class="form-label">
                                <i class="fas fa-user me-1"></i> Employé
                            </label>
                            <select id="employee_select" class="form-select">
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $emp['id'] == $selected_employee ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['full_name'] ?: $emp['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="date_select" class="form-label">
                                <i class="fas fa-calendar me-1"></i> Date
                            </label>
                            <input type="date" id="date_select" class="form-control" value="<?php echo $selected_date; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary" onclick="updateTimeline()">
                                <i class="fas fa-search me-1"></i> Afficher
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($selected_employee_data): ?>
                <div class="employee-info">
                    <h3><i class="fas fa-user-clock me-2"></i><?php echo htmlspecialchars($selected_employee_data['full_name'] ?: $selected_employee_data['username']); ?></h3>
                    <p class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        <?php echo date('d/m/Y', strtotime($selected_date)); ?>
                        <?php
                        $day_name = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                        echo ' - ' . $day_name[date('w', strtotime($selected_date))];
                        ?>
                    </p>
                </div>
                <?php endif; ?>

                <div class="timeline-container">
                    <div class="timeline-header">
                        <h4><i class="fas fa-timeline me-2"></i>Frise Chronologique</h4>
                        <small>Pointages et réparations de la journée</small>
                    </div>

                    <div class="timeline-track">
                        <div class="timeline-hours">
                            <?php for ($h = 7; $h <= 19; $h++): ?>
                                <div class="hour-mark"><?php echo sprintf('%02d:00', $h); ?></div>
                            <?php endfor; ?>
                        </div>

                        <?php
                        // Calculer les périodes de travail et de pause
                        $work_periods = [];
                        $break_periods = [];
                        
                        foreach ($time_tracking_data as $tt) {
                            if ($tt['clock_in'] && $tt['clock_out']) {
                                $start_minutes = timeToMinutes($tt['clock_in']);
                                $end_minutes = timeToMinutes($tt['clock_out']);
                                
                                // Limiter à la plage horaire 7h-19h (420-1140 minutes)
                                $start_minutes = max(420, $start_minutes); // 7h = 420 min
                                $end_minutes = min(1140, $end_minutes);    // 19h = 1140 min
                                
                                if ($start_minutes < $end_minutes) {
                                    $work_periods[] = [
                                        'start' => $start_minutes,
                                        'end' => $end_minutes
                                    ];
                                }
                            }
                            
                            if ($tt['break_start'] && $tt['break_end']) {
                                $break_start_minutes = timeToMinutes($tt['break_start']);
                                $break_end_minutes = timeToMinutes($tt['break_end']);
                                
                                // Limiter à la plage horaire 7h-19h
                                $break_start_minutes = max(420, $break_start_minutes);
                                $break_end_minutes = min(1140, $break_end_minutes);
                                
                                if ($break_start_minutes < $break_end_minutes) {
                                    $break_periods[] = [
                                        'start' => $break_start_minutes,
                                        'end' => $break_end_minutes
                                    ];
                                }
                            }
                        }
                        
                        // Afficher les périodes de travail
                        foreach ($work_periods as $period) {
                            $left_percent = (($period['start'] - 420) / 720) * 100; // 720 = 12 heures (7h-19h)
                            $width_percent = (($period['end'] - $period['start']) / 720) * 100;
                            ?>
                            <div class="work-period" style="left: <?php echo $left_percent; ?>%; width: <?php echo $width_percent; ?>%;"></div>
                            <?php
                        }
                        
                        // Afficher les périodes de pause
                        foreach ($break_periods as $period) {
                            $left_percent = (($period['start'] - 420) / 720) * 100;
                            $width_percent = (($period['end'] - $period['start']) / 720) * 100;
                            ?>
                            <div class="break-period" style="left: <?php echo $left_percent; ?>%; width: <?php echo $width_percent; ?>%;"></div>
                            <?php
                        }
                        
                        // Créer des blocs de durée pour les réparations
                        $repair_blocks = [];
                        $temp_repairs = [];
                        
                        foreach ($repair_logs_data as $rl) {
                            if ($rl['action_type'] == 'demarrage') {
                                $temp_repairs[$rl['reparation_id']] = $rl;
                            } elseif ($rl['action_type'] == 'terminer' && isset($temp_repairs[$rl['reparation_id']])) {
                                $start_repair = $temp_repairs[$rl['reparation_id']];
                                $start_minutes = timeToMinutes($start_repair['date_action']);
                                $end_minutes = timeToMinutes($rl['date_action']);
                                
                                if ($start_minutes >= 420 && $end_minutes <= 1140 && $start_minutes < $end_minutes) {
                                    $repair_blocks[] = [
                                        'start' => $start_minutes,
                                        'end' => $end_minutes,
                                        'reference' => $rl['reference'],
                                        'type_appareil' => $rl['type_appareil'],
                                        'client' => $rl['client_nom']
                                    ];
                                }
                                unset($temp_repairs[$rl['reparation_id']]);
                            }
                        }
                        
                        // Afficher les blocs de réparations
                        foreach ($repair_blocks as $block) {
                            $left_percent = (($block['start'] - 420) / 720) * 100;
                            $width_percent = (($block['end'] - $block['start']) / 720) * 100;
                            $duration_minutes = $block['end'] - $block['start'];
                            $duration_text = sprintf('%dh%02d', intval($duration_minutes / 60), $duration_minutes % 60);
                            ?>
                            <div class="timeline-event event-repair-start" 
                                 style="left: <?php echo $left_percent; ?>%; width: <?php echo $width_percent; ?>%; min-width: <?php echo max(80, $width_percent * 8); ?>px;"
                                 data-bs-toggle="tooltip" 
                                 data-bs-placement="top"
                                 title="<?php echo htmlspecialchars($block['reference'] . ' - ' . $block['type_appareil'] . ' (' . $block['client'] . ') - Durée: ' . $duration_text); ?>">
                                <div class="text-center">
                                    <div style="font-size: 11px; font-weight: bold;"><?php echo htmlspecialchars($block['reference']); ?></div>
                                    <div style="font-size: 9px; opacity: 0.9;"><?php echo $duration_text; ?></div>
                                </div>
                            </div>
                            <?php
                        }
                        
                        // Afficher les événements ponctuels (pointages)
                        foreach ($events as $event) {
                            if (in_array($event['type'], ['clock_in', 'clock_out', 'break_start', 'break_end'])) {
                                $event_minutes = timeToMinutes($event['time']);
                                
                                // Ne afficher que les événements dans la plage 7h-19h
                                if ($event_minutes >= 420 && $event_minutes <= 1140) {
                                    $left_percent = (($event_minutes - 420) / 720) * 100;
                                    
                                    $event_class = 'event-' . str_replace('_', '-', $event['type']);
                                    $event_text = formatTime($event['time']);
                                    ?>
                                    <div class="timeline-event <?php echo $event_class; ?>" 
                                         style="left: <?= $left_percent %>%; width: auto;"
                                         data-bs-toggle="tooltip" 
                                         data-bs-placement="top"
                                         title="<?= htmlspecialchars($event['title'] . ' - ' . formatTime($event['time'])) ?>">
                                        <?= htmlspecialchars($event_text) ?>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </div>

                    <?php if (empty($events)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                        <p class="text-muted">Aucune donnée disponible pour cette date et cet employé.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Légende -->
                <div class="timeline-container">
                    <h5><i class="fas fa-info-circle me-2"></i>Légende</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <div class="timeline-event event-clock-in me-2" style="position: relative; transform: none;">Arrivée</div>
                                <span>Pointage d'arrivée</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="timeline-event event-clock-out me-2" style="position: relative; transform: none;">Sortie</div>
                                <span>Pointage de sortie</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="timeline-event event-break-start me-2" style="position: relative; transform: none;">Pause</div>
                                <span>Début de pause</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <div class="timeline-event event-break-end me-2" style="position: relative; transform: none;">Reprise</div>
                                <span>Fin de pause</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="timeline-event event-repair-start me-2" style="position: relative; transform: none;">REP-XXX</div>
                                <span>Démarrage réparation</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="timeline-event event-repair-end me-2" style="position: relative; transform: none;">REP-XXX</div>
                                <span>Arrêt réparation</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialiser les tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        function updateTimeline() {
            const employeeId = document.getElementById('employee_select').value;
            const date = document.getElementById('date_select').value;
            
            // Recharger la page avec les nouveaux paramètres
            window.location.href = `?page=employee_timeline&employee_id=${employeeId}&date=${date}`;
        }

        // Permettre la mise à jour avec Enter
        document.getElementById('date_select').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                updateTimeline();
            }
        });

        document.getElementById('employee_select').addEventListener('change', function() {
            updateTimeline();
        });
    </script>
</body>
</html>
