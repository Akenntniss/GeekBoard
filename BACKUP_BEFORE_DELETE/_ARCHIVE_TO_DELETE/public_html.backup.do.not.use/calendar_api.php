<?php
/**
 * API pour récupérer les données de calendrier de pointage
 * Compatible avec le système multi-magasins
 */

// Configuration multi-magasins avec détection automatique
// Détecter l'environnement et charger la bonne configuration
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'mdgeek.top') !== false) {
    // Serveur de production
    require_once __DIR__ . '/new_config_server.php';
} else {
    // Environnement local
    require_once __DIR__ . '/new_config.php';
}

// Fonction pour détecter le magasin actuel
function detectCurrentShop() {
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        if (preg_match('/^([^.]+)\.mdgeek\.top$/', $host, $matches)) {
            return $matches[1];
        }
    }
    // Fallback vers session si disponible
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['shop_name'] ?? 'main'; // Par défaut main (base qui existe)
}

// Débuter la session si nécessaire
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Stocker le nom du magasin détecté en session
$detected_shop = detectCurrentShop();
$_SESSION['shop_name'] = $detected_shop;

// Vérification simple de fonctionnement
if (isset($_GET['test'])) {
    header('Content-Type: application/json');
    
    try {
        $test_pdo = getShopDBConnection($detected_shop);
        
        echo json_encode([
            'success' => true,
            'message' => 'API fonctionne correctement',
            'detected_shop' => $detected_shop,
            'host' => $_SERVER['HTTP_HOST'] ?? 'Non défini',
            'database_connection' => $test_pdo ? 'OK' : 'ECHEC',
            'database_name' => 'geekboard_' . $detected_shop,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur API: ' . $e->getMessage(),
            'detected_shop' => $detected_shop,
            'host' => $_SERVER['HTTP_HOST'] ?? 'Non défini',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    exit;
}

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session
session_start();

class CalendarAPI {
    private $pdo;
    private $shop_name;
    
    public function __construct() {
        try {
            $this->shop_name = detectCurrentShop();
            error_log("CalendarAPI: Tentative de connexion au magasin: " . $this->shop_name);
            
            // Obtenir la connexion au magasin spécifique
            $this->pdo = getShopDBConnection($this->shop_name);
            
            if (!$this->pdo) {
                throw new Exception("Impossible de se connecter à la base de données du magasin: " . $this->shop_name);
            }
            
            error_log("CalendarAPI: Connexion réussie au magasin: " . $this->shop_name);
            
        } catch (Exception $e) {
            error_log("CalendarAPI: Erreur de connexion pour le magasin {$this->shop_name}: " . $e->getMessage());
            $this->sendResponse(false, "Erreur de connexion au magasin {$this->shop_name}: " . $e->getMessage(), 500);
        }
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'get_calendar_data':
                    return $this->getCalendarData();
                case 'get_entry_details':
                    return $this->getEntryDetails();
                default:
                    $this->sendResponse(false, 'Action non reconnue', 400);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), 400);
        }
    }
    
    private function getCalendarData() {
        try {
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
            
            $stmt = $this->pdo->prepare($sql);
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
                    'work_duration' => $entry['work_duration']
                ];
            }
            
            $this->sendResponse(true, 'Données du calendrier récupérées', 200, [
                'calendar_data' => $calendar_data,
                'month' => $month,
                'year' => $year,
                'total_entries' => count($entries)
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur lors de la récupération des données: ' . $e->getMessage(), 400);
        }
    }
    
    private function getEntryDetails() {
        try {
            $entry_id = $_GET['entry_id'] ?? $_POST['entry_id'] ?? null;
            
            if (!$entry_id) {
                $this->sendResponse(false, 'ID de pointage requis', 400);
            }
            
            // Récupérer les détails complets du pointage
            $sql = "
                SELECT 
                    tt.*,
                    u.full_name,
                    u.username,
                    u.role,
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
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$entry_id]);
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$entry) {
                $this->sendResponse(false, 'Pointage non trouvé', 404);
            }
            
            // Formater les données pour l'affichage
            $formatted_entry = [
                'id' => $entry['id'],
                'employee' => [
                    'name' => $entry['full_name'] ?: 'Employé inconnu',
                    'username' => $entry['username'],
                    'role' => $entry['role']
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
                    'browser' => $this->getBrowserFromUserAgent($entry['user_agent']),
                    'device' => $this->getDeviceFromUserAgent($entry['user_agent'])
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
                    'created_at' => $entry['created_at'],
                    'updated_at' => $entry['updated_at']
                ]
            ];
            
            $this->sendResponse(true, 'Détails du pointage récupérés', 200, [
                'entry' => $formatted_entry
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur lors de la récupération des détails: ' . $e->getMessage(), 400);
        }
    }
    
    private function getBrowserFromUserAgent($userAgent) {
        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
            return 'Google Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Mozilla Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edg') !== false) {
            return 'Microsoft Edge';
        } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
            return 'Opera';
        } else {
            return 'Navigateur inconnu';
        }
    }
    
    private function getDeviceFromUserAgent($userAgent) {
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'Tablette';
        } else {
            return 'Ordinateur';
        }
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
    $api = new CalendarAPI();
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
