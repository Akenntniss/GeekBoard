<?php
/**
 * API de pointage par QR Code - Version simplifiée
 * Système de pointage moderne et sécurisé
 */

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configuration session et base de données
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/database.php';

class QRTimeTrackingAPI {
    private $pdo;
    private $current_user_id;
    
    public function __construct() {
        try {
            // Connexion dynamique à la base du shop actuel
            $this->pdo = getShopDBConnection();
            
            if (!$this->pdo) {
                $this->sendResponse(false, 'Erreur de connexion à la base de données du magasin', 500);
            }
            
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
            
            // Ajouter les colonnes de base à time_tracking
            $columns = $this->pdo->query("SHOW COLUMNS FROM time_tracking")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('auto_approved', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN auto_approved BOOLEAN DEFAULT FALSE");
            }
            if (!in_array('approval_reason', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN approval_reason VARCHAR(255) NULL");
            }
            if (!in_array('admin_notes', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN admin_notes TEXT NULL");
            }
            if (!in_array('qr_code_used', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN qr_code_used BOOLEAN DEFAULT FALSE");
            }
            
        } catch (Exception $e) {
            // Ignorer les erreurs de création de tables
        }
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
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
                case 'get_user_info':
                    return $this->getUserInfo();
                default:
                    $this->sendResponse(false, 'Action non reconnue', 400);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur: ' . $e->getMessage(), 500);
        }
    }
    
    private function clockIn() {
        try {
            $user_id = $this->current_user_id;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $qr_code_used = isset($_POST['qr_code']) ? true : false;
            
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
            
            // Insérer le pointage
            $stmt = $this->pdo->prepare("
                INSERT INTO time_tracking (
                    user_id, clock_in, ip_address, user_agent, status,
                    auto_approved, approval_reason, qr_code_used,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'active', ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $user_id, $now, $ip_address, $user_agent,
                $approval_data['auto_approve'], $approval_data['reason'], $qr_code_used
            ]);
            
            $entry_id = $this->pdo->lastInsertId();
            
            $message = $approval_data['auto_approve'] 
                ? '✅ Pointage d\'arrivée enregistré et approuvé automatiquement'
                : '⏱️ Pointage d\'arrivée enregistré - En attente d\'approbation';
            
            if ($qr_code_used) {
                $message .= ' 📱 (via QR Code)';
            }
            
            $this->sendResponse(true, $message, 200, [
                'entry_id' => $entry_id,
                'clock_in' => $now,
                'auto_approved' => $approval_data['auto_approve'],
                'approval_reason' => $approval_data['reason'],
                'qr_code_used' => $qr_code_used
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur clock-in: ' . $e->getMessage());
        }
    }
    
    private function clockOut() {
        try {
            $user_id = $this->current_user_id;
            $qr_code_used = isset($_POST['qr_code']) ? true : false;
            
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
            
            // Mettre à jour avec les données de sortie
            $stmt = $this->pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = ?, 
                    work_duration = ?, 
                    total_hours = ?,
                    status = 'completed',
                    qr_code_used = CASE WHEN qr_code_used = 1 OR ? = 1 THEN 1 ELSE 0 END,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$now, $work_duration, $work_duration, $qr_code_used, $entry['id']]);
            
            $message = '✅ Pointage de départ enregistré';
            if ($qr_code_used) {
                $message .= ' 📱 (via QR Code)';
            }
            
            $this->sendResponse(true, $message, 200, [
                'entry_id' => $entry['id'],
                'clock_out' => $now,
                'work_duration' => round($work_duration, 2),
                'qr_code_used' => $qr_code_used
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur clock-out: ' . $e->getMessage());
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
            'qr_code_used' => (bool)$entry['qr_code_used']
        ];
        
        if ($entry['current_status'] === 'active') {
            $work_duration = (time() - strtotime($entry['clock_in'])) / 3600;
            $data['current_duration'] = round($work_duration, 2);
        } else {
            $data['work_duration'] = $entry['work_duration'];
        }
        
        $this->sendResponse(true, 'Statut récupéré', 200, $data);
    }
    
    private function getUserInfo() {
        $user_id = $this->current_user_id;
        
        $stmt = $this->pdo->prepare("
            SELECT id, username, full_name, role 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->sendResponse(false, 'Utilisateur non trouvé');
            return;
        }
        
        $this->sendResponse(true, 'Informations utilisateur récupérées', 200, [
            'user' => $user
        ]);
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
        ]);
        exit;
    }
}

// Instancier et traiter la requête
try {
    $api = new QRTimeTrackingAPI();
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
