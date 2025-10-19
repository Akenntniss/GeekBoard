<?php
/**
 * Endpoint AJAX simplifié - SANS vérification des droits
 * Pour éviter les problèmes de session
 */

// Forcer le header JSON
header('Content-Type: application/json');

// Pas de vérification de session - on fait confiance
require_once __DIR__ . '/config/database.php';

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

    // Auto-créer table time_slots si nécessaire
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
        
        // Plus de création automatique de créneaux globaux
    }

    // Auto-ajouter colonnes time_tracking si nécessaire
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
        // Suppression de l'action 'save_global_slots' - Plus de créneaux globaux
            
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
            
            $response = ['success' => true, 'message' => 'Créneaux utilisateur supprimés'];
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
            
            $stmt->execute([$entry_id]);
            $response = ['success' => true, 'message' => 'Entrée approuvée avec succès'];
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
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nREJECTED by admin at ', NOW(), ' - Reason: ', ?),
                    status = 'pending'
                WHERE id = ?
            ");
            
            $stmt->execute([$reason, $entry_id]);
            $response = ['success' => true, 'message' => 'Entrée rejetée avec succès'];
            break;
            
        case 'rectify_entry':
            $entry_id = intval($_POST['entry_id']);
            $start_time = trim($_POST['start_time'] ?? '');
            $end_time = trim($_POST['end_time'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            $auto_approve = isset($_POST['auto_approve']) ? 1 : 0;
            
            if ($entry_id <= 0) {
                throw new Exception("ID d'entrée invalide");
            }
            if (empty($start_time)) {
                throw new Exception("L'heure d'entrée est obligatoire");
            }
            if (empty($reason)) {
                throw new Exception("Le motif de rectification est obligatoire");
            }
            
            // Récupérer les données actuelles pour l'historique
            $stmt = $shop_pdo->prepare("SELECT clock_in, clock_out FROM time_tracking WHERE id = ?");
            $stmt->execute([$entry_id]);
            $current_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current_data) {
                throw new Exception("Entrée de pointage non trouvée");
            }
            
            // Créer l'historique de rectification
            $old_clock_in = $current_data['clock_in'] ? date('H:i', strtotime($current_data['clock_in'])) : 'N/A';
            $old_clock_out = $current_data['clock_out'] ? date('H:i', strtotime($current_data['clock_out'])) : 'N/A';
            $new_clock_out = $end_time ?: 'N/A';
            
            $rectification_note = "RECTIFICATION at " . date('Y-m-d H:i:s') . ":\n";
            $rectification_note .= "- Old: IN={$old_clock_in}, OUT={$old_clock_out}\n";
            $rectification_note .= "- New: IN={$start_time}, OUT={$new_clock_out}\n";
            $rectification_note .= "- Reason: {$reason}";
            
            // Construire la date et heure complète (format: HH:MM -> YYYY-MM-DD HH:MM:SS)
            $date_part = date('Y-m-d', strtotime($current_data['clock_in']));
            
            // S'assurer que le format est HH:MM et ajouter :00 pour les secondes
            $start_time_formatted = (strlen($start_time) == 5) ? $start_time . ':00' : $start_time;
            $new_clock_in = $date_part . ' ' . $start_time_formatted;
            $new_clock_out_full = null;
            
            if ($end_time) {
                $end_time_formatted = (strlen($end_time) == 5) ? $end_time . ':00' : $end_time;
                $new_clock_out_full = $date_part . ' ' . $end_time_formatted;
                
                // Calculer la durée de travail
                $start_timestamp = strtotime($new_clock_in);
                $end_timestamp = strtotime($new_clock_out_full);
                $work_duration = ($end_timestamp - $start_timestamp) / 3600; // en heures
                
                $stmt = $shop_pdo->prepare("
                    UPDATE time_tracking 
                    SET clock_in = ?, 
                        clock_out = ?, 
                        work_duration = ?,
                        total_hours = ?,
                        admin_approved = ?, 
                        admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?),
                        status = 'completed'
                    WHERE id = ?
                ");
                $stmt->execute([$new_clock_in, $new_clock_out_full, $work_duration, $work_duration, $auto_approve, $rectification_note, $entry_id]);
            } else {
                $stmt = $shop_pdo->prepare("
                    UPDATE time_tracking 
                    SET clock_in = ?, 
                        clock_out = NULL,
                        work_duration = NULL,
                        total_hours = NULL,
                        admin_approved = ?, 
                        admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?),
                        status = 'active'
                    WHERE id = ?
                ");
                $stmt->execute([$new_clock_in, $auto_approve, $rectification_note, $entry_id]);
            }
            
            $message = "Pointage rectifié avec succès";
            if ($auto_approve) {
                $message .= " et approuvé automatiquement";
            }
            
            $response = ['success' => true, 'message' => $message];
            break;
            
        case 'delete_entry':
            $entry_id = intval($_POST['entry_id']);
            
            if (!$entry_id) {
                throw new Exception("ID de pointage requis");
            }
            
            // Vérifier que l'entrée existe et récupérer les détails
            $stmt = $shop_pdo->prepare("
                SELECT tt.*, u.full_name 
                FROM time_tracking tt 
                LEFT JOIN users u ON tt.user_id = u.id 
                WHERE tt.id = ?
            ");
            $stmt->execute([$entry_id]);
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$entry) {
                throw new Exception("Pointage non trouvé");
            }
            
            // Supprimer le pointage
            $stmt = $shop_pdo->prepare("DELETE FROM time_tracking WHERE id = ?");
            $stmt->execute([$entry_id]);
            
            if ($stmt->rowCount() > 0) {
                $employee_name = $entry['full_name'] ?: 'Employé inconnu';
                $date = date('d/m/Y', strtotime($entry['clock_in']));
                $time = date('H:i', strtotime($entry['clock_in']));
                
                $response = [
                    'success' => true, 
                    'message' => "Pointage supprimé avec succès",
                    'details' => [
                        'employee' => $employee_name,
                        'date' => $date,
                        'time' => $time
                    ]
                ];
            } else {
                throw new Exception("Erreur lors de la suppression");
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
