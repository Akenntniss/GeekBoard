<?php
/**
 * API de pointage simplifiée - Vérification WiFi uniquement
 * Version légale et respectueuse de la vie privée
 */

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session
session_start();

class SimpleWiFiTimeTrackingAPI {
    private $pdo;
    private $current_user_id;
    
    public function __construct() {
        try {
            // Connexion directe
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
            // Créer la table wifi_authorized_ssids
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS wifi_authorized_ssids (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ssid VARCHAR(255) NOT NULL,
                    description VARCHAR(255) NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_ssid (ssid)
                )
            ");
            
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
            
            if (!in_array('wifi_ssid', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN wifi_ssid VARCHAR(255) NULL");
            }
            if (!in_array('auto_approved', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN auto_approved BOOLEAN DEFAULT FALSE");
            }
            if (!in_array('approval_reason', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN approval_reason VARCHAR(255) NULL");
            }
            if (!in_array('admin_notes', $columns)) {
                $this->pdo->exec("ALTER TABLE time_tracking ADD COLUMN admin_notes TEXT NULL");
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
                case 'get_authorized_ssids':
                    return $this->getAuthorizedSSIDs();
                case 'add_ssid':
                    return $this->addSSID();
                case 'remove_ssid':
                    return $this->removeSSID();
                case 'update_ssid':
                    return $this->updateSSID();
                default:
                    $this->sendResponse(false, 'Action non reconnue', 400);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur: ' . $e->getMessage(), 500);
        }
    }
    
    private function isSSIDAuthorized($ssid) {
        if (empty($ssid)) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM wifi_authorized_ssids 
            WHERE ssid = ? AND is_active = TRUE
        ");
        $stmt->execute([$ssid]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    private function clockIn() {
        try {
            $user_id = $this->current_user_id;
            $wifi_ssid = $_POST['wifi_ssid'] ?? '';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Vérifier le WiFi
            if (!$this->isSSIDAuthorized($wifi_ssid)) {
                $this->sendResponse(false, '❌ WiFi non autorisé. Vous devez être connecté au WiFi du magasin pour pointer.');
                return;
            }
            
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
            
            // Insérer le pointage simplifié
            $stmt = $this->pdo->prepare("
                INSERT INTO time_tracking (
                    user_id, clock_in, ip_address, user_agent, status,
                    auto_approved, approval_reason, wifi_ssid,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'active', ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $user_id, $now, $ip_address, $user_agent,
                $approval_data['auto_approve'], $approval_data['reason'], $wifi_ssid
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
                'wifi_ssid' => $wifi_ssid
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse(false, 'Erreur clock-in: ' . $e->getMessage());
        }
    }
    
    private function clockOut() {
        try {
            $user_id = $this->current_user_id;
            $wifi_ssid = $_POST['wifi_ssid'] ?? '';
            
            // Vérifier le WiFi pour la sortie aussi
            if (!$this->isSSIDAuthorized($wifi_ssid)) {
                $this->sendResponse(false, '❌ WiFi non autorisé pour pointer la sortie.');
                return;
            }
            
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
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$now, $work_duration, $work_duration, $entry['id']]);
            
            $this->sendResponse(true, '✅ Pointage de départ enregistré', 200, [
                'entry_id' => $entry['id'],
                'clock_out' => $now,
                'work_duration' => round($work_duration, 2),
                'wifi_ssid' => $wifi_ssid
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
            'wifi_ssid' => $entry['wifi_ssid']
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
    
    // Gestion des SSID autorisés
    private function getAuthorizedSSIDs() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM wifi_authorized_ssids 
            WHERE is_active = TRUE 
            ORDER BY ssid ASC
        ");
        $stmt->execute();
        $ssids = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendResponse(true, 'SSIDs autorisés récupérés', 200, ['ssids' => $ssids]);
    }
    
    private function addSSID() {
        $ssid = trim($_POST['ssid'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($ssid)) {
            $this->sendResponse(false, 'SSID requis');
            return;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO wifi_authorized_ssids (ssid, description) 
                VALUES (?, ?)
            ");
            $stmt->execute([$ssid, $description]);
            
            $this->sendResponse(true, 'SSID ajouté avec succès', 200, [
                'id' => $this->pdo->lastInsertId(),
                'ssid' => $ssid,
                'description' => $description
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $this->sendResponse(false, 'Ce SSID existe déjà');
            } else {
                $this->sendResponse(false, 'Erreur lors de l\'ajout: ' . $e->getMessage());
            }
        }
    }
    
    private function removeSSID() {
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            $this->sendResponse(false, 'ID requis');
            return;
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM wifi_authorized_ssids WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $this->sendResponse(true, 'SSID supprimé avec succès');
        } else {
            $this->sendResponse(false, 'SSID non trouvé');
        }
    }
    
    private function updateSSID() {
        $id = intval($_POST['id'] ?? 0);
        $ssid = trim($_POST['ssid'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
        
        if (!$id || empty($ssid)) {
            $this->sendResponse(false, 'ID et SSID requis');
            return;
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE wifi_authorized_ssids 
            SET ssid = ?, description = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ssid, $description, $is_active, $id]);
        
        if ($stmt->rowCount() > 0) {
            $this->sendResponse(true, 'SSID mis à jour avec succès');
        } else {
            $this->sendResponse(false, 'SSID non trouvé ou aucun changement');
        }
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
    $api = new SimpleWiFiTimeTrackingAPI();
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
