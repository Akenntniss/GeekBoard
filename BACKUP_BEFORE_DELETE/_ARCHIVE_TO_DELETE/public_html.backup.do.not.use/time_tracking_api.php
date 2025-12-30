<?php
/**
 * API de gestion des pointages Clock-In/Clock-Out
 * Compatible avec la structure GeekBoard existante
 */

// Configuration de base
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Inclure la configuration des sous-domaines
if (file_exists(__DIR__ . '/config/subdomain_config.php')) {
    require_once __DIR__ . '/config/subdomain_config.php';
}

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session et initialiser la session shop
session_start();
initializeShopSession();

class TimeTrackingAPI {
    private $pdo;
    private $current_user_id;
    private $is_admin;
    
    public function __construct() {
        try {
            $this->pdo = getShopDBConnection();
            $this->current_user_id = $_SESSION['user_id'] ?? null;
            $this->is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
            
            if (!$this->current_user_id) {
                // Vérifier si la session shop est initialisée mais pas l'utilisateur
                if (isset($_SESSION['shop_id'])) {
                    // Pour le debug: utiliser l'utilisateur par défaut du magasin
                    try {
                        $stmt = $this->pdo->prepare("SELECT id FROM users ORDER BY id LIMIT 1");
                        $stmt->execute();
                        $user = $stmt->fetch();
                        if ($user) {
                            $this->current_user_id = $user['id'];
                            // Log pour debugging
                            error_log("API Pointage: Utilisation user_id par défaut: " . $this->current_user_id);
                        } else {
                            throw new Exception('Aucun utilisateur disponible dans ce magasin');
                        }
                    } catch (Exception $e) {
                        throw new Exception('Erreur récupération utilisateur: ' . $e->getMessage());
                    }
                } else {
                    throw new Exception('Session magasin non initialisée');
                }
            }
            
            // Tables créées manuellement - pas besoin de vérification
            // $this->ensureTablesExist();
            
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), 401);
        }
    }
    
    /**
     * S'assurer que les tables de pointage existent
     */
    private function ensureTablesExist() {
        try {
            // Créer la table time_tracking si elle n'existe pas
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS time_tracking (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    clock_in DATETIME NOT NULL,
                    clock_out DATETIME NULL,
                    break_start DATETIME NULL,
                    break_end DATETIME NULL,
                    break_duration DECIMAL(5,2) DEFAULT 0,
                    total_hours DECIMAL(5,2) DEFAULT 0,
                    work_duration DECIMAL(5,2) DEFAULT 0,
                    status ENUM('active', 'break', 'completed', 'cancelled') DEFAULT 'active',
                    location TEXT NULL,
                    ip_address VARCHAR(45) NULL,
                    notes TEXT NULL,
                    approved BOOLEAN DEFAULT FALSE,
                    approved_by INT NULL,
                    approved_at DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_status (status),
                    INDEX idx_clock_in (clock_in),
                    INDEX idx_date (DATE(clock_in))
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("Erreur création table time_tracking: " . $e->getMessage());
            throw new Exception("Erreur d'initialisation des tables de pointage");
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'clock_in':
                    return $this->clockIn();
                    
                case 'clock_out':
                    return $this->clockOut();
                    
                case 'start_break':
                    return $this->startBreak();
                    
                case 'end_break':
                    return $this->endBreak();
                    
                case 'get_status':
                    return $this->getCurrentStatus();
                    
                case 'get_today_entries':
                    return $this->getTodayEntries();
                    
                case 'admin_get_active':
                    if (!$this->is_admin) {
                        throw new Exception('Accès refusé - Droits administrateur requis');
                    }
                    return $this->getActiveUsers();
                    
                case 'admin_approve':
                    if (!$this->is_admin) {
                        throw new Exception('Accès refusé - Droits administrateur requis');
                    }
                    return $this->approveEntry();
                    
                case 'get_weekly_report':
                    return $this->getWeeklyReport();
                    
                default:
                    throw new Exception('Action non reconnue');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), 400);
        }
    }
    
    private function clockIn() {
        // Vérifier s'il n'y a pas déjà une session active
        $stmt = $this->pdo->prepare("SELECT id FROM time_tracking WHERE user_id = ? AND status = 'active' ORDER BY clock_in DESC LIMIT 1");
        $stmt->execute([$this->current_user_id]);
        
        if ($stmt->fetch()) {
            throw new Exception('Vous avez déjà une session de pointage active');
        }
        
        // Récupérer les données de localisation si disponibles
        $location = $_POST['location'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $notes = $_POST['notes'] ?? null;
        
        // Créer une nouvelle entrée de pointage
        $stmt = $this->pdo->prepare("
            INSERT INTO time_tracking (user_id, clock_in, status, location, ip_address, notes) 
            VALUES (?, NOW(), 'active', ?, ?, ?)
        ");
        
        $stmt->execute([$this->current_user_id, $location, $ip_address, $notes]);
        
        $entry_id = $this->pdo->lastInsertId();
        
        // Récupérer l'entrée créée
        $stmt = $this->pdo->prepare("SELECT * FROM time_tracking WHERE id = ?");
        $stmt->execute([$entry_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $this->sendResponse(true, 'Pointage d\'arrivée enregistré avec succès', 200, [
            'entry' => $entry,
            'clock_in_time' => date('H:i', strtotime($entry['clock_in']))
        ]);
    }
    
    private function clockOut() {
        // Trouver la session active
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_tracking 
            WHERE user_id = ? AND status IN ('active', 'break') 
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$this->current_user_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            throw new Exception('Aucune session de pointage active trouvée');
        }
        
        $notes = $_POST['notes'] ?? $entry['notes'];
        
        // Calculer les durées
        $clock_in = new DateTime($entry['clock_in']);
        $clock_out = new DateTime();
        $total_duration = $clock_out->diff($clock_in);
        $total_hours = $total_duration->h + ($total_duration->i / 60);
        
        // Calculer la durée de pause si applicable
        $break_duration = $entry['break_duration'] ?? 0;
        $work_duration = $total_hours - $break_duration;
        
        // Mettre à jour l'entrée
        $stmt = $this->pdo->prepare("
            UPDATE time_tracking 
            SET clock_out = NOW(), 
                status = 'completed',
                total_hours = ?,
                work_duration = ?,
                notes = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$total_hours, $work_duration, $notes, $entry['id']]);
        
        // Récupérer l'entrée mise à jour
        $stmt = $this->pdo->prepare("SELECT * FROM time_tracking WHERE id = ?");
        $stmt->execute([$entry['id']]);
        $updated_entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $this->sendResponse(true, 'Pointage de sortie enregistré avec succès', 200, [
            'entry' => $updated_entry,
            'clock_out_time' => date('H:i'),
            'total_hours' => number_format($total_hours, 2),
            'work_hours' => number_format($work_duration, 2)
        ]);
    }
    
    private function startBreak() {
        // Trouver la session active
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_tracking 
            WHERE user_id = ? AND status = 'active' 
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$this->current_user_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            throw new Exception('Aucune session de pointage active trouvée');
        }
        
        if ($entry['break_start']) {
            throw new Exception('Une pause est déjà en cours');
        }
        
        // Démarrer la pause
        $stmt = $this->pdo->prepare("
            UPDATE time_tracking 
            SET break_start = NOW(), status = 'break'
            WHERE id = ?
        ");
        $stmt->execute([$entry['id']]);
        
        return $this->sendResponse(true, 'Pause commencée', 200, [
            'break_start_time' => date('H:i')
        ]);
    }
    
    private function endBreak() {
        // Trouver la session en pause
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_tracking 
            WHERE user_id = ? AND status = 'break' 
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$this->current_user_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry || !$entry['break_start']) {
            throw new Exception('Aucune pause active trouvée');
        }
        
        // Calculer la durée de la pause
        $break_start = new DateTime($entry['break_start']);
        $break_end = new DateTime();
        $break_duration_obj = $break_end->diff($break_start);
        $new_break_duration = $break_duration_obj->h + ($break_duration_obj->i / 60);
        $total_break_duration = ($entry['break_duration'] ?? 0) + $new_break_duration;
        
        // Terminer la pause
        $stmt = $this->pdo->prepare("
            UPDATE time_tracking 
            SET break_end = NOW(), 
                break_duration = ?,
                status = 'active'
            WHERE id = ?
        ");
        $stmt->execute([$total_break_duration, $entry['id']]);
        
        return $this->sendResponse(true, 'Pause terminée', 200, [
            'break_end_time' => date('H:i'),
            'break_duration' => number_format($new_break_duration, 2),
            'total_break_duration' => number_format($total_break_duration, 2)
        ]);
    }
    
    private function getCurrentStatus() {
        $stmt = $this->pdo->prepare("
            SELECT tt.*, u.full_name, u.username 
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE tt.user_id = ? AND tt.status IN ('active', 'break') 
            ORDER BY tt.clock_in DESC LIMIT 1
        ");
        $stmt->execute([$this->current_user_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $status = [
            'is_clocked_in' => false,
            'is_on_break' => false,
            'current_session' => null,
            'work_duration' => 0,
            'break_duration' => 0
        ];
        
        if ($entry) {
            $status['is_clocked_in'] = true;
            $status['is_on_break'] = $entry['status'] === 'break';
            $status['current_session'] = $entry;
            
            // Calculer la durée actuelle
            $clock_in = new DateTime($entry['clock_in']);
            $now = new DateTime();
            $duration = $now->diff($clock_in);
            $total_hours = $duration->h + ($duration->i / 60);
            
            $status['work_duration'] = $total_hours - ($entry['break_duration'] ?? 0);
            $status['break_duration'] = $entry['break_duration'] ?? 0;
        }
        
        return $this->sendResponse(true, 'Statut récupéré', 200, $status);
    }
    
    private function getTodayEntries() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM time_tracking 
            WHERE user_id = ? AND DATE(clock_in) = CURDATE() 
            ORDER BY clock_in DESC
        ");
        $stmt->execute([$this->current_user_id]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->sendResponse(true, 'Entrées du jour récupérées', 200, [
            'entries' => $entries,
            'count' => count($entries)
        ]);
    }
    
    private function getActiveUsers() {
        $stmt = $this->pdo->prepare("
            SELECT tt.*, u.full_name, u.username, u.role
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE tt.status IN ('active', 'break')
            ORDER BY tt.clock_in ASC
        ");
        $stmt->execute();
        $active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrichir avec des calculs en temps réel
        foreach ($active_users as &$user) {
            $clock_in = new DateTime($user['clock_in']);
            $now = new DateTime();
            $duration = $now->diff($clock_in);
            $total_hours = $duration->h + ($duration->i / 60);
            
            $user['current_work_duration'] = $total_hours - ($user['break_duration'] ?? 0);
            $user['current_total_time'] = $total_hours;
            $user['formatted_duration'] = sprintf('%02d:%02d', 
                floor($user['current_work_duration']), 
                ($user['current_work_duration'] - floor($user['current_work_duration'])) * 60
            );
        }
        
        return $this->sendResponse(true, 'Utilisateurs actifs récupérés', 200, [
            'active_users' => $active_users,
            'count' => count($active_users)
        ]);
    }
    
    private function getWeeklyReport() {
        $user_id = $this->is_admin ? ($_GET['user_id'] ?? $this->current_user_id) : $this->current_user_id;
        
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE(clock_in) as work_date,
                COUNT(*) as sessions_count,
                SUM(work_duration) as total_work_hours,
                SUM(break_duration) as total_break_hours,
                MIN(clock_in) as first_clock_in,
                MAX(clock_out) as last_clock_out
            FROM time_tracking 
            WHERE user_id = ? 
                AND clock_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND status = 'completed'
            GROUP BY DATE(clock_in)
            ORDER BY work_date DESC
        ");
        $stmt->execute([$user_id]);
        $weekly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->sendResponse(true, 'Rapport hebdomadaire récupéré', 200, [
            'weekly_report' => $weekly_data,
            'user_id' => $user_id
        ]);
    }
    
    private function sendResponse($success, $message, $code = 200, $data = null) {
        http_response_code($code);
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
}

// Traitement de la requête
try {
    $api = new TimeTrackingAPI();
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

