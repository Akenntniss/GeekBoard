<?php
/**
 * API pour la génération de rapports d'export de pointages
 * Gère les retards selon les créneaux spécifiques et globaux
 */

// Configuration de base
require_once __DIR__ . '/config/database.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session
session_start();

class ExportAPI {
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = getShopDBConnection();
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur de connexion: ' . $e->getMessage(), 500);
        }
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'generate_report':
                    return $this->generateReport();
                default:
                    $this->sendResponse(false, 'Action non reconnue', 400);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), 400);
        }
    }
    
    private function generateReport() {
        try {
            $employee_id = $_GET['employee_id'] ?? null;
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? '';
            $report_type = $_GET['report_type'] ?? 'timesheet';
            
            if (!$start_date || !$end_date) {
                throw new Exception('Dates de début et fin requises');
            }
            
            // Récupérer les créneaux horaires
            $time_slots = $this->getTimeSlots();
            
            switch ($report_type) {
                case 'timesheet':
                    $data = $this->generateTimesheetReport($employee_id, $start_date, $end_date, $time_slots);
                    break;
                case 'late_report':
                    $data = $this->generateLateReport($employee_id, $start_date, $end_date, $time_slots);
                    break;
                case 'overtime_report':
                    $data = $this->generateOvertimeReport($employee_id, $start_date, $end_date, $time_slots);
                    break;
                case 'summary':
                    $data = $this->generateSummaryReport($employee_id, $start_date, $end_date, $time_slots);
                    break;
                default:
                    throw new Exception('Type de rapport non supporté');
            }
            
            $this->sendResponse(true, 'Rapport généré avec succès', 200, $data);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur lors de la génération du rapport: ' . $e->getMessage(), 400);
        }
    }
    
    private function getTimeSlots() {
        // Récupérer uniquement les créneaux spécifiques (plus de créneaux globaux)
        $stmt = $this->pdo->prepare("
            SELECT user_id, slot_type, start_time, end_time 
            FROM time_slots 
            WHERE user_id IS NOT NULL AND is_active = TRUE
        ");
        $stmt->execute();
        $user_slots = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user_slots[$row['user_id']][$row['slot_type']] = $row;
        }
        
        return ['users' => $user_slots];
    }
    
    private function calculateLateness($user_id, $clock_in_time, $time_slots) {
        $clock_in_hour = (int)date('H', strtotime($clock_in_time));
        $clock_in_minutes = date('H:i', strtotime($clock_in_time));
        
        // Déterminer si c'est matin ou après-midi
        $period = $clock_in_hour < 13 ? 'morning' : 'afternoon';
        
        // Utiliser uniquement les créneaux spécifiques (plus de créneaux globaux)
        $applicable_slot = null;
        if (isset($time_slots['users'][$user_id][$period])) {
            $applicable_slot = $time_slots['users'][$user_id][$period];
            $slot_source = 'Spécifique';
        }
        
        if (!$applicable_slot) {
            return [
                'is_late' => false,
                'late_minutes' => 0,
                'expected_time' => 'N/A',
                'slot_type' => 'Aucun créneau',
                'slot_source' => 'N/A'
            ];
        }
        
        $expected_start = $applicable_slot['start_time'];
        $clock_in_timestamp = strtotime($clock_in_time);
        $expected_timestamp = strtotime(date('Y-m-d', $clock_in_timestamp) . ' ' . $expected_start);
        
        $late_seconds = $clock_in_timestamp - $expected_timestamp;
        $late_minutes = max(0, round($late_seconds / 60));
        
        return [
            'is_late' => $late_minutes > 0,
            'late_minutes' => $late_minutes,
            'expected_time' => substr($expected_start, 0, 5),
            'slot_type' => ucfirst($period) . ' (' . $slot_source . ')',
            'slot_source' => $slot_source
        ];
    }
    
    private function generateTimesheetReport($employee_id, $start_date, $end_date, $time_slots) {
        $sql = "
            SELECT 
                tt.*,
                u.full_name as employee_name,
                u.id as user_id,
                DATE(tt.clock_in) as work_date,
                TIME(tt.clock_in) as clock_in_time,
                TIME(tt.clock_out) as clock_out_time
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE DATE(tt.clock_in) BETWEEN ? AND ?
        ";
        
        $params = [$start_date, $end_date];
        
        if ($employee_id) {
            $sql .= " AND tt.user_id = ?";
            $params[] = $employee_id;
        }
        
        $sql .= " ORDER BY u.full_name, tt.clock_in";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser par employé
        $employees = [];
        foreach ($entries as $entry) {
            $employee_name = $entry['employee_name'];
            if (!isset($employees[$employee_name])) {
                $employees[$employee_name] = [
                    'name' => $employee_name,
                    'entries' => [],
                    'total_hours' => 0,
                    'total_days' => 0
                ];
            }
            
            // Calculer le retard
            $lateness = $this->calculateLateness($entry['user_id'], $entry['clock_in'], $time_slots);
            
            $status_text = 'Présent';
            if ($entry['admin_approved'] == 0) {
                $status_text = 'En attente';
            } elseif ($entry['auto_approved'] == 1) {
                $status_text = 'Auto-approuvé';
            }
            
            $employees[$employee_name]['entries'][] = [
                'date' => $entry['work_date'],
                'clock_in' => $entry['clock_in_time'],
                'clock_out' => $entry['clock_out_time'],
                'hours_worked' => $entry['work_duration'] ? number_format($entry['work_duration'], 1) : '0.0',
                'status_text' => $status_text,
                'late_minutes' => $lateness['late_minutes']
            ];
            
            $employees[$employee_name]['total_hours'] += $entry['work_duration'] ?? 0;
            $employees[$employee_name]['total_days']++;
        }
        
        return [
            'type' => 'timesheet',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'employee_name' => $employee_id ? $employees[array_key_first($employees)]['name'] ?? null : null,
            'employees' => array_values($employees)
        ];
    }
    
    private function generateLateReport($employee_id, $start_date, $end_date, $time_slots) {
        $sql = "
            SELECT 
                tt.*,
                u.full_name as employee_name,
                u.id as user_id,
                DATE(tt.clock_in) as work_date,
                TIME(tt.clock_in) as clock_in_time
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE DATE(tt.clock_in) BETWEEN ? AND ?
        ";
        
        $params = [$start_date, $end_date];
        
        if ($employee_id) {
            $sql .= " AND tt.user_id = ?";
            $params[] = $employee_id;
        }
        
        $sql .= " ORDER BY tt.clock_in DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $late_entries = [];
        $total_late_minutes = 0;
        
        foreach ($entries as $entry) {
            $lateness = $this->calculateLateness($entry['user_id'], $entry['clock_in'], $time_slots);
            
            if ($lateness['is_late']) {
                $late_entries[] = [
                    'employee_name' => $entry['employee_name'],
                    'date' => $entry['work_date'],
                    'expected_time' => $lateness['expected_time'],
                    'actual_time' => $entry['clock_in_time'],
                    'late_minutes' => $lateness['late_minutes'],
                    'slot_type' => $lateness['slot_type']
                ];
                
                $total_late_minutes += $lateness['late_minutes'];
            }
        }
        
        $summary = [
            'total_late_entries' => count($late_entries),
            'total_late_minutes' => $total_late_minutes,
            'average_late_minutes' => count($late_entries) > 0 ? round($total_late_minutes / count($late_entries), 1) : 0
        ];
        
        return [
            'type' => 'late_report',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'employee_name' => $employee_id ? ($late_entries[0]['employee_name'] ?? null) : null,
            'late_entries' => $late_entries,
            'summary' => $summary
        ];
    }
    
    private function generateSummaryReport($employee_id, $start_date, $end_date, $time_slots) {
        $sql = "
            SELECT 
                tt.*,
                u.full_name as employee_name,
                u.id as user_id
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE DATE(tt.clock_in) BETWEEN ? AND ?
        ";
        
        $params = [$start_date, $end_date];
        
        if ($employee_id) {
            $sql .= " AND tt.user_id = ?";
            $params[] = $employee_id;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $employees = [];
        $total_hours = 0;
        $total_late_entries = 0;
        
        foreach ($entries as $entry) {
            $employee_name = $entry['employee_name'];
            if (!isset($employees[$employee_name])) {
                $employees[$employee_name] = [
                    'name' => $employee_name,
                    'days_worked' => 0,
                    'total_hours' => 0,
                    'late_count' => 0,
                    'attendance_rate' => 0
                ];
            }
            
            $lateness = $this->calculateLateness($entry['user_id'], $entry['clock_in'], $time_slots);
            
            $employees[$employee_name]['days_worked']++;
            $employees[$employee_name]['total_hours'] += $entry['work_duration'] ?? 0;
            
            if ($lateness['is_late']) {
                $employees[$employee_name]['late_count']++;
                $total_late_entries++;
            }
            
            $total_hours += $entry['work_duration'] ?? 0;
        }
        
        // Calculer les taux de présence
        $work_days = $this->getWorkDaysBetween($start_date, $end_date);
        foreach ($employees as &$employee) {
            $employee['attendance_rate'] = $work_days > 0 ? round(($employee['days_worked'] / $work_days) * 100, 1) : 0;
            $employee['total_hours'] = number_format($employee['total_hours'], 1);
        }
        
        $total_employees = count($employees);
        $punctuality_rate = count($entries) > 0 ? round((1 - ($total_late_entries / count($entries))) * 100, 1) : 100;
        
        $summary = [
            'total_employees' => $total_employees,
            'total_hours' => number_format($total_hours, 1),
            'total_late_entries' => $total_late_entries,
            'punctuality_rate' => $punctuality_rate
        ];
        
        return [
            'type' => 'summary',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'employees' => array_values($employees),
            'summary' => $summary
        ];
    }
    
    private function generateOvertimeReport($employee_id, $start_date, $end_date, $time_slots) {
        // Requête similaire à generateLateReport mais pour les heures supplémentaires
        $sql = "
            SELECT tt.*, u.full_name as employee_name,
                   DATE(tt.clock_in) as work_date,
                   TIME(tt.clock_in) as clock_in_time,
                   TIME(tt.clock_out) as clock_out_time
            FROM time_tracking tt
            LEFT JOIN users u ON tt.user_id = u.id
            WHERE DATE(tt.clock_in) BETWEEN ? AND ? 
                  AND tt.clock_out IS NOT NULL
        ";
        
        $params = [$start_date, $end_date];
        
        if ($employee_id) {
            $sql .= " AND tt.user_id = ?";
            $params[] = $employee_id;
        }
        
        $sql .= " ORDER BY u.full_name, tt.clock_in";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser par employé
        $employees = [];
        foreach ($entries as $entry) {
            $employee_name = $entry['employee_name'];
            if (!isset($employees[$employee_name])) {
                $employees[$employee_name] = [
                    'name' => $employee_name,
                    'entries' => [],
                    'total_overtime_hours' => 0,
                    'overtime_days' => 0
                ];
            }
            
            // Calculer les heures supplémentaires
            $overtime = $this->calculateOvertime($entry['user_id'], $entry['clock_out'], $time_slots);
            
            if ($overtime['overtime_minutes'] > 0) {
                $status_text = 'Heures supplémentaires';
                if ($entry['admin_approved'] == 0) {
                    $status_text = 'En attente';
                } elseif ($entry['auto_approved'] == 1) {
                    $status_text = 'Auto-approuvé';
                }
                
                $employees[$employee_name]['entries'][] = [
                    'date' => $entry['work_date'],
                    'clock_in' => $entry['clock_in_time'],
                    'clock_out' => $entry['clock_out_time'],
                    'hours_worked' => $entry['work_duration'] ? number_format($entry['work_duration'], 1) : '0.0',
                    'status_text' => $status_text,
                    'overtime_minutes' => $overtime['overtime_minutes'],
                    'overtime_hours' => number_format($overtime['overtime_minutes'] / 60, 1),
                    'expected_end' => $overtime['expected_time'],
                    'slot_type' => $overtime['slot_type']
                ];
                
                $employees[$employee_name]['total_overtime_hours'] += $overtime['overtime_minutes'] / 60;
                $employees[$employee_name]['overtime_days']++;
            }
        }
        
        // Générer le HTML
        $html = $this->generateOvertimeHTML($employees, $start_date, $end_date);
        
        return [
            'html' => $html,
            'employee_count' => count($employees),
            'period' => $start_date . ' au ' . $end_date
        ];
    }
    
    private function calculateOvertime($user_id, $clock_out_time, $time_slots) {
        $clock_out_hour = (int)date('H', strtotime($clock_out_time));
        
        // Déterminer si c'est matin ou après-midi
        $period = $clock_out_hour < 13 ? 'morning' : 'afternoon';
        
        // Utiliser uniquement les créneaux spécifiques (plus de créneaux globaux)
        $applicable_slot = null;
        if (isset($time_slots['users'][$user_id][$period])) {
            $applicable_slot = $time_slots['users'][$user_id][$period];
            $slot_source = 'Spécifique';
        }
        
        if (!$applicable_slot) {
            return [
                'overtime_minutes' => 0,
                'expected_time' => 'N/A',
                'slot_type' => 'Aucun créneau'
            ];
        }
        
        $expected_end = $applicable_slot['end_time'];
        $clock_out_timestamp = strtotime($clock_out_time);
        $expected_timestamp = strtotime(date('Y-m-d', $clock_out_timestamp) . ' ' . $expected_end);
        
        $overtime_seconds = $clock_out_timestamp - $expected_timestamp;
        $overtime_minutes = max(0, round($overtime_seconds / 60));
        
        return [
            'overtime_minutes' => $overtime_minutes,
            'expected_time' => substr($expected_end, 0, 5),
            'slot_type' => ucfirst($period) . ' (' . $slot_source . ')'
        ];
    }
    
    private function generateOvertimeHTML($employees, $start_date, $end_date) {
        $html = "
        <div class='report-header'>
            <h2>📊 Rapport des Heures Supplémentaires</h2>
            <h3>Période : " . date('d/m/Y', strtotime($start_date)) . " au " . date('d/m/Y', strtotime($end_date)) . "</h3>
        </div>
        
        <div class='report-summary'>
            <p><strong>Nombre d'employés concernés :</strong> " . count($employees) . "</p>
        </div>";
        
        if (empty($employees)) {
            $html .= "<div class='alert alert-info'>Aucune heure supplémentaire trouvée pour cette période.</div>";
            return $html;
        }
        
        foreach ($employees as $employee) {
            $html .= "
            <div class='employee-section'>
                <h4>👤 {$employee['name']}</h4>
                <p><strong>Total heures supplémentaires :</strong> " . number_format($employee['total_overtime_hours'], 1) . " heures</p>
                <p><strong>Jours avec heures supplémentaires :</strong> {$employee['overtime_days']}</p>
                
                <table class='table table-striped'>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sortie</th>
                            <th>Fin attendue</th>
                            <th>Heures sup.</th>
                            <th>Créneau</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($employee['entries'] as $entry) {
                $overtime_class = $entry['overtime_minutes'] > 60 ? 'text-danger' : 'text-warning';
                $html .= "
                        <tr>
                            <td>" . date('d/m/Y', strtotime($entry['date'])) . "</td>
                            <td>{$entry['clock_out']}</td>
                            <td>{$entry['expected_end']}</td>
                            <td class='$overtime_class'><strong>{$entry['overtime_hours']}h</strong></td>
                            <td>{$entry['slot_type']}</td>
                            <td>{$entry['status_text']}</td>
                        </tr>";
            }
            
            $html .= "
                    </tbody>
                </table>
            </div>";
        }
        
        return $html;
    }
    
    private function getWorkDaysBetween($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($start, $interval, $end->add($interval));
        
        $work_days = 0;
        foreach ($period as $date) {
            $day_of_week = $date->format('N'); // 1 = lundi, 7 = dimanche
            if ($day_of_week >= 1 && $day_of_week <= 5) { // Lundi à vendredi
                $work_days++;
            }
        }
        
        return $work_days;
    }
    
    private function sendResponse($success, $message, $code = 200, $data = null) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// Instancier et traiter la requête
try {
    $api = new ExportAPI();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
