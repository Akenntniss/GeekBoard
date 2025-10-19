<?php
/**
 * API de gestion des pointages avec système de créneaux
 * Version ultra-simplifiée qui fonctionne sans session utilisateur
 */

// Configuration de base
require_once __DIR__ . '/config/database.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session
session_start();

class SimpleTimeTrackingAPI {
    private $pdo;
    private $current_user_id;
    
    public function __construct() {
        try {
            $this->pdo = getShopDBConnection();
            
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
                        INSERT INTO users (username, full_name, email, password, role) 
                        VALUES ('testuser', 'Utilisateur Test', 'test@example.com', 'password', 'user')
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
            
            // Ajouter les colonnes manquantes à time_tracking
            $columns = $this->pdo->query("SHOW COLUMNS FROM time_tracking")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('auto_approved', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN auto_approved BOOLEAN DEFAULT FALSE");
            }
            
            if (!in_array('approval_reason', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN approval_reason VARCHAR(255) NULL");
            }
            
        } catch (Exception $e) {
            // Ignorer les erreurs de création de tables
        }
    }
    
    private function checkTimeSlotApproval($user_id, $clock_time) {
        $time_only = date('H:i:s', strtotime($clock_time));
        $day_period = (date('H', strtotime($clock_time)) < 13) ? 'morning' : 'afternoon';
        
        try {
            // 1. Vérifier les créneaux spécifiques à l'utilisateur
            $stmt = $this->pdo->prepare("
                SELECT start_time, end_time 
                FROM time_slots 
                WHERE user_id = ? AND slot_type = ? AND is_active = TRUE
            ");
            $stmt->execute([$user_id, $day_period]);
            $user_slot = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_slot) {
                if ($time_only >= $user_slot['start_time'] && $time_only <= $user_slot['end_time']) {
                    return [
                        'auto_approved' => true,
                        'approval_reason' => null
                    ];
                } else {
                    return [
                        'auto_approved' => false,
                        'approval_reason' => "Pointage hors créneau spécifique ({$user_slot['start_time']}-{$user_slot['end_time']})"
                    ];
                }
            }
            
            // 2. Vérifier les créneaux globaux
            $stmt = $this->pdo->prepare("
                SELECT start_time, end_time 
                FROM time_slots 
                WHERE user_id IS NULL AND slot_type = ? AND is_active = TRUE
            ");
            $stmt->execute([$day_period]);
            $global_slot = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($global_slot) {
                if ($time_only >= $global_slot['start_time'] && $time_only <= $global_slot['end_time']) {
                    return [
                        'auto_approved' => true,
                        'approval_reason' => null
                    ];
                } else {
                    return [
                        'auto_approved' => false,
                        'approval_reason' => "Pointage hors créneau global ({$global_slot['start_time']}-{$global_slot['end_time']})"
                    ];
                }
            }
        } catch (Exception $e) {
            // En cas d'erreur, approuver automatiquement
        }
        
        // Par défaut, approuver automatiquement
        return [
            'auto_approved' => true,
            'approval_reason' => null
        ];
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
                    return $this->getCurrentStatus();
                case 'admin_get_active':
                    return $this->getActiveUsers();
                default:
                    $this->sendResponse(true, 'API Simple active - Action: ' . $action, 200, [
                        'user_id' => $this->current_user_id,
                        'session_data' => $_SESSION ?? []
                    ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), 400);
        }
    }
    
    private function clockIn() {
        try {
            // Vérifier s'il n'y a pas déjà une session active
            $stmt = $this->pdo->prepare("SELECT id FROM time_tracking WHERE user_id = ? AND status IN ('active', 'break') ORDER BY clock_in DESC LIMIT 1");
            $stmt->execute([$this->current_user_id]);
            
            if ($stmt->fetch()) {
                throw new Exception('Vous avez déjà une session de pointage active');
            }
            
            $clock_time = date('Y-m-d H:i:s');
            $location = $_POST['location'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            
            // Vérifier l'approbation automatique
            $approval_check = $this->checkTimeSlotApproval($this->current_user_id, $clock_time);
            
            // Créer une nouvelle entrée de pointage
            $stmt = $this->pdo->prepare("
                INSERT INTO time_tracking 
                (user_id, clock_in, status, location_in, ip_address, admin_approved, auto_approved, approval_reason, created_at) 
                VALUES (?, ?, 'active', ?, ?, ?, ?, ?, NOW())
            ");
            
            $admin_approved = $approval_check['auto_approved'] ? 1 : 0;
            
            $stmt->execute([
                $this->current_user_id,
                $clock_time,
                $location,
                $ip_address,
                $admin_approved,
                $approval_check['auto_approved'] ? 1 : 0,
                $approval_check['approval_reason']
            ]);
            
            $entry_id = $this->pdo->lastInsertId();
            
            $message = 'Pointage d\'entrée enregistré avec succès';
            if (!$approval_check['auto_approved']) {
                $message .= ' - En attente d\'approbation manuelle';
            }
            
            $this->sendResponse(true, $message, 200, [
                'entry_id' => $entry_id,
                'clock_in' => $clock_time,
                'auto_approved' => $approval_check['auto_approved'],
                'approval_reason' => $approval_check['approval_reason']
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur clock-in: ' . $e->getMessage(), 400);
        }
    }
    
    private function clockOut() {
        try {
            // Trouver la session active
            $stmt = $this->pdo->prepare("
                SELECT id, clock_in, auto_approved, approval_reason 
                FROM time_tracking 
                WHERE user_id = ? AND status IN ('active', 'break') 
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$this->current_user_id]);
            $active_session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$active_session) {
                throw new Exception('Aucune session de pointage active trouvée');
            }
            
            $clock_out_time = date('Y-m-d H:i:s');
            $location = $_POST['location'] ?? null;
            
            // Calculer la durée de travail
            $clock_in_time = new DateTime($active_session['clock_in']);
            $clock_out_time_obj = new DateTime($clock_out_time);
            $duration = $clock_out_time_obj->diff($clock_in_time);
            $work_duration = $duration->h + ($duration->i / 60) + ($duration->s / 3600);
            
            // Vérifier l'approbation pour le clock-out
            $approval_check = $this->checkTimeSlotApproval($this->current_user_id, $clock_out_time);
            
            // Approbation finale
            $final_auto_approved = $active_session['auto_approved'] && $approval_check['auto_approved'];
            $final_admin_approved = $final_auto_approved ? 1 : 0;
            
            $final_approval_reason = null;
            if (!$final_auto_approved) {
                $reasons = array_filter([
                    $active_session['approval_reason'],
                    $approval_check['approval_reason']
                ]);
                $final_approval_reason = implode(' | ', $reasons);
            }
            
            // Mettre à jour l'entrée
            $stmt = $this->pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = ?, 
                    status = 'completed', 
                    location_out = ?, 
                    work_duration = ?,
                    admin_approved = ?,
                    auto_approved = ?,
                    approval_reason = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $clock_out_time,
                $location,
                $work_duration,
                $final_admin_approved,
                $final_auto_approved ? 1 : 0,
                $final_approval_reason,
                $active_session['id']
            ]);
            
            $message = 'Pointage de sortie enregistré avec succès';
            if (!$final_auto_approved) {
                $message .= ' - En attente d\'approbation manuelle';
            }
            
            $this->sendResponse(true, $message, 200, [
                'entry_id' => $active_session['id'],
                'clock_out' => $clock_out_time,
                'work_duration' => round($work_duration, 2),
                'auto_approved' => $final_auto_approved,
                'approval_reason' => $final_approval_reason
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur clock-out: ' . $e->getMessage(), 400);
        }
    }
    
    private function getCurrentStatus() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT tt.*, 
                       CASE 
                           WHEN tt.status = 'active' THEN 1 
                           ELSE 0 
                       END as is_clocked_in,
                       CASE 
                           WHEN tt.status = 'break' THEN 1 
                           ELSE 0 
                       END as is_on_break,
                       TIME_TO_SEC(TIMEDIFF(NOW(), tt.clock_in)) / 3600 as current_duration
                FROM time_tracking tt
                WHERE tt.user_id = ? 
                AND tt.status IN ('active', 'break')
                ORDER BY tt.clock_in DESC 
                LIMIT 1
            ");
            $stmt->execute([$this->current_user_id]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response_data = [
                'is_clocked_in' => false,
                'is_on_break' => false,
                'current_session' => null,
                'current_duration' => 0,
                'user_id' => $this->current_user_id
            ];
            
            if ($status) {
                $response_data = [
                    'is_clocked_in' => (bool)$status['is_clocked_in'],
                    'is_on_break' => (bool)$status['is_on_break'],
                    'current_session' => $status,
                    'current_duration' => round($status['current_duration'] ?? 0, 2),
                    'user_id' => $this->current_user_id
                ];
            }
            
            $this->sendResponse(true, 'Statut récupéré', 200, $response_data);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur get status: ' . $e->getMessage(), 400);
        }
    }
    
    private function getActiveUsers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT tt.*, u.full_name, u.username,
                       TIME_TO_SEC(TIMEDIFF(NOW(), tt.clock_in)) / 3600 as current_duration
                FROM time_tracking tt
                JOIN users u ON tt.user_id = u.id
                WHERE tt.status IN ('active', 'break')
                ORDER BY tt.clock_in ASC
            ");
            $stmt->execute();
            $active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse(true, 'Utilisateurs actifs récupérés', 200, $active_users);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur get active users: ' . $e->getMessage(), 400);
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
    $api = new SimpleTimeTrackingAPI();
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
