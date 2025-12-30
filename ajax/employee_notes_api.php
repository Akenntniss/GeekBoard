<?php
/**
 * API de gestion des notes employés
 * Permet de créer, modifier, supprimer et récupérer les remarques contextuelles sur les employés
 * 
 * Actions:
 * - get_notes : Liste des notes (filtrable)
 * - get_note : Une note spécifique
 * - create_note : Créer une nouvelle note
 * - update_note : Modifier une note
 * - delete_note : Supprimer une note
 * - toggle_resolved : Marquer comme résolu/non résolu
 * - toggle_ai_inclusion : Activer/désactiver inclusion dans IA
 * - get_employee_context : Contexte formaté pour l'IA
 * - get_statistics : Statistiques des notes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session magasin
initializeShopSession();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// Vérification authentification et role admin
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;

if (!$user_id || $user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé - Admin uniquement']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Connexion base de données échouée');
    }
    
    switch ($action) {
        case 'get_notes':
            $result = getNotes($pdo, $_GET);
            break;
            
        case 'get_note':
            $result = getNote($pdo, $_GET['id'] ?? 0);
            break;
            
        case 'create_note':
            $result = createNote($pdo, $_POST, $user_id);
            break;
            
        case 'update_note':
            $result = updateNote($pdo, $_POST, $user_id);
            break;
            
        case 'delete_note':
            $result = deleteNote($pdo, $_POST['id'] ?? 0);
            break;
            
        case 'toggle_resolved':
            $result = toggleResolved($pdo, $_POST['id'] ?? 0);
            break;
            
        case 'toggle_ai_inclusion':
            $result = toggleAiInclusion($pdo, $_POST['id'] ?? 0);
            break;
            
        case 'get_employee_context':
            $result = getEmployeeContext($pdo, $_GET['employee_id'] ?? 0);
            break;
            
        case 'get_statistics':
            $result = getStatistics($pdo);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Récupérer la liste des notes avec filtres
 */
function getNotes($pdo, $filters) {
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['employee_id'])) {
        $where[] = 'employee_id = ?';
        $params[] = $filters['employee_id'];
    }
    
    if (!empty($filters['type'])) {
        $where[] = 'note_type = ?';
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['severity'])) {
        $where[] = 'severity = ?';
        $params[] = $filters['severity'];
    }
    
    if (isset($filters['include_in_ai'])) {
        $where[] = 'include_in_ai_analysis = ?';
        $params[] = $filters['include_in_ai'] ? 1 : 0;
    }
    
    if (!empty($filters['date_start'])) {
        $where[] = 'date_incident >= ?';
        $params[] = $filters['date_start'];
    }
    
    if (!empty($filters['date_end'])) {
        $where[] = 'date_incident <= ?';
        $params[] = $filters['date_end'];
    }
    
    $sql = "
        SELECT 
            en.*,
            u.full_name as employee_name,
            creator.full_name as created_by_name
        FROM employee_notes en
        INNER JOIN users u ON en.employee_id = u.id
        LEFT JOIN users creator ON en.created_by = creator.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY en.date_incident DESC, en.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupérer une note spécifique
 */
function getNote($pdo, $id) {
    $sql = "
        SELECT 
            en.*,
            u.full_name as employee_name,
            creator.full_name as created_by_name
        FROM employee_notes en
        INNER JOIN users u ON en.employee_id = u.id
        LEFT JOIN users creator ON en.created_by = creator.id
        WHERE en.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Créer une nouvelle note
 */
function createNote($pdo, $data, $user_id) {
    $sql = "
        INSERT INTO employee_notes (
            employee_id, note_type, title, description, date_incident,
            severity, is_resolved, is_private, include_in_ai_analysis, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['employee_id'],
        $data['note_type'],
        $data['title'],
        $data['description'],
        $data['date_incident'] ?? date('Y-m-d'),
        $data['severity'] ?? 'info',
        $data['is_resolved'] ?? 0,
        $data['is_private'] ?? 1,
        $data['include_in_ai_analysis'] ?? 1,
        $user_id
    ]);
    
    return ['id' => $pdo->lastInsertId(), 'message' => 'Note créée avec succès'];
}

/**
 * Mettre à jour une note
 */
function updateNote($pdo, $data, $user_id) {
    $sql = "
        UPDATE employee_notes SET
            employee_id = ?,
            note_type = ?,
            title = ?,
            description = ?,
            date_incident = ?,
            severity = ?,
            is_resolved = ?,
            is_private = ?,
            include_in_ai_analysis = ?
        WHERE id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['employee_id'],
        $data['note_type'],
        $data['title'],
        $data['description'],
        $data['date_incident'],
        $data['severity'],
        $data['is_resolved'] ?? 0,
        $data['is_private'] ?? 1,
        $data['include_in_ai_analysis'] ?? 1,
        $data['id']
    ]);
    
    return ['message' => 'Note modifiée avec succès'];
}

/**
 * Supprimer une note
 */
function deleteNote($pdo, $id) {
    $sql = "DELETE FROM employee_notes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    return ['message' => 'Note supprimée avec succès'];
}

/**
 * Basculer le statut résolu
 */
function toggleResolved($pdo, $id) {
    $sql = "UPDATE employee_notes SET is_resolved = NOT is_resolved WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    return ['message' => 'Statut modifié'];
}

/**
 * Basculer l'inclusion dans l'IA
 */
function toggleAiInclusion($pdo, $id) {
    $sql = "UPDATE employee_notes SET include_in_ai_analysis = NOT include_in_ai_analysis WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    return ['message' => 'Inclusion IA modifiée'];
}

/**
 * Récupérer le contexte formaté pour l'IA d'un employé
 */
function getEmployeeContext($pdo, $employee_id) {
    $sql = "
        SELECT *
        FROM employee_notes
        WHERE employee_id = ?
        AND include_in_ai_analysis = 1
        ORDER BY severity DESC, date_incident DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notes)) {
        return ['context' => '', 'notes_count' => 0];
    }
    
    // Formater pour l'IA
    $context = "CONTEXTE MANAGÉRIAL POUR CET EMPLOYÉ :\n\n";
    
    $severity_labels = [
        'info' => 'INFO',
        'low' => 'GRAVITÉ FAIBLE',
        'medium' => 'GRAVITÉ MOYENNE',
        'high' => 'GRAVITÉ ÉLEVÉE',
        'critical' => 'GRAVITÉ CRITIQUE'
    ];
    
    $type_labels = [
        'avertissement' => 'AVERTISSEMENT',
        'incident' => 'INCIDENT',
        'appreciation' => 'APPRÉCIATION',
        'remarque' => 'REMARQUE',
        'sanction' => 'SANCTION',
        'autre' => 'AUTRE'
    ];
    
    foreach ($notes as $note) {
        $type = strtoupper($type_labels[$note['note_type']] ?? $note['note_type']);
        $severity = $severity_labels[$note['severity']] ?? $note['severity'];
        $date = date('d/m/Y', strtotime($note['date_incident']));
        $resolved = $note['is_resolved'] ? ' [RÉSOLU]' : '';
        
        $context .= "[$type - $severity - $date]$resolved\n";
        $context .= $note['title'] . "\n";
        if (!empty ($note['description'])) {
            $context .= $note['description'] . "\n";
        }
        $context .= "\n";
    }
    
    return [
        'context' => $context,
        'notes_count' => count($notes),
        'notes' => $notes
    ];
}

/**
 * Statistiques des notes
 */
function getStatistics($pdo) {
    $sql = "
        SELECT 
            COUNT(*) as total_notes,
            COUNT(CASE WHEN note_type = 'avertissement' THEN 1 END) as nb_avertissements,
            COUNT(CASE WHEN note_type = 'incident' THEN 1 END) as nb_incidents,
            COUNT(CASE WHEN note_type = 'appreciation' THEN 1 END) as nb_appreciations,
            COUNT(CASE WHEN severity IN ('high', 'critical') THEN 1 END) as nb_graves,
            COUNT(CASE WHEN is_resolved = 1 THEN 1 END) as nb_resolus,
            COUNT(CASE WHEN include_in_ai_analysis = 1 THEN 1 END) as nb_in_ai
        FROM employee_notes
    ";
    
    $stmt = $pdo->query($sql);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
