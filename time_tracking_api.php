<?php
/**
 * API de gestion des pointages avec TRACKING ANTI-TRICHE MAXIMAL
 * Version renforcée avec géolocalisation et empreinte digitale complète
 */

// Configuration de base - connexion directe pour éviter les dépendances
// require_once __DIR__ . '/config/database.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session
session_start();

class EnhancedTimeTrackingAPI {
    private $pdo;
    private $current_user_id;
    
    public function __construct() {
        try {
            // Connexion directe pour éviter les dépendances
            $this->pdo = new PDO("mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4", 'root', 'Mamanmaman01#', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Essayer de récupérer l'user_id de la session
            $this->current_user_id = $_SESSION['user_id'] ?? null;
            
            // Si pas d'user_id, utiliser un utilisateur par défaut pour les tests
            if (!$this->current_user_id) {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role != 'admin' ORDER BY id LIMIT 1");
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $this->current_user_id = $user['id'];
                } else {
                    // Créer un utilisateur de test si aucun n'existe
                    $stmt = $this->pdo->prepare("
                        INSERT INTO users (username, full_name, password, role) 
                        VALUES ('testuser', 'Utilisateur Test', 'password', 'technicien')
                    ");
                    $stmt->execute();
                    $this->current_user_id = $this->pdo->lastInsertId();
                }
            }
            
            $this->ensureTablesExist();
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur de connexion: ' . $e->getMessage(), 500);
        }
    }
    
    private function ensureTablesExist() {
        try {
            // Créer la table time_slots si elle n'existe pas
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS time_slots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    slot_type ENUM('morning', 'afternoon') NOT NULL,
                    start_time TIME NOT NULL,
                    end_time TIME NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_slot (user_id, slot_type)
                )
            ");
            
            // Insérer les créneaux par défaut
            $this->pdo->exec("
                INSERT IGNORE INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES
                (NULL, 'morning', '08:00:00', '12:30:00', TRUE),
                (NULL, 'afternoon', '14:00:00', '19:00:00', TRUE)
            ");
            
            // Améliorer la table time_tracking avec BEAUCOUP plus de colonnes de tracking
            $this->enhanceTimeTrackingTable();
            
        } catch (Exception $e) {
            // Ignorer les erreurs de création de tables
        }
    }
    
    private function enhanceTimeTrackingTable() {
        $columns = $this->pdo->query("SHOW COLUMNS FROM time_tracking")->fetchAll(PDO::FETCH_COLUMN);
        
        // Colonnes de géolocalisation
        if (!in_array('latitude_in', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN latitude_in DECIMAL(10, 8) NULL");
        }
        if (!in_array('longitude_in', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN longitude_in DECIMAL(11, 8) NULL");
        }
        if (!in_array('latitude_out', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN latitude_out DECIMAL(10, 8) NULL");
        }
        if (!in_array('longitude_out', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN longitude_out DECIMAL(11, 8) NULL");
        }
        if (!in_array('gps_accuracy_in', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN gps_accuracy_in FLOAT NULL");
        }
        if (!in_array('gps_accuracy_out', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN gps_accuracy_out FLOAT NULL");
        }
        if (!in_array('altitude_in', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN altitude_in FLOAT NULL");
        }
        if (!in_array('altitude_out', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN altitude_out FLOAT NULL");
        }
        
        // Informations de l'appareil
        if (!in_array('device_fingerprint', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN device_fingerprint TEXT NULL");
        }
        if (!in_array('screen_resolution', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN screen_resolution VARCHAR(20) NULL");
        }
        if (!in_array('browser_language', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN browser_language VARCHAR(10) NULL");
        }
        if (!in_array('timezone_offset', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN timezone_offset INT NULL");
        }
        if (!in_array('platform', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN platform VARCHAR(50) NULL");
        }
        if (!in_array('cpu_cores', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN cpu_cores INT NULL");
        }
        if (!in_array('memory_gb', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN memory_gb FLOAT NULL");
        }
        
        // Données réseau
        if (!in_array('connection_type', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN connection_type VARCHAR(20) NULL");
        }
        if (!in_array('connection_speed', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN connection_speed VARCHAR(20) NULL");
        }
        if (!in_array('ip_v6', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN ip_v6 VARCHAR(45) NULL");
        }
        
        // Sécurité et détection de fraude
        if (!in_array('battery_level', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN battery_level FLOAT NULL");
        }
        if (!in_array('is_charging', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN is_charging BOOLEAN NULL");
        }
        if (!in_array('device_orientation', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN device_orientation VARCHAR(20) NULL");
        }
        if (!in_array('canvas_fingerprint', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN canvas_fingerprint VARCHAR(255) NULL");
        }
        if (!in_array('webgl_fingerprint', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN webgl_fingerprint VARCHAR(255) NULL");
        }
        if (!in_array('audio_fingerprint', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN audio_fingerprint VARCHAR(255) NULL");
        }
        
        // Horodatage précis
        if (!in_array('client_timestamp', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN client_timestamp TIMESTAMP NULL");
        }
        if (!in_array('server_timestamp', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN server_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
        if (!in_array('processing_time_ms', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN processing_time_ms INT NULL");
        }
        
        // Détection de VPN/Proxy
        if (!in_array('is_vpn_proxy', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN is_vpn_proxy BOOLEAN NULL");
        }
        if (!in_array('isp_name', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN isp_name VARCHAR(100) NULL");
        }
        if (!in_array('country_code', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN country_code VARCHAR(3) NULL");
        }
        if (!in_array('city_name', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN city_name VARCHAR(100) NULL");
        }
        
        // Colonnes existantes améliorées
        if (!in_array('auto_approved', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN auto_approved BOOLEAN DEFAULT FALSE");
        }
        if (!in_array('approval_reason', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN approval_reason VARCHAR(255) NULL");
        }
        if (!in_array('admin_notes', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN admin_notes TEXT NULL");
        }
        if (!in_array('location_in', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN location_in TEXT NULL");
        }
        if (!in_array('location_out', $columns)) {
            $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN location_out TEXT NULL");
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        $start_time = microtime(true);
        
        try {
            switch ($action) {
                case 'clock_in':
                    return $this->clockIn();
                case 'clock_out':
                    return $this->clockOut();
                case 'get_status':
                    return $this->getStatus();
                case 'force_clock_out':
                    return $this->forceClockOut();
                default:
                    $this->sendResponse(false, 'Action non reconnue', 400);
            }
        } catch (Exception $e) {
            $processing_time = round((microtime(true) - $start_time) * 1000);
            $this->sendResponse(false, 'Erreur: ' . $e->getMessage(), 500, ['processing_time_ms' => $processing_time]);
        }
    }
    
    private function getDeviceFingerprint() {
        // Collecte d'informations côté serveur
        $fingerprint = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            'accept' => $_SERVER['HTTP_ACCEPT'] ?? '',
            'connection' => $_SERVER['HTTP_CONNECTION'] ?? '',
            'host' => $_SERVER['HTTP_HOST'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            'real_ip' => $_SERVER['HTTP_X_REAL_IP'] ?? '',
            'request_time' => $_SERVER['REQUEST_TIME'] ?? time(),
            'remote_port' => $_SERVER['REMOTE_PORT'] ?? '',
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        ];
        
        return json_encode($fingerprint);
    }
    
    private function detectVpnProxy($ip) {
        // Détection basique de VPN/Proxy par patterns IP connus
        $vpn_patterns = [
            '10.', '172.16.', '172.17.', '172.18.', '172.19.', '172.20.',
            '172.21.', '172.22.', '172.23.', '172.24.', '172.25.', '172.26.',
            '172.27.', '172.28.', '172.29.', '172.30.', '172.31.', '192.168.'
        ];
        
        foreach ($vpn_patterns as $pattern) {
            if (strpos($ip, $pattern) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function clockIn() {
        try {
            $start_time = microtime(true);
            
            // Récupérer toutes les données de tracking
            $tracking_data = $_POST;
            
            // Informations de base
            $user_id = $this->current_user_id;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $client_timestamp = $tracking_data['client_timestamp'] ?? null;
            
            // Géolocalisation
            $latitude = $tracking_data['latitude'] ?? null;
            $longitude = $tracking_data['longitude'] ?? null;
            $accuracy = $tracking_data['accuracy'] ?? null;
            $altitude = $tracking_data['altitude'] ?? null;
            
            // Empreinte de l'appareil
            $device_fingerprint = $this->getDeviceFingerprint();
            $screen_resolution = !empty($tracking_data['screen_resolution']) ? $tracking_data['screen_resolution'] : null;
            $browser_language = !empty($tracking_data['browser_language']) ? $tracking_data['browser_language'] : null;
            $timezone_offset = !empty($tracking_data['timezone_offset']) ? (int)$tracking_data['timezone_offset'] : null;
            $platform = !empty($tracking_data['platform']) ? $tracking_data['platform'] : null;
            $cpu_cores = !empty($tracking_data['cpu_cores']) ? (int)$tracking_data['cpu_cores'] : null;
            $memory_gb = !empty($tracking_data['memory_gb']) ? (float)$tracking_data['memory_gb'] : null;
            
            // Données réseau
            $connection_type = $tracking_data['connection_type'] ?? null;
            $connection_speed = $tracking_data['connection_speed'] ?? null;
            $ip_v6 = $tracking_data['ip_v6'] ?? null;
            
            // Données de l'appareil
            $battery_level = !empty($tracking_data['battery_level']) ? (float)$tracking_data['battery_level'] : null;
            $is_charging = isset($tracking_data['is_charging']) && $tracking_data['is_charging'] !== '' ? (int)(bool)$tracking_data['is_charging'] : null;
            $device_orientation = !empty($tracking_data['device_orientation']) ? $tracking_data['device_orientation'] : null;
            
            // Empreintes de sécurité
            $canvas_fingerprint = $tracking_data['canvas_fingerprint'] ?? null;
            $webgl_fingerprint = $tracking_data['webgl_fingerprint'] ?? null;
            $audio_fingerprint = $tracking_data['audio_fingerprint'] ?? null;
            
            // Détection VPN/Proxy
            $is_vpn_proxy = $this->detectVpnProxy($ip_address) ? 1 : 0;
            $isp_name = !empty($tracking_data['isp_name']) ? $tracking_data['isp_name'] : null;
            $country_code = !empty($tracking_data['country_code']) ? $tracking_data['country_code'] : null;
            $city_name = !empty($tracking_data['city_name']) ? $tracking_data['city_name'] : null;
            
            // Vérifier s'il y a déjà un pointage actif
            $stmt = $this->pdo->prepare("
                SELECT id FROM time_tracking 
                WHERE user_id = ? AND clock_out IS NULL 
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$user_id]);
            
            if ($stmt->fetch()) {
                $this->sendResponse(false, 'Vous avez déjà un pointage en cours');
                return;
            }
            
            $now = date('Y-m-d H:i:s');
            $approval_data = $this->checkTimeSlotApproval($user_id, $now);
            
            // Location string détaillée
            $location_string = "GPS: {$latitude},{$longitude}";
            if ($accuracy) $location_string .= " (±{$accuracy}m)";
            if ($altitude) $location_string .= " Alt:{$altitude}m";
            if ($city_name) $location_string .= " - {$city_name}";
            if ($country_code) $location_string .= " ({$country_code})";
            
            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            // Insérer le pointage avec TOUTES les données
            $stmt = $this->pdo->prepare("
                INSERT INTO time_tracking (
                    user_id, clock_in, ip_address, user_agent, status,
                    auto_approved, approval_reason, location_in,
                    latitude_in, longitude_in, gps_accuracy_in, altitude_in,
                    device_fingerprint, screen_resolution, browser_language,
                    timezone_offset, platform, cpu_cores, memory_gb,
                    connection_type, connection_speed, ip_v6,
                    battery_level, is_charging, device_orientation,
                    canvas_fingerprint, webgl_fingerprint, audio_fingerprint,
                    client_timestamp, server_timestamp, processing_time_ms,
                    is_vpn_proxy, isp_name, country_code, city_name,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, 'active',
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, NOW(), ?,
                    ?, ?, ?, ?,
                    NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                $user_id, $now, $ip_address, $user_agent,
                $approval_data['auto_approve'], $approval_data['reason'], $location_string,
                $latitude, $longitude, $accuracy, $altitude,
                $device_fingerprint, $screen_resolution, $browser_language,
                $timezone_offset, $platform, $cpu_cores, $memory_gb,
                $connection_type, $connection_speed, $ip_v6,
                $battery_level, $is_charging, $device_orientation,
                $canvas_fingerprint, $webgl_fingerprint, $audio_fingerprint,
                $client_timestamp, $processing_time,
                $is_vpn_proxy, $isp_name, $country_code, $city_name
            ]);
            
            $entry_id = $this->pdo->lastInsertId();
            
            $message = $approval_data['auto_approve'] 
                ? '✅ Pointage d\'arrivée enregistré et approuvé automatiquement'
                : '⏱️ Pointage d\'arrivée enregistré - En attente d\'approbation';
            
            $this->sendResponse(true, $message, 200, [
                'entry_id' => $entry_id,
                'clock_in' => $now,
                'auto_approved' => $approval_data['auto_approve'],
                'approval_reason' => $approval_data['reason'],
                'processing_time_ms' => $processing_time,
                'security_info' => [
                    'ip_address' => $ip_address,
                    'location' => $location_string,
                    'is_vpn_proxy' => $is_vpn_proxy,
                    'device_fingerprinted' => !empty($device_fingerprint)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur clock-in: ' . $e->getMessage());
        }
    }
    
    private function clockOut() {
        try {
            $start_time = microtime(true);
            
            // Récupérer toutes les données de tracking
            $tracking_data = $_POST;
            
            $user_id = $this->current_user_id;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            
            // Géolocalisation de sortie
            $latitude_out = $tracking_data['latitude'] ?? null;
            $longitude_out = $tracking_data['longitude'] ?? null;
            $accuracy_out = $tracking_data['accuracy'] ?? null;
            $altitude_out = $tracking_data['altitude'] ?? null;
            
            // Chercher le pointage actif
            $stmt = $this->pdo->prepare("
                SELECT * FROM time_tracking 
                WHERE user_id = ? AND clock_out IS NULL 
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$entry) {
                $this->sendResponse(false, 'Aucun pointage actif trouvé');
                return;
            }
            
            $now = date('Y-m-d H:i:s');
            
            // Calculer la durée
            $start_timestamp = strtotime($entry['clock_in']);
            $end_timestamp = strtotime($now);
            $work_duration = ($end_timestamp - $start_timestamp) / 3600; // en heures
            
            // Location string de sortie
            $location_out_string = "GPS: {$latitude_out},{$longitude_out}";
            if ($accuracy_out) $location_out_string .= " (±{$accuracy_out}m)";
            if ($altitude_out) $location_out_string .= " Alt:{$altitude_out}m";
            
            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            // Mettre à jour avec les données de sortie
            $stmt = $this->pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = ?, 
                    work_duration = ?, 
                    total_hours = ?,
                    status = 'completed',
                    location_out = ?,
                    latitude_out = ?, 
                    longitude_out = ?, 
                    gps_accuracy_out = ?, 
                    altitude_out = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $now, $work_duration, $work_duration, $location_out_string,
                $latitude_out, $longitude_out, $accuracy_out, $altitude_out,
                $entry['id']
            ]);
            
            // Calculer la distance entre arrivée et départ
            $distance_km = 0;
            if ($entry['latitude_in'] && $entry['longitude_in'] && $latitude_out && $longitude_out) {
                $distance_km = $this->calculateDistance(
                    $entry['latitude_in'], $entry['longitude_in'],
                    $latitude_out, $longitude_out
                );
            }
            
            $this->sendResponse(true, '✅ Pointage de départ enregistré', 200, [
                'entry_id' => $entry['id'],
                'clock_out' => $now,
                'work_duration' => round($work_duration, 2),
                'distance_km' => round($distance_km, 3),
                'processing_time_ms' => $processing_time,
                'security_info' => [
                    'location_in' => $entry['location_in'],
                    'location_out' => $location_out_string,
                    'movement_detected' => $distance_km > 0.1
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur clock-out: ' . $e->getMessage());
        }
    }
    
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
    
    private function checkTimeSlotApproval($user_id, $clock_time) {
        $time_only = date('H:i:s', strtotime($clock_time));
        $day_period = (date('H', strtotime($clock_time)) < 13) ? 'morning' : 'afternoon';
        
        try {
            // 1. Vérifier les créneaux spécifiques à l'utilisateur
            $stmt = $this->pdo->prepare("
                SELECT * FROM time_slots 
                WHERE user_id = ? AND slot_type = ? AND is_active = TRUE
            ");
            $stmt->execute([$user_id, $day_period]);
            $user_slot = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_slot) {
                if ($time_only >= $user_slot['start_time'] && $time_only <= $user_slot['end_time']) {
                    return [
                        'auto_approve' => true,
                        'reason' => "Pointage dans créneau spécifique ({$user_slot['start_time']}-{$user_slot['end_time']})"
                    ];
                } else {
                    return [
                        'auto_approve' => false,
                        'reason' => "Pointage hors créneau spécifique ({$user_slot['start_time']}-{$user_slot['end_time']})"
                    ];
                }
            }
            
            // 2. Vérifier les créneaux globaux
            $stmt = $this->pdo->prepare("
                SELECT * FROM time_slots 
                WHERE user_id IS NULL AND slot_type = ? AND is_active = TRUE
            ");
            $stmt->execute([$day_period]);
            $global_slot = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($global_slot) {
                if ($time_only >= $global_slot['start_time'] && $time_only <= $global_slot['end_time']) {
                    return [
                        'auto_approve' => true,
                        'reason' => "Pointage dans créneau global ({$global_slot['start_time']}-{$global_slot['end_time']})"
                    ];
                } else {
                    return [
                        'auto_approve' => false,
                        'reason' => "Pointage hors créneau global ({$global_slot['start_time']}-{$global_slot['end_time']})"
                    ];
                }
            }
            
            // 3. Aucun créneau défini = demande d'approbation
            return [
                'auto_approve' => false,
                'reason' => 'Aucun créneau horaire défini'
            ];
            
        } catch (Exception $e) {
            return [
                'auto_approve' => false,
                'reason' => 'Erreur vérification créneaux: ' . $e->getMessage()
            ];
        }
    }
    
    private function getStatus() {
        $user_id = $this->current_user_id;
        
        $stmt = $this->pdo->prepare("
            SELECT *, 
                   CASE WHEN clock_out IS NULL THEN 'active' ELSE 'completed' END as current_status
            FROM time_tracking 
            WHERE user_id = ? 
            ORDER BY clock_in DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            $this->sendResponse(true, 'Aucun pointage', 200, ['status' => 'no_entry']);
            return;
        }
        
        $data = [
            'status' => $entry['current_status'],
            'entry_id' => $entry['id'],
            'clock_in' => $entry['clock_in'],
            'clock_out' => $entry['clock_out'],
            'auto_approved' => (bool)$entry['auto_approved'],
            'approval_reason' => $entry['approval_reason'],
            'location_in' => $entry['location_in'],
            'location_out' => $entry['location_out'],
            'security_info' => [
                'device_fingerprint_exists' => !empty($entry['device_fingerprint']),
                'gps_tracked' => !empty($entry['latitude_in']),
                'is_vpn_proxy' => (bool)$entry['is_vpn_proxy']
            ]
        ];
        
        if ($entry['current_status'] === 'active') {
            $work_duration = (time() - strtotime($entry['clock_in'])) / 3600;
            $data['current_duration'] = round($work_duration, 2);
        } else {
            $data['work_duration'] = $entry['work_duration'];
        }
        
        $this->sendResponse(true, 'Statut récupéré', 200, $data);
    }
    
    private function forceClockOut() {
        $user_id = $this->current_user_id;
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_tracking 
            WHERE user_id = ? AND clock_out IS NULL 
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            $this->sendResponse(false, 'Aucun pointage actif à terminer');
            return;
        }
        
        $now = date('Y-m-d H:i:s');
        $work_duration = (strtotime($now) - strtotime($entry['clock_in'])) / 3600;
        
        $stmt = $this->pdo->prepare("
            UPDATE time_tracking 
            SET clock_out = ?, work_duration = ?, total_hours = ?, status = 'completed',
                admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nForce clock-out at ', ?)
            WHERE id = ?
        ");
        $stmt->execute([$now, $work_duration, $work_duration, $now, $entry['id']]);
        
        $this->sendResponse(true, 'Pointage forcé terminé', 200, [
            'entry_id' => $entry['id'],
            'clock_out' => $now,
            'work_duration' => round($work_duration, 2)
        ]);
    }
    
    private function sendResponse($success, $message, $code = 200, $data = null) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->current_user_id
        ]);
        exit;
    }
}

// Instancier et traiter la requête
try {
    $api = new EnhancedTimeTrackingAPI();
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
