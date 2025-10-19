<?php
/**
 * Script pour intégrer l'approbation automatique basée sur les créneaux horaires
 * À inclure ou intégrer dans le système de pointage existant
 */

/**
 * Fonction pour vérifier si un pointage est dans les créneaux autorisés
 * et l'approuver automatiquement ou créer une demande d'approbation
 */
function processTimeTrackingApproval($user_id, $clock_time, $shop_pdo) {
    $time_only = date('H:i:s', strtotime($clock_time));
    $hour = intval(date('H', strtotime($clock_time)));
    
    // Déterminer le type de créneau (matin ou après-midi)
    $slot_type = ($hour < 13) ? 'morning' : 'afternoon';
    
    // Chercher d'abord les règles spécifiques à l'utilisateur
    $stmt = $shop_pdo->prepare("
        SELECT start_time, end_time 
        FROM time_slots 
        WHERE user_id = ? AND slot_type = ? AND is_active = TRUE
    ");
    $stmt->execute([$user_id, $slot_type]);
    $user_slot = $stmt->fetch();
    
    // Si pas de règle spécifique, utiliser la règle globale
    if (!$user_slot) {
        $stmt = $shop_pdo->prepare("
            SELECT start_time, end_time 
            FROM time_slots 
            WHERE user_id IS NULL AND slot_type = ? AND is_active = TRUE
        ");
        $stmt->execute([$slot_type]);
        $user_slot = $stmt->fetch();
    }
    
    if ($user_slot) {
        $is_in_slot = ($time_only >= $user_slot['start_time'] && $time_only <= $user_slot['end_time']);
        
        if ($is_in_slot) {
            // Pointage dans les créneaux autorisés - approbation automatique
            return [
                'auto_approved' => true,
                'admin_approved' => true,
                'approval_reason' => null
            ];
        } else {
            // Pointage hors créneaux - demande d'approbation
            $reason = sprintf(
                "Pointage %s hors créneau autorisé (%s - %s). Pointé à %s",
                $slot_type === 'morning' ? 'du matin' : 'de l\'après-midi',
                substr($user_slot['start_time'], 0, 5),
                substr($user_slot['end_time'], 0, 5),
                substr($time_only, 0, 5)
            );
            
            return [
                'auto_approved' => false,
                'admin_approved' => false,
                'approval_reason' => $reason
            ];
        }
    }
    
    // Pas de créneau défini - demande d'approbation par défaut
    return [
        'auto_approved' => false,
        'admin_approved' => false,
        'approval_reason' => 'Aucun créneau horaire défini pour cet employé'
    ];
}

/**
 * Fonction pour mettre à jour un pointage existant avec la logique d'approbation
 */
function updateTimeTrackingWithApproval($tracking_id, $shop_pdo) {
    // Récupérer les informations du pointage
    $stmt = $shop_pdo->prepare("
        SELECT user_id, clock_in 
        FROM time_tracking 
        WHERE id = ?
    ");
    $stmt->execute([$tracking_id]);
    $tracking = $stmt->fetch();
    
    if (!$tracking) {
        return false;
    }
    
    // Traiter l'approbation
    $approval_data = processTimeTrackingApproval($tracking['user_id'], $tracking['clock_in'], $shop_pdo);
    
    // Mettre à jour l'enregistrement
    $stmt = $shop_pdo->prepare("
        UPDATE time_tracking 
        SET auto_approved = ?, 
            admin_approved = ?, 
            approval_reason = ?
        WHERE id = ?
    ");
    
    return $stmt->execute([
        $approval_data['auto_approved'],
        $approval_data['admin_approved'],
        $approval_data['approval_reason'],
        $tracking_id
    ]);
}

/**
 * Fonction pour créer un nouveau pointage avec approbation automatique
 */
function createTimeTrackingWithApproval($user_id, $clock_in, $additional_data, $shop_pdo) {
    // Traiter l'approbation
    $approval_data = processTimeTrackingApproval($user_id, $clock_in, $shop_pdo);
    
    // Préparer les données de base
    $insert_data = array_merge([
        'user_id' => $user_id,
        'clock_in' => $clock_in,
        'status' => 'active',
        'auto_approved' => $approval_data['auto_approved'],
        'admin_approved' => $approval_data['admin_approved'],
        'approval_reason' => $approval_data['approval_reason']
    ], $additional_data);
    
    // Construire la requête dynamiquement
    $fields = array_keys($insert_data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO time_tracking (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $shop_pdo->prepare($sql);
    
    return $stmt->execute(array_values($insert_data));
}

/**
 * Fonction pour migrer les pointages existants (à exécuter une seule fois)
 */
function migrateExistingTimeTracking($shop_pdo) {
    // Récupérer tous les pointages non traités
    $stmt = $shop_pdo->prepare("
        SELECT id, user_id, clock_in 
        FROM time_tracking 
        WHERE auto_approved IS NULL OR auto_approved = 0
    ");
    $stmt->execute();
    $trackings = $stmt->fetchAll();
    
    $updated = 0;
    foreach ($trackings as $tracking) {
        if (updateTimeTrackingWithApproval($tracking['id'], $shop_pdo)) {
            $updated++;
        }
    }
    
    return $updated;
}

/**
 * Exemple d'intégration dans le système de pointage existant
 */
function exampleClockInWithApproval($user_id, $shop_pdo) {
    $clock_in = date('Y-m-d H:i:s');
    
    // Données supplémentaires selon votre système
    $additional_data = [
        'location' => 'Bureau principal', // exemple
        'device_info' => 'Web App', // exemple
        'created_at' => $clock_in
    ];
    
    if (createTimeTrackingWithApproval($user_id, $clock_in, $additional_data, $shop_pdo)) {
        echo "Pointage enregistré avec succès\n";
        
        // Vérifier si une approbation est nécessaire
        $approval_data = processTimeTrackingApproval($user_id, $clock_in, $shop_pdo);
        
        if ($approval_data['auto_approved']) {
            echo "✅ Pointage approuvé automatiquement\n";
        } else {
            echo "⚠️ Pointage en attente d'approbation: " . $approval_data['approval_reason'] . "\n";
        }
        
        return true;
    }
    
    return false;
}

/**
 * Fonction pour obtenir le statut d'approbation d'un employé
 */
function getEmployeeApprovalStatus($user_id, $shop_pdo) {
    $stmt = $shop_pdo->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(CASE WHEN auto_approved = 1 THEN 1 ELSE 0 END) as auto_approved_count,
            SUM(CASE WHEN admin_approved = 1 THEN 1 ELSE 0 END) as admin_approved_count,
            SUM(CASE WHEN admin_approved = 0 AND auto_approved = 0 THEN 1 ELSE 0 END) as pending_count
        FROM time_tracking 
        WHERE user_id = ? AND status = 'completed'
        AND DATE(clock_in) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$user_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Exemple d'utilisation:
/*
// Dans votre système de pointage, remplacez les INSERT directs par:
if (isset($_POST['clock_in'])) {
    $user_id = $_SESSION['user_id'];
    
    if (exampleClockInWithApproval($user_id, $shop_pdo)) {
        // Succès
        header('Location: ?page=timetracking&success=1');
    } else {
        // Erreur
        header('Location: ?page=timetracking&error=1');
    }
    exit;
}

// Pour migrer les données existantes (à exécuter une seule fois):
// $migrated = migrateExistingTimeTracking($shop_pdo);
// echo "Migré $migrated entrées";
*/

?>
