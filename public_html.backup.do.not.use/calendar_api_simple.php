<?php
/**
 * API simplifiée pour récupérer les données de calendrier de pointage
 */

// Configuration de base
require_once __DIR__ . '/config/database.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Fonction pour envoyer une réponse JSON
function sendResponse($success, $message, $code = 200, $data = null) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Gestion d'erreur globale
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    sendResponse(false, "Erreur PHP: $errstr in $errfile:$errline", 500);
});

try {
    // Vérifier la session
    session_start();
    
    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        sendResponse(false, 'Erreur de connexion à la base de données', 500);
    }
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_calendar_data':
            $employee_id = $_GET['employee_id'] ?? null;
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');
            $status = $_GET['status'] ?? '';
            
            // Construire la requête de base
            $sql = "
                SELECT 
                    tt.*,
                    u.full_name,
                    u.username,
                    DATE(tt.clock_in) as work_date,
                    TIME(tt.clock_in) as start_time,
                    TIME(tt.clock_out) as end_time,
                    HOUR(tt.clock_in) as clock_hour,
                    CASE 
                        WHEN HOUR(tt.clock_in) < 13 THEN 'morning'
                        ELSE 'afternoon'
                    END as period,
                    CASE 
                        WHEN tt.auto_approved = 1 THEN 'auto'
                        WHEN tt.admin_approved = 1 THEN 'approved'
                        ELSE 'pending'
                    END as approval_status
                FROM time_tracking tt
                JOIN users u ON tt.user_id = u.id
                WHERE MONTH(tt.clock_in) = ? AND YEAR(tt.clock_in) = ?
            ";
            
            $params = [$month, $year];
            
            // Filtrer par employé si spécifié
            if ($employee_id) {
                $sql .= " AND tt.user_id = ?";
                $params[] = $employee_id;
            }
            
            // Filtrer par statut si spécifié
            if ($status) {
                switch ($status) {
                    case 'approved':
                        $sql .= " AND tt.admin_approved = 1";
                        break;
                    case 'pending':
                        $sql .= " AND tt.admin_approved = 0 AND tt.status = 'completed'";
                        break;
                    case 'auto':
                        $sql .= " AND tt.auto_approved = 1";
                        break;
                }
            }
            
            $sql .= " ORDER BY tt.clock_in ASC";
            
            $stmt = $shop_pdo->prepare($sql);
            $stmt->execute($params);
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organiser les données par jour
            $calendar_data = [];
            foreach ($entries as $entry) {
                $date = $entry['work_date'];
                if (!isset($calendar_data[$date])) {
                    $calendar_data[$date] = [];
                }
                
                $calendar_data[$date][] = [
                    'id' => $entry['id'],
                    'employee' => $entry['full_name'],
                    'start_time' => $entry['start_time'],
                    'end_time' => $entry['end_time'],
                    'period' => $entry['period'],
                    'approval_status' => $entry['approval_status'],
                    'auto_approved' => $entry['auto_approved'],
                    'admin_approved' => $entry['admin_approved'],
                    'approval_reason' => $entry['approval_reason'],
                    'work_duration' => $entry['work_duration'],
                    'date' => $date
                ];
            }
            
            sendResponse(true, 'Données du calendrier récupérées', 200, [
                'calendar_data' => $calendar_data,
                'month' => $month,
                'year' => $year,
                'total_entries' => count($entries)
            ]);
            break;
            
        case 'get_entry_details':
            $entry_id = $_GET['entry_id'] ?? $_POST['entry_id'] ?? null;
            
            if (!$entry_id) {
                sendResponse(false, 'ID de pointage requis', 400);
            }
            
            // Récupérer les détails complets du pointage
            $sql = "
                SELECT 
                    tt.*,
                    u.full_name,
                    u.username,
                    u.email,
                    u.phone,
                    DATE(tt.clock_in) as work_date,
                    TIME(tt.clock_in) as start_time,
                    TIME(tt.clock_out) as end_time,
                    HOUR(tt.clock_in) as clock_hour,
                    CASE 
                        WHEN HOUR(tt.clock_in) < 13 THEN 'morning'
                        ELSE 'afternoon'
                    END as period,
                    CASE 
                        WHEN tt.auto_approved = 1 THEN 'auto'
                        WHEN tt.admin_approved = 1 THEN 'approved'
                        ELSE 'pending'
                    END as approval_status
                FROM time_tracking tt
                LEFT JOIN users u ON tt.user_id = u.id
                WHERE tt.id = ?
            ";
            
            $stmt = $shop_pdo->prepare($sql);
            $stmt->execute([$entry_id]);
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$entry) {
                sendResponse(false, 'Pointage non trouvé', 404);
            }
            
            // Détection du navigateur
            function getBrowserFromUserAgent($userAgent) {
                if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
                    return 'Google Chrome';
                } elseif (strpos($userAgent, 'Firefox') !== false) {
                    return 'Mozilla Firefox';
                } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
                    return 'Safari';
                } elseif (strpos($userAgent, 'Edg') !== false) {
                    return 'Microsoft Edge';
                } else {
                    return 'Navigateur inconnu';
                }
            }
            
            function getDeviceFromUserAgent($userAgent) {
                if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false) {
                    return 'Mobile';
                } elseif (strpos($userAgent, 'Tablet') !== false || strpos($userAgent, 'iPad') !== false) {
                    return 'Tablette';
                } else {
                    return 'Ordinateur';
                }
            }
            
            // Formater les données pour l'affichage
            $formatted_entry = [
                'id' => $entry['id'],
                'employee' => [
                    'name' => $entry['full_name'] ?: 'Employé inconnu',
                    'username' => $entry['username'],
                    'email' => $entry['email'],
                    'phone' => $entry['phone']
                ],
                'timing' => [
                    'date' => $entry['work_date'],
                    'start_time' => $entry['start_time'],
                    'end_time' => $entry['end_time'],
                    'period' => $entry['period'],
                    'duration' => $entry['work_duration'],
                    'total_hours' => $entry['total_hours'],
                    'status' => $entry['status']
                ],
                'technical' => [
                    'ip_address' => $entry['ip_address'],
                    'user_agent' => $entry['user_agent'],
                    'browser' => getBrowserFromUserAgent($entry['user_agent'] ?: ''),
                    'device' => getDeviceFromUserAgent($entry['user_agent'] ?: '')
                ],
                'location' => [
                    'location_in' => $entry['location_in'],
                    'location_out' => $entry['location_out']
                ],
                'approval' => [
                    'status' => $entry['approval_status'],
                    'auto_approved' => $entry['auto_approved'],
                    'admin_approved' => $entry['admin_approved'],
                    'approval_reason' => $entry['approval_reason'],
                    'admin_notes' => $entry['admin_notes']
                ],
                'timestamps' => [
                    'clock_in' => $entry['clock_in'],
                    'clock_out' => $entry['clock_out'],
                    'created_at' => $entry['created_at'] ?? '',
                    'updated_at' => $entry['updated_at'] ?? ''
                ]
            ];
            
            sendResponse(true, 'Détails du pointage récupérés', 200, [
                'entry' => $formatted_entry
            ]);
            break;
            
        default:
            sendResponse(false, 'Action non reconnue', 400);
    }
    
} catch (Exception $e) {
    sendResponse(false, 'Erreur: ' . $e->getMessage(), 500);
} catch (Error $e) {
    sendResponse(false, 'Erreur fatale: ' . $e->getMessage(), 500);
}
?>
