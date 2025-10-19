<?php
/**
 * API d'export compatible système multi-magasin
 * Utilise getShopDBConnection() pour la détection automatique de base de données
 */

// Configuration de base - Utilisation du système existant
require_once __DIR__ . '/config/database.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session
session_start();

// Fonction de réponse
function sendResponse($success, $message, $code = 200, $data = null) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'shop_info' => [
            'shop_id' => $_SESSION['shop_id'] ?? null,
            'shop_name' => $_SESSION['shop_name'] ?? 'Magasin inconnu'
        ]
    ]);
    exit;
}

try {
    dbDebugLog("=== DÉBUT REQUÊTE API EXPORT ===");
    dbDebugLog("Sous-domaine: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini'));
    dbDebugLog("Session shop_id: " . ($_SESSION['shop_id'] ?? 'Non défini'));
    
    // INITIALISER LA SESSION MAGASIN SI NÉCESSAIRE
    initializeShopSession();
    
    // UTILISER LE SYSTÈME MULTI-MAGASIN EXISTANT
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception("Impossible d'établir la connexion à la base de données du magasin");
    }
    
    dbDebugLog("Connexion établie au magasin: " . ($_SESSION['shop_name'] ?? 'Inconnu'));

    // Récupérer les paramètres
    $action = $_GET['action'] ?? '';
    $employee_id = $_GET['employee_id'] ?? null;
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $report_type = $_GET['report_type'] ?? 'timesheet';
    
    if ($action !== 'generate_report') {
        sendResponse(false, 'Action non reconnue', 400);
    }
    
    if (empty($start_date) || empty($end_date)) {
        sendResponse(false, 'Dates de début et fin requises', 400);
    }
    
    dbDebugLog("Génération rapport: type=$report_type, période=$start_date à $end_date");
    
    // Récupérer les créneaux spécifiques uniquement
    $stmt = $pdo->prepare("
        SELECT user_id, slot_type, start_time, end_time 
        FROM time_slots 
        WHERE user_id IS NOT NULL AND is_active = TRUE
    ");
    $stmt->execute();
    $user_slots = [];
    while ($row = $stmt->fetch()) {
        $user_slots[$row['user_id']][$row['slot_type']] = $row;
    }
    
    dbDebugLog("Créneaux trouvés pour " . count($user_slots) . " utilisateurs");
    
    // Générer le rapport selon le type
    switch ($report_type) {
        case 'overtime_report':
            $data = generateOvertimeReport($pdo, $employee_id, $start_date, $end_date, $user_slots);
            break;
        case 'late_report':
            $data = generateLateReport($pdo, $employee_id, $start_date, $end_date, $user_slots);
            break;
        case 'timesheet':
        default:
            $data = generateTimesheetReport($pdo, $employee_id, $start_date, $end_date, $user_slots);
            break;
    }
    
    dbDebugLog("Rapport généré avec succès: " . $data['employee_count'] . " employé(s)");
    sendResponse(true, 'Rapport généré avec succès', 200, $data);
    
} catch (Exception $e) {
    dbDebugLog("Erreur génération rapport: " . $e->getMessage());
    sendResponse(false, 'Erreur: ' . $e->getMessage(), 500);
}

function initializeShopSession() {
    // Inclure le fichier de configuration des sous-domaines
    if (file_exists(__DIR__ . '/config/subdomain_config.php')) {
        require_once __DIR__ . '/config/subdomain_config.php';
    }
    
    // Vérifier si la session est déjà initialisée avec un shop_id
    if (!isset($_SESSION['shop_id'])) {
        dbDebugLog("Aucun shop_id en session, détection automatique...");
        
        // Utiliser la fonction de détection de votre système
        if (function_exists('detectShopFromSubdomain')) {
            $detected_shop_id = detectShopFromSubdomain();
            
            if ($detected_shop_id) {
                dbDebugLog("Magasin détecté automatiquement: $detected_shop_id");
                
                // Récupérer les infos du magasin depuis la base principale
                try {
                    $main_pdo = getMainDBConnection();
                    if ($main_pdo) {
                        $stmt = $main_pdo->prepare("SELECT id, name FROM shops WHERE id = ? AND active = 1");
                        $stmt->execute([$detected_shop_id]);
                        $shop = $stmt->fetch();
                        
                        if ($shop) {
                            $_SESSION['shop_id'] = $shop['id'];
                            $_SESSION['shop_name'] = $shop['name'];
                            dbDebugLog("Session initialisée: {$shop['name']} (ID: {$shop['id']})");
                        }
                    }
                } catch (Exception $e) {
                    dbDebugLog("Erreur récupération infos magasin: " . $e->getMessage());
                }
            } else {
                dbDebugLog("Aucun magasin détecté pour le sous-domaine: " . ($_SERVER['HTTP_HOST'] ?? 'inconnu'));
            }
        } else {
            dbDebugLog("Fonction detectShopFromSubdomain non disponible");
        }
    } else {
        dbDebugLog("Session déjà initialisée avec shop_id: " . $_SESSION['shop_id']);
    }
}

function generateOvertimeReport($pdo, $employee_id, $start_date, $end_date, $user_slots) {
    // Requête pour récupérer les pointages avec sortie
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
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $entries = $stmt->fetchAll();
    
    // Organiser par employé
    $employees = [];
    foreach ($entries as $entry) {
        $employee_name = $entry['employee_name'] ?: 'Employé inconnu';
        if (!isset($employees[$employee_name])) {
            $employees[$employee_name] = [
                'name' => $employee_name,
                'entries' => [],
                'total_overtime_hours' => 0,
                'overtime_days' => 0
            ];
        }
        
        // Calculer les heures supplémentaires
        $overtime = calculateOvertime($entry['user_id'], $entry['clock_out'], $user_slots);
        
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
    $html = generateOvertimeHTML($employees, $start_date, $end_date);
    
    return [
        'html' => $html,
        'employee_count' => count($employees),
        'period' => $start_date . ' au ' . $end_date
    ];
}

function calculateOvertime($user_id, $clock_out_time, $user_slots) {
    $clock_out_hour = (int)date('H', strtotime($clock_out_time));
    
    // Déterminer si c'est matin ou après-midi
    $period = $clock_out_hour < 13 ? 'morning' : 'afternoon';
    
    // Utiliser uniquement les créneaux spécifiques
    if (!isset($user_slots[$user_id][$period])) {
        return [
            'overtime_minutes' => 0,
            'expected_time' => 'N/A',
            'slot_type' => 'Aucun créneau défini'
        ];
    }
    
    $applicable_slot = $user_slots[$user_id][$period];
    $expected_end = $applicable_slot['end_time'];
    $clock_out_timestamp = strtotime($clock_out_time);
    $expected_timestamp = strtotime(date('Y-m-d', $clock_out_timestamp) . ' ' . $expected_end);
    
    $overtime_seconds = $clock_out_timestamp - $expected_timestamp;
    $overtime_minutes = max(0, round($overtime_seconds / 60));
    
    return [
        'overtime_minutes' => $overtime_minutes,
        'expected_time' => substr($expected_end, 0, 5),
        'slot_type' => ucfirst($period) . ' (Spécifique)'
    ];
}

function generateLateReport($pdo, $employee_id, $start_date, $end_date, $user_slots) {
    // Version simplifiée du rapport des retards
    $shop_name = $_SESSION['shop_name'] ?? 'Magasin';
    $html = "<h2>📊 Rapport des Retards - $shop_name</h2><p>Période : $start_date au $end_date</p>";
    $html .= "<div class='alert alert-info'>Rapport des retards disponible uniquement pour les employés avec créneaux spécifiques.</div>";
    
    return [
        'html' => $html,
        'employee_count' => 0,
        'period' => $start_date . ' au ' . $end_date
    ];
}

function generateTimesheetReport($pdo, $employee_id, $start_date, $end_date, $user_slots) {
    // Version simplifiée du rapport de pointages
    $shop_name = $_SESSION['shop_name'] ?? 'Magasin';
    $html = "<h2>📊 Rapport de Pointages - $shop_name</h2><p>Période : $start_date au $end_date</p>";
    $html .= "<div class='alert alert-info'>Rapport de pointages basé sur les créneaux spécifiques.</div>";
    
    return [
        'html' => $html,
        'employee_count' => 0,
        'period' => $start_date . ' au ' . $end_date
    ];
}

function generateOvertimeHTML($employees, $start_date, $end_date) {
    $shop_name = $_SESSION['shop_name'] ?? 'Magasin';
    
    $html = "
    <div class='report-header'>
        <h2>📊 Rapport des Heures Supplémentaires - $shop_name</h2>
        <h3>Période : " . date('d/m/Y', strtotime($start_date)) . " au " . date('d/m/Y', strtotime($end_date)) . "</h3>
        <p><em>Système basé sur les créneaux spécifiques par employé</em></p>
    </div>
    
    <div class='report-summary'>
        <p><strong>Nombre d'employés concernés :</strong> " . count($employees) . "</p>
    </div>";
    
    if (empty($employees)) {
        $html .= "<div class='alert alert-info'>
            <h5>ℹ️ Aucune heure supplémentaire trouvée</h5>
            <p>Raisons possibles :</p>
            <ul>
                <li>Aucun employé n'a travaillé au-delà de ses créneaux durant cette période</li>
                <li>Les employés n'ont pas de créneaux spécifiques configurés</li>
                <li>Période sélectionnée sans pointages</li>
            </ul>
        </div>";
        return $html;
    }
    
    foreach ($employees as $employee) {
        $html .= "
        <div class='employee-section' style='margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px; padding: 1rem;'>
            <h4 style='color: #007bff;'>👤 {$employee['name']}</h4>
            <div style='background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;'>
                <p><strong>Total heures supplémentaires :</strong> <span style='color: #dc3545;'>" . number_format($employee['total_overtime_hours'], 1) . " heures</span></p>
                <p><strong>Jours avec heures supplémentaires :</strong> {$employee['overtime_days']}</p>
            </div>
            
            <table style='width: 100%; border-collapse: collapse; margin-top: 1rem;'>
                <thead>
                    <tr style='background: #007bff; color: white;'>
                        <th style='padding: 8px; border: 1px solid #ddd;'>Date</th>
                        <th style='padding: 8px; border: 1px solid #ddd;'>Sortie</th>
                        <th style='padding: 8px; border: 1px solid #ddd;'>Fin attendue</th>
                        <th style='padding: 8px; border: 1px solid #ddd;'>Heures sup.</th>
                        <th style='padding: 8px; border: 1px solid #ddd;'>Créneau</th>
                        <th style='padding: 8px; border: 1px solid #ddd;'>Statut</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($employee['entries'] as $entry) {
            $overtime_class = $entry['overtime_minutes'] > 60 ? 'color: #dc3545; font-weight: bold;' : 'color: #ffc107; font-weight: bold;';
            $row_style = $entry['overtime_minutes'] > 60 ? 'background: #fff5f5;' : '';
            
            $html .= "
                    <tr style='$row_style'>
                        <td style='padding: 8px; border: 1px solid #ddd;'>" . date('d/m/Y', strtotime($entry['date'])) . "</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$entry['clock_out']}</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$entry['expected_end']}</td>
                        <td style='padding: 8px; border: 1px solid #ddd; $overtime_class'>{$entry['overtime_hours']}h</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$entry['slot_type']}</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$entry['status_text']}</td>
                    </tr>";
        }
        
        $html .= "
                </tbody>
            </table>
        </div>";
    }
    
    $html .= "
    <div style='margin-top: 2rem; padding: 1rem; background: #e7f3ff; border-left: 4px solid #007bff;'>
        <h5>📝 Note importante</h5>
        <p>Ce rapport calcule les heures supplémentaires en se basant uniquement sur les <strong>créneaux spécifiques</strong> configurés pour chaque employé.</p>
        <p>Les employés sans créneaux configurés n'apparaîtront pas dans ce rapport.</p>
        <p><strong>Magasin actuel :</strong> $shop_name</p>
    </div>";
    
    return $html;
}
?>
