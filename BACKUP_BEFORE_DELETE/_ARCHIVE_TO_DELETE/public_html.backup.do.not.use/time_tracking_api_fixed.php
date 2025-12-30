<?php
/**
 * API de pointage principale - Version corrigée
 * Compatible avec le système de créneaux spécifiques uniquement
 */

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session
session_start();

class TimeTrackingAPI {
    private $pdo;
    private $current_user_id;
    
    public function __construct() {
        try {
            // Connexion directe
            $this->pdo = new PDO("mysql:host=localhost;dbname=geekboard_mkmkmk;charset=utf8mb4", 'root', 'Mamanmaman01#', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Récupérer l'user_id de la session ou des paramètres POST
            $this->current_user_id = $_SESSION['user_id'] ?? $_POST['user_id'] ?? null;
            
            // Si pas d'user_id, utiliser un utilisateur par défaut pour les tests
            if (!$this->current_user_id) {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role != 'admin' ORDER BY id LIMIT 1");
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $this->current_user_id = $user['id'];
                } else {
                    throw new Exception("Aucun utilisateur disponible");
                }
            }
            
            $this->ensureTablesExist();
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur de connexion: ' . $e->getMessage(), 500);
        }
    }
    
    private function ensureTablesExist() {
        // Auto-créer la table time_tracking si elle n'existe pas
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS time_tracking (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                clock_in DATETIME NOT NULL,
                clock_out DATETIME NULL,
                work_duration DECIMAL(5,2) NULL,
                notes TEXT NULL,
                ip_address VARCHAR(45) NULL,
                auto_approved BOOLEAN DEFAULT FALSE,
                admin_approved BOOLEAN DEFAULT FALSE,
                approval_reason TEXT NULL,
                admin_notes TEXT NULL,
                qr_code_used BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_clock_in (clock_in),
                INDEX idx_user_date (user_id, clock_in)
            )
        ");

        // Auto-créer la table time_slots si elle n'existe pas
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
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'clock_in':
                    return $this->clockIn();
                case 'clock_out':
                    return $this->clockOut();
                case 'get_status':
                    return $this->getStatus();
                case 'admin_get_active':
                    return $this->getActiveUsers();
                case 'get_user_entries':
                    return $this->getUserEntries();
                default:
                    $this->sendResponse(false, 'Action non reconnue: ' . $action, 400);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur: ' . $e->getMessage(), 500);
        }
    }
    
    private function clockIn() {
        // Vérifier s'il y a déjà un pointage en cours
        $stmt = $this->pdo->prepare("
            SELECT id FROM time_tracking 
            WHERE user_id = ? AND clock_out IS NULL 
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$this->current_user_id]);
        
        if ($stmt->fetch()) {
            $this->sendResponse(false, 'Vous avez déjà un pointage en cours', 400);
        }
        
        $clock_time = date('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Vérifier les créneaux pour auto-approbation
        $approval_check = $this->checkTimeSlotApproval($this->current_user_id, $clock_time);
        
        // Insérer le pointage
        $stmt = $this->pdo->prepare("
            INSERT INTO time_tracking (
                user_id, clock_in, ip_address, 
                auto_approved, admin_approved, approval_reason,
                qr_code_used
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $this->current_user_id,
            $clock_time,
            $ip_address,
            $approval_check['auto_approve'] ? 1 : 0,
            $approval_check['auto_approve'] ? 1 : 0,
            $approval_check['reason'],
            0 // Pas de QR code utilisé
        ]);
        
        $entry_id = $this->pdo->lastInsertId();
        
        $this->sendResponse(true, '✅ Pointage d\'arrivée enregistré', 200, [
            'entry_id' => $entry_id,
            'clock_in' => $clock_time,
            'auto_approved' => $approval_check['auto_approve'],
            'approval_reason' => $approval_check['reason']
        ]);
    }
    
    private function clockOut() {
        // Trouver le pointage en cours
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_tracking 
            WHERE user_id = ? AND clock_out IS NULL 
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$this->current_user_id]);
        $entry = $stmt->fetch();
        
        if (!$entry) {
            $this->sendResponse(false, 'Aucun pointage en cours trouvé', 400);
        }
        
        $clock_time = date('Y-m-d H:i:s');
        $work_duration = (strtotime($clock_time) - strtotime($entry['clock_in'])) / 3600;
        
        // Mettre à jour le pointage
        $stmt = $this->pdo->prepare("
            UPDATE time_tracking 
            SET clock_out = ?, work_duration = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$clock_time, $work_duration, $entry['id']]);
        
        $this->sendResponse(true, '✅ Pointage de départ enregistré', 200, [
            'entry_id' => $entry['id'],
            'clock_out' => $clock_time,
            'work_duration' => round($work_duration, 2)
        ]);
    }
    
    private function getStatus() {
        // Vérifier s'il y a un pointage en cours
        $stmt = $this->pdo->prepare("
            SELECT id, clock_in, TIMESTAMPDIFF(SECOND, clock_in, NOW()) as elapsed_seconds
            FROM time_tracking 
            WHERE user_id = ? AND clock_out IS NULL 
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$this->current_user_id]);
        $entry = $stmt->fetch();
        
        if ($entry) {
            $hours = floor($entry['elapsed_seconds'] / 3600);
            $minutes = floor(($entry['elapsed_seconds'] % 3600) / 60);
            
            $this->sendResponse(true, 'Pointage en cours', 200, [
                'is_clocked_in' => true,
                'entry_id' => $entry['id'],
                'clock_in' => $entry['clock_in'],
                'elapsed_time' => sprintf('%02d:%02d', $hours, $minutes),
                'elapsed_seconds' => (int)$entry['elapsed_seconds']
            ]);
        } else {
            $this->sendResponse(true, 'Pas de pointage en cours', 200, [
                'is_clocked_in' => false
            ]);
        }
    }
    
    private function getActiveUsers() {
        // Récupérer tous les utilisateurs avec un pointage en cours
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.full_name, u.username,
                   tt.clock_in,
                   TIMESTAMPDIFF(SECOND, tt.clock_in, NOW()) as elapsed_seconds
            FROM time_tracking tt
            LEFT JOIN users u ON tt.user_id = u.id
            WHERE tt.clock_out IS NULL
            ORDER BY tt.clock_in DESC
        ");
        $stmt->execute();
        $active_users = $stmt->fetchAll();
        
        // Formater les données
        foreach ($active_users as &$user) {
            $hours = floor($user['elapsed_seconds'] / 3600);
            $minutes = floor(($user['elapsed_seconds'] % 3600) / 60);
            $user['elapsed_time'] = sprintf('%02d:%02d', $hours, $minutes);
            $user['status'] = 'En cours';
        }
        
        $this->sendResponse(true, 'Utilisateurs actifs récupérés', 200, [
            'active_users' => $active_users,
            'count' => count($active_users)
        ]);
    }
    
    private function getUserEntries() {
        $limit = $_GET['limit'] ?? 10;
        $offset = $_GET['offset'] ?? 0;
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_tracking 
            WHERE user_id = ? 
            ORDER BY clock_in DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$this->current_user_id, $limit, $offset]);
        $entries = $stmt->fetchAll();
        
        $this->sendResponse(true, 'Entrées récupérées', 200, [
            'entries' => $entries,
            'user_id' => $this->current_user_id
        ]);
    }
    
    private function checkTimeSlotApproval($user_id, $clock_time) {
        $time_only = date('H:i:s', strtotime($clock_time));
        $day_period = (date('H', strtotime($clock_time)) < 13) ? 'morning' : 'afternoon';
        
        try {
            // Vérifier uniquement les créneaux spécifiques à l'utilisateur
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
                        'reason' => "Pointage dans créneau autorisé ({$user_slot['start_time']}-{$user_slot['end_time']})"
                    ];
                } else {
                    return [
                        'auto_approve' => false,
                        'reason' => "Pointage hors créneau autorisé ({$user_slot['start_time']}-{$user_slot['end_time']})"
                    ];
                }
            }
            
            // Aucun créneau spécifique défini = demande d'approbation systématique
            return [
                'auto_approve' => false,
                'reason' => 'Aucun créneau horaire défini pour cet utilisateur'
            ];
            
        } catch (Exception $e) {
            return [
                'auto_approve' => false,
                'reason' => 'Erreur vérification créneaux: ' . $e->getMessage()
            ];
        }
    }
    
    private function sendResponse($success, $message, $code = 200, $data = null) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->current_user_id
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Instancier et traiter la requête
try {
    $api = new TimeTrackingAPI();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>