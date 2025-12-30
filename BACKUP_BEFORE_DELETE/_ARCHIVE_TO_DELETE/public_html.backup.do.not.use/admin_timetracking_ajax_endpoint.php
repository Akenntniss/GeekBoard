<?php
/**
 * Endpoint AJAX séparé pour les actions d'administration du pointage
 * Ce fichier est appelé directement pour éviter l'inclusion du layout HTML
 */

// Forcer le header JSON dès le début
header('Content-Type: application/json');

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// S'assurer que les fichiers de configuration sont chargés
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

if (!function_exists('getShopDBConnection')) {
    require_once BASE_PATH . '/config/database.php';
}

// Vérifier les droits admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès refusé - droits administrateur requis']);
    exit;
}

// Vérifier que c'est une requête POST avec action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Connexion à la base de données échouée");
    }

    // Vérifier/créer la table time_slots
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_slots'");
    $stmt->execute();
    $slots_table_exists = $stmt->fetch();
    
    if (!$slots_table_exists) {
        $shop_pdo->exec("
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
        
        $shop_pdo->exec("
            INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES
            (NULL, 'morning', '08:00:00', '12:30:00', TRUE),
            (NULL, 'afternoon', '14:00:00', '19:00:00', TRUE)
            ON DUPLICATE KEY UPDATE 
            start_time = VALUES(start_time),
            end_time = VALUES(end_time)
        ");
    }

    // Vérifier/ajouter colonnes time_tracking
    $stmt = $shop_pdo->prepare("SHOW COLUMNS FROM time_tracking LIKE 'auto_approved'");
    $stmt->execute();
    $auto_approved_exists = $stmt->fetch();
    
    if (!$auto_approved_exists) {
        $shop_pdo->exec("
            ALTER TABLE time_tracking 
            ADD COLUMN auto_approved BOOLEAN DEFAULT FALSE AFTER admin_approved,
            ADD COLUMN approval_reason VARCHAR(255) NULL AFTER auto_approved
        ");
    }

    // Traitement des actions
    switch ($_POST['action']) {
        case 'save_global_slots':
            $morning_start = $_POST['morning_start'] . ':00';
            $morning_end = $_POST['morning_end'] . ':00';
            $afternoon_start = $_POST['afternoon_start'] . ':00';
            $afternoon_end = $_POST['afternoon_end'] . ':00';
            
            // Validation des heures
            if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $morning_start) ||
                !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $morning_end) ||
                !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $afternoon_start) ||
                !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $afternoon_end)) {
                throw new Exception("Format d'heure invalide");
            }
            
            // Mise à jour créneaux
            $stmt = $shop_pdo->prepare("
                INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                VALUES (NULL, 'morning', ?, ?, TRUE)
                ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)
            ");
            $stmt->execute([$morning_start, $morning_end]);
            
            $stmt = $shop_pdo->prepare("
                INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                VALUES (NULL, 'afternoon', ?, ?, TRUE)
                ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)
            ");
            $stmt->execute([$afternoon_start, $afternoon_end]);
            
            $response = ['success' => true, 'message' => 'Créneaux globaux sauvegardés avec succès'];
            break;
            
        case 'save_user_slots':
            $user_id = intval($_POST['user_id']);
            $morning_start = !empty($_POST['user_morning_start']) ? $_POST['user_morning_start'] . ':00' : null;
            $morning_end = !empty($_POST['user_morning_end']) ? $_POST['user_morning_end'] . ':00' : null;
            $afternoon_start = !empty($_POST['user_afternoon_start']) ? $_POST['user_afternoon_start'] . ':00' : null;
            $afternoon_end = !empty($_POST['user_afternoon_end']) ? $_POST['user_afternoon_end'] . ':00' : null;
            
            if ($user_id <= 0) {
                throw new Exception("Utilisateur invalide");
            }
            
            // Supprimer anciens créneaux
            $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $created_slots = 0;
            
            // Ajouter nouveaux créneaux
            if ($morning_start && $morning_end) {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                    VALUES (?, 'morning', ?, ?, TRUE)
                ");
                $stmt->execute([$user_id, $morning_start, $morning_end]);
                $created_slots++;
            }
            
            if ($afternoon_start && $afternoon_end) {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) 
                    VALUES (?, 'afternoon', ?, ?, TRUE)
                ");
                $stmt->execute([$user_id, $afternoon_start, $afternoon_end]);
                $created_slots++;
            }
            
            $response = ['success' => true, 'message' => "Créneaux utilisateur sauvegardés ($created_slots créneaux créés)"];
            break;
            
        case 'remove_user_slots':
            $user_id = intval($_POST['user_id']);
            if ($user_id <= 0) {
                throw new Exception("Utilisateur invalide");
            }
            
            $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $response = ['success' => true, 'message' => 'Créneaux utilisateur supprimés, utilisation des créneaux globaux'];
            break;
            
        case 'approve_entry':
            $entry_id = intval($_POST['entry_id']);
            if ($entry_id <= 0) {
                throw new Exception("ID d'entrée invalide");
            }
            
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET admin_approved = TRUE, 
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nApproved by admin at ', NOW())
                WHERE id = ?
            ");
            
            if ($stmt->execute([$entry_id])) {
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    $response = ['success' => true, 'message' => 'Entrée approuvée avec succès'];
                } else {
                    $response = ['success' => false, 'message' => 'Aucune entrée trouvée avec cet ID'];
                }
            } else {
                throw new Exception('Erreur lors de l\'approbation en base de données');
            }
            break;
            
        case 'reject_entry':
            $entry_id = intval($_POST['entry_id']);
            $reason = trim($_POST['reason'] ?? '');
            
            if ($entry_id <= 0) {
                throw new Exception("ID d'entrée invalide");
            }
            if (empty($reason)) {
                throw new Exception("Une raison de rejet est requise");
            }
            
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET admin_approved = FALSE, 
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nRejected by admin at ', NOW(), ' - Reason: ', ?),
                    status = 'rejected'
                WHERE id = ?
            ");
            
            if ($stmt->execute([$reason, $entry_id])) {
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    $response = ['success' => true, 'message' => 'Entrée rejetée avec succès'];
                } else {
                    $response = ['success' => false, 'message' => 'Aucune entrée trouvée avec cet ID'];
                }
            } else {
                throw new Exception('Erreur lors du rejet en base de données');
            }
            break;
            
        case 'force_clock_out':
            $user_id = intval($_POST['user_id']);
            if ($user_id <= 0) {
                throw new Exception("ID utilisateur invalide");
            }
            
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = NOW(), 
                    status = 'completed',
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nForced clock-out by admin at ', NOW()),
                    total_hours = TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0,
                    work_duration = (TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0) - IFNULL(break_duration, 0)
                WHERE user_id = ? AND status IN ('active', 'break')
            ");
            
            if ($stmt->execute([$user_id])) {
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    $response = ['success' => true, 'message' => 'Pointage forcé avec succès'];
                } else {
                    $response = ['success' => false, 'message' => 'Aucun pointage actif trouvé pour cet utilisateur'];
                }
            } else {
                throw new Exception('Erreur lors du pointage forcé en base de données');
            }
            break;
            
        default:
            throw new Exception("Action non reconnue: " . $_POST['action']);
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
}

// Retourner uniquement du JSON
echo json_encode($response);
exit;
?>
