<?php
/**
 * AJAX endpoint pour récupérer les infos rapides d'une tâche
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Non connecté');
    }
    
    $task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($task_id <= 0) {
        throw new Exception('ID tâche invalide');
    }
    
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Requête pour récupérer les infos de la tâche
    $stmt = $shop_pdo->prepare("
        SELECT 
            t.id,
            t.title,
            t.description,
            t.status,
            t.priority,
            t.due_date,
            t.created_at,
            u_assigned.full_name as assigned_to_name,
            u_created.full_name as created_by_name
        FROM tasks t
        LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
        LEFT JOIN users u_created ON t.created_by = u_created.id
        WHERE t.id = ?
    ");
    
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        throw new Exception('Tâche non trouvée');
    }
    
    // Formatter le statut
    $status_labels = [
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'termine' => 'Terminée',
        'aide_necessaire' => 'Aide nécessaire'
    ];
    
    $status_colors = [
        'en_attente' => 'secondary',
        'en_cours' => 'primary',
        'termine' => 'success',
        'aide_necessaire' => 'warning'
    ];
    
    $priority_labels = [
        'basse' => 'Basse',
        'moyenne' => 'Moyenne',
        'haute' => 'Haute',
        'urgente' => 'Urgente'
    ];
    
    $priority_colors = [
        'basse' => 'secondary',
        'moyenne' => 'info',
        'haute' => 'warning',
        'urgente' => 'danger'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $task['id'],
            'title' => $task['title'],
            'description' => $task['description'],
            'status' => $task['status'],
            'status_label' => $status_labels[$task['status']] ?? $task['status'],
            'status_color' => $status_colors[$task['status']] ?? 'secondary',
            'priority' => $task['priority'],
            'priority_label' => $priority_labels[$task['priority']] ?? $task['priority'],
            'priority_color' => $priority_colors[$task['priority']] ?? 'secondary',
            'assigned_to' => $task['assigned_to_name'],
            'created_by' => $task['created_by_name'],
            'due_date' => $task['due_date'],
            'created_at' => $task['created_at']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
