<?php
// Export simple des logs d'activité
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="logs_activite_' . date('Y-m-d_H-i') . '.txt"');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo "Erreur: Impossible de se connecter à la base de données";
        exit;
    }
    
    // Récupérer les paramètres de filtrage depuis l'URL
    $log_type = $_GET['log_type'] ?? 'all';
    $employe_id = isset($_GET['employe_id']) ? intval($_GET['employe_id']) : 0;
    $action_type = $_GET['action_type'] ?? '';
    $date_debut = $_GET['date_debut'] ?? '';
    $date_fin = $_GET['date_fin'] ?? '';
    
    // Construire la requête
    $where_conditions_rep = [];
    $where_conditions_task = [];
    $params = [];
    
    if ($employe_id > 0) {
        $where_conditions_rep[] = "rl.employe_id = ?";
        $where_conditions_task[] = "tl.employe_id = ?";
        $params[] = $employe_id;
        $params[] = $employe_id;
    }
    
    if (!empty($action_type)) {
        $where_conditions_rep[] = "rl.action_type = ?";
        $where_conditions_task[] = "tl.action_type = ?";
        $params[] = $action_type;
        $params[] = $action_type;
    }
    
    if (!empty($date_debut)) {
        $where_conditions_rep[] = "DATE(rl.date_action) >= ?";
        $where_conditions_task[] = "DATE(tl.date_action) >= ?";
        $params[] = $date_debut;
        $params[] = $date_debut;
    }
    
    if (!empty($date_fin)) {
        $where_conditions_rep[] = "DATE(rl.date_action) <= ?";
        $where_conditions_task[] = "DATE(tl.date_action) <= ?";
        $params[] = $date_fin;
        $params[] = $date_fin;
    }
    
    $where_clause_rep = !empty($where_conditions_rep) ? 'WHERE ' . implode(' AND ', $where_conditions_rep) : '';
    $where_clause_task = !empty($where_conditions_task) ? 'WHERE ' . implode(' AND ', $where_conditions_task) : '';
    
    // Construire la requête selon le type de log
    $sql_parts = [];
    
    if ($log_type === 'all' || $log_type === 'reparations') {
        $sql_parts[] = "
            SELECT 
                rl.date_action,
                'REPARATION' as type_log,
                rl.action_type,
                rl.statut_avant,
                rl.statut_apres,
                rl.details,
                u.full_name as employe_nom,
                CONCAT('Réparation #', rl.reparation_id) as reference
            FROM reparation_logs rl
            LEFT JOIN users u ON rl.employe_id = u.id
            $where_clause_rep
        ";
    }
    
    if ($log_type === 'all' || $log_type === 'taches') {
        $sql_parts[] = "
            SELECT 
                tl.date_action,
                'TACHE' as type_log,
                tl.action_type,
                tl.statut_avant,
                tl.statut_apres,
                tl.details,
                u.full_name as employe_nom,
                COALESCE(t.titre, CONCAT('Tâche #', tl.tache_id)) as reference
            FROM task_logs tl
            LEFT JOIN users u ON tl.employe_id = u.id
            LEFT JOIN taches t ON tl.tache_id = t.id
            $where_clause_task
        ";
    }
    
    if (empty($sql_parts)) {
        echo "Aucun log à exporter";
        exit;
    }
    
    $sql = implode(' UNION ALL ', $sql_parts) . " ORDER BY date_action DESC";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // En-tête du fichier
    echo "=== EXPORT LOGS D'ACTIVITÉ ===\n";
    echo "Date d'export: " . date('d/m/Y H:i:s') . "\n";
    echo "Nombre de logs: " . count($logs) . "\n";
    echo "Filtres appliqués:\n";
    echo "- Type: " . ($log_type === 'all' ? 'Tous' : ucfirst($log_type)) . "\n";
    if ($employe_id > 0) {
        $stmt_user = $shop_pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt_user->execute([$employe_id]);
        $user = $stmt_user->fetch();
        echo "- Employé: " . ($user ? $user['full_name'] : 'Inconnu') . "\n";
    }
    if (!empty($action_type)) echo "- Action: " . $action_type . "\n";
    if (!empty($date_debut)) echo "- Date début: " . $date_debut . "\n";
    if (!empty($date_fin)) echo "- Date fin: " . $date_fin . "\n";
    echo "\n" . str_repeat("=", 80) . "\n\n";
    
    // Export des logs
    foreach ($logs as $log) {
        echo "[" . date('d/m/Y H:i:s', strtotime($log['date_action'])) . "] ";
        echo "[" . $log['type_log'] . "] ";
        echo "[" . strtoupper($log['action_type']) . "] ";
        echo $log['reference'] . "\n";
        
        echo "Employé: " . ($log['employe_nom'] ?: 'Inconnu') . "\n";
        
        if ($log['statut_avant'] || $log['statut_apres']) {
            echo "Statut: ";
            if ($log['statut_avant']) echo $log['statut_avant'];
            if ($log['statut_avant'] && $log['statut_apres']) echo " → ";
            if ($log['statut_apres']) echo $log['statut_apres'];
            echo "\n";
        }
        
        if ($log['details']) {
            echo "Détails: " . $log['details'] . "\n";
        }
        
        echo str_repeat("-", 50) . "\n\n";
    }
    
    if (empty($logs)) {
        echo "Aucun log trouvé avec les critères sélectionnés.\n";
    }
    
} catch (Exception $e) {
    echo "Erreur lors de l'export: " . $e->getMessage();
}
?>
