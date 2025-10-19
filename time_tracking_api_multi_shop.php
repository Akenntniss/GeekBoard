<?php
/**
 * API de pointage compatible système multi-magasin
 * Utilise getShopDBConnection() pour la détection automatique de base de données
 */

// Configuration de base - Utilisation du système existant
require_once __DIR__ . '/config/database.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Vérifier la session
session_start();

class MultiShopTimeTrackingAPI {
    private $pdo;
    private $current_user_id;
    private $shop_info;
    
    public function __construct() {
        try {
            // INITIALISER LA SESSION MAGASIN SI NÉCESSAIRE
            $this->initializeShopSession();
            
            // UTILISER LE SYSTÈME MULTI-MAGASIN EXISTANT
            $this->pdo = getShopDBConnection();
            
            if (!$this->pdo) {
                throw new Exception("Impossible d'établir la connexion à la base de données du magasin");
            }
            
            // Récupérer les informations du magasin actuel
            $this->shop_info = [
                'shop_id' => $_SESSION['shop_id'] ?? null,
                'shop_name' => $_SESSION['shop_name'] ?? 'Magasin inconnu',
                'database' => $_SESSION['current_database'] ?? 'Base inconnue'
            ];
            
            dbDebugLog("API Pointage initialisée pour magasin: " . $this->shop_info['shop_name']);
            
            // Récupérer l'user_id de la session ou des paramètres POST
            $this->current_user_id = $_SESSION['user_id'] ?? $_POST['user_id'] ?? null;
            
            // Si pas d'user_id, utiliser un utilisateur par défaut pour les tests
            if (!$this->current_user_id) {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role != 'admin' ORDER BY id LIMIT 1");
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $this->current_user_id = $user['id'];
                    dbDebugLog("Utilisation utilisateur par défaut: " . $this->current_user_id);
                } else {
                    throw new Exception("Aucun utilisateur disponible dans cette base de données");
                }
            }
            
            $this->ensureTablesExist();
            
        } catch (Exception $e) {
            dbDebugLog("Erreur initialisation API: " . $e->getMessage());
            $this->sendResponse(false, 'Erreur de connexion: ' . $e->getMessage(), 500);
        }
    }
    
    private function initializeShopSession() {
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
    
    private function ensureTablesExist() {
        try {
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
            
            dbDebugLog("Tables de pointage vérifiées/créées avec succès");
            
        } catch (Exception $e) {
            dbDebugLog("Erreur création tables: " . $e->getMessage());
            // Ne pas arrêter si les tables existent déjà
        }
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        dbDebugLog("Traitement action: $action pour utilisateur: " . $this->current_user_id);
        
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
            dbDebugLog("Erreur traitement action $action: " . $e->getMessage());
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
        
        // Insérer le pointage avec status = 'active'
        $stmt = $this->pdo->prepare("
            INSERT INTO time_tracking (
                user_id, clock_in, ip_address, 
                auto_approved, admin_approved, approval_reason,
                qr_code_used, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $this->current_user_id,
            $clock_time,
            $ip_address,
            $approval_check['auto_approve'] ? 1 : 0,
            $approval_check['auto_approve'] ? 1 : 0,
            $approval_check['reason'],
            0, // Pas de QR code utilisé
            'active' // Status initial
        ]);
        
        $entry_id = $this->pdo->lastInsertId();
        
        dbDebugLog("Clock-in réussi: entry_id=$entry_id, auto_approved=" . ($approval_check['auto_approve'] ? 'Oui' : 'Non'));
        
        $this->sendResponse(true, '✅ Pointage d\'arrivée enregistré', 200, [
            'entry_id' => $entry_id,
            'clock_in' => $clock_time,
            'auto_approved' => $approval_check['auto_approve'],
            'approval_reason' => $approval_check['reason'],
            'shop_info' => $this->shop_info
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
        
        // Mettre à jour le pointage avec status = 'completed'
        $stmt = $this->pdo->prepare("
            UPDATE time_tracking 
            SET clock_out = ?, work_duration = ?, status = 'completed', updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$clock_time, $work_duration, $entry['id']]);
        
        dbDebugLog("Clock-out réussi: entry_id={$entry['id']}, durée=" . round($work_duration, 2) . "h");
        
        $this->sendResponse(true, '✅ Pointage de départ enregistré', 200, [
            'entry_id' => $entry['id'],
            'clock_out' => $clock_time,
            'work_duration' => round($work_duration, 2),
            'shop_info' => $this->shop_info
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
                'elapsed_seconds' => (int)$entry['elapsed_seconds'],
                'shop_info' => $this->shop_info
            ]);
        } else {
            $this->sendResponse(true, 'Pas de pointage en cours', 200, [
                'is_clocked_in' => false,
                'shop_info' => $this->shop_info
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
        
        dbDebugLog("Utilisateurs actifs trouvés: " . count($active_users));
        
        $this->sendResponse(true, 'Utilisateurs actifs récupérés', 200, [
            'active_users' => $active_users,
            'count' => count($active_users),
            'shop_info' => $this->shop_info
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
            'user_id' => $this->current_user_id,
            'shop_info' => $this->shop_info
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
            dbDebugLog("Erreur vérification créneaux: " . $e->getMessage());
            return [
                'auto_approve' => false,
                'reason' => 'Erreur vérification créneaux: ' . $e->getMessage()
            ];
        }
    }
    
    private function sendResponse($success, $message, $code = 200, $data = null) {
        http_response_code($code);
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->current_user_id,
            'shop_info' => $this->shop_info ?? ['shop_name' => 'Inconnu']
        ];
        
        dbDebugLog("Réponse API: " . ($success ? 'SUCCESS' : 'ERROR') . " - $message");
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Instancier et traiter la requête
try {
    dbDebugLog("=== DÉBUT REQUÊTE API POINTAGE ===");
    dbDebugLog("Sous-domaine détecté: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini'));
    dbDebugLog("Session shop_id: " . ($_SESSION['shop_id'] ?? 'Non défini'));
    
    $api = new MultiShopTimeTrackingAPI();
    $api->handleRequest();
    
} catch (Exception $e) {
    dbDebugLog("Erreur fatale API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'shop_info' => $_SESSION['shop_name'] ?? 'Magasin inconnu'
    ], JSON_UNESCAPED_UNICODE);
}
?>
