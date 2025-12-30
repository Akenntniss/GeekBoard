<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

initializeShopSession();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Utiliser $_GET directement car filter_input peut ne pas fonctionner avec des valeurs simulées
$repair_id = isset($_GET['repair_id']) ? (int)$_GET['repair_id'] : 0;

if (!$repair_id || $repair_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de réparation manquant ou invalide']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    
    // 1. Récupérer les informations de base de la réparation
    $repair_stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.date_reception as date_creation,
            r.date_fin_prevue as date_restitution,
            r.date_modification,
            r.prix,
            r.statut as statut_actuel,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            CONCAT(r.type_appareil, ' ', r.marque, ' ', r.modele) as appareil,
            r.description_probleme
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    
    $repair_stmt->execute([$repair_id]);
    $repair = $repair_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        echo json_encode(['success' => false, 'error' => 'Réparation non trouvée']);
        exit;
    }
    
    // Chercher la date de restitution dans les logs
    $restitution_stmt = $pdo->prepare("
        SELECT date_action 
        FROM reparation_logs 
        WHERE reparation_id = ? 
        AND (statut_apres LIKE '%effectue%' OR statut_apres LIKE '%termine%' OR statut_apres LIKE '%restitue%')
        ORDER BY date_action DESC 
        LIMIT 1
    ");
    $restitution_stmt->execute([$repair_id]);
    $restitution_date = $restitution_stmt->fetchColumn();
    
    // Si pas de log mais statut indique restitué, utiliser la date de modification
    if (!$restitution_date && in_array(strtolower($repair['statut_actuel']), ['restitue', 'termine', 'effectue', 'reparation_effectue'])) {
        $restitution_date = $repair['date_modification'] ?? null;
    }
    
    // Formater les dates
    $repair['date_creation'] = $repair['date_creation'] ? 
        date('d/m/Y à H:i', strtotime($repair['date_creation'])) : 'Non définie';
    $repair['date_restitution'] = $restitution_date ? 
        date('d/m/Y à H:i', strtotime($restitution_date)) : 'Non définie';
    $repair['prix'] = $repair['prix'] ? $repair['prix'] . ' €' : 'Non défini';
    
    // 2. Récupérer l'historique complet des actions
    $status_stmt = $pdo->prepare("
        SELECT 
            rl.date_action,
            rl.action_type as action,
            rl.details as commentaire,
            COALESCE(rl.statut_apres, rl.action_type) as statut_nom,
            u.full_name as user_name,
            r.statut as current_status,
            rl.statut_apres as nouveau_statut
        FROM reparation_logs rl
        LEFT JOIN users u ON rl.employe_id = u.id
        LEFT JOIN reparations r ON rl.reparation_id = r.id
        WHERE rl.reparation_id = ? 
        ORDER BY rl.date_action ASC
    ");
    
    $status_stmt->execute([$repair_id]);
    $status_history = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater l'historique des statuts
    foreach ($status_history as &$status) {
        $status['date_formatted'] = date('d/m/Y à H:i', strtotime($status['date_action']));
        $status['user_name'] = $status['user_name'] ?: 'Système';
        $status['is_current'] = ($status['nouveau_statut'] == $status['current_status']);
    }
    
    // 3. Récupérer l'historique des SMS (par reparation_id ET par numéro de téléphone)
    $sms_stmt = $pdo->prepare("
        SELECT 
            id,
            recipient,
            message,
            date_envoi,
            status,
            reparation_id
        FROM sms_logs 
        WHERE (reparation_id = ? OR recipient = ?)
        ORDER BY date_envoi DESC
        LIMIT 50
    ");
    
    $sms_stmt->execute([$repair_id, $repair['client_telephone']]);
    $sms_history = $sms_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater l'historique SMS
    foreach ($sms_history as &$sms) {
        $sms['date_envoi_formatted'] = date('d/m/Y à H:i', strtotime($sms['date_envoi']));
        $sms['statut_badge'] = $sms['status'] == 1 ? 'success' : 'danger';
        $sms['statut_text'] = $sms['status'] == 1 ? 'Envoyé' : 'Échec';
    }
    
    echo json_encode([
        'success' => true,
        'repair' => $repair,
        'status_history' => $status_history,
        'sms_history' => $sms_history
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur SQL dans get_complete_repair_history.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur dans get_complete_repair_history.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
