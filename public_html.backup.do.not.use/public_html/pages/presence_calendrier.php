<?php
// Page de calendrier des événements de présence

// Auto-initialisation du système de présence
include_once 'includes/presence_auto_init.php';

// Gestion de la navigation du calendrier
$currentMonth = $_GET['month'] ?? date('Y-m');
$currentDate = new DateTime($currentMonth . '-01');

// Boutons de navigation
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'prev':
            $currentDate->modify('-1 month');
            break;
        case 'next':
            $currentDate->modify('+1 month');
            break;
        case 'today':
            $currentDate = new DateTime();
            break;
    }
    $currentMonth = $currentDate->format('Y-m');
}

// Vérification du rôle admin et utilisateur connecté
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$current_user_id = $_SESSION['user_id'] ?? null;

// Récupération des filtres
$filter_user = $_GET['user'] ?? ($is_admin ? '' : $current_user_id);
$filter_type = $_GET['type'] ?? '';

// Si l'utilisateur n'est pas admin, forcer le filtre sur son propre ID
if (!$is_admin && $current_user_id) {
    $filter_user = $current_user_id;
}

// Données par défaut
$events = [];
$users = [];
$presence_types = [];
$timetracking_entries = [];

// Récupération des données depuis la base
if (isset($shop_pdo)) {
    try {
        // Récupération des événements du mois
        $start_date = $currentDate->format('Y-m-01 00:00:00');
        $end_date = $currentDate->format('Y-m-t 23:59:59');
        
        $query = "
            SELECT pe.*, u.full_name, u.username, pt.name as type_name, pt.color_code as color
            FROM presence_events pe
            JOIN users u ON pe.employee_id = u.id
            LEFT JOIN presence_types pt ON pe.type_id = pt.id
            WHERE pe.date_start >= ? AND pe.date_start <= ?
        ";
        
        $params = [$start_date, $end_date];
        
        if ($filter_user) {
            $query .= " AND pe.employee_id = ?";
            $params[] = $filter_user;
        }
        
        if ($filter_type) {
            $query .= " AND pt.name = ?";
            $params[] = $filter_type;
        }
        
        $query .= " ORDER BY pe.date_start ASC";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupération des pointages de l'utilisateur connecté
        $timetracking_query = "
            SELECT 
                tt.id,
                tt.user_id,
                tt.clock_in,
                tt.clock_out,
                tt.break_start,
                tt.break_end,
                tt.work_duration,
                tt.status,
                tt.admin_approved,
                u.full_name,
                u.username,
                DATE(tt.clock_in) as date_pointage,
                TIME(tt.clock_in) as time_in,
                TIME(tt.clock_out) as time_out
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE DATE(tt.clock_in) >= ? AND DATE(tt.clock_in) <= ?
        ";
        
        $timetracking_params = [$currentDate->format('Y-m-01'), $currentDate->format('Y-m-t')];
        
        // Filtrer par utilisateur connecté si pas admin
        if (!$is_admin && $current_user_id) {
            $timetracking_query .= " AND tt.user_id = ?";
            $timetracking_params[] = $current_user_id;
        } elseif ($filter_user) {
            $timetracking_query .= " AND tt.user_id = ?";
            $timetracking_params[] = $filter_user;
        }
        
        $timetracking_query .= " ORDER BY tt.clock_in DESC";
        
        $stmt = $shop_pdo->prepare($timetracking_query);
        $stmt->execute($timetracking_params);
        $timetracking_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupération des utilisateurs pour le filtre
        $stmt = $shop_pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name, username");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupération des types de présence pour le filtre
        $stmt = $shop_pdo->query("SELECT id, name, color_code as color FROM presence_types ORDER BY name");
        $presence_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
    }
}

// Organiser les événements par jour
$eventsByDay = [];
foreach ($events as $event) {
    $day = date('j', strtotime($event['date_start']));
    if (!isset($eventsByDay[$day])) {
        $eventsByDay[$day] = [];
    }
    $eventsByDay[$day][] = $event;
}

// Organiser les pointages par jour
$timetrackingByDay = [];
foreach ($timetracking_entries as $entry) {
    $day = date('j', strtotime($entry['date_pointage']));
    if (!isset($timetrackingByDay[$day])) {
        $timetrackingByDay[$day] = [];
    }
    $timetrackingByDay[$day][] = $entry;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-calendar me-2"></i>Calendrier des Événements et Pointages</h1>
                <div>
                    <a href="index.php?page=presence_ajouter" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ajouter
                    </a>
                    <a href="index.php?page=presence_gestion" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Liste
                    </a>
                </div>
            </div>

            <!-- Messages d'erreur -->
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-filter me-2"></i>Filtres</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <input type="hidden" name="page" value="presence_calendrier">
                        <input type="hidden" name="month" value="<?php echo $currentMonth; ?>">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Utilisateur</label>
                                <?php if ($is_admin): ?>
                                    <select name="user" class="form-select">
                                        <option value="">Tous les utilisateurs</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" 
                                                    <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <?php
                                    $current_user_name = '';
                                    foreach ($users as $user) {
                                        if ($user['id'] == $current_user_id) {
                                            $current_user_name = $user['full_name'] ?: $user['username'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <input type="hidden" name="user" value="<?php echo $current_user_id; ?>">
                                    <div class="form-control bg-light">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                                        <small class="text-muted ms-2">(Vos événements)</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($presence_types as $type): ?>
                                        <option value="<?php echo $type['name']; ?>" 
                                                <?php echo $filter_type == $type['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Mois</label>
                                <input type="month" name="month" class="form-control" value="<?php echo $currentMonth; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="fas fa-search"></i> Filtrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Navigation du calendrier -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo $currentDate->format('F Y'); ?>
                    </h5>
                    <div class="btn-group">
                        <a href="?page=presence_calendrier&action=prev&month=<?php echo $currentMonth; ?>&user=<?php echo $filter_user; ?>&type=<?php echo $filter_type; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                        <a href="?page=presence_calendrier&action=today&user=<?php echo $filter_user; ?>&type=<?php echo $filter_type; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            Aujourd'hui
                        </a>
                        <a href="?page=presence_calendrier&action=next&month=<?php echo $currentMonth; ?>&user=<?php echo $filter_user; ?>&type=<?php echo $filter_type; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Calendrier -->
                    <div class="table-responsive">
                        <table class="table table-bordered calendar-table">
                            <thead>
                                <tr class="table-dark">
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
                                // Calculs pour le calendrier
                                $firstDay = $currentDate->format('Y-m-01');
                                $lastDay = $currentDate->format('Y-m-t');
                                $startWeek = date('N', strtotime($firstDay)) - 1; // 0 = lundi
                                $daysInMonth = $currentDate->format('t');
                                $today = date('Y-m-d');
                                $currentMonthYear = $currentDate->format('Y-m');
                                
                                $day = 1;
                                for ($week = 0; $week < 6; $week++) {
                                    echo "<tr>";
                                    for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++) {
                                        if (($week == 0 && $dayOfWeek < $startWeek) || $day > $daysInMonth) {
                                            echo "<td class='table-light calendar-empty'></td>";
                                        } else {
                                            $currentDayDate = sprintf('%s-%02d', $currentMonthYear, $day);
                                            $isToday = ($currentDayDate == $today) ? 'calendar-today' : '';
                                            $hasEvents = isset($eventsByDay[$day]) ? 'calendar-has-events' : '';
                                            $hasTimetracking = isset($timetrackingByDay[$day]) ? 'calendar-has-timetracking' : '';
                                            
                                            echo "<td class='calendar-day $isToday $hasEvents $hasTimetracking'>";
                                            echo "<div class='calendar-day-header'>";
                                            echo "<strong>$day</strong>";
                                            
                                            // Compter les événements et pointages
                                            $totalCount = 0;
                                            if (isset($eventsByDay[$day])) {
                                                $totalCount += count($eventsByDay[$day]);
                                            }
                                            if (isset($timetrackingByDay[$day])) {
                                                $totalCount += count($timetrackingByDay[$day]);
                                            }
                                            
                                            if ($totalCount > 0) {
                                                echo "<span class='badge bg-info calendar-count'>$totalCount</span>";
                                            }
                                            echo "</div>";
                                            
                                            echo "<div class='calendar-content'>";
                                            
                                            // Afficher les pointages en premier
                                            if (isset($timetrackingByDay[$day])) {
                                                echo "<div class='calendar-timetracking'>";
                                                foreach ($timetrackingByDay[$day] as $entry) {
                                                    $userName = $entry['full_name'] ?: $entry['username'];
                                                    $timeIn = $entry['time_in'] ? date('H:i', strtotime($entry['time_in'])) : '--:--';
                                                    $timeOut = $entry['time_out'] ? date('H:i', strtotime($entry['time_out'])) : 'En cours';
                                                    $duration = $entry['work_duration'] ? round($entry['work_duration'], 1) . 'h' : '';
                                                    
                                                    // Couleur selon le statut
                                                    $statusColor = '#28a745'; // Vert par défaut
                                                    if ($entry['status'] == 'active') {
                                                        $statusColor = '#007bff'; // Bleu pour actif
                                                    } elseif ($entry['status'] == 'break') {
                                                        $statusColor = '#ffc107'; // Jaune pour pause
                                                    } elseif (!$entry['admin_approved']) {
                                                        $statusColor = '#fd7e14'; // Orange pour non approuvé
                                                    }
                                                    
                                                    $title = "$userName - Pointage: $timeIn → $timeOut";
                                                    if ($duration) $title .= " ($duration)";
                                                    
                                                    echo "<div class='calendar-timetracking-entry' style='background-color: $statusColor;' title='$title'>";
                                                    echo "<div class='timetracking-icon'>🕐</div>";
                                                    echo "<div class='timetracking-times'>";
                                                    echo "<small>$timeIn</small>";
                                                    if ($timeOut != 'En cours') {
                                                        echo "<br><small>$timeOut</small>";
                                                    }
                                                    echo "</div>";
                                                    if ($duration && $timeOut != 'En cours') {
                                                        echo "<div class='timetracking-duration'><tiny>$duration</tiny></div>";
                                                    }
                                                    echo "</div>";
                                                }
                                                echo "</div>";
                                            }
                                            
                                            // Afficher les événements de présence
                                            if (isset($eventsByDay[$day])) {
                                                echo "<div class='calendar-events'>";
                                                foreach ($eventsByDay[$day] as $event) {
                                                    $eventColor = $event['color'] ?? '#6c757d';
                                                    $eventType = $event['type_name'] ?? 'Événement';
                                                    $userName = $event['full_name'] ?: $event['username'];
                                                    
                                                    echo "<div class='calendar-event' style='background-color: $eventColor;' title='$userName - $eventType'>";
                                                    echo "<small>" . substr($eventType, 0, 8) . "</small>";
                                                    if (!$is_admin || count($eventsByDay[$day]) == 1) {
                                                        echo "<br><tiny>" . substr($userName, 0, 10) . "</tiny>";
                                                    }
                                                    echo "</div>";
                                                }
                                                echo "</div>";
                                            }
                                            
                                            echo "</div>"; // calendar-content
                                            echo "</td>";
                                            $day++;
                                        }
                                    }
                                    echo "</tr>";
                                    if ($day > $daysInMonth) break;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Légende -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-info-circle me-2"></i>Légende</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($presence_types as $type): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge me-2" style="background-color: <?php echo $type['color']; ?>;">
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </span>
                                    <span><?php echo ucfirst($type['name']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h6><i class="fas fa-clock me-2"></i>Pointages</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge me-2" style="background-color: #007bff;">🕐 08:30</span>
                                        <small>Pointage actif</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge me-2" style="background-color: #28a745;">🕐 8.5h</span>
                                        <small>Pointage terminé</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge me-2" style="background-color: #ffc107;">🕐 Pause</span>
                                        <small>En pause</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge me-2" style="background-color: #fd7e14;">🕐 Non app.</span>
                                        <small>Non approuvé</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Survolez les éléments pour voir les détails
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Événements : <strong><?php echo count($events); ?></strong> | 
                                Pointages : <strong><?php echo count($timetracking_entries); ?></strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-table {
    table-layout: fixed;
}

.calendar-day {
    height: 120px;
    vertical-align: top;
    padding: 8px;
    position: relative;
}

.calendar-empty {
    height: 120px;
}

.calendar-today {
    background-color: #fff3cd !important;
    border: 2px solid #ffc107;
}

.calendar-has-events {
    background-color: #f8f9fa;
}

.calendar-has-timetracking {
    border-left: 3px solid #007bff;
}

.calendar-day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.calendar-count {
    font-size: 0.7rem;
}

.calendar-content {
    display: flex;
    flex-direction: column;
    gap: 3px;
    max-height: 85px;
    overflow-y: auto;
}

.calendar-timetracking {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-bottom: 3px;
}

.calendar-timetracking-entry {
    display: flex;
    align-items: center;
    padding: 2px 4px;
    border-radius: 4px;
    color: white;
    font-size: 0.7rem;
    line-height: 1.1;
    cursor: pointer;
    transition: opacity 0.2s;
}

.calendar-timetracking-entry:hover {
    opacity: 0.8;
}

.timetracking-icon {
    font-size: 0.6rem;
    margin-right: 3px;
}

.timetracking-times {
    flex: 1;
    text-align: center;
}

.timetracking-duration {
    font-size: 0.6rem;
    margin-left: 3px;
    opacity: 0.9;
}

.calendar-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.calendar-event {
    padding: 2px 4px;
    border-radius: 3px;
    color: white;
    font-size: 0.75rem;
    line-height: 1.2;
    margin-bottom: 1px;
    cursor: pointer;
}

.calendar-event tiny {
    font-size: 0.65rem;
    opacity: 0.9;
}

.table td {
    position: relative;
}

@media (max-width: 768px) {
    .calendar-day {
        height: 80px;
        padding: 4px;
    }
    
    .calendar-event {
        font-size: 0.65rem;
        padding: 1px 2px;
    }
    
    .calendar-events {
        max-height: 50px;
    }
}
</style>

<script>
// Améliorer l'expérience utilisateur avec des tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter des tooltips aux événements
    const events = document.querySelectorAll('.calendar-event');
    events.forEach(event => {
        event.addEventListener('mouseenter', function() {
            // Optionnel : ajouter des effets de survol
            this.style.opacity = '0.8';
        });
        
        event.addEventListener('mouseleave', function() {
            this.style.opacity = '1';
        });
    });
});
</script>