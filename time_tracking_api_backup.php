<?php
/**
 * API de gestion des pointages Clock-In/Clock-Out
 * Compatible avec la structure GeekBoard existante
 */

// Configuration de session comme dans index.php
require_once __DIR__ . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Configuration pour les sous-domaines
require_once __DIR__ . '/config/subdomain_config.php';

// Configuration de base
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

class TimeTrackingAPI {
    private $pdo;
    private $user_id;
    private $is_admin;

    public function __construct() {
        // Obtenir la connexion à la base de données
        $this->pdo = getShopDBConnection();
        
        // Vérifier l'authentification
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
        
        // Debug session (optionnel, peut être supprimé)
        error_log("API TimeTracking - User ID: " . ($this->user_id ?? 'NULL'));
        error_log("API TimeTracking - Is Admin: " . ($this->is_admin ? 'YES' : 'NO'));
        error_log("API TimeTracking - Session Role: " . ($_SESSION['user_role'] ?? 'UNDEFINED'));
    }

    private function isAuthenticated() {
        return !is_null($this->user_id);
    }

    private function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->sendError('Utilisateur non authentifié', 401);
        }
    }

    private function requireAdmin() {
        $this->requireAuth();
        if (!$this->is_admin) {
            $this->sendError('Accès administrateur requis', 403);
        }
    }

    private function sendResponse($data) {
        echo json_encode($data);
        exit;
    }

    private function sendError($message, $code = 400) {
        http_response_code($code);
        $this->sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    private function sendSuccess($data = [], $message = 'Success') {
        $this->sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function clockIn() {
        $this->requireAuth();

        try {
            // Vérifier s'il y a déjà une session active
            $stmt = $this->pdo->prepare("
                SELECT id FROM time_tracking 
                WHERE user_id = ? AND status IN ('active', 'break')
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$this->user_id]);
            
            if ($stmt->fetch()) {
                $this->sendError('Vous avez déjà une session de pointage active');
            }

            // Créer nouvelle session
            $stmt = $this->pdo->prepare("
                INSERT INTO time_tracking (user_id, clock_in, status, ip_address, location) 
                VALUES (?, NOW(), 'active', ?, ?)
            ");
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $location = $_POST['location'] ?? 'Non spécifiée';
            
            $stmt->execute([$this->user_id, $ip_address, $location]);

            $this->sendSuccess([
                'session_id' => $this->pdo->lastInsertId(),
                'clock_in_time' => date('Y-m-d H:i:s')
            ], 'Pointage d\'entrée enregistré avec succès');

        } catch (Exception $e) {
            $this->sendError('Erreur lors du pointage: ' . $e->getMessage());
        }
    }

    public function clockOut() {
        $this->requireAuth();

        try {
            // Trouver la session active
            $stmt = $this->pdo->prepare("
                SELECT id, clock_in, break_duration 
                FROM time_tracking 
                WHERE user_id = ? AND status IN ('active', 'break')
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$this->user_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                $this->sendError('Aucune session de pointage active trouvée');
            }

            // Calculer les durées
            $clock_in = new DateTime($session['clock_in']);
            $clock_out = new DateTime();
            $total_minutes = $clock_out->diff($clock_in)->i + ($clock_out->diff($clock_in)->h * 60);
            $total_hours = $total_minutes / 60;
            $break_duration = floatval($session['break_duration'] ?? 0);
            $work_duration = $total_hours - $break_duration;

            // Mettre à jour la session
            $stmt = $this->pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = NOW(), 
                    status = 'completed',
                    total_hours = ?,
                    work_duration = ?
                WHERE id = ?
            ");
            $stmt->execute([$total_hours, $work_duration, $session['id']]);

            $this->sendSuccess([
                'session_id' => $session['id'],
                'clock_out_time' => $clock_out->format('Y-m-d H:i:s'),
                'total_hours' => round($total_hours, 2),
                'work_hours' => round($work_duration, 2)
            ], 'Pointage de sortie enregistré avec succès');

        } catch (Exception $e) {
            $this->sendError('Erreur lors du pointage de sortie: ' . $e->getMessage());
        }
    }

    public function startBreak() {
        $this->requireAuth();

        try {
            // Trouver la session active
            $stmt = $this->pdo->prepare("
                SELECT id FROM time_tracking 
                WHERE user_id = ? AND status = 'active'
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$this->user_id]);
            $session = $stmt->fetch();

            if (!$session) {
                $this->sendError('Aucune session active trouvée');
            }

            // Démarrer la pause
            $stmt = $this->pdo->prepare("
                UPDATE time_tracking 
                SET break_start = NOW(), status = 'break'
                WHERE id = ?
            ");
            $stmt->execute([$session['id']]);

            $this->sendSuccess([
                'break_start_time' => date('Y-m-d H:i:s')
            ], 'Pause démarrée');

        } catch (Exception $e) {
            $this->sendError('Erreur lors du démarrage de la pause: ' . $e->getMessage());
        }
    }

    public function endBreak() {
        $this->requireAuth();

        try {
            // Trouver la session en pause
            $stmt = $this->pdo->prepare("
                SELECT id, break_start, break_duration 
                FROM time_tracking 
                WHERE user_id = ? AND status = 'break'
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$this->user_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                $this->sendError('Aucune pause active trouvée');
            }

            // Calculer la durée de la pause
            $break_start = new DateTime($session['break_start']);
            $break_end = new DateTime();
            $break_minutes = $break_end->diff($break_start)->i + ($break_end->diff($break_start)->h * 60);
            $new_break_duration = floatval($session['break_duration'] ?? 0) + ($break_minutes / 60);

            // Terminer la pause
            $stmt = $this->pdo->prepare("
                UPDATE time_tracking 
                SET break_end = NOW(), 
                    break_duration = ?,
                    status = 'active'
                WHERE id = ?
            ");
            $stmt->execute([$new_break_duration, $session['id']]);

            $this->sendSuccess([
                'break_end_time' => $break_end->format('Y-m-d H:i:s'),
                'break_duration' => round($new_break_duration, 2)
            ], 'Pause terminée');

        } catch (Exception $e) {
            $this->sendError('Erreur lors de la fin de pause: ' . $e->getMessage());
        }
    }

    public function getStatus() {
        $this->requireAuth();

        try {
            // Récupérer le statut actuel
            $stmt = $this->pdo->prepare("
                SELECT id, clock_in, break_start, status,
                       TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0 as current_duration,
                       TIME_FORMAT(TIMEDIFF(NOW(), clock_in), '%H:%i') as formatted_duration
                FROM time_tracking 
                WHERE user_id = ? AND status IN ('active', 'break')
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$this->user_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($session) {
                $this->sendSuccess([
                    'is_clocked_in' => true,
                    'status' => $session['status'],
                    'session_id' => $session['id'],
                    'clock_in_time' => $session['clock_in'],
                    'current_duration' => round(floatval($session['current_duration']), 2),
                    'formatted_duration' => $session['formatted_duration'],
                    'on_break' => $session['status'] === 'break',
                    'break_start' => $session['break_start']
                ], 'Statut récupéré avec succès');
            } else {
                $this->sendSuccess([
                    'is_clocked_in' => false,
                    'status' => 'not_working',
                    'session_id' => null
                ], 'Aucune session active');
            }

        } catch (Exception $e) {
            $this->sendError('Erreur lors de la récupération du statut: ' . $e->getMessage());
        }
    }

    public function adminGetActive() {
        $this->requireAdmin();

        try {
            $stmt = $this->pdo->prepare("
                SELECT tt.*, u.full_name, u.username, u.role,
                       TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) / 60.0 as current_duration,
                       TIME_FORMAT(TIMEDIFF(NOW(), tt.clock_in), '%H:%i') as formatted_duration
                FROM time_tracking tt
                JOIN users u ON tt.user_id = u.id
                WHERE tt.status IN ('active', 'break')
                ORDER BY tt.clock_in ASC
            ");
            $stmt->execute();
            $active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->sendSuccess([
                'active_sessions' => $active_sessions,
                'count' => count($active_sessions)
            ], 'Sessions actives récupérées');

        } catch (Exception $e) {
            $this->sendError('Erreur lors de la récupération des sessions actives: ' . $e->getMessage());
        }
    }

    public function adminForceClockOut() {
        $this->requireAdmin();

        $target_user_id = $_POST['user_id'] ?? null;
        if (!$target_user_id) {
            $this->sendError('ID utilisateur requis');
        }

        try {
            // Forcer le pointage de sortie
            $stmt = $this->pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = NOW(), 
                    status = 'completed',
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nForced clock-out by admin at ', NOW()),
                    total_hours = TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0,
                    work_duration = (TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0) - IFNULL(break_duration, 0),
                    admin_approved = 1
                WHERE user_id = ? AND status IN ('active', 'break')
            ");
            
            $stmt->execute([$target_user_id]);
            $affected = $stmt->rowCount();

            if ($affected > 0) {
                $this->sendSuccess(['affected_rows' => $affected], 'Pointage forcé avec succès');
            } else {
                $this->sendError('Aucune session active trouvée pour cet utilisateur');
            }

        } catch (Exception $e) {
            $this->sendError('Erreur lors du pointage forcé: ' . $e->getMessage());
        }
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? null;

        if (!$action) {
            $this->sendError('Action requise');
        }

        switch ($action) {
            case 'clock_in':
                $this->clockIn();
                break;
            case 'clock_out':
                $this->clockOut();
                break;
            case 'start_break':
                $this->startBreak();
                break;
            case 'end_break':
                $this->endBreak();
                break;
            case 'get_status':
                $this->getStatus();
                break;
            case 'admin_get_active':
                $this->adminGetActive();
                break;
            case 'admin_force_clock_out':
                $this->adminForceClockOut();
                break;
            default:
                $this->sendError('Action non reconnue: ' . $action);
        }
    }
}

// Gérer la requête
try {
    $api = new TimeTrackingAPI();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
